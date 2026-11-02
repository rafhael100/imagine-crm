# PORTAL-COM-04 — Preço de tabela e promoções “De/Por” independentes por serviço e mensalidade

**Status:** **DIRETRIZ FUNCIONAL APROVADA pelo responsável em 02/11/2026:** “cada serviço tenha o seu preço base próprio; a mesma estratégia dos valores de tabela de referência também para a parte da mensalidade, utilizando o De/Por”. **Valores monetários e regras adicionais identificadas como proposta seguem pendentes de decisão expressa.**  
**Escopo:** Portal Imagine; catálogo comercial de produtos e serviços que possuam implantação e/ou mensalidade, incluindo Onepage, Landing Page e futuros serviços compatíveis. Não altera os preços/recursos do CRM Imagine SaaS.  
**Fase:** somente planejamento/documentação; não contratar, programar, criar campanhas reais, cobrar ou publicar preços novos.
**Referências:** [PORTAL-COM-03 — Motor de preços/campanhas](PORTAL-COM-03-motor-precos-promocoes-pix-contadores.md); [PORTAL-COM-02 — estudo de valores](PORTAL-COM-02-proposta-revisao-precos-pix-20-cartao-4x-mensalidade-87.md); [PORTAL-D04 — cobranças recorrentes](PORTAL-D04-cobranca-recorrente-sites.md); [PORTAL-D01 — aceite de orçamento](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md); [PORTAL-01 — matriz](PORTAL-01-matriz-pendencias-plataforma-sites.md).

## 1. Modelo comercial aprovado: dois conjuntos de preço por serviço

Cada serviço/produto do Portal Imagine terá **valores-base independentes** dos demais, em duas dimensões quando aplicável:

1. **Preço de tabela da implantação:** preço normal efetivamente praticável e verificável para aquela implantação, produto, versão e escopo.
2. **Preço de tabela da mensalidade:** preço normal efetivamente praticável e verificável para a assinatura daquele produto, versão e escopo.
3. **Preço promocional da implantação:** por campanha e produto, com apresentação “De [tabela implantação] / Por [implantação promocional]”, se a comparação for legítima.
4. **Preço promocional da mensalidade:** por campanha e produto, com apresentação própria “De [tabela mensal] / Por [mensal promocional]”, se a comparação for legítima.
5. **Incentivo de meio de pagamento da implantação:** **mais 20% de desconto no Pix à vista sobre o preço de implantação escolhido, inclusive o promocional**, conforme COM-03.
6. **Incentivo de meio de pagamento da mensalidade:** **mais 10% no Pix Automático sobre o preço mensal contratado após eventual promoção**, conforme COM-03/D04. Pagamento recorrente por cartão não recebe esse desconto.

**Não presumir que cada produto compartilha um único preço de tabela ou mensalidade, nem que uma campanha reduza necessariamente ambas as dimensões.** Um administrador poderá propor desconto só na implantação, só na mensalidade ou em ambas; as decisões de permissões, política comercial e tipos de campanha serão detalhadas na etapa de especificação.

## 2. Fluxo de cálculo e apresentação

### Implantação

`implantacao_base_produto` → (aplicar campanha elegível ou manter preço-base) → `implantacao_da_campanha` → Pix à vista `×0,80` → `preco_final_implantacao_pix`.

Ao pagar por cartão, o cliente não recebe a camada Pix 20%. Condições e limites de parcelamento da implantação **a ratificar**; não herdar automaticamente regras de serviços adicionais PORTAL-D02.

### Mensalidade

`mensalidade_base_produto` → (aplicar campanha mensal elegível ou manter preço-base) → `mensalidade_contratada_da_campanha` → Pix Automático `×0,90` → `mensalidade_a_cobrar_enquanto_metodo_elegivel`.

Cartão recorrente: cobrança do **valor mensal efetivamente contratado**, sem desconto Pix Automático. Não aplicar 20% da implantação à mensalidade, nem 10% da mensalidade ao valor da implantação.

### Exemplos didáticos, sem aprovar preços reais

| Serviço fictício | Dimensão | Valor “De” ilustrativo | Valor “Por” ilustrativo | Valor final elegível |
|---|---|---:|---:|---:|
| Onepage | Implantação | R$ 2.500,00 | R$ 1.200,00 | **R$ 960,00 no Pix à vista** (20% sobre R$ 1.200) |
| Onepage | Mensalidade | R$ 120,00/mês | R$ 87,00/mês | **R$ 78,30/mês no Pix Automático** (10% sobre R$ 87) |
| Landing Page | Implantação | Preço próprio a decidir | Promoção própria a decidir | 20% sobre a implantação efetivamente ofertada em Pix |
| Landing Page | Mensalidade | Preço próprio a decidir | Promoção própria a decidir | 10% sobre mensalidade ofertada se Pix Automático |

**R$ 2.500 e R$ 120 são apenas números didáticos para demonstrar “De/Por”. R$ 1.200 e R$ 87 também não foram ratificados como preços reais de determinado produto.** Os preços de implantação anteriores (Onepage R$ 990; Landing R$ 1.290) e a mensalidade anterior R$ 79,90 continuam registros históricos de planejamento até decisão expressa de revisão por produto.

## 3. Campanhas independentes de implantação e mensalidade

A criação de uma campanha poderá definir **qual dimensão do preço é atingida**:

- Implantação apenas; mensalidade permanece na tabela, com possível desconto próprio de Pix Automático.
- Mensalidade apenas; implantação permanece na tabela, com seu desconto próprio de Pix à vista.
- Implantação **e** mensalidade, cada uma com “De/Por” e preço promocional independentes.
- Campanhas com **tempo real, quantidade real ou ambos**, expirando ao primeiro evento, conforme COM-03. O fim da campanha impede **novas contratações àquela condição**, mas não deve, por si só, alterar silenciosamente compromissos já aceitos por clientes.

