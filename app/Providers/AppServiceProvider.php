<?php

namespace App\Providers;

use App\Services\SubdivisionBranding;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SubdivisionBranding::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap(config('stockables', []));

        // Logo and name of the current subdivision, shared with every view.
        View::composer('*', function ($view) {
            $view->with(
                app(SubdivisionBranding::class)->forSegment(request()->segment(1))
            );
        });

        // Cart, checkout and payment pages: branded after the subdivision the
        // customer came from (see RememberShoppingSubdivision), or Smart Market.
        View::composer(['layouts.flow', 'Cart.*', 'Orders.*', 'OrderPayment.*', 'Payment.*'], function ($view) {
            $view->with('shopping', app(SubdivisionBranding::class)->shopping(
                session(\App\Http\Middleware\RememberShoppingSubdivision::KEY)
            ));
        });
    }
}
