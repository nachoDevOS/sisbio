@extends('layouts.app')

@section('titulo', 'Equipos biométricos')

@section('contenido')
    <div class="cabecera">
        <h1>Equipos biométricos</h1>
        <a href="{{ route('equipos.create') }}" class="btn"><x-heroicon-o-plus />Nuevo equipo</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>IP</th>
                    <th>Puerto</th>
                    <th>Ubicación</th>
                    <th>En línea</th>
                    <th>Activo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equipos as $equipo)
                    <tr>
                        <td><a href="{{ route('equipos.show', $equipo) }}"><strong>{{ $equipo->nombre }}</strong></a></td>
                        <td>{{ $equipo->ip }}</td>
                        <td>{{ $equipo->puerto }}</td>
                        <td>{{ $equipo->ubicacion ?? '—' }}</td>
                        <td>
                            <span class="pill {{ $equipo->en_linea ? 'pill--ok' : 'pill--no' }}">
                                {{ $equipo->en_linea ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <span class="pill {{ $equipo->activo ? 'pill--ok' : 'pill--no' }}">
                                {{ $equipo->activo ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td>
                            <div class="acciones">
                                <a href="{{ route('equipos.edit', $equipo) }}" class="btn-icon" title="Editar" aria-label="Editar"><x-heroicon-o-pencil-square /></a>
                                <div class="dropdown" x-data="{ open: false }" x-on:click.outside="open = false">
                                    <button type="button" class="dropdown-toggle" x-on:click="open = !open" aria-haspopup="true" :aria-expanded="open">
                                        Mas <x-heroicon-o-chevron-down />
                                    </button>
                                    <div class="dropdown-menu" x-show="open" x-cloak x-transition.opacity.duration.100ms>
                                        <form action="{{ route('equipos.destroy', $equipo) }}" method="POST"
                                              onsubmit="return confirm('¿Eliminar el equipo «{{ $equipo->nombre }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="peligro"><x-heroicon-o-trash />Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="vacio">Aún no hay equipos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">
        {{ $equipos->links() }}
    </div>
@endsection
