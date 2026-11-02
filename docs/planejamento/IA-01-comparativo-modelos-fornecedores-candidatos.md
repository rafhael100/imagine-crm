# IA-01 — Comparativo de Modelos e Fornecedores Candidatos para Agentes Técnicos Imagine

**Status:** PESQUISA E COMPARAÇÃO CONCLUÍDAS; **MODELOS, FORNECEDORES E ORDEM DE PREFERÊNCIA AINDA NÃO APROVADOS**.  
**Data da referência de preços e catálogo:** 02/11/2026.  
**Autorização do responsável:** resposta "ok" para **iniciar a comparação** de OpenAI, Anthropic e Google, sem decisão de compra ou catálogo definitivo.  
**Fase:** exclusivamente planejamento e documentação; sem programação, testes pagos, provisionamento, contratação, alteração de permissões ou consumo real.
**Referências internas:** [IA-01 — três classes aprovadas](IA-01-matriz-classes-modelos-por-tarefa-agentes-tecnicos.md), [IN-157 — gestão de modelos/fallback](IN-157-gestao-inteligente-modelos-ia-contingencia.md), [IN-158 — custos de agentes](IN-158-monitoramento-inteligente-custos-consumo-agentes.md), [IN-166 — ferramentas/permissões](IN-166-gestao-inteligente-ferramentas-permissoes-agentes.md) e [IN-168 — pendências](IN-168-matriz-pendencias-fechamento-arquitetura.md).

## 1. Decisões já aprovadas, que não serão rediscutidas

- **Três classes de execução:** econômica, intermediária e avançada, por complexidade/qualidade/custo, aprovadas na matriz IA-01.
- **Multimodelo e seleção inteligente por tarefa**, com possibilidade de **fallback entre modelos e provedores previamente autorizados e homologados**, conforme IN-157.
- **Modelos externos via API** são a hipótese de dimensionamento da VPS exclusiva de agentes; nenhuma GPU/modelo local foi aprovado.
- O orquestrador e agentes operam em **VPS exclusiva**, distinta da VPS do n8n, conforme ARQ-01. Os modelos externos não residem nessa VPS; é onde rodam as ferramentas, regras, serviços de coordenação e autorização.
- O modelo pode propor uma ação, mas **somente autorizador técnico independente** pode conceder execução de ferramenta dentro das permissões, procedimentos e políticas homologados. Ações críticas exigem **uma aprovação humana quando aplicável**, nunca dupla.
- Controles de monitoramento, alertas prioritários, recepção de leads, filas e outras proteções determinísticas **não podem depender de qualquer modelo ou do Supervisor**.
- Custos internos dos agentes não se confundem com créditos dos clientes do CRM; vedado ampliar orçamento, contratar fornecedores ou criar novas despesas automaticamente.

## 2. Metodologia desta pesquisa

- Comparação inicial baseada em **catálogos e documentação oficial dos fornecedores** e **preços públicos** para requisições de texto pela API, não em testes próprios realizados nos sistemas da Imagine.
- Todos os valores de tokens apresentados em **USD por 1 milhão de tokens** (*MTok*), separados para **entrada/saída**, **modo de processamento padrão** e **sem cache**. Não incluem imposto, câmbio, ferramentas, consultas web, áudio/vídeo, backups, segurança, armazenamento, agentes externos, custo da VPS, tarifas de encaminhamento, regiões especiais ou cobranças adicionais.
- Cada provedor usa tokenizador e tratamento de contexto/saída próprios. Os números por MTok servem como **triagem financeira**, não comparação direta de qualidade, nem prova de que a mesma tarefa gastará a mesma quantidade de tokens.
- **A categoria indicada é proposta de elegibilidade para testes**, não homologação, vitória de benchmark, garantia de suporte a uma função específica ou autorização de processar dados reais.

## 3. Comparação por classe

### 3.1 Econômica

