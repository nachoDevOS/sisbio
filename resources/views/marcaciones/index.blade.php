@extends('layouts.app')

@section('titulo', 'Marcaciones')

@php
    $pillPorTipo = [
        \App\Models\Sia\Asistencia::TIPO_RELOJ => 'pill--ok',
        \App\Models\Sia\Asistencia::TIPO_MANUAL => 'pill--advertencia',
    ];
@endphp

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-finger-print /></span>
            <h1>Marcaciones (SIA)</h1>
        </div>
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
        <div class="campo">
            <label for="buscar">Buscar</label>
            <input type="text" id="buscar" name="buscar" value="{{ $buscar }}" placeholder="CI o nombre…" class="input">
        </div>
        <div class="campo">
            <label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" class="input">
                <option value="">Todos</option>
                <option value="{{ \App\Models\Sia\Asistencia::TIPO_RELOJ }}" @selected($tipo === \App\Models\Sia\Asistencia::TIPO_RELOJ)>R</option>
                <option value="{{ \App\Models\Sia\Asistencia::TIPO_A }}" @selected($tipo === \App\Models\Sia\Asistencia::TIPO_A)>A</option>
                <option value="{{ \App\Models\Sia\Asistencia::TIPO_MANUAL }}" @selected($tipo === \App\Models\Sia\Asistencia::TIPO_MANUAL)>M</option>
            </select>
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
                        <td><span class="pill {{ $pillPorTipo[trim((string) $marcacion->Tipo)] ?? 'pill--info' }}">{{ trim((string) $marcacion->Tipo) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $marcaciones->links() }}</div>
@endsection
