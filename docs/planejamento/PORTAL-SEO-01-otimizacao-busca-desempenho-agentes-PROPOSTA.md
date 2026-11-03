# PORTAL-SEO-01 — SEO Técnico, Indexação, Desempenho e Otimização Contínua por Agentes

**Situação (03/11/2026): PROPOSTA FUNCIONAL EM ANÁLISE**, no detalhamento solicitado após aprovação do escopo inicial de três integrações. **Não interpretar este texto como autorização para modificar sites, instalar tags ou executar campanhas.**
**Referências vigentes:** [GOO-01 — três integrações Google](PORTAL-GOO-01-DECISAO-VIGENTE-03-11-2026-catalogo-tres-integracoes.md), [MON-01 — Central de Resultados (proposta)](PORTAL-MON-01-central-resultados-sites-metricas-e-conversoes-PROPOSTA.md), [COM-13 — Entrega automática](PORTAL-COM-13-entrega-automatica-checklist-qualidade-agente-ia.md), [COM-14 — LGPD](PORTAL-COM-14-lgpd-aceites-privacidade-consentimentos-checklist.md), [COM-15 — Política de Privacidade padrão](PORTAL-COM-15-central-documentos-legais-consentimentos.md).
**Fase:** somente planejamento, sem programação.

## 1. Finalidade

Oferecer SEO básico consistente a Onepages e Landing Pages Imagine e preparar estrutura opcional de **monitoramento/otimização contínua**, coordenando agentes de IA, Search Console e testes técnicos. O SEO deve melhorar qualidade, indexabilidade, conteúdo e experiência, **sem garantir posicionamento, tráfego, indexação ou volume de contatos**.

## 2. Checklist de SEO na produção/publicação — proposta

- **URL/domínio:** HTTPS e redirecionamentos consistentes, URL canônica, páginas públicas acessíveis, respostas HTTP corretas, configuração de robots.txt e meta robots, sem noindex acidental para páginas destinadas à pesquisa.
- **Conteúdo:** título descritivo e único por URL; meta description coerente; H1/H2 semânticos; textos sobre serviços, público e localidade real do cliente, sem fabricar experiências, avaliações, endereço ou especialidades. Revisar claims de saúde/finanças/áreas regulamentadas.
- **Mídia:** imagens otimizadas, dimensões, texto alternativo útil, carregamento equilibrado e layout responsivo/acessibilidade aplicável.
- **Estrutura:** sitemap.xml com URLs canônicas indexáveis quando útil; dados estruturados válidos e verdadeiros (Organization/LocalBusiness, FAQ etc. somente quando elegível às diretrizes Google); links acessíveis; HTML renderizado e conteúdo principal legível por crawlers.
- **Performance:** testes Lighthouse/PageSpeed em desktop/móvel, Core Web Vitals observados pelo CrUX quando amostra suficiente, segurança e estabilidade. Não prometer notas 100; definir limiares por produto/uso real.
- **LGPD:** Política de Privacidade revisada/publicada conforme COM-15, captura CRM/formulário verificada; GTM/GA4, quando conectados, respeitam consentimento e regras aplicáveis. SEO não é justificativa para ativar rastreamento sem requisitos de privacidade.
- **Validação IA de entrega:** cada critério obrigatório/condicional precisa de evidência, versão do site, resultado e tratamento de erro conforme COM-13; falhas impeditivas bloqueiam ENTREGUE até correção/homologação.

## 3. Integração Google Search Console

1. Cliente conecta conta/propriedade via **Realizar integração** (GOO-01), concedendo acesso/validando titularidade com os recursos oficiais. A Imagine automatiza o permitido, sem pedir senha ou escolher a conta do cliente.
2. Detectar propriedade correta por URL/domínio e verificar status de configuração, sitemap e dados recebidos, sem usar API Ads/Business Profile.
3. Monitorar **cliques, impressões, CTR, posição média, consultas e páginas**. Não assumir que todos os termos retornam: Search Analytics API mostra linhas principais, com defasagens e limites.
4. Para URLs específicas, **URL Inspection API informa a situação da versão no índice**, não substitui teste de página ao vivo; combinar com HTTP, metatags e validação própria.
5. Abrir pendências claras: sitemap inacessível, URL em noindex, erro 404, canonical inadequado, conteúdo inacessível, falha de permissão Search Console. Falta de clique na pesquisa não prova erro técnico em sites novos.
6. Não usar a **Google Indexing API** como rotina de indexação de Onepages comuns; essa API é destinada a tipos especiais compatíveis (ex.: vagas ou transmissão ao vivo). Sitemap e inspeção não garantem indexação/ranking.

