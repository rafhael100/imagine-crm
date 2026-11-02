# PORTAL-GOO-01 — Integrações Google no Portal Imagine: conectividade, provisionamento assistido e autonomia do cliente

**Status (02/11/2026): DIRETRIZ FUNCIONAL DE INTEGRAÇÃO SOLICITADA PELO RESPONSÁVEL.** No debate sobre métricas de acesso, SEO e Google Analytics/Search Console, o responsável determinou incluir no relatório/planejamento a **possibilidade de o cliente conectar essas ferramentas pelo sistema de integrações do Portal Imagine**, enquanto a **Imagine cria e mantém o modelo técnico/padrão de integração e realiza configuração e integração quando necessário**. A interpretação operacional adotada é conexão de **contas/propriedades Google do cliente** ou provisionamento orientado pela Imagine, sem criar uma identidade Google em nome de alguém sem autorização. Os três blocos MON-01 / SEO-01 / GOO-01 sugeridos ainda dependem de fechamento formal de escopo; esta especificação cobre inicialmente o **GOO-01** e suas interfaces com métricas/SEO.
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
