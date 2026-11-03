# PORTAL-GOO-01 — Integrações Google no Portal Imagine: conectividade, provisionamento assistido e autonomia do cliente

**Status (02/11/2026): DIRETRIZ FUNCIONAL DE INTEGRAÇÃO SOLICITADA PELO RESPONSÁVEL.** No debate sobre métricas de acesso, SEO e Google Analytics/Search Console, o responsável determinou incluir no relatório/planejamento a **possibilidade de o cliente conectar essas ferramentas pelo sistema de integrações do Portal Imagine**, enquanto a **Imagine cria e mantém o modelo técnico/padrão de integração e realiza configuração e integração quando necessário**. A interpretação operacional adotada é conexão de **contas/propriedades Google do cliente** ou provisionamento orientado pela Imagine, sem criar uma identidade Google em nome de alguém sem autorização. Os três blocos MON-01 / SEO-01 / GOO-01 sugeridos ainda dependem de fechamento formal de escopo; esta especificação cobre inicialmente o **GOO-01** e suas interfaces com métricas/SEO.
**CLAREZA OPERACIONAL POSTERIOR (03/11/2026): cliente escolhe a conta Google, cria a conta Google se não tiver e autoriza todos os acessos/escopos pertinentes; Imagine disponibiliza o conector e automatiza o que for tecnicamente possível DEPOIS das autorizações. O botão de entrada é “Realizar integração” dentro de uma área de Integrações com explicação de cada serviço. Esta regra prevalece sobre interpretações anteriores de que Imagine criaria contas de usuário Google para clientes ou poderia selecionar/autorizá-las em nome deles.**
**ESCOPO INICIAL AMPLIADO E APROVADO (03/11/2026): disponibilizar desde a PRIMEIRA VERSÃO do Portal as cinco integrações Google Analytics 4, Search Console, Tag Manager, Google Ads e Perfil da Empresa no Google, com conectores reais, fluxo de autorização e automação conforme recursos/limites de API. As três últimas deixam de ser apenas previsão futura/opcional por etapa, mas continuam optativas PARA CADA CLIENTE e condicionadas a elegibilidade/permissões do provedor. Ver seção 10.**
**Fase:** PLANEJAMENTO — sem programação, provisionamento real de propriedades, acesso a contas Google, coleta de dados, instalação de tags, publicações ou contratação.
**Conexões:** [PORTAL-01 — Matriz do Portal](PORTAL-01-matriz-pendencias-plataforma-sites.md), [PORTAL-COM-13 — Checklist IA de entrega](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md), [PORTAL-COM-14 — LGPD](PORTAL-COM-14-lgpd-aceites-privacidade-consentimentos-checklist.md), [PORTAL-COM-15 — Política padrão de privacidade](PORTAL-COM-15-central-documentos-legais-consentimentos.md), [IN-095/096/097 — Produção/Cloudflare/Central de Sites no histórico de decisões], [IN-125 — Formulários](IN-125-gestao-inteligente-formularios.md).

## 1. Objetivo funcional aprovado de alto nível

A Imagine **desenvolverá e administrará a infraestrutura de integração**, os conectores/API e modelos de configuração, concentrando resultados de sites no Portal. Cada cliente poderá, por sua empresa/site, **vincular propriedades existentes das ferramentas do Google pelo módulo Integrações**, com controle de permissões e ajuda guiada, sem entregar senhas ao suporte Imagine. A Imagine também poderá realizar **configuração/provisionamento assistido ou automático do que as APIs e permissões permitirem**, com autorização do proprietário legítimo e aceite de Termos dos provedores quando aplicável.

A escolha de usar uma propriedade administrada pela Imagine, criar propriedade vinculada ao cliente ou conectar uma já existente **não deve resultar em perda de acesso, troca silenciosa de titularidade, compartilhamento indevido entre clientes ou duplicação de tags sem necessidade**. O desenho definitivo de propriedade, administração, portabilidade e custos ainda será homologado.

