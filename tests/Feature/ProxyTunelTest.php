<?php

use Illuminate\Support\Facades\Route;

/**
 * El sitio se publica hacia afuera con un túnel local (cloudflared, ngrok): el
 * túnel termina el HTTPS y entrega la petición por loopback en http. Sin
 * confiar en ese proxy, Laravel arma todas las URLs en http y el navegador
 * bloquea por contenido mixto las llamadas AJAX de los listados.
 */
test('detrás del túnel las redirecciones se arman en https', function () {
    $this->get('/funcionarios', ['X-Forwarded-Proto' => 'https'])
        ->assertRedirect('https://localhost/login');
});

test('detrás del túnel las URLs generadas usan el host público', function () {
    Route::middleware('web')->get('/prueba-url-tunel', fn (): string => route('login'));

    $this->get('/prueba-url-tunel', [
        'X-Forwarded-Proto' => 'https',
        'X-Forwarded-Host' => 'sismark.trycloudflare.com',
    ])->assertOk()->assertSee('https://sismark.trycloudflare.com/login');
});

test('sin la cabecera del proxy las URLs siguen en http', function () {
    $this->get('/funcionarios')
        ->assertRedirect('http://localhost/login');
});

test('solo se confía en el proxy que llega por loopback', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->get('/funcionarios', ['X-Forwarded-Proto' => 'https'])
        ->assertRedirect('http://localhost/login');
});
