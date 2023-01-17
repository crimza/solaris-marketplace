<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Integrations\CryptoPaymentPlatform\ApiClient;

class InitCryptoPaymentSystemDomain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cpp:init-domain';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Init new domain for the CPP system';

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
        $api = app(ApiClient::class);
        $name = $this->ask('Domain name only latin symbols');

        $domain = $api->createDomain($name);

        if(isset($domain['id'])) {
            $path = base_path('.env');

            if (file_exists($path)) {
                file_put_contents($path, str_replace(
                    'CPP_DOMAIN=' . env('CPP_DOMAIN'), 'CPP_DOMAIN=' . $domain['id'], file_get_contents($path)
                ));
            }

            $api->setDomain($domain['id']);
            $wallet = $api->createWallet();

            if(isset($wallet['id'])) {
                file_put_contents($path, str_replace(
                    'CPP_MASTER_ACCOUNT=' . env('CPP_MASTER_ACCOUNT'), 'CPP_MASTER_ACCOUNT=' . $wallet['id'], file_get_contents($path)
                ));
            } else {
                $this->warn('Domain was not init. Response data: ' . print_r($wallet,1));
            }
        } else {
            $this->warn('Domain was not init. Response data: ' . print_r($domain,1));
        }
    }
}
