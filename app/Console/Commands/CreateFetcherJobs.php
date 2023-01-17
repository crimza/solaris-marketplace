<?php

namespace App\Console\Commands;

use App\Good;
use App\Jobs\FetchImages;
use App\Order;
use App\Shop;
use Illuminate\Console\Command;

class CreateFetcherJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'catalog:fetcher_jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $shops = Shop::whereEnabled(true)->where('image_cached', false)->get();
        foreach ($shops as $shop) {
            dispatch(new FetchImages($shop));
        }

        $goods = Good::with(['shop'])->where('image_cached', false)->get();
        foreach ($goods as $good) {
            dispatch(new FetchImages($good));
        }

        $orders = Order::with(['shop'])->where('good_image_cached', false)->get();
        foreach ($orders as $order) {
            dispatch(new FetchImages($order));
        }
    }
}
