# PORTAL-COM-14 — LGPD, avisos, aceites e consentimentos no Portal e nos sites Imagine

**Status (02/11/2026): REQUISITO DE PRIVACIDADE SOLICITADO PELO RESPONSÁVEL — incluir os “aceites da LGPD brasileira”.** A existência dos avisos, bases legais e controles pertinentes entra no escopo do Portal e no checklist de entrega automatizada. **Detalhes de redação, hipóteses legais por finalidade, telas, parâmetros e responsáveis ainda devem ser definidos e validados antes de implementar.**
**REGRA ATUALIZADA (02/11/2026): para sites simples dentro de modelo jurídico homologado, a Política de Privacidade é publicada automaticamente com o site, após personalização/validações, sem aceite individual prévio do cliente; comunicar no Portal e permitir ajustes posteriores. Os itens históricos de aprovação prévia e lembretes de 3/7 dias não se aplicam ao fluxo padrão — COM-15 seção 14.**

**Decisão relacionada já aprovada:** o checklist de entrega com IA será **CONFIGURÁVEL POR TIPO DE PRODUTO**, sem permissão para desativar requisitos essenciais de qualidade, privacidade, segurança ou aceite contratual.
**Fase:** planejamento/documentação; nenhuma publicação, consentimento, mensagem, coleta de dados adicional, programação ou implantação real autorizada.
**Referências internas:** [PORTAL-COM-13 — checklist IA de entrega](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md), [PORTAL-D01 — aceite eletrônico de site/orçamento](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md), [PORTAL-COM-12 — relacionamento](PORTAL-COM-12-relacionamento-inteligente-retencao-expansao-clientes.md), [PORTAL-REL-01 — jornada](PORTAL-REL-01-jornada-pos-entrega-onboarding-30-dias.md), [IN-125 — gestão de formulários](IN-125-gestao-inteligente-formularios.md), [IN-150 — segurança de formulários/APIs](IN-150-seguranca-inteligente-formularios-apis.md), [PORTAL-01 — pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md). Histórico IN-091–IN-101 no ramo docs/integracao-site-crm-basico.

## 1. Bases jurídicas de referência

- **Lei nº 13.709/2018 (LGPD), texto vigente:** https://www2.camara.leg.br/legin/fed/lei/2018/lei-13709-14-agosto-2018-787077-normaatualizada-pl.html
- **ANPD — Perguntas frequentes, bases legais:** https://www.gov.br/anpd/pt-br/acesso-a-informacao/perguntas-frequentes
- **ANPD — Guia orientativo de cookies:** https://www.gov.br/anpd/pt-br/centrais-de-conteudo/materiais-educativos-e-publicacoes/guia_orientativo_cookies_e_protecao_de_dados_pessoais

**Princípio obrigatório de desenho:** a LGPD **não exige consentimento para todo tratamento**. Identificar finalidades e bases legais adequadas (consentimento, contrato, obrigação legal, legítimo interesse quando cabível, etc.), com transparência e proporcionalidade. **Nunca transformar “aceite da política de privacidade” em autorização genérica e forçada para marketing, analytics ou qualquer finalidade.** Quando **consentimento** for a base, deve ser livre, informado, inequívoco, para finalidade determinada, passível de prova e revogação facilitada; não usar opções pré-marcadas, silêncio ou navegação contínua como consentimento. Aviso de privacidade e termos contratuais **não são o mesmo documento nem o mesmo ato**.

**Revisão especializada:** modelos contratuais, avisos, cookies, responsabilidade dos agentes e bases legais por finalidade devem ser submetidos à revisão jurídica/privacidade competente antes da publicação; IA pode verificar presença e comportamento técnico, **não declarar conformidade jurídica absoluta por conta própria**.

## 2. Onde haverá documentos e controles de aceites (escopo aprovado em princípio; detalhes pendentes)

### 2.1 Portal Imagine — cliente contratante / usuários autenticados

