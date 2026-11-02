# INT-10 — Monitoramento Inteligente, Diagnóstico, Contingência e Recuperação de Integrações

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Saúde operacional, diagnóstico, incidentes e recuperação técnica especializada.
**Dependências e referências:** INT-01 a INT-09, Central de Observabilidade, Eventos, Filas, Aprovações.
**Cenários propostos de homologação:** HT-02, HT-06, HT-08, HT-11, HT-16 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Monitorar conectividade, autorizações, erros, filas, eventos, latência, quotas, processos, dependências e alterações de disponibilidade.
- Classificar saúde e incidentes distinguindo saudável, degradado, indisponível, desconhecido/sob investigação, recuperação, verificação e normalização com ressalvas.
- Retentativas e correções técnicas automáticas somente seguras, limitadas, homologadas e autorizadas; intervenção humana para ações condicionadas.
- Investigar resultado desconhecido e operação parcialmente aplicada antes de novas escritas, sobretudo efeitos financeiros, comerciais, contratuais ou operacionais.
- Isolamento de falhas, limitação de taxa, prevenção de cascata, contingência e retomada gradual condicionada à revalidação.
- Concluir recuperação somente mediante evidências suficientes; saúde técnica não implica conclusão das operações pendentes.

## Competências reservadas e exclusões
- Não renovar autorizações, mudar provedores críticos ou executar compensações de negócio por conta própria.
- INT-09 mantém estados e coordenação de instâncias; proprietários confirmam resultados; INT-12 correlaciona evidências.
- Não duplicar observabilidade, alertas, tarefas, aprovações e auditoria centrais; IA consultiva.

## Regras transversais obrigatórias
- Tenant/conta, identidade, recurso e finalidade verificados em cada ato protegido; isolamento entre empresas e entre processos internos da Imagine e processos dos tenants.
- Competência e confirmação oficial permanecem no módulo proprietário. Transmissão, HTTP 2xx, workflow n8n concluído ou webhook recebido **não** bastam para afirmar conclusão de negócio.
- Comandos e eventos tipados/versionados, correlação e causalidade, idempotência contextual, concorrência controlada, estados persistentes, reconciliação e rastreabilidade.
- Autorização vigente e aprovações válidas conferidas nas fronteiras protegidas; IA apenas sugere, nunca autoconcede permissões, poderes ou confirmações.
- Reutilizar motores e serviços centrais: Identidade e Segurança; Gatilhos, Condições e Ações; Construtor de Fluxos; Eventos; Datas e Prazos; Tarefas; Aprovações; Filas; Arquivos; COM; Analytics; Auditoria Geral.
- Nenhum SQL arbitrário, scripts/código livre de clientes, URLs irrestritas, acesso direto aos bancos de negócio ou superprivilégios ao n8n.
- Não confundir dados estruturados obrigatórios de operação/auditoria com franquias de arquivos. Preservar CRM Básico e trial sem API pública, com integrações nativas autorizadas.

## Complemento de rastreabilidade P0 (sem nova aprovação)
- Diferenciar estados de saúde técnica, investigação, recuperação autorizada/em execução, intervenção, verificação, normalização e encerramento com ressalvas; sem equiparar telemetria ausente a estado saudável.
- **INT-10 utiliza a Central de Observabilidade IN-159 e logs/traces IN-160**, sem criar outra central técnica ou serviço paralelo de alertas/incidentes. A ausência do painel ou do agente de IA não pode interromper monitoramento essencial.
- Recuperação automática só de procedimento técnico seguro previamente homologado e escopado; ação sensível segue autoridade e aprovação aplicável. A capacidade N2 de agente técnico (IN-167) não implica autoridade livre da IA.
- Recuperação de conectividade do provedor não comprova conclusão de comandos/negócios pendentes. Resultado desconhecido exige reconciliação e recuperação gradual após revalidação.
- [Conferência P0 INT-09 a INT-12](P0-REV-INT09-12-conferencia-cruzada.md). Políticas e transições específicas ainda pendentes de detalhamento.

## Governança e próximo passo
- Rastrear cada requisito à decisão original, responsável, versão, contrato, invariantes INV e cenário HT em [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- Não presumir que esta síntese reproduz palavra por palavra o texto integral da aprovação. Conferir literalidade, lacunas, limites, prazos e exceções no item P0-DOC da [matriz P0](INT-P0-fechamento-documental.md).
- As decisões já aprovadas prevalecem sobre esta redação sintética; alterações exigem deliberação expressa.
