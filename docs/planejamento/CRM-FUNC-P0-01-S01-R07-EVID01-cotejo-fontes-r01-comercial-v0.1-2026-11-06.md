# FUNC-P0-01/S01-R07 — EVID01: cotejo real de fontes R01, transições comerciais e prontidão (v0.1)

**Data:** 06/11/2026. **Natureza:** EVIDÊNCIA DOCUMENTAL OBSERVADA; não é nova ratificação, alteração da decisão nem homologação de contrato.  
**Antecedente:** [DEC R07-D01..D08-A](CRM-DEC-FUNC-P0-01-S01-R07-D01-D08-alternativas-A-2026-11-06.md) ratificou somente diretrizes preliminares.  
**Procedimento:** leitura direta via integração GitHub de arquivos com `path`, `branch`, `blob SHA` e títulos abaixo. **Não houve execução de código, testes, CI ou acesso à VPS**. A existência de documentos não prova a existência, correção ou implementação de regras de runtime.

## 1. GAP-01: a pauta literal original R01 **FOI LOCALIZADA**

**Fonte canônica recuperada:** [FUNC-P0-01/S01-R01 — Campos, estados e segurança (proposta v0.1)](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md), branch `dev/fast-track-crm-2026-11-04`, blob SHA `36a353caf695aa03aea3b0d6e0e13331a1fda5b3`, **seção 6, linhas 128–140**.  
**Fonte auxiliar:** [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22), que aponta explicitamente a pauta R01 como proposta; e [registro das primeiras diretrizes S01-D01..D08](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-DEC-ratificacao-diretrizes-preliminares-D01-D08-2026-11-06.md), blob `9cd1ae39c3060e9b2762f25f14226decb3f0e2ff`.

| ID R01 | Texto/alcance da alternativa A encontrado na fonte original | Correspondência com decisão R01 |
|---|---|---|
| **R01-D01 Visibilidade** | Mostrar somente **CAM-01** (nome de apresentação da organização) e **CAM-02** (rótulo do próprio vínculo ativo); não exibir CAM-03..10 | A escolha **A** já foi registrada na DEC R01, mas seu conteúdo literal anteriormente constava como não localizado |
| **R01-D02 Membership** | Somente **vínculo próprio ativo e comprovado** torna operação elegível; outros estados negam S01 | A escolha **A** não deve ser interpretada como autorização para expor status suspenso/revogado |
| **R01-D03 Estados da organização** | Separar **estado técnico da organização** e **estado comercial**, sem decidir o acesso final de estados ainda não homologados | Decisão comercial concreta permanece com CORE08/FIN/CON; não se confunde com membership |
| **R01-D04 Proteções** | Priorizar sessão verificada, tenant server-side, MFA por risco, RLS non-bypass, cache restrito e fail-closed; elaborar provas antes de código | SEG/CORE01/04/06 e definições físicas continuam pendentes |
| **R01-D05 Evidência** | Manter **S01-T01..T22** como matriz de aceitação **futura**; exigir owner, ambiente, SHA e risco por caso | **Nenhum** caso T01..T22 foi executado ou homologado nesta tarefa |

**Achado material:** a afirmação histórica “R01 não foi localizado em arquivo canônico” precisa ser **qualificada por esta nova evidência**. O registro DEC-R01 anterior deve ser lido com este EVID01; **não editar retroativamente a decisão assinada por contexto e não declarar homologação**. O documento R01 original na DEV conserva o status histórico de *proposta não aprovada* na época da criação, mas a escolha **A para os cinco IDs foi posteriormente ratificada como diretriz preliminar** na branch documental.  
**GAP-01:** subpendência de **localização da pauta resolvida por evidência**; **rastreabilidade consolidada e conferência de precedência ainda em revisão documental**. O gate de segurança e homologação **permanece aberto**.

## 2. GAP-05 e GAP-06: cotejo das fontes comerciais oficiais de planejamento

| Fonte lida em `main` | Blob SHA verificado | Achados funcionais documentados |
|---|---|---|
| [REV-02 — planos, trial, assinaturas e benefício Básico](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) | `dffa14e7f4e3e9557614f4522389430e42c30b43` | trial 14 dias e guarda 60; CRM pago 7 dias tolerância, cancelamento aos 30 dias de atraso e guarda 120 após cancelamento CRM; serviços Portal e CRM separados; **REV02-D01..D12/P01..P12 seguem abertos** |
| [IN-119 — gestão da cortesia](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-119-gestao-cortesia-crm-basico.md) | `014c00b8487b786a4a546c5edee8cbc30bfc1159` | último site elegível cancelado inicia transição de 30 dias; site adicional elegível preserva cortesia; complementos COM-08/09 distinguem suspensão por inadimplência de cancelamento |
| [IN-120 — fim da cortesia e continuidade](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-120-encerramento-cortesia-continuidade-conta.md) | `6494eb524aef751211241b6967853fd71184a4cf` | cancelamento comum: 30 dias com CRM normal; ao fim sem novo direito, acesso restrito e portabilidade; 120 dias contam do cancelamento **formal da própria conta CRM** |
| [COM-08 — inadimplência do site](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md) | `7f44060c8486356a46c3332682d630e29c49ee39` | sete dias de tolerância do site; ao suspender efetivamente o único site elegível, restringir operação da cortesia; não afetar CRM pago ou cortesia com outro sustentador; **marco jurídico/operacional de contagem e cancelamento do site ainda pendentes** |
| [COM-09 — transição por causa do cancelamento](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md) | `a8b6d8d71c0ae882cf4336326c0647aee34823e8` | cancelamento **comum** último site: 30 dias de operação normal; cancelamento **por inadimplência após suspensão**: 30 dias de acesso restrito; ambos desde **cancelamento formal do último vínculo**; não iniciar por atraso/suspensão |
| [IN-122 — múltiplos sites e contas](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-122-multiplos-sites-contas-crm-cortesias.md) | `0da1107aa505b4983a992b9acfaf96524685cdeb` | no máximo uma cortesia por organização, vários sites podem sustentar a mesma; novo destino CRM escolhido e troca por chamado; elegibilidade de ambas as contas reavaliada |
| [IN-123 — painel de integrações](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-123-painel-integracoes-sites-crm.md) | `17e5597845a59776676f782609ef2f389244a4bf` | Portal tem painel de status comercial e vínculo, distinto da **autoconsulta S01**; atualização de vínculo mediante chamado, fonte oficial e autorização ainda a especificar |
| [REV-01 — IAM multiempresa](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) | `ccaaeb52975d3c6861328a198523101071bb09dd` | entidade global/membership/tenant e RLS no escopo conceitual, sem permissão executável; admin Imagine não herda leitura de dados do cliente |

