<?php

namespace App\Console\Commands;

use App\AdvStats;
use App\AdvStatsCache;
use Illuminate\Console\Command;

class AdvStatsDropCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:advstats_drop_cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop AdvStats cache to database';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $stats = AdvStats::all();
        $cache = AdvStatsCache::get();

        foreach ($stats as $stat) {
            if (!isset($cache[$stat->id])) {
                continue;
            }

            $cached = collect($cache[$stat->id]);

            if(!$cached->has('views') && !$cached->has('registrations') && !$cached->has('uniques')) {
                continue;
            }

            $stat->views += $cached->get('views');
            $stat->uniques += $cached->get('uniques');
            $stat->registrations += $cached->get('registrations');
            $stat->save();
        }

        AdvStatsCache::flush();
    }
}
