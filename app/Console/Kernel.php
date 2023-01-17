<?php

namespace App\Console;

use App\Console\Commands\AdvStatsDropCache;
use App\Console\Commands\CalculateStats;
use App\Console\Commands\Cleanup;
use App\Console\Commands\CreateFetcherJobs;
use App\Console\Commands\EmulateRequests;
use App\Console\Commands\Init;
use App\Console\Commands\CacheRemove;
use App\Console\Commands\InitCryptoPaymentSystemDomain;
use App\Console\Commands\InitCryptoPaymentSystemUserAccounts;
use App\Console\Commands\RabbitConsumer;
use App\Console\Commands\UpdateBitcoinBlockCount;
use App\Console\Commands\UpdateBitcoinRates;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CreateFetcherJobs::class,
        UpdateBitcoinRates::class,
        Cleanup::class,
        CalculateStats::class,
        Init::class,
        AdvStatsDropCache::class,
        CacheRemove::class,
        InitCryptoPaymentSystemDomain::class,
        InitCryptoPaymentSystemUserAccounts::class,
        RabbitConsumer::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('catalog:cleanup')->everyTenMinutes();
        $schedule->command('catalog:update_rates')->everyFiveMinutes()->appendOutputTo(storage_path() . '/logs/rates.log');
        $schedule->command('catalog:fetcher_jobs')->everyFiveMinutes();
        $schedule->command('catalog:calculate_stats')->daily();
        $schedule->command('catalog:advstats_drop_cache')->everyTenMinutes();
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
