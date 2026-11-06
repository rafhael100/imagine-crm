# CRM Imagine — CORE07-D08: revogação e continuidade após alteração ou desligamento — proposta v0.1

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **PROPOSTA DOCUMENTAL NÃO APROVADA — CORE07-D08 INTEGRAL ABERTA**.  
**Objetivo:** reconciliar a revogação da participação de uma pessoa numa empresa com trabalhos pendentes, automações, exportações e histórico. Não escolher política SQL ou TTL, não executar testes e não autorizar programação.

## 1. Limites e fontes preexistentes

- CORE07-D01..D03: um usuário global pode ter vínculos distintos com diferentes organizações; desativar membership no tenant A não extingue identidade nem direitos legítimos em B; preservar autoria e histórico.
- [CORE07-D07 Modelo B](CRM-DEC-ratificacao-explicita-CORE07-D07-modelo-B-redistribuicao-hibrida-2026-11-06.md): redistribuição automática apenas onde o módulo proprietário já a permite; demais casos, revisão humana autorizada.
- CORE02-D08: aplicar autorização no servidor em todas as superfícies, com defesa Laravel/RLS e revalidação de novos efeitos após revogação. CORE03: identidade técnica/autoridade causal de jobs; fatos já confirmados legitimamente não são desfeitos apenas pela saída do funcionário.
- CORE04/MFA/sessões, CORE05/suporte, CORE06/auditoria, CORE08/plano e CORE09/identidades técnicas mantêm seus contratos independentes.
- Importação, exportação, backup e portabilidade legítimas são direitos do titular/organização, mas um **ex-funcionário não herda** esses direitos por ter agendado operação anteriormente. Distinguir autoridade da empresa de autoridade pessoal que originou uma exportação.

## 2. Três alternativas para classificar tarefas pendentes — NÃO APROVADAS

| Modelo | Após revogar membership/perfil/sessão | Benefício | Risco |
|---|---|---|---|
| **A — Cancelamento indiscriminado** | Interromper todos os jobs, serviços e automações relacionados a qualquer tarefa iniciada pela pessoa | Conservador para acesso pessoal | Pode interromper rotinas oficialmente autorizadas pela organização e causar perda de serviço |
| **B — Revogação seletiva com revalidação por autoridade** | Negar novos efeitos pessoais que dependam da autoridade revogada; revalidar jobs na execução e antes da entrega; manter rotinas técnicas autônomas que conservem fundamento oficial | Protege acesso sem quebrar obrigações independentes | Requer classificar claramente principal causal, executor, proprietário e autoridade atual |
| **C — Finalização integral de operações iniciadas** | Deixar tarefas pessoais já enfileiradas completar sem revalidar | Menor impacto aparente em filas | Pode liberar exportações e alterações após desligamento sem direito vigente |

**Recomendação técnica para manifestação futura: Modelo B**, que concretiza diretrizes já aprovadas de CORE03/CORE02 sem ampliar direitos. Esta escolha **não** define períodos de cache ou se cada rotina específica pode continuar; isso pertence ao proprietário e a Segurança.

## 3. Diretrizes candidatas associadas a B — NÃO APROVADAS

### D08-A — Revogação vinculada ao tenant, sem efeitos globais implícitos

Quando membership no tenant A perder validade, impedir novas consultas, edições, exports, downloads, atribuições e ações de agente que exijam aquela autoridade. Não derrubar participação legítima no tenant B nem eliminar identidade global por analogia. Reatribuir recursos apenas nos fluxos autorizados do D07.

### D08-B — Trabalhos diferidos exigem revisão da autoridade efetiva

Definir para jobs/filas/exports/arquivos se a operação requer usuário humano ainda ativo, mandato, identidade técnica autônoma ou novo aceite de gestor. Revalidar nos marcos de novos efeitos protegidos, especialmente **antes de disponibilizar artefato/exportação**. Não permitir execução por token persistente de usuário desligado.

### D08-C — Continuidade legítima e estados de exceção

Manter serviço autônomo com autorização empresarial válida quando desvinculado da sessão revogada, sem criar poderes para n8n, IA ou suporte. Preservar atos comerciais oficialmente confirmados e registros históricos; falhas/paradas devem gerar pendência diagnóstica e reprocessamento somente sob regras do proprietário, não rollback geral.

### D08-D — Auditoria, UX e critérios futuros negativos

Exigir rastreabilidade de ator, tenant, escopo, vínculo, versão da política, solicitação/execução/disponibilização, revogação e resultado, com registros minimizados. Provar futuramente que: (i) export pendente não é entregue a ex-funcionário; (ii) tenant B permanece legítimo; (iii) serviço independente válido continua; (iv) URL de arquivo antiga não burla revogação; (v) fila atrasada não restaura poder; (vi) IA/operador de suporte não se eleva. Nenhum teste executado por este documento.

## 4. Lacunas preservadas

- Definir catálogo por recurso/ação/campo, autoridade causal e owner por módulo; quais serviços têm fundamento independente; cache e propagação; reprocessamento idempotente; sessão global versus vínculo local; invalidação de arquivo temporário; mensagens ao usuário; SLA/risco.
- CORE07-D08 e D04..D10 integrais permanecem **ABERTAS**, assim como CORE02, CORE01, CORE03..10, SEG-01/02, INT P0/G01..G06, ARQ/AGT, FIN/CON, LGPD e gate global. Os 67 IDs técnicos CORE não são fechados por aprovação de subdiretrizes.
- Esta pauta não autoriza código, testes executáveis, credenciais, operação real com clientes, merge, novo serviço, preço, contratação, `main`, branch Codex, VPS ou produção.

**Próxima deliberação solicitada:** ratificar ou não **Modelo B de revogação seletiva**, com D08-A/B/C/D apenas como diretrizes documentais preliminares, preservadas todas as dependências de homologação.
