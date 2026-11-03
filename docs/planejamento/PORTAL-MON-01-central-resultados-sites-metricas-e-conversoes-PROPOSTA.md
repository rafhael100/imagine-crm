# PORTAL-MON-01 — Central de Resultados, Métricas e Conversões dos Sites Imagine

**Situação (03/11/2026): PROPOSTA FUNCIONAL EM ANÁLISE.** O responsável concordou em continuar o detalhamento do acompanhamento dos resultados; **ainda não houve aprovação expressa de todos os indicadores, abas, frequências e automações listados abaixo**. Não iniciar programação.
**Escopo:** sites Onepage e Landing Pages do Portal Imagine, com controles multiempresa e integração CRM. 
**Decisões vigentes preservadas:** o catálogo inicial de Google inclui APENAS **GA4, Search Console e Tag Manager**; **Google Ads e Perfil da Empresa no Google estão excluídos**. O cliente escolhe/cria conta Google e autoriza, enquanto Imagine configura automaticamente o tecnicamente viável. [Decisão vigente GOO-01](PORTAL-GOO-01-DECISAO-VIGENTE-03-11-2026-catalogo-tres-integracoes.md).
**Referências:** [IN-123](IN-123-painel-integracoes-sites-crm.md), [IN-125](IN-125-gestao-inteligente-formularios.md), [COM-13](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md), [COM-14](PORTAL-COM-14-lgpd-aceites-privacidade-consentimentos-checklist.md), [COM-15](PORTAL-COM-15-central-documentos-legais-consentimentos.md), [SEO-01](PORTAL-SEO-01-otimizacao-busca-desempenho-agentes-PROPOSTA.md).

## 1. Experiência proposta no Portal

Cada projeto/site terá aba **Resultados**, com filtro por período (proposta: últimos 7, 30 e 90 dias, e período personalizado) e comparação com período anterior. Subabas sugeridas:

1. **Visão geral:** resumo de tráfego, aquisição, pesquisa orgânica, leads recebidos, saúde do site e pontos de atenção. Cada cartão exibe fonte e data/hora da última atualização.
2. **Acessos:** usuários/sessões/páginas, origem de visitas, dispositivos e tendências do Google Analytics 4 se conectado, e informações técnicas Cloudflare quando disponíveis.
3. **Presença no Google / SEO:** impressões, cliques, CTR, posição média, principais consultas e páginas no Search Console, com indicação de limitações e ausência de dados.
4. **Leads e conversões:** número de formulários **confirmados pelo serviço de ingestão/CRM Imagine** vinculados ao site e formulário; eventos GA4 de interação em coluna separada. **Clique WhatsApp não equivale a conversa efetiva; clique em enviar não equivale a lead entregue**.
5. **Desempenho e saúde:** resultados PageSpeed/Lighthouse, CrUX quando houver amostra, disponibilidade HTTP, HTTPS/SSL e falhas técnicas monitoráveis.
6. **Recomendações da IA:** achados, evidências, prioridade e sugestões. Alterações relevantes de conteúdo/SEO/tags precisam de autorização conforme governança do projeto.

Estados visíveis: **Integração não conectada**, **Aguardando cliente**, **Coletando**, **Sem dados no período**, **Atualização atrasada**, **Falha na coleta**, **Conectada**. Não exibir zero quando a fonte está indisponível ou ainda não tem dados. Uma tela sem integração não deve expor informação de outra empresa.

## 2. Fonte oficial de cada indicador

