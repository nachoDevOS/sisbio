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
                <th>ID</th>
                <th>CI</th>
                <th>Funcionario</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Tipo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($marcaciones as $marcacion)
                <tr>
                    <td>{{ $marcacion->id }}</td>
                    <td>{{ trim((string) $marcacion->ci) }}</td>
                    <td>
                        @if ($nombres[trim((string) $marcacion->ci)] ?? null)
                            {{ $nombres[trim((string) $marcacion->ci)] }}
                        @else
                            <span style="color: var(--muted); font-style: italic;">Sin persona</span>
                        @endif
                    </td>
                    <td>{{ $marcacion->fecha?->format('d/m/Y') }}</td>
                    <td>{{ $marcacion->hora?->format('H:i:s') }}</td>
                    <td><span class="pill {{ $pillPorTipo[trim((string) $marcacion->tipo)] ?? 'pill--info' }}">{{ trim((string) $marcacion->tipo) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="paginacion">{{ $marcaciones->links() }}</div>
