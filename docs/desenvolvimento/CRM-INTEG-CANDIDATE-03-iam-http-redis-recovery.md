# CRM-INTEG-CANDIDATE-03 — Candidato único com IAM HTTP, Redis e recuperação MFA

**Estado:** DRAFT / NO-MERGE / NO-DEPLOY / NO-PII / NO-PUBLIC-LOGIN / NO-ALLOW / NO-CHARGE / NO-GO.

## Base e método de consolidação

Data de preparação: 14/11/2026. A DEV `dev/fast-track-crm-2026-11-04`
foi conferida no SHA `d7c7aacc766c7ade4c65dcd211ed4569442144a9`.
O candidato integrado parte da HEAD verificada da PR #603,
`1c32f80a41399242caace20a402e4437806bcb21`, descendente da
PR #596, que preserva as alterações da DEV em Agents.
A comparação GitHub anterior à criação deste dossiê indicou:
**29 commits à frente, 0 atrás, 119 arquivos alterados**. Esta
documentação adiciona mais um arquivo; exigir comparação e CI na
HEAD exata após seu commit. Não houve merge na DEV/main nem deploy.

## Componentes reunidos na mesma linha

- #596 — candidato inicial Core multitenant, leads e tarefas fictícias,
  página comercial Laravel PT/EN/ES, Kanban com PostgreSQL16 sob RLS,
  Asaas/C6 **somente mock**, Chrome real, backup/restore de laboratório;
- #597 — senha, TOTP e sessão HTTP Laravel com CSRF, identidade segura,
  logout e acesso a negócios negado;
- #598 — sessão MFA e memberships com PostgreSQL16 real sob FORCE RLS,
  isolamento A/B e revogação, nas rotas exclusivamente do PHPUnit;
- #599 — Redis 7 real no job PostgreSQL com SESSION_ENCRYPT e flags seguras;
- #600 — bytes de sessão cifrados no Redis e destruição das sessões anteriores;
- #603 — caminho de recuperação MFA com código único, bloqueio de replay
  e CSRF, testado tanto em F0 quanto em Redis/PostgreSQL.

Nenhum desses testes disponibiliza login, dashboard autenticado,
formulário de inscrição ou autorização `ALLOW` para clientes.
Os códigos de autenticação continuam experimentais e proibidos
em produção. As telas de demonstração só servem dados inventados;
a leitura persistida do Kanban usa um tenant sintético fixo.
A integração de Agents posterior à DEV-base, em PRs independentes,
deve ser reavaliada antes de eventual atualização da DEV.

## Gates explícitos para transformação em SaaS operacional

**P0 — ainda bloqueados:**
1. Fluxo privado e autorizado de registro + verificação de titularidade
   CNPJ + OTP de e-mail/telefone, antiabuso e consentimento;
2. IAM com usuários reais, MFA e recuperação segura, permissões por papel,
   sessões auditáveis, isolamento dinâmico de empresa no runtime;
3. CRUD real de leads/clientes/funis/tarefas com limites de planos/trial,
   backups, exportação e auditoria LGPD;
4. Sandbox Asaas autenticado com reconciliação e idempotência, contrato
   de ativação e suspensão; C6 configurável sujeito à negociação;
5. NFS-e, integração segura Sites/Portal, revogação concorrente
   verificada #564 e fluxo comercial até cancelamento;
6. VPS Leaseweb privada com rede/TLS/DNS/segredos/backup e restauração,
   sem publicar dados ou serviços antes das revisões.

**Revisões independentes pendentes:** #27 SEG/DBA, #259 CORE/LGPD,
#416 UX, #557 GO operacional; não atribuir aprovação fictícia
a uma IA ou a CI verde.

## Critérios de verificação desta PR

- Executar todos os workflows da **HEAD exata**, mesmo que os ancestrais
  #596–#603 já tenham passado em suas SHAs.
- Confirmar que a comparação com DEV não perdeu arquivos nem
  adicionou migrations produtivas, endpoints públicos, `.env`,
  segredo ou alteração de política `DenyByDefaultAuthorizer`.
- Manter o diff como candidato de revisão integrado; **não
  mesclar, implantar, ativar contas, testar em produção, coletar PII
  ou criar cobranças**.
- Revisar a UX das telas com o artefato de capturas CI, separado da
  afirmação de que o SaaS está completo.

**Conclusão:** uma única HEAD para revisão técnica da camada Core,
páginas privadas e laboratório de IAM. Não é homologação operacional.
