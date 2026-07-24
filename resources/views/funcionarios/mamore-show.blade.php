@extends('layouts.app')

@section('titulo', 'Funcionario ' . ($persona['ci'] ?? ''))

@section('contenido')
    <div class="cabecera">
        <h1>{{ $persona['full_name'] ?? 'Funcionario' }} · CI {{ $persona['full_ci'] ?? ($persona['ci'] ?? '—') }}</h1>
        <div class="acciones">
            <a href="{{ route('funcionarios.index', ['fuente' => 'mamore']) }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver</a>
        </div>
    </div>

    <div class="aviso">Datos de solo lectura desde el sistema Mamoré.</div>

    <div class="form-grid">
        <div class="tarjeta">
            <h2>Datos personales</h2>
            <dl class="datos grid-2">
                <div><dt>Nombre completo</dt><dd>{{ $persona['full_name'] ?? '—' }}</dd></div>
                <div><dt>Cédula</dt><dd>{{ $persona['full_ci'] ?? ($persona['ci'] ?? '—') }}</dd></div>
                <div><dt>Emisión</dt><dd>{{ $persona['issued'] ?? '—' }}</dd></div>
                <div><dt>Género</dt><dd>{{ $persona['gender'] ?? '—' }}</dd></div>
                <div><dt>Fecha de nacimiento</dt><dd>{{ $persona['birthday'] ?? '—' }}</dd></div>
                <div><dt>Estado civil</dt><dd>{{ $persona['civil_status'] ?? '—' }}</dd></div>
                <div><dt>Nº de hijos</dt><dd>{{ $persona['number_children'] ?? '—' }}</dd></div>
                <div><dt>Profesión</dt><dd>{{ $persona['profession'] ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="tarjeta">
            <h2>Contacto</h2>
            <dl class="datos">
                <dt>Teléfono</dt><dd>{{ $persona['phone'] ?? '—' }}</dd>
                <dt>E-mail</dt><dd>{{ $persona['email'] ?? '—' }}</dd>
                <dt>Dirección</dt><dd>{{ $persona['address'] ?? '—' }}</dd>
                <dt>Ciudad</dt><dd>{{ $persona['city'] ?? '—' }}</dd>
                <dt>Departamento</dt><dd>{{ $persona['state'] ?? '—' }}</dd>
            </dl>
        </div>
    </div>
@endsection
