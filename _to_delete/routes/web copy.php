<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AccountController;
use App\Http\Controllers\Web\SubdivisionsController;
use App\Http\Controllers\Web\LocationController;
use App\Http\Controllers\Web\DeliveryLocationController;

use App\Http\Controllers\Web\HomeCity\PropertyController;
use App\Http\Controllers\Web\HomeMarket\ItemsController;
use App\Http\Controllers\Web\CookingGas\ProductController;

use App\Http\Controllers\Web\Cart\CartController;
use App\Http\Controllers\Web\Cart\CheckoutController;
use App\Http\Controllers\Web\Cart\CheckoutControllerV2;

use App\Http\Controllers\Web\Payment\PaymentController;
use App\Http\Controllers\Web\OrderPayment\MpesaPaymentController;
use App\Http\Controllers\Web\OrderPayment\StatusController;
use App\Http\Controllers\Web\OrderPayment\MpesaC2BPaymentController;
use App\Http\Controllers\Web\Order\OrderController;

use App\Http\Controllers\Web\Hub\DeliveryController;
use App\Http\Controllers\Web\Hub\MarketerController;
use App\Http\Controllers\Web\Hub\RiderController;
use App\Http\Controllers\Web\Hub\RiderLocationController;

use App\Models\Customer\Cart;

use Illuminate\Http\Request;


