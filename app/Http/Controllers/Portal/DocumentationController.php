<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    /**
     * Obter os tópicos da documentação estruturados.
     */
    protected function getDocumentationTopics(): array
    {
        return [
            'visao-geral' => [
                'title' => '1. Visão Geral e Primeiros Passos',
                'category' => 'Fundamentos',
                'icon' => 'sparkles',
                'summary' => 'O que é o Assistente Comercial Inteligente e como ele atua no dia a dia.',
                'content' => <<<MD
### O que é o Assistente Comercial Inteligente?
O **Assistente Comercial Inteligente** é a sua plataforma de vendas e relacionamento no WhatsApp. Ele combina:
- **WhatsApp Integrado via Evolution API**: Atendimento automático e manual em tempo real.
- **CRM Completo**: Histórico de clientes, contatos, compras, tags e score RFM comercial.
- **Motor Determinístico de Preços**: Aplicação 100% segura de preços por quantidade e campanhas promocionais.
- **Agente de IA Oficial**: Atende clientes, tira dúvidas de catálogo, conduz cotações e auxilia o representante com tarefas e resumos diários.
- **Automações de Vendas**: Alertas de recompras, clientes inativos e follow-ups de orçamentos parados.

---

### Filosofia e Transparência
1. **Dados Estruturados são a Fonte da Verdade**: A IA nunca inventa preço, desconto ou estoque. Todos os valores apresentados são calculados e validados pelo motor central.
2. **Transparência Ética**: O assistente atua de forma cordial e brasileira, identificando-se como assistente virtual oficial do representante caso seja perguntado pelo cliente.
3. **Controle Total do Representante**: O representante tem autonomia para assumir conversas a qualquer momento, aprovar pedidos com descontos especiais e ajustar condições comerciais.
MD,
            ],
            'atendimento-ia' => [
                'title' => '2. Atendimento Inteligente no WhatsApp & Regras da IA',
                'category' => 'Atendimento',
                'icon' => 'bot',
                'summary' => 'Como a IA conversa com seus clientes e quais regras ela obedece.',
                'content' => <<<MD
### Estilo de Conversa
O assistente é configurado para conversar com naturalidade, cordialidade e objetividade, usando o tom brasileiro do comércio:
- *"Opa, Celso! Tudo certo por aí? Já estou verificando a condição da Dipirona pra você."*
- *"Para 50 caixas, consigo aplicar a nossa faixa especial de R$ 8,90 a unidade! Fica R$ 445,00 no total."*

---

### Regras Absolutas de Segurança
1. **Preço Real**: A IA consulta as tabelas cadastradas no sistema antes de responder qualquer cotação.
2. **Estoque e Disponibilidade**: Se um produto estiver indisponível ou inativo, o assistente avisa com clareza.
3. **Campanhas Vigentes**: Aplica automaticamente descontos sazonais e condições por volume.
4. **Isolamento Total**: Memórias, preferências e histórico de uma empresa nunca são compartilhados com outra.
MD,
            ],
            'crm-carteira' => [
                'title' => '3. CRM Comercial, Tags e Score RFM',
                'category' => 'CRM',
                'icon' => 'users',
                'summary' => 'Gestão de empresas, múltiplos contatos, tags e score explicável.',
                'content' => <<<MD
### Gestão de Empresas e Contatos
No módulo **CRM**, você visualiza sua carteira completa:
- **Dados Cadastrais**: Razão social, nome fantasia, CNPJ, endereço, cidade e UF.
- **Múltiplos Contatos**: Cadastre compradores, farmacêuticos e gerentes por empresa.
- **Segmentos & Tags**: Classifique clientes por tags (*VIP*, *Hospitalar*, *Farmácia*, *Distribuidora*).

---

### Score Comercial Explicável (RFM)
O sistema calcula automaticamente uma nota de 0 a 100 para cada cliente com base em:
- **Recência (40%)**: Tempo decorrido desde o último pedido faturado.
- **Frequência (30%)**: Regularidade e número de pedidos realizados.
- **Monetário (30%)**: Volume financeiro total comprado.

> **Transparência**: O painel exibe o motivo exato da nota e alerta sobre clientes em tendência de risco (*at_risk*).
MD,
            ],
            'precos-campanhas' => [
                'title' => '4. Catálogo de Produtos, Tabela por Volume e Campanhas',
                'category' => 'Catálogo',
                'icon' => 'tags',
                'summary' => 'Como funcionam os preços escalonados e campanhas promocionais.',
                'content' => <<<MD
### Preços por Faixa de Quantidade
Cada produto pode ter faixas de preço progressivas por volume de compra:
- **1 a 9 un**: Preço base (ex: R$ 12,00)
- **10 a 49 un**: Desconto por faixa (ex: R$ 10,50)
- **50+ un**: Preço especial de atacado (ex: R$ 8,90)

---

### Campanhas Promocionais
Cadastre campanhas com data de início e fim:
- **Preço Fixo Promocional**: Define valor unitário específico para a campanha.
- **Desconto Percentual**: Aplica percentual extra sobre o produto.
- **Elegibilidade**: Restrinja campanhas para públicos específicos (ex: apenas *Farmácias* ou clientes *Classificação A*).
MD,
            ],
            'cotacoes-pedidos' => [
                'title' => '5. Cotações e Fechamento com Trava de Segurança',
                'category' => 'Vendas',
                'icon' => 'file-check',
                'summary' => 'O ciclo de vida da cotação e a proteção contra alterações de preço.',
                'content' => <<<MD
### Ciclo de Vida da Cotação
Uma cotação passa pelos seguintes status:
1. `draft` (Rascunho) → 2. `sent` (Enviada ao cliente) → 3. `accepted` (Aceita) → 4. `converted_to_order` (Fechada em Pedido).

---

### Trava Determinística de Fechamento (Order Lock)
No instante exato em que uma cotação é convertida em pedido, o sistema executa uma **revalidação completa**:
- Se uma campanha promocional expirou minutos antes, o sistema **bloqueia** o fechamento e avisa o vendedor.
- Se o preço cadastrado foi alterado ou o item ficou sem estoque, o fechamento é interrompido para revisão.
- **Alçadas de Desconto**: Se o desconto total concedido ultrapassar o limite do representante (ex: 15%), o pedido entra como `pending_approval` para validação do gestor.
MD,
            ],
            'handover-humano' => [
                'title' => '6. Transferência Humana (Handover) e Pausa da IA',
                'category' => 'Atendimento',
                'icon' => 'user-check',
                'summary' => 'Como pausar o robô e assumir o chat diretamente pelo portal.',
                'content' => <<<MD
### Como Funciona o Handover?
O atendimento passa para o modo humano em três situações:
1. **Solicitação do Cliente**: Se o cliente pedir para falar com uma pessoa, vendedor ou representante.
2. **Gatilho de Segurança**: Em casos de dúvidas médicas ou exceções comerciais.
3. **Ação Manual no Portal**: Clicando no botão **"Assumir Conversa"** no Inbox.

---

### Comportamento da Conversa
- Quando o humano assume, a IA **pausa imediatamente** todas as respostas automáticas.
- O representante pode enviar mensagens de texto diretamente pelo Inbox.
- Para reativar o robô após encerrar a conversa com o cliente, basta clicar em **"Retomar IA"**.
MD,
            ],
            'comandos-ia' => [
                'title' => '7. Guia de Comandos em Linguagem Natural',
                'category' => 'Assistente',
                'icon' => 'terminal',
                'summary' => 'Perguntas e ordens que você pode enviar para seu assistente.',
                'content' => <<<MD
### Exemplos de Comandos para o Representante
Você pode interagir com o assistente em linguagem natural tanto pelo WhatsApp quanto pelo portal:

- **Consultas Rápidas**:
  - *"Quanto vendi este mês?"*
  - *"Quais cotações estão em aberto?"*
  - *"Qual o preço de 100 caixas de Paracetamol?"*

- **Gestão de Carteira**:
  - *"Quais clientes estão sem comprar há mais de 30 dias?"*
  - *"Quem são os clientes com maior chance de reposição hoje?"*

- **Agendamento e Tarefas**:
  - *"Agende um follow-up para a Drogaria São Bento na próxima quinta-feira."*
  - *"Lembre-me de ligar para a Dra. Mariana amanhã às 10h."*
MD,
            ],
            'automacoes' => [
                'title' => '8. Automações Comerciais e Rotinas Diárias',
                'category' => 'Automação',
                'icon' => 'zap',
                'summary' => 'Follow-up de cotações, recuperação de clientes e reposições.',
                'content' => <<<MD
### Rotinas Automáticas do Sistema
O sistema roda rotinas diárias para garantir que nenhuma venda seja esquecida:
1. **Follow-up de Cotações Paradas**: Alerta sobre orçamentos enviados há mais de 2 dias sem resposta.
2. **Recuperação de Clientes em Risco**: Identifica clientes que ultrapassaram sua média de compras e gera tarefas de recuperação.
3. **Oportunidades de Reposição**: Estima a data de esgotamento do estoque do cliente com base no volume da última compra e gera uma tarefa prioritária de contato.
MD,
            ],
            'seguranca-lgpd' => [
                'title' => '9. Segurança, LGPD e Firewall Clínico',
                'category' => 'Compliance',
                'icon' => 'shield-check',
                'summary' => 'Proteção de dados, opt-out automático e salvaguardas de saúde.',
                'content' => <<<MD
### Conformidade com a LGPD
- **Opt-Out Automático**: Se o cliente enviar palavras como *"PARAR"*, *"SAIR"* ou *"CANCELAR"*, o sistema desativa imediatamente comunicações automáticas para o número.
- **Histórico e Consentimento**: Todas as preferências de privacidade ficam gravadas e visíveis no cadastro do cliente.

---

### Firewall Clínico e Farmacêutico
Por exigência regulatória e segurança do paciente:
- O assistente é **estritamente comercial** e **nunca** realiza prescrições, posologias ou diagnósticos clínicos.
- Ao detectar perguntas como *"estou com dor, posso tomar quantos comprimidos?"*, o sistema recusa a resposta médica e transfere para atendimento humano.

---

### Logs e Auditoria Completa
Todas as ações de alteração de preço, criação de cotações, fechamento de pedidos e execuções da IA são registradas no banco de auditoria com IP, data e autor.
MD,
            ],
        ];
    }

    public function index(Request $request): View
    {
        $topics = $this->getDocumentationTopics();
        $selectedSlug = $request->input('topic', 'visao-geral');

        if (!array_key_exists($selectedSlug, $topics)) {
            $selectedSlug = 'visao-geral';
        }

        $activeTopic = $topics[$selectedSlug];
        $activeTopic['slug'] = $selectedSlug;

        return view('portal.docs.index', compact('topics', 'activeTopic', 'selectedSlug'));
    }
}
