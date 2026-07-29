@php
    use App\Models\Asistencia;

    $pillPorTipo = [
        Asistencia::TIPO_RELOJ => 'pill--ok',
        Asistencia::TIPO_MANUAL => 'pill--advertencia',
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
                <th>Origen</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($marcaciones as $marcacion)
                <tr>
                    <td>{{ $marcacion->id }}</td>
                    <td>{{ trim((string) $marcacion->ci) }}</td>
                    <td>
                        @php($ficha = $fichas[trim((string) $marcacion->ci)] ?? null)
                        @if ($ficha)
                            {{ $ficha['nombre'] }}
                            @if (!empty($ficha['cargo']))
                                <div class="ayuda">{{ $ficha['cargo'] }}</div>
                            @endif
                        @else
                            <span style="color: var(--muted); font-style: italic;">Sin persona</span>
                        @endif
                    </td>
                    <td>{{ $marcacion->fecha?->format('d/m/Y') }}</td>
                    <td>{{ $marcacion->hora?->format('H:i:s') }}</td>
                    @php($tipoMarcacion = trim((string) $marcacion->tipo))
                    <td style="white-space: nowrap;">
                        <span class="pill {{ $pillPorTipo[$tipoMarcacion] ?? 'pill--info' }}">{{ $tipoMarcacion }}</span>
                        <span class="ayuda">{{ Asistencia::TIPOS[$tipoMarcacion] ?? '—' }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="paginacion">{{ $marcaciones->links() }}</div>
