<?php

namespace App\Console\Commands;

use App\User;
use Artisan;
use Illuminate\Console\Command;
use PDOException;

class Init extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations, cache routes and optimize work. Runs only by docker.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Running artisan view:clear ...');
        Artisan::call('view:clear');
        $this->info('Running artisan config:cache ...');
        Artisan::call('config:cache');
        $this->info('Running artisan route:cache ...');
        Artisan::call('route:cache');
        $this->info('Running artisan optimize ...');
        Artisan::call('optimize');
        $this->info('Running queue:restart');
        Artisan::call('queue:restart');

        $this->info('Waiting for database ...');
        $usersCount = null;
        for ($i = 1; $i <= 10; $i++) {
            try {
                $usersCount = User::count();
            } catch (PDOException $e) {
                $this->info('Database is not ready yet, waiting for 5 seconds ...');
                sleep(5);
                continue;
            }
        }

        if ($usersCount === null) {
            $this->info('Failed to connect.');
            exit(1);
        }

        $this->info('Database is ready.');
        $this->info('Running artisan migrate ...');
        Artisan::call('migrate', [
            '--force' => true
        ]);

        if ($usersCount === 0) { // first launch
            $this->info('First catalog launch: initialisation.');
            $requiredVariables = [
                'APP_ID', 'APP_TITLE', 'APP_HEADER_TITLE', 'SYNC_SERVER', 'AUTH_SERVER'
            ];

            foreach ($requiredVariables as $variable) {
                if (env($variable, 'NOTSET') === 'NOTSET') {
                    $this->error('FATAL: ' . $variable . ' IS NOT SET');
                    exit(1);
                }
            }

            $this->info('Linking storage ...');
            Artisan::call('storage:link');

            $this->info('Catalog initialisation complete.');
        }

        $this->info('All tasks are completed!');
    }
}