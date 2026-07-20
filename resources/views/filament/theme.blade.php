{{-- Tema visual del panel: sidebar azul marino + topbar blanco + cabecera de tabla azul. --}}
{{-- Sin build de Vite: se inyecta como <style> vía renderHook (ver AdminPanelProvider). --}}
<style>
    /* ===== Paleta del panel ===== */
    :root {
        --panel-verde: #00a65a;          /* Verde principal (botón Crear, acción primaria) */
        --panel-verde-osc: #008d4c;
        --panel-azul: #0d3b3e;           /* Azul petróleo de cabecera de tabla / acento (match sidebar) */
        --panel-azul-osc: #082628;
        --panel-sidebar: #0d3b3e;        /* Fondo del sidebar (azul petróleo) */
        --panel-sidebar-header: #082628; /* Bloque del logo, más oscuro */
        --panel-sidebar-text: #ffffff;   /* Texto del sidebar */
        --panel-sidebar-hover: #164e52;
        --panel-sidebar-active: #1c6266;
        --panel-body-bg: #f4f6f9;        /* Fondo del contenido */
        --panel-topbar-h: 3.5rem;
    }

    /* ===== Layout: sidebar hasta arriba, topbar y contenido a su derecha ===== */
    /* Filament pone el topbar a lo ancho y el sidebar debajo (top: 4rem).
       Aquí el sidebar abierto pasa a fijo desde el borde superior, y el topbar
       y el contenido arrancan después de su ancho. */
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

        /* Logo dentro del sidebar: Filament oculta el header del sidebar en
           desktop cuando hay topbar (lg:hidden); se restaura y se quita el
           logo duplicado del topbar mientras el sidebar esté abierto. */
        .fi-body-has-topbar .fi-sidebar.fi-sidebar-open .fi-sidebar-header {
            display: flex !important;
        }
        .fi-body-has-topbar:has(.fi-sidebar.fi-sidebar-open) .fi-topbar .fi-logo {
            display: none !important;
        }
    }

    /* ===== Topbar: banda blanca, delgada ===== */
    .fi-topbar {
        background: #ffffff !important;
        border-bottom: 1px solid #e5e7eb;
        box-shadow: none;
        min-height: var(--panel-topbar-h) !important;
        padding-block: 0;
    }
    @media (min-width: 1024px) {
        .fi-body-has-topbar .fi-sidebar:not(.fi-sidebar-open) {
            top: var(--panel-topbar-h) !important;
            height: calc(100dvh - var(--panel-topbar-h)) !important;
        }
    }

    /* ===== Sidebar: azul marino ===== */
    .fi-sidebar {
        background: var(--panel-sidebar) !important;
        border-right: 0;
    }
    .fi-sidebar .fi-sidebar-header {
        background: var(--panel-sidebar-header) !important;
        box-shadow: none;
    }
    .fi-sidebar .fi-sidebar-header .fi-logo { color: #ffffff !important; }
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
        color: var(--panel-sidebar-text);
        font-size: .875rem;
    }
    .fi-sidebar .fi-sidebar-item-icon {
        color: var(--panel-sidebar-text);
        width: 1.125rem;
        height: 1.125rem;
    }
    .fi-sidebar .fi-sidebar-group-label {
        color: #5b6690;
        font-size: .6875rem;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .fi-sidebar .fi-sidebar-item-btn:hover { background: var(--panel-sidebar-hover); }
    .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-label { color: #ffffff; }
    .fi-sidebar .fi-sidebar-item-btn:hover .fi-sidebar-item-icon { color: #ffffff; }
    /* Ítem activo: fondo más claro + barra azul a la izquierda */
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-btn {
        background: var(--panel-sidebar-active);
        box-shadow: inset 3px 0 0 var(--panel-azul);
    }
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label { color: #ffffff; font-weight: 600; }
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon { color: #ffffff; }

    /* ===== Títulos de página más compactos (Filament trae 1.5–1.875rem) ===== */
    .fi-header-heading {
        font-size: 1.25rem !important;
        line-height: 1.75rem;
    }

    /* ===== Fondo del contenido ===== */
    .fi-body,
    .fi-main-ctn {
        background: var(--panel-body-bg);
    }

    /* ===== Tablas: cabecera azul con texto blanco ===== */
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
        background-color: var(--panel-azul) !important;
        border-color: rgba(255, 255, 255, .2) !important;
    }
    .fi-ta-table thead > tr > th,
    .fi-ta-table thead .fi-ta-header-cell,
    .fi-ta-table thead .fi-ta-header-cell-label,
    .fi-ta-table thead button,
    .fi-ta-table thead svg {
        color: #ffffff !important;
    }

    /* ===== Filas: cebra suave + hover azulado ===== */
    .fi-ta-row.fi-striped { background: #f6f7fb; }
    .fi-ta-row:hover { background: #eef0fa; }

    /* ===== Paginación ===== */
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
    /* Página activa: azul del panel con número en blanco */
    .fi-pagination-item.fi-active .fi-pagination-item-btn {
        background: var(--panel-azul) !important;
    }
    .fi-pagination-item.fi-active .fi-pagination-item-label {
        color: #ffffff !important;
    }
    /* Hover de páginas: azul muy claro */
    .fi-pagination-item-btn:enabled:hover {
        background: #eef0fa;
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

    /* ===== Densidad compacta: filas de tabla y contenido de secciones ===== */
    /* Filament trae bastante aire por defecto; menos padding = más filas
       visibles sin scroll, sobre todo en Marcaciones/Funcionarios. */
    .fi-ta-cell {
        padding-block: .5rem !important;
        padding-inline: .75rem !important;
    }
    .fi-section-content {
        padding: 1rem 1.25rem !important;
    }

    /* ===== Filtros arriba de la tabla: barra compacta, no tarjeta pesada ===== */
    .fi-ta-filters-above-content-ctn .fi-ta-filters {
        padding: .75rem 1rem !important;
        box-shadow: none !important;
        border: 1px solid #d2d6de;
        border-radius: .375rem;
    }
    .fi-ta-filters-above-content-ctn .fi-ta-filters-header {
        margin-bottom: .5rem;
    }
    .fi-ta-filters-above-content-ctn .fi-ta-filters-heading {
        font-size: .8125rem !important;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
</style>
