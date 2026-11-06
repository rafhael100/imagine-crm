# CRM-DEC — Aprovação expressa preliminar CORE02-D10-A/B/C/D

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Manifestação do responsável:** **“aprovado”** em resposta à pergunta explícita “Você aprova D10-A, B, C e D nessas condições?”, após apresentação de [CORE02-D10 — rastreabilidade, evidências e homologação](CRM-CORE-P0-ETAPA2-CORE02-D10-rastreabilidade-evidencias-homologacao-proposta-v0.1.md).  
**Estado:** **CORE02-D10-A/B/C/D APROVADAS COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES. CORE02-D10 INTEGRAL E CORE02-P0-02 PERMANECEM ABERTOS.**

## 1. Diretrizes preliminares expressamente aprovadas

1. **D10-A — rastreabilidade de permissões e contratos:** vincular cada ação protegida ao proprietário, fonte e versão contratual, recurso, classe de campo, tenant/membership ou mandato, plano/entitlement, perfil/escopo, finalidade, critérios de aceitação/recusa e efeitos. Catálogo de ações não outorga `allow` sem política e evidências homologadas.
2. **D10-B — cenários positivos, negativos e transversais:** reaproveitar CORE02-T, CORE01/03..09-T, REV/OPS e INT HT01..18 sem duplicar suítes, abrangendo negação cross-tenant, permissão de export distinta de leitura, campos protegidos, revogação, MFA, plano e quota, IA/suporte sem mandato e integrações. Casos devem ter resultados esperados e responsáveis por competência, sem declarar testes executados por aprovação documental.
3. **D10-C — resultados verificáveis e tratamento de defeitos:** distinguir proposto, preliminarmente aprovado, especificado, implementado, testado e homologado. Evidências futuras devem incluir SHA/versão, ambiente, data, procedimento, logs, resultado e aceites competentes. Defeitos críticos de isolamento/autorização bloqueiam promoção da funcionalidade.
4. **D10-D — homologação coordenada e aceite expresso:** compatibilizar CORE01..10, IAM, Segurança, proprietários funcionais DAT/VEN/ATD/AGD/AUT/IA, FIN/CON, Portal/Sites, INT, Auditoria/LGPD e operações. O encerramento de CORE02 integral e dos gates transversais exigirá decisão específica posterior, não se produz por mera aprovação preliminar.

## 2. Pendências não encerradas

- **CORE02-D04, D05, D06, D07, D08, D09 e D10 permanecem abertos integralmente** apesar de suas diretrizes A/B/C/D preliminares, inclusive escolha da precedência P1–P4 de perfis/grants/denies, catálogo real por módulo e campo, fatores/tempos de step-up, alçadas, autorização Laravel/PostgreSQL RLS executável, política de revogação, quotas/preços definitivos por plano e evidências de homologação.
- **CORE01-D04..D07, CORE03..CORE10-D04..D10, SEG-01/02, INT P0/G01..G06, ARQ/AGT, FIN/CON, LGPD e todos os 67 identificadores CORE permanecem sem fechamento técnico.** Diretrizes não equivalem à contagem de tarefas concluídas ou à liberação global.
- O desenvolvimento experimental do Codex segue autorização própria na branch `dev/fast-track-crm-2026-11-04`. Este registro **não** autoriza código adicional, merges, SQL/grants reais, testes de homologação, contas, contratações, alterações de VPS ou produção.
- `main` e a VPS devem permanecer inalteradas; mudanças documentais seguem PR de revisão separado em rascunho.

## 3. Próximo trabalho documental

Preparar **revisão consolidada das lacunas CORE02-D04..D10**, priorizando decisão específica da política P1/P2 de precedência de direitos, sem declarar CORE02 homologado. A revisão é um diagnóstico e pauta, não nova aprovação.
