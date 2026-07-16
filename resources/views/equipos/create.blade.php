@extends('layouts.app')

@section('titulo', 'Nuevo equipo')

@section('contenido')
    <div class="cabecera">
        <h1>Nuevo equipo</h1>
        <a href="{{ route('equipos.index') }}" class="btn btn--gris">← Volver</a>
    </div>

    <div class="card" style="padding: 1.5rem;">
        <form action="{{ route('equipos.store') }}" method="POST">
            @csrf
            @include('equipos._form')

            <div class="form-acciones">
                <button type="submit" class="btn">Registrar equipo</button>
                <a href="{{ route('equipos.index') }}" class="btn btn--gris">Cancelar</a>
            </div>
        </form>
    </div>
@endsection
