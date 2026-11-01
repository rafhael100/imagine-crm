# 07 — Integração de sites Imagine com CRM Básico cortesia

> **Decisão IN-002 aprovada (2026-11-01).** Planejamento funcional e contratual; nenhuma integração está implementada. O documento é uma extensão dos planos e regras do CRM Imagine, sincronizada com o projeto `rafhael100/imagine-portal`.

### IN-032 — Avisos em 80%/95% e bloqueio apenas de novos anexos a 100% (APROVADA)

**Aprovada em 2026-11-01:** nos quatro planos CRM, exibir utilização e saldo da franquia de arquivos/anexos, com avisos **internos no CRM em 80% e 95%**. A **100% do espaço**, bloquear **somente novos uploads de anexos**, sem excluir ou negar acesso autorizado aos arquivos existentes e sem interromper geração/recepção de **leads por formulário Imagine**, contatos, agendamentos, oportunidades ou demais dados estruturados. Tentativas de upload acima do saldo não podem ser confirmadas como aceitas. Respeitar quota por organização, segurança e separação entre Portal Imagine e CRM; avisos internos do CRM **não criam mensagens externas, campanhas, ou cobranças automáticas**. Armazenamento adicional e preços, arquivos temporários e política de retenção seguem pendentes. [Matriz IN-032](13-matriz-permissoes-basico-cortesia.md).

### IN-031 — Franquias de arquivos/anexos dos quatro planos CRM (APROVADA)

**Aprovada em 2026-11-01:** as franquias totais **por empresa e somente para arquivos/anexos** passam a ser **Básico Cortesia 1 GB, Essencial 5 GB, Profissional 20 GB, Empresarial 50 GB**. **Dados estruturados ficam fora da franquia em todos os planos**, não apenas no Básico (IN-029). Quotas anteriores dos pagos 1/5/10 GB foram substituídas. Esgotamento da franquia de anexos não afeta recebimento de leads sem anexos nem permite descartar arquivos aceitos. O limite global anterior de 5 MB por arquivo e o mesmo catálogo de tipos (IN-030) permanecem. Sem mudança automática de plano ou cobrança; adicionais, avisos e arquivos temporários ainda pendentes. O Portal Imagine não passa a possuir acesso aos anexos CRM. [Matriz IN-031](13-matriz-permissoes-basico-cortesia.md).

### IN-030 — Tipos de anexos iguais entre CRM Básico e planos pagos (APROVADA)

**Aprovação em 2026-11-01:** o Administrador da Empresa poderá anexar a leads/contatos no CRM Básico os **mesmos tipos de imagens, PDFs e documentos comuns permitidos nos planos pagos**, com lista de formatos seguros comum, verificação de arquivos, armazenamento privado, controle de acesso, histórico e auditoria. Limite global por arquivo **5 MB**, já aprovado para o CRM geral. **A IN-031 aprovou posteriormente franquias de arquivos/anexos para os quatro planos: 1/5/20/50 GB, excluindo dados estruturados em todos os planos**, substituindo os limites pagos anteriores de 1/5/10 GB. A IN-029 mantém dados estruturados fora da franquia. **O Portal Imagine não recebe acesso automático aos arquivos anexados ao CRM** pela oferta Onepage; capturas e documentos do site seguem autorização específica. [Matriz IN-030](13-matriz-permissoes-basico-cortesia.md).

### IN-029 — Dados estruturados fora da franquia comercial fixa de arquivos (APROVADA)

**IN-029 aprovada em 2026-11-01:** no CRM Básico Cortesia, dados cadastrais/textuais de leads, contatos e seus históricos **não consomem a franquia comercial fixa destinada a anexos/arquivos**; a quantidade de leads e contatos continua sem teto comercial rígido (IN-024). O formulário Imagine pode receber leads legítimos mesmo quando a futura franquia de anexos estiver esgotada; não descartar capturas confirmadas. **Não presumir** que anexos nos contatos ou uploads permanentes foram incluídos, nem quotas/tamanhos. Arquivos temporários de importação/exportação e retenção serão especificados separadamente. A integração do Portal não obtém novo acesso a arquivos dos clientes. [Matriz IN-029](13-matriz-permissoes-basico-cortesia.md).

### IN-028 — Mesclagem manual completa e controlada no CRM Básico (APROVADA)

O Administrador da Empresa poderá **mesclar manualmente leads e contatos duplicados**, com **escolha do registro principal, revisão dos conflitos campo a campo e confirmação explícita**, sem perda de vínculos com agendamentos/oportunidades, origens do site, observações, históricos ou auditoria. **Mesclagem automática não é permitida**, inclusive no recebimento de formulários ou na importação CSV/XLSX. O **Portal Imagine não poderá iniciar mesclagem em nome do cliente** apenas por manter o site ou a integração; o fluxo deve ser autenticado, autorizado e restrito à empresa no CRM. Detalhes de referência histórica e tratamento de consentimentos estão pendentes. [Matriz IN-028](13-matriz-permissoes-basico-cortesia.md).

### IN-027 — Exclusão definitiva controlada de leads e contatos dentro do CRM (APROVADA)

O Administrador da Empresa do CRM Básico poderá **excluir definitivamente cadastros elegíveis, com confirmação explícita**. Cadastros ligados a agendamentos, oportunidades, históricos ou obrigações legais exigem **procedimento protegido**, com preservação da integridade dos vínculos, LGPD e auditoria; sem eliminação automática em cascata. O **Portal Imagine não ganha permissão de exclusão dos registros de CRM** somente por operar o Onepage ou a integração dos formulários, e não deve confundir encerramento do site com instrução de apagar a base CRM. **IN-028 aprovou posteriormente a mesclagem manual controlada; critérios técnicos de exclusão/mesclagem, anonimização e retenção ainda precisam ser definidos.** [Matriz IN-027](13-matriz-permissoes-basico-cortesia.md).