- **Termos de Uso e Contratação:** aceite expresso, versionado e auditável do documento/condições quando a relação contratual o exigir. Preservar todas as regras já aprovadas na PORTAL-D01 sobre versão e autorização do cliente.
- **Aviso/Política de Privacidade do Portal Imagine:** disponível antes ou no momento pertinente, com informações claras sobre finalidade, controlador, contato, direitos, compartilhamentos, retenção e formas de exercício de direitos. **Dar ciência/acesso não equivale a consentimento para tudo**; não impor caixa “concordo com LGPD” se não houver consentimento apropriado a uma finalidade específica.
- **Comunicações comerciais e relacionamento inteligente:** separar notificações operacionais ligadas ao contrato de mensagens promocionais, usar bases legais específicas e registrar preferências por canal e finalidade, com respeito a oposição/descadastro e revogação quando aplicável. Relacionamento REL-01 não autoriza marketing indiscriminado.
- **Cookies e telemetria do Portal:** informar categorias, finalidades, provedores e bases, dar controles proporcionais; quando consentimento for a base, permitir recusa/revogação e não ativar a categoria antes da escolha afirmativa.

### 2.2 Sites públicos feitos pela Imagine — visitante/lead do cliente

- **Política/Aviso de Privacidade do site:** documento configurável por cliente/projeto, com identidade e contato do controlador adequado, categorias e finalidades de dados coletados, integrações, direitos e práticas de tratamento, a ser validado pelo responsável competente.
- **Formulários de contato, orçamento, agendamento quando elegíveis e outros:** fornecer informação adequada no momento de coleta; campo **separado para comunicações promocionais, se consentimento for a hipótese pertinente**. A submissão pode ser processada sob outra base adequada para atender à solicitação do usuário; **não bloquear desnecessariamente um pedido de contato por recusa ao marketing**.
- **Banner/central de cookies**, **quando houver cookies ou tecnologias que exijam informação/opção**: descrever categorias como necessários, análise/estatística e publicidade; se o processamento não essencial se basear em consentimento, permitir **aceitar, rejeitar e gerenciar preferências**, sem pré-seleções favoráveis ou ativação prévia incompatível com a finalidade/base. Cookies estritamente necessários devem ser tratados segundo suas bases e necessidade, **sem simular consentimento onde não é aplicável**.
- **Integração ao CRM Imagine:** verificar que destinatário/tenant é o correto, informação ao titular adequada, finalidades determinadas e consentimentos opcionais propagados com status certo quando forem relevantes. **Não transformar um consentimento de contato com a empresa-cliente em consentimento para a própria Imagine fazer marketing**.
- **Serviços específicos** como analytics, pixels, chatbot, WhatsApp, mapas, embeds e provedores internacionais exigem análise própria de finalidade, base, fornecedores/fluxo, transparência e eventuais transferências internacionais; não presumir que todos estão contratados.
- **Crianças, dados sensíveis e segmentos regulados:** aplicar avaliação e requisitos especiais quando o projeto envolver essas situações, sem ativar modalidade padrão antes de revisão adequada.

### 2.3 Relação controlador–operador e responsabilidades

A Imagine deverá manter seus papéis por tratamento efetivamente realizado: **na gestão comercial de seus próprios clientes e Portal, pode atuar como controladora**; **na hospedagem/integração de dados coletados pelo site de empresa-cliente, pode atuar como operadora**, a depender de quem determina finalidades/meios. A atribuição não é automática nem igual para todas as operações: documentar controlador, operadores, suboperadores e instruções/contratos pertinentes. O **cliente que decide finalidades** de seus formulários e marketing deve participar da validação das políticas, dados e fornecedores; agente IA **não pode inventar a base legal ou concordar em nome de alguém**.

## 3. Provas, versionamento e escolhas de titulares