## 2. Dois percursos que o Portal deverá oferecer

### 2.1 Cliente já tem conta/propriedade Google

1. No Portal Imagine, escolher **Integrações → Google → Conectar** (nome de interface em proposta), e o serviço desejado, p.ex. **Google Analytics 4 (GA4)** ou **Google Search Console**.
2. Encaminhar à página oficial de autorização do **Google OAuth 2.0**, solicitando **somente escopos necessários** e exibindo finalidade, permissões e conta selecionada. **Não pedir nem armazenar senha Google.**
3. Depois da autorização, enumerar **somente contas/propriedades acessíveis**, para que o cliente selecione **a propriedade correta do seu domínio/site**. Exigir validação de propriedade/domínio e permissões necessárias. A conta Google de uma agência ou múltiplas empresas pode ter muitos sites: não capturar automaticamente tudo.
4. Verificar conectividade e integridade, detectar tags/medições já presentes para **não instalar rastreadores em duplicidade**, vincular IDs à empresa/site no Portal e começar a coleta agendada apenas após checagem de LGPD, condições de uso e permissões. Exibir **Conectado, Pendente de autorização, Sem permissão, Erro, Desconectado** (estados de desenho).
5. Permitir **reconectar, reautorizar, alterar propriedade mediante autorização, suspender coleta e desconectar/revogar**; registrar histórico/auditoria sem disponibilizar dados de outros clientes. Para alterações de vínculo ou controle de titularidade, exigir fluxo de aprovação seguro compatível com o risco.

### 2.2 Cliente ainda não tem propriedade configurada

1. A Imagine mantém **modelos homologados de configuração**, integrações Google APIs e rotinas de provisionamento. O usuário poderá solicitar a **configuração assistida ou automática a partir do Portal**, com explicação de acesso, dados e condições exigidas.
2. **Google Analytics:** quando o cliente tiver uma conta Google autorizada e uma conta Analytics apta, a **Analytics Admin API** prevê criação de **propriedades GA4 e fluxos de dados web** com escopo de edição. A criação de uma **nova conta Analytics** tem fluxo de provisionamento com **aceite de termos** pelo usuário; não prometer criação silenciosa de novas contas Google/Analytics. Checar permissões, limites, verificações do Google e custos antes da implementação.
3. **Search Console:** a API permite **adicionar propriedades**, e a **Google Site Verification API** pode apoiar a verificação de titularidade de domínio/URL quando houver autorização e método compatível (p.ex. DNS/arquivo). Adicionar uma propriedade na API **não dispensa comprovação de titularidade** nem atribui propriedade arbitrária ao cliente.
4. **PageSpeed Insights / Lighthouse:** executar testes por URL com dados públicos sem obrigar conexão de conta Google para cada site; vincular resultado à plataforma e preservar quotas, limites e caráter pontual/sintético da medição.
5. **Tag Manager, Google Ads e Business Profile:** oferecer **conectores opcionais e separados** quando contratados/solicitados; não ativar rastreamento/marketing, campanhas, cobrança, perfil de negócio ou vinculação publicitária automaticamente. As APIs de Business Profile/Ads dependem de aprovação, elegibilidade, termos e escopos próprios.

## 3. Dados no painel central Imagine

