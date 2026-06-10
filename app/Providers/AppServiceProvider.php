<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\CookingGas\BusinessGasStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Simply ensure the variable exists so the nav doesn't crash on view-gas, etc.
        // View::composer('CookingGas.partials.nav', function ($view) {
        //     $view->with('cylinders', []);
        // });

        Relation::morphMap(config('stockables', []));

        View::composer('*', function ($view) {
            try {
                // Extracts exactly 'home-market' from the first browser URL block segment
                $currentLink = Request::segment(1); 

                // Pull row using your precise connection credentials matrix
                $subdivision = DB::connection('hub')
                    ->table('sub_divisions') // Verify if this is 'subdivisions' or 'sub_divisions'
                    ->where('weblink', $currentLink)
                    ->select(['name', 'image_path'])
                    ->first();

                $baseMediaUrl = rtrim(env('MEDIA_URL', 'http://127.0.0.1:8000'), '/');

                if ($subdivision && !empty($subdivision->image_path)) {
                    //  Combines directly into: http://127.0.0
                    // Use asset() if the files live locally in your current public directory track instead
                    $logoUrl = $baseMediaUrl . '/' . ltrim($subdivision->image_path, '/');
                    $subdivisionName = $subdivision->name;
                } else {
                    // Fallback baseline layout tracking if no string match is resolved
                    $logoUrl = asset('img/logo/homemarket.png');
                    $subdivisionName = 'Marketplace';
                }

            } catch (\Throwable $e) {
                $logoUrl = asset('img/logo/homemarket.png');
                $subdivisionName = 'Marketplace';
            }

            $view->with([
                'logoUrl' => $logoUrl,
                'subdivisionName' => $subdivisionName
            ]);
        });
    }

}
