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
                     :por-pagina="$porPagina" placeholder="Buscar por CI o nombre…">
        <x-slot:filtros>
            <select name="fuente" onchange="this.form.submit()" aria-label="Fuente de datos">
                <option value="mamore" @selected($fuente === 'mamore')>Mamoré</option>
                <option value="siat" @selected($fuente === 'siat')>SIAT</option>
            </select>
        </x-slot:filtros>
    </x-tabla-filtros>

    @if ($errorFuente)
        <div class="aviso aviso--error">{{ $errorFuente }}</div>
    @endif

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre completo</th>
                    <th>Fecha nac.</th>
                    <th>PIN reloj</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($funcionarios as $persona)
                    <tr>
                        <td>{{ $persona['id'] }}</td>
                        <td>
                            <div class="persona-celda">
                                <span class="persona-foto"><x-heroicon-o-user /></span>
                                <div>
                                    <div class="persona-nombre">{{ $persona['nombre'] }}</div>
                                    <div class="persona-meta">
                                        {{ $persona['ci'] }}
                                        @if (!empty($persona['profesion']))
                                            <br>{{ $persona['profesion'] }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            {{ $persona['nacimiento'] ?: '—' }}
                            @if (!is_null($persona['edad']))
                                <div class="persona-meta">{{ $persona['edad'] }} años</div>
                            @endif
                        </td>
                        <td>{{ $persona['pinReloj'] ?: 'Sin PIN' }}</td>
                        <td class="acciones">
                            @if ($persona['ver'])
                                <a href="{{ $persona['ver'] }}" class="btn-icon btn-icon--gris" title="Ver" aria-label="Ver"><x-heroicon-o-eye /></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="vacio">Sin funcionarios en el criterio buscado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $funcionarios->links() }}</div>
@endsection
