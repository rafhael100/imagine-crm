# CRM Imagine — F4-01: núcleo de atividades e prévia offline (handoff para revisão)

**Data:** 09/11/2026. **Estado:** implementação laboratorial integrada **apenas na DEV**, sem GO funcional.  
**Fontes:** [PR #127](https://github.com/rafhael100/imagine-crm/pull/127) e [PR #128](https://github.com/rafhael100/imagine-crm/pull/128).  
**Escopo de autorização:** somente PHP puro e demonstração HTML offline com dados inventados. **Não é um módulo de gestão de tarefas operacional.**

## 1. Implementação existente (e somente isto)

| Código | Contrato verificável | O que não faz |
|---|---|---|
| `app/Core/Activities/SyntheticFollowUpQueue.php` | Recebe até 20 pares estritos `due_at` UTC canônico + `completed` boolean e instante UTC explícito. Retorna contagens `overdue`, `due_now`, `upcoming`, `completed` e apenas posições dos exemplos abertos ordenadas por prazo e posição original. Sempre `authorized=false`, `can_notify=false`. | Não consulta/atribui pessoas, clientes, leads, organizações, tarefas, calendários, filas, planos ou sistema de relógio. Não define prazo obrigatório/SLA nem envia avisos. |
| `prototypes/s01-prot-01/index.html` | Mostra na tela já existente de monitoramento quatro categorias ilustrativas, com texto local, `role=status`, `aria-live` e seleção única. Cinco registros inteiramente inventados servem de referência. | Não renderiza lista de tarefas reais, não persiste estados, não abre API ou form, não recebe identificadores pessoais, não conecta n8n. |
| `tests/Unit/SyntheticFollowUpQueueTest.php` | Verifica ordenação estável, fronteiras de data, dia bissexto, estados vazios, 20 itens, rejeição de datas invalidas/campos forjados. | Não comprova prazo operacional, custódia, retenção, revogação ou direito de enviar notificação. |
| `tests/Unit/OfflinePrototypeFollowUpDemoTest.php` e browser CI | DOM, labels, contagens coerentes com fixtures PHP, quatro controles acessíveis e Chromium em viewports convencionais e 320 CSS pixels. | Não substitui avaliação humana WCAG ou análise de implantação. |

**CI verificada no squash #127 DEV `063648d996feb4f08768816eac61b51663339e93`:** [F0 #42739900102](https://github.com/rafhael100/imagine-crm/actions/runs/42739900102), [PostgreSQL/RLS #42739900562](https://github.com/rafhael100/imagine-crm/actions/runs/42739900562) — success.

**CI verificada no squash #128 DEV `1a14216b6ffd91fede26f6910a60195d8a7d22f4`:** [F0 #42740358075](https://github.com/rafhael100/imagine-crm/actions/runs/42740358075), [PostgreSQL/RLS #42740358212](https://github.com/rafhael100/imagine-crm/actions/runs/42740358212) — success.

## 2. Próximo contrato funcional a deliberar (não aprovado neste documento)

**S01-R01 e SEG/DBA são precedentes ao primeiro módulo operacional.** Para atividades reais, o futuro recorte precisa de aprovação própria e não pode herdar grants de planos, telas ou fixtures. A especificação seguinte deverá decidir formalmente:

1. **Objeto e owner:** definição de tarefa/atividade, vínculo ao lead/tenant, destinatário/atribuição e fonte oficial. Não deduzir acesso a partir de `tenant_id`, nome de papel ou CNPJ.
2. **Operações:** matriz independente de criar, ler, listar, editar, concluir, reabrir, delegar, notificar, exportar e auditar, cada qual com recurso/ação/campo/escopo/canal versionados e negação por padrão.
3. **Tempo/SLA:** sem pressupor que `due_now` corresponde a alerta, prazo legal ou SLA. Resolver timezone de usuário, DST, expediente, feriados, calendário empresarial, atrasos, tolerâncias e idempotência de execução por decisão de Produto e consentimento quando aplicável.
4. **Persistência/concorrência:** SQL/RLS non-bypass, membership atual e sessão revalidadas, transações, isolamento entre tenants, integridade da associação a leads, deduplicação e garantia de não envio duplicado; sem usar GUC como autenticação.
5. **Privacidade/observabilidade:** minimização, consentimento/finalidade de contatos, retenção, auditoria sanitizada, exclusão conforme direitos legais, ausência de segredo em logs e proteção de payload de notificação.
6. **Testes e release:** provas positivas e negativas de ownership por tenant, IDOR, revogação, concorrência, alertas duplicados, falhas de worker, UTC/DST e impacto em usuários reais; somente ambientes e código explicitamente autorizados. Revisão independente SEG/DBA e decisão separada de produto/release.

### Restrições NO-GO não alteradas

- [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22): R01-D01..D05, CAM-01..10 e EST-01..14 continuam na etapa de deliberação, sem contrato físico ou `ALLOW`.
- [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27): revisor SEG/DBA independente não designado; SEGDBA-01..05/07 seguem abertos, e mitigação específica de SEGDBA-06 não encerra a revisão.
- CORE02/CORE04/CORE06/CORE07/CORE08/CORE09/CORE10, AUT, LGPD, FIN/CON, ARQ e demais gates permanecem abertos até evidência e aceite respectivos.
- Nenhum `ALLOW`, endpoint de tarefas/leads, migrations operacionais, credencial, exportação, integração externa, deploy, alteração de `main` ou VPS foi criada por F4-01.

**Critério de continuidade:** seguir com laboratórios, testes adversariais e documentação em DEV. Qualquer código operacional requer novo aceite específico do escopo e dos controles de identidade/tenant/segurança, não somente CI verde.


## 3. Extensão F4-02 e SEC-F4-03 — final da rodada de 09/11/2026

| Incremento DEV | Contrato limitado, com evidência |
|---|---|
| [PR #130](https://github.com/rafhael100/imagine-crm/pull/130) — `SyntheticFollowUpWorklist` | Cinco visões fechadas de acompanhamento anônimo, páginas locais com 1..20 posições, ordenação por prazo UTC, rejeição de índices/entradas inválidos, `authorized=false` e `can_notify=false`. [CI F0](https://github.com/rafhael100/imagine-crm/actions/runs/42743813555), [PG/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42743813791) **sucesso pós-merge**, SHA DEV `76a8d9eb74bc24ed1cc4748c403bbac98743f022`. |
| [PR #131](https://github.com/rafhael100/imagine-crm/pull/131) — UX-F4-02 | Lista offline de cinco exemplos com filtro de quatro categorias, paginação 1/2, estados, foco, evidências de browser e 320px; não há banco/API nem relógio atual. [CI F0](https://github.com/rafhael100/imagine-crm/actions/runs/42744165899), [PG/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42744165945) **sucesso pós-merge**, SHA DEV `593f3a26ebba9179ecaa3efa160471b27a8c6ffd`. |
| [PR #132](https://github.com/rafhael100/imagine-crm/pull/132) — SEC-F4-03 | Gate regressivo nega publicação de rotas públicas de atividades, tarefas, follow-ups, lembretes e notificações antes de revisão específica; assegura ausência de autoridade/aviso nos dois componentes PHP. [CI F0](https://github.com/rafhael100/imagine-crm/actions/runs/42744477725), [PG/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/42744477812) **sucesso pós-merge**, SHA DEV `f3f69dff060c815f2eea8ae35c70e2fe0bcc4106`. |

### Distinção de produto e próximos gates

Estes incrementos **não** criam um motor de tarefas operacional. O sistema não autentica nem identifica pessoas/organizações reais para essas atividades, não grava tarefas, não calcula SLA aprovado, não envia notificações, não ativa workers, não oferece API e não cobra clientes. A prova de RLS é em PostgreSQL de CI, não um aceite de acesso a um tenant real.

- **Funcionalidade S01:** [R01-D01..D05 / Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) ainda necessita decisão específica sobre exposição de campos/estados, identidade confiável e evidência. Não presumir aprovação a partir de pedidos gerais de continuidade.
- **Segurança de dados e operação:** [SEG/DBA / Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) exige responsável independente verificável e avaliação por risco P0/P1. Nem testes automatizados nem autoavaliação de quem desenvolveu se tornam parecer independente.
- **Futuro módulo de atividades:** requer contrato próprio de autorização criar/atribuir/ler/editar/concluir/reabrir/exportar/notificar, revalidação por request, isolamento tenant-bound, política de tempos/SLA/consentimento/retention, auditoria, testes de revogação e idempotência/concorrência. Não é possível substituir esses requisitos por código de interface.
- **Release:** só após homologação técnica/privacidade/QA e aceite de produto, infraestrutura habilitada separadamente, e decisão explícita para `main`/VPS/produção. Nenhum desses passos foi realizado aqui.


## 4. Horizonte UTC F4-04 / UX-F4-04 / QA-F4-05 — 09/11/2026

**Natureza:** ainda somente exemplificação anônima em PHP puro e HTML offline. A implementação abaixo não determina agenda comercial, prazo de atuação de vendedor, SLA, feriados, fuso do usuário, permissões ou regra para notificação.

- [PR #134](https://github.com/rafhael100/imagine-crm/pull/134): `SyntheticFollowUpUtcHorizon::summarize` valida todas as fixtures (incluindo concluídas e não selecionadas), aceita janela 1..14 **dias de calendário UTC**, mostra contagem diária de prazos **posteriores** ao instante de referência e conserva contagem fora da janela; sem identificação, retorno de tarefa ou efeitos. F0 e PostgreSQL/RLS pós-merge **success** ([#42746292021](https://github.com/rafhael100/imagine-crm/actions/runs/42746292021), [#42746291935](https://github.com/rafhael100/imagine-crm/actions/runs/42746291935)).
- [PR #135](https://github.com/rafhael100/imagine-crm/pull/135): protótipo da tela de monitoramento apresenta horizonte de **1 dia ou 7 dias UTC**, dia a dia com `textContent` e `replaceChildren`. Cinco exemplos inventados têm 2 futuros (1 no dia inicial e 1 no quarto dia UTC) para exercitar ambos os modos. Testes PHP e Chromium normais/320px; pós-merge **success** ([F0 #42746736965](https://github.com/rafhael100/imagine-crm/actions/runs/42746736965), [PG #42746737084](https://github.com/rafhael100/imagine-crm/actions/runs/42746737084)).
- [PR #136](https://github.com/rafhael100/imagine-crm/pull/136): `SyntheticFollowUpProjectionParityTest` cruza `SyntheticFollowUpQueue`, `SyntheticFollowUpWorklist` e `SyntheticFollowUpUtcHorizon` para tamanho de fixture 0..20, todas as visões, tamanhos de página e janelas UTC; extrai as cinco datas realmente escritas na interface para prevenir divergência silenciosa entre demonstração e cálculo PHP. Pós-merge **success** ([F0 #42747103676](https://github.com/rafhael100/imagine-crm/actions/runs/42747103676), [PG #42747103574](https://github.com/rafhael100/imagine-crm/actions/runs/42747103574)).

### Faltam decisões de produto e comprovações reais

1. **R01 e controle de acesso real:** [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22) aguarda aprovação itemizada CAM/EST/R01-D01..D05, política runtime de autorização por ação/recurso/campo/escopo e origem confiável de tenant/identidade/membership. Não deduzir `ALLOW` de fixtures ou da existência da interface.
2. **SEG/DBA independente:** [Issue #27](https://github.com/rafhael100/imagine-crm/issues/27) permanece aberta; separar evidência de CI do parecer independente sobre isolamento, credenciais, bypass RLS, migrações, backup/restore e acessos administrativos.
3. **Atividades operacionais:** definir sob responsabilidade da direção/Produto/Segurança/Privacidade as ações de tarefas, quem pode delegar, cronologia e TZ de empresas e pessoas, expediente, feriados, vencimentos, reabertura, eventos duplicados, comunicação, consentimento e retenção. Depois construir API/SQL protegido e testes de revogação/cross-tenant somente sob autorização específica.
4. **Release:** homologar como pacote, com QA/WCAG/LGPD/SEG/DBA e autorização separada de deploy. Nenhuma entrega deste laboratório habilita clientes reais, `main`, VPS, notificações, cobranças ou armazenamento de tarefas.

**Resumo:** o incremento atesta apenas a consistência de cenários fictícios. Não substitui o caminho de MVP multiempresa nem um parecer técnico independente.
