# CRM Imagine — CORE-P0 Etapa 2 / R01: Consolidação transversal de contratos e dependências técnicas (v0.1)

**Data:** 04/11/2026.
**Estado:** **CONSOLIDAÇÃO DOCUMENTAL DE TRABALHO, NÃO HOMOLOGADA; NENHUM P0 OU GATE ENCERRADO**.
**Autorização:** aprovação expressa das subdiretrizes [CORE01-D07-A/B/C/D](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md) e ordem de **consolidar as pendências técnicas da Etapa 2 sem programação, testes ou implantação**.

**Natureza:** índice de dependências e plano de revisão. Os códigos **E2-X01..E2-X12 são localizadores de contratos comuns**, não criam novos módulos, P0, decisões aprovadas, atribuições de cargo, preço, ferramentas ou motores.

## 1. Linha de base e estado real

| Conjunto | Aceite existente | Ainda bloqueado |
|---|---|---|
| **CORE01-D01..D03** | Base lógica aprovada | Especificação executável do CORE01 |
| **CORE01-D04-A/B/C** | Diretrizes técnicas documentais preliminares aprovadas | **CORE01-D04 aberta** até CORE02/04/07/08 |
| **CORE01-D05-A/B/C/D** | Diretrizes técnicas documentais preliminares aprovadas | **CORE01-D05 aberta** até CORE02/03/04/05/09 e SEG-01/02 |
| **CORE01-D06-A/B/C/D** | Diretrizes técnicas documentais preliminares aprovadas | **CORE01-D06 aberta** até CORE08, FIN/CON, Portal/Sites, INT e Segurança |
| **CORE01-D07-A/B/C/D** | **Diretrizes técnicas documentais preliminares aprovadas** ([decisão](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md)) | **CORE01-D07 aberta** até especificações físicas, contratos CORE02..09, SEG-01/02, INT e Segurança |
| **CORE02..CORE10-D01..D03** | Bases documentais aprovadas | **CORE02..CORE10-D04..D10 ainda abertos**, 9×7=63 |
| **Etapa 2 — total** | 10 conjuntos de princípios de CORE + subdiretrizes preliminares | **67 identificadores D04..D10/D04..D07 ABERTOS**, não são 67 riscos nem 67 implementações |
| **IN-168 e INT** | Diretrizes ARQ e CT/HT parcialmente aceitas | **ARQ-01/02/03, SEG-01/02, AGT-01** P0; INT P0-DOC/TR/OWN/CON/SEC/DEP/VER/ACC/XREF/GIT; G01..G06 não concluídos; **18 roteiros HT documentados, 0 executados** |
| **Comercial, jurídico e operação** | Regras de Site↔CRM, trial, cortesia, FIN, gratuidades, portabilidade já normatizadas em partes | Contratos e precedência financeira, LGPD, preços/quota finais, RTO/RPO, cofre/licenças, custeio, aceite global |

Fontes de consulta: [matriz individual de 67 IDs](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md); [Painel CORE Etapa 1](CRM-CORE-P0-ETAPA1-painel-fechamento.md); [CORE01 D04..D07](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md); [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md); [painel INT G01..G06](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md); [DEV-GATE-00 — minuta NO-GO](CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md).

## 2. Mapa consolidado de contratos compartilhados — todos ABERTOS

**Observação:** "competência revisora" indica domínio responsável por validar a matéria, **não** pessoa designada oficialmente nem nova alçada autorizada. "Evidência mínima" é **DOCUMENTO/contrato planejado**, nunca teste executado.

