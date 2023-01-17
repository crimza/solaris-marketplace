<?php
/**
 * File: BalanceController.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;


use App\ExternalExchange;
use App\Http\Requests\Balance\ExchangeConfirmationRequest;
use App\Http\Requests\Balance\ExchangeRequest;
use App\Integrations\CryptoPaymentPlatform\ApiClient;
use App\Packages\Utils\BitcoinUtils;
use Carbon\Carbon;
use Illuminate\Contracts\View\Factory;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use View;

class BalanceController extends Controller
{
    /** @var ApiClient */
    private $client;

    public function __construct()
    {
        parent::__construct();
        View::share('page', 'balance');
        $this->client = app(ApiClient::class);
    }

    public function index(Request $request)
    {
        $external_exchanges = Auth::user()->externalExchanges()
            ->where('created_at', '>=', Carbon::now()->addHours(-1))
            ->orderBy('created_at', 'desc')
            ->get();

        $exchange = [
            'rub' => BitcoinUtils::getRate(BitcoinUtils::CURRENCY_RUB),
            'usd' => BitcoinUtils::getRate(BitcoinUtils::CURRENCY_USD),
        ];

        $wallet_id = Auth::user()->wallet_id;
        $balance = [];
        $history = [];
        $address = '';

        if(!$request->page) {
            $page = 0;
        } else {
            $page = $request->page - 1;
        }

        if($wallet_id) {
            $balance = $this->client->getBalance($wallet_id);
            $address = $this->client->getPaymentAddress($wallet_id);
            $history = Auth::user()->initCryptoPaymentService()->getHistory($page, true);
        }

        return view('balance.index', [
            'exchange' => $exchange,
            'balance' => sprintf("%.8f", $balance['btc'] ?? 0),
            'address' => $address['btc'] ?? '',
            'operations' => $history,
            'current_page' => $request->page ?? 1,
            'exchanges' => $external_exchanges,
        ]);
    }

    /**
     * @param ExchangeRequest $request
     * @return Factory|Application|RedirectResponse|Redirector|\Illuminate\Support\Facades\View
     */
    public function exchange(ExchangeRequest $request)
    {
        $cacheKey = Auth::user()->username . '_exchange_attempts';
        if (Cache::has($cacheKey) && Cache::get($cacheKey) >= 2) {
            return redirect('/balance')
                ->with('flash_warning', __('management.exchange_order_is_not_expired'));
        }

        $exchange = [
            'rub' => BitcoinUtils::getRate(BitcoinUtils::CURRENCY_RUB),
            'usd' => BitcoinUtils::getRate(BitcoinUtils::CURRENCY_USD),
        ];

        $btcAmount = BitcoinUtils::convert(
            $request->amount,
            $request->currency,
            BitcoinUtils::CURRENCY_BTC
        );

        $exchanges = \Auth::user()->externalExchanges()
            ->where('created_at', '>=', Carbon::now()->addHours(-1))
            ->orderBy('created_at', 'desc')
            ->get();

        $wallet_id = Auth::user()->wallet_id;

        if(!$request->page) {
            $page = 0;
        } else {
            $page = $request->page - 1;
        }

        $balance = $this->client->getBalance($wallet_id);
        $address = $this->client->getPaymentAddress($wallet_id);
        $history = Auth::user()->initCryptoPaymentService()->getHistory($page, true);

        return view('balance.index', [
            'exchange' => $exchange,
            'operations' => $history,
            'exchanges' => $exchanges,
            'request' => $request,
            'btcAmount' => $btcAmount,
            'currency' => $request->currency,
            'amount' => $request->amount,
            'balance' => sprintf("%.8f", $balance['btc'] ?? 0),
            'address' => $address['btc'] ?? '',
            'current_page' => $request->page ?? 1,
        ]);
    }

    /**
     * @param ExchangeConfirmationRequest $request
     * @return Factory|Application|RedirectResponse|Redirector|\Illuminate\Support\Facades\View
     */
    public function exchangeConfirmation(ExchangeConfirmationRequest $request)
    {
        $cacheKey = Auth::user()->username . '_exchange_attempts';
        if (Cache::has($cacheKey) && Cache::get($cacheKey) >= 2) {
            return redirect('/balance')
                ->with('flash_warning', __('management.exchange_order_is_not_expired'));
        }

        $btcAmount = BitcoinUtils::convert(
            $request->amount,
            $request->currency,
            BitcoinUtils::CURRENCY_BTC
        );

        $paymentId = Str::random(32);
        $exchangeData = $this->exchangeData($paymentId, $request->get('amount'), $request->get('currency'));
        $exchangeRequestData = json_encode($exchangeData);
        $encrypter = new Encrypter(config('catalog.exchanges_encryption_key'), 'AES-256-CBC');

        ExternalExchange::create([
            'payment_id' => $paymentId,
            'user_id' => Auth::id(),
            'amount' => $btcAmount
        ]);

        $cacheKey = Auth::user()->username . '_exchange_attempts';
        Cache::add($cacheKey, 0, 15); // 15 minutes
        Cache::increment($cacheKey);

        return view('balance.exchange_confirmation', [
            'formAction' => config('catalog.exchanges_api_url') . '/api/v1/get_av_exchange',
            'data' => $encrypter->encryptString($exchangeRequestData)
        ]);
    }

    /**
     * @param $paymentId
     * @param $amount
     * @param $currency
     * @return array
     */
    private function exchangeData($paymentId, $amount, $currency): array
    {
        $btcAmount = BitcoinUtils::convert($amount, $currency, BitcoinUtils::CURRENCY_BTC);
        $wallet_id = Auth::user()->wallet_id;
        $address = $this->client->getPaymentAddress($wallet_id);

        return [
            'id' => $paymentId,
            'shop_id' => 'CATALOG',
            'btc_address' => $address['btc'],
            'user_id' => (string)Auth::id(),
            'user_name' => Auth::user()->username,
            'amount' => $btcAmount
        ];
    }

    /**
     * @param Request $request
     * @param $paymentId
     * @return Factory|Application|View
     */
    public function redirectToExchange(Request $request, $paymentId)
    {
        $exchange = \Auth::user()->externalExchanges()
            ->where('payment_id', $paymentId)
            ->where('created_at', '>=', Carbon::now()->addHours(-1))
            ->firstOrFail();
        $exchangeData = $this->exchangeData($paymentId, $exchange->amount, BitcoinUtils::CURRENCY_BTC);
        $exchangeRequestData = json_encode($exchangeData);
        $encrypter = new Encrypter(config('catalog.exchanges_encryption_key'), 'AES-256-CBC');

        return view('balance.exchange_confirmation', [
            'formAction' => config('catalog.exchanges_api_url') . '/api/v1/get_av_exchange',
            'data' => $encrypter->encryptString($exchangeRequestData)
        ]);
    }
}