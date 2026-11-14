# F3-AIA — PostgreSQL descartável: unicidade atômica do nonce entre conexões

**14/11/2026 — estudo executável no CI, ainda NO-GO operacional.**

A [CRM PR #583](https://github.com/rafhael100/imagine-crm/pull/583) comprovou que dois objetos `AiaFixtureNonceLedger` podem aceitar o mesmo nonce, pois o ledger atual fica apenas na memória de um worker. Este incremento experimenta **somente dentro da base PostgreSQL 16 descartável do GitHub Actions** uma alternativa para uma etapa futura: `INSERT ... ON CONFLICT (issuer,audience,nonce) DO NOTHING` respaldado por PRIMARY KEY não-deferrable.

O teste `tests/Feature/AiaPostgresAtomicNonceCandidateTest.php` cria e remove uma tabela própria com prefixo `aia_lab_`, sem migration ou alteração do esquema do produto. Exige identidade precisa do runner, banco `imagine_crm_ci`, usuário `postgres`, host loopback, role de teste e ambiente de testes antes de qualquer DDL. Duas conexões PostgreSQL independentes confirmam que apenas uma delas registra um nonce, mesmo após mudança de tenant declarado. Também verifica rollback, constraint física e falha segura quando a tabela desaparece.

**Limitações cruciais:** a prova observa dois processos de conexão em sequência, não mede competição temporal simultânea, latência, expiração, tombstones, rotação de chaves, crash-recovery ou retenção LGPD. O helper SQL está exclusivamente no teste; não existe implementação de serviço, migração operacional, nova permissão de banco nem wiring ao `AiaHmacFixturePreflight`. A CI SQLite registra SKIP intencional; a CI PostgreSQL executa os quatro testes de verdade e falha se o ambiente não for o banco descartável esperado.

**CRM #57 segue OPEN/DRAFT, 14 gates / 13 cenários reais pendentes, NO-GO.** Sem identidade real, API/HTTP, VPS, segredo, produção, cliente, `main` ou gasto adicional.
