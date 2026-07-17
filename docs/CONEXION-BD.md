# Conexión a las bases de datos — SISBIO

Este documento explica **cómo el sistema se conecta a sus bases de datos**, con el
código comentado línea por línea. Sirve como material de presentación.

---

## 1. Resumen: dos bases de datos

SISBIO trabaja contra **dos** bases de datos al mismo tiempo:

| Conexión  | Motor                | Uso                                   | Escritura |
|-----------|----------------------|---------------------------------------|-----------|
| `default` | MySQL (local)        | Datos del panel: equipos, usuarios, roles | Sí (lectura y escritura) |
| `sia`     | SQL Server 2008 R2   | Sistema legado SIA: funcionarios y marcaciones | **No (solo lectura)** |

La base local es propia del sistema. La base `sia` es un servidor **remoto y antiguo**
que ya existía; SISBIO solo la **lee**, nunca escribe sobre ella.

Toda la configuración vive en un solo archivo: **`config/database.php`**. Los datos
sensibles (host, usuario, contraseña) **no** se escriben ahí: se leen del archivo
`.env` con la función `env()`, para no exponerlos en el código.

```
┌─────────────┐   conexión 'default'   ┌──────────────────────┐
│   SISBIO    │ ─────────────────────► │  MySQL local (sisbio)│  equipos, users, roles
│  (Laravel)  │                        └──────────────────────┘
│             │   conexión 'sia'       ┌──────────────────────┐
│             │ ─────────────────────► │ SQL Server 2008 (SIA)│  Personas, Asistencia (solo lectura)
└─────────────┘                        └──────────────────────┘
```

---

## 2. Conexión local (MySQL) — datos del panel

Es la conexión **por defecto**: si una consulta no dice a qué base ir, va aquí.

```php
// config/database.php

// 'default' decide qué conexión usar cuando no se indica otra.
// Se lee de la variable DB_CONNECTION del .env; si no existe, usa 'sqlite'.
'default' => env('DB_CONNECTION', 'sqlite'),

'connections' => [

    'mysql' => [
        'driver'    => 'mysql',                       // Motor de base de datos.
        'host'      => env('DB_HOST', '127.0.0.1'),   // Dónde está el servidor MySQL.
        'port'      => env('DB_PORT', '3306'),        // Puerto estándar de MySQL.
        'database'  => env('DB_DATABASE', 'laravel'), // Nombre de la base (en producción: "sisbio").
        'username'  => env('DB_USERNAME', 'root'),    // Usuario de conexión.
        'password'  => env('DB_PASSWORD', ''),        // Contraseña (vacía en local).
        'charset'   => env('DB_CHARSET', 'utf8mb4'),  // Juego de caracteres (soporta emojis/acentos).
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix'    => '',                            // Sin prefijo en los nombres de tabla.
        'strict'    => true,                          // Modo estricto: MySQL avisa de datos inválidos.
    ],
],
```

**Variables en `.env`:**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisbio
DB_USERNAME=root
DB_PASSWORD=
```

Los modelos que usan esta conexión (por ejemplo `App\Models\Equipo` y
`App\Models\User`) **no declaran nada especial**: al no indicar conexión, Laravel
usa la `default` automáticamente.

---

## 3. Conexión SIA (SQL Server 2008 R2) — sistema legado, solo lectura

Esta es la parte importante y la que más se explica en la presentación, porque
conecta con un **servidor antiguo** y tiene ajustes especiales.

```php
// config/database.php  →  dentro de 'connections'

