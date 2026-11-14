# CRM — reciprocidade F221 com código PHP original dos dois repositórios

**14/11/2026 — DEV, NO-GO.**

A CI `ci-crm-aia-synthetic-parity.yml` usava o AI Agents commit `36de10eecbd11e04e7684ab3d15989ab4e9749dd`, anterior aos codecs JSON F219/F220 e à troca F221. Esta entrega substitui o pin por `a18ad19ad8a24f9a04f1f4bce789ff4af6316d55`, hash de DEV já validado após F223, sem usar branch mutável nem acesso à rede de produção.

Em **PHP 8.3 e 8.4**, o novo `scripts/ci/check-aia-op01-json-exchange.php` chama as classes PHP **reais do Agents fixadas por SHA**, passando bytes JSON pela borda, fachada, owner sintético e auditoria. O `AgentsReadResponseInspector` e `AiaReadOnlyMockBoundary` **originais do CRM** recusam todos os direitos, inclusive quando a projeção fictícia foi aceita pela fachada.

Provas executáveis: (1) `READ_SYNTHETIC_ONLY` sem autorização real, (2) replay negado, (3) chave JSON duplicada rejeitada antes da leitura, (4) dono sem concessão, (5) ausência de identidade sintética. Saída pública uniforme e sem PII, nenhuma chamada externa.

Não foi criada rota HTTP, service token, chave, API, IAM, ACL/RLS real, banco, migration ou alteração da `main`. Repositórios privados exigirão solução separada de checkout autenticado somente leitura. O contrato operacional da CRM PR #57 permanece **OPEN/DRAFT**, com 14 gates e 13 cenários reais **NAO_EXECUTADO**. O teste de laboratório não significa homologação.
