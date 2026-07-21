{{-- Lista de marcaciones leídas en vivo del equipo. Mismo estilo de tabla que el resto del sitio (funcionarios/marcaciones). --}}
@if (! empty($error))
    <div class="aviso aviso--error">{{ $error }}</div>
@elseif ($marcaciones->isEmpty())
    <div class="vacio">El equipo no tiene marcaciones guardadas.</div>
@else
    <table>
        <thead>
            <tr>
                <th>CI / ID</th>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Hora</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($marcaciones as $m)
                @php
                    $fecha = $m['timestamp'] ? \Illuminate\Support\Carbon::parse($m['timestamp']) : null;
                @endphp
                <tr>
                    <td><strong>{{ $m['user_id'] }}</strong></td>
                    <td>{{ filled($m['nombre'] ?? null) ? $m['nombre'] : 'Sin nombre' }}</td>
                    <td>{{ $fecha?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $fecha?->format('H:i:s') ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
