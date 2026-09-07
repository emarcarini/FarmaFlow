#!/bin/sh
set -e

echo "🚀 Iniciando FarmaFlow..."

# Criar .env se não existir
if [ ! -f .env ]; then
    echo "📋 Criando .env a partir do .env.example..."
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        touch .env
    fi
fi

# Garantir que a linha APP_KEY exista no .env
if ! grep -q "^APP_KEY=" .env; then
    echo "APP_KEY=" >> .env
fi

# Se APP_KEY foi passada via variável de ambiente, atualizar no .env
if [ -n "$APP_KEY" ]; then
    echo "🔑 Usando APP_KEY configurada no ambiente..."
    sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
elif ! grep -q "^APP_KEY=base64:" .env; then
    echo "🔑 Gerando chave de segurança da aplicação..."
    php artisan key:generate --force || true
fi

# Criar e ajustar estrutura do storage
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache/data \
         storage/logs \
         storage/app/public \
         bootstrap/cache

touch storage/logs/laravel.log storage/logs/worker.log

# Ajustar permissões de escrita
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Aguardar MySQL se estiver configurado
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "⏳ Aguardando banco de dados MySQL ficar pronto em $DB_HOST:$DB_PORT..."
    max_tries=30
    counter=0
    until nc -z "$DB_HOST" "$DB_PORT" || [ $counter -gt $max_tries ]; do
        echo "Aguardando conexão com MySQL... ($counter/$max_tries)"
        sleep 2
        counter=$((counter + 1))
    done
    echo "✅ MySQL conectado!"
fi

# Criar link simbólico do storage
php artisan storage:link || true

# Executar migrações e seeders
echo "📦 Executando migrações do banco de dados..."
php artisan migrate --force --graceful || true

echo "🌱 Garantindo dados iniciais com Seeders..."
php artisan db:seed --force || true

# Limpar e otimizar caches para produção
echo "⚡ Otimizando rotas e configurações..."
php artisan optimize:clear || true
php artisan optimize || true

echo "🎉 FarmaFlow pronto para atender!"

# Executar comando principal (Supervisor / PHP-FPM + Nginx)
exec "$@"
