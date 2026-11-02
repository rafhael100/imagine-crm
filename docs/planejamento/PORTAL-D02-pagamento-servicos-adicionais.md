# PORTAL-D02 — Pagamentos, Parcelamento e Execução de Serviços Adicionais do Portal Imagine

**Status:** **APROVAÇÃO PARCIAL EM 02/11/2026**: aprovados (a) início de execução condicionado ao aceite e ao recebimento integral ou da primeira parcela e (b) **até três parcelas sem juros ao cliente para adicionais acima de R$ 270,00**, com valor total e parcelas informados previamente. **DECISÃO MAIS RECENTE (02/11/2026): parcelamento de adicionais SOMENTE PELO CARTÃO DE CRÉDITO. A hipótese anterior de cobrança Pix em parcelas foi expressamente substituída; Pix permanece opção de pagamento À VISTA. Incentivo comercial ao Pix à vista segue como diretriz, sem percentual de desconto aprovado. Taxas, descontos, vencimentos, cartão, inadimplência, cancelamento e reembolso continuam pendentes.**  
**Fase:** somente planejamento e documentação. **Não autoriza cobrança real, contratação, programação, comunicação comercial, emissão de faturas ou alteração operacional**.
**Escopo:** serviços extras/adicionais e orçamentos específicos solicitados por clientes no **Portal Imagine**, **não** implantação base de Onepage/Landing Page, mensalidade normal do site, contratação de CRM ou benefícios do CRM.
**Referências:** [PORTAL-D01 — aceite expresso](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md), [PORTAL-01 — matriz de pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md), contratos e políticas financeiras ainda sujeitos a revisão ao final do planejamento.

## 1. Valores e formas anteriormente definidos — preservar

- **Orçamento adicional até R$ 270,00:** pagamento **à vista**.
- **Orçamento adicional acima de R$ 270,00:** cliente poderá optar entre **à vista ou parcelamento em até 3 vezes sem juros para o cliente**, com preço total fechado e parcelas apresentadas previamente. Custos previstos de processamento devem ser considerados na formação do orçamento pela Imagine; os meios e vencimentos ainda serão definidos.
- **Orçamento pré-validado pela equipe Imagine** é submetido a **aceite eletrônico expresso pelo cliente ou representante autorizado**, conforme PORTAL-D01.
- **Sem juros no parcelamento** aprovado; não incluir acréscimos financeiros surpresa no orçamento aceito. Esta decisão não cria desconto automático à vista, nem define meio de pagamento, calendário de vencimento, antecipação financeira ou termos jurídicos.

## 2. Decisão funcional expressamente aprovada — marco para iniciar execução

**O serviço adicional só poderá ser liberado para execução depois que os dois eventos seguintes forem verificados:**

1. **Aceite eletrônico válido** do orçamento exato, incluindo escopo, versão, preço e condições apresentadas, por cliente autenticado e autorizado (PORTAL-D01).
2. **Confirmação financeira do recebimento**:
   - **à vista:** confirmação do pagamento integral devido para iniciar o serviço adicional;
   - **parcelado:** confirmação do pagamento efetivo da **primeira parcela** do orçamento aprovado.

- **Aceite isolado não libera execução**, nem autoriza débito automático.
- **Pagamento não comprovado** por conciliação/validação confiável não libera execução; comprovante enviado isoladamente pelo cliente não equivale necessariamente à liquidação confirmada.
- **Confirmado aceite + recebimento necessário**, o Portal pode liberar a ordem de serviço para a equipe Imagine ou para agentes dentro das permissões e procedimentos de produção já homologados. A liberação financeira não substitui checagens de escopo, qualidade, autorização de mudança, versão nem eventuais aprovações técnicas.
- Registrar vínculos auditáveis entre **pedido/orçamento/versão aceitos, cliente autorizado, transação/primeira parcela, confirmação e ordem de serviço**, com prevenção de processamento duplicado (idempotência).
- **Não criar regra de execução antecipada por urgência, confiança no cliente, exceção automática ou promessa de pagamento** sem aprovação posterior específica.
- O marco acima vale para o **início da execução do adicional**. **Não altera marcos de pagamento da implantação do site, o dia 90 da mensalidade ou a concessão do CRM Básico**.

## 3. Exemplos esclarecedores

