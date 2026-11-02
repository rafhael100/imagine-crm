# PORTAL-COM-10 — Reativação de site após cancelamento: janela de 60 dias e taxa

**Status (02/11/2026): DIRETRIZES FUNCIONAIS APROVADAS:** o responsável respondeu **“sim e cobrar uma taxa de reativação”** à pergunta expressa sobre **reativar site cancelado com janela padrão de 60 dias, configurável pelo administrador, cobrando uma taxa de reativação**. **Ainda não definidos/aprovados:** valor, modo de parametrizar por serviço, descontos/meios de pagamento da taxa, condições de contratos novos/legados, retenção técnica efetiva de backups, garantia de restauração ou prazo de cancelamento formal de site por inadimplência.
**Etapa:** somente planejamento/documentação; sem programação, publicação, cobrança, alterações operacionais ou recuperação real.
**Referências:** [PORTAL-COM-08 — atraso e suspensão](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md), [PORTAL-COM-09 — cortesia após cancelamento](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md), [PORTAL-D04 — cobrança](PORTAL-D04-cobranca-recorrente-sites.md), [PORTAL-01 — matriz](PORTAL-01-matriz-pendencias-plataforma-sites.md), IN-100/101 (domínios e backups) em docs/decisoes.md no ramo docs/integracao-site-crm-basico; [IN-119](IN-119-gestao-cortesia-crm-basico.md), [IN-120](IN-120-encerramento-cortesia-continuidade-conta.md), [IN-121](IN-121-notificacoes-reativacao-automatica-cortesia.md), [IN-122](IN-122-multiplos-sites-contas-crm-cortesias.md).

## 1. Objetivo e escopo da aprovação

Permitir ao cliente solicitar a **reativação do site mesmo após o cancelamento formal** e, se tecnicamente possível, reaproveitar versões, conteúdo, estrutura, dados de configuração e domínio legítimo. Distinguir **suspensão de contrato ainda ativo** (normalmente regularização/restabelecimento, sem nova implantação) de **cancelamento contratual definitivo** (nova contratação/termo específico, com tentativa de recuperação do projeto antigo, se retido).

**A política de cancelamento do site após inadimplência total de 30 dias foi apenas proposta ao usuário e AINDA NÃO aprovada.** O prazo de 7 dias de tolerância para suspensão já foi aprovado. Não promover automaticamente os 30 dias a decisão vigente.

## 2. Fluxo funcional: reativação aprovada, detalhes operacionais a homologar

1. **Site suspenso sem cancelamento:** após conciliar pagamento e validar conta, reativar o mesmo site e benefício CRM cortesia conforme IN-119–122; sem taxa adicional de implantação sugerida quando não há nova produção.
2. **Site formalmente cancelado e recuperável:** cliente entra no Portal, escolhe “Solicitar reativação”, valida titularidade, confirma domínio e ativos disponíveis, analisa pendências antigas (quitação ou acordo documentado segundo política aprovada), recebe simulação financeira e aprova expressamente **nova contratação/termo de reativação**. A equipe restaura projeto tecnicamente recuperável, verifica DNS, SSL, formulários, envio de leads ao CRM correto e integridade antes de republicar; sem criar tenant indevido.
3. **Site cancelado sem backup utilizável ou com domínio indisponível:** não prometer restauração. Oferecer orçamento transparente para reconstrução/nova implantação e obter aceite. Não substituir direito de domínio de terceiro nem oferecer transferência não autorizada.

### Preços e condições

**Regra comercial APROVADA:** **cobrar uma taxa de reativação quando houver reativação de site JÁ CANCELADO**, no âmbito da janela comercial aprovada e se tecnicamente recuperável, com valor e condições **exibidos e aceitos expressamente antes da cobrança e do serviço**. **Valor exato e modelo administrativo da taxa ainda pendentes.** Como proposta de detalhamento, permitir parametrizar valor por serviço/campanha e autorização comercial; essa possibilidade adicional não deve ser tratada como aprovada automaticamente. **A taxa de reativação é diferente de nova implantação, mensalidades e dívidas antigas**; não cobrar outra implantação quando a reativação não exigiu reconstrução, salvo condição expressa acordada posteriormente. Quando o site não for recuperável, preparar **novo orçamento de implantação**, sem automaticamente cumular taxa de reativação e implantação. Custos extraordinários devem ser informados e aceitos. Não cobrar taxa oculta nem presumir desconto Pix 20% aplicável a ela.

