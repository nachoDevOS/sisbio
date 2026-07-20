# SISBIO

Sistema de Sincronización de Biométricos — Gobierno Autónomo Departamental
del Beni. Aplicación **Laravel 13** (MVC clásico) que administra los equipos
biométricos ZKTeco de la institución (vía un microservicio Python) y
consulta la asistencia registrada en la base institucional **SIA**
(SQL Server 2008 R2 remoto).

```
Equipos ZKTeco <--TCP 4370--> device-service (Python/FastAPI) <--REST + X-Auth-Token--> Laravel
                                                                                          |
                                                              SQL Server 2008 R2 (SIA) <--+  (conexión 'sia', pdo_sqlsrv)
                                                              MySQL local (sisbio)     <--+  (conexión por defecto)
```

---

## 1. ¿Qué hace el sistema?

### Tablero (dashboard)
- **Tarjetas de equipos:** total registrado, en línea, fuera de línea y maestros.
- **Equipos fuera de línea:** tabla con los equipos activos sin conexión
  (nombre, IP, ubicación, última sincronización); clic en la fila lleva a
  editar el equipo. Si todo está bien muestra «Todos los equipos están en línea».
- **Asistencia SIA:** marcaciones de hoy, personas que marcaron, marcaciones
  del mes y funcionarios registrados. Con caché de 5 minutos y sin polling
  para no castigar el SQL Server 2008; si el SIA no responde, el tablero
  sigue en pie.
- **Gráfico:** marcaciones por día de los últimos 14 días.

### Equipos (biométricos ZKTeco)
- Alta/edición/baja de equipos: nombre, IP, puerto (4370), COMM key,
  ubicación, algoritmo, maestro/esclavo, activo.
- **Probar conexión:** consulta el equipo real vía el microservicio y guarda
  estado en línea, algoritmo y hora del aparato.
- **Ver marcaciones:** lee las marcaciones directamente del equipo, en vivo.

### Asistencia SIA (solo lectura)
- **Marcaciones:** listado paginado con filtro por rango de fechas y tipo,
  búsqueda por funcionario y orden por fecha. La paginación usa
  `ROW_NUMBER()` (grammar propio) porque SQL Server 2008 no soporta
  `OFFSET/FETCH`. El filtro por defecto (mes actual hasta hoy) excluye las
  fechas basura que arrastra el SIA (años 2064/2103).
- **Funcionarios:** personal registrado en el SIA con su PIN de reloj.

### Usuarios, roles y permisos
- Usuarios del sistema con **foto de perfil** (correo y contraseña).
- **Roles y permisos** propios (spatie/laravel-permission): matriz de
  checkboxes por recurso y habilidad (ver/crear/editar/eliminar) en `/roles`.
  Cada controlador exige el permiso correspondiente vía `$this->authorize()`.

### Experiencia de uso
- Identidad institucional: sidebar petróleo con el logo y el nombre del
  sistema (`APP_NAME`), topbar blanco, tablas con cabecera a juego y
  paginación con la página activa resaltada.
- Tras crear un registro se vuelve al listado.
- Mensajes flash en español para crear/guardar/eliminar y para errores.
- La raíz `/` muestra el Escritorio si hay sesión; si no, pide login.

---

## 2. Cómo funciona (arquitectura)

| Pieza | Rol |
|---|---|
| **Laravel 13 (MVC)** | Controladores + Blade, lógica de negocio, base local MySQL `sisbio` (usuarios, roles, equipos). |
| **device-service (Python/FastAPI + pyzk)** | Única pieza que habla TCP 4370 con los ZKTeco. Laravel lo consume por REST con token `X-Auth-Token` (`app/Services/DeviceService.php`). |
| **Conexión `sia` (pdo_sqlsrv)** | Lectura de la base institucional `SIA_DEV` en SQL Server 2008 R2. Modelos de solo lectura en `app/Models/Sia/` y grammar `SqlServer2008Grammar` para paginación compatible. |

Puntos clave:

- Laravel **nunca** abre sockets a los equipos; si el microservicio está
  caído, el sistema sigue funcionando (solo fallan las acciones de equipos).
