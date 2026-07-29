@php
    $pillPorSituacion = ['vigente' => 'pill--ok', 'vencida' => 'pill--no', 'futura' => 'pill--info'];
    $etiquetaSituacion = ['vigente' => 'Vigente', 'vencida' => 'Vencida', 'futura' => 'Aún no vigente'];
    // Sin acciones cuando la tabla se muestra solo de referencia (modal de licencia).
    $conAcciones = $conAcciones ?? true;
    $columnas = $conAcciones ? 8 : 7;
@endphp
<div class="card">
    <table>
        <thead>
            <tr>
                <th>Turno</th>
                <th>Día</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Desde</th>
                <th>Hasta</th>
                <th>Situación</th>
                @if ($conAcciones)
                    <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($asignaciones as $asignacion)
                <tr @class(['fila--inactiva' => $asignacion->situacion === 'vencida'])>
                    <td><strong>{{ trim((string) $asignacion->turno?->nombreTurno) ?: '—' }}</strong></td>
                    <td>{{ $asignacion->turno?->nombre_dia ?? '—' }}</td>
                    <td>{{ $asignacion->turno?->hEntrada?->format('H:i') ?? '—' }}</td>
                    <td>{{ $asignacion->turno?->hSalida?->format('H:i') ?? '—' }}</td>
                    <td>{{ $asignacion->desde?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $asignacion->hasta?->format('d/m/Y') ?? '—' }}</td>
                    <td>
                        <span class="pill {{ $pillPorSituacion[$asignacion->situacion] ?? 'pill--info' }}">
                            {{ $etiquetaSituacion[$asignacion->situacion] ?? $asignacion->situacion }}
                        </span>
                    </td>
                    @if ($conAcciones)
                        <td>
                            <div class="acciones">
                                {{-- Concluir es lo que corresponde cuando el funcionario dejó
                                     ese turno; eliminar, solo si la asignación se cargó mal. --}}
                                @if ($asignacion->situacion !== 'vencida')
                                    @can('update', $asignacion)
                                        <x-boton-concluir :accion="route('turnos-asignados.concluir', $asignacion)"
                                                          :mensaje="'Turno «'.trim((string) $asignacion->turno?->nombreTurno).'» de CI '.trim((string) $asignacion->ci).'.'"
                                                          origen="{{ $origenFicha ?? '' }}" />
                                    @endcan
                                @endif
                                @can('delete', $asignacion)
                                    <x-boton-eliminar :accion="route('turnos-asignados.destroy', $asignacion)"
                                                      :mensaje="'Se elimina la asignación del turno «'.trim((string) $asignacion->turno?->nombreTurno).'». Si el funcionario dejó ese turno, concluilo en vez de borrarlo.'"
                                                      ancla="turnos" />
                                @endcan
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $columnas }}" class="vacio">El funcionario no tiene turnos asignados en este filtro.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="paginacion">{{ $asignaciones->links() }}</div>
