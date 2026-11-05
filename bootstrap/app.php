<?php

use App\Exceptions\LegacyWriteBlocked;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('office') || $request->is('office/*')) {
                return route('office.login');
            }

            return route('login');
        });
        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('office') || $request->is('office/*')) {
                return '/office';
            }

            return '/account';
        });
        $middleware->alias([
            'staff.2fa' => \App\Http\Middleware\RequireStaffTwoFactor::class,
            'staff.touch' => \App\Http\Middleware\TouchStaffSession::class,
            'staff.view' => \App\Http\Middleware\ViewAsReadOnly::class,
        ]);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->validateCsrfTokens(except: [
            'payments/mpesa/callback',
            'payments/mpesa/b2c',
            'payments/paypal/webhook',
            'payments/paypal/ipn',
            'payments/stripe/webhook',
            'payments/pesapal/ipn',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (LegacyWriteBlocked $exception, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 409);
            }

            return redirect()->back()->withInput()->withErrors(['legacy' => $exception->getMessage()]);
        });
    })->create();
