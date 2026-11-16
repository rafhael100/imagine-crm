# CRM F246 — role exclusivamente SELECT com PostgreSQL FORCE RLS

**15/11/2026 · experimental em CI · NO-GO / NO-MERGE / NO-DEPLOY**

Avança a F245 com um peer PostgreSQL de privilégios mínimos, sem copiar segredos ou instalar serviços. A classe `AgentsPrivateRestrictedKeyringLab` somente consulta dados **sintéticos** de chave pública Ed25519 e empresa de uma tabela fictícia com `ENABLE ROW LEVEL SECURITY` e `FORCE ROW LEVEL SECURITY`. A role `crm_aia_keyreader_f246_ci` tem apenas `CONNECT`, `USAGE` e `SELECT` nessa tabela, com `NOSUPERUSER`, `NOINHERIT` e `NOBYPASSRLS`. Não pode executar INSERT, UPDATE nem DELETE, ler leads ou obter identidade humana.

O laboratório de PHP/Laravel exige GitHub Actions do repositório original, `APP_ENV=testing`, banco descartável `imagine_crm_ci`, host loopback e role exata. Confirma também `row_security_active` **na própria conexão restrita** antes de consultar chave; desativar RLS nega inclusive uma linha válida. Revogação, chave desconhecida, tenant trocado, assinatura inválida ou banco ausente resultam em negativa. A origem da empresa e da chave pública é exclusivamente o banco do CRM; claims/cabeçalhos não concedem autoridade.

A CI fixa o código Ed25519 original dos Agentes na SHA `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5`, monta e destrói a role e a tabela apenas no PostgreSQL 16 efêmero e executa testes positivos/negativos. O segredo de teste da role é um valor público descartável **sem validade fora dessa CI**. Nenhuma migration, rota, grant operacional, implantação ou alteração da VPS é criada.

**F246 não autoriza acesso real:** ainda são necessários políticas de membership e field-level ACL, identidade S2S protegida, transações com revogação concorrente, mTLS, auditoria LGPD, revisão independente SEG/DBA/CORE/IAM e os gates do CRM #57.
