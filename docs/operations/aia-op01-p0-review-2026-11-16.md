# CRM ↔ Agentes de IA — revisão P0 e liberação progressiva do OP-01

**16/11/2026 — Decisão operacional: NO-GO.** Escopo aprovado nesta branch: preparar contrato, código inerte, testes e ambiente demonstrativo sem ativar leitura real, grants, rotas, segredos ou deploy.

## Baselines e diff

- **CRM funcional:** [PR #628](https://github.com/rafhael100/imagine-crm/pull/628), SHA exato `c67aaa0440ac4f9f1c607883d0c1347f5a067f92`, empilhado sobre a [PR #614](https://github.com/rafhael100/imagine-crm/pull/614). Comparação histórica com `d7c7aacc766c7ade4c65dcd211ed4569442144a9`: 168 commits à frente, 0 atrás, 226 arquivos na comparação. Isto exige revisão de diffs por competência; não é autorização de merge.
- **Imagine Agentes:** [PR #334](https://github.com/rafhael100/imagine-ai-agents/pull/334), SHA `49f623a655049046806d2f216a11322a5478c4a4`. Base `3a166d7c9a94dd84d0331cc17771f7dfe3e83609`: 124 commits à frente, 0 atrás, 57 arquivos na comparação.
- GitHub Actions já confirmadas na HEAD original: CRM F249 [#43795878769](https://github.com/rafhael100/imagine-crm/actions/runs/43795878769); Agents R01 [#43796484782](https://github.com/rafhael100/imagine-ai-agents/actions/runs/43796484782), contrato cruzado F250 [#43796484809](https://github.com/rafhael100/imagine-ai-agents/actions/runs/43796484809). Nenhuma CI substitui aceite de Segurança, IAM, DBA, CORE e LGPD.

## O que já é código testado, mas não operacional

| Componente | Evidência | Limite |
|---|---|---|
| Identidade Ed25519 original | Agents F231 e CRM F241–F249 | Chaves e tenants ainda sintéticos no laboratório |
| Replay PostgreSQL16 entre conexões | CRM F242/F249 | Não provisionado para autorização operacional |
| Chaves públicas CRM-owned, role de leitura com FORCE RLS | CRM F245/F246 | Papel e tabela temporários de CI, não IAM de usuário |
| Revogação concorrente em transação | CRM F248 | Recorte com tabela sintética e bloqueio local de linha |
| HTTP e resposta uniforme | CRM F241 e Agents F239/F240 | Adaptador nunca registra rota e sempre responde 404 |
| Authz de negócio real | `DenyByDefaultAuthorizer` | Não concede leitura de lead, mesmo com assinatura válida |
| Fluxo CRM de clientes/admin | CRM #614 | Homologação de aplicação privada, não API Agentes |

## Contrato proposto OP-01 e responsabilidades

**Operação única inicial**: `crm.lead.read_minimal`, somente leitura. Requisição lógica inclui referência opaca da chamada, serviço autenticado, principal delegado, empresa **derivada pelo CRM**, recurso, finalidade e lista de campos aprovada. O Agents não pode declarar roles, grants, consentimento ou tenant como fonte de autoridade; informações no corpo servem apenas para correlação e comparação.

**Resposta de negócio futura:** proposta condicionada a revisão; nunca transmitir `title`, telefone, e-mail, anexos, observações ou histórico sem decisão específica de classificação e contrato. Os campos `ref` e `stage` usados nos mocks **não têm aprovação como esquema físico**. Respostas de lead inexistente, de outra empresa ou sem direito devem impedir enumeração.

**Fluxo de confiança exigido:** autenticar o peer/issuer/audience por canal seguro → resolver a empresa e o usuário no CRM usando fatos persistidos confiáveis → verificar membership, contrato ativo, delegação e revogação → obter recurso no tenant por query sob RLS → aplicar ACL de operação e de campos → consumir nonce atômico e produzir auditoria minimizada na mesma fronteira segura → emitir somente o mínimo permitido. Qualquer falha ou prova ausente nega; nunca confiar em `app.tenant_id` isolado.

## Matriz de execução e bloqueadores

| Sequência | Responsável técnico | Entrega verificável | Estado |
|---|---|---|---|
| 01 | CORE + SEG | Matriz de membership / serviço / recurso / campo com casos negativos | EM PREPARAÇÃO, não aprovada |
| 02 | IAM + SEG | Certificado, gestão e rotação de chaves, serviço autenticado e revogação | ABERTO |
| 03 | DBA + CORE | Política RLS e papel mínimo em banco de homologação, nonce durável, backup/rollback | ABERTO |
| 04 | Produto + Privacidade | Mapa dos campos reais, base legal, finalidade, retenção e subprocessadores | ABERTO |
| 05 | Arquitetura + QA | Esquema versionado, limites, timeouts, 13 cenários OP01-N01..N11/P01..P02 | ABERTO |
| 06 | Operações + Direção | Gate de staging, escopo financeiro, reversão e autorização do piloto | ABERTO |

**Critério de GO:** a [PR #57](https://github.com/rafhael100/imagine-crm/pull/57) tem **7 decisões, 14 gates e 13 cenários operacionais** que seguem não homologados. A conclusão de trabalho preparatório não aprova nenhum item automaticamente. Não fazer merge para `main`, habilitar ingress real, mexer na VPS ou usar dados de clientes até decisão de competência e evidência verificável.

## Próxima implementação aprovada nesta branch

Criar um *seam* de aplicação com feature flag permanentemente desabilitada por padrão, contrato de resposta negativa e testes com o autorizador **real** do CRM vinculado em container (que é deny-by-default). Esse componente não expõe rota nem aciona callback de dados. A interface navegável dos Agentes e um ensaio de agente comercial com leads fictícios podem evoluir em paralelo.
