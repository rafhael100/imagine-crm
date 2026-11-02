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

## Governança e próximo passo
- Rastrear cada requisito à decisão original, responsável, versão, contrato, invariantes INV e cenário HT em [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
- Não presumir que esta síntese reproduz palavra por palavra o texto integral da aprovação. Conferir literalidade, lacunas, limites, prazos e exceções no item P0-DOC da [matriz P0](INT-P0-fechamento-documental.md).
- As decisões já aprovadas prevalecem sobre esta redação sintética; alterações exigem deliberação expressa.
