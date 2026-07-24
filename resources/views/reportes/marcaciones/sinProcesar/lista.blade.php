@php
    $nombreEmpleado = collect([$persona->paterno, $persona->materno, $persona->nombres])
        ->map(fn ($parte) => trim((string) $parte))
        ->filter()
        ->implode(' ');
    $parametros = ['persona' => trim($persona->ci), 'desde' => $desde, 'hasta' => $hasta, 'tipo' => $tipo];
@endphp

{{-- Partial: se inyecta bajo el filtro del reporte vía AJAX (no lleva layout). --}}
<div class="card card--padded">
    <div class="cabecera" style="margin-bottom: 1rem;">
        <div>
            <strong>{{ $nombreEmpleado ?: 'Funcionario' }}</strong> · CI {{ trim($persona->ci) }} ·
            PIN reloj {{ trim((string) $persona->pinReloj) ?: '—' }}<br>
            <span style="color: var(--muted);">
                Rango: {{ $desde ?: '—' }} a {{ $hasta ?: '—' }} · Total: {{ $marcaciones->count() }} registro(s)
            </span>
        </div>
        <div class="acciones">
            <a class="btn" target="_blank" rel="noopener"
               href="{{ route('reportes.marcaciones.sin-procesar.generar', $parametros + ['print' => 1]) }}"><x-heroicon-o-printer />Imprimir</a>
            <a class="btn btn--gris"
               href="{{ route('reportes.marcaciones.sin-procesar.generar', $parametros + ['print' => 2]) }}"><x-heroicon-o-table-cells />Excel (CSV)</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 3rem;">N.º</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($marcaciones as $marcacion)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $marcacion->fecha?->format('d/m/Y') }}</td>
                    <td>{{ $marcacion->hora?->format('H:i:s') }}</td>
                    <td>{{ trim((string) $marcacion->tipo) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