- **GA4:** visualizações/sessões, fontes de aquisição, eventos e conversões de navegação *quando a instrumentação e as condições de privacidade estiverem ativas*. **Não prometer equivalência GA4 = volume real de todos os visitantes**, pois bloqueadores de scripts e escolhas de consentimento podem reduzir a medição.
- **Search Console:** impressões, cliques orgânicos, CTR, consultas, páginas, dispositivos, posição média, desempenho de indexação/sitemaps *conforme cobertura e limites da API*. **Não confundir CTR de SEO com CTR de anúncios**, nem equiparar os cliques GSC às sessões GA4.
- **PageSpeed/CrUX:** auditorias e Core Web Vitals com limites e dados reais quando houver amostra suficiente; não presumir dados CrUX para site novo/baixo tráfego.
- **Infraestrutura Imagine/Cloudflare:** publicação, domínio/SSL, disponibilidade, erros e telemetria permitida. Viabilidade da Cloudflare Web Analytics para muitos sites ainda depende de limitações e escala verificadas.
- **CRM Imagine:** contatos/leads efetivamente aceitos no backend e tenant correto, origem e vínculo legítimo; não contar clique em botão ou formulário falhado como lead confirmado.
- Todos os indicadores deverão incluir **fonte**, janela/atualização, ausência de dados, limites/amostragem e definição, com separação por cliente/site, auditabilidade e comparações consistentes.

## 4. Segurança, titularidade, autorização e LGPD

- A conexão Google é uma **autorização expressa, verificável e revogável**. O Portal nunca captura senhas do Google. Manter tokens OAuth **criptografados em cofre de credenciais**, com rotação/revogação, escopos mínimos, auditoria de conexões e acesso interno restrito; não gravar credenciais no site público ou em arquivos GitHub.
- Se a propriedade pertence ao **cliente**, preservar o acesso administrativo apropriado do cliente; a Imagine opera mediante permissão. Se a Imagine provisionar dentro de estrutura de sua administração, documentar **quem é proprietário e administrador, como garantir acesso/transferência/portabilidade ao cliente e o que acontece no cancelamento**. Não prometer transferência automática incompatível com regras Google.
- Quando possível, reutilizar propriedades existentes em vez de duplicar dados/histórico. Verificação de domínio Search Console exige poderes legítimos sobre DNS/site. Google pode exigir verificações, autorizações, limites e aceites de termos que impedem operação totalmente automática.
- **LGPD:** fazer inventário de scripts/cookies e finalidades. Não instalar/ativar GA4, GTM, Ads, pixels e quaisquer tecnologias não essenciais sem transparência, base legal adequada e preferências/consentimento quando aplicável; a mera vinculação OAuth **não é autorização LGPD de visitantes**. O recurso opcional pode permanecer desligado e o site seguir funcionando dentro do núcleo mínimo legal.
- **Não unificar dados pessoais brutos de visitantes entre clientes**, não transformar leads dos sites em base comercial da Imagine, e não reutilizar tokens de um cliente em outro.
- Separar status de **integração disponível**, **propriedade conectada**, **tag ativada**, **coleta autorizada**, **dados recebidos** e **relatório atualizado**; evitar mostrar sucesso falso ao cliente.

## 5. Responsabilidade Imagine e autonomia do cliente

| Função | Imagine | Cliente |
|---|---|---|
| Criar conectores, telas e modelos padrões | Responsável pelo desenvolvimento e manutenção | Usa integrações disponíveis |
| Configuração inicial de site/SEO básico e monitoramento técnico | Automação Imagine dentro do serviço contratado | Acompanha no Portal |
| Conectar conta Google/propriedade existente | Oferece OAuth, suporte e validação técnica | Autoriza e seleciona conta/propriedade |
| Provisionar novas propriedades Analytics/Search Console | Pode automatizar/assistir onde API, escopos e termos permitem | Confirma identidade, permissões, titularidade/termos quando aplicável |
| Vincular serviços opcionais (Ads, GTM, Perfil da Empresa) | Disponibiliza conector se homologado/contratado | Autoriza/solicita separadamente |
| Desconexão e portabilidade | Processa fluxo seguro e preserva evidências | Pode solicitar/autorizar, conforme permissões |

## 6. Fluxo de implementação por etapas (proposta, não execução)

