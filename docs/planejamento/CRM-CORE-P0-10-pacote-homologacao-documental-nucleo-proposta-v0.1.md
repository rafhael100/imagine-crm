# CRM-CORE-P0-10 — Pacote documental de homologação do núcleo (proposta v0.1)

**Data:** 04/11/2026.  
**Estado:** **MINUTA DOCUMENTAL NÃO APROVADA — CORE-P0-10 ABERTO. CORE10-D01, D02 e D03 submetidas a decisão; D04–D10 ficam abertas.**  
**Escopo:** consolidação de critérios e dependências da Etapa 1 do CORE Imagine, não encerramento dos P0 anteriores e não criação de um módulo novo de execução de testes.  
**Fase:** somente planejamento/documentação GitHub, rastreabilidade e preparação de evidências. **Nenhuma programação, Codex, infraestrutura, teste real, schema, credencial, conta, contratação, alteração de dados de clientes, pagamento, deploy, compra ou homologação física está autorizada.**

## 1. Objetivo, precedências e regra de não duplicação

O CORE-P0-10 organiza o **pacote de homologação a ser utilizado futuramente** para verificar os controles do núcleo, cruzando bases funcionais aprovadas com as pendências executáveis ainda abertas. A aceitação de sua matriz **não equivale a aprovar o sistema para programação** nem significa que um teste já foi executado.

Referências:
- [CRM-REV-01 — núcleo e lacunas CORE-P0-01..10](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md), princípios N-01..13, casos documentais S01..S10.
- [Painel de fechamento do CORE](CRM-CORE-P0-ETAPA1-painel-fechamento.md).
- **CORE-P0-01..09**, todos com **D01–D03 aprovadas como bases documentais** e **D04–D10 abertas** (CORE01 D04–D07); documentos e decisões individuais acessíveis pelo painel.
- [CRM-REV-09 — infraestrutura, segurança, auditoria e operações centrais](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md): OPS-P01..18, cenários OPS-T01..16; regras de prontidão documental e desenvolvimento posterior.
- [IN-168 — pendências críticas](IN-168-matriz-pendencias-fechamento-arquitetura.md): ARQ-01/02/03, SEG-01/02, AGT-01, sem homologação executável, custos ou infraestrutura aprovada.
- [INT P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) e [INT P0-ACC](integracoes/P0-ACC-planos-homologacao-HT01-HT18.md): G01..G06 incompletos; 18/18 roteiros HT aceitos como bases documentais; **0/18 executados**; P0-OWN/SEC/CON/ACC/DOC/TR/DEP/VER/XREF/GIT ainda exigem fechamento.
- [CORE-P0-03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md): eventos e jobs não são fonte de autoridade/resultado final.
- [IN127-R01..R06](IN-127-R01-R06-ratificacoes-2026-11-03.md): políticas especiais Site→CRM (72h+360h, T0 durável, descarte, metadados, destinos autorizados); não generalizar como janela de todas as filas.
- [CRM-DEC motor próprio](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md): motor próprio para automações configuradas pelos clientes, n8n somente em processos compatíveis com licença verificada; sem autorização de licenciamento/execução.

**Não duplicar testes nem competências:** CORE-T, REV-01 S, OPS-T, AUT-X, INT HT e IN-168 são catálogos a reconciliar. Não criar uma bateria independente e redundante; o CORE-P0-10 deve apontar **um caso canônico por risco** e anexar referências cruzadas, preservando os testes específicos dos módulos proprietários.

## 2. Situação factual de cada CORE-P0 — parcial, não encerrado

