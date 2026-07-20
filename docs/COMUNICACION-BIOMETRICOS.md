# Comunicación con los equipos biométricos — SISBIO

Este documento explica **cómo el sistema se comunica con los relojes biométricos
ZKTeco**, con el código comentado. Sirve como material de presentación.

> **Estado actual (importante):** el sistema hoy **lee** información de los equipos
> (estado en línea y marcaciones). La **replicación de huellas** entre el equipo
> maestro y los demás **aún no está implementada** — está descrita como trabajo
> pendiente en la sección 6.

---

## 1. La idea clave: Laravel NO habla directo con los relojes

Los relojes ZKTeco hablan un **protocolo TCP propio** (puerto 4370). PHP/Laravel no
sabe hablar ese protocolo. Por eso hay una pieza intermedia:

```
┌─────────────┐   HTTP + token   ┌────────────────────────┐   TCP 4370   ┌──────────┐
│   SISBIO    │ ───────────────► │  Microservicio Python  │ ───────────► │  Reloj   │
│  (Laravel)  │   (JSON)         │     (FastAPI)          │  protocolo   │ ZKTeco   │
│             │ ◄─────────────── │  habla ZKTeco por TCP  │ ◄─────────── │ (LAN)    │
└─────────────┘   respuesta JSON └────────────────────────┘              └──────────┘
```

- **Laravel** solo hace peticiones **HTTP** (fáciles) a un microservicio.
- El **microservicio Python (FastAPI)** es el único que habla el protocolo ZKTeco
  por TCP con el equipo físico.
- Van autenticados con un **token compartido** (`X-Auth-Token`) para que nadie más
  pueda mandar órdenes al microservicio.

Ventaja: si el protocolo del reloj cambia, solo se toca el microservicio Python;
Laravel sigue igual.

---

## 2. Configuración de la conexión al microservicio

Los datos del microservicio (URL y token) se leen del `.env`, nunca van en el código:

```php
// config/services.php

'device_service' => [
    'url'   => env('DEVICE_SERVICE_URL', 'http://127.0.0.1:9001'), // Dónde vive el microservicio.
    'token' => env('DEVICE_SERVICE_TOKEN'),                        // Token compartido secreto.
],
```

```env
# .env
DEVICE_SERVICE_URL=http://127.0.0.1:9001
DEVICE_SERVICE_TOKEN=un-token-secreto
```

---

## 3. El cliente: `DeviceService`

Toda la comunicación pasa por **una sola clase**: `App\Services\DeviceService`. Es el
único punto del sistema que llama al microservicio. Traduce cada operación a una
petición HTTP autenticada y convierte los errores en mensajes claros para el usuario.

```php
// app/Services/DeviceService.php

class DeviceService
{
    private readonly string $baseUrl; // URL del microservicio (sin barra final).
    private readonly ?string $token;  // Token compartido para autenticar.

    public function __construct()
    {
        // Lee la configuración una sola vez al crear el servicio.
        $this->baseUrl = rtrim((string) config('services.device_service.url'), '/');
        $this->token   = config('services.device_service.token');
    }

    // Pide al equipo su identificación (nombre, firmware, algoritmo de huella).
    public function info(Equipo $equipo): array
    {
        return $this->get('/device/info', $equipo);
    }

    // Pide la lista de usuarios registrados en el equipo.
    public function users(Equipo $equipo): array
    {
        return $this->get('/device/users', $equipo);
    }

    // Pide las marcaciones (asistencia) guardadas en el equipo.
    public function attendance(Equipo $equipo): array
    {
        return $this->get('/device/attendance', $equipo);
    }

    /**
     * Ejecuta un GET autenticado contra el microservicio para un equipo dado.
     * Aquí se centraliza el manejo de errores de red y del equipo.
     */
    private function get(string $path, Equipo $equipo): array
    {
        try {
            $response = Http::withHeaders(['X-Auth-Token' => $this->token]) // Autenticación.
                ->connectTimeout(5) // Si el microservicio no responde en 5s, corta.
                ->timeout(20)       // Margen para que el equipo conteste por TCP.
                ->get($this->baseUrl.$path, [
                    'ip'       => $equipo->ip,       // A qué reloj conectarse...
                    'port'     => $equipo->puerto,   // ...en qué puerto...
                    'password' => $equipo->comm_key, // ...con qué clave de comunicación.
                ]);
        } catch (ConnectionException $e) {
            // Ni siquiera se pudo contactar al microservicio (apagado / URL mal puesta).
            throw new DeviceServiceException(
                'No se pudo contactar al microservicio de dispositivos. Verifica que esté encendido.',
                previous: $e,
            );
        }

        if ($response->failed()) {
            // El microservicio respondió con error (ej. 503 equipo caído, 401 token malo).
            $detalle = $response->json('detail') ?? 'El microservicio devolvió un error inesperado.';
            throw new DeviceServiceException($detalle);
        }

        return $response->json(); // Todo bien: devuelve la respuesta como arreglo.
    }
}
```

