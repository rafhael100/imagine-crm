# PORTAL-COM-08 — Inadimplência do site, bloqueio da cortesia CRM e elegibilidade administrável

**Status:** **DIRETRIZES FUNCIONAIS APROVADAS em 02/11/2026** conforme solicitação expressa do responsável: (a) seguir para o Portal Imagine a referência do CRM Imagine de **7 dias corridos de tolerância antes da suspensão por inadimplência**; (b) **na suspensão do site por falta de pagamento, suspender também o acesso operacional do CRM Básico concedido como cortesia, caso o benefício dependa do site suspenso**; (c) **tornar administrável oferecer ou não o CRM Básico como cortesia em cada serviço e campanha**. **Detalhes operacionais e jurídicos não definidos abaixo ficam pendentes.**

**Fase:** exclusivamente planejamento/documentação, sem iniciar programação, cobrança real, publicação comercial ou alterações em contratos vigentes.
**Escopo:** Portal Imagine (sites Onepage, Landing Page e futuros), benefício de **CRM Básico cortesia**. **Não alterar autonomamente planos pagos ou o SaaS CRM Imagine.**
**Referências:** [PORTAL-D04 — mensalidades/inadimplência](PORTAL-D04-cobranca-recorrente-sites.md), [PORTAL-COM-07 — vencimentos](PORTAL-COM-07-vencimentos-recorrentes-configuraveis.md), [PORTAL-COM-03 — campanhas](PORTAL-COM-03-motor-precos-promocoes-pix-contadores.md), [PORTAL-COM-04 — produtos e De/Por](PORTAL-COM-04-precos-individuais-implantacao-mensalidade-de-por.md), [IN-119 — cortesia e 30 dias de transição](IN-119-gestao-cortesia-crm-basico.md), [IN-120 — continuidade e dados](IN-120-encerramento-cortesia-continuidade-conta.md), [IN-121 — notificações e reativação](IN-121-notificacoes-reativacao-automatica-cortesia.md), [IN-122 — múltiplos sites](IN-122-multiplos-sites-contas-crm-cortesias.md), [IN-123 — painel](IN-123-painel-integracoes-sites-crm.md), [PORTAL-01 — matriz](PORTAL-01-matriz-pendencias-plataforma-sites.md).

## 1. Regra da inadimplência de site — aprovada

- **Referência comercial:** aplicar ao Portal Imagine **7 dias corridos de tolerância para suspensão, alinhados ao CRM Imagine** (política geral SaaS: 7 dias de tolerância antes da suspensão e 30 dias até cancelamento, sem misturar as assinaturas ou os efeitos do cancelamento).
- Respeitar o **prazo sem juros/multa até o próximo dia útil bancário** quando a cobrança devida em sábado, domingo ou feriado falhar, conforme PORTAL-COM-07.
- **Contagem operacional proposta para consolidar:** os sete dias são contados a partir da **data-limite de regularização sem encargos efetivamente aplicável** ao ciclo. Em vencimento útil, normalmente começa após esse vencimento; em falha de dia não útil, após o próximo dia útil concedido. Isso impede cobrar prazo de tolerância enquanto o cliente ainda possui direito à regularização sem penalidade; submeter o marco exato e política legal à homologação final, **sem criar tolerância adicional arbitrária**.
- No período de sete dias, o site não é suspenso por essa pendência; registrar estado pendente e enviar avisos de cobrança. **Falta definir cronograma/canais, notificação pré-suspensão, taxas e política de cancelamento**.
- Encerrado o prazo, **verificar pagamento conciliado, contrato, elegibilidade e notificações**, e somente então registrar suspensão contratualmente válida. **Não suspender por falha transitória de webhook, comprovante pendente de conciliação ou processamento bancário em atraso.**
- A suspensão não exclui site nem dados. **A eventual política de cancelamento do site após prazo maior permanece pendente**; não importar automaticamente os 30 dias do CRM como prazo de cancelamento do site.

## 2. Suspensão sincronizada da cortesia CRM — aprovada

**Regra específica aprovada agora:** quando o site é efetivamente **suspenso por inadimplência** e sua elegibilidade é o **único fundamento ativo da cortesia do CRM Básico em uma conta vinculada**, **suspender também o acesso operacional do CRM Básico daquela conta**, sem cobrança automática do plano básico e sem exclusão de dados.

