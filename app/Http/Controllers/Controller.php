<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Cantidad de filas por página del listado, tomada de `?por_pagina=` y
     * acotada a los valores permitidos del selector «Mostrar N registros».
     */
    protected function porPagina(Request $request, int $defecto = 10): int
    {
        $porPagina = (int) $request->query('por_pagina', $defecto);

        return in_array($porPagina, [10, 25, 50, 100], true) ? $porPagina : $defecto;
    }
}
