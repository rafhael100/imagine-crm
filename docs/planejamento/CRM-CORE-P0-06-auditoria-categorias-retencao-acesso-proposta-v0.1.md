# CRM-CORE-P0-06 — Auditoria, rastreabilidade, acesso e retenção por categoria (v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE06-D01, D02 e D03 APROVADAS COMO BASE DOCUMENTAL**, conforme [CRM-DEC de aceite](CRM-DEC-aprovacao-CORE-P0-06-D01-D03-base-auditoria-2026-11-04.md). **CORE-P0-06 PARCIAL/ABERTO**; CORE06-D04..D10 e demais dependências técnicas permanecem pendentes. Menções históricas a “PROPOSTA/NÃO APROVADAS” quanto a D01–D03 antecedem a aprovação e são superadas por este aditamento.  
**Escopo:** organizar as autoridades e regras já aprovadas de auditoria administrativa da Imagine, auditoria comercial por tenant, evidências oficiais de integrações, telemetria e logs técnicos, trilha de recepção e entrega de leads, fatos de negócio, backups e histórico de agentes, sem equiparar categorias diferentes.  
**Fase:** planejamento, referência e versionamento; **sem código, execução de testes, descarte real, produção, contratação, alteração de política física ou de valores**.

## 1. Precedências documentais obrigatórias

