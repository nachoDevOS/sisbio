@extends('layouts.app')

@section('titulo', 'Roles')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-shield-check /></span>
            <h1>Roles y permisos</h1>
        </div>
        <a href="{{ route('roles.create') }}" class="btn"><x-heroicon-o-plus />Nuevo rol</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Permisos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $rol)
                    <tr>
                        <td><strong>{{ $rol->name }}</strong></td>
                        <td>{{ $rol->permissions_count }}</td>
                        <td>
                            <div class="acciones">
                                <a href="{{ route('roles.edit', $rol) }}" class="btn-icon" title="Editar" aria-label="Editar"><x-heroicon-o-pencil-square /></a>
                                @if ($rol->name !== 'super_admin')
                                    <form action="{{ route('roles.destroy', $rol) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar el rol «{{ $rol->name }}»?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon btn-icon--peligro" title="Eliminar" aria-label="Eliminar"><x-heroicon-o-trash /></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="vacio">No hay roles registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $roles->links() }}</div>
@endsection