A integração deve distinguir rigorosamente:
1. **CRM Básico cortesia**, sustentado **somente pelo site inadimplente suspenso** → **acesso operacional suspenso simultaneamente**, com conta preservada; informar causa e forma de regularizar, manter caminhos seguros de consulta financeira/reativação e **portabilidade/exportação de dados** conforme IN-118/IN-120. Definir o nível exato de acesso restrito antes de implementar.
2. **CRM Básico cortesia com outro site ativo e elegível vinculado** → **não suspender a conta CRM**, pois outra contratação mantém a elegibilidade da mesma organização (IN-119 e IN-122).
3. **CRM Básico contratado separadamente e pago, ou plano Essencial/Profissional/Empresarial com assinatura própria válida** → **não suspender CRM** por atraso do site; preservar autonomia de contratos, cobrança e funcionalidades do CRM pago.
4. **Site contratado em campanha sem cortesia** → não existe benefício de cortesia a bloquear nesse contrato; integrações e eventuais assinaturas CRM pagas seguem suas próprias políticas.
5. **Site regularizado/reativado** → reavaliar automaticamente se a conta possui cortesia vinculada e restaurar o acesso operacional após verificar pagamento conciliado, situação contratual, titularidade e ausência de outros bloqueios legítimos. Recuperação auditável/idempotente, sem créditos, duplicação de cortesia ou cobrança automática.
6. **Bloqueio de site não equivale a cancelamento de CRM nem exclusão de dados**. Preservar dados, isolamento, histórico, segurança e obrigações de portabilidade.

### Importante: distinguir suspensão por inadimplência e transição pelo cancelamento do último site

**IN-119, IN-120 e IN-121 já aprovaram**: no **cancelamento do último site elegível**, a cortesia entra em **transição de 30 dias**, durante a qual o CRM continua operacional e oferece migração para assinatura paga expressamente aceita, seguido de suspensão com acesso restrito se não houver nova elegibilidade nem contratação. Esses 30 dias **não foram revogados expressamente** pela decisão atual de suspensão sincronizada por inadimplência.

**Interpretação operacional a conciliar antes do desenvolvimento:** **suspensão temporária por inadimplência do site** é uma condição distinta de **cancelamento/encerramento efetivo do último vínculo de cortesia**. O CRM pode ser temporariamente bloqueado durante inadimplência, sem dizer que o prazo de 30 dias já começou ou terminou. Se houver cancelamento formal do último site, aplicar a política de transição existente conforme contrato/regras a detalhar; **definir como o estado suspenso por dívida migra para o estado de transição sem criar acesso acidental ou perda de dados**. O usuário não autorizou revogar a transição de 30 dias nesta etapa. **Pendência explícita** de consolidação jurídica/fluxo de estados.

## 3. Cortesia administrável por serviço e campanha — aprovada

O administrador Imagine poderá configurar para **novas ofertas**, sem programação, se determinado serviço/variante/campanha **concede ou não o CRM Básico como cortesia**:
- **Por produto/serviço:** escolha da política-padrão de elegibilidade de cortesia: habilitado/desabilitado, sujeito a revisão administrativa com registro de versão. **Não presumir que Onepage/Landing atualmente elegíveis percam benefício já contratado**.
- **Por campanha:** possibilidade de **herdar a configuração do produto**, **oferecer cortesia** ou **não oferecer cortesia**, mesmo quando outro atributo da campanha reduza preço de implantação ou mensalidade. A escolha fica **clara antes da contratação e no contrato/aceite**, sem esconder benefício e sem alteração retroativa.
- **Caso com cortesia:** conceder **a mesma assinatura pública de CRM Básico** como benefício sem mensalidade adicional, conforme IN-119/IN-122, se a conta vinculada tiver elegibilidade efetiva. **Uma cortesia por organização CRM**, ainda que vários sites elegíveis a sustentem; várias contas só se cada uma tiver um site elegível.
- **Caso sem cortesia:** o cliente poderá contratar CRM Básico pago ou outro plano conforme catálogo e aceites próprios do CRM; site e formulários poderão ser vinculados ao CRM existente conforme permissões. **Não faturar CRM automaticamente** por ausência da cortesia.
- **Clientes legados:** alteração da configuração do produto/campanha não exclui benefício de contrato vigente. Troca do CRM vinculado continua gratuita por chamado, sem migração automática de dados, conforme IN-122/123.
- Guardar **versão e origem da elegibilidade** (produto, campanha, contrato, conta CRM, site), datas de início e fim, suspensão/reativação, motivo, log e responsável, com revisão automática antes de qualquer bloqueio.

