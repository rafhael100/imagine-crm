# PORTAL-COM-06 — Início administrável da primeira mensalidade por serviço e campanha

**Status:** DIRETRIZES FUNCIONAIS **APROVADAS EM 02/11/2026** pelo responsável:
1. **Contar os N meses de mensalidade promocional a partir da primeira mensalidade devida**, e não desde a data de contratação;
2. **Permitir configurar administrativamente, por serviço e por campanha, quando começará a primeira cobrança recorrente**;
3. **Preservar 90 dias como padrão comercial ATUAL**, mas sem torná-lo uma regra fixa de código: campanhas/serviços futuros poderão usar outro marco de início, sempre com aceite do cliente.

**Escopo:** Portal Imagine — mensalidades de Onepage, Landing Page e futuros serviços de assinatura. Não altera a cobrança de implantação, orçamentos de adicionais nem o CRM Imagine SaaS.
**Fase:** planejamento/documentação somente; **sem programação, cobrança, alteração de contrato ativo ou implantação**.
**Referências:** [COM-05 — vigência da mensalidade e índice anual](PORTAL-COM-05-gestao-administrativa-precos-campanhas-reajuste-anual.md), [COM-04 — tabela De/Por por serviço](PORTAL-COM-04-precos-individuais-implantacao-mensalidade-de-por.md), [COM-03 — motor de campanhas](PORTAL-COM-03-motor-precos-promocoes-pix-contadores.md), [D04 — cobrança recorrente](PORTAL-D04-cobranca-recorrente-sites.md), [D01 — aceite](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md), [PORTAL-01 — pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md).

## 1. Regra-base e precedência aprovadas

1. **Padrão atual** da assinatura do site: **primeira cobrança mensal prevista para o 90º dia após a contratação**, conforme marco contratual. **Não há mudança geral do prazo nesta decisão**.
2. Cada **serviço/produto** poderá ter um **prazo-padrão próprio de início da primeira mensalidade** administrado pela Imagine. Na ausência de configuração específica, conserva-se o padrão histórico vigente (90 dias), respeitando contratos/versões aplicáveis.
3. Cada **campanha elegível** poderá **substituir o prazo-padrão do serviço para as novas contratações dessa campanha**: a precedência é **prazo aceito no pedido/contrato > condição de campanha no momento do aceite > condição-padrão do produto**.
4. O administrador poderá editar **somente as condições das ofertas futuras**. Uma campanha nova, encerrada ou editada **não altera a data de cobrança nem a duração de benefícios contratados por clientes anteriores**.
5. A **data efetiva da primeira mensalidade** deve ser claramente **calculada, exibida e incorporada ao aceite** antes da contratação, junto do preço mensal, meio de pagamento, período promocional, índice/aniversário de reajuste e demais condições.
6. **N meses promocionais** significam **N mensalidades devidas no preço promocional**, contadas **desde o primeiro ciclo mensal devido**, independentemente dos dias decorridos desde a assinatura do contrato. Ex.: promoção de 6 mensalidades + primeira cobrança após 90 dias → os 6 primeiros ciclos mensais devidos usam o preço promocional, não somente os primeiros 6 meses corridos desde a assinatura.
7. O **reajuste por índice** possui **relógio separado**: somente depois de **12 meses do contrato**, com periodicidade anual e índice contratual definido. **Mudar início da primeira mensalidade não muda automaticamente o aniversário de reajuste**. Se uma campanha adiar a primeira mensalidade até perto ou depois desse marco, os termos deverão prevenir cobrança/reajuste ambíguos; parametrização de exceções e base de cálculo requer especificação/aprovação jurídica.
8. **Campanha com contador de prazo ou quantidade** controla apenas a oportunidade de **contratar** a condição. A expiração do contador não antecipa a primeira mensalidade de um contrato já aceito.

## 2. Exemplos de campanhas — ilustrações, não valores ativados

| Serviço/campanha hipotético | Início da primeira mensalidade | Promoção mensal | Observação |
|---|---|---|---|
| Padrão vigente | **90 dias após a contratação** | Nenhuma ou conforme campanha | Mantém condição histórica atual |
| Campanha de lançamento | **30 dias** | 6 primeiras mensalidades | Contar seis cobranças a partir da primeira mensalidade devida |
| Black Friday | **120 dias** | Mensalidade promocional enquanto contrato ativo | Prazo de início muda somente para novas vendas dessa campanha |
| Oferta sem carência | **No início do contrato**, se expressamente homologada | 3 primeiras mensalidades | Exemplo de flexibilidade; não se presume permitido/ativado automaticamente |

