# CRM — candidato integrado para auditoria de homologação privada

## Autorização e escopo

Em 15/11/2026 a Direção autorizou integração e implantação exclusivamente privada, condicionada a isolamento, acesso, backup e reversão comprovados. Não autoriza produção, clientes, cobranças ou neutralizar guardas CI. Esta autorização substitui a antiga proibição genérica de investigar a VPS; não homologa contratos ainda ausentes.

## Base e conflito resolvido

Base: PR #609, `41e9c85733e92e8a9e18b31f4d5892df6def0e54`, 12 workflows completed/success conferidos novamente no GitHub. Contém o candidato #608 e a linha IAM #606. A PR #611, `142fa8eea5efb6fbace357f0e05405e88eb027da`, descende da linha Sites independente: trocar a base inteira apagaria testes IAM, OTP, tratamento de falha de autenticação e o readiness de PostgreSQL definitivo no restore. Foram incorporados somente os **11 arquivos adicionados** na linha F399–F401. Nenhuma remoção ou versão mais antiga sobrepôs os arquivos da #609.

O manifesto `infra/staging/integrated-source-manifest.json` fixa hashes dos contratos preservados. O verificador e o contraexemplo de regressão rodam na F0. Esse controle detecta perdas na consolidação; não substitui revisão de segurança. Alterações futuras legítimas devem atualizar os hashes após revisão explícita do diff.

## Inventário funcional observado

| Área | Evidência atual | Falta para a jornada solicitada |
|---|---|---|
| Comercial PT/EN/ES | Laravel Blade em staging, GET `/` | Conversão ligada a cadastro protegido |
| Painel e Kanban | `/demo` visual; `/demo/live` leitura de fixtures PG sob RLS | CRUD autenticado e política por ação |
| IAM | Senha/TOTP, Redis criptografado, revogação, recuperação e HTTP testados em CI | Perfil staging com identidade e autorização revisadas; não basta flag |
| OTP/trial | HMAC, 6 dígitos/15 min/5 tentativas, aliases históricos atômicos em PG16 CI | Entrega privada de teste, provisionamento e concessão de trial persistente |
| Leads/tarefas | Ledgers transacionais, membership e RLS em PG16 CI | Backend homologável fora de CI, cotas e auditoria por ação |
| Sites/Portal | F398 + F399/F400/F401 originais, HMAC/inbox/revogação em CI | Identidade de serviço e integração operacional aprovadas |
| Cobrança | Catálogo de referência e ledgers Asaas mock | Jornada simulada navegável; Sandbox real ainda desligado |
| Equipes, automações, CSV, preferências | Contratos e protótipos sintéticos | Integrar recursos seguros e identificar demonstrações |
| Backup/restore | Ensaio PG16 efêmero em Actions | Backup cifrado, restore e rollback do novo banco privado na VPS |

**Não existe nesta SHA um fluxo HTTP completo cadastro → OTP → trial → login → CRUD.** As rotas atuais não registram POST de negócio. `DatabaseSessionAuthority` só admite local/testing e os ledgers verificam ambiente CI/repositório/banco/roles exatos. Não alterar esses ambientes ou fingir `GITHUB_ACTIONS=true` na VPS.

## Inspeção operacional read-only na VPS

DNS `crm-dev.imaginecomunica.com.br` resolve para a VPS. A porta 127.0.0.1:18080 está ocupada pelo Portal F64; o CRM F64 usa 127.0.0.1:18081. Nenhum desses contêineres foi parado ou alterado. O Compose candidato em `infra/staging/compose.yaml` fixa 18080 e **não pode ser iniciado como está nessa VPS**. A nova composição deve usar nome, banco, rede, volumes e porta livre próprios, com proxy protegido após validação; preservar o Compose CI existente.

A imagem instalada `imagine-crm-f64:local` não possui source bind mount. Ainda não foi demonstrado que corresponde ao novo candidato. Não apresentar a prévia F64 como o CRM consolidado.

## Estado de aceite

Fase A: consolidação de fonte preparada; novas suites precisam de execução na SHA deste candidato. Fases B–E pendentes. Nenhum deploy deste candidato, nova tabela persistente, segredo, cobrança, ALLOW, merge main ou alteração de outros projetos foi realizado. O SaaS e a jornada de homologação integral **não estão concluídos**. Os pareceres #27/#259/#416/#564/#557 não foram atribuídos ficticiamente nem encerrados.
