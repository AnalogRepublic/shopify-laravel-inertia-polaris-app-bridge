<?php

namespace App\Providers;

use App\Lib\DbSessionStorage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Shopify\Context;
use App\Lib\Handlers\AppUninstalled;
use Illuminate\Support\Facades\URL;
use Shopify\Webhooks\Registry;
use Shopify\Webhooks\Topics;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Context::initialize(
            Config::get('shopify.api_key'),
            Config::get('shopify.api_secret'),
            Config::get('shopify.scopes'),
            str_replace('https://', '', Config::get('shopify.host')),
            new DbSessionStorage()
        );

        URL::forceScheme('https');

        Registry::addHandler(Topics::APP_UNINSTALLED, new AppUninstalled());
    }
}
