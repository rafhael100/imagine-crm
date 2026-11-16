# CRM F247 — RLS + identidade Ed25519 + nonce PG16 + HTTP 404 no Laravel

**15/11/2026 — laboratório descarte total, DRAFT / NO-GO / NO-MERGE / NO-DEPLOY.**

A prova F247 une, na mesma execução PHP/Laravel, quatro componentes previamente desenvolvidos em ramos separados:

- A **chave pública e a empresa** vêm apenas do cadastro sintético CRM F246 consultado por role SELECT-only em PostgreSQL 16 com FORCE RLS;
- O corpo, a assinatura e o escopo são avaliados pelos algoritmos originais Agents fixados na SHA `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5`;
- Um nonce válido entra no ledger atômico PostgreSQL 16 já existente no CRM; uma tentativa repetida, mesmo em nova instância PHP, é negada. Esse ledger permanece um **ensaio** usando o superusuário do banco CI, não um componente operacional;
- O adaptador privado original `AgentsSignedIngressCandidate` devolve sempre HTTP 404 com `DENIED_OR_NOT_FOUND`, mesmo após evidências sintéticas positivas. Sem endpoint registrado, rota real, dados de negócio, publicação, IAM ou ALLOW.

Os testes cobrem adulteração do tenant (nega antes de consumir nonce), primeira chamada com assinatura válida, replay, cabeçalho de tenant forjado, revogação de chave e desligamento do RLS. Também executam os testes F246 anteriores de privilégios mínimos e limpeza da role.

**Limitação crítica:** F247 não elimina a possibilidade de uma revogação concorrente entre leitura da chave e gravação de nonce; não existe transação operacional cobrindo ambas. O recorte seguinte deve provar serialização da revogação, derivação de identidade e auditoria antes de qualquer GO. Ainda faltam revisão independente SEG/DBA/CORE/IAM/LGPD, mTLS, chave operacional protegida e os 14 gates/7 decisões/13 cenários reais do CRM #57. Nenhuma instalação, custo, migração real, VPS ou merge automático.
