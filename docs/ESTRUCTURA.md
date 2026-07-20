# Estructura del código — SISBIO

Mapa de archivos del sistema y qué hace cada uno. Para el "cómo desplegar"
ver el [README](../README.md); para la historia día a día, [sesiones/](sesiones/).

---

## Idea clave: MVC clásico de Laravel

El sistema corrió durante un tiempo con Filament (panel en `/admin`) en
paralelo a un sitio Blade/MVC propio. Filament se retiró por completo: hoy
todo es MVC clásico — controladores, FormRequests, vistas Blade y rutas en
`routes/web.php`.

| Pieza | Qué define | Ejemplo (Usuarios) |
|---|---|---|
| `app/Http/Controllers/XxxController.php` | Listar/crear/editar/eliminar | `UserController.php` |
| `app/Http/Requests/StoreXxxRequest.php` / `UpdateXxxRequest.php` | Validación | `StoreUserRequest.php` |
| `resources/views/xxx/*.blade.php` | Pantallas: `index`, `create`, `edit`, `_form` parcial | `usuarios/*.blade.php` |
| `app/Policies/XxxPolicy.php` | Autorización por acción (viewAny/view/create/update/delete) | `UserPolicy.php` |

**Regla rápida:** ¿algo del listado o la ficha? → la vista Blade del recurso.
¿Validación? → el FormRequest. ¿Quién puede hacer qué? → la Policy +
`$this->authorize()` en el controlador.

---

## Respuestas rápidas: ¿dónde está...?

| Busco | Archivo |
|---|---|
| Listado/ficha de **funcionarios (personas del SIA)** | `app/Http/Controllers/PersonaController.php` + `resources/views/funcionarios/*.blade.php` (la ficha incluye sus marcaciones filtradas) |
| Listado de **usuarios** (roles, avatar) | `app/Http/Controllers/UserController.php` + `resources/views/usuarios/*.blade.php` |
| **Roles y su matriz de permisos** | `app/Http/Controllers/RoleController.php` + `resources/views/roles/*.blade.php`; la lista de permisos vive en `App\Policies\RolePolicy::MODELOS`/`ABILIDADES` |
| Listado de **marcaciones del SIA** (filtros de fecha/tipo/búsqueda) | `app/Http/Controllers/MarcacionController.php` + `resources/views/marcaciones/index.blade.php` |
| **Comunicación con los biométricos (Python)** | `device-service/main.py` — todo el microservicio en un archivo |
| Cliente Laravel → microservicio | `app/Services/DeviceService.php` |
| Acciones **"Probar conexión"** y **"Ver marcaciones"** | `app/Http/Controllers/EquipoController.php` (`probarConexion`, `marcaciones`) |
| Formulario de alta/edición de equipos | `resources/views/equipos/_form.blade.php` |
| Foto de perfil (avatar) del usuario | Campo: `resources/views/usuarios/_form.blade.php` · guardado: `UserController.php` (disco `public`, carpeta `avatars`) |
| Conexión al SQL Server 2008 del SIA | `config/database.php` (conexión `sia`) + `app/Database/SqlServer2008*.php` |
| Tema visual (colores, sidebar, paginación) | `resources/views/layouts/app.blade.php` |
| Login/logout | `app/Http/Controllers/Auth/LoginController.php` + `resources/views/auth/login.blade.php` |
| Escritorio (tablero de inicio) | `app/Http/Controllers/DashboardController.php` + `resources/views/dashboard/index.blade.php` |
| Comportamientos globales (policy de Role, bypass de super_admin) | `app/Providers/AppServiceProvider.php` |
| Permisos por recurso | `app/Policies/*.php`, gestionados desde `/roles` |

---

## Árbol comentado