Manter registro seguro e mínimo de eventos **quando houver aceite ou consentimento pertinente**, com:
1. Identificador do titular/representante ou contexto autenticado aplicável; não recolher documentos ou IP indiscriminadamente por padrão;
2. Identificador de cliente, projeto, ambiente, finalidade e **documento/termo/versão exata** apresentada;
3. Base legal definida para cada tratamento, estado **concedido/recusado/revogado/não aplicável** quando pertinente, canal e categorias de cookies;
4. Data/hora com fuso, origem e prova auditável proporcional, preservando histórico sem manipulação;
5. Mecanismo de **revisar preferências, revogar consentimento gratuitamente e exercer direitos LGPD**, com reflexo no motor de automações e nas integrações;
6. Prazo de guarda adequado à finalidade/obrigações, acesso por pessoal autorizado e descarte no prazo apropriado. **Não definir prazo universal sem avaliação**.

**A revogação/recusa de marketing não deve prejudicar indevidamente recursos contratuais essenciais.** Registro histórico de consentimento revogado pode ser mantido pelo prazo legitimamente necessário para comprovação, sem continuar o tratamento baseado no consentimento revogado.

## 4. Checklist configurável POR PRODUTO e requisitos globais irrenunciáveis

**Aprovação expressa em 02/11/2026 (“sim”)** à pergunta “Você aprova um checklist configurável por produto, preservando os requisitos obrigatórios de qualidade e segurança?”. **Diretriz vigente:**

- **Núcleo global obrigatório e não desativável por simples configuração:** identificação de cliente/site/versão/escopo, aceite expresso exigido da versão, segurança técnica mínima, privacidade e LGPD aplicáveis, integridade do formulário/entrega CRM se presente, evidências de testes, ausência de bloqueadores e regras de autorização do agente.
- **Modelo especializado por produto (Onepage, Landing Page e futuros):** selecionar testes próprios e critérios mensuráveis de páginas, layout, conteúdo, SEO, responsividade, formulários, analytics e integrações conforme o que foi efetivamente contratado.
- **Itens condicionais:** somente se recurso ou tratamento estiver presente; “NÃO APLICÁVEL” exige prova/justificativa auditável, não podendo esconder falhas legais/técnicas.
- **Administração/versionamento:** painel poderá atribuir a cada tipo de produto um checklist versionado, com catálogo de critérios, evidências, homologação de mudanças e regras de risco; **não permitir que administrador ou IA retire o núcleo obrigatório por mera edição**, nem que IA se autoatribua autorização ou aceite de titular.
- **Entrega automática:** somente se os itens globais e condicionais aplicáveis forem aprovados com evidências, inclusive as verificações LGPD do produto; em caso de falta de documento, consentimento requerido, comportamento incorreto de cookie ou dúvida jurídica bloqueadora, suspender entrega e **encaminhar à equipe humana/jurídica competente**. A conformidade não é uma certificação legal emitida pela IA.

## 5. Checklist LGPD sugerido para o agente IA (subcritérios ainda precisam de homologação)

| Verificação | Critério proposto | Aplicação |
|---|---|---|
| Inventário de dados e finalidades | O projeto possui inventário dos formulários, cookies, scripts e integrações relevantes, com finalidades/bases validadas | Conforme projeto; global avaliar aplicabilidade |
| Política de privacidade e responsável | Link acessível, versão correta, controlador/contato/finalidades/direitos adequados, aprovados pelo responsável competente | Global conforme tratamentos praticados |
| Aceites de contrato/projeto | Aceite expresso do cliente para versão/termos que exijam aceite; não é autorização global para processamento de dados de visitantes | Obrigatório quando exigido |
| Formulários | Transparência próxima ao botão de envio, coleta mínima e indicação clara da finalidade | Condicional ao recurso |
| Consentimentos opcionais | Marketing e outras finalidades específicas não vêm pré-marcados; recusa não bloqueia indevidamente pedido essencial; status prova/reflete escolha | Quando a base legal for consentimento |
| Cookies / trackers | Scripts opcionais respeitam base e preferências; se dependerem de consentimento, **não são ativados antes do opt-in**; preferências podem ser alteradas/revogadas | Condicional ao uso |
| Destino e transferência | Site → API → CRM vinculado correto, sem compartilhar dados/consentimentos com tenant errado; provedores informados | Quando houver integrações |
| Direitos e segurança | Canal para direitos, acesso restrito, retenção a definir, dados sintéticos em testes, sem expor dados privados | Conforme projeto |
| Versões e evidências | Registrar versão dos textos/políticas, teste funcional do banner e dos campos, resultado e responsável | Obrigatório para itens aplicáveis |
| Revisão de exceção | Dados sensíveis, crianças, integração não padrão, dúvida de base legal ou processo de alto risco exige revisão humana antes da entrega | Quando sinalizado |

