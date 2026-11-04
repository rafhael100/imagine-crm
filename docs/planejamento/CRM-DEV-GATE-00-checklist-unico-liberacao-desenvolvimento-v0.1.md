# CRM Imagine — DEV-GATE-00: Checklist único de liberação para iniciar desenvolvimento (v0.1)

**Data de referência:** 04/11/2026.  
**Estado:** **MINUTA DOCUMENTAL DE GOVERNANÇA; NÃO APROVADA COMO GATE.**  
**Resultado atualizado após a decisão de 04/11/2026:** **GO RESTRITO PARA F0 — desenvolvimento inicial isolado na branch `dev/fast-track-crm-2026-11-04`; NO-GO para produção, clientes, integrações e funções sensíveis não homologadas.** [Decisão executiva posterior](CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md). Qualquer indicação posterior ou histórica de NO-GO absoluto para programação foi parcialmente superada **somente na F0**.  
**Origem da iniciativa:** continuidade do planejamento autorizada com “ok”, após discussão dos bloqueios necessários para iniciar o desenvolvimento. **Esse aceite de continuidade não equivale a aprovação dos itens técnicos, do cronograma, de gastos ou da autorização de programação.**

> Este documento é **um índice executivo de fechamento**: não cria novos requisitos, serviços, agentes, motores, gates obrigatórios, preços ou títulos de P0; organiza as fontes já existentes e a evidência necessária para futura decisão do responsável.

## 1. Princípio e alcance de DEV-GATE-00

- O planejamento funcional/arquitetural está avançado, mas **o fechamento técnico e a reconciliação global ainda não ocorreram**.
- Em [CORE Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md), existem **67 identificadores de decisão técnica abertos**: CORE01-D04..D07 (**4**) e CORE02..CORE10-D04..D10 (**63**). Alguns são sobrepostos e devem ser fechados por pacote com rastreabilidade **individual**, sem contá-los como 67 riscos independentes.
- **CORE01-D04-A/B/C**, **D05-A/B/C/D** e **D06-A/B/C/D** têm apenas diretrizes preliminares aceitas; **CORE01-D04, D05 e D06 integrais permanecem abertos**. [CORE01-D07-A/B/C/D](CRM-CORE-P0-ETAPA2-CORE01-D07-modelo-fisico-integridade-RLS-proposta-v0.1.md) foram [**aceitas apenas como diretrizes preliminares**](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md); **CORE01-D07 permanece ABERTA**, sem schema/roles/policies homologados.
- [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) mantém **ARQ-01, ARQ-02, ARQ-03, SEG-01, SEG-02 e AGT-01** P0; P1/P2 devem ser avaliados por impacto, sem fechamento ou adiamento tácito de riscos críticos.
- O [painel INT](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) mantém **P0-DOC/TR/OWN/CON/SEC/DEP/VER/ACC/XREF/GIT** abertos/parciais e **G01..G06 não concluídos**. Os roteiros **HT01..18** estão documentados, com **0/18 executados**; execução de testes só após autorização de programação/homologação em ambiente apropriado.
- [REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) ainda requer conciliação FIN/CON e decisão de valores/recursos finais; [REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) mantém OPS-P01..P18; demais CRM-REV também registram diferenças/P0 próprios.
- A autorização final deverá ocorrer **separadamente e por manifestação expressa do responsável**, referindo-se a uma versão final GitHub identificada (SHA ou tag) e a um escopo de desenvolvimento especificado. Aceitar este checklist ou uma minuta CORE **não libera código**.

## 2. Checklist de saída — itens bloqueantes para autorizar a primeira fase

**Legenda de estados:** ABERTO / EM ANÁLISE / DOCUMENTO APROVADO PARCIAL / FECHADO POR EVIDÊNCIA E ACEITE / NÃO APLICÁVEL POR DELIBERAÇÃO ESPECÍFICA. O estado inicial abaixo é conservador e não deve ser atualizado por inferência.