| Indicador | Fonte principal | Limites importantes |
|---|---|---|
| Sessões, usuários e páginas | **GA4 Data API** | Dependem de tags habilitadas, coleta, consentimento aplicável, filtros/amostragem/atribuição e atraso do provedor |
| Aquisição e eventos | **GA4** | Atribuição não significa lead confirmado; evento configurado pode faltar |
| Impressões, cliques, CTR, consultas e posição média | **Search Console Search Analytics API** | Só pesquisa Google, dados podem chegar depois, API prioriza linhas principais e não retorna tudo |
| Situação de indexação por URL | **Search Console URL Inspection API** | Situação da versão conhecida no índice; não testa a URL ao vivo; ausência de dados não implica falha |
| Configuração de tags e contêiner | **Tag Manager API** | **GTM não mede visitas por si só**; mostra/gerencia configuração, não substitui GA4 |
| Formulários aceitos e recebidos | **Backend seguro de formulários/CRM Imagine** | Evidência de recebimento e site/tenant de destino; não acessar pipeline/dados privados além das permissões legítimas |
| Operação, HTTP, SSL, erros e monitoramento técnico | **Camada Imagine / Cloudflare** | Não converter requests de bots/cache em “visitantes”; disponibilidade a medir por testes próprios |
| Testes de velocidade por URL/dispositivo | **PageSpeed Insights / Lighthouse** | Resultado de laboratório pontual e variável; não equivale ao tempo real observado pelos visitantes |
| Core Web Vitals reais (se disponíveis) | **CrUX API / History API** | Sites novos ou com pouco tráfego podem não ter amostra; não mostrar zero falso |

**Revisão importante:** documentação Cloudflare de agosto de 2026 informa limite de **dez sites NÃO proxied**, mas **sem limite numérico de sites proxied** para Web Analytics; a visualização agregada no dashboard limita a 1.000 por consulta. Não tratar “dez sites por conta” como limite absoluto. Conferir custos, coleta e datasets para a arquitetura real Workers Static Assets e multiempresa.

## 3. Arquitetura de coleta — proposta para homologação

Fluxo: **Integração OAuth autorizada pelo cliente → Serviços Imagine de coleta via APIs do Google e Cloudflare → filas/jobs agendados e validados (n8n pode orquestrar) → base central agregada por empresa/site/tempo → API do Portal → painel e agentes IA**.

- Guardar **tokens OAuth somente no backend/cofre criptografado**, nunca em HTML público, GitHub ou frontend; revogação/reconexão e escopos mínimos por serviço. Não permitir consulta cruzada entre tenants.
- Adotar modelo de dados **site, tenant, propriedade, fonte, indicador, período, timezone, data atualização, estado da coleta, versão do conector, valores agregados**. Respeitar retenção e minimização; sem centralizar identificadores individuais de visitantes por padrão.
- Não somar números do GA4, Search Console e Cloudflare como se fossem um mesmo contador. **Contratos de métricas/definições por fonte** e UI de ajuda impedem comparações incorretas.
- Evitar solicitações a APIs em toda abertura de painel; pré-coletar e armazenar agregados para conter limites/quota/custos; processar falhas com retentativas limitadas, backoff e fila segura.
- A coleta de dados do cliente é **condicionada a conexão voluntária da propriedade, permissões atuais e configurações LGPD**. A automação de análise de SEO técnico, disponibilidade e PageSpeed por URL pode funcionar sem conta Google conectada, quando pública e permitida.

## 4. Cadências PROPOSTAS (a deliberar)

| Processo | Cadência inicial sugerida | Observação |
|---|---|---|
| GA4 (resumo histórico) | Uma vez por dia | API pode atrasar e revisar valores |
| Search Console | Uma vez por dia | Dados orgânicos têm defasagem; informar data da última observação |
| Leads aceitos | Atualização por evento do backend | Confirmar ingestão sem duplicar nem expor contatos indevidos |
| PageSpeed/Lighthouse | Ao publicar/alterar e uma vez por semana | Custos e limites/variação dependem de homologação |
| Disponibilidade/SSL | Monitoramento técnico periódico | Intervalo e limiares ainda por dimensionar |
| Relatório/resumo IA | Semanal, sob configuração | Cadência, destinatários e canal não aprovados; **não enviar e-mail automático sem decisão** |