| CORE-P0 | Base documental aceita | Evidência faltante que bloqueia encerramento |
|---|---|---|
| **01 — identidade/tenant/RLS** | D01–D03 | **D04–D07**: matriz final e permissões físicas, contexto RLS por tabela/papel/worker, esquemas/keys/constraints, eventos e contratos FIN/CON |
| **02 — permissões** | D01–D03 | **D04–D10**: catálogo físico de recursos/ações/campos, perfis e alçadas por operação, políticas backend/RLS, módulos proprietários |
| **03 — jobs/eventos/webhooks** | D01–D03 | **D04–D10**: envelopes/versionamento, revalidação, event schema/queues, transições, resultado oficial, recuperações |
| **04 — MFA/sessões/recuperação** | D01–D03 | **D04–D10**: fatores, step-up, recuperação excepcional, revogação, UX e avaliação concreta por papel |
| **05 — suporte excepcional** | D01–D03 | **D04–D10**: catálogo de acesso, fundamento/representação, mandato físico sem bypass, emergência, transparência e auditoria |
| **06 — auditoria/retencão** | D01–D03 | **D04–D10**: classe física de evidências, proteção, consulta/export, privacidade, descarte/backup/restore e custos |
| **07 — convites/assentos/equipes** | D01–D03 | **D04–D10**: token de convite, concorrência/assentos, único administrador, equipes, revogação e auditoria |
| **08 — Básico/cortesia/trial** | D01–D03 | **D04–D10**: contratos Site↔CRM e FIN/CON, precedência de benefícios, antifraude, estados de assinatura, quotas definitivas |
| **09 — cofre/segredos/serviço** | D01–D03 | **D04–D10**: fornecedor/arquitetura de cofre, service principals, matriz real, rotação/revogação, desastre, licença e custos |
| **10 — pacote e homologação** | **D01–D03 PROPOSTAS, NÃO APROVADAS** | **D04–D10**: proveniência/completude, responsabilidades reais, parâmetros, versões, aceites cruzados e liberação final global |

**Regra:** o conjunto de 9 bases conceituais não encerra sequer a Etapa 1 integralmente enquanto persistirem decisões técnicas impeditivas. A proposta D01–D03 abaixo aprova, no máximo, **como vamos verificar e registrar** o fechamento, não afirma que ele ocorreu.

## 3. CORE10-D01 — Matriz de rastreabilidade, evidências e estado documental (PROPOSTA)

Propor uso de **uma matriz única de verificação documental**, sem criar plataforma extra de auditoria, com as dimensões conceituais seguintes:

| Campo lógico do checklist | Objetivo | Restrição |
|---|---|---|
| Código/tema da decisão | CORE, INT, SEG, ARQ/AGT, FIN/CON, IN e fonte historicamente aprovada | Não substituir texto integral da decisão por paráfrase |
| Texto normativo e precedência | O que foi aprovado, qual revisão/commit supera uma versão anterior | Não converter minuta/histórico em aprovação |
| Dono da competência | Arquitetura, Segurança, módulo proprietário, Auditoria, Operações, Jurídico, FIN/CON etc. | Não designar uma pessoa nominal por inferência |
| Classe de evidência | Documento, matriz, esquema contratual, teste planejado, log futuro, parecer/aceite, orçamento quando devido | Documento não prova execução ou comportamento real |
| Estado | Aprovado como base, pendente decisão, pendente especificação, orçamento, validar em homologação, resolvido por evidência/aceite | **Parcial ≠ fechado**; não inventar “aprovado tecnicamente” |
| Recurso/ação/tenant | RLS, role, campo, comando, efeito de negócio, risco de acesso ou resultado | Sem concessão executável de direito |
| Dependência e bloqueio | P0 e gates que dependem do item, condição que permite atualização do estado | Sem encerramento automático por mero link |
| Critério de aceite | Condição verificável, resultado positivo/negativo, responsável, contrato oficial, referência da evidência | Não declarar teste executado sem evidência na fase correta |
| Versão e trilha | Caminho GitHub, commit do documento e, no fechamento, referência imutável | Não etiquetar tag final antes de aprovação global |

### Fonte de verdade e resultado

- **Aprovação documental:** comprova aceite de princípios/catálogo; não comprova schema, configuração, infraestrutura, teste ou contrato.
- **Especificação executável:** detalha comportamento, autoridade, estados e interface. Mesmo aprovada futuramente, não é resultado de teste.
- **Teste futuro planejado:** identifica cenário, pré-condição, dados fictícios e esperado; seu estado até lá é **NÃO EXECUTADO**.
- **Evidência física de homologação:** produzida somente quando a fase de desenvolvimento/homologação for expressamente autorizada; associada à versão, ambiente, execução, resultado, responsável e defeitos.
- **Aceite final:** exige não apenas anexar arquivos, mas verificar proveniência, exceções, conflitos, responsáveis, gates e autorização expressa.

**D01 não aprova** valores de limiar de testes, pontuação, ambiente, identidade, orçamento nem início do trabalho de implementação.

## 4. CORE10-D02 — Pacote mínimo de cenários de homologação futura por fronteira (PROPOSTA)

