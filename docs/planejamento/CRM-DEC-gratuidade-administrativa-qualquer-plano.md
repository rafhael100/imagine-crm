# CRM-DEC — Concessão administrativa de qualquer plano CRM sem mensalidade

**Data:** 2026-11-03. **Status da decisão central:** **REQUISITO FUNCIONAL APROVADO EXPLICITAMENTE PELO RESPONSÁVEL**.
**Manifestação:** “Algo importante no CRM e que eu como adminitrador possa habilitar de forma gratuita um determinado em cliente em qualquer plano que eu desejar”.
**Interpretação contextual:** administrador **geral da plataforma Imagine**, e não administrador de uma empresa cliente, poderá escolher **uma empresa/organização cliente específica** e habilitar gratuitamente **qualquer plano público do CRM Imagine**. A aprovação foi dada juntamente com a [CRM-REV-07 como base documental](CRM-DEC-aprovacao-REV-07-base.md).
**Limite:** os itens marcados “proposta” abaixo são salvaguardas para análise, **não prazos, alçadas ou parâmetros já aprovados**.

## 1. Direito funcional aprovado

- O **Administrador Geral da Imagine** poderá selecionar uma empresa cliente e conceder **gratuidade integral da mensalidade do plano escolhido**: Básico, Essencial, Profissional ou Empresarial.
- A empresa terá as **funcionalidades, franquias e usuários incluídos no plano escolhido**, sujeitas às regras normais de segurança, limites técnicos e à definição final da matriz de benefícios. **Gratuito não significa acesso irrestrito a funções fora do plano**.
- A concessão administrativa **não exige contratar site Imagine, não consome trial, não depende da cortesia Básico e não muda a tabela pública de preços**. Não criar um quinto plano artificial denominado “Grátis”.
- A concessão é **individual para a empresa/organização escolhida**, não estende automaticamente o direito a todas as outras organizações do mesmo CNPJ, a outros sites ou clientes, nem a planos adicionais de terceiros.
- Enquanto a gratuidade estiver válida, **não cobrar a mensalidade base concedida**. Não emitir uma cobrança de valor integral para então considerá-la “inadimplente” por não pagar; registrar a isenção/benefício como condição comercial efetiva.
- Somente administradores gerais autorizados da **Imagine** poderão gerir essa concessão. O administrador da empresa beneficiária **não** pode autoconcedê-la, transferi-la a outro tenant nem alterar a própria alçada.

## 2. Distinção de três regimes

| Regime | Quem habilita | Condição | Escopo de plano | Cobrança |
|---|---|---|---|---|
| Assinatura comum | Cliente e mecanismos comerciais autorizados | Contratação e pagamento conforme ciclo | Plano contratado | Mensalidade comercial aplicável |
| **Cortesia Básico vinculada a Site** | Integração Portal/Sites por contrato elegível e regras IN-119/122 | Site/campanha elegível | **Básico** | Não cobrar mensalidade CRM Básico do benefício |
| **Gratuidade administrativa Imagine — NOVA, APROVADA** | **Administrador Geral Imagine autorizado** | Concessão manual à organização escolhida | **Qualquer plano**, inclusive superior | **Mensalidade base do plano isenta enquanto concessão válida** |

O cancelamento/inadimplência do site **não revoga automaticamente** uma gratuidade administrativa independente, nem a concessão transforma benefício Básico em crédito cumulativo. Se coexistirem fontes de direito, a precedência exata e o plano efetivo precisam ser documentados antes da implementação.

## 3. Salvaguardas propostas para revisão — não pressupostas como decisões já aprovadas

1. **Ação no painel central:** escolher empresa por identificador seguro, exibir plano atual e situação de assinatura, selecionar plano do benefício, pré-visualizar o efeito e confirmar. Não oferecer controle de concessão no painel da empresa.
2. **Segurança:** MFA obrigatório ao Administrador Geral Imagine, autenticação recente/step-up para alteração comercial sensível conforme política real, permissão explícita, confirmação de impacto e auditoria imutável.
3. **Rastreabilidade:** registrar administrador, empresa, plano anterior/novo, motivo interno, início, eventual término, tipo de concessão, versões/alterações e o impacto financeiro previsto. **A definição do campo motivo e da política de justificativa ainda precisa de aprovação técnica/comercial.**
4. **Prazo e vigência — APROVADO EM DECISÃO POSTERIOR:** o responsável aprovou **ambas as modalidades**, por **prazo determinado** (data final definida) ou **prazo indeterminado** (sem data final pré-definida, até encerramento autorizado), conforme [CRM-DEC — Modalidades de vigência](CRM-DEC-gratuidade-vigencia-modalidades.md). **Seis meses é apenas exemplo**, não prazo padrão ou máximo. Continuam pendentes aviso, início de vigência, prorrogação, alçadas e transição; **nunca iniciar cobrança automaticamente** após término.
5. **Cliente já pagante:** se concedido a uma assinatura em andamento, tratar mês pago, crédito, estorno e data de início de isenção pelo contrato de FIN/CON; **não cancelar cobrança capturada nem reembolsar automaticamente** sem decisão.
6. **Fim da gratuidade:** informar antes, permitir contratação expressa do plano escolhido ou seleção de outra alternativa elegível. **Não converter automaticamente gratuidade em mensalidade recorrente cobrada** nem usar falha de pagamento inexistente como inadimplência; preservação de dados e quotas de downgrade conforme políticas vigentes.
7. **Adicionais e uso variável:** a isenção aprovada se refere à **mensalidade base do plano**. **Ainda falta decidir** se inclui usuários extras, armazenamento adicional, créditos de IA, números de WhatsApp, custos de provedores, consumo variável e outros adicionais. **Não presumir cobertura** desses itens.
8. **Conflitos de benefícios:** distinguir benefício administrativo, promoção, Pix Automático, cortesia Básico por site, trial e plano pago; definir se são cumulativos ou não, o plano efetivo e a política para mudança sem perda de dados. **Não criar gratuidade acumulável como saldo ou reembolso.**
9. **Reversão segura e falhas:** concessão/alteração/revogação idempotentes por organização, confirmação oficial de benefício e faturamento, rastreio de solicitações conflitantes, sem mudar contrato/site/tenant indevidamente.
10. **Relatórios e governança:** painel central com concessões ativas/encerradas, plano concedido, responsabilidade, data, justificativa e visibilidade financeira controlada, sem exposição ao público de políticas promocionais internas.

