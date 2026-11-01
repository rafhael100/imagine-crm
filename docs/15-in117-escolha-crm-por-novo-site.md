# IN-117 — Escolha da organização CRM ao contratar novo site ou Landing Page Imagine

> Decisão de planejamento de 01/11/2026. **Não implementar antes da conclusão e aprovação de toda a documentação.** Esta regra complementa IN-001/002, IN-020/021, IN-024 e IN-116; não altera preços nem amplia automaticamente vagas, usuários ou quotas dentro de uma organização existente.

## 1. Necessidade e decisão funcional aprovada

Se um cliente que já possui um Onepage ou Landing Page Imagine contratar **outro site ou Landing Page**, o **Portal Imagine deverá perguntar explicitamente** qual **organização (ambiente de trabalho) do CRM Imagine** será usada para o novo projeto, apresentando **duas opções claras**:

1. **Vincular novo projeto a uma organização CRM existente:** mostrar **somente as organizações a que o usuário/representante autenticado tem direito de vincular um novo site**. Exigir seleção e confirmação da organização e do projeto. Associar o site e seus formulários à organização selecionada; atribuir origem identificável por site, formulário e campanha/UTM para permitir separação operacional por relatórios/filtros conforme os direitos do plano. **Não misturar nem transferir automaticamente registros históricos de uma organização para outra.** Os limites de usuários, agenda, automações e armazenamento continuam aplicáveis por organização: diversos sites conectados à mesma organização **não multiplicam** licenças ou franquias.
2. **Solicitar novo ambiente/organização CRM para o projeto:** criar/provisionar **uma nova organização de dados segregada**, associada ao novo contrato elegível, com permissões, registros, configuração e franquias próprios do plano aplicável. **Reutilizar o login do cliente, sem gerar automaticamente segunda identidade ou novo usuário de login**; usar a alternância segura de contexto multiempresa já planejada. A criação deve verificar legitimidade do solicitante, direitos sobre o contrato e organização de destino, identificação empresarial/CNPJ e riscos de duplicidade ou abuso. Pode haver múltiplos ambientes sob acesso autorizado da mesma identidade, mas acesso a um não concede acesso aos demais sem vínculo/permissão.

A confirmação de destino deve fazer parte da jornada de **contratação, ativação e integração** do novo site pelo Portal, de forma compreensível ao cliente. **Não selecionar automaticamente a organização mais recente, a primeira do usuário ou qualquer organização baseada apenas em e-mail/CNPJ**. Se houver uma única organização possível, apresentar sua identificação para confirmação e a alternativa de novo ambiente. Sem escolha válida ou autorização comprovada, **não ativar integração nem publicar um formulário apontado para organização incerta**; permitir continuar o planejamento do pedido sem uma associação incorreta até resolver a pendência.

## 2. Terminologia e controles

- **Login/usuário CRM:** identidade autenticada; poderá alternar organizações que administra/tem acesso, sem duplicação compulsória de login por site.
- **Organização/ambiente CRM:** fronteira de isolamento de dados, acesso e quotas. Esta é a entidade que o cliente escolhe para cada novo site. O termo comercial "nova conta CRM" deve ser explicado como **novo ambiente/organização**, não novo e-mail/senha nem uma licença extra no ambiente atual.
- **Site/landing page:** produto e contrato próprio no Portal Imagine. Um site aponta para **uma organização CRM ativa e autorizada de cada vez** no fluxo operacional; múltiplos sites podem apontar para **uma mesma organização**, identificados individualmente por origem.
- **Benefício CRM Básico:** enquanto uma organização for elegível pelo contrato do site, aplica-se o **mesmo Básico público** da IN-116 sem segunda mensalidade CRM; **não criar cobranças, parcelas nem uma licença de usuário adicional automaticamente**. Regras detalhadas de benefício para duas organizações distintas da **mesma pessoa jurídica/CNPJ** e eventual limite comercial de ambientes por cliente **seguem em revisão**, inclusive critérios de criação, prevenção de uso indevido e transição ao cancelamento. Não presumir nem negar automaticamente segundo ambiente por coincidência de CNPJ; requer política aprovada e tratamento transparente.
- **Plano Básico avulso:** usuários que contratarem CRM diretamente continuam elegíveis a escolher uma organização autorizada quando também contratarem site; mudança de origem de concessão/assinatura paga precisa evitar cobrança duplicada, pró-rata indevido e regressão de acesso. **Valores atuais de planejamento:** Básico R$ 69/mês, Essencial R$ 89/mês; revisão final de todos os planos/serviços após documentação completa.

## 3. Jornada de contratação e ativação (planejada)

