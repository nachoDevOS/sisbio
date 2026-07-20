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
        {{-- Vista compartida con la acción "Ver marcaciones" del recurso Filament: mismo look, misma lógica. --}}
        @include('filament.equipos.marcaciones', ['marcaciones' => $marcaciones, 'error' => $error])
    </div>
@endsection
