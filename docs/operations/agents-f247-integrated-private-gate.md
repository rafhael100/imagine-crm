# CRM F247 — candidato de integração F241–F246 (Laravel funcional privado)

**15/11/2026 — DRAFT / NO-GO / NO-MERGE / NO-DEPLOY / SEM NOVOS CUSTOS**

## Objetivo do candidato

Reunir em uma única revisão de código e CI os recortes que antes eram testados isoladamente:

| Recorte | Prova de laboratório |
| --- | --- |
| F241 / PR #618 | Requisição assinada do código original Agents → adaptador HTTP privado F248, negativa 404 uniforme |
| F242 / PR #619 | Ed25519 original Agents + nonce atômico PostgreSQL16 entre duas conexões |
| F243 / PR #620 | Diagnóstico integrado HTTP F248 → assinatura/escopo → PostgreSQL16, sempre negativa |
| F244 / PR #621 | Suite conjunta F241–F243 no mesmo Laravel privado, [CI #43793848537](https://github.com/rafhael100/imagine-crm/actions/runs/43793848537) aprovada |
| F245 / PR #622 | Fonte da chave pública e da empresa derivada de registro CRM PostgreSQL globalmente único, revogação/rotação. [CI #43794364074](https://github.com/rafhael100/imagine-crm/actions/runs/43794364074) aprovada |
| F246 / PR #624 | Consulta da chave pública sob role PostgreSQL separada, nonowner, SELECT-only, FORCE RLS e sem BYPASSRLS. [CI #43794846777](https://github.com/rafhael100/imagine-crm/actions/runs/43794846777) aprovada |

O workflow `ci-crm-aia-f247-unified.yml` deve repetir as sete suítes, incluindo a F248 original e o teste histórico PG, no mesmo código Laravel e banco PG16 descartável. A árvore do Agents F240 continua SHA-pinned em `fbf50543e7c52e6c59c6ffec58b7f6a6d30564b5` e a F248 histórica mantém seu pin em `fe79e7e9a55e4d6728ab42c2ef578e40d9bd68ba`; nenhum fork de algoritmo é criado.

## Limites e lacunas a preservar na revisão

O banco e as identidades são inteiramente fictícios. F245 é uma prova controlada com conexão privilegiada **somente na CI**; F246 demonstra que a leitura de uma tabela de **chaves públicas** pode ser limitada por uma role restrita e RLS. Nenhum desses testes concede uma política real de autorização para leads, campos ou membros. A política `USING (true)` do laboratório F246 atende exclusivamente à leitura de chave pública globalmente selecionada por ID; **não** deve ser portada para tabelas de clientes.

A criação e revogação da chave privada, autoridade de identidade S2S, proteção mTLS, origem real da empresa, RLS de dados comerciais, autorização humana, controle de finalidade, anti-replay atômico e revogação concorrente, LGPD, auditoria minimizada e revisão externa SEG/CORE/DBA/IAM continuam pendentes.

Não há nova rota, controller público, deploy, migração produtiva, integração de gateway de pagamento, custo, mudança na VPS ou alteração de `main`. Todas as respostas existentes do adaptador F248 permanecem **404 / DENIED_OR_NOT_FOUND**, com `real_authorization=false`.

**Critérios para usar em staging real:** decisão independente e registrada de responsáveis, compatibilização explícita com CRM #614 e segurança CRM #602, revisão de código/protocolo, ambiente isolado, rollback e execução dos **14 gates, 7 decisões e 13 cenários operacionais da PR CRM #57**. CI verde F247 é só evidência de laboratório; **NO-GO** para ativar serviço Agents com dados de clientes.
