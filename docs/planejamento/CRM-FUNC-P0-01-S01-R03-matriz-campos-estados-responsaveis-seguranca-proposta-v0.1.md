# FUNC-P0-01 / S01-R03 — Matriz documental de campos, estados, responsáveis e critérios de segurança (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** **PROPOSTA NÃO APROVADA — para avaliação do responsável**.  
**Antecedentes:** [ratificação R01-D01..D05-A](CRM-DEC-FUNC-P0-01-S01-R01-alternativas-A-D01-D05-2026-11-06.md), limitada a diretrizes documentais preliminares e sujeita à conferência da pauta literal R01; [ratificação R02-D01..D08-A](CRM-DEC-FUNC-P0-01-S01-R02-D01-D08-alternativas-A-2026-11-06.md), também somente preliminar. Os contratos **R02-C01..C08 estão ABERTOS**.  
**Fontes primárias de reconciliação:** `CRM-REV-01-nucleo-identidade-multitenant-permissoes.md` (N-01..N-13) e `CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md` (COM-R01..COM-R20), ambas com pendências abertas.  
**Alcance:** especificação **conceitual** para autoconsulta humana do próprio vínculo e da própria organização. **Não** autoriza código, endpoints, ALLOW, SQL, testes funcionais, migrations, mudança de preços, quotas, cobranças, entitlements, provedor, VPS, CI, PR ou merge.

## 1. Dois planos de dados completamente separados

### 1.1 Projeção mínima de S01 — candidata a ser apresentada ao próprio usuário

| Campo conceitual | Origem autoritativa candidata | Visibilidade candidata | Condição prévia (a homologar) | Restrições |
|---|---|---|---|---|
| `organizacao_nome_exibicao` | cadastro canônico da organização vinculada, resolvido **pelo servidor** | **apenas** nome de exibição da organização corrente, após autorização | sessão humana confiável + tenant selecionado por fluxo seguro + membership **ativa e correspondente ao principal** + autorização especializada + isolamento de banco | sem lista de tenants, sem ID arbitrário, sem CNPJ completo, sem proprietário, sem contatos ou financeirização |
| `proprio_vinculo_estado` | registro canônico IAM/membership da identidade autenticada para organização corrente | somente estado do **próprio vínculo** permitido pela política específica | mesmo contexto e condições de autorização; o estado exibível exato exige acordo CORE/SEG | nunca informar memberships de terceiros; vínculo suspenso, revogado ou ausente não dá direito de consulta só por existir registro |

**Importante:** a classificação, a nomenclatura de payload e os estados efetivamente exibíveis não foram homologados. `ativo` é exemplo de estado passível de exibição quando a operação já tiver sido autorizada; uma tentativa de vínculo revogado **não** deve vazar o estado interno de uma conta que não autoriza a consulta. Ausência/revogação deve seguir contrato uniforme de falha/negativa, ainda pendente, sem enumeração de organizações.

**Fora de S01:** estado do trial, plano, assinatura, benefício por site, preço, inadimplência, faturas, pagamentos, mandatos Pix, cartão, boleto, limite de quota, CNPJ completo, endereço, lista de funcionários, permissões de terceiros, histórico de auditoria e dados cadastrais extensos. São **atributos internos ou de outras jornadas**, não campos liberados por R02-D01..D08.

### 1.2 Atributos internos de contexto e decisão — NUNCA expostos automaticamente por S01

| Família | Atributos lógicos de referência (não nomes físicos) | Dono/autoridade candidata | Uso permitido apenas após contratos e gates |
|---|---|---|---|
| Autenticação | principal autenticado, validade da sessão, fatores/step-up exigidos | IAM + SEG-01/02 | comprovar quem solicita; nenhum token bruto em log/UI |
| Contexto | organização escolhida por fluxo confiável, snapshot/versionamento e correlation ID | CORE01 + CORE03/04 + SEG | evitar substituição de tenant por parâmetro de cliente |
| Membership | vínculo do próprio principal, estado, vigência, versão, revogação | CORE01/02 + Administração Empresa + SEG | condição de elegibilidade para **autoconsulta específica**, não para todo CRM |
| Trial/assinatura | modalidade, situação e marcos comerciais oficiais do CRM | FIN/CON + produto CRM | apenas matriz comercial transversal, sem novos campos S01 |
| Benefício Básico | vínculo de serviço/site, elegibilidade, sustentação e transição | Portal Sites + FIN/CON + produto CRM | distinguir múltiplos sites, cortesia e conta paga |
| Operacionalidade | restrita/normal/suspensa/indeterminada, com razão versionada | CORE/SEG + FIN/CON, segundo autoridade por domínio | efeito derivado só por decisão oficial homologada; nunca por rótulo UI |
| Evidência/auditoria | operação, versão da política, decisão, categoria, motivo resumido, correlação | SEG + auditoria + LGPD | trilha mínima, sem segredos, payloads pessoais excedentes ou consulta global implícita |

