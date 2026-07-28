@php
    $pillPorTipo = [
        \App\Models\Asistencia::TIPO_RELOJ => 'pill--ok',
        \App\Models\Asistencia::TIPO_MANUAL => 'pill--advertencia',
    ];
@endphp
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($marcaciones as $marcacion)
                <tr>
                    <td>{{ $marcacion->fecha?->format('d/m/Y') }}</td>
                    <td>{{ $marcacion->hora?->format('H:i:s') }}</td>
                    <td><span class="pill {{ $pillPorTipo[trim((string) $marcacion->tipo)] ?? 'pill--info' }}">{{ trim((string) $marcacion->tipo) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="3" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="paginacion">{{ $marcaciones->links() }}</div>
