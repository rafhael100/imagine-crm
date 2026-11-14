# CRM-STG-02 — Candidato de homologação privada integrado (CI sem deploy)

**DRAFT / NO-MERGE / NO-DEPLOY / NO-GRANT / NO-PII**

## Alteração sobre a base STG-01

A configuração candidata de homologação privada da [PR #556](https://github.com/rafhael100/imagine-crm/pull/556) foi incorporada à mesma HEAD da [PR #567](https://github.com/rafhael100/imagine-crm/pull/567), contendo o laboratório de captação Sites, RLS, Kanban, auditoria e tarefas.

Além da verificação estática de Docker Compose, `ci-crm-private-staging-smoke.yml` faz um ensaio **somente no runner temporário do GitHub Actions**, sem conexão à Leaseweb:

1. Verifica os arquivos do Core integrado e que nenhuma classe `Laboratory*` foi conectada a rota de negócio pública.
2. Gera `APP_KEY` e senha PostgreSQL **aleatórias e descartáveis** neste runner, sem cadastro em cofre real.
3. Constrói imagem PHP-FPM a partir de `composer.lock`, sem migrations de produção.
4. Sobe contêineres isolados com Nginx publicado **apenas em `127.0.0.1:18080`** e sem portas públicas de PHP/PostgreSQL.
5. Verifica `GET /up` e que `/api/leads`, `/api/sites/intake` e `/api/tasks` permanecem 404.
6. Executa `docker compose down --volumes` incondicionalmente ao fim.

**A execução é uma prova de build, inicialização e roteamento de um contêiner efêmero, não significa homologação na VPS.** Dados de tenants, logins, pagamentos e sites reais continuam inteiramente desabilitados. Não existe HTTPS público, consentimento ou integração externa; autenticação operacional e cobrança não foram ativadas.

## Critérios pendentes para liberar produto

- IAM real com login independente, MFA, revogação, controles de usuário, quotas, políticas `ALLOW` revisadas.
- Migrações não destrutivas de dados operacionais e papéis PostgreSQL homologados; RLS com fonte de identidade confiável, backups e restore.
- Emissor Sites autenticado com vínculo aprovado, segredos rotativos, nonce/at-most-once distribuído, consentimento e eventual handling sob supervisão SEG/DBA.
- Asaas Sandbox autenticado, conciliação/estornos, contrato/planos e emissão fiscal.
- UX/admin/cliente efetivamente conectados, testes de dispositivos e acessibilidade.
- TLS/rede/monitoramento/backup da VPS, homologação ponta a ponta, pareceres independentes [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259), [UX #416](https://github.com/rafhael100/imagine-crm/issues/416), [#564](https://github.com/rafhael100/imagine-crm/issues/564) e aceite formal [#557](https://github.com/rafhael100/imagine-crm/issues/557).

**Nenhuma execução do GitHub Action instala algo no servidor do usuário ou altera a main.** A CI não dispõe de credenciais da VPS ou do Asaas; não haverá merge nem deploy automático.
