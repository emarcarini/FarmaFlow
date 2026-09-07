# Variáveis de Ambiente — referência

Não commitar segredos.

Exemplos de grupos:

```env
APP_NAME="Assistente Comercial Inteligente"
APP_URL=http://localhost:8000
APP_ENV=local
APP_KEY=

DB_CONNECTION=sqlite
# Para MySQL/MariaDB:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=assistente_comercial
# DB_USERNAME=root
# DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# IA Oficial — Google Gemini API
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash

# WhatsApp — Evolution API
EVOLUTION_API_URL=http://localhost:8080
EVOLUTION_API_KEY=
EVOLUTION_INSTANCE=comercial
EVOLUTION_WEBHOOK_SECRET=

QUEUE_CONNECTION=sync
```
