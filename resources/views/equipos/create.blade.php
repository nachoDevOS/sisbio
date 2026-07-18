@extends('layouts.app')

@section('titulo', 'Nuevo equipo')

@section('contenido')
    <div class="cabecera">
        <h1>Nuevo equipo</h1>
        <a href="{{ route('equipos.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
    </div>

    <div class="card card--padded">
        <form action="{{ route('equipos.store') }}" method="POST">
            @csrf
            @include('equipos._form')

            <div class="form-acciones">
                <button type="submit" class="btn"><x-heroicon-o-check />Registrar equipo</button>
                <a href="{{ route('equipos.index') }}" class="btn btn--gris"><x-heroicon-o-x-mark />Cancelar</a>
            </div>
        </form>
    </div>
@endsection
