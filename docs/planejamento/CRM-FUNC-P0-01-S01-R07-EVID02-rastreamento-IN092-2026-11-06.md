# FUNC-P0-01/S01-R07 — EVID02: busca rastreável por IN-092 (06/11/2026)

**Tipo:** inventário de busca e de pendência documental. **Não equivale à inexistência histórica da IN-092 nem à homologação de sua regra.**

## Escopo verificado

- Consulta das árvores GitHub `git/trees/<branch>?recursive=1` no repositório `rafhael100/imagine-crm` para as **15 branches atuais enumeradas**: `main`, `docs/integracao-site-crm-basico`, `docs/especificacao-inicial-2026-10-31`, `docs/func-p0-01-s01-r01-r02-2026-11-06`, `docs/core02-d06-ratificacao-d07-pauta-2026-11-05`, `dev/fast-track-crm-2026-11-04` e `codex/f1-02` até `codex/f1-10` (nomes completos recuperados por catálogo de branches).
- As árvores consultadas foram retornadas **sem truncamento**. Não foi encontrado **caminho de arquivo contendo `IN-092` ou `092`**.
- Consulta adicional à árvore `main` do repositório `rafhael100/imagine-portal` também não encontrou caminho `IN-092` nem `092`.
- Busca textual de código por `IN-092` na `main` do CRM não retornou resultado pelo mecanismo disponível. Isso é uma limitação do mecanismo de busca, **não evidência de ausência de menções**: a [REV-02](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), efetivamente lida, **menciona IN-092** como fonte histórica de divergência.
- A busca **não percorreu todo o histórico Git, PRs antigos fechados nem todos os outros repositórios ou arquivos pessoais**. Não afirmar que a IN-092 nunca existiu.

## Conteúdo material verificável independentemente da IN-092

- [IN-120](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/IN-120-encerramento-cortesia-continuidade-conta.md), blob `6494eb524aef751211241b6967853fd71184a4cf`: suspensão não é cancelamento; 120 dias de retenção após cancelamento formal da própria conta CRM.
- [COM-09](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md), blob `a8b6d8d71c0ae882cf4336326c0647aee34823e8`: 30 dias desde cancelamento formal do último vínculo elegível, operação normal no cancelamento comum e restrita quando proveniente de inadimplência; 120 dias da retenção CRM só após cancelamento formal do CRM.
- [CRM-REV-02](https://github.com/rafhael100/imagine-crm/blob/main/docs/planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), blob `dffa14e7f4e3e9557614f4522389430e42c30b43`: divergência REV02-D04 identifica IN-092 como texto anterior a conciliar; REV02-D01..12 e P01..12 continuam abertas.

## Situação e próximo passo específico

**IN-092 = REFERÊNCIA HISTÓRICA NÃO RECUPERADA COMO ARQUIVO INDEPENDENTE NAS ÁRVORES CONSULTADAS.** Não criar substituto com conteúdo presumido. Se surgir caminho/commit/arquivo, comparar texto integral e versão com IN-120/COM-09 e registrar divergência. **GAP-05 e REV02-D04 permanecem ABERTOS**, sem alteração de regras legais/comerciais. Nenhum código/ALLOW/SQL/teste/deploy foi autorizado ou executado.