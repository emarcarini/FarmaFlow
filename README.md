# Assistente Comercial Inteligente

Plataforma comercial centrada no WhatsApp, com CRM completo e um agente de IA alimentado por **Google Gemini** que atende clientes, consulta dados comerciais, conduz cotações, aplica campanhas e funciona como assistente do representante.

---

## 🎯 Objetivo
Construir uma plataforma comercial com:
- Atendimento via WhatsApp (Evolution API).
- CRM de empresas, contatos, score RFM e timeline unificada.
- Motor determinístico de precificação por quantidade e campanhas promocionais.
- Trava comercial de fechamento de pedidos (Order Lock).
- Agente inteligente de vendas alimentado por **Google Gemini API** (com Function Calling nativo e Firewall Clínico).
- Central de Documentação do Representante integrada na rota protegida `/docs`.

---

## 🛠️ Stack Tecnológica
- **Backend:** Laravel 12 (PHP 8.3+)
- **Banco de Dados:** MySQL / MariaDB (ou SQLite em desenvolvimento/testes)
- **Filas & Cache:** Redis
- **WhatsApp Engine:** Evolution API v2
- **Inteligência Artificial:** Google Gemini API (`gemini-2.0-flash` / `gemini-1.5-flash`)
- **Painel:** Blade + Tailwind CSS + Lucide Icons + Livewire

---

## 🚀 Como Executar Localmente

### 1. Requisitos
- PHP 8.3+ com extensões (`curl`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `zip`, `bcmath`)
- Composer 2.x

### 2. Instalação e Execução
```bash
# Instalar dependências
composer install

# Configurar variáveis de ambiente
cp .env.example .env
php artisan key:generate

# Rodar migrações e seeders com dados de demonstração
php artisan migrate --seed

# Executar os testes automatizados
php artisan test

# Iniciar o servidor local
php artisan serve
```

---

## 🔑 Credenciais de Acesso
- **Administrador & Representante:** `admin@comercial.com.br` | Senha: `senha123` (Emmanuel Marcarini)
- **WhatsApp Atendente (Bot):** `55 28 99915-8412`
- **Instância Evolution API:** `farmaflow`

---

## 📚 Central de Documentação
Após efetuar login, acesse o manual completo e interativo em:
👉 `http://localhost:8000/docs`
