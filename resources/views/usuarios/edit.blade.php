@extends('layouts.app')

@section('titulo', 'Editar usuario')

@section('contenido')
    <div class="cabecera">
        <h1>Editar «{{ $usuario->name }}»</h1>
        <a href="{{ route('usuarios.index') }}" class="btn btn--gris">← Volver</a>
    </div>

    <div class="card" style="padding: 1.5rem;">
        <form action="{{ route('usuarios.update', $usuario) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('usuarios._form')

            <div class="form-acciones">
                <button type="submit" class="btn">Guardar cambios</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn--gris">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
