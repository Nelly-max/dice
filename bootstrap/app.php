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

        // An expired form (419, CSRF token too old): instead of an error page, send the person
        // back to the form with what they typed and a toast. JSON callers get a clear message.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $message = 'This page had expired, so nothing was sent. Please try again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'expired' => true], 419);
            }

            $back = url()->previous();
            if ($request->isMethod('GET') || !$back || $back === $request->fullUrl()) {
                return null;   // errors/419.blade.php
            }

            return redirect()->to($back)
                ->withInput($request->except(['password', 'password_confirmation', '_token', 'current_password']))
                ->with('error', $message);
        });

        // JSON / AJAX callers (cart, checkout, rider requests) always get a short JSON
        // message, never an HTML error page or a stack trace.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->expectsJson() || $request->is('api/*');
        });

        // Model lookups that miss (findOrFail / route model binding): a plain 404 message for JSON,
        // not "No query results for model [App\\Models\\…]".
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Not found.'], 404);
            }
            return null;
        });
    })->create();
