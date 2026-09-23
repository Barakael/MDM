<?php

use App\Http\Controllers\MdmGatewayController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')->prefix('mdm')->group(function () {
                Route::get('/enroll/{token}', [MdmGatewayController::class, 'enroll']);
                Route::post('/checkin', [MdmGatewayController::class, 'checkin']);
                Route::put('/checkin', [MdmGatewayController::class, 'checkin']);
                Route::post('/connect', [MdmGatewayController::class, 'connect']);
                Route::put('/connect', [MdmGatewayController::class, 'connect']);
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SPA uses Sanctum personal access tokens (Bearer), not cookie sessions.
        // statefulApi() would require /sanctum/csrf-cookie and causes 419 on login.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
