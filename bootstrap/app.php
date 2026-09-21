<?php

use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        // Subdomain dilayani di balik proxy/load balancer ArahInn, sehingga
        // X-Forwarded-* perlu dipercaya agar skema HTTPS dan IP klien benar.
        $middleware->trustProxies(at: '*');

        // Notifikasi Midtrans datang dari server Midtrans, bukan dari peramban
        // yang memegang sesi — tidak ada token CSRF yang bisa disertakannya.
        // Keasliannya dijamin tanda tangan SHA512 di controller, yang justru
        // lebih kuat daripada token sesi.
        $middleware->validateCsrfTokens(except: [
            'midtrans/notifikasi',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();