# SISBIO · device-service

Microservicio en Python (FastAPI + pyzk) que habla el protocolo ZKTeco con los
equipos biométricos por TCP 4370. Es la **única** pieza del sistema que abre
sockets a los equipos; Laravel se comunica con él por HTTP.

```
Equipos ZKTeco  <--TCP 4370-->  device-service (este)  <--REST + X-Auth-Token-->  Laravel
```

## Seguridad

- **Nunca** exponer a internet. Escuchar solo en localhost / red interna.
- Toda petición (menos `/health`) exige el encabezado `X-Auth-Token`, que debe
  coincidir con `DEVICE_SERVICE_TOKEN`. Ese mismo token vive en el `.env` de
  Laravel para que ambos lados coincidan.

## Instalación

Requiere Python 3.10+.

**Linux/macOS:**

```bash
cd device-service
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

Si `python3 -m venv` falla porque falta el paquete del sistema (Debian/Ubuntu),
instalalo con `sudo apt install python3-venv`, o instalá las dependencias al
usuario sin entorno virtual:

```bash
pip3 install --user -r requirements.txt
```

**Windows:** el comando es `python`, no `python3` (el alias `python3.exe` de
Windows solo abre la Microsoft Store). En PowerShell:

```powershell
cd device-service
python -m venv .venv
.venv\Scripts\Activate.ps1
pip install -r requirements.txt
```

Sin entorno virtual (más simple para desarrollo local):

```powershell
pip install --user -r requirements.txt
```

## Configuración

Copiar el ejemplo y pegar el mismo token que Laravel:

```bash
cp .env.example .env
# editar .env y poner DEVICE_SERVICE_TOKEN = el mismo valor del .env de Laravel
```

## Levantar el servicio

El microservicio lee el `.env` solo (no hace falta exportar las variables a
mano en la terminal). Arrancá uvicorn en el puerto 9001 (el que espera Laravel
por defecto):

**Linux/macOS:**

```bash
python3 -m uvicorn main:app --host 127.0.0.1 --port 9001
```

**Windows** (PowerShell o cmd, comando `python` sin el `3`):

```powershell
python -m uvicorn main:app --host 127.0.0.1 --port 9001
```

En desarrollo podés añadir `--reload` para recarga automática.

## Endpoints

| Método | Ruta                | Token | Descripción                                              |
|--------|---------------------|-------|----------------------------------------------------------|
| GET    | `/health`           | No    | Chequeo de vida.                                         |
| GET    | `/device/info`      | Sí    | Info del equipo: nombre, serial, plataforma, firmware y firma de algoritmo. |
| GET    | `/device/users`     | Sí    | Lista de usuarios registrados en el equipo.              |
| GET    | `/device/attendance`| Sí    | Marcaciones guardadas en el equipo (más recientes primero), con el nombre resuelto contra `/device/users`. |

Parámetros de `/device/info`, `/device/users` y `/device/attendance`:

- `ip` (obligatorio) — IP del equipo en la LAN.
- `port` (opcional, por defecto `4370`) — puerto TCP.
- `password` (opcional, por defecto `0`) — COMM key del equipo.

### Ejemplos

```bash
# Health (sin token)
curl http://127.0.0.1:9001/health

# Info de un equipo (con token)
curl -H "X-Auth-Token: TU_TOKEN" \
  "http://127.0.0.1:9001/device/info?ip=192.168.1.201&port=4370&password=0"
```

Si el equipo no responde, el servicio devuelve **503** con un mensaje claro en
`detail`, sin tumbar el proceso. La conexión siempre se reactiva y se cierra,
incluso ante error.
