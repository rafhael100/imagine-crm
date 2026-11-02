# PORTAL-01 — Matriz de Pendências do Portal Imagine (Sites por Assinatura)

**Status:** MATRIZ DE PENDÊNCIAS — **PORTAL-D01 aprovada; PORTAL-D02 parcialmente aprovada, agora com cartão como único parcelamento de adicionais e Pix somente à vista; PORTAL-D04 (cobranças recorrentes de sites) em proposta, sem aprovação**. Outras pautas permanecem pendentes.  
**Data:** 02/11/2026.  
**Escopo:** **Portal Imagine**, que contrata e administra sites Onepage/Landing Page; não é o CRM Imagine SaaS nem a futura Universidade Imagine.  
**Fase:** somente planejamento e documentação, **sem programação, contratos, cobranças, infraestrutura nova, migração ou publicação**.
**Rastreabilidade:** este repositório guarda decisões históricas até IN-101 no ramo `docs/integracao-site-crm-basico` em `docs/decisoes.md`, as políticas IN-116/117 naquele ramo e as IN-118+ (integrações, agentes, continuidade) no ramo `main`. Decisões de preços/serviços posteriores IN-102–115 constam também das aprovações conversacionais de 01/11/2026 e devem ser **harmonizadas no documento mestre antes da revisão final**. Esta matriz não altera os registros históricos de cada ramo.

## A. Diretrizes existentes — não reabrir sem solicitação expressa

| Assunto | Situação atual | Observação |
|---|---|---|
| **Onepage** | Implantação **R$ 990**; pagamentos de implantação inicialmente definidos em marcos de 0/30/60 dias; **R$ 79,90/mês a partir do 90º dia** | Valores aprovados como referência vigente de planejamento; revisar condições comerciais integralmente no fim |
| **Landing Page** | Implantação **R$ 1.290**; **R$ 79,90/mês a partir do 90º dia** | Contrato e regras de cobrança específicos ainda precisam ser reconciliados na documentação final |
| **Entrega e ajustes iniciais** | Referência aprovada de **7 dias úteis** e **2 rodadas de ajustes** para Onepage/Landing Page | Explicitar marco de início, suspensão por materiais e limites do escopo; não prometer prazos sem pré-condições |
| **Atualização de conteúdo na vigência** | Sem limite comercial rígido de textos/imagens, sob uso justo; alteração **simples em até 3 dias úteis** | Critérios concretos de uso justo, marcos do prazo e complexidade ainda exigem detalhamento |
| **Mudança estrutural** | Layout, novos blocos e funcionalidades adicionais **dependem de orçamento prévio** | Distinguir atualização incluída de serviço extra, preservar trilha de aceite |
| **Orçamentos de adicionais** | Portal é canal oficial; orçamento **validado pela Imagine** e **aceito expressamente pelo cliente**; até **R$ 270 à vista**, acima desse valor pode ser **à vista ou em até 3 parcelas** | Taxas, vencimentos, juros, política de cancelamento de adicionais, forma de cobrança e fluxo após pagamento permanecem pendentes |
| **Site + CRM Básico** | Site elegível concede mesmo CRM Básico público sem mensalidade própria; upgrade do CRM é cobrado pelo próprio CRM | Referências atuais CRM Básico **R$ 69** e Essencial **R$ 89**, sujeitas à revisão comercial final |
| **Múltiplos sites/organizações CRM** | Pode vincular site a organização CRM existente ou solicitar nova; mudança posterior **gratuita por chamado**, sem migração automática de dados; uma cortesia por organização com site elegível | IN-117 e IN-119–123 já aprovaram regras adicionais, incluindo transição de 30 dias da cortesia e proteção de dados |
| **Sites estáticos e operação** | **Cloudflare Workers Static Assets**, GitHub para versões, agentes/APIs/n8n para processos autorizados; **sem WHM/cPanel** para novos sites Onepage/Landing Page | IN-096–101; Workers for Platforms é hipótese futura, não escolha |
| **Domínios e backups** | Domínios próprios preservam titularidade/e-mail; registros novos só conforme autorização/contrato; backups de site separados dos dados CRM; histórico/rollback e aprovação humana para operações críticas | IN-100/101; frequência/retenção/local de backup ainda serão dimensionados |
| **Suporte e integração de leads** | Central de Suporte IA no Portal com protocolos; formulários protegidos, duas APIs independentes e recibo durável; Portal exibe estados sem acesso indiscriminado aos dados do CRM | IN-099/123–125; critérios operacionais e custos ainda pendentes |
| **Aceite da versão de site** | Cliente deve aprovar **expressamente a versão apresentada**; silêncio não equivale a aceite | IN-095; evidência técnica, autenticidade, prazos e retenção da prova a definir |