**Atenção:** não interpretar as cadências como prazos prometidos aos clientes; validar custos, quotas, retenção, capacidade e regras por plano. Dados históricos não são necessariamente tempo real.

## 5. Inteligência e permissões — proposta

- Agentes podem detectar: queda sustentada de tráfego orgânico, falha de indexação, links/SSL quebrados, lentidão, formulário fora do ar, evento de conversão não configurado, divergência suspeita de métricas e oportunidades de melhorar título/conteúdo.
- **Tarefas automáticas de baixo risco** só poderão ocorrer se já previstas em playbook homologado, com teste, versionamento, rollback e auditoria. Mudanças de conteúdo, URLs, canonical/robots, contêiner GTM, coleta/consentimento e decisões que afetem cliente necessitam de alçada configurada e validação humana quando cabível.
- Análise IA explica qual sinal observou, qual fonte e período, o nível de confiança e ação proposta. Não prometer ranking, leads ou aumento de tráfego.

## 6. Pendências abertas para aprovação

Estrutura exata das seis áreas; indicadores obrigatórios por produto e plano; frequência dos dados e relatórios; alertas e canais; limiares de qualidade; escopo de visibilidade do CRM; retenção; custos/quota; infraestrutura de agregação; termos Google/Cloudflare e LGPD. **Não aprovado nem implementado neste documento ainda.**

## Fontes oficiais consultadas (03/11/2026)
- Google Analytics Data API: https://developers.google.com/analytics/devguides/reporting/data/v1/basics
- Search Console Analytics: https://developers.google.com/webmaster-tools/v1/searchanalytics/query
- GTM API: https://developers.google.com/tag-platform/tag-manager/api/v2
- Cloudflare Analytics Limits (atualizado agosto de 2026): https://developers.cloudflare.com/web-analytics/limits/
- Cloudflare GraphQL API: https://developers.cloudflare.com/analytics/graphql-api/getting-started/
- PageSpeed: https://developers.google.com/speed/docs/insights/v5/get-started
- CrUX: https://developer.chrome.com/docs/crux/guides/crux-api

**Status:** PROPOSTA PARA DISCUSSÃO. Sem programação ou conexão real.


## Decisão posterior vinculante — excluir métricas GA4 do Portal Imagine (03/11/2026)

**REQUISITO FUNCIONAL APROVADO pelo responsável, posterior à proposta MON-01:** o **Google Analytics 4 será instalado/conectado facultativamente** ao site apenas a pedido do cliente, depois de autenticação, configuração e requisitos de privacidade, **mas o Portal Imagine não coletará nem exibirá métricas do GA4**. Os relatórios serão consultados **diretamente no painel Google Analytics pelo próprio cliente**. **Ficam superadas todas as referências anteriores deste documento a GA4 Data API, sessões, usuários, fontes, eventos ou conversões GA4 como métricas nativas do Portal**, inclusive tabelas, abas e cadências propostas. A Integrações pode exibir **somente estado técnico da conexão GA4** (p.ex. configurado/desconectado), sem consumir a API de relatórios.

**Permanecem em análise:** monitoramento próprio Cloudflare/Web Analytics, disponibilidade, SSL, erros, métricas de SEO pelo Search Console se autorizado, leads confirmados pela infraestrutura Imagine/CRM sob permissões, recomendações de agentes. As métricas “Acessos” da proposta deverão ser redefinidas **sem GA4**, exigindo aprovação específica de fonte, definição e apresentação. A decisão posterior do cliente sobre DNS não interfere na titularidade do domínio; preferência Cloudflare DNS ou do registrador, sem DNS próprio Imagine. [GOO-01](PORTAL-GOO-01-DECISAO-VIGENTE-03-11-2026-catalogo-tres-integracoes.md), [CF-01](PORTAL-CF-01-estudo-custos-cloudflare-100-a-700-sites-PROPOSTA.md). Somente planejamento.
