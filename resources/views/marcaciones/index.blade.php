@extends('layouts.app')

@section('titulo', 'Marcaciones')

@php
    $pillPorTipo = [
        \App\Models\Asistencia::TIPO_RELOJ => 'pill--ok',
        \App\Models\Asistencia::TIPO_MANUAL => 'pill--advertencia',
    ];
@endphp

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-finger-print /></span>
            <h1>Marcaciones</h1>
        </div>
        @can('create', \App\Models\Asistencia::class)
            <form method="POST" action="{{ route('marcaciones.importar') }}" enctype="multipart/form-data" style="display: flex; align-items: flex-start; gap: .5rem;">
                @csrf
                <div>
                    <input type="file" name="archivo" accept=".csv,text/csv" required class="input" style="width: auto;">
                    @error('archivo') <div class="error">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn"><x-heroicon-o-arrow-up-tray />Importar CSV</button>
            </form>
        @endcan
    </div>

    <x-tabla-filtros :action="route('marcaciones.index')" :busqueda="$buscar" campo="buscar"
                     :por-pagina="$porPagina" placeholder="Buscar por CI o nombre…">
        <x-slot:filtros>
            <input type="date" name="desde" value="{{ $desde }}" onchange="this.form.submit()" aria-label="Desde">
            <input type="date" name="hasta" value="{{ $hasta }}" onchange="this.form.submit()" aria-label="Hasta">
            <select name="tipo" onchange="this.form.submit()" aria-label="Tipo">
                <option value="">Todos</option>
                <option value="{{ \App\Models\Asistencia::TIPO_RELOJ }}" @selected($tipo === \App\Models\Asistencia::TIPO_RELOJ)>R</option>
                <option value="{{ \App\Models\Asistencia::TIPO_A }}" @selected($tipo === \App\Models\Asistencia::TIPO_A)>A</option>
                <option value="{{ \App\Models\Asistencia::TIPO_MANUAL }}" @selected($tipo === \App\Models\Asistencia::TIPO_MANUAL)>M</option>
            </select>
        </x-slot:filtros>
    </x-tabla-filtros>

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
                        <td>{{ trim((string) $marcacion->ci) }}</td>
                        <td>{{ $marcacion->persona?->nombre_completo ?? '—' }}</td>
                        <td>{{ $marcacion->fecha?->format('d/m/Y') }}</td>
                        <td>{{ $marcacion->hora?->format('H:i:s') }}</td>
                        <td><span class="pill {{ $pillPorTipo[trim((string) $marcacion->tipo)] ?? 'pill--info' }}">{{ trim((string) $marcacion->tipo) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $marcaciones->links() }}</div>
@endsection
