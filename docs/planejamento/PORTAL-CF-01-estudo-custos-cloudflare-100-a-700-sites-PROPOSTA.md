# PORTAL-CF-01 — Estudo de custos Cloudflare para 100–700 Sites Imagine

**Data-base:** 03/11/2026. **Status:** ESTUDO PRELIMINAR; arquitetura e contratação ainda **NÃO APROVADAS**.
**Escopo:** sites Onepage e Landing Page predominantly estáticos, Cloudflare Workers Static Assets conforme IN-096; infraestrutura DNS, SaaS custom hostnames, Workers, Workers for Platforms, R2, segurança/analytics, Turnstile e possibilidades opcionais.
**Moeda:** USD, valores mensais de tabela e projeções aritméticas, **não cotação final**; fora: impostos, câmbio, registro/renovação de domínios, n8n, Laravel/PostgreSQL/CRM, custos de operação, provedor de e-mail, tráfego dinâmico excedente e revisão jurídica.

## 1. Duas decisões novas do responsável (03/11/2026)

1. **GA4 opcional somente para instalação/conexão do site ao Google Analytics**. O Portal disponibiliza **Realizar integração** com conta/propriedade escolhida e autorizada pelo próprio cliente; **a Imagine NÃO consultará, importará, armazenará nem exibirá relatórios/métricas do GA4**. O cliente acessa o próprio Google Analytics para isso. A configuração técnica/instalação da tag e suas verificações de privacidade continuam elegíveis à automação. Não instalar GA4 sem escolha do cliente nem sem controles LGPD aplicáveis. **Isso prevalece sobre quaisquer propostas anteriores de coletar dados GA4 via Data API para os painéis Imagine.**
2. **Não operar DNS próprio da Imagine nem assumir gestão rotineira dos registros DNS dos clientes.** Domínio permanece com o registrador de escolha do cliente, enquanto o **DNS autoritativo permanece com o registrador/provedor ou, preferencialmente, com a Cloudflare**, mediante decisão/configuração pelo cliente. Imagine fornece instruções, valida domínio e automatiza apenas o tecnicamente permitido e autorizado; não exigir transferência de titularidade do domínio, não criar DNS autoritativo próprio. **A decisão pela arquitetura específica de hospedagem/custom hostnames ainda é pendente.**

## 2. Serviços Cloudflare e preços base oficiais consultados

