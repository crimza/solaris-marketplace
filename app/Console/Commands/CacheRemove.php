<?php

namespace App\Console\Commands;

use App\Packages\Loggers\RemoveCacheLogger;
use App\User;
use Illuminate\Console\Command;

class CacheRemove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:remove {category}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * @var RemoveCacheLogger
     */
    protected $log;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->log = (new RemoveCacheLogger());
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        switch ($this->argument('category')) {
            case 'user-names-cache':
                $start = microtime(true);
                User::resetPublicCacheNames();
                $this->log->info("Users cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'admin':
                $start = microtime(true);
                User::resetIsAdminsCache();
                $this->log->info("Admin cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-categories':
                $start = microtime(true);
                User::resetPolicyCategoriesCache();
                $this->log->info("Category policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-cities':
                $start = microtime(true);
                User::resetPolicyCitiesCache();
                $this->log->info("Cities policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-goods':
                $start = microtime(true);
                User::resetPolicyGoodsAllCache();
                $this->log->info("Goods policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-news':
                $start = microtime(true);
                User::resetPolicyNewsAllCache();
                $this->log->info("News policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-regions':
                $start = microtime(true);
                User::resetPolicyRegionsCache();
                $this->log->info("Regions policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-shops':
                $start = microtime(true);
                User::resetPolicyShopsCache();
                $this->log->info("Shops policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-stats':
                $start = microtime(true);
                User::resetPolicyStatsCache();
                $this->log->info("Shops policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-tickets':
                $start = microtime(true);
                User::resetPolicyTicketsCache();
                $this->log->info("Shops policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            case 'policy-users':
                $start = microtime(true);
                User::resetPolicyUsersCache();
                $this->log->info("Users policy cache reset in " . (sprintf("%.2f", microtime(true) - $start)) . "s.");
                break;

            default:
                # TODO: config/logging.php
                $this->log->error("invalid category");
                exit;
        }
    }
}
