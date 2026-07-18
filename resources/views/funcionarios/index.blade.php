@extends('layouts.app')

@section('titulo', 'Funcionarios')

@section('contenido')
    <div class="cabecera">
        <h1>Funcionarios (SIA)</h1>
        <a href="{{ route('funcionarios.create') }}" class="btn"><x-heroicon-o-plus />Nuevo funcionario</a>
    </div>

    <form method="GET" action="{{ route('funcionarios.index') }}" class="toolbar">
        <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar por CI o nombre…" class="input">
        <button type="submit" class="btn"><x-heroicon-o-magnifying-glass />Buscar</button>
        @if ($busqueda !== '')
            <a href="{{ route('funcionarios.index') }}" class="btn btn--gris"><x-heroicon-o-x-mark />Limpiar</a>
        @endif
    </form>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>CI</th>
                    <th>Paterno</th>
                    <th>Materno</th>
                    <th>Nombres</th>
                    <th>PIN reloj</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($funcionarios as $persona)
                    <tr>
                        <td><strong>{{ trim($persona->IdPersona) }}</strong></td>
                        <td>{{ trim($persona->Paterno) }}</td>
                        <td>{{ trim($persona->Materno) ?: '—' }}</td>
                        <td>{{ trim($persona->Nombres) }}</td>
                        <td>{{ trim((string) $persona->PinReloj) ?: 'Sin PIN' }}</td>
                        <td class="acciones">
                            <a href="{{ route('funcionarios.show', $persona) }}" class="btn-icon btn-icon--gris" title="Ver" aria-label="Ver"><x-heroicon-o-eye /></a>
                            <a href="{{ route('funcionarios.edit', $persona) }}" class="btn-icon" title="Editar" aria-label="Editar"><x-heroicon-o-pencil-square /></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="vacio">Sin funcionarios en el criterio buscado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $funcionarios->links() }}</div>
@endsection
