@extends('layouts.app')

@section('titulo', 'Editar funcionario')

@section('contenido')
    <div class="cabecera">
        <h1>Editar funcionario · CI {{ trim($persona->ci) }}</h1>
        <a href="{{ route('funcionarios.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
    </div>

    <form action="{{ route('funcionarios.update', $persona) }}" method="POST">
        @csrf
        @method('PUT')
        @include('funcionarios._form')

        <div class="form-acciones">
            <button type="submit" class="btn"><x-heroicon-o-check />Guardar cambios</button>
            <a href="{{ route('funcionarios.index') }}" class="btn btn--gris"><x-heroicon-o-x-mark />Cancelar</a>
        </div>
    </form>
@endsection
