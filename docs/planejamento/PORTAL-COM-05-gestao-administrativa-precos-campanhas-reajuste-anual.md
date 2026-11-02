# PORTAL-COM-05 — Administração de preços, campanhas e mensalidades promocionais com reajuste anual

**Situação:** **DIRETRIZES FUNCIONAIS APROVADAS pelo responsável em 02/11/2026** (em duas manifestações consecutivas): (i) preços efetivos e condições promocionais **configuráveis administrativamente por serviço e por campanha**; (ii) mensalidade promocional pode ser por **alguns meses** OU **enquanto o cliente mantiver contrato ativo**, com **reajustes somente anuais pelo índice definido em contrato, após 12 meses de contrato**.  
**Não aprovado:** qualquer preço nominal definitivo por produto, índice de reajuste específico (IPCA, IGP-M ou outro), percentuais especiais além dos 20% Pix implantação e 10% Pix Automático recorrente já registrados, custo, duração padrão, regra de incidência no vencimento de promoção/contrato, reajuste fora das condições pactuadas, fornecedor, publicação comercial, cobrança ou programação.
**Fase:** planejamento/documentação exclusivamente.  
**Produto:** Portal Imagine, sites Onepage/Landing Page e futuros serviços de assinatura; não altera preços, contratos ou políticas do CRM Imagine SaaS.
**Referências:** [PORTAL-COM-03 — campanhas e limites](PORTAL-COM-03-motor-precos-promocoes-pix-contadores.md), [PORTAL-COM-04 — De/Por por serviço](PORTAL-COM-04-precos-individuais-implantacao-mensalidade-de-por.md), [PORTAL-D04 — mensalidades](PORTAL-D04-cobranca-recorrente-sites.md), [PORTAL-D01 — aceite contratual](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md), [PORTAL-01 — matriz](PORTAL-01-matriz-pendencias-plataforma-sites.md).

## 1. Decisão aprovada: administração comercial sem alterar código

Cada produto/serviço poderá ter **seu próprio preço verdadeiro de tabela** (implantação e mensalidade), e o painel administrativo poderá **preparar, revisar, aprovar e publicar** campanhas que alterem preços de novas contratações **por produto, escopo e campanha**, sem edição manual do código do sistema. A campanha define separadamente:
- preço “De/Por” de implantação, quando elegível, com **mais 20% de desconto para Pix à vista** sobre o preço após campanha;
- preço “De/Por” da mensalidade, quando elegível, com **mais 10% para Pix Automático** sobre a mensalidade já promocional;
- tipo de vigência da condição de mensalidade: **prazo determinado por meses** ou **até o término do contrato ativo**;
- marcos de validade/quantidade da campanha para novas vendas (COM-03), distintos das condições já aceitas individualmente;
- parâmetros e modelos contratuais que devem constar claramente do aceite.

**Alterar preço no painel ou encerrar uma campanha só afeta novas ofertas elegíveis; não altera unilateralmente mensalidades já contratadas.** Os descontos de 20%/10% não autorizaram a escolha de valores-base nominais (R$ 2.500, R$ 1.200, R$ 120, R$ 87 são somente exemplos/hipóteses até aprovação por produto).

## 2. Dois modelos de mensalidade promocional — aprovados

### Modelo A — Promoção por quantidade definida de meses

- O administrador configura o **número de meses promocionais** específico daquela campanha e o **valor de mensalidade promocional**.
- O cliente deve ver, **antes do aceite**: valor promocional mensal; duração da condição; **valor da mensalidade após o período promocional**; o marco a partir do qual passa a valer; método Pix Automático e seu desconto de 10%, quando aplicável; índice, aniversário do reajuste anual e demais termos.
- Ao fim dos meses promocionais, aplicar **a condição posterior previamente informada e aceita**, **sem alterar preços arbitrariamente e sem gerar um reajuste por índice com periodicidade inferior a 12 meses**.
- O fim do desconto temporário e o **reajuste pelo índice** são eventos distintos. O reajuste seguirá **somente periodicidade anual e índice contratual**; eventual transição promocional deve ter preço posterior expresso e ser juridicamente validada para evitar caracterização de reajuste disfarçado.
- **Proposta técnica a detalhar:** definir se “N meses” será contado da ativação da assinatura/primeira cobrança ou da data do contrato (marco contratual exato a declarar); respeitar que a mensalidade do site tem referência de início **no 90º dia**, conforme decisões anteriores. Não presumir N mensalidades pagas se a campanha disser N meses corridos.

### Modelo B — Promoção mantida durante todo o contrato ativo

- O valor promocional mensal **persiste durante a vigência do contrato ativo**, sujeito **apenas ao reajuste anual previsto contratualmente** e às condições que o cliente aceitou.
- O encerramento da campanha para **novas contratações** não cancela o benefício do cliente já contratado, nem troca o preço pelo preço de tabela.
- O desconto de **10% pelo Pix Automático**, quando habilitado e elegível, incide sobre a mensalidade contratada vigente (inclusive reajustada); a mudança de método de pagamento pode afetar esse desconto específico, mas **não revoga por si só a condição promocional da assinatura**.
- O encerramento, inadimplência, suspensão, reativação e eventual nova contratação devem respeitar políticas próprias a detalhar; não presumir que uma suspensão apaga benefício nem que todo contrato encerrado manterá preço em futura contratação.

## 3. Reajuste anual — regra aprovada

