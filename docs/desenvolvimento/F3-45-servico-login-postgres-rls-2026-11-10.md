# CRM F3-45 — PostgreSQL 16: identidade de conexão restrita e isolamento real entre empresas

**Escopo:** exclusivamente GitHub Actions descartável, PHP 8.3/8.4, UUIDs sintéticos, sem rotas Laravel, credenciais operacionais, dados pessoais, lead real, staging, merge ou deploy.

## Evolução em relação à F3-40/F3-44

Nos laboratórios iniciais, as transações PostgreSQL do CRM eram executadas com um usuário administrador de CI que invocava `SET LOCAL ROLE` para assumir um papel sem privilégios. Isso comprovava políticas RLS e transações, mas não demonstrava que a própria **conexão física do serviço** fosse de privilégio mínimo.

A F3-45 testa diretamente dois logins PostgreSQL distintos, sem superusuário, sem `BYPASSRLS`, sem `CREATEROLE` e sem `CREATEDB`: `crm_f345_service_a` e `crm_f345_service_b`. São identidades **fictícias de CI** que herdam somente os privilégios dos papéis por tenant `crm_f340_receiver_a/b` criados pela fixture F3-40. A associação no PostgreSQL 16 usa `INHERIT TRUE`, `SET FALSE`, `ADMIN FALSE`, para que cada login obtenha apenas SELECT/INSERT das tabelas técnicas já definidas para sua empresa, **sem poder trocar de papel**. Os usuários de teste têm senhas fictícias constantes no CI, que **não são desenho de secret management nem credenciais de produção**.

O processo PHP abre cada conexão diretamente como seu respectivo login. Não usa `SET ROLE`, usuário administrador, política baseada em `app.tenant_id` ou claims do navegador para operar.

## Verificações executáveis

- O banco esperado precisa ser `imagine_crm_handoff_ci`, recém-criado no contêiner PostgreSQL 16; os objetos devem ter `FORCE ROW LEVEL SECURITY`.
- Cada sessão identifica `current_user=session_user` como seu login, com `rolsuper=false`, `rolbypassrls=false`, `rolcreaterole=false` e `rolcreatedb=false`.
- O login A grava atomicamente um nonce e uma submissão técnica de A. O login B grava o mesmo tipo de metadados somente para B. Ambos enxergam suas próprias linhas e não conseguem ler o outro tenant, inclusive na tabela de nonces.
- A PK física impede regravar nonce já utilizado; nonce novo para mesma submissão não cria segundo registro lógico.
- Inserções cruzadas A→B e B→A falham por RLS e deixam o PDO fora de transação. As tentativas de `UPDATE`, `DELETE`, `TRUNCATE`, `ALTER TABLE ... DISABLE ROW LEVEL SECURITY`, `DROP TABLE`, `SET ROLE postgres` e `SET ROLE` para o papel de privilégio herdado devem falhar com erro de permissão `42501`.
- Alterar manualmente `app.tenant_id` dentro da sessão não confere acesso a dados da outra empresa. A verificação física final espera somente os envios legítimos.
- O relatório final de testes nunca significa `realCrmPersisted=true` ou entrega de lead: as tabelas só armazenam UUIDs de teste, sem contato, mensagem, consentimento ou recibo operacional.

## Arquivos

- `tests/Labs/F3_45/service_login_pg16.sql`: criação de **dois logins fictícios** em CI e associação restrita aos papéis da F3-40. Nunca incluir em migrations operacionais.
- `tests/Labs/F3_45/test_service_logins_pg16.php`: prova de acesso físico, insert/SELECT idempotentes, RLS multitenant, privilégios negativos e falsificação de GUC.
- `.github/workflows/ci-f3-45-service-login-rls.yml`: PostgreSQL 16 efêmero, matriz PHP 8.3/8.4, execução direta com credenciais restritas, sem qualquer referência ao código de runtime.

## Limites que permanecem abertos

**O CI não fornece identidade verdadeira de serviço, nem valida membership ou autorização de uma empresa real.** Um login de banco é diferente de autenticação de serviço Portal→CRM; o vínculo entre serviço autorizado, organização, site, formulário e destino ainda precisa de verificação independente. Além disso, não existe payload de lead, consentimento LGPD, cifragem, auditoria, observabilidade, backup, integração HTTP operacional nem recibo de produção. Esta etapa só cobre parte do limite de privilégios na camada de banco.

As políticas CORE/SEG/DBA permanecem bloqueadas, assim como a F70 do Comercial. Os testes precisam estar verdes no commit final da PR antes de considerar a F3-45 tecnicamente validada. Não houve mudança na `main`, VPS, DNS, produção, despesas ou dados pessoais reais.
