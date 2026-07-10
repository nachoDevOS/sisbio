# SISBIO · device-service

Microservicio en Python (FastAPI + pyzk) que habla el protocolo ZKTeco con los
equipos biométricos por TCP 4370. Es la **única** pieza del sistema que abre
sockets a los equipos; el panel de Laravel/Filament se comunica con él por HTTP.

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

## Configuración

Copiar el ejemplo y pegar el mismo token que Laravel:

```bash
cp .env.example .env
# editar .env y poner DEVICE_SERVICE_TOKEN = el mismo valor del .env de Laravel
```

## Levantar el servicio

El microservicio lee el token desde las variables de entorno. Cargá el `.env` y
arrancá uvicorn en el puerto 9001 (el que espera Laravel por defecto):

```bash
set -a && source .env && set +a
python3 -m uvicorn main:app --host 127.0.0.1 --port 9001
```

En desarrollo podés añadir `--reload` para recarga automática.

## Endpoints

| Método | Ruta             | Token | Descripción                                              |
|--------|------------------|-------|----------------------------------------------------------|
| GET    | `/health`        | No    | Chequeo de vida.                                         |
| GET    | `/device/info`   | Sí    | Info del equipo: nombre, serial, plataforma, firmware y firma de algoritmo. |
| GET    | `/device/users`  | Sí    | Lista de usuarios registrados en el equipo.             |

Parámetros de `/device/info` y `/device/users`:

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
