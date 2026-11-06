# FUNC-P0-01 / S01-R02 — Estados comerciais e contratos conceituais de segurança (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** MINUTA PARA AVALIAÇÃO, SEM APROVAÇÃO.  
**Dependência:** S01-R01 teve alternativas A de R01-D01..D05 ratificadas *somente como diretrizes preliminares*; conteúdo literal da pauta R01 carece de conferência na fonte.  
**Escopo:** detalhar contrato de leitura segura da **própria organização e do próprio vínculo**, sem ampliar o recorte de autoconsulta, nem implementar planos, cobranças, permissões ou acesso de terceiros.  
**Fontes cotejadas:** `CRM-REV-01-nucleo-identidade-multitenant-permissoes.md` (N-01..13) e `CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md` (COM-R01..20). Estas fontes são bases de reconciliação; suas pendências prevalecem.  
**Proibições:** nenhum código funcional, ALLOW/GRANT, SQL/migration, teste funcional, novo entitlement, alteração de plano/cobrança, CI, PR, merge, main, branch DEV, VPS ou produção.

## 1. Premissa: autoconsulta S01 NÃO é painel financeiro

A apresentação inicial de S01 limita-se ao **nome de exibição da organização selecionada** e à **situação do próprio vínculo autenticado**; *não* listar ou fornecer dados de outras organizações, pessoas, faturas, meios de pagamento, titulares, CNPJ completo ou detalhes sensíveis de cobrança por padrão.

O modelo comercial abaixo é uma **especificação transversal pendente**, para permitir decisões futuras sobre contratos seguros; **não** insere novos campos na tela S01 sem deliberação específica, catálogo de campos e autorização por ação. O administrador geral Imagine não herda leitura irrestrita de dados do cliente.

## 2. Modelo proposto: separar quatro eixos, não reduzir tudo a "ativo/inativo"

| Eixo independente | Estados conceituais candidatos | Fonte de verdade / risco |
|---|---|---|
| **Identidade e vínculo** | identidade autenticada; membership ativa; membership suspensa/revogada; vínculo pendente; vínculo não comprovado | IAM/sessão e autorização por organização, versionadas; **não** inferir acesso a partir de status financeiro |
| **Trial / assinatura própria do CRM** | trial vigente; trial expirado; assinatura paga vigente; paga em tolerância; paga suspensa; paga formalmente cancelada; indeterminado | evento comercial oficial FIN/CON; diferenciar vencimento, confirmação de pagamento e cancelamento |
| **Benefício Básico por site** | elegível/ativo; benefício suspenso por único site inadimplente; transição normal; transição restrita; sem sustentação; indeterminado | contrato elegível de site, vínculo site↔organização e fato oficial do Portal; múltiplos sites não somam quotas |
| **Operacionalidade do CRM** | operação normal; operação restrita; operação suspensa; portabilidade/contratação permitidas sob política; indeterminado/requer revisão | decisão derivada **somente após** matriz de precedência, entitlement homologado e revalidação de segurança; jamais gerada diretamente pela UI |

**Não confundir:** status comercial, estado da organização, membership, permissão específica, retenção de dados e disponibilidade operacional. O rótulo `indeterminado` é semântica documental; não é permissão permissiva nem resultado oficial aprovado.

## 3. Transições comerciais existentes a respeitar na futura matriz

