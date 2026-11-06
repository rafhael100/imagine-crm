# CRM Imagine — CORE07-D10: matriz de homologação de convites, assentos, equipes e revogações — proposta v0.1

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado atualizado em 06/11/2026:** **CORE07-D10-A/B/C/D RATIFICADAS EXPRESSAMENTE COMO DIRETRIZES DOCUMENTAIS PRELIMINARES**, conforme [decisão formal](CRM-DEC-ratificacao-explicita-CORE07-D10-ABCD-2026-11-06.md). **CORE07-D10 integral permanece ABERTA**, sem testes executados ou homologação. Menções abaixo a propostas ainda não aprovadas são históricas anteriores à ratificação.  
**Finalidade:** converter as diretrizes preliminares D04–D09 em um roteiro de critérios, proprietários, rastreabilidade e evidências para futuras fases técnicas, sem declarar CORE07 concluído, executar testes nem criar novo gate paralelo ao CORE10/SEG/INT.

## 1. Precedências e estados

- [CORE07-D01..D03](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md): base documental aprovada, mas CORE07 integral aberto.
- [D04 Modelo B](CRM-DEC-ratificacao-explicita-CORE07-D04-modelo-B-renovacao-2026-11-06.md): renovar convite requer iniciativa autorizada, revoga link antigo e cria novo prazo de 7 dias; [D05 Modelo A](CRM-DEC-ratificacao-explicita-CORE07-D05-modelo-A-reserva-assentos-2026-11-06.md): reserva temporária de vaga de convite elegível, sem nova cobrança.
- [D06 Modelo A](CRM-DEC-ratificacao-explicita-CORE07-D06-modelo-A-transferencia-admin-2026-11-06.md): troca atômica de titular, respeitando Básico 1 administrador MFA; [D07 Modelo B](CRM-DEC-ratificacao-explicita-CORE07-D07-modelo-B-redistribuicao-hibrida-2026-11-06.md): distribuição automática somente onde VEN/ATD/AGD já autorizaram.
- [D08 Modelo B e A/B/C/D](CRM-DEC-ratificacao-explicita-CORE07-D08-modelo-B-ABCD-2026-11-06.md): revogação seletiva de autoridade; [D09 A/B/C/D](CRM-DEC-ratificacao-explicita-CORE07-D09-ABCD-2026-11-06.md): eventos e evidências via auditoria central CORE06.
- **CORE07-D04..D10 continuam integralmente ABERTAS**; nenhuma destas aprovações preliminares produz política física, testes de segurança, autorização de deploy ou fecho de gate. Reutilizar CORE01/02/03/04/05/06/08/09/10, SEG-01/02 e INT P0/G01..G06. Todos os 67 identificadores CORE continuam abertos tecnicamente.

## 2. Diretrizes CORE07-D10-A/B/C/D ratificadas preliminarmente

### CORE07-D10-A — Inventário de contratos, proprietários e cenários obrigatórios

Exigir matriz versionada contendo **objeto/ação/campo/tenant, fonte normativa, dono funcional/técnico, ator e competência, entitlement/plano, estado de convite/membership/equipe, efeito oficial, revogação e trilha**. Separar os contratos CORE07 dos proprietários CORE01/02/04/08/FIN/CON e VEN/ATD/AGD; não usar uma tabela de perfil como autorização autônoma. Sem catálogo efetivo de eventos e operações, não classificar requisito como homologado.

### CORE07-D10-B — Cobertura de cenários positivos, negativos e concorrentes

Reusar **CORE07-T01..T14**, CORE01/02/03/04/05/06/08/09-T, CORE10, SEG e INT HT01..HT18 onde correspondentes, evitando suíte duplicada. Planejar validação futura de:
- convite original revogado por renovação; identidade destinatária diferente; expiração e cancelamento;
- aceitação simultânea da última vaga, reserva única, liberação em cancelamento, zero acesso antes da aceitação e nenhuma cobrança por convite;
- Básico com exatamente um administrador MFA e transferência atômica sem dois admins operacionais;
- mudança de equipe sem ler dados fora do escopo, incluindo API, relatório, export e job;
- VEN: impedir redistribuição por SLA de lead já atendido; ATD: origem mantém posse até aceite do destino; AGD: destino físico validado antes de liberar a reserva;
- revogar vínculo em tenant A preservando tenant B; export/download de ex-funcionário bloqueado antes de novo efeito;
- serviço empresarial legítimo continua quando independente da autoridade revogada; IA/suporte sem autorização não se tornam administradores;
- histórico/autoria e trilha imutáveis perante substituição, redistribuição e desligamento.

