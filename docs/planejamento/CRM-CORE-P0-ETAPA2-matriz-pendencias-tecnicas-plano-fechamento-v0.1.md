# CRM-CORE-P0 — Etapa 2: matriz de pendências técnicas e roteiro de fechamento documental (v0.1)

**Data:** 04/11/2026.  
**Estado:** **PLANO DOCUMENTAL DE TRABALHO — NÃO APROVADO COMO FECHAMENTO TÉCNICO**.  
**Motivo de criação:** aceite expresso de [CORE10-D01–D03](CRM-DEC-aprovacao-CORE-P0-10-D01-D03-base-homologacao-2026-11-04.md) com ordem de prosseguir **somente** no planejamento documental das pendências.  
**Regra absoluta:** **não programar, não executar testes**, não provisionar contas, infraestrutura, segredos, logs, automações nem serviços reais. Não comprar/contratar, implantar, criar banco ou acessar dados de clientes.

## 1. Linha de base, alcance e contagem

- [Etapa 1](CRM-CORE-P0-ETAPA1-painel-fechamento.md): dez blocos CORE-P0-01..10 têm **D01–D03 aprovadas como bases documentais**. Nenhum bloco está concluído tecnicamente.
- **Pendências identificadas por decisão:** CORE01-D04..D07 = **4**; CORE02..CORE10-D04..D10 = **9 × 7 = 63**; total = **67 identificadores pendentes**. O número mede entradas documentais, **não** 67 riscos independentes: vários IDs compartilham uma mesma dependência de SEG/INT/FIN/ARQ e não devem gerar decisões, motores ou verificações duplicadas.
- **CORE01 termina originalmente em D07**; não criar CORE01-D08..D10 para uniformizar tabela.
- A aprovação de [CORE10 D01–D03](CRM-DEC-aprovacao-CORE-P0-10-D01-D03-base-homologacao-2026-11-04.md) aprovou **o método de rastrear e preparar homologação**, não o fechamento dos 67 IDs.
- Antes do desenvolvimento exige-se reconciliar ainda [INT P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md), FIN/CON, políticas jurídicas, dados e contratos, donos reais, custos, licenças, revisão comercial e aprovação global expressa. **G01–G06 permanecem abertos**, e INT HT01–18 permanecem **0/18 executados**.

## 2. Inventário de pendências por bloco — sem mudança de estado

| Bloco | Decisões ABERTAS | Objeto técnico/documental que falta decidir | Competências e dependências |
|---|---|---|---|
| [CORE01 — Identidade, tenant, RLS](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md) | **D04–D07** | Membership/perfil/assento; contexto de tenant e RLS por conexão/job/serviço; vida da organização e contrato; esquema/constraints/índices e matriz futura | CORE02/03/04/05/07/08/09, FIN/CON, SEG, INT |
| [CORE02 — Direitos por recurso/campo](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) | **D04–D10** | Classificação de campos; perfis e precedência de grants/negações; catálogo por módulo, ação, exportação e escopo; alçadas; direitos de agentes; evidências e homologação | Proprietários ATD/VEN/AGD/DAT/FIN/CON, IAM, INT P0-OWN/SEC |
| [CORE03 — Jobs/eventos/webhooks](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md) | **D04–D10** | Envelope físico, delegação e revalidação, contexto RLS em workers, outbox/inbox/filas por domínio, interfaces, quotas/retencão, casos negativos | CORE01/02/04/06/09, ARQ-02, INT CT-03/04, AUT |
| [CORE04 — Login/MFA/sessões](CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md) | **D04–D10** | Fatores, tokens e política por papel, recuperação e último admin Básico, step-up, antiautomação, auditoria e revogação integrada | CORE01/02/03/05/06/07/09, Segurança |
| [CORE05 — Suporte excepcional](CRM-CORE-P0-05-acesso-excepcional-imagine-mandatos-suporte-proposta-v0.1.md) | **D04–D10** | Catálogo de operações e campos, legitimidade/aprovação, mandato sem bypass, break-glass, transparência, auditoria e testes | LGPD, CORE01/02/04/06, INT P0-OWN/SEC |
| [CORE06 — Auditoria/retencão](CRM-CORE-P0-06-auditoria-categorias-retencao-acesso-proposta-v0.1.md) | **D04–D10** | Eventos por categoria, esquema de evidência e acesso/export, retenção de fatos e legais, expurgo/restore, monitoramento/custos e critérios de homologação | Auditoria Geral, LGPD, INT-12, IN-162, FIN/CON, CORE05 |
| [CORE07 — Convites/equipes](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md) | **D04–D10** | Token/concorrência de convite, reserva de assento, troca de administrador, alcance de equipe, revogação em sessões/jobs, auditoria e casos negativos | CORE01/02/03/04/06/08, FIN/CON |
| [CORE08 — Básico/trial/cortesia](CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md) | **D04–D10** | Contratos Site↔tenant/CNPJ/benefício/assinatura; conflitos de concessões; titularidade e antifraude; estados/timers; acesso suspenso; quotas/preços finais; homologação | FIN/CON, Portal/Sites, CRM-REV02, GRAT-P, INT |
| [CORE09 — Cofre/identidade técnica](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md) | **D04–D10** | Fornecedor/cofre e topologia, bootstrap de principal técnico, matriz de ferramentas, TTL/rotação/revogação, DR e custo/licença, auditoria e testes | **SEG-01/SEG-02**, IN-151, ARQ-01, AGT-01, INT-02 |
| [CORE10 — Evidência/gates](CRM-CORE-P0-10-pacote-homologacao-documental-nucleo-proposta-v0.1.md) | **D04–D10** | Fonte primária, checklist implementável, casos canônicos/dados sintéticos, NO-GO/aceite de risco, custos/LGPD, reconciliação G01–G06, tag/aceite final | CORE01..09, INT P0, IN-168, FIN/CON, Governança |

