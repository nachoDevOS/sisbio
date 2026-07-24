@extends('layouts.app')

@section('titulo', 'Días excepcionales')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-calendar-days /></span>
            <h1>Días excepcionales</h1>
        </div>
        <a href="{{ route('dias-excepcionales.create') }}" class="btn"><x-heroicon-o-plus />Nuevo día</a>
    </div>

    <p class="ayuda" style="margin: -.4rem 0 1rem;">Fechas que no se toman en cuenta para el control de asistencia.</p>

    <x-tabla-filtros :action="route('dias-excepcionales.index')" :busqueda="$busqueda"
                     :por-pagina="$porPagina" placeholder="Buscar por motivo o fecha…" />

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Motivo de inasistencia general</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($diasExcepcionales as $dia)
                    <tr>
                        <td>{{ $dia->id }}</td>
                        <td><strong>{{ $dia->fecha?->format('d/m/Y') }}</strong></td>
                        <td>{{ $dia->motivoInasistencia ?: '—' }}</td>
                        <td class="acciones">
                            <a href="{{ route('dias-excepcionales.edit', $dia) }}" class="btn-icon" title="Editar" aria-label="Editar"><x-heroicon-o-pencil-square /></a>
                            <form action="{{ route('dias-excepcionales.destroy', $dia) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar el día excepcional del {{ $dia->fecha?->format('d/m/Y') }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-icon btn-icon--peligro" title="Eliminar" aria-label="Eliminar"><x-heroicon-o-trash /></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="vacio">{{ $busqueda !== '' ? 'Sin días excepcionales para la búsqueda.' : 'Aún no hay días excepcionales registrados.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $diasExcepcionales->links() }}</div>
@endsection
