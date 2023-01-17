<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\View;

class Controller extends BaseController
{

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
        View::share('page', '');

        $this->middleware(function ($request, $next) {
            if (Auth::check()) {
                View::share('unreadNotifications', Auth::user()->unreadNotifications());
            }
            return $next($request);
        });
    }

    protected function getRedirectUrl()
    {
        if (request()->has('redirect_to')) {
            try {
                $url =url(request()->get('redirect_to'));
                $path = parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY);

                return  Str::replaceFirst("https://", "http://", url($path));
            } catch (Exception $e) {
                return app(UrlGenerator::class)->previous();
            }
        } else {
            return app(UrlGenerator::class)->previous();
        }
    }
}