1. **GOO-01:** cadastro de conectores Google por site/cliente, OAuth seguro, propriedade existente, estados de integração, reconexão/revogação e validação de domínio. Iniciar pelo Search Console e GA4 (sujeito à política de privacidade).
2. **Provisionamento opcional:** automatizar propriedades GA4/stream e fluxos Search Console/verificação autorizada, tratando aprovação de Termos do Google e disponibilidade de permissão. Sem criar contas Google pessoais em nome de clientes.
3. **MON-01:** consolidar dados de GA4, Search Console, PageSpeed/CrUX, Cloudflare e CRM com qualidade de dados, períodos, fontes e indicadores descritos.
4. **SEO-01:** checklist pré-publicação (título/meta/sitemap/robots/canonical/HTML semântico/desempenho), monitoramento de indexação, ações técnicas de risco baixo e sugestões de conteúdo para aprovação conforme regras Imagine.
5. **Evolução opcional:** GTM/Google Ads/Business Profile por produto, contrato, elegibilidade, privacy gate e aprovação do cliente.
6. Homologar custos, quotas de APIs, número de sites por conta, limites Google/Cloudflare, revisão jurídica, suporte, mudança de domínio/cancelamento e migrações antes de desenvolver.

## 7. Pendências a deliberar

1. Política comercial: o que será incluído para todos, quais conectores serão opcionais/pagos e se haverá limite de consultas/dados/relatórios por plano.
2. **Titularidade e administração das propriedades** criadas automaticamente pela Imagine; acesso do cliente e regras de transferência/continuidade após cancelamento.
3. Qual fluxo o cliente pode iniciar **sozinho** e quais mudanças exigem autorização Imagine e validação de domínio (e.g., alteração de propriedade).
4. Estratégia de domínio verificado e eventual subdomínio fornecido pela Imagine; OAuth, verificação e escopos.
5. Cadência das consultas, retenção de dados, regras LGPD/consentimento, qualidade de dados/erros de API, limites de custo, disponibilidade e contratos Google.
6. Definição final dos módulos MON-01/SEO-01 e da autonomia de IA para mudanças de conteúdo, URL, SEO e tagueamento: a aprovação nesta interação cobre a **possibilidade de integração e responsabilidade de construir os conectores**, não aprova ativação indiscriminada de publicidade e rastreamento.

## 8. Referências técnicas oficiais para homologação

- Google Analytics Admin API — criar propriedade: https://developers.google.com/analytics/devguides/config/admin/v1/rest/v1beta/properties/create
- Google Analytics Admin API — provisionamento de conta com aceite de termos: https://developers.google.com/analytics/devguides/config/admin/v1/rest/v1beta/accounts/provisionAccountTicket
- Google Analytics Admin API — criar data stream: https://developers.google.com/analytics/devguides/config/admin/v1/rest/v1beta/properties.dataStreams/create
- Google OAuth 2.0 — web server: https://developers.google.com/identity/protocols/oauth2/web-server
- Search Console API — sites: https://developers.google.com/webmaster-tools/v1/sites
- Google Site Verification API: https://developers.google.com/site-verification/v1/getting_started
- Search Console API — métricas: https://developers.google.com/webmaster-tools/v1/searchanalytics/query
- Search Console API — overview: https://developers.google.com/webmaster-tools/about

**Resultado:** requisito funcional de **conectores Google e associação pelo cliente via Portal**, com infraestrutura e configuração mantidas pela Imagine, foi incluído no planejamento. **Nenhuma programação ou integração efetiva iniciada.**


## 9. REGRA VIGENTE — Área de integrações no Portal, contas Google escolhidas/criadas pelo cliente (03/11/2026)

**Definição expressa do responsável:** “A ideia e termos uma parte no portal para integração e nessa parte tenha Google Analytics um texto explicando e o botao realziar integração. Realizaremos o que for possivel de forma automatica mas a parte de definir qual vai ser a conta do goole ou criar a conta no google e dar as permissoes todas serao o cliente”. **REGRA FUNCIONAL APROVADA COMO ESCLARECIMENTO.**

### 9.1 Interface por serviço de integração

Criar no **Portal Imagine → Integrações** catálogo de serviços disponíveis por site/empresa, começando por **Google Analytics** e expandindo a Google Search Console e demais conectores após aprovação/homologação. Cada serviço deverá mostrar:

