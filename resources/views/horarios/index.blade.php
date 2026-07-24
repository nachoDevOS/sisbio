@extends('layouts.app')

@section('titulo', 'Horarios')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-clock /></span>
            <h1>Administrador de horarios</h1>
        </div>
        <a href="{{ route('horarios.create') }}" class="btn"><x-heroicon-o-plus />Nuevo horario</a>
    </div>

    <div class="card card--padded" style="margin-bottom: 1rem;">
        <form method="GET" action="{{ route('horarios.index') }}" class="toolbar">
            <div class="campo" style="flex: 1; min-width: 12rem;">
                <label for="buscar">Buscar horario</label>
                <input type="text" id="buscar" name="buscar" value="{{ $buscar }}" class="input"
                       placeholder="Nombre del horario, ej.: «08:00 - 16:00»">
            </div>
            <div class="campo">
                <label for="dia">Día</label>
                <select id="dia" name="dia" class="input">
                    <option value="">Todos los días</option>
                    @foreach (\App\Models\Turno::DIAS as $numero => $nombre)
                        <option value="{{ $numero }}" @selected($dia === (string) $numero)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn"><x-heroicon-o-funnel />Filtrar</button>
            @if ($buscar !== '' || $dia !== '')
                <a href="{{ route('horarios.index') }}" class="btn btn--gris"><x-heroicon-o-x-mark />Limpiar</a>
            @endif
        </form>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Día</th>
                    <th>Horario</th>
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
                                      onsubmit="return confirm('¿Eliminar el horario «{{ trim($horario->nombreTurno) }}»?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon btn-icon--peligro" title="Eliminar" aria-label="Eliminar"><x-heroicon-o-trash /></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="vacio">Aún no hay horarios registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">
        {{ $horarios->links() }}
    </div>
@endsection
