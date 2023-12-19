<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CustomBladeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Blade::if('anyauth', function () {
            return Auth::guard('web')->check() || Auth::guard('store')->check();
        });
    }

    // ...
}