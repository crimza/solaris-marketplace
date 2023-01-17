<?php
/**
 * File: NewsController.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;


use App\News;
use Auth;
use Carbon\Carbon;
use View;

class NewsController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        View::share('page', 'news');
    }

    public function index()
    {
        $news = News::orderBy('created_at', 'desc')
            ->paginate(10);
        $view = view('news.index', [
            'news' => $news
        ])->render();

        if (Auth::check()) {
            Auth::user()->update([
                'news_last_read' => Carbon::now()
            ]);
        }

        return $view;
    }
}