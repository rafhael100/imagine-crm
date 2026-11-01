# 07 — Integração de sites Imagine com CRM Básico cortesia

> **Decisão IN-002 aprovada (2026-11-01).** Planejamento funcional e contratual; nenhuma integração está implementada. O documento é uma extensão dos planos e regras do CRM Imagine, sincronizada com o projeto `rafhael100/imagine-portal`.

## 1. Diretriz comercial aprovada

- Cliente que contrata **Site Onepage** (ou futura **Landing Page**) Imagine recebe **acesso gratuito ao pacote Básico do CRM Imagine**, condicionado ao vínculo do contrato do site. O CRM **não emite assinatura ou cobrança do Básico** ao beneficiário.
- O cliente paga somente a assinatura do site no **Portal Imagine**. Para o Onepage atual: implantação **R$ 990** (R$ 330 em D0/D30/D60) e mensalidade de **R$ 79,90**, primeiro vencimento em D90. Os valores e condições da Landing Page ainda serão definidos no projeto de origem.
- Cliente que desejar um **plano mais avançado** faz upgrade e contrata diretamente pelo **CRM Imagine**. Nessa hipótese existem **duas mensalidades**: site no Portal Imagine e plano pago do CRM no CRM Imagine. Não deslocar, abater ou cancelar automaticamente a assinatura do site.
- A adesão ao plano pago deve ser confirmada expressamente no CRM. Não converter automaticamente o benefício em assinatura paga nem cobrar um plano de CRM sem aceite.
- É **benefício condicionado à contratação**, não plano gratuito aberto ao público nem extensão do **trial de 14 dias**. Mantém-se o conceito aprovado dos planos **Essencial, Profissional e Empresarial** e a regra de ausência de plano gratuito público no lançamento, com a **exceção específica** de beneficiários do site.
- **IN-003 aprovada em 2026-11-01:** o CRM Básico cortesia será uma **versão mais enxuta que o Essencial**, exclusiva a contratantes elegíveis de Onepage ou Landing Page.
- **IN-004 aprovada em 2026-11-01:** o Básico cortesia **inclui Agenda e Agendamentos**. **IN-007:** uso de agenda e agendamentos no Básico fica **restrito à criação/gestão manual e interna por usuário autorizado**; visitante não agenda pelo Onepage/Landing Page nem por página pública CRM. Agendamento público e criação automática pelo visitante são exclusivos de **planos pagos**, mediante verificação dos direitos. **IN-008 e IN-009 aprovadas:** o Básico terá **1 profissional com agenda própria e até 10 serviços ativos por organização**, sem atribuir automaticamente 1 licença/assento de CRM; regras de horários aprovadas (IN-011) e agendamentos manuais **sem teto comercial mensal**, sujeitos a uso justo (IN-012), em [Agenda Básico](11-agenda-manual-basico-cortesia.md). Escopo avançado e quotas manuais pendentes. **IN-005 aprovada em 2026-11-01:** Básico Cortesia oferece **até 3 automações comerciais ativas por organização, somente a partir de modelos prontos**. Catálogo e detalhes de agenda continuam em revisão; [inventário e opções](09-revisao-agenda-e-automacoes-basico.md). **Não é o Essencial gratuito**, nem renomeia os planos pagos. A matriz sugerida de funcionalidades e cotas, ainda não aprovada, consta em [08 — Plano Básico cortesia](08-plano-basico-cortesia.md).

## 2. Integração comercial dos dois sistemas

| Evento | Portal Imagine | CRM Imagine |
| --- | --- | --- |
| Pagamento inicial confirmado de site elegível | Valida contrato e dispara evento idempotente de benefício | Provisiona/vincula organização e direito ao Básico sem cobrança |
| Site e CRM Básico em uso | Cobra somente mensalidade do site segundo contrato | Disponibiliza recursos Básico sem mensalidade CRM |
| Cliente faz upgrade no CRM | **Não modifica a assinatura do site** | Apresenta termos e preço do CRM pago; cobra somente após consentimento, libera recursos após pagamento |
| CRM pago termina, site ainda elegível | Mantém assinatura do site | Propõe-se retorno ao Básico conforme ciclo e limites, preservando os dados; detalhes pendentes |
| Site termina, CRM pago permanece vigente | Interrompe elegibilidade do benefício segundo futura regra contratual | **Não cancela automaticamente** assinatura CRM paga e não apaga os dados |
| Site tem inadimplência, estorno ou contestação | Informa estado financeiro conciliado, sem mudar contrato CRM diretamente | Aplica política de benefício somente após regra aprovada, sem excluir dados indevidamente |

A regra existente de **upgrade self-service, pagamento proporcional ao restante do ciclo, liberação após pagamento, downgrade no ciclo seguinte e regularização de excedentes** continua valendo para assinaturas pagas CRM. Não presumir que a contratação do site consome o trial do CRM ou que um usuário pode receber benefícios em todas as suas organizações.

