@extends('layouts.app')

@section('titulo', 'Funcionario ' . trim($persona->IdPersona))

@php
    $sexos = ['F' => 'Femenino', 'M' => 'Masculino'];
    $estadosCiviles = ['S' => 'Soltero(a)', 'C' => 'Casado(a)', 'D' => 'Divorciado(a)', 'V' => 'Viudo(a)'];
@endphp

@section('contenido')
    <div class="cabecera">
        <h1>{{ $persona->nombre_completo ?: 'Funcionario' }} · CI {{ trim($persona->IdPersona) }}</h1>
        <div class="acciones">
            <a href="{{ route('funcionarios.edit', $persona) }}" class="btn"><x-heroicon-o-pencil-square />Editar</a>
            <a href="{{ route('funcionarios.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
        </div>
    </div>

    <div class="form-grid">
        <div class="tarjeta">
            <h2>Datos personales</h2>
            <dl class="datos grid-2">
                <div>
                    <dt>Nro. carnet de identidad</dt>
                    <dd>{{ trim($persona->IdPersona) }}</dd>
                </div>
                <div>
                    <dt>Expedido en</dt>
                    <dd>{{ trim((string) $persona->OrigenId) ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Apellido paterno</dt>
                    <dd>{{ trim((string) $persona->Paterno) ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Apellido materno</dt>
                    <dd>{{ trim((string) $persona->Materno) ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Nombres</dt>
                    <dd>{{ trim((string) $persona->Nombres) ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Fecha de nacimiento</dt>
                    <dd>{{ $persona->FechaNacimiento?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Lugar de nacimiento</dt>
                    <dd>{{ trim((string) $persona->LugarNacimiento) ?: '—' }}</dd>
                </div>
                <div>
                    <dt>Sexo</dt>
                    <dd>{{ $sexos[$persona->Sexo] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Estado civil</dt>
                    <dd>{{ $estadosCiviles[$persona->EstadoCivil] ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="tarjeta">
            <h2>Estudios</h2>
            <dl class="datos">
                <dt>Profesión</dt>
                <dd>{{ trim((string) $persona->profesion?->NombreProfesion) ?: '—' }}</dd>

                <dt>Nivel</dt>
                <dd>{{ trim((string) $persona->NivelEstudio) ?: '—' }}</dd>
            </dl>
        </div>

        <div class="tarjeta">
            <h2>Contactos</h2>
            <dl class="datos">
                <dt>Teléfonos</dt>
                <dd>{{ trim((string) $persona->Telefono) ?: '—' }}</dd>

                <dt>Dirección</dt>
                <dd>{{ trim((string) $persona->Direccion) ?: '—' }}</dd>

                <dt>E-mail</dt>
                <dd>{{ trim((string) $persona->CorreoE) ?: '—' }}</dd>
            </dl>
        </div>

        <div class="tarjeta">
            <h2>Control de asistencia</h2>
            <dl class="datos">
                <dt>PIN reloj lector de huellas</dt>
                <dd>{{ trim((string) $persona->PinReloj) ?: 'Sin PIN' }}</dd>

                <dt>Puede marcar con contraseña</dt>
                <dd>
                    <span class="pill {{ $persona->MarcaDirecta ? 'pill--ok' : 'pill--no' }}">
                        {{ $persona->MarcaDirecta ? 'Sí' : 'No' }}
                    </span>
                </dd>
            </dl>
        </div>
    </div>
@endsection
