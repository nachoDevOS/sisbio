@extends('layouts.app')

@section('titulo', 'Marcaciones de ' . $equipo->nombre)

@section('contenido')
    <div class="cabecera">
        <h1>Marcaciones de «{{ $equipo->nombre }}»</h1>
        <div class="acciones">
            @if (! $error)
                <a href="{{ route('equipos.marcaciones.exportar', $equipo) }}" class="btn btn--gris"><x-heroicon-o-arrow-down-tray />Descargar CSV</a>
            @endif
            <a href="{{ route('equipos.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
        </div>
    </div>

    <div class="card">
        @include('equipos._marcaciones_lista', ['marcaciones' => $marcaciones, 'error' => $error])
    </div>

    @if (! $error)
        <div class="paginacion">{{ $marcaciones->links() }}</div>
    @endif
@endsection