Campanhas em paralelo, acumulação entre promoções, produtos elegíveis, limites mínimos de margem, descontos especiais e prioridade de regras precisam de especificação posterior. **O desconto adicional Pix de 20%/10% incide sobre o preço já resultante da campanha pertinente**.

## 4. Preservação do preço contratado de mensalidade — recomendação para aprovação posterior

Para garantir transparência e viabilidade contratual, **recomenda-se**:
- registrar, no aceite, **mensalidade promocional contratada**, alcance do desconto, data inicial da assinatura, prazo de vigência da condição promocional, reajustes legais/contratuais previstos e condições em caso de troca de método de pagamento;
- **não aumentar a mensalidade de um assinante existente apenas porque a campanha deixou de ser anunciada para novas vendas**; se o preço promocional durar período limitado, informar inequivocamente duração e preço aplicável após esse período antes da contratação;
- manter **10% do Pix Automático vinculado à autorização ativa e pagamento por tal meio**, sem cobrar esse desconto no cartão; eventual mudança de meio deve respeitar termos previamente aceitos, cobrança pendente e aviso ao cliente;
- preservar contratos de clientes antigos, condições individuais pactuadas, histórico e regras legais aplicáveis. Sem alteração unilateral retroativa.

A **estrutura De/Por para a mensalidade foi aprovada**, mas **prazo de garantia do preço mensal, critérios de reajuste e ofertas com mensalidade promocional por N meses ainda não foram aprovados expressamente**; são recomendações de governança contratual a discutir.

## 5. Transparência, prevenção de publicidade enganosa e auditoria

- A mensagem “**De R$ X por R$ Y**” requer um preço normal de referência **verdadeiro, praticável e comprovável para aquele mesmo serviço, escopo e período**; não equiparar estimativa de mercado, “valor percebido”, custo teórico ou preço arbitrário inflado ao preço regular efetivo.
- Caso preço de tabela proposto **ainda não tenha sido efetivamente praticado**, a comunicação pode usar “**preço de lançamento R$ Y**” sem “De R$ X” até haver evidências adequadas e validação jurídica.
- A interface deve distinguir claramente **implantação (pagamento único)** de **mensalidade (recorrente)**, seus preços promocionais, descontos de Pix, condições de contrato, início da recorrência e validade/quantidade reais.
- Armazenar versões de tabela por serviço, campanhas, preços aceitos, prazo de descontos, identidade do contratante, canal e método de pagamento; conservar evidências sem permitir falsificar histórico.
- Promessas de desconto/escassez devem refletir disponibilidade real, não cronômetro fictício ou falta de vagas inventada.
- Lei 8.078/1990 (CDC), arts. 30, 31, 36 e 37: informações/ofertas claras e proibição de publicidade enganosa. Lei 13.455/2017 admite diferenciar preços por meio/prazo de pagamento com exposição clara de descontos. **Verificar os textos e termos com assessoria jurídica antes da publicação.**

**Referências:** https://www.planalto.gov.br/ccivil_03/leis/l8078compilado.htm ; https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2017/lei/l13455.htm ; https://www.gov.br/mj/pt-br/assuntos/arquivos-imprensa/senacon/cartilha-black-friday-senacon-2025

## 6. Decisões futuras identificadas

1. Definir preços-base verídicos de implantação **por serviço** e seus escopos.
2. Definir preços-base verídicos de mensalidade **por serviço** e condições normais da assinatura.
3. Definir valores/política das primeiras campanhas promocionais em cada dimensão, e confirmar preços de implantação R$ 1.200 / assinatura R$ 87 nos serviços a que se aplicarem.
4. Ratificar o prazo de duração contratual da mensalidade promocional, reajustes, relação com mudança de Pix Automático para cartão e eventuais ofertas por tempo limitado.
5. Ratificar 4x para implantação, sem alterar indevidamente serviços adicionais, e conciliar marcos de implantação e recorrência.
6. Definir margens mínimas, alçadas administrativas, limites de quantidade, status de campanha e regras de desconto em outros serviços.
7. Especificar integração, contadores, versão de preço, checkout, faturamento, LGPD e auditoria.

**Conclusão:** **aprovada a arquitetura comercial de tabelas e promoções “De/Por” independentes por serviço para implantação e mensalidade**, com incentivos adicionais Pix distintos já aprovados. **Não aprovados preços concretos, publicação de ofertas, prazos contratuais ou programação.**


## Complemento aprovado COM-05 — administração de campanhas e duas modalidades de mensalidade (02/11/2026)

O responsável definiu que **os preços-base e as condições de campanhas serão gerenciáveis pelo painel administrativo para cada serviço**. A mensalidade promocional de uma campanha poderá ser **por uma quantidade definida de meses** (depois passa para a condição futura **previamente informada e aceita**) **OU por todo o período em que o contrato continuar ativo**. Em ambos os modelos, **reajuste somente anual pelo índice indicado no contrato, depois de 12 meses de contrato**, sem reajustes intermediários por índice. O fim de campanha para novos clientes não altera o contrato já vigente. O desconto de 10% Pix Automático aplica-se sobre a mensalidade contratada elegível, inclusive promocional; os 20% Pix à vista da implantação permanecem separados. **Índice específico, valores por produto, marcos de contagem dos N meses e políticas de reajuste técnico/contratual não estão definidos.** Veja [COM-05 — Gestão administrativa e reajuste anual](PORTAL-COM-05-gestao-administrativa-precos-campanhas-reajuste-anual.md). Nenhuma programação/cobrança autorizada.