// Route::get('/', function () {
//     return view('welcome');
// });

    /*
    |--------------------------------------------------------------------------
    | CUSTOMER ACCOUNTS ROUTES
    |--------------------------------------------------------------------------  
    */
    Route::get('/account-details', [CheckoutController::class, 'accountDetails'])
        ->middleware('auth:customer')
        ->name('account.details');

    /*
    |--------------------------------------------------------------------------
    | Home Routes
    |--------------------------------------------------------------------------  
    */
    // Route::get('/', fn () => view('home'))->name('home');
    Route::get('/', [SubdivisionsController::class, 'index'])->name('home');
    Route::get('/setting', fn () => view('setting'))->name('setting');    
    Route::get('/account', [AccountController::class, 'showLoginForm'])->name('login');
    // Route::post('/account', [AccountController::class, 'login']);



    Route::post('/account/register', [AccountController::class, 'register'])->name('account.register.create');

    Route::post('/account/login', [AccountController::class, 'login'])->name('account.login');
    Route::post('/account/logout', [AccountController::class, 'logout'])->name('logout');


    Route::get('/account/password_reset', fn () => view('Auth.resetPassword'))->name('account.resetpassword');

    Route::get('/major-division/{id}', [ProductController::class, 'byMajorDivision'])->name('products.byMajorDivision');
    Route::get('/sub-division/{id}', [ProductController::class, 'bySubDivision'])->name('products.bySubDivision');



    /*
    |--------------------------------------------------------------------------
    | Cart Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'view'])->name('cart.index');
        Route::post('/add', [CartController::class, 'addToCart'])->name('cart.add');
        Route::put('/update', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
        Route::get('/clear', [CartController::class, 'clear'])->name('cart.clear');

        Route::get('/count', function () {
            return [
                'count' => Cart::forCurrent()->sum('quantity')
            ];
        });


        // Route::get('checkout', fn () => view('Cart/checkout'))->name('cart.checkout');
        Route::get('checkout', [CheckoutController::class, 'checkout'])->name('cart.checkout');
        Route::post( '/checkout/select',[CheckoutController::class, 'selectShipment']
            )->name('cart.checkout.select');

        Route::get('/checkout/account-details', [CheckoutController::class, 'accountDetails'])
            ->middleware('auth:customer')
            ->name('cart.checkout.account.details');

        Route::post('/checkout/distance', [CheckoutController::class, 'calculateDistance']);

        Route::post('/checkout/prepare', [CheckoutController::class, 'prepare'])
            ->name('checkout.prepare');
    });

    /*
    |--------------------------------------------------------------------------
    | Invoice Routes
    |--------------------------------------------------------------------------
    */

    Route::post('/checkout/invoice', [CheckoutControllerV2::class, 'checkoutInvoice'])
        ->name('invoice');

    
    /*
    |--------------------------------------------------------------------------
    | Payment Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('payment')->group(function () {
        // Route::get('/mpesa', [PaymentController::class, 'mpesa'])
        // ->name('payment.mpesa');


        // **************************************
        // MPESA PAYMENT METHOD
        // **************************************

        Route::get('/mpesa/{invoice}', [PaymentController::class, 'mpesa'])
            ->name('payment.mpesa');

        Route::post('/mpesa/stk-push', [MpesaPaymentController::class, 'stkPush'])
            ->name('payment.mpesa.stk');
            
        Route::post('/mpesa/callback', [MpesaPaymentController::class, 'callback'])
            ->name('payment.mpesa.callback');

        Route::get('/mpesa/status', [MpesaPaymentController::class, 'status'])
            ->name('payment.mpesa.status');

        // Webhooks matching the registered c2b addresses
        Route::post('/mpesa/c2b/validation', [MpesaC2BPaymentController::class, 'validatePayment']);
        Route::post('/mpesa/c2b/confirmation', [MpesaC2BPaymentController::class, 'confirmPayment']);

        // Dev Utility execution management routers
        Route::post('/mpesa/c2b/register', [MpesaC2BPaymentController::class, 'register']);
        Route::post('/mpesa/c2b/simulate', [MpesaC2BPaymentController::class, 'simulate']);



        // **************************************
        // CARD PAYMENT METHOD
        // **************************************

        Route::get('/card', [PaymentController::class, 'card'])
            ->name('payment.card');
        
        Route::get('/cash', [PaymentController::class, 'cash'])
            ->name('payment.cash');
    });

    Route::get( '/payment/status/{invoice:uuid}', [StatusController::class, 'status'])
            ->name('payment.status');

    /*
    |--------------------------------------------------------------------------
    | Customer order Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('orders')->group(function () {
        Route::get('/{order:uuid}/placed', [OrderController::class, 'orderPlaced'])
            ->name('orders.placed');

    });

    /*
    |--------------------------------------------------------------------------
    | SMS & Email / API-like Routes
    |--------------------------------------------------------------------------
    */
    // Route::prefix('notification')->group(function () {
    //     Route::get('/talk-sasa/send-sms', [SmsController::class, 'sendTalkSasaSms']);

    //     Route::get('/text-sms/send-sms', [SmsController::class, 'sendTextSms']);
    // });



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
                    ->viewCylinder($request);
            })->name('gas.cylinder.view');

        Route::get('/product-variant', [ProductController::class, 'variant'])
            ->name('product.variant');
            

        Route::get('/search', [ProductController::class, 'search'])
            ->name('search');
    });

    // Use a placeholder name like 'subdivision.route'
    Route::get('/{weblink}', [SubdivisionsController::class, 'handleWeblink'])
        ->name('subdivision.route');




    /*
    |--------------------------------------------------------------------------
    | Customer Hub Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('hub')
        ->middleware('auth:customer')
        ->name('hub.') 
        ->group(function () {
            Route::get('/home', fn () => view('Hub.index'))->name('index');
            Route::get('/orders', fn () => view('Hub.orders'))->name('orders');
            Route::get('/orders', [OrderController::class, 'index'])
                ->name('orders');

            Route::get('/account', fn () => view('Hub.account'))->name('account.index');
            Route::get('/account/join-team', fn () => view('Hub.joinTeam'))->name('account.join-team');
            // Route::get('/account/rider-application', fn () => view('Hub.riderApplication'))->name('account.rider-application');

            
            Route::get('/team/marketer', [MarketerController::class, 'marketer'])
                ->name('account.marketer.index');
            
            Route::get('/team/join', [MarketerController::class, 'joinMarketing'])
                ->name('account.marketer.join');

            Route::post('/marketer/store', [MarketerController::class,'storeMarketer'])
                ->name('marketer.store');

            Route::get('/account/rider-application', [RiderController::class, 'riderApplication'])
                ->name('account.rider-application');

            Route::post('/account/rider-application/apply', [RiderController::class, 'apply'])
                ->name('account.rider-application.apply');


            // Route::get('/deliveries', fn () => view('Hub.deliveries'))->name('deliveries');
            Route::get('/deliveries', [DeliveryController::class, 'deliveries'])->name('deliveries');
            // Route::get('/navigate/map', fn () => view('Hub.deliveryMap'))->name('deliveries.map');
            Route::get('/navigate/map', [RiderLocationController::class, 'navigate'])->name('navigate.map');
            Route::get('/navigate/{delivery}', [RiderLocationController::class, 'navigate']);

            Route::get('/rider/delivery/request', [RiderController::class, 'pendingRequest']);
    });

    Route::middleware('auth:customer')->group(function () {

        Route::post('/rider/location/update', [RiderLocationController::class, 'updateRiderLocation'])
            ->name('account.riderLocation.update');
    
        Route::post('/rider/status/update', [RiderLocationController::class, 'updateStatus'])
            ->name('account.riderStatus.update');

        Route::get('/rider/status', [RiderLocationController::class, 'getStatus'])
            ->name('account.riderStatus.get');

        Route::get('/rider/init', [RiderLocationController::class, 'init']);

        Route::get('/rider/delivery/request', [RiderController::class, 'pendingRequest']);

        Route::post('/rider/delivery/{request}/accept', [RiderController::class, 'acceptDelivery'])
            ->name('rider.delivery.accept');

        Route::get( '/rider/active-delivery', [RiderController::class, 'activeDelivery'] )
            ->name('rider.active.delivery');

        Route::post('/rider/delivery/{delivery}/arrived-shop', [RiderController::class, 'arrivedAtShop'])
            ->name('rider.delivery.arrived-shop');

        Route::post('/rider/delivery/{delivery}/pickup', [RiderController::class, 'pickupDelivery'])
            ->name('rider.delivery.pickup');

        Route::post('/rider/delivery/{delivery}/start', [RiderController::class, 'startDelivery'])
            ->name('rider.delivery.start');
    });

    

    /*
    |--------------------------------------------------------------------------
    | Customer Delivery Location
    |--------------------------------------------------------------------------
    */
    Route::post('/delivery-location', [DeliveryLocationController::class, 'store'])
    ->name('delivery.location.store');

    Route::get('/delivery-location', [DeliveryLocationController::class, 'get'])
        ->name('delivery.location.get');

    Route::delete('/delivery-location', [DeliveryLocationController::class, 'clear'])
        ->name('delivery.location.clear');
    



    /*
|--------------------------------------------------------------------------
| Public Utility / API-like Routes
|--------------------------------------------------------------------------
*/
Route::get('/locations/countries', [LocationController::class, 'getCountries']);
Route::get('/locations/counties', [LocationController::class, 'getCounties']);
Route::get('/locations/counties/{id}', [LocationController::class, 'getCounties']);
Route::get('/locations/towns/{id}', [LocationController::class, 'getTowns']);
Route::get('/locations/places/{id}', [LocationController::class, 'getPlaces']);