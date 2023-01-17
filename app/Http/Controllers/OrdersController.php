<?php
/**
 * File: OrdersController.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;


use Auth;
use Illuminate\Http\Request;
use View;

class OrdersController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        View::share('page', 'orders');
    }


    public function index(Request $request)
    {
        $orders = Auth::user()->orders()
            ->applySearchFilters($request)
            ->with(['shop'])
            ->orderBy('app_created_at', 'desc')
            ->paginate(20);

        return view('orders.index', [
            'orders' => $orders
        ]);
    }

    public function order($orderId)
    {
        $order = Auth::user()->orders()->findOrFail($orderId);
        return view('orders.order', [
            'order' => $order
        ]);
    }
}