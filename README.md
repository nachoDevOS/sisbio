# SISBIO

Sistema de control biométrico — Gobierno Autónomo Departamental del Beni.
Panel en Laravel 13 + Filament 5 que administra equipos biométricos ZKTeco
(vía un microservicio Python) y consulta la base institucional **SIA** en un
SQL Server 2008 R2 remoto.

```
Equipos ZKTeco <--TCP 4370--> device-service (Python/FastAPI) <--REST + X-Auth-Token--> Laravel/Filament
                                                                                          |
                                                              SQL Server 2008 R2 (SIA) <--+  (conexión 'sia', pdo_sqlsrv)
                                                              MySQL local (sisbio)     <--+  (conexión por defecto)
```

---

## 1. Requisitos generales

| Componente | Versión / detalle |
|---|---|
| PHP | 8.3 (Laragon: build **TS, vs16, x64**) |
| Composer | 2.x |
| Node.js + npm | Para assets (Vite / Tailwind 4) |
| MySQL | Base local `sisbio` (conexión por defecto) |
| Python | 3.10+ (solo para el microservicio de biométricos) |

### Instalación base

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
```

---

## 2. Conexión a los equipos biométricos (ZKTeco)

Laravel **nunca** habla TCP directo con los equipos. Todo pasa por el
microservicio `device-service/` (FastAPI + pyzk), única pieza que abre
sockets al puerto **4370** de cada equipo.

### Requisitos

- Python 3.10+ con `venv` disponible.
- Red: la máquina que corre `device-service` debe alcanzar la IP de cada
  equipo ZKTeco por **TCP 4370**.
- Un token compartido: el mismo valor en el `.env` de Laravel y en el
  `.env` de `device-service` (`DEVICE_SERVICE_TOKEN`).
- El microservicio **no debe exponerse a internet**: solo localhost / red interna.

### Pasos

1. **Instalar dependencias del microservicio:**

   ```bash
   cd device-service
   python3 -m venv .venv
   source .venv/bin/activate        # Windows: .venv\Scripts\activate
   pip install -r requirements.txt  # fastapi, uvicorn, pydantic, pyzk
   ```

2. **Configurar el token compartido:**

   ```bash
   cp .env.example .env
   # editar .env: DEVICE_SERVICE_TOKEN = mismo valor que en el .env de Laravel
   ```

3. **Levantar el servicio** (puerto 9001, el que Laravel espera por defecto):

   ```bash
   set -a && source .env && set +a
   python3 -m uvicorn main:app --host 127.0.0.1 --port 9001
   ```

4. **Configurar Laravel** — en `.env`:

   ```env
   DEVICE_SERVICE_URL=http://127.0.0.1:9001
   DEVICE_SERVICE_TOKEN=el_mismo_token_del_microservicio
   ```

5. **Registrar los equipos en el panel** (recurso *Equipos*): IP, puerto
   (4370 por defecto), COMM key y ubicación de cada aparato.

6. **Verificar:**

   ```bash
   # ¿Vive el microservicio? (sin token)
   curl http://127.0.0.1:9001/health

   # ¿Responde un equipo? (con token)
   curl -H "X-Auth-Token: TU_TOKEN" "http://127.0.0.1:9001/device/info?ip=192.168.1.201&port=4370&password=0"
   ```

   En el panel, la acción **"Probar conexión"** de cada equipo hace esta misma
   consulta y guarda estado, algoritmo y hora; **"Ver marcaciones"** consulta
   `/device/attendance` en vivo.

Endpoints disponibles: `/health`, `/device/info`, `/device/users`,
`/device/attendance` (todos menos `/health` exigen `X-Auth-Token`).
Más detalle en [device-service/README.md](device-service/README.md).

---

## 3. Conexión a SQL Server 2008 R2 (base SIA)

El sistema consulta la base institucional `SIA_DEV` en un SQL Server 2008 R2
remoto mediante la conexión Laravel **`sia`** (`config/database.php`).

### Requisitos

- **Red:** alcance TCP al servidor SQL por el puerto **1433**.
- **ODBC:** "ODBC Driver 17 for SQL Server" (x64) instalado en Windows.
- **PHP:** extensión `pdo_sqlsrv` (PECL 5.12+). La build debe coincidir con
  el PHP instalado: misma versión (8.3), **Thread Safety (TS)**, **vs16** y
  **x64**. Verificar antes de descargar con `php -i` (Thread Safety y
  `extension_dir`).
- **TLS antiguo (crítico):** SQL Server 2008 R2 no soporta TLS moderno. La
  conexión **debe** llevar `encrypt=no` y `trust_server_certificate=true`;
  sin eso el handshake TLS falla con drivers actuales.
- **Docker (Debian):** instalar `msodbcsql18` + `pecl install sqlsrv pdo_sqlsrv`
  en la imagen. La configuración no cambia: todo se lee de variables de entorno.

### Pasos

1. **Verificar red** (PowerShell):

   ```powershell
   Test-NetConnection IP_DEL_SERVIDOR -Port 1433
   # debe dar TcpTestSucceeded : True
   ```

2. **Verificar la extensión PHP:**

   ```bash
   php -m | grep sqlsrv
   # debe listar: pdo_sqlsrv
   ```

   Si falta: descargar de PECL la DLL que coincida con la build de PHP,
   copiarla a `ext/` y agregar `extension=pdo_sqlsrv` al `php.ini`.

3. **Verificar el driver ODBC** (PowerShell):

   ```powershell
   Get-OdbcDriver | Where-Object Name -like '*SQL Server*'
   # debe listar "ODBC Driver 17 for SQL Server" en 64-bit
   ```

4. **Configurar `.env`** (las credenciales **solo** van aquí — nunca en
   commits, código ni documentación; `.env` está en `.gitignore`):

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

5. **Limpiar caché de configuración:**

   ```bash
   php artisan config:clear
   ```

6. **Prueba final desde Laravel:**

   ```bash
   # Comillas dobles por fuera: en cmd.exe el "->" con comillas simples
   # se interpreta como redirección y crea un archivo vacío.
   php artisan tinker --execute "var_dump(DB::connection('sia')->select('SELECT TOP 3 name FROM sys.tables'));"
   ```

   Debe devolver nombres de tablas reales de `SIA_DEV`.

### Uso en código

```php
DB::connection('sia')->select('...');
// o en un modelo:
protected $connection = 'sia';
```

---

## 4. Levantar el sistema completo (desarrollo)

1. MySQL local corriendo (Laragon).
2. `device-service` corriendo en `127.0.0.1:9001`.
3. Acceso de red al SQL Server (puerto 1433).
4. `php artisan serve` (o el vhost de Laragon) + `npm run dev` si se tocan assets.

---

## Documentación

- Bitácora de sesiones: [docs/sesiones/](docs/sesiones/)
- Microservicio de biométricos: [device-service/README.md](device-service/README.md)