| Orçamento extra | Forma escolhida | Evento mínimo para liberar execução |
|---|---|---|
| R$ 180,00 | À vista | Aceite expresso + R$ 180,00 recebidos e confirmados |
| R$ 270,00 | À vista | Aceite expresso + R$ 270,00 recebidos e confirmados |
| R$ 300,00 | À vista | Aceite expresso + R$ 300,00 recebidos e confirmados |
| R$ 300,00 | Até 3 parcelas | Aceite expresso + **primeira parcela recebida e confirmada**, segundo plano efetivamente contratado; vencimentos/saldos sujeitos à próxima decisão |

**Juros ao cliente no parcelamento foram expressamente dispensados pela aprovação posterior.** Valor total e valores das parcelas devem ser exibidos antes do aceite; valores exatos de cada parcela, vencimentos, meios, política de taxas cobradas de fornecedores e descontos à vista ainda dependem de especificação, sem acréscimos inesperados depois do aceite.

## 4. Pendências que NÃO foram aprovadas neste “ok”

1. **Parcelamento — parcialmente resolvido:** **sem juros ao cliente**, até 3 vezes, para orçamentos acima de R$ 270,00. **Ainda pendentes:** como serão distribuídos os valores e vencimentos, quais meios aceitam parcelamento e como tratar taxas do adquirente sem repassá-las como acréscimo surpresa ao preço aceito.
2. **Meios de pagamento:** cartão, Pix, boleto e eventuais outros; conciliação, expiração e disponibilidade por modalidade.
3. **Vencimentos e prazos:** validade do orçamento, prazo para pagamento da primeira parcela, vencimentos das demais, início da contagem dos prazos de entrega e expiração da ordem de serviço.
4. **Atraso posterior:** consequências de inadimplência de parcela subsequente para serviços iniciados/em andamento/concluídos, aviso, suspensão quando contratualmente permitida, cobrança e renegociação.
5. **Desistência, contestação e reembolso:** tratamento de trabalhos não iniciados, parcialmente entregues ou concluídos, estorno/chargeback e reversão segura, à luz da legislação aplicável.
6. **Autorizações excepcionais:** casos de gratuidade, crédito, serviços contratuais já cobertos, compensações e procedimentos de liberação manual, com alçadas e auditoria.
7. **Contrato, termos e compliance:** impostos/documentos fiscais, retenção dos comprovantes, apresentação de informações ao cliente, reconciliação, antifraude e revisão jurídica.

## 5. Parcelamento sem juros — DECISÃO APROVADA

**Aprovado em 02/11/2026 (resposta “sim” à pergunta específica):** oferecer **até três parcelas sem juros ao cliente** para serviços adicionais acima de **R$ 270,00**, com **valor total fechado já contemplando custos de processamento previstos** na formação do preço, sem acrescentar encargos inesperados; apresentar previamente os valores das parcelas. Não criar desconto automático à vista sem aprovação própria. Esta regra **não define** tecnologia de cobrança, antecipação financeira, taxas concretas do adquirente, calendário de vencimentos ou meios aceitos.

**Próxima decisão pendente:** estabelecer **meios de pagamento e calendário de vencimentos** dos serviços adicionais, distinguindo parcelamento em cartão de pagamentos recorrentes via Pix/boleto quando houver. Após isso, tratar cobrança de atraso, cancelamento, reembolso e contestação.

**Sem testes, software, contratação ou execução financeira realizados.**


## 6. Registro da segunda aprovação parcial PORTAL-D02 (02/11/2026)

O responsável respondeu **“sim”** à pergunta expressa sobre o **parcelamento em até três vezes sem juros** para adicionais **acima de R$ 270**, com **valor fechado e informado antes do aceite**. Preserva-se a regra anterior de serviço liberado **somente após aceite eletrônico válido e recebimento confirmado do valor à vista ou da primeira parcela**, respectivamente. O preço será formado contemplando custos previsíveis de processamento, **sem cobrar juros do cliente pelo parcelamento**, nem adicionar encargos inesperados após aceitar o orçamento. Não houve aprovação de desconto à vista automático, juros por atraso, meios de pagamento, datas das parcelas, antecipação financeira, inadimplência, reembolso, operações reais ou programação. O item **PORTAL-D02 permanece parcialmente aberto**.


## 7. Histórico: proposta anterior de Pix parcelado (SUPERADA pela seção 8)

