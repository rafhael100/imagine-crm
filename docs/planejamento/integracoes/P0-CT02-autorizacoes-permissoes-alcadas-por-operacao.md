# P0-CT02 — Matriz de autorização contextual, permissões e alçadas por operação

**Data de registro:** 2026-11-02.
**Estado:** **MINUTA PARA REVISÃO**, construída sobre regras transversais já aprovadas e sobre as [24 fichas aceitas como base documental](P0-DEC-aprovacao-OP24-base.md). Este documento **não aprova** novos perfis, políticas de risco, MFA obrigatório universal, papéis privilegiados, limites numéricos ou alçadas por operação. A aceitação das 24 fichas não é aprovação automática desta nova matriz.
**Fase:** exclusivamente planejamento/documentação; nenhuma programação, alteração de produção, teste real, contratação, despesa ou novo serviço/automação.
**Autoridade existente:** Identidade e Segurança, INT-02 para autorizações externas, cofre central de segredos IN-151, Central de Aprovações, módulos proprietários por efeito, Auditoria Geral como fonte oficial. Não criar mecanismo paralelo de autorização.

## 1. Objetivo

CT-02 define **o que precisa ser verificado** antes de aceitar, encaminhar, executar, retomar, compensar ou expor o resultado de um ato protegido no CRM Imagine. A validação não se resume a login, perfil de “administrador” ou posse de token: deve considerar tenant, recurso, operação, finalidade, estado, autoridade do módulo e contexto de execução. A aplicação concreta de RBAC/MFA/consentimentos depende das políticas já aprovadas e da matriz final a aprovar.

CT-02 é **contrato de intercâmbio com os serviços centrais existentes**, não novo produto, IAM, RBAC, cofre, mecanismo de consentimento, política de retenção ou motor de regras de negócio.

## 2. Quatro responsabilidades que devem permanecer separadas

| Papel de autorização | Quem atua | Limite |
|---|---|---|
| Solicitante | Humano ou serviço identificado no contexto legítimo | Ter acesso para solicitar não significa poder aprovar ou confirmar |
| Autoridade de segurança/aprovação | Identidade e Segurança, INT-02, Central de Aprovações conforme ato | Autoriza o escopo legítimo, não substitui decisão do módulo proprietário |
| Executor técnico | INT-04, INT-06, INT-07, INT-08, INT-09/10 ou serviços centrais permitidos | Executa apenas ato tipado, autorizado e dentro das precondições |
| Confirmador oficial | Módulo proprietário do efeito (FIN, CON, VEN, AGD, ATD, CAT, COM, CMS, Segurança etc.) | Só ele valida o fato de sua competência; INT-12 consulta via Auditoria Geral |

**Regra:** aprovação humana é um ato distinto de execução e confirmação oficial. Evitar dupla aprovação **da mesma decisão**, mas não dispensar autorizações distintas de outros proprietários quando necessárias.

## 3. Envelope de autorização CT-02 — classes propostas, nomes físicos pendentes

| Grupo | O que representar | Fonte/validador |
|---|---|---|
| Identidade | Ator, autenticação da sessão/serviço, status, MFA conforme política | Identidade e Segurança |
| Contexto | tenant, empresa/unidade/marca, ambiente, vínculo de conta/connection | Identidade + INT-01 |
| Finalidade | motivo/objetivo e âmbito de uso dos dados, exigência de consentimento quando aplicável | Políticas de dados, módulo proprietário, INT-02 para escopo externo |
| Recurso | módulo, entidade, registro, campo sensível, origin/destination autorizados | Proprietário e permissões centrais |
| Ação | operação tipada/versão, natureza de leitura/escrita/alteração/reversão/exportação | INT-04 e proprietário |
| Delegação | serviço executor, quem solicitou, limites de atuação em nome de outrem | Identidade e Segurança + autorização externa |
| Aprovação | referência, decisor competente, escopo, vigência e risco quando exigido | Central de Aprovações |
| Estado | versão/estado atual, prazo, status de conexão/credencial, pré-condições | Proprietário / INT-01/02/11 |
| Limites | plano, quota, frequência, escopo de execução, limites de ferramenta | Regras comerciais e serviços centrais |
| Evidência | referência da decisão, motivo de bloqueio, correlação CT-03/04 e Auditoria Geral | Auditoria Geral / módulos proprietários |