'sia' => [
    'driver'   => 'sqlsrv',                        // Driver de SQL Server (requiere la extensión pdo_sqlsrv de PHP).
    'host'     => env('DB_HOST_SIA', '127.0.0.1'), // IP/nombre del servidor SIA en la red.
    'port'     => env('DB_PORT_SIA', '1433'),      // Puerto estándar de SQL Server.
    'database' => env('DB_DATABASE_SIA', 'SIA_DEV'),// Nombre de la base del sistema SIA.
    'username' => env('DB_USERNAME_SIA'),          // Usuario (sin valor por defecto: obligatorio en .env).
    'password' => env('DB_PASSWORD_SIA'),          // Contraseña (obligatoria en .env).
    'charset'  => 'utf8',
    'prefix'   => '',
    'prefix_indexes' => true,

    // --- Ajustes propios de SQL Server 2008 R2 (servidor viejo) ---

    // 'encrypt' => 'no': el servidor es de 2008 y usa TLS antiguo. Los drivers
    // modernos intentan cifrar por defecto y la conexión FALLA. Con 'no' se
    // desactiva ese cifrado moderno para poder conectar.
    'encrypt' => env('DB_ENCRYPT_SIA', 'no'),

    // Acepta el certificado del servidor aunque no esté firmado por una
    // autoridad reconocida (típico en servidores internos antiguos).
    'trust_server_certificate' => env('DB_TRUST_SERVER_CERT_SIA', true),
],
```

**Variables en `.env`:**

```env
# SIA - SQL Server 2008 R2 remoto (encrypt=no obligatorio por TLS antiguo)
DB_HOST_SIA=
DB_PORT_SIA=1433
DB_DATABASE_SIA=SIA_DEV
DB_USERNAME_SIA=
DB_PASSWORD_SIA=
DB_ENCRYPT_SIA=no
DB_TRUST_SERVER_CERT_SIA=true
```

> **Punto clave para explicar:** `encrypt=no` no es un descuido de seguridad, es un
> **requisito**. SQL Server 2008 R2 no soporta el cifrado TLS moderno; sin este
> ajuste la conexión ni siquiera se establece. Como la base es interna y de solo
> lectura, el riesgo es acotado.

---

## 4. Cómo un modelo usa la conexión SIA

La conexión por sí sola no hace nada. Un **modelo** la usa declarando la propiedad
`$connection`. Ejemplo real del sistema:

```php
// app/Models/Sia/Persona.php

class Persona extends Model
{
    // Le dice a Laravel: "este modelo NO usa la base local, usa la conexión 'sia'".
    protected $connection = 'sia';

    protected $table = 'Personas';   // Tabla real en el SQL Server del SIA.
    protected $primaryKey = 'IdPersona';
    protected $keyType = 'string';   // La clave es texto (CI), no un número autoincremental.
    public $incrementing = false;    // La clave no se genera sola.
    public $timestamps = false;      // La tabla legada no tiene created_at / updated_at.
}
```

Cada vez que se consulta `Persona::all()`, Laravel usa automáticamente la conexión
`sia` y va al SQL Server remoto, no a la base local.

**Consultar una conexión concreta a mano** (sin modelo) también es posible:

```php
use Illuminate\Support\Facades\DB;

// Fuerza la consulta contra la conexión 'sia'.
DB::connection('sia')->table('Personas')->count();
```

---

## 5. Por qué el SIA es "solo lectura"

El sistema **nunca** ejecuta `INSERT`, `UPDATE` ni `DELETE` sobre la base `sia`. Es
una decisión de diseño:

- Es un sistema **en producción** de otra área; escribir podría corromper sus datos.
- Los controladores del SIA (`PersonaController`, `MarcacionController`) solo tienen
  el método `index` (listar). No hay `store`, `update` ni `destroy`.
- La tabla `Asistencia` tiene ~4.4 millones de filas, por eso las consultas siempre
  van **acotadas por fecha** (por defecto, el mes actual) para no sobrecargar el
  servidor viejo.

---

## 6. Cómo se prueba sin el servidor real

En los tests no hay acceso al SQL Server remoto. La función `fakeSiaDatabase()`
(en `tests/Pest.php`) **reemplaza** la conexión `sia` por un SQLite en memoria con
las mismas tablas, para probar sin red:

```php
// tests/Pest.php  (resumen)

function fakeSiaDatabase(): void
{
    // Cambia la configuración de la conexión 'sia' en caliente:
    // en vez del SQL Server remoto, usa un SQLite temporal en memoria.
    config()->set('database.connections.sia', [
        'driver'   => 'sqlite',
        'database' => ':memory:',
        'prefix'   => '',
    ]);

    DB::purge('sia'); // Descarta cualquier conexión 'sia' ya abierta.

    // Crea las tablas Personas y Asistencia iguales a las del SIA real.
    Schema::connection('sia')->create('Personas', function ($tabla) { /* ... */ });
    Schema::connection('sia')->create('Asistencia', function ($tabla) { /* ... */ });
}
```

Así los tests corren rápido y sin depender de la red ni del servidor de 2008.

---

## 7. Archivos involucrados

| Archivo | Rol |
|---------|-----|
| `config/database.php` | Define las dos conexiones (`mysql` local y `sia` remota). |
| `.env` | Guarda los datos reales de conexión (host, usuario, contraseña). Nunca se sube a git. |
| `.env.example` | Plantilla con los nombres de las variables, sin valores sensibles. |
| `app/Models/Sia/Persona.php` | Modelo que usa la conexión `sia` (funcionarios). |
| `app/Models/Sia/Asistencia.php` | Modelo que usa la conexión `sia` (marcaciones). |
| `tests/Pest.php` → `fakeSiaDatabase()` | Reemplaza `sia` por SQLite en los tests. |
