<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Equipos') · {{ config('app.name') }}</title>
    <style>
        :root { --verde: #00a65a; --verde-osc: #008d4c; --bg: #f4f6f9; --card: #fff;
            --fg: #1f2937; --muted: #6b7280; --border: #e5e7eb; --danger: #ef4444; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            background: var(--bg); color: var(--fg); }
        a { color: inherit; text-decoration: none; }
        .barra { background: var(--verde); color: #fff; padding: .9rem 1.5rem;
            display: flex; align-items: center; justify-content: space-between; }
        .barra a { font-weight: 600; }
        .barra__nav { display: flex; gap: 1.25rem; align-items: center; }
        .barra__nav a { opacity: .9; }
        .barra__nav a:hover { opacity: 1; text-decoration: underline; }
        .barra__vol { font-size: .85rem; opacity: .9; }
        .contenedor { max-width: 960px; margin: 1.75rem auto; padding: 0 1.25rem; }
        .cabecera { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; }
        .cabecera h1 { font-size: 1.4rem; margin: 0; }
        .btn { display: inline-flex; align-items: center; gap: .4rem; border: 0; cursor: pointer;
            background: var(--verde); color: #fff; padding: .55rem .9rem; border-radius: .5rem;
            font-size: .875rem; font-weight: 600; }
        .btn:hover { background: var(--verde-osc); }
        .btn--gris { background: #6b7280; }
        .btn--gris:hover { background: #4b5563; }
        .btn--peligro { background: var(--danger); }
        .btn--peligro:hover { background: #dc2626; }
        .btn--sm { padding: .35rem .6rem; font-size: .8rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: .75rem;
            overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        thead th { text-align: left; background: #f9fafb; color: var(--muted); font-size: .72rem;
            text-transform: uppercase; letter-spacing: .04em; padding: .7rem .9rem; border-bottom: 1px solid var(--border); }
        tbody td { padding: .7rem .9rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #f9fafb; }
        .pill { display: inline-block; padding: .15rem .5rem; border-radius: 9999px; font-size: .72rem; font-weight: 600; }
        .pill--ok { background: #dcfce7; color: #166534; }
        .pill--no { background: #fee2e2; color: #991b1b; }
        .acciones { display: flex; gap: .4rem; }
        .aviso { background: #dcfce7; color: #166534; padding: .75rem 1rem; border-radius: .5rem; margin-bottom: 1.25rem; font-size: .9rem; }
        .campo { margin-bottom: 1.1rem; }
        .campo label { display: block; font-weight: 600; font-size: .85rem; margin-bottom: .35rem; }
        .campo input[type=text], .campo input[type=number] { width: 100%; padding: .55rem .7rem;
            border: 1px solid var(--border); border-radius: .5rem; font-size: .9rem; }
        .campo .ayuda { color: var(--muted); font-size: .78rem; margin-top: .3rem; }
        .campo .error { color: var(--danger); font-size: .8rem; margin-top: .3rem; }
        .check { display: flex; align-items: center; gap: .5rem; }
        .check input { width: 1.1rem; height: 1.1rem; }
        .form-acciones { display: flex; gap: .6rem; margin-top: 1.5rem; }
        .datos dt { color: var(--muted); font-size: .78rem; text-transform: uppercase; letter-spacing: .03em; }
        .datos dd { margin: .2rem 0 1rem; font-size: .95rem; font-weight: 500; }
        .vacio { padding: 2.5rem; text-align: center; color: var(--muted); }
    </style>
</head>
<body>
    <nav class="barra">
        <div class="barra__nav">
            <a href="{{ route('equipos.index') }}">Equipos</a>
            <a href="{{ route('usuarios.index') }}">Usuarios</a>
            <a href="{{ route('funcionarios.index') }}">Funcionarios</a>
            <a href="{{ route('marcaciones.index') }}">Marcaciones</a>
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