- Las consultas al SIA se cachean 5 minutos y toleran caída del servidor.
- El tema visual vive en `resources/views/layouts/app.blade.php` (CSS
  embebido); no requiere build de Vite para cambiar estilos.

---

## 3. Requisitos

### Comunes (desarrollo y producción)

| Componente | Versión / detalle |
|---|---|
| PHP | 8.3 con extensiones: `pdo_mysql`, `pdo_sqlsrv`, `intl`, `zip`, `gd` |
| Composer | 2.x |
| Node.js + npm | Solo para compilar assets (Vite / Tailwind 4) |
| MySQL / MariaDB | Base local `sisbio` (conexión por defecto) |
| ODBC | «ODBC Driver 17 for SQL Server» x64 (18 en Linux con `msodbcsql18`) |
| Python | 3.10+ para el microservicio `device-service` |
| Red | TCP 1433 al SQL Server del SIA; TCP 4370 a cada equipo ZKTeco (desde la máquina del microservicio) |

> **pdo_sqlsrv en Windows:** la DLL de PECL debe coincidir con la build de
> PHP: 8.3, **Thread Safety (TS)**, **vs16**, **x64** (verificar con `php -i`).
> **En Docker/Debian:** `msodbcsql18` + `pecl install sqlsrv pdo_sqlsrv`.

---

## 4. Despliegue en desarrollo

```bash
git clone <repo> sisbio && cd sisbio
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link        # fotos de perfil (avatars)
php artisan db:seed             # usuario de prueba test@example.com
```

Configurar en `.env`:

```env
APP_NAME="SISTEMA DE SINCRONIZACIÓN DE BIOMETRICO"
DB_DATABASE=sisbio ...                    # MySQL local
DB_HOST_SIA=... DB_USERNAME_SIA=...       # SQL Server del SIA (sección 6)
DEVICE_SERVICE_URL=http://127.0.0.1:9001  # microservicio (sección 5)
DEVICE_SERVICE_TOKEN=un_token_compartido
```

Levantar todo:

1. MySQL corriendo (Laragon).
2. `device-service` corriendo en `127.0.0.1:9001` (sección 5).
3. Acceso de red al SQL Server (puerto 1433).
4. `php artisan serve` (o el vhost de Laragon) + `npm run dev` si se tocan assets.

Pruebas:

```bash
php artisan test --compact
```

---

## 5. Microservicio de biométricos (`device-service`)

Laravel **nunca** habla TCP directo con los equipos. Todo pasa por
`device-service/` (FastAPI + pyzk), única pieza que abre sockets al puerto
**4370** de cada equipo.

1. **Instalar dependencias:**

   ```bash
   cd device-service
   python3 -m venv .venv
   source .venv/bin/activate        # Windows: .venv\Scripts\activate
   pip install -r requirements.txt  # fastapi, uvicorn, pydantic, pyzk
   ```

2. **Token compartido:** `cp .env.example .env` y poner en
   `DEVICE_SERVICE_TOKEN` el mismo valor que en el `.env` de Laravel.

3. **Levantar** (puerto 9001, el que Laravel espera por defecto):

   ```bash
   set -a && source .env && set +a
   python3 -m uvicorn main:app --host 127.0.0.1 --port 9001
   ```

4. **Registrar los equipos en el panel** (recurso *Equipos*): IP, puerto,
   COMM key y ubicación.

5. **Verificar:**

   ```bash
   curl http://127.0.0.1:9001/health                     # vive (sin token)
   curl -H "X-Auth-Token: TU_TOKEN" "http://127.0.0.1:9001/device/info?ip=192.168.1.201&port=4370&password=0"
   ```

Endpoints: `/health`, `/device/info`, `/device/users`, `/device/attendance`
(todos menos `/health` exigen `X-Auth-Token`). El microservicio **no debe
exponerse a internet**: solo localhost / red interna. Más detalle en
[device-service/README.md](device-service/README.md).

---

## 6. Conexión a SQL Server 2008 R2 (base SIA)

