# Estructura del código — SISBIO

Mapa de archivos del sistema y qué hace cada uno. Para el "cómo desplegar"
ver el [README](../README.md); para la historia día a día, [sesiones/](sesiones/).

---

## Idea clave: aquí no hay controladores clásicos

Filament reemplaza los controladores de Laravel por **Resources** (recursos)
con páginas Livewire. Cada recurso se parte en cuatro piezas:

| Pieza | Qué define | Ejemplo (Usuarios) |
|---|---|---|
| `XxxResource.php` | Modelo, ícono, grupo del menú, rutas de sus páginas | `UserResource.php` |
| `Pages/` | Las pantallas: listar, crear, editar | `ListUsers`, `CreateUser`, `EditUser` |
| `Schemas/XxxForm.php` | Los campos del formulario | `UserForm.php` |
| `Tables/XxxsTable.php` | Las columnas, filtros y acciones del listado | `UsersTable.php` |

**Regla rápida:** ¿algo del listado (columnas, botones de fila, filtros)? →
`Tables/`. ¿Algo del formulario? → `Schemas/`. ¿Redirecciones o
comportamiento de la pantalla? → `Pages/`. ¿Menú/permisos/rutas? → el
`Resource`.

---

## Respuestas rápidas: ¿dónde está...?

| Busco | Archivo |
|---|---|
| Listado de **funcionarios (personas del SIA)** | `app/Filament/Resources/Personas/Tables/PersonasTable.php` (columnas/búsqueda) y `Pages/ListPersonas.php` (la pantalla) |
| Listado de **usuarios del panel** | `app/Filament/Resources/Users/Tables/UsersTable.php` |
| Listado de **marcaciones del SIA** | `app/Filament/Resources/Marcaciones/Tables/MarcacionesTable.php` (filtros de fecha/tipo, orden) |
| **Comunicación con los biométricos (Python)** | `device-service/main.py` — todo el microservicio en un archivo |
| Cliente Laravel → microservicio | `app/Services/DeviceService.php` |
| Acciones **"Probar conexión"** y **"Ver marcaciones"** | `app/Filament/Resources/Equipos/Tables/EquiposTable.php` |
| Formulario de alta/edición de equipos | `app/Filament/Resources/Equipos/Schemas/EquipoForm.php` |
| Foto de perfil (avatar) del usuario | Campo: `Users/Schemas/UserForm.php` · URL: `app/Models/User.php` (`getFilamentAvatarUrl`) |
| Conexión al SQL Server 2008 del SIA | `config/database.php` (conexión `sia`) + `app/Database/SqlServer2008*.php` |
| Tema visual (colores, sidebar, paginación) | `resources/views/filament/theme.blade.php` |
| Configuración global del panel (menú, logo, redirecciones, errores) | `app/Providers/Filament/AdminPanelProvider.php` |
| Comportamientos globales (sin "crear otro", tablas cebra, "por página" arriba) | `app/Providers/AppServiceProvider.php` |
| Permisos por recurso | `app/Policies/*.php` + roles de Filament Shield (recurso Roles en el panel) |

---

## Árbol comentado

