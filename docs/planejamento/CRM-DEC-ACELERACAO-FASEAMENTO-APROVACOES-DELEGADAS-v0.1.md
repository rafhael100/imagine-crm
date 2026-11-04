# CRM Imagine — Decisão delegada e plano de aceleração para início do desenvolvimento (v0.1)

**Data:** 04/11/2026.  
**Solicitação literal do responsável:** “quero que vc aprove o que ainda falta pois precisamos inicir o desenvolvimento”.  
**Natureza:** registro da **delegação de revisão/decisões técnicas documentais** ao assistente, com limites de evidência, e plano **proposto** de liberação por fase. **Não simula aceite pessoal específico ou testes inexistentes**.
**Estado atual:** **NO-GO para programação integral/produção** porque o fechamento técnico, segurança, contratos e baseline ainda são insuficientes. O objetivo deste documento é **reduzir o número de rodadas de aprovação**, não marcar o projeto como pronto indevidamente.

## 1. Regras operacionais de aprovação em lote

1. **Pode decidir por delegação técnica e registrar no GitHub** uma diretriz documental ou alternativa concreta quando: (a) houver fonte/versionamento verificáveis; (b) houver avaliação de compatibilidade com decisões já aprovadas; (c) a escolha não criar acesso, gasto, dados reais, capacidade comercial nem obrigação legal novos; (d) as pendências residuais constarem explicitamente do mesmo ato.
2. **Não pode converter aprovação documental em homologação técnica:** tabelas, RLS, provedores, cofres, chaves, topologia física, custos, retenção legal, contratos e autorizações externas exigem especificação completa, competência/aceites e evidências. Contratos ainda não escritos não estão aprovados.
3. **Não declarar 67 P0 CORE fechados** por decisão genérica: para cada D04..D10/D07 é preciso mapa de dependências com contrato e evidência, mesmo que se aprove **um pacote comum** em um único ato.
4. **Alterar a regra histórica de planejamento integral antes do código** requer decisão explícita de **GO por escopo/ambiente/risco**. A intenção de acelerar é registrada, mas **esta minuta não autoriza programação por si**; nenhuma alteração a sistemas/infraestrutura nem geração de cobrança é consentida por silêncio.
5. **Testes de homologação técnica só são executados durante o desenvolvimento**, após GO para o escopo; não exigir 18/18 HT executados antes de qualquer código, mas exigir contratos, critérios e controles documentados para o respectivo escopo e sem alegar PASS.

## 2. Aprovação delegada documental já efetuada

| ID | Resultado e fonte | Situação integral |
|---|---|---|
| **CORE02-D06-A/B/C/D** | [Aceite delegado preliminar documentado](CRM-DEC-aprovacao-delegada-CORE02-D06-ABCD-preliminar-2026-11-04.md) após análise de [proposta concreta](CRM-CORE-P0-ETAPA2-CORE02-D06-catalogo-acoes-por-modulo-proposta-v0.1.md); catálogo tipado, efeitos separados, direitos contextualizados e confirmação por proprietário | **D06 ABERTA**: ação/campo efetivos, donos, políticas finais, planos, segurança e provas pendentes |

| **CORE02-D05 — escolha de composição de perfis** | [Política técnica delegada P1 + P2 escopada](CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md) seleciona união de grants válidos sob bloqueios mandatórios e negações explícitas só no escopo correspondente; P3/P4 não são padrão | **D05 ABERTA**: owners, esquema de papéis, herança/precedência por módulo, segurança, cache/revogação, provas ainda pendentes |

Nenhuma aprovação genérica de CORE02-D07..D10, CORE03..CORE10-D04..D10, SEG-01/SEG-02 ou INT-G01..G06 foi emitida. **As propostas documentais ainda podem ser examinadas em lote**, mas é obrigatório discriminar os itens tecnicamente decididos dos ainda sem especificação.

## 3. Desdobramento em faixas de liberação — alternativas sem ativação automática

