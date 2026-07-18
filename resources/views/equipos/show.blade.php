@extends('layouts.app')

@section('titulo', $equipo->nombre)

@section('contenido')
    <div class="cabecera">
        <h1>{{ $equipo->nombre }}</h1>
        <div class="acciones">
            <a href="{{ route('equipos.edit', $equipo) }}" class="btn btn--gris"><x-heroicon-o-pencil-square />Editar</a>
            <a href="{{ route('equipos.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
        </div>
    </div>

    <div class="card card--padded">
        <dl class="datos">
            <dt>IP</dt>
            <dd>{{ $equipo->ip }}</dd>

            <dt>Puerto</dt>
            <dd>{{ $equipo->puerto }}</dd>

            <dt>COMM key</dt>
            <dd>{{ $equipo->comm_key }}</dd>

            <dt>Ubicación</dt>
            <dd>{{ $equipo->ubicacion ?? '—' }}</dd>

            <dt>Algoritmo</dt>
            <dd>{{ $equipo->algoritmo ?? 'Sin detectar' }}</dd>

            <dt>Maestro</dt>
            <dd>{{ $equipo->es_master ? 'Sí' : 'No' }}</dd>

            <dt>En línea</dt>
            <dd>
                <span class="pill {{ $equipo->en_linea ? 'pill--ok' : 'pill--no' }}">
                    {{ $equipo->en_linea ? 'Sí' : 'No' }}
                </span>
            </dd>

            <dt>Activo</dt>
            <dd>{{ $equipo->activo ? 'Sí' : 'No' }}</dd>

            <dt>Última sincronización</dt>
            <dd>{{ $equipo->ultima_sync?->format('d/m/Y H:i') ?? 'Nunca' }}</dd>
        </dl>
    </div>
@endsection
