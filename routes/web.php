<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\HomeCity\PropertyController;
use App\Http\Controllers\Web\HomeMarket\ItemsController;
use App\Http\Controllers\Web\SubdivisionsController;
use App\Http\Controllers\Web\CartController;

use Illuminate\Http\Request;

use App\Http\Controllers\Web\CookingGas\ProductController;

// Route::get('/', function () {
//     return view('welcome');
// });

    /*
    |--------------------------------------------------------------------------
    | Home Routes
    |--------------------------------------------------------------------------
    */
    // Route::get('/', fn () => view('home'))->name('home');
    Route::get('/', [SubdivisionsController::class, 'index'])->name('home');
    Route::get('/setting', fn () => view('setting'))->name('setting');

    Route::get('/major-division/{id}', [ProductController::class, 'byMajorDivision'])->name('products.byMajorDivision');
    Route::get('/sub-division/{id}', [ProductController::class, 'bySubDivision'])->name('products.bySubDivision');



    /*
    |--------------------------------------------------------------------------
    | Cart Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'view'])->name('cart.view');
        Route::post('/add', [CartController::class, 'add'])->name('cart.add');
        Route::put('/update', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
        Route::get('/clear', [CartController::class, 'clear'])->name('cart.clear');
    });


    /*
    |--------------------------------------------------------------------------
    | Home City Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('home-city')->group(function () {
        Route::get('/', [PropertyController::class, 'index'])->name('home');
        Route::get('/property/{id}', [PropertyController::class, 'viewListing'])->name('homecity.listing.view');
    });



    /*
    |--------------------------------------------------------------------------
    | Home Market Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('home-market')->group(function () {
        Route::get('/', fn () => view('HomeMarket.home'))->name('home');
        Route::get('/', [ItemsController::class, 'index'])->name('homemarket.home');
        Route::get('product/viewItem', fn () => view('HomeMarket.products.viewItem'))->name('items.view');
        // Route::get('product/viewItem/{id}', [ItemsController::class, 'viewItem'])->name('item.view');
        // Route::get('product/viewItem/{business_account}/{id}', [ItemsController::class, 'ViewItem'])->name('item.view');
        Route::get('product/viewItem/{inventoryId}', [ItemsController::class, 'ViewItem'])->name('item.view');
        Route::get('/property/{id}', [PropertyController::class, 'viewListing'])->name('homecity.listing.view');
    });


    /*
    |--------------------------------------------------------------------------
    | Cooking Gas Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('gas')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('gas.products.index');
        // Route::get('/view-gas', function () { return view('CookingGas.viewCylinder');})->name('view-gas');

        // Route::get('/view', [ProductController::class, 'index'])->name('gas.products.index');
        Route::get('/view-gas', function (Request $request) {
                return app(ProductController::class)
                    ->show($request);
            })->name('view-gas');

        Route::get('/product-variant', [ProductController::class, 'variant'])
            ->name('product.variant');

        Route::get('/search', [ProductController::class, 'search'])
            ->name('search');
    });

    // Use a placeholder name like 'subdivision.route'
    Route::get('/{weblink}', [SubdivisionsController::class, 'handleWeblink'])
        ->name('subdivision.route');

