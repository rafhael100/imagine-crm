# CRM F3-44 — Teste de falhas reais PostgreSQL e rollback antes de recibo

**Escopo controlado:** PHP 8.3/8.4 e PostgreSQL 16 exclusivo de GitHub Actions. Dados UUID inteiramente fictícios e código em `tests/Labs/F3_44/`, sem endpoint Laravel, migração operacional, credencial real, tráfego externo ou processamento de contatos.

## Objetivo técnico

F3-40/41 executam o receptor experimental do CRM; F3-42 validou código real de ambos os repositórios em duas bases separadas; F3-43 demonstrou concorrência multiprocesso de tentativas assinadas. Faltava exercitar especificamente o **momento de falha de gravação SQL**, verificando que um nonce inserido antes da linha de destino nunca sobrevivesse a um erro de persistência nem gerasse confirmação indevida.

A F3-44 cria um gatilho **temporário e exclusivamente no banco `imagine_crm_handoff_ci`** que injeta duas falhas determinísticas para duas referências sintéticas de submissão:

- **Exceção SQL provocada:** um `BEFORE INSERT` em `crm_portal_handoff_ci.metadata_receipts` dispara erro controlado. A operação CTE do receptor F3-40 já tentou gravar `request_nonces` na mesma transação. O teste exige que **ambas** as tabelas permaneçam sem a submissão, que não haja transação PDO aberta, que o resultado seja `LAB_DATABASE_UNVERIFIED` e não inclua recibo.
- **Timeout real de instrução:** outro ID de submissão faz o PostgreSQL executar `pg_sleep` dentro do gatilho, enquanto `statement_timeout` está configurado em 110 ms. O servidor PostgreSQL cancela a instrução durante a gravação. O teste exige **rollback do nonce e do metadado**, fim da transação PDO, ausência de recibo e confirmação negativa por leitura independente do próprio banco de CI.

Depois dos dois cenários, o teste executa lookup autenticado por HMAC de domínio exclusivo, que retorna `LAB_RECORD_NOT_VERIFIED` e nunca cria recibo para registro inexistente. Apenas **no teste descartável**, após remover o gatilho e provar ausência física da linha, repete explicitamente a chamada assinada e verifica que a transação correta produz uma linha e um nonce. Não há política operacional de retry implementada.

A matriz também exige: nonce já consumido continua rejeitado; alteração de destino originalmente registrado provoca `PINNED_REPLAY_CONFLICT` com rollback do novo nonce; alteração de variável de banco no processo não consegue contornar a barreira `DISPOSABLE_CI_REQUIRED`.

## Arquivos

- `tests/Labs/F3_44/fault_injection_pg16.php`: testes PHP contra transações e gatilhos PostgreSQL reais; exige GitHub-hosted, flags `CRM_F340_LAB=1` e `CRM_F344_FAULT_LAB=1`, banco/host/senha estritamente de fixture; valida somente UUIDs técnicos e indicadores de resultado.
- `.github/workflows/ci-f3-44-pg-fault-injection.yml`: CI PHP 8.3 e PHP 8.4 com banco PostgreSQL 16 e credenciais descartáveis, regressão F3-40 e tripwire para impedir importação de F3-44 em `app`, `routes`, `bootstrap` ou migrations.

## Limites e riscos abertos

Este teste verifica **atomicidade do receptor experimental sob falhas locais do PostgreSQL**. Não testa rede real entre datacenters, falha após commit remoto, persistência de PII, replicação, backups, serviço IAM, KMS ou qualificação jurídica de consentimento. Uma chamada com erro ou timeout em uma integração remota pode ter sido gravada pela outra ponta; o próximo passo continua sendo conciliação autenticada e bloqueio de reenvio diante de resultado desconhecido. O F122 do Portal já testa a retenção local da incerteza em ambiente isolado, mas não substitui um worker operacional.

O repositório mantém o gate de rotas: **só a rota de saúde `/up` está autorizada**. O receptor operacional com identidade de serviço, autorização por empresa, guarda de credenciais, privacidade, payload consentido e persistência definitiva exige contrato, parecer independente SEG/DBA/LGPD e staging. A F70 do Comercial permanece bloqueada. Nenhum `main`, VPS, DNS, produção, despesa ou dado pessoal real foi modificado.

**Evidência de aceite:** PR draft filial da F3-43; considerar tecnicamente validada somente após workflows verdes na SHA final, não por testes anteriores.
