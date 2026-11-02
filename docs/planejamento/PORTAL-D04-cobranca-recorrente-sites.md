# PORTAL-D04 — Cobranças Recorrentes dos Sites Imagine

**Status:** **PROPOSTA PARA DELIBERAÇÃO — modalidades de recorrência AINDA NÃO APROVADAS** (02/11/2026).  
**Origem:** pergunta do responsável "E nos pagamentos recorrentes?" após decidir que **serviços adicionais só parcelam por cartão de crédito** ([PORTAL-D02](PORTAL-D02-pagamento-servicos-adicionais.md)).
**Escopo:** mensalidades recorrentes de **Onepage e Landing Page** contratados pelo **Portal Imagine**, não parcelamento de orçamento de adicional, nem mensalidade/upgrade do CRM Imagine.
**Fase:** planejamento/documentação exclusivamente. Não iniciar implementação, contratar instituição/PSP, emitir cobrança ou ativar débito real.
**Dependências:** [PORTAL-01 — matriz de pendências](PORTAL-01-matriz-pendencias-plataforma-sites.md); IN-091/092 (pagamentos do site e direito a CRM Básico); IN-119/120/121 (cortesia, período de transição e notificações); [PORTAL-D01 — aceite e provas](PORTAL-D01-aceite-eletronico-versoes-orcamentos.md).

## 1. Decisões anteriores que não devem ser reabertas

- Assinatura mensal do site: valor comercial de referência de planejamento **R$ 79,90/mês**, iniciando no **90º dia** conforme regras comerciais já aprovadas; definição final de preços e marcos contratuais passa pela revisão global.
- Portal gerencia **contrato e pagamentos do site**; assinatura CRM paga/upgrade pertence ao **CRM** e não sofre encerramento automático porque o site atrasou/cancelou.
- Cortesia CRM Básico associada à elegibilidade do(s) site(s), respeitando as políticas já aprovadas de transição de **30 dias** e reativação/suspensão, e sem cobrança automática por benefício expirado.
- Parcelamento dos **serviços adicionais** acima de R$ 270 em até 3x sem juros ocorre **exclusivamente por cartão** (decisão mais recente da PORTAL-D02). Não confundir parcelamento de compra com mensalidade recorrente.

## 2. Proposta de meios de pagamento para mensalidades de site

| Meio | Proposta | Premissas e cuidados |
|---|---|---|
| **Pix Automático** | **Modalidade preferencial proposta** | Cliente fornece **autorização no banco** para recorrência; Portal/PSP envia cobranças conforme periodicidade contratada. Não é empréstimo nem parcelamento. Cada mensalidade é recebida quando efetivamente liquidada. Provedor/integração e tarifas precisam ser avaliados. |
| **Cartão de crédito recorrente** | **Alternativa principal proposta** | Cliente autoriza cobrança periódica, normalmente uma transação por competência; cartão não fica comprometido por um parcelamento de múltiplos meses. Precisa tokenização segura, conciliação, política de falha/atualização e não armazenar PAN/CVV no Portal. |
| **Pix comum (QR dinâmico/copia e cola)** | **Alternativa manual e recuperação de falhas** | Cada competência terá cobrança identificável, valor correto, vencimento e pagamento confirmado; não criar recorrência automática sem consentimento. |
| **Boleto** | **Alternativa opcional em avaliação** | Disponibilidade e tarifas segundo provedor e custo operacional; política final ainda a deliberar. |

**Pix Automático vs Pix parcelado bancário:** o Pix Automático é uma **autorização de cobranças recorrentes por contrato**, não operação de crédito ou financiamento. O cliente pode alterar ou cancelar a autorização segundo regras do serviço. Se a autorização for cancelada, **não significa automaticamente cancelamento da obrigação contratual ou do site**; Portal deve propor outra forma de pagar, respeitando regras contratuais e direitos do consumidor.

**Pix comum financiado pelo banco:** se o cliente tiver crédito bancário, a instituição pode financiar um Pix individual por conta própria, com taxas/juros informados por ela. A Imagine **não comercializará isso como modalidade "Pix parcelado" do Portal**.

## 3. Fluxo funcional sugerido (ainda não aprovado)

