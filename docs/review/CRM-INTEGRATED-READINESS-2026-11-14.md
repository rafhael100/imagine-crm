# Consolidação reversível — DRAFT / NO-MERGE / NO-DEPLOY

## Snapshot consultado em 14/11/2026

| PR | HEAD | Evidência retornada pela API (pull_request, primeira página) |
|---|---|---|
| #530 | 336becf88902759e286ddd9a7eb11f64b27b12b1 | F0, PG16/RLS, Sites, Agents e WEB-P0: success |
| #528 | 70d8a6894a208916cf8e21a434b237d5d8e8fff7 | F0, PG16/RLS, Sites, Agents e WEB-P0: success |
| #522 | 50c1eb574aa0ed81055dc256e77302acd1781d71 | F0, PG16/RLS, Sites, Agents e PG18/PHP8.3+8.4: success |

Fontes: https://github.com/rafhael100/imagine-crm/pull/530,
https://github.com/rafhael100/imagine-crm/pull/528,
https://github.com/rafhael100/imagine-crm/pull/522.

Jobs WEB-P0 inspecionados: https://github.com/rafhael100/imagine-crm/actions/runs/43558563292
(painel, trial, bundle e manifesto) e https://github.com/rafhael100/imagine-crm/actions/runs/43547439320
(trial e bundle anteriores). PG18: https://github.com/rafhael100/imagine-crm/actions/runs/43542120326
(ambas as versões PHP e casos negativos concluídos).

Essas execuções continuam evidências históricas válidas para o que executaram.
A associação de um run à HEAD **não atesta checkout da HEAD**: os workflows
consultados usam checkout padrão, que em pull_request seleciona o merge sintético.
Não transferir esses sucessos para esta consolidação. A API consultada só retorna
runs de pull_request e a primeira página: ausência de HTTP/TLS nesta lista não
prova ausência de um run push. Não foi possível inventariar esses runs via gh sem
credencial local. Nenhuma execução push anterior é declarada inválida.

## Dependências e inconsistências concretas

- #530 tem como base exatamente a HEAD de #528: cadeia web coerente no snapshot.
- #522 deriva de DEV, não de #528/#530. Os quatro arquivos do laboratório PG18
  não existem na HEAD #530. As duas frentes ainda não foram verificadas juntas.
- HTTP só dispara por push na branch WEB-P3; HTTPS por push em duas branches;
  catálogo tem filtro de base WEB-P1; UX só dispara por push na branch do painel.
  Uma nova PR integrada para DEV não executaria esse conjunto automaticamente.
- PG16/RLS e PG18 são laboratórios distintos. A função PG18 SECURITY DEFINER
  é criada pelo superusuário postgres: FORCE RLS e testes de privilégios não
  comprovam enforcement RLS para o executor superusuário da função. Não é
  validação de isolamento de uma API operacional nem autorização para promover SQL.
- O painel usa dados estáticos. Nenhum lead PG18 é consumido pela interface;
  consolidar fontes e CI não cria integração de dados, login ou identidade S2S.

## Entrega

Branch filha de revisão contendo #530/#528 e o laboratório imutável de #522,
sem alterar as branches existentes. PR direcionada a DEV somente para revisão.
Seis workflows especializados passam a executar em PR para DEV sem filtros de
paths que possam omitir regressões transversais. Os dez workflows do conjunto
selecionam explicitamente pull_request.head.sha e verificam git rev-parse HEAD,
registrando a SHA no resumo de cada job. Fixtures externas seguem nas SHAs
originais; nenhum segredo é usado para checkout.

Aceite técnico desta entrega exige, na mesma HEAD: F0, PG16/RLS, Sites, Agents,
WEB-P0, catálogo, HTTP privado, HTTPS efêmero, UX Cliente/Admin e PG18 com ambas
as versões PHP. Qualquer mudança de HEAD invalida a promoção das evidências.
O checkout da HEAD exige avaliar eventual compatibilidade de merge separadamente.

## Verificação local e limites

PASS: test_preview.py, staging/test_configuration.py, node --check dashboard.js,
parse YAML de todos os workflows e git diff --check.
Chromium: dashboard_journey_browser.py e trial_handoff_browser.py tentados sem
resultado; socket do processo Chromium recusado pelo ambiente (Operation not
permitted). Não classificados como sucesso. PHP, Composer e Docker indisponíveis
localmente; PHPUnit, HTTP/TLS Docker e PG18 dependem da CI descartável.

Rollback: fechar a PR e excluir apenas sua branch. Sem migrations operacionais,
rotas reais, signup, cobrança, VPS, DNS, main, usuário ou dado real alterados.

## Gates mantidos

SEG/DBA #27, CORE/LGPD #259 e UX #416 exigem evidência/aceite próprios; esta
entrega não os aprova. Permanecem pendentes IAM/sessões/tenant, consentimento real,
retenção/exclusão, identidade S2S, segredos, backups/restauração, auditoria,
observabilidade e infraestrutura real. Próximo recorte seguro: revisar os dez
resultados e suas SHAs antes de propor qualquer ensaio adicional. Nenhum sucesso
CI abre homologação operacional, merge ou deploy automaticamente.