## 2. Matriz de estados e proprietário da verdade (referência documental)

| Eixo | Transição/fato de referência | Fonte oficial candidata e owner | Consequência documental proposta — não implementada |
|---|---|---|---|
| IAM | sessão ausente, vencida, identidade discordante | provedor IAM + SEG/CORE04 | não fornecer os dois campos S01; padronizar negativa |
| Membership | ativa, suspensa, revogada, não comprovada | CORE01/02 + owner de membros da empresa | consulta S01 somente se o próprio vínculo satisfizer política; negação sem vazamento para estados não autorizados |
| Trial | vigente → expirado, sem conversão validada | FIN/CON CRM + CORE08 | separar expiração de retenção de 60 dias; não criar novo direito |
| CRM pago | vigente → tolerância (7 dias) → suspensão → cancelamento formal (30 dias de atraso) | FIN/CON CRM | preservar relógio próprio; não misturar com site ou membership |
| CRM pago | cancelamento formal → retenção de referência 120 dias | FIN/CON CRM + LGPD | não iniciar contagem por mero cancelamento de site |
| Site/benefício | site elegível ativo, inclusive quando existem outros sites elegíveis | Portal + FIN/CON + CORE08 | manter benefício conforme vínculo oficial; não somar quotas por múltiplos sites |
| Site/benefício | suspensão por dívida do único site sustentador | Portal + FIN/CON + CORE08 | benefício operacional restrito; não iniciar transição de cancelamento prematuramente |
| Site/benefício | cancelamento comum do último site | Portal + FIN/CON + CORE08 | transição normal por 30 dias desde cancelamento formal |
| Site/benefício | cancelamento por inadimplência do último site | Portal + FIN/CON + CORE08 | transição restrita por 30 dias desde cancelamento formal |
| Reconciliação | fato ausente, duplicado, atrasado, conflitante ou sem assinatura de autoridade | owner do fato + INT + SEG/auditoria | nenhum benefício novo nem cobrança por inferência; não bloquear ou revogar direito previamente comprovado sem regra homologada e investigação |

**Observação:** relógios e decisões acima resumem fontes funcionais documentadas; suas regras físicas, precedência de versões, estados finais e impactos de cobrança **permanecem sujeitos aos gates FIN/CON, REV02 e LGPD**. Não elevar contexto comercial à condição de autorização IAM.

## 3. Contratos de fronteira e integridade a detalhar com responsáveis (todos PENDENTES)

| Contrato | Responsável técnico/negócio sugerido | Pergunta de fechamento documental | Evidência FUTURA exigida |
|---|---|---|---|
| **R02-C01** Autenticação | CORE04 / SEG | qual prova de sessão, MFA e step-up vale por papel e sensibilidade? | política de identidade e casos negativos aprovados |
| **R02-C02** Contexto/tenant/membership | CORE01/02 / SEG | como ligar principal, organização escolhida e versão do vínculo sem input arbitrário? | matriz de invariantes e autoridade do membership |
| **R02-C03** Isolamento/revogação | CORE01/03 / SEG | como garantir revogação e RLS inclusive em concorrência, filas e cache? | contrato de decisão e revalidação por operação |
| **R02-C04** Campos | CORE01/02 / LGPD | quais estados do próprio vínculo são exibíveis sem vazamento? | catálogo de campos classificados e finalidade |
| **R02-C05** Fato comercial | FIN/CON / Portal / INT | qual evento comprova contratação, pagamento, suspensão e cancelamento? | proveniência, correlação e precedência de eventos |
| **R02-C06** Direito versus estado | CORE02/08 / FIN/CON | como entitlement combina plano, cortesia, vigência, quotas e deny? | matriz de direitos com precedência e titularidade |
| **R02-C07** Falha/concorrência | CORE03 / FIN/CON / INT | como tratar evento tardio, reprocessamento, duplicidade e inconsistência? | critérios de conciliação, idempotência e rollback conceitual |
| **R02-C08** Auditoria | SEG / auditoria / LGPD | que evidência mínima, acesso, prazo e mascaramento correspondem à categoria? | classificação e regime de retenção aprovados |

Atribuições acima são **donos candidatos**, não designação executiva, delegação de privilégio ou fechamento de gate.

## 4. Critérios de segurança e comportamento de erro para futura homologação