| Pacote | Evidência concreta a produzir e aprovar antes do GO | Fontes obrigatórias e dependências | Situação nesta data |
|---|---|---|---|
| **A — IAM, multiempresa, dados e RLS** | Contratos de identidade global, memberships, usuários/assentos, recursos/campos, MFA/sessões, matriz de entidades e integridade tenant-bound, política Laravel+PostgreSQL RLS e exceções legítimas, incluindo jobs/exportações | CORE01-D04..D07, CORE02/03/04/05/06/07, SEG-01/02, REV01, REV03 | **ABERTO** |
| **B — Segurança técnica e operações críticas** | Autorizações independentes de agentes e ferramentas por ação/tenant/risco, cofre/segredos e identidades técnicas, suporte excepcional, trilha/auditoria, revogação, playbooks e proteção de ambientes | SEG-01/02, CORE05/06/09, AGT-01, INT P0-SEC/OWN, LGPD | **ABERTO** |
| **C — Infraestrutura, filas e contingência** | Topologia executável dos serviços e ambientes, capacidade e custo aprovado, persistência/idempotência de API principal/contingência e filas, supervisão/monitoramento independente, backup e recuperação | ARQ-01/02/03, IN-127 R01..R06, REV09, CON/OBS P1 quando bloquearem fase inicial | **ABERTO** |
| **D — Contratos entre sistemas** | Fontes oficiais por efeito, contratos tipados/versões, envelopes/erros/estados, reconciliação, autenticação de provedores, risco de circularidade, interfaces Portal/Sites/CRM/n8n/agentes | INT P0-DOC/TR/OWN/CON/SEC/DEP/VER/XREF, CORE03/08/09, Portal/Sites | **ABERTO** |
| **E — Comercial/financeiro e entitlements** | Contratos oficiais de plano/cortesia/trial/gratuidade, titularidade, ciclo financeiro, assinaturas, critérios de suspensão/portabilidade, recursos e preços finais conforme versão de lançamento | CORE08-D04..D10, REV02, FIN/CON, GRAT-P, Portal COM-08/09, IN-119..122, LGPD | **ABERTO** |
| **F — Módulos funcionais e escopo da versão** | Congelar o que entra em v1 e o que é posterior; donos e fronteiras de leads, atendimento, vendas, agenda, dados/anexos, automações e IA; critérios de exclusão explícitos | CRM-REV01..09, módulos DAT/ATD/VEN/AGD/AUT/IA, INs e direitos de planos | **ABERTO** |
| **G — Conformidade, rastreabilidade e aceitação documental** | Fonte primária e decisões reconciliadas, exceções, proprietários por competência/operação, matriz de critérios e roteiros futuros, classificação de dados/retentões, riscos e NO-GO, pendências P0 fechadas ou exclusões expressamente aprovadas | CORE10-D04..D10, INT P0-ACC/GIT, G01..G06, REV09 OPS, LGPD | **ABERTO** |
| **H — Baseline e GO explícito** | Revisão cruzada final; SHA/tag imutável da documentação; checklist completo; autorização textual expressa para **iniciar apenas a fase acordada**; separar liberação futura de testes e implantação | G05/G06, CORE10, governança global e responsável pelo projeto | **PENDENTE** |

**Nenhum item está marcado como FECHADO.** O fechamento posterior requer fontes, versão, prova documental, aceitação e resolução das dependências, não apenas quantidade de arquivos ou histórico parcial.

## 3. Estratégia sugerida de fechamento agrupado (sem novas aprovações por esta minuta)

| Ordem documental proposta | Pacotes coordenados | Marco verificável | Observação |
|---|---|---|---|
| **1 — Base de dados e identidade** | CORE01-D07, CORE02/04/07, CORE03 e SEG-01 | Matriz tenant×recurso×ação×campo; RLS, integridade, MFA/sessões, convite/assento e revogação especificados de forma coerente | Não criar DDL/SQL; CORE01-D07 já existe como minuta pendente |
| **2 — Segurança, credenciais, arquitetura** | CORE09 + SEG-01/02 + ARQ-01/02/03 + AGT-01 | Política de identidades técnicas, ferramentas, cofre, continuidade, topologia/custos, risco e evidências | Sem contratações, provisionamento ou token real |
| **3 — Comercial e escopo v1** | CORE08, FIN/CON, REV02, Portal Sites, DAT/ATD/VEN/AGD/AUT/IA | Matriz funcional de recursos e assinaturas por fase, preço/benefício/retentão e dependência entre produtos | Não inventar preços/quotas; revisão comercial agrupada |
| **4 — INT e governança** | INT-01..12, P0-DOC/TR/OWN/CON/SEC/DEP/VER/XREF/ACC, CORE03/10 | Contratos, owners, fontes primárias, versões e testes de homologação futura prontos no papel; G01..G04 resolvidos conforme critérios próprios | **HT01..18 continuam NÃO EXECUTADOS** |
| **5 — Liberação final** | P0-GIT, G05/G06, CORE10, REV/IN e aprovação global | Revisão final, SHA/tag, aceite expresso, escopo v1 e GO formal | Separar GO para código de autorização de produção |

A ordem permite revisões cruzadas, e cada P0 mantém seu ID original. Não inventar percentual ou prazo de conclusão a partir da contagem de arquivos/decisões.