| Situação de referência | Prazo/regra já documentados | Tratamento de segurança e pendência |
|---|---|---|
| Trial CRM | 14 dias; 1 trial por CNPJ; ao expirar bloqueio operacional; política de guarda de 60 dias após expiração | Não transformar novo site em segundo trial; validar identidade e organização |
| CRM pago em dia | independência do contrato pago perante o site | Site inadimplente não suspende CRM pago por si só |
| CRM pago inadimplente | 7 dias corridos de tolerância após vencimento; suspensão depois; cancelamento aos 30 dias de atraso | Relógio de atraso CRM é diferente do relógio do Portal; estado exige evento oficial e reconciliação |
| Cancelamento formal da própria assinatura CRM | retenção de referência de 120 dias **após o cancelamento formal da conta CRM**, não por mera suspensão/site | Distinguir cancelamento, suspensão, retenção legal e direito de portabilidade |
| Básico por site elegível | site/contrato validamente vinculado sustenta benefício; outros sites elegíveis o preservam | Vínculo explícito, por organização, sem autoconcessão |
| Único site suspenso por inadimplência | Básico operacional restrito, sem iniciar automaticamente prazo de cancelamento do site | Não suspender outra organização elegível nem CRM pago |
| Cancelamento comum do último site | 30 dias de transição **com operação normal** e avisos documentados | Marco é cancelamento formal; sem cobrança CRM automática |
| Cancelamento por inadimplência do último site | 30 dias desde cancelamento formal, **mantendo operação restrita** desde suspensão prévia | Não restaurar acesso pleno apenas pelo cancelamento |
| Fim da transição do Básico sem site elegível | área restrita autenticada, contratação voluntária e portabilidade conforme política | Não excluir dados imediatamente nem lançar assinatura paga automática |
| Reconciliação incompleta ou conflito | sem fato comercial suficiente para decidir | Não fabricar estado `ativo`, suspender usuário arbitrariamente ou cobrar por heurística; encaminhar conciliação e política de fail-safe |

**Precedência pendente:** diferenças históricas em `IN-092`, `IN-119/120/121`, `COM-08/09` e `REV02-D01..D12` exigem revisão literal com FIN/CON, Portal, Segurança e LGPD. Este quadro **não** homologa marcos temporais físicos, relógios, faturas ou autorização de acesso.

## 4. Contratos de segurança conceituais C01–C08 (TODOS PENDENTES)

| ID | Entrada/garantia exigida em proposta | Caso negativo de homologação FUTURA (não executar agora) |
|---|---|---|
| **R02-C01** Identidade humana | principal autenticado por mecanismo IAM confiável, sessão íntegra e política MFA/step-up conforme papel | sessão ausente, forjada, expirada ou MFA insuficiente não revela a organização |
| **R02-C02** Tenant e membership | organização corrente resolvida no backend, vínculo da **mesma identidade** ativo, versão/validade e autorização de autoconsulta | alterar ID por parâmetro, forçar tenant alheio, usar vínculo revogado |
| **R02-C03** Isolamento e revogação | RLS PostgreSQL complementa autorização na aplicação; revalidar em operação e em retomada/replay, conforme política homologada | sessão válida mas tenant de outro cliente, snapshot desatualizado, revogação concorrente |
| **R02-C04** Minimização de campos | somente `nome_apresentacao_organizacao` e `estado_proprio_vinculo` como candidatos atuais S01; demais campos comerciais permanecem fora do recorte | acesso indireto a outras contas, CNPJ/financeiro, dados de funcionários, faturas ou perfil de terceiros |
| **R02-C05** Fato comercial oficial | referenciar origem, contrato, organização, versão, instante do fato, autoridade e estado de conciliação antes de **qualquer** derivação comercial futura | webhook/HTTP 2xx, input do cliente ou cache não confirmados tratados como pagamento/autorização |
| **R02-C06** Direito ≠ estado | benefício, assinatura, quotas e ações requerem entitlements separados, vigência e matriz de autorização por operação | um rótulo de interface, promessa comercial ou status de site concede ALLOW automaticamente |
| **R02-C07** Falha e concorrência | ausência, divergência, retroatividade, duplicidade e ordem inversa de eventos geram estado pendente de conciliação; regras de resolução e owner a definir | pagamento duplicado, reativação por evento tardio, troca de tenant no meio da leitura |
| **R02-C08** Auditoria/privacidade | trilha mínima de tentativa, decisão, tenant, categoria, versão e correlação sem armazenar dados excessivos; acesso e retenção por domínio | auditoria com payload sensível, exportação indevida, suporte global por atalho |

Todos os itens são **contratos candidatos**, não schemas físicos, API, SQL, migrations, claims JWT, permissões executáveis ou políticas de segurança aprovadas. A lista de campos é **conceitual**; nenhum identificador define tabela ou payload de produção.

## 5. Proposta de comportamento seguro na futura consulta (sem implementação)