- **Sem enumeração:** falha de autorização para tenant inexistente, revogado ou alheio não deve revelar a existência de organização ou vínculo; padronização de resposta a decidir por CORE/SEG.
- **Sem escopo implícito:** mesmo principal autenticado pode ter várias organizações, mas a consulta só usa a **organização corrente validada**; trocar organização requer fluxo próprio e nova verificação.
- **Sem inferência comercial:** estado de assinatura/site não cria nem revoga membership automaticamente. Efeito operacional exige política de precedência especializada.
- **Sem autoconcessão:** tela, status local, webhook 2xx, evento não conciliado, tempo decorrido e cache não geram ALLOW ou cobrança.
- **Sem privilégios de suporte por padrão:** administrador Imagine, agentes e n8n não herdam consulta S01 de dados de cliente.
- **Sem exposição excessiva:** não transmitir CNPJ completo, pagamentos, endereço, lista de funcionários ou diagnósticos de segurança na projeção S01; auditoria/erros não contêm segredo.
- **Sem revogação arbitrária:** conflito de fatos bloqueia **nova concessão inferida** e exige investigação; impacto sobre acesso preexistente comprovado seguirá política de segurança e FIN/CON, nunca regra automática improvisada.
- **Sem falsa homologação:** nenhum teste executado nesta etapa. Casos negativos são requisitos para futura revisão documental de critérios de aceite, não código/testes.

## 5. Decisões propostas R03-D01 a R03-D08 — **PENDENTES**

| ID | Pergunta | **Alternativa A sugerida** | Alternativa B, para contraste |
|---|---|---|---|
| **R03-D01** | Qual é o limite da projeção S01? | **A:** exatamente nome de exibição da própria organização e estado exibível do próprio vínculo; tudo mais interno | B: acrescentar assinaturas e financeiro |
| **R03-D02** | Que estado do vínculo pode ser mostrado? | **A:** somente estado do próprio vínculo após autorização válida; estados não autorizados geram negativa sem enumeração | B: apresentar suspenso/revogado mesmo sem direito de consulta |
| **R03-D03** | Como tratar tenant? | **A:** contexto corrente confirmado no backend com revalidação por operação; sem aceitar tenant indicado livremente pelo cliente | B: resolver organização pelo ID em requisição |
| **R03-D04** | Quem define o comercial? | **A:** FIN/CON e Portal, cada um para seus fatos oficiais, conciliados e versionados; IAM mantém autoridade do membership | B: derivar plano/vínculo por sinal superficial de UI |
| **R03-D05** | Como separar decisão comercial e permissão? | **A:** estado e entitlement independentes, com matriz de precedência e deny; sem concessão implícita | B: `ativo` equivale a acesso global |
| **R03-D06** | Como resolver conflito/falta de evento? | **A:** conciliação por owner, sem novas concessões/cobranças inferidas nem suspensão arbitrária do que já foi comprovado | B: assumir benefício ativo ou suspender por qualquer erro |
| **R03-D07** | Como fechar contratos de segurança? | **A:** detalhar R02-C01..C08 por owner e evidência, todos abertos até homologação separada | B: considerar contratos fechados pela ratificação de R02 |
| **R03-D08** | Como registrar auditoria e privacidade? | **A:** evidência mínima, separação por finalidade/categoria, acesso estrito e retenção submetida à LGPD | B: registrar payload completo com acesso amplo |

**Pedido ao responsável:** aprovar, revisar ou rejeitar **cada alternativa A de R03-D01..D08**, somente como diretriz documental preliminar, sem aprovar os contratos R02-C01..C08 por consequência.

## 6. Dependências abertas antes de qualquer autorização funcional

1. Conferir a pauta original de R01-D01..D05 e sua rastreabilidade; a ratificação A registrada não substitui o texto original.
2. Fechar semanticamente os estados exibíveis na autoconsulta e o catálogo de campos por CORE01/02 e SEG.
3. Reconciliar CRM-REV02-D01..D12 e P01..P12, FIN/CON, Portal Sites IN-119..123/COM-08/09 e contratos de eventos INT.
4. Homologar contratos de autenticação, RLS, revogação, auditoria, privacidade e direitos por operação.
5. Preservar **67 IDs técnicos CORE**, **SEG-01/02**, **AUT**, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD e **DEV-GATE global ABERTOS**; nenhum ALLOW, SQL, endpoint ou teste funcional autorizado.
6. Preservar `main`, **PRs #2/#21**, `dev/fast-track-crm-2026-11-04`, VPS e produção inalterados por esta documentação.

**Saída:** minuta técnica-documental para avaliação, não implementação nem homologação.