- **Nome e descrição curta**: o que é a ferramenta, quais informações serão acessadas e qual a utilidade dos relatórios no Portal.
- **Condições de uso**: necessidade de conta Google, propriedade correspondente ao site, autorizações solicitadas, possíveis requisitos de verificação de domínio, custos/limites externos quando aplicáveis e regras de privacidade/consentimento.
- **Botão principal: “Realizar integração”**, quando ainda não conectado; depois, estado claro de **Conectado / Aguardando autorização / Erro ou permissão insuficiente / Desconectado**, com opções para ver detalhes, reconectar ou desconectar se permitido.
- **Associação explícita ao site**: se cliente possuir múltiplos sites e contas, indicar em qual site e em qual empresa a conexão será usada; não presumir vínculo automático a todas as propriedades Google.

O texto de explicação e ajuda deve ser administrável/atualizável pela equipe Imagine conforme documentação e mudanças oficiais do provedor. Não substituir as autorizações oficiais Google por checkboxes meramente internos.

### 9.2 Responsabilidades e sequência do botão “Realizar integração”

1. **Cliente inicia no Portal:** escolhe o serviço (por exemplo, Google Analytics) e site, lê as explicações e clica em **Realizar integração**.
2. **Conta Google é responsabilidade do cliente:** o sistema redireciona para o fluxo oficial Google; o **cliente escolhe qual conta Google existente utilizar ou cria ele mesmo uma conta Google**, se ainda não tiver. A **Imagine NÃO cria contas Google pessoais dos clientes, NÃO escolhe a conta por eles e NÃO recolhe credenciais/senhas**.
3. **Permissões também são responsabilidade do cliente:** o cliente analisa e **concede/nega explicitamente** permissões apresentadas pelo Google, inclusive requisitos para acesso aos produtos e a propriedades. Se recusar ou não possuir permissão suficiente, integração fica **pendente de autorização**, sem simular sucesso.
4. **Imagine automatiza a configuração subsequente:** depois de obter autorização legítima e escopos necessários, o conector descobre contas/propriedades acessíveis, **orienta o cliente a selecionar a propriedade relevante quando necessário** e configura, associa, verifica, testa e sincroniza o máximo que APIs/limites/termos permitirem; agentes IA poderão auxiliar nas rotinas seguras homologadas. A eventual **criação técnica de uma propriedade GA4, de fluxo web, verificação Search Console ou configuração de tag** poderá ser automatizada **dentro da conta escolhida/autorizada pelo cliente**, conforme possibilidades reais das APIs, escopos e exigências Google. **Criar propriedade/stream NÃO equivale a criar conta Google do cliente**. Se Google exigir passos manuais, mostrá-los ao cliente com instrução clara e retomar o fluxo.
5. **Conferência e comunicação:** verificar conexão e escopos, identificar eventos/tags duplicados, garantir LGPD e autorização para coleta antes de ativar scripts de tracking, exibir resultado e último status de sincronização. A conexão OAuth **não é consentimento dos visitantes**; análise pode permanecer configurada, porém sem coleta ativa, caso controles de privacidade ainda estejam pendentes.
6. **Administração posterior:** cliente poderá consultar status, reautorizar ou desconectar via Portal dentro das regras aprovadas; mudanças críticas (por exemplo, mudança da propriedade vinculada) exigem confirmação segura, auditoria e tratamento de consequências. O cliente deve continuar controlando sua conta Google e permissões externas.

### 9.3 Exemplo de conteúdo na área de integrações (texto de referência, não tela implementada)

**Google Analytics** — “Acompanhe visitas, origem dos acessos e interações no seu site. Conecte sua conta Google para disponibilizar os indicadores compatíveis no Portal Imagine. Se você ainda não possui conta Google ou acesso ao Analytics, o processo indicará os passos necessários.”

