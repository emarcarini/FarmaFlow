# 05 — WhatsApp e Conversas

## Fluxo
Mensagem → webhook → identificação do contato → carregamento de contexto → agente → ferramenta(s) → validação → resposta → registro.

## Requisitos
- Suporte a texto.
- Preparar arquitetura para áudio, imagem, PDF e localização futuramente.
- Controle de mensagens duplicadas.
- Idempotência.
- Status de envio/entrega/leitura quando disponível.
- Retry seguro.
- Registro de erros.
- Janela/conformidade de mensagens conforme regras vigentes da plataforma.

## Transferência humana
Ao detectar necessidade de humano:
1. pausar respostas automáticas;
2. registrar motivo;
3. notificar representante;
4. marcar conversa como “humano assumiu”;
5. permitir retomada da automação.

## Identidade e transparência
A experiência pode ser extremamente natural e personalizada, mas o produto não deve implementar mecanismos de falsificação de identidade ou instruções destinadas a enganar deliberadamente o cliente sobre a natureza automatizada do atendimento.
