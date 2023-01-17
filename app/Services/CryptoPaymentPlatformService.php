<?php

namespace App\Services;

use App\Integrations\CryptoPaymentPlatform\ApiClient;
use App\User;
use Illuminate\Support\Facades\Cache;

class CryptoPaymentPlatformService
{
    const PAGE_LIMIT = 10;

    /**
     * @var ApiClient
     */
    private ApiClient $client;

    /**
     * @var User
     */
    private User $user;

    /**
     * @param  User  $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->client = app(ApiClient::class);
    }

    /**
     * @param  int  $page
     * @param  bool  $cached
     * @return array
     */
    public function getHistory(int $page = 0, bool $cached = false) : array
    {
        if(!$cached){
            $history = $this->client->getHistory($this->user->wallet_id, self::PAGE_LIMIT * $page, self::PAGE_LIMIT);
        } else {
            $history = Cache::get('history_' . $this->user->wallet_id . '_' . $page, []);
            if(!$history) {
                $history = $this->client->getHistory($this->user->wallet_id, self::PAGE_LIMIT * $page, self::PAGE_LIMIT);
                Cache::put('history_' . $this->user->wallet_id . '_' . $page, $history, 350);
            }
        }

        $total = $history['total'] ?? 0;

        $result = [
            'result' => $history['result'] ?? [],
            'pages' => ceil($total / self::PAGE_LIMIT),
        ];

        return $result;
    }

}