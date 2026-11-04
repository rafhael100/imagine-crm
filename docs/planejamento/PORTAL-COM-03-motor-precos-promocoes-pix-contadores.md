# PORTAL-COM-03 — Motor de preços, promoções e contadores de ofertas do Portal Imagine

> **ATENÇÃO — POLÍTICA COMERCIAL POSTERIOR (03/11/2026):** os percentuais históricos de **20% para Pix à vista de implantação** e **10% para Pix Automático** foram substituídos, nas matérias correspondentes, por **5% no Pix à vista** e **5% no Pix Automático**. Cartão de implantação: até **3x** sem desconto Pix; Pix parcelado não é ofertado. Ver [registro vigente](PORTAL-COM-POLITICA-VIGENTE-PIX-05-CARTAO-3X-2026-11-03.md). Preservam-se os fundamentos do motor de preços e campanhas, sujeitos à revisão de cumulatividade e preços finais. Conteúdo abaixo é histórico e não pode ser lido isoladamente como política atual.

**Status (02/11/2026): DIRETRIZES FUNCIONAIS APROVADAS pelo responsável para planejamento: (a) preço de tabela configurável por produto, (b) campanhas promocionais configuráveis, (c) DESCONTO ADICIONAL DE 20% PARA PIX À VISTA NA IMPLANTAÇÃO, INDEPENDENTEMENTE DA PROMOÇÃO, (d) DESCONTO DE 10% EM MENSALIDADES COM PIX AUTOMÁTICO, (e) campanhas com limite REAL de quantidade, prazo ou ambos, vencendo ao ocorrer o PRIMEIRO LIMITE.**  
**Não aprovados:** R$ 2.500 como preço definitivo por produto, descontos/promoções específicos de R$ 1.200 ou R$ 600 para lançamento real, número de vagas, data final, margem mínima, regra financeira de adicional, ativação de oferta ou programação.  
**Fase:** somente planejamento/documentação; sem código, publicação de valores, contratos, pagamentos ou consumo de serviços.  
**Relacionamentos:** [PORTAL-COM-02 — comparação anterior](PORTAL-COM-02-proposta-revisao-precos-pix-20-cartao-4x-mensalidade-87.md), [PORTAL-01 — matriz de pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md), [PORTAL-D02 — serviços extras](PORTAL-D02-pagamento-servicos-adicionais.md), [PORTAL-D04 — cobrança recorrente dos sites](PORTAL-D04-cobranca-recorrente-sites.md), [PORTAL-D01 — aceite da oferta](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md).

## 1. Objetivo e natureza das decisões

O responsável quer **desvincular o valor real do produto da promoção vigente**. Cada produto pode possuir um **preço de tabela legítimo e comprovável**, e o painel administrativo permite **criar campanhas** com preços promocionais ou descontos próprios. O **incentivo por Pix aplica-se adicionalmente** sobre o preço que restar após a promoção. O objetivo é divulgar valor comercial transparente e vender a condições acessíveis, sem desconto fictício.

**Exemplo apresentado pelo responsável:** implantação de referência de **R$ 2.500,00** (mencionou faixa estimada de R$ 2.500–3.000 para valor de referência), promoções de R$ 1.200 ou até R$ 600, e **mais 20% de desconto na escolha de Pix à vista**. **Este é exemplo comercial**; a escolha do preço de tabela efetivo para Onepage, Landing Page e outros produtos continua pendente. Não substituir sem confirmação os preços anteriores de planejamento (implantação Onepage R$ 990; Landing Page R$ 1.290).

**Mensalidade:** a regra de **10% de desconto para Pix Automático** é confirmada como estratégia funcional. Se a proposta anterior de mensalidade **R$ 87,00** for aprovada para o produto, o cliente pagará **R$ 78,30** com Pix Automático e **R$ 87,00** no cartão recorrente. O preço-base mensal por produto e a alteração da referência histórica R$ 79,90 ainda precisam de ratificação por produto e de revisão comercial/contratual.

## 2. Preço e ordem de cálculo — regras aprovadas