**Importante:** o checklist de IA confirma **funcionamento técnico e presença de políticas validadas**; **não é parecer jurídico nem prova absoluta de conformidade à LGPD**. Critérios legais/bases aprovados e textos de referência são responsabilidade dos agentes humanos competentes.

## 6. Relação com relacionamento e oportunidades comerciais

A central COM-12 e a REL-01 devem usar somente **contatos autorizados e base apropriada por finalidade/canal**. Um cliente da Imagine ter comprado um site **não equivale a consentir automaticamente receber publicidade por WhatsApp**, nem permite usar os leads/visitantes do site desse cliente para vender produtos da Imagine. Registrar preferências, deduplicação e limites; ao receber revogação/oposição válida, interromper fluxos afetados. **Avisos operacionais legítimos e conteúdo educativo relacionado à prestação do serviço têm análise própria e não devem ser usados como disfarce para marketing**.

## 7. Pendências antes de execução

1. **Central de Documentos Legais e Consentimentos APROVADA na COM-15:** cadastro e manutenção administrativa, versões e histórico de aceites, com associação por cliente/site. **Pendente:** modelos de **Termos do Portal**, Política de Privacidade Imagine e templates configuráveis dos sites e suas aprovações jurídicas e dos controladores.
2. Matriz de finalidade × dados × base legal × controlador/operador × prazo de retenção × terceiros.
3. Inventário de cookies, pixels e tecnologias de rastreamento por produto/serviço; requisitos do banner/central de preferências e testes automatizados.
4. Diferenciar **aceite contratual**, **ciência de aviso** e **consentimento específico** nos eventos, interfaces e arquivos de prova.
5. Definir quando a recusa implica apenas bloquear a finalidade opcional, sem bloquear contrato/formulário/entrega por ausência de consentimento não exigido.
6. Canais para titulares, revogação, resposta a solicitações e propagação a integrações; política de retenção e documentação do fluxo.
7. Checklist por produto, identificadores/versões, parâmetros de testes, perfil de administração, auditoria e homologação antes de ativar agente IA.
8. Revisão jurídica brasileira de documentos e fluxos, inclusive situações de dados de crianças/sensíveis e transferências internacionais quando pertinentes.

**Resultado:** requisito LGPD e aceites/controles legais pertinentes **incluídos no planejamento**, núcleo obrigatório de checklist não desativável e checklist **configurável por produto APROVADO**. **Nenhuma programação, campanha ou coleta adicional iniciada.**


## 8. Aprovação da Central de Documentos Legais e Consentimentos — COM-15 (02/11/2026)

O responsável respondeu **“ok”** à pergunta expressa sobre criar **Central de Documentos Legais e Consentimentos**, com **gestão administrativa, versionamento e histórico de aceites**, documentos por projeto/site/cliente e validação do responsável quando cabível. **Módulo APROVADO** como governança dos textos e registros previstos nesta COM-14. A Central deverá permitir cadastrar, editar, revisar, publicar, consultar e versionar **Termos, Políticas de Privacidade e consentimentos por finalidade** sem mudança de código para cada texto homologado; preservar versão exata de aceite/consentimento e revogação. Separar **aceite contratual**, **ciência de aviso de privacidade** e **consentimento específico quando essa for a base legal**; não exigir consentimento universal.

**Documentos de sites dos clientes:** aplicar modelos adequados ao controlador, serviços, formulários, scripts/cookies e contexto, com aprovação competente antes da publicação, preservando segregação entre empresas. **Integração COM-13:** entrega IA só quando documentos legais exigidos estiverem vigentes e mecanismos aplicáveis passarem testes, sem IA certificar suficiência jurídica. Detalhes em [COM-15](PORTAL-COM-15-central-documentos-legais-consentimentos.md). Textos, alçadas e prazos de retenção ainda pendentes; sem programação ou publicação.