| Ref | Contrato comum faltante | Cruzamentos diretamente bloqueados | Competências revisoras propostas | Evidência documental antes de fechar |
|---|---|---|---|---|
| **E2-X01** | Identidade global × tenant × membership: unicidade, estados, convite versus grant, admin único, múltiplas empresas e revogação | CORE01-D04/D07; CORE02-D05; CORE04-D05/D07; CORE07-D04/D05/D06/D08; CORE08-D09 | IAM/Segurança; gestão de usuários e planos | Dicionário conceitual + regras de estados/concorrência/capacidade por tenant + negativas, conflitos de grants e sucessão |
| **E2-X02** | Matriz canônica ator×recurso×ação×campo×tenant×plano, perfis e atributos; propriedade/consentimento; inclusive exportação | CORE01-D04/D05/D07; **CORE02-D04..D10**; CORE05/06/07/08; INT P0-OWN/SEC; SEG-01 | Segurança/IAM + donos DAT/VEN/ATD/AGD/AUT/IA/FIN/INT | Catálogo por domínio, classificação de campo, política de mascaramento, precedência entre perfis e ações negativas |
| **E2-X03** | Modelo relacional/ER por classe global, tenant-bound e integração; PK/FK/constraints contextuais, indexação, soft-delete e histórico | **CORE01-D07**; CORE07-D04/D05; CORE08-D04/D06; CORE06; LEG/FIN/CON | Arquitetura de dados + owners de módulos + LGPD | Mapa ER/contrato de integridade por entidade com relações A↔B proibidas e estratégia física **ainda a decidir** |
| **E2-X04** | Contexto seguro Laravel→PostgreSQL RLS, roles e operações SQL, transações, pools, cache, workers e exceções legítimas | CORE01-D05/D07; CORE02-D08; CORE03-D06; CORE05; CORE09; **SEG-01/02** | Backend/DBA/Segurança + arquitetura | Matriz por tabela e operação `SELECT/INSERT/UPDATE/DELETE`, `USING/WITH CHECK`, owner/bypass, contexto e reset/retry; sem SQL em produção |
| **E2-X05** | Autenticação humana, MFA, sessão, step-up, convite e mandato temporário de suporte, com auditabilidade | CORE01-D04/D05; CORE04-D04..D10; CORE05-D04..D10; CORE07-D04/D06/D08 | IAM/Segurança + atendimento/suporte | Fluxos por papel, estados/expiração, provas de recuperação e revogação por tenant, separação de support mandate |
| **E2-X06** | Envelopes de comandos/eventos, ator causal vs executor, versão, assinatura/origem, idempotência, fila, retry/DLQ e resultado oficial | CORE01-D05/D06/D07; CORE03-D04..D10; CORE08-D07; INT P0-CON/OWN/SEC/VER; ARQ-02 | Integrações/Arquitetura + donos de efeitos (CRM, Portal, FIN) | Contratos CT e estados por operação, autenticação, reconciliação, confirmação por owner; **não** assumir exactly-once global |
| **E2-X07** | Dados de Site↔CRM, vínculo/autorização do destino, titularidade, leads pendentes, API principal/contingência, captação/descarte | CORE01-D06/D07; CORE03-D08; CORE08-D04/D06/D07; IN127-R01..R06; INT/ARQ-02 | Portal/Sites + CRM + Segurança + Privacidade | Prova de contrato/destino, T0 durável e janela específica 72h+360h, não redirecionamento implícito, não captar sem CRM autorizado |
| **E2-X08** | Planos/benefícios e ciclos financeiros: trial por CNPJ, Básico público/cortesia, gratuidade admin, assinaturas pagas e quotas | CORE01-D06/D07; CORE07-D05; CORE08-D04..D09; FIN/CON; GRAT-P; REV02 | Financeiro/Comercial + CRM/Portal | Matriz fonte de verdade, precedência, cronologia e confirmação financeira, cancelamento e retenção por objeto, custos e aceite comercial |
| **E2-X09** | Cofre e identidade técnica de serviços, agentes, n8n, provedores e autorização independente por risco/ambiente/tenant | CORE01-D05/D07; CORE02-D08; CORE03-D04; CORE09-D04..D10; **SEG-01/02; AGT-01** | Segurança/IAM + Infra + Integrações | Matriz ferramenta×ator×ação×tenant×risco, segredo por serviço e ambiente, rotação/revogação/contingência, fornecedor/custo sujeitos a aceite |
| **E2-X10** | Auditoria Geral, classificação/retentão de evidências, LGPD, portabilidade, backups, restauração e expurgo | CORE01-D07; CORE05/06; CORE08-D08; CORE09-D09; FIN/CON; IN-168 | Auditoria/Segurança/Privacidade + operações | Mapa de classes com owners, eventos/PII permitidos, retenções por categoria, efeitos de restore e consulta/export controladas |
| **E2-X11** | Infraestrutura, continuidade, telemetria independente e limites de falha para APIs, CRM, Portal, agentes e filas | CORE03-D07/D09; CORE09-D04/D08; IN-168 ARQ-01/02/03, AGT-01, CON/OBS P1 quando impeditivos | Infra/Operações/Segurança/Financeiro | Topologia/capacidade por serviço, dependências, opções de cofre, contingência, estimativas/cotações e aprovações de custo; nenhuma compra |
| **E2-X12** | Homologação documental, versões, matriz de cenários negativos, fontes primárias, exceções, owners e gates de decisão | CORE01-D07; CORE02..10-D10; INT P0-DOC/TR/DEP/ACC/GIT; G01..G06; DEV-GATE-00 | Arquitetura documental + donos de módulos + responsável por aceite | Um catálogo de riscos/roteiros canônico, registros de conflitos e resoluções, baseline final SHA/tag e **GO expresso só ao final** |

