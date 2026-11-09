# CRM Imagine — F3-16 — Repetição de recibos: HMAC + marcador persistente no PostgreSQL (laboratório)

**Data:** 08/11/2026 (America/Sao_Paulo). **Status:** implementação de teste controlado, sem serviço real. Vinculado à [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), [Issue #27 SEG/DBA](https://github.com/rafhael100/imagine-crm/issues/27), F3-13, F3-15 e ao [PR #49 — F3-14](https://github.com/rafhael100/imagine-crm/pull/49) ainda **DRAFT, sem homologação**.

## Hipótese

Uma assinatura HMAC-SHA256 íntegra **não impede replay**, pois os mesmos bytes passam novamente na validação. Um marcador de nonce único e durável **pode evitar uma segunda inserção da mesma chave dentro da base que o contém**; isso, isoladamente, não impede que um envio lógico duplicado seja apresentado com *outro nonce*, nem oferece coordenação entre duas APIs em bancos **independentes**.

F3-16 adiciona quatro casos ao arquivo `tests/Feature/PostgresLeadIdempotencyBoundaryTest.php`, usando exclusivamente a tabela **`crm_lab_async_ledger` já existente**, no contêiner efêmero PostgreSQL 16 do GitHub Actions, e conexões restritas `crm_async_ci`. **Não há ledger de replay de leads em produto, SQL novo, migration, job, segredo real ou API de entrada.**

## Casos adicionados

| Caso | Evidência sintética esperada | Não prova |
|---|---|---|
| HMAC correto apresentado duas vezes | Ambas as verificações de laboratório retornam `LAB_MAC_MATCH_UNTRUSTED`; a chave UUID usada como `idempotency_key` da tabela aceita 1 inserção e ignora o segundo replay no **mesmo tenant** | Origem autenticada, garantia de não duplicação entre APIs com bancos independentes, CRM entregue |
| Reenvio depois do `expires_at` ilustrativo | O índice único ainda bloqueia a mesma chave enquanto o registro persistir; o timestamp não apaga nem concede novo uso | Política definitiva de TTL, retenção legal ou limpeza da fila real |
| Mesma `digest`, nonces diferentes | A tabela aceita duas operações distintas. A chave única de nonce **não substitui idempotência por `(tenant_id, submission_id)`** | Deduplicação de pessoa ou de entrega CRM |
| Receptor tenta apagar marcador para refazer envio | Papel limitado recebe `42501` e a restrição de chave continua bloqueando um novo registro | Revogação de privilégios de owner/admin, segurança de produção |

Os seis testes PostgreSQL F3-13 existentes permanecem, incluindo concorrência física entre duas conexões com `55P03`, rollback, RLS, mesma chave em tenants diferentes e rejeição de escrita cruzada `42501`.

## Garantias e limites

1. **Duas APIs independentes:** marcadores únicos em bases locais separadas não garantem replay global. É necessária idempotência final atômica no CRM, reconciliação das fontes e política de tratamento de falhas e partição de rede.
2. **Mesmo nonce x mesmo envio:** nonce e identificador estável do envio lógico são conceitos diferentes. O nonce identifica uma tentativa/recibo e deve ter escopo apropriado; a submissão estável sustenta idempotência de negócio, inclusive se um novo nonce for emitido.
3. **Chaves e assinaturas:** o exemplo usa uma string sintética fixa em um teste; não é credential operacional nem recomenda segredo embutido no repositório. Gestão/rotação por KMS e assinatura de corpo integral ficam bloqueadas até homologação.
4. **RLS:** `app.tenant_id` configurável pela sessão é apenas filtro contextual. Não comprova identidade, ownership, membership ou autorização de serviço.
5. **Descarte e restauração:** a limpeza de registros sintéticos ao final dos testes acontece pela fixture admin somente na base descartável CI; não existe decisão automatizada de apagar recibos reais, nem recuperação de tombstones após restore.
6. **Sem dados pessoais:** nenhum payload, nome, e-mail, telefone ou cliente real é inserido. A tabela genérica possui campos técnicos e estados fechados por CHECK; o teste não cria nenhum dado funcional do CRM.
7. **Gates:** 67 CORE, SEG/DBA Issue #27, AUT/INT/LGPD e o PR documental F3-14 continuam abertos. Não promover a `main`, VPS ou produção sem autorização crítica específica e parecer independente.

## Requisitos para uma versão operacional

- Ledger dedicado para `tenant+submission`, vínculo/site/formulário/destino pinados, e **ledger de nonces** com índice único e transações na fonte autoritativa.
- Protocolo versão e autenticação da API emissora, integridade de payload, key IDs, rotação e revogação, validade temporal, correlação minimizada sem PII.
- Reserva atômica real com tratamento de contenção, lease/fencing e revalidação de tenant/mandatos antes dos efeitos de negócio.
- Evidência confiável do recebimento durável na API e da confirmação definitiva no CRM, mesmo sob timeout, failover/failback e partição de rede.
- Estratégia de retenção 3+15 dias, 24 horas para limpeza de cópias já entregues, auditoria técnica 90 dias e recuperação de backups — sem reiniciar relógio por replay.
- Revisão externa/independente SEG/DBA **antes de migrations operacionais**.

**Nota:** o teste de PostgreSQL valida apenas comportamentos da tabela sintética CI preexistente. Não é um serviço anti-replay pronto, nem certificação de segurança do ecossistema Imagine.
