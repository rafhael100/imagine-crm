# CRM-CORE-P0-01 — Etapa 2 / CORE01-D04 a D07: memberships, contexto de tenant, ciclos e RLS (proposta v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE01-D04-A/B/C aprovadas apenas como diretrizes técnicas documentais preliminares** [decisão](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md); **CORE01-D04 (item integral), D05, D06 e D07 continuam ABERTAS** e não homologadas. A redação histórica posterior que indica D04-A/B/C ainda não aprovadas foi superada pelo aceite parcial.  
**Finalidade:** desdobrar quatro dependências técnicas da [base lógica CORE-P0-01 D01–D03 aprovada](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md) em contratos verificáveis e opções de decisão.  
**Vínculo:** [Etapa 2 — matriz de pendências](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) e [registro de aprovação metodológica CORE10](CRM-DEC-aprovacao-CORE-P0-10-D01-D03-base-homologacao-2026-11-04.md).  
**Fase:** estritamente planejamento. **Não criar schema, migração, RLS real, endpoint, ambiente de teste, conta de usuário, job, credencial, provisionamento, programação nem executar qualquer teste**.

## 1. Fundamentos aprovados que não serão redeliberados sem conflito real

- Uma identidade humana de autenticação **global** pode ter memberships independentes em múltiplas organizações CRM, sem compartilhar direitos, quotas, dados ou sessões de acesso ao tenant automaticamente.
- Uma organização CRM corresponde a **um tenant lógico**; mesmo CNPJ pode possuir múltiplos tenants por contratos de Site elegíveis, e **um trial por CNPJ** permanece distinto desses direitos.
- O CRM usa **Laravel e PostgreSQL**; identidade/autorização verificadas pelo backend e **PostgreSQL Row-Level Security (RLS)** como defesa adicional nos registros tenant-bound. A decisão RLS já aprovada não determina sua política executável.
- Admin Imagine não é admin tenant: suporte requer mandato excepcional verificável. Identidade de serviço, agentes, n8n e integrações não herdam permissões de usuário humano nem de prompt.
- Plano Básico: **um único usuário humano autenticado**, administrador da empresa com MFA. Profissionais de agenda e identidades técnicas são quotas diferentes.
- Convite de funcionário: **7 dias**, aceitação segura por identidade, assento, MFA e política do tenant; desligar membership numa organização não destrói identidade nem outras memberships.
- Invariantes CORE01-I01..I10 já aceitos como referência lógica: negação por padrão; tenant resolvido no servidor; isolamento em Laravel+RLS; conexões/worker sem vazamento; integridade de FKs; objetos globais por exceção; identidade distinta de entitlement; agentes restritos; transições preservam histórico; testes apenas planejados.
- **Não pressupor SSO Portal↔CRM**, seleção de tenant pelo e-mail/CNPJ, acesso genérico ao banco, superusuário em RLS, backup compartilhado nem compra de serviço novo.
- CORE02-D04..D10, CORE03-D04..D10, CORE04-D04..D10, CORE05-D04..D10, CORE07-D04..D10, CORE08-D04..D10, CORE09-D04..D10, SEG-01/02, INT P0 e G01..G06 continuam abertos.

## 2. Fronteiras de dados para especificação futura (não tabelas aprovadas)

| Família | Classificação lógica pretendida | Segurança e fonte oficial |
|---|---|---|
| Identidade global (login, MFA, recuperação) | **Global protegida** | Identidade/Segurança define autenticação; relações de pertencimento não abrem conteúdo |
| Tenant/organização, estado e titulares | **Tenant com metadados administrativos protegidos** | CRM/FIN/CON definem situação; CNPJ não é tenant nem permissão |
| Membership/papel/escopo/equipe/convite | **Relação tenant↔identidade, tenant-bound** | IAM/CORE02/07; estados e assentos por tenant |
| Leads, contatos, oportunidades, agendas, atendimentos, arquivos e automações | **Dados de negócio tenant-bound** | Proprietários de módulo; todo caminho (API, SQL, job, arquivo, exportação, busca) respeita tenant e direitos |
| Site/contrato, campanha, seleção de destino CRM e benefício | **Relação transversal com dono externo e tenant de destino autorizado** | Portal/Sites e CRM/FIN; evidência oficial e estados; sem merge de leads |
| Plano/catálogo, parâmetros centrais | **Global por exceção expressa** | Administração Geral; leitura global não autoriza dados tenant-bound |
| Segredo, serviço/agent principal e conexão externa | **Serviço protegido / vínculo por tenant e ambiente** | Cofre/CORE09/INT-02/SEG-01/02; token externo não basta |
| Auditoria e evidências | **Categoria e escopo próprios, sem acesso genérico** | Auditoria Geral/CORE06; preserva retenção por classe e auditoria da auditoria |
| Mandato de suporte | **Escopo excepcional por caso/tenant/ator/período** | CORE05; nunca bypass irrestrito de RLS |
| Jobs, eventos e entregas | **Mensagens identificadas e correlacionadas por escopo oficial** | CORE03 e dono do negócio; presença de tenant_ref não equivale a grant |

