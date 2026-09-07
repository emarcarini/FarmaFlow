#!/bin/sh

echo "🚀 Iniciando FarmaFlow..."

# 1. Garantir que o PHP-FPM herde todas as variáveis de ambiente do Docker (clear_env = no)
for conf in /usr/local/etc/php-fpm.d/www.conf /etc/php83/php-fpm.d/www.conf /etc/php82/php-fpm.d/www.conf /etc/php-fpm.d/www.conf; do
    if [ -f "$conf" ]; then
        sed -i 's/;clear_env = no/clear_env = no/' "$conf"
        sed -i 's/clear_env = yes/clear_env = no/' "$conf"
        if ! grep -q "clear_env = no" "$conf"; then
            echo "clear_env = no" >> "$conf"
        fi
    fi
done

# 2. Preparar arquivo .env
if [ ! -f .env ]; then
    echo "📋 Criando .env..."
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        touch .env
    fi
fi

# 3. Garantir APP_KEY válida
if [ -z "$APP_KEY" ]; then
    if ! grep -q "^APP_KEY=base64:" .env; then
        echo "🔑 Gerando chave de segurança da aplicação..."
        RANDOM_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));" 2>/dev/null || head -c 32 /dev/urandom | base64)
        if ! echo "$RANDOM_KEY" | grep -q "^base64:"; then
            RANDOM_KEY="base64:$RANDOM_KEY"
        fi
        APP_KEY="$RANDOM_KEY"
    fi
fi

# 4. Sincronizar todas as variáveis do Docker para o .env (persistência completa para PHP-FPM e CLI)
for var in \
    APP_NAME APP_ENV APP_KEY APP_DEBUG APP_URL \
    DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD \
    CACHE_STORE QUEUE_CONNECTION SESSION_DRIVER REDIS_HOST REDIS_PORT \
    GEMINI_API_KEY GEMINI_MODEL \
    EVOLUTION_API_URL EVOLUTION_PUBLIC_URL EVOLUTION_API_KEY EVOLUTION_INSTANCE \
    EVOLUTION_WEBHOOK_URL EVOLUTION_WEBHOOK_SECRET; do
    
    val=$(eval echo "\$$var")
    if [ -n "$val" ]; then
        if grep -q "^${var}=" .env; then
            sed -i "s|^${var}=.*|${var}=${val}|" .env
        else
            echo "${var}=${val}" >> .env
        fi
    fi
done

# 3. Criar estrutura de pastas do storage
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache/data \
         storage/logs \
         storage/app/public \
         bootstrap/cache

touch storage/logs/laravel.log storage/logs/worker.log

# 4. Permissões de escrita
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 5. Aguardar banco de dados se configurado
if [ "$DB_CONNECTION" = "pgsql" ] || [ "$DB_CONNECTION" = "postgres" ]; then
    echo "⏳ Aguardando banco de dados PostgreSQL em $DB_HOST:$DB_PORT..."
    counter=0
    while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null && [ $counter -lt 30 ]; do
        sleep 1
        counter=$((counter + 1))
    done
    echo "✅ Conexão de rede com PostgreSQL estabelecida!"
elif [ "$DB_CONNECTION" = "mysql" ]; then
    echo "⏳ Aguardando banco de dados MySQL em $DB_HOST:$DB_PORT..."
    counter=0
    while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null && [ $counter -lt 30 ]; do
        sleep 1
        counter=$((counter + 1))
    done
    echo "✅ Conexão com banco de dados estabelecida!"
fi

# 6. Link simbólico
php artisan storage:link 2>/dev/null || true

# 7. Migrações e Seeds (seguras e sem travar container)
echo "📦 Verificando migrações do banco de dados..."
php artisan migrate --force --graceful || true

echo "🌱 Sincronizando dados iniciais..."
php artisan db:seed --force || true

# 8. Otimização de caches e rotas
echo "⚡ Atualizando rotas e configurações..."
php artisan optimize:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan config:cache 2>/dev/null || true

echo "🎉 FarmaFlow pronto para atender!"

# 9. Iniciar serviços (Nginx + PHP-FPM + Supervisor)
exec "$@"