Esses itens **orientam revisão de produto/segurança/financeiro** e não alteram autonomamente direitos, alçadas ou prazos.

## 4. Casos documentais para futura aceitação — nenhum executado

| Cenário | Comportamento a preservar |
|---|---|
| Administrador Imagine concede Empresarial a uma empresa sem Site | Entitlement Empresarial concedido à organização indicada, **mensalidade base isenta**, sem trial obrigatório |
| Cliente do plano Essencial pago recebe concessão Profissional | Efeito em assinatura/vigência determinado por FIN/CON e confirmado, sem faturamento duplicado ou estorno presumido |
| Administrador da empresa tenta habilitar plano gratuito | Acesso negado, sem poder de alterar concessão central |
| Empresa com várias organizações recebe cortesia em apenas uma | Outras organizações preservam seus contratos, planos e contas próprios |
| Site que sustentava Básico é suspenso, mas há concessão administrativa independente | Preservar benefício administrativo conforme vigência, sem suspensão por vínculo site irrelevante |
| Concessão revogada ou expirada | **Não iniciar cobrança recorrente sem aceite expresso do cliente**; aplicar transição e preservação de dados a definir |
| Plano Empresarial grátis tenta consumir extra de WhatsApp/IA sem direito definido | Não presumir que gratuidade cubra adicional ou custo variável |
| Repetição de evento administrativo | Não criar duas isenções ou alterar registros financeiros retroativamente |

## 5. Decisão complementar aprovada: modalidade da gratuidade

A [CRM-DEC de vigência](CRM-DEC-gratuidade-vigencia-modalidades.md) registra o **aceite contextual explícito** de duas opções — gratuidade por prazo determinado e por prazo indeterminado. Ambas mantêm a regra de **não ativar mensalidade recorrente sem nova contratação expressa**. Não há prazo padrão fixado nem decisão sobre extras, retroatividade ou aprovação adicional.

## 6. Pendências específicas abertas antes de implementar

| ID | Pergunta / decisão ainda necessária | Competência proposta |
|---|---|---|
| GRAT-P01 — **PARCIAL** | **Ambas modalidades aprovadas**: prazo determinado e indeterminado. Ainda decidir data inicial, padrão, eventual prazo máximo, renovação e avisos. | Comercial / Administração Geral |
| GRAT-P02 | Quais administradores gerais podem conceder, alterar, renovar e revogar? Exigir segunda aprovação ou apenas MFA/ação auditada? | Segurança / Administração |
| GRAT-P03 | É possível iniciar imediatamente ou agendar concessão futura? Como tratar ciclo já pago? | FIN/CON |
| GRAT-P04 — **PARCIAL** | **Vedada cobrança automática** após expiração/encerramento. Faltam avisos, transição operacional, direitos e prazos para contratação expressa ou portabilidade. | Comercial / FIN/CON / LGPD |
| GRAT-P05 | Quais extras ou custos variáveis estão cobertos ou excluídos do benefício? | Comercial / Financeiro / donos de produtos |
| GRAT-P06 | Qual precedência entre plano pago, trial, cortesia Básico do site, promoção e gratuidade administrativa? | Planos / FIN/CON / Portal |
| GRAT-P07 | Como distinguir desconto 100% sobre mensalidade, benefício manual e registro fiscal/contábil sem criar fatura cobrável? | FIN/CON / Fiscal |
| GRAT-P08 | Quais limites para uso interno, relatórios, justificativas, auditoria, antiabuso e alteração de tenant/mesmo CNPJ? | Administração Geral / Segurança / Auditoria |

## 6. Relações e controle de planejamento

- [CRM-REV-02 — Planos, Trial, Assinaturas e Cobranças](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md): **nova modalidade aprovada** de concessão de mensalidade sem valor devido.
- [CRM-REV-01 — Administração e Segurança](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md): ato reservado ao Administrador Geral Imagine e permissões separadas do Admin da Empresa.
- [IN-119 — Cortesia Básico por Site](IN-119-gestao-cortesia-crm-basico.md): **benefício distinto**, não alterar suas regras atuais de 30 dias ou site elegível.
- [CRM-REV-07 — Agenda](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md): gratuidade de plano superior habilita recursos **do plano efetivamente concedido**, não converte o Básico em agenda pública.
- [CRM-MASTER-01](CRM-MASTER-01-indice-modulos-fontes-status.md): a decisão deve integrar checklist final de planos/financeiro/segurança.

**Decisão central aprovada.** Fluxos, parâmetros, interfaces e exceções financeiras listados acima estão **propostos/pendentes**, não aprovados implicitamente. **Não iniciar programação** antes do planejamento global fechado, versionado e aprovado.