**Atenção:** dados históricos em ramos diferentes não são prova de que existe documento único consolidado em `main`. Fazer sincronização/validação documental antes da aprovação final do projeto, preservando os números já decididos.

## B. Decisões e especificações ainda abertas — priorização sugerida

| Código de pauta | Tema | O que está pendente | Situação |
|---|---|---|---|
| **PORTAL-D01** | **Aceite eletrônico de versões de site e orçamentos** | **Aprovado:** cliente autenticado/autorizado, objeto/versão exatos, aceite expresso, registro auditável, preservação da prova, pedido de ajustes e contestação; aceite não representa pagamento. **Pendente:** modelo contratual e detalhes técnicos/jurídicos de assinatura, retenção e UX. | **Fluxo funcional APROVADO** — [PORTAL-D01](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md) |
| **PORTAL-D02** | **Adicionais e cobrança** | **Decisão vigente (02/11):** até R$ 270 à vista; acima de R$ 270 à vista ou até 3 parcelas **EXCLUSIVAMENTE POR CARTÃO** sem juros; execução após aceite e recebimento confirmado. **Pix à vista permitido e incentivo comercial ao Pix em estudo; Pix em parcelas foi revogado.** Pendente descontos, vencimentos, métodos à vista, taxas, estornos e inadimplência. | **APROVAÇÃO PARCIAL** — [PORTAL-D02](PORTAL-D02-pagamento-servicos-adicionais.md) |
| **PORTAL-D03** | **Uso justo e prazos** | Como caracterizar conteúdo simples versus mudança estrutural, evitar abuso sem estabelecer teto oculto, marco de contagem dos 3 dias e cronograma de pedidos complexos | **Pendente**; preservar conteúdo sem teto fixo |
| **PORTAL-D04** | **Mensalidade e cobrança recorrente de sites** | **Proposta em revisão:** Pix Automático e cartão de crédito recorrente, com Pix manual para contingência e boleto opcional. Diferenciar recorrência de financiamento Pix parcelado; manter mensalidade a partir do 90º dia, autonomia financeira do Portal e elegibilidade da cortesia CRM. Pendente modalidades, vencimentos, falhas, cancelamento, PSP e custos. | **PENDENTE DE DECISÃO** — [PORTAL-D04](PORTAL-D04-cobranca-recorrente-sites.md) |
| **PORTAL-D05** | **Viabilidade econômica** | Custos reais de publicação, agentes, IA, APIs, armazenamento, backups, atendimento, meios de pagamento, impostos e margem por site/serviço; revisão final de preços | **Pendente de orçamento e homologação** |
| **PORTAL-D06** | **Arquitetura multiempresa da Cloudflare** | Worker por site versus agrupamento seguro, limites/quotas reais, desempenho, isolamento, domínio/SSL, critérios para Workers for Platforms | **Pendente de especificação e testes** (IN-097) |
| **PORTAL-D07** | **Backups, retenção, segurança e LGPD** | Frequência, destino, prazo de retenção, testes, RTO/RPO, políticas de dados de clientes e descarte seguro, separar Portal/site/CRM | **Pendente de parâmetros e custos** |
| **PORTAL-D08** | **Experiência e administração do Portal** | Fluxos finais de contratação, financeiro/orçamentos, uploads, briefing, comunicação, histórico, painel de integrações, suporte e papéis; UX e critérios de aceite | **Pendente de especificação e protótipos** |
| **PORTAL-D09** | **Operação, agentes e suporte** | Alçadas executáveis, evidências de sucesso, prazos/SLA contratuais efetivos, horários e cobertura humana real, observabilidade, escalonamento de incidentes | **Pendente de especificação e capacidade** |
| **PORTAL-D10** | **Consolidação documental final** | Harmonizar IN-091–115, IN-116/117, IN-118+ e documentação cruzada, confirmar decisões sem duplicação e obter aprovação final expressa antes do Codex | **Pendente, bloqueia programação** |

