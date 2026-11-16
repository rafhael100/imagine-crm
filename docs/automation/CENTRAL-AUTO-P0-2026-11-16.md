# Central de Automações — P0 candidato, 16/11/2026

Autorização: direção, 16/11/2026. Referências CRM #557, AI Agents #339, Sites #37, Portal #802. DRAFT / NO-MERGE / NO-DEPLOY / NO-CHARGE.

## Inventário verificado e reaproveitamento

| Projeto | HEAD inspecionada | Reaproveitamento / limite |
|---|---|---|
| CRM DEV | d7c7aacc766c7ade4c65dcd211ed4569442144a9 | AsyncAuthorityBridge/ExecutionClass e deny-by-default preservados; nenhum ALLOW novo |
| CRM jornada privada | f12d57aa0057387729d6dba89987a94975b67478 | Workspace já oferece followup_qualified/followup_proposal; modelos preservados no catálogo candidato, ainda sem trocar o dispatcher da jornada |
| AI Agents #371 | b408fa97b3b23df4763556e76ad854ea4e2fa5d6 | AUTO-IA router, envelopes assinados, inbox PostgreSQL, outbox, workflow determinístico e infra/n8n-private já existem; não criar outro Gateway/stack |
| AI Agents #372 | 30252ffa14925af409fbacfe87fbf8c13ded3c97 | Outra linhagem de outbox atômica; não presumida incorporada à #371 |
| Portal #900 | 3f1e2cd5573fe483608587f3b9aa5c07b40dbf00 | Administração privada/S2S/MFA; não presumidos na main |
| Sites #483 | 814f0afd0741821696b42ff0c7fa180af6286ba1 | Staging específico; nenhum acesso/alteração VPS nesta entrega |

Comercial, SEO, Universidade e Performance foram inventariados como consumidores futuros. Cada produto mantém sua autorização; nenhuma publicação, anúncio, cobrança ou aprovação humana é delegada ao n8n.

## Implementação deste recorte

`RuleDefinition` fecha o esquema e limita quatro modelos: distribuição de novo lead, follow-up de qualificado/proposta e aviso interno de atraso. Só produz comando nativo tipado, não executa efeito. Rejeita código, URLs, ações financeiras/publicação/IA e eventos fora da empresa/janela. Não recebe limites do navegador. IA = zero por construção.

`tools/automation/LabRuleStore` exercita **PostgreSQL descartável de CI**, não é serviço operacional. Serializa por empresa; publica versões com comparação otimista; conserva histórico; pausa/edição cancelam agendas antigas; conta quota diária em admissões inclusive canceladas/falhadas; deduplica chave com hash dos fatos; retentativas têm backoff e limite; revalida política e versão ao consumir; grava efeito fictício e auditoria na mesma transação. Sem callback arbitrário ou transporte.

O SQL é fixture exclusiva de CI, fora de migrations Laravel. RLS FORCE e role NOSUPERUSER/NOBYPASSRLS são testados. O GUC isola consultas, mas **não autentica**: o harness controla os dois tenants; não prova IAM, papel humano, associação do destinatário ou revogação de usuário. Falta ponte homologada de autoridade para substituir o simulador de efeito por tarefas/atribuições reais. Não alterar DenyByDefaultAuthorizer para contornar esse gate.

## Contrato com n8n: aproveitamento, sem falsa integração

O contrato existente é `AUTO_IA_S2S_V1` em AI Agents `tools/auto_ia/pg/003_signed_ingress_lab.sql`: issuer/audience/product/operation/tenant/event/nonce/request/issued/expires/payload_hash/key_id/signature. O emissor não repassa sessão humana. A assinatura atesta integridade, nunca autorização comercial. Destino deve revalidar concessão, tenant e revogação inclusive em replays.

Os comandos `lead.assignment.request`, `task.followup.create` e `notification.internal.create` **não** são traduzidos para `lead.ingest`/`lead.score.rules` apenas para passar no router atual. São efeitos do CRM. Até mapping técnico e autorização bilateral próprios, integração n8n permanece bloqueada. Notificações externas exigem consentimento, destinatário e alçada no produto; nenhum envio aqui.

Fluxos Portal→CRM/Sites exigem serviço habilitado e confirmação de negócio no destino. n8n→Gateway exige orçamento e consentimento verificados; não recebe credenciais de provedor. Nenhuma conexão de n8n aos bancos dos produtos.

## Licença e infraestrutura

A configuração candidata já existe em AI Agents `infra/n8n-private/`, com PostgreSQL/volumes/redes próprios, porta sugerida 17990 em loopback, imagens exigindo digest e sem restart automático. A porta **não foi inventariada nem reservada nesta rodada**. Não instalar novamente. Redis/queue/workers aguardam carga e capacidade verificadas.

A orientação oficial n8n de 06/10/2026 inclui configuração de workflows por interface própria/API/MCP na hipótese de licença comercial; ocultar o editor não basta. Fonte: https://support.n8n.io/article/can-i-use-your-license-for-my-use-case . Por isso o CRM executa o motor nativo configurável, sem converter regras dos clientes em workflows n8n. Somente fluxos técnicos internos controlados pela Imagine são candidatos; o enquadramento concreto da licença continua gate. Sem compra/contato externo.

Antes de instalar: inventário VPS, capacidade/portas, licença aplicável, imagens pinadas revisadas, segredos fora de environment/logs, TLS/identidade de serviço, egress restrito, task runners/Code nodes isolados ou desabilitados, retenção de payloads, backup externo e restauração, autenticação nominal/MFA e proxy privado. Compose existente não equivale a homologação desses pontos.

## Evidências e pendências

Workflow `central-automation-p0.yml` executa PHP 8.4/PDO/PG16 com dados fictícios: condições, novo lead/atraso, cross-tenant, esquema fechado, ações sensíveis, duplicidade/hash conflitante, pausa, edição, versões, revogação, quotas, retry/backoff, duas execuções PHP concorrentes e exatamente um efeito durável. Resultados devem ser lidos na SHA efetiva; não inferir sucesso pelo arquivo de teste.

Ainda não entregue: UI cliente/Portal, RBAC/MFA operacional, cancelamento individual autenticado, revisão independente, contratos bilaterais operacionalizados, scheduler/worker real, integração com tarefas reais, alertas externos e teste Portal→Sites deste recorte. Nenhuma nova URL e nenhum deploy. Próxima entrega: conectar autoridade e armazenamento nativo à jornada privada em branch própria, com formulários versionados/CSRF/RBAC, auditoria e testes HTTP; depois adaptar somente eventos técnicos aprovados à inbox existente dos Agents.

### Prova bilateral adicionada

O job `pinned-agents-contract` faz checkout separado da SHA Agents acima, inicializa seu receiver PostgreSQL **sem copiar/modificar a implementação** e exercita assinatura PHP, inserção idempotente, troca de tenant, alteração de payload, operação sensível/IA bloqueadas e revogação de chave seguida do replay exato. Evento testado é o `lead.ingest` já catalogado, não um disfarce para os comandos de tarefas. SQL gerado apenas entre processos do runner; não é HTTP, mTLS, serviço n8n ou autorização operacional.