**Situação de todos os grupos:** **ABERTO/AGUARDA DECISÃO**. A enumeração não concede aprovação técnica.

## 3. Matriz transversal dos bloqueios que não podem desaparecer

| Origem | Pendências não concluídas | Reutilização e cautela |
|---|---|---|
| [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) | **ARQ-01, ARQ-02, ARQ-03, SEG-01, SEG-02, AGT-01** | Seis bloqueios estruturais; segmentação da VPS dos agentes aprovada em princípio, porém topologia, custos, cofre, riscos, playbooks e integração não homologados |
| [INT P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) | **P0-DOC, TR, OWN, CON, SEC, DEP, VER, ACC, XREF, GIT** | Rastrear contratos e autoridade do dono; não duplicar motor IAM/cofre/auditoria |
| INT Gates | **G01..G04 parciais/abertos; G05/G06 pendentes** | Fonte e completude; dependências/owners; aceite global; SHA/tag final, sem fechar por criação de arquivos |
| FIN/CON/CRM-REV02/GRAT-P | Titularidade, entitlement, estados de cobrança, gratuidade e assinaturas | Nenhuma compra, valor final, benefício, desconto, assinatura, licença ou cobrança por inferência |
| LGPD, Auditoria e ciclos de dados | Retenção por categoria, export, exclusão, backups, suporte excepcional | Não inventar prazo único ou direito irrestrito de auditor |
| Licenciamento n8n e serviços IA | Direitos de uso interno, limites de provedores, custo e contratação | Nenhum licenciamento ou execução de cliente foi homologado por aprovar modelos lógicos |

## 4. Ordem recomendada de tratamento — dependências antes das decisões de integração

| Onda documental | Escopo a aprofundar | Resultado esperado do PLANEJAMENTO (não implantação) | Gate de revisão |
|---|---|---|---|
| **E2-A — Isolamento e identidade** | **CORE01-D04..D07**, cruzar CORE02, CORE03, CORE04, CORE07, CORE09; SEG-01/02 | Matriz de objetos globais × tenant, lifecycle de memberships, contexto RLS por fronteira, contratos e pontos de decisão; [minuta E2-A](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md) | Aprovação futura específica D04..D07; não fechar CORE01 sem aceitar dependências |
| **E2-B — Direitos e sessão** | CORE02-D04..D10; CORE04-D04..D10; CORE07-D04..D10 | Matriz recurso×ação×campo×escopo, MFA/step-up por função, revogação e convites/assentos | Segurança + proprietários de módulos |
| **E2-C — Jobs e fontes de verdade** | CORE03-D04..D10; ARQ-02; INT OWN/CON/SEC | Contratos de identidade causal, evento e resultado oficial, revalidação por fila; sem mudar IN127-R01..R06 | Integrações/Filas/Segurança e dono do evento |
| **E2-D — Suporte, auditoria e dados** | CORE05-D04..D10; CORE06-D04..D10 | Mandato escopado, catálogo de acesso, evidência, minimização, retenções e descarte legal | Auditoria Geral/Privacidade/Segurança |
| **E2-E — Contratos comerciais** | CORE08-D04..D10; CORE07-D05; REV02/GRAT-P/FIN/CON | Estados de plano, site, cortesia, trial, reativação, cargos, quotas e autorização de despesa | FIN/CON, Portal/Sites, Segurança |
| **E2-F — Segredos/agentes/infra** | CORE09-D04..D10; **SEG-01/02**, ARQ-01/03, AGT-01 | Opções de cofre, serviço, ferramentas, playbooks, custos e arquitetura; seleção somente por aprovação expressa | Arquitetura/Segurança/Operações/FIN |
| **E2-G — Evidências e liberação** | CORE10-D04..D10, INT P0, G01..G06 e todas as pendências P0 de REV/IN | Matriz de rastreabilidade final, casos com critério e estados, owners, custos, contratos e divergências resolvidas | Aceite global futuro expressamente solicitado; G05/G06 só mediante confirmação específica |

