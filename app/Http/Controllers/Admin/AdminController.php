<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 18.09.2018
 * Time: 3:40
 */

namespace App\Http\Controllers\Admin;

use App\Category;
use App\City;
use App\Dispute;
use App\Good;
use App\Http\Controllers\Controller;
use App\AdvStats;
use App\Models\Tickets\Ticket;
use App\News;
use App\Region;
use App\Shop;
use App\Stat;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use View;

/**
 * @property \Illuminate\Support\Collection admin_categories
 */
class AdminController extends Controller
{
    const PER_PAGE = 20;
    const ADMIN_CATEGORY_GOODS = 'goods';
    const ADMIN_CATEGORY_ORDERS = 'orders';
    const ADMIN_CATEGORY_CATEGORIES = 'categories';
    const ADMIN_CATEGORY_CITIES = 'cities';
    const ADMIN_CATEGORY_NEWS = 'news';
    const ADMIN_CATEGORY_REGIONS = 'regions';
    const ADMIN_CATEGORY_SHOPS = 'shops';
    const ADMIN_CATEGORY_USERS = 'users';
    const ADMIN_CATEGORY_TICKETS = 'ticket';
    const ADMIN_CATEGORY_ADVSTATS = 'advstats';
    const ADMIN_CATEGORY_DISPUTES = 'disputes';
    const ADMIN_CATEGORY_STATS = 'stats';
    static $redirect_to = '/admin';
    public $admin_categories = null;

    /**
     * AdminController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('admin');
        View::share('page', 'admin');

        $this->admin_categories = collect([
            self::ADMIN_CATEGORY_GOODS => __('goods.Goods'),
            self::ADMIN_CATEGORY_CATEGORIES => __('layout.Goods categories'),
            self::ADMIN_CATEGORY_CITIES => __('layout.Cities'),
            self::ADMIN_CATEGORY_NEWS => __('layout.News'),
            self::ADMIN_CATEGORY_REGIONS => __('layout.Regions'),
            self::ADMIN_CATEGORY_SHOPS => __('layout.Shops'),
            self::ADMIN_CATEGORY_USERS => __('layout.Users'),
            self::ADMIN_CATEGORY_TICKETS => __('feedback.Tickets'),
            self::ADMIN_CATEGORY_DISPUTES => __('layout.disputes'),
            self::ADMIN_CATEGORY_STATS => 'Статистика',
            self::ADMIN_CATEGORY_ADVSTATS => 'Статистика по рекламе',
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAdminCategories($user): \Illuminate\Support\Collection
    {
        $categories = [];
        // $categories = (clone $this->admin_categories)->toArray();

        if(policy(Good::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_GOODS] = __('goods.Goods');
        }

        if(policy(User::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_USERS] = __('layout.Users');
        }

        if(policy(Ticket::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_TICKETS] = __('feedback.Tickets');
        }

        if(policy(Shop::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_SHOPS] = __('layout.Shops');
        }

        if(policy(Category::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_CATEGORIES] = __('layout.Goods categories');
        }

        if(policy(City::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_CITIES] = __('layout.Cities');
        }

        if(policy(News::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_NEWS] = __('layout.News');
        }

        if(policy(Region::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_REGIONS] = __('layout.Regions');
        }

        if(policy(Stat::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_STATS] = 'Статистика';
        }

        if(policy(AdvStats::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_ADVSTATS] = 'Статистика по рекламе';
        }

        if(policy(Dispute::class)->index($user)) {
            $categories[self::ADMIN_CATEGORY_DISPUTES] = __('layout.disputes');
        }

        return collect($categories);
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirect(Request $request)
    {
        // "оптимизация" порядка, сверху должны быть проверки у которых больше всего общих прав | goods сверху чтобы меню у админов открывалось как и раньше с товаров
        if(policy(Good::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_GOODS);
        }

        if(policy(User::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_USERS);
        }

        if(policy(Ticket::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_TICKETS);
        }

        if(policy(Shop::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_SHOPS);
        }

        if(policy(Category::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_CATEGORIES);
        }

        if(policy(City::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_CITIES);
        }

        if(policy(News::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_NEWS);
        }

        if(policy(Region::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_REGIONS);
        }

        if(policy(Stat::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_STATS);
        }

        if(policy(Dispute::class)->index($request->user())) {
            return redirect(self::$redirect_to . '/' . self::ADMIN_CATEGORY_DISPUTES);
        }

        return redirect('/')->with('flash_error', 'Недостаточно прав для просмотра раздела.');
    }
}
