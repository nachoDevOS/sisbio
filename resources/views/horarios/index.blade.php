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

    <x-tabla-filtros :action="route('horarios.index')" :busqueda="$buscar" campo="buscar"
                     :por-pagina="$porPagina" placeholder="Buscar por nombre del horario…">
        <x-slot:filtros>
            <select name="dia" onchange="this.form.submit()">
                <option value="">Todos los días</option>
                @foreach (\App\Models\Turno::DIAS as $numero => $nombre)
                    <option value="{{ $numero }}" @selected($dia === (string) $numero)>{{ $nombre }}</option>
                @endforeach
            </select>
        </x-slot:filtros>
    </x-tabla-filtros>

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
