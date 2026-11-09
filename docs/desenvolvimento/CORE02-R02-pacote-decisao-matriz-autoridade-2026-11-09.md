# CORE02-R02 — Pacote de decisão para fechar a política de autorização por recurso, campo e organização

**Data-base:** 09/11/2026. **Natureza:** síntese de reconciliação e pedido de parecer, **não aprovação**. **Destino:** DEV documental. **Referências:** [matriz CORE02 original](../planejamento/CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md), [Etapa 2](../planejamento/CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md), [D04](../planejamento/CRM-CORE-P0-ETAPA2-CORE02-D04-classificacao-campos-direitos-proposta-v0.1.md), [D05](../planejamento/CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md), [D06](../planejamento/CRM-CORE-P0-ETAPA2-CORE02-D06-catalogo-acoes-por-modulo-proposta-v0.1.md), [REV-01](../planejamento/CRM-REV-01-nucleo-identidade-multitenant-permissoes.md), [Issue SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27).

## 1. Situação de aprovação, sem inferências

| ID de decisão | Situação verificada | Entrega necessária antes do fechamento integral | Competência que deve validar |
|---|---|---|---|
| CORE02-D04 | **A/B/C/D aceitas como diretrizes preliminares; D04 ABERTA** | Inventário **real** por módulo/owner: entidade/campo, classe F-ID..F-ART, finalidade, operações, exposições em busca/IA/CSV/API e proteções de custom fields | Donos DAT/ATD/VEN/AGD/AUT/IA/FIN/CON/ARQ, Segurança, Privacidade |
| CORE02-D05 | **A/B/C/D aceitas como diretrizes preliminares; D05 ABERTA** | Escolher explicitamente P1/P2/P3/P4 e precedência de grants/denies; delegação, autoelevação, união/interseção dos escopos, invalidação/versionamento | IAM/CORE01/CORE07, Segurança, donos dos módulos |
| CORE02-D06 | **Proposta D06-A/B/C/D existente; D06 ABERTA** | Catálogo versionado por proprietário com recurso, ação, efeito, campo, escopo, canal, ator e estado; aceite de cada proprietário | Proprietários de cada módulo, INT P0-OWN/CON |
| CORE02-D07 | **ABERTA** | Política de risco, MFA/step-up, confirmação pelo ator competente, alçada e prova de autorização para ato específico, sem inventar segundo aprovador universal | CORE04, CORE05, Segurança, FIN/CON e módulos sensíveis |
| CORE02-D08 | **ABERTA** | Contrato de enforcement Laravel + RLS, locks/revalidação, contexto por transação, schema físico/índices, cache, API, jobs e revogação; testes de acesso negado | CORE01, CORE03, arquitetura, SEG/DBA independente |
| CORE02-D09 | **ABERTA** | Separar entitlement/plano/quotas de papéis e autorizações; homologar Básico, trial, cortesia por site, FIN/CON, extras e mudanças de contrato | CORE08, FIN/CON, Portal/Sites, Comercial |
| CORE02-D10 | **ABERTA** | Roteiros de homologação com casos negativos, dados fictícios, owner por decisão, evidência reproducível, revisão de privacidade/segurança e aceite versionado | CORE10, Segurança, LGPD/Privacidade, responsáveis de módulos |

**Não alterar status técnico por existir este pacote.** CORE02-D01–D03 são base documental aceita, não autorização executável. **67 identificadores CORE da Etapa 2 seguem abertos**; D04 e D05 continuam abertos, embora subdiretrizes tenham aceite preliminar.

## 2. Fronteiras de decisões que não podem ser confundidas

O fluxo futuro de autorização deve cruzar **todos** os fatores aplicáveis, com fontes legítimas independentes: identidade/sessão/MFA; membership de tenant vigente ou mandato de suporte estritamente competente; recurso e proprietário; escopo concreto de registro/equipe/unidade/caixa; operação e canal; classe de cada campo; estado do objeto; plano e entitlement; finalidade/consentimento quando aplicáveis; alçada/risco; garantia de isolamento RLS; revalidação antes de efeitos; auditoria minimizada. Essa lista é **critério para revisão**, não algoritmo aprovado nem motor novo.

| Erro de interpretação | Separação necessária no desenho e no aceite |
|---|---|
| A mesma identidade opera empresas A e B | Membership e atribuições **por tenant**, nunca união de direitos de A com B |
| Administrador de empresa versus administrador geral Imagine | Admin da empresa não altera preço/gratuidade Imagine; admin geral não lê comercial de cliente sem mandato |
| Membro de equipe versus dono de registro | Posse e escopo por módulo; integrar equipes não equivale a acessar tudo nem altera classificação de campos |
| `read`/busca/relatório versus exportar/importar/backup | Direitos separados inclusive máscaras, campo protegido e dados deriváveis; respeitar portabilidade essencial IN-118 |
| Anexos `view_or_download_attachment` | Preservar **uma única permissão conjunta de ver/baixar**, distinta de upload/excluir e sujeita a antimalware |
| Plano Básico pago/cortesia versus trial | Mesmas funções do Básico conforme regra, **um usuário humano autenticado com MFA**, trial 1/CNPJ distinto de novo Básico elegível |
| Prompt, webhook, job ou token externo | Não são autorizações; identidade técnica, ferramenta, versão, mandato e revalidação sem `BYPASSRLS` |
| Prova de autenticação/MFA versus autorização de campo | MFA válido não concede acesso a campo F-PII/F-RES, segredo F-SEC ou relatório F-EVID |