**Puntos para explicar en la presentación:**
- Un solo lugar (`DeviceService`) concentra la comunicación → fácil de mantener.
- Dos timeouts distintos: uno para el microservicio, otro para el reloj.
- Los errores se convierten en `DeviceServiceException` con mensaje en español, que
  el panel muestra como notificación.

---

## 4. Operación "Probar conexión" (lo que se ve en la demo)

En la tabla de equipos, el botón **"Probar conexión"** usa `DeviceService::info()`
para verificar que el reloj responde y guardar su estado.

```php
// app/Http/Controllers/EquipoController.php  (método probarConexion)

public function probarConexion(Equipo $equipo, DeviceService $deviceService): RedirectResponse
{
    try {
        $info = $deviceService->info($equipo); // Llama al reloj vía microservicio.

        // Éxito: guarda que está en línea, el algoritmo detectado y la hora.
        $equipo->update([
            'en_linea' => true,
            'algoritmo' => $info['algoritmo'] ?? $equipo->algoritmo,
            'ultima_sync' => now(),
        ]);

        return back()->with('estado', "Conectado a «{$equipo->nombre}»...");
    } catch (DeviceServiceException $e) {
        // Falla: marca el equipo fuera de línea y muestra el motivo.
        $equipo->update(['en_linea' => false]);

        return back()->with('error', "No se pudo conectar: {$e->getMessage()}");
    }
}
```

Flujo completo:

```
Usuario clic "Probar conexión"
      │
      ▼
DeviceService::info($equipo)
      │  HTTP GET /device/info  (ip, port, password)
      ▼
Microservicio Python  → habla TCP con el reloj
      │
      ├─ responde OK  → equipo.en_linea = true,  guarda algoritmo y ultima_sync
      └─ responde error → equipo.en_linea = false, muestra el motivo
```

---

## 5. Operación "Ver marcaciones"

El botón **"Ver marcaciones"** usa `DeviceService::attendance()` para leer en vivo las
marcaciones guardadas en el equipo y mostrarlas en una página propia.

```php
// app/Http/Controllers/EquipoController.php  (método marcaciones, resumen)

public function marcaciones(Equipo $equipo, DeviceService $deviceService): View
{
    try {
        $respuesta = $deviceService->attendance($equipo); // Lee del reloj.
        $marcaciones = $respuesta['marcaciones'] ?? [];
        $error = null;
    } catch (DeviceServiceException $e) {
        // Si el equipo no responde, la página muestra el motivo en vez de la lista.
        $marcaciones = [];
        $error = $e->getMessage();
    }

    return view('equipos.marcaciones', compact('equipo', 'marcaciones', 'error'));
}
```

---

## 6. Sincronización de huellas (PENDIENTE — trabajo futuro)

El objetivo final del sistema es **replicar las huellas** del equipo **maestro**
(`es_master = true`) hacia el resto de equipos activos, para que un funcionario
registrado en un reloj pueda marcar en todos.

**Estado:** diseñado en la base de datos, **no implementado** aún.

Lo que ya está preparado:
- Campo `es_master` en cada equipo → marca cuál es el origen de las huellas.
- Campo `activo` → indica qué equipos participarían en la sincronización.
- Campo `ultima_sync` → registraría la última vez que se replicó.

Lo que falta:
1. **Endpoints de escritura en el microservicio Python** (hoy solo tiene lectura):
   por ejemplo `GET /device/templates` (leer huellas del maestro) y
   `POST /device/templates` (escribirlas en otro equipo).
2. **Un método en `DeviceService`** para leer y escribir huellas.
3. **Un disparador**: un comando artisan (`php artisan equipos:sincronizar`) o un
   botón "Sincronizar ahora" en el panel, que:
   - lea las huellas del equipo maestro,
   - las escriba en cada equipo `activo`,
   - actualice `ultima_sync` en cada uno.

Diseño previsto:

```
php artisan equipos:sincronizar
      │
      ▼
DeviceService: leer huellas del MAESTRO (es_master = true)
      │
      ▼
Para cada equipo activo (activo = true, es_master = false):
      │  escribir esas huellas en el equipo
      ▼
Actualizar ultima_sync de cada equipo replicado
```

> En la presentación conviene mostrar esto como **la siguiente fase**: la base de
> datos y la arquitectura ya están listas para soportarlo; falta la lógica de
> replicación y los endpoints de escritura en el microservicio.

---

## 7. Archivos involucrados

| Archivo | Rol |
|---------|-----|
| `config/services.php` | Guarda la URL y el token del microservicio. |
| `app/Services/DeviceService.php` | Único cliente que habla con el microservicio (info, users, attendance). |
| `app/Exceptions/DeviceServiceException.php` | Error con mensaje en español para el usuario. |
| `app/Http/Controllers/EquipoController.php` | Acciones "Probar conexión" y "Ver marcaciones". |
| `resources/views/equipos/_marcaciones_lista.blade.php` | Parcial que muestra las marcaciones leídas del equipo. |
| *(pendiente)* microservicio Python | Habla el protocolo ZKTeco por TCP con los relojes. |
