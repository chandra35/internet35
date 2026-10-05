<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust Cloudflare proxies for correct HTTPS detection & real IP
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Sesi telah berakhir, silakan muat ulang halaman.'], 419);
            }
            return redirect()->route('login')->with('warning', 'Sesi Anda telah berakhir. Silakan login kembali.');
        });

        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Anda tidak memiliki hak akses untuk aksi ini.'], 403);
            }

            if (auth()->check()) {
                if (auth()->user()->hasRole('client')) {
                    return redirect()->route('pelanggan.dashboard')->with('info', 'Halaman tersebut hanya untuk administrator. Anda telah diarahkan ke Portal Pelanggan.');
                }

                if ($request->routeIs('admin.dashboard')) {
                    return response()->view('errors.403', ['exception' => $e], 403);
                }

                return redirect()->route('admin.dashboard')->with('error', 'Anda tidak memiliki hak akses untuk halaman tersebut.');
            }

            return redirect()->route('login');
        });
    })->create();
