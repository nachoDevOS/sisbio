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
            <div style="display: flex; align-items: flex-start; gap: .5rem; flex-wrap: wrap;"
                 x-data="{ nueva: {{ $errors->hasAny(['ci', 'fecha', 'hora']) ? 'true' : 'false' }} }">
                <button type="button" class="btn" x-on:click="nueva = true"><x-heroicon-o-plus />Nueva marcación</button>

                <form method="POST" action="{{ route('marcaciones.importar') }}" enctype="multipart/form-data" style="display: flex; align-items: flex-start; gap: .5rem;">
                    @csrf
                    <div>
                        <input type="file" name="archivo" accept=".csv,text/csv" required class="input" style="width: auto;">
                        @error('archivo') <div class="error">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn"><x-heroicon-o-arrow-up-tray />Importar CSV</button>
                </form>

                {{-- Modal de alta manual: crea una marcación tipo M en la base local. --}}
                <div class="modal-fondo" x-show="nueva" x-cloak
                     x-on:click.self="nueva = false" x-on:keydown.escape.window="nueva = false">
                    <div class="modal-caja">
                        <h2>Nueva marcación manual</h2>
                        <form method="POST" action="{{ route('marcaciones.store') }}">
                            @csrf
                            <div class="campo">
                                <label for="ci">CI del funcionario <span class="req">*</span></label>
                                <input type="text" id="ci" name="ci" value="{{ old('ci') }}" required>
                                @error('ci') <div class="error">{{ $message }}</div> @enderror
                            </div>
                            <div class="grid-2">
                                <div class="campo">
                                    <label for="fecha_manual">Fecha <span class="req">*</span></label>
                                    <input type="date" id="fecha_manual" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" required>
                                    @error('fecha') <div class="error">{{ $message }}</div> @enderror
                                </div>
                                <div class="campo">
                                    <label for="hora_manual">Hora <span class="req">*</span></label>
                                    <input type="time" id="hora_manual" name="hora" step="1" value="{{ old('hora') }}" required>
                                    @error('hora') <div class="error">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <p class="ayuda">Se registra como tipo <strong>M</strong> (manual).</p>
                            <div class="modal-acciones">
                                <button type="button" class="btn btn--gris" x-on:click="nueva = false"><x-heroicon-o-x-mark />Cancelar</button>
                                <button type="submit" class="btn"><x-heroicon-o-check />Registrar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
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
                    <th>ID</th>
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
                        <td>{{ $marcacion->id }}</td>
                        <td>{{ trim((string) $marcacion->ci) }}</td>
                        <td>{{ $marcacion->persona?->nombre_completo ?? '—' }}</td>
                        <td>{{ $marcacion->fecha?->format('d/m/Y') }}</td>
                        <td>{{ $marcacion->hora?->format('H:i:s') }}</td>
                        <td><span class="pill {{ $pillPorTipo[trim((string) $marcacion->tipo)] ?? 'pill--info' }}">{{ trim((string) $marcacion->tipo) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="vacio">Sin marcaciones en el rango seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">{{ $marcaciones->links() }}</div>
@endsection
