<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Shop;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        $shops = Cache::remember('index', 20, function () {
            return Shop::available()->inRandomOrder()->limit(24)->get();
        });

        return view('home', ['shops' => $shops]);
    }

    /**
     * @return View
     */
    public function advert(): View
    {
        return view('advert.index');
    }
}