| Faixa | Trabalho candidato | Requisitos prévios ao GO | Proibições mesmo após eventual GO limitado |
|---|---|---|---|
| **FASE 0 — documentação, sem código** | Matriz única de contratos E2-X01..12, fechar decisões para foundation, selecionar política IAM, topologia/custos, definir backlog v1, revisar FIN/CON e LGPD | Autorização de planejamento já existente; nada a contratar | Código executável, migração, testes, dados reais, segredos, produção |
| **FASE 1 — código isolado de fundação, somente se GO explícito posterior** | Estrutura modular Laravel, testes com dados exclusivamente sintéticos, contratos de usuário/tenant, IAM, migrations a partir de **schema aprovado**, RLS conforme **contrato aprovado**, pipelines CI sem segredos reais | **Gate documental mínimo F1:** arquitetura IAM e RLS concreta revisada, fronteiras de dados/serviços especificadas, SEG-01/02 relevantes a F1 fechados, ambiente de desenvolvimento seguro definido, contratos CORE01/02/03/04/07/09 necessários aceitos, owners e critérios negativos definidos, ato de GO textual com commit SHA/escopo | Operar sobre clientes, acessar produção, integrar bancos/provedores reais, contratar serviços, publicar, usar dados pessoais reais, fazer deploy público |
| **FASE 2 — CRM essencial em homologação** | Leads, contatos, funis, permissões, export/import, integrações de módulos conforme contrato | Fechamento do escopo funcional, DAT/VEN/ARQ, direitos de campos, testes de F1 com evidências e aprovação de etapa | Operações de produção sem aceite, exposição de PII, dados cruzados |
| **FASE 3 — financeiro, canais, IA, produção** | Assinaturas/trial/cortesia, Site↔CRM, atendimento/canais, agentes, automações e lançamento por módulos | FIN/CON e contratos INT/Portal, CORE08, Segurança, IN-168, licenciamento, custos, ambiente real, testes e aceite de implantação | Ativação sem homologação, cobranças automáticas não contratadas, acesso de IA irrestrito |

**FASE 1 é uma proposta de exceção à regra anterior**, a aprovar formalmente quando seu gate mínimo estiver especificado. Não há GO já concedido; não confundir “proposta de GO faseado” com autorização de código ou teste.

## 4. Pré-requisitos que continuam bloqueantes (estado verificado)

| Área | Evidência pendente | Fonte |
|---|---|---|
| **Dados e identidade** | CORE01-D04..D07 contratos físicos; CORE02-D04..D10 recursos/ações/campos, composição de perfis, MFA, RLS e revogação | [Matriz CORE Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) |
| **Segurança** | SEG-01 autorizador independente de agentes/serviços, SEG-02 cofre/identidades técnicas e políticas efetivas; CORE09 e CORE05 | [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) |
| **Arquitetura e continuidade** | ARQ-01 topologia/custo/capacidade, ARQ-02 contratos principal/contingência/filas, ARQ-03 monitoramento independente e AGT-01 playbooks | [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) |
| **Integrações e autoridade** | P0-DOC/TR/OWN/CON/SEC/DEP/VER/ACC/XREF/GIT, G01..G06 incompletos, aceites de módulos/fontes, 18 roteiros HT documentados e **não executados** | [Painel INT P0](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) |
| **Comercial/dados** | FIN/CON, planos definitivos, contratos Site↔CRM, LGPD, retenções/portabilidade por categoria, escopo v1 e custos | [DEV-GATE-00](CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md) |
| **Controle de versão** | Revisão de incompatibilidades, baseline SHA/tag e GO expressamente limitado | INT G05/G06; DEV-GATE-00 |

## 5. Pacotes de decisões para aprovação técnica em lote (sem novos módulos)

- **Pacote IAM/RLS:** CORE01-D04..D07, CORE02-D04..D10, CORE03-D04/05/06, CORE04/05/07, CORE09 e SEG-01/02 — entregar uma matriz implementável de entidades, recurso, ação, campo, papel, MFA, RLS por tabela, roles, pools, revogação e confiança de serviço. Não escolher política SQL sem avaliação.
- **Pacote Serviços/Integrações:** CORE03-D07..D10, INT CT-01..11, ARQ-02, IN127-R01..R06 — comando tipado, idempotência contextual, resultado oficial, fila, retry, contingência e segregação.
- **Pacote Planos/Portal:** CORE08, FIN/CON, REV02, PORTAL-COM e Sites — estados, trial, cortesia, assinaturas, titularidade, direitos por plano e custos.
- **Pacote Operações e evidências:** CORE06/09/10, IN-168 ARQ/AGT/CON/OBS, INT ACC/G01..G06 — auditoria, retenção, backups, monitoramento, inventário, provas futuras, termos de GO e baseline imutável.

**Aplicação:** redigir um único ato de aceite por pacote quando houver a matriz completa, mas listar nominalmente cada subdecisão/contrato/pendência. **Não usar aprovações prévias de princípios para simular contratos não decididos.**

## 6. Resultado e próxima ação prioritária

A [Etapa 2 R01](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md) já associa **12 contratos compartilhados** aos 67 IDs de CORE e deve ser usada como base, não duplicada. A proposta **CORE02-D06** recebeu aceite delegado preliminar; a prioridade restante é fechar **modelo concreto de permissão/composição, IAM/RLS e segurança** para um gate F1 verificável, não elaborar dezenas de “aprovado” sem prova.

**Conclusão:** delegação registrada; aprovação preliminar rastreável efetuada; desenvolvimento integral e FASE 1 **ainda não estão liberados**. Não houve código, teste, migração, credencial, compra nem implantação.
