<?php

namespace App\Http\Controllers\Admin;

use App\City;
use App\Good;
use App\Order;
use App\User;
use Illuminate\Http\Request;

class OrdersController extends AdminController
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        $category = $request->get('category', self::ADMIN_CATEGORY_ORDERS);
        $title = $this->admin_categories->get($category, self::ADMIN_CATEGORY_ORDERS);
        $data = ['title' => $title, 'cats' => $this->admin_categories, 'category' => $category];
        $data['orders'] = Order::paginate(self::PER_PAGE);
        $users = [];
        $cities = [];

        foreach ($data['orders'] as $order) {
            if (!in_array($order->user_id, $users)) {
                $users[] = $order->user_id;
            }

            if (!in_array($order->city_id, $cities)) {
                $cities[] = $order->city_id;
            }
        }

        $usersArray = (new User)->findMany($users);
        $data['users'] = [];

        unset($users);

        foreach ($usersArray as $user) {
            $data['users'][$user->id] = $user->username;
        }

        unset($usersArray);

        $citiesArray = (new City)->findMany($cities);
        $data['cities'] = [];

        unset($cities);

        foreach ($citiesArray as $city) {
            $data['cities'][$city->id] = $city->title;
        }

        unset($citiesArray);

        $data['orders']->withPath($category);

        return view('admin.index', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function getEdit(Request $request)
    {
        $order_id = $request->get('id');
        $order = (new Order)->find($order_id);

        if ($order_id && $order) {
            $data = [
                'title' => 'Editing order',
                'orders' => $order,
                'cats' => $this->admin_categories,
                'category' => self::ADMIN_CATEGORY_ORDERS,
                'order' => new Order,
                'users' => (new User)->all(),
                'cities' => (new City)->all(),
                'goods' => (new Good)->all(),
                'good' => $order->_stub_good(),
                'package' => $order->_stub_package()
            ];

            return view('admin.edit', $data);
        } else {
            return redirect('/admin/orders');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postUpdate(Request $request)
    {
        $id = $request->get('id');
        $order = (new Order)->find($id);

        if ($id && $order) {
            $this->validate($request, ['good_title' => 'max:255',]);
            $postData = collect($request->except('_token'));

            foreach ($postData as $k => $v) $order->$k = $v;

            $order->save();

            return redirect('/admin/edit_orders?id=' . $order->id, 303)->with('flash_success', 'Order successfully updated.');
        } else {
            return redirect('/admin/orders');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getCreate(Request $request)
    {
        // TODO position_id, review_id, app_id, app_order_id, app_good_id, good_image_url_local?
        $data = [
            'title' => 'Adding order',
            'cats' => $this->admin_categories,
            'category' => $request->get('category', self::ADMIN_CATEGORY_ORDERS),
            'order' => new Order,
            'users' => (new User)->all(),
            'cities' => (new City)->all(),
            'goods' => (new Good)->all()
        ];

        return view('admin.components.orders.add', $data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postStore(Request $request)
    {
        $this->validate($request, ['good_title' => 'max:255',]);
        $order = new Order;
        $postData = collect($request->except('_token'));

        foreach ($postData as $k => $v) $order->$k = $v;

        $order->save();

        return redirect('/admin/edit_orders?id=' . $order->id, 303)->with('flash_success', 'Order successfully added.');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function getDestroy(Request $request)
    {
        $id = $request->get('id');
        $order = (new Order)->find($id);

        if (!$id || !$order) return redirect('/admin/orders');

        if (Order::destroy($id)) {
            $what_removed = 'Order deleted.';
            $flash_type = 'flash_success';
        } else {
            $what_removed = 'Failed to delete order.';
            $flash_type = 'flash_warning';
        }

        return redirect('/admin/orders', 303)->with($flash_type, $what_removed);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function getView(Request $request)
    {
        $order_id = $request->get('id');
        $order = (new Order)->find($order_id);

        if ($order_id && $order) {
            $data = [
                'title' => 'Viewing order',
                'orders' => $order,
                'cats' => $this->admin_categories,
                'category' => self::ADMIN_CATEGORY_ORDERS,
                'order' => $order,
                'users' => (new User)->all(),
                'cities' => (new City)->all(),
                'goods' => (new Good)->all(),
                'good' => $order->_stub_good(),
                'package' => $order->_stub_package()
            ];

            return view('admin.view', $data);
        } else {
            return redirect('/admin/orders');
        }
    }
}
