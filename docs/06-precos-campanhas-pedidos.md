# 06 — Preços, Campanhas e Pedidos

## Fonte da verdade
Preço e condição comercial devem vir de dados estruturados e vigentes.

## Modelo de condição
Uma condição pode ter:
- produto
- público/segmento opcional
- quantidade mínima/máxima
- preço unitário
- desconto
- validade inicial/final
- prioridade
- condições de pagamento
- regras adicionais
- status

## Resolução de preço
Ao montar cotação:
1. localizar produto;
2. verificar disponibilidade;
3. localizar campanhas vigentes;
4. verificar elegibilidade do cliente;
5. aplicar faixa de quantidade;
6. selecionar condição válida de maior prioridade/benefício conforme regra comercial;
7. calcular total;
8. registrar origem da condição;
9. apresentar ao cliente;
10. revalidar no fechamento.

## Trava de pedido
O pedido não deve ser finalizado se:
- campanha expirou;
- preço foi alterado;
- quantidade não atende faixa;
- desconto excede limite;
- produto está indisponível;
- condição exige aprovação.

## Exemplo
Produto X:
- 1–9: R$ 20
- 10–49: R$ 18
- 50+: R$ 15

Cliente pede 60 → sistema aplica R$ 15, recalcula total e valida novamente antes de criar pedido.

## Campanhas
Campanhas devem possuir controle de:
- público
- validade
- mensagem
- produtos
- condições
- frequência
- aprovação
- status
- resultados
- opt-out
