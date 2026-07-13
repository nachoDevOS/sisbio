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
        --siscor-topbar-h: 3rem;        /* Alto del topbar (Filament trae 4rem) */
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

        /* Logo dentro del sidebar (como AdminLTE): Filament oculta el header
           del sidebar en desktop cuando hay topbar (lg:hidden); se restaura
           y se quita el logo duplicado del topbar mientras el sidebar esté abierto. */
        .fi-body-has-topbar .fi-sidebar.fi-sidebar-open .fi-sidebar-header {
            display: flex !important;
        }
        .fi-body-has-topbar:has(.fi-sidebar.fi-sidebar-open) .fi-topbar .fi-logo {
            display: none !important;
        }
    }

    /* ===== Topbar: banda verde SISCOR, más delgada que el default ===== */
    .fi-topbar {
        background: var(--siscor-green-dark) !important;
        border-bottom: 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
        min-height: var(--siscor-topbar-h) !important;
        padding-block: 0;
    }
    /* El sidebar cerrado en desktop arranca debajo del topbar (Filament asume 4rem). */
    @media (min-width: 1024px) {
        .fi-body-has-topbar .fi-sidebar:not(.fi-sidebar-open) {
            top: var(--siscor-topbar-h) !important;
            height: calc(100dvh - var(--siscor-topbar-h)) !important;
        }
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
        background: var(--siscor-sidebar) !important;
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

    /* ===== Filas: cebra suave + hover verdoso ===== */
    .fi-ta-row.fi-striped { background: #f6f8fa; }
    .fi-ta-row:hover { background: #eef7f2; }

    /* ===== Paginación estilo AdminLTE ===== */
    /* El "por página" vive arriba de la tabla (hook TOOLBAR_START); se oculta el de abajo */
    .fi-pagination .fi-pagination-records-per-page-select-ctn {
        display: none !important;
    }
    /* Selector superior compacto, alineado a la izquierda del toolbar */
    .siscor-per-page-top {
        display: block;
        margin-inline-end: auto;
    }
    /* Números abajo a la derecha; "Mostrando X a Y de Z" a la izquierda.
       (Al ocultar el "por página" central, el grid los dejaba al medio.) */
    .fi-pagination .fi-pagination-overview {
        grid-column: 1;
        justify-self: start;
    }
    .fi-pagination .fi-pagination-items {
        grid-column: 3;
        justify-self: end;
    }
    /* Caja de números: borde limpio, sin sombra */
    .fi-pagination-items {
        box-shadow: none;
        border: 1px solid #d2d6de;
        border-radius: .375rem;
        overflow: hidden;
    }
    /* Página activa: verde SISCOR con número en blanco */
    .fi-pagination-item.fi-active .fi-pagination-item-btn {
        background: var(--siscor-green) !important;
    }
    .fi-pagination-item.fi-active .fi-pagination-item-label {
        color: #ffffff !important;
    }
    /* Hover de páginas: verde muy claro */
    .fi-pagination-item-btn:enabled:hover {
        background: #e8f5ee;
    }
    /* Texto "Mostrando X a Y de Z" discreto */
    .fi-pagination-overview {
        color: #6b7280;
        font-weight: 400;
    }

    /* ===== Cajas / secciones ===== */
    .fi-section {
        border-radius: .375rem !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .12);
    }
    /* Botón primario (Crear) usa el verde del primary de Filament. */
</style>
