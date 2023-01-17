<?php

namespace App\Integrations\CryptoPaymentPlatform;

use App\Packages\Loggers\CryptoPaymentPlatformLogger;
use Exception;
use GuzzleHttp\Client;

class ApiClient
{
    private $host;

    private $port;

    private $domain;

    private $client;

    private $logger;

    public function __construct(Client $client, CryptoPaymentPlatformLogger $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->host = config('cpp.cpp_host');
        $this->port = config('cpp.cpp_port');
        $this->domain = config('cpp.cpp_domain');
    }

    /**
     * It allows to set domain in case domain is not yet set in config when object was created.
     *
     * @param int $domain
     * @return void
     */
    public function setDomain(int $domain)
    {
        if (!$this->domain) {
            $this->domain = $domain;
        }
    }

    /**
     * Init new domain
     *
     * @param string $name
     * @return array
     */
    public function createDomain(string $name): array
    {
        return $this->sendRequest(
            '/domains',
            'post',
            [
                'name' => $name,
                'coins' => ["btc"]
            ]
        );
    }

    /**
     * Create wallet in the current domain. If the domain is not exist return error
     *
     * @return array
     */
    public function createWallet(): array
    {
        return $this->domain ? $this->sendRequest(
            '/accounts',
            'post',
            [
                'domain_id' => $this->domain,
            ]
        ) : ['error' => 'Domain has not been init'];
    }

    /**
     * Get user's wallet balance
     *
     * @param int $wallet
     * @return array
     */
    public function getBalance(int $wallet): array
    {
        return $this->sendRequest(
            '/accounts/' . $wallet . '/balance',
            'get',
            [
                'virtual' => "true",
            ]
        );
    }

    /**
     * Get payment address for wallet
     *
     * @param int $wallet
     * @param string $currency
     * @return array
     */
    public function getPaymentAddress(int $wallet, string $currency = 'btc'): array
    {
        return $this->sendRequest(
            '/accounts/' . $wallet . '/address',
            'get',
            [
                'coin' => $currency
            ]
        );
    }

    /**
     * Get user's wallet balance
     *
     * @param  int  $wallet
     * @param  int  $skip
     * @param  int  $take
     * @return array
     */
    public function getHistory(int $wallet, int $skip, int $take) : array
    {
        return $this->sendRequest(
            '/accounts/' . $wallet . '/history',
            'get',
            [
                'skip' => $skip,
                'take' => $take,
            ]
        );
    }

    /**
     * Get currency rate
     *
     * @return array
     */
    public function getCurrencyRate(): array
    {
        return $this->sendRequest('/prices', 'GET', ['coin' => 'btc']);
    }

    private function sendRequest(string $path, string $method, array $data = []): array
    {
        try {
            $response = $this->client->$method(
                $this->host . ':' . $this->port . $path . '?' . http_build_query($data), ['timeout' => 15]
            );
        } catch (Exception $e) {
            $this->logger->error('', ['data' => $data, 'response' => $e->getMessage()]);

            return ['error' => $e->getMessage()];
        }

        $content = $response->getBody()->getContents();
        $this->logger->info('', ['data' => $data, 'response' => $content]);

        return json_decode($content, true) ?? [];
    }

}