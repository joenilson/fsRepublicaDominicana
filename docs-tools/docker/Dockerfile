# Instalación limpia de FacturaScripts para generar las capturas de la documentación.
# No contiene datos reales: la base de datos se crea vacía en un contenedor aparte.
ARG FS_VERSION=v2026.65

FROM php:8.3-apache AS base
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip nodejs npm libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libxml2-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip intl mysqli pdo_mysql pgsql pdo_pgsql bcmath mbstring xml \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN { echo 'memory_limit=512M'; echo 'upload_max_filesize=64M'; echo 'post_max_size=64M'; \
      echo 'max_execution_time=300'; echo 'date.timezone=America/Santo_Domingo'; } > /usr/local/etc/php/conf.d/fs.ini
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

FROM base
ARG FS_VERSION
WORKDIR /var/www/html
RUN git clone --depth 1 --branch ${FS_VERSION} https://github.com/NeoRazorX/facturascripts.git . \
    && composer install --no-dev --no-interaction --prefer-dist \
    && npm install --omit=dev --no-audit --no-fund \
    && rm -rf .git \
    && cp htaccess-sample .htaccess \
    && mkdir -p MyFiles Plugins \
    && chown -R www-data:www-data /var/www/html
