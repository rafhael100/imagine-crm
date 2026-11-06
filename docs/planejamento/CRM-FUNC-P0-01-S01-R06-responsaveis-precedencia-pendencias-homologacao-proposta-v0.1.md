# FUNC-P0-01 / S01-R06 — Responsabilidades, precedência e dependências de homologação (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** **MINUTA PARA AVALIAÇÃO, NÃO APROVADA**.  
**Base de decisões documentais preliminares:** R01-D01..D05-A, R02-D01..D08-A, R03-D01..D08-A, R04-D01..D08-A e [R05-D01..D08-A](CRM-DEC-FUNC-P0-01-S01-R05-D01-D08-alternativas-A-2026-11-06.md).  
**Base de escopo:** [R05 — matriz de evidências](CRM-FUNC-P0-01-S01-R05-matriz-evidencias-criterios-homologacao-seguranca-proposta-v0.1.md) e [R04 — contratos por domínio](CRM-FUNC-P0-01-S01-R04-contratos-seguranca-por-owner-evidencias-proposta-v0.1.md).  
**Limites:** R02-C01..C08 continuam **todos PENDENTES**. 67 CORE, SEG, AUT e demais gates **ABERTOS**. A minuta não nomeia responsável real, não encerra pendência, não cria direito nem aprova implementação.

## 1. Objeto e fronteira imutável de S01

S01 permanece **autoconsulta humana somente leitura** de dados restritos à própria organização corrente e ao próprio vínculo da identidade autenticada. Saída conceitual candidata estritamente limitada a:
- `organizacao_nome_exibicao` — nome de exibição da organização corrente **após autorização**;
- `proprio_vinculo_estado` — estado do próprio vínculo **somente quando autorizado**.

Nada neste documento autoriza campos comerciais, listas de empresas, CNPJ completo, faturas, pagamentos, empregados, membros terceiros, ações de administração ou consulta global. A definição física dos campos, das mensagens de erro e dos estados mostrados continua pendente. Um vínculo revogado não confere acesso para descobrir sua existência.

## 2. Responsabilidades por contrato — owner candidato ≠ autoridade nomeada

| Contrato | Owner funcional/técnico candidato | Revisores competentes candidatos | Decisão concreta ainda pendente | Artefato documental a produzir |
|---|---|---|---|---|
| **C01** sessão/MFA | IAM + CORE04 | SEG-01/02, arquitetura autenticação | requisitos MFA/step-up por papel, expiração e revogação | matriz de fatores, sessão e casos de negativa |
| **C02** principal/tenant/membership | CORE01/02 e administração de empresa | SEG, dono identidade/tenant | proveniência do tenant selecionado, validade/versão do vínculo e regra de troca | diagrama de contexto confiável e invariantes |
| **C03** autorização/RLS/revogação | CORE01/03/04 + arquitetura PostgreSQL | SEG-01/02 e CORE02 | responsabilidades aplicação vs banco; roles, bypass, revogação e cache | matriz conceitual de recursos/roles/RLS sem SQL |
| **C04** campos e não enumeração | UX S01 + CORE01/02 | SEG + LGPD + dados da organização | catálogo mínimo e política de resposta não enumerativa | catálogo campo×ator×finalidade×estado |
| **C05** fatos comerciais | FIN/CON CRM + Portal Sites | INT + CORE08 + SEG | contrato de fatos de trial/CRM/site e comprovação oficial; fora da tela S01 | mapa de origem, autoridade, eficácia e versão |
| **C06** estados vs direitos | CORE02/08 + donos de módulo | FIN/CON + SEG | precedência de deny, entitlement, quotas e escopos sem ALLOW | matriz de autorização proposta por domínio |
| **C07** falhas/concorrência | CORE03 + INT | FIN/CON + SEG + auditoria | idempotência, ordem de fatos, revalidação e reconciliação | matriz de eventos conflitantes e responsáveis |
| **C08** auditoria/LGPD | auditoria + privacidade/LGPD | SEG + donos de dados | categorias, finalidade, minimização, acesso, retenção e descarte | catálogo de evidências e classes de retenção |

**Controle obrigatório proposto:** antes de tratar qualquer owner como competente, registrar área, representante designado, atribuição de aprovação, versão, data de aceite e eventual impedimento de segregação de funções. Não inferir uma designação efetiva a partir desta tabela.

## 3. Precedência entre autoridades — matriz conceitual proposta

