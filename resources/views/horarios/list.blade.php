<div class="card">
    <table>
        <thead>
            <tr>
                <th>Día</th>
                <th>Turno</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Tol. entrada</th>
                <th>Tol. salida</th>
                <th>Horas</th>
                <th>Día sig.</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($horarios as $horario)
                <tr>
                    <td><strong>{{ $horario->nombre_dia }}</strong></td>
                    <td>{{ trim($horario->nombreTurno) }}</td>
                    <td>{{ $horario->hEntrada?->format('H:i') }}</td>
                    <td>{{ $horario->hSalida?->format('H:i') }}</td>
                    <td>{{ $horario->hTolerancia?->format('H:i') }}</td>
                    <td>{{ $horario->sTolerancia?->format('H:i') }}</td>
                    <td>{{ number_format((float) $horario->hTrabajadas, 2) }}</td>
                    <td>
                        <span class="pill {{ $horario->siguienteDia ? 'pill--advertencia' : 'pill--no' }}">
                            {{ $horario->siguienteDia ? 'Sí' : 'No' }}
                        </span>
                    </td>
                    <td>
                        <div class="acciones">
                            <a href="{{ route('horarios.show', $horario) }}" class="btn-icon btn-icon--gris" title="Ver" aria-label="Ver"><x-heroicon-o-eye /></a>
                            <a href="{{ route('horarios.edit', $horario) }}" class="btn-icon" title="Editar" aria-label="Editar"><x-heroicon-o-pencil-square /></a>
                            <form action="{{ route('horarios.destroy', $horario) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar el turno «{{ trim($horario->nombreTurno) }}»?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-icon--peligro" title="Eliminar" aria-label="Eliminar"><x-heroicon-o-trash /></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="vacio">Aún no hay turnos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="paginacion">
    {{ $horarios->links() }}
</div>
