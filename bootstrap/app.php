<?php

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
        // Los invitados van al login propio del sitio (routes/web.php).
        $middleware->redirectGuestsTo(fn () => route('login'));

        // El sitio se publica detrás de un túnel local (cloudflared, ngrok):
        // el túnel corre en la misma máquina y llega por loopback, así que
        // solo se confía en esa IP. Sin esto Laravel no ve el
        // `X-Forwarded-Proto: https` del túnel y arma las URLs en http, y el
        // navegador bloquea las llamadas AJAX de los listados por contenido
        // mixto.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
