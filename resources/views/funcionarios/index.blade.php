@extends('layouts.app')

@section('titulo', 'Funcionarios')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-user-group /></span>
            <h1>Funcionarios</h1>
        </div>
    </div>

    {{-- Filtros del listado (browse): disparan la carga AJAX de la tabla. --}}
    <div class="tabla-filtros">
        <label class="tabla-filtros__mostrar">
            Mostrar
            <select id="f-paginate">
                @foreach ([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </select>
            registros
        </label>

        <div class="tabla-filtros__extra">
            <select id="f-fuente" aria-label="Fuente de datos">
                <option value="mamore">Mamoré</option>
                <option value="siat">SIAT</option>
            </select>
        </div>

        <div class="buscador">
            <x-heroicon-o-magnifying-glass />
            <input type="text" id="f-buscar" placeholder="Buscar por CI o nombre…">
        </div>
    </div>

    {{-- Aquí se inyecta el parcial funcionarios.list (tabla + paginación). --}}
    <div id="div-results" style="min-height: 8rem;">
        <div class="vacio">Cargando…</div>
    </div>

    <script>
        (function () {
            const url = @json(route('funcionarios.list'));
            const resultados = document.getElementById('div-results');
            const inputBuscar = document.getElementById('f-buscar');
            const selPaginate = document.getElementById('f-paginate');
            const selFuente = document.getElementById('f-fuente');
            let temporizador = null;

            async function cargar(page = 1) {
                const params = new URLSearchParams({
                    fuente: selFuente.value,
                    q: inputBuscar.value,
                    por_pagina: selPaginate.value,
                    page: page,
                });
                resultados.innerHTML = '<div class="vacio">Cargando…</div>';
                try {
                    const resp = await fetch(`${url}?${params.toString()}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    resultados.innerHTML = await resp.text();
                } catch (e) {
                    resultados.innerHTML = '<div class="aviso aviso--error">No se pudo cargar el listado. Reintentá.</div>';
                }
            }

            // Paginación: los enlaces del parcial se inyectan dinámicamente, se
            // delega el click sobre el contenedor.
            resultados.addEventListener('click', function (e) {
                const enlace = e.target.closest('a.pag__link');
                if (!enlace) { return; }
                e.preventDefault();
                const page = new URL(enlace.href).searchParams.get('page') || 1;
                cargar(page);
            });

            selPaginate.addEventListener('change', () => cargar(1));
            selFuente.addEventListener('change', () => cargar(1));
            inputBuscar.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); clearTimeout(temporizador); cargar(1); }
            });
            inputBuscar.addEventListener('input', () => {
                clearTimeout(temporizador);
                temporizador = setTimeout(() => cargar(1), 500);
            });

            cargar(1);
        })();
    </script>
@endsection
