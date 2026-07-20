{{-- Modal que lista las marcaciones (asistencia) leídas del equipo en vivo. --}}
{{--
    Se usa CSS propio embebido (scoped bajo .sisbio-marcaciones) porque las
    utilidades de Tailwind arbitrarias no están garantizadas en el bundle de
    Filament. Así el diseño se ve igual siempre, en claro y oscuro.
--}}
@php
    // Colores de avatar rotando según la inicial, para distinguir empleados.
    $paletaAvatar = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#ef4444', '#14b8a6'];
@endphp

<div class="sisbio-marcaciones">
    <style>
        .sisbio-marcaciones { --bg: #fff; --fg: #1f2937; --muted: #6b7280; --faint: #9ca3af;
            --border: #e5e7eb; --head-bg: #f9fafb; --row-hover: #f9fafb; --card-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .dark .sisbio-marcaciones { --bg: transparent; --fg: #e5e7eb; --muted: #9ca3af; --faint: #6b7280;
            --border: rgba(255,255,255,.1); --head-bg: rgba(255,255,255,.03); --row-hover: rgba(255,255,255,.04); --card-shadow: none; }

        .sisbio-marcaciones__resumen { display: flex; align-items: center; gap: .5rem; margin-bottom: .75rem;
            font-size: .875rem; color: var(--muted); }
        .sisbio-marcaciones__resumen strong { color: var(--fg); font-weight: 600; }
        .sisbio-marcaciones__dot { width: 8px; height: 8px; border-radius: 9999px; background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.15); }

        .sisbio-marcaciones__card { border: 1px solid var(--border); border-radius: .75rem; overflow: hidden;
            box-shadow: var(--card-shadow); }
        .sisbio-marcaciones__scroll { max-height: 26rem; overflow-y: auto; }
        .sisbio-marcaciones table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .sisbio-marcaciones thead th { position: sticky; top: 0; z-index: 1; background: var(--head-bg);
            text-align: left; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em;
            color: var(--muted); padding: .625rem .875rem; border-bottom: 1px solid var(--border);
            backdrop-filter: blur(4px); }
        .sisbio-marcaciones tbody tr { border-bottom: 1px solid var(--border); }
        .sisbio-marcaciones tbody tr:last-child { border-bottom: 0; }
        .sisbio-marcaciones tbody tr:hover { background: var(--row-hover); }
        .sisbio-marcaciones td { padding: .625rem .875rem; color: var(--fg); vertical-align: middle; }

        .sisbio-emp { display: flex; align-items: center; gap: .625rem; }
        .sisbio-emp__avatar { flex: none; width: 34px; height: 34px; border-radius: 9999px; color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 600; }
        .sisbio-emp__nombre { font-weight: 600; line-height: 1.2; }
        .sisbio-cedula { font-weight: 600; font-variant-numeric: tabular-nums; color: var(--fg); }

        .sisbio-fechahora { display: inline-flex; align-items: baseline; gap: .4rem; white-space: nowrap;
            font-variant-numeric: tabular-nums; }
        .sisbio-fechahora__hora { font-size: 1.05rem; font-weight: 700; color: var(--fg); letter-spacing: .01em; }
        .sisbio-fechahora__sep { color: var(--faint); font-weight: 600; }
        .sisbio-fechahora__dia { font-size: 1rem; font-weight: 600; color: var(--fg); }

        .sisbio-marcaciones__aviso { border-radius: .75rem; padding: 1rem; font-size: .875rem; }
        .sisbio-marcaciones__aviso--error { background: #fef2f2; color: #b91c1c; }
        .dark .sisbio-marcaciones__aviso--error { background: rgba(239,68,68,.1); color: #fca5a5; }
        .sisbio-marcaciones__aviso--vacio { background: var(--head-bg); color: var(--muted); }
    </style>

    @if (! empty($error))
        <div class="sisbio-marcaciones__aviso sisbio-marcaciones__aviso--error">{{ $error }}</div>
    @elseif (empty($marcaciones))
        <div class="sisbio-marcaciones__aviso sisbio-marcaciones__aviso--vacio">
            El equipo no tiene marcaciones guardadas.
        </div>
    @else
        <div class="sisbio-marcaciones__resumen">
            <span class="sisbio-marcaciones__dot"></span>
            <span><strong>{{ count($marcaciones) }}</strong> marcaciones · más recientes primero</span>
        </div>

        <div class="sisbio-marcaciones__card">
            <div class="sisbio-marcaciones__scroll">
                <table>
                    <thead>
                        <tr>
                            <th>CI / ID</th>
                            <th>Nombre</th>
                            <th>Hora - Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($marcaciones as $m)
                            @php
                                $nombre = filled($m['nombre'] ?? null) ? $m['nombre'] : 'Sin nombre';
                                $inicial = mb_strtoupper(mb_substr($nombre, 0, 1));
                                $color = $paletaAvatar[mb_ord($inicial) % count($paletaAvatar)];
                                $fecha = $m['timestamp'] ? \Illuminate\Support\Carbon::parse($m['timestamp']) : null;
                            @endphp
                            <tr>
                                <td><span class="sisbio-cedula">{{ $m['user_id'] }}</span></td>
                                <td>
                                    <div class="sisbio-emp">
                                        <span class="sisbio-emp__avatar" style="background: {{ $color }}">{{ $inicial }}</span>
                                        <span class="sisbio-emp__nombre">{{ $nombre }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if ($fecha)
                                        <span class="sisbio-fechahora">
                                            <span class="sisbio-fechahora__hora">{{ $fecha->format('H:i:s') }}</span>
                                            <span class="sisbio-fechahora__sep">-</span>
                                            <span class="sisbio-fechahora__dia">{{ $fecha->format('d/m/Y') }}</span>
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
