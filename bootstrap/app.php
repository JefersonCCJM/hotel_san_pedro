<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\FactusApiException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });

            \Illuminate\Support\Facades\RateLimiter::for('cleaning-panel', function (\Illuminate\Http\Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($request->ip());
            });

            \Illuminate\Support\Facades\RateLimiter::for('livewire', function (\Illuminate\Http\Request $request) {
                return \Illuminate\Cache\RateLimiting\Limit::perMinute(600)->by($request->ip());
            });
        },
    )
    ->withProviders([
        \App\Providers\PublicPathServiceProvider::class,
        Spatie\Permission\PermissionServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'security_control' => \App\Http\Middleware\SecurityControlMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityControlMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El recurso solicitado no fue encontrado.'
                ], 404);
            }
            return back()->with('error', 'El recurso solicitado no fue encontrado.')->withInput();
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción.'
                ], 403);
            }
            return back()->with('error', 'No tienes permisos para realizar esta acción.')->withInput();
        });

        $exceptions->render(function (\App\Exceptions\FactusApiException $e, $request) {
            return $e->render($request);
        });

        $exceptions->shouldRenderJsonWhen(function ($request, $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });
    })->create();
