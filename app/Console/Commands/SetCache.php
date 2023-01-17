<?php

namespace App\Console\Commands;

use App\GoodsCity;
use App\GoodsPackage;
use App\Packages\Loggers\CacheLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SetCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:cache {which?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    protected $log;


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->log = (new CacheLogger());
        $start = memory_get_usage(true);

        if(config('app.debug')) {
            $this->log->info('[start] memory usage: '.round($start / 1024 / 1024) . " MiB");
        }

        switch ($this->argument('which')) {
            case 'goods_packages':
                $prefix = "goods packages";
                $this->setGoodsPackages();

                if(config('app.debug')) {
                    $mem = round((memory_get_usage(true)) / 1024 / 1024) . " MiB";
                }

                break;

            case 'goods_cities':
                $prefix = "goods cities";
                $this->setGoodsCities();

                if(config('app.debug')) {
                    $mem = round((memory_get_usage(true)) / 1024 / 1024) . " MiB";
                }

                break;

            default:
                return 0;
        }

        if(config('app.debug') && isset($mem)) {
            $this->log->debug("[$prefix] memory usage: " . $mem);
        }

        $this->log->info("[peak] memory usage: " . round(memory_get_peak_usage(true) / 1024 / 1024) . " MiB\n");

        return 0;
    }

    private function setGoodsPackages()
    {
        (new GoodsPackage)->orderBy('id')->chunk(config('catalog.cache.chunk_size.goods_packages', 1000), function ($goodsPackages) {
             $this->log->debug("[gp chunk pre] (first id: ".($goodsPackages->first()->id).") memory usage: " . round((memory_get_usage(true)) / 1024 / 1024) . " MiB");

            foreach ($goodsPackages as $goodsPackage) {
                $key = "goods_packages:$goodsPackage->app_id:$goodsPackage->app_good_id:$goodsPackage->app_package_id";
                Cache::put($key, $goodsPackage, config('catalog.cache.valid_time.goods_packages'));
            }

            $this->log->debug("[gp chunk post] (last id: ".($goodsPackages->last()->id).") memory usage: " . round((memory_get_usage(true)) / 1024 / 1024) . " MiB");
        });
    }

    private function setGoodsCities()
    {
        (new GoodsCity)->orderBy('id')->chunk(config('catalog.cache.chunk_size.goods_cities', 1000), function ($goodsCities) {
             $this->log->debug("[gc chunk pre] (first id: ".($goodsCities->first()->id).") memory usage: " . round((memory_get_usage(true)) / 1024 / 1024) . " MiB");

            foreach ($goodsCities as $goodsCity) {
                $key = "goods_cities:$goodsCity->app_id:$goodsCity->app_good_id:$goodsCity->city_id";
                Cache::put($key, $goodsCity, config('catalog.cache.valid_time.goods_cities'));
            }

            $this->log->debug("[gc chunk post] (last id: ".($goodsCities->last()->id).") memory usage: " . round((memory_get_usage(true)) / 1024 / 1024) . " MiB");
        });
    }
}