A priorização **não significa aprovação da solução** nem reabre regras anteriores. No ato de detalhar, verificar todas as decisões anteriores para não refazer perguntas já respondidas pelo responsável.

## C. PORTAL-D01 — aceite formal da versão e do orçamento (APROVADA em 02/11/2026)

**Fluxo funcional aprovado expressamente pelo responsável em 02/11/2026 (resposta “sim”) à proposta abaixo. O detalhamento jurídico e técnico permanece pendente:**

1. **Aceite autenticado:** somente cliente/representante **autenticado e autorizado** poderá aprovar a versão específica do site ou um orçamento de adicional no Portal.
2. **Identificação inequívoca:** apresentar claramente **site/projeto, escopo, número e URL da versão, data, valor/condições de orçamento quando houver** e ação explícita de aceitar; silêncio, visualização, envio automático de URL ou passagem de prazo **não constituem aceite**.
3. **Registro de evidências:** guardar **versão/identificador, conteúdo relevante do termo aceito, usuário e autorização, data/hora com fuso, evento auditável e integridade verificável**; definir tecnicamente dados mínimos necessários e prazo de retenção em documento contratual/LGPD. IP e dados do dispositivo, caso necessários e proporcionais, precisam de justificativa, controle de acesso e retenção próprios — **não presumir coleta irrestrita**.
4. **Imutabilidade da prova:** alteração posterior de versão ou orçamento **não reescreve o aceite antigo**; criar novo registro e colher novo aceite quando houver mudança material.
5. **Solicitação de ajustes:** permitir recusa justificada ou pedido de revisão pelo próprio Portal, com histórico e vinculação à versão; **duas rodadas incluídas** nas condições já aprovadas; excedentes ou alteração estrutural passam ao fluxo de escopo/orçamento, sem cobrança automática.
6. **Aceite de orçamento ≠ pagamento nem autorização financeira implícita:** orçamento validado previamente pela equipe Imagine, com preço, escopo, prazo e forma de pagamento apresentados. A execução poderá ficar condicionada a um evento de pagamento se **o respectivo marco for expressamente aprovado em PORTAL-D02**, não presumir cobrança automática.
7. **Contestação e correção administrativa:** preservar histórico, não apagar prova e oferecer chamado para questionamento; correções exigem justificativa, autorização e trilha de auditoria.

**Pendente de discussão separada:** eventual necessidade de autenticação reforçada, assinaturas eletrônicas de nível específico para cada documento, campos e períodos de retenção, contrato padrão, validade do orçamento, marcos de vencimento e início de execução, fluxos de desistência/reembolso e conformidade jurídica. Não afirmar que o aceite proposto supre sozinho todos os requisitos jurídicos.

## D. Continuidade do planejamento — próxima pauta PORTAL-D02

**PORTAL-D01 está aprovada** e documentada em [PORTAL-D01](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md). **Próxima pauta: PORTAL-D02 — regras financeiras de adicionais e orçamentos**, seguida de PORTAL-D03 (uso justo e prazos), sem alterar os preços e condições já aprovados; ao final consolidar contratos, limites, capacidade/custos e revisão jurídica/financeira.

**Somente a PORTAL-D01 recebeu aprovação funcional nova nesta matriz. PORTAL-D02 e seguintes não foram aprovadas. Nenhum código, contrato ou servidor foi criado e nenhuma contratação realizada.**


## Registro de aprovação PORTAL-D01 (02/11/2026)

