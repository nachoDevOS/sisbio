@extends('layouts.app')

@section('titulo', 'Equipos biométricos')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-computer-desktop /></span>
            <h1>Equipos biométricos</h1>
        </div>
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
                    <th>Algoritmo</th>
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
                        <td>{{ $equipo->algoritmo ?? 'Sin detectar' }}</td>
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
                                <form action="{{ route('equipos.probar-conexion', $equipo) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn-icon" title="Probar conexión" aria-label="Probar conexión"><x-heroicon-o-signal /></button>
                                </form>
                                <a href="{{ route('equipos.marcaciones', $equipo) }}" class="btn-icon" title="Ver marcaciones" aria-label="Ver marcaciones"><x-heroicon-o-clock /></a>
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
                        <td colspan="8" class="vacio">Aún no hay equipos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">
        {{ $equipos->links() }}
    </div>
@endsection
