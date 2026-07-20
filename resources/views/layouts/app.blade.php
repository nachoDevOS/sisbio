<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Equipos') · {{ config('app.name') }}</title>
    {{-- Alpine.js autohospedado (sin CDN externo, sin build): para el dropdown
         "Mas" de las acciones de fila y el toggle del sidebar en móvil. --}}
    <script defer src="{{ asset('vendor/alpine/alpine.min.js') }}"></script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --verde: #00a65a; --verde-osc: #008d4c; --bg: #f4f6f9; --card: #fff;
            --fg: #1f2937; --muted: #6b7280; --border: #e5e7eb; --danger: #ef4444;
            --sidebar: #1b2540; --sidebar-hover: #263252; --sidebar-activo: #2c3a63;
            --sidebar-fg: #b9c2d8; --sidebar-fg-activo: #fff;
            --thead: #3b4b8f; --thead-fg: #fff;
            --sidebar-w: 15.5rem;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: var(--bg); color: var(--fg); font-size: .9rem; line-height: 1.45;
            display: flex; min-height: 100vh; }
        a { color: inherit; text-decoration: none; }
        svg { display: block; }

        /* ===== Sidebar ===== */
        .sidebar { width: var(--sidebar-w); flex-shrink: 0; background: var(--sidebar);
            color: var(--sidebar-fg); display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 30;
            transition: transform .2s ease; }
        .sidebar__marca { display: flex; align-items: center; gap: .6rem; padding: 1rem 1.1rem;
            font-weight: 700; font-size: .8rem; color: #fff; border-bottom: 1px solid rgba(255,255,255,.08);
            line-height: 1.25; }
        .sidebar__marca img { height: 2rem; width: auto; flex-shrink: 0; }
        .sidebar__nav { padding: .6rem; display: flex; flex-direction: column; gap: .15rem; }
        .sidebar__link { display: flex; align-items: center; gap: .7rem; padding: .55rem .7rem;
            border-radius: .45rem; font-size: .8rem; font-weight: 500; color: var(--sidebar-fg); }
        .sidebar__link svg { width: 1.15rem; height: 1.15rem; flex-shrink: 0; }
        .sidebar__link:hover { background: var(--sidebar-hover); color: #fff; }
        .sidebar__link.activo { background: var(--sidebar-activo); color: var(--sidebar-fg-activo); }
        .sidebar__overlay { display: none; }

        /* ===== Zona principal: topbar + contenido ===== */
        .zona { margin-left: var(--sidebar-w); flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .topbar { background: #fff; border-bottom: 1px solid var(--border); padding: .65rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            position: sticky; top: 0; z-index: 20; }
        .topbar__toggle { display: none; background: none; border: 0; cursor: pointer; padding: .3rem; }
        .topbar__toggle svg { width: 1.4rem; height: 1.4rem; }
        .topbar__vol { font-size: .8125rem; color: var(--muted); }
        .topbar__vol:hover { color: var(--fg); text-decoration: underline; }

        .contenedor { padding: 1.5rem; }
        .cabecera { display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.1rem; gap: 1rem; flex-wrap: wrap; }
        .cabecera__titulo { display: flex; align-items: center; gap: .65rem; }
        .cabecera__icono { display: inline-flex; align-items: center; justify-content: center;
            width: 2.25rem; height: 2.25rem; border-radius: .55rem; background: #eef2ff; color: var(--thead); flex-shrink: 0; }
        .cabecera__icono svg { width: 1.25rem; height: 1.25rem; }
        .cabecera h1 { font-size: 1.15rem; margin: 0; }

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
        thead th { text-align: left; background: var(--thead); color: var(--thead-fg); font-size: .7rem;
            text-transform: uppercase; letter-spacing: .04em; padding: .6rem .75rem; }
        tbody td { padding: .5rem .75rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #f9fafb; }

        .pill { display: inline-block; padding: .2rem .6rem; border-radius: 9999px; font-size: .7rem; font-weight: 700; }
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
            background: #3b5bdb; color: #fff; border: 0; cursor: pointer;
            padding: 0 .55rem; height: 1.9rem; border-radius: .4rem; font-size: .75rem; font-weight: 600; }
        .dropdown-toggle:hover { background: #3348b0; }
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

        /* ===== Barra de herramientas: buscador y filtros en caja, con etiqueta arriba ===== */
        .toolbar { display: flex; gap: .9rem; align-items: flex-end; flex-wrap: wrap; margin-bottom: 1rem;
            background: var(--card); border: 1px solid var(--border); border-radius: .625rem; padding: .9rem 1rem; }
        .toolbar .campo { margin-bottom: 0; }
        .toolbar input[type=text].input { min-width: 16rem; }

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

        /* ===== Móvil: sidebar oculto tras el botón de la topbar ===== */
        @media (max-width: 960px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.abierto { transform: translateX(0); box-shadow: 0 0 0 100vmax rgba(0,0,0,.35); }
            .zona { margin-left: 0; }
            .topbar__toggle { display: inline-flex; }
        }
    </style>
</head>
<body x-data="{ sidebarAbierto: false }">
    <aside class="sidebar" :class="{ abierto: sidebarAbierto }">
        <a href="{{ route('equipos.index') }}" class="sidebar__marca">
            <img src="{{ asset('image/icon.png') }}" alt="">
            <span>{{ config('app.name') }}</span>
        </a>
        <nav class="sidebar__nav">
            <a href="{{ route('equipos.index') }}" class="sidebar__link {{ request()->routeIs('equipos.*') ? 'activo' : '' }}">
                <x-heroicon-o-computer-desktop />Equipos
            </a>
            <a href="{{ route('usuarios.index') }}" class="sidebar__link {{ request()->routeIs('usuarios.*') ? 'activo' : '' }}">
                <x-heroicon-o-user />Usuarios
            </a>
            <a href="{{ route('funcionarios.index') }}" class="sidebar__link {{ request()->routeIs('funcionarios.*') ? 'activo' : '' }}">
                <x-heroicon-o-user-group />Funcionarios
            </a>
            <a href="{{ route('marcaciones.index') }}" class="sidebar__link {{ request()->routeIs('marcaciones.*') ? 'activo' : '' }}">
                <x-heroicon-o-finger-print />Marcaciones
            </a>
        </nav>
    </aside>

    <div class="zona">
        <header class="topbar">
            <button type="button" class="topbar__toggle" x-on:click="sidebarAbierto = !sidebarAbierto" aria-label="Abrir menú">
                <x-heroicon-o-bars-3 />
            </button>
            <span></span>
            <a href="/admin" class="topbar__vol">Volver al panel →</a>
        </header>

        <main class="contenedor">
            @if (session('estado'))
                <div class="aviso">{{ session('estado') }}</div>
            @endif

            @yield('contenido')
        </main>
    </div>
</body>
</html>
