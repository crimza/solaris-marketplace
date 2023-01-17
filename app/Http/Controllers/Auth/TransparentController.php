<?php
/**
 * File: TransparentController.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Shop;
use Auth;
use Exception;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;

class TransparentController extends Controller
{
    protected $loginController;

    public function __construct(LoginController $controller)
    {
        parent::__construct();
        $this->loginController = $controller;
    }

    public function index(Request $request)
    {
        $encryptedData = $request->get('token', '');
        $encrypter = new Encrypter(config('catalog.catalog_encryption_key'), 'AES-256-CBC');
        try {
            $data = $encrypter->decrypt($encryptedData);
            /** @var Shop $shop */
            $shop = Shop::whereAppId($data['app_id'])->firstOrFail();
            assert($shop->app_key == $data['app_key']);
        } catch (Exception $e) {
            return view('errors.decrypt_error');
        }

        if (Auth::user()) {
            return redirect()->to(catalog_jump_url($shop->id, $data['back'], true));
        }

        $request->replace([
            'redirect_to' => '/auth/login',
            'redirect_after_login' => catalog_jump_url($shop->id, $data['back'], true)
        ]);

        if ($data['username'] && $data['password']) {
            $request->replace([
                'username' => $data['username'],
                'password' => $data['password'],
            ]);
            return $this->loginController->login($request);
        } else {
            return $this->loginController->showLoginForm($request);
        }
    }
}