<?php

namespace App\Http\Controllers\Admin;

use App\Stat;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatsController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            if(!policy(Stat::class)->index($request->user())) {
                abort(403);
            }

            \View::share('category', 'stats');
            \View::share('cats', $this->getAdminCategories($request->user()));
            return $next($request);
        });
    }

    public function index()
    {
        $stats = Stat::where('date', '>=', Carbon::now()->addDays(-16))
            ->orderBy('date')
            ->get();

        $margin = 0.05; // % for top and bottom spacing
        $maxUsers = $stats->max('visitors_count') * (1 + $margin);
        $minUsers = $stats->min('visitors_count') * (1 - $margin);
        $maxOrders = $stats->max('orders_count') * (1 + $margin);
        $minOrders = $stats->min('orders_count') * (1 - $margin);

        $usersGraph = [];
        $ordersGraph = [];
        for ($i = 1; $i < $stats->count(); $i++) {
            $currentDay = $stats[$i];
            $prevDate = $stats[$i - 1];
            $usersDiffCount = $maxUsers - $minUsers;
            $usersGraph[] = [
                'date' => Carbon::createFromFormat('Y-m-d', $currentDay->date),
                'start' => ($prevDate->visitors_count - $minUsers) / ($usersDiffCount > 0 ? $usersDiffCount : 1), // давайте больше не будем делить на 0, пожалуйста?
                'size' => ($currentDay->visitors_count - $minUsers) / ($usersDiffCount > 0 ? $usersDiffCount : 1),
                'value' => $currentDay->visitors_count
            ];

            $ordersDiffCount = $maxOrders - $minOrders;
            $ordersGraph[] = [
                'date' => Carbon::createFromFormat('Y-m-d', $currentDay->date),
                'start' => ($prevDate->orders_count - $minOrders) / ($ordersDiffCount > 0 ? $ordersDiffCount : 1),
                'size' => ($currentDay->orders_count - $minOrders) / ($ordersDiffCount > 0 ? $ordersDiffCount : 1),
                'value' => $currentDay->orders_count
            ];
        }

        return view('admin.stats', [
            'stats' => $stats,
            'usersGraph' => $usersGraph,
            'ordersGraph' => $ordersGraph
        ]);
    }
}