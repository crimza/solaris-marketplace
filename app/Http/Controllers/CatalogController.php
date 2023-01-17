<?php

namespace App\Http\Controllers;

use App\Category;
use App\Good;
use App\Role;
use App\Shop;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use View;
use Vinkla\Hashids\Facades\Hashids;

class CatalogController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        View::share('page', 'catalog');
    }

    /**
     * Show the application dashboard.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $goods = Good::available()
            ->applySearchFilters($request)
            ->with(['shop', 'cities', 'availablePackages'])
            ->orderBy('rating', 'desc')
            ->paginate(16);

        $title = '';
        if ($request->has('category') && $category = Category::find($request->get('category'))) {
            $title = $category->title;
        }

        return view('catalog.index', [
            'goods' => $goods,
            'title' => $title
        ]);
    }

    public function jump(Request $request, $encryptedData)
    {
        try {
            $data = decrypt($encryptedData);
        } catch (Exception $e) {
            return view('errors.decrypt_error');
        }

        $encrypter = new Encrypter(config('catalog.catalog_encryption_key'), 'AES-256-CBC');
        /** @var Shop $shop */
        $shop = Shop::findOrFail($data['shop_id']);

        $gateHash = null;
        if ($shop->gate_enabled && !is_null($shop->gate_lan_ip) && !is_null($shop->gate_lan_port)) {
            $gateLastOctet = intval(last(explode('.', $shop->gate_lan_ip)));
            $gatePort = intval($shop->gate_lan_port);
            $gateHash = Hashids::connection('catalog')->encode([$gateLastOctet, $gatePort]);
        }

        $urlData = parse_url($shop->url);
        $domain = $urlData['host'];
        $path = str_replace('/s:' . $gateHash . '/', '', $urlData['path']);

        if (Auth::check()) {
            $user = Auth::user();
            $role = $user->roles->first();

            $loginData = $encrypter->encrypt([
                'app_id' => $shop->app_id,
                'username' => $user->username,
                'outer_id' => $user->id,
                'wallet_id' => $user->wallet_id,
                'role_type_id' => $role ? $role->id : Role::User,
                'buy_count' => $user->buy_count,
                'created_at' => $user->created_at->format('U'),
                'route' => ($data['from_root'] ? '' : $path) . $data['route']
            ]);

            if ($gateHash) {
                $finalUrl = url('/s:' . $gateHash . '/auth/transparent?token=' . $loginData);
            } else {
                $finalUrl = 'http://' . $domain . '/auth/transparent?token=' . $loginData;
            }
        } else {
            if ($gateHash) {
                $finalUrl = url('/s:' . $gateHash . $path . $data['route']);
            } else {
                $finalUrl = 'http://' . $domain . $path . $data['route'];
            }
        }
        return redirect()->to($finalUrl, 301);
    }

    public function notificationsRead(Request $request)
    {
        if ($request->get('_token') !== csrf_token()) {
            throw new TokenMismatchException;
        }

        $user = Auth::user();
        $user->notification_last_read_at = Carbon::now();
        $user->save();
        return redirect()->back();
    }
}
