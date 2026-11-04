# CRM Imagine — F1-P0: pacote consolidado de identidade, isolamento, RLS e segurança (v0.1)

**Data:** 04/11/2026.  
**Estado:** **ESPECIFICAÇÃO TÉCNICA CANDIDATA + MATRIZ DE GO/NO-GO, AINDA NÃO HOMOLOGADA PARA F1**.  
**Autorização:** pedido "ok vamos fazer isso" para **fechar em um único pacote** os aspectos críticos de IAM, tenant, RLS e Segurança; há uma [decisão distinta de GO F0](CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md) permitindo scaffold/código não privilegiado **somente** em branch isolada. O pedido atual não aprova automaticamente dados de clientes, acesso real, grants, ambiente pago, integração, release ou produção.  
**Branch F0:** `dev/fast-track-crm-2026-11-04`. **F1:** planejamento de contratos físicos e segurança, **ainda não liberada** para implementar/ativar recursos privilegiados com dados reais.  
**Repositório:** `rafhael100/imagine-crm`. Documento canônico de coordenação **sem criar novos motores, P0, gates ou serviços**. A matriz [E2-R01](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md) continua sendo índice transversal dos 67 itens CORE.  
**Sem atividade executável por este ato:** não executar testes, instalar, migrar, configurar banco, provisionar cofre/usuários, emitir credenciais, contratar serviços, realizar pagamentos, alterar dados reais ou implantar produção.

## 1. Decisões de arquitetura já autorizadas a serem preservadas

| Fronteira | Invariante já aceito | Base e status |
|---|---|---|
| **Identidade global × tenant** | Uma identidade global pode participar de **vários tenants separados**; **uma organização CRM = um tenant**. CNPJ igual não implica unificação ou acesso; **uma membership lógica ativa identidade×tenant**. | [CORE01](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md); D04 diretrizes preliminares, **D04 ABERTA** |
| **Convite × vínculo × assento** | Convite de 7 dias é intenção e não concede acesso; aceite idempotente e ativo só após requisitos/assento. Básico tem **um administrador humano autenticado com MFA**. | CORE01-D04, [CORE07](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md); regras físicas e concorrência pendentes |
| **Autorização cumulativa** | Tenant/membership/mandato, plano/entitlement, módulo, ação, campo, escopo, estado, finalidade e segurança são cumulativos; **negação por padrão**. | [CORE02](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md); D04/D06 preliminares; contratos reais abertos |
| **Perfis múltiplos** | Selecionada como política-base documental delegada **P1 (união de grants válidos)** com **P2 (negação explícita qualificada limitada ao mesmo objeto/escopo)** sob bloqueios superiores. | [Decisão P1+P2](CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md); **CORE02-D05 ABERTA** até contrato por módulo/campo |
| **RLS em duas camadas** | Laravel autoriza o negócio e PostgreSQL **RLS restringe linhas**; serviço/worker/suporte não herdam superusuário, BYPASSRLS ou identidade humana por analogia. | CORE01-D05/D07 preliminares; [SEG-01/02](IN-168-matriz-pendencias-fechamento-arquitetura.md) ainda P0 |
| **Assíncronos/integrações** | Principal técnico e autor humano causal são diferentes; transação/contexto/replay/revogação exigem revalidação segundo o efeito; confirmação só pelo módulo proprietário. | [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md), INT; detalhes físicos ainda abertos |
| **Segredos/suporte** | Suporte central não ganha acesso comercial universal; acesso excepcional depende de mandato; segredos não entram no GitHub, prompts ou logs. | [CORE05](CRM-CORE-P0-05-acesso-excepcional-imagine-mandatos-suporte-proposta-v0.1.md), [CORE09](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md), SEG-01/02 |
| **F0 existente** | Estrutura Laravel 13/PHP>=8.3 em branch segregada, sem autoridade funcional real, testes declarados ou produção. | [DEC-FASTTRACK-01](CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md) |

**Precedência:** nenhum dos exemplos físicos abaixo derruba política funcional do CRM, Site/Portal, FIN/CON, IN-118, IN-127 ou LGPD. É proibida a interpretação "novo campo = novo dado a coletar" ou "ação candidata = recurso vendido".

