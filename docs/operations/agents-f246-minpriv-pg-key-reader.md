# CRM F246 — leitura PostgreSQL 16 de chave pública com privilégio mínimo e FORCE RLS

**15/11/2026 — DRAFT / NO-GO / NO-MERGE / NO-DEPLOY**

## Posição na arquitetura

A F245 introduziu a origem controlada pelo CRM para a chave pública e vínculo sintético de empresa. Na F245, a leitura da tabela ainda utiliza superusuário **somente do banco efêmero de CI**. F246 acrescenta o caminho com uma **role PostgreSQL independente, nonowner, SELECT-only, NOBYPASSRLS, NOINHERIT**, chamada `crm_aia_f246_reader_ci`, mantendo o administrador limitado a criar e destruir dados sintéticos do teste.

O serviço de consulta `AgentsPrivateRestrictedKeyReaderLab` só aceita esta role exata e valida, **no servidor SQL**, o usuário corrente, database `imagine_crm_ci`, row_security=on, `ENABLE/FORCE ROW LEVEL SECURITY`, ausência de propriedade da tabela, ausência de superuser/BYPASSRLS/INHERIT, grants SELECT presente e INSERT/UPDATE/DELETE ausentes, e ausência de tenant GUC herdado. Se qualquer prova falhar, não tenta outra conexão.

A tabela registra apenas chaves públicas Ed25519 e vínculos de empresas **fictícias**. A política F246 concede leitura de **chaves públicas** ao papel de serviço, pois a empresa ainda precisa ser derivada a partir do `key_id` globalmente único — **isso não é RLS de leads ou autorização multiempresa**. A operação exige issuer/audience/subject imutáveis, chave com encoding base64 estrito, enabled/revoked/expiração e revisão positiva. O código original Agents é carregado em SHA imutável e verifica uma assinatura sintética com a chave retornada pelo SELECT restrito.

## Cobertura

`AgentsPrivateRestrictedKeyReaderTest` cria e destrói a role, tabela, grants e política **somente no banco temporário GitHub Actions**; recusa tentativa de UPDATE pela role, verifica identidades A/B, chave desconhecida, role superuser inesperada, GUC de tenant injetado, FORCE RLS removida, revogação, rotação, chave pública corrompida e indisponibilidade da tabela. A suite não abre API, não registra rota e não acessa registros de negócio.

## Limites

É uma prova de menor privilégio para **metadados de chave pública**, não um sistema operacional de identidade. Faltam vínculo com cadastro real CRM, política e revisão SEG/CORE/DBA/IAM, gestão de chave privada, revogação concorrente e atomicidade com nonce, mTLS, RLS de leads, titularidade e campos, LGPD e homologação na VPS. A resposta pública do agente continua sempre `DENIED_OR_NOT_FOUND` e os **14 gates, 7 decisões e 13 cenários reais da PR CRM #57 permanecem abertos**. Sem merge, deploy, custos adicionais ou clientes reais.
