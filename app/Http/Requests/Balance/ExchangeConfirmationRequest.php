<?php

namespace App\Http\Requests\Balance;

use App\Http\Requests\Request;
use App\Packages\Utils\BitcoinUtils;

/**
 * @property $amount
 * @property string $currency
 */
class ExchangeConfirmationRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'currency' => 'required|in:' . implode(',', [BitcoinUtils::CURRENCY_RUB, BitcoinUtils::CURRENCY_USD, BitcoinUtils::CURRENCY_BTC]),
            'amount' => 'required|numeric|min:0.000001'
        ];
    }
}