| Serviço | Papel possível Imagine | Preço de referência e alerta |
|---|---|---|
| Workers Static Assets | Servir HTML/CSS/JS/imagens; armazenamento da publicação | Assets estáticos sem tarifa de requisição e sem tarifa própria de armazenamento; **requisições que invocam Worker geram uso faturável** |
| Workers Paid | Roteamento, funções e operação | **US$5/conta/mês**; 10 milhões de requests e 30 milhões de CPU-ms incluídos; depois **US$0,30/milhão de requests** e **US$0,02/milhão CPU-ms**. Limite padrão **500 Workers por conta paga** |
| Cloudflare DNS Free | DNS dos clientes que preferirem Cloudflare | **US$0** em plano Free; DNS queries sem cobrança em Free/Pro/Business; cliente pode manter registro em Registro.br/outro |
| CDN, SSL universal, WAF básico e DDoS | Entrega, HTTPS, proteção | Disponíveis no Free dentro das políticas/limites de plano; Pro/Business são upgrades opcionais por zona, **não necessários universalmente** |
| Web Analytics | Métricas de visitas sem integrar GA4 | Gratuito; **10 sites não proxied, proxied sem limite numérico de sites**; painel de agregado até 1.000 simultâneos; capacidade de relatórios por domínio deve ser homologada |
| GraphQL Analytics API | Coleta de métricas técnicas | Sem tarifa-base específica de consulta; **300 consultas/5 minutos por usuário/token por padrão**, restrições por dataset/plano; ausência de acesso ou histórico depende de plano |
| Cloudflare for SaaS | Receber hostnames de clientes com DNS próprio do cliente | **100 hostnames incluídos, US$0,10 por hostname adicional/mês**, até 50.000 por zona não Enterprise; plano da zona/SSL/apex podem gerar custos adicionais |
| Workers for Platforms | User Worker/Static Assets separados por site com plataforma de publicação | **US$25/mês**, inclui **1.000 scripts, 20 milhões de requests e 60 milhões CPU-ms**, depois scripts **US$0,02 cada**, requests **US$0,30/milhão**, CPU **US$0,02/milhão CPU-ms** |
| R2 Standard | Backup/artefatos opcionais separados do repositório | **10 GB-mês incluídos**, depois **US$0,015/GB-mês**, além de operações Classe A/B se excedidas. Egress zero |
| Workers KV | Mapeamento hostname→site, cache de configuração (se necessário) | No Workers Paid, **10M leituras/mês**, depois **US$0,50/milhão**; **1M escritas**, depois US$5/milhão; **1GB armazenado**, depois US$0,50/GB-mês |
| Cloudflare Queues | Filas complementares se adotadas em vez de n8n/PostgreSQL | Workers Paid: **1M operações/mês incluídas**, depois **US$0,40/milhão**; operação de mensagem normalmente conta 3x para produzir/ler/apagar |
| Turnstile | Proteção de formulários | **Free: 20 widgets por conta e 10 hostnames por widget**. Com um hostname por site, uma conta Free não permite a implantação trivial de 700 sites: usar outra estratégia/contas autorizadas ou obter **Enterprise sob cotação** |
| Images Transformations | Otimizações dinâmicas quando não feitas na produção | **5.000 transformações únicas/mês grátis**, depois no plano pago **US$0,50/1.000 transformações** |
| Plano Pro/Business por zona | Recursos adicionais de proteção/otimização por domínio | **Pro: US$25/mês** por zona com cobrança mensal (US$20 faturamento anual); **Business: US$250/mês** (US$200 no anual); não pressupor que todos os clientes precisem |
| Cloudflare for SaaS apex proxying | Conectar domínio raiz por registro A quando DNS externo não comporta CNAME apex | Recurso específico de Enterprise/add-on **sob cotação**; não incluso nas projeções |

## 3. Premissas das tabelas

- **Uma conta/plataforma central da Imagine**, e cada cliente controla seu registrador/DNS; preferência pela Cloudflare DNS na conta do cliente ou configuração autorizada, não servidores DNS Imagine.
- **SaaS 1 hostname/site:** usar principalmente `www.empresa.com.br`, ou outro hostname apto a CNAME. O domínio raiz é assunto de arquitetura: DNS com CNAME flattening/ALIAS, redirecionamento do cliente ou apex proxying pago, conforme provedor, SSL e propriedade. **Não prometer apex em todos os registradores por US$0,10.**
- **SaaS 2 hostnames/site:** hipótese matemática de pagar e servir `www` e apex por cliente, **não prova viabilidade de domínio raiz**.
- Um SaaS compartilhado com **Workers Paid** (US$5 base) e outro com **Workers for Platforms** (US$25 base) são **alternativas conceituais**, ambas acrescidas de SaaS custom hostnames. Confirmar comercialmente se a configuração final precisa também do plano Workers Paid ou outra taxa.
- Sem requisições/CPU/armazenamento/transformações excedentes na tabela base; nenhuma garantia de capacidade. Em SaaS com roteamento por Worker, invocações dinâmicas podem ocorrer também ao solicitar arquivos estáticos, e portanto gerar cobranças; testes devem identificar quais requests ativam script.
- A tabela considera um hostname por site, com franquia agregada dos **primeiros 100** em uma única zona SaaS. Distribuir hostnames por várias zonas, upgrades de plano ou múltiplas contas pode mudar o faturamento.

## 4. Projeções mensais por volume (USD)