| Fonte | Precedência funcional relevante |
|---|---|
| [LEG-03 — Administração Geral Imagine](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md) | Auditoria administrativa central, MFA, mudanças críticas e acesso excepcional; registros protegidos de modificação por administradores ordinários; **retenção padrão histórica de 5 anos**, sujeita à LGPD/lei |
| [LEG-04 — Empresas/Usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Auditoria de criação, edição, exclusão de leads, mudanças em oportunidades, transferências, permissões e exportações; contexto do usuário, tempo, objeto e valores antes/depois quando relevantes; consulta restrita; administrador do tenant não pode apagar/editar; **12 meses enquanto empresa ativa**, com regras legais/exclusão específicas |
| [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) N-09 e [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) OPS-R17/R18/R19 | Categorias distintas: auditoria central 5 anos, comercial tenant 12 meses, telemetria por IN-162; não duplicar a competência da Auditoria Geral nem confundir história de negócio e logs |
| [IN-162 — Retenção de logs](IN-162-politica-inteligente-retencao-armazenamento-logs.md) | Padrões aprovados: debug **7 dias**; operacionais **30 dias**; traces **30 dias**; métricas **90 dias**; auditoria técnica de filas de leads **90 dias** conforme política específica; incidentes críticos/eventos de segurança **180 dias**; guarda excepcional legitimamente documentada, minimização e limpeza segura |
| [IN-127-R01–R06](IN-127-R01-R06-ratificacoes-2026-11-03.md) | Para captação Sites→CRM, T0=recepção durável; entrega elegível por **72+360=432h contínuas**; descartar payload pendente nas estruturas ativas/temporárias ao vencimento; cópias de entregues seguem IN-128 **24h**; metadados técnicos mínimos por **até 90×24h a partir do evento terminal**; após vencimento sem entrega, reconciliação apenas consultiva, sem novo envio |
| [INT-12](integracoes/INT-12.md), [INT-R01](integracoes/INT-R01-governanca-contratos-homologacao.md), [INT-R02](integracoes/INT-R02-rastreabilidade-dependencias-validacao.md) | **Auditoria Geral** permanece fonte oficial; INT-12 correlaciona/investiga trilhas e hipóteses, sem criar banco paralelo, editar prova ou confirmar resultado de negócio; CT-02..04 relacionam autorização, comando, estado técnico e confirmação oficial |
| [CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md), [CORE-P0-02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md) | Identidade global + tenant separado com Laravel/RLS; acesso à auditoria por papel, recurso, ação, campo e finalidade; consulta ≠ exportação ≠ modificação |
| [CORE-P0-03 D01–D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md) | Autoria e executor separados; causalidade, correlação, tentativa, revogação e resultado oficial; evidência técnica não é confirmação de negócio |
| [CORE-P0-04 D01–D03](CRM-DEC-aprovacao-CORE-P0-04-D01-D03-base-autenticacao-2026-11-04.md), [CORE-P0-05 D01–D03](CRM-DEC-aprovacao-CORE-P0-05-D01-D03-base-suporte-excepcional-2026-11-04.md) | MFA/segurança por contexto; suporte excepcional por caso, finalidade e prazo, sujeito a auditoria; administradores Imagine não acessam conteúdo dos clientes por padrão |
| [IN-163](IN-163-ciclo-vida-conhecimento-agentes.md), [IN-101](IN-101-backups-inteligentes-sites-imagine.md) e políticas específicas de backup CRM | Conhecimento estruturado de agentes ativo enquanto necessário, arquivado por **24 meses**; backup e retenção de anexos têm ciclo próprio, não são uma auditoria nem justificam guardar logs indefinidamente |
| [IN-040/LEG-13 — Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) | Única permissão de visualizar/baixar anexos; decisão prévia de **não auditar individualmente toda visualização e download de anexo** deve ser preservada, sem dispensar auditoria de ações críticas e segurança |

**Regra de precedência:** políticas específicas e ratificações posteriores prevalecem em suas matérias. **Não usar IN-162 para apagar documentos fiscais, registros de contratos, mensagens ou fatos oficiais** nem usar auditoria central de 5 anos como justificativa para conservar todos os logs e dados de todos os clientes. Prazos de categorias ainda não aprovadas permanecem pendentes.

## 2. Distinguir evento, registro oficial, evidência e telemetria

1. **Fato de negócio (fonte proprietária):** lead criado/associado, oportunidade alterada, mensagem enviada, assinatura, cobrança, reserva ou transferência. O módulo dono preserva seu estado e o histórico funcional; auditoria é evidência do ato, não cópia de todos os campos do registro.
2. **Trilha de auditoria oficial:** quem/qual serviço realizou, autorizou, negou, alterou, cancelou, concedeu, exportou ou investigou um ato sensível, com vínculo ao fato, contexto e fontes quando necessário. A **Auditoria Geral** detém a integridade, proveniência, acesso e retenção.
3. **Evidência de integração/INT-12:** correlação de CT-02 autorização, CT-03 comando, CT-04 relato de resultado, tentativas, aprovações, compensações, incidentes e confirmação oficial; não é motor de auditoria duplicado nem poder de alterar negócio.
4. **Log/trace/métrica técnicos:** operacional e observabilidade, com volume, sampling e retenções IN-162; não comprova sozinho aceite comercial nem deve conter PII integral/token/segredo.
5. **Histórico administrativo e comercial:** administradores e equipes autorizados consultam evidências de sua categoria/tenant, sem acesso a dados de outras empresas ou poderes para alterar/apagar a fonte.
6. **Backups, anexos e conhecimento técnico:** possuem finalidades e retenções próprias; não se transformam em armazenamentos alternativos de provas expiradas.

## 3. CORE06-D01 — Taxonomia de fontes e categorias (PROPOSTA)

Aceitar como **base lógica transversal**, sem criar serviços paralelos, as categorias de auditoria abaixo.

| Categoria lógica | Exemplos de fatos | Fonte oficial / política base | Separação essencial |
|---|---|---|---|
| **AUD-CENTRAL** | Administração Imagine: mudanças globais, suspensão de empresa, privilégios, concessões de plano/benefício, configurações, casos excepcionais | Auditoria Geral com competência da Administração Imagine; LEG-03 | Não conceder leitura universal de dados de clientes; prazo histórico 5 anos |
| **AUD-TENANT** | Criação/edição/exclusão protegida, transferências, alterações de permissões e exportações por empresa | Auditoria Geral com proprietário comercial; LEG-04 | Consulta por tenant/autorização; prazo histórico 12 meses enquanto empresa ativa |
| **AUD-SEG** | Tentativas de acesso, MFA, recuperação, bloqueio, revogação, credenciais, investigação, acessos excepcionais | Identidade/Segurança + Auditoria Geral; LEG-03/04, CORE04/05 | Evento técnico pode possuir prazo IN-162; evidência administrativa de ato segue categoria aplicável; classificação final D04/D06 |
| **AUD-INT** | Solicitação, autorização, comando, tentativa, resposta técnica, reconciliação, confirmação de proprietário, consulta de evidência | Módulos donos + Auditoria Geral; INT-12 correlaciona | Transportar 2xx/ACK não cria confirmação; retenção por categoria da evidência subjacente |
| **AUD-LEADS-API** | Recepção durável, entrega/erro, vencimento/descarte, T0, prova de destino | APIs/filas e Auditoria Geral nas categorias pertinentes; IN-127 ratificada | Metadados técnicos minimizados até 90×24h do evento terminal, sem payload/contatos |
| **OBS-TECH** | Debug, erros operacionais, traces, métricas e incidentes de segurança | Central de Observabilidade, IN-162 | Prazos distintos 7/30/90/180 dias, não registros oficiais completos |
| **HIST-BUSINESS** | Histórico de lead, conversa, pagamento, reserva, contrato e documentos fiscais | Proprietário de cada módulo (DAT/ATD/FIN/CON/AGD etc.) | **Não presumir expurgo após 12 meses** só porque a auditoria comercial tem padrão histórico de 12 meses |
| **KB/BACKUP** | Playbooks, conhecimento e cópias de segurança | Serviços proprietários, IN-163 e políticas por produto | 24 meses de conhecimento arquivado não são prazo de log bruto; backup sujeito a sua própria retenção |

Os rótulos são **identificadores documentais candidatos**, não nomes de tabela, direito novo ou classificações físicas homologadas. Um evento com duas naturezas pode exigir **referência cruzada** ou evidências separadas minimizadas conforme competência, mas não duplicação integral por padrão.

## 4. CORE06-D02 — Envelope lógico de evidência, integridade e direitos de consulta (PROPOSTA)

### 4.1. Registro mínimo proporcional

Adotar os campos semânticos **quando pertinentes** e sujeitos à classificação da operação — **não** como esquema de tabela ou obrigatoriedade de gravar todos em todo evento:

| Dimensão | Elementos conceituais candidatos | Regra de proteção |
|---|---|---|
| Origem e contexto | serviço, módulo proprietário, ambiente, `tenant_ref` quando aplicável, categoria e finalidade | Não inferir tenant do body público; escopo verdadeiro validado |
| Identidade e autoridade | pessoa ou serviço causal, executor, mandato/caso de suporte, autorização/aprovação, versão da política | Distinção ator autor do evento, aprovador e executor; nenhuma credencial em claro |
| Ato e alvo | ação, recurso, ID referencial, versão, estado anterior/posterior **somente quando relevante** | Redação/máscara de campos sensíveis, sem cópia de objeto integral por padrão |
| Tempo e cadeia | instantes oficiais do evento, registro, tentativa e confirmação; causation/correlation/idempotency refs | Não declarar timestamp de recepção igual ao momento do efeito |
| Resultado e prova | permitido/negado, falhou/inconclusivo, referência ao resultado oficial, justificativa mínima e incidente | Fonte do negócio valida seu próprio efeito; HTTP 2xx não basta |
| Política e guarda | categoria, referência à política de retenção, relógio de expiração, exceção legal quando formalmente válida | Não fixar retenção por heurística, prompt de IA ou duração da sessão |
| Integridade/proveniência | cadeia de origem, versão da fonte, trilha de correção/retificação auditável e evidência de descarte | Auditabilidade e integridade adequadas à classe, solução física D05 |

### 4.2. Autorização por finalidade e operação

- **Consulta:** identidade, tenant, categoria, finalidade, recurso/campo e autorização específica. **Administrador de empresa não apaga nem altera auditoria oficial**. Admin Imagine não lê dados privados apenas por papel central.
- **Exportação/extração:** distinta de visualizar a trilha; exige escopo próprio, minimização, proteção contra exfiltração, limites/avaliação de risco e auditoria. **Uma exportação de trilha não libera automaticamente exportação da base de clientes**.
- **Auditoria da auditoria:** toda consulta/investigação relevante, exportação, alteração de política, retenção excepcional, correção e descarte controlados geram evidência própria conforme INT-12/Auditoria Geral; sem registro de todos os downloads de anexos por analogia.
- **IA consultiva:** pode correlacionar, resumir e apontar hipótese **marcada como hipótese**, nunca reescrever fato, criar prova, emitir parecer jurídico final, conceder papel, autorizar descarte ou confirmar resultado de negócio.
- **Integrações/suporte:** acesso excepcional deve possuir mandato válido CORE-P0-05. Service principals do INT e agentes só acessam dados exigidos por tarefa/ambiente/tenant. Não exportar segredo para log/prompt.
- **Dados pessoais:** aplicar minimização, proteção de dados sensíveis e finalidade legítima; regras exatas de mascaramento e direitos de titulares ficam para detalhamento do responsável e privacidade/LGPD.

**Exceção importante já aprovada:** IN-040 mantém uma permissão conjunta para ver/baixar anexos, e a decisão histórica de **não auditar individualmente cada visualização/download** continua preservada; este CORE-P0-06 **não** cria monitoramento granular de navegação em anexos sem novo aceite. Eventos de segurança/alteração de permissões/exports continuam auditáveis por outras regras.

## 5. CORE06-D03 — Matriz de retenções preservadas e separação entre regimes (PROPOSTA)

O aceite de D03 **reconhece e organiza decisões pré-existentes**, sem votar novamente cada prazo ou instituir períodos novos:

| Regime | Prazo já registrado | Delimitação/condição essencial |
|---|---|---|
| Auditoria administrativa Imagine | **Padrão histórico 5 anos** | LEG-03, desde que categoria/finalidade e base legal pertinentes sejam confirmadas; não transferir ao conteúdo comercial de todo cliente |
| Auditoria comercial por empresa | **Padrão histórico 12 meses enquanto empresa estiver ativa** | LEG-04; eventos comerciais protegidos; encerramento, suspensão e retenção posterior continuam dependentes da política específica |
| Debug de operações técnicas | **7 dias** | IN-162, sem segredo ou payload pessoal completo |
| Logs operacionais de servidores, sites e APIs | **30 dias** | IN-162, salvo categoria mais específica e preservação legítima |
| Traces distribuídos | **30 dias** | IN-162, minimizar PII, nunca transportar token em claro |
| Métricas históricas | **90 dias** | IN-162, séries técnicas; agregações derivadas dependem de política própria |
| Auditoria técnica minimizada de filas de leads | **Até 90 períodos de 24h após evento terminal** | IN127-R04, precedência específica para recepção/entrega; sem conteúdo do lead, data/forma de início distintas de T0 |
| Incidentes críticos e eventos técnicos de segurança | **180 dias padrão** | IN-162; não prolonga automaticamente logs de debug ou trilhas de negócio |
| Payload de lead não entregue, sob controle de captação Imagine | **Até T0 + 432h (72h + 360h)** para tentativas elegíveis; fim e descarte operacional devido | IN127-R01..R06; preservar leads já entregues no CRM; após prazo, consultar metadados permitidos sem reenviar payload |
| Cópias temporárias de lead entregue | **Limpeza após 24h da confirmação** | IN-128/IN127-R03; não excluir registro oficial CRM |
| Conhecimento arquivado de agentes | **24 meses após arquivamento; ativo enquanto necessário** | IN-163; não retenção de logs originais |
| Dados oficiais de clientes e contratos, documentos fiscais, arquivos/anexos, backups CRM/Sites e outras auditorias específicas | **Conforme política própria — não definido aqui** | Nunca inferir 7/30/90/180 dias só por coexistirem com telemetria |

### 5.1. Ciclo de vida lógico de evidências

`classificado na origem → persistido/protegido → consultável somente por autorizado → elegível à revisão/expurgo → (descarte seguro comprovado | preservação excepcional legítima) → conclusão auditável`.

- **Expurgo conforme política aprovada:** processo controlado e rastreável; quando vencido, eliminar registros elegíveis e cópias temporárias abrangidas. Dados não elegíveis por obrigação/ordem/justificativa válida podem receber bloqueio de descarte específico, sob revisão, sem prazo infinito arbitrário.
- **Múltiplas cópias:** tratar índices, réplicas, exportações, backups e armazenamento frio segundo contratos próprios; não prometer eliminação física instantânea de cópias protegidas antes do seu ciclo técnico/obrigação.
- **Alteração de categoria:** evento cujo conteúdo comprova incidente de segurança pode ter evidência oficial separada segundo 180 dias; evitar reter automaticamente todo debug como segurança.
- **Reclassificação e correção:** eventos posteriores ou fato oficial tardio podem adicionar correção auditável sem sobrescrever prova anterior; não reiniciar prazo de retenção de forma oportunista.
- **Segredos e dados pessoais:** log não é depósito de credencial, histórico integral de atendimento, resumo de IA ou cópia de lead entregue.
- **Custos:** retenção automatizada e armazenamento dimensionados sem contratação de serviço pago por inferência desta minuta.

### 5.2. Exemplos de conflitos que exigem resolução específica

1. Um login suspeito de segurança também aparece no log operacional: distinguir evento técnico de 30 dias, evidência de incidente com 180 dias **se efetivamente classificada**, e possível ato administrativo central; não duplicar payload inteiro.
2. Uma mudança de oportunidade gera histórico do negócio e auditoria de alteração comercial: a expiração desta **não apaga a oportunidade/histórico** que possui regime próprio.
3. Um lead recebido via Site é entregue ao CRM: relógio de auditoria técnica da integração refere-se ao evento terminal; payload temporário segue limpeza após 24h; registro oficial CRM permanece.
4. Uma entrega nunca é confirmada dentro das 432h: expirar tentativas e descartar payload ativo/temporário conforme IN127-R03, guardar somente a evidência mínima necessária do evento terminal por prazo específico.
5. Agente encontra causa-raiz de incidente: criar/atualizar conhecimento protegido com síntese técnica, sem usar a biblioteca como cópia de logs brutos ou PII.
6. O gestor de um tenant vê um caso de auditoria da sua empresa: não pode ler outro tenant, apagar trilha ou exportar dados fora da permissão específica.

## 6. Governança de propriedade, LGPD e arquitetura

- **Auditoria Geral** tem competência de fonte oficial, integridade, acesso e retenção. Proprietários de negócios persistem seus estados/fatos; **INT-12** oferece leitura especializada e correlação dentro dessas fontes.
- **Identidade/Segurança** define quem pode consultar, investigar, alterar categoria/política ou solicitar exceção; **nunca** apenas o papel de Admin Imagine ou prompt de IA.
- **Privacidade/Legal/Contratos** devem validar finalidade, base legal, retenção após cancelamento, pedidos dos titulares, suspensão de descarte e conflito entre exigência fiscal/probatória e minimização. **Nenhum parecer jurídico é aprovado automaticamente**.
- **Arquitetura/Operações** deve definir integridade verificável, proteção contra adulteração, múltiplas cópias, capacidade de consulta, limpeza por categoria, evidência de destruição e recuperação segura. Não se presume WORM, blockchain, produto, storage nem hash imutável específico.
- **Serviços críticos e contingência** exigem observabilidade independente, sem produzir falsos estados de saúde quando logs deixam de chegar; auditoria mandatória não pode ser omitida por amostragem para reduzir custos.
- **Planos:** retenção/auditoria exigidas por segurança e conformidade não são removidas do Básico por franquia de arquivo, nem concedem acesso comercial extra. Acesso aos próprios dados por mecanismos IN-118 não equivale a poder editar/exportar a trilha imutável.
- **Correção de irregularidade:** identificar/analisar/reconciliar evidências não autoriza INT-12, IA ou suporte a reescrever registros de negócio; atos de correção pertencem ao proprietário.

## 7. Testes negativos e evidências planejadas — NÃO EXECUTADOS

| ID | Cenário futuro | Resultado esperado |
|---|---|---|
| CORE06-T01 | Administrador tenant tenta editar ou excluir trilha comercial | Negar; preservar imutabilidade funcional |
| CORE06-T02 | Admin Geral usa painel central para visualizar dados de outro tenant em trilha | Negar acesso sem mandato e finalidade legítima |
| CORE06-T03 | Usuário autorizado a consultar auditoria tenta exportar lote sem grant | Negar exportação e auditar solicitação protegida |
| CORE06-T04 | Log técnico de 30 dias inclui token, senha ou payload integral do cliente | Proteção/minimização; tratar vazamento sem perpetuar segredo |
| CORE06-T05 | Expurgo padrão de log debug 7 dias encontra exceção legal formal | Preservar somente escopo comprovado e registrar revisão; não guardar todos os logs |
| CORE06-T06 | Expiração de auditoria comercial 12 meses é confundida com exclusão do lead | Não remover registro funcional por prazo da trilha |
| CORE06-T07 | Usuário de outro tenant adivinha correlation_id da auditoria | Negar acesso mesmo que conheça ID |
| CORE06-T08 | HTTP 202 gera evento de integração sem prova de criação de lead | Conservar resultado técnico como tal; não registrar fato falso de negócio |
| CORE06-T09 | Lead não entregue chega a T0+432h | Impedir reenvio, descartar payload em estruturas abrangidas, preservar somente metadados mínimos elegíveis |
| CORE06-T10 | Lead entregue e cópia temporária chega a 24h | Limpar cópia elegível; não apagar registro CRM |
| CORE06-T11 | Integrador INT-12 tenta alterar confirmação oficial | Negar; proprietário confirma negócio |
| CORE06-T12 | Auditor tenta apagar a própria consulta/exportação sensível | Proteger auditoria da auditoria e registrar acesso, conforme competência |
| CORE06-T13 | Falha de observabilidade leva sistema a marcar serviço “saudável” sem provas | Exibir desconhecido/alerta conforme política de observabilidade |
| CORE06-T14 | Conhecimento técnico arquivado completa 24 meses | Aplicar política IN-163 e exceções válidas, sem estender logs originais |
| CORE06-T15 | Restore de backup reintroduz registros cujo expurgo ativo já foi confirmado | Impedir reativação operacional indevida/reconciliar e reaplicar descarte legal |
| CORE06-T16 | Anexo é baixado em operação ordinária por usuário autorizado | Não criar auditoria individual obrigatória de cada visualização/download contrariando IN-040; manter controles de acesso |
| CORE06-T17 | Mandato de suporte expira enquanto investigação tenta consultar evidência | Negar novos acessos, preservando prova anterior |
| CORE06-T18 | Incidente crítico expira e IA tenta prolongar retenção indefinidamente | Negar extensão sem fundamento, autoridade, escopo e revisão |

Confrontar estes casos com INT HT-01..18, CORE01..05, CRM-REV09 OPS-T e IN-162; evitar suítes duplicadas. Execução real apenas após aprovação de desenvolvimento e homologação.

## 8. Decisões submetidas agora — NÃO APROVADAS

- **CORE06-D01 — PROPOSTA:** aprovar a **separação lógica de categorias e fontes oficiais** (seções 2–3): administrativa Imagine, comercial tenant, segurança, integração, leads/filas, observabilidade, fatos de negócio e conhecimento/backup, sem duplicar Auditoria Geral e sem aplicar um prazo único a tudo.
- **CORE06-D02 — PROPOSTA:** aprovar o **envelope lógico proporcional de evidência, rastreabilidade e autorização de consulta/exportação** (seção 4), preservando actor/tenant/ação/resultado/correlação quando pertinentes, minimização, imutabilidade da trilha e auditoria da auditoria, sem conceder poderes nem alterar regra de visualização/download de anexos.
- **CORE06-D03 — PROPOSTA:** aprovar como referência a **matriz de retenções diferenciadas já vigentes**, sua precedência e seu ciclo lógico de descarte/preservação (seção 5), sem fixar prazos novos de dados oficiais, contratos, backups ou anexos e sem autorizar limpeza real.

## 9. CORE06-D04 a D10 — dependências ABERTAS

| Código | Especificação/decisão pendente e competências |
|---|---|
| **CORE06-D04** | Catálogo exato de eventos por domínio/ação/tenant, categorias jurídicas, dados antes/depois e classificação de sensibilidade/PII; proprietários DAT/ATD/AGD/VEN/FIN/CON/SEG |
| **CORE06-D05** | Esquemas, integridade e armazenamento físico da Auditoria Geral, referência de fontes, criptografia, acesso, partição, resistência à adulteração, fail-safe, recuperação e custos; Arquitetura/Segurança |
| **CORE06-D06** | Regras concretas de acesso/consulta/exportação, visões e segregação de auditoria, justificação e auditoria da auditoria; CORE02-D04/D06, CORE05-D09, INT-12, IAM |
| **CORE06-D07** | Política de retenção por registro oficial não coberto; após cancelamento, contratos, obrigações fiscais, backups, anexos, direitos LGPD, legal holds e revisão; Privacidade/Jurídico/FIN/CON e proprietários |
| **CORE06-D08** | Execução futura de expurgo por categoria, backups/réplicas, correções, restauracão sem ressuscitar dado descartado, prova de destruição e exceções; IN-162/127/CORE03/ARQ |
| **CORE06-D09** | Volume, custos, cotas, índices, monitoramento, alarmes, exportações e métricas de integridade/retencão, sem sacrificar auditoria mandatória; Operações/Observabilidade |
| **CORE06-D10** | Pacote final de homologação documental de trilhas, fronteiras, negativo/positivos, responsabilidades, LGPD, INT HT e gates G01–G06; CORE-P0-10/INT P0 |

**Continuam abertos, sem alteração:** CORE01-D04..D07; CORE02-D04..D10; CORE03-D04..D10; CORE04-D04..D10; CORE05-D04..D10; CORE-P0-07..10; INT P0-OWN/SEC/CON/ACC, IN-168 (ARQ/SEG/AGT), G01–G06. **Nenhum P0 é concluído** pela adoção parcial de princípios documentais.

## 10. Continuidade e limites

Próximo após eventual aceite expresso de CORE06-D01..D03: **CORE-P0-07 — convites, assentos, equipes e desligamento**. Não antecipar a decisão nem abrir direitos sem aprovação. O gate global do planejamento e o aceite final continuam obrigatórios antes de Codex/implementação.

**Proibições:** nenhuma programação, teste executável, limpeza de logs reais, exclusão de dados, migração, acesso a credenciais, provisionamento, contratação ou configuração operacional. Somente documentação e decisões expressamente aprovadas.

## 11. Aditamento de aprovação parcial — 04/11/2026

O responsável aprovou expressamente **CORE06-D01, D02 e D03 como base documental** no [registro oficial de decisão](CRM-DEC-aprovacao-CORE-P0-06-D01-D03-base-auditoria-2026-11-04.md). Foram aceitas a taxonomia lógica de auditoria e fontes oficiais, o envelope proporcional de rastreabilidade com direitos distintos de consulta/exportação e a separação dos prazos de retenção **já aprovados**, sem fixar prazos novos. A aprovação preserva IN-040, IN-162, IN127-R01..R06 e LGPD. **CORE06-D04 a D10 ainda estão abertas**, assim como P0 CORE/INT/SEG/ARQ e G01..G06. Próxima fase autorizada: **planejamento CORE-P0-07**, sem programação nem testes reais.