## 2. F1-01 — contrato candidato de dados (MER lógico para revisão, NÃO SQL)

| Família/tabela candidata | Escopo lógico | Cardinalidade/integridade proposta | Segurança e dono |
|---|---|---|---|
| **identities** | Global protegida | identidade única; meios de login e verificações vinculadas; nunca serializar MFA/segredos livremente | IAM/CORE04, fora de RLS comercial |
| **tenants** | Catálogo de organizações CRM | chave opaca por tenant; titularidade/CNPJ em referência controlada, não chave universal para unir dados | CORE01/08/FIN/CON; administração global delimitada |
| **memberships** | Tenant-bound + referência global a identity | **unicidade lógica (tenant, identity)**, estados explícitos, histórico e revogação por tenant | CORE01/07; convite ≠ membership ativa |
| **invitations** | Tenant-bound | token opaco, armazenar somente hash, destinatário verificado, expiração 7 dias, consumo/cancelamento idempotente | CORE04/07; não reserva de assento por padrão sem decisão CORE07-D05 |
| **roles / role_assignments / grants / denies** | Tenant-bound | perfil não cria entitlement; fonte/escopo/campo/versão e delegação rastreados; grant≠poder de delegar | CORE02, CORE07; política P1+P2 ainda sem matriz efetiva dos módulos |
| **teams / team_memberships** | Tenant-bound | unidade/equipe, relações e vigência por tenant; equipe não é papel de acesso universal | CORE07/ATD/VEN/AGD |
| **sessions / auth_challenges / recovery** | Principal/tenant-contextual conforme finalidade | revogação, step-up, MFA, rotação/invalidade de sessão e dispositivos; segredos não em texto claro | CORE04/09/SEG |
| **service_principals / mandates** | Administração técnica protegida + autorização explícita tenant-bound quando cabível | principal técnico ≠ autor causal; mandato por caso/ação/recurso/tenant/prazo, revogação e razão | CORE05/09, SEG-01/02 |
| **audit_events / security_events** | Categoria e proprietário próprio, sempre com relação segura ao tenant quando aplicável | append-only lógico, prova de origem, minimização e retenção por classe | CORE06/Auditoria Geral/LGPD |
| **dados comerciais futuros** | **sempre tenant-bound** (DAT/VEN/ATD/AGD/AUT etc.) | toda referência A→B entre dados tenant-bound exige consistência do tenant, inclusive arquivos, índices e exports | CORE01/02/03 + dono funcional; fora do recorte inicial F1 |

**Recomendação candidata de integridade:** identificadores opacos internamente únicos para entidades; em relações entre entidades tenant-bound, aplicar **FK composta (tenant_id, parent_id) referenciando chave/index único (tenant_id, id)**, além de RLS e autorização de negócio. Outras alternativas com índices/constraints equivalentes devem ser comparadas. Uma FK só por `id` não demonstra isolamento de relacionamento. Para identidade global, usar referência legítima específica e **não** FK tenant-bound artificial em identities. **Não foram escolhidos tipos SQL, tabelas finais, esquema e migrations**.

**Pendências P0 F1-01:** decidir PK física (UUID/ULID/inteiro) e índices/particionamento, estados/temporalidade, unicidade por tenant para histórico, restrições de delete/restauração, todas as exceções globais e classificação F-ID..F-ART por campo. CORE01-D07, CORE02-D04, CORE06/07, LGPD e FIN/CON **abertos**.

## 3. F1-02 — contrato candidato PostgreSQL RLS e contexto de tenant

**Escolha de segurança recomendada para especificação final:** **default deny**, roles de aplicação **sem `SUPERUSER` nem `BYPASSRLS` e preferencialmente que não sejam proprietárias das tabelas**, política RLS explícita por tabela/ação e reforço de `FORCE ROW LEVEL SECURITY` onde a tabela/owner permitir. Separar identidade de **dono do schema/migrations** e papel de **execução da aplicação**, com privilégios mínimos.

