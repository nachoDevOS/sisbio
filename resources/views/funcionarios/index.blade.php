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

            {{-- Solo Mamoré conoce los contratos: con SIAT el select se oculta. --}}
            <select id="f-contrato" aria-label="Situación de contrato">
                <option value="todos">Todos</option>
                <option value="con">Con contrato</option>
                <option value="sin">Sin contrato</option>
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
            const selContrato = document.getElementById('f-contrato');
            let temporizador = null;

            // El filtro por contrato solo aplica a Mamoré (SIAT no tiene contratos).
            function sincronizarContrato() {
                const esMamore = selFuente.value === 'mamore';
                selContrato.hidden = !esMamore;
                if (!esMamore) { selContrato.value = 'todos'; }
            }

            // La API devuelve cuántos hay en cada situación (ya con la búsqueda
            // aplicada): se muestran en la etiqueta de cada opción.
            const etiquetas = { todos: 'Todos', con: 'Con contrato', sin: 'Sin contrato' };

            function actualizarTotales() {
                const datos = resultados.querySelector('#totales-contrato');
                for (const opcion of selContrato.options) {
                    const total = datos ? datos.dataset[opcion.value] : '';
                    opcion.textContent = total
                        ? `${etiquetas[opcion.value]} (${Number(total).toLocaleString('es-BO')})`
                        : etiquetas[opcion.value];
                }
            }

            async function cargar(page = 1) {
                const params = new URLSearchParams({
                    fuente: selFuente.value,
                    contrato: selContrato.value,
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
                    actualizarTotales();
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
            selContrato.addEventListener('change', () => cargar(1));
            selFuente.addEventListener('change', () => { sincronizarContrato(); cargar(1); });
            inputBuscar.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); clearTimeout(temporizador); cargar(1); }
            });
            inputBuscar.addEventListener('input', () => {
                clearTimeout(temporizador);
                temporizador = setTimeout(() => cargar(1), 500);
            });

            sincronizarContrato();
            cargar(1);
        })();
    </script>
@endsection
