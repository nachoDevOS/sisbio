{{--
    Panel de marcaciones de la ficha del funcionario (local y Mamoré): filtros
    que disparan la carga AJAX del parcial funcionarios.marcaciones-list, igual
    que el listado principal.

    Variables: $ci (cédula a listar), $desde, $hasta, $tipo y $reporteUrl (URL
    del reporte imprimible, o null si esa cédula no está en la base local).
--}}
<div class="tarjeta" style="margin-top: 1.5rem;">
    <h2>Marcaciones</h2>

    <div class="tabla-filtros">
        <label class="tabla-filtros__mostrar">
            Mostrar
            <select id="m-paginate" aria-label="Cantidad de registros a mostrar">
                @foreach ([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}" @selected($n === 25)>{{ $n }}</option>
                @endforeach
            </select>
            registros
        </label>

        <div class="tabla-filtros__extra">
            <input type="date" id="m-desde" value="{{ $desde }}" aria-label="Desde">
            <input type="date" id="m-hasta" value="{{ $hasta }}" aria-label="Hasta">
            <select id="m-tipo" aria-label="Tipo">
                <option value="">Todos</option>
                <option value="{{ \App\Models\Asistencia::TIPO_RELOJ }}" @selected($tipo === \App\Models\Asistencia::TIPO_RELOJ)>R</option>
                <option value="{{ \App\Models\Asistencia::TIPO_A }}" @selected($tipo === \App\Models\Asistencia::TIPO_A)>A</option>
                <option value="{{ \App\Models\Asistencia::TIPO_MANUAL }}" @selected($tipo === \App\Models\Asistencia::TIPO_MANUAL)>M</option>
            </select>
            @if ($reporteUrl)
                <a id="m-reporte" class="btn btn--gris" target="_blank" rel="noopener" href="{{ $reporteUrl }}">
                    <x-heroicon-o-printer />Imprimir reporte
                </a>
            @endif
        </div>
    </div>

    {{-- Aquí se inyecta el parcial funcionarios.marcaciones-list (tabla + paginación). --}}
    <div id="m-results" style="min-height: 8rem;">
        <div class="vacio">Cargando…</div>
    </div>
</div>

<script>
    (function () {
        const url = @json(route('funcionarios.marcaciones.list'));
        const ci = @json($ci);
        const reporteBase = @json($reporteUrl);
        const resultados = document.getElementById('m-results');
        const selPaginate = document.getElementById('m-paginate');
        const dateDesde = document.getElementById('m-desde');
        const dateHasta = document.getElementById('m-hasta');
        const selTipo = document.getElementById('m-tipo');
        const enlaceReporte = document.getElementById('m-reporte');

        function filtros() {
            return { desde: dateDesde.value, hasta: dateHasta.value, tipo: selTipo.value };
        }

        // El reporte imprimible se abre con los mismos filtros que la tabla.
        function sincronizarReporte() {
            if (!enlaceReporte) { return; }
            enlaceReporte.href = `${reporteBase}?${new URLSearchParams(filtros()).toString()}`;
        }

        async function cargar(page = 1) {
            const params = new URLSearchParams({
                ci: ci,
                ...filtros(),
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
                resultados.innerHTML = '<div class="aviso aviso--error">No se pudieron cargar las marcaciones. Reintentá.</div>';
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
        for (const control of [dateDesde, dateHasta, selTipo]) {
            control.addEventListener('change', () => { sincronizarReporte(); cargar(1); });
        }

        sincronizarReporte();
        cargar(1);
    })();
</script>