Os nomes físicos, obrigatoriedade condicional por operação, representações de política, períodos de validade e portadores de identidade ainda deverão ser especificados e aprovados. **Nunca confiar no tenant, perfil ou nível de privilégio simplesmente declarado por payload do cliente, evento ou texto da IA.**

## 4. Resultado da verificação de autorização — taxonomia proposta, sem enum aprovado

| Decisão documental possível | Consequência planejada |
|---|---|
| Elegível/autorizado | Permitir **somente** a operação tipada no escopo/tempo/estado verificado |
| Negado | Não executar/consultar/exportar dado indevido; registrar motivo minimizado |
| Aprovação necessária | Encaminhar à Central de Aprovações e aguardar; não executar por “aceite implícito” |
| Inconclusivo/indisponível | Não conceder privilégio por indisponibilidade; avaliar retomada segura e proteção do que já estava em andamento |
| Fora do escopo/incompatível | Negar ou suspender; corrigir vínculo/versão/autorização pela autoridade competente |

**Proposta:** estados acima são resultados de interoperabilidade, **não** substituem estados próprios de Identidade e Segurança, Central de Aprovações ou CT-04.

## 5. Revalidação em fronteiras protegidas
- **Solicitação:** autenticar sujeito e obter tenant, finalidade, operação, recurso, versão e pré-condições; serviço não herda acesso por ter recebido evento.
- **Despacho/execução:** validar autorização atual e precondição do proprietário, especialmente antes de escrita ou exportação sensível.
- **Filas e assincronismo:** recuperar contexto por referência segura e revalidar o ato no momento da execução conforme política; evento assinado não substitui direito de atuar.
- **Retomada após suspensão/rede/failover:** conferir credenciais, versão/conector, autoridade, estado do recurso e eventual aprovação ainda aplicável.
- **Alteração de vínculo:** não transferir comandos/solicitações pendentes entre contas ou tenants automaticamente.
- **Compensação/reversão:** verificar elegibilidade e aprovação do proprietário para **aquele** efeito.
- **Revogação:** bloquear novas operações protegidas desde que revogação aplicável tenha efeito; não fingir desfazer escrita já enviada/confirmada.
- **Consulta/exportação de evidências:** exigir autorização específica, minimização e auditoria da própria consulta/exportação, sem acesso amplo presumido.

## 6. Matriz CT-02 por operação — 24 fichas de revisão