**Precedência documental verificável:** COM-08 e COM-09 são complementos expressamente posteriores incorporados em IN-119/120/122, não meras interpretações novas deste relatório. Portanto, diferenciar **suspensão de site**, **cancelamento formal de site** e **cancelamento formal CRM** já tem lastro documental.  
**Continua pendente:** origem/autenticidade de eventos físicos, relógios (dia útil/fuso/marcos), conciliação de pagamento e reativação, cláusulas contratuais, regras completas PSP/FIN/CON/LGPD e owner/aprovador nomeado. O fato de localizar o documento **não fecha GAP-05 nem GAP-06**.

## 3. Separação de fontes e riscos de transposição

- **Fonte de IAM (CORE/SEG)** prova *quem* é a pessoa, a organização corrente e o vínculo, sujeita a autorização por operação. Não inferir vínculo a partir de site ou plano pago.
- **FIN/CON CRM** prova o estado da assinatura CRM; **Portal** prova contrato de site e elegibilidade; **INT** apenas transporta eventos; nenhuma confirmação HTTP isolada comprova pagamento.
- **Autoconsulta S01** só poderá mostrar o nome da organização atual e o rótulo do vínculo **ativo e autorizado**, não estados comerciais nem informações de cobrança. A eventual interface do **Portal IN-123** é operação e produto distintos.
- **IN-092:** foi referido como fonte histórica no REV-02, mas o caminho tentado `docs/planejamento/IN-092-gestao-cortesia-crm-basico.md` retornou **404**. **Não** afirmar inexistência do documento em outras branches/nomes; localizar a fonte exata ainda está **PENDENTE**.
- **R01-T01..T22:** encontrados como matriz futura de aceite na documentação de S01; **sem execução ou resultado funcional**.
- **GAP-09 owners:** fonte R01 enumera **competências candidatas**, não pessoas nomeadas ou pareceres assinados. Falta nomeação/aceite especializado.

## 4. Resultado do cotejo por lacuna — sem baixa de gates

| GAP | Resultado evidencial desta rodada | Status de governança |
|---|---|---|
| **GAP-01** | Fonte literal original R01 e cinco alternativas A **encontradas** na DEV, SHA acima | Localização comprovada; **cotejo de precedência/fechamento ainda pendentes** |
| **GAP-02/03/04** | R01 e REV-01 reforçam requisitos IAM e negação; sem política homologada | **ABERTOS** |
| **GAP-05** | Separação temporal/comercial cotejada em REV-02, IN-119/120 e COM-08/09 | **ABERTO** — parâmetros/eventos físicos e IN-092 pendentes |
| **GAP-06** | IN-122/123 e COM-08/09 comprovam regras funcionais multi-site e transição | **ABERTO** — contrato de eventos e efeitos pendentes |
| **GAP-07** | Necessidade de conciliação e prevenção de autoconcessão verificada | **ABERTO** — sem algoritmo, owner ou aprovação |
| **GAP-08** | Categorias históricas de auditoria distinguíveis em REV-01 | **ABERTO** — LGPD/retencões ainda a homologar |
| **GAP-09** | Owners **candidatos** em R01/S01 e matrizes REV; nomeações não verificadas | **ABERTO** |
| **GAP-10** | Cenários S01-T01..T22 **encontrados em documento** | **ABERTO** — nenhum teste executado, nenhuma homologação |

## 5. Próximas atividades documentais com resultado verificável, sem solicitar aprovação genérica

1. Localizar **IN-092** na branch histórica correta ou registrar impossibilidade rastreável, sem criar arquivo fictício.
2. Preparar **quadro de eventos por contrato e organização** e evidências requeridas, confrontando FIN/CON, Portal e INT; escolher formalmente owner por domínio antes de aprovar o contrato.
3. Revisar classificação e exibição do CAM-01/CAM-02 frente à R01 original, sem permitir status inativos em S01.
4. Produzir **fichas individuais de evidência C01..C08**, com owner candidato, fonte/versão, cenários negativos, limitações e estado PENDENTE, sem marcar nenhuma prova executada.

**Restrições invariáveis:** **67 CORE**, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, DEV-GATE e **R02-C01..C08 continuam ABERTOS/PENDENTES**. Documento criado somente na branch documental isolada; `main`, PR #2/#21, branch DEV, VPS, CI, código, SQL, ALLOW, testes funcionais e produção fora do escopo.