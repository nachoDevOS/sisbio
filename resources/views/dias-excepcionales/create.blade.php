@extends('layouts.app')

@section('titulo', 'Nuevo día excepcional')

@section('contenido')
    <div class="cabecera">
        <h1>Nuevo día excepcional</h1>
        <a href="{{ route('dias-excepcionales.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
    </div>

    <div class="card card--padded">
        <form action="{{ route('dias-excepcionales.store') }}" method="POST">
            @csrf
            @include('dias-excepcionales._form')

            <div class="form-acciones">
                <button type="submit" class="btn"><x-heroicon-o-check />Registrar día</button>
                <a href="{{ route('dias-excepcionales.index') }}" class="btn btn--gris"><x-heroicon-o-x-mark />Cancelar</a>
            </div>
        </form>
    </div>
@endsection
