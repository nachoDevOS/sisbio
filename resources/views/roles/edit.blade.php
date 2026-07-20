@extends('layouts.app')

@section('titulo', 'Editar rol')

@section('contenido')
    <div class="cabecera">
        <h1>Editar «{{ $role->name }}»</h1>
        <a href="{{ route('roles.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
    </div>

    <div class="card card--padded">
        <form action="{{ route('roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')
            @include('roles._form')

            <div class="form-acciones">
                <button type="submit" class="btn"><x-heroicon-o-check />Guardar cambios</button>
                <a href="{{ route('roles.index') }}" class="btn btn--gris"><x-heroicon-o-x-mark />Cancelar</a>
            </div>
        </form>
    </div>
@endsection