Cada família terá em etapa própria: objetos concretos, campos/classificação, fonte de verdade, proprietário, consulta/escrita, retenção, testes e exceções justificadas. **Nenhuma família está aprovada como schema físico neste documento.**

## 3. CORE01-D04 — Contrato de membership, perfis, convite e assentos (PROPOSTA DE PAUTA, NÃO DECISÃO)

**Pendência original:** compatibilizar estados e direitos da identidade em organização com CORE02/07, sem duplicar usuários ou assentos.

### 3.1. Requisitos documentais candidatos

1. Um vínculo efetivo deve ser referenciável por **identidade + organização**, com estado/vigência e histórico. Mesmo login pode ter vínculo ativo em A e desligado em B; isso não altera a titularidade do tenant nem libera outra conta.
2. Convite deve ser **intenção pendente** (e não grant), com destinatário, tenant, papel pretendido e aceite verificável; expiração **7 dias**, cancelamento e reenvio sem duplicação. Ocupação/reserva de assento em convites pendentes **não está decidida** (CORE07-D05).
3. Perfis, escopos próprio/equipe/organização, ações e campos só geram direito após resolução de **CORE02**; mudança em equipe ou status exige reavaliar novas consultas/exportações/jobs.
4. Identidade global, membership, papel, equipe e entitlement do plano são objetos distintos; **Básico 1 usuário autenticado** não cria segundo operador por convite, agenda ou substituição administrativa informal.
5. Desligamento de A deve revogar novos acessos de A e preservar B, sem apagar autorias e fatos históricos. Riscos de acesso residual em tokens/worker/cache seguem CORE03/04/07.
6. Titularidade/representação legal da empresa e associação contratual não são deduzidas de e-mail idêntico, CPF/CNPJ ou domínio.

### 3.2. Decisões concretas que exigirão aceite posterior

| Subquestão | Opções a confrontar, SEM escolha agora | Dependência |
|---|---|---|
| Unicidade de vínculo | Uma relação por identidade×tenant com estados versionados versus outras representações justificadas; duplicatas e readmissões | CORE01-D07, CORE07-D04/06 |
| Papéis múltiplos e negações | Precedência/combinação de papéis; negação explícita, herança, escopo de equipe, privilégios de admin | CORE02-D04..D10 |
| Contagem de assentos | Convite reserva vaga ou apenas aceite efetivo; suspensão/reativação e corrida pelo último assento | CORE07-D05, FIN/CON/CORE08 |
| Recuperar admin principal | Substituição legítima e último admin Básico sem criar superusuário ou bypass do MFA | CORE04-D07, CORE07-D06 |
| Vínculos especiais | Suporte/mandato, serviço, representantes e auditoria **não** equivalem a funcionário normal | CORE05/06/09 |

**Critério futuro da D04:** aceitar um único contrato versionado de membership/convite/papel/assento, com estados, fonte, owner, invariantes de concorrência, exemplos de permissão negada e exceções, após cotejar CORE02/07. **D04 continua ABERTA.**

## 4. CORE01-D05 — Contexto de tenant, conexões RLS e principais técnicos (PROPOSTA DE PAUTA, NÃO DECISÃO)

**Pendência original:** fazer o isolamento Laravel+PostgreSQL RLS funcionar de maneira coerente em APIs humanas, jobs, conexões reutilizadas, integrações, suporte e cofre.

### 4.1. Fronteiras onde o contexto deve ser provado

