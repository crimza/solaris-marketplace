<?php
/**
 * File: BitcoinUtils.php
 * This file is part of MM2 project.
 * Do not modify if you do not know what to do.
 * 2016.
 */

namespace App\Packages\Utils;


use AssertionError;
use Illuminate\Support\Facades\Cache;

class BitcoinUtils
{
    #region Work with rates & currencies
    const CURRENCY_USD = 'usd';
    const CURRENCY_RUB = 'rub';
    const CURRENCY_BTC = 'btc';

    public function __construct()
    {
    }

    public static function getBlockCount()
    {
        return (int)Cache::get('rates_block_count', 0);
    }

    /**
     * Convers $amount USD to BTC.
     * @param float $amount
     * @return float
     */
    public static function usdToBtc($amount = 1.00)
    {
        return self::convert($amount, self::CURRENCY_USD, self::CURRENCY_BTC);
    }

    public static function convert($amount, $from, $to)
    {
        if (!self::isPaymentsEnabled()) {
            return '-';
        }

        if ($from === $to) {
            return $amount;
        }

        $fromRate = self::getRate($from);
        $toRate = self::getRate($to);

        return ($toRate / $fromRate) * $amount;
    }

    /**
     * Should return false if something is wrong with payments.
     * You can add future payments checks here.
     *
     * @return bool
     */
    public static function isPaymentsEnabled()
    {
        try {
            if (self::getRate(self::CURRENCY_RUB) === 0) throw new AssertionError('RUB rate is 0.');
            if (self::getRate(self::CURRENCY_USD) === 0) throw new AssertionError('USD rate is 0.');
        } catch (AssertionError $e) {
            return FALSE;
        }

        return TRUE;
    }

    public static function getRate($currency)
    {
        if ($currency === self::CURRENCY_BTC) {
            return 1;
        }

        return Cache::get('rates_' . $currency, 0);
    }

    /**
     * Converts $amount RUB to BTC
     *
     * @param float $amount
     * @return float
     */
    public static function rubToBtc($amount = 1.00)
    {
        return self::convert($amount, self::CURRENCY_RUB, self::CURRENCY_BTC);
    }

    /**
     * Converts $amount BTC to USD
     *
     * @param float $amount
     * @return float
     */
    public static function btcToUsd($amount = 1.00)
    {
        return self::convert($amount, self::CURRENCY_BTC, self::CURRENCY_USD);
    }
    #endregion

    #region Work with Bitcoind

    /**
     * Converts $amount BTC to RUB
     *
     * @param float $amount
     * @return float
     */
    public static function btcToRub($amount = 1.00)
    {
        return self::convert($amount, self::CURRENCY_BTC, self::CURRENCY_RUB);
    }

    /**
     * @param $amount
     * @return float
     */
    public static function prepareAmountToJSON($amount)
    {
        return (float)(round($amount, 8));
    }

    #endregion
}