| Componente | Contrato candidato mínimo | Não aprovado/risco a resolver |
|---|---|---|
| **Laravel request** | contexto derivado de identidade verificada + membership/entitlement ativo e tenant solicitado; autorização de ação/campo/escopo em policy própria | método de login/sessão/MFA, owner/versão da matriz; CORE02/04 |
| **Início da operação DB** | contexto tenant + ator causal + serviço autenticados, **limitados à transação**, com limpeza automática no encerramento, sem contexto persistente no pool | técnica física exata (`SET LOCAL`, `set_config`, connection pooler, nested transactions, retries) CORE01-D05/CORE03-D06 |
| **RLS para tabela tenant-bound** | restringir **SELECT/UPDATE/DELETE** a linhas do tenant comprovado; **INSERT/UPDATE** também precisam de condição de escrita (`WITH CHECK`) | matriz real **USING/WITH CHECK** por tabela/role/ação, exceções de serviços e owner |
| **Prova independente** | ACL backend e FK de integridade tenant-bound, revalidação por recurso; serviços externos/arquivos requerem ACL própria | RLS em metadados **não protege automaticamente** URLs de arquivos, caches, índices, objetos de storage ou filas |
| **Serviços/worker** | cada job possui tenant, operação, actor causal, service principal, versão e origem verificadas; checar autoridade atual antes de novo efeito sensível | envelope físico CORE03-D04..D10, SEG-01/02; nunca executar n8n com privilégio global presumido |
| **Acesso de suporte** | IAM central separado dos dados comerciais; mandato por caso, recurso, tempo, finalidade e auditoria | fluxo CORE05-D05/06/07, break-glass e regras legais pendentes |

**Advertência obrigatória de segurança:** uma *custom session variable* PostgreSQL definida pela aplicação (por exemplo, `app.tenant_id`) **não é, por si só, credencial confiável de tenant**, pois uma conexão indevidamente exposta ou uma vulnerabilidade de SQL injection poderia alterá-la. Não confiar em RLS baseada apenas em variável arbitrária. O fechamento deverá demonstrar controle da origem do contexto e da função que o estabelece, permissão SQL mínima, validações independentes de membership/principal para operações humanas ou serviço e isolamento transacional/pool, inclusive contra SQL injection e acesso lateral. A estratégia física exata permanece **NO-GO F1** até revisão de Segurança/DBA.

**Casos negativos a especificar, não executados:** consulta ID de B por usuário A, FK A→B, retry após revogação, worker B recebendo conexão A, admin central sem mandato, arquivo privado com URL reutilizada, bypass por owner e `SECURITY DEFINER`, query em view, funções SQL privilegiadas, import/export de dados cruzados.

## 4. F1-03 — autenticação, sessões, MFA e último administrador

1. **Identidade global com autenticação individual** e escolha explícita de tenant a cada contexto, sem união automática de permissões de organizações diferentes. Conta global ativa não substitui membership ativa.
2. **MFA obrigatório** para administrador Imagine e administrador de empresa, inclusive único usuário humano Básico; meios concretos, inscrição, recuperação, autenticação multifator resistente a phishing quando suportada e notificações devem ser definidos em CORE04-D04/D07.
3. **Sessão web candidata:** cookies `Secure`, `HttpOnly`, proteção CSRF, política `SameSite` adequada aos fluxos, rotação em login/elevação, timeout por risco, revogação imediata proporcional, revalidação de membership e tenant em transições. Os prazos concretos e token/device session API ficam CORE04-D05.
4. **Convite** verificado, expira em 7 dias, apenas intenção; não habilita APIs, papel, assento ou dados até confirmação do estado oficial. Política de reserva/concorrência de assentos e transferência do último admin seguem CORE07-D04/05/06.
5. **Recuperação MFA/conta:** não criar segundo administrador operacional nem bypass permanente. Requer processo com competência, evidência, exceção/revisão e alertas; escolha técnica do método e alçadas permanece CORE04/05/07.
6. **Negativas padronizadas e neutras** para enumeração de login e abuso, limites/alertas; retenção de logs e reação a incidentes preservam CORE04-D06/D09 e CORE06.

