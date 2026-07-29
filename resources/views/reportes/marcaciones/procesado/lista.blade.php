@php
    use App\Models\Turno;
    use App\Services\ProcesadorAsistencia as P;

    // $persona es la ficha resuelta (Mamoré, con la base local como respaldo).
    $nombreEmpleado = $persona['nombreFormal'] ?: $persona['nombre'];
    $parametros = ['persona' => $persona['ci'], 'desde' => $desde, 'hasta' => $hasta];
@endphp

{{-- Partial: se inyecta bajo el filtro del reporte vía AJAX (no lleva layout). --}}
<div class="card card--padded">
    <div class="cabecera" style="margin-bottom: 1rem;">
        <div>
            <strong>{{ $nombreEmpleado ?: 'Funcionario' }}</strong> · CI {{ $persona['ci'] }} ·
            PIN reloj {{ $persona['pinReloj'] ?: '—' }}<br>
            @if (!empty($persona['cargo']))
                <span style="color: var(--muted);">
                    {{ $persona['cargo'] }}{{ empty($persona['direccion']) ? '' : ' · '.$persona['direccion'] }}
                </span><br>
            @endif
            <span style="color: var(--muted);">
                Rango: {{ $desde ?: '—' }} a {{ $hasta ?: '—' }} · {{ $totales['dias'] }} día(s)
            </span>
        </div>
        <div class="acciones">
            <a class="btn" target="_blank" rel="noopener"
               href="{{ route('reportes.marcaciones.procesado.generar', $parametros + ['print' => 1]) }}"><x-heroicon-o-printer />Imprimir</a>
            <a class="btn btn--gris"
               href="{{ route('reportes.marcaciones.procesado.generar', $parametros + ['print' => 2]) }}"><x-heroicon-o-table-cells />Excel (CSV)</a>
        </div>
    </div>

    {{-- Resumen del rango: las horas del período viven acá, no en cada fila. --}}
    <div class="toolbar" style="gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1rem;">
        <div>
            <small style="color: var(--muted); display: block;">Horas computadas</small>
            <strong style="font-size: 1.1rem;">{{ P::duracion($totales['computado']) }}</strong>
            <span style="color: var(--muted);">de {{ P::duracion($totales['esperado']) }}</span>
        </div>
        <div>
            <small style="color: var(--muted); display: block;">Saldo</small>
            <strong style="font-size: 1.1rem; color: {{ $totales['saldo'] < 0 ? '#991b1b' : '#166534' }};">
                {{ $totales['saldo'] > 0 ? '+' : '' }}{{ P::duracion($totales['saldo']) }}
            </strong>
        </div>
        <div>
            <small style="color: var(--muted); display: block;">Atraso acumulado</small>
            <strong style="font-size: 1.1rem;">{{ P::desvio($totales['atraso']) }}</strong>
        </div>
        <div>
            <small style="color: var(--muted); display: block;">Salida anticipada</small>
            <strong style="font-size: 1.1rem;">{{ P::desvio($totales['anticipo']) }}</strong>
        </div>
        <div style="flex: 1; min-width: 14rem;">
            <small style="color: var(--muted); display: block; margin-bottom: .25rem;">Días por estado</small>
            @foreach ($totales['porEstado'] as $estado => $cantidad)
                <span class="pill {{ P::COLORES[$estado] ?? 'pill--info' }}" style="margin-right: .25rem;">
                    {{ P::ETIQUETAS[$estado] ?? $estado }}: {{ $cantidad }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- Columnas del reporte del sistema de escritorio viejo, con «Día» sumado
         adelante. Las horas del turno no van por fila: están en el resumen. --}}
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Día</th>
                    <th>Turno</th>
                    <th>Entró</th>
                    <th>Salió</th>
                    <th>Atraso</th>
                    <th>Abandono</th>
                    <th>Falta</th>
                    <th>Entrada lic.</th>
                    <th>Salida lic.</th>
                    <th title="Licencia de turno completo">T.C.</th>
                    <th title="Con goce de haberes">C.G.H.</th>
                    <th>Motivo licencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dias as $dia)
                    @php
                        $nombreDia = Turno::DIAS[$dia['fecha']->dayOfWeek + 1] ?? '—';
                        $filas = max(1, count($dia['bloques']));
                    @endphp

                    @if ($dia['bloques'] === [])
                        {{-- Día resuelto sin mirar turnos: excepcional o sin turno asignado. --}}
                        <tr>
                            <td><strong>{{ $dia['fecha']->format('d/m/Y') }}</strong></td>
                            <td>{{ $nombreDia }}</td>
                            <td colspan="10" style="color: var(--muted);">
                                <span class="pill {{ P::COLORES[$dia['estado']] ?? 'pill--info' }}">{{ P::ETIQUETAS[$dia['estado']] ?? $dia['estado'] }}</span>
                                @if ($dia['marcas'] !== [])
                                    <small>· marcó {{ collect($dia['marcas'])->map(fn ($s) => P::hora($s))->implode(', ') }}</small>
                                @endif
                            </td>
                            <td>{{ $dia['motivo'] ?: '—' }}</td>
                        </tr>
                    @else
                        @foreach ($dia['bloques'] as $indice => $bloque)
                            @php
                                $licencia = $bloque['licencia'];
                                $falta = P::FALTAS[$bloque['estado']] ?? '';
                            @endphp
                            <tr>
                                @if ($indice === 0)
                                    <td rowspan="{{ $filas }}"><strong>{{ $dia['fecha']->format('d/m/Y') }}</strong></td>
                                    <td rowspan="{{ $filas }}">{{ $nombreDia }}</td>
                                @endif
                                <td>
                                    {{ trim((string) $bloque['turno']->nombreTurno) }}
                                    @foreach ($bloque['avisos'] as $aviso)
                                        <br><small style="color: #92400e;">⚠ {{ $aviso }}</small>
                                    @endforeach
                                </td>
                                <td>
                                    {{ $bloque['entrada'] === null ? '' : P::hora($bloque['entrada']) }}
                                    @unless ($bloque['entradaExigida'])
                                        <small style="color: var(--muted);">licencia</small>
                                    @endunless
                                </td>
                                <td>
                                    {{ $bloque['salida'] === null ? '' : P::hora($bloque['salida']) }}
                                    @unless ($bloque['salidaExigida'])
                                        <small style="color: var(--muted);">licencia</small>
                                    @endunless
                                </td>
                                <td style="color: #92400e; font-weight: 600;">{{ $bloque['atraso'] > 0 ? P::desvio($bloque['atraso']) : '' }}</td>
                                <td style="color: #991b1b; font-weight: 700;">{{ $bloque['estado'] === P::ABANDONO ? 'ABANDONO' : '' }}</td>
                                <td style="color: #991b1b; font-weight: 700;">{{ $falta }}</td>
                                <td>{{ $licencia?->lEntra?->format('H:i') ?? '' }}</td>
                                <td>{{ $licencia?->lSale?->format('H:i') ?? '' }}</td>
                                <td>{{ $licencia === null ? '' : ($licencia->tCompleto ? 'Sí' : 'No') }}</td>
                                <td>{{ $licencia === null ? '' : ($licencia->goceHaberes ? 'Sí' : 'No') }}</td>
                                <td>{{ $licencia?->motivo ?? '' }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr><td colspan="13" class="vacio">Sin días en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p style="color: var(--muted); font-size: .8rem; margin-bottom: 0;">
        <strong>T.C.</strong> = licencia de turno completo · <strong>C.G.H.</strong> = con goce de haberes.
        El atraso se dispara con la tolerancia del turno y se mide contra su hora de entrada.
        <strong>Abandono</strong> = se retiró antes de la mínima hora de salida, o no marcó un tramo que la licencia no cubría.
    </p>
</div>
