<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Equipos') · {{ config('app.name') }}</title>
    {{-- Alpine.js autohospedado (sin CDN externo, sin build): solo para el
         dropdown "Mas" de las acciones de fila. --}}
    <script defer src="{{ asset('vendor/alpine/alpine.min.js') }}"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root { --verde: #00a65a; --verde-osc: #008d4c; --bg: #f4f6f9; --card: #fff;
            --fg: #1f2937; --muted: #6b7280; --border: #e5e7eb; --danger: #ef4444; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: var(--bg); color: var(--fg); font-size: .9rem; line-height: 1.45; }
        a { color: inherit; text-decoration: none; }
        svg { display: block; }

        /* ===== Barra superior: marca + navegación + volver al panel ===== */
        .barra { background: var(--verde-osc); color: #fff; padding: .65rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            flex-wrap: wrap; box-shadow: 0 2px 8px rgba(0, 0, 0, .15); }
        .barra__marca { display: flex; align-items: center; gap: .5rem; font-weight: 700;
            font-size: .9rem; letter-spacing: .01em; }
        .barra__marca img { height: 1.75rem; width: auto; }
        .barra__nav { display: flex; gap: .25rem; align-items: center; }
        .barra__nav a { opacity: .85; padding: .4rem .75rem; border-radius: .4rem;
            font-size: .875rem; transition: background .15s ease, opacity .15s ease; }
        .barra__nav a:hover { opacity: 1; background: rgba(255, 255, 255, .1); }
        .barra__nav a.activo { opacity: 1; background: rgba(255, 255, 255, .18); font-weight: 600; }
        .barra__vol { font-size: .8125rem; opacity: .85; }
        .barra__vol:hover { opacity: 1; text-decoration: underline; }

        .contenedor { max-width: 1120px; margin: 1.5rem auto; padding: 0 1.5rem; }
        .cabecera { display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.1rem; gap: 1rem; flex-wrap: wrap; }
        .cabecera h1 { font-size: 1.3rem; margin: 0; }

        /* ===== Botones ===== */
        .btn { display: inline-flex; align-items: center; gap: .4rem; border: 0; cursor: pointer;
            background: var(--verde); color: #fff; padding: .5rem .85rem; border-radius: .5rem;
            font-size: .8125rem; font-weight: 600; line-height: 1.2; white-space: nowrap; }
        .btn svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; }
        .btn:hover { background: var(--verde-osc); }
        .btn--gris { background: #6b7280; }
        .btn--gris:hover { background: #4b5563; }
        .btn--peligro { background: var(--danger); }
        .btn--peligro:hover { background: #dc2626; }
        .btn--sm { padding: .3rem .6rem; font-size: .75rem; }
        .btn--sm svg { width: 1rem; height: 1rem; }

        /* ===== Cajas ===== */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: .625rem;
            overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .card--padded { padding: 1.25rem; }

        table { width: 100%; border-collapse: collapse; font-size: .8125rem; }
        thead th { text-align: left; background: #f9fafb; color: var(--muted); font-size: .7rem;
            text-transform: uppercase; letter-spacing: .04em; padding: .55rem .75rem; border-bottom: 1px solid var(--border); }
        tbody td { padding: .5rem .75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #f9fafb; }

        .pill { display: inline-block; padding: .15rem .5rem; border-radius: 9999px; font-size: .7rem; font-weight: 600; }
        .pill--ok { background: #dcfce7; color: #166534; }
        .pill--no { background: #fee2e2; color: #991b1b; }

        .acciones { display: flex; gap: .35rem; align-items: center; }

        /* ===== Botón ícono cuadrado: acción principal de fila (Ver/Editar) ===== */
        .btn-icon { display: inline-flex; align-items: center; justify-content: center;
            width: 1.9rem; height: 1.9rem; border-radius: .4rem; border: 0; cursor: pointer;
            background: var(--verde); color: #fff; flex-shrink: 0; }
        .btn-icon svg { width: 1rem; height: 1rem; }
        .btn-icon:hover { background: var(--verde-osc); }
        .btn-icon--gris { background: #6b7280; }
        .btn-icon--gris:hover { background: #4b5563; }
        .btn-icon--peligro { background: var(--danger); }
        .btn-icon--peligro:hover { background: #dc2626; }

        /* ===== Dropdown "Mas": agrupa acciones secundarias/destructivas ===== */
        .dropdown { position: relative; display: inline-flex; }
        .dropdown-toggle { display: inline-flex; align-items: center; gap: .3rem;
            background: #eef2ff; color: #3730a3; border: 0; cursor: pointer;
            padding: 0 .55rem; height: 1.9rem; border-radius: .4rem; font-size: .75rem; font-weight: 600; }
        .dropdown-toggle:hover { background: #e0e7ff; }
        .dropdown-toggle svg { width: .8rem; height: .8rem; }
        .dropdown-menu { position: absolute; right: 0; top: calc(100% + .3rem); z-index: 20;
            background: #fff; border: 1px solid var(--border); border-radius: .5rem;
            box-shadow: 0 8px 20px rgba(0,0,0,.15); min-width: 9.5rem; padding: .3rem; }
        .dropdown-menu form { margin: 0; }
        .dropdown-menu a, .dropdown-menu button { display: flex; align-items: center; gap: .5rem;
            width: 100%; text-align: left; background: none; border: 0; cursor: pointer;
            padding: .4rem .6rem; border-radius: .35rem; font-size: .8125rem; color: var(--fg);
            font-family: inherit; }
        .dropdown-menu a:hover, .dropdown-menu button:hover { background: #f3f4f6; }
        .dropdown-menu .peligro { color: var(--danger); }
        .dropdown-menu svg { width: 1rem; height: 1rem; flex-shrink: 0; }
        .aviso { background: #dcfce7; color: #166534; padding: .65rem .9rem; border-radius: .5rem;
            margin-bottom: 1.1rem; font-size: .85rem; }

        /* ===== Barra de herramientas: buscador y filtros arriba de una tabla ===== */
        .toolbar { display: flex; gap: .6rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1rem; }
        .toolbar .campo { margin-bottom: 0; }
        .toolbar input[type=text].input { flex: 1; min-width: 12rem; }

        /* ===== Formularios ===== */
        .campo { margin-bottom: .9rem; }
        .campo label { display: block; font-weight: 600; font-size: .8rem; margin-bottom: .3rem; }
        .campo input[type=text], .campo input[type=number], .campo input[type=date],
        .campo input[type=email], .campo input[type=password], .campo select,
        .input { width: 100%; padding: .5rem .7rem;
            border: 1px solid var(--border); border-radius: .5rem; font-size: .85rem;
            background: #fff; color: var(--fg); font-family: inherit; }
        .campo input:focus, .campo select:focus, .input:focus { outline: none;
            border-color: var(--verde); box-shadow: 0 0 0 2px rgba(0, 166, 90, .35); }
        .campo .req { color: var(--danger); }

        /* Formulario tipo panel: cards por sección en grilla de dos columnas. */
        .form-grid { display: grid; grid-template-columns: 1.4fr 1fr; gap: 1rem;
            align-items: start; }
        .tarjeta { background: var(--card); border: 1px solid var(--border); border-radius: .625rem;
            box-shadow: 0 1px 2px rgba(0,0,0,.05); padding: 1rem 1.25rem; }
        .tarjeta h2 { font-size: .9375rem; margin: 0 0 .85rem; }
        .tarjeta fieldset { border: 0; padding: 0; margin: 0; }
        .tarjeta fieldset[disabled] .campo { opacity: .55; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
        @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
        @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
        .campo .ayuda { color: var(--muted); font-size: .75rem; margin-top: .25rem; }
        .campo .error { color: var(--danger); font-size: .78rem; margin-top: .25rem; }
        .check { display: flex; align-items: center; gap: .5rem; }
        .check input { width: 1.1rem; height: 1.1rem; }
        .form-acciones { display: flex; gap: .6rem; margin-top: 1.25rem; }

        .datos dt { color: var(--muted); font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; }
        .datos dd { margin: .2rem 0 .9rem; font-size: .9rem; font-weight: 500; }

        .vacio { padding: 2.5rem; text-align: center; color: var(--muted); }
        .paginacion { margin-top: 1rem; }
    </style>
</head>
<body>
    <nav class="barra">
        <a href="{{ route('equipos.index') }}" class="barra__marca">
            <img src="{{ asset('image/icon.png') }}" alt="">
            <span>{{ config('app.name') }}</span>
        </a>
        <div class="barra__nav">
            <a href="{{ route('equipos.index') }}" class="{{ request()->routeIs('equipos.*') ? 'activo' : '' }}">Equipos</a>
            <a href="{{ route('usuarios.index') }}" class="{{ request()->routeIs('usuarios.*') ? 'activo' : '' }}">Usuarios</a>
            <a href="{{ route('funcionarios.index') }}" class="{{ request()->routeIs('funcionarios.*') ? 'activo' : '' }}">Funcionarios</a>
            <a href="{{ route('marcaciones.index') }}" class="{{ request()->routeIs('marcaciones.*') ? 'activo' : '' }}">Marcaciones</a>
        </div>
        <a href="/admin" class="barra__vol">Volver al panel →</a>
    </nav>

    <main class="contenedor">
        @if (session('estado'))
            <div class="aviso">{{ session('estado') }}</div>
        @endif

        @yield('contenido')
    </main>
</body>
</html>
