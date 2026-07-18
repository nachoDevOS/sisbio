@extends('layouts.app')

@section('titulo', 'Marcaciones')

@section('contenido')
    <div class="cabecera">
        <h1>Marcaciones (SIA)</h1>
    </div>

    <form method="GET" action="{{ route('marcaciones.index') }}" class="toolbar">
        <div class="campo">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ $desde }}" class="input">
        </div>
        <div class="campo">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $hasta }}" class="input">
        </div>
        <button type="submit" class="btn"><x-heroicon-o-funnel />Filtrar</button>
    </form>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>CI</th>
                    <th>Funcionario</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($marcaciones as $marcacion)
                    <tr>
                        <td>{{ trim((string) $marcacion->IdPersona) }}</td>
                        <td>{{ $marcacion->persona?->nombre_completo ?? '—' }}</td>
                        <td>{{ $marcacion->Fecha?->format('d/m/Y') }}</td>
                        <td>{{ $marcacion->Hora?->format('H:i:s') }}</td>
                        <td><span class="pill pill--ok">{{ trim((string) $marcacion->Tipo) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $marcaciones->links() }}</div>
@endsection
