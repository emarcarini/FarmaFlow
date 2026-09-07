# 15 — Decisões de Produto

## Confirmadas
1. Evolution API será a camada de WhatsApp.
2. O sistema terá CRM completo.
3. O agente terá dois contextos: cliente e representante.
4. Preços/campanhas serão dados estruturados, não conhecimento livre da IA.
5. Pedido terá validação final de condição comercial.
6. O estilo de comunicação será informal e configurável.
7. Haverá automações de follow-up, recuperação e reposição.
8. Haverá campanhas segmentadas.
9. O representante poderá dar comandos em linguagem natural.

## Decisões pendentes
- sistema externo de estoque/pedidos ou estoque interno;
- regras fiscais e emissão de documentos;
- múltiplos representantes na V1;
- limites de desconto;
- origem inicial dos clientes/produtos;
- regras de opt-in e base legal;
- modelo de aprovação de campanha;
- identidade/transparência apresentada ao cliente.

## Regra para desenvolvimento
Quando uma decisão pendente bloquear implementação, registrar a dúvida em `DECISIONS.md` e escolher a solução reversível mais simples, sem inventar requisito.