| Ficha | Operação | Quem pode solicitar (classe proposta) | Competência de validação | Condição de acesso essencial | Aprovação / alçada | Retomar, negar e auditar | HT |
|---|---|---|---|---|---|---|---|
| OP-01-CAD | Configurar conexão | Gestor autorizado da conexão no tenant; serviço interno escopado | INT-01 / Identidade e Segurança; conector INT-11, credenciais INT-02 | Permissão de administrar conexão do próprio tenant; conexão/conta/versão corretas | Requer alçada de alteração conforme risco; não pressupor aprovação adicional universal | Revisar tenant, destino, conector e vínculo antes de confirmar; não alterar destino de operações passadas | HT-01,HT-13 |
| OP-01-CIC | Suspender conexão | Operador autorizado à gestão de conexão; intervenção técnica elegível | INT-01; efeitos pendentes pertencem aos módulos de negócio | Poder explícito de suspender/desativar a conexão e avaliar impacto de instâncias | Aprovação conforme criticidade/efeitos; contenção segura segue política existente | Bloquear novas ações, preservar as já transmitidas, não produzir cancelamentos de negócio | HT-18,HT-08 |
| OP-02-AUT | Vincular/renovar autorização externa | Titular/delegado ou serviço habilitado a conduzir autorização | INT-02 e Identidade e Segurança; cofre central IN-151 | Escopo mínimo do provedor, finalidade e validade, com base legal/consentimento quando necessário | Aprovações/consentimentos exigidos conforme tipo, sem inventar consentimento universal | Não permitir credenciais em browser/IA/log; revalidar origem e delegação | HT-01,HT-02 |
| OP-02-REV | Revogar autorização externa | Titular/operador de segurança autorizado; contenção técnica homologada | INT-02/Identidade e Segurança; provedor confirma efeito remoto | Permissão de revogação sobre a credencial e conta certas; escopo e impacto | Revogação potencialmente disruptiva/critica exige alçada aplicável; contenção previamente autorizada pode ter tratamento próprio | Bloqueio local imediato de novos usos quando aplicável; sucesso remoto deve ser comprovado | HT-02,HT-13 |
| OP-03-VAL | Validar mapeamento/prévia | Usuário com permissão para visualizar campos de origem/destino | INT-03; proprietário controla dados | Leitura dos campos/classificações e ambiente autorizados; prévia não concede escrita | Sem aprovação nova quando somente leitura autorizada; dados sensíveis podem exigir controle adicional | Máscara/minimização; nenhum ajuste de dado oficial por validação | HT-03,HT-07 |
| OP-03-VER | Publicar versão de mapeamento | Responsável autorizado à gestão de esquemas | INT-03 e módulos consumidores; políticas de homologação | Direito de publicar versão, ambiente e conjunto de entidades permitidos | Revisão/aprovação quando versão incompatível ou impacto significativo, conforme governança | Proibir atualização silenciosa de instâncias e mapeamentos ativos; compatibilidade documentada | HT-12,HT-03 |
| OP-04-ENV | Enviar comando tipado | Identidade humana/técnica com delegação efetiva | INT-04 transmite; proprietário do efeito autoriza/atesta | Operação, entidade, recurso/campo e conexão autorizados; CT-03 válido | Aprovação do ato de negócio na alçada competente quando exigida | Revalidar no despacho e na escrita; resultado inconclusivo bloqueia retry arriscado | HT-08,HT-13 |
| OP-04-RSL | Registrar/encaminhar resultado | Principal de serviço confiável e módulo proprietário | INT-04 para transporte; módulo proprietário para negócio | Fonte autenticada, correlação e estado compatíveis, sem poder de inventar confirmação | Sem nova aprovação para registrar fato técnico legítimo; retificação de negócio segue proprietário | Resultado HTTP não modifica status de negócio; evento contraditório é investigado | HT-08,HT-14 |
| OP-05-APP | Gerenciar aplicativo integrado | Administrador/delegado autorizado do tenant e serviço técnico escopado | INT-05, INT-02 e Identidade e Segurança | Permissões de apps e escopos, elegibilidade comercial/tenant | Aprovação para acesso ampliado ou ação crítica, quando política exigir; contratação depende de FIN/CON | Trial não libera API pública; não conceder acesso implícito a registros | HT-04,HT-13 |
| OP-05-API | Chamar API pública | Aplicação/usuário autenticado com delegação válida | INT-05 fronteira; proprietário confirma efeito | API habilitada no plano, tenant, app, token e operação/objeto/fields autorizados | Aprovação do efeito de negócio quando exigida, não apenas autenticação de API | Bloquear no trial, preservar integrações nativas permitidas; checar recurso individual | HT-04,HT-13 |
| OP-06-IN | Receber webhook externo | Provedor/assinatura identificados e validados no contexto de conexão | INT-06; motor de Eventos interno, proprietário de negócio | Validação de origem/autenticidade, tenant, evento, versão, proteção anti-replay | Nenhuma assinatura de webhook concede aprovação do ato de negócio | Sem segredo no payload/log; não assumir que webhook externo já é evento interno confirmado | HT-05,HT-16 |
| OP-06-OUT | Entregar webhook ao destino | Serviço autorizado a notificar assinante habilitado | INT-06 entrega; COM para comunicações ao cliente | Evento/assinante/destino/finalidade/escopo de campos e URL registrada autorizados | Configuração de assinatura e entrega sujeitas a alçadas de mudanças sensíveis | Impedir envio cross-tenant e vazamento de dados; HTTP de destino é aceite técnico | HT-05,HT-13 |
| OP-07-SYNC | Executar sincronização incremental | Serviço habilitado sob delegação de fonte/destino; operador autorizado | INT-07 coordena; proprietários gravam/confirmam | Entidade/campos/direção/fonte/destino e conexão vigentes | Conflitos ou alterações sensíveis exigem decisão do proprietário conforme política | Não usar ausência como exclusão automática; checkpoint depois de confirmação apropriada | HT-06,HT-08 |
| OP-07-RET | Retomar sincronização | Serviço homologado com revalidação; operador de recuperação habilitado | INT-07; INT-10 para incidente; proprietários para dados | Checkpoint íntegro, token/conexão/versão ainda válidos, efeitos não duplicáveis | Retomada de risco aumenta alçada conforme política, sem dupla aprovação para mesma decisão | Se estado incerto, reconciliar antes de avançar cursor ou repetir escrita | HT-06,HT-16 |
| OP-08-IMP | Importar/migrar/restaurar dados delimitados | Usuário autorizado à portabilidade e gestores dos dados envolvidos | INT-08 com Central IN-118; módulos proprietários | Direitos na origem e destino, entidade/campo e arquivo; prévia/manifesto e separação entre tenants | Confirmação/aceite para operação destrutiva ou conflito conforme política de proteção | Não restaurar segredos de outro tenant; não mesclar/destruir automaticamente registros | HT-07,HT-13 |
| OP-08-EXP | Exportar dados/pacote | Usuário/serviço autorizado a ler/exportar escopo do tenant | INT-08 e IN-118; proprietários dos dados; Arquivos central | Escopo de consulta e exportação, campos sensíveis, finalidade e duração do link | Exportação sensível/em massa pode exigir confirmação reforçada conforme política, sem revogar autoatendimento essencial | Franquia cheia de anexos não bloqueia exportação dos dados existentes; auditoria da extração | HT-17,HT-13 |
| OP-09-INI | Instanciar/processar fluxo multissistema | Operador/serviço autorizado pelo modelo/instância e pelo tenant | INT-09 coordena; cada dono decide efeito; motores centrais | Modelo/versionamento, autorização de cada etapa, recurso, aprovação e dependências | Aprovações específicas dos efeitos críticos, não aprovação universal pelo orquestrador | Revalidar etapa assíncrona; não dispensar dependência por sucesso independente | HT-09,HT-15 |
| OP-09-COM | Solicitar compensação | Responsável autorizado a solicitar reversão; executor competente | INT-09 coordena; FIN/CON/AGD/outro proprietário decidem e confirmam | Elegibilidade de reversão, efeito original, risco, escopo e estado atual | Aprovação específica do ato de compensação na alçada proprietária | Sem estorno/cancelamento automático por IA; compensação parcial continua explícita | HT-10,HT-15 |
| OP-10-DIA | Investigar incidente | Identidade técnica/funcionário com acesso de diagnóstico autorizado | INT-10; Observabilidade IN-159/160; Central de Incidentes | Acesso mínimo a telemetria por ambiente, serviço e tenant; dados minimizados | Monitoramento de baixo risco dentro das permissões existentes; nenhuma aprovação de negócio implícita | Não misturar operação interna Imagine e dados de tenants; telemetria ausente=desconhecida | HT-11,HT-16 |
| OP-10-REC | Executar recuperação técnica | Agente/operador escopado com playbook homologado | INT-10; proprietário dos efeitos; Identidade e Segurança | Ação e ambiente específicos permitidos; limites e pré-condições do playbook | N2 homologado/seguro pode seguir aprovação prévia; intervenção crítica requer aprovação humana específica quando exigida | IA não concede privilégios; resultado técnico não confirma operação de negócio | HT-11,HT-08 |
| OP-11-HOM | Homologar/publicar conector | Revisor e publicador autorizados; parceiro apenas submete quando permitido | INT-11; Segurança e Central de Aprovações | Permissão de homologar versão/artefato e ambiente; isolamento de terceiros | Publicação controlada exige aprovação humana adequada; não herdar aceite da versão anterior | Não executar código livre de terceiros nem distribuir antes de homologação | HT-12,HT-18 |
| OP-11-SUS | Suspender/revogar conector | Operador de catálogo/segurança com autoridade específica | INT-11 catálogo; INT-01 conexões; INT-02 escopos | Direito de restrição por conector/versão e abrangência de clientes | Aprovação conforme risco; contenção urgente homologada segue política técnica existente | Não migrar conexões ativas silenciosamente; notificar pelos serviços centrais competentes | HT-18,HT-12 |
| OP-12-INV | Consultar/investigar auditoria | Funcionário autorizado a investigar escopo definido | INT-12 visão; Auditoria Geral fonte oficial | Acesso por tenant, caso, classificação e finalidade, inclusive segregação interna | Alçada de investigação/visão sensível segundo política; não presumir gestor com acesso total | Consultas também auditáveis, hipóteses separadas de fatos, sem manipular evidência | HT-14,HT-17 |
| OP-12-AUD | Exportar evidências auditáveis | Responsável com permissão específica de exportação | Auditoria Geral; INT-12 interface de consulta/exportação | Direito de acesso e exportação por escopo, registro, período, finalidade, retenção | Confirmação reforçada e aprovação específica quando exigidas, sem download indiscriminado | Minimização e marca de proveniência; registrar a própria exportação; sem retenção ilimitada | HT-17,HT-14 |

