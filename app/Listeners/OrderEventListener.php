<?php

namespace App\Listeners;

use App\Events\OrderFinished;
use App\Order;
use App\Packages\Utils\BitcoinUtils;

class OrderEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param OrderFinished $event
     * @return void
     */
    public function handle(OrderFinished $event)
    {
        /** @var Order $order */
        $order = $event->order;

        if ($order->user) {
            $order->user->lockForUpdate();
            $order->user->buy_count += 1;
            $orderRubSum = BitcoinUtils::convert($order->package_price, $order->package_currency, BitcoinUtils::CURRENCY_RUB);
            if ($orderRubSum !== '-') {
                $order->user->buy_sum += $orderRubSum;
            }
            $order->user->save();
        }
    }
}
