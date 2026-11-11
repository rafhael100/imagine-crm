# CRM UX F3-64 — conferência Portal ↔ CRM sem importar a pilha antiga

**Data:** 11/11/2026. **Branch base:** `dev/fast-track-crm-2026-11-04`, SHA verificada `b7ffc00716732a3badcd2fb8b6178de9b7295c4c`. **Fonte funcional:** [PR #252](https://github.com/rafhael100/imagine-crm/pull/252), HEAD `82b92439f4cf7034ba32c28a51017d182ec8823d`, anteriormente **2 commits atrás** da DEV, apesar dos cinco workflows bem-sucedidos naquela SHA.

## Trabalho efetivo de reconciliação

Esta branch **nova**, criada diretamente sobre a DEV pós-OPS-44, porta **apenas os 13 arquivos específicos de UX, testes e workflow do PR #252**, preservando o código do Kanban `prototypes/f3-59-offline-kanban/index.html` e os marcos DEV F3-59..63. O workflow antigo com trigger exclusivo da branch F3-63 foi substituído por `.github/workflows/ci-ux-f3-64-reconciliation-fresh-dev.yml`, com acionamento da nova branch.

Foram preservados o hub em `prototypes/index.html` com **seis destinos locais**, o painel `prototypes/f3-52-delivery-reconciliation/index.html` com **seis cenários de entrega fictícia** (inclusive ACK incerto, idempotência e tenant divergente *apenas como explicação de risco*), links bidirecionais entre interfaces, whitelist local estrita, browser smoke Chromium desktop/320px e contratos PHP de acesso e ausência de rede.

**Não transportamos** commits da cadeia de laboratório Portal↔CRM #209..#232, scripts de migração, credenciais, autorização/ALLOW, componentes Core sensíveis nem dados reais. O PR de origem fica como referência da evolução; esta candidata é um rebase de **escopo**, sem reescrever sua branch.

## Gate de integração

A CI deve incluir, na HEAD **exata desta PR**, os cinco workflows: F0 Laravel/Chromium; PostgreSQL/RLS; Sites parity; Agents parity; e o workflow UX F3-64 com provas de navegação real 320px. Uma PR com `behind_by>0`, conflito ou falha não pode ser integrada à DEV sem novo ciclo de checks.

Este recorte está reservado para **revisão independente** da UX e da política de fronteira. Uma aprovação de CI prova o comportamento *offline*, não autoriza operação de Portal/CRM real, retransmissão, ACK, persistência, IAM, dados pessoais ou acesso à VPS. Produção, main, CORE/SEG/DBA/LGPD e homologação continuam NO-GO. Se o parecer UX independente não estiver registrado, manter **DRAFT / sem merge**.

## QA revisável

Avaliar seis cards do hub, destinos locais de navegação, foco por teclado em desktop/320px, ordenação/filtros/ficha/histórico do painel de reconciliação, regressão Kanban F3-63, CSV e ausência de captura, storage, API, grants e rede. Separar o parecer visual da revisão SEG/DBA e dos gates de produção.
