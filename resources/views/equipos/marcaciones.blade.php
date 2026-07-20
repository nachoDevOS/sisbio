@extends('layouts.app')

@section('titulo', 'Marcaciones de ' . $equipo->nombre)

@section('contenido')
    <div class="cabecera">
        <h1>Marcaciones de «{{ $equipo->nombre }}»</h1>
        <div class="acciones">
            <a href="{{ route('equipos.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
        </div>
    </div>

    <div class="tarjeta">
        @include('equipos._marcaciones_lista', ['marcaciones' => $marcaciones, 'error' => $error])
    </div>
@endsection
