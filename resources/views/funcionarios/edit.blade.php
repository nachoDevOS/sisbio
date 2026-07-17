@extends('layouts.app')

@section('titulo', 'Editar funcionario')

@section('contenido')
    <div class="cabecera">
        <h1>Editar funcionario · CI {{ trim($persona->IdPersona) }}</h1>
        <a href="{{ route('funcionarios.index') }}" class="btn btn--gris">← Volver</a>
    </div>

    <form action="{{ route('funcionarios.update', $persona) }}" method="POST">
        @csrf
        @method('PUT')
        @include('funcionarios._form')

        <div class="form-acciones">
            <button type="submit" class="btn">Guardar cambios</button>
            <a href="{{ route('funcionarios.index') }}" class="btn btn--gris">Cancelar</a>
        </div>
    </form>
@endsection