Em resposta à pergunta direta de aprovação, o responsável respondeu **“sim”** e aprovou as regras de **aceite eletrônico autenticado, por versão e orçamento identificáveis, prova íntegra e auditável, novo aceite para mudança material, solicitação de ajustes e contestação, sem cobrança automática nem confundir aceite com pagamento**. Preservar as duas rodadas de ajustes incluídas, a validação prévia dos orçamentos pela Imagine e o aceite explícito do cliente. Documento funcional detalhado: [PORTAL-D01 — Aceite Eletrônico de Versões e Orçamentos](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md). Retenção de comprovantes, termos legais e tecnologia de assinatura dependem de detalhamento e validação jurídica posteriores. **A PORTAL-D02 segue pendente; nenhuma programação/cobrança autorizada.**


## Decisão parcial PORTAL-D02 — liberação da execução após pagamento (02/11/2026)

O responsável respondeu **“ok”** à proposta de **somente iniciar a execução de serviço adicional após aceite eletrônico válido do orçamento + confirmação do pagamento integral (à vista) ou da primeira parcela (parcelado)**. A decisão mantém os marcos comerciais já definidos — **até R$ 270 à vista**; **acima de R$ 270 à vista ou até três parcelas** — e não altera implantação/mensalidade normal dos sites, CRM, autonomia de agentes ou contratos. O fluxo funcional está registrado na [PORTAL-D02 — Pagamento de Serviços Adicionais](PORTAL-D02-pagamento-servicos-adicionais.md). **PORTAL-D02 não está integralmente encerrada:** taxas do processador, vencimentos, meios de pagamento, atrasos posteriores, estornos e cancelamento continuam sem aprovação; o parcelamento sem juros foi aprovado em decisão posterior. Não iniciar cobrança ou programação. **Decisão posterior de 02/11/2026:** o parcelamento em até 3 vezes sem juros ao cliente para adicionais acima de R$ 270 foi aprovado. A próxima pauta são meios de pagamento e vencimentos.


## Atualização PORTAL-D02 — Pix em parcelas e incentivos comerciais (02/11/2026)

O responsável determinou **aceitar Pix também no parcelamento de adicionais acima de R$ 270**, preservando até 3 parcelas sem juros e liberação do serviço após aceite + primeira parcela liquidada. Pediu **priorizar Pix na comunicação e avaliar descontos que incentivem pagamento integral ou de todas as parcelas via Pix**, em benefício de custos e fluxo financeiro. O Pix em três cobranças próprias **não significa recebimento integral instantâneo**; cada transferência liquida na data do pagamento. Modalidade de crédito bancário que paga o vendedor integralmente é diferente e **pode gerar juros ao cliente**, exigindo análise própria antes da oferta. **Nenhum percentual ou valor de desconto foi fixado**. Os meios de cobrança, autorizações Pix Automático, prazos, vencimentos, taxas, atraso, cancelamento e reembolsos continuam pendentes na [PORTAL-D02](PORTAL-D02-pagamento-servicos-adicionais.md). As condições aceitas deverão ser comunicadas com transparência e baseadas no custo real. **Sem programação ou cobrança**.


## Decisão posterior e prevalente PORTAL-D02 — somente cartão para parcelamento (02/11/2026)

O responsável confirmou que **somente o cartão de crédito será ofertado para parcelar serviços adicionais acima de R$ 270 em até 3 vezes sem juros**. Isto **substitui** a manifestação histórica anterior de aceitar Pix em parcelas; manter Pix à vista e avaliação de incentivo comercial ao Pix, sem desconto numérico aprovado. Não confundir operações de crédito Pix disponibilizadas pelo banco do cliente com parcelamento oferecido pela Imagine. A seção atualizada da [PORTAL-D02](PORTAL-D02-pagamento-servicos-adicionais.md) prevalece sobre seus parágrafos históricos anteriores.

## Abertura de discussão PORTAL-D04 — mensalidades recorrentes dos sites (02/11/2026)