```
app/
├── Database/
│   ├── SqlServer2008Connection.php   # Conexión sqlsrv que usa el grammar 2008
│   └── SqlServer2008Grammar.php      # Paginación con ROW_NUMBER() (2008 no tiene OFFSET/FETCH)
├── Exceptions/
│   └── DeviceServiceException.php    # Errores del microservicio con mensaje claro para el usuario
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php   # Escritorio: stats de equipos + asistencia SIA + gráfico
│   │   ├── EquipoController.php      # CRUD + probarConexion() + marcaciones() (habla con el microservicio)
│   │   ├── MarcacionController.php   # Solo lectura: listado del SIA con filtros
│   │   ├── PersonaController.php     # CRUD de funcionarios + sus marcaciones en la ficha
│   │   ├── RoleController.php        # CRUD de roles + matriz de permisos
│   │   ├── UserController.php        # CRUD de usuarios del sistema (roles, avatar)
│   │   └── Auth/LoginController.php  # Login/logout (guard 'web')
│   └── Requests/                     # Store*/Update* por recurso
├── Models/
│   ├── Equipo.php                    # Equipo ZKTeco (tabla local `equipos`)
│   ├── User.php                      # Usuario del sistema (roles Spatie + avatar)
│   └── Sia/                          # Solo lectura, conexión `sia`
│       ├── Asistencia.php            # Marcaciones (clave primaria compuesta)
│       └── Persona.php               # Funcionarios con su PIN de reloj
├── Policies/                         # Autorización por modelo, un método por habilidad
│   ├── EquipoPolicy.php · RolePolicy.php · UserPolicy.php
│   └── Sia/PersonaPolicy.php · Sia/AsistenciaPolicy.php
├── Providers/
│   └── AppServiceProvider.php        # Conexión sqlsrv 2008, policy de Role, bypass de super_admin
└── Services/
    └── DeviceService.php             # Cliente HTTP del microservicio (X-Auth-Token)

device-service/                       # Microservicio Python (FastAPI + pyzk)
├── main.py                           # TODO el servicio: endpoints /health, /device/info,
│                                     #   /device/users, /device/attendance; abre TCP 4370
├── requirements.txt                  # fastapi, uvicorn, pydantic, pyzk
└── .env.example                      # DEVICE_SERVICE_TOKEN (compartido con Laravel)

resources/views/
├── layouts/app.blade.php             # Layout único: sidebar, topbar, paleta de colores, flashes
├── auth/login.blade.php
├── dashboard/index.blade.php
├── equipos/ · usuarios/ · funcionarios/ · marcaciones/ · roles/
│   └── index · create · edit · _form (parciales)  # mismo patrón en todos

config/
├── database.php                      # Conexiones: mysql (defecto) y `sia` (SQL Server 2008)
├── services.php                      # URL y token del device-service
└── permission.php                    # Config de spatie/laravel-permission

database/
├── migrations/                       # Tablas locales: users (+avatar), equipos, roles/permissions, etc.
├── factories/                        # Datos falsos para pruebas
└── seeders/
    ├── DatabaseSeeder.php            # Usuario de prueba + RolesAndPermissionsSeeder
    └── RolesAndPermissionsSeeder.php # Reproduce los permisos y el rol super_admin

routes/web.php                        # Todas las rutas: login, escritorio, y los CRUD

tests/Feature/                        # Pruebas Pest (la conexión SIA se simula en SQLite)
docs/
├── ESTRUCTURA.md                     # ← este archivo
└── sesiones/MM-YYYY/YYYY-MM-DD.md    # Bitácora de cada día de trabajo
```

---

## Flujos principales

### 1. "Probar conexión" de un equipo

```
Botón en equipos/index.blade.php (POST equipos/{equipo}/probar-conexion)
  → EquipoController::probarConexion()
  → DeviceService::info($equipo)                 [app/Services/DeviceService.php]
  → GET http://127.0.0.1:9001/device/info        [HTTP + X-Auth-Token]
  → device-service/main.py: device_info()        [abre TCP 4370 al equipo con pyzk]
  → respuesta JSON → se guarda en_linea/algoritmo/ultima_sync en la tabla `equipos`
  → mensaje flash de éxito o error
```

### 2. Listado de marcaciones del SIA

```
Sidebar → Marcaciones
  → MarcacionController::index() (filtros: rango de fechas, búsqueda, tipo)
  → modelo Sia\Asistencia (conexión `sia`)
  → SqlServer2008Grammar convierte la paginación a ROW_NUMBER()
  → SQL Server 2008 R2 remoto (solo lectura)
```

### 3. Escritorio al iniciar sesión

```
DashboardController::index()
  ├── Equipos: total/en línea/fuera/maestros + tabla de equipos caídos → MySQL local (sin caché)
  └── Asistencia SIA: tarjetas + mini gráfico (CSS puro) de 14 días
        → caché 5 min → si expiró, consulta al SIA
        → si el SIA no responde: tarjeta "Sin conexión" (el escritorio no se cae)
```

### 4. Permisos

```
Usuario inicia sesión → Gate::before en AppServiceProvider:
  - super_admin → acceso total, sin mirar permisos individuales
  - cualquier otro rol → cada controlador llama $this->authorize(...)
      → resuelve la Policy del modelo (convención App\Models\X → App\Policies\XPolicy)
      → la Policy pregunta $authUser->can('Habilidad:Modelo') (spatie/laravel-permission)
  → sin el permiso, la acción devuelve 403
```
