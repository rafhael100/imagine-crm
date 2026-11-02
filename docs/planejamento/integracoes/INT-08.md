# INT-08 — Gestão Inteligente de Importações, Exportações e Migrações Integradas de Dados

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Movimentações delimitadas e controladas de dados por operação/lote.
**Dependências e referências:** INT-02, INT-03, INT-04, Arquivos, Aprovações.
**Cenários propostos de homologação:** HT-03, HT-07, HT-13, HT-17 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Planejar, configurar, revisar, aprovar, simular, executar, acompanhar, suspender, recuperar, reconciliar e encerrar importações, exportações e migrações.
- Suportar arquivos e formatos autorizados (inclusive CSV, XLSX e JSON conforme contratos), validação segura de arquivos, prévias sem escrita e mapeamentos INT-03.
- Processamento em lotes, checkpoints, idempotência, pré-condições, limites, confirmação de efeitos pelos proprietários e aceite fundamentado.
- Exportação com escopo e permissões, proteção dos dados, links temporários, histórico e auditoria.
- Trial mantém importação CSV permitida e API pública desabilitada.

## Competências reservadas e exclusões
- Não efetuar mesclagens, exclusões, rollback universal ou escritas inconclusivas por decisão da INT-08.
- Não tratar transferência entre contas como obrigação automática da Imagine.
- Sem decisão autônoma de IA para aplicar migrações ou assumir resultados.

## Regras transversais obrigatórias
- Tenant/conta, identidade, recurso e finalidade verificados em cada ato protegido; isolamento entre empresas e entre processos internos da Imagine e processos dos tenants.
- Competência e confirmação oficial permanecem no módulo proprietário. Transmissão, HTTP 2xx, workflow n8n concluído ou webhook recebido **não** bastam para afirmar conclusão de negócio.
- Comandos e eventos tipados/versionados, correlação e causalidade, idempotência contextual, concorrência controlada, estados persistentes, reconciliação e rastreabilidade.
- Autorização vigente e aprovações válidas conferidas nas fronteiras protegidas; IA apenas sugere, nunca autoconcede permissões, poderes ou confirmações.
- Reutilizar motores e serviços centrais: Identidade e Segurança; Gatilhos, Condições e Ações; Construtor de Fluxos; Eventos; Datas e Prazos; Tarefas; Aprovações; Filas; Arquivos; COM; Analytics; Auditoria Geral.
- Nenhum SQL arbitrário, scripts/código livre de clientes, URLs irrestritas, acesso direto aos bancos de negócio ou superprivilégios ao n8n.
- Não confundir dados estruturados obrigatórios de operação/auditoria com franquias de arquivos. Preservar CRM Básico e trial sem API pública, com integrações nativas autorizadas.

## Relação documental importante com a Central de Backup e Portabilidade
- [IN-118 — Central de Backup e Portabilidade de Dados](../IN-118-central-backup-portabilidade-dados.md) assegura autoatendimento essencial em **todos os planos, inclusive Básico**: exportação e importação CSV/XLSX, backup completo para download, restauração compatível, migração entre contas autorizadas e histórico, sem cobrança adicional por operação. A INT-08 participa das movimentações delimitadas; **não assumir que detém integralmente a Central de Backup** nem duplicar infraestrutura central de arquivos/backups.
- Exportação simples e backup completo são distintos. O limite de **100% da franquia de anexos** bloqueia apenas novos uploads de anexos, não a exportação/download de dados existentes. Valem as franquias 1/5/20/50 GB e avisos 80/95%, com limites operacionais de backup/processamento a dimensionar.
- Transferência de vínculo de site para outra conta CRM por chamado: Imagine realiza gratuitamente apenas a alteração técnica. A migração de dados históricos cabe ao cliente, usando funcionalidades autorizadas de portabilidade (IN-118/122/123).
- Conferência cruzada documentada em [P0-REV-INT05-08](P0-REV-INT05-08-conferencia-cruzada.md). **O cotejo literal da aprovação INT-08 ainda está pendente**; este acréscimo é uma referência a decisões existentes, não nova decisão.

## Governança e próximo passo
- Rastrear cada requisito à decisão original, responsável, versão, contrato, invariantes INV e cenário HT em [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- Não presumir que esta síntese reproduz palavra por palavra o texto integral da aprovação. Conferir literalidade, lacunas, limites, prazos e exceções no item P0-DOC da [matriz P0](INT-P0-fechamento-documental.md).
- As decisões já aprovadas prevalecem sobre esta redação sintética; alterações exigem deliberação expressa.
