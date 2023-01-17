<?php
/**
 * File: PagesController.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers;


use View;

class PagesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        View::share('page', 'pages');
    }


    public function rules()
    {
        View::share('page', 'rules');
        return view('pages.rules');
    }
}