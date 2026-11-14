# F3-AIA — componente SQL descartável com bloqueio explícito de produção

**14/11/2026 · DEV/CI, NO-GO.**

A PR CRM #592 provou competição simultânea por chave de nonce no PostgreSQL 16 descartável, inclusive COMMIT e ROLLBACK, mas o código de `INSERT ... ON CONFLICT DO NOTHING` estava somente dentro da classe de testes. Esta entrega extrai a operação para `app/Support/Synthetic/AiaPgNonceCandidateLedger.php`, um **adaptador real PHP mas estritamente limitado ao laboratório**, sem qualquer rota, service provider, migration ou vínculo à autenticação do CRM.

A classe injeta uma conexão Laravel em vez de criar conexões silenciosamente; valida **antes de consultar ou gravar** a identidade do runner, banco `imagine_crm_ci`, host loopback, usuário, driver, referências `SYNTHETIC_...` e timestamp. Verifica adicionalmente `current_database/current_user` no PostgreSQL. Duplicidades, SQL indisponível, schema ausente, entradas inválidas e qualquer alteração do ambiente devolvem `false` (fail closed). O método nunca retorna concessão de acesso ao CRM, apenas sucesso de inserção na tabela transitória.

Os testes de duas conexões, rollback e backend indisponível passam a exercitar a classe **original do CRM**, e foi acrescentado o caso de oito entradas inválidas sem escrita. Os dois ensaios de concorrência bloqueante da PR #592 continuam testando diretamente o recurso físico do PostgreSQL. O runner SQLite segue pulando a prova; o GitHub Actions PostgreSQL executa os testes reais.

**Não é um nonce store operacional:** usa tabela temporária criada no `setUp`, exige ambiente de CI, não possui limpeza/TTL/retention, credenciais, IAM, garantias interservidor, RBAC, observabilidade ou integração a `AiaHmacFixturePreflight`. CRM PR #57 continua OPEN/DRAFT; 14 gates, 7 decisões e 13 cenários reais permanecem NO-GO. `main`, VPS, clientes e produção intactos.
