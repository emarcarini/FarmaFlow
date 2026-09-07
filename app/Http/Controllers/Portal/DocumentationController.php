<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    /**
     * Obter os tópicos da documentação estruturados no padrão Vitepress Catppuccin.
     */
    protected function getDocumentationTopics(): array
    {
        return [
            'instalacao-configuracao' => [
                'title' => 'Instalação e Deploy',
                'category' => 'Começando',
                'badge' => 'Guia',
                'icon' => 'terminal',
                'summary' => 'Guia passo a passo para instalação, variáveis de ambiente e deploy no Dokploy/Docker.',
                'content' => <<<'MD'
# Instalação e Configuração

O **FarmaFlow** foi projetado para execução escalável e resiliente em contêineres Docker, compatível com orquestradores modernos como **Dokploy**, **Coolify** ou **Docker Compose** nativo.

::: tip PRÉ-REQUISITOS
- **Docker 24.0+** e **Docker Compose v2+**
- **Traefik** ou **Nginx Reverse Proxy** com suporte a HTTPS/SSL
- **Evolution API v2.2+** (para conexão com instâncias do WhatsApp)
- Chave de API do **Google Gemini** (modelo `gemini-2.0-flash`)
:::

---

## 1. Clonando o Repositório

Faça o clone do repositório oficial e acesse o diretório raiz do projeto:

```bash
git clone https://github.com/emarcarini/FarmaFlow.git
cd FarmaFlow
```

---

## 2. Configurando o Arquivo .env

Copie o arquivo de exemplo e configure suas credenciais de banco, Redis e Evolution API:

```bash
cp .env.example .env
```

Principais variáveis de ambiente a serem configuradas:

```env
APP_NAME=FarmaFlow
APP_ENV=production
APP_KEY=base64:seuAppKeyGeradoPeloArtisan
APP_DEBUG=false
APP_URL=https://farmaflow.npper.com

# Banco de Dados MySQL
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=farmaflow
DB_USERNAME=farmaflow_user
DB_PASSWORD=sua_senha_super_segura

# Cache & Sessões no Redis
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Evolution API v2 (WhatsApp Engine)
EVOLUTION_API_URL=http://evolution-api:8080
EVOLUTION_API_KEY=farmaflow_evolution_key_123
EVOLUTION_INSTANCE=comercial

# Google Gemini AI
GEMINI_API_KEY=AIzaSySuaChaveGoogleGeminiAqui
GEMINI_MODEL=gemini-2.0-flash
```

---

## 3. Inicialização dos Contêineres Docker

Para subir a stack completa (Nginx, PHP 8.3 FPM, MySQL 8.0, Redis e Evolution API):

```bash
docker compose up -d --build
```

O contêiner `app` executa automaticamente as migrações, seeds de demonstração e sincronização de credenciais na inicialização via `entrypoint.sh`.

::: info CONTAS DE DEMONSTRAÇÃO
- **Representante:** `carlos@comercial.com.br` / `senha123`
- **Administrador:** `admin@comercial.com.br` / `senha123`
:::
MD
            ],
            'visao-geral' => [
                'title' => 'Visão Geral da Plataforma',
                'category' => 'Começando',
                'badge' => 'Arquitetura',
                'icon' => 'sparkles',
                'summary' => 'O que é o FarmaFlow, filosofia de desenvolvimento e fontes da verdade.',
                'content' => <<<'MD'
# Visão Geral da Plataforma

O **FarmaFlow** é uma solução completa de **Inteligência Comercial e Relacionamento via WhatsApp** desenvolvida especialmente para representantes de distribuidoras farmacêuticas e laboratórios.

```
+-------------------------------------------------------------------------+
|                              FarmaFlow Core                             |
|                                                                         |
|  +-------------------+   +--------------------+   +------------------+  |
|  |   Evolution API   |-->|   AI Prompt Hub    |-->|  Deterministic   |  |
|  |    (WhatsApp)     |   | (Gemini 2.0 Flash) |   |  Pricing Engine  |  |
|  +-------------------+   +--------------------+   +------------------+  |
|            |                       |                       |            |
|            v                       v                       v            |
|  +-------------------+   +--------------------+   +------------------+  |
|  |  Real-time Inbox  |   |    CRM 360° com    |   | Commercial Lock  |  |
|  | & Human Handoff   |   |     Score RFM      |   | & Order Approval |  |
|  +-------------------+   +--------------------+   +------------------+  |
+-------------------------------------------------------------------------+
```

---

## Pilares Fundamentais

### 1. Dados Estruturados como Fonte da Verdade
A Inteligência Artificial atua como interface de linguagem natural, mas **nunca inventa preços, descontos, prazos ou estoques**. Todas as consultas matemáticas e regras de tabela são executadas diretamente pelo motor determinístico do Laravel.

### 2. Transparência e Humanização Ética
O assistente conversa em português brasileiro corporativo, com simpatia e agilidade. Ao ser questionado pelo cliente, identifica-se de forma transparente como assistente digital oficial do representante comercial.

### 3. Autonomia e Controle do Representante
O representante possui um painel completo para:
- Acompanhar conversas em tempo real.
- Assumir qualquer atendimento com 1 clique (*Human Handoff*).
- Aprovar alçadas especiais de desconto que ultrapassam os limites automáticos.
- Receber alertas diários de recompra e cotações estagnadas.
MD
            ],
            'evolution-whatsapp' => [
                'title' => 'WhatsApp & Evolution API v2',
                'category' => 'Canais & Mensageria',
                'badge' => 'API v2',
                'icon' => 'message-circle',
                'summary' => 'Conexão de instâncias WhatsApp, webhook handling e envio de mensagens.',
                'content' => <<<'MD'
# Conexão WhatsApp & Evolution API v2

A comunicação entre o FarmaFlow e o WhatsApp ocorre através da **Evolution API v2**, utilizando sessões gerenciadas em Redis para alta disponibilidade.

::: info ENDPOINTS PRINCIPAIS
- **Base URL Interna:** `http://evolution-api:8080`
- **Dashboard Web / Swagger:** `https://evolution.npper.com/manager`
- **API Token:** Definido no `.env` (`EVOLUTION_API_KEY`)
:::

---

## 1. Webhook de Mensagens Recebidas

O FarmaFlow escuta eventos no endpoint `/api/v1/webhook/whatsapp`. Cada mensagem recebida dispara o pipeline assíncrono:

```
[Cliente WhatsApp] ──> [Evolution API] ──> [Laravel Webhook Route]
                                                   │
                                                   ▼
                                        [ProcessIncomingMessage Job]
                                                   │
                                     ┌─────────────┴─────────────┐
                                     ▼                           ▼
                              [Audit Logging]           [AI Intent Engine]
```

---

## 2. Handoff Humano vs Robô

Cada conversa possui um atributo `is_robot_active` no banco de dados.

- Quando `is_robot_active = true`: A IA processa cada mensagem e responde automaticamente.
- Quando `is_robot_active = false`: As mensagens chegam no Inbox, mas a IA permanece em silêncio para que o representante digite manualmente.

::: warning ATENÇÃO AO ASSUMIR CHATS
Ao clicar em **"Assumir Atendimento"** no portal, o robô é pausado instantaneamente para aquele contato, evitando respostas cruzadas.
:::
MD
            ],
            'ia-gemini' => [
                'title' => 'Motor de IA (Gemini 2.0 Flash)',
                'category' => 'Inteligência Artificial',
                'badge' => 'Google Gemini',
                'icon' => 'bot',
                'summary' => 'Como a IA interpreta intenções, consulta catálogo e aplica salvaguardas.',
                'content' => <<<'MD'
# Motor de IA: Google Gemini 2.0 Flash

O FarmaFlow utiliza o modelo **Gemini 2.0 Flash**, combinando velocidade ultrarrápida de inferência (inferior a 800ms) com alta fidelidade a regras de negócio e formatações estruturadas (JSON Function Calling).

---

## Estrutura do System Prompt

O prompt do sistema é injetado dinamicamente com as seguintes seções:

1. **Contexto do Representante:** Nome do vendedor, região de atuação e canais de contato.
2. **Contexto da Empresa Cliente:** Razão social, limite de crédito, histórico recente de compras e score RFM.
3. **Catálogo & Preços Vigentes:** Lista de medicamentos, códigos SKU e faixas de volume ativas para o cliente.
4. **Regras de Encerramento:** Instruções explícitas de jamais garantir itens fora de estoque ou conceder descontos fora das alçadas cadastradas.

---

## Exemplos de Interação

### Consulta de Preço por Faixa
> **Cliente:** *"Boa tarde, quanto sai a caixa de Paracetamol 750mg se eu levar 80 caixas?"*
>
> **FarmaFlow IA:** *"Olá, Dr. Celso! Boa tarde! Para 80 caixas de Paracetamol 750mg (cód. MED-002), conseguimos aplicar a faixa de atacado de **R$ 6,80 por unidade**. O total do pedido fica em **R$ 544,00**. Gostaria que eu já gerasse a cotação formal para envio?"*

### Identificação de Tentativa de Burlar Preço
> **Cliente:** *"O representante do concorrente me fez por R$ 5,00 a unidade. Faz igual?"*
>
> **FarmaFlow IA:** *"Entendo perfeitamente, Celso! A nossa tabela autorizada para esse lote é de R$ 6,80 para pedidos acima de 50 caixas. Posso registrar uma solicitação especial de alçada de desconto para o Carlos (nosso representante) aprovar para você. Quer que eu encaminhe?"*
MD
            ],
            'crm-rfm' => [
                'title' => 'CRM Comercial & Score RFM',
                'category' => 'Gestão Comercial',
                'badge' => 'Preditivo',
                'icon' => 'users-2',
                'summary' => 'Metodologia de cálculo do Score RFM (Recência, Frequência e Valor Monetário).',
                'content' => <<<'MD'
# CRM Comercial & Score RFM

O FarmaFlow implementa um modelo matemático proprietário de **Score RFM (Recency, Frequency, Monetary)** adaptado para a distribuição farmacêutica B2B.

---

## Fórmula de Composição do Score (0 a 100)

Score Total = (R * 0.40) + (F * 0.30) + (M * 0.30)

### 1. Recência (R - Peso 40%)
Mede o intervalo em dias desde o último pedido faturado:
- **0 a 15 dias:** 100 pontos
- **16 a 30 dias:** 80 pontos
- **31 a 60 dias:** 50 pontos
- **61 a 90 dias:** 25 pontos
- **> 90 dias:** 0 pontos (Cliente considerado *at_risk* ou inativo)

### 2. Frequência (F - Peso 30%)
Quantidade de compras nos últimos 180 dias em relação à média da carteira.

### 3. Monetário (M - Peso 30%)
Volume financeiro acumulado no ano corrente frente à meta da classificação do cliente (A, B ou C).

---

## Classificação de Clientes

| Classe | Perfil de Compra | Frequência Típica | Score Médio |
| :--- | :--- | :--- | :--- |
| **Classe A** | Redes de Farmácias e Hospitais | Semanal / Quinzenal | 85 - 100 |
| **Classe B** | Farmácias Independentes | Mensal | 60 - 84 |
| **Classe C** | Pequenos Varejos e Clínicas | Trimestral / Esporádico | 0 - 59 |
MD
            ],
            'catalogo-precos' => [
                'title' => 'Catálogo & Preços Determinísticos',
                'category' => 'Gestão Comercial',
                'badge' => 'Tabelas',
                'icon' => 'layers',
                'summary' => 'Preços base, escalonamento por volume e regras de campanhas promocionais.',
                'content' => <<<'MD'
# Catálogo & Preços Determinísticos

O motor de precificação do FarmaFlow opera de maneira estritamente determinística através do serviço `App\Services\Pricing\PricingEngine`.

---

## Hierarquia de Aplicação de Preços

Ao calcular o valor unitário de um item em uma cotação, o sistema segue a seguinte ordem de precedência:

1. **Campanha Promocional Específica Ativa**: Se houver cupom/código de campanha vinculado e elegível.
2. **Faixa Escalonada por Quantidade**: Se a quantidade solicitada atingir o piso de uma faixa cadastrada.
3. **Preço Base de Tabela**: Valor padrão unitário cadastrado no produto.

```php
// Exemplo de retorno estruturado:
[
    'unit_price' => 7.50,
    'base_price' => 12.00,
    'discount_percent' => 37.5,
    'applied_rule' => 'Volume Tier (100+ un) + Campanha OUTUBRO_ROSA',
    'requires_approval' => false
]
```
MD
            ],
            'cotacoes-pedidos' => [
                'title' => 'Cotações, Trava & Alçadas',
                'category' => 'Vendas & Fechamento',
                'badge' => 'Segurança',
                'icon' => 'shopping-bag',
                'summary' => 'Fluxo de conversão, trava de preço (Order Lock) e aprovação de descontos.',
                'content' => <<<'MD'
# Cotações, Trava de Segurança & Alçadas

Garantir a integridade financeira das vendas é essencial para distribuidores e laboratórios. O FarmaFlow possui um mecanismo à prova de falhas chamado **Deterministic Order Lock**.

---

## O Ciclo de Vida da Venda

```
[Cotação Aberta] ──> [Validação de Estoque & Preço] ──> [Verificação de Alçada]
                                                              │
                                     ┌────────────────────────┴────────────────────────┐
                                     ▼                                                 ▼
                         [Desconto Dentro da Alçada]                      [Desconto Excede Limite]
                                     │                                                 │
                                     ▼                                                 ▼
                         [Pedido Faturado Instantâneo]                    [Aguardando Aprovação Gerencial]
```

::: danger TRAVA DETERMINÍSTICA (ORDER LOCK)
Se um cliente demorar dias para aceitar uma cotação e, nesse meio tempo, uma campanha promocional expirar ou o produto sofrer reajuste de custo, o fechamento é **automaticamente bloqueado** para evitar prejuízo à distribuidora.
:::

---

## Matriz de Alçadas de Desconto

- **Até 10% de desconto adicional:** Liberação imediata pelo representante.
- **De 10.1% a 20%:** Requer aprovação do Gerente Comercial Regional.
- **Acima de 20%:** Requer aprovação da Diretoria Comercial no painel.
MD
            ],
            'handover-humano' => [
                'title' => 'Handoff Humano-Robô',
                'category' => 'Atendimento',
                'badge' => 'Inbox',
                'icon' => 'user-check',
                'summary' => 'Como funciona o controle híbrido de atendimento e quando intervir.',
                'content' => <<<'MD'
# Handoff Humano-Robô

O FarmaFlow não substitui o representante comercial — ele atua como seu **copiloto 24/7**.

---

## Cenários de Transferência Automática

A IA passa a conversa para o modo humano nos seguintes casos:

1. **Pedido Explícito:** O cliente escreve expressões como *"quero falar com um humano"*, *"cadê o Carlos?"* ou *"me liga"*.
2. **Exceção Clínica:** Perguntas sobre sintomas graves, posologias veterinárias ou dosagens controladas.
3. **Negociação Travada:** Três tentativas consecutivas de desconto fora das tabelas permitidas.

::: tip RETOMADA RÁPIDA DA IA
Após atender o cliente no WhatsApp, o representante pode clicar no botão **"Retomar IA"** na barra superior do chat para devolver o monitoramento automático.
:::
MD
            ],
            'automacoes-rotinas' => [
                'title' => 'Automações & Rotinas Diárias',
                'category' => 'Operações & Cron',
                'badge' => 'Artisan',
                'icon' => 'zap',
                'summary' => 'Comandos cron que rodam diariamente para aquecimento e follow-up.',
                'content' => <<<'MD'
# Automações & Rotinas Diárias

O FarmaFlow executa rotinas agendadas (Laravel Scheduler) para manter a carteira sempre aquecida.

---

## Comandos Disponíveis

### 1. Follow-up de Cotações Estagnadas
Identifica orçamentos enviados há mais de 48h sem resposta:
```bash
php artisan sales:check-abandoned-quotes
```

### 2. Recálculo Diário de Score RFM
Atualiza as notas de todos os clientes com base nas notas fiscais faturadas:
```bash
php artisan crm:recalculate-rfm-scores
```

### 3. Sugestão de Reposição Inteligente
Estima o esgotamento de estoque do cliente e agenda uma tarefa para o vendedor:
```bash
php artisan crm:detect-repurchase-opportunities
```
MD
            ],
            'seguranca-lgpd' => [
                'title' => 'Compliance LGPD & Firewall Clínico',
                'category' => 'Segurança & Compliance',
                'badge' => 'LGPD',
                'icon' => 'shield-check',
                'summary' => 'Políticas de privacidade, salvaguardas éticas e proteção de dados médicos.',
                'content' => <<<'MD'
# Compliance LGPD & Firewall Clínico

O FarmaFlow está em conformidade estrita com a **Lei Geral de Proteção de Dados (Lei nº 13.709/2018)** e com as normativas da **ANVISA**.

---

## Diretrizes de Privacidade

- **Opt-Out Instantâneo:** Se o cliente enviar palavras como *"SAIR"*, *"PARAR"* ou *"CANCELAR"*, o número é adicionado à lista de exclusão imediatamente.
- **Logs Criptografados:** Todas as mensagens são armazenadas em banco com criptografia em repouso.
- **Firewall Clínico:** O assistente **nunca receita nem orienta tratamentos médicos**. Ao detectar consultas clínicas de leigos, reforça a recomendação de consulta com um médico ou farmacêutico habilitado.
MD
            ],
            'comandos-artisan' => [
                'title' => 'Referência de Comandos CLI',
                'category' => 'Operações & Cron',
                'badge' => 'CLI',
                'icon' => 'code-2',
                'summary' => 'Lista completa de comandos Artisan para manutenção e testes.',
                'content' => <<<'MD'
# Referência de Comandos CLI

Comandos úteis para diagnóstico, testes e manutenção no servidor:

```bash
# Testar conexão com a Evolution API
php artisan evolution:test-connection

# Enviar mensagem de teste via WhatsApp
php artisan whatsapp:send-test --phone=5511999999999 --message="Teste FarmaFlow"

# Limpar caches de configuração e rotas
php artisan optimize:clear

# Rodar migrações e popular dados de teste
php artisan migrate:fresh --seed
```
MD
            ],
        ];
    }

    /**
     * Renderiza o markdown de forma segura com Admonitions estilo Vitepress
     */
    protected function parseMarkdown(string $markdown): string
    {
        // 1. Tip Callout
        $markdown = preg_replace(
            '/:::\s*tip\s*([^\n]*)\n(.*?)\n:::/s',
            '<div class="catppuccin-callout tip my-6 p-4 rounded-2xl bg-[#a6e3a1]/10 border border-[#a6e3a1]/30 text-[#cdd6f4]"><div class="flex items-center gap-2 font-bold text-[#a6e3a1] text-xs font-mono uppercase mb-2"><i data-lucide="lightbulb" class="w-4 h-4"></i><span>$1</span></div><div class="text-xs leading-relaxed text-[#bac2de]">$2</div></div>',
            $markdown
        );

        // 2. Info Callout
        $markdown = preg_replace(
            '/:::\s*info\s*([^\n]*)\n(.*?)\n:::/s',
            '<div class="catppuccin-callout info my-6 p-4 rounded-2xl bg-[#89b4fa]/10 border border-[#89b4fa]/30 text-[#cdd6f4]"><div class="flex items-center gap-2 font-bold text-[#89b4fa] text-xs font-mono uppercase mb-2"><i data-lucide="info" class="w-4 h-4"></i><span>$1</span></div><div class="text-xs leading-relaxed text-[#bac2de]">$2</div></div>',
            $markdown
        );

        // 3. Warning Callout
        $markdown = preg_replace(
            '/:::\s*warning\s*([^\n]*)\n(.*?)\n:::/s',
            '<div class="catppuccin-callout warning my-6 p-4 rounded-2xl bg-[#f9e2af]/10 border border-[#f9e2af]/30 text-[#cdd6f4]"><div class="flex items-center gap-2 font-bold text-[#f9e2af] text-xs font-mono uppercase mb-2"><i data-lucide="alert-triangle" class="w-4 h-4"></i><span>$1</span></div><div class="text-xs leading-relaxed text-[#bac2de]">$2</div></div>',
            $markdown
        );

        // 4. Danger Callout
        $markdown = preg_replace(
            '/:::\s*danger\s*([^\n]*)\n(.*?)\n:::/s',
            '<div class="catppuccin-callout danger my-6 p-4 rounded-2xl bg-[#f38ba8]/10 border border-[#f38ba8]/30 text-[#cdd6f4]"><div class="flex items-center gap-2 font-bold text-[#f38ba8] text-xs font-mono uppercase mb-2"><i data-lucide="shield-alert" class="w-4 h-4"></i><span>$1</span></div><div class="text-xs leading-relaxed text-[#bac2de]">$2</div></div>',
            $markdown
        );

        try {
            return Str::markdown($markdown);
        } catch (\Throwable) {
            return '<pre>' . e($markdown) . '</pre>';
        }
    }

    public function index(Request $request): View
    {
        $topics = $this->getDocumentationTopics();
        $selectedSlug = $request->input('topic', 'instalacao-configuracao');

        if (!array_key_exists($selectedSlug, $topics)) {
            $selectedSlug = 'instalacao-configuracao';
        }

        $activeTopic = $topics[$selectedSlug];
        $activeTopic['slug'] = $selectedSlug;
        $activeTopic['html_content'] = $this->parseMarkdown($activeTopic['content']);

        // Agrupar tópicos por categoria no padrão Vitepress
        $groupedTopics = [];
        $slugsList = array_keys($topics);
        $currentIndex = array_search($selectedSlug, $slugsList);

        $prevTopic = $currentIndex > 0 ? $topics[$slugsList[$currentIndex - 1]] : null;
        if ($prevTopic) {
            $prevTopic['slug'] = $slugsList[$currentIndex - 1];
        }

        $nextTopic = $currentIndex < count($slugsList) - 1 ? $topics[$slugsList[$currentIndex + 1]] : null;
        if ($nextTopic) {
            $nextTopic['slug'] = $slugsList[$currentIndex + 1];
        }

        foreach ($topics as $slug => $topic) {
            $cat = $topic['category'] ?? 'Geral';
            $groupedTopics[$cat][$slug] = $topic;
        }

        return view('portal.docs.index', compact(
            'topics',
            'groupedTopics',
            'activeTopic',
            'selectedSlug',
            'prevTopic',
            'nextTopic'
        ));
    }
}