**NO-GO F1-03:** ainda falta escolher método concreto de MFA, recuperação, duração de sessão, rota de autorização por risco, verificações, rate limit e catálogo final de permissões por campo. Uma sugestão de `TOTP`/WebAuthn não equivale a homologação nem implementação.

## 5. F1-04 — contrato base de autorização e revogação

**Política-base adotada por delegação técnica CORE02-D05:** 
- **P1:** união de concessões positivas válidas dentro do mesmo tenant e subescopo permitido;
- **P2:** negativa explícita válida **somente na interseção** de tenant, recurso, ação, campo e escopo; não é veto universal;
- acima disso, sempre prevalecem **bloqueios mandatórios** de tenant, RLS, entitlement, finalidade, dado protegido, estado, MFA e risco;
- não presumir que `read` implica `export`, `send`, `approve`, `delete`, uso por IA, concessão financeira ou papel de delegante; anexos têm **permissão conjunta ver/baixar**, sujeita à segurança do arquivo;
- mudança de membership, role, quota, mandato ou sessão aciona revalidação para novas operações e entrega de resultado; auditoria guarda motivo minimizado e versão.

| Operação inicial de F1 | Autorização conceitual | Ressalva |
|---|---|---|
| Login e MFA | dono IAM, sessão individual, sem auto-switch para tenant | métodos concretos CORE04 |
| Listar somente memberships da própria identidade | identidade legítima + filtros e proteção de estado | sem listar contas de outras pessoas |
| Escolher tenant de uma membership ativa | verificar titularidade e entitlement; criar contexto de operação só após validação | RLS e pool dependem CORE01/03/SEG |
| Convidar humano | papel competente, quota/plano, proteção de token e aceite | reserva último assento CORE07 pendente |
| Atribuir/revogar papel | capacidade **específica de delegar**, sem autoelevação e fora do Básico quando plano não permite | matriz por módulo CORE02-D05/D06/07 |
| Ler auditoria da própria organização | categoria e competência explicitamente autorizadas | não expor F-SEC/F-EVID irrestritos |
| Intervenção central Imagine | telemetria sem dado comercial ou mandato excepcional por caso | sem bypass RLS |
| Job interno com privilégio | **não incluído para execução sensível no primeiro incremento F1** | precisa SEG-01/02, CORE03, INT |

**CORE02-D06** recebeu somente avaliação preliminar delegada de catálogo por módulo; os contratos por recurso/ação/campo/escopo concretos continuam pendentes.

## 6. F1-05 — segurança dos serviços e segredos (SEG-01 / SEG-02)

| Ator | Alvo permitido como **escopo proposto** | Bloqueios que permanecem |
|---|---|---|
| Pessoa administradora de tenant | somente dados e funções **do próprio tenant** e do plano, com MFA e direito específico | sem acesso a outro tenant, segredos, faturas de terceiros, política global |
| Pessoa colaboradora | recursos/ações/campos próprios/equipe/unidade/caixa conforme perfil e owner | sem export/finanças/papel por cargo, sem autoelevação |
| Administrador Geral Imagine | configuração central e ações gerais legitimamente concedidas | **sem leitura comercial universal**, sem mandato implícito |
| Suporte Imagine | telemetria mínima, ou caso/mandato autorizado e auditado | sem `BYPASSRLS` permanente, acesso por conveniência ou dados fora do caso |
| Worker e serviço interno | somente operação tipada e tenant/destino oficial após autenticação técnica e revalidação | sem super-credencial global, reexecução após revogação, segredos em job |
| Agente IA / n8n | **nenhuma ação privilegiada real em F1**; apenas contratos e cenários futuros | sem ferramenta, webhook, finanças, envio externo ou acesso a dados reais sem SEG-01 |
| Provedor externo | **nenhum conector real em F1** | sem receber segredos/clientes por decisão técnica parcial |

**SEG-01 contrato mínimo antes de integrar serviço:** matriz `principal/tipo × ambiente × tenant/recurso × ferramenta/ação × risco × TTL/limite × autorizador independente × auditabilidade/revogação`, com dono e casos de abuso. IA nunca decide seu próprio escopo.