| Dado ou decisão | Autoridade de referência | Não pode ser substituída por | Condição para aceitar versão futura |
|---|---|---|---|
| **Quem é o principal** | IAM autenticado | e-mail informado em tela, sessão não comprovada, agente ou status comercial | prova de autenticação e política aplicável |
| **Qual organização e vínculo** | CORE01/02 com membership confiável | ID livre na requisição, contrato de site, CNPJ igual ou tenant de cache | tenant corrente validado, membership pertencente ao principal, vigente e versionado |
| **Autorização de ação/atributo** | CORE02 + SEG + donos do recurso | UI, plano `ativo`, webhook, flag ou superadmin sem escopo | política por ação/recurso/campo/tenant, decisão deny, SEG aprovado |
| **Status de trial/assinatura** | FIN/CON CRM | IAM, Portal, registro financeiro não conciliado | fato oficial com versão, origem, eficácia, conciliação |
| **Elegibilidade cortesia por site** | Portal Sites + FIN/CON segundo contrato | simples ligação visual do site, CNPJ, tentativa de pagamento | vínculo autorizado site↔CRM e evento oficial |
| **Fato transportado por integração** | INT prova transporte; **owner do domínio** confirma resultado de negócio | código HTTP 2xx, sucesso técnico de fila ou callback isolado | proveniência, idempotência e confirmação do domínio |
| **Auditoria e retenção** | SEG/auditoria/LGPD por categoria | retenção do trial, cancelamento CRM ou prazo universal de logs | base legal/finalidade, revisão de acesso, prazo e descarte |
| **Homologação e fechamento** | owner competente + SEG/LGPD/FIN/CON quando cabível, com gate próprio | aprovação preliminar R01–R06 ou documentação genérica | evidências versionadas e autorização formal e distinta |

**Regra de conflito proposta:** sem fonte oficial reconciliada, **não criar novo direito, cobrança ou reativação**. Também **não suspender arbitrariamente** direitos previamente comprovados; avaliar política de contingência própria, segurança da conta e autoridade de cada estado. Essa regra não substitui decisão final FIN/CON/SEG.

## 4. Sequência de dependências documentais (sem executar código nem testes)

| Etapa candidata | Entradas mínimas necessárias | Saída documental esperada | Pode fechar C01..C08 ou gates agora? |
|---|---|---|---|
| **R06-P01** Cotejar fontes | recuperar texto original R01-D01..D05; versões R01–R05; REV-01/02 | inventário de divergências e precedências | **NÃO** |
| **R06-P02** Nomear owners | matriz do §2 e competências verificáveis | quadro de responsáveis e aprovadores a confirmar | **NÃO** |
| **R06-P03** Classificar dados | S01, CORE01/02, LGPD | catálogo de somente dois campos + respostas negativas | **NÃO** |
| **R06-P04** Separar fatos comerciais | FIN/CON/Portal/INT/CORE08 | mapa de eventos com estados/relógios separados | **NÃO** |
| **R06-P05** Avaliar políticas | CORE/SEG/INT/AUT | critérios de autorização, revalidação, isolamento e concorrência sem implementação | **NÃO** |
| **R06-P06** Rever evidências | R05 e documentação validada por owner | fichas de evidências por C01..C08, sem testes executados | **NÃO** |
| **R06-P07** Submeter homologação futura | decisão expressa para eventual fase técnica e artefatos reproduzíveis | pacote de provas para aprovação individual futura | **NÃO, sem autorização distinta e provas** |

Não interpretar etapas como ordem vinculante de implementação: todas são propostas documentais ainda pendentes.

## 5. Critérios negativos de revisão conceitual

| Caso | Conflito/ataque | Resultado exigido na política FUTURA, a homologar |
|---|---|---|
| **R06-N01** | mesmo usuário alterna tenants e tenta ler vínculo de outra organização | negar sem enumeração ou vazamento, selecionar contexto por fluxo seguro |
| **R06-N02** | membership revogada e sessão ainda válida | consultar apenas quando política/versão do vínculo for válida; negação segura |
| **R06-N03** | RLS filtra mas aplicação confia no tenant do cliente | não aceitar o cliente como autoridade; defesa em profundidade obrigatória |
| **R06-N04** | site inadimplente, CRM pago válido | regra comercial do site não bloqueia CRM pago por si só |
| **R06-N05** | webhook 2xx sem comprovação de pagamento | não conceder benefício, reativar ou cobrar por inferência |
| **R06-N06** | evento comercial obsoleto é repetido | não sobrepor estado mais novo; conciliador decide sem autoconcessão |
| **R06-N07** | suporte/global tenta ler S01 de cliente sem autorização específica | nenhuma autoridade global por padrão, negar e registrar minimamente |
| **R06-N08** | auditoria registra payload pessoal completo | exigir minimização e retenção/acesso por categoria |
| **R06-N09** | tentativa de retornar CNPJ/faturas/empregados junto aos dois campos | negar campos extras e preservar projeção mínima |