### CORE07-D10-C — Evidência verificável e bloqueio por falhas

Separar **proposto / preliminarmente aprovado / especificado / implementado / testado / homologado**. Uma evidência futura precisa apontar owner responsável, ID do caso, requisito/contrato, ambiente isolado, SHA e versão, dados de teste adequados, procedimento, resultado real, logs minimizados, falhas/reteste e aceite competente. CI verde ou testes do Codex em branch experimental **não** homologam CORE07 integral. Defeitos relevantes de isolamento, acesso indevido, assentos excedentes, recuperação sem MFA, exportação após revogação ou inconsistência transacional **bloqueiam a promoção da funcionalidade afetada** até reparo e nova prova.

### CORE07-D10-D — Reconciliação intermodular e decisão formal de saída

Exigir validação por CORE01 (tenant/membership/RLS), CORE02 (permissões), CORE03 (jobs), CORE04 (MFA/sessão), CORE05 (acesso excepcional), CORE06 (auditoria), CORE08 (entitlement/trial/Básico), CORE09 (segredos/identidades técnicas), CORE10 (homologação), donos VEN/ATD/AGD/AUT/IA, FIN/CON, Portal/Sites e INT/SEG. A matriz de fechamento precisa registrar **dependência, owner, critério de aceite, evidência, exceção/gap, decisão e data**. **CORE07-D10 e CORE07 integral somente podem ser encerrados por decisão posterior e específica**, sem equiparar aprovação a INT G01..G06, SEG-01/02, autorização de merge ou produção.

## 3. Amostras de contratos de aceitação futura — NÃO EXECUTADOS

| Situação | Resultado esperado | Owner principal |
|---|---|---|
| Dois convites disputam última vaga | Não ultrapassar quota e manter reserva única por vínculo elegível | CORE07 + CORE08/FIN |
| Convite renovado usa token anterior | Negar aceitação, sem nova membership | CORE07 + CORE04/SEG |
| Troca de admin no Básico | Antigo perde autoridade quando novo é ativado, sem estado operacional 2/1 | CORE07 + CORE04 + CORE02 |
| Funcionário desligado inicia download | Negar disponibilização por falta de autoridade vigente | CORE07 + CORE02/03 |
| VEN tenta redistribuir lead já atendido por atraso | Negar regra automática inaplicável; preservar histórico | VEN |
| ATD recebe pedido de transferência sem aceite do destino | Não declarar posse do destino; manter origem responsável | ATD |
| Agenda tenta reassumir horário antes de validar destino | Não confirmar destino nem liberar origem sem regras AGD | AGD |
| Auditor consulta log de tenant errado | Negar e não vazar metadados/confidencialidade | CORE06 + CORE01/SEG |

## 4. Inventário de lacunas que seguirá aberto mesmo após aprovação das quatro diretrizes

1. **Especificação efetiva:** classes e direitos de dados, perfis e equipes, modelo físico, estado/transições/idempotência, lock/conflito, propagação de revogação, TTL/cache, papel do suporte e MFA/step-up por risco.
2. **Regras por proprietário:** pendências CORE07-D04..D09 (integrais), cotas finais e política de assentos comerciais, consentimentos/notificações, parametrização VEN/ATD/AGD e direito de export/backups/portabilidade.
3. **Homologação real:** cenários executados, testes de regressão/corrida/cross-tenant, evidências e aceites de Segurança, QA, Operações, FIN/CON, donos funcionais e INT, ainda ausentes por esta fase documental.
4. **Governança geral:** manter CORE01-D04..D07, CORE02..CORE10-D04..D10, **67 IDs CORE** abertos, SEG-01/02, ARQ/AGT, INT P0/G01..G06, FIN/CON e LGPD; não inferir dev gate fechado.

**Sem programação, testes reais, provisionamento, criação de contas/tokens, compra/contratação, alterações de `main`, VPS, produção ou branch fast-track `dev/fast-track-crm-2026-11-04`.** Esta é proposta documental adicional no PR em rascunho.

## 5. Próxima decisão pedida

**Aprovação expressa registrada:** **CORE07-D10-A/B/C/D ratificadas somente como diretrizes documentais preliminares**. **D10 integral e todo o CORE07 permanecem ABERTOS**. Próximo passo: **revisão consolidada CORE07-D04..D10**, sem fechamento técnico por simples acumulação de diretrizes.
