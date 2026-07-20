@extends('layouts.app')

@section('titulo', 'Escritorio')

@php
    $maxMarcaciones = max([1, ...$grafico['totales']]);
@endphp

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-home /></span>
            <h1>Escritorio</h1>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card__valor">{{ $equipos['total'] }}</div>
            <div class="stat-card__label">Equipos registrados</div>
        </div>
        <div class="stat-card stat-card--success">
            <div class="stat-card__valor">{{ $equipos['en_linea'] }}</div>
            <div class="stat-card__label">En línea</div>
        </div>
        <div class="stat-card stat-card--danger">
            <div class="stat-card__valor">{{ $equipos['fuera_linea'] }}</div>
            <div class="stat-card__label">Fuera de línea</div>
        </div>
        <div class="stat-card stat-card--warning">
            <div class="stat-card__valor">{{ $equipos['maestros'] }}</div>
            <div class="stat-card__label">Equipos maestros</div>
        </div>
    </div>

    <div class="stats-grid">
        @if ($sinConexionSia)
            <div class="stat-card stat-card--danger">
                <div class="stat-card__valor">Sin conexión</div>
                <div class="stat-card__label">No se pudo consultar el servidor SIA</div>
            </div>
        @else
            <div class="stat-card stat-card--success">
                <div class="stat-card__valor">{{ number_format($sia['marcaciones_hoy'], 0, ',', '.') }}</div>
                <div class="stat-card__label">Marcaciones hoy</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__valor">{{ number_format($sia['personas_hoy'], 0, ',', '.') }}</div>
                <div class="stat-card__label">Personas que marcaron hoy</div>
            </div>
            <div class="stat-card stat-card--info">
                <div class="stat-card__valor">{{ number_format($sia['marcaciones_mes'], 0, ',', '.') }}</div>
                <div class="stat-card__label">Marcaciones del mes</div>
            </div>
            <div class="stat-card">
                <div class="stat-card__valor">{{ number_format($sia['funcionarios'], 0, ',', '.') }}</div>
                <div class="stat-card__label">Funcionarios registrados</div>
            </div>
        @endif
    </div>

    <div class="tarjeta" style="margin-bottom: 1.5rem;">
        <h2>Marcaciones por día (últimos 14 días)</h2>
        <div class="mini-chart">
            @foreach ($grafico['totales'] as $total)
                <div class="mini-chart__barra" style="height: {{ max(2, ($total / $maxMarcaciones) * 100) }}%" title="{{ $total }} marcaciones"></div>
            @endforeach
        </div>
        <div class="mini-chart__ejes">
            <span>{{ $grafico['dias'][0] }}</span>
            <span>{{ $grafico['dias'][count($grafico['dias']) - 1] }}</span>
        </div>
    </div>

    <div class="tarjeta">
        <h2>Equipos fuera de línea</h2>
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>IP</th>
                    <th>Ubicación</th>
                    <th>Última sincronización</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($equiposFueraDeLinea as $equipo)
                    <tr>
                        <td><a href="{{ route('equipos.edit', $equipo) }}"><strong>{{ $equipo->nombre }}</strong></a></td>
                        <td>{{ $equipo->ip }}</td>
                        <td>{{ $equipo->ubicacion ?? 'Sin ubicación' }}</td>
                        <td>{{ $equipo->ultima_sync?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="vacio">Todos los equipos están en línea.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
