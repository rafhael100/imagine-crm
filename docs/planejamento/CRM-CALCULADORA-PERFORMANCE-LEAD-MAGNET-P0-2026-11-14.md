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

## Fluxo offline de opt-in e descadastro (entrega adicional)

O módulo `SyntheticCalculatorNurtureLifecycle.php` implementa máquina determinística de eventos sem dados pessoais e **sem enviar mensagens**. Fases sintéticas: relatório solicitado; marketing solicitado separadamente; confirmação de e-mail em até sete dias; expiração; revogação; novo pedido pós-revogação. Se confirmado apenas no laboratório, sugere quatro conteúdos nos dias 0, 3, 7 e 14, sem job, destinatário ou canal configurado.

A ausência de prova autenticada do consentimento deixa permanentemente `can_send_report=false`, `can_send_marketing=false` e `can_persist_lead=false`. O teste CI `tests/offline_calculator_nurture_lifecycle_test.php` cobre replay, datas fora de ordem, confirmação vencida, descadastro e reentrada. Antes de qualquer operação: gravar prova de origem assinada, canal de contato verificado, identidade do anunciante, base legal, revogação durável, supressão e auditoria.

## PERF-CALC-PG02 — ensaio end-to-end Comercial Node → CRM PHP → PostgreSQL 16

A prova de integração agora inclui os seguintes arquivos (todos **CI-only**, sem clientes ou dados pessoais):

- `SyntheticCalculatorInterestInteropInspector.php`: valida estrutura exata e HMAC de evento de interesse do Comercial; mesmo um MAC válido não autoriza tenant, consentimento, envio, armazenamento de PII ou ativação.
- `tests/offline_calculator_interest_interop_test.php`: vetor HMAC fixo Node/PHP, campos adulterados, janela de validade e tentativa de elevar interesse a consentimento.
- `tests/Labs/calculator/calculator-interest-rls-ci.sql` e `calculator-interest-rls-assertions-ci.sql`: tabelas descartáveis de **metadados**, bloqueio de replay/nonce, integridade por tenant e RLS forçado, sem privilégios UPDATE/DELETE para o papel de teste.
- `scripts/ci/run-calculator-interest-pg-roundtrip.php`: consome a fixture assinada do repositório Comercial fixado no commit `f2454eb58b77702f2df69be658c43ecd49f3beb3`, testa INSERT em PostgreSQL real descartável, replay idêntico, conflito de valor, reutilização do nonce e fraude de permissão de marketing.
- `.github/workflows/ci-crm-calculator-pg-rls.yml`: executa PostgreSQL16 temporário na infraestrutura de CI; banco e papéis desaparecem ao final.

**Resultado:** a gravação transacional de **metadados sintéticos** foi validada; isto NÃO equivale a gravar lead real, a um endpoint HTTP homologado, a prova de LGPD, a opt-in comprovado ou a entrega do relatório. Os campos reais de contato continuam fora do laboratório; `real_crm_receipt_proven=false`, `can_store_pii=false` e `can_send_marketing=false`.

## Próximas dependências operacionais

1. Binding de organização Imagine e origem Comercial assinados em backend independente, autenticação e autorização em produção.
2. Aviso de privacidade, finalidade e prova de consentimento separadas, com revisão jurídica, retenção e direitos do titular.
3. Endpoint privado protegido por CSRF/anti-bot/rate limit, idempotência persistente por organização, controle de exceções e fila de reenvio.
4. Recibo CRM durável verificado, worker de relatório solicitado e opt-in confirmado com revogação e supressão atômica.
5. Piloto real com monitoramento, segurança e aprovação explícita antes de liberar captação no site.

## Dependências
- Comercial PR #389 (calculadora pública, sem PII).
- Performance PR #351 (terceiro canal em laboratório).
- Portal PR #770 (valores comerciais aprovados, migração de catálogo e contratos pendentes).
- LGPD, política de privacidade, consentimento/versionamento e revisão legal; documentação ANPD https://www.gov.br/anpd/pt-br/assuntos/noticias/anpd-lanca-guia-orientativo-sobre-legitimo-interesse

Nenhum envio de e-mail, cobrança, campanha ou deploy será realizado pela presente PR.
