# F3-AIA — teste de concorrência real de nonces entre sessões PostgreSQL

**14/11/2026 · prova no runner efêmero, sem endpoint/IAM real · NO-GO.**

A [PR CRM #584](https://github.com/rafhael100/imagine-crm/pull/584) verificava a restrição de unicidade de `(issuer,audience,nonce)` usando duas conexões **em sequência**. A extensão desta etapa testa concorrência efetiva: uma sessão abre transação e insere um nonce; outra envia simultaneamente uma inserção `ON CONFLICT DO NOTHING RETURNING nonce` via `pg_send_query_params` antes que a primeira finalize. A verificação consulta `pg_stat_activity` até observar a segunda sessão aguardando um lock do PostgreSQL. Só depois libera a primeira transação.

Executa dois cenários: **COMMIT** da primeira sessão implica zero linhas concedidas à segunda; **ROLLBACK** libera a segunda inserção e grava um único registro com o tenant da sessão vencedora. A prova exige PIDs distintos, estado `Lock`, timeout de lock/statement e conteúdo resultante. Despacha duas conexões do driver `pgsql` nativo em PHP e não envolve threads simuladas. Falhas continuam reprovando o job PostgreSQL, sem autorizar acesso.

**Limites/salvaguardas:** restrito por `setUp()` a GitHub Actions do repositório correto, banco PostgreSQL descartável `imagine_crm_ci`/usuário `postgres`/loopback e credenciais públicas de CI. A tabela é criada e removida por teste, sem migrations. O cenário não comprova throughput, retomada de crash, distribuição entre servidores, expiração/retenção, filas, revogação ou autenticidade de serviço. O script não é conectado ao `AiaHmacFixturePreflight` real e não oferece endpoints.

[CRM #57](https://github.com/rafhael100/imagine-crm/pull/57) continua OPEN/DRAFT, **14 gates, 7 decisões, 13 cenários reais pendentes; NO-GO**. `main`, VPS, dados pessoais e produção não alterados.
