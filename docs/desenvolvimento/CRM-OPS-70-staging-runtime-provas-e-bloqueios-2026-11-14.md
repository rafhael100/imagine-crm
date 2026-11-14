# CRM OPS-70 — evidência de execução do staging privado e limites da homologação

**Data:** 14/11/2026 (UTC, execução do GitHub Actions). **Ambiente:** somente runners descartáveis de pull request, **não a VPS**. **Fonte:** PR [#517](https://github.com/rafhael100/imagine-crm/pull/517), que continua **DRAFT / NO-MERGE / NO-DEPLOY**.

## Entregas efetivamente verificadas

| Recorte | Prova material | Resultado |
|---|---|---|
| F3-267 — build PHP/Nginx | [GitHub Actions #43542647814](https://github.com/rafhael100/imagine-crm/actions/runs/43542647814) | Imagens construídas; Composer de produção, FPM, Nginx e GET/HEAD `/up` HTTP 200; GET/POST comerciais e arquivos internos negados |
| F3-268 — isolamento | [GitHub Actions #43543520064](https://github.com/rafhael100/imagine-crm/actions/runs/43543520064) | UID sem root, rootfs read-only, cap_drop ALL, no-new-privileges, redes exatas; PHP-FPM sem rota padrão nem porta do host; web apenas loopback |
| F3-269 — indisponibilidade/recovery | [GitHub Actions #43544656952](https://github.com/rafhael100/imagine-crm/actions/runs/43544656952) | PHP-FPM suspenso com IP preservado; Nginx retornou HTTP 502/503/504 sem cookie e com headers; `/up` voltou HTTP 200 após unpause |
| F3-270 — saúde nativa do Compose | [GitHub Actions #43546705237](https://github.com/rafhael100/imagine-crm/actions/runs/43546705237) | FPM e web iniciaram healthy; web alterou healthy → unhealthy → healthy na falha simulada |
| F3-271 — inventário do pacote | Verificar PR #517 / CI na SHA do inventário, **não há aceite operacional antecipado** | Proposta para hashes SHA256 e versões somente públicas, preservando a política no-deploy |

As provas F3-269 e F3-270 tiveram **6/6 workflows success na HEAD exata**, incluindo F0, PG/RLS descartável, Sites, Agents, contrato Compose e runtime. O commit comprovado de F3-270 foi **`9c249dbff5694a6adca3d996bc527f7a62d47f0e`**. O candidato pode evoluir sem publicação, mas **não foi integrado à DEV**.

## Defeitos reais corrigidos na CI

1. Falta de headers de desenvolvimento `libsqlite3-dev` para compilar `pdo_sqlite` no Debian (build real revelou e bloqueou).
2. Montagem temporária cobrindo o conjunto da árvore `storage` (corrigida por mounts limitados aos subdiretórios graváveis).
3. Docker `internal: true` impedindo a ligação do host ao Nginx; adotou-se rede interna do backend e rede bridge de loopback para o Nginx, com NAT mascarado desativado.
4. Inferência falsa de porta publicada por `docker compose port`; substituída por Docker `HostConfig.PortBindings`.
5. Simulação por parada do FPM perturbava o DNS da rede; substituída por pause/unpause com FastCGI timeout e trap de restauração.
6. Healthcheck PHP com variáveis interpretadas pelo Compose; substituído por comando de socket local fixo sem variáveis.

## O que ainda **não** está homologado

As ações passaram **somente** em ambiente GitHub efêmero. **Não** houve: implantação VPS; publicação de login, inscrição, OTP, cobrança ou checkout; conexão a DB real/persistente; ativação de usuários/tenants; confirmação de segurança da rede do host, firewall, segredos, TLS e Cloudflare; backup, rollback, restauração real ou sistema de métricas/SLA. Imagens locais do CI não são digests de registry aprovados, nem constituem assinatura de proveniência.

**Gates externos seguem abertos:** [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27), [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259) e [UX #416](https://github.com/rafhael100/imagine-crm/issues/416).

## Próxima sequência operacional

1. Concluir inventário/SBOM, digest fixo de dependências e revisão de origem das imagens, mantendo PR #517 DRAFT.
2. Concluir revisão SEG/DBA independente, isolamento da VPS compartilhada, egress, SSH/TLS, política de segredos, logs, limites de recursos e reversão.
3. Validar papéis de DB não-superusuários, RLS, migração, backup/restore e LGPD; não ativar dados de clientes sem aceite.
4. Obter aceite UX e da jornada comercial Portal → cadastro/OTP → trial/CRM → checkout (checkout continua negado até gateway e cobrança aprovados).
5. Somente após gates, autorizar revisão/merge/deploy específico, com rollback e evidência do ambiente real.

**Estado para a DEV:** este dossiê é **documental**, não integra o candidato #517 e não autoriza alterar `main`, VPS, domínios ou produção.
