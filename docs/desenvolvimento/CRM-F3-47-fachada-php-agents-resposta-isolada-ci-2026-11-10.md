# CRM F3-47 — teste cruzado da fachada PHP pública real do Imagine Agents

**Data:** 10/11/2026. **Escopo:** laboratório de integração sintética, sem ativação funcional.

## Problema resolvido

O F3-45 verificava o parser de pedidos real do Agents; o F3-46 passou a verificar também o contrato formal de respostas. Nenhum deles executava a **fachada PHP pública real do Agents** para ver quais valores são produzidos e se uma resposta sintética de leitura bem-sucedida poderia ser incorretamente interpretada pelo CRM como permissão.

## Incremento verificável

- O CI continua fixando o Agents em `4a03adf5f91e760950adaa3239fb2c8dba05da6d`, sem buscar branch móvel, secrets ou dados reais.
- O script `scripts/ci/check-aia-php-public-facade-witness.php` executa por PHP CLI o próprio `tools/op01_php_public_response_probe.php` do Agents (código real, não uma cópia de contrato).
- Exige exatamente seis cenários nominais, duas respostas `READ_SYNTHETIC_ONLY` com projeção fictícia mínima e quatro respostas `DENIED_OR_NOT_FOUND` sem projeção.
- Exige resposta de cinco campos na ordem definida: contrato público `SYNTHETIC_CRM_AIA_PUBLIC_V1`, estado, projeção, `real_authorization=false` e `external_calls=0`.
- Inspeciona as seis respostas de fachada com a fronteira CRM `AiaReadOnlyMockBoundary`; **todas as seis devem ser negadas**, inclusive as duas leituras sintéticas positivas do Agents. Não trata resultado do serviço como prova de identidade, ACL, token, consentimento ou existência de lead real.
- Evita valores de requisição, nonce, owner e principal nos dados públicos; não chama banco, rede, HTTP, API, serviço de IA nem Laravel.

## Critérios de avanço

CI cruzada PHP 8.3/8.4, F0 e PostgreSQL/RLS devem passar na HEAD da entrega, com revisão de diff e ausência de divergência da DEV. Integração, se aprovada, ocorre somente em `dev/fast-track-crm-2026-11-04`.

## Bloqueios mantidos

Não há consumidor operacional de resposta do Agents, usuário CRM autenticado, permissão `ALLOW`, rota de leitura, sessão, campo real, dados de cliente, deploy ou homologação. O gate SEG/DBA (#27 / PR #175), OP-01 (#57), CORE/AUT/LGPD permanece aberto. O laboratório de Portal ↔ CRM (#209/#210/#211/#215/#218) é outra sequência e **não é liberado** por esta prova.
