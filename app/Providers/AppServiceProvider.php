<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Share currency symbol, rate and list with every view so price displays are consistent.
        View::composer('*', function ($view) {
            $user           = Auth::user();
            $currencies     = User::CURRENCIES;
            $currencyCode   = $user?->currency ?? 'USD';
            $currencySymbol = $currencies[$currencyCode]['symbol'] ?? '$';
            $currencyRate   = $currencies[$currencyCode]['rate']   ?? 1.0;
            $view->with(compact('currencies', 'currencyCode', 'currencySymbol', 'currencyRate'));
        });
    }
}
