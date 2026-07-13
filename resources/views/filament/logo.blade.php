{{-- Marca del panel: ícono + nombre de la app (APP_NAME del .env). --}}
<div style="display: flex; align-items: center; gap: .625rem; height: 100%;">
    <img
        src="{{ asset('image/icon.png') }}"
        alt="{{ config('app.name') }}"
        style="height: 100%; width: auto;"
    />
    <span style="font-size: .8125rem; font-weight: 700; line-height: 1.2; letter-spacing: .02em; white-space: normal;">
        {{ config('app.name') }}
    </span>
</div>
