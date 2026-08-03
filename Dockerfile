# =============================================================================
# SisMark — imagen de producción (Laravel 13 + PHP 8.3 sobre NGINX Unit)
#
# Todo en un solo Dockerfile: no hace falta docker compose. La imagen levanta
# la aplicación completa en un único proceso (`unitd`), que sirve los estáticos
# de `public/` y ejecuta PHP sin necesidad de nginx + php-fpm + supervisor.
#
# NO incluye el driver de SQL Server. La conexión `sia` solo se usa en local,
# para correr los comandos `sia:migrar-*` que copian el SIA a MySQL; en el
# servidor la base ya viene migrada.
#
# La base MySQL es un recurso aparte (en Coolify: «+ New» → «Database» → MySQL)
# y se apunta con las variables de entorno. Ver .env.docker.example.
#
# El microservicio de biométricos tiene su propio Dockerfile, en
# `device-service/`, y se despliega como un segundo recurso.
#
#   docker build -t sismark .
#   docker run -d --env-file .env.docker -p 8000:8000 sismark
# =============================================================================


# -----------------------------------------------------------------------------
# Etapa 1 — Assets de Vite (Tailwind 4)
#
# El layout del sistema trae su CSS embebido y no depende de Vite: el único
# archivo que usa @vite es welcome.blade.php, que hoy no tiene ruta. Se compila
# igual para que el manifiesto exista si algún día se usa.
#
# Si el servidor de construcción no tiene salida a internet, esta etapa falla
# al descargar las tipografías de bunny.net (plugin `fonts` de vite.config.js).
# En ese caso, comentar esta etapa y el COPY que la usa más abajo.
# -----------------------------------------------------------------------------
FROM node:22-bookworm-slim AS assets

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build


# -----------------------------------------------------------------------------
# Etapa 2 — Aplicación
# -----------------------------------------------------------------------------
FROM unit:1.34.2-php8.3

# Hora de Bolivia: los relojes biométricos guardan en hora local y el
# procesador de asistencia compara marcas contra horarios sin zona horaria.
ENV TZ=America/La_Paz

# --- Librerías del sistema y extensiones de PHP ------------------------------
# gd    → simple-qrcode (los QR de los reportes) y phpspreadsheet
# zip   → phpspreadsheet (el .xlsx es un zip)
# intl  → fechas y números en español
# pcntl → señales, para los comandos largos de migración
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        unzip \
        git \
        ca-certificates \
        default-mysql-client \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        intl \
        zip \
        gd \
        bcmath \
        exif \
        pcntl \
        opcache \
    && rm -rf /var/lib/apt/lists/*

# --- Ajustes de PHP ----------------------------------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-sismark.ini

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# --- Dependencias de Composer ------------------------------------------------
# Primero solo los manifiestos: si no cambian, Docker reutiliza esta capa y no
# vuelve a bajar nada.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# --- Código de la aplicación -------------------------------------------------
COPY . .
COPY --from=assets /build/public/build ./public/build

# `--optimize` sin `--classmap-authoritative`: blade-ui-kit/blade-heroicons
# resuelve componentes por nombre en tiempo de ejecución.
RUN composer dump-autoload --no-dev --optimize

# `public/hot` es del servidor de desarrollo de Vite: si viaja a la imagen,
# Laravel cree que hay un dev-server escuchando y pide los assets a un puerto
# que no existe. Se borra por si el .dockerignore no lo atrapó.
RUN rm -f public/hot public/fonts-manifest.dev.json

# --- Permisos ----------------------------------------------------------------
# Unit corre los procesos de PHP como el usuario `unit` (uid 999), así que es
# quien tiene que poder escribir en storage y en la caché de Blade.
RUN mkdir -p storage/framework/cache/data storage/framework/sessions \
             storage/framework/views storage/logs storage/app/public bootstrap/cache \
    && chown -R unit:unit storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Enlace de las fotos de perfil. Se crea acá y no en el arranque porque
# `public/` es de root y el proceso de PHP no puede escribir ahí.
RUN ln -sf /var/www/html/storage/app/public /var/www/html/public/storage

# --- Configuración de Unit ---------------------------------------------------
# El entrypoint de la imagen aplica los .json de este directorio en el primer
# arranque. Como Unit valida el esquema y rechaza cualquier clave que no
# conozca —incluidas las de comentario tipo "//"—, el archivo va sin
# anotaciones y lo que hace se explica acá:
#
#   listeners."*:8000".forwarded  El proxy de adelante (Traefik en Coolify)
#       termina el HTTPS. Sin esto Unit ve la petición como http, Laravel arma
#       las URLs en http y el navegador bloquea los AJAX de los listados por
#       contenido mixto. Se confía solo en rangos privados, nunca en internet.
#   routes.sismark  Lo que exista como archivo en public/ lo sirve Unit
#       directo; el resto cae en index.php. La excepción de /index.php impide
#       pedir el front controller como archivo estático.
#   applications.sismark.processes  Arranca con 2 procesos y crece hasta 16:
#       un reporte procesado o la lectura en vivo de un equipo pueden tardar, y
#       no conviene que una consulta lenta deje la aplicación sin procesos.
COPY docker/unit.json /docker-entrypoint.d/unit.json

# --- Arranque ----------------------------------------------------------------
COPY docker/entrypoint.sh /usr/local/bin/sismark-entrypoint
# El repositorio se edita en Windows: con finales de línea CRLF el shell falla
# con "exec format error".
RUN sed -i 's/\r$//' /usr/local/bin/sismark-entrypoint \
    && chmod +x /usr/local/bin/sismark-entrypoint

EXPOSE 8000

# `/up` es la ruta de salud que registra bootstrap/app.php.
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8000/up || exit 1

# El entrypoint propio prepara la base y las cachés, y después le cede el
# control al entrypoint de Unit, que es quien aplica /docker-entrypoint.d/.
ENTRYPOINT ["sismark-entrypoint"]
CMD ["unitd", "--no-daemon", "--control", "unix:/var/run/control.unit.sock"]
