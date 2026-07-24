@props([
    'action',
    'busqueda' => '',
    'porPagina' => 10,
    'placeholder' => 'Buscar…',
    'campo' => 'q',
])

{{-- Filtros de tabla estilo DataTables: «Mostrar N registros» a la izquierda y
     buscador a la derecha. El slot `filtros` permite intercalar filtros extra
     (fechas, selects) dentro del mismo formulario GET. --}}
<form method="GET" action="{{ $action }}" class="tabla-filtros">
    <label class="tabla-filtros__mostrar">
        Mostrar
        <select name="por_pagina" onchange="this.form.submit()">
            @foreach ([10, 25, 50, 100] as $n)
                <option value="{{ $n }}" @selected((int) $porPagina === $n)>{{ $n }}</option>
            @endforeach
        </select>
        registros
    </label>

    @isset($filtros)
        <div class="tabla-filtros__extra">{{ $filtros }}</div>
    @endisset

    <div class="buscador">
        <x-heroicon-o-magnifying-glass />
        <input type="text" name="{{ $campo }}" value="{{ $busqueda }}" placeholder="{{ $placeholder }}">
    </div>
</form>