**SEG-02 contrato mínimo antes de integrar credenciais reais:** inventário classificado, identidades por serviço/ambiente, cofre/provedor aprovado, política de bootstrap, não exportar segredo ao assistente/log, rotação/expiração/recuperação, segregação dev/prod, custos e plano de falha segura. **Produto/cofre e mecanismo físico ainda não selecionados.** Para scaffold F0, usar só `.env.example` fictício e nenhuma chave real.

**Incidentes:** falha de cofre/autorizador afeta operações dependentes com **negação segura**, sem pretensão de travar todos os tenants ou serviços autônomos legítimos; revalidação, monitoramento independente e procedimentos CORE09/ARQ/AGT pendentes.

## 7. Mapa consolidado de dependências e owners por competência

| Pacote F1 | Referências que permanece necessário especificar | Donos/revisores por competência | Resultado mínimo antes de declarar F1 apta |
|---|---|---|---|
| **F1-01 dados/ER** | CORE01-D04/D07, CORE02-D04, CORE06/07, FIN/CON, LGPD | Arquitetura dados/DBA, IAM, Privacidade, owners | ER versionado com campos, PK/FK/index/constraints, RLS por tabela e exceções |
| **F1-02 RLS/contexto** | CORE01-D05/D07, CORE02-D08, CORE03-D04/D06, CORE05, CORE09, SEG-01/02 | Backend, DBA, Segurança | ciclo seguro de transação/contexto, roles, políticas e casos de ataque explicados |
| **F1-03 identidade/sessões** | CORE04-D04..D10, CORE07-D04/D05/D06/D08, CORE08 | IAM/Segurança, Operações | métodos de login, MFA, recovery, quotas/admin, sessão/expiração/revogação definidos |
| **F1-04 autorização** | CORE02-D04..D10, CORE01, CORE07, INT OWN/SEC/CT02 | Segurança/IAM, donos funcionais | matriz real por recurso/ação/campo e escopo, P1/P2 qualificado, negativas |
| **F1-05 serviços/segredos** | SEG-01/02, CORE03/05/09, ARQ-01/02/03, AGT-01 | Segurança, Infra, Integrações, Operações | matriz técnica, cofre/identidade por ambiente, isolamento, custos e falha segura aprovados |
| **F1-GO / testes futuros** | CORE10, INT P0/G01..G06, DEV-GATE-00, LGPD, baseline | Arquitetura, Segurança, responsável pelo projeto | evidências/roteiros futuros, riscos explicitados, SHA/tag e GO específico do escopo |

**Owners são competências propostas, não indicação de funcionários reais ou revisão realmente ocorrida.** O mapa não encerra P0 nem G01–G06.

## 8. Backlog de decisão prioritária — o que ainda precisa ser fechado de verdade

| Ordem | Ato técnico preciso necessário | Estado | Impede |
|---|---|---|---|
| 1 | Matriz de entidades/tabelas/campos reais, chave composta, tratamento de identities/mandates, FK/índices e estados | **PENDENTE DE ESCOLHA/REVISÃO** | migrações F1 de identidade/tenant |
| 2 | Matriz PostgreSQL de roles/ownership/RLS por tabela/ação, provas do contexto transacional e anti-bypass/SQL injection | **PENDENTE DE ESCOLHA/REVISÃO** | autorizar APIs/DB tenant-bound |
| 3 | Catálogo final mínimo de permissões IAM, grants/denies P1+P2 por escopo e contrato de revogação | **PENDENTE DE OWNER/REVISÃO** | autorização de papéis no código |
| 4 | MFA concreto, senha/passkey, sessões, recuperação, step-up e regras do admin único/convites concorrentes | **PENDENTE DE OWNER/REVISÃO** | ativação de autenticação e contas |
| 5 | SEG-01: matriz de ferramentas/ações/tenants/ambientes e autorizador independente, inclusive suporte | **P0 ABERTO** | agentes, serviços privilegiados e suporte |
| 6 | SEG-02: escolha de cofre, service identities, bootstrap, rotação, falha segura e custos | **P0 ABERTO** | credenciais e provedores reais |
| 7 | Topologia e custo mínimo do ambiente F1, logs/backup segregados, critérios de não uso de produção | **P0/ARQ ABERTO** | provisionamento/integração em ambiente real |
| 8 | Casos negativos rastreáveis (cross-tenant, FK, pool, revogação, mandatário, segredo) e plano de teste executável **futuro** | **PLANOS DOCUMENTAIS** | marcar homologação F1 como concluída |
| 9 | Revisão cruzada/aceite técnico com evidência e baseline GitHub (SHA) por escopo | **NÃO REALIZADA** | GO F1 |

