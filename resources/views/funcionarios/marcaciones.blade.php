@extends('layouts.app')

@section('titulo', 'Marcaciones de ' . trim($persona->IdPersona))

@php
    $tipos = ['R' => 'Reloj', 'M' => 'Manual', 'A' => 'A'];
@endphp

@section('contenido')
    <div class="cabecera">
        <h1>Marcaciones · {{ $persona->nombre_completo ?: 'CI ' . trim($persona->IdPersona) }}</h1>
        <div class="acciones">
            <a href="{{ route('funcionarios.show', $persona) }}" class="btn btn--gris">← Ficha</a>
        </div>
    </div>

    <form method="GET" action="{{ route('funcionarios.marcaciones', $persona) }}"
          style="margin-bottom: 1.25rem; display: flex; gap: .6rem; align-items: flex-end; flex-wrap: wrap;">
        <div class="campo" style="margin: 0;">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ $desde }}">
        </div>
        <div class="campo" style="margin: 0;">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ $hasta }}">
        </div>
        <button type="submit" class="btn">Filtrar</button>
    </form>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($marcaciones as $marcacion)
                    <tr>
                        <td>{{ $marcacion->Fecha?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $marcacion->Hora?->format('H:i:s') ?? '—' }}</td>
                        <td><span class="pill pill--ok">{{ $tipos[trim((string) $marcacion->Tipo)] ?? trim((string) $marcacion->Tipo) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">{{ $marcaciones->links() }}</div>
@endsection
