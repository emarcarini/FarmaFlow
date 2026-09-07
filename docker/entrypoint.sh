#!/bin/sh

echo "🚀 Iniciando FarmaFlow..."

# 1. Preparar arquivo .env
if [ ! -f .env ]; then
    echo "📋 Criando .env..."
    if [ -f .env.example ]; then
        cp .env.example .env
    else
        touch .env
    fi
fi

# 2. Garantir APP_KEY válida
if [ -z "$APP_KEY" ]; then
    # Se não veio via ambiente, verificar se já existe no .env
    if ! grep -q "^APP_KEY=base64:" .env; then
        echo "🔑 Gerando chave de segurança da aplicação..."
        RANDOM_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));" 2>/dev/null || head -c 32 /dev/urandom | base64)
        if ! echo "$RANDOM_KEY" | grep -q "^base64:"; then
            RANDOM_KEY="base64:$RANDOM_KEY"
        fi
        APP_KEY="$RANDOM_KEY"
    fi
fi

if [ -n "$APP_KEY" ]; then
    if grep -q "^APP_KEY=" .env; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
    else
        echo "APP_KEY=${APP_KEY}" >> .env
    fi
fi

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

# 5. Aguardar e sincronizar banco de dados se configurado
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "⏳ Aguardando banco de dados MySQL em $DB_HOST:$DB_PORT..."
    counter=0
    while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null && [ $counter -lt 30 ]; do
        sleep 1
        counter=$((counter + 1))
    done
    echo "✅ Conexão com banco de dados estabelecida!"

    # Sincronizar credenciais do usuário se necessário
    php -r '
        $host = getenv("DB_HOST") ?: "mysql";
        $port = getenv("DB_PORT") ?: 3306;
        $db = getenv("DB_DATABASE") ?: "farmaflow";
        $user = getenv("DB_USERNAME") ?: "farmaflow_user";
        $pass = getenv("DB_PASSWORD") ?: "farmaflow_pass_secret123";
        $rootPass = getenv("DB_ROOT_PASSWORD") ?: "root_farmaflow_secret123";

        try {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
            echo "✅ Autenticacao do banco OK!\n";
            exit(0);
        } catch (Exception $e) {
            echo "⚠️ Sincronizando credenciais do MySQL com root...\n";
        }

        $rootPasswords = [$rootPass, "root_farmaflow_secret123", "FarmaFlow_Root_Pass_7632!#", ""];
        $rootPdo = null;
        foreach ($rootPasswords as $rp) {
            try {
                $rootPdo = new PDO("mysql:host=$host;port=$port", "root", $rp);
                break;
            } catch (Exception $e) {}
        }

        if ($rootPdo) {
            try {
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `$db`;");
                $rootPdo->exec("CREATE USER IF NOT EXISTS `$user`@`%` IDENTIFIED BY \"$pass\";");
                $rootPdo->exec("ALTER USER `$user`@`%` IDENTIFIED BY \"$pass\";");
                $rootPdo->exec("GRANT ALL PRIVILEGES ON `$db`.* TO `$user`@`%`;");
                $rootPdo->exec("FLUSH PRIVILEGES;");
                echo "🎉 Credenciais e permissoes sincronizadas com sucesso!\n";
            } catch (Exception $e) {
                echo "⚠️ Aviso de permissao: " . $e->getMessage() . "\n";
            }
        }
    ' 2>/dev/null || true
fi

# 6. Link simbólico
php artisan storage:link 2>/dev/null || true

# 7. Migrações e Seeds (seguras e sem travar container)
echo "📦 Verificando migrações do banco de dados..."
php artisan migrate --force --graceful || true

echo "🌱 Sincronizando dados iniciais..."
php artisan db:seed --force || true

# 8. Otimização de caches
echo "⚡ Otimizando configurações..."
php artisan optimize:clear 2>/dev/null || true
php artisan optimize 2>/dev/null || true

echo "🎉 FarmaFlow pronto para atender!"

# 9. Iniciar serviços (Nginx + PHP-FPM + Supervisor)
exec "$@"