| Fronteira | Principal/contexto mínimo necessário na futura especificação | Perigo a bloquear |
|---|---|---|
| Requisição de usuário | Identidade autenticada, tenant selecionado de vínculo ativo, papel/ação/campo, entitlement e política do módulo | Aceitar `tenant_id` enviado arbitrariamente por header/body ou último tenant |
| Worker/job | Executor técnico válido + referência à autoridade causal, tenant/objeto, versão/estado, revalidação atual quando exigível | Herança irrestrita de sessão humana revogada |
| SQL/PostgreSQL | Contexto de segurança confiável por operação e sessão/conexão; controles RLS adequados por família | Bypass, SET inseguro, reuso de conexão A para B, pool com contexto residual |
| Integração externa | Site/formulário, conta/conexão, destino CRM autorizado, credencial/assinatura quando aplicável | Provider ACK 200 ou CNPJ como atestado de direito/fato |
| Administração central | Principal central e ato administrativo tipado, não autorização automática aos dados | Superadmin invisível, suporte sem mandato |
| Suporte excepcional | Caso/tenant/ator/período/objeto/ação aprovados e rastreáveis | Contorno geral de RLS por manutenção |
| Exportação, busca, backup e restore | Tenant resolvido, visibilidade/recurso/campo, autoridade específica; revalidar no início e antes de disponibilizar resultado | Documento temporário disponível a quem perdeu acesso, mistura entre tenants |
| IA, agente, n8n interno | Principal técnico + ferramenta tipada + tenant/ambiente/versão/playbook/risco; sem permissão via prompt | Acesso ampliado por modelo de IA, workflow ou token do provedor |

### 4.2. Escolhas técnicas ainda pendentes

- **Isolamento RLS:** mapear papéis de banco necessários, política por tabela/família, como resolver tenant confiavelmente em transações e operações de leitura/escrita, como impedir bypass por role e como tratar exceções globais. **Nenhuma política SQL, GUC, SET LOCAL, role ou pool está homologada**; a escolha posterior deve comparar alternativas e incidentes possíveis.
- **Mecanismo de contexto:** escolher como Laravel transmite ao PostgreSQL/worker informações validadas, proteção do canal, limitação ao escopo mínimo e reset após transação/erro; compatibilidade com workers, transações aninhadas, pool e retry.
- **Serviços e suporte:** escolher como identidades técnicas recebem direitos por operação sem chave global, e como mandato excepcional atravessa a fronteira de serviço mantendo a finalidade e expiração; SEG-01/02 e CORE09-D05/06 devem ser fechados coordenadamente.
- **Integração com sessões:** troca de tenant, MFA por papel, expiração, logout/revogação, alteração de perfil e senha exigem reavaliação; CORE04-D05/10 e CORE03-D05/06.
- **Falha segura:** ausência do contexto, autorizador/cofre indisponível ou invalidação de vínculo **nega novos atos protegidos**, sem inventar permissões por fallback.
- **Resultados:** rejeição do acesso, pendência de job e resultado oficial de negócio são coisas diferentes; preservar trilhas e fatos confirmados.

**Critério futuro da D05:** publicar matriz IAM+RLS por fronteira, opção de contexto escolhida com riscos e rollback, compatibilidade Laravel/PostgreSQL e critérios de verificação sem vazamento cross-tenant. **D05 continua ABERTA.**

## 5. CORE01-D06 — Ciclo de vida do tenant, titularidade e vínculos comerciais (PROPOSTA DE PAUTA, NÃO DECISÃO)

**Pendência original:** coordenação do ciclo de organização e direito financeiro por contratos próprios, sem unificar CNPJs, trials ou dados.

| Evento | Resultado lógico que deve continuar verdadeiro | Especificação/owner pendente |
|---|---|---|
| Primeiro contrato Site elegível | Cliente confirma tenant autorizado existente ou novo; CRM confirma ativação de benefício, sem criação duplicada | Portal/Sites/CRM, FIN/CON, CORE08-D04/06/07 |
| Segundo Site do mesmo CNPJ | Pode sustentar tenant diferente com cortesia se contrato e escolha legítimos; **não cria novo trial** | Regra de titularidade, prova contratual, idempotência |
| Dois Sites no mesmo tenant | Uma cortesia efetiva; sem duplicar assentos e franquias | Contratos de benefício/cota/versão |
| Trial por CNPJ consumido | Não criar trial adicional por tenant ou Site | Fonte oficial do histórico de trial e política de antifraude |
| Cancelamento comum do último Site | 30 dias operacionais e avisos oficiais; sem cobrança tácita | Origem e data do evento, timers e concorrência |
| Cancelamento após suspensão por inadimplência | 30 dias **restritos** desde cancelamento formal, preservando exportação/contratação paga | Precedência COM-08/09, FIN/CON |
| CRM pago ativo, Site encerra | Preservar assinatura paga independentemente | FIN/CON e escopo por tenant |
| Conta CRM formalmente cancelada | Retenção de 120 dias após **cancelamento do CRM**, não por mera suspensão ou evento do Site | LGPD, CRM-REV02/CORE06 |
| Troca de tenant vinculado ao Site | Chamado e nova integração técnica gratuita; sem migração silenciosa de históricos | Portal/CRM, INT P0/IN-118/122 |
| Alteração/reativação de titularidade/representante | Comprovar direito, registrar transição e preservar isolamento | Segurança/Contrato; não conceder papel por semelhança de CNPJ |