**Todas as 24 linhas são propostas de interpretação e detalhamento de controles já aprovados**, não novas regras comerciais, perfis oficiais ou permissões concedidas. Os papéis genéricos (“gestor autorizado”, “responsável”) não definem quem recebe o privilégio; essa concessão requer a política e a revisão da operação.

## 7. Regras de atenção especial, sem novas competências

### 7.1. Multiempresa e alternância de contexto
A autenticação por login único não transforma contexto de empresa em autorização cruzada. Cada CT-02 deve testar tenant atual, empresa de origem, empresa do destino, recurso e identidade. O usuário com acesso a dois tenants não deve transferir um dado entre eles sem ato de portabilidade legítimo e específico.

### 7.2. Clientes de Sites Imagine e CRM Básico
A mudança de vínculo do site para outra conta CRM ocorre por chamado e alteração técnica gratuita da Imagine; a migração de dados é autoatendimento, conforme IN-118/122/123. Não criar permissão de “superadministrador” para redirecionar ou copiar históricos sem verificação. Com 100% da franquia de anexos utilizada, somente novos uploads de anexos são bloqueados, não exportação de dados existentes.

### 7.3. API pública e trial
No trial, API pública desabilitada, mas integrações nativas autorizadas continuam possíveis. Não confundir autenticação de servidor para a API de formulários com login do visitante de um formulário; nenhum segredo privilegiado no navegador ou URL pública.

