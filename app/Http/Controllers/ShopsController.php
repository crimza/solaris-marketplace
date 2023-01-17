<?php

namespace App\Http\Controllers;

use App\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View as FacadeView;
use Illuminate\View\View;

class ShopsController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        FacadeView::share('page', 'shops');
    }

    /**
     * @param Request $request Объект запроса.
     *
     * @return View
     */
    public function index(Request $request): View
    {
        $shops = Shop::select(DB::raw('shops.*, avg(goods.rating) as avg_rate, avg(goods.reviews_count) as avg_reviews'))
            ->join('goods', 'shops.app_id', '=', 'goods.app_id')
            ->where('shops.enabled', true)
            ->groupBy('shops.app_id', 'shops.id')
            ->orderBy('shops.last_sync_at', 'desc')
            ->orderBy('avg_reviews', 'desc')
            ->orderBy('avg_rate', 'desc')
            ->applySearchFilters($request)
            ->paginate(24);

        return view('shops.index', ['shops' => $shops]);
    }
}
