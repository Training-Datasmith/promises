FROM php:7.2-cli@sha256:5249d94b4b00db63a0ed53e6528114926110ab5dcb0d0f07cc30d1ea7dbe747e

RUN sed -i 's|deb.debian.org|archive.debian.org|g' /etc/apt/sources.list \
    && sed -i 's|security.debian.org|archive.debian.org|g' /etc/apt/sources.list \
    && sed -i '/buster-updates/d' /etc/apt/sources.list \
    && printf 'Acquire::Check-Valid-Until "false";\n' > /etc/apt/apt.conf.d/99no-check-valid \
    && apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        ca-certificates \
        git \
        unzip \
        libonig-dev \
    && docker-php-ext-install -j"$(nproc)" mbstring \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