**Botão:** **Realizar integração**

**Nota de privacidade:** “A medição de acessos depende das configurações do seu site e dos requisitos de privacidade aplicáveis. A autorização de conexão não ativa automaticamente cookies/tags não essenciais.”

**Google Search Console** — “Acompanhe como seu site aparece na Pesquisa Google, incluindo consultas, impressões, cliques e indexação. Conecte uma conta Google com acesso à propriedade do domínio ou siga as etapas oficiais para confirmar a titularidade.”

**Botão:** **Realizar integração**

### 9.4 Decisão vigente e limites

**APROVADO NESTE ESCLARECIMENTO:** criar **área de integrações no Portal**, com **cartão/explicação por ferramenta e botão “Realizar integração”**, **cliente responsável por escolher ou criar sua conta Google, fazer login no provedor e conceder permissões**, enquanto a **Imagine automatiza todas as etapas técnicas possíveis depois dessas autorizações**, sem pedir senhas.

**PENDENTE DE FECHAMENTO:** lista inicial de integrações expostas, textos definitivos, fluxos específicos por serviço, política de titularidade/acesso das **propriedades** Google e portabilidade, aprovação OAuth do aplicativo pela Google se exigida, escopos, relação de permissão administrativa Google, o que cada plano inclui, limite de sites/propriedades, verificações LGPD e homologação. Nenhuma programação nem conexão real.


## 10. DECISÃO APROVADA — cinco integrações Google funcionais no lançamento (03/11/2026)

**Resposta expressa do responsável à proposta de disponibilizar inicialmente somente GA4 e Search Console e deixar GTM, Ads e Perfil da Empresa como opcionais futuros:** “ja vamso deixar as cinco ferramentas preparadas pois o cliente pode querer ja utilizar as demais”. **DECISÃO APROVADA: todas as cinco integrações deverão compor o catálogo funcional da versão inicial, sem deixar a implementação das três últimas para uma fase futura.** O uso por cliente continua facultativo, conforme produto/conta, permissões e LGPD; preparação real de APIs e homologação são pré-requisitos para anunciar conexão operacional.

### 10.1 Catálogo inicial obrigatório — cinco cartões de integração

| Integração | Explicação de valor ao cliente | Conexão mínima planejada |
|---|---|---|
| **Google Analytics 4** | Acompanhar visitas, sessões, origens de tráfego, páginas e eventos de conversão quando instrumentados | Autorização do cliente, seleção/criação de propriedade e stream dentro da conta permitida, ligação ao site, verificação de tag/consentimento e dados via API |
| **Google Search Console** | Ver pesquisa orgânica, consultas, impressões, cliques, CTR, indexação e sitemap | Autorização, seleção/adicionar propriedade do domínio, verificação de titularidade e leitura de dados autorizados |
| **Google Tag Manager** | Organizar e administrar tags, eventos, integrações e preferências de consentimento | Autorização, seleção da conta/container, conferência de permissões e existência de tags; edições/publicações sensíveis exigem autonomia e aprovação próprias |
| **Google Ads** | Conectar contas publicitárias, visualizar métricas de campanhas e conversões quando disponíveis | Autorização OAuth e seleção da conta Ads, **developer token e nível de acesso da API necessários à aplicação Imagine**, verificação de permissões e ingestão de dados autorizados; **não criar campanha/gastar dinheiro automaticamente** |
| **Perfil da Empresa no Google (Business Profile)** | Acompanhar e administrar, quando cabível, presença local e informações/performance da ficha Google | Autorização e escolha da conta/ficha elegível; **a Imagine deve obter acesso às APIs do Business Profile por processo de aprovação Google**; não prometer conexão operacional antes dessa liberação |