O primeiro incremento **F0** já pode continuar apenas no seu escopo previamente autorizado: estrutura sem privilégio e contratos deny-by-default na branch isolada. **Não confundir com autorização F1**.

## 9. Critérios documentais GO / NO-GO por tipo de operação

| Ação | Estado conhecido em 04/11/2026 | Motivo |
|---|---|---|
| **F0 scaffold sem privilégios, sem dados de clientes** | **GO F0 EXISTENTE** conforme DEC-FASTTRACK-01 | Branch segregada e obrigação de negar por padrão |
| **Planejar esquema IAM/RLS, critérios e roteiros** | **GO DOCUMENTAL** | Não produz efeito real |
| **Implementar F1 com permissão de acessar tenant/dados sensíveis em ambiente compartilhado** | **NO-GO** até itens 1–6 e baseline revistos, depois testes sintéticos autorizados | Recurso físico ainda sem contrato seguro |
| **Homologar F1 para merge de integração sensível** | **NO-GO** até critérios futuros executados e evidências | Roteiros não equivalem a PASS |
| **Ligar Portal/Sites, FIN, n8n, WhatsApp, provedores/IA ou dados reais** | **NO-GO** até INT/SEG/ARQ/FIN/CON/G01..G06 aplicáveis fechados | Efeito multissistema, credenciais e dados reais |
| **Deploy público/produção ou cobrança** | **NO-GO** até homologação, requisitos legais/financeiros e autorização de release | Nenhuma aprovação de produção |

**Sem percentual artificial de progresso:** permanecem **67 IDs CORE da Etapa 2 abertos** (CORE01-D04..D07; CORE02..CORE10-D04..D10), IN-168 SEG-01/02, ARQ-01/02/03, AGT-01, INT P0/G01–G06, FIN/CON, LGPD e todos os bloqueios da primeira versão. Os documentos parciais podem instruir implementação F0 e decisões, **não** comprovam fechamento técnico.

## 10. Forma de acelerar sem repetir 67 aprovações

- Uma **matriz única** F1-01..F1-05 alimenta os contratos compartilhados **E2-X01/X02/X03/X04/X05/X09/X10/X11/X12** já catalogados em R01. Uma escolha física validada pode satisfazer várias pendências **com evidências por ID**, não por declaração genérica.
- Para cada escolha, registrar **alternativas, decisão delegada fundamentada ou decisão do responsável, classe de risco, compatibilidade com contrato existente, owner, segurança, artefato versionado e critério de falha**.
- Separar **decisão de projeto**, **implementação em branch**, **teste realmente executado**, **homologação** e **release** como estados distintos.
- As dependências fora do recorte IAM/RLS (FIN, WhatsApp, IA, Sites, Performance AI) não têm implantação antecipada; F0 existente continua possível sem preencher requisitos de produção.

## 11. Próxima decisão de alto impacto

Reconciliar em **um único ato técnico** os itens do backlog 1–4 (ER, RLS e contexto DB, IAM/grants, MFA/sessões), apresentando alternativas físicas concretas e riscos, depois SEG-01/02 e ARQ mínimos. Quando documentação suficiente existir, submeter a **GO F1** por escopo à autoridade competente.

**Não registrar neste momento** uma aprovação final F1, um fechamento P0 ou resultado positivo de testes. **Não alterar a branch F0 nem o código já criado** por outro fluxo apenas para "acompanhar" este documento.
