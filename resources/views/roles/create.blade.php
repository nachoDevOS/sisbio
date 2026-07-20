@extends('layouts.app')

@section('titulo', 'Nuevo rol')

@section('contenido')
    <div class="cabecera">
        <h1>Nuevo rol</h1>
        <a href="{{ route('roles.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
    </div>

    <div class="card card--padded">
        <form action="{{ route('roles.store') }}" method="POST">
            @csrf
            @include('roles._form')

            <div class="form-acciones">
                <button type="submit" class="btn"><x-heroicon-o-check />Crear rol</button>
                <a href="{{ route('roles.index') }}" class="btn btn--gris"><x-heroicon-o-x-mark />Cancelar</a>
            </div>
        </form>
    </div>
@endsection
