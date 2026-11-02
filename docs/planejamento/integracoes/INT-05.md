# INT-05 — Aplicações Integradas e API Pública

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Gestão de aplicações autorizadas e uso da API pública do CRM.
**Dependências e referências:** INT-02, INT-04, Identidade e Segurança.
**Cenários propostos de homologação:** HT-04, HT-13 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Registrar aplicações e acessos por identidade, tenant, finalidade, escopos, permissões, versão e quotas.
- Exigir contratos de API tipados, documentação e proteção de dados e de operações protegidas.
- Regras de publicação, elegibilidade comercial, bloqueio, revogação e auditoria de acessos.
- No trial, API pública permanece desabilitada, mas isso não bloqueia integrações nativas legitimamente autorizadas.

## Competências reservadas e exclusões
- Não contornar autenticação, aprovações ou competência do módulo proprietário.
- Não fornecer operações sem autorização nem acesso aos bancos de negócio.
- Credenciais e escopos são de INT-02; execução de capacidades é de INT-04.

## Regras transversais obrigatórias
- Tenant/conta, identidade, recurso e finalidade verificados em cada ato protegido; isolamento entre empresas e entre processos internos da Imagine e processos dos tenants.
- Competência e confirmação oficial permanecem no módulo proprietário. Transmissão, HTTP 2xx, workflow n8n concluído ou webhook recebido **não** bastam para afirmar conclusão de negócio.
- Comandos e eventos tipados/versionados, correlação e causalidade, idempotência contextual, concorrência controlada, estados persistentes, reconciliação e rastreabilidade.
- Autorização vigente e aprovações válidas conferidas nas fronteiras protegidas; IA apenas sugere, nunca autoconcede permissões, poderes ou confirmações.
- Reutilizar motores e serviços centrais: Identidade e Segurança; Gatilhos, Condições e Ações; Construtor de Fluxos; Eventos; Datas e Prazos; Tarefas; Aprovações; Filas; Arquivos; COM; Analytics; Auditoria Geral.
- Nenhum SQL arbitrário, scripts/código livre de clientes, URLs irrestritas, acesso direto aos bancos de negócio ou superprivilégios ao n8n.
- Não confundir dados estruturados obrigatórios de operação/auditoria com franquias de arquivos. Preservar CRM Básico e trial sem API pública, com integrações nativas autorizadas.

## Conferência documental P0 — INT-05 a INT-08
- No trial, a **API pública segue desabilitada**, mas integrações nativas legitimamente autorizadas não devem ser bloqueadas por essa condição. Não confundir API pública com recepção central de formulários Imagine (IN-124/150).
- Evidências, ressalvas e títulos ainda sujeitos a conferência literal em [P0-REV-INT05-08](P0-REV-INT05-08-conferencia-cruzada.md). **Não equivale a homologação nem a encerramento de P0-DOC-01.**

## Governança e próximo passo
- Rastrear cada requisito à decisão original, responsável, versão, contrato, invariantes INV e cenário HT em [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- Não presumir que esta síntese reproduz palavra por palavra o texto integral da aprovação. Conferir literalidade, lacunas, limites, prazos e exceções no item P0-DOC da [matriz P0](INT-P0-fechamento-documental.md).
- As decisões já aprovadas prevalecem sobre esta redação sintética; alterações exigem deliberação expressa.
