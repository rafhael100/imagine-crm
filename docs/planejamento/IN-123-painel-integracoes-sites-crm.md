# IN-123 — Painel de Integrações dos Sites Imagine com o CRM Imagine

**Estado:** APROVADA funcionalmente em 01/11/2026 pelo responsável do projeto.  
**Fase:** planejamento e documentação; NÃO iniciar programação.  
**Escopo:** Portal Imagine e integração Imagine Sites/landing pages ↔ CRM Imagine.
**Dependências:** IN-118 a IN-122; contratos e assinaturas de sites e CRM.

## Objetivo
Oferecer no **Portal Imagine** um painel centralizado para o cliente consultar seus projetos de sites e landing pages, conhecer a conta CRM vinculada a cada projeto, acompanhar o estado das integrações e sua elegibilidade à cortesia do CRM Básico e solicitar suporte para troca de vínculo.

## Funcionalidades aprovadas
1. **Lista de projetos contratados:** apresentar sites e landing pages do cliente aos quais tenha acesso autorizado.
2. **CRM vinculado:** exibir, por projeto, a conta CRM Imagine que recebe os dados dos respectivos formulários.
3. **Estado da integração:** identificar estados como **ativa**, **pendente** ou **com erro**, com informação contextual e atualização a partir do estado efetivo da integração.
4. **Cortesia do Básico:** indicar a elegibilidade e quais sites elegíveis sustentam o benefício da conta CRM, preservando a regra de **no máximo uma cortesia por conta** independentemente do número de sites vinculados (IN-122).
5. **Durante uma nova contratação:** permitir ao cliente escolher entre vincular o novo site/landing page a **uma conta CRM existente** autorizada ou **criar uma nova conta CRM** para o projeto.
6. **Solicitação de troca de CRM:** disponibilizar **abertura de chamado pelo painel**. A alteração de vínculo continuará sendo efetuada pela **equipe Imagine**, de forma **gratuita**, e não por troca direta/instantânea executada pelo cliente.
7. **Histórico:** disponibilizar registros auditáveis das alterações de vínculo e eventos relevantes, respeitando autorização e segregação entre contas/empresas.
8. **Teste de integração:** oferecer forma de verificar se formulários do site estão encaminhando leads corretamente à conta CRM vinculada, sem presumir que mera indicação de serviço online comprova a entrega.

## Regras vinculadas previamente aprovadas
- O suporte Imagine cobre gratuitamente **somente a alteração técnica de vínculo e integração**, mediante chamado, incluindo verificação do direcionamento de novos leads.
- **Migração dos dados antigos não está incluída**; o próprio cliente usa backup/exportação/importação (IN-118).
- A troca de vínculo não exclui a conta antiga nem seus dados; elegibilidade e benefícios de **ambas as contas** devem ser reavaliados (IN-119 a IN-122).
- Na ausência do último site elegível, aplicar transição de **30 dias** e notificações conforme IN-119 a IN-121.
- Nenhum benefício é acumulado na mesma conta e uma conta CRM sem site elegível não recebe cortesia apenas por constar no portal.

## Cuidados funcionais e técnicos a detalhar
- Permissões: apenas clientes autorizados podem visualizar contratos, contas, identificadores e registros de integração.
- Evitar expor dados internos de outros projetos/empresas em relações de titularidade compartilhada.
- Mapear estados reais e transições de integração, origem dos testes e eventuais falhas sem gerar leads comerciais falsos; decidir como registrar/testar submissões de forma segura.
- Definir filtros, busca, ações rápidas e detalhamento do histórico.
- Associar o chamado ao projeto, vínculo atual e conta de destino escolhida, com confirmações e validação de titularidade.
- Não substituir o suporte e o procedimento formal de troca por uma ação direta no portal.
- Compatibilizar dados do Portal Imagine e CRM Imagine com fonte de verdade única para contrato, vínculos e cortesia; registrar auditoria e falhas.

## Delimitação de aprovação
**Aprovados:** painel com projetos, CRM vinculado, estado, benefício, escolha de conta na contratação, solicitação de troca via chamado, histórico e teste de integração.  
**A detalhar antes de desenvolver:** formatos dos testes, permissões granulares, UX, operação de chamados e sincronização de estados.  
**Nenhuma programação autorizada.**


## Complemento COM-08 — visibilidade da cortesia por produto e campanha (02/11/2026)

O Portal deverá permitir à equipe Imagine **configurar no cadastro de cada serviço e campanha** se a contratação **oferece ou não CRM Básico como cortesia**, sempre para novas vendas e com cláusula clara no aceite. O painel de integrações deverá indicar **motivo e contrato(s) que sustentam o benefício**, distinguindo: cortesia ativa, cortesia suspensa por inadimplência do único site elegível, ausência de cortesia por campanha, plano CRM pago e transição de 30 dias após cancelamento do último vínculo. Não bloquear CRM pago ou cortesia mantida por outro site; proteger dados e reavaliar ao regularizar. Condições específicas em [PORTAL-COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md); detalhamento técnico futuro.