**Direção aprovada pelo responsável, com linguagem expressa:** "no parcelamento, podemos aceitar também o PIX parcelado. Podemos incentivar seja por texto levando o cliente à escolha do PIX ou por ações de mais desconto o cliente efetuar todos os pagamentos via PIX". Assim, a proposta anterior de parcelamento exclusivamente por cartão **não foi aprovada** e não deve constar como restrição funcional.

### 7.1 Opções de pagamento dentro do escopo de serviços adicionais

- **Pix à vista:** elegível para orçamentos de qualquer valor, com liberação do serviço somente após aceite válido + confirmação do **valor total efetivamente recebido**.
- **Pix em parcelas cobradas diretamente pela Imagine:** para **orçamentos acima de R$ 270**, oferecer **até três parcelas sem juros** pagas por Pix. Emitir cobranças/vencimentos individuais ou solução de Pix Automático homologada, **somente depois de aceite/autorizações aplicáveis**. A Imagine recebe **cada parcela na respectiva liquidação**; não considerar as futuras antecipadamente recebidas. O serviço é liberado após aceite + **primeira parcela efetivamente paga**, preservadas demais autorizações contratuais e técnicas.
- **Cartão de crédito:** manter opção à vista e até três vezes sem juros para orçamentos acima de R$ 270, desde que produto/processador permita as condições aprovadas; a política de disponibilidade e tarifas do provedor é detalhe operacional pendente.
- **Boleto à vista:** conservar como **opção em avaliação** da proposta anterior, ainda sem decisão expressa de adoção final nesta etapa.
- **Pix parcelado financiado pelo banco do cliente:** **diferente** de três cobranças Pix da Imagine. Em operação de crédito concedida pela instituição, a transferência pode ser integral e imediata ao fornecedor, enquanto o cliente paga parcelas ao banco, possivelmente com juros e encargos próprios. **Não promover como “sem juros” sem garantia contratual real para o cliente.** Disponibilidade, taxas, comunicação e compatibilidade com política da Imagine ainda exigem escolha específica.
- **Pix Automático:** possibilidade técnica para futuras parcelas se houver provedor compatível e autorização formal do pagador; **não presumir débitos automáticos**, autorização irrevogável ou ausência de tarifas.

### 7.2 Incentivos ao Pix — diretriz aprovada, parâmetros ainda pendentes

- **Diretriz comercial aceita:** o Portal poderá **destacar o Pix de forma informativa e positiva**, incluindo mensagens de conveniência, conciliação mais rápida e eventuais benefícios comerciais, sem esconder cartão ou outras opções contratualmente permitidas.
- **Estratégia de descontos em planejamento:** poderá oferecer condição mais vantajosa para **Pix à vista** e avaliar benefício também quando **todas as parcelas do orçamento** forem pagas via Pix, sempre com política de elegibilidade, preço total e regras transparentes. Nenhum **percentual/valor de desconto foi aprovado agora**; **nenhum desconto automático deve ser ativado** antes de aprovação expressa de suas condições e margem.
- **Não prometer recebimento total instantâneo no Pix em três cobranças**; somente cada transferência liquidada é instantânea. A menor tarifa do Pix pode variar por banco, intermediador e serviço; **simular custo líquido real** versus cartão (tarifas, estornos, crédito, fraudes, cobrança/atraso, adiantamento e esforço operacional).
- **Transparência:** mostrar o valor total com e sem benefício, o meio de pagamento, valor e vencimento de cada parcela e requisitos de elegibilidade **antes do aceite do orçamento**, sem taxas surpresas, descontos fictícios, preços de referência artificiais ou alterações retroativas. Forma de comunicação e regras devem ser revisadas à luz da Lei nº 13.455/2017 e da proteção do consumidor.
- **Regras de preferência Pix não substituem segurança:** somente pagamentos conciliados e confirmados liberam execução. Cobranças Pix futuras precisam de identificador próprio, estados, monitoramento e tratamento idempotente de duplicidade.

### 7.3 Temas expressamente pendentes

1. **Percentual/valor e elegibilidade** do desconto de Pix integral, eventual desconto para todas as parcelas em Pix, possibilidade de desconto à vista no cartão e política de não cumulatividade.
2. **Datas de vencimento**, intervalo entre até três parcelas, prazo de pagamento da primeira parcela, cronograma de entrega e aviso de atraso.
3. Formas de **cobrança Pix** (Pix Cobrança QR dinâmico, Pix Automático/autorizações, pagamento manual) e provedor, integração, conciliação, tarifas e custos operacionais.
4. **Parcelas não pagas**, cancelamento de autorização Pix Automático, disputa de comprovantes, estornos, reembolsos e efeitos em serviço já iniciado.
5. **Apresentação comercial** por modalidade, aceites, limites de uso, obrigações legais e impacto financeiro de descontos.
6. Confirmar se Pix com crédito contratado no banco será oferecido como mecanismo adicional, sem presumir isenção de juros do financiamento.

