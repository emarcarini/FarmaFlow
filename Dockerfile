FROM php:8.3-fpm-alpine

# Instalar dependências de sistema, Nginx e Supervisor
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    netcat-openbsd \
    curl \
    git \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    bash

# Configurar e instalar extensões PHP essenciais
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        bcmath \
        mbstring \
        zip \
        intl \
        opcache \
        pcntl \
        exif \
        gd

# Instalar Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto
COPY . .

# Instalar dependências PHP sem pacotes de desenvolvimento
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configurações do Nginx e Supervisor
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Permissões do entrypoint e pastas de escrita
RUN chmod +x docker/entrypoint.sh \
    && mkdir -p /var/log/supervisor /run/nginx storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
