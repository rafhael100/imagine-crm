# CRM Imagine — DEC-MVP-P0-01 — Prioridade do primeiro CRM funcional e métrica inicial de progresso

**Data:** 06/11/2026 (America/Sao_Paulo). **Natureza:** DIRETRIZ DE PRIORIZAÇÃO DE PRODUTO, **NÃO GO DE IMPLEMENTAÇÃO** ou mudança de gates.

## 1. Aprovação da Direção e limite

Após resposta com levantamento de código e estimativa gerencial de **aproximadamente 10%** (faixa indicativa 5–15%), foi recomendado priorizar o primeiro CRM funcional com **autenticação segura, leads, contatos, funil de vendas e acompanhamento de atividades**, sem descumprir os requisitos de isolamento/autorização.

**Manifestação expressa da Direção:** “aprovado”. A ratificação atinge **ordem e prioridade de produto** e a utilização do percentual somente como referência inicial gerencial estimada — **não autoriza** implementação funcional, `ALLOW`, migrations comerciais, testes funcionais S01, abertura de novas rotas, merge, VPS, produção nem dispensa de revisão. A mensuração formal por funcionalidades ainda não foi feita.

## 2. Base real do código / distinção entre planejamento e produto

- [STATUS-ATUAL.md da DEV](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/STATUS-ATUAL.md) (blob `e064c13d755a2c1c4b55dff7d19a511898dd8a4c`) registra F1-02..F1-10 integradas **somente na DEV**, com mecanismos de IAM/tenant/async de laboratório, `DenyByDefaultAuthorizer` e nenhum fluxo comercial habilitado.
- [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) / FUNC-P0-01/S01 aprovou **somente documentação** de autoconsulta da própria organização e próprio membership; campos físicos/autorizações reais pendentes.
- [PR #23](https://github.com/rafhael100/imagine-crm/pull/23) aberto e **DRAFT**, HEAD `008fcf97c4c332ca85f7abd4cbd9d0f7ecf17558`, base DEV; CI F0 [42252193082](https://github.com/rafhael100/imagine-crm/actions/runs/42252193082) e PostgreSQL [42252193108](https://github.com/rafhael100/imagine-crm/actions/runs/42252193108) verdes **somente em laboratório**; integração depende de ato posterior.
- [DEV-GATE-00](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/planejamento/CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md) é uma minuta NO-GO global; os 67 CORE, SEG-01/02, AUT/ARQ/INT, FIN/CON, LGPD, CORE10 permanecem abertos. Exceções pontuais F1 já autorizadas não promovem outros módulos a `ALLOW`.

## 3. Priorização do primeiro produto funcional (aprovada como direção, não como backlog executável)

| Sequência | Resultado alvo | Recorte funcional e de segurança | Critérios mínimos de valor/evidência antes de considerar entregue | Situação verificada em DEV | Dependências |
|---|---|---|---|---|---|
| MVP-00 | Segurança/contratos mínimos para primeiro fluxo | Identidade/sessão e tenant próprios, membership ativa, autorização por recurso/ação/campo, RLS e integridade, trilha mínima e revogação | No-go enquanto inexistir decisão CORE/SEG que habilite explicitamente o recorte e testes de segurança | Base F1-02..F1-10 em DEV; deny-only sem ALLOW; gates abertos | IAM/CORE01/02/03/04/07/SEG |
| MVP-01 | Login, sessão e seleção da organização | Fluxo humano de autenticação, MFA conforme política, seleção de organização própria, sessão, logout, proteção de CSRF, revogação; autoconsulta S01 reduzida | Sessão real validada com dados fictícios, negativa cross-tenant e para membership revogada, auditoria mínima | S01-D01..D08 e CAM01/CAM02 apenas documentais; código ainda não autorizado | CORE01/02/04/07/SEG, FUNC-P0-01/S01 |
| MVP-02 | Contatos e leads | Criar, listar, buscar, visualizar, editar e arquivar lead/contato, vínculo de responsável, origem opcional oficial, importação futura separada | CRUD com acesso de tenant/equipe/usuário correto; campos e retenções documentados; sem acesso cruzado | Sem código comercial comprovado na DEV verificada | DAT, CORE01/02/06/07, LGPD |
| MVP-03 | Funil e oportunidades | Funis configuráveis por plano, etapas, negócio/oportunidade, movimentação entre etapas, responsável e histórico | Fluxo completo lead→oportunidade→etapa, acesso apropriado, trilha auditável; sem faturamento automático | Sem código comercial comprovado na DEV verificada | VEN, CORE02/07/08, FIN/CON se direito de plano |
| MVP-04 | Atividades e visão operacional | Criar e concluir tarefas, lembretes, agenda mínima interna, atividades ligadas a lead/oportunidade, painel simples de acompanhamento | Tela de trabalho do usuário com pendências e ações vinculadas, sem notificações externas não aprovadas | Sem código comercial comprovado na DEV verificada | AGD/ATD/VEN, CORE02/03/06 |
| MVP-05 | Validação integrada e preparação para uso controlado | Testes de jornadas reais usando dados sintéticos, isolamento, backup/exportação básica, restauração, políticas de acesso, UX e logs | Gate de QA/SEG, importação/exportação/backups em seu próprio módulo, contrato de plano/tenant revisado, rollback e eventual pilotagem aprovada | Não houve homologação nem implantação; PR #2/main e #23 DEV restritos | SEG/QA/LGPD/FIN/CON/CORE10/DEV-GATE |

**Inclusão mínima essencial conforme decisões anteriores:** ferramentas de exportação, importação e backup simples pelo próprio cliente são requisitos gerais do produto, mas devem entrar em módulo próprio com autorização específica; não antecipar fluxo de dados sem contrato.

**Exclusões de lançamento por enquanto:** IA operacional autônoma, captação externa por formulário sem CRM vinculado, n8n sob controle direto do cliente, campanhas, canais externos, billing automático, distribuição sofisticada, relatórios avançados e integrações complexas. São produtos/módulos planejados, não excluídos do CRM definitivo; a versão comercial e os planos devem ser definidos com os seus owners.

## 4. Medição gerencial de desenvolvimento

- **Base visual inicial:** **10% estimados** do **código do CRM completo**, com faixa orientativa **5–15%** devido à ausência de métricas objetivas da totalidade do escopo; não utilizar como percentual auditado, comprometido, medido por commits, linhas ou quantidade de testes.
- **Separar sempre** três métricas: (1) planejamento/documentação (não representa linhas de código); (2) engenharia de fundação validada em CI sintético; (3) jornadas funcionais de cliente implementadas e testadas. Não somar percentuais de categorias heterogêneas.
- **Próxima aferição objetiva:** catalogar 100% do backlog de módulos e cada jornada, atribuir peso publicado para complexidade/valor de cada entrega e contar pontos apenas após code review, testes de aceitação, isolamento, gate e integração autorizada. Sem esse denominador, qualquer novo percentual permanece mera estimativa.
- O avanço de PR #23 em segurança **não altera automaticamente** o número gerencial 10%, porque seu recorte reduz risco de laboratório, não entrega jornada comercial.

## 5. Como destravar desenvolvimento sem liberar acesso prematuro

1. Consolidar dependências **MVP-00/MVP-01**: IAM, política estrita por recurso/ação/campo, membership, RLS, MFA e revogação. Reconciliar S01-R01 CAM/EST e gates relevantes com critérios de teste negativos.
2. Solicitar **autorização específica de recorte funcional mínimo** após a revisão/ato normativo aplicável, por exemplo somente autenticação e autoconsulta segura do próprio tenant, com branch filha da DEV, PR DRAFT e testes sintéticos. **Não antecipar código por este documento**.
3. Com uma primeira jornada autorizada e validada, avançar na ordem **contatos/leads → funis/oportunidades → atividades → exportação/importação/backups/autorização e testes integrados**, com revisão de dependências por módulo e sem `ALLOW` global.
4. Reportar no GitHub cada jornada entregue, SHA, testes, riscos e evolução da métrica, sem confundir documentação, CI verde e homologação para clientes.

## 6. Estado preservado

**PR #23 DRAFT, sem merge; PRs #2/#21 também DRAFT, `main` inalterada, DEV original e VPS sem intervenção por esta decisão**. C01..C08 e os gates CORE, SEG, AUT, ARQ, INT, FIN/CON, LGPD e CORE10 continuam pendentes. Nenhuma nova conta real, permissão, segredo, plano ou dado de cliente foi criado. Este registro acrescenta **apenas um Markdown** à branch documental.
