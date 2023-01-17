<?php

namespace App\Console\Commands;

use App\Integrations\CryptoPaymentPlatform\ApiClient;
use App\User;
use Illuminate\Console\Command;

class InitCryptoPaymentSystemUserAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cpp:init-users-wallet';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Init wallet for all users';

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

        do {
            $users = User::whereNull('wallet_id')->limit(500)->get();
            $count = count($users);

            foreach($users as $user) {
                $wallet = $api->createWallet();

                if(!isset($wallet['error']) && isset($wallet['id'])) {
                    $user->wallet_id = $wallet['id'];
                    $user->save();
                } else {
                    $this->warn('Wallet was not init. Response data: ' . print_r($wallet,1));
                }
            }
        } while($count != 0);
    }
}