## 3. Vínculo por empresa — SaaS multiempresa

- O mesmo usuário pode participar de várias organizações/empresas. O benefício vincula **pedido de site → organização CRM confirmada/autorizada**, e não apenas CPF/e-mail, para evitar benefícios em tenants não contratantes.
- Se a organização ainda não existe, o CRM a cria com verificação de titularidade; se existe, vincula após autorização. A identidade do usuário não precisa ser duplicada.
- O direito gratuito é modelado como `entitlement`/concessão de benefício com origem e validade próprias, **não como transação paga fictícia de R$ 0** nem assinatura financeira.
- Quando há plano CRM pago ativo, ele define o conjunto efetivo de recursos. O entitlement gratuito pode permanecer como fallback, sem somar quotas arbitrariamente.
- O mesmo tenant com múltiplos contratos de site não ganha contas ou direitos duplicados sem regra.
- Serviços independentes, bancos separados e API servidor-servidor autenticada. Requisições, filas, jobs e integrações aplicam **tenant_id/RLS** e verificação de permissão em cada etapa.

## 4. Formulários Onepage / Landing Page

- Formulários de captura de leads dos sites publicados usam **ingestão pública protegida**, com `form_public_id` sem segredos e vinculação no backend do Portal/serviço de integração à empresa CRM correta.
- Uma fila/outbox garante armazenamento durável e entrega idempotente a endpoint privado do CRM; indisponibilidade não implica perda silenciosa de envios já aceitos.
- CRM registra lead, página/site, formulário, campanhas/UTMs e autorizações pertinentes. Respeitar permissões, duplicidade de leads, campos personalizados, limites do plano, políticas LGPD e antispam já planejados para formulários públicos CRM.
- Não expor token de integração CRM no frontend nem aceitar `tenant_id` informado pelo visitante.
- O botão WhatsApp continua disponível, independentemente do formulário.

## 5. Estados técnicos sugeridos (AINDA NÃO APROVADOS)

Entitlement de benefício: `pending_provision`, `eligible`, `suspended`, `expired`, `revoked`. Plano CRM pago: mecanismo financeiro próprio, contrato independente e estados de assinatura já previstos. A precedência deve evitar múltiplas cobranças ou regressões de permissão.

Campos sugeridos: `source_product=imagine_site`, `source_order_id`, `crm_org_id`, `entitlement_type=site_bundle_basic`, `entitlement_version`, `created_at`, `effective_at`, `eligibility_status`, `last_reconciled_at`; índices únicos e auditoria. Nomes físicos ainda dependem do modelo de dados aprovado.

## 6. Pendências específicas

1. Definir o conjunto exato de funcionalidades, limites e quantidade de usuários do **Básico cortesia**, distinguindo-o do **trial de 14 dias** e dos planos Essencial, Profissional e Empresarial.
2. Definir quando começa o benefício e como é elegível durante D0→D90, inadimplência/suspensão, rescisão, disputa/estorno e eventual recontratação, com avisos e prazos.
3. Definir regras de downgrade/retorno do plano CRM pago ao Básico: fim do período pago, excedentes, limites, retenção e exportação de dados, sem exclusão surpresa.
4. Definir preço e modelo comercial da Landing Page, evento de elegibilidade e relação com o Onepage atual.
5. Definir APIs, fila, autenticação de serviço, reconciliação entre as plataformas, idempotência, observabilidade e fluxo de suporte.
6. Definir comunicação da oferta, tratamento de dados e autorizações contratuais, inclusive caso a cortesia não esteja disponível até o lançamento do CRM.

## 7. Critérios mínimos de aceite

1. Contratação elegível gera um único entitlement Básico; cobrança do CRM permanece **zero** enquanto só há benefício.
2. Upgrade pago resulta em **duas cobranças em sistemas separados**, ambas legitimamente autorizadas; não duplica a fatura do site.
3. Cliente com mais de um tenant acessa apenas o benefício do tenant vinculado e autorizado.
4. Cancelar site não cancela silenciosamente assinatura CRM paga.
5. Cancelamento/downgrade do CRM pago preserva dados e respeita ciclo e política do benefício do site.
6. Formulário do cliente A não pode criar lead no tenant B; envio duplicado não gera dois eventos técnicos.
7. Ausência de serviço CRM ou falha financeira não cria sucesso fictício de cobrança/ativação.
8. Trial público e preços normais do CRM continuam separados do benefício site; usuários sem site não recebem gratuidade permanente.

**Situação:** regra comercial IN-002 aprovada e documentada. Não alterar `main` nem iniciar programação do CRM enquanto planejamento e revisão não forem concluídos.