## 9. Fluxo aprovado de validação jurídica e aprovação por responsável — COM-15 (02/11/2026)

**Aprovado expressamente (“sim”):** documentos jurídicos **da própria Imagine** serão aprovados pela **administração Imagine**, após revisão legal/privacidade necessária; **Políticas de Privacidade específicas de sites dos clientes** poderão ter minuta assistida por IA, mas dependerão da **validação/aceite explícito do cliente responsável por aquele site, por representante habilitado, antes de sua publicação**. Ausência de aprovação não pode ser suprida por silêncio, agente IA ou aceite comercial distinto. Conservar versão exata, histórico, responsável, data/hora e correções, e encaminhar riscos legais a revisão humana competente. Se a política exigida não tiver sido aprovada e disponibilizada, o checklist de entrega COM-13 **bloqueia o estado ENTREGUE**, sem desnecessariamente exigir consentimento genérico dos visitantes. Os aceites de versão de site (D01), ciência de aviso e consentimentos por finalidade seguem independentes. Registro detalhado [COM-15](PORTAL-COM-15-central-documentos-legais-consentimentos.md). Nenhuma publicação/implementação iniciada.


## 10. Política padrão entregue ao cliente para aprovação ou ajustes — COM-15 (02/11/2026)

**Regra funcional aprovada posteriormente:** todo novo site Imagine terá, por padrão, **uma Política de Privacidade de modelo oficial previamente revisado**, instanciada e adaptada aos dados reais da empresa/controlador e aos tratamentos, formulários, cookies e integrações aplicáveis. Antes da liberação pública, o cliente responsável visualiza a versão no Portal e escolhe **“Aprovar Política de Privacidade”** ou **“Solicitar Alterações”**. Em caso de ajustes, gerar nova versão e devolver para aprovação. **Somente uma versão aprovada expressamente pelo representante autorizado do cliente pode ser publicada como política definitiva no site**. Silêncio ou uso do serviço não aprovam o texto. Guardar versão/hash, aprovador, data/hora, histórico de pedidos e versões. O modelo padrão **não garante conformidade LGPD por si só**; casos especiais exigem revisão legal específica. **Entrega COM-13 fica bloqueada enquanto não houver a política específica aprovada e publicada quando exigida**. Aprovação da política pelo cliente é distinta de consentimentos dos visitantes ou do aceite comercial. Especificação [COM-15](PORTAL-COM-15-central-documentos-legais-consentimentos.md). Planejamento, sem publicação real.


## 11. Revisão assistida por IA da política solicitada pelo cliente — COM-15 (02/11/2026)

**APROVADO (“ok”)** o fluxo inteligente: quando o cliente solicitar alterações à Política de Privacidade padrão específica de seu site, um agente de IA poderá **analisar os pedidos, preparar uma nova minuta versionada e apresentá-la novamente ao cliente para aprovação**. A IA deverá respeitar modelo previamente validado, finalidade/base legal e dados reais; **alterações de alcance jurídico, dados sensíveis, direitos dos titulares, transferência internacional, informações contraditórias ou casos fora do padrão exigem revisão humana/jurídica** antes de voltar ao cliente. O cliente mantém poder de aprovação/novo pedido de ajustes; **não há publicação por silêncio nem aprovação pela IA**. A prova de consentimento dos visitantes permanece separada do aceite da política pelo cliente. Site só passa a ENTREGUE quando a versão correta da política estiver aprovada pelo cliente e publicada, além dos demais requisitos do checklist. Documento principal [COM-15](PORTAL-COM-15-central-documentos-legais-consentimentos.md). Sem programação.


## 12. Avisos automáticos de aprovação da política por Portal e e-mail — COM-15 (02/11/2026)

