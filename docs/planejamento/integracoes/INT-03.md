# INT-03 — Esquemas, Mapeamentos e Transformações de Dados de Integrações

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Contratos estruturais de dados, mapeamentos versionados e transformações tipadas.
**Dependências e referências:** INT-04, INT-07, INT-08.
**Cenários propostos de homologação:** HT-03, HT-07, HT-12 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Definir esquemas e correspondências entre campos e entidades, mantendo origem, destino, versão e finalidade.
- Validações de tipo, obrigatoriedade, compatibilidade, enumerações, normalização e tratamento explícito de divergências.
- Prévia e testes sem escrita para verificar resultados antes da aplicação pelas operações proprietárias.
- Respeitar autoridade dos módulos por campo, confidencialidade, rastreabilidade e mudanças de versão.

## Competências reservadas e exclusões
- Não executar SQL, scripts livres, transformações arbitrárias ou acesso direto a bancos.
- Não determinar mesclagem, exclusão ou sobrescrita oficial de dados sem módulo proprietário.
- INT-04 executa contratos técnicos; INT-07/08 administram sincronização e movimentações delimitadas.

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
