@extends('layouts.app')

@section('titulo', 'Marcaciones')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-finger-print /></span>
            <h1>Marcaciones</h1>
        </div>
        @can('create', \App\Models\Asistencia::class)
            <div style="display: flex; align-items: flex-start; gap: .5rem; flex-wrap: wrap;">
                <x-modal-marcacion />

                <form method="POST" action="{{ route('marcaciones.importar') }}" enctype="multipart/form-data" style="display: flex; align-items: flex-start; gap: .5rem;">
                    @csrf
                    <div>
                        <input type="file" name="archivo" accept=".csv,text/csv" required class="input" style="width: auto;">
                        @error('archivo') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn"><x-heroicon-o-arrow-up-tray />Importar CSV</button>
                </form>
            </div>
        @endcan
    </div>

    {{-- Filtros del listado (browse): disparan la carga AJAX de la tabla. --}}
    <div class="tabla-filtros">
        <label class="tabla-filtros__mostrar">
            Mostrar
            <select id="f-paginate" aria-label="Cantidad de registros a mostrar">
                @foreach ([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}" @selected($porPagina == $n)>{{ $n }}</option>
                @endforeach
            </select>
            registros
        </label>

        <div class="tabla-filtros__extra">
            <input type="date" id="f-desde" value="{{ $desde }}" aria-label="Desde">
            <input type="date" id="f-hasta" value="{{ $hasta }}" aria-label="Hasta">
            <select id="f-tipo" aria-label="Tipo">
                <option value="">Todos</option>
                <option value="{{ \App\Models\Asistencia::TIPO_RELOJ }}" @selected($tipo === \App\Models\Asistencia::TIPO_RELOJ)>R</option>
                <option value="{{ \App\Models\Asistencia::TIPO_A }}" @selected($tipo === \App\Models\Asistencia::TIPO_A)>A</option>
                <option value="{{ \App\Models\Asistencia::TIPO_MANUAL }}" @selected($tipo === \App\Models\Asistencia::TIPO_MANUAL)>M</option>
            </select>
        </div>

        <div class="buscador">
            <x-heroicon-o-magnifying-glass />
            <input type="text" id="f-buscar" placeholder="Buscar por CI o nombre…">
        </div>
    </div>

    {{-- Aquí se inyecta el parcial marcaciones.list (tabla + paginación). --}}
    <div id="div-results" style="min-height: 8rem;">
        <div class="vacio">Cargando…</div>
    </div>

    <script>
        (function () {
            const url = @json(route('marcaciones.list'));
            const resultados = document.getElementById('div-results');
            const inputBuscar = document.getElementById('f-buscar');
            const selPaginate = document.getElementById('f-paginate');
            const dateDesde = document.getElementById('f-desde');
            const dateHasta = document.getElementById('f-hasta');
            const selTipo = document.getElementById('f-tipo');

            async function cargar(page = 1) {
                const params = new URLSearchParams({
                    desde: dateDesde.value,
                    hasta: dateHasta.value,
                    tipo: selTipo.value,
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
            dateDesde.addEventListener('change', () => cargar(1));
            dateHasta.addEventListener('change', () => cargar(1));
            selTipo.addEventListener('change', () => cargar(1));
            // La búsqueda se dispara solo con Enter: escribir no recarga la tabla.
            inputBuscar.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); cargar(1); }
            });

            cargar(1);
        })();
    </script>
@endsection