| Fornecedor | Modelo / identificador | Input USD/MTok | Output USD/MTok | Aplicação candidata |
|---|---|---:|---:|---|
| OpenAI | `gpt-6-luna` | **0,10** | **0,50** | Classificação, resumos mínimos, análise simples, roteamento quando IA realmente necessária |
| Anthropic | `claude-haiku-5-5` | **0,10** | **0,50** | Extração/classificação/roteamento, resposta breve e baixa latência |
| Google | `gemini-3.5-flash-lite` | **0,30** | **2,50** | Alto volume, processamento simples, resumos e triagem |

**Atenção:** Claude Haiku 5.5 cobra **US$ 0,50 de entrada e US$ 2,50 de saída** para requisições cujo prompt ultrapasse 100 mil tokens; o valor da tabela pressupõe tamanho menor ou igual a 100 mil tokens por requisição. Não usar entrada gigante sem verificar limiar.

### 3.2 Intermediária

| Fornecedor | Modelo / identificador | Input USD/MTok | Output USD/MTok | Aplicação candidata |
|---|---|---:|---:|---|
| OpenAI | `gpt-6.1-terra` | **1,00** | **5,00** | Diagnóstico de complexidade moderada, análise de ferramentas e procedimentos |
| Anthropic | `claude-sonnet-5-5` | **2,00** | **10,00** | Diagnóstico de código e incidentes, triagem técnica e propostas de correção |
| Google | `gemini-3.8-flash` | **0,75** | **3,75** | Engenharia de software/fluxos agênticos, diagnósticos e tarefas multimodais |

**Preço com validade anunciada:** Gemini 3.8 Flash passa a **US$ 1,50 entrada / US$ 7,50 saída em 01/01/2027** na tabela da Gemini Developer API. Considerar esse aumento nas simulações anuais antes de aprovar um modelo principal.

### 3.3 Avançada

| Fornecedor | Modelo / identificador | Input USD/MTok | Output USD/MTok | Aplicação candidata |
|---|---|---:|---:|---|
| OpenAI | `gpt-6.1-sol` | **2,00** | **10,00** | Investigações complexas, arquitetura de correções e análise de código |
| Anthropic | `claude-opus-5-5` | **4,00** | **20,00** | Engenharia de software/agentes de longa duração e investigação de causa raiz |
| Google | `gemini-4-argon` | **2,00** | **10,00** | Raciocínio complexo, software e coordenação multietapas |

**Preço com validade anunciada:** Gemini 4 Argon passa a **US$ 4,00 entrada / US$ 20,00 saída em 01/12/2026**. Não dimensionar custo permanente com tarifa promocional de novembro.

**Reserva excepcional a avaliar:** `gpt-6-astra` (OpenAI) e `claude-fable-5-1` (Anthropic), ambos **US$ 10 entrada / US$ 50 saída por MTok** no modo padrão. Somente candidatar à operação quando os testes mostrarem qualidade superior suficiente para justificar maior custo; não presumir necessidade para todas as tarefas difíceis.

## 4. Simulação meramente ilustrativa para comparação de custos

**Cenário fictício homogêneo:** 10.000 análises em um mês, com **1.500 tokens de entrada e 300 tokens de saída por tarefa**, ou **15 milhões de tokens de entrada + 3 milhões de saída**. Sem cache, raciocínio/tokens extras, ferramentas, retries ou custos fixos. **Não é projeção de consumo real da Imagine nem orçamento aprovado**.

| Modelo | Custo matemático aproximado para o cenário (USD) |
|---|---:|
| GPT-6 Luna | 3,00 |
| Claude Haiku 5.5 (prompts <=100k) | 3,00 |
| Gemini 3.5 Flash-Lite | 12,00 |
| GPT-6.1 Terra | 30,00 |
| Claude Sonnet 5.5 | 60,00 |
| Gemini 3.8 Flash (preço até dez/2026) | 22,50 |
| GPT-6.1 Sol | 60,00 |
| Claude Opus 5.5 | 120,00 |
| Gemini 4 Argon (preço até nov/2026) | 60,00 |
| Gemini 4 Argon (a partir de dez/2026) | 120,00 |
| GPT-6 Astra ou Claude Fable 5.1 | 300,00 |

