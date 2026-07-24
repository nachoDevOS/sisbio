@extends('layouts.app')

@section('titulo', 'Funcionarios')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-user-group /></span>
            <h1>Funcionarios</h1>
        </div>
    </div>

    <x-tabla-filtros :action="route('funcionarios.index')" :busqueda="$busqueda"
                     :por-pagina="$porPagina" placeholder="Buscar por CI o nombre…" />

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
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
                        <td>{{ $persona->id }}</td>
                        <td><strong>{{ trim($persona->ci) }}</strong></td>
                        <td>{{ trim($persona->paterno) }}</td>
                        <td>{{ trim((string) $persona->materno) ?: '—' }}</td>
                        <td>{{ trim($persona->nombres) }}</td>
                        <td>{{ trim((string) $persona->pinReloj) ?: 'Sin PIN' }}</td>
                        <td class="acciones">
                            <a href="{{ route('funcionarios.show', $persona) }}" class="btn-icon btn-icon--gris" title="Ver" aria-label="Ver"><x-heroicon-o-eye /></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="vacio">Sin funcionarios en el criterio buscado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $funcionarios->links() }}</div>
@endsection