**Mensalidade e promoção:** após **cancelamento formal**, não presumir restabelecimento automático de preço promocional vitalício, condições de carência, cortesia ou prazo do contrato anterior. Ofertar as condições do **novo contrato** (tabela e campanha vigente, ou condição específica de recuperação que o administrador autorizar) e colher novo aceite. **Não alterar retroativamente contratos ainda ativos**. Se o usuário desejar manter promoções antigas em janela de recuperação, precisará aprovar essa exceção expressamente.

**Débitos:** o cancelamento e a reativação não significam perdão automático da dívida antiga, nem cobrança automática indevida; ofertar quitação ou negociação explícita antes da reativação conforme política contratual. Não presumir que dívida contestada pode bloquear direitos de dados.

## 3. Janela comercial de 60 dias — APROVADA; retenção técnica ainda pendente

Aprovada em IN-101 a necessidade de **backups independentes de sites**, com verificações de integridade, recuperação segura, histórico de versões e respeito a domínios. **PRAZO DE RETENÇÃO DOS BACKUPS DE SITES NÃO FOI APROVADO**, e não deve ser confundido com os **120 dias de retenção após CANCELAMENTO FORMAL DE CONTA CRM** da IN-120.

**Decisão APROVADA:** manter **janela padrão comercial de 60 dias corridos desde o cancelamento formal do site**, **configurável pelo administrador**, para que o cliente solicite **reativação sujeita à disponibilidade efetiva de backup e ativos necessários**. **Não é promessa de disponibilidade técnica garantida**. O período real de retenção técnica, frequência, armazenamento, exclusão/anonimização, testes e custos dos backups de sites precisam ser dimensionados e aprovados à parte (IN-101). O sistema deverá validar os arquivos antes de oferecer restauração e informar claramente limitações. O prazo de 60 dias **não equivale aos 120 dias de retenção do CRM**.

**Após janela/sem backup:** nova produção, com proposta comercial específica, sujeita a direitos de dados, contratualidade e limites de retenção.

## 4. Conciliação com CRM Básico cortesia e planos pagos

1. Se o **novo contrato do site for elegível à cortesia** e a conta CRM correta estiver válida, reavaliar elegibilidade e reativar o Básico conforme IN-119–122. Se o contrato/campanha nova **não conceder cortesia**, oferecer CRM pago somente por aceite expresso.
2. Durante os **30 dias de transição do CRM** após cancelamento do último site, observar o tipo aprovado: **cancelamento comum** → CRM funciona normalmente; **cancelamento por inadimplência após suspensão** → CRM permanece **restrito à exportação/portabilidade e contratação de plano pago** (COM-09). Uma reativação legítima de vínculo elegível suspende o cronograma de expiração após validar contratos/vínculos.
3. **Outro site ativo elegível** preserva a cortesia da mesma conta mesmo que este continue cancelado; **assinaturas CRM pagas independentes** continuam operando.
4. **120 dias após cancelamento formal de conta CRM** são política própria do CRM e não são promessa de retenção/reversibilidade do site, domínio ou banco de dados. Não apagar/levar em conta dados de CRM como se fossem parte do backup do site. Não gerar cobranças ou múltiplas cortesias automaticamente.

## 5. Interface sugerida — sujeita à aprovação

