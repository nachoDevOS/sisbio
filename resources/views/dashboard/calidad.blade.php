@php
    use App\Models\Asistencia;

    $numero = fn (?int $n): string => $n === null ? '—' : number_format($n, 0, ',', '.');
@endphp

<dl class="datos-lista">
    <div>
        <dt>Marcaciones registradas</dt>
        <dd>{{ $numero($calidad['total']) }}</dd>
    </div>
    @foreach ($calidad['tipos'] as $tipo => $total)
        <div>
            <dt>Tipo «{{ $tipo }}» · {{ Asistencia::TIPOS[$tipo] ?? 'Desconocido' }}</dt>
            <dd>{{ $numero($total) }}</dd>
        </div>
    @endforeach
    <div class="{{ $calidad['antes_2000'] > 0 ? 'datos-lista__alerta' : '' }}">
        <dt>Con fecha anterior al 2000</dt>
        <dd>{{ $numero($calidad['antes_2000']) }}</dd>
    </div>
    <div class="{{ $calidad['futuras'] > 0 ? 'datos-lista__alerta' : '' }}">
        <dt>Con fecha futura (reloj desajustado)</dt>
        <dd>{{ $numero($calidad['futuras']) }}</dd>
    </div>
</dl>

@if ($calidad['antes_2000'] > 0 || $calidad['futuras'] > 0)
    <p class="datos-lista__nota">
        Las fechas imposibles vienen del reloj con la batería del RTC agotada.
        Quedan guardadas, pero fuera de cualquier rango que se consulte.
    </p>
@endif
