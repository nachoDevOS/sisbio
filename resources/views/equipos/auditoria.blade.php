@extends('layouts.app')

@section('titulo', 'Bitácora de equipos')

@section('contenido')
    <div class="cabecera">
        <div class="cabecera__titulo">
            <span class="cabecera__icono"><x-heroicon-o-clipboard-document-list /></span>
            <h1>Bitácora de equipos</h1>
        </div>
        <a href="{{ route('equipos.index') }}" class="btn btn--gris"><x-heroicon-o-arrow-left />Volver a equipos</a>
    </div>

    <p style="margin: -.4rem 0 1.1rem; color: var(--muted); font-size: .85rem;">
        Quién exportó, envió a la base del SIA, limpió o dio de baja cada equipo biométrico.
        Las acciones que borran información llevan el motivo escrito por quien las hizo.
    </p>

    <x-tabla-filtros :action="route('equipos.auditoria')" :busqueda="$busqueda"
                     :por-pagina="$porPagina" placeholder="Buscar por equipo, IP, motivo o usuario…">
        <x-slot:filtros>
            <select name="accion" onchange="this.form.submit()">
                <option value="">Todas las acciones</option>
                @foreach ($etiquetas as $valor => $texto)
                    <option value="{{ $valor }}" @selected($accion === $valor)>{{ $texto }}</option>
                @endforeach
            </select>
        </x-slot:filtros>
    </x-tabla-filtros>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Usuario</th>
                    <th>Acción</th>
                    <th>Equipo</th>
                    <th>Motivo / detalle</th>
                    <th>Marcaciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        <td style="white-space: nowrap;">
                            {{ $registro->created_at->format('d/m/Y H:i') }}
                            @if ($registro->ip_usuario)
                                <div style="color: var(--muted); font-size: .75rem;">desde {{ $registro->ip_usuario }}</div>
                            @endif
                        </td>
                        <td><strong>{{ $registro->nombreUsuario() }}</strong></td>
                        <td>
                            <span class="pill {{ in_array($registro->accion, \App\Models\EquipoAuditoria::ACCIONES_DESTRUCTIVAS, true) ? 'pill--no' : 'pill--ok' }}">
                                {{ $registro->etiquetaAccion() }}
                            </span>
                            @unless ($registro->exito)
                                <div style="color: var(--danger); font-size: .75rem; margin-top: .2rem;">Falló</div>
                            @endunless
                        </td>
                        <td>
                            {{-- Se muestran los datos guardados al momento de la acción: siguen
                                 siendo correctos aunque después le cambien la IP o lo den de baja. --}}
                            <strong>{{ $registro->nombreEquipo() }}</strong>
                            <div style="color: var(--muted); font-size: .75rem;">
                                {{ $registro->datos_equipo['ip'] ?? '—' }}:{{ $registro->datos_equipo['puerto'] ?? '—' }}
                                @if (! empty($registro->datos_equipo['ubicacion']))
                                    · {{ $registro->datos_equipo['ubicacion'] }}
                                @endif
                            </div>
                            @if (! empty($registro->datos_equipo['algoritmo']))
                                <div style="color: var(--muted); font-size: .75rem;">{{ $registro->datos_equipo['algoritmo'] }}</div>
                            @endif
                        </td>
                        <td style="max-width: 22rem;">
                            @if ($registro->motivo)
                                <div>{{ $registro->motivo }}</div>
                            @endif
                            @if ($registro->detalle)
                                <div style="color: var(--muted); font-size: .75rem;">{{ $registro->detalle }}</div>
                            @endif
                            @if ($registro->desde || $registro->hasta)
                                <div style="color: var(--muted); font-size: .75rem;">
                                    Rango: {{ $registro->desde ?? 'inicio' }} → {{ $registro->hasta ?? 'hoy' }}
                                </div>
                            @endif
                            @if (! $registro->motivo && ! $registro->detalle && ! $registro->desde && ! $registro->hasta)
                                —
                            @endif
                        </td>
                        <td>{{ $registro->total_marcaciones ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="vacio">Todavía no hay movimientos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="paginacion">
        {{ $registros->links() }}
    </div>
@endsection