**Critério futuro da D06:** contrato de eventos oficiais por proprietário, estados e versões de organização/benefício/assinatura e prova de titularidade, decisões de precedência, resposta a duplicação/out-of-order, política de uso restrito, auditoria e limites por tenant. Sem encerrar CORE08, FIN/CON ou INT automaticamente. **D06 continua ABERTA.**

## 6. CORE01-D07 — Modelo executável e critérios futuros de segurança (PROPOSTA DE PAUTA, NÃO DECISÃO)

**Pendência original:** reconciliar os elementos D04..D06 em uma especificação física segura de Laravel, PostgreSQL e integrações. Um inventário não substitui a escolha concreta.

### 6.1. Artefatos exigíveis para aprovação posterior

| Artefato documental futuro | Deve cobrir | Dependência |
|---|---|---|
| **Mapa de entidades e relações** | Identidade, tenant, membership, papéis, equipes, convites, Site↔tenant, entitlement, service principal e mandato; exceções globais | D04/D05/D06, CORE02/07/08/09 |
| **Classificação por tabela/família** | Tenant-bound ou global por exceção; leituras/escritas, campos sensíveis, owner, retenção | CORE02, CORE06, LGPD |
| **Contrato de integridade** | Identificadores, unicidade, foreign keys, constraints, referências cruzadas e prevenção de tenant A→B | CORE01-I05, CORE07-D04/D05 |
| **Plano lógico RLS por papel/serviço** | Negrar por falta de contexto, verificação no banco e backend, exceções justificadas, proteção contra bypass | D05, SEG-01/02 |
| **Semântica de conexão e transação** | Pool/reuso, início/fim/reset do tenant, erro/retry, workers e exportações | CORE03-D06, CORE04-D05 |
| **Contratos de operações e eventos** | Versionamento, idempotência contextual, alteração de vínculo, estado oficial, conciliação, provas de resultado | CORE03/08, INT CT-03/04, FIN/CON |
| **Matriz de acesso/negação** | Identidade/tenant/recurso/ação/campo, MFA, suporte excepcional e identidade técnica; matriz pela competência correta | CORE02/04/05/09, INT P0-OWN/SEC |
| **Plano de migração/recuperação** | Compatibilidade, versionamento/rollback de schema, dados existentes, backups e credenciais sem reativação indevida | CORE06/09, IN-168 ARQ/SEG |
| **Planos de homologação futura** | Cenários negativos, dados fictícios, ambiente, evidências, responsável; todos **NÃO EXECUTADOS** | CORE-P0-10, INT HT, SEG-01/02, G01..G06 |

### 6.2. Critérios negativos que devem integrar a homologação futura (não executados)

| ID de referência | Caso | Resultado exigível, sem teste hoje |
|---|---|---|
| CORE01-E2-T01 | Usuário com vínculo ativo em A tenta obter recurso de B por ID ou body | Laravel e RLS negam; sem ler metadados sensíveis de B |
| CORE01-E2-T02 | Reuso de conexão depois de operação em A carrega contexto de A para B | Limpeza/reset comprováveis; nenhuma leitura cruzada |
| CORE01-E2-T03 | Worker exporta dados após membership do solicitante ser revogada | Revalidar; negar novo efeito protegido e preservar evidência do anterior |
| CORE01-E2-T04 | FK associaria entidade A a registro de B | Integridade contextual nega o vínculo |
| CORE01-E2-T05 | Admin Imagine tenta ler lead sem mandato | Negar por escopo; trilha oficial |
| CORE01-E2-T06 | Serviço n8n/IA pede privilégio pela instrução do modelo | Negar; ferramenta/ação tipada e autorizada por SEG |
| CORE01-E2-T07 | Mesmo CNPJ contrata segundo Site com novo tenant Básico | Elegibilidade verificada, sem novo trial e sem mezclar registros |
| CORE01-E2-T08 | Restore de backup reintegra permissão ou segredo revogado | Não reativar sem política/autoridade válida e reconciliação |
| CORE01-E2-T09 | Solicitação de exportar depois de suspender conta por cortesia | Respeitar acesso restrito de portabilidade e autenticação; não reabrir operação plena |
| CORE01-E2-T10 | Formulário em Site estático tenta receber lead sem CRM autorizado | Backend bloqueia independentemente do cache e do domínio |

