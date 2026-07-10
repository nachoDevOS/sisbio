{{-- Tema visual de SISBIO al estilo institucional (Gobierno del Beni). --}}
{{-- Banda verde arriba + sidebar navy + acentos verde/dorado. Sin build de Vite. --}}
<style>
    /* ===== Topbar: banda verde institucional con acento dorado ===== */
    .fi-topbar {
        background: #098429 !important;
        border-bottom: 3px solid #FFD700;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
    }
    /* Marca, íconos y textos del topbar en blanco */
    .fi-topbar .fi-logo,
    .fi-topbar a,
    .fi-topbar .fi-icon-btn,
    .fi-topbar .fi-topbar-item-btn {
        color: #ffffff !important;
    }

    /* ===== Sidebar: navy oscuro limpio ===== */
    .fi-sidebar {
        background: #1b2532 !important;
        border-right: 1px solid rgba(0, 0, 0, .15);
    }
    .fi-sidebar .fi-sidebar-header {
        background: #1b2532 !important;
    }
    .fi-sidebar .fi-sidebar-item-label { color: #d6dde5; }
    .fi-sidebar .fi-sidebar-item-icon { color: #8b97a5; }
    .fi-sidebar .fi-sidebar-group-label { color: #6b7885; }
    .fi-sidebar .fi-sidebar-item-btn:hover { background: rgba(255, 255, 255, .05); }
    /* Ítem activo: fondo más claro + barra + ícono verde */
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-btn {
        background: #2c3845;
        box-shadow: inset 3px 0 0 #2FB251;
    }
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-label { color: #ffffff; font-weight: 600; }
    .fi-sidebar .fi-sidebar-item.fi-active .fi-sidebar-item-icon { color: #2FB251; }

    /* ===== Cajas / tablas: limpias, redondeadas, con leve realce ===== */
    .fi-ta-ctn,
    .fi-section {
        border-radius: 1rem !important;
    }
    .fi-ta-ctn {
        box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
    }
    /* Botón primario (Crear equipo) ya usa el verde del primary de Filament. */
</style>
