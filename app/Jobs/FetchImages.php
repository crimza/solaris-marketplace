<?php

namespace App\Jobs;

use App\FetchedImage;
use App\Good;
use App\Order;
use App\Packages\Loggers\ImageFetcherLogger;
use App\Shop;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class FetchImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Shop|Good|Order
     */
    protected $model;

    /**
     * @var boolean
     */
    protected bool $break = false;

    /**
     * @var ImageFetcherLogger
     */
    protected ImageFetcherLogger $log;

    /**
     * @var float
     */
    private $start_at;

    /**
     * Create a new job instance.
     *
     * @param Shop|Good|Order $model
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->log = (new ImageFetcherLogger());

        if (($image = FetchedImage::whereAppId($this->model->app_id)
                ->whereRemoteUrl($this->model->remoteImageURL())->first()) !== null) {
            $this->model->{$this->model->localImageURLColumn()} = $image->local_url;
            $this->model->{$this->model->localImageCachedColumn()} = true;
            $this->model->save();
            $this->break = true;
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle($attempt = 0)
    {
        $this->start_at = microtime(true);

        if ($this->break) {
            return;
        }

        // set image folder and get shop gate data
        if ($this->model instanceof Shop) {
            $shop = $this->model;
            $folder = 'shops';
        } else {
            $shop = $this->model->shop;
            $folder = 'goods';
        }

        if(!$shop) {
            return;
        }

        // default url is *.onion url
        $url = $this->model->remoteImageURL();

        // default curl options
        $options = [
            'headers' => [
                'X-DDoS-Bypass' => true,
                'X-Guard-Bypass' => true,
            ],
            'timeout' => config('catalog.guzzle_onion_image_fetch_timeout', 10),
        ];

        // try to load image from lan first time
        if($shop->gate_enabled && $attempt < 3) {
            $url = $this->model->lanImageURL($shop->gate_lan_ip, $shop->gate_lan_port);
            $options['timeout'] = config('catalog.guzzle_local_image_fetch_timeout', 5);
        } else {
            // old single-client proxy
            $prxStr = sprintf('socks5h://%s:%d', config('catalog.tord_host'), config('catalog.tord_port'));

            // or random proxy from list if set
            if(!empty($tor_hosts = config('catalog.tor_hosts')) && Str::contains($tor_hosts, ',')) {
                $proxyList = explode(",", $tor_hosts);

                if (count($proxyList) - 1 > 0) {
                    if(strlen($newPrxStr = trim($proxyList[mt_rand(0, count($proxyList) - 1)])) >= 7) {
                        $prxStr = $newPrxStr;
                    }
                }
            }

            if(!$prxStr) {
                return;
            }

            $options['proxy'] = ['http' => $prxStr, 'https' => $prxStr];
            $options['curl'] = [CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5_HOSTNAME];
        }

        $reasonException = null;
        $attempt++;
        $this->client = new Client($options);
        $msg = sprintf("> %-20s %-15s %s", $shop->app_id, "downloading", $url);
        $this->log->debug($msg);
        $start_at = microtime(true);

        try {
            $this->log->debug(sprintf("> download url: %-60s; options: %s", $url, print_r($options, 1)));
            $response = $this->client->request('GET', $url);
            $download_time = microtime(true) - $start_at;
        } catch (Exception $e) {
            $download_time = microtime(true) - $start_at;
            $msg = sprintf("> %-20s [%.2f %s :: %.2f %s] %-15s %s:\n%s", $shop->app_id, $download_time,
                "(s) download", microtime(true)-$this->start_at, "(s) all", "load error", $url, $e->getTraceAsString());
            $this->log->error($msg);

            if ($attempt < 5) {
                sleep(5);
                $this->handle($attempt);
                return;
            }

            $response = null;
            $reasonException = $e;
        }

        if (!$response) {
            throw new Exception('Failed to fetch image: ' . $reasonException->getMessage());
        }

        $image_ext = explode('.', $url);
        $image_ext = end($image_ext);
        $disk = Storage::disk('public');
        $path = $folder . '/' . Str::random(40) . '.' . $image_ext;

        $disk->put($path, $response->getBody());
        $localUrl = $disk->url($path);
        $this->model->{$this->model->localImageURLColumn()} = $localUrl;
        $this->model->{$this->model->localImageCachedColumn()} = true;
        $this->model->save();

        FetchedImage::create([
            'app_id' => $this->model->app_id,
            'remote_url' => $this->model->remoteImageURL(),
            'local_url' => $localUrl,
        ]);

        $msg = sprintf("> %-20s [%.2f %s :: %.2f %s] %-15s\n\tfrom%s to %s", $shop->app_id,
            $download_time, "(s) download", microtime(true)-$this->start_at, "(s) all", "download complete",
            $url, $localUrl);
        $this->log->info($msg);
    }
}