1. Administrador Imagine cadastra por produto/variante: **preço-base de tabela**, versões, condições válidas e, quando aplicável, a assinatura/mensalidade (separada da implantação).
2. Campanha elegível ativa define **preço promocional fixo** (ex.: R$ 1.200) ou um **desconto promocional percentual/valor** em relação ao preço-base, conforme configuração previamente aprovada por perfil autorizado.
3. **Preço promocional é determinado primeiro**. Se o cliente escolher **Pix à vista na implantação**, o Portal calcula **20% de desconto ADICIONAL** sobre o valor promocional final; se não houver promoção, aplica o mesmo adicional sobre o preço de tabela vigente. **O desconto de Pix não desaparece porque já existe promoção.**
4. **Cartão de crédito:** não recebe o desconto de 20% por Pix. A hipótese de aumentar para **até 4 vezes sem juros na implantação** permanece registrada como **proposta sob validação**, distinta dos **serviços adicionais** da PORTAL-D02, que continuam com **até 3 vezes sem juros pelo cartão** para orçamentos acima de R$ 270.
5. **Pix Automático nas mensalidades recorrentes dos sites:** descontar **10%** da mensalidade efetivamente contratada elegível, sem acumular por engano com 20% de implantação; cartão recorrente sem o desconto do Pix Automático. O mecanismo de cobrança recorrente, autorização bancária, descontos de manutenção e marco de vigência exigem finalização da PORTAL-D04.
6. Preço, forma de pagamento, descontos, vigência e total devem constar do **pedido e aceite eletrônico**, com cálculo no servidor, precisão monetária centesimal e trilha de auditoria. Não alterar preço contratado retroativamente nem cobrar preço não aceito.
7. Se houver várias promoções compatíveis, a política de elegibilidade/precedência precisa ser definida; **não acumular promoções entre si automaticamente**. A **camada de 20% do Pix** é, contudo, adicional obrigatório para os casos de implantação elegíveis, mesmo sobre uma promoção.

### Exemplos aritméticos autorizados como simulações — NÃO campanhas reais

| Preço de tabela ilustrativo | Promoção ilustrativa | Cartão, sem 20% Pix | Pix à vista (mais 20%) | Desconto efetivo total vs. R$ 2.500 |
|---:|---:|---:|---:|---:|
| R$ 2.500,00 | R$ 1.200,00 | R$ 1.200,00 | **R$ 960,00** | **61,6%** |
| R$ 2.500,00 | R$ 600,00 | R$ 600,00 | **R$ 480,00** | **80,8%** |
| R$ 2.500,00 | Sem promoção | R$ 2.500,00 | **R$ 2.000,00** | **20%** |

Fórmulas de negócio: `preco_em_campanha = preco_promocional_ativo || preco_de_tabela`; `total_implantacao_pix = arredondar_2(preco_em_campanha * 0.80)`; `mensalidade_pix_automatico = arredondar_2(mensalidade_base_contratada * 0.90)`. **Fórmulas documentais, não código em produção**.

## 3. Motor de campanhas e gerenciamento — conceito aprovado, opções detalhadas sujeitas a aprovação

**Capacidades de alto nível solicitadas/aprovadas:** a administração Imagine deve poder cadastrar campanhas **por tempo**, **por quantidade de ofertas/vagas**, ou **pelas duas condições simultaneamente**, com opção de promoções de Black Friday e campanhas especiais; preços e descontos variados por produto. O contador é visualização dos dados reais do servidor, **não simulação artificial**.

**Campos sugeridos para especificação futura:**
- ID, nome, categoria/natureza da promoção e descrição pública;
- produto(s)/variante(s) elegíveis, preço-base vigente versionado;
- preço promocional fixo ou desconto por percentual/valor, limites de autorização e eventual custo/margem;
- data/hora inicial e final **com fuso horário explícito**, ou total real de vagas/contratos promocionais, ou ambos;
- status (rascunho, revisão, aprovada, agendada, ativa, esgotada, expirada, suspensa, encerrada);
- responsável autorizado, canal/página, visibilidade, regras de elegibilidade e histórico de mudanças;
- regra de precedência entre campanhas (a validar), confirmação de pagamento, cancelamento, vagas e política de reserva;
- rótulos públicos opcionais e exibição de valor final Pix com desconto adicional.
- limiar interno de **margem de contribuição mínima**, alerta de redução e revisão/aprovação humana para campanhas agressivas — **valor do limiar ainda não aprovado**.

