# INT-07 — Sincronizações Contínuas e Checkpoints

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Sincronizações delimitadas por fontes, destinos, entidades, campos e políticas.
**Dependências e referências:** INT-03, INT-04, INT-06, INT-10.
**Cenários propostos de homologação:** HT-06, HT-08, HT-16 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Sincronização unidirecional ou bidirecional apenas com autoridade por campo e políticas explícitas.
- Cursores, checkpoints, revalidação de autorização, concorrência, idempotência e retomada segura.
- Gerir conflitos, exclusões, alterações fora de ordem, ciclos, estados inconclusivos e divergências sem pressupor resolução unilateral.
- Preservar confirmação oficial do módulo proprietário e reconciliar os efeitos efetivamente aplicados.

## Competências reservadas e exclusões
- Sem regra universal de último escritor, mesclagem ou sobrescrita de fonte oficial.
- Migrações e importações delimitadas pertencem à INT-08; processos multissistema à INT-09.
- Recuperação especializada de incidentes pertence à INT-10.

## Regras transversais obrigatórias
- Tenant/conta, identidade, recurso e finalidade verificados em cada ato protegido; isolamento entre empresas e entre processos internos da Imagine e processos dos tenants.
- Competência e confirmação oficial permanecem no módulo proprietário. Transmissão, HTTP 2xx, workflow n8n concluído ou webhook recebido **não** bastam para afirmar conclusão de negócio.
- Comandos e eventos tipados/versionados, correlação e causalidade, idempotência contextual, concorrência controlada, estados persistentes, reconciliação e rastreabilidade.
- Autorização vigente e aprovações válidas conferidas nas fronteiras protegidas; IA apenas sugere, nunca autoconcede permissões, poderes ou confirmações.
- Reutilizar motores e serviços centrais: Identidade e Segurança; Gatilhos, Condições e Ações; Construtor de Fluxos; Eventos; Datas e Prazos; Tarefas; Aprovações; Filas; Arquivos; COM; Analytics; Auditoria Geral.
- Nenhum SQL arbitrário, scripts/código livre de clientes, URLs irrestritas, acesso direto aos bancos de negócio ou superprivilégios ao n8n.
- Não confundir dados estruturados obrigatórios de operação/auditoria com franquias de arquivos. Preservar CRM Básico e trial sem API pública, com integrações nativas autorizadas.

## Governança e próximo passo
- Rastrear cada requisito à decisão original, responsável, versão, contrato, invariantes INV e cenário HT em [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- Não presumir que esta síntese reproduz palavra por palavra o texto integral da aprovação. Conferir literalidade, lacunas, limites, prazos e exceções no item P0-DOC da [matriz P0](INT-P0-fechamento-documental.md).
- As decisões já aprovadas prevalecem sobre esta redação sintética; alterações exigem deliberação expressa.
