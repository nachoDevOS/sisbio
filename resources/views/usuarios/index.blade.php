@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    <div class="cabecera">
        <h1>Usuarios del panel</h1>
        <a href="{{ route('usuarios.create') }}" class="btn">+ Nuevo usuario</a>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Roles</th>
                    <th>Creado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td><strong>{{ $usuario->name }}</strong></td>
                        <td>{{ $usuario->email }}</td>
                        <td>
                            @forelse ($usuario->roles as $rol)
                                <span class="pill pill--ok">{{ $rol->name }}</span>
                            @empty
                                <span style="color: #9ca3af;">Sin roles</span>
                            @endforelse
                        </td>
                        <td>{{ $usuario->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="acciones">
                                <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn--gris btn--sm">Editar</a>
                                <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar al usuario «{{ $usuario->name }}»?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn--peligro btn--sm">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="vacio">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $usuarios->links() }}</div>
@endsection
