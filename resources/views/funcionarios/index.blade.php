@extends('layouts.app')

@section('titulo', 'Funcionarios')

@section('contenido')
    <div class="cabecera">
        <h1>Funcionarios (SIA)</h1>
    </div>

    <form method="GET" action="{{ route('funcionarios.index') }}" style="margin-bottom: 1.25rem; display: flex; gap: .6rem;">
        <input type="text" name="q" value="{{ $busqueda }}" placeholder="Buscar por CI o nombre…"
               style="flex: 1; padding: .55rem .7rem; border: 1px solid #e5e7eb; border-radius: .5rem;">
        <button type="submit" class="btn">Buscar</button>
        @if ($busqueda !== '')
            <a href="{{ route('funcionarios.index') }}" class="btn btn--gris">Limpiar</a>
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
                    </tr>
                @empty
                    <tr><td colspan="5" class="vacio">Sin funcionarios en el criterio buscado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $funcionarios->links() }}</div>
@endsection