## 3. Ficha mínima para o proprietário de cada recurso/ação — proposta de levantamento

Cada domínio precisa produzir uma **linha versionada** por operação relevante, com referências verificáveis aos documentos aceitos. **Sem preencher uma linha faltante com `ALLOW`, perfil padrão ou permissão inferida.**

| Campo da ficha | Conteúdo a fornecer |
|---|---|
| Identificação | `owner_domain`, `resource`, `action`, versão de catálogo e fonte decisória |
| Autoridade | ator/canal permitido *em tese*, origem do principal validado, tenant/membership/mandato e plano |
| Alvo e alcance | identificador e tenant do objeto, dono/atribuição, equipe/unidade/canal/caixa e regra de visibilidade |
| Dados | cada classe F-ID..F-ART aplicável, colunas reais, finalidade, operação de busca/IA/export, máscara/proibição |
| Execução | leitura/escrita/serviço externo/efeito sensível, condição de negócio, confirmação do dono e idempotência |
| Segurança | MFA/step-up se exigido, tempo/versão/revogação, exclusão da cache, RLS e não-autoconcessão |
| Evidência | roteiros negativos, responsável pelo aceite, trilha/auditoria, retenção LGPD e documento com SHA |

Os nomes acima são **campos de checklist documental**, não tabelas/colunas, dados pessoais novos, API pública ou schema aprovado. Se não houver owner, fonte de autorização ou direito de campo definido, **a operação continua sem implementação de acesso**.

## 4. Prioridade para homologação, sem abrir novos gates

1. **CORE02-D04 + D06:** cruzar catálogo de recursos/ações do D06 com campos reais do D04. Primeiro DAT/lead/contato/portabilidade, IAM/convites e anexos, por estarem ligados às interfaces já exploradas, **sem declarar esses módulos liberados**. Seguir com VEN, ATD, AGD, AUT, IA, FIN/CON, AUD e INT.
2. **CORE02-D05:** apresentar alternativas P1–P4 de precedência já registradas e impactos em perfis múltiplos, negação explícita, mudanças de equipe e autoelevação; **a Direção não escolheu alternativa integral**. Exigir recomendação técnica de Segurança e casos de regressão.
3. **CORE02-D07/D09:** conferir MFA e alçadas por ato, suporte e agentes, junto a plano/trial/assentos e FIN/CON, para não transformar privilégio de papel em benefício financeiro.
4. **CORE02-D08:** reconciliar IAM autêntico, isolamento RLS, serviços, jobs e cache com CORE01/03/04/07 e **parecer independente SEG/DBA**. Não usar apenas GUC `app.tenant_id` como prova.
5. **CORE02-D10 + CORE10:** matriz de aceite por operação, casos de tenant cruzado, field leak via busca/export/IA e revogação concorrente, dono, publicação de evidência, rastreabilidade SHA e GO específico.

## 5. Evidência técnica disponível versus evidência ainda faltante

| Evidência já disponível em DEV (somente laboratório) | Limite, nunca aceito por inferência |
|---|---|
| CORE02-MX-01 [PR #84](https://github.com/rafhael100/imagine-crm/pull/84): 864 negativas para `lab.record`, ações/classes/escopos/canais | A matriz **não exercita grants reais**, registros de cliente nem os papéis aprovados dos módulos |
| CORE02-MX-02 [PR #85](https://github.com/rafhael100/imagine-crm/pull/85): 54 negativas com sessão sintética, double de membership e boundary | Não prova política final de equipe, alçada, operador real ou vínculo PostgreSQL por caso |
| F3-24 [PR #81](https://github.com/rafhael100/imagine-crm/pull/81) e F3-25 [PR #82](https://github.com/rafhael100/imagine-crm/pull/82): contrato negativo de importação e tripwire de release | Não implementam importação/exportação/backup real nem autorização por recurso/campo |
| Testes PostgreSQL efêmeros na CI e contratos deny-only `AuthorizationDecision` / `DenyByDefaultAuthorizer` | Não equivalem a revisão independente de esquema, grants, política de produção ou LGPD |

**Pendências concretas:** owner de cada efeito; matriz oficial de campos; composição de perfis D05; identidade forte e contextos de jobs; RLS e dados físicos revisados; escopos de suporte; classificação/retentão; estratégia de homologação ponta a ponta; decisão documental de cada D04..D10; parecer independente SEG/DBA [#27](https://github.com/rafhael100/imagine-crm/issues/27); reconciliação IN-168 e INT G01–G06.

## 6. Critérios de bloqueio e entrega

Nenhum `AuthorizationDecision::allow`, grants RLS, migração operacional, endpoint de negócio, alteração do `main`, VPS, custo de fornecedor, dado real ou deploy está autorizado pelo presente documento. O `DenyByDefaultAuthorizer` continua inalterado e negando todas as operações; não existe aprovação tácita ou por CI verde.

**Roteiro de saída:** revisão pelo owner de cada módulo → revisão de IAM/Segurança/Privacidade e FIN/CON quando aplicável → parecer SEG/DBA para a fronteira física → decisão expressa itemizada sobre D04..D10, com baseline/SHAs e riscos aceitos → somente então desenho do implementável autorizado em ambiente apropriado, sem equiparar à autorização de produção. Toda divergência é registrada como pendência, não resolvida por default silencioso.