Ao perguntar sobre pagamentos recorrentes, o responsável abriu pauta específica, **sem aprovar uma forma ainda**. A [proposta PORTAL-D04](PORTAL-D04-cobranca-recorrente-sites.md) recomenda **Pix Automático e cartão recorrente** como meios principais da mensalidade dos sites, com Pix manual como alternativa, boleto opcional e autorização do pagador, sem presumir financiamento. Preservar início da mensalidade no 90º dia conforme contrato, regras financeiras do Portal e separação das cobranças do CRM. Datas, custos, fornecedor e elegibilidade do incentivo recorrente ficam pendentes; nenhuma contratação, cobrança ou programação foi autorizada.

## Proposta comercial — Pix 20%, cartão 4x e mensalidade R$ 87 (02/11/2026)

O responsável apresentou como **hipótese, não aprovação**: implantação exemplo de **R$ 1.200** com Pix à vista **20% OFF (R$ 960)** ou cartão em **até 4x de R$ 300**; mensalidade-base de **R$ 87**, com **10% OFF via Pix Automático (R$ 78,30)** e cartão recorrente por R$ 87. **Ainda não confirmou se R$ 1.200 é preço real do Onepage, Landing Page, ambos ou apenas ilustração; tampouco estendeu o 4x aos serviços adicionais.** O desconto de 20% precisa de validação econômica; preços atuais, parcelas anteriores e regras específicas da PORTAL-D02 permanecem válidos até decisão expressa. Documento: [PORTAL-COM-02 — comparação e proposta comercial](PORTAL-COM-02-proposta-revisao-precos-pix-20-cartao-4x-mensalidade-87.md). A [PORTAL-D04](PORTAL-D04-cobranca-recorrente-sites.md) continua como proposta, ainda não homologada. **Sem programação/cobranças reais.**


## Diretriz funcional PORTAL-COM-03 — Motor de preço, campanhas e urgência verificável (02/11/2026)

O responsável **definiu como estratégia funcional** preços de tabela por produto, **promoções configuráveis** por valor/percentual, **20% adicionais no Pix à vista de implantação mesmo depois de qualquer promoção elegível** e **10% no Pix Automático da mensalidade**, sem confundir essas duas bases. Solicitou **contador verificável de vagas reais e/ou prazo real**, encerrando a campanha quando **a quantidade se esgotar OU o tempo chegar ao fim, o que ocorrer primeiro**. Tudo permanece em planejamento, sem execução. Exemplo ilustrativo: tabela R$ 2.500 → promoção R$ 1.200 → Pix R$ 960; ou promoção R$ 600 → Pix R$ 480. **R$ 2.500 como preço regular de cada produto, os preços promocionais, margem, horários e limites numéricos não estão individualmente aprovados.** Preço “de/por” só deve comparar preços-base efetivamente praticáveis/comprováveis; **proibida escassez artificial, contador reiniciado ou preço inflado para simular desconto**. Registro detalhado: [PORTAL-COM-03 — Motor de campanhas](PORTAL-COM-03-motor-precos-promocoes-pix-contadores.md). Preço mensal-base de R$ 87 e parcelamento 4x de implantação ainda exigem ratificação por produto; não alterar regras do orçamento de adicionais PORTAL-D02 por inferência. A proposta preliminar [PORTAL-COM-02](PORTAL-COM-02-proposta-revisao-precos-pix-20-cartao-4x-mensalidade-87.md) é **histórica e complementada** por essa diretriz.


## PORTAL-COM-04 — Tabelas e promos De/Por por serviço (02/11/2026)

Diretriz aprovada: **cada serviço terá seu próprio preço de tabela e preço promocional de implantação e mensalidade**, sem valores únicos obrigatórios para Onepage e Landing Page. Camadas de incentivo independentes: **20% adicionais no Pix à vista da implantação** e **10% adicionais no Pix Automático da mensalidade promocional contratada**. Campanhas podem abranger uma ou ambas as dimensões. Os valores de R$ 2.500, R$ 1.200, R$ 120 e R$ 87 são apenas exemplos ou hipóteses; preços definitivos por produto pendentes. A oferta De/Por exige referência comprovável e não autoriza alterar contratos existentes ao término da campanha. Detalhes em [PORTAL-COM-04](PORTAL-COM-04-precos-individuais-implantacao-mensalidade-de-por.md). Sem programação.