## 4. Contadores reais — especificação funcional

### 4.1 Tempo

- Se a campanha tiver **prazo final real**, mostrar contagem regressiva até esse marco, calculada no **servidor**, com fuso e data/hora exibidos em linguagem clara.
- Ao alcançar o fim, **a promoção deixa de estar disponível para novos pedidos**; não reiniciar o relógio silenciosamente, nem fingir que a oferta terminou quando permanece idêntica por tempo indefinido.
- Uma prorrogação ou nova campanha precisa de decisão e registro administrativos; comunicar mudanças em linguagem que não induza o cliente a erro.

### 4.2 Quantidade

- Se a campanha limitar a um número real de contratações ou capacidade real de produção/preço promocional, o sistema mantém **cota autorizada, consumida e restante**, com conciliação de pagamentos e pedidos.
- Exibir somente o número verificável de **ofertas disponíveis**; não anunciar quantidade fictícia ou contagem de "pessoas comprando" não verificável.
- Reservas temporárias de checkout, se adotadas, precisam de **prazo previamente aprovado**, capacidade limitada, transação atômica e liberação rastreável se o pedido não for concluído, sem contagem inflada nem vendas acima da quantidade.
- Definir tecnicamente se o marco que consome cota é **pedido confirmado, aceite mais pagamento ou reserva protegida**; assegurar que a promessa feita ao comprador durante checkout seja honrada conforme contrato e lei.

### 4.3 Tempo E quantidade — vence o primeiro

**Diretriz expressa do responsável:** ativar ambos os limites na mesma campanha, encerrando-a **assim que o prazo expirar OU a quantidade real se esgotar — o que ocorrer primeiro**. O preço deve parar de ser ofertado a novos clientes quando ocorrer o primeiro evento, respeitados pedidos já legitimamente aceitos e reservas válidas segundo condições contratuais.

Exemplo **sem campanha ativa**: total ilustrativo de 30 ofertas até uma data final configurada; se consumir 30 antes do prazo, termina por quantidade; se vencer a data antes de consumir 30, termina por tempo. Não anunciar data real ou "faltam X" sem dados correspondentes.

## 5. Interface sugerida ao cliente

**Preço exibido por etapas:**
- preço-base **legítimo e auditável** de tabela (quando puder ser representado como referência verdadeira);
- preço da promoção e economia correspondente;
- forma de pagamento disponível (cartão ou Pix à vista);
- desconto adicional de 20% Pix, aplicado a partir do preço promocional;
- total final, mensalidade recorrente independente e eventuais condições de desconto Pix Automático em separado;
- condições da campanha, quantidade real restante, prazo real e significado do contador.

**Mensagem sugerida e NÃO uma promoção real:** "Preço de tabela R$ 2.500 | Oferta especial R$ 1.200 | Mais 20% no Pix à vista: R$ 960". Somente exibir como "**De R$ 2.500 por R$ 1.200**" se **R$ 2.500 for efetivamente um preço normal verdadeiro, acessível e comprovável**, e não apenas estimativa de mercado/valor percebido criada para parecer desconto.

## 6. Controles antiabuso, transparência e finanças

- **Preço de referência real:** diferenciar **valor percebido/estimativa de mercado** de **preço regular efetivamente praticado**. Não publicar desconto comparativo fictício, inflar antes da Black Friday ou exibir "de X" sem lastro verificável.
- **Escassez verdadeira:** nenhum contador falso, estoque inventado, renovação automática disfarçada da oferta ou timer que reinicia ao atualizar página. Somente usar limite de vagas quando houver limite real de preço/capacidade.
- **Conferência financeira prévia:** desconto promocional + Pix 20% pode produzir preço final baixo (ex.: R$ 480), com possível margem negativa; recomenda-se **simular custo e estabelecer regra de aprovação antes de ativar** campanha. O desconto Pix 20% deve estar incluído no cálculo dessa conferência, e não suprimido em checkout sem informar o cliente.
- **Não cobrar retroativamente:** condições promocionais aceitas devem ser congeladas no pedido e historizadas; política de reserva, alteração/cancelamento/ressarcimento e limites de elegibilidade deverão ser especificados.
- **Pagamento:** a programação futura deve conciliar o recebimento real e manter assinatura/implantação separadas; parcela de cartão não deve receber desconto Pix; modalidade Pix financiado por banco é assunto independente, não oferta de parcelamento da Imagine.
- **Auditoria:** guardar versão de tabela, campanha, início/fim, cota real, critério que a encerrou, cálculos, aprovador, pedidos aceitos, mudanças e evidências de comunicação com a retenção legal/contratual pertinente.
- **Direitos do consumidor:** realizar revisão jurídica/contratual de promoções, arrependimento, oferta, transparência, preços e publicidade antes de lançamento.

