# CRM Imagine — ENT-02 — Avisos de capacidade de anexos, sem upload nem autorização

**Data:** 09/11/2026. **Estado:** componente PHP puro, laboratório DEV somente com números fictícios. **Não é regra de autorização, upload ou cobrança.**

**Fontes:** [CRM-REV-02 — COM-R08 e COM-R09](../planejamento/CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) e [ENT-01](ENT-01-projecao-franquias-sem-direitos-2026-11-09.md). Essas fontes registram avisos de consumo de arquivos/anexos em **80% e 95%** e condição de **100%** para atenção ao limite. O armazenamento estruturado de leads/contatos **não** consome a mesma franquia de arquivos.

## Implementação

- `App\Core\Plans\AttachmentCapacityAdvisory` recebe dois **inteiros em bytes já medidos** de uma origem hipotética: limite e uso atual; ambos não negativos. Nada é calculado a partir de assinaturas, CNPJ, tenants, recursos ou preços.
- `AttachmentCapacityBand` retorna exclusivamente uma **etiqueta de informação**, sem disparar avisos: `unavailable` (limite 0, uso 0), `below_notice`, `notice_80`, `notice_95`, `at_limit` ou `over_limit`.
- O corte de 80/95 usa **arredondamento para cima de bytes inteiros**: `ceil(limite * percentual / 100)`. O cálculo decompõe quociente/resto para evitar overflow de `PHP_INT_MAX` e uso de `float`.
- As operações `remainingBytes()` e `excessBytes()` fornecem exclusivamente saldos descritivos, sem apagar conteúdo nem dizer se uma nova operação pode prosseguir.
- O limite `0` **não significa ilimitado**. Se `limit=0, usage=0` é `unavailable`; `limit=0, usage>0` é `over_limit`. Conteúdo preexistente que já excede o limite **permanece representado**, não é apagado.
- Testes unitários cobrem precisamente antes/nos cortes de 80, 95 e 100%, quota zero, limite 101 (arredondamento), `PHP_INT_MAX`, valores negativos e proibição de métodos de upload/ALLOW/cobrança.

## Limites e pendências

**A faixa informativa não concede direitos**. Um resultado `below_notice`, `notice_80` ou `notice_95` **não** permite upload. Uma camada de autorização real exigiria usuário e organização autenticados, membership, recurso/ação/campo, entitlement do plano aprovado, quota persistida e controle transacional para concorrência, regras de arquivos de **até 5 MB** e bytes/MB/MiB formalizados. **A classe não escolhe unidade comercial, não normaliza limites, não resolve planos ou trial e não implementa a regra efetiva de bloqueio de novos uploads a 100%.**

`notice_80` e `notice_95` **não enviam notificações**; a publicação/canal e idempotência dos avisos dependem de regras posteriores. A classe não lê/escreve banco, envia e-mail, chama API, realiza importação, cria/alterar clientes nem modifica código de produto.

[Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27), [PR #49 F3-14](https://github.com/rafhael100/imagine-crm/pull/49) e os 67 gates CORE, AUT, FIN/CON, INT e LGPD permanecem em aberto. **Sem `ALLOW`, novas migrations, `main`, VPS, produção ou despesas.**
