@extends('layouts.app')

@section('titulo', 'Nuevo usuario')

@section('contenido')
    <div class="cabecera">
        <h1>Nuevo usuario</h1>
        <a href="{{ route('usuarios.index') }}" class="btn btn--gris">← Volver</a>
    </div>

    <div class="card" style="padding: 1.5rem;">
        <form action="{{ route('usuarios.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('usuarios._form')

            <div class="form-acciones">
                <button type="submit" class="btn">Crear usuario</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn--gris">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