### 7.4 Fontes públicas consultadas (02/11/2026)

- Banco Central — Pix Cobrança e liquidação: https://www.bcb.gov.br/estabilidadefinanceira/pix-cobranca
- Banco Central — Pix Automático para recebedores e tarifas: https://www.bcb.gov.br/estabilidadefinanceira/pix-automatico-recebedores
- Banco Central — oferta de crédito durante Pix, Resolução BCB nº 603 de 23/10/2026: https://www.bcb.gov.br/estabilidadefinanceira/exibenormativo?numero=603&tipo=Resolu%C3%A7%C3%A3o+BCB
- Lei nº 13.455/2017 — diferenciação de preços por prazo/meio, com informação ostensiva de desconto: https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2017/lei/l13455.htm

**Esta seção registra a decisão de aceitar Pix no parcelamento e a orientação comercial de incentivar Pix. Não define preço/percentual, não ativa cobranças reais nem autoriza desenvolvimento.**


## 8. Decisão mais recente — revogação do parcelamento via Pix (02/11/2026)

**O responsável esclareceu e decidiu:** "vamos continuar trabalhando [...] **somente com o cartão de crédito no parcelamento**". Esta decisão **SUBSTITUI a aceitação anterior de parcelamento Pix** descrita na seção 7, que fica preservada apenas como histórico e **não deve orientar o desenvolvimento**.

### Regra funcional vigente para serviços adicionais

- **Até R$ 270,00:** pagamento **à vista**, incluindo **Pix à vista**; outros meios à vista (ex.: cartão e boleto) continuam sujeitos à definição do catálogo final de meios.
- **Acima de R$ 270,00:** **à vista** (Pix preferencial como opção comercial) ou **parcelamento exclusivamente em cartão de crédito**, até três parcelas **sem juros para o cliente** conforme condições fechadas no orçamento.
- **Não disponibilizar Pix em duas/três cobranças diretas como parcelamento do Portal** nem comercializar "Pix parcelado" financiado por banco como produto próprio do Portal; o cliente pode usar recursos de crédito de sua instituição financeira de forma independente para realizar um Pix à vista, mas a Imagine não promete nem administra juros ou financiamento bancário.
- **Somente liberar execução** de adicional após **aceite eletrônico** e confirmação de **pagamento integral à vista ou pagamento/autorizaçao financeira equivalente à primeira parcela no fluxo de cartão**, com conciliação e garantias da adquirente a especificar. **Esta formulação não autoriza executar sem confirmação financeira efetiva.**
- **Incentivar Pix à vista por comunicação clara e possível desconto** permanece diretriz do responsável. **Percentuais, elegibilidade, valores mínimos e custos devem passar por revisão financeira expressa**, antes de figurar como desconto ativo; eliminar a hipótese de incentivo por "todas as parcelas pagas via Pix", pois essa modalidade de parcelamento foi descartada.
- **Mensalidades recorrentes dos sites** constituem outra política, separada de parcelamento dos adicionais. **Pix Automático para recorrência NÃO é Pix parcelado bancário**; sua adoção ainda será deliberada no **PORTAL-D04**.

### Pendências restantes da PORTAL-D02

Meios finais para pagamentos à vista, processo/fornecedor de cartão e conciliação, datas e contagem de vencimentos, tratamento de falha de autorização/pagamento, tarifas, desconto Pix à vista, inadimplência de parcelas, estornos/reembolsos, regras contratuais e auditoria. A decisão desta seção **não altera o contrato de implantação de site nem o dia inicial das mensalidades**. **Sem programação nem cobranças reais**.

**Referências técnicas do BC:** crédito contratado pelo cliente durante Pix é operação distinta de Pix Automático: https://www.bcb.gov.br/estabilidadefinanceira/exibenormativo?numero=603&tipo=Resolu%C3%A7%C3%A3o+BCB ; funcionamento de recorrência: https://www.bcb.gov.br/estabilidadefinanceira/pix-automatico
