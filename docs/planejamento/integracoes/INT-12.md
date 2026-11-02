# INT-12 — Auditoria Inteligente, Rastreabilidade e Conformidade de Integrações

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Visão especializada, investigação e correlação de evidências oficiais de integração.
**Dependências e referências:** INT-01 a INT-11, Auditoria Geral, Analytics, Identidade e Segurança.
**Cenários propostos de homologação:** HT-14, HT-17 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Correlacionar comandos, operações, eventos, tentativas, autorizações, versões, aprovações, incidentes, compensações e confirmações oficiais.
- Linha do tempo fundamentada por tenant, identidade, conexão, operação, origem, destino, causalidade, idempotência e referência de evidência.
- Auditar acessos e alterações, falhas, recuperação e a própria atividade de auditoria; detectar divergências e lacunas sem inventar resultados.
- Consultas, investigações e exportações autorizadas, com minimização, mascaramento, proveniência, integridade e retenção governada.
- IA consultiva para resumos, anomalias e hipóteses, identificando interpretações separadamente dos fatos originais.

## Competências reservadas e exclusões
- Auditoria Geral é a fonte oficial: sem banco ou trilhas paralelas de auditoria.
- INT-12 não reconcilia efeitos de negócio por autoridade própria nem executa correções, exclusões ou compensações.
- Analytics produz indicadores e relatórios oficiais; módulos proprietários confirmam seus fatos.

## Regras transversais obrigatórias
- Tenant/conta, identidade, recurso e finalidade verificados em cada ato protegido; isolamento entre empresas e entre processos internos da Imagine e processos dos tenants.
- Competência e confirmação oficial permanecem no módulo proprietário. Transmissão, HTTP 2xx, workflow n8n concluído ou webhook recebido **não** bastam para afirmar conclusão de negócio.
- Comandos e eventos tipados/versionados, correlação e causalidade, idempotência contextual, concorrência controlada, estados persistentes, reconciliação e rastreabilidade.
- Autorização vigente e aprovações válidas conferidas nas fronteiras protegidas; IA apenas sugere, nunca autoconcede permissões, poderes ou confirmações.
- Reutilizar motores e serviços centrais: Identidade e Segurança; Gatilhos, Condições e Ações; Construtor de Fluxos; Eventos; Datas e Prazos; Tarefas; Aprovações; Filas; Arquivos; COM; Analytics; Auditoria Geral.
- Nenhum SQL arbitrário, scripts/código livre de clientes, URLs irrestritas, acesso direto aos bancos de negócio ou superprivilégios ao n8n.
- Não confundir dados estruturados obrigatórios de operação/auditoria com franquias de arquivos. Preservar CRM Básico e trial sem API pública, com integrações nativas autorizadas.

## Complemento de rastreabilidade P0 (sem nova aprovação)
- Evidências especializadas correlacionam comando, tentativa, evento, autorização, aprovação, origem, resultado técnico, resultado oficial, compensação, investigação, exportação e atividade da própria auditoria, preservando **Auditoria Geral como única fonte oficial**.
- **Retenção de logs técnicos da IN-162** é categorizada (debug 7 dias, operações/traces 30 dias, métricas 90 dias, auditoria técnica de filas 90 dias, incidentes críticos/segurança 180 dias, com ressalvas). Esses prazos não definem automaticamente retenção de registros oficiais de contratos, FIN ou demais atos de negócio.
- Consultas/exportações exigem identidade, finalidade, escopo, RBAC/MFA aplicáveis, minimização e isolamento; IA interpreta evidências, não as inventa ou altera.
- Divergências identificadas pela auditoria devem ser encaminhadas ao módulo proprietário/INT-10; INT-12 não executa reconciliação de efeitos de negócio unilateralmente.
- [Conferência P0 INT-09 a INT-12](P0-REV-INT09-12-conferencia-cruzada.md). Taxonomia jurídica/contratual por categoria e fonte integral ainda pendentes.

## Governança e próximo passo
- Rastrear cada requisito à decisão original, responsável, versão, contrato, invariantes INV e cenário HT em [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- Não presumir que esta síntese reproduz palavra por palavra o texto integral da aprovação. Conferir literalidade, lacunas, limites, prazos e exceções no item P0-DOC da [matriz P0](INT-P0-fechamento-documental.md).
- As decisões já aprovadas prevalecem sobre esta redação sintética; alterações exigem deliberação expressa.
