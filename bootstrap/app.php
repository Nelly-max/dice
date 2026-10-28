<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })

    ->withMiddleware(function (Middleware $middleware) {
        // remembers which subdivision the customer is shopping in (cart/checkout header)
        $middleware->web(append: [
            \App\Http\Middleware\RememberShoppingSubdivision::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payment/mpesa/callback' 
        ]);
    })
    
    ->withExceptions(function (Exceptions $exceptions): void {
        // A form bigger than PHP's post_max_size (e.g. several large phone photos) arrives
        // empty and is rejected with a bare 413 page. Send the person back with a reason.
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            $message = 'Your photos are too large to send together. Please use smaller photos (under 5 MB each) and try again.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }
            return redirect()->back()->withErrors(['upload' => $message]);
        });
    })->create();