## 4. Otimização contínua e papéis da IA — proposta

**Observação:** o agente correlaciona dados de busca, tráfego, saúde técnica, velocidade e leads recebidos, com fonte/recorte temporal e qualidade dos dados.

**Diagnóstico:** apresenta mudança no período, possíveis causas e evidências concretas; diferencia sugestão de oportunidade de erro comprovado. Não inventa palavras-chave, posições, buscas, intenções do usuário ou causalidade apenas por correlação.

**Sugestão:** melhorias em conteúdo/título, links, imagens, chamadas à ação, headings, meta/canonical, sitemap, legibilidade e desempenho.

**Execução controlada:** dentro de permissões já aprovadas e procedimentos homologados, realizar correções técnicas de baixo risco com teste, versão e rollback; **propostas de mudança significativa de texto/serviços/URL, consentimento ou tags** requerem aprovação apropriada do cliente/equipe. Alterações críticas ou com impacto legal exigem supervisão. Nunca alterar campanhas Ads ou Perfil da Empresa — conectores removidos do escopo.

**Verificação posterior:** depois da publicação, conferir links, sitemap, tags, integridade dos formulários, métricas e status. Registrar resultado observado quando houver dados suficientes, sem apresentar melhora garantida.

## 5. Dados no Portal — proposta

A aba **SEO e Desempenho** poderia exibir: visibilidade na Pesquisa Google, top consultas/páginas, indexação de URLs, checklist SEO, testes PageSpeed (mobile/desktop), CrUX real quando disponível, histórico de erros e lista priorizada de recomendações IA. Cada indicador mostra fonte, última atualização, status de conexão/limitação e recomendação. Não criar “pontuação SEO Imagine” opaca antes de definir pesos verificáveis e sem confundi-la com nota oficial do Google.

## 6. Diferenciação de serviço (pendente de aprovação comercial)

- **SEO técnico básico de implantação:** título/descrições, HTML, sitemap/canonical/robots, imagens, desempenho e checagem de indexabilidade conforme contrato/checklist.
- **Monitoramento pós-publicação:** leitura de tendências, alertas técnicos e diagnósticos com automação IA dentro de limites aprovados.
- **SEO estratégico contínuo:** pesquisas de conteúdo e produção/otimização recorrente sob escopo comercial separado, com aprovação apropriada e sem promessa de posição no Google.

**IMPORTANTE:** o que está incluso na assinatura, a frequência de auditorias, as revisões de conteúdo, a autonomia da IA e eventuais valores **ainda não estão aprovados** por esta proposta.

## 7. Pendências para decisão

Critérios obrigatórios por produto; periodicidade de SEO/alertas; escopo gratuito vs serviço adicional; alçadas para alterações técnicas e de conteúdo; limites de APIs/custos; comunicação a cliente; critérios reais de sucesso; execução de testes em Cloudflare Workers Static Assets; obtenção de amostras CrUX e limites de rastreamento Search Console; homologação jurídica e LGPD.

## Fontes técnicas oficiais

- Google Search Central, rastreamento/indexação: https://developers.google.com/search/help/crawling-index-faq
- Google Search Analytics API: https://developers.google.com/webmaster-tools/v1/searchanalytics/query
- Google URL Inspection API: https://developers.google.com/webmaster-tools/v1/urlInspection.index/inspect
- PageSpeed Insights API: https://developers.google.com/speed/docs/insights/v5/get-started
- CrUX API: https://developer.chrome.com/docs/crux/guides/crux-api
- Google Search Technical Essentials: https://developers.google.com/search/docs/essentials/technical

**Status: proposta para aprovação, não programação nem execução.**