**Os 30/120/0 dias são números ilustrativos, NÃO datas/condições comercialmente aprovadas.** As opções concretas, limites mínimos/máximos, relação com onboarding/ativação, calendário e viabilidade financeira requerem configuração/projeto.

## 3. Painel administrativo — recursos funcionais e controles sugeridos

**Por produto/serviço:** ativar cobrança recorrente, preço de tabela mensal, modelo contratual, **prazo-padrão para primeira mensalidade** (unidade explícita, preferencialmente dias corridos como no histórico de 90 dias), versão/validade. Sem impor um padrão mensal único a todos.

**Por campanha:** selecionar produto e elegibilidade, definir **prazo especial de primeira cobrança** ou herdar padrão, preço mensal “De/Por”, optar por **mensalidade promocional por N ciclos devidos ou durante o contrato ativo**, escolher forma de pagamento e regras de desconto elegíveis (Pix Automático 10%), campanhas com limite real de tempo/quantidade, status/autorizações, simulação de calendário e impacto financeiro.

**Antes de publicar:** conferir preço-base “De” realmente praticável, clareza da oferta e data da primeira cobrança, margens/custos de adiar mensalidade, integridade do índice de reajuste, aprovação humana de alçada comercial e auditoria. Os valores, datas exatas, cotas e regras de promoção serão salvos como **versão imutável do pedido aceito**.

**Durante execução:** gerar o primeiro título somente na data/competência contratada, não na publicação da campanha ou no dia da adesão quando há prazo de espera; emitir comunicações sobre o início e permitir meios de cobrança homologados; aplicar idempotência, conciliação, tratamento de pagamentos falhos e notificações. **Não conceder ao administrador um botão capaz de antecipar ou postergar unilateralmente obrigações de clientes já contratados**.

**Premissas a validar:** data-base de contratação para início de dias; dias corridos ou úteis; regras para feriados/fins de semana; fuso; data/hora do aceite; data de ativação do site versus assinatura contratual; quando começa o direito de uso do site e benefício CRM; relação do adiamento com cobrança da implantação, cancelamento e reajuste.

## 4. Cenários de borda e transparência contratual

- **Início após a carência de 90 dias** não significa automaticamente “site gratuito”: implantação e escopo têm condições financeiras próprias.
- Se o cliente mudar Pix Automático para cartão, preservar o **valor mensal contratado da campanha**, exceto a **camada de 10% exclusiva do Pix Automático** conforme termos pactuados.
- Se a campanha tiver **mensalidade promocional por N meses**, o valor posterior e a data de transição devem estar **claros e aceitos desde a contratação**; não tratar fim de desconto como reajuste por índice antecipado.
- **Aniversário contratual de 12 meses e N ciclos promocionais** são marcos diferentes. Se ocorrerem durante um mesmo período, não aplicar mudanças sem regra previamente pactuada e juridicamente revisada.
- **Alteração administrativa para novas vendas ≠ mudança contratual retroativa.** Mudanças de um contrato específico exigem procedimento próprio, justificativa, informações e consentimento quando aplicável.
- **Campanhas e promessas de gratuidade/adiamento** só podem ser anunciadas se condições e custos totais estiverem claros ao consumidor, com prova da oferta.

## 5. Pendências de detalhamento, sem reabrir decisões

1. Escolher valores concretos para início da primeira mensalidade em cada serviço e campanha (90 dias é o **padrão atual**, outras opções somente ilustrativas).
2. Marco de data-base — contrato aceito versus ativação do site, contagem de dias corridos, feriados, meses de tamanhos distintos, vencimento.
3. Se a carência pode ultrapassar 12 meses e como deve ser tratado o primeiro reajuste nesses casos.
4. Índice de reajuste anual exato, base de cálculo, comunicação prévia e validade dos preços legados.
5. Modelo operacional e autorização Pix Automático/cartão, conciliação, datas de emissão, falhas/recuperação e cancelamento.
6. Intervalos de configuração de prazo, alçadas comerciais, limites de risco/margem, simulações e aprovação antes de publicação.

**Resumo da decisão:** **cada serviço e cada campanha podem definir quando vence a primeira mensalidade; hoje, padrão de 90 dias; contagem de N mensalidades promocionais começa na primeira mensalidade devida; reajuste anual mantém aniversário próprio após 12 meses de contrato.** Sem programação, alteração retroativa ou nova cobrança.