**A ordem é uma sugestão de documentação e pode ter trabalho cruzado/iterativo.** A profundidade de uma onda não autoriza fechar outras nem cria prioridade de implementação. As escolhas que dependem de fornecedor ou orçamento só são submetidas após informações e alternativas adequadas.

## 5. Ficha única de decisão de pendência (padrão de rastreabilidade; não software)

| Campo | Como registrar sem antecipar resultado |
|---|---|
| ID e bloco | Exatamente o Dxx, P0/SEG/INT/GATE existente, sem duplicar alias |
| Fonte e versão | Documento original, capítulo, texto de aprovação, caminho GitHub, SHA/commit e precedência |
| Escopo e contexto | Serviço, tenant, entidade, módulo, ambiente, operação e risco |
| Estado documental | **Aberto → Proposta → Em revisão → Aprovado documentalmente → Fechado documental/executável**, somente com deliberação/evidência correspondentes; **NÃO EXECUTADO** é estado separado dos testes |
| Dono de competência | Serviço/proprietário funcional; pessoa designada só quando houver atribuição real |
| Opções e trade-offs | Segurança, simplicidade, custos/licença, suporte, dados, continuidade, mitigação |
| Decisão explícita | Opção escolhida pelo responsável, data, justificativa, exceções, limites e documento |
| Dependências e riscos | P0 relacionados, condições de NO-GO, problemas remanescentes |
| Critério de aceite futuro | Casos positivos/negativos, invariantes, evidência exigível e aprovador; nunca “aprovado” sem teste |
| Histórico/revisões | Versão, fonte anterior substituída, commit da decisão; manter trilha de mudanças |

## 6. Critérios de não-encerramento

- Uma deliberação D04 ou D05 não aprova os demais Dxx.
- Escolher modelo lógico de isolamento não homologa RLS implementável nem acesso a bancos reais.
- Matriz de testes aprovada não é evidência de teste executado; **INT HT01..18 = 0/18 executados**.
- Aprovação da Etapa 2 não significa que CORE01..10, SEG-01/02, ARQ, AGT, INT ou G01..06 estejam encerrados.
- Nunca tratar aprovação de CNPJ/múltiplos Sites como autorização para misturar dados ou conceder trial duplo.
- Não estabelecer cifras, limites técnicos, prazo de token, esquema, produto cloud, custo, licenças ou contratos novos sem decisão própria.
- **NO-GO para programação enquanto faltar fechamento documental global e aceite expresso em SHA/tag final.**

## 7. Encaminhamento inicial

Elaborar e submeter primeiro a [**CORE01-D04..D07 — isolamento, memberships e contratos executáveis**](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md). É **minuta para avaliação**, não aprovação tácita dos quatro itens. Em seguida, continuar o bloco E2-B de direitos, sessões e convites sem mudar funcionalidades já aceitas.

**Estado final desta matriz:** plano de trabalho documentado; todos os D04..D10, CORE01-D04..D07, SEG-01/02, ARQ/AGT, INT P0/G01..G06 e autorização global continuam **ABERTOS**.


## 8. Primeira decisão técnica preparada — CORE01-D04

A primeira proposta de deliberação concreta foi registrada em [**CORE01-D04 — membership, estados, unicidade, convite e revogação**](CRM-CORE-P0-ETAPA2-CORE01-D04-decisao-membership-estados-unicidade-proposta-v0.1.md): recomenda unicidade lógica identidade×tenant, convite como intenção distinta de membership e desligamento/reativação estritamente por organização, com histórico oficial. **CORE01-D04 permanece ABERTA; A/B/C foram aprovadas apenas como diretrizes documentais preliminares** [conforme aceite expresso](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md), pendendo conciliação CORE02/04/07/08. As políticas de assentos, MFA, perfis e admin único permanecem em CORE02/04/07/08; D05..D07 não mudam de estado. **Sem testes e sem programação.**


## 9. Estado atualizado de CORE01-D04 e nova proposta CORE01-D05 — 04/11/2026

- **CORE01-D04-A/B/C aceitas somente como diretrizes preliminares**: unicidade lógica de vínculo identidade×tenant, convite como intenção distinta de concessão de acesso e revogação/desligamento por tenant. [Decisão](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md). **CORE01-D04 continua aberta**, dependente de CORE02, CORE04, CORE07 e CORE08, sem contrato físico final.
- **CORE01-D05 — [nova proposta de contexto seguro, transação, Laravel/PostgreSQL RLS e service principals](CRM-CORE-P0-ETAPA2-CORE01-D05-contexto-tenant-RLS-proposta-v0.1.md)**: quatro diretrizes candidatas D05-A/B/C/D, **ainda não aprovadas**, preservando necessidade de definir autenticação de contexto no banco, RLS por tabela, papéis/ownership, pool de conexão, workers, mandatos e auditoria.
- **Contagem inalterada: 67 itens técnicos abertos**; nenhum CORE-P0, SEG-01/02, INT P0 ou G01..G06 foi concluído por esses documentos. Nenhum teste, código, conta ou infraestrutura real criado.
