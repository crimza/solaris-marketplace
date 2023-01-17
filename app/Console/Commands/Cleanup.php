<?php

namespace App\Console\Commands;

use App\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform cleaning tasks';

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
        // cleaning not ready pre-orders
        $orders = Order::where('status', Order::STATUS_PREORDER_PAID)
            ->where('updated_at', '<=', Carbon::now()->addHours(-config('catalog.preorder_close_time')))
            ->get();

        foreach ($orders as $order) {
                $order->delete();
        }
    }
}
