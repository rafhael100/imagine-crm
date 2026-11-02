# INT-09 — Orquestração Inteligente de Operações Externas e Processos Multissistema

**Situação funcional:** APROVADA pelo responsável no planejamento do CRM Imagine.
**Situação documental:** síntese consolidada nesta etapa P0; conferência literal contra a deliberação original e revisão cruzada ainda pendentes (ver INT-P0).
**Fase:** documentação apenas; sem programação, implantação, execução de testes ou alterações de produção.
**Proprietário da competência:** Modelos, instâncias, etapas, dependências, estados, coordenação e planos de compensação.
**Dependências e referências:** INT-01 a INT-08, Motores Centrais, Aprovações, Tarefas, Eventos, Filas.
**Cenários propostos de homologação:** HT-08, HT-09, HT-10, HT-11, HT-15 (não executados).
**Data desta consolidação:** 2026-11-02.

## Escopo funcional aprovado — síntese
- Planejamento, modelagem, homologação, aprovação, programação, execução, monitoramento, cancelamento, reconciliação e encerramento fundamentado de processos multissistema.
- Modelos tipados, homologados e versionados; instâncias com identidade estável, tenant, versões, contexto, comandos, tentativas, correlação e histórico próprios, sem mutação silenciosa das instâncias ativas.
- Execução sequencial, condicional, paralela, convergente, por eventos/prazos, com subprocessos e tarefas humanas; prevenção de ciclos inválidos, iterações limitadas e convergência apenas com confirmações efetivas.
- Estados de modelo/instância/etapa/comando/compensação separados; nenhum HTTP, webhook ou n8n concluído equivale a confirmação de negócio.
- Comandos com ID estável, versão, causalidade, idempotência, estado esperado e tentativa; resultados desconhecidos exigem reconciliação antes de retentativas arriscadas.
- Compensações condicionadas a elegibilidade, reversibilidade, aprovação, execução e confirmação pelo módulo competente; sem rollback universal.
- Contingências com provedores alternativos somente homologados, compatíveis e autorizados; sem troca crítica por iniciativa de IA.
- Sem acesso livre a segredos, SQL, scripts, URLs irrestritas, bancos de tenants; IA apenas recomenda.

## Competências reservadas e exclusões
- INT-01 a INT-08 mantêm suas competências; INT-10 monitora/recupera; INT-11 homologa; INT-12 audita.
- FIN, CON, CAT, VEN, AGD, ATD, COM, PVD, RET, MKT, CMS e Identidade e Segurança preservam todas as decisões proprietárias.
- Motor Central de Datas e Prazos diferencia timeout técnico de vencimento de negócio; Aprovações/Tarefas/Eventos apenas pelos serviços centrais.
- Cancelamento técnico não cancela automaticamente contratos, pagamentos, reservas ou efeitos já confirmados.

## Regras transversais obrigatórias
- Tenant/conta, identidade, recurso e finalidade verificados em cada ato protegido; isolamento entre empresas e entre processos internos da Imagine e processos dos tenants.
- Competência e confirmação oficial permanecem no módulo proprietário. Transmissão, HTTP 2xx, workflow n8n concluído ou webhook recebido **não** bastam para afirmar conclusão de negócio.
- Comandos e eventos tipados/versionados, correlação e causalidade, idempotência contextual, concorrência controlada, estados persistentes, reconciliação e rastreabilidade.
- Autorização vigente e aprovações válidas conferidas nas fronteiras protegidas; IA apenas sugere, nunca autoconcede permissões, poderes ou confirmações.
- Reutilizar motores e serviços centrais: Identidade e Segurança; Gatilhos, Condições e Ações; Construtor de Fluxos; Eventos; Datas e Prazos; Tarefas; Aprovações; Filas; Arquivos; COM; Analytics; Auditoria Geral.
- Nenhum SQL arbitrário, scripts/código livre de clientes, URLs irrestritas, acesso direto aos bancos de negócio ou superprivilégios ao n8n.
- Não confundir dados estruturados obrigatórios de operação/auditoria com franquias de arquivos. Preservar CRM Básico e trial sem API pública, com integrações nativas autorizadas.

## Complemento de rastreabilidade P0 (sem nova aprovação)
- A deliberação detalhada distingue **modelo de processo**, **instância**, estado do **processo solicitado/autorizado/programado/iniciado/parcialmente concluído/suspenso/bloqueado/em compensação/reconciliado/concluído/encerrado com ressalvas**, estados da **etapa** (elegível/iniciada/pendente/confirmada/rejeitada/inconclusiva), do **comando** (preparado/enviado/aceito/aplicado/oficialmente confirmado), da **aprovação** (solicitada/concedida) e da **compensação** (planejada/solicitada/executada/verificada/parcial/inconclusiva). A modelagem formal e as transições ficam para especificação P0-CON, sem inventar transições.
- Convergência de paralelismo requer confirmação efetiva das dependências; nenhuma etapa obrigatória é dispensada por sucesso de etapa independente. Iterações somente com limites e critérios de parada, sem ciclos inválidos.
- Revalidar autorizações vigentes em início, retomada, antes de operações protegidas e compensações e em tarefas assíncronas. **Cancelamento técnico não altera retroativamente fatos de negócio confirmados**.
- As comunicações a clientes pertencem a COM; eventos, tarefas, prazos, aprovações e arquivos aos serviços centrais. A IA não reprocessa escritas inconclusivas nem executa compensação crítica autonomamente.
- [Conferência P0 INT-09 a INT-12](P0-REV-INT09-12-conferencia-cruzada.md). Cotejo integral da origem com referência imutável continua pendente.

## Governança e próximo passo
- Rastrear cada requisito à decisão original, responsável, versão, contrato, invariantes INV e cenário HT em [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- Não presumir que esta síntese reproduz palavra por palavra o texto integral da aprovação. Conferir literalidade, lacunas, limites, prazos e exceções no item P0-DOC da [matriz P0](INT-P0-fechamento-documental.md).
- As decisões já aprovadas prevalecem sobre esta redação sintética; alterações exigem deliberação expressa.