Propor um mapa de **famílias de risco e exemplos negativos** para consolidar os catálogos já existentes. Cada cenário exigirá no desenho técnico final evidência de autorização/negar, isolamento e confirmação oficial, com identificador e fonte do teste canônico.

| Família | Casos de referência a consolidar | Critério indispensável |
|---|---|---|
| **Identidade e isolamento** | Mesmo usuário em tenants A/B; trocar tenant_id arbitrariamente; RLS em query e job; mesmo CNPJ com organizações distintas | Acesso apenas ao tenant legitimamente ativo; sem vazamento ou união pelo CNPJ |
| **Permissões e exportação** | Papel permite ver, mas não editar/exportar; campo sensível; módulo não incluído no plano; tentativa de escalada | Negar ação/recurso/campo/entitlement não autorizado e auditar conforme categoria |
| **Jobs/webhooks e provas** | Mensagem forjada, replay, idempotência, job após revogação, HTTP 2xx sem resultado de negócio, reconciliação | Não duplicar efeito nem declarar confirmação falsa; respeitar owner e contexto atual |
| **Autenticação/MFA/sessão** | Admin sem MFA; recuperação de fator sem verificação; sessão revogada/expirada; saída de tenant A | MFA/alçada por papel, negar nova ação não autenticada; preservar acesso válido B |
| **Mandato de suporte** | Admin Imagine consulta lead sem autorização; mandato expirado; exportação além de leitura; break-glass improvisado | Sem superusuário/bypass RLS, finalidade e escopo por caso, logs protegidos |
| **Auditoria e retenções** | Tenant edita trilha; expurgo de log apaga lead; rotação põe segredo em log; restore reintroduz dado descartado | Auditoria íntegra/categorizada; retenção distinta; LGPD e exceções comprovadas |
| **Convites/equipes/assentos** | Convite vencido/duplicado, concorrência pelo último assento, Básico segundo login, alteração de equipe | Quotas e vínculos por tenant, revogação consistente, histórico preservado |
| **Básico/cortesia/trial** | Novo site cria segundo trial mesmo CNPJ; dois sites um CRM; cancelamento comum × inadimplência; cobrança automática | Uma cortesia por organização e trial único/CNPJ, transições corretas, sem cobrança implícita |
| **Cofre/agentes/segredos** | Prompt solicita token, modelo alternativo evade autorizador, n8n recebe credencial universal, rotação/revogação com jobs | Negação por default, mínima autoridade, sem segredo em prompt/HTML/log, autorização externa à IA |
| **Portal/Sites/API→CRM** | Site sem destino CRM tenta enviar lead; API principal/contingência, fila até T0+432h, entrega duplicada ou falha | Backend nega captação sem destino autorizado; confirmação oficial; descarte/retencão específicos |
| **Integrações e FIN/CON** | Provador autorizou API mas módulo interno negou; PSP relata 2xx sem liquidação; retry de comando já concluído | Dono do fato comprova resultado; autorizações e estados versionados CT-02..11/OP24 |
| **Resiliência e produção futura** | Falta de cofre, n8n indisponível, alerta sem observabilidade independente, rollback parcial, backup e restore | Contingência com limites, sem atalho privilegiado, sem prometer perda zero; aprovação de produção separada |

**Compatibilidade:** antes de executar, reconciliar com REV-01 S01..10, CORE01-T..CORE09-T, OPS-T01..16, INT HT01..18, AUT-X e critérios IN-168 para que não haja testes redundantes nem riscos sem cobertura.

### Campos de roteiro futuro (propostos, não aprovados fisicamente)

`test_ref; fonte_normativa; risco; dono_modulo; ambiente_futuro; dados_sinteticos; precondicao; ator_causal; executor; tenant_escopo; acao; entrada; resposta_esperada; resultado_oficial_esperado; evidencia_a_capturar; severidade; bloqueio_associado; estado_NAO_EXECUTADO`.

**D02 não aprova** frequência, ferramenta, framework, dados reais, carga, limites, testes sintéticos recorrentes em produção nem execução nesta etapa.

## 5. CORE10-D03 — Gates de liberação documental e sequência segura (PROPOSTA)

Propor a distinção entre quatro estágios, que **não substituem** G01..G06 das integrações nem o gate final do projeto:

