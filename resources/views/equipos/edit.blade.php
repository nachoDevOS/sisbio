@extends('layouts.app')

@section('titulo', 'Editar equipo')

@section('contenido')
    <div class="cabecera">
        <h1>Editar «{{ $equipo->nombre }}»</h1>
        <a href="{{ route('equipos.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
    </div>

    <div class="card card--padded">
        <form action="{{ route('equipos.update', $equipo) }}" method="POST">
            @csrf
            @method('PUT')
            @include('equipos._form')

            <div class="form-acciones">
                <button type="submit" class="btn"><x-heroicon-o-check />Guardar cambios</button>
                <a href="{{ route('equipos.index') }}" class="btn btn--gris"><x-heroicon-o-x-mark />Cancelar</a>
            </div>
        </form>
    </div>
@endsection
