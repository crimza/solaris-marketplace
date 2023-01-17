<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Stat;
use App\User;
use Auth;
use Cache;
use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Session;
use View;
use App\Packages\Utils\PGPUtils;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest', ['except' => 'logout']);
        View::share('page', 'login');
    }

    public function showLoginForm(Request $request)
    {
        return view('auth.login');
    }

    /**
     * @inheritdoc
     */
    public function logout(Request $request)
    {
        if (Session::get('_token') !== $request->get('_token')) {
            throw new TokenMismatchException;
        }

        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return redirect('/auth/login', 303)->with('logout', true);
    }

    public function authenticated(Request $request, $user)
    {
        /** @var User $user */
        if ($user->totp_key) {
            Auth::logout();
            Session::put('2fa:totp:id', $user->id);
            return redirect('/auth/2fa/otp', 303);
        }

        if ($user->pgp_key) {
            \Auth::logout();
            \Session::put('2fa:pgp:id', $user->id);
            return redirect('/auth/2fa/pgp', 303);
        }

        $todayVisitors = Cache::get(Stat::getVisitorsCacheKey(), []);
        if (!in_array($user->id, $todayVisitors)) {
            $todayVisitors[] = $user->id;
            Cache::put(Stat::getVisitorsCacheKey(), $todayVisitors, Carbon::now()->addDays(2));
        }

//        $user->last_login_at = Carbon::now();
//        $user->save();

        return redirect()->intended($this->redirectTo, 303);
    }

    public function show2FAOTPForm(Request $request)
    {
        if (!Session::has('2fa:totp:id')) {
            return redirect('/');
        }

        return view('auth.2fa_otp');
    }

    public function login2FAOTP(Request $request, Google2FA $google2FA)
    {
        if (!Session::has('2fa:totp:id')) {
            return redirect('/');
        }

        $this->validate($request, [
            'code' => 'required|digits:6'
        ]);

        $user = User::findOrFail(Session::get('2fa:totp:id'));
        if (!$google2FA->verifyKey($user->totp_key, $request->get('code'))) {
            return redirect('/auth/2fa/otp')->with('invalid_code', true);
        }

        Auth::login($user);

        $todayVisitors = Cache::get(Stat::getVisitorsCacheKey(), []);
        if (!in_array($user->id, $todayVisitors)) {
            $todayVisitors[] = $user->id;
            Cache::put(Stat::getVisitorsCacheKey(), $todayVisitors, Carbon::now()->addDays(2));
        }

//        $user->last_login_at = Carbon::now();
        $user->save();

        return redirect()->intended($this->redirectTo, 303);
    }

    /**
     * Validate the user login request.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    protected function validateLogin(Request $request)
    {
        if ($request->has('redirect_after_login')) {
            try {
                $url = url($request->get('redirect_after_login'));
                $path = parse_url($url, PHP_URL_PATH) . '?' . parse_url($url, PHP_URL_QUERY);
                Session::put('url.intended', url($path));
            } catch (Exception $e) {
                Session::forget('url.intended');
            }
        }

        $request->merge([
            $this->username() => $request->has($this->username())
                ? ltrim(trim($request->get($this->username())), '@')
                : null
        ]);

        $this->validate($request, [
            $this->username() => 'required',
            'password' => 'required',
        ]);

        $username = $request->get($this->username());
        $cacheKey = $username . '_login_attempts';

        if (Cache::get($cacheKey, 0) >= 3) {
            $this->validate($request, [
                'captcha' => 'required|captcha'
            ]);
        }

        Cache::add($cacheKey, 0, 30); // 30 minutes
        Cache::increment($cacheKey);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $errors = [$this->username() => trans('auth.failed')];

        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }

        $username = $request->get($this->username());
        $cacheKey = $username . '_login_attempts';

        if (Cache::get($cacheKey, 0) >= 3) {
            $errors['captcha'] = 'we can show captcha now';
        }

        return redirect()->to('/auth/login', 303)
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    public function show2FAPGPForm(Request $request)
    {
        if (!\Session::has('2fa:pgp:id')) {
            return redirect('/');
        }

        /** @var User $user */
        $user = User::findOrFail(\Session::get('2fa:pgp:id'));
        $code = Str::random();
        \Session::put('2fa:pgp:code', $code);

        $message = PGPUtils::encrypt($user->pgp_key, $code);

        return view('auth.2fa_pgp', [
            'message' => $message
        ]);
    }

    public function login2FAPGP(Request $request)
    {
        if (!\Session::has('2fa:pgp:id') || !\Session::has('2fa:pgp:code')) {
            return redirect('/');
        }

        $this->validate($request, [
            'code' => 'required'
        ]);

        $user = User::findOrFail(\Session::get('2fa:pgp:id'));
        if (\Session::pull('2fa:pgp:code') !== trim($request->get('code'))) {
            return redirect('/auth/2fa/pgp')->with('invalid_code', true);
        }

        \Auth::login($user);

        $todayVisitors = \Cache::get(Stat::getVisitorsCacheKey(), []);
        if (!in_array($user->id, $todayVisitors)) {
            $todayVisitors[] = $user->id;
            \Cache::put(Stat::getVisitorsCacheKey(), $todayVisitors, Carbon::now()->addDays(2));
        }

        $user->save();
        return redirect()->intended($this->redirectTo, 303);
    }
}
