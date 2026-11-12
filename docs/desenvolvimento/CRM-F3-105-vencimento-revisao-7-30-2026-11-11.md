# CRM F3-105 — Revisão informativa de vencimento (marcos 7/30)

**Data:** 11/11/2026. **Escopo:** DEV, PHP puro + tela local de laboratório, sem cobrança ou conta real.

## Motivação

Além das duas janelas de retenção ilustrativas (14 + 60 dias de trial e 120 dias pós-cancelamento), o planejamento comercial menciona **marcos de revisão de vencimento de 7 e 30 dias**. Nenhum deles é implementado como bloqueio automático, confirmação de dívida, suspensão, multa, boleto, renovação ou tentativa de recebimento. São somente contadores a partir de um **vencimento hipotético fornecido** por um teste.

O componente `SyntheticDelinquencyReviewTimeline` calcula em UTC:

- `before_due`: instante observado antes do vencimento hipotético;
- `before_first_marker`: vencimento atingido e ainda antes de 7 × 86.400 segundos;
- `between_markers`: após 7 dias e antes de 30 dias;
- `second_marker_elapsed`: a partir do marco de 30 dias.

Exibe os dois marcos UTC, o próximo marco ou `null`, os segundos restantes e dias arredondados para cima. Calcula o arredondamento com quociente e resto, sem somar 86.399 a um inteiro potencialmente próximo a `PHP_INT_MAX`.

O painel offline de ciclo de vida (`prototypes/f3-104-retention-lifecycle/index.html`) ganha sete cenários, totalizando **16**. Exibe o primeiro marco de 7 dias separadamente do limite de 30 dias e esclarece: **vencimento não comprova falta de pagamento**.

## Testes

`SyntheticDelinquencyReviewTimelineTest` cobre segundo anterior ao vencimento, dia do vencimento, segundo final do dia 7, instante exato do dia 7, segundo final do dia 30, instante exato e posterior ao dia 30, limites de `PHP_INT_MAX`, contadores e nenhuma capacidade operacional.

`OfflineRetentionLifecycleParityTest` cruza os 16 cenários presentes no próprio HTML com os modelos PHP reais. O smoke Chromium do painel verifica os 16 casos em desktop e 320px, incluindo seleção por teclado, marcadores de alerta, ausência de rolagem horizontal e permanente proibição operacional.

## Controles e decisões pendentes

As flags de cobrança, suspensão, acesso, exclusão e envio de cobrança são permanentemente `false`, inclusive após 30 dias. `unpaid_status_verified=false` em todos os estados. Nenhum serviço pode usar a saída da simulação para bloquear cliente, escolher política de mora, cobrar, gravar histórico ou notificar usuário.

Nenhum endpoint, pagamento, banco, migration, job agendado, dado pessoal, credencial, `main`, VPS ou produção. Uma versão real exige conciliação financeira, contrato, autenticação por empresa, critérios legais, trilha de auditoria e revisão independente CORE/SEG/DBA/LGPD, com aprovação comercial específica.
