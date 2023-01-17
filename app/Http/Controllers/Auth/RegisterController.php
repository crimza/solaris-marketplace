<?php

namespace App\Http\Controllers\Auth;

use App\AdvStatsCache;
use App\Http\Controllers\Controller;
use App\Integrations\CryptoPaymentPlatform\ApiClient;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/catalog';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => 'required|string|min:4|max:14|alpha_num|pgusers',
            'password' => 'required|string|min:6|confirmed',
            'captcha' => 'required|captcha',
            'terms' => 'accepted'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param array $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'username' => $data['username'],
            'password' => bcrypt($data['password']),
            'news_last_read' => Carbon::now()
        ]);
    }

    protected function registered(Request $request, $user)
    {
        $id = $request->cookie('advstats');
        if (is_numeric($id)) {
            AdvStatsCache::add($id, 0, 0, 1);
        }

        $api = app(ApiClient::class);

        $wallet = $api->createWallet();
        $wallet_id = 0;

        if(!isset($wallet['error']) && isset($wallet['id'])) {
            $wallet_id = $wallet['id'];
        }

        $user->wallet_id = $wallet_id;
        $user->save();
    }
}