| Sites | Taxa SaaS / 1 hostname por site | Workers Paid + SaaS (compartilhado) | Workers for Platforms + SaaS (User Worker/site) | Workers for Platforms + SaaS / 2 hostnames por site | R2 opcional 1 GB/site* |
|---:|---:|---:|---:|---:|---:|
| 100 | 0 | 5 | 25 | 35 | 1,35 |
| 200 | 10 | 15 | 35 | 55 | 2,85 |
| 300 | 20 | 25 | 45 | 75 | 4,35 |
| 400 | 30 | 35 | 55 | 95 | 5,85 |
| 500 | 40 | 45 | 65 | 115 | 7,35 |
| 600 | 50 | 55 | 75 | 135 | 8,85 |
| 700 | 60 | 65 | 85 | 155 | 10,35 |

**Fórmulas:** SaaS 1 = max(0, N−100)×0,10; SaaS 2 = max(0,2N−100)×0,10; Workers = base 5 ou WFP base 25; **R2** = max(0,N×1GB−10GB)×0,015, desconsiderando operações e arredondamentos. *Suposição didática de 1GB TOTAL armazenado por site já contando todas suas versões/retensão; dimensão real de backups deve ser validada. **Não somar as colunas alternativas Workers Paid e WFP.**

## 5. Exemplo de sensibilidade a tráfego dinâmico

Suposição fictícia: cada site recebe **10 mil visualizações de página/mês**, que geram **5 invocações de Worker por visualização**, cada uma com **3 ms CPU**. Para Workers for Platforms, o excedente incremental estimado acima dos 20 milhões requests e 60 milhões CPU-ms incluídos é:

| Sites | Requests que invocam Worker / mês | Custo excedente estimado WFP |
|---:|---:|---:|
| 100 | 5 milhões | US$0 |
| 200 | 10 milhões | US$0 |
| 300 | 15 milhões | US$0 |
| 400 | 20 milhões | US$0 |
| 500 | 25 milhões | US$1,80 |
| 600 | 30 milhões | US$3,60 |
| 700 | 35 milhões | US$5,40 |

**Hipótese altamente sensível ao desenho:** se assets forem servidos sem invocar script, parte desses custos não ocorre; se scripts executarem em todas as requisições com CPU maior, a conta cresce. Outros custos como KV, R2, analytics, redirects, proteção de formulários e domínio não entram nesse excedente.

## 6. Outras opções e impactos econômicos

### Domínio com zona individual sob a Imagine + Worker próprio

- **Workers Paid até 500 Workers por conta como limite padrão**; se houver um Worker por site, 600–700 ficam além desse limite sem aumento negociado, repartição controlada ou Workers for Platforms.
- Zonas Free individuais permitem DNS/SSL/CDN básicos, mas centralizar zonas na conta Imagine **contradiz a preferência de não administrar DNS dos clientes**; e manter domínios na conta Cloudflare do próprio cliente com Worker da Imagine requer integração especial entre contas. **Não considerar esse caminho operacionalmente aprovado.**
- **Pro para todas as zonas a US$25/cliente/mês** custaria US$2.500 para 100 sites e US$17.500 para 700 sites. **Business para todas a US$250** custaria US$25.000–175.000/mês. Não recomendado de forma universal; upgrades específicos são decisões por cliente.
- **Workers for Platforms** resolve o limite padrão de 500 Worker scripts da conta paga comum, pois tem 1.000 scripts inclusos e admite scripts adicionais por cobrança.

### Subdomínio e apex do domínio

- Em DNS autoritativo externo, o `www` normalmente aponta via CNAME ao destino SaaS. O apex (`empresa.com.br`) **não costuma aceitar CNAME em DNS tradicional**, e pode exigir ALIAS/CNAME flattening ou recurso pago de apex proxying. Cloudflare DNS oferece **CNAME flattening no apex**; os fluxos de O2O/SSL e regras do SaaS devem ser testados em ambientes reais. Outra alternativa é servir `www` como site principal e fazer **redirecionamento do apex pelo serviço DNS/redirect do cliente**, se disponível, evitando duplicar hostnames, mas com validação de HTTPS para o domínio raiz.
- Manter domínio registrado no Registro.br e apontar NS para Cloudflare DNS não altera titularidade registral. A Imagine orienta DNS e verifica propagação/certificado, mas **não mantém servidores DNS próprios nem passa a controlar DNS sem autorização**.

### Turnstile: alerta de escala