- Cliente: projeto cancelado → **Solicitar reativação** → mostrar o que é recuperável e o que precisa de nova contratação, preço/mensalidade/campanha ou orçamento, eventuais débitos e encargos declarados, domínio, prazo e aceite → acompanhamento da análise e ativação.
- Admin: estado/razão/data do cancelamento; disponibilidade do backup validada; titularidade e domínio; custo/margem; definição de preço, taxa e termos; conciliação de débito e titularidade; aprovação; recuperação segura; integração CRM; auditoria e comunicação.
- NUNCA reativar automaticamente serviço cancelado por um pagamento tardio que não possua contrato válido ou aceite de reativação. Tratar depósito inesperado sem contratar automaticamente, com reembolso/apropriação conforme política legal.

## 6. Perguntas ainda abertas

1. **APROVADO:** permitir reativação de site cancelado sob solicitação/aceite específico, condicionada à possibilidade técnica de recuperação.
2. **APROVADO:** janela comercial padrão de **60 dias** contados do cancelamento formal, configurável pelo administrador; **PENDENTE:** garantir política técnica compatível de retenção/backup e delimitar ajustes por produto.
3. Aprovar se um contrato reativado pode manter o preço e as promoções anteriores ou **sempre adota o novo preço**, exceto exceção administrativa expressa.
4. **APROVADO:** cobrar **taxa de reativação** para reabrir site já cancelado; **PENDENTE:** definir valor e se será fixo, variável por serviço, por campanha ou mediante orçamento excepcional, meios de pagamento, descontos e condições de cobrança. Aceite explícito indispensável.
5. Definir **retenção e política técnica de backups de sites** (IN-101), aviso de exclusão, domínio e custos.
6. Fechar prazo exato de **cancelamento do site por inadimplência** (30 dias total foi PROPOSTO, não aprovado), marcos de suspensão, reativação e reversões.
7. Definir coordenação com suporte/IA, dias de restauração e atualização de DNS, SSL, banco e destino de leads.

**Diretrizes APROVADAS:** possibilidade de solicitar reativação de site formalmente cancelado, **janela comercial padrão de 60 dias configurável administrativamente** e **cobrança de taxa de reativação**. **Não aprovados:** valor da taxa, retenção técnica garantida, reuso automático de promoções antigas, cancelamento automático do site após 30 dias de inadimplência e detalhes contratuais/de pagamento. Sem programação, cobrança ou reativação reais.


## 7. Aprovação posterior: janela e taxa — discriminação de cobranças (02/11/2026)

O responsável aprovou expressamente **sim** para a janela de 60 dias administrável, e exigiu **“cobrar uma taxa de reativação”**. A aplicação será para site **formalmente CANCELADO**, não meramente **SUSPENSO** em período de tolerância. O cliente deverá ver claramente a disponibilidade da recuperação, a **taxa única de reativação a ser definida**, eventuais mensalidades futuras com seu próprio vencimento/campanha, eventuais débitos antigos ou propostas de acordo e outras despesas necessárias, antes de aceitar.

**Não somar automaticamente a taxa de reativação a uma nova taxa integral de implantação quando o projeto não puder ser restaurado**: nesse caso, informar que a recuperação não é possível e fornecer **orçamento de nova implantação**, ainda sujeito a deliberação comercial. Taxa de reativação não equivale à mensalidade, nem é perdão de débitos vencidos; o Pix adicional de 20% destinado à implantação e o desconto de 10% da mensalidade por Pix Automático **não se estendem automaticamente à taxa**, sem decisão específica.

**Antes de publicar política de 60 dias**, alinhar a oferta com retenção real, custos e segurança dos backups do site (IN-101). **O prazo é comercial, e não garantia de recuperação técnica ainda não homologada**. Preservar domínio próprio do cliente, e-mail existente e integrações legítimas sem assumir renovação ou titularidade indevida (IN-100). O CRM cortesia segue as condições do novo contrato aceito e da IN-119–122, inclusive transição e reativação previstas na COM-09.

**Ainda é preciso decidir** preço exato e administração da taxa, contratos de reativação, se existe exceção de preço promocional anterior, política de cancelamento formal por inadimplência e retenção técnica de backups. Não iniciar programação.