Os resultados de qualidade, os tokenizadores, as políticas de raciocínio, a quantidade de iterações e de tokens gerados podem mudar muito o custo verdadeiro. A simulação serve exclusivamente para ilustrar **sensibilidade ao preço**.

## 5. Capacidade técnica: o que deve ser homologado com testes

1. **Tarefas e contratos estruturados:** classificações de eventos, extração de sinais, JSON válido, compatibilidade com schema, tratamento de saída malformada, latência e custo.
2. **Uso de ferramentas e orquestração:** capacidade de produzir chamadas com parâmetros corretos, reconhecer erro de ferramenta e aguardar resultado; **nunca permitir execução direta sem autorizador externo à IA**.
3. **Análise de código e infraestrutura:** hipóteses baseadas em evidências de logs/análises, checagem contra estado real, impacto da correção e redução de erros. Não declarar intervenção bem-sucedida apenas por texto plausível.
4. **Casos complexos:** diagnóstico envolvendo API de leads, n8n, CRM, Cloudflare, domínios/DNS, backup, segurança e reprocessamento idempotente, sem perda de dados.
5. **Privacidade e proteção:** minimização e pseudonimização, separação cliente/ambiente, proteção contra injeção por logs e tickets, regras de residência/retencão para cada provedor/conta.
6. **Desempenho e custo total:** latência (p50/p95), disponibilidade, tokens efetivos, ferramentas pagas, caching, chamadas repetidas, throughput, limites de taxa, preços regionais, riscos de reajuste e gastos anormais.
7. **Continuidade:** fallback para modelos homologados; preservar ID/checkpoint e não repetir ações externas já concluídas, mesmo se outro modelo for usado. Operação determinística segue funcionando com **todos os modelos indisponíveis**.
8. **Métricas por classe e por especialidade:** medir desempenho da classe em comparação com custo/qualidade; modelo econômico apenas quando entrega requisito verificável. Critérios numéricos pendentes de aprovação.

### Matriz de exemplos para iniciar a futura homologação

| Tarefa | Classe inicial candidata | Escalonamento candidato | Evidência exigida |
|---|---|---|---|
| Classificar erro de formulário | Econômica ou regra convencional | Intermediária | Categoria/ID, logs minimizados e estado de fila |
| Diagnosticar timeout no n8n | Intermediária | Avançada | Histórico de tentativas, quotas, logs e causa confirmada |
| Falha em deploy Cloudflare | Intermediária | Avançada | Resultado de publicação, versão, testes/rollback |
| Incidente de segurança | Intermediária (análise sem ação) | Avançada e revisão humana quando necessária | Evidências técnicas, contenção autorizada e auditoria |
| Planejar recuperação de API/CRM | Avançada | Especialista humano se houver risco | Estado real, backups, validações, impactos e plano aprovado |
| Verificar integridade/health | **Código determinístico, sem IA** | IA apenas para diagnóstico | Monitoramento independente |

## 6. Privacidade e análise contratual de cada provedor