- **20 widgets gratuitos e 10 hostnames por widget** (até 200 hostnames configuráveis por conta Free, no uso máximo teórico). Assim, para 300–700 sites com formulários e domínio próprio, a solução gratuita em conta única não é suficiente **sem outra estratégia homologada**. **O preço Enterprise é sob consulta**, não inventar preço. Também é necessário estudo de gestão de widgets/sitekeys, uso de contas adicionais autorizadas e alternativas anti-bot. **Não prometer proteção Turnstile para 700 sites com custo zero** com esse modelo.

## 7. Recomendações de arquitetura para discutir, não aprovar por inferência

1. **Candidata principal para estudo:** Cloudflare for SaaS + Workers for Platforms + Workers Static Assets, com dados de cada cliente segregados e DNS controlado pelo cliente (preferencialmente Cloudflare). Avaliar viabilidade de conectar e redirecionar apex, domínio com/sem www, wildcard routes e impacto de Worker dispatch.
2. **Candidata econômica:** Cloudflare for SaaS + Worker compartilhado com hospedagem estática distribuída de forma correta e isolada; custo-base inferior, mas desenho de roteamento, publicação independente, arquivo por cliente e segurança devem passar por provas técnicas.
3. Não utilizar DNS próprio ou WHM/cPanel para novos sites estáticos; não modificar domínio/e-mail/NS do cliente silenciosamente.
4. **Monitoramento Imagine:** Cloudflare Web Analytics + GraphQL/saúde técnica podem fornecer dados por site se APIs/granularidade/retensão confirmadas; **não importar métricas GA4**. As subabas MON-01 anteriores de visitantes/sessões GA4 estão superadas; Google Search Console (quando conectado) continua sujeito a proposta de relatórios de SEO e ao aceite futuro.
5. Segurança: avaliar Turnstile em escala acima de 200 hostnames, se implantado em todos; captcha alternativo ou nível Enterprise exigem orçamento separado.
6. Solução final depende de protótipo técnico, custos de uso real e termos Cloudflare; **não iniciar código antes de concluir/aprovar todo planejamento**.

## 8. Referências oficiais (verificadas em 03/11/2026)

- Workers pricing: https://developers.cloudflare.com/workers/platform/pricing/
- Workers limits: https://developers.cloudflare.com/workers/platform/limits/
- Workers Static Assets: https://developers.cloudflare.com/workers/static-assets/billing-and-limitations/
- Workers for Platforms pricing: https://developers.cloudflare.com/cloudflare-for-platforms/workers-for-platforms/reference/pricing/
- Workers for Platforms Static Assets: https://developers.cloudflare.com/cloudflare-for-platforms/workers-for-platforms/configuration/static-assets/
- Cloudflare for SaaS plans: https://developers.cloudflare.com/cloudflare-for-platforms/cloudflare-for-saas/plans/
- SaaS + Worker routing: https://developers.cloudflare.com/cloudflare-for-platforms/cloudflare-for-saas/start/advanced-settings/worker-as-origin/
- SaaS apex proxying: https://developers.cloudflare.com/cloudflare-for-platforms/cloudflare-for-saas/start/advanced-settings/apex-proxying/
- Cloudflare DNS FAQ: https://developers.cloudflare.com/dns/faq/
- Cloudflare DNS CNAME flattening: https://developers.cloudflare.com/dns/cname-flattening/set-up-cname-flattening/
- Web Analytics limits: https://developers.cloudflare.com/web-analytics/limits/
- GraphQL Analytics: https://developers.cloudflare.com/analytics/graphql-api/limits/
- R2 pricing: https://developers.cloudflare.com/r2/pricing/
- Turnstile plans: https://developers.cloudflare.com/turnstile/plans/
- Images pricing: https://developers.cloudflare.com/images/pricing/
- Cloudflare plans: https://www.cloudflare.com/plans/

**Registro:** duas decisões do cliente aprovadas (GA4 somente integração externa; DNS do cliente com preferência Cloudflare). Cenários de arquitetura, projeções e demais recursos são **estudo preliminar, não decisão nem proposta contratual vinculante**.