## 4. Critério de faseamento de desenvolvimento — proposta, não decisão

O desenvolvimento **poderá ser organizado** por entregas, após **o planejamento global ou o recorte de lançamento formalmente aprovado conforme a política vigente**. No regime atualmente decidido, **o planejamento completo deve ser fechado antes de iniciar código**; uma eventual exceção que permitisse começar apenas com planejamento parcial exigiria **alteração explícita e separada dessa regra**.

| Sequência candidata para programação APÓS GO | Escopo indicado, a confirmar | Não significa |
|---|---|---|
| **Fase 1** | Fundação Laravel/PostgreSQL, IAM, multiempresa, RLS, permissões, sessão, auditoria, filas, infraestrutura de homologação | Criar serviços reais hoje |
| **Fase 2** | CRM essencial: entidades, leads/contatos, funis/tarefas, importação/exportação, perfis conforme plano | Lançar sem direitos/isolamento |
| **Fase 3** | Assinaturas/planos/trial/cortesia e Portal Sites, com regras financeiras homologadas | Cobrar sem contratação e testes |
| **Fase 4** | Atendimento, agenda, automações, integrações e canais | Colocar integrações sem guardrails |
| **Fase 5** | Agentes IA avançados, copilotos, previsões, otimizações e relatórios | Direito de agente executar por prompt |

**Os nomes de fases são apenas ordenação de execução.** As funcionalidades a desenvolver e os módulos dependentes do MVP/v1 devem ser fixados em documento próprio e aprovados. Para toda fase futura: critérios de aceite, dados de teste fictícios, homologação real após código, autorização de produção independente.

## 5. Itens de maior dependência — onde ganhar eficiência

1. **CORE01-D07 ↔ CORE02 ↔ CORE04 ↔ CORE07 ↔ SEG-01/02:** um pacote de matriz única de identidade, tenant, perfil, RLS e revogação resolve interfaces de várias decisões, preservando aceites individuais de cada ID.
2. **CORE03 ↔ ARQ-02 ↔ INT P0-OWN/CON/SEC ↔ IN127-R01..R06:** contrato de mensagens, efeito oficial e reconciliação sem duplicar motores ou alterar prazos já ratificados.
3. **CORE08 ↔ FIN/CON ↔ REV02 ↔ Site/Portal:** catálogo de direitos, estados financeiros e contratação por tenant; sem mesclar faturas, quotas ou dados.
4. **CORE09 ↔ SEG-01/02 ↔ AGT-01 ↔ CORE10:** identidade técnica, cofre, limites de agentes, supervisão e evidências de homologação sem instalar sistemas.
5. **REV/IN ↔ INT G01–G06 ↔ CORE10:** fonte verificável e contratos finais sem transformar checklist parcial em autorização automática.

## 6. Registro final e controle de mudanças

- **Situação atual:** NO-GO para desenvolvimento; sem prazo prometido e sem percentual artificioso.
- **Validade:** checklist é uma **minuta** de organização; a sua existência não conclui CORE, REV, IN, FIN, INT ou G01..06.
- **Para atualizar “ABERTO → FECHADO”**: apontar o texto aprovado, o ato expresso, as dependências verificadas, a evidência/documento definitivo, revisão por proprietário competente e SHA. P0 sem dependência completa continua aberto.
- **Para diferir item**: apenas decisão expressa de escopo/fase e avaliação de segurança, integrações, custos e impacto; P0 que bloqueia o núcleo não pode ser implicitamente reclassificado.
- **Para autorizar programação**: submeter baseline final, escopo da Fase 1, matriz de não-bloqueios, evidências de G05/G06 e solicitar **GO textual e expresso** do responsável. Depois, durante implementação, executar testes previstos e produzir evidência; produção exige outro aceite.
- **Proibições vigentes:** não iniciar programação/Codex, SQL, testes, provisionamento, compra, credenciais, integrações, alteração de dados, implantação ou pagamento.

## 7. Anexo de conciliação CORE01-D07 (04/11/2026)

[**CORE01-D07 — matriz de conciliação de entidades, integridade, RLS e contratos**](CRM-CORE-P0-ETAPA2-CORE01-D07-matriz-conciliacao-dependencias-v0.1.md) foi preparada apenas como **anexo técnico documental**, com rastreamento de dependências CORE02/03/04/05/06/07/08/09, SEG-01/02, INT/FIN/CON e critérios de verificação futura. O documento **não aprova** CORE01-D07-A/B/C/D nem reduz os 67 IDs em aberto. DEV-GATE-00 continua **MINUTA e NO-GO**; não há autorização de programação, testes, migrações ou implantação.

