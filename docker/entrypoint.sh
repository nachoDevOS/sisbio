#!/bin/sh
# =============================================================================
# Arranque del contenedor de SisMark.
#
# Deja la aplicación lista y después le cede el control al entrypoint de NGINX
# Unit, que es quien aplica la configuración de /docker-entrypoint.d/ y levanta
# `unitd`.
# =============================================================================
set -e

cd /var/www/html

# --- 0. ¿Arranque del servidor o comando suelto? -----------------------------
# Solo la secuencia completa cuando se levanta el servicio de verdad. Para
# cualquier otro comando (`php artisan …`, `sh`, `composer`) se ejecuta y
# listo: si no, `docker run --rm sismark php artisan key:generate --show`
# —el comando que genera la APP_KEY— moriría por no tener APP_KEY.
case "$1" in
    unitd|unitd-debug) ;;
    *)
        exec "$@"
        ;;
esac

echo "[sismark] Arrancando…"

# --- 1. Configuración mínima -------------------------------------------------
if [ -z "${APP_KEY}" ]; then
    echo "[sismark] ERROR: APP_KEY está vacía."
    echo "[sismark] Generar una con:  docker run --rm sismark php artisan key:generate --show"
    echo "[sismark] y ponerla en las variables de entorno del contenedor."
    exit 1
fi

if [ -z "${DB_HOST}" ] || [ -z "${DB_DATABASE}" ]; then
    echo "[sismark] ERROR: faltan DB_HOST o DB_DATABASE."
    exit 1
fi

# --- 2. Esperar a MySQL ------------------------------------------------------
# La base es un recurso aparte y puede tardar en aceptar conexiones. Sin esta
# espera, la primera migración muere y la aplicación queda contra una base que
# todavía no está.
#
# `127.0.0.1` y `localhost` son el error más común al desplegar: dentro del
# contenedor apuntan al contenedor mismo, no a la base. Se avisa de entrada en
# vez de dejar que el arranque se cuelgue dos minutos y el orquestador lo dé
# por muerto sin decir por qué.
case "${DB_HOST}" in
    127.0.0.1|localhost|::1)
        echo "[sismark] ERROR: DB_HOST=${DB_HOST} apunta al propio contenedor, no a la base."
        echo "[sismark] Usar el nombre del servicio o del recurso de la base"
        echo "[sismark] (p. ej. 'mysql'), o 'host.docker.internal' si la base"
        echo "[sismark] corre en la máquina que hospeda a Docker."
        exit 1
        ;;
esac

espera_maxima=90
echo "[sismark] Esperando a MySQL en ${DB_HOST}:${DB_PORT:-3306} (hasta ${espera_maxima}s)…"
intentos=0
until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT:-3306}" --silent 2>/dev/null; do
    intentos=$((intentos + 1))
    if [ "$((intentos * 3))" -ge "${espera_maxima}" ]; then
        echo "[sismark] ERROR: MySQL no respondió en ${espera_maxima}s."
        echo "[sismark] Revisar que DB_HOST sea alcanzable desde este contenedor,"
        echo "[sismark] que la base esté en la misma red, y que el usuario"
        echo "[sismark] '${DB_USERNAME:-(sin DB_USERNAME)}' pueda conectarse."
        # El motivo real del último intento, que hasta acá se descartaba.
        mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT:-3306}" 2>&1 | sed 's/^/[sismark]   /'
        exit 1
    fi
    # Una señal de vida cada 15 s: en un log de despliegue, el silencio no
    # distingue «todavía arrancando» de «colgado».
    if [ "$((intentos % 5))" -eq 0 ]; then
        echo "[sismark] …sigue sin responder ($((intentos * 3))s)"
    fi
    sleep 3
done
echo "[sismark] MySQL responde."

# --- 3. Permisos -------------------------------------------------------------
# `storage` suele venir de un volumen: el dueño lo pone Docker, no la imagen.
chown -R unit:unit storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# --- 4. Migraciones ----------------------------------------------------------
if [ "${SISMARK_SKIP_MIGRATIONS}" = "true" ]; then
    echo "[sismark] Migraciones salteadas (SISMARK_SKIP_MIGRATIONS=true)."
else
    echo "[sismark] Corriendo migraciones…"
    php artisan migrate --force
fi

# --- 5. Semillas (solo la primera vez, si se pide) --------------------------
# Crea los permisos y el rol super_admin. Es idempotente, pero se deja detrás
# de una variable para no correrlo en cada reinicio.
if [ "${SISMARK_SEED}" = "true" ]; then
    echo "[sismark] Sembrando roles y permisos…"
    php artisan db:seed --class=RolesAndPermissionsSeeder --force
fi

# --- 6. Cachés de producción -------------------------------------------------
# Se regeneran en cada arranque: las variables de entorno pueden haber cambiado.
echo "[sismark] Cacheando configuración, rutas y vistas…"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Las cachés se escribieron como root: se devuelven al usuario de Unit.
chown -R unit:unit storage bootstrap/cache

echo "[sismark] Listo. Cediendo el control a NGINX Unit."

exec /usr/local/bin/docker-entrypoint.sh "$@"