1. Cliente autentica no Portal Imagine, seleciona novo Onepage ou Landing Page, aceita contrato do novo site pelos canais e versão definidos; exibir etapa **“Como deseja utilizar o CRM Imagine neste novo projeto?”**
2. **Opção A — integrar ao meu CRM existente:** listar organização, nome comercial/CNPJ quando autorizado e status; consultar permissão de vinculação, evitar organização alheia, selecionar e confirmar; mostrar que os leads ficarão no mesmo ambiente, sem multiplicação de quotas. Antes da publicação, garantir que o formulário configurado e testado aponta para a organização correta.
3. **Opção B — criar ambiente CRM separado:** apresentar diferenciação de dados/gestão, verificar titularidade e representantes, identificar empresa/site, revisar política de concessão por novo contrato, confirmar criação e provisionar **organização separada**, com rastreabilidade e isolamento. Preservar login e vincular administrador habilitado. A criação não é autorização para criar inúmeros entitlements sem política.
4. Confirmar ao cliente no Portal o vínculo efetivo **pedido/site ↔ organização CRM**, com identificação, origem de leads, status, data e responsável; permitir acompanhamento de falha e reconciliação. Não declarar integração pronta enquanto não houver confirmação técnica.
5. Mudança posterior do destino do site exige fluxo específico autorizado com impacto sobre formulários futuros, dados históricos, LGPD e idempotência; **não migrar histórico automaticamente**. Definir política de alteração, transferência ou criação de site em outra empresa antes de lançar.
6. Contratos, termos e UX devem informar: destino dos dados, gestão de autorização, eventual concessão gratuita do Básico por contrato, regras de cancelamento/inadimplência do site, limites por organização, separação dos dados e fluxo de troca de destino.

## 4. Salvaguardas de segurança e integridade

- Nunca aceitar tenant_id, organização CRM ou destino de lead informado pelo **visitante do site**; validação e vínculo partem exclusivamente do backend autenticado e do formulário público com identificador seguro. Proteção contra IDOR, enumerabilidade de empresas, duplicidade de eventos, concorrência de escolhas/ativação, retry, troca de propriedade e escalonamento de privilégios.
- Histórico e relatórios devem preservar **site_id/form_id/source_campaign** em cada lead recebido. Distinguir organização do cliente e campanha do site. Não permitir que escolha do destino seja usada para acessar dados de CRM de terceiros.
- Na ativação de novo ambiente, autenticação MFA e RBAC existentes valem normalmente; sem conceder automaticamente múltiplos usuários na mesma organização.
- Se o cliente escolher organização com plano CRM pago, o direito efetivo do plano pago prevalece sem somar capacidade do Básico; benefício de site pode ser registrado como fallback conforme política de elegibilidade.
- Auditabilidade: pedido, organização de origem/destino, identidade do solicitante e aprovador, momento, escolha, versões de termos, provisionamento e resultado; nenhuma nova assinatura financeira ou fatura de R$ 0 é criada por inferência.

## 5. Pontos a decidir antes da implementação

- **Novo ambiente para mesmo CNPJ:** confirmar se qualquer contrato adicional de site elegível **pode conceder uma organização CRM Básico separada gratuitamente**, com os mesmos recursos e 1 usuário próprio por organização, mesmo quando várias organizações representam uma mesma pessoa jurídica; critérios de abuso/escala e eventual teto comercial/contábil. A decisão de apresentar a alternativa ao cliente está aprovada; **os termos comerciais específicos de múltiplos ambientes no mesmo CNPJ não estão**.
- Quem pode escolher ou alterar a organização vinculada (titular, administrador, representante), convites e verificações de CNPJ, especialmente quando empresa contratante do Portal é diferente do titular da organização CRM.
- Se uma organização existente estiver em trial, paga, cancelada, suspensa ou de outra pessoa jurídica: política explícita de elegibilidade e verificação.
- Se nenhum vínculo for permitido, como orientar ou permitir continuar a contratação/ativação e quando pedir auxílio administrativo.
- Regras de desconexão, reversão, migração voluntária de leads, exclusão, custos, retenção, auditoria, timing e LGPD.
- Critérios finais de entitlements e preço/recursos de todos os planos serão **revistos ao fim de toda a documentação**, antes de programação, homologação e lançamento.

**Status:** aprovada a **pergunta obrigatória e as duas opções** no Portal Imagine ao contratar site adicional. Políticas de concessão para organizações separadas sob mesmo CNPJ e outras regras detalhadas permanecem **pendentes**, sem decisão implícita de novo custo ou de gratuidade por tenant ilimitado. **Nenhum código, banco, checkout, cliente ou ambiente de produção foi alterado.**