1. Solicitação por sessão humana autenticada e organização previamente selecionada em contexto seguro.
2. Resolução do próprio vínculo do principal naquele tenant, sem aceitar uma troca de tenant imposta pelo formulário.
3. Conferência de estado/versão de vínculo e decisão autorizadora; aplicar defesa em profundidade com isolamento de banco conforme contrato ainda pendente.
4. Projetar estritamente **dois campos conceituais mínimos** de S01 após autorização válida; sem mesclar o modelo financeiro.
5. Dados comerciais, quando futuramente aprovados para outra tela/ação, dependerão de fonte oficial, matriz FIN/CON e autorização específica; conflitos ficam `não verificados`.
6. Revalidar em mudança de contexto, revogação e condições determinadas pelo contrato de segurança; gerar evidência sem expor dados pessoais.

**Critérios de negação futuros:** tenant divergente, vínculo inativo, ausência de sessão, principal distinto, tentativa de campo extra, cobrança não confirmada, suporte sem escopo, evento obsoleto. São **cenários de documentação**, não testes implementados/executados.

## 6. Deliberações R02-D01..D08 — alternativas ainda NÃO aprovadas

| ID | Decisão para avaliação | Alternativa A proposta | Alternativa B (não recomendada) |
|---|---|---|---|
| **R02-D01** | Modelo comercial de estados | **A:** quatro eixos independentes; derivar operacionalidade só por matriz oficial | B: um estado geral `ativo/inativo` |
| **R02-D02** | CRM pago vs site | **A:** contrato pago independente; site não bloqueia CRM pago por si | B: qualquer dívida de site pode bloquear CRM pago |
| **R02-D03** | Expiração/cancelamento | **A:** trial, CRM pago e benefício por site com relógios, marcos e retenções separados | B: tratar expirações/cancelamentos como evento único |
| **R02-D04** | Fim da elegibilidade Básico | **A:** diferenciar transição normal e transição restrita por inadimplência; considerar outros sites | B: 30 dias sempre em operação plena |
| **R02-D05** | Fonte dos estados | **A:** fatos oficiais versionados e conciliados; sem autoativação por cache/interface | B: inferir a partir de eventos não confirmados |
| **R02-D06** | Campos visíveis em S01 | **A:** manter os dois campos mínimos, sem dados comerciais/financeiros | B: ampliar com assinatura, faturamento, status financeiro e titularidade |
| **R02-D07** | Fronteira de segurança | **A:** principal/tenant/membership/revogação verificados por operação, com isolamento de banco | B: confiar apenas em sessão ou tenant informado pelo cliente |
| **R02-D08** | Divergência/falhas | **A:** marcar como não verificado, não conceder acesso/cobrança e exigir conciliação | B: assumir estado ativo ou compensar sem fato oficial |

**Recomendação documental:** avaliar as alternativas **A**, sem decidir nada automaticamente. As alternativas B aparecem apenas para deixar explícitos os riscos rejeitáveis; sua apresentação não altera decisões já aprovadas.

## 7. Critérios para futura homologação (não executados)

- Conferir texto literal de R01-D01..D05 e versão de S01; formalizar aprovador e autoridade de cada campo/estado.
- Conciliar REV02-D01..D12, CRM-REV02-P01..P12, FIN/CON, Portal IN-119..123/COM-08/09, CORE01/02/03/04/08, SEG-01/02, AUT, INT, LGPD e precedência de direitos.
- Homologar matriz campo × ator × tenant × origem × operação e contratos de eventos/tempo/conciliação.
- Submeter cenários negativos a SEG/CORE/FIN/CON e donos de dados **documentalmente**; nenhuma suíte funcional agora.
- Manter **67 IDs CORE**, SEG, AUT e demais gates **ABERTOS** até suas aprovações específicas; sem ALLOW ou SQL.

## 8. Saída desta etapa

Somente minuta versionada para avaliação. Nenhum item R02-D01..D08 ou R02-C01..C08 é homologado. Não criar PR, alterar a main, os PRs #2/#21, a branch DEV, CI, VPS ou produção a partir desta proposta.
