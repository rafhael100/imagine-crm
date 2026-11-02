# INT-04 — Operações Técnicas, Adaptadores e Capacidades de Integração

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Operações tipadas, contratos, adaptadores e capacidades verificadas.
**Dependências e referências:** INT-02, INT-03, INT-11.
**Cenários propostos de homologação:** HT-01, HT-03, HT-08, HT-12 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Manter catálogo de capacidades externas homologáveis, versionadas e explicitamente permitidas.
- Executar operações técnicas tipadas somente com autorização vigente, contratos definidos, limites e controles de erro.
- Separar preparado, enviado, aceito, aplicado e confirmado; preservar resultado desconhecido e evidências para reconciliação.
- Aplicar idempotência contratual e limites de concorrência, sem inferir sucesso pelo transporte.

## Competências reservadas e exclusões
- Não oferecer execução de endpoints genéricos, URLs irrestritas ou código arbitrário.
- Não criar conexão nem credenciais paralelas a INT-01/02.
- Não declarar fatos de negócio por conta própria; autoridade é do módulo proprietário.
- A homologação do conector pertence à INT-11.

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
