<?php

use App\Lib\Handlers\CookieHandler;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Shopify\Utils;
use App\Models\Session;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Shopify\Context;
use Shopify\Auth\OAuth;
use Shopify\Webhooks\Registry;
use Shopify\Webhooks\Topics;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::fallback(function (Request $request) {
    $shop = Utils::sanitizeShopDomain($request->query('shop'));
    $host = $request->query('host');
    $appInstalled = Session::where('shop', $shop)->exists();

    if (!$appInstalled) {
        return redirect("/login?shop={$shop}");
    }

    // This looks insecure but it is not because the host has to be valid
    return Inertia::render('Home', [
        'shop' => $shop,
        'host' => $host,
        'apiKey' => Context::$API_KEY
    ]);
});

Route::get('/login/toplevel', function (Request $request, Response $response) {
    $shop = Utils::sanitizeShopDomain($request->query('shop'));

    $cookie = cookie()->forever('shopify_top_level_oauth', '', null, null, true, true, false, 'strict');
    Cookie::queue($cookie);

    return view('top_level_redirect', [
        'apiKey' => Context::$API_KEY,
        'shop' => $shop,
        'hostname' => Context::$HOST_NAME
    ]);
});

Route::get('/login', function (Request $request) {
    $shop = Utils::sanitizeShopDomain($request->query('shop'));

    if (!$request->hasCookie('shopify_top_level_oauth')) {
        return redirect("/login/toplevel?shop={$shop}");
    }

    $installUrl = Oauth::begin($shop, '/auth/callback', true, [CookieHandler::class, 'saveShopifyCookie']);

    return redirect($installUrl);
});

Route::get('/auth/callback', function (Request $request) {
    $session = OAuth::callback(
        $request->cookie(),
        $request->query(),
        [CookieHandler::class, 'saveShopifyCookie'],
    );

    $host = $request->query('host');
    $shop = Utils::sanitizeShopDomain($request->query('shop'));

    $response = Registry::register('/webhooks', Topics::APP_UNINSTALLED, $shop, $session->getAccessToken());
    if ($response->isSuccess()) {
        Log::debug("Registered APP_UNINSTALLED webhook for shop $shop");
    } else {
        Log::error(
            "Failed to register APP_UNINSTALLED webhook for shop $shop with response body: " .
                print_r($response->getBody(), true)
        );
    }

    return redirect("?" . http_build_query(['host' => $host, 'shop' => $shop]));
});