Todos os cartões precisam trazer **texto explicativo, botão “Realizar integração”**, pré-requisitos, estados operacionais, passos oficiais de autenticação, propriedades/IDs selecionados e mensagens claras sobre indisponibilidade ou pendência de autorização. **Não apresentar como “Conectado” integrações cujo acesso à API não esteja aprovado ou que não tenham concluído testes.** Se o Google não liberar um conector a tempo do lançamento, isso é **risco de prontidão a resolver**, não autorização para fingir integração ativa ou tratar o conector como entrega pronta.

### 10.2 Experiência e responsabilidade do cliente em todos os cinco serviços

1. O cliente **seleciona ou cria sua conta Google**, faz login por fluxo oficial, aceita termos quando cabíveis, **concede permissões e escolhe a conta/propriedade do produto** a vincular ao site/empresa. A Imagine não escolhe login pelo cliente, não acessa a senha e não substitui verificação de titularidade.
2. Após concessão de escopos legítimos, a plataforma Imagine automatiza **o máximo tecnicamente suportado**: listar propriedades/contas autorizadas, vincular ao site correto, preencher parâmetros, criar elementos técnicos quando API permite, testar, sincronizar dados, apresentar indicadores e acompanhar status.
3. **Cada serviço mantém credenciais e permissões de mínimo privilégio separadas**: uma autorização para GA4 não autoriza automaticamente Ads, GTM, Search Console ou Business Profile. Permissões de leitura não se tornam permissão de escrita/publicação por inferência. Não conceder escopos amplos sem necessidade explícita.
4. Se uma integração já existir no site, detectar/configurar sem **duplicar tags ou medir duas vezes**. Conexão de Google Ads ou Tag Manager não significa contratação de publicidade, aceite de despesas, publicação automática de tags ou ativação de rastreamento. Vincular cada serviço ao tenant/site correto com auditoria.
5. Não usar consentimento OAuth do administrador como consentimento LGPD dos visitantes; **cookies/rastreadores/eventos pessoais não essenciais precisam de base legal, aviso e mecanismo de escolha adequados à finalidade**. Quando aplicável, consent mode básico poderá bloquear as tags até manifestação apropriada do visitante; técnica exata segue revisão e homologação COM-14/COM-15.
6. Prover reconexão, revogação de acesso, alertas de falha, guarda segura de tokens, portabilidade e gestão de acesso apropriada. O cliente mantém controle sobre suas contas Google, salvo permissões administrativas explicitamente dadas à Imagine.

### 10.3 Condições particulares, homologação e aceite de entrega

- **GA4:** Analytics Admin API tem métodos para propriedades e data streams em contas com acesso adequado; o cliente pode precisar criar/selecionar a conta e aceitar termos. A Data API deverá oferecer o relatório de sessões/origens/eventos com contexto, sem igualar métricas do Search Console.
- **Search Console:** site/dominio exige verificação de proprietário/permissões; adicionar propriedade via API não elimina a verificação. Indexação e posição não são garantias nem resultados imediatos.
- **GTM:** a Tag Manager API permite consultar/editar contêineres e versões, com escopos distintos para publicar ou administrar usuários. Autorização do cliente e controles de mudanças permanecem indispensáveis.
- **Google Ads:** integração exige OAuth **e token de desenvolvedor aprovado** para o nível de acesso pretendido, podendo envolver quotas/conta gerenciadora. **Dados de campanhas e conversões não autorizam gestão de mídia paga**, mudança de orçamento ou despesas. Vincular conversões exige validar origem, privacidade e conta correta.
- **Perfil da Empresa no Google:** elegibilidade da empresa, existência ou criação da ficha pelo cliente, verificação, direitos e **aprovação do projeto Google Cloud Imagine para API** são condições externas. Nem todo site/negócio elegível a Onepage é automaticamente elegível a Perfil da Empresa.
- **Entrega do módulo inicial:** cinco cartões, cinco fluxos de autorização/instruções e conectores homologados para capacidades permitidas, relatórios/status pertinentes e tratamento de falhas, respeitando os prazos e acesso concedido pelo Google. Onde provedor exigir etapas manuais, **o sistema orienta o cliente**, não declara integração concluída antes do sucesso.
- **Integrações devem ser testadas com ambiente apropriado/contas autorizadas**, segregar empresas e demonstrar reconexão/revogação, consentimento LGPD, limite de custo e logs. Perfis autorizados e provisionamento automático de recursos Google específicos permanecem a detalhar por serviço.