### Campos sugeridos para a interface (detalhamento ainda pendente)

**Produto:** `crm_basico_cortesia_padrao = sim|nao`. **Campanha:** `crm_basico_cortesia = herdar|sim|nao`. **Oferta/contrato:** valor final decidido e imutável daquela adesão, a exibir no checkout. **Assinatura:** vínculo de quais sites ativos elegíveis sustentam a conta CRM, sem duplicação.

**Não confundir concessão de cortesia para novas vendas com um comando de bloquear manualmente todo CRM**. Alçadas/permissões de alterar promoções, concessões excepcionais, fraudes e casos de suporte serão homologadas separadamente.

## 4. Proposta de fluxo operacional para detalhar na próxima etapa

- Vencimento → tentar cobrança na data original permitida → falha em não útil ganha direito de regularização até próximo dia útil sem encargos → notificar pendência → tolerância comercial de **7 dias corridos** → conciliação/aviso prévio → suspensão efetiva do site se ainda inadimplente → reavaliar, **por conta CRM**, todos os contratos sustentadores → suspensão **somente de cortesia exclusiva do(s) site(s) suspenso(s)** → notificação rastreada → regularização e reativação segura.
- Integração assíncrona idempotente, com fonte de verdade do Portal para contrato do site e elegibilidade concedida, e CRM para plano/assinatura paga e status operacional. Eventos devem ser verificáveis, sem permitir bloquear conta errada ou degradar a modalidade paga.
- Evitar suspender site por falha do processador e bloquear CRM antes de confirmar legitimamente inadimplência. Registrar motivo, prazo, comprovantes e reversões.
- Dados do CRM e site preservados; **não iniciar automaticamente o período geral de retenção de 120 dias** apenas por suspensão (IN-120).

## 5. Pendências que continuam abertas

- Confirmar **marco contratual definitivo** de contagem dos 7 dias em caso de vencimento não útil e o calendário da notificação prévia; a escolha de 7 dias e o vínculo de suspensão foram aprovados.
- Tratamento exato de **juros/multa** após janela de dia útil e política geral de cobrança; custo/impedimento legal de suspensão, prazos de aviso, reativação, eventuais suspensões parciais.
- **Conciliação da transição 30 dias (IN-119–121) com suspensão por inadimplência e posterior cancelamento formal**.
- Direitos de backup/exportação na conta bloqueada, fluxo de cliente que deseja pagar o CRM mesmo com site suspenso, e impacto dos sites múltiplos por organização.
- Publicar o catálogo administrativo de produtos e campanhas, campos de elegibilidade, aprovadores, margens e regras de publicidade, com aceite e versionamento.
- Regras para suspensão/cancelamento dos sites após os 7 dias; prazo de cancelamento do **CRM SaaS pago (30 dias)** não deve ser copiado sem aprovação para o Portal.

**Condição final:** documentação de decisões para o Portal, sem desenvolver ou alterar contratos reais.


## 6. Decisão posterior aprovada — COM-09 (02/11/2026)

O responsável respondeu **“sim”** à distinção proposta: **se site suspenso por inadimplência for posteriormente cancelado e for o último vínculo elegível**, a **transição de 30 dias da cortesia CRM começa no cancelamento formal, porém o CRM permanece com acesso operacional bloqueado**; o titular conserva **acesso autenticado e restrito para exportação/baixar dados e contratar plano CRM pago expressamente**, sem cobrança nem exclusão automática. **Cancelamentos comuns** continuam com **30 dias de CRM normalmente operacional**, nos termos IN-119–121. Se outro site elegível ativo sustentar a cortesia, não bloquear; se houver CRM pago, não prejudicá-lo. A contagem dos 30 dias **não se confunde com sete dias de tolerância nem com a data da suspensão anterior**. O calendário IN-121 de avisos permanece, com textos adaptados ao tipo de transição; dados preservados e retenção de 120 dias somente após cancelamento formal do CRM. **Detalhes de quando cancelar o site suspenso, reversões e operação técnica ainda pendentes.** Regra detalhada em [PORTAL-COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md). Sem programação/cobrança.