### 7.4. n8n, agentes e playbooks
n8n e agentes operam com identidade técnica e ferramentas de menor privilégio, em alvos/ambientes definidos. A IA não é autor da decisão de autorização. Playbook N2 seguro, previamente homologado e autorizado pode executar conforme a política técnica já existente; intervenção crítica exige **uma aprovação humana específica quando exigida**, evitando duplicação da mesma aprovação. Não transformar prioridade do incidente em risco da ação.

### 7.5. Credenciais externas e consentimentos
INT-02 administra escopos/autorização externa; o cofre técnico IN-151 preserva segredos. Não presumir consentimento como única base legal para todo tratamento ou que autorização do provedor substitua permissão interna do CRM. Segredos, tokens, payloads pessoais e chaves nunca devem entrar em prompts ou logs.

### 7.6. Auditoria e retenção
A consulta e a exportação por INT-12 devem obedecer à Auditoria Geral, com controle por finalidade/escopo e retenção da categoria; períodos de IN-162 aplicam-se a logs técnicos e não automaticamente a todos os registros oficiais.

## 8. Matriz proposta de validação negativa e aceite documental

| ID de cenário proposto | Condição | Resultado esperado | HT associada |
|---|---|---|---|
| CT02-T01 | Usuário troca tenant e tenta executar comando de tenant anterior | Negar/suspender novo ato, preservar rastreabilidade de comando anterior | HT-13 |
| CT02-T02 | Credencial externa revogada entre fila e execução | Bloquear nova ação protegida sem reativar privilégio automaticamente | HT-02 |
| CT02-T03 | Aplicação tenta API pública durante trial | Negar API pública, permitir apenas integrações nativas elegíveis autorizadas | HT-04 |
| CT02-T04 | Serviço n8n inicia comando fora do escopo de sua identidade | Negar ação, registrar tentativa; sem superprivilégio | HT-13, HT-15 |
| CT02-T05 | Webhook válido transporta pedido de alteração sem aprovação de negócio | Aceite do evento não produz alteração proprietária não autorizada | HT-05 |
| CT02-T06 | Compensação solicitada por operador sem alçada para efeito financeiro | Bloquear compensação; encaminhar à competência do FIN | HT-10 |
| CT02-T07 | Uploads de anexos atingem franquia de 100%, cliente exporta dados existentes | Exportação autorizada preservada sem desbloqueio genérico de upload | HT-07, HT-17 |
| CT02-T08 | Colaborador tem leitura permitida de dado, mas tenta exportar evidência sensível | Verificar permissão específica de exportação; não presumir leitura=exportação | HT-17 |
| CT02-T09 | Alteração do vínculo site/CRM acontece com mensagens anteriores em fila | Não redirecionar silenciosamente pedidos antigos para nova conta | HT-09 |
| CT02-T10 | Autorização foi concedida para uma etapa, não para outra | Usar apenas a concessão válida naquele escopo/ato, sem dupla aprovação da mesma decisão | HT-09, HT-15 |
| CT02-T11 | Modelo de IA recomenda ação crítica sem autorização de ferramenta | Registrar recomendação; recusar execução | HT-15 |
| CT02-T12 | Conector é suspenso durante instância ativa | Negar novo uso incompatível, preservar fatos/pendências, submeter substituição à autoridade competente | HT-18 |