La conexión Laravel **`sia`** (`config/database.php`) lee la base
institucional `SIA_DEV`.

1. **Red** (PowerShell): `Test-NetConnection IP_DEL_SERVIDOR -Port 1433`
   debe dar `TcpTestSucceeded : True`.
2. **Extensión PHP:** `php -m | grep sqlsrv` debe listar `pdo_sqlsrv`.
3. **Driver ODBC** (PowerShell): `Get-OdbcDriver | Where-Object Name -like '*SQL Server*'`.
4. **`.env`** (credenciales solo aquí; `.env` está en `.gitignore`):

   ```env
   # SIA - SQL Server 2008 R2 remoto (encrypt=no obligatorio por TLS antiguo)
   DB_HOST_SIA=ip_del_servidor
   DB_PORT_SIA=1433
   DB_DATABASE_SIA=SIA_DEV
   DB_USERNAME_SIA=usuario
   DB_PASSWORD_SIA="contraseña_entre_comillas_si_tiene_caracteres_especiales"
   DB_ENCRYPT_SIA=no
   DB_TRUST_SERVER_CERT_SIA=true
   ```

   > **TLS antiguo (crítico):** SQL Server 2008 R2 no soporta TLS moderno;
   > sin `encrypt=no` + `trust_server_certificate=true` el handshake falla.

5. `php artisan config:clear` y prueba final:

   ```bash
   php artisan tinker --execute "var_dump(DB::connection('sia')->select('SELECT TOP 3 name FROM sys.tables'));"
   ```

Uso en código: `DB::connection('sia')` o `protected $connection = 'sia';`
en el modelo (los de `app/Models/Sia/` ya lo hacen).

---

## 7. Despliegue en producción

### Requisitos adicionales

- Servidor web (Nginx/Apache) apuntando a `public/` con HTTPS.
- PHP-FPM 8.3 con OPcache habilitado.
- Supervisor (o systemd) para el worker de colas.
- El microservicio como servicio del sistema (systemd en Linux, NSSM o
  Tarea Programada en Windows), escuchando **solo** en localhost/red interna.

### Pasos

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env               # y completar (ver abajo)
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`.env` de producción (diferencias clave):

```env
APP_ENV=production
APP_DEBUG=false                    # activa los toasts de error en español
APP_URL=https://sisbio.beni.gob.bo # la URL real
LOG_LEVEL=warning
SESSION_ENCRYPT=true
```

Worker de colas (Supervisor):

```ini
[program:sisbio-queue]
command=php /ruta/sisbio/artisan queue:work --tries=3 --timeout=90
autostart=true
autorestart=true
```

### Primer usuario y permisos

`php artisan db:seed` corre `RolesAndPermissionsSeeder` (genera los permisos
y el rol `super_admin`) y crea el usuario de prueba `test@example.com`. En
producción, cambiar la contraseña de ese usuario o crear el propio con
`php artisan tinker` y asignarle el rol:

```bash
php artisan tinker --execute '
$u = App\Models\User::factory()->create(["name" => "Admin", "email" => "admin@beni.gob.bo"]);
$u->assignRole("super_admin");
'
```

### Checklist de seguridad

- [ ] `APP_DEBUG=false` y `APP_KEY` generada.
- [ ] `.env` fuera del control de versiones y con permisos restringidos.
- [ ] `DEVICE_SERVICE_TOKEN` largo y aleatorio; microservicio **no** expuesto a internet.
- [ ] Usuario del SQL Server SIA con permisos de **solo lectura**.
- [ ] HTTPS forzado; `storage/` y `bootstrap/cache/` escribibles solo por el usuario de PHP.

### Al actualizar versión

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

---

## 8. Documentación

- Mapa del código (qué archivo hace qué): [docs/ESTRUCTURA.md](docs/ESTRUCTURA.md)
- Bitácora de sesiones de trabajo: [docs/sesiones/](docs/sesiones/)
- Microservicio de biométricos: [device-service/README.md](device-service/README.md)
- Pruebas: `php artisan test --compact` (Pest 4; las pruebas simulan la
  conexión SIA en SQLite, no requieren el servidor real).
