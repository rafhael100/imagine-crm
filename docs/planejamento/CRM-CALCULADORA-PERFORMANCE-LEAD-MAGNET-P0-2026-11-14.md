# CRM — Lead magnet da calculadora Imagine Performance (14/11/2026)

**Status:** rascunho técnico, dados sintéticos, SEM endpoint, SEM comunicação e SEM base legal comprovada.

## Objetivo
Quando o backend autorizado estiver disponível, receber leads da calculadora no Imagine Comercial como origem identificada, registrar resultado de simulação comercial e permitir futura nutrição educativa com permissão específica.

## Separação de finalidades
- Consulta anônima à calculadora: sem nome, e-mail ou telefone; sem CRM.
- Envio do estudo solicitado: solicitação específica do titular, com base legal, aviso e versão comprovados pelo backend.
- Conteúdo educativo e marketing: opção voluntária, desmarcada por padrão, com registro separado de opt-in, histórico e descadastro. Não herda permissão do pedido do estudo.
- Pedido de orçamento/contato pelo WhatsApp não é assinatura de newsletters.

## Etapas para implementação real
1. Imagine Comercial deve ter formulário legítimo somente depois que a API privada homologada estiver disponível. O backend valida nome/e-mail e registra prova de origem e das escolhas, independentemente da alegação do navegador.
2. Empacotar assinatura HMAC S2S, vínculo fixo da organização Imagine, timestamp, nonce anti-replay, versionamento de finalidade, criptografia e rate limit. Não enviar dados sensíveis a logs, prompts, query string ou Analytics.
3. Registrar lead uma vez no CRM por chave de idempotência durável; aceitar confirmação apenas após persistência auditável. Suportar reprocessamento, indisponibilidade, revogação e exclusão.
4. Funil de interesse em Performance: campo de origem, canais de interesse, faixa de mídia, hipótese de CPL histórico marcada como autodeclarada, consentimentos independentes. Dados sintéticos jamais devem ser misturados com reais.
5. Nutrição automática somente após prova backend de base legal e opt-in, controle de frequência e descadastro funcional; parar imediatamente após revogação.
6. Segmentação por interesse declarado e comportamento permitido. Nenhum dado pessoal de empresa cliente do CRM deve alimentar outro tenant.

## Entrega atual
`app/Core/Leads/SyntheticCalculatorNurtureConsentInspector.php` valida um rascunho sintético, recusa canais/versões de aviso desconhecidos, separa marcações de interesse e **sempre recusa** envio de relatório, inscrição em marketing, armazenamento e execução de campanhas; booleans enviados pelo navegador não provam consentimento.

## Dependências
- Comercial PR #389 (calculadora pública, sem PII).
- Performance PR #351 (terceiro canal em laboratório).
- Portal PR #770 (valores comerciais aprovados, migração de catálogo e contratos pendentes).
- LGPD, política de privacidade, consentimento/versionamento e revisão legal; documentação ANPD https://www.gov.br/anpd/pt-br/assuntos/noticias/anpd-lanca-guia-orientativo-sobre-legitimo-interesse

Nenhum envio de e-mail, cobrança, campanha ou deploy será realizado pela presente PR.