```
app/
├── Database/
│   ├── SqlServer2008Connection.php   # Conexión sqlsrv que usa el grammar 2008
│   └── SqlServer2008Grammar.php      # Paginación con ROW_NUMBER() (2008 no tiene OFFSET/FETCH)
├── Exceptions/
│   └── DeviceServiceException.php    # Errores del microservicio con mensaje claro para el usuario
├── Filament/
│   ├── Resources/
│   │   ├── Equipos/                  # CRUD de equipos ZKTeco (base local)
│   │   │   ├── EquipoResource.php
│   │   │   ├── Pages/                # ListEquipos, CreateEquipo, EditEquipo
│   │   │   ├── Schemas/EquipoForm.php
│   │   │   └── Tables/EquiposTable.php   # ← "Probar conexión" y "Ver marcaciones"
│   │   ├── Marcaciones/              # Solo lectura, datos del SIA
│   │   │   ├── MarcacionResource.php
│   │   │   ├── Pages/ListMarcaciones.php
│   │   │   └── Tables/MarcacionesTable.php  # filtros por fecha/tipo, búsqueda por funcionario
│   │   ├── Personas/                 # Solo lectura, funcionarios del SIA
│   │   │   ├── PersonaResource.php
│   │   │   ├── Pages/ListPersonas.php
│   │   │   └── Tables/PersonasTable.php
│   │   └── Users/                    # Usuarios del panel (con roles y avatar)
│   │       ├── UserResource.php      # ← agrupado junto a Roles en el menú
│   │       ├── Pages/                # ListUsers, CreateUser, EditUser
│   │       ├── Schemas/UserForm.php  # ← campo de foto de perfil
│   │       └── Tables/UsersTable.php # ← columna circular con la foto
│   └── Widgets/                      # Tablero (dashboard)
│       ├── EquiposStats.php          # Tarjetas: total/en línea/fuera/maestros
│       ├── EquiposFueraDeLinea.php   # Tabla de equipos caídos (clic → editar)
│       ├── SiaAsistenciaStats.php    # Tarjetas de asistencia SIA (caché 5 min)
│       └── SiaMarcacionesChart.php   # Gráfico de marcaciones, últimos 14 días
├── Models/
│   ├── Equipo.php                    # Equipo ZKTeco (tabla local `equipos`)
│   ├── User.php                      # Usuario del panel (roles Spatie + avatar)
│   └── Sia/                          # Solo lectura, conexión `sia`
│       ├── Asistencia.php            # Marcaciones (clave primaria compuesta)
│       └── Persona.php               # Funcionarios con su PIN de reloj
├── Policies/                         # Autorización por modelo (las usa Shield)
│   ├── EquipoPolicy.php · RolePolicy.php · UserPolicy.php
├── Providers/
│   ├── AppServiceProvider.php        # Conexión sqlsrv 2008, reglas globales de UI
│   └── Filament/AdminPanelProvider.php  # Panel: marca, colores, menú, widgets, errores
└── Services/
    └── DeviceService.php             # Cliente HTTP del microservicio (X-Auth-Token)

device-service/                       # Microservicio Python (FastAPI + pyzk)
├── main.py                           # TODO el servicio: endpoints /health, /device/info,
│                                     #   /device/users, /device/attendance; abre TCP 4370
├── requirements.txt                  # fastapi, uvicorn, pydantic, pyzk
└── .env.example                      # DEVICE_SERVICE_TOKEN (compartido con Laravel)

resources/views/filament/
├── theme.blade.php                   # Tema SISCOR/AdminLTE: sidebar, topbar, tablas, paginación
├── logo.blade.php                    # Marca: ícono + APP_NAME
├── tables/per-page-top.blade.php     # Selector "por página" arriba de cada tabla
└── equipos/marcaciones.blade.php     # Modal "Ver marcaciones" en vivo del equipo

config/
├── database.php                      # Conexiones: mysql (defecto) y `sia` (SQL Server 2008)
├── services.php                      # URL y token del device-service
└── filament-shield.php               # Configuración de roles/permisos (Shield)

database/
├── migrations/                       # Tablas locales: users (+avatar), equipos, etc.
├── factories/                        # Datos falsos para pruebas
└── seeders/DatabaseSeeder.php        # Usuario de prueba

routes/web.php                        # Solo `/` → redirige a /admin (el panel registra el resto)

tests/Feature/                        # Pruebas Pest (la conexión SIA se simula en SQLite)
docs/
├── ESTRUCTURA.md                     # ← este archivo
└── sesiones/MM-YYYY/YYYY-MM-DD.md    # Bitácora de cada día de trabajo
```

---

## Flujos principales

### 1. "Probar conexión" de un equipo

```
Panel (botón en EquiposTable)
  → DeviceService::info($equipo)                 [app/Services/DeviceService.php]
  → GET http://127.0.0.1:9001/device/info        [HTTP + X-Auth-Token]
  → device-service/main.py: device_info()        [abre TCP 4370 al equipo con pyzk]
  → respuesta JSON → se guarda en_linea/algoritmo/ultima_sync en la tabla `equipos`
  → toast de éxito o error en el panel
```

### 2. Listado de marcaciones del SIA

```
Menú «Asistencia SIA → Marcaciones»
  → ListMarcaciones (página Livewire)
  → MarcacionesTable (columnas/filtros)
  → modelo Sia\Asistencia (conexión `sia`)
  → SqlServer2008Grammar convierte la paginación a ROW_NUMBER()
  → SQL Server 2008 R2 remoto (solo lectura)
```

### 3. Tablero al abrir el panel

```
Dashboard
  ├── EquiposStats + EquiposFueraDeLinea   → MySQL local (barato, sin caché)
  └── SiaAsistenciaStats + SiaMarcacionesChart
        → caché 5 min → si expiró, consulta al SIA
        → si el SIA no responde: tarjeta "Sin conexión" / gráfico en cero (el panel no se cae)
```

### 4. Permisos

```
Usuario inicia sesión → Shield carga sus roles (Spatie Permission, MySQL local)
  → cada Resource/Página/Widget consulta su Policy o permiso generado
  → lo que no puede ver, no aparece en el menú
```