**Não criar suíte duplicada:** mapear cada referência CORE01-E2-T acima para CORE01-T01..10, REV01 S, OPS-T, INT HT13 e CORE10-D06; IDs E2 são **referências de revisão** e não testes executáveis ou obrigações inéditas. Se um caso já for canônico, usar seu identificador existente e descartar o alias E2 antes da aprovação final.

### 6.3. Estados que não podem ser misturados

- **Proposta criada:** este documento; ainda não aprova CORE01-D04..D07.
- **Decisão D04/D05/D06/D07 aprovada documentalmente:** só por ato expresso e escopo preciso.
- **CORE01 tecnicamente fechado:** somente quando contratos físicos, revisões e dependências críticas forem resolvidos documentalmente.
- **Desenvolvimento autorizado:** somente depois do aceite final do planejamento global com referência imutável/versão, por decisão própria.
- **Teste executado / homologado:** apenas em fase futura expressamente autorizada, com evidências reais; não se deve preencher agora.

## 7. Pontos que exigem deliberação separada

| Decisão ainda aberta | Pergunta objetiva para futura escolha | Requisitos para submetê-la |
|---|---|---|
| **CORE01-D04** | Qual contrato versionado de membership/papel/convite/assento e quais estados/precedência? | Confrontar CORE02-D04..D10 e CORE07-D04..D10 |
| **CORE01-D05** | Como transmitir e revalidar contexto confiável de tenant em Laravel, PostgreSQL RLS, workers, serviços e suporte sem bypass? | Propor opções executáveis, riscos e implicações de SEG-01/02/CORE03/04/09 |
| **CORE01-D06** | Como definir e provar eventos, titularidade, ciclos e autoridade dos vínculos Site↔CRM e assinaturas sem duplicação? | Contratos FIN/CON, CORE08-D04..D10, INT P0 |
| **CORE01-D07** | Qual é o esquema final e matriz de políticas RLS e constraints por recurso/serviço e qual homologação documental comprova? | D04..D06 reconciliadas, owners, versões e critérios CORE10 |

**Próxima revisão recomendada:** começar com **CORE01-D04** (membership, papéis e assentos) juntamente com CORE02/07, pois as decisões concretas de concorrência de convites e última vaga ainda não estão tomadas. D05–D07 devem ser aprofundadas em seguida, sem acelerar fechamento pelo simples aceite das propostas.

## 8. Bloqueios e não autorização

**CORE01-D04 a D07 permanecem ABERTAS; CORE02..CORE10-D04 a D10 continuam ABERTAS.** Especialmente **IN-168 SEG-01/02**, ARQ-01/02/03 e AGT-01, INT P0 e G01–G06, FIN/CON, LGPD, revisão de preços/custos e aprovação global.

O documento registra hipóteses para **futuras escolhas**, não escolhe produto, fornecedor, schema, políticas executáveis, provedor de autenticação ou permissões novas; não realiza operação e não é instrução de programar. A continuidade exige aceite específico do responsável quando as opções forem fechadas.

**SEM PROGRAMAR OU EXECUTAR TESTES.**


## 9. Aceite preliminar de CORE01-D04-A/B/C e continuidade CORE01-D05 — 04/11/2026

[CRM-DEC de aceite parcial](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md): aprovadas somente como diretrizes técnicas documentais preliminares **unicidade de membership identidade×tenant**, **convite separado de concessão** e **revogação por organização**. A decisão exige manter **CORE01-D04 ABERTA** até conciliação CORE02/04/07/08, e preserva D05–D07/SEG/INT/G01–G06. Próxima [proposta técnica CORE01-D05 sobre contexto de tenant, Laravel e PostgreSQL RLS](CRM-CORE-P0-ETAPA2-CORE01-D05-contexto-tenant-RLS-proposta-v0.1.md), cujas diretrizes A/B/C/D **ainda não foram aprovadas**. Nenhuma programação, teste ou implantação.
