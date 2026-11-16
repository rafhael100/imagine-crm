# CRM F245 — origem de chave pública e vínculo de empresa controlados pelo CRM (laboratório)

**15/11/2026 — DRAFT / NO-GO / NO-MERGE / NO-DEPLOY / SEM NOVOS GASTOS**

## O que muda

A sequência F241–F244 aprovou os contratos de HTTP 404, Ed25519 original Agents, escopo de laboratório e PostgreSQL16 com nonce único. Porém a chave pública e a empresa ainda eram injetadas pelo próprio teste no diagnóstico F243.

F245 acrescenta `AgentsPrivateOwnedKeyringLab`, que consulta **somente uma tabela descartável criada na CI** para obter o par `key_id → tenant_ref + public_key_base64`. O identificador presente no envelope assinado é apenas um seletor, nunca prova de empresa. A chave pública não vem de cabeçalhos, token, corpo do pedido ou argumento do cliente. `key_id` é globalmente único no banco temporário (PRIMARY KEY). Os registros exigem issuer/audience/subject canônicos, janela de tempo, revisão positiva, `enabled=true`, `revoked_at IS NULL`, e formato Ed25519 estrito.

O verificador original Agents recebe a chave e o tenant **resolvidos pela tabela CRM**, depois compara a assinatura, os bytes canônicos e o escopo ficcional; `AgentsPrivatePgDiagnostic` só então pode registrar o nonce único no PG16 de laboratório. **O adaptador F248 retorna sempre HTTP 404**, independentemente da avaliação. A classe não é vinculada ao container Laravel e nenhuma rota nova é criada.

## Casos negativos executáveis

`AgentsPrivateOwnedKeyringTest` cobre as empresas fictícias A e B, replay após consulta de chave, assinatura A tentando alegar tenant B, `key_id` inexistente, revogação e desativação de chave, rotação para outra chave do mesmo tenant, indisponibilidade do registro PostgreSQL e cabeçalhos de tenant externos. A coluna de chave armazena **apenas chave pública de teste derivada de seed pública determinística**, jamais segredo real.

### Limites essenciais

Este ensaio **não comprova**: role de menor privilégio para o keyring (usa o superusuário apenas no banco efêmero GitHub CI), FORCED RLS operacional, associação com cadastro real de empresas, autorização de humano, dono de lead e campos, provisão/rotação segura de chaves privadas, segurança de várias instâncias, revogação instantânea entre transações, mTLS, LGPD ou disponibilidade em staging. Essas continuam tarefas próprias dos gates independentes SEG/DBA/IAM/CORE/Privacidade, não podendo ser contornadas por CI verde.

A [CRM PR #57](https://github.com/rafhael100/imagine-crm/pull/57) mantém **14 gates, 7 decisões e 13 cenários reais não homologados**. Não habilitar rede, credenciais, dados reais, produção, cobrança, merge automático ou VPS.
