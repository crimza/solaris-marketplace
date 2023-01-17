<?php

namespace App\Console\Commands;

use App\Integrations\CryptoPaymentPlatform\ApiClient;
use App\Packages\Loggers\CryptoPaymentPlatformLogger;
use App\Packages\Utils\BitcoinUtils;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;


class UpdateBitcoinRates extends Command
{
    use PrependsOutput, PrependsTimestamp;

    /**
     * @var Client
     */
    protected $client;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:update_rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Bitcoin rates.';

    /**
     * Create a new command instance.
     *
     * @param Client $client
     * @param CryptoPaymentPlatformLogger $log
     */
    public function __construct(Client $client, CryptoPaymentPlatformLogger $log)
    {
        $this->log = $log;
        $this->client = $client;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($attempt = 0)
    {
        $attempt++;
        $this->log->info('Updating Bitcoin rates (attempt #' . $attempt . ')');

        $api = app(ApiClient::class);
        $currencyRates = $api->getCurrencyRate();
        $response = $currencyRates['btc'] ?? [];

        try {
            if (!isset($response['usd'])) throw new \AssertionError('Property USD not exists.');
            if (!isset($response['rub'])) throw new \AssertionError('Property RUB not exists.');
        } catch (\AssertionError $e) {
            $this->log->warning('Rates are not updated: ' . $e);

            if ($attempt < 3)
            {
                sleep(10);
                return $this->handle($attempt);
            }

            if (!BitcoinUtils::isPaymentsEnabled()) {
                $this->log->warning('!!!! Payments are marked as disabled.');
            }
            $this->log->info('Finished updating Bitcoin rates.');
            return null;
        }

        $expiresAt = Carbon::now()->addMinutes(config('catalog.rates_cache_expires_at'));
        Cache::put('rates_usd', $response['usd'], $expiresAt);
        Cache::put('rates_rub', $response['rub'], $expiresAt);
        $this->log->info('Finished updating Bitcoin rates.', ['usd' => $response['usd'], 'rub' => $response['rub']]);
    }
}