**Nenhum dos casos acima foi executado ou homologado**. A redação não cria suites nem certificados de conformidade.

## 6. Quadro de pendências e bloqueadores (NÃO ENCERRAR)

| Pendência | Situação nesta minuta | Consequência |
|---|---|---|
| Pauta literal R01-D01..D05 | **a recuperar/conferir** | nenhuma equivalência textual inventada |
| R02-C01..C08 | **todos PENDENTES** | owners/contratos não homologados |
| 67 IDs técnicos CORE | **todos ABERTOS** | nenhum fechamento por ratificação documental |
| SEG-01/02, AUT | **ABERTOS** | sem política executável nem direito novo |
| CORE02 precedência e CORE08 quotas | **ABERTOS** | não interpretar estado comercial como permissão |
| FIN/CON + Portal + INT | **ABERTOS** | fonte oficial e conciliação ainda a homologar |
| LGPD, ARQ/AGT, DEV-GATE e demais gates | **ABERTOS** | sem liberar desenvolvimento, CI ou produção |
| PR #2/#21 e `main` | **fora do escopo** | nenhum merge/rebase/modificação |
| VPS e branch DEV | **fora do escopo** | sem acesso/instalação/deploy |

## 7. Próxima deliberação R06-D01..D08 — ALTERNATIVAS A PROPOSTAS, NÃO APROVADAS

| Decisão | **Alternativa A proposta** | Alternativa B de contraste |
|---|---|---|
| **R06-D01** | **A:** confirmar owners e competência documental por C01..C08 antes de aceitar aprovações de domínio | B: presumir autoridade de qualquer aprovador genérico |
| **R06-D02** | **A:** manter autoridade IAM/membership independente de FIN/CON/Portal e autorizações CORE/SEG | B: derivar acesso de status comercial |
| **R06-D03** | **A:** mapear autoridade oficial, versão, eficácia e precedência dos eventos CRM/Portal/INT | B: aceitar callback técnico ou cache como fato final |
| **R06-D04** | **A:** limitar S01 aos dois campos mínimos e exigir não enumeração/visibilidade por autorização válida | B: abrir dados de outras organizações ou comerciais |
| **R06-D05** | **A:** exigir reconciliação de concorrência/conflito por owner, sem autoconcessões nem suspensão arbitrária | B: escolher `ativo` ou `inativo` automaticamente sem prova |
| **R06-D06** | **A:** manter fichas R05 versionadas e critérios negativos como documentação até autorização técnica distinta | B: tratar documentos como testes realizados |
| **R06-D07** | **A:** sujeitar auditoria, acesso, retenção e descarte à categoria/finalidade e revisão LGPD/SEG | B: usar retenção universal e logs integrais |
| **R06-D08** | **A:** preservar C01..C08, 67 CORE, SEG, AUT e demais gates ABERTOS; homologação posterior individual | B: declarar fechamento por sequência de aprovações preliminares |

**Pedido para deliberação:** ratificar, revisar ou rejeitar **as alternativas A de R06-D01..D08 somente como diretrizes documentais preliminares**, sem aprovar automaticamente owners, contratos de segurança, evidências finais ou critérios executáveis.

## 8. Proibições e rastreabilidade da entrega

Documento Markdown novo **somente na branch isolada** `docs/func-p0-01-s01-r01-r02-2026-11-06`. Não modificar o texto de R05 para declará-lo homologado; sua seção de propostas permanece histórica, prevalecendo a decisão expressa de ratificação R05 como registro separado.  
**Não** criar ou alterar código, SQL, GRANT/ALLOW, schema, migration, testes funcionais, CI, APIs, cobranças, dados reais, credenciais, VPS, produção ou branch `dev/fast-track-crm-2026-11-04`. Não alterar `main` ou os PRs #2 e #21.  
**Todas as pendências de homologação seguem abertas.**
