#!/bin/sh
set -e

echo "🚀 Iniciando FarmaFlow..."

# Criar .env se não existir
if [ ! -f .env ]; then
    echo "📋 Criando .env a partir do .env.example..."
    cp .env.example .env
fi

# Gerar chave da aplicação se estiver vazia
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "🔑 Gerando chave de segurança da aplicação..."
    php artisan key:generate --force
fi

# Ajustar permissões de escrita
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Aguardar MySQL se estiver configurado
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "⏳ Aguardando banco de dados MySQL ficar pronto em $DB_HOST:$DB_PORT..."
    until nc -z -v -w30 "$DB_HOST" "$DB_PORT"; do
        echo "Aguardando conexão com MySQL..."
        sleep 2
    done
    echo "✅ MySQL conectado!"
fi

# Executar migrações e seeders
echo "📦 Executando migrações do banco de dados..."
php artisan migrate --force --graceful

echo "🌱 Garantindo dados iniciais com Seeders..."
php artisan db:seed --force

# Otimizar caches para produção
echo "⚡ Otimizando rotas e configurações..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "🎉 FarmaFlow pronto para atender!"

# Executar comando principal (Supervisor / PHP-FPM + Nginx)
exec "$@"