**Regra informada pelo responsável:** os preços mensais contratados **somente** poderão receber reajuste por índice após **12 meses de contrato**, e depois novamente em intervalos anuais, conforme **índice de reajuste expressamente previsto no contrato**.

- Definir no contrato: **índice escolhido** (ainda pendente), marco temporal do aniversário (referência funcional aprovada: **12 meses de contrato**), data/base de cálculo, série de referência, comunicação, arredondamento, tratamento de índice negativo/não divulgado/extinto e eventuais substituições; estes detalhes **não foram aprovados** por inferência.
- **Não fazer reajuste mensal, trimestral, semestral nem antecipar o índice quando mudar uma campanha**, salvo hipóteses legais excepcionais examinadas separadamente.
- Reajuste deverá ser **calculado sobre a mensalidade contratada vigente**, conforme regra e base contratualmente estabelecidas; o desconto Pix Automático é aplicado à mensalidade elegível conforme política comercial.
- **Nenhum cliente muda de valor por simples edição da tabela/promoção**. Reajuste por índice é operação contratual, com registro de versão, comunicação e auditabilidade, não liberdade discricionária de trocar o valor.
- A disciplina contratual deve ser revisada à luz da **Lei federal nº 10.192/2001, art. 2º**, que estabelece periodicidade mínima anual para reajuste por índice, e às regras de transparência do CDC. Para contratos privados, não copiar sem análise as regras específicas de data-base aplicáveis a contratos públicos.

## 4. Exemplos meramente didáticos — não aprovam preços ou índices

**Exemplo A (6 meses promocionais):** valor promocional fictício R$ 87 por 6 meses de mensalidade (se esse for o critério previsto e aceito na campanha); valor mensal futuro fictício R$ 120 a partir do 7º ciclo; reajuste por índice só no marco anual contratual pertinente, após 12 meses. O preço futuro tem de ser informado e aceito desde o início; a expiração promocional **não é novo índice de reajuste**.

**Exemplo B (por toda a vigência contratual):** valor mensal promocional fictício R$ 87 enquanto contrato estiver ativo. Não mudar para R$ 120 só porque a promoção acabou na vitrine. Após 12 meses de contrato, aplicar apenas o índice contratado à base vigente e manter a condição promocional; Pix Automático elegível aplica os 10% ao valor de mensalidade vigente.

**Atenção:** o primeiro vencimento mensal pode ocorrer a partir do 90º dia, conforme contrato já planejado. O **aniversário de reajuste** e o **marco de duração dos meses promocionais** devem estar escritos e exibidos separadamente, para evitar alterar a contagem por erro.

## 5. Especificação sugerida do painel, campos e controles — detalhes ainda pendentes

- **Catálogo:** produto/variante, contrato, preço real de tabela de implantação, preço real de tabela de mensalidade, versão vigente e prova de referência do “De”.
- **Campanha:** nome, escopo, canal, data início/fim, quantidade real de vagas, regra de encerramento, promo implantação e/ou promo mensal, público elegível, status e prévia de divulgação/checkout.
- **Promoção de mensalidade:** escolher **por N meses** ou **durante todo o contrato ativo**; valores mensais promocional/futuro, marcos, método de pagamento, índice e contrato/versionamento.
- **Reajuste:** índice autorizado e fonte de consulta, data-base individual, cálculo auditável com precisão monetária e prévia de comunicação; alçada para exceções/alterações excepcionais, sujeito a revisão jurídica.
- **Fluxo de administração:** rascunho → simulação → verificação de preços reais/margem → revisão/aprovação por pessoa autorizada → agendamento → publicação → acompanhamento → encerramento; limites e papéis a decidir.
- **Segurança financeira:** alteração de campanha **não altera contratos legados**, cobranças confirmadas ou condições de aceite; nunca reajustar por silenciamento e não usar contador falso.
- **Auditoria:** guardar preço-base de tabela, campanha exata, vigência, preço e condições aceitos, índice/versão, cálculo anual, alterações e aprovações, sem apagar evidência.

## 6. Próximas decisões ainda abertas (sem reabrir o já aprovado)

1. **Escolha do índice contratual anual**, regra de índice negativo/indisponível e comunicação ao cliente.
2. **Marco de contagem de meses promocionais**: desde contratação, ativação ou primeira mensalidade, respeitando contrato e o início da cobrança a partir do dia 90.
3. Definir **preços-base reais por produto** e campanhas concretas; poderão ser geridos no painel, mas só após parametrização aprovada, preço “De” real e validação de margem.
4. Condições e valores posteriores nos planos temporários, reajuste no caso de transição coincidente com aniversário anual, e regras de proteção dos contratos antigos.
5. Se e como o preço promocional é conservado em suspensão/retomada, troca de titularidade ou recontratação.
6. Política financeira, falhas de cobrança, permissões administrativas, teste e homologação antes do uso real.

**Fonte legal:** [Lei nº 10.192/2001, art. 2º — Câmara dos Deputados](https://www2.camara.leg.br/legin/fed/lei/2001/lei-10192-14-fevereiro-2001-365669-normaatualizada-pl.html). CDC arts. 30/31 — [consumidor.gov.br](https://consumidor.gov.br/pages/conteudo/publico/102).

**Resumo:** capacidade de alterar preços de **novas campanhas** no painel e oferecer mensalidade promocional **por N meses ou durante o contrato ativo**, com **reajuste por índice somente após 12 meses de contrato**, **APROVADA** funcionalmente. **Não foi aprovada a edição retroativa de contratos ou qualquer índice, valor, campanha ou operação real.**
