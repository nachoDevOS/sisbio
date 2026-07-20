<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Acceso · {{ config('app.name') }}</title>
    <style>
        :root {
            --verde: #00a65a; --verde-osc: #008d4c; --sidebar: #0d3b3e; --sidebar-header: #082628;
            --bg: #f4f6f9; --card: #fff; --fg: #1f2937; --muted: #6b7280; --border: #e5e7eb; --danger: #ef4444;
        }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: var(--fg); }
        .login-card { background: var(--card); border: 1px solid var(--border); border-radius: .75rem;
            box-shadow: 0 4px 16px rgba(0,0,0,.06); padding: 2rem; width: 100%; max-width: 22rem; }
        .login-marca { display: flex; flex-direction: column; align-items: center; gap: .6rem; margin-bottom: 1.5rem; }
        .login-marca img { height: 3rem; width: auto; }
        .login-marca span { font-size: .9rem; font-weight: 700; color: var(--sidebar-header); text-align: center; }
        .campo { margin-bottom: 1rem; }
        .campo label { display: block; font-weight: 600; font-size: .8rem; margin-bottom: .3rem; }
        .campo input { width: 100%; padding: .55rem .7rem; border: 1px solid var(--border); border-radius: .5rem;
            font-size: .85rem; font-family: inherit; }
        .campo input:focus { outline: none; border-color: var(--verde); box-shadow: 0 0 0 2px rgba(0,166,90,.35); }
        .campo .error { color: var(--danger); font-size: .78rem; margin-top: .25rem; }
        .check { display: flex; align-items: center; gap: .5rem; margin-bottom: 1.1rem; font-size: .82rem; }
        .btn { width: 100%; border: 0; cursor: pointer; background: var(--verde); color: #fff;
            padding: .6rem .85rem; border-radius: .5rem; font-size: .875rem; font-weight: 600; }
        .btn:hover { background: var(--verde-osc); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-marca">
            <img src="{{ asset('image/icon.png') }}" alt="{{ config('app.name') }}">
            <span>{{ config('app.name') }}</span>
        </div>

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="campo">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>

            <label class="check">
                <input type="checkbox" name="remember">
                Recordarme
            </label>

            <button type="submit" class="btn">Entrar</button>
        </form>
    </div>
</body>
</html>