Cenários T01..12 são **roteiros candidatos** para os testes HT já aprovados, não novos cenários obrigatórios ou testes executados.

## 9. Pontos ainda sem decisão técnica específica

| Item | Necessidade | Estado |
|---|---|---|
| CT02-P01 | Vocabulário efetivo de direitos/perfis, relações RBAC e delegações por empresa/unidade | Pendente de desenho e validação |
| CT02-P02 | Quais atos exigem MFA adicional, confirmação ou aprovação humana e respectivas alçadas | Pendente por operação e risco |
| CT02-P03 | Matrizes de direitos por entidade/campo e contexto multiempresa | Pendente |
| CT02-P04 | Semântica de expiração, revogação, reuso de aprovação e credenciais externas por provedor | Pendente |
| CT02-P05 | Políticas de execução automática homologada, limites técnicos e separação por ambiente | Pendente |
| CT02-P06 | Representação física de evidências e decisão CT-02 no contrato CT-03/04 | Pendente |
| CT02-P07 | Regras de compatibilidade entre versões de política, conector e operações pendentes | Pendente |
| CT02-P08 | Cotejo literal das aprovações originais e cobertura exaustiva de requisitos | Pendente |
| CT02-P09 | Casos técnicos completos e execução dos testes no futuro | Não executados |

**Situação final desta minuta:** CT-02 foi detalhado como documento para revisão, utilizando as 24 fichas aceitas como base. **P0-OWN-01 e P0-SEC-01 permanecem abertos**, assim como P0-CON-01, P0-TR-01, P0-DOC-01, P0-VER-01 e P0-ACC-01. Nenhuma autoridade nova foi aprovada, nenhuma implementação iniciada.
