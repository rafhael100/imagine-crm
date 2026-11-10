# CRM Imagine — STATUS ATUAL

**Atualizado em:** 10/11/2026 (America/Sao_Paulo).  
**Fonte:** GitHub, PRs, Issues, branches e workflows consultados; este registro é operacional, **não homologação nem autorização de produção**.  
**Branch de desenvolvimento:** [`dev/fast-track-crm-2026-11-04`](https://github.com/rafhael100/imagine-crm/tree/dev/fast-track-crm-2026-11-04).  
**HEAD DEV no levantamento:** `fddcf15c52912a75afe0798d1af724fea672011b` — PRs [#142](https://github.com/rafhael100/imagine-crm/pull/142), [#143](https://github.com/rafhael100/imagine-crm/pull/143), [#144](https://github.com/rafhael100/imagine-crm/pull/144) e [#145](https://github.com/rafhael100/imagine-crm/pull/145) integrados exclusivamente DEV, cada qual com F0 e PostgreSQL/RLS aprovados **após merge**. Trial ilustrativo de 14 dias, bandas de franquia e projeção hipotética de consumo, com controles acessíveis no protótipo offline. Sem elegibilidade de trial, cobranças, reservas, direitos, APIs, dados reais, VPS, `main` ou produção.
**HEAD `main`:** `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012` — sem merge dos recortes experimentais.  
**VPS/produção:** **não implantadas nesta sequência**; nenhuma API de cliente, lead real, cadastro funcional, `ALLOW`, SQL operacional ou serviço pago adicional habilitado.

## A. Estado consolidado — 09/11/2026

| Frente | Evidência | Situação |
|---|---|---|
| F1-02 a F1-10 — IAM/RLS/contratos iniciais | [PR #20](https://github.com/rafhael100/imagine-crm/pull/20) e anteriores | Experimentos DEV integrados; sem direitos reais |
| F3-01 a F3-06 — identidade/submissões, exportação, UX e formulário mínimo | [Issue #35](https://github.com/rafhael100/imagine-crm/issues/35), PRs #36–#41 | Experimentos e protótipos integrados DEV |
| F3-07 a F3-12 — evidências, retenção, reconciliação, envelope, idempotência CAS | PRs #42–#47 | PHP puro/contratos sintéticos DEV; **não** são APIs operacionais |
| F3-13 — unicidade física e RLS no PostgreSQL descartável | [PR #48](https://github.com/rafhael100/imagine-crm/pull/48) | Testes de contenção CI integrados DEV; não é ledger de leads |
| F3-14 — proposta de ledger dedicado e revisão especializada | [PR #49](https://github.com/rafhael100/imagine-crm/pull/49) | **DRAFT documental, sem merge**, aguardando parecer independente SEG/DBA |
| F3-15 — HMAC e limites de replay | [PR #50](https://github.com/rafhael100/imagine-crm/pull/50) | Laboratório PHP integrado DEV; sem origem/chaves reais |
| F3-16 — replay e nonce no banco descartável | [PR #51](https://github.com/rafhael100/imagine-crm/pull/51) | Testes físicos integrados DEV, sem anti-replay global |
| F3-17 — GUC não é identidade autenticada | [PR #52](https://github.com/rafhael100/imagine-crm/pull/52) | Evidência de risco integrada DEV; RLS condicionada a contexto verificado |
| F3-18 — bloqueio de rotas e desativação de `local.serve` | [PR #53](https://github.com/rafhael100/imagine-crm/pull/53) | Integrado DEV; nenhum endpoint de negócio publicado |
| SEC-CI-02 — preflight do PostgreSQL descartável e push/PR | [PR #55](https://github.com/rafhael100/imagine-crm/pull/55) | Integrado DEV; três verificações de identidade antes das migrations; não protege produção |
| OPS-01 — normas de execução controlada | [PR #56](https://github.com/rafhael100/imagine-crm/pull/56) | `AGENTS.md` atualizado para autonomia DEV com gates preservados |
| F3-20 — prévia CSV sintética em memória | [PR #58](https://github.com/rafhael100/imagine-crm/pull/58) | Integrado DEV; decodificador limitado, somente resumo/posições, sem importação |
| UX | [Protótipo offline de nove telas](prototypes/s01-prot-01/index.html), [PRs #59](https://github.com/rafhael100/imagine-crm/pull/59), [#60](https://github.com/rafhael100/imagine-crm/pull/60), [#61](https://github.com/rafhael100/imagine-crm/pull/61) | Teclado e layout testados com Chromium real em 520/320 CSS px; inspeção humana/WCAG ainda pendente |
| ENT-01 — cálculo puro de franquias por recurso | [PR #63](https://github.com/rafhael100/imagine-crm/pull/63) | Integrado DEV; saldo/excesso sem ativação de plano, cobrança ou autorização |
| UX-F3-14 — filtro de leads fictícios por etapa | [PR #64](https://github.com/rafhael100/imagine-crm/pull/64) | Integrado DEV; filtro/contagens no navegador sem dados reais ou persistência |
| ENT-02 — faixas informativas para anexos, sem upload | [PR #67](https://github.com/rafhael100/imagine-crm/pull/67) | Integrado DEV: limiares aritméticos de 80/95/100%, sem concessão de direitos nem cobrança |
| ENT-03 — janela sintética informativa de trial | [PR #68](https://github.com/rafhael100/imagine-crm/pull/68) | Integrado DEV: fases/contagem de 14 dias, sem OTP/CNPJ, suspensão, retenção, entitlement, ativação ou cobrança |
| UX-F3-15 — ordenação dos leads fictícios | [PR #70](https://github.com/rafhael100/imagine-crm/pull/70) | Integrado DEV: ordem original/maior/menor pontuação, filtros independentes, Chromium 520/320 px, sem scoring real |
| ENT-04 — resumo informativo multi-medidor | [PR #71](https://github.com/rafhael100/imagine-crm/pull/71) | Integrado DEV: 1–12 medições sintéticas únicas, faixas de uso e excesso sem decisões de plano, acesso ou cobrança |
| UX-F3-16 — medidores estáticos no painel | [PR #72](https://github.com/rafhael100/imagine-crm/pull/72) | Integrado DEV: três valores inventados em medidores HTML; nenhuma ligação com ENT-04, backend, empresa ou cliente |
| F3-21 — prévia de mapa de colunas sintéticas | [PR #74](https://github.com/rafhael100/imagine-crm/pull/74) | Integrado DEV: mapa posicional único, incompletudes e validações negativas; sem resolver schema real nem autorizar importação |
| F3-22 — prévia CSV com cabeçalho efetivamente decodificado | [PR #75](https://github.com/rafhael100/imagine-crm/pull/75) | Integrado DEV: CSV sintético e mapeamento na mesma leitura; resultado agregado, nenhum conteúdo de célula ou `ALLOW` |
| UX-F3-23 — estados ilustrativos do mapeamento | [PR #76](https://github.com/rafhael100/imagine-crm/pull/76) | Integrado DEV: 3 cenários offline acessíveis, Chromium 520/320 CSS px; sem upload, backend ou dados de cliente |
| F3-23 — relatório sintético de avisos de prévia | [PR #78](https://github.com/rafhael100/imagine-crm/pull/78) | Integrado DEV: valida contagens e partições forjadas; emite avisos fixos de fórmulas/vazios/lacunas, sem autorização, conteúdo de célula ou contrato real |
| UX-F3-24 — avisos fictícios no protótipo | [PR #79](https://github.com/rafhael100/imagine-crm/pull/79) | Integrado DEV: três cenários acessíveis de revisão CSV, 520/320px Chromium, sem conexão com PHP, upload ou dados reais |
| F3-24 — contrato negativo por tenant/campos de importação | [PR #81](https://github.com/rafhael100/imagine-crm/pull/81) | Integrado DEV: valida forma, referência de organização, identidade apenas declarada e alvos mapeados; **sempre DENY**, mesmo com mapa sem avisos |
| F3-25 — tripwire de release para portabilidade | [PR #82](https://github.com/rafhael100/imagine-crm/pull/82) | Integrado DEV: testes negativos rejeitam novas rotas de portabilidade e qualquer método de autorização/mutação nos contratos sintéticos |
| CORE02-MX-01 — matriz negativa 864 combinações | [PR #84](https://github.com/rafhael100/imagine-crm/pull/84) | Integrado DEV: 6 ações × 9 classificações × 4 escopos × 4 canais, todas DENY; contextos forjados e membership inativa/irrelevante não concedem direito |
| CORE02-MX-02 — sessão/membership com 54 negações de efeito | [PR #85](https://github.com/rafhael100/imagine-crm/pull/85) | Integrado DEV: sessão experimental + membership **in-memory somente de teste** + `DenyByDefaultAuthorizer`, negação pós-revogação/troca de tenant/logout; teste não substitui RLS PostgreSQL |
| CORE02-R02 — pacote de revisão/decisão D04..D10 | [PR #87](https://github.com/rafhael100/imagine-crm/pull/87) | Integrado DEV documental: owners e evidências para encerrar D04..D10, **sem fechar** as decisões nem escolher política P1..P4 |
| CORE02-MX-03 — sinais mínimos em negativas | [PR #88](https://github.com/rafhael100/imagine-crm/pull/88) | Integrado DEV: 432 verificações com metadados sintéticos; não expor principal, tenant, campo, ação ou razão arbitrária no sinal; nenhum acesso real |
| UX-F3-25 — limites de leitura, exportação e tenant no protótipo | [PR #90](https://github.com/rafhael100/imagine-crm/pull/90) | Integrado DEV: três cenários de texto fixo acessíveis e testados no Chromium a 520/320 CSS px; sem IAM, exportação, backend ou alteração de direitos |
| UX-F3-26 — ficha individual de leads fictícios | [PR #92](https://github.com/rafhael100/imagine-crm/pull/92) | Integrado DEV: painel acessível de leitura, fechado ao ocultar item/alternar tela, acompanha ordenação; Chromium 520/320px; sem registros reais, edição, API ou permissão |
| F3-26 + UX-F3-27 — avaliação e eventos fictícios de histórico | [PR #94](https://github.com/rafhael100/imagine-crm/pull/94), [PR #95](https://github.com/rafhael100/imagine-crm/pull/95) | Integrados somente DEV; nenhuma leitura/alteração de histórico real, autoria ou cronologia comprovadas |
| UX-F3-28 — navegação entre funil e ficha | [PR #100](https://github.com/rafhael100/imagine-crm/pull/100) | Integrado somente DEV: quatro botões acessíveis, restauração de filtro, seleção única; PHPUnit e Chromium 520/320px |
| F3-28 — continuidade sintética de histórico | [PR #101](https://github.com/rafhael100/imagine-crm/pull/101) | Integrado somente DEV: duas fatias de até 32 alegações; detecta duplicata/gap/tenant/ator e SEMPRE nega proveniência/autoridade |
| F3-27 — tripwire negativo para rotas de histórico | [PR #102](https://github.com/rafhael100/imagine-crm/pull/102) | Integrado somente DEV: impede abertura acidental de endpoints e grants de histórico; não habilita leitura nem exportação |
| CI GitHub Actions — incidente de jobs não iniciados | [Issue #99](https://github.com/rafhael100/imagine-crm/issues/99) | Incidente de infraestrutura diagnosticado; jobs F0/PostgreSQL voltaram e ambos SUCCESS nos PRs #100–102; PRs legados #97/#98 encerrados sem merge |
| F3-29 — descritor de consultas de listas sem autoridade | [PR #104](https://github.com/rafhael100/imagine-crm/pull/104) | Integrado somente DEV: 8 chaves lab, campos e sort fechados, page_size 1..20, cursor `null`, 30 combinações sintáticas sempre DENY; nenhum endpoint/SQL |
| UX-F3-29 — filtros combinados e acessíveis | [PR #105](https://github.com/rafhael100/imagine-crm/pull/105) | Integrado somente DEV: Etapa × Frio/Morno/Quente, contagem 0..4, histórico fechado quando ocultado, reset no funil, testes Chromium 520/320 CSS px |
| UX-F3-30 — mensagem de zero resultados e limpeza de filtros | [PR #108](https://github.com/rafhael100/imagine-crm/pull/108) | Integrado DEV: estado vazio com `aria-live`, botão limpar filtros e foco restaurado, validação Chromium 520/320 CSS px; nenhuma consulta real |
| CORE/SEG/AUT/INT/LGPD | [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) e gates da documentação | **67 pendências CORE e gates especializados ABERTOS**, sem homologação |

## B. Evidências recentes de CI

- [F3-17 Laravel pós-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42555951350) e [PostgreSQL pós-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42555951348): **SUCCESS** na HEAD `d494bc4b920ca1553addbef34419f522b8346249`.
- [F3-18 Laravel pré-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42556600454): **643 passed / 77 skipped / 3.057 assertions**. [PostgreSQL pré-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42556600460): **719 passed / 1 skipped / 4.108 assertions**.
- [F3-18 Laravel pós-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42557261161) e [PostgreSQL pós-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42557261201): rodadas acionadas para HEAD `7e1658b157b9334c1967b9d5d5161bd6d7d00818`; **consultar logs/estado corrente antes de declarar concluídas**.
- A primeira CI F3-18 detectou rota `PUT storage/{path}` por `'serve' => true` no disco local; a revisão passou após `'serve' => false`. Isso **não** constitui homologação do uso futuro de anexos.

- [SEC-CI-02 push pós-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42564240499): PostgreSQL efêmero confirmado; [F0](https://github.com/rafhael100/imagine-crm/actions/runs/42564240500) também SUCCESS.
- [F3-20 push pós-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42565316394): **770 passed / 2 skipped**; [F0](https://github.com/rafhael100/imagine-crm/actions/runs/42565316373) **694 passed / 78 skipped**.
- [UX-F3-11 pré-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42574575588): **699 passed / 77 skipped**, [PostgreSQL](https://github.com/rafhael100/imagine-crm/actions/runs/42574575564) **775 passed / 1 skipped**.
- [UX-F3-12 pré-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42576254355): Chromium real PASS em 520 px após corrigir overflow de 1685 px; [PostgreSQL](https://github.com/rafhael100/imagine-crm/actions/runs/42576254354) SUCCESS.
- [UX-F3-13 pré-merge](https://github.com/rafhael100/imagine-crm/actions/runs/42577035221): Chromium real PASS em viewport CSS **320 px** e 520 px; Laravel **699 passed / 77 skipped**, [PostgreSQL](https://github.com/rafhael100/imagine-crm/actions/runs/42577035284) **775 passed / 1 skipped**. Sem screenshot/auditoria WCAG.
- [UX-F3-13 push pós-merge F0 #42577253275](https://github.com/rafhael100/imagine-crm/actions/runs/42577253275) e [PostgreSQL #42577253600](https://github.com/rafhael100/imagine-crm/actions/runs/42577253600): ambos **SUCCESS** na HEAD DEV `56f98f1df061866fe953325a926272d752456ae2`; PR checks da mesma SHA [F0 #42577259661](https://github.com/rafhael100/imagine-crm/actions/runs/42577259661) e [PostgreSQL #42577259656](https://github.com/rafhael100/imagine-crm/actions/runs/42577259656) também SUCCESS. Não representa homologação de UX/segurança de produção.

- [ENT-01 pré-merge F0 #42609578412](https://github.com/rafhael100/imagine-crm/actions/runs/42609578412): **726 passed / 77 skipped**; [PostgreSQL #42609578448](https://github.com/rafhael100/imagine-crm/actions/runs/42609578448) **802 passed / 1 skipped**; [push pós-merge F0 #42609828289](https://github.com/rafhael100/imagine-crm/actions/runs/42609828289) **725 passed / 78 skipped**, [PostgreSQL #42609828364](https://github.com/rafhael100/imagine-crm/actions/runs/42609828364) **801 passed / 2 skipped**, ambos SUCCESS.
- [UX-F3-14 pré-merge F0 #42610462217](https://github.com/rafhael100/imagine-crm/actions/runs/42610462217): **728 passed / 77 skipped** e Chromium real 520 px/320 px PASS, incluindo filtros; [PostgreSQL #42610462071](https://github.com/rafhael100/imagine-crm/actions/runs/42610462071): **804 passed / 1 skipped**. [Push pós-merge F0 #42610696263](https://github.com/rafhael100/imagine-crm/actions/runs/42610696263) **727 passed / 78 skipped** e [PostgreSQL #42610696197](https://github.com/rafhael100/imagine-crm/actions/runs/42610696197) **803 passed / 2 skipped**, ambos SUCCESS.
- A CI verde não demonstra homologação de quotas/entitlements, triagem comercial real, importações ou acesso multiempresa de clientes.

- **ENT-02 pré-merge** F0 [#42614485114](https://github.com/rafhael100/imagine-crm/actions/runs/42614485114) **758 passed / 77 skipped**; PostgreSQL [#42614485129](https://github.com/rafhael100/imagine-crm/actions/runs/42614485129) **834 passed / 1 skipped**. **Pós-merge** na DEV `00ee814bfed714d034624d9ccb700f5170b99be6`: [F0 #42614860559](https://github.com/rafhael100/imagine-crm/actions/runs/42614860559) e [PostgreSQL #42614860567](https://github.com/rafhael100/imagine-crm/actions/runs/42614860567), ambos **SUCCESS**.
- **ENT-03 pré-merge** na HEAD `bbe2683775f26202df46bb9b4f32c815d6d25326`: [F0 #42617084658](https://github.com/rafhael100/imagine-crm/actions/runs/42617084658) **772 passed / 77 skipped / 3.757 assertions** e Chromium 320px PASS; [PostgreSQL #42617084590](https://github.com/rafhael100/imagine-crm/actions/runs/42617084590) **848 passed / 1 skipped / 4.808 assertions**, ambos **SUCCESS**. **Pós-merge** na DEV `c163961ec1aee8536fcdb28c9d191fe02764442d`: [F0 #42617271487](https://github.com/rafhael100/imagine-crm/actions/runs/42617271487) e [PostgreSQL #42617271556](https://github.com/rafhael100/imagine-crm/actions/runs/42617271556), ambos **SUCCESS**.
- ENT-03 é *projeção de interface em segundos UTC*, não política contratual homologada de dias corridos. Permanecem pendentes marco confiável de início, único trial por CNPJ, consentimento/OTP, transição, restrição de acesso e retenção 60 dias. **Não confundir CI verde com autorização funcional ou de produção.**

- **UX-F3-15 (PR #70):** antes do merge `08e43e0be60ab941226ccda3e35f1fc19ef72046`, [F0 #42621077606](https://github.com/rafhael100/imagine-crm/actions/runs/42621077606) e [PostgreSQL #42621077572](https://github.com/rafhael100/imagine-crm/actions/runs/42621077572) **SUCCESS**. Depois do merge `85fa7dc3d201c6832ca197b23702374591966f6a`, [F0 #42621436004](https://github.com/rafhael100/imagine-crm/actions/runs/42621436004) e [PostgreSQL #42621436283](https://github.com/rafhael100/imagine-crm/actions/runs/42621436283) **SUCCESS**.
- **ENT-04 (PR #71):** antes do merge `b65cc300336deb3283fa24d856ba1290e98650a9`, [F0 #42621484352](https://github.com/rafhael100/imagine-crm/actions/runs/42621484352) e [PostgreSQL #42621484228](https://github.com/rafhael100/imagine-crm/actions/runs/42621484228) **SUCCESS**. Depois do merge `217cf8717398f9df0e0ff4ff82c5fb5ac4480f0e`, [F0 #42622233922](https://github.com/rafhael100/imagine-crm/actions/runs/42622233922) e [PostgreSQL #42622233763](https://github.com/rafhael100/imagine-crm/actions/runs/42622233763) **SUCCESS**.
- **UX-F3-16 (PR #72):** antes do merge `c3c7431cd0e6f64dbbc37c1c99b000cd6fb4b40e`, [F0 #42622096488](https://github.com/rafhael100/imagine-crm/actions/runs/42622096488) e [PostgreSQL #42622096668](https://github.com/rafhael100/imagine-crm/actions/runs/42622096668) **SUCCESS**. Depois do merge `550b56cf38111b5c0f67da90508bef70af5f85f4`, [F0 #42622445579](https://github.com/rafhael100/imagine-crm/actions/runs/42622445579) e [PostgreSQL #42622445696](https://github.com/rafhael100/imagine-crm/actions/runs/42622445696) **SUCCESS**. Integração também passou sem conflitos com ENT-04 no código, mas **não** acoplou os medidores HTML à classe PHP.
- Estes checks são regressões sintéticas e navegação em Chromium, **não** homologação de UX/WCAG, IAM, quotas, cobrança ou ambiente real.

- **F3-21 (PR #74):** pré-merge `1ce9baff42ad988fd2e07b92166af8cc45e25bdd`: [F0 #42624808070](https://github.com/rafhael100/imagine-crm/actions/runs/42624808070) e [PostgreSQL #42624808132](https://github.com/rafhael100/imagine-crm/actions/runs/42624808132), **SUCCESS**. Pós-merge DEV `5b2c1ac922c02129a602103a54d2b04342f8c198`: [F0 #42625012548](https://github.com/rafhael100/imagine-crm/actions/runs/42625012548) e [PostgreSQL #42625012740](https://github.com/rafhael100/imagine-crm/actions/runs/42625012740), **SUCCESS**.
- **F3-22 (PR #75):** pré-merge inicial [F0 #42625530833](https://github.com/rafhael100/imagine-crm/actions/runs/42625530833) **FAIL**, por teste que confundia chave estrutural `source` com cabeçalho; corrigido em `cf23e9499039a29bc42ff55976392242170c1434`. Após correção, [F0 #42625694524](https://github.com/rafhael100/imagine-crm/actions/runs/42625694524) e [PostgreSQL #42625694541](https://github.com/rafhael100/imagine-crm/actions/runs/42625694541) **SUCCESS**. Pós-merge DEV `0e3f6514ca8be8ff6693e0673857d7dc432c51f4`: [F0 #42626164022](https://github.com/rafhael100/imagine-crm/actions/runs/42626164022) e [PostgreSQL #42626164186](https://github.com/rafhael100/imagine-crm/actions/runs/42626164186), **SUCCESS**.
- **UX-F3-23 (PR #76):** pré-merge `a638903441a8443834678a8d06bfd4ad543762e0`: [F0 #42626077569](https://github.com/rafhael100/imagine-crm/actions/runs/42626077569) e [PostgreSQL #42626077545](https://github.com/rafhael100/imagine-crm/actions/runs/42626077545), **SUCCESS**. Pós-merge DEV `19cdc50fbbf195c177af35aba18e64dfd9e55f1d`: [F0 #42626397020](https://github.com/rafhael100/imagine-crm/actions/runs/42626397020) e [PostgreSQL #42626396846](https://github.com/rafhael100/imagine-crm/actions/runs/42626396846), **SUCCESS**.
- Todos são experimentos de CSV e interface com fixtures; **não** atestam autorização por empresa, LGPD, schemas finais, importação/exportação real, backup/restauração, WCAG ou homologação. Gates SEG/DBA, CORE, AUT, FIN/CON, INT e LGPD permanecem **abertos**.

- **F3-23 (PR #78):** antes de merge `013c604831dcf314099c777291f0ade7fa3958a2`, [F0 #42628263260](https://github.com/rafhael100/imagine-crm/actions/runs/42628263260) e [PostgreSQL #42628263172](https://github.com/rafhael100/imagine-crm/actions/runs/42628263172), **SUCCESS**. Depois de merge DEV `21011389439656b3417108c0e0ae84ddb8ea9031`, [F0 #42628494459](https://github.com/rafhael100/imagine-crm/actions/runs/42628494459) e [PostgreSQL #42628494586](https://github.com/rafhael100/imagine-crm/actions/runs/42628494586), **SUCCESS**.
- **UX-F3-24 (PR #79):** antes de merge `428a5549a14b4fee36106a02860961a3ae335a34`, [F0 #42628975234](https://github.com/rafhael100/imagine-crm/actions/runs/42628975234) e [PostgreSQL #42628975313](https://github.com/rafhael100/imagine-crm/actions/runs/42628975313), **SUCCESS**. Depois de merge DEV `91c54bebcbf34cd67e6b7b8b6858676191f4c348`, [F0 #42629166698](https://github.com/rafhael100/imagine-crm/actions/runs/42629166698) e [PostgreSQL #42629166702](https://github.com/rafhael100/imagine-crm/actions/runs/42629166702), **SUCCESS**. Ausência de avisos sintéticos jamais é evidência de importação autorizada.
- Todos os novos resultados são **laboratórios/instrumentos de UX**, não revisão independente de segurança, LGPD, dados de clientes, liberação de quotas ou autorização operacional.

- **F3-24 (PR #81):** antes do merge `a2aa6544e7ed7fcbc608b617672c8b1ae902be01`, [F0 #42631492068](https://github.com/rafhael100/imagine-crm/actions/runs/42631492068) e [PostgreSQL #42631492089](https://github.com/rafhael100/imagine-crm/actions/runs/42631492089), **SUCCESS**. Depois do merge DEV `a6d06d5e252da90faf706645ff53b83b756eb597`, [F0 #42631807569](https://github.com/rafhael100/imagine-crm/actions/runs/42631807569) e [PostgreSQL #42631807520](https://github.com/rafhael100/imagine-crm/actions/runs/42631807520), **SUCCESS**.
- **F3-25 (PR #82):** antes do merge `18d5e9d5dfb5b0b9e61a0b4c58631a2bce8f8a7f`, [F0 #42632263184](https://github.com/rafhael100/imagine-crm/actions/runs/42632263184) e [PostgreSQL #42632263337](https://github.com/rafhael100/imagine-crm/actions/runs/42632263337), **SUCCESS**. Depois do merge DEV `61ce0c43e65f41600c10c47d051e6ae2fc046c72`, [F0 #42632585564](https://github.com/rafhael100/imagine-crm/actions/runs/42632585564) e [PostgreSQL #42632585047](https://github.com/rafhael100/imagine-crm/actions/runs/42632585047), **SUCCESS**.
- Reforço de segurança de regressão somente: UUID e contexto construídos não são autenticação; `SCOPE_NOT_VERIFIED` permanece `allowed=false`; a CI negativa não aprova endpoints, IA, exportação, campos reais, privacidade ou homologação de produção.

- **CORE02-MX-01 (PR #84):** pré-merge `472e18d0429dbfd8e7f60d7605286f4dd2d33140`, [F0 #42643614247](https://github.com/rafhael100/imagine-crm/actions/runs/42643614247) e [PostgreSQL #42643614245](https://github.com/rafhael100/imagine-crm/actions/runs/42643614245), ambos **SUCCESS**. Pós-merge DEV `4fd28dd53d1456c084921b831b3bc7196ece618b`, [F0 #42643864944](https://github.com/rafhael100/imagine-crm/actions/runs/42643864944) e [PostgreSQL #42643864947](https://github.com/rafhael100/imagine-crm/actions/runs/42643864947), ambos **SUCCESS**. 864 casos de decisão **negativa** no laboratório.
- **CORE02-MX-02 (PR #85):** pré-merge `1099e5547b82cad58a8498e410e47fad20a869c6`, [F0 #42644700093](https://github.com/rafhael100/imagine-crm/actions/runs/42644700093) e [PostgreSQL #42644700137](https://github.com/rafhael100/imagine-crm/actions/runs/42644700137), ambos **SUCCESS**. Pós-merge DEV `1cf345612e479d9e90c98601eb99d664204de1b7`, [F0 #42644933725](https://github.com/rafhael100/imagine-crm/actions/runs/42644933725) e [PostgreSQL #42644933736](https://github.com/rafhael100/imagine-crm/actions/runs/42644933736), ambos **SUCCESS**. 54 chamadas de boundary negadas e zero callbacks executados; membership de laboratório em memória.
- Os dois testes são evidência de **negação e regressão**, não prova de política RBAC/ABAC homologada. Nenhuma permissão por perfil, organização, módulo, ação ou campo foi concedida. A política real, revisão independente SEG/DBA (Issue #27), CORE, AUT, INT e LGPD permanecem **bloqueadas**.

- **CORE02-R02 (PR #87):** pré-merge `a6e7b4fe1cea109afaef957f3875140023ffc863`, [F0 #42657595955](https://github.com/rafhael100/imagine-crm/actions/runs/42657595955) e [PostgreSQL #42657595977](https://github.com/rafhael100/imagine-crm/actions/runs/42657595977), **SUCCESS**. Pós-merge DEV `fc0efa2da60d0d3969f9ccb7de60ad53b6d7f769`, [F0 #42657820601](https://github.com/rafhael100/imagine-crm/actions/runs/42657820601) e [PostgreSQL #42657820812](https://github.com/rafhael100/imagine-crm/actions/runs/42657820812), **SUCCESS**. Só documentação, sem ratificação de D04..D10.
- **CORE02-MX-03 (PR #88):** pré-merge `7f74f090365b9ed1725c190aec618672f8a32450`, [F0 #42658154143](https://github.com/rafhael100/imagine-crm/actions/runs/42658154143) e [PostgreSQL #42658154280](https://github.com/rafhael100/imagine-crm/actions/runs/42658154280), **SUCCESS**. Pós-merge DEV `137dd0dffbb1134d38ccb7ef06711acf2e2a64cd`, [F0 #42658383163](https://github.com/rafhael100/imagine-crm/actions/runs/42658383163) e [PostgreSQL #42658383004](https://github.com/rafhael100/imagine-crm/actions/runs/42658383004), **SUCCESS**. São 432 decisões negativas e apenas sinais mínimos; não prova auditoria, privacidade nem operação real.
- Nenhum dos dois lotes concede direito, define perfil aprovado, altera RLS ou substitui a revisão independente SEG/DBA [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27). CORE, AUT, INT, FIN/CON e LGPD continuam **abertos**.

- **UX-F3-25 (PR #90):** pré-merge `de20b0ea4ed63387d96d711805d436671cfbd46f`, [F0 #42661786208](https://github.com/rafhael100/imagine-crm/actions/runs/42661786208) e [PostgreSQL #42661786212](https://github.com/rafhael100/imagine-crm/actions/runs/42661786212), ambos **SUCCESS**. Pós-merge DEV `713979ee75c06abb7bddf71fc0122ad5b9d6f78a`, [F0 #42662008250](https://github.com/rafhael100/imagine-crm/actions/runs/42662008250) e [PostgreSQL #42662008675](https://github.com/rafhael100/imagine-crm/actions/runs/42662008675), ambos **SUCCESS**. Testes PHPUnit/Chromium 520/320px dos três cenários inertes; revisão humana WCAG ainda pendente.
- Esta UI não comprova nem concede permissão. CORE02-D04..D10, revisão independente SEG/DBA [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), CORE, AUT, INT, FIN/CON e LGPD continuam **abertos**.

- **UX-F3-26 (PR #92):** pré-merge `e138c9499fef2fe0eaeabfb457ac5e5c315db5d6`, [F0 #42665269506](https://github.com/rafhael100/imagine-crm/actions/runs/42665269506) e [PostgreSQL #42665269478](https://github.com/rafhael100/imagine-crm/actions/runs/42665269478), ambos **SUCCESS**. Pós-merge DEV `4c120d0f9b1fdf475f71649a6fc9de73b7c1c3cf`, [F0 #42665534050](https://github.com/rafhael100/imagine-crm/actions/runs/42665534050) e [PostgreSQL #42665534128](https://github.com/rafhael100/imagine-crm/actions/runs/42665534128), ambos **SUCCESS**. Quatro fichas fictícias somente de leitura; smoke no navegador 520/320 CSS px; revisão humana de UX/WCAG ainda pendente.
- Esta UX não representa autorização, scoring real nem produto funcional. CORE02-D04..D10, revisão independente SEG/DBA [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), demais gates CORE, AUT, INT, FIN/CON e LGPD permanecem **abertos**.

- **UX-F3-28 (PR #100):** pré-merge `f7a171e865de1eec44c4b5d37973238d4faaf913`, [F0 #42678798986](https://github.com/rafhael100/imagine-crm/actions/runs/42678798986) e [PostgreSQL #42678798702](https://github.com/rafhael100/imagine-crm/actions/runs/42678798702), SUCCESS. Pós-merge DEV `7cfee526636e09e6458274333d54747f26cc50db`, [F0 #42678997427](https://github.com/rafhael100/imagine-crm/actions/runs/42678997427) e [PostgreSQL #42678996900](https://github.com/rafhael100/imagine-crm/actions/runs/42678996900), SUCCESS.
- **F3-28 (PR #101):** pré-merge `4dbcf13283ea1f112d084ea76b93571f37dbc4dd`, [F0 #42679291364](https://github.com/rafhael100/imagine-crm/actions/runs/42679291364) e [PostgreSQL #42679291196](https://github.com/rafhael100/imagine-crm/actions/runs/42679291196), SUCCESS. Pós-merge DEV `49b8c320cfdbfee6993d5a49a06a86d34f667d0f`, [F0 #42679516406](https://github.com/rafhael100/imagine-crm/actions/runs/42679516406) e [PostgreSQL #42679516052](https://github.com/rafhael100/imagine-crm/actions/runs/42679516052), SUCCESS. O [PR #98](https://github.com/rafhael100/imagine-crm/pull/98) foi encerrado sem merge após reaplicação.
- **F3-27 (PR #102):** pré-merge `aa3183aaeff7754666d5864ce970c6b0dd5ad1f1`, [F0 #42679784601](https://github.com/rafhael100/imagine-crm/actions/runs/42679784601) e [PostgreSQL #42679784470](https://github.com/rafhael100/imagine-crm/actions/runs/42679784470), SUCCESS. Pós-merge DEV `1aeb1cc7b80bf8e41d890e965a69280241c85b00`, [F0 #42680011059](https://github.com/rafhael100/imagine-crm/actions/runs/42680011059) e [PostgreSQL #42680011000](https://github.com/rafhael100/imagine-crm/actions/runs/42680011000), SUCCESS. O [PR #97](https://github.com/rafhael100/imagine-crm/pull/97) foi encerrado sem merge após reaplicação.
- **Recuperação CI:** [Issue #99](https://github.com/rafhael100/imagine-crm/issues/99), falhas históricas de 09/11 sem jobs associadas à instabilidade de GitHub Actions/Checks API. Todos os seis workflows de PR e seis pós-merge de #100–102 criaram jobs reais e passaram; não se alteraram definições de workflows, permissões ou cobrança como workaround.
- **Gates que NÃO foram encerrados:** 67 identificadores CORE na Etapa 2, CORE02-D04..D10, revisão independente SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27), INT/FIN/CON/AUT/LGPD e revisão humana de UX/WCAG. Sem autenticação/autorização funcional de histórico, `ALLOW`, grants, banco real, exportação ou produto lançado.

- **F3-29 (PR #104):** pré-merge `c189b98f33c087b62b1ccf89d57d56c3d89cee35`, [F0 #42686992621](https://github.com/rafhael100/imagine-crm/actions/runs/42686992621) e [PostgreSQL/RLS #42686992656](https://github.com/rafhael100/imagine-crm/actions/runs/42686992656), SUCCESS. Pós-merge DEV `4792a31ace847dd901672cb688e9b88ec210b0e9`, [F0 #42687265909](https://github.com/rafhael100/imagine-crm/actions/runs/42687265909) e [PostgreSQL #42687265970](https://github.com/rafhael100/imagine-crm/actions/runs/42687265970), SUCCESS. Só proposta sintática fechada `lab.lead` e sinais mínimos; não valida tenant ou concede direito.
- **UX-F3-29 (PR #105):** pré-merge `bafbcec927836138ca76acfd5cbf9dbbeacb5478`, [F0 #42688222263](https://github.com/rafhael100/imagine-crm/actions/runs/42688222263) e [PostgreSQL #42688222447](https://github.com/rafhael100/imagine-crm/actions/runs/42688222447), SUCCESS. Pós-merge DEV `842f443d757bf421ab517b53ce8b4d791a466e6c`, [F0 #42688525982](https://github.com/rafhael100/imagine-crm/actions/runs/42688525982) e [PostgreSQL #42688526044](https://github.com/rafhael100/imagine-crm/actions/runs/42688526044), SUCCESS. Somente quatro dados fictícios e botões nativos; nenhuma requisição de backend.
- **Gates ainda abertos:** CORE02-D04..D10 e demais CORE; revisão independente SEG/DBA [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27), INT/DAT/FIN/CON/AUT/LGPD, autorização de tenant/campo e revisão humana de acessibilidade. Sem `ALLOW`, migrations operacionais, produção, `main`, dados de clientes nem VPS.

- **UX-F3-30 (PR #108):** pré-merge `7c8f5e4103aad8b6da4c7c10d9e354c983a51568`, [F0 #42689646117](https://github.com/rafhael100/imagine-crm/actions/runs/42689646117) e [PostgreSQL #42689646134](https://github.com/rafhael100/imagine-crm/actions/runs/42689646134), **SUCCESS**. Pós-merge DEV `0b4eb0d5ef768f33cb515510236de32df9921dd5`, [F0 #42689998029](https://github.com/rafhael100/imagine-crm/actions/runs/42689998029) e [PostgreSQL #42689998124](https://github.com/rafhael100/imagine-crm/actions/runs/42689998124), **SUCCESS**. Testes de DOM e Chromium 520/320 px verificam estado vazio, reset e foco. Todas as ações continuam offline; revisão manual WCAG ainda pendente.
- CORE02-D04..D10 e revisão independente SEG/DBA [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) permanecem **abertas**; não há política de grants, `ALLOW`, leitura/exportação de clientes, publicação ou homologação funcional em decorrência destes testes.

## C. Bloqueios para passar de laboratório a produto

1. **Revisor SEG/DBA independente NÃO DESIGNADO:** [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27). O documento [F3-14-R01](https://github.com/rafhael100/imagine-crm/blob/docs/f3-14-lead-ledger-transactional-design-2026-11-08/docs/desenvolvimento/F3-14-R01-pacote-revisao-independente-seg-dba-2026-11-09.md), integrado **apenas no PR #49 DRAFT**, reúne riscos e critérios de aceite; **não é parecer**.
2. **IAM/tenant:** `app.tenant_id` mutável precisa de origem autenticada, membership vigente, delegação, escopo por recurso/campo e limpeza em workers/pools; a RLS não é autorização autossuficiente.
3. **Ledger/captura:** nenhum schema dedicado aprovado, confirmação final CRM, idempotência distribuída ou recepção real; necessita decisão de arquitetura e parecer SEG/DBA.
4. **Privacidade:** finalidade/base legal, aviso/controlador, retenção/exclusão, segurança dos contatos e auditoria devem ser aceitos antes de receber dados reais.
5. **Release:** `DenyByDefaultAuthorizer` permanece deny-only; não autorizar `ALLOW`, APIs públicas, migrations operacionais, `main`, VPS ou produção por mera CI verde.

## D. Fluxo de continuidade autorizado

Prosseguir com **branches independentes, PRs revisáveis, fixtures sintéticas, CI e testes de segurança** sem solicitar confirmações rotineiras. Só requerer decisão humana sobre mudanças críticas de arquitetura, contratação/designação do revisor independente, permissões, acesso a dados reais, despesas e implantação. GitHub e o estado efetivo das PRs/Actions prevalecem sobre este resumo datado.

---

## E. Histórico arquivado de 06/11/2026 (preservado para rastreabilidade)

**Atualizado em:** 06/11/2026 (America/Sao_Paulo).  
**Base:** GitHub Issues, PRs, commits e Actions consultados na data.  
**Natureza:** registro operacional datado, não aprovação de gates, homologação de segurança nem autorização de produção.

## 1. Situação resumida

- **Fase:** desenvolvimento técnico controlado e experimental com Laravel 13, PostgreSQL 16 e Redis em CI, sempre com fixtures sintéticas, sem autorização de negócio.
- **Branch de trabalho:** [`dev/fast-track-crm-2026-11-04`](https://github.com/rafhael100/imagine-crm/tree/dev/fast-track-crm-2026-11-04). **F1-02 a F1-10 integradas exclusivamente na DEV**.
- **Último merge:** [PR #20 — F1-10](https://github.com/rafhael100/imagine-crm/pull/20) fechado como MERGED, commit `6e3d02f432e0e6e6e1c9322c2d286b035661c5c2`, autorização expressa para a DEV.
- **`main`:** permanece no commit verificado `e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012`. [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) continua **DRAFT** para `main`, sem merge.
- **Recorte escolhido:** [Issue #22 — FUNC-P0-01](https://github.com/rafhael100/imagine-crm/issues/22): **A aprovado exclusivamente para DETALHAMENTO DOCUMENTAL** da autoconsulta própria read-only. [Diretrizes S01](docs/desenvolvimento/FUNC-P0-01-S01-DEC-ratificacao-diretrizes-preliminares-D01-D08-2026-11-06.md) **RATIFICADAS PRELIMINARMENTE só em documentação**; [R01 campos/estados/segurança](docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) **AGUARDANDO AVALIAÇÃO**; nenhuma autorização de código, `ALLOW`, login/rotas reais ou ambiente de clientes.
- **VPS/produção:** não foi feito deploy nesta sequência. Não foram habilitados endpoints públicos, jobs, webhooks, automações de clientes, credenciais reais ou permissões de dados comerciais.

## 2. Entregas

| Etapa | Evidência | Estado |
| --- | --- | --- |
| F0-02 | [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) | DRAFT para `main`, não integrado |
| F1-01 | [Prova IAM/RLS](docs/desenvolvimento/F1-01-modelo-IAM-RLS-evidencias-CI-2026-11-04.md) | Prova experimental, sem homologação |
| F1-02 | [PR #4](https://github.com/rafhael100/imagine-crm/pull/4) | Integrada em DEV, `3ec4cfa0621140056eab274826a639aba125e0a6` |
| F1-03 | [PR #6](https://github.com/rafhael100/imagine-crm/pull/6) | Integrada em DEV, `3a3a5b816843ca1fd7d329fa6f0d467406a3ff10` |
| F1-04 | [PR #8](https://github.com/rafhael100/imagine-crm/pull/8) | Integrada em DEV, `407a2ead654864cd457436080999736a1186e219` |
| F1-05 | [PR #10](https://github.com/rafhael100/imagine-crm/pull/10) | Integrada em DEV, `9cc5ae4214e07903b2b69d9d40541c61f395d4bf` |
| F1-06 | [PR #12](https://github.com/rafhael100/imagine-crm/pull/12) / [Issue #11](https://github.com/rafhael100/imagine-crm/issues/11) | Integrada em DEV, `691f8c0e508fabae5d3560cca044bf280d2fcfb5`. Contratos A–E de autoridade assíncrona inertes/deny-only |
| F1-07 | [PR #14](https://github.com/rafhael100/imagine-crm/pull/14) / [Issue #13](https://github.com/rafhael100/imagine-crm/issues/13) | Integrada exclusivamente na DEV, merge `71cecea7d53fd5f2608d4b0b96d685c696a144ed`; telemetria e reconciliação sanitizadas e inertes |
| F1-08 | [PR #16](https://github.com/rafhael100/imagine-crm/pull/16) / [Issue #15](https://github.com/rafhael100/imagine-crm/issues/15) | Integrada exclusivamente na DEV, merge `2923cc57afe240f8aa6024d7028a75bb8f8b54ef`; ledger PostgreSQL sintético e inbox/outbox inertes |
| F1-09 | [PR #18](https://github.com/rafhael100/imagine-crm/pull/18) / [Issue #17](https://github.com/rafhael100/imagine-crm/issues/17) | Integrada exclusivamente na DEV, merge `af4cd127655572f9ce7327b4f52dca36a5e0a864`; integridade, restauração sintética e reconciliação consultiva, sem efeitos |
| F1-10 | [PR #20](https://github.com/rafhael100/imagine-crm/pull/20) / [Issue #19](https://github.com/rafhael100/imagine-crm/issues/19) | Integrada em DEV, merge `6e3d02f432e0e6e6e1c9322c2d286b035661c5c2`; baseline regressiva e matriz 67 CORE, sem permissão comercial |
| FUNC-P0-01 A | [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) / [escopo](docs/desenvolvimento/FUNC-P0-01-contratos-primeiro-recorte-funcional-proposta-v0.1.md) | **APROVADO APENAS PARA ESPECIFICAÇÃO DOCUMENTAL**, autoconsulta própria sem código |
| FUNC-P0-01 S01-D01..D08 | [Ratificação preliminar](docs/desenvolvimento/FUNC-P0-01-S01-DEC-ratificacao-diretrizes-preliminares-D01-D08-2026-11-06.md) | **D01/D02/D03 aprovadas apenas como diretrizes documentais; D04..D08 aprovadas em propósito geral**, sem mecanismos, código ou homologação |
| FUNC-P0-01 S01-R01 | [Proposta campos/estados/segurança](docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) | **R01-D01..D05 aguardam avaliação**: CAM-01..10, EST-01..14, matriz de segurança e evidências futuras |

## 3. Testes conferidos

**Antes do merge F1-06:** head `fd08a9212a43c01d9b8a66f1188a361e66b14774`.
- [Laravel/SQLite — run 42070845197](https://github.com/rafhael100/imagine-crm/actions/runs/42070845197): **307 aprovados, 33 ignorados, 1.014 asserções**, success.
- [PostgreSQL/Redis — run 42070845055](https://github.com/rafhael100/imagine-crm/actions/runs/42070845055): **339 aprovados, 1 ignorado, 1.233 asserções**, success.

**Depois do merge F1-06:** SHA `691f8c0e508fabae5d3560cca044bf280d2fcfb5`.
- [Laravel — run 42073457091](https://github.com/rafhael100/imagine-crm/actions/runs/42073457091): **success**.
- [PostgreSQL/RLS/Redis — run 42073457093](https://github.com/rafhael100/imagine-crm/actions/runs/42073457093): **success**.

**F1-07 antes do merge:** head `c4255fd728ff29f68a8ca3668eef8861e633134d`.
- [Laravel/SQLite — run 42089372399](https://github.com/rafhael100/imagine-crm/actions/runs/42089372399): **337 aprovados, 36 ignorados, 1.304 asserções**, success.
- [PostgreSQL/Redis — run 42089372691](https://github.com/rafhael100/imagine-crm/actions/runs/42089372691): **372 aprovados, 1 ignorado, 1.569 asserções**, success.

**F1-07 depois do merge:** SHA `71cecea7d53fd5f2608d4b0b96d685c696a144ed`.
- [Laravel — run 42092229368](https://github.com/rafhael100/imagine-crm/actions/runs/42092229368): **success**.
- [PostgreSQL/RLS/Redis — run 42092229274](https://github.com/rafhael100/imagine-crm/actions/runs/42092229274): **success**.

**F1-08 antes do merge:** SHA `49bd5b661ddee3fec434cd550530c07c1430e878`.
- [Laravel/SQLite run 42119017856](https://github.com/rafhael100/imagine-crm/actions/runs/42119017856): **340 aprovados, 49 ignorados, 1.366 asserções**, success.
- [PostgreSQL 16/Redis 7 run 42119018061](https://github.com/rafhael100/imagine-crm/actions/runs/42119018061): **388 aprovados, 1 ignorado, 1.827 asserções**, success.

**F1-08 pós-merge na DEV:** SHA `2923cc57afe240f8aa6024d7028a75bb8f8b54ef`.
- [Laravel run 42122175413](https://github.com/rafhael100/imagine-crm/actions/runs/42122175413): **success**.
- [PostgreSQL/RLS/Redis run 42122175636](https://github.com/rafhael100/imagine-crm/actions/runs/42122175636): **success**.

**F1-09 antes do merge:** head `6c8dd1dbe9b0c5ff78402ff7d186cae571825536`.
- [Laravel/SQLite — run 42126536166](https://github.com/rafhael100/imagine-crm/actions/runs/42126536166): **366 aprovados, 63 ignorados, 1.574 asserções**, success.
- [PostgreSQL 16/RLS/Redis 7 — run 42126536165](https://github.com/rafhael100/imagine-crm/actions/runs/42126536165): **428 aprovados, 1 ignorado, 2.250 asserções**, success.

**F1-09 depois do merge na DEV:** SHA `af4cd127655572f9ce7327b4f52dca36a5e0a864`.
- [Laravel — run 42130265061](https://github.com/rafhael100/imagine-crm/actions/runs/42130265061): **success**.
- [PostgreSQL/RLS/Redis — run 42130265271](https://github.com/rafhael100/imagine-crm/actions/runs/42130265271): **success**.

**F1-10, SHA pré-merge `b6a5d4e3afd2d34a00846783afb89aff60fbab9a`:**
- [Laravel/SQLite 42154310465](https://github.com/rafhael100/imagine-crm/actions/runs/42154310465): **383 aprovados, 64 ignorados, 2.205 asserções**, success.
- [PostgreSQL/RLS/Redis 42154310247](https://github.com/rafhael100/imagine-crm/actions/runs/42154310247): **446 aprovados, 1 ignorado, 2.992 asserções**, success.

**F1-10 após merge em DEV, SHA `6e3d02f432e0e6e6e1c9322c2d286b035661c5c2`:**
- [Laravel 42155367413](https://github.com/rafhael100/imagine-crm/actions/runs/42155367413): **success**.
- [PostgreSQL/RLS/Redis 42155367330](https://github.com/rafhael100/imagine-crm/actions/runs/42155367330): **success**.

Novos commits de documentação disparam novos checks. Verificar `HEAD` e Actions atuais antes de abrir ou integrar PR. Suítes sintéticas não comprovam prontidão para produção.

## 4. Segurança e pendências preservadas

1. **`DenyByDefaultAuthorizer` continua a única autorização ativa**, sem política `ALLOW`. Contratos `AuthorizationIntent`, `AuthorizationEnvelope`, `AsyncAuthorityRequest` e `AsyncAuthorityBridge` são modelos de laboratório, não credenciais ou direitos transportáveis.
2. CORE01..CORE10, incluindo CORE02-D04..D10 e CORE03/CORE09-D04..D10, os **67 IDs técnicos CORE**, SEG-01/02, ARQ/INT/FIN/CON/LGPD permanecem abertos conforme decisões/aceites por módulo. D01..D03 de CORE03/09 são bases documentais aprovadas, não liberação técnica.
3. Um `tenant_id`/GUC `app.tenant_id` fornecido não autentica. RLS, sessão, membership, identidade técnica, dono de módulo e autorização exigem comprovação independente.
4. F1-06 tem somente inspeção/rejeição para classes A–E; não faz dispatch, não expõe webhook nem confirma fato comercial.
5. F1-07 acrescentou diagnóstico sanitizado e reconciliação **sintética**; nenhum sink de observação, resultado oficial ou retry produtivo está habilitado. F1-08 não poderá introduzir ALLOW nem efeitos externos. HTTP 2xx/recibo técnico não comprovam resultado de negócio.
6. A baseline F1-10 e as 67 decisões CORE abertas impedem interpretar CI como GO funcional. CORE02-D05-B e D06..D10 no PR #21 são princípios documentais preliminares, nunca política ALLOW nem migração de grants.
7. Acesso de clientes, dados pessoais, segredos, n8n, infraestrutura, fila/outbox/inbox **produtivos**, contratação e VPS não estão autorizados. A F1-08 contém persistência **isolada em laboratório**, sem transportador ou publicação. A F1-09 integrou apenas leitura/validação sintética e reconciliação consultiva, nunca correção automática ou efeitos. A escrita sintética F1-08 não comprova autoridade/entrega, e o teste de restauração F1-09 não comprova RPO/RTO ou recuperação real. CI verde não encerra gates.

## 5. Próxima tarefa de desenvolvimento controlado

A [Issue #22 — FUNC-P0-01](https://github.com/rafhael100/imagine-crm/issues/22) registra **RECORTE A ESCOLHIDO APENAS PARA DETALHAMENTO DOCUMENTAL**. A [S01](docs/desenvolvimento/FUNC-P0-01-S01-autoconsulta-organizacao-membership-contratos-homologacao-proposta-v0.1.md) tem **D01/D02/D03 e diretrizes gerais D04..D08 APROVADAS SOMENTE DOCUMENTALMENTE**, mas seus detalhes físicos, operacionais e implementações **NÃO HOMOLOGADOS** para a tela de autoconsulta read-only do próprio tenant/vínculo, sem API/login público, gestão de usuários ou acesso de staff Imagine. Reúne contratos S01-C01..C06, 22 cenários de homologação futura e oito decisões S01-D01..D08, cujas diretrizes foram ratificadas preliminarmente. A [S01-R01](docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) e seus itens R01-D01..D05 **não foram aprovados**. Direção e owners ainda devem aprovar campos, autorização, estados e gates antes de qualquer programação, `ALLOW` ou implementação de testes de homologação. **Não encaminhar S01 ao Codex como tarefa de código.** 

[PR documental #21](https://github.com/rafhael100/imagine-crm/pull/21) continua DRAFT para `main`; seu head observado `0e702e63acdc6c4cc3e67b19d10db10fa252be7f` registra ratificações preliminares CORE02-D06..D10, **CORE02-D05 alternativa B (P1+P2 escopada)** e **CORE02-D05-GOV01 Modelo M2** ratificados expressamente em 06/11. **CORE02-D05-GOV02 permanece somente PROPOSTA, sem aprovação**. Não está integrado ao DEV/main por esta tarefa nem fecha CORE02-D05..D10. [PR #2](https://github.com/rafhael100/imagine-crm/pull/2) permanece DRAFT e não foi alterado. O próximo passo é **avaliação do pacote pela direção**, não envio automático ao Codex.

**Fronteira de aprovação S01 (06/11/2026):** a escolha A e as diretrizes preliminares S01-D01..D08 para *documentar* estão aprovadas; **S01-R01 campos/estados/segurança segue PROPOSTA NÃO APROVADA**; os contratos operacionais e critérios físicos não têm aceite próprio, não são executáveis e não reduzem os 67 CORE, SEG, AUT, ARQ/INT/FIN/CON/LGPD e G01..G06. Nenhum teste positivo de autoconsulta real foi executado. A política B P1+P2 e GOV01 M2 são ratificações documentais; GOV02 permanece NÃO aprovada. 

## 6. Governança

O `AGENTS.md` é instrução de programação, `STATUS-ATUAL.md` é resumo histórico e `docs/planejamento` contém decisões normativas. GitHub PRs, Issues e checks prevalecem para situação operacional. Nenhum registro documental habilita produção.

## D. Rodada DEV controlada — 09/11/2026

| PR | Incremento DEV |
|---|---|
| [#109](https://github.com/rafhael100/imagine-crm/pull/109) | OPS-15: documentação dos filtros fictícios |
| [#110](https://github.com/rafhael100/imagine-crm/pull/110) | SEC-UX-01: teste de recursos externos no protótipo |
| [#111](https://github.com/rafhael100/imagine-crm/pull/111) | UX-SEC-02: teste de integridade da navegação |
| [#112](https://github.com/rafhael100/imagine-crm/pull/112) | SEC-UX-03: bloqueio estático de sinks perigosos |
| [#113](https://github.com/rafhael100/imagine-crm/pull/113) | F3-31: contagem sintética por etapa e faixa, sem dados reais |
| [#114](https://github.com/rafhael100/imagine-crm/pull/114) | F3-32: comparação aritmética de snapshots fictícios, sem identidade nem conversão |
| [#115](https://github.com/rafhael100/imagine-crm/pull/115) | OPS-16: matriz documental [GO/NO-GO](docs/desenvolvimento/CRM-GO-NOGO-matriz-primeira-funcionalidade-2026-11-09.md) |
| [#116](https://github.com/rafhael100/imagine-crm/pull/116) | F3-33: verificação de consistência entre fixtures HTML e agregador PHP |

**Estado de aprovação:** PRs acima integrados exclusivamente na DEV com testes pré-merge F0 e PostgreSQL/RLS aprovados. Para cada HEAD de DEV deve-se conferir novamente as duas CIs após merge; os números de PR não provam homologação nem release.

**NO-GO funcional ainda vigente:** R01-D01..D05 sem ratificação itemizada, [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) sem revisor independente, e CORE02/IAM/CORE04/CORE07/CORE08/LGPD/QA ainda abertos. Nada de cliente real, ALLOW, migração de produto, main, VPS ou produção.


## E. Laboratório de visualização de leads F3-34..F3-37 — 09/11/2026

| Etapa | Entrega e limite |
|---|---|
| [#118](https://github.com/rafhael100/imagine-crm/pull/118) F3-34 | Contador por etapa/faixa fictícias, 16 combinações, validado exclusivamente em memória; DEV `77e59479` |
| [#119](https://github.com/rafhael100/imagine-crm/pull/119) F3-35 | Matriz 3x3 de facetas e fonte única para filtros; DEV `b9652bb9` |
| [#120](https://github.com/rafhael100/imagine-crm/pull/120) F3-36 | Ordenação estável de posições de exemplos, sem IDs; DEV `c049a808` |
| [#121](https://github.com/rafhael100/imagine-crm/pull/121) F3-37 | Prévia composta filtro+ordenacao: 48 combinações, dados exclusivamente artificiais; DEV `c0d67814` |

**Evidência CI:** todos os quatro PRs tiveram F0 e PostgreSQL/RLS verdes na HEAD; os dois workflows pós-merge da HEAD `c0d67814` também passaram: [F0 #42733335721](https://github.com/rafhael100/imagine-crm/actions/runs/42733335721), [RLS #42733335724](https://github.com/rafhael100/imagine-crm/actions/runs/42733335724). Essas provas não executam política de `ALLOW` nem comprovam release de CRM real.

**Gates preservados:** [S01/R01-D01..D05](https://github.com/rafhael100/imagine-crm/issues/22) continuam sem ratificação operacional, [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) exige revisão independente, e CORE02/IAM/CORE04/CORE07/CORE08/LGPD/QA permanecem abertos. Nenhuma implantação, API de clientes, SQL de negócio, dados pessoais, grants ou alterações em `main`/VPS foi autorizada por esses testes.


## F. F3-38 / UX-F3-38 / UX-F3-39 — 09/11/2026

| PR | Resultado validado somente DEV |
|---|---|
| [#123](https://github.com/rafhael100/imagine-crm/pull/123) F3-38 | Paginação local, cursor-free, de até 20 fixtures sintéticas: posições, tamanho 1..20, intervalo de páginas, critérios negativos e 240 combinações de filtros/ordem/tamanhos; DEV `c0556939` |
| [#124](https://github.com/rafhael100/imagine-crm/pull/124) UX-F3-38 | Paginação acessível do HTML offline com 2/4 exemplos, botões anterior/próxima e testes reais Chromium 520/320 CSS pixels; DEV `394e203f` |
| [#125](https://github.com/rafhael100/imagine-crm/pull/125) UX-F3-39 | Navegação do funil para página correta do exemplo e foco do teclado, também verificada nos dois tamanhos de viewport; DEV `eab49ced` |

**Checks pós-merge confirmados:** #123 [F0](https://github.com/rafhael100/imagine-crm/actions/runs/42736369674) + [PG/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42736369156); #124 [F0](https://github.com/rafhael100/imagine-crm/actions/runs/42736895340) + [PG/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42736895373); #125 [F0](https://github.com/rafhael100/imagine-crm/actions/runs/42737318558) + [PG/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42737318162), todos `success` nas SHAs verificadas.

**Limite:** esses casos validam apenas laboratório sintético e HTML estático. Não constituem autenticação, isolamento de clientes em operação, paginação real, conversão de leads, comercialização, homologação de segurança ou aprovação LGPD. Nenhum endpoint, migration de produto, direito positivo/ALLOW, importação real, VPS ou `main` foi alterado nesta rodada.

**NO-GO permanece:** [S01/R01-D01..D05](https://github.com/rafhael100/imagine-crm/issues/22) sem deliberação executável; [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) ainda precisa de revisor independente real; CORE02/04/06/07/08/09/10, AUT, LGPD, FIN/CON e demais gates sem aceite específico. Não confundir múltiplos testes verdes com produto pronto para clientes.


## G. F4-01 — núcleo de acompanhamentos e demo offline (09/11/2026)

| PR | Entrega DEV | Validação pós-merge |
|---|---|---|
| [#127](https://github.com/rafhael100/imagine-crm/pull/127) | Classificação UTC de até 20 fixtures anônimas, contagens, ordenação de posições, testes negativos; DEV `063648d9` | [F0 #42739900102](https://github.com/rafhael100/imagine-crm/actions/runs/42739900102) + [PG/RLS #42739900562](https://github.com/rafhael100/imagine-crm/actions/runs/42739900562), ambos success |
| [#128](https://github.com/rafhael100/imagine-crm/pull/128) | Prévia acessível de quatro estados de prazo no monitor offline, cinco tarefas inventadas e testes reais Chromium/320px; DEV `1a14216b` | [F0 #42740358075](https://github.com/rafhael100/imagine-crm/actions/runs/42740358075) + [PG/RLS #42740358212](https://github.com/rafhael100/imagine-crm/actions/runs/42740358212), ambos success |

**Handoff técnico:** [F4-01: limites, evidências e critérios de GO](docs/desenvolvimento/CRM-F4-01-atividades-handoff-laboratorio-2026-11-09.md). A fila é puramente aritmética, não gere SLA, mandato, identidade, task real, APIs, lembretes ou filas de trabalho. A interface usa texto fixo e CSP offline. O código não concede autorizações e não grava informação pessoal.

**NO-GO funcional vigente:** [R01-D01..D05 / Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) seguem propostas sem aceite operacional, [Issue SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) não possui parecer independente itemizado; CORE/AUT/LGPD/FIN/CON permanecem em aberto. Não aprovar cliente, `main`, VPS ou releases por CI laboratorial.


## H. F4-02 e SEC-F4-03 — evolução restrita e provas (09/11/2026)

| PR integrado apenas DEV | Entrega e garantia limitada | CI pós-merge |
|---|---|---|
| [#130](https://github.com/rafhael100/imagine-crm/pull/130) — F4-02 | PHP puro: filtros de cinco estados de prazos UTC sintéticos, ordenação estável e paginação local, sem autoridade, cursor real ou dados de cliente. SHA `76a8d9eb`. | [F0 #42743813555](https://github.com/rafhael100/imagine-crm/actions/runs/42743813555) + [PG/RLS #42743813791](https://github.com/rafhael100/imagine-crm/actions/runs/42743813791): success |
| [#131](https://github.com/rafhael100/imagine-crm/pull/131) — UX-F4-02 | Lista fictícia paginada na interface offline, controles acessíveis e testes no navegador a 320 CSS pixels. SHA `593f3a26`. | [F0 #42744165899](https://github.com/rafhael100/imagine-crm/actions/runs/42744165899) + [PG/RLS #42744165945](https://github.com/rafhael100/imagine-crm/actions/runs/42744165945): success |
| [#132](https://github.com/rafhael100/imagine-crm/pull/132) — SEC-F4-03 | Gate regressivo para impedir exposição de rotas públicas de tarefas/atividades/avisos sem aceite e confirmar ausência de autoridade dos laboratórios. SHA `f3f69dff`. | [F0 #42744477725](https://github.com/rafhael100/imagine-crm/actions/runs/42744477725) + [PG/RLS #42744477812](https://github.com/rafhael100/imagine-crm/actions/runs/42744477812): success |

**Relatório/handoff:** [F4-01/F4-02/SEC-F4-03 — limites e próximos controles](docs/desenvolvimento/CRM-F4-01-atividades-handoff-laboratorio-2026-11-09.md). Nenhuma operação de criação/edição/atribuição de atividade, agendamento, notificação, SLA real, leitura de lead de cliente, worker, webhook, API, login comercial, RLS de produção ou receita foi implementada.

**Impedimentos de produto ainda abertos:** decisões [S01-R01-D01..D05 / Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) sem ratificação itemizada; [revisão SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) ainda sem revisor técnico independente; CORE02/IAM/CORE04/CORE07/CORE08/LGPD/QA e demais gates sem aceite. Nenhuma solicitação genérica de continuidade é prova suficiente de autorização operacional. `main`/VPS/produção continuam protegidas.


## I. F4-04 / UX-F4-04 / QA-F4-05 — consolidação de horizonte UTC (09/11/2026)

| PR somente DEV | Entrega e escopo limitado | Evidência pós-merge |
|---|---|---|
| [#134](https://github.com/rafhael100/imagine-crm/pull/134) — F4-04 | Agregação de até 20 fixtures anônimas por 1..14 dias UTC; separação de vencido/agora/futuros dentro/fora da janela, sem SLA, `authorized=false` e `can_notify=false`. DEV `fad917b7`. | [F0 #42746292021](https://github.com/rafhael100/imagine-crm/actions/runs/42746292021), [PG/RLS #42746291935](https://github.com/rafhael100/imagine-crm/actions/runs/42746291935): success |
| [#135](https://github.com/rafhael100/imagine-crm/pull/135) — UX-F4-04 | Protótipo offline com seleção 1/7 dias, resumo por dia, cinco datas fictícias, sem relógio real e testes de navegador também a 320px. DEV `c3d091ec`. | [F0 #42746736965](https://github.com/rafhael100/imagine-crm/actions/runs/42746736965), [PG/RLS #42746737084](https://github.com/rafhael100/imagine-crm/actions/runs/42746737084): success |
| [#136](https://github.com/rafhael100/imagine-crm/pull/136) — QA-F4-05 | Teste de consistência integrada entre fila, lista paginada, horizonte e datas literais da interface, para 0..20 itens artificiais, sem saída de dados pessoais. DEV `782fed72`. | [F0 #42747103676](https://github.com/rafhael100/imagine-crm/actions/runs/42747103676), [PG/RLS #42747103574](https://github.com/rafhael100/imagine-crm/actions/runs/42747103574): success |

**Handoff atualizado:** [módulo de atividades — escopo laboratorial e riscos](docs/desenvolvimento/CRM-F4-01-atividades-handoff-laboratorio-2026-11-09.md). Estas provas não homologam calendários comerciais, fuso horário do usuário, SLA, comunicação, persistência, privacidade, autenticação, autorização, identidade nem infraestrutura.

**NO-GO permanece:** [R01-D01..D05 / Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) sem aceite de acesso operacional; [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) sem revisor independente e parecer P0/P1; CORE02/IAM/CORE04/CORE06/CORE07/CORE08/LGPD/QA e demais gates ainda pendentes. `main`, VPS e produção não foram alteradas.


## J. SEC-ROUTE-01 / REC-LAB-04 / UX-REC-LAB-04 — 10/11/2026

| PR integrado somente DEV | Resultado observado | Evidência pós-merge |
|---|---|---|
| [#138](https://github.com/rafhael100/imagine-crm/pull/138) — SEC-ROUTE-01 | Novo teste bloqueia publicação de **qualquer** rota pública além de `GET|HEAD /up` antes do aceite CORE/SEG, inclusive aliases GET não capturados pelo regex anterior. Corrigida incompatibilidade do teste com Laravel antes do merge. DEV `944552a6`. | [F0 #42748710143](https://github.com/rafhael100/imagine-crm/actions/runs/42748710143), [PG/RLS #42748709903](https://github.com/rafhael100/imagine-crm/actions/runs/42748709903) — success |
| [#139](https://github.com/rafhael100/imagine-crm/pull/139) — REC-LAB-04 | Verificador **apenas em memória**, com até 32 KiB de bytes totalmente fictícios, digest autodeclarado e formato/tempo estritos; `canRestore=false`, `canAcceptAsVerifiedBackup=false`, `canDeleteOriginal=false` sempre. DEV `5e823bc`. | [F0 #42749280802](https://github.com/rafhael100/imagine-crm/actions/runs/42749280802), [PG/RLS #42749281497](https://github.com/rafhael100/imagine-crm/actions/runs/42749281497) — success |
| [#140](https://github.com/rafhael100/imagine-crm/pull/140) — UX-REC-LAB-04 | Três estados textuais de hash fictício na prévia offline, sem arquivos, upload, download, recuperação, segredos, permissão ou rede. Testes PHP DOM e navegador, inclusive 320px. Corrigido seletor do teste antes do merge. DEV `50a25b0c`. | [F0 #42749836340](https://github.com/rafhael100/imagine-crm/actions/runs/42749836340), [PG/RLS #42749836326](https://github.com/rafhael100/imagine-crm/actions/runs/42749836326) — success |

**Relatório técnico e handoff:** [OPS-23 — rotas, integridade e requisitos reais de recuperação](docs/desenvolvimento/CRM-OPS-23-rotas-backup-integridade-laboratorio-2026-11-10.md). SHA-256 fornecido pela mesma origem **não prova autenticidade**. A base atual não grava backups, não verifica um repositório externo confiável, não faz WAL/PITR e não restaura nada.

**Gates NO-GO inalterados:** [Issue #22 (R01-D01..D05)](https://github.com/rafhael100/imagine-crm/issues/22) sem homologação operacional; [Issue #27 (SEG/DBA)](https://github.com/rafhael100/imagine-crm/issues/27) sem revisor independente/aceite P0/P1. Nenhuma liberação de `main`, VPS, clientes, privilégios positivos, conexão externa ou dados reais foi autorizada por estes testes. A CI é prova de laboratório, não de produto comercial pronto.


## K. REV-F5 — Trial, franquias e projeções hipotéticas (10/11/2026)

| PR DEV | Entrega isolada e limite declarado | CI pós-merge |
|---|---|---|
| [#142](https://github.com/rafhael100/imagine-crm/pull/142) · REV-F5-01 | PHP puro une `TrialWindowAdvisory` e `QuotaSnapshotReadout`; fases antes/durante/depois, 14 dias ilustrativos, zero grants, billing ou entitlement. DEV `1030b9e6`. | [F0 #42752837537](https://github.com/rafhael100/imagine-crm/actions/runs/42752837537) / [PG-RLS #42752837544](https://github.com/rafhael100/imagine-crm/actions/runs/42752837544) — success |
| [#143](https://github.com/rafhael100/imagine-crm/pull/143) · UX-REV-F5-01 | Dashboard offline: três fases de trial e quatro bandas de franquia, paridade com PHP e Chromium/320px. DEV `03279c48`. | [F0 #42753286028](https://github.com/rafhael100/imagine-crm/actions/runs/42753286028) / [PG-RLS #42753285986](https://github.com/rafhael100/imagine-crm/actions/runs/42753285986) — success |
| [#144](https://github.com/rafhael100/imagine-crm/pull/144) · REV-F5-02 | PHP aritmético para medidores 1..12, impacto de uso hipotético sem reserva, sem cobrança, entrada malformada rejeitada. DEV `812bedbc`. | [F0 #42753689673](https://github.com/rafhael100/imagine-crm/actions/runs/42753689673) / [PG-RLS #42753689544](https://github.com/rafhael100/imagine-crm/actions/runs/42753689544) — success |
| [#145](https://github.com/rafhael100/imagine-crm/pull/145) · UX-REV-F5-02 | Visão offline de 4/20 leads fictícios com acréscimos 0,10,16,17, estados `in_range`/`at_limit`/`would_exceed`, Chromium/320px. DEV `fddcf15c`. | [F0 #42754127338](https://github.com/rafhael100/imagine-crm/actions/runs/42754127338) / [PG-RLS #42754127309](https://github.com/rafhael100/imagine-crm/actions/runs/42754127309) — success |

**Handoff detalhado:** [OPS-24 — trial e franquias, contratos futuros e evidências](docs/desenvolvimento/CRM-OPS-24-trial-franquias-handoff-laboratorio-2026-11-10.md). Nenhuma integração com CNPJ/OTP/autenticação, consumo real, pagamento, mudança de plano, suspensão de conta, crédito de IA ou faturamento foi implementada; são dados totalmente fabricados. `DenyByDefaultAuthorizer` continua exclusivamente deny-only, e só `GET|HEAD /up` permanece como rota pública.

**NO-GO operacional ainda vigente:** [R01/CAM/EST / Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) sem aceites específicos e [SEG/DBA / Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) sem parecer técnico independente/aceite P0/P1. CORE/AUT/FIN/CON/LGPD/QA/infraestrutura e demais dependências continuam pendentes. Nenhum merge para `main`, VPS, deploy ou cliente real.