### IN-026 — Exportação CSV/XLSX de leads e contatos pelo cliente CRM (APROVADA)

O Administrador da Empresa pode **exportar os leads e contatos de sua organização em CSV e XLSX**, incluindo todos os registros autorizados ou só os filtrados, com isolamento tenant, auditoria, autenticação e downloads protegidos. O Portal Imagine continua operando apenas a integração técnica/contractual do site: **não deve expor nem baixar em nome próprio a base de clientes do contratante** apenas por manter o site. **Ainda pendentes:** tamanho de lotes, expiração dos links, arquivos temporários e escopo exato dos campos exportáveis. Os direitos LGPD não exigem compra de upgrade. [Matriz IN-026](13-matriz-permissoes-basico-cortesia.md).

### IN-025 — Importação básica de leads e contatos por CSV e XLSX no CRM (APROVADA)

O Administrador da Empresa no Básico pode **importar CSV e XLSX de leads/contatos**, dentro do **CRM autenticado**, com mapeamento de campos, prévia, validação e alerta de possíveis duplicidades; **sem mesclagem automática ou alterações silenciosas**. O upload manual autorizado não equivale a API pública, ingestão por robôs ou importação feita pelo Portal Imagine em nome do cliente. As importações respeitam isolamento do tenant, validações, registros de execução e direitos IN-024 (sem teto comercial de quantidade), mantendo **pendentes os limites técnicos por arquivo/lote e o tratamento de duplicidades**. A captação dos formulários do site continua **fluxo independente**; não criar agendamento a partir da importação. [Matriz IN-025](13-matriz-permissoes-basico-cortesia.md).

### IN-024 — Sem teto comercial de leads e contatos na cortesia (APROVADA)

**IN-024 aprovada em 2026-11-01:** não existe limite comercial rígido de **quantidade de leads/contatos captados pelos formulários Imagine ou cadastrados manualmente** no Básico; proteger com uso justo, antispam e controles técnicos documentados, nunca quota mensal/total oculta. Capturas já confirmadas devem permanecer duravelmente enfileiradas/registradas e entregues ao CRM, sem descarte silencioso; falha real deve ser tratada como tal. **Não incluídos por essa decisão:** API pública de ingestão, importação em massa, armazenamento irrestrito ou política de retenção. O Portal respeita a separação de direitos; não inventar contagem de leads para bloquear formulário/forçar upgrade. [Matriz IN-024](13-matriz-permissoes-basico-cortesia.md).

### IN-023 — Leads e Contatos: administração manual no Básico (APROVADA)

O administrador único do CRM Básico pode **consultar, cadastrar, editar e pesquisar leads e contatos**, registrar observações/histórico manual, detectar possíveis duplicidades por alerta **sem mesclar automaticamente** e **arquivar sem excluir dados ou histórico**. O formulário do Onepage/Landing Page continua enviando leads à organização autorizada; envio confirmado não poderá ser silenciosamente perdido. Não conceder automaticamente importação em massa, exportação comercial, mesclagem ou exclusão definitiva; limites de registros, retenção e requisitos LGPD serão definidos separadamente. O formulário público não cria reservas. [Matriz IN-023](13-matriz-permissoes-basico-cortesia.md).

### IN-022 — Administração da Empresa e Segurança do CRM Básico (APROVADA)

O Administrador único do Básico pode editar dados cadastrais comuns da empresa, alterar CNPJ somente após validação específica, gerir senha/e-mail/MFA com verificação de identidade, consultar/encerrar suas sessões, consultar plano e limites e **contratar upgrade CRM apenas mediante aceite expresso**, mantendo a assinatura do site no Portal independente. **Não** cria segundo usuário nem obtém superadmin ou acesso indevido a outros tenants. O Portal Imagine não deve fornecer atalhos que contornem MFA, validação de identidade, os direitos do CRM ou a separação de faturamento. [Matriz de permissões IN-022](13-matriz-permissoes-basico-cortesia.md).

### IN-020 — Usuário de login e profissional de agenda são quotas separadas

O CRM Básico Cortesia concede **até 1 usuário autenticado por organização**, independente de seu **único profissional agendável** (IN-008). Vários contratos Onepage/Landing Page na mesma empresa não multiplicam a quota de contas nem a de agendas. Atribuir um profissional à agenda não cria uma conta de login automaticamente. A tentativa de adicionar usuário acima da quota deve informar restrição e upgrade elegível no CRM, sem cobrança automática (IN-010); **IN-021 aprovou o perfil Administrador da Empresa para o único usuário, com MFA obrigatório**, limitado aos recursos efetivamente incluídos no Básico; detalhamento de ações por módulo e substituição da conta ainda está pendente. O Portal Imagine não pode criar usuário extra contornando os entitlements do CRM. [Especificação IN-020](11-agenda-manual-basico-cortesia.md).

### IN-013 — Canais de aviso da agenda vinculados à cortesia

A agenda do CRM Básico Cortesia oferece somente **notificações e lembretes internos no próprio CRM**. Esses avisos operacionais **não ocupam vagas de automações comerciais**. O Portal Imagine não deverá enviar e-mails automáticos de agendamento aos visitantes nem acionar API oficial WhatsApp como benefício do site: canais externos para agenda requerem plano CRM pago ou adicional elegível e respectivas autorizações. [Agenda manual — IN-013](11-agenda-manual-basico-cortesia.md).

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