## 8. Atualização de estado CORE01-D07 — 04/11/2026

[**CORE01-D07-A/B/C/D aceitas preliminarmente**](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md). **CORE01-D07 ainda aberta** até especificações físicas, contratos, segurança, fontes e owners. Este checklist continua **NO-GO**, os **67 IDs CORE estão abertos**, SEG-01/02, IN-168, INT P0/G01..G06 e decisão final global permanecem pendentes. O “ok” de continuidade documental não é aprovação de código; nenhum teste ou programação foi autorizado.


## 7. Consolidação Etapa 2 R01 e aprovação preliminar D07 — 04/11/2026

[**CORE01-D07-A/B/C/D**](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md) foram **aprovadas somente como diretrizes técnicas documentais preliminares**, sem especificação física aprovada. A [**Etapa 2 — R01**](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md) consolida doze interfaces de contrato, responsáveis por competência e caminho documental de fechamento, sem criar novos P0, gates ou serviços. Todos **67 IDs CORE técnicos permanecem abertos** e IN-168 ARQ/SEG/AGT, INT P0/G01..G06, FIN/CON, LGPD, custeio/licenças e revisão global continuam pendentes. **DEV-GATE-00 permanece NO-GO**, sem programação, testes executáveis, implantação ou autorização de produção.


## 8. CORE02-D04 aceito preliminarmente e D05 pendente (04/11/2026)

[**CORE02-D04-A/B/C/D**](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md) foram aprovadas apenas como diretrizes documentais preliminares. **CORE02-D04 permanece ABERTA** por catálogo concreto e direitos por módulo. [**CORE02-D05**](CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md) foi apenas proposta, sem aceite. Os 67 IDs CORE, SEG-01/02, ARQ/AGT, INT P0/G01..G06 e demais blocos continuam abertos. **DEV-GATE-00 continua NO-GO para iniciar programação, testes executáveis ou implantação**.


## 9. Decisão posterior — GO restrito F0 e retenção dos gates críticos (04/11/2026)

Por [**DEC-FASTTRACK-01**](CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md), a ordem anterior de não programar fica **superada exclusivamente para desenvolvimento inicial F0 isolado**. Criada a branch [**dev/fast-track-crm-2026-11-04**](https://github.com/rafhael100/imagine-crm/tree/dev/fast-track-crm-2026-11-04) com esqueleto PHP, dependência Laravel 13 e contratos seguros que **negam todas as operações por padrão**.

**O checklist técnico A–H NÃO foi declarado fechado.** Os **67 IDs CORE técnicos**, SEG-01/02, ARQ-01/02/03, AGT-01, INT P0/G01..G06, FIN/CON, LGPD, custos e demais riscos permanecem abertos **quanto à solução/evidência**. O responsável autorizou seu **tratamento por engenharia e PRs revisáveis**, sem exigir rodada de aprovação de cada minuta antes de escrever qualquer código.

**GO apenas F0:** estrutura de projeto, classes sem privilégio, protótipos em branch isolada e revisão de contratos. **NO-GO:** produção, uso de dados reais, migrações em ambiente real, concessão de permissões, pagamentos, envios externos, provisionamento, compras, merge de funcionalidades sensíveis e liberação ao cliente sem especificações verificadas, testes documentados e autorização própria. 

## 10. Aceleração por delegação técnica e faixas — 04/11/2026

O responsável solicitou que a avaliação das aprovações remanescentes fosse delegada, para acelerar o desenvolvimento. As seguintes decisões **restritas e rastreáveis** foram registradas: [CORE02-D06-A/B/C/D como diretrizes preliminares](CRM-DEC-aprovacao-delegada-CORE02-D06-ABCD-preliminar-2026-11-04.md), [política-base de composição CORE02-D05 P1+P2 escopada](CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md) e [plano de aceleração](CRM-DEC-ACELERACAO-FASEAMENTO-APROVACOES-DELEGADAS-v0.1.md). A aprovação delegada não substitui especificações, P0 nem fontes oficiais.

**GO permanece NÃO concedido.** Uma FASE 1 isolada de desenvolvimento só pode ser formalmente autorizada após fechar **os bloqueios aplicáveis a esse escopo**, com evidência documental de IAM/RLS e SEG-01/02, arquitetura mínima, contratos CORE/INT relevantes, critérios de homologação futura, versão SHA e aceite formal. Produção/integrações reais exigem avaliação posterior específica. **67 IDs CORE e G01..G06 permanecem abertos; não houve código ou testes.**
