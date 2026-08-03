# =============================================================================
# device-service — microservicio de biométricos ZKTeco (FastAPI + pyzk)
#
# Es la única pieza que abre sockets TCP 4370 contra los equipos, así que la
# máquina donde corra tiene que tener ruta de red hasta cada reloj. Las
# conexiones son SALIENTES, de modo que funciona por NAT desde una red bridge
# sin publicar ningún puerto.
#
# Se construye y se corre aparte de la aplicación:
#     docker build -t sismark-devices ./device-service
#     docker run -d --name sismark-devices --network sismark-net #                -e DEVICE_SERVICE_TOKEN=... sismark-devices
#
# NUNCA exponer este servicio a internet.
# =============================================================================
FROM python:3.12-slim-bookworm

ENV TZ=America/La_Paz \
    PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

WORKDIR /srv

COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY main.py ./

# Usuario sin privilegios: el servicio solo necesita abrir sockets salientes.
RUN useradd --system --no-create-home --shell /usr/sbin/nologin sismark \
    && chown -R sismark:sismark /srv
USER sismark

EXPOSE 9001

# `/health` no exige token: sirve de sonda sin repartir el secreto.
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD python -c "import urllib.request,sys; sys.exit(0 if urllib.request.urlopen('http://127.0.0.1:9001/health', timeout=3).status==200 else 1)"

# El host de escucha se deja configurable: con `--network host` conviene
# 127.0.0.1 si Laravel corre en la misma máquina, o la IP interna del servidor
# si Laravel está en otra red de Docker.
CMD ["sh", "-c", "python -m uvicorn main:app --host ${DEVICE_SERVICE_BIND:-0.0.0.0} --port ${DEVICE_SERVICE_PORT:-9001}"]
