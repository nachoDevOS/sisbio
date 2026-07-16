@extends('layouts.app')

@section('titulo', 'Marcaciones')

@section('contenido')
    <div class="cabecera">
        <h1>Marcaciones (SIA)</h1>
    </div>

    <form method="GET" action="{{ route('marcaciones.index') }}"
          style="margin-bottom: 1.25rem; display: flex; gap: .6rem; align-items: flex-end; flex-wrap: wrap;">
        <div class="campo" style="margin: 0;">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ $desde }}"
                   style="padding: .5rem; border: 1px solid #e5e7eb; border-radius: .5rem;">
        </div>
        <div class="campo" style="margin: 0;">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $hasta }}"
                   style="padding: .5rem; border: 1px solid #e5e7eb; border-radius: .5rem;">
        </div>
        <button type="submit" class="btn">Filtrar</button>
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

    <div style="margin-top: 1rem;">{{ $marcaciones->links() }}</div>
@endsection