## PORTAL-COM-05 — Painel administrativo e vigência de mensalidades promocionais (02/11/2026)

**Aprovado como diretriz funcional:** preços de tabela por serviço e promoções de implantação/mensalidade devem ser **configuráveis pelo painel administrativo para cada campanha**, sem alterar o código; a mensalidade promocional pode vigorar **durante N meses** previstos no contrato, passando à condição posterior expressa desde o início, **ou durante todo o contrato ativo**. **Somente reajustes anuais conforme índice definido contratualmente após 12 meses de contrato**; a campanha terminar na vitrine não altera assinatura vigente. Permanecem pendentes o índice escolhido, preços-base concretos, prazos específicos de futuras campanhas, detalhes de calendário e data-base da cobrança inicial (90 dias é o padrão atual configurável), base de cálculo anual, condições em caso de inadimplência/suspensão e regras jurídicas da transição promocional. Preservar as camadas de 20% no Pix da implantação e 10% no Pix Automático da mensalidade, após a campanha pertinente. Documento detalhado: [COM-05](PORTAL-COM-05-gestao-administrativa-precos-campanhas-reajuste-anual.md). **Nenhuma programação/cobrança autorizada.**


## PORTAL-COM-06 — Configuração administrativa do início da primeira cobrança (02/11/2026)

**Aprovado expressamente:** para cada **serviço** e **campanha**, o administrador pode definir o prazo para **início da primeira mensalidade**, sendo **90 dias o prazo padrão atual** (não um prazo rígido de sistema). A data concreta deve ser mostrada e contratada, e alterações de campanha não afetam contratos existentes. Também **aprovado:** promoção de N mensalidades começa a contar na **primeira mensalidade devida**, não na assinatura do contrato. O **reajuste anual pelo índice contratual após 12 meses de contrato** é um marco separado. Permanecem pendentes escolha das durações específicas das futuras campanhas, cálculo exato da data-base, calendário, índice contratual e tratamento de promoções que ultrapassem aniversário anual. Documento: [COM-06](PORTAL-COM-06-inicio-mensalidade-configuravel-por-campanha.md). **Não altera imediatamente os 90 dias nem autoriza programação/cobranças.**


## PORTAL-COM-07 — vencimento recorrente administrável (02/11/2026)

O responsável respondeu **“ok”** à regra de **vencimento mensal configurável no Portal por produto/campanha**, com **padrão no mesmo dia do mês da primeira mensalidade devida**. Preservar os contratos existentes e os três marcos independentes: início da primeira cobrança (90 dias como padrão atual), contagem de N mensalidades promocionais desde a primeira mensalidade devida, e reajuste anual pelo índice pactuado após 12 meses de contrato. **Decisão posterior aprovada por “ok”:** em meses sem o dia de referência, vencer no **último dia calendário do mês** (dia 31 → fevereiro 28/29 → março 31), sem perder o dia âncora. **Ainda pendentes:** feriados/fins de semana e calendário por meio de pagamento. Documento [PORTAL-COM-07](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md). Sem implementação ou cobrança.


## Decisão complementar PORTAL-COM-07 — vencimentos em meses curtos (02/11/2026)

**APROVADO:** em mês no qual o dia de vencimento originalmente contratado não exista, cobrar com vencimento no **último dia calendário disponível desse mês**, retomando **o dia original** nos meses seguintes que o possuam. Ex.: âncora 31 → fevereiro 28/29 → março 31. A aprovação “ok” foi dada à pergunta específica sobre essa regra, **não** à regra de feriados/fins de semana. Restam esses casos e as particularidades operacionais por meio de pagamento. Referência [COM-07](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md). Sem programação nem cobrança.


## Decisão posterior PORTAL-COM-07 — tentativa em dia não útil e prazo de regularização (02/11/2026)

