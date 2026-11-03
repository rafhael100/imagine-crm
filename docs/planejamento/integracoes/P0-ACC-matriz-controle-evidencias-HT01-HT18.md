# P0-ACC — Controle de planos, responsáveis e evidências HT-01 a HT-18

**Data:** 2026-11-03.
**Estado:** MATRIZ **ACEITA COMO BASE DOCUMENTAL**, conforme [P0-DEC](P0-DEC-aprovacao-ACC-HT01-HT18-base.md); sem homologação ou testes. Não há execução, ambiente homologado, resultado PASS/FAIL, medição ou evidência técnica real.
**Cenários HT-01..18:** aprovados na [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
**Planos detalhados:** [P0-ACC — Procedimentos HT-01 a HT-18](P0-ACC-planos-homologacao-HT01-HT18.md) — **rascunhos para revisão**, não procedimentos finais homologados.
**Decisão autorizadora:** [P0-DEC — Aprovação SEC/VER como bases](P0-DEC-aprovacao-SEC-VER-base.md) autoriza somente desenvolver documentação, sem programar ou executar testes.

## 1. Matriz de rastreabilidade e responsabilidades (funções, não nomes designados)

| HT | Cenário | Dependências | Contratos | Invariantes | Evidência futura principal | Funções que deverão participar do aceite | Estado |
|---|---|---|---|---|---|---|---|
| HT-01 | Conexão autorizada | INT-01/02/04/11 | CT-01/02/03/04 | INV-01/02/03/10 | Conexão, identidade, versão e negação indevida | Autoridades de INT-01, INT-02 e Segurança | Minuta registrada / **não executado** |
| HT-02 | Revogação de credenciais | INT-01/02/10 | CT-02/04/09 | INV-03/04/08/12 | Revalidação, revogação, preservação de pendências | Segurança, INT-02 e INT-10 | Minuta registrada / **não executado** |
| HT-03 | Transformação de dados | INT-03/04 | CT-03/04/07 | INV-02/10/11/14 | Prévia, schema, versão, rejeições e ausência de escrita | INT-03 e proprietário dos dados | Minuta registrada / **não executado** |
| HT-04 | API no trial | INT-05/Segurança | CT-02/03/04 | INV-01/03/16 | Bloqueio API pública e preservação da nativa | Gestão de planos/INT-05 e Segurança | Minuta registrada / **não executado** |
| HT-05 | Webhook duplicado | INT-06/Eventos | CT-04/05 | INV-05/07/14 | Assinatura, event ID, ordem, deduplicação, proprietário | INT-06, Eventos e proprietário | Minuta registrada / **não executado** |
| HT-06 | Sync interrompida | INT-07/10 | CT-02/04/06/09 | INV-03/07/08/10 | Checkpoint, cursor, efeitos, retomada, autorização | INT-07, INT-10 e proprietário | Minuta registrada / **não executado** |
| HT-07 | Lote parcialmente válido | INT-03/08/IN-118 | CT-02/04/07 | INV-02/07/15/16 | Manifesto, prévia, registros aceitos/rejeitados e plano | INT-08, Portabilidade e proprietário | Minuta registrada / **não executado** |
| HT-08 | Escrita inconclusiva | INT-04/09/10 | CT-03/04/09/11 | INV-05/06/07/08 | Command/attempt IDs, consulta e não duplicação | INT-04/09/10 e proprietário | Minuta registrada / **não executado** |
| HT-09 | Paralelismo/convergência | INT-09/Eventos | CT-02/03/04/08 | INV-02/04/05/07/10 | Dependências, etapas, aprovações, confirmações | INT-09, Aprovações e proprietários | Minuta registrada / **não executado** |
| HT-10 | Compensação parcial | INT-09/10/Proprietários | CT-02/04/08/10 | INV-02/04/08/09 | Reversibilidade, autorização, efeitos e pendências | Módulo de negócio, INT-09 e Aprovações | Minuta registrada / **não executado** |
| HT-11 | Provedor indisponível | INT-01/09/10 | CT-01/04/09 | INV-03/05/08/13/14 | Telemetria, diagnóstico, playbook e pendências | INT-10, Observabilidade e proprietário | Minuta registrada / **não executado** |
| HT-12 | Versão incompatível | INT-03/04/09/11 | CT-01/03/04/08 | INV-03/10/11/14 | Matriz V-C, pin, homologação e não migração | INT-11, INT-09 e proprietários | Minuta registrada / **não executado** |
| HT-13 | Cross-tenant | Todas/Segurança | CT-01..11 | INV-01/02/03/11/12/14 | Negativas de permissão, ausência de vazamento | Identidade e Segurança com proprietários | Minuta registrada / **não executado** |
| HT-14 | Auditoria integral | Todas/Auditoria Geral | CT-03/04/11 | INV-05/07/14/15 | Proveniência, integridade, correlação e lacunas | Auditoria Geral e INT-12 | Minuta registrada / **não executado** |
| HT-15 | IA e ação crítica | INT-09..12/Aprovações | CT-02/03/09/10 | INV-03/04/09/11/12/13 | Decisões humanas, bloqueio IA, ação N2/N3 | Segurança, Aprovações e proprietário | Minuta registrada / **não executado** |
| HT-16 | Falha de filas/eventos | INT-06..10/Filas | CT-04/05/06/08/09 | INV-05/06/07/08/14 | Filas/ack, tentativas, estado, recuperação | Filas, Eventos e INT-10 | Minuta registrada / **não executado** |
| HT-17 | Exportação de evidências | INT-12/Auditoria Geral | CT-02/07/11 | INV-01/03/14/15/16 | Acesso, manifesto, integridade, auditoria do acesso | Auditoria Geral, Segurança e INT-12 | Minuta registrada / **não executado** |
| HT-18 | Conector suspenso | INT-01/02/10/11 | CT-01/02/04/09 | INV-03/05/08/10/13 | Versão/suspensão, impacto, reconciliação | INT-11, Segurança e INT-10 | Minuta registrada / **não executado** |

**Nota:** “funções que deverão participar” não nomeia pessoas, cria cargos, concessões ou alçadas. A lista depende de revisão da competência de cada módulo, da estrutura das equipes e das regras já aprovadas.

## 2. Campos obrigatórios sugeridos ao registro futuro de homologação

| Campo do controle | Condição atual |
|---|---|
| ID e versão do procedimento | Definidos como IDs HT-01..18 e plano documental inicial |
| Fontes da aprovação original/TR completos | Parcial; P0-DOC-01/P0-TR-01 pendentes |
| Responsável por cada execução | Não designado |
| Aprovador documental e aprovador técnico por competência | Não designados |
| Ambiente, acesso e isolation/segregação | Pendente de especificação; nenhum ambiente utilizado |
| Identidades/tenant de teste e dados sintéticos | Roteiros propostos; nenhuma conta/dado real criado |
| Versões de contrato, conector, schema e modelo | A determinar por cenário e provedores homologados |
| Entradas, estados iniciais, falhas simuladas | Descritos como roteiro, sujeitos a validação |
| Medições, logs, trilhas e evidências | **Não coletadas** |
| Desvios/defeitos/tickets | **Nenhum registrado como resultado de teste** |
| Decisão PASS/FAIL/BLOQUEADO ou dispensa formal | **Não aplicável antes da execução** |
| Assinatura, data, versão final e aceitação técnica | **Pendentes** |

## 3. Critérios de aprovação documental P0-ACC-01

A etapa de escrita de planos só será declarada **documentalmente validada** depois de:
1. Conferir a origem literal de todos os requisitos aprovados e classificar lacunas sem suprimir decisões.
2. Aprovar critérios, passos, precondições e aceites de cada HT pelo proprietário e por Segurança/Auditoria quando aplicável.
3. Completar identificadores, contratos, versões e matriz de cobertura TR/INV/OP por cenário.
4. Definir futuramente ambientes isolados, dados sintéticos, ferramentas homologadas, pessoas autorizadas e coleta de evidências.
5. Registrar aceite expresso da versão consolidada e sua referência no GitHub, sem confundir isso com teste executado.

**P0-ACC-01 permanece ABERTO/PARCIAL.** Uma aprovação apenas documental não muda o estado “não executado” dos 18 testes. Testes futuros só serão realizados após a autorização de iniciar a fase de implementação/homologação e mediante controles próprios.

## 4. Critérios de falha técnica futuros (não testados)

| Tipo de risco | Resultado que deverá bloquear aprovação técnica |
|---|---|
| Segurança | Acesso cross-tenant ou execução sem autorização válida |
| Dados | Perda silenciosa, deduplicação destrutiva, exclusão ou escrita indevida |
| Negócio | Aceite HTTP/externo contabilizado como fato oficial não confirmado |
| Consistência | Repetição de escrita inconclusiva que produz efeito duplicado |
| Versionamento | Alteração silenciosa de schema/conector/modelo em instância ativa |
| IA | Execução de ação crítica só por recomendação de modelo ou supervisor |
| Evidência | Ausência da referência oficial exigível, proveniência incoerente ou trilha concorrente |
| Continuidade | Retomada sem checkpoint confiável, reconciliação ou autorização pertinente |
| Portabilidade | Bloqueio do autoatendimento essencial aprovado ou migração de segredo indevida |

**Regra de saída:** documentar e versionar não é homologar. Nenhum script/teste foi executado e nenhum resultado PASS/FAIL é afirmado por esta matriz.