**Fundamentação pública consultada:**
- Código de Defesa do Consumidor (Lei 8.078/1990), publicidade enganosa, ofertas e orçamento: https://www.planalto.gov.br/ccivil_03/leis/l8078compilado.htm
- Decreto 7.962/2013, informações da contratação eletrônica: https://www.planalto.gov.br/ccivil_03/_ato2011-2014/2013/decreto/d7962.htm
- Lei 13.455/2017, diferenciação por meio/prazo de pagamento: https://www.planalto.gov.br/ccivil_03/_ato2015-2018/2017/lei/l13455.htm
- Senacon, orientação sobre ofertas por tempo limitado e ofertas falsas: https://www.gov.br/mj/pt-br/assuntos/noticias/secretaria-nacional-do-consumidor-orienta-sobre-o-risco-das-compras-impulsivas-durante-a-black-friday

## 7. Limites da aprovação e próximo fechamento comercial

**Aprovado como direção funcional em 02/11/2026:** motor de preço-base + campanha configurável + **Pix à vista 20% adicional MESMO sobre campanha** + **Pix Automático 10% sobre mensalidade** + limites de campanha por tempo, quantidade ou ambos, terminando no primeiro evento real.

**Pendente para deliberação específica antes do lançamento:**
1. **Cada serviço terá preço-base próprio de implantação e de mensalidade** (decisão posterior aprovada). Falta definir os valores efetivos por produto, inclusive se R$ 2.500 será preço regular real de algum produto ou apenas referência de mercado, e as promoções individuais.
2. Confirmar a mensalidade-base **R$ 87** por produto e o prazo de início (preservar regra do 90º dia até decisão contrária).
3. Ratificar **4x no cartão** para implantação e marcos de recebimento/produção; não alterar o limite **3x** dos serviços adicionais sem aprovação específica.
4. Como distinguir e aprovar promoções de **R$ 1.200 / R$ 600**, seus escopos, duração, cota, elegibilidade e limite de margem; **nenhuma delas está ativa**.
5. Parâmetros de reserva/contagem, precedência de campanhas, validade de propostas aceitas, descontos em serviços adicionais, regras para clientes legados, forma de cobrança e políticas de retorno após encerramento.

**Não iniciar programação. Nenhuma campanha, preço público ou desconto em cobrança foi ativado.**


## 8. Complemento aprovado — De/Por separado por serviço e mensalidade (02/11/2026)

O responsável decidiu que **cada serviço terá seu próprio preço-base de tabela**, e que o motor de campanhas **De/Por** se aplicará também **à mensalidade recorrente**, e não somente à implantação. Assim, cada produto/variante terá **duas dimensões independentes** de preço e promoção, quando aplicáveis: (1) implantação: tabela → promoção → **mais 20% de Pix à vista**; e (2) mensalidade: tabela mensal → promoção mensal → **mais 10% por Pix Automático**. A campanha pode afetar só implantação, só mensalidade ou ambos conforme configuração. Preços e escopos de Onepage e Landing Page não são automaticamente iguais. Documento detalhado e aprovado como diretriz: [PORTAL-COM-04 — Tabelas e promoções independentes](PORTAL-COM-04-precos-individuais-implantacao-mensalidade-de-por.md).

**Valores como R$ 2.500, R$ 1.200, R$ 120 e R$ 87 permanecem exemplos/hipóteses até aprovação de preços para cada produto**, mesmo após aprovação da arquitetura De/Por. **A exibição pública de “De” exige preço regular comprovável, não referência artificial ou estimativa de mercado.** A duração da condição mensal promocional para contratos já celebrados deve ser fechada e comunicada previamente, não alterada apenas pelo fim do contador da campanha.