1. **Base aprovada:** decisões funcionais/lógicas registradas e rastreáveis. CORE01..09 têm base D01–D03; seus D04..D10 permanecem impeditivos até decisão/especificação.
2. **Fechamento de P0 documental-executável:** modelos físicos, contratos, autorização, segurança, dados, fontes, versões, hipóteses, riscos, dependências e custos exigíveis revisados pelos respectivos proprietários. Distinguir responsável por documentação de pessoa oficialmente designada.
3. **Aceite global antes de programar:** alinhar CRM-REV-01..09, todos CORE-P0, IN-168 ARQ/SEG/AGT, INT P0, G01..G06, FIN/CON, Portal/Sites, LGPD e licenciamento n8n. Registrar **aprovação expressa do planejamento completo** e **SHA/tag de referência final**. Sem esses elementos, **NO-GO para programação**.
4. **Desenvolvimento e homologação física posteriores:** depois da autorização expressa em etapa futura, executar testes dos roteiros aprovados em ambientes segregados, capturar evidências por commit/versão, corrigir falhas e obter autorização de produção **separada**. Não exigir testes de software realizados antes da primeira linha de código, pois isso seria contraditório; exigir apenas que os testes estejam planejados e os contratos fechados.

### G01–G06 — estados preservados conforme INT P0-GATE

| Gate INT | Condição e restrição | Situação de referência |
|---|---|---|
| **G01** | Decisões com fonte e proveniência verificadas | Parcial/aberto |
| **G02** | Documentação integral de normas, versões e exceções | Parcial/aberto |
| **G03** | Dependências, competências, autorização e contratos completos | Parcial/aberto |
| **G04** | Pendências/responsáveis e critérios realmente fechados | Parcial/aberto |
| **G05** | Aceite final expresso do conjunto documental integrado | Pendente |
| **G06** | Referência final imutável de versão/commit aprovada | Pendente |

**D03 não altera estados G01–G06, não fecha CORE01..10 ou IN-168 e não inicia Codex.**

## 6. Quadro de riscos e cruzamentos pendentes

| Fronteira | Dependências a reconciliar | Risco se ignoradas |
|---|---|---|
| Tenant/segurança real | CORE01/02/03/04/05/07/09, SEG-01/02, INT P0-SEC | Escalada de privilégio, vazamento multiempresa, RLS contornado |
| Dados pessoais/retencão | CORE06, LEG-03/04, IN-162/127, IN-118, LGPD | Prova apagada cedo, dado guardado demais ou expurgo indevido |
| Plano, trial, cortesia e faturamento | CORE07/08, REV-02, GRAT-P, FIN/CON, Sites/Portal, INT CT | Conta/benefício indevido, cobrança não autorizada, bloqueio de CRM pago |
| Leads/integrações | CORE03/08/09, IN-127 R01..06, ARQ-02, INT P0-OWN/CON | Lead perdido/duplicado, destino não autorizado, sucesso técnico falso |
| Agentes e n8n | CORE09, IN-151/166/167/168, motor próprio, licença n8n | Token exposto, execução além do contrato, dependência ou custo não aprovado |
| Infra, segurança e recuperação | ARQ-01/02/03, SEG-01/02, AGT-01, OPS-P01..18 | Ponto único de falha, resposta insegura, promessa de resiliência não comprovada |
| Evidências e fontes | REV-01..09, INT P0-DOC/TR/DEP/VER/XREF/ACC/GIT, G01..06 | Aceite documental apresentado falsamente como homologação técnica |

## 7. Exemplos de encaminhamento sem execução

1. **Um gestor do CRM consegue ver dados de outra organização em um teste planejado:** bloquear o aceite executável da matriz CORE01/02/07; não marcar como risco resolvido porque o princípio deny-by-default foi aprovado.
2. **CORE09 propõe cofre protegido, mas não há fornecedor/identidade física definida:** a base lógica é válida; SEG-02 e CORE09-D04..D10 continuam P0.
3. **Existem roteiros HT01..18, mas nenhum foi executado:** manter 0/18 executados e G05/G06 pendentes. Aprovação documental dos roteiros não equivale a aprovação física.
4. **Foi criado um novo documento de permissões no GitHub:** apenas acrescentar caminho/commit e revisar precedência; não efetivar privilégios nem concluir o P0 sem contratos e aceites requeridos.
5. **Uma revisão de produto troca valores comerciais ainda em estudo:** preservar CORE08 como base lógica, porém deixar preço final, quotas, GTM e FIN/CON para aprovação específica.
6. **Fase de programação futura:** somente após fechamento documental e autorização global do responsável, planejar tickets de desenvolvimento versionados, critérios por release, ambientes e testes; não iniciar esta fase agora.