**Aprovação corretiva mais recente:** cobrar/tentar debitar **na data configurada**, inclusive sábados, domingos e feriados bancários, conforme suporte e respaldo legal do meio de pagamento. **Se falhar em dia não útil, permitir quitação até o próximo dia útil bancário, sem juros e multa**, sem penalidade por essa falha nesse prazo. A tentativa não será automaticamente transferida ao dia útil; este é apenas o prazo de regularização sem encargos. **Separar** data original, status da tentativa e data-limite sem encargos; preservar vencimento-âncora, preço promocional e ausência de cobranças duplicadas. Para Pix Automático, a tentativa em dia não útil exige validar a configuração do PSP e respaldo legal, pois pode haver ajuste padrão para dia útil. Política de atraso após a janela, calendários locais e retentativas permanecem pendentes na [PORTAL-COM-07](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md). Não iniciar programação/cobranças.

## PORTAL-D04 — Pendência sem suspensão imediata após regularização (02/11/2026)

**Aprovado por resposta “ok” à pergunta específica:** vencido o prazo de regularização sem juros/multa após falha em sábado, domingo ou feriado bancário, se o pagamento **continuar não confirmado**, o Portal **marca a mensalidade como pendente de pagamento e notifica o cliente**, **sem suspender o site imediatamente**. Verificar conciliação para não gerar cobrança duplicada nem penalizar pagamento efetivamente liquidado. **A quantidade de dias de tolerância antes de eventual suspensão permanece pendente**, assim como avisos, encargos, retentativas, reativação e impacto contratual. A cortesia CRM não é suspensa automaticamente por falha de pagamento: conservar regras independentes já aprovadas de elegibilidade/transição. Registro [PORTAL-D04](PORTAL-D04-cobranca-recorrente-sites.md). Planejamento apenas.


## PORTAL-COM-08 — Site inadimplente e beneficio CRM configuravel (02/11/2026)

**Aprovado:** **7 dias corridos de tolerância para suspensão do site**, alinhados à política do CRM Imagine, respeitado prazo de regularização em dia não útil da COM-07; após conciliação e suspensão efetiva de um site, **bloquear operacionalmente o CRM Básico cortesia SOMENTE quando a conta depender exclusivamente daquele site suspenso**. Não afetar outros sites ativos elegíveis da mesma conta nem plano CRM pago independente, nem apagar dados. Reativar com elegibilidade e pagamento legítimos. **A oferta de cortesia do CRM Básico será administrável por produto e campanha para NOVOS contratos**, com opção sim/não ou herdar, informação explícita no aceite, auditabilidade, mantendo as cortesias já prometidas em contratos antigos. Preservar a **transição de 30 dias IN-119–121 para cancelamento formal do último site**, separada da suspensão temporária por dívida; transição entre estados exige especificação. **Pendentes:** marco exato de contagem dos 7 dias em não úteis, política de encargos, avisos, prazo de cancelamento do site e detalhamento do acesso restrito/retomada. Documento [PORTAL-COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md). Somente planejamento.


## PORTAL-COM-09 — transição da cortesia após cancelamento por inadimplência (02/11/2026)

**APROVADO (“sim”):** no **cancelamento formal do último site elegível por inadimplência após suspensão**, o CRM Básico cortesia mantém **acesso operacional bloqueado durante os 30 dias de transição**, oferecendo **exportação/download/portabilidade e assinatura CRM paga mediante aceite expresso**. No **cancelamento comum** do último site, aplicam-se os **30 dias de funcionamento normal**, como previsto nas IN-119–121. Ambos os prazos começam no encerramento formal do último vínculo, **não** no atraso ou na suspensão prévia de sete dias. Se outro site ativo elegível sustentar a mesma conta ou houver CRM pago válido, **preservar acesso sem bloqueio indevido**. Não gerar cobrança automática nem excluir dados; retenção de 120 dias somente após cancelamento formal da **conta CRM**, não do site. **Pendente:** quando cancelar o site após suspensão, validações de reversão, mensagens e calendário técnico. Documento [COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md). Planejamento apenas.