1. Cliente contrata Onepage/Landing Page no Portal e **aceita expressamente contrato, preço, data de início e periodicidade**.
2. Portal oferece escolha transparente: **Pix Automático** como sugestão de conveniência, **cartão recorrente** como alternativa, e meios manuais de contingência se aprovados.
3. A autorização do Pix Automático é concedida pelo cliente **no ambiente da instituição financeira ou fluxo homologado**, com permissões de valor, periodicidade, validade e limites. **Aceite do contrato do site não substitui a autorização bancária.**
4. Mensalidade só entra no ciclo **na data contratual prevista (referência atual: 90º dia)**; não cobrar mensalidade imediatamente sem fundamento contratual.
5. A cada ciclo, criar identificador imutável e registro **idempotente**, apresentar vencimento, conciliar confirmação do pagamento e **não duplicar cobranças** em trocas de Pix/cartão/manual.
6. Em falha de cobrança, registrar estado verdadeiro, informar o cliente e possibilitar pagamento manual seguro ou atualização do método; aplicar notificações e tolerância/suspensão **apenas conforme política contratual aprovada**, não criar nova carência aqui.
7. Cobrança e contrato do site seguem separados do **CRM pago**. Mudança de elegibilidade do CRM Básico depende do estado contratual efetivo e das IN-119–121, com cuidado para não suspender benefício por mera falha de webhook ou atraso de confirmação bancária.
8. Cancelamento da autorização de Pix Automático, substituição de cartão, cancelamento contratual do site e reativação são **eventos distintos**, com confirmação, auditoria e políticas próprias.

## 4. Incentivos comerciais e riscos

- A diretriz geral de incentivo ao Pix à vista foi manifestada no planejamento da PORTAL-D02; eventual **incentivo para Pix Automático em mensalidades recorrentes** requer **aprovação separada de percentuais, margem, elegibilidade, continuidade da oferta e transparência**.
- Não declarar Pix Automático grátis para empresa: o **Banco Central permite tarifa para o recebedor**. Consultar custo de bancos/PSPs e comparar com cartão, retries, chargeback, custo de boletos e taxas de falhas.
- A disponibilidade do Pix Automático para recebedor e integração via PSP depende da instituição, certificação/implementação e condições contratuais. **Sem fornecedor ou custo já aprovado.**
- Pix Automático só funciona com autorização válida e condições de saldo/limites; **não prometer liquidação automática garantida**.
- Evitar operar dois métodos de cobrança simultâneos no mesmo ciclo. Mudança de método exige reconciliação e prevenção de dupla cobrança.
- Proteção de dados, identidade, trilha de autorização, tratamento de contestação, links seguros, fraude e reembolso ficam sujeitos à especificação técnica/jurídica.

## 5. Pendências posteriores

- **Decisão principal desta pauta:** aprovar ou ajustar a oferta de **Pix Automático + cartão recorrente** para mensalidades dos sites e **Pix manual como alternativa**, com boleto opcional a deliberar.
- Datas/vencimentos (90º dia; dia fixo/mês; feriados e limites), cobrança inicial, comunicação antes do vencimento.
- Falhas de renovação, janelas de retentativa, troca de forma de pagamento, cancelamento do mandato Pix, autorização de cartão, conciliação de múltiplos meios, inadimplência e retomada.
- Preços/reajustes, possíveis descontos para Pix Automático, tarifas, planos de PSP, NFS-e e obrigações fiscais; revisão financeira geral ao final do projeto.
- Fluxo jurídico de aceite do contrato e autorização bancária separada, notificação, guarda de evidências e LGPD.

## 6. Fontes oficiais e referência temporal

Consultadas em 02/11/2026:
- Banco Central — Pix Automático: https://www.bcb.gov.br/estabilidadefinanceira/pix-automatico
- Banco Central — perguntas frequentes sobre pagamento recorrente e Pix Agendado: https://www.bcb.gov.br/meubc/faqs/s/pix
- Banco Central — Pix Automático para recebedores: https://www.bcb.gov.br/estabilidadefinanceira/pix-automatico-recebedores
- Banco Central — regulamentação de crédito durante iniciação de Pix: https://www.bcb.gov.br/estabilidadefinanceira/exibenormativo?numero=603&tipo=Resolu%C3%A7%C3%A3o+BCB

**Conclusão: a escolha do responsável sobre cartão como único parcelamento dos adicionais já foi registrada na PORTAL-D02. Os meios de pagamento recorrente desta PORTAL-D04 são somente PROPOSTA, aguardando decisão expressa. Não iniciar programação nem cobrança.**