## 8. Decisões CORE10-D01 a D03 — SUBMETIDAS, NÃO APROVADAS

- **CORE10-D01 — PROPOSTA:** aprovar a **matriz única de rastreabilidade e evidências por decisão, fonte, versão, proprietário, risco, contrato, gate, critério de aceite e estado**, sem declarar P0 fechado por possuir minuta/base.
- **CORE10-D02 — PROPOSTA:** aprovar o **mapa de famílias de cenários positivos/negativos e seus critérios de homologação futura**, cruzando REV-01 S, CORE-T, OPS-T, AUT-X e INT HT01..18 sem criar conjunto redundante, e mantendo TODOS como **NÃO EXECUTADOS**.
- **CORE10-D03 — PROPOSTA:** aprovar a **separação de bases documentais, fechamento executável dos P0, aceite global de planejamento com versão/SHA e posterior execução de testes em fase autorizada**, sem alterar G01..G06 nem liberar desenvolvimento.

## 9. CORE10-D04 a D10 — ABERTAS

| ID | Especificação/decisão pendente | Competência |
|---|---|---|
| **CORE10-D04** | Rastrear literalmente cada fonte primária, versão, aprovação e precedência; resolver divergências CORE/REV/INT/Sites/Portal, política de exceções e evidências faltantes | Arquitetura documental, INT P0-DOC/TR |
| **CORE10-D05** | Checklist executável de domínio/tenant/papel/recurso/ação/campo, owners reais, schemas, contratos técnicos e dependências de CORE01..09 | Segurança/IAM, módulos proprietários, CORE02, INT P0-OWN/SEC |
| **CORE10-D06** | Consolidar testes por risco com ID canônico, requisitos, cenários positivos/negativos, dados sintéticos, limites, ambiente e evidências; evitar duplicações S/CORE/OPS/AUT/HT | Qualidade/Homologação, INT P0-ACC, AGT-01 |
| **CORE10-D07** | Critérios de severidade e de NO-GO, abordagem de exceções, cobertura mínima/limiares, gestão de defeitos e critérios por release, sem aprovação implícita de risco | Segurança, Arquitetura, donos de módulo |
| **CORE10-D08** | Mapear custos, provedores/licenças, LGPD, contratos, observabilidade, continuidade/backup, topologia, RPO/RTO, ambientes e recursos para homologação futura | FIN/CON, Jurídico, Operações, IN-168 |
| **CORE10-D09** | Reconciliar resultado da Etapa 1 com INT G01..G06, IN-168 seis P0, FIN/CON e REV-01..09, responsáveis concretos, pendências P1/P2 bloqueantes, trilha de conclusão | Arquitetura global, Segurança, Auditoria, P0-GATE |
| **CORE10-D10** | Pacote final e assinatura/aceite do responsável: commit/tag imutável, checklist de artefatos aprovados, escopo de primeira entrega, autorização expressa para desenvolvimento **somente futura** | Governança de produto e projeto |

**Continuam abertos independentemente deste pacote:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE04-D04..D10; CORE05-D04..D10; CORE06-D04..D10; CORE07-D04..D10; CORE08-D04..D10; CORE09-D04..D10; INT P0-OWN/SEC/CON/ACC/DOC/TR/DEP/VER/XREF/GIT; IN-168 ARQ-01/02/03, SEG-01/02, AGT-01; G01..G06, FIN/CON, LGPD, licenciamento e revisão global. **CORE-P0-10 também permanece aberto**; aceitar D01..03 não resolve D04..10.

## 10. Próxima etapa depois de eventual aceite D01–D03

1. Registrar decisão CORE10 D01–D03 como **base documental**, com D04–D10 e demais bloqueios explícitos.
2. Devolver panorama transparente: **Etapa 1 possui princípios lógicos aceitos, não fechamento técnico**.
3. Recomendar sequência de fechamento dos **D04..D10 e seis P0 IN-168, INT G01..06, FIN/CON e revisões finais**, com decisões separadas e evidências.
4. **Não começar código, testes executáveis, aquisição, provisionamento ou operações reais** sem aprovação final expressa de planejamento e autorizações específicas.

A preparação deste arquivo não cria testes de produção recorrentes, não amplia autonomia da IA nem altera nenhuma política funcional já aceita.
