# Instruções para o Agente de Desenvolvimento — Antigravity

Você está construindo o Assistente Comercial Inteligente descrito neste workspace.

## Ordem de leitura obrigatória
1. README.md
2. docs/01-visao-produto.md
3. docs/02-requisitos-funcionais.md
4. docs/04-agente-ia.md
5. docs/05-whatsapp.md
6. docs/06-precos-campanhas-pedidos.md
7. docs/09-seguranca-lgpd-compliance.md
8. docs/10-modelo-dados.md
9. docs/13-criterios-de-aceite.md
10. specs/BACKLOG.md

## Objetivo
Construir primeiro uma V1 funcional e estável. Não tente implementar tudo de uma vez.

## Regras de engenharia
- Não invente requisitos.
- Não hardcode preços, campanhas ou regras comerciais.
- Não coloque segredo em código.
- Prefira serviços pequenos e testáveis.
- Toda ação de escrita comercial relevante deve ser auditável.
- Toda ação sensível da IA deve passar por autorização configurável.
- Use idempotência nos webhooks.
- Nunca permita que uma resposta da IA seja considerada fonte de verdade para preço/estoque.
- Dados estruturados são a fonte de verdade.
- Trate falhas externas de forma segura.
- Nunca misture memória ou contexto entre clientes.

## Arquitetura conceitual
WhatsApp/Evolution API → webhook → aplicação → identificação/contexto → agente → ferramentas → validação → resposta → auditoria.

## Agente
O agente não deve ter acesso irrestrito ao banco. Exponha ferramentas/serviços com contratos claros.

Exemplos:
- consultar_preco(produto, cliente, quantidade)
- validar_condicao(cotacao)
- criar_cotacao(cliente, itens)
- criar_pedido(cotacao)
- criar_followup(cliente, data, motivo)
- transferir_humano(conversa, motivo)

## Preço
Implemente um serviço determinístico de resolução de preço. A IA solicita o resultado; não decide o preço.

## Pedidos
Antes de confirmar pedido, execute validação final contra as condições vigentes.

## WhatsApp
Implementar webhook robusto, idempotente, com logs, retries e tratamento de mensagens duplicadas.

## Segurança
Implementar autenticação, autorização por papel, segregação de dados e auditoria.

## UX
O painel deve ser simples para um representante comercial: cliente, conversa, oportunidade, cotação, pedido, tarefa e próxima ação devem estar próximos.

## Estilo de conversa
O agente deve ser informal e personalizado, conforme configuração do representante, sem produzir respostas artificiais ou excessivamente longas. Não implementar mecanismos destinados a falsificar identidade humana.

## Medicamentos
O produto é comercial. O agente não deve diagnosticar, prescrever ou recomendar tratamento individualizado. Questões clínicas devem ser encaminhadas.

## Processo de execução
1. Inspecione o workspace.
2. Crie plano de implementação.
3. Implemente P0 por incrementos.
4. Após cada incremento, rode testes.
5. Corrija regressões.
6. Atualize documentação se houver decisão nova.
7. Não avance para P1 enquanto P0 crítico não estiver funcional.

## Primeiro marco
Entregar um esqueleto executável com:
- autenticação;
- CRM básico;
- produtos;
- condições comerciais;
- conversas;
- webhook Evolution API;
- agente com ferramenta de consulta de preço;
- cotação;
- validação comercial;
- auditoria;
- testes automatizados essenciais.

Depois expandir o backlog.
