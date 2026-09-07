# 10 — Modelo de Dados Conceitual

## Entidades principais
- users
- representatives
- companies
- contacts
- customer_tags
- products
- product_prices
- commercial_campaigns
- campaign_products
- campaign_audiences
- conversations
- messages
- opportunities
- quotes
- quote_items
- orders
- order_items
- tasks
- followups
- customer_product_history
- customer_scores
- notifications
- approvals
- audit_logs
- consent_preferences
- ai_memories

## Relacionamentos essenciais
- empresa possui contatos
- cliente pertence a carteira/representante
- cliente possui conversas
- conversa possui mensagens
- cliente possui histórico de produtos
- cotação pertence a cliente e possui itens
- pedido pode originar-se de cotação
- campanha possui produtos e público
- campanha gera envios/resultados
- tarefa/follow-up pertence a cliente/oportunidade
- aprovação pertence a uma ação sensível

## Requisito
Toda entidade comercial importante deve possuir timestamps, status e histórico/auditoria quando houver alteração relevante.
