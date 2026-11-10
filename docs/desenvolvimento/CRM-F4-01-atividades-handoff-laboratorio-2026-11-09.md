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