- **OpenAI:** documentação de controles de dados da API, retenção, monitoramento de abuso, treinamento por padrão e elegibilidade para configurações especiais conforme modelo/organização. Fonte: https://developers.openai.com/api/docs/guides/your-data
- **Anthropic:** conferir documentação dos modelos, contratos para uso comercial, residência/inferência, retenção de dados, acesso administrativo, limites de taxa e uso de ferramentas. Fonte: https://platform.claude.com/docs/en/models/overview e https://platform.claude.com/docs/en/about-claude/pricing
- **Google:** tiers Free/Paid e política de uso de dados são diferentes: **o Free Tier pode usar conteúdo para melhorar produtos; o Paid Tier indica que não usa conteúdo para essa finalidade**. Para dados empresariais da Imagine, avaliar apenas modalidades compatíveis com a política de dados e contratos aprovados. Fonte: https://ai.google.dev/gemini-api/docs/pricing e https://ai.google.dev/gemini-api/docs/billing
- Para todos, **nenhuma senha, token, chave privada, dado pessoal excessivo ou conteúdo de clientes de outro tenant** deve ser enviado. Definir região, confidencialidade, transferência e retenção conforme LGPD, termos e política interna.

## 7. Diretrizes de arquitetura e seleção ainda pendentes de aprovação

**Sugestão de estratégia para futuras decisões, não decisão atual:**
- Catalogar os **nove candidatos** das três classes apenas como **candidatos a testes**, com possíveis modelos excepcionais adicionais.
- Para cada tipo de tarefa, selecionar **primário e uma alternativa homologada**, idealmente de outro fornecedor quando isso reduzir falha compartilhada, sempre sujeito à disponibilidade real, privacidade e orçamento.
- Não instalar modelos grandes na VPS dos agentes: continuar, como hipótese de planejamento, com APIs externas. Monitoramento determinístico e ferramentas essenciais não dependem dessa escolha.
- Implantar critérios claros para elevar classe após diagnóstico insuficiente; não executar loops ilimitados nem utilizar modelo caro para verificação simples.
- Antes de produção, testar funcionalidade real, custos por tarefa, qualidade, segurança, fornecedor, versão e plano de contingência.

**Ficam EXPRESSAMENTE pendentes:** escolha de **modelos, fornecedores, versões e regiões**, primário/fallback por tipo de atividade, desempenho homologado, limites/quotas/cotas, orçamentos mensais e reserva financeira, avaliação de dados/contratos, teste técnico, contratação, implantação e programação. O responsável autorizou **esta comparação, não a seleção definitiva**.

## 8. Fontes públicas oficiais (consultadas em 02/11/2026)

- OpenAI — catálogo: https://developers.openai.com/api/docs/models
- OpenAI — preços: https://developers.openai.com/api/docs/pricing
- OpenAI — GPT-6 Astra: https://developers.openai.com/api/docs/models/gpt-6-astra
- OpenAI — GPT-6.1 Terra: https://developers.openai.com/api/docs/models/gpt-6.1-terra
- OpenAI — GPT-6.1 Sol: https://developers.openai.com/api/docs/models/gpt-6.1-sol
- Anthropic — catálogo: https://platform.claude.com/docs/en/models/overview
- Anthropic — preços: https://platform.claude.com/docs/en/about-claude/pricing
- Google — catálogo: https://ai.google.dev/gemini-api/docs/models
- Google — preços e reajustes anunciados: https://ai.google.dev/gemini-api/docs/pricing

**Conclusão:** pesquisa documentada; **nenhum candidato foi escolhido, homologado, contratado ou autorizado a consumir recursos**, e não houve programação.


## Decisão posterior de 02/11/2026 — Lista inicial para avaliação

**Aprovado:** conservar os nove modelos apresentados nas tabelas das seções 3.1, 3.2 e 3.3 como **candidatos para futura avaliação e homologação**, distribuídos pelas três classes previamente aprovadas. Esta aprovação não escolhe nenhum modelo como principal ou contingência e não equivale a homologação técnica, autorização de uso, contratação ou orçamento. Antes de qualquer escolha final, confirmar os nomes e identificadores de API, disponibilidade e preços atualizados, qualidade por tarefa, compatibilidade de ferramentas e requisitos de proteção de dados. Os dois modelos excepcionais citados no estudo não fazem parte desta lista de nove. **Nenhuma programação, contratação, criação de credenciais ou consumo das APIs foi autorizada.**
