<?php

namespace App\Providers;

use App\AdvStats;
use App\Dispute;
use App\Good;
use App\Category;
use App\City;
use App\News;
use App\Policies\AdvStatsPolicy;
use App\Policies\DisputePolicy;
use App\Policies\NewsPolicy;
use App\Region;
use App\Shop;
use App\Stat;
use App\Models\Tickets\Ticket;
use App\User;
use App\Policies\CategoryPolicy;
use App\Policies\CityPolicy;
use App\Policies\RegionPolicy;
use App\Policies\GoodPolicy;
use App\Policies\ShopPolicy;
use App\Policies\StatsPolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Good::class => GoodPolicy::class,
        Category::class => CategoryPolicy::class,
        City::class => CityPolicy::class,
        Region::class => RegionPolicy::class,
        Shop::class => ShopPolicy::class,
        Stat::class => StatsPolicy::class,
        Ticket::class => TicketPolicy::class,
        User::class => UserPolicy::class,
        News::class => NewsPolicy::class,
        AdvStats::class => AdvStatsPolicy::class,
        Dispute::class => DisputePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('pgauth', function($app, array $config) {
            return new EloquentUserProvider($app['hash'], $config['model']);
        });
    }
}