**Princípio de não duplicação:** E2-X01..12 **não substituem IDs CORE** nem criam 12 serviços; cada decisão D04..D10 continua rastreada individualmente. Um mesmo contrato validado por vários donos pode satisfazer partes de várias pendências, **sem encerramento automático**.

## 3. Ordem proposta de resolução por dependência — não é aprovação de soluções

| Onda documental | Condição de entrada e foco | Resultado que precisará ser deliberado | Por que esta ordem |
|---|---|---|---|
| **1 — direitos e dados** | E2-X01/X02/X03: **CORE02-D04/D05/D08**, CORE07-D04/D05, CORE01-D04/D07; classificação global/tenant/campo | Proposta de classificação e matriz de direitos por módulo, precedência de perfis, entidades/constrains candidatos, exceções de acesso | Antes de definir policy RLS é preciso saber o que cada ator/serviço pode efetivamente acessar; D07 preliminar não decidiu grants |
| **2 — contexto, sessão e serviços** | E2-X04/X05/X09: CORE01-D05/D07; CORE03-D06; CORE04/05; CORE09; **SEG-01/02** | Alternativas físicas de isolamento/RLS, autenticação por papel e serviço, cofre, autorização, expiração e revogação, com riscos | Evita criar bypass por roles de aplicação, suporte ou IA e reuso inseguro de pools |
| **3 — comandos, filas, vínculos e financeiros** | E2-X06/X07/X08: CORE03, CORE08-D04..D09, CORE01-D06; INT CT, Portal/Sites, FIN/CON | Contratos tipados, estados oficiais, idempotência contextual, confirmação pelo módulo dono e regras de cobrança/portabilidade | Não misturar transporte/ACK com resultado de negócio e não criar benefícios/cobranças implícitos |
| **4 — auditoria, resiliência e critérios** | E2-X10/X11/X12: CORE06, CORE09, CORE10, ARQ/AGT, INT e DEV-GATE-00 | Critérios de retenção, recuperação, topologia, custos, evidências futuras e responsável/versão | Protege continuidade e proveniência sem executar testes prematuramente |
| **5 — reconciliação cruzada e aceite final** | Todos D04..D10 especificados/aceitos pelos donos, IN-168/INT/FIN/LGPD reconciliados | Comprovar requisitos documentais G01..G06 e baseline aprovada; requer **aceite global expresso** | Sem fechamento total e autorização textual, projeto permanece NO-GO para programação |

