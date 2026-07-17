@extends('layouts.app')

@section('titulo', 'Nuevo funcionario')

@section('contenido')
    <div class="cabecera">
        <h1>Nuevo funcionario (SIA)</h1>
        <a href="{{ route('funcionarios.index') }}" class="btn btn--gris">← Volver</a>
    </div>

    <form action="{{ route('funcionarios.store') }}" method="POST">
        @csrf
        @include('funcionarios._form')

        <div class="form-acciones">
            <button type="submit" class="btn">Registrar funcionario</button>
            <a href="{{ route('funcionarios.index') }}" class="btn btn--gris">Cancelar</a>
        </div>
    </form>
@endsection
