# INT-01 — Cadastro Mestre e Governança Inteligente de Integrações, Provedores e Conexões

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Cadastro, ciclo de vida e referências oficiais de provedores, contas e conexões.
**Dependências e referências:** INT-02, INT-04, INT-11.
**Cenários propostos de homologação:** HT-01, HT-02, HT-18 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Modelar cadastros e vínculos de provedores, contas e conexões sob contexto de tenant, unidade, marca, recurso, finalidade e capacidade.
- Registrar estados, disponibilidade cadastral, dependências, provedores autorizados e vínculo a conector/versão homologados.
- Manter referências a requisitos e permissões, sem ser cofre de segredos ou emissor autônomo de autorizações.
- Administrar alterações e encerramentos de conexões por fluxos controlados, com histórico e impacto nas operações pendentes.

## Competências reservadas e exclusões
- Credenciais, tokens, escopos e revogações: INT-02 e Identidade e Segurança.
- Esquemas, adaptadores, APIs, webhooks, sincronizações e orquestrações: INT-03 a INT-09.
- Homologação e catálogo de conectores: INT-11.

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