### 10.4 Lista de riscos e pendências da primeira versão

1. Solicitar/verificar **Google Ads API developer token** e nível de acesso apropriado antes de prometer consulta de campanhas via Portal; validar processo de aprovação e escalabilidade por contas dos clientes.
2. Solicitar **acesso Business Profile APIs** para o projeto Google Cloud Imagine, habilitar serviços correspondentes somente após aprovação; mapear negócios elegíveis, proprietários, unidades e métricas realmente acessíveis.
3. Homologar OAuth do aplicativo Google (consent screen, escopos/verificação quando exigida), múltiplas contas por cliente, expiração/revogação e auditoria; verificar APIs e quotas das cinco integrações.
4. Definir o **mínimo viável funcional de cada integração na versão 1** sem prometer recursos de edição/públicação de campanhas, fichas ou tags fora dos escopos e contratos autorizados.
5. Revisar LGPD, Consent Mode, modelo de política padrão Imagine, padrões de tags e dados compartilhados com provedores antes de ativação, inclusive eventual custo e retenção.
6. **Não rebaixar Ads, GTM ou Business Profile a “apenas planejados para o futuro”**: a disponibilidade inicial é um requisito a buscar/homologar; riscos de aprovação Google precisam de resolução ou explicitação como bloqueio externo, jamais simulação de funcionamento.

### 10.5 Referências de API para homologação

- Google Ads: autenticação e developer token https://developers.google.com/google-ads/api/rest/auth
- Google Tag Manager: autenticação e escopos https://developers.google.com/tag-platform/tag-manager/api/v2/authorization
- Google Tag Manager REST https://developers.google.com/tag-platform/tag-manager/api/reference/rest
- Google Business Profile: elegibilidade e aprovação https://developers.google.com/my-business/content/overview
- Google Business Profile: requisitos de configuração https://developers.google.com/my-business/content/basic-setup
- Google Tag Consent Mode https://developers.google.com/tag-platform/security/concepts/consent-mode

**Estado: escopo de cinco conectores APROVADO para o lançamento do Portal. Não significa que contas, tokens, projetos Google, tags ou integrações já tenham sido configurados. Nenhuma programação iniciada.**


## 12. Decisão vigente — GA4 apenas conexão, nunca importação de métricas Imagine (03/11/2026)

**Decisão expressa posterior do responsável:** o GA4 será **somente uma integração opcional solicitada pelo cliente**, para o cliente usar sua própria conta/propriedade e consultar dados na **plataforma Google Analytics**. **A Imagine não lerá, armazenará, processará nem apresentará relatórios GA4** no Portal, mesmo quando a integração estiver conectada. O Portal pode apresentar apenas status da conexão, configuração/instalação técnica da medição e eventuais falhas. As seções anteriores que mencionam indicadores GA4 no painel Imagine ou coleta agendada pela Google Analytics Data API são **histórico superado**; não implementar.

**DNS também foi esclarecido:** a Imagine **não manterá DNS próprio nem administrará continuamente os DNS dos clientes**; cliente mantém registro e DNS no registrador ou, preferencialmente, usa Cloudflare DNS, com Imagine orientando e verificando a configuração autorizada. O estudo de custos, 100–700 sites e alternativas Cloudflare for SaaS/Workers for Platforms foi registrado em [PORTAL-CF-01](PORTAL-CF-01-estudo-custos-cloudflare-100-a-700-sites-PROPOSTA.md). A infraestrutura final ainda não foi aprovada; o catálogo Google permanece restrito a GA4, Search Console e Tag Manager conforme a decisão de três integrações vigente. Nenhuma programação.