**APROVADO (“ok”):** toda vez que a Política de Privacidade padrão do site ou uma versão revisada estiver pronta para **aprovação expressa do cliente**, disponibilizar **notificação interna no Portal Imagine e aviso por e-mail**, com link seguro/autenticado para a **versão exata**. A notificação é **operacional**, não autorização para ofertas comerciais; informar destinatários legitimados, minimizar dados no e-mail e impedir exposição entre empresas, com registro auditável de envios/falhas e deduplicação por evento/versão. **Visualizar e-mail ou notificação não representa aprovação nem consentimento de visitantes**. Publicação segue bloqueada enquanto faltar aceite do cliente; entrega COM-13 também. **Primeiro lembrete após 3 dias úteis foi APROVADO posteriormente na COM-15; outros lembretes periódicos, WhatsApp/SMS, modelos de texto e horários detalhados não foram aprovados**. Referência [COM-15](PORTAL-COM-15-central-documentos-legais-consentimentos.md). Apenas planejamento.


## 13. Primeiro lembrete de aprovação após 3 dias úteis — LGPD e COM-15 (02/11/2026)

**APROVADO (“sim”)** o **primeiro lembrete automático por Portal Imagine e e-mail, após 3 dias úteis**, caso o cliente não tenha respondido à solicitação de aprovação da **versão específica da Política de Privacidade de seu site**. Cancelar lembrete se cliente aprovar, solicitar alterações, perder permissão, se a versão for substituída ou o fluxo suspenso. **Nova versão revisada** abre novo ciclo somente após disponibilização, com aviso inicial e prazo próprio. **No máximo um primeiro lembrete aprovado por versão/ciclo**, com destinatário autorizado, link autenticado à versão, registro e deduplicação; não incluir publicidade. Aviso, clique e silêncio **não constituem aceite**, não dispensam consentimentos necessários nem autorizam publicação da política/entrega do site. Calendário de dias úteis, feriados, horário de envio, texto e custos ainda pendem de detalhamento. WhatsApp/SMS, segundos lembretes e recorrência não aprovados. [COM-15](PORTAL-COM-15-central-documentos-legais-consentimentos.md). Somente planejamento.


## 14. DECISÃO POSTERIOR — Política padrão publicada automaticamente, com ciência e ajustes posteriores (02/11/2026)

**APROVADO (“sim”)**, substituindo as orientações anteriores de aprovação obrigatória prévia das seções 8–13 **somente para o fluxo normal dos sites simples elegíveis**: a Imagine disponibiliza **Política de Privacidade padrão já revisada juridicamente**, adaptada por agente de IA ao controlador/empresa-cliente, formulário, CRM, cookies, fornecedores e tratamentos **efetivamente validados**, e **publica automaticamente a versão apropriada junto com o site**, sem aguardar um clique individual do cliente em “Aprovar Política”. **Comunicar ao cliente no Portal e oferecer opção de solicitar alterações após a publicação**, com revisão inteligente versionada e supervisão jurídica quando necessária.

**Preservar LGPD:** dados e base legal não podem ser inventados; transparência e segurança mínimas, direitos dos titulares, consentimentos quando necessários e implementação de preferência de cookies continuam obrigatórios. O agente IA não certifica adequação jurídica absoluta. **Tratamento fora do escopo do modelo** (dados sensíveis/de crianças, práticas especiais, tracking/cookies/terceiros fora do catálogo jurídico) exige revisão específica e só pode ser ativado quando autorizado e legalmente adequado. A ausência de aprovação individual da política **deixa de ser bloqueador da publicação e da entrega oficial** em sites padrão se todo checklist restante for aprovado. Aceite da versão do site em D01 e consentimento de visitantes não foram alterados.

**COM-15 seção 14 prevalece sobre os itens históricos anteriores** que exigiam aprovação prévia para publicar a política padrão e sobre notificações/lembretes de 3/7 dias para coleta desse aceite. Esses lembretes não são convertidos em notificações de revisão sem nova decisão; novos fluxos excepcionais exigirão desenho específico. [COM-15](PORTAL-COM-15-central-documentos-legais-consentimentos.md) e [COM-13](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md). Somente planejamento.
