{{-- Tema visual de SISBIO al estilo SISCOR (AdminLTE skin verde, Gobierno del Beni). --}}
{{-- Topbar verde + sidebar carbón oscuro + cabecera de tabla verde. Sin build de Vite. --}}
<style>
    /* ===== Paleta SISCOR (AdminLTE green skin) ===== */
    :root {
        --siscor-green: #00a65a;        /* Verde principal (botones, tabla) */
        --siscor-green-dark: #008d4c;   /* Verde oscuro (marca / topbar) */
        --siscor-sidebar: #222d32;      /* Fondo del sidebar */
        --siscor-sidebar-user: #1a2226; /* Bloque del usuario, más oscuro */
        --siscor-sidebar-text: #b8c7ce; /* Texto del sidebar */
        --siscor-body-bg: #ecf0f5;      /* Fondo del contenido */
    }

    /* ===== Layout AdminLTE: sidebar hasta arriba, topbar a su derecha ===== */
    /* Filament pone el topbar a lo ancho y el sidebar debajo (top: 4rem).
       Aquí el sidebar abierto pasa a fijo desde el borde superior, y el topbar
       y el contenido arrancan después de su ancho, como en SISCOR/AdminLTE. */
    .fi-body-has-topbar .fi-sidebar.fi-sidebar-open {
        position: fixed !important;
        top: 0 !important;
        height: 100dvh !important;
        z-index: 40 !important;
    }
    @media (min-width: 1024px) {
        .fi-body-has-topbar:has(.fi-sidebar.fi-sidebar-open) .fi-topbar-ctn,
        .fi-body-has-topbar:has(.fi-sidebar.fi-sidebar-open) .fi-main-ctn {
            margin-inline-start: var(--sidebar-width, 14.5rem);
        }
    }

    /* ===== Topbar: banda verde SISCOR ===== */
    .fi-topbar {
        background: var(--siscor-green-dark) !important;
        border-bottom: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
    }
    /* Marca, íconos y textos del topbar en blanco */
    .fi-topbar .fi-logo,
    .fi-topbar a,
    .fi-topbar .fi-icon-btn,
    .fi-topbar .fi-topbar-item-btn {
        color: #ffffff !important;
    }

    /* ===== Sidebar: carbón oscuro AdminLTE, compacto ===== */
    .fi-sidebar {
        background: var(--siscor-sidebar) !important;
        border-right: 0;
    }
    .fi-sidebar .fi-sidebar-header {
        background: var(--siscor-green-dark) !important;
        box-shadow: none;
    }
    .fi-sidebar .fi-sidebar-header .fi-logo { color: #ffffff !important; }
    /* Navegación más compacta y aireada */
    .fi-sidebar .fi-sidebar-nav {
        padding: .5rem .625rem;
        gap: .125rem;
    }
    .fi-sidebar .fi-sidebar-item-btn {
        border-radius: .375rem;
        padding: .5rem .625rem;
        transition: background .15s ease, color .15s ease;
    }
    .fi-sidebar .fi-sidebar-item-label {
        color: var(--siscor-sidebar-text);
        font-size: .875rem;
    }
    .fi-sidebar .fi-sidebar-item-icon {
        color: #8a9ba5;
        width: 1.125rem;
        height: 1.125rem;
    }
    .fi-sidebar .fi-sidebar-group-label {
        color: #4b646f;
        font-size: .6875rem;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .fi-sidebar .fi-sidebar-item-btn:hover { background: #1e282c; }
    .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-label { color: #ffffff; }
    .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-icon { color: #ffffff; }
    /* Ítem activo: fondo oscuro + barra verde a la izquierda (como AdminLTE) */
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-btn {
        background: #1e282c;
        box-shadow: inset 3px 0 0 var(--siscor-green);
    }
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label { color: #ffffff; font-weight: 600; }
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon { color: var(--siscor-green); }

    /* ===== Fondo del contenido: gris claro AdminLTE ===== */
    .fi-body,
    .fi-main-ctn {
        background: var(--siscor-body-bg);
    }

    /* ===== Tablas: cabecera verde con texto blanco (como SISCOR) ===== */
    .fi-ta-ctn {
        border-radius: .375rem !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
        overflow: hidden;
    }
    /* Filament pinta el fondo en el <tr> y el color en el <th>, por eso se
       sobreescriben los tres niveles (thead, tr, th) y no solo el thead. */
    .fi-ta-table thead,
    .fi-ta-table thead > tr,
    .fi-ta-table thead > tr > th {
        background-color: var(--siscor-green) !important;
        border-color: rgba(255, 255, 255, .2) !important;
    }
    .fi-ta-table thead > tr > th,
    .fi-ta-table thead .fi-ta-header-cell,
    .fi-ta-table thead .fi-ta-header-cell-label,
    .fi-ta-table thead button,
    .fi-ta-table thead svg {
        color: #ffffff !important;
    }

    /* ===== Cajas / secciones ===== */
    .fi-section {
        border-radius: .375rem !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
    }
    /* Botón primario (Crear) usa el verde del primary de Filament. */
</style>