**Cuidado:** a ordem é de **consolidação das decisões documentais**. Dependências podem tramitar conjuntamente e revisões comerciais/infra têm seus próprios owners; não esperar conclusão física de todo o software para elaborar documentação, nem declarar pronta uma especificação sem resolver conflitos.

## 4. Próxima frente objetiva recomendada — CORE02-D04, NÃO APROVADA

O maior contrato compartilhado imediato é **E2-X02 (classificação de campos e direitos)**. A [CORE-P0-02, seção 6](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) já propõe **F-ID, F-BIZ, F-PII, F-RES, F-CONS, F-FIN, F-SEC, F-EVID e F-ART**, mas **CORE02-D04 continua ABERTA**. Proposta de próxima revisão, sem voto implícito:

- definir classes e critérios de máscara, leitura, edição, busca, IA, exportação, logs e anexos por **módulo proprietário**; classificação de campos personalizados com validação por cliente/administrador autorizado;
- confirmar direitos efetivos por papel, tenant, recurso, operação e finalidade; não interpretar uma classe como grant, nem um campo visível como automaticamente exportável;
- preservar **F-SEC fora de exportações de negócio** e segredos em cofre, F-EVID com consulta restrita e direito unificado ver/baixar anexos já definido;
- **não decidir por CORE02-D04 isolada** precedência de múltiplos perfis (**D05**), catálogo completo de ações de módulos (**D06**), MFA/alçadas (**D07**) ou roles/RLS físicas (**D08**).

Uma futura aprovação de princípios CORE02-D04 não encerrará D04 sem catálogo real por módulo, classificação de campos e contratos CORE01/SEG/INT, e nem fechará CORE01-D04/D07.

**Minuta especializada preparada:** [**CORE02-D04 — classificação de campos e direitos por operação (v0.1)**](CRM-CORE-P0-ETAPA2-CORE02-D04-classificacao-campos-direitos-proposta-v0.1.md), com propostas A/B/C/D **ainda não aprovadas**. Essa publicação **não fecha CORE02-D04** e não aprova direitos de acesso, campos físicos, políticas RLS ou execução.

## 5. Registro de evidências e política de status

Para cada decisão de cada pacote, manter **fonte e link/commit**, texto normativo/precedência, dono por competência, estado **subdiretriz preliminar → especificação pendente → decisão técnica aprovada por owner → fechamento documental auditável**, dependências bloqueantes, condições negativas, versões, custos e lacunas. **Não** atribuir “homologado” a documento sem execução permitida. INT HT-01..18 **0 executados**.

**Status preservados:** CORE01-D04..D07 e CORE02..CORE10-D04..D10 — **67 ABERTOS**. ARQ-01/02/03, SEG-01/02, AGT-01, FIN/CON, INT P0, G01..G06, LGPD, licenças, preços finais, custos e revisão global — **ABERTOS**. DEV-GATE-00 — **NO-GO**.

**Nenhuma programação, teste executável, SQL/migração, provisionamento, alterações em produção, tratamento de dados reais, mudança de contrato, despesa ou implantação foi autorizada ou realizada.**

## 6. Avanço documental CORE02-D04 — inventário, classificação e exposição de campos (04/11/2026)

A [**minuta CORE02-D04 de classificação de campos e visibilidade por operação**](CRM-CORE-P0-ETAPA2-CORE02-D04-classificacao-campos-visibilidade-proposta-v0.1.md) detalha o contrato E2-X02 em quatro propostas **D04-A/B/C/D ainda NÃO APROVADAS**: dicionário/proprietário/classificação; exposição por ação/canal/escopo; campos personalizados/derivados/exportação; evidências negativas/segregação. **CORE02-D04..D10 permanecem ABERTAS** e nenhuma política física ou permissão nova foi autorizada. A continuação documental foi autorizada; não houve homologação, teste ou programação. CORE01-D04..D07, 67 IDs CORE, SEG-01/02, INT P0/G01..G06 e DEV-GATE-00 NO-GO permanecem inalterados.
