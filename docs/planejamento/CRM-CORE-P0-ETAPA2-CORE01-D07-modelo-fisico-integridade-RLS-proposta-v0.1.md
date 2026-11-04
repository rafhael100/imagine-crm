# CRM Imagine — Etapa 2 / CORE01-D07: Modelo físico, integridade multiempresa, RLS e contratos técnicos (proposta v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DOCUMENTAL PARA DELIBERAÇÃO — CORE01-D07 ABERTA; D07-A/B/C/D AINDA NÃO APROVADAS.**  
**Objeto:** organizar os artefatos, invariantes e critérios para especificar futuramente um modelo físico executável de Laravel + PostgreSQL RLS, **sem escolher nem aplicar um schema SQL neste momento**.  
**Ordem vigente:** concluir a documentação técnica do CRM Imagine antes da programação. **Não criar código, migrations, scripts, políticas de banco, infraestrutura, dados reais, testes executáveis, contas, credenciais, pagamentos ou deploy.**

## 1. Precedência e limites das aprovações existentes

1. [CORE01-D01–D03 — base lógica](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): uma identidade global, memberships por tenant e **uma organização CRM = um tenant isolado**, com autorização Laravel e PostgreSQL Row Level Security (RLS) complementares; não equivale a schema físico.
2. [CORE01-D04-A/B/C — diretrizes preliminares](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md): no máximo uma membership lógica efetiva por identidade×tenant; convite de 7 dias é intenção distinta de grant; desligamento afeta só a organização, preservando outras memberships e histórico. **D04 permanece aberta** até CORE02/04/07/08.
3. [CORE01-D05-A/B/C/D — diretrizes preliminares](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md): contexto confiável por operação, transação/pool seguros, identidades técnicas sem bypass geral, revalidação e resultado oficial pelo proprietário. **D05 aberta**, com CORE02/03/04/05/09 e SEG-01/SEG-02.
4. [CORE01-D06-A/B/C/D — diretrizes preliminares](CRM-DEC-aprovacao-CORE01-D06-ABCD-diretrizes-preliminares-2026-11-04.md): fontes e ciclos por tenant, assinaturas, cortesia e trial independentes, vínculos Sites↔CRM idempotentes e portabilidade/retencão sem efeito colateral. **D06 aberta** com CORE08, FIN/CON, Portal/Sites, INT e Segurança.
5. [CORE01 D04–D07 — inventário de fronteiras](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md) e [matriz de 67 pendências da Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md): o planejamento técnico precisa de fonte verificável, proprietário, critério e aceite próprio antes de considerar qualquer CORE fechado.
6. [CORE02](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md), [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md), [CORE05](CRM-CORE-P0-05-acesso-excepcional-imagine-mandatos-suporte-proposta-v0.1.md), [CORE06](CRM-CORE-P0-06-auditoria-categorias-retencao-acesso-proposta-v0.1.md), [CORE07](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md), [CORE08](CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md) e [CORE09](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md): não recriar IAM, catálogo de autorização, motor de eventos, gestão de segredos nem Auditoria Geral; os contratos de cada proprietário continuam a prevalecer.
7. [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md), [INT P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md), [CORE10](CRM-CORE-P0-10-pacote-homologacao-documental-nucleo-proposta-v0.1.md): **SEG-01/02, ARQ-01/02/03, AGT-01, INT P0 e G01–G06 abertos**, com roteiros de testes apenas documentais. Nenhuma evidência executável autoriza fechamento.
8. [IN127-R01..R06](IN-127-R01-R06-ratificacoes-2026-11-03.md): formulário de Site só capta quando há destino CRM oficialmente vinculado e autorizado, mesmo após cache/rollback. Leads previamente admitidos conservam autorização e destino originalmente provados; T0/72h+360h/432h e retenção de metadados são específicos da integração de leads, não padrão de todas as filas.

**Regra de precedência:** este texto é **minuta de arquitetura**, não uma decisão para mudar bases aprovadas, valores, prazos, licenças, responsabilidade do cliente, regimes de dados ou entitlement.

## 2. CORE01-D07-A — Inventário de entidades e limites global/tenant (PROPOSTA)

Adotar como documento obrigatório, antes da programação futura, uma **matriz canônica de entidades, proprietário, classe de dados e regime de segurança**. Sugestões de classes:

| Família candidata | Limite conceitual | Proprietário e restrição |
|---|---|---|
| Identidade global e autenticação/MFA | **Global protegida por IAM**, fora dos dados comerciais tenant-bound | CORE01/04: identidade não contém autorização de acesso a todos os tenants |
| Representação jurídica, CNPJ, autorizações e organizações | **Catálogo central de titularidade com escopo expresso**, não um tenant comercial compartilhado | CORE01/08/FIN/CON: mesmo CNPJ pode ter tenants diferentes; acesso não nasce de CNPJ/e-mail |
| Membership, papeis, equipes, convites e assentos | **Relações por tenant**, com referência controlada à identidade global | CORE01/02/07: uma membership efetiva identidade×tenant; convite não é grant |
| Leads, contatos, oportunidades, atendimentos, agenda, tarefas, automações e dados operacionais | **Tenant-bound**, com dono de módulo e ação | DAT/VEN/ATD/AGD/AUT; RLS + autorização Laravel + controle de campo |
| Anexos, objetos, mídias e índices de busca | **Referências/ACL por tenant** com objetos armazenados sob fronteira protegida | Arquivos/Busca: RLS de metadados **não basta** para URLs pré-assinadas, cache, download e armazenamento externo |
| Site, formulário, vínculo de destino e submissões | **Dados de contrato e integração com fonte oficial Portal/Sites; referências vinculadas ao tenant autorizado** | IN-117..122, IN127; site público não contém token privilegiado; sem redirecionamento implícito |
| Assinatura, plano, trial, cortesia, gratuidade e limites | **Entitlements por organização**; CNPJ e contrato como referências de elegibilidade | CRM/FIN/CON/CORE08: não somar direitos por Sites nem confundir fatura com confirmação |
| Auditoria oficial, segredos e mandatos excepcionais | **Categorias/serviços especializados, segregados do acesso comercial** | CORE05/06/09: não criar tabela global legível por todos; logs não conterão segredos |
| Catálogo de planos e configurações transversais | **Global por exceção justificada**, administrada pela Imagine | Catálogo global não atribui acesso amplo aos registros dos tenants |

**Para toda entidade candidata, exigir**: owner e finalidade; classe global/tenant/integração; dados pessoais/sensíveis; operações de leitura/escrita; identificação de tenant e exceção; contratos/índices/FKs; RLS e autorizações Laravel; campos versionados; retenção e backup; efeito de revogação; auditoria; custos/volume; critérios negativos; rastreabilidade de fonte.

**Não escolhido na D07-A:** nomes físicos de tabelas, estrutura única ou schema por domínio, chaves UUID/ULID/inteiras, campos definitivos, tipagem SQL, enumerações, política de partição/índice, provedores ou residência dos dados.

## 3. CORE01-D07-B — Unicidade e integridade relacional entre tenants (PROPOSTA)

Preservar integridade coerente com **uma membership lógica por identidade×tenant** e com prevenção de relações indevidas entre organizações:

1. **Relacionamentos tenant-bound:** proposta de exigir que toda referência entre registros comerciais carregue **prova contextual de tenant consistente**, inclusive nos vínculos de leads, equipe, pipeline, arquivo e automação. Avaliar **chaves compostas com tenant + ID** e constraints/FKs que impeçam associar registro A com registro B; a técnica física ainda depende de revisão.
2. **Unicidade contextual:** memberships, convites aceitos, vínculos Sites↔CRM, consumo de trial por CNPJ, concessões por contrato e idempotência de eventos requerem **invariantes próprias** de unicidade e estado; não aplicar uma única constraint genérica a todos, pois algumas têm owner externo ou exceções temporais.
3. **Referências globais:** apontar para identidade global ou catálogo central não transforma a linha de negócio em global. FKs cruzando domínio de identidade devem respeitar controle de exposição e exclusão.
4. **Alteração e readmissão:** manter histórico confiável de membership e eventos sem ressuscitar concessões ou duplicar assentos quando a pessoa retorna; reservas em convites e admin único **continuam CORE07-D05/D06**, sem decisão de política nesta D07.
5. **Dados temporários e importação/exportação:** staging, CSV, anexos, jobs, backups, réplicas, relatórios, buscas e derivados de IA devem carregar escopo/ownership e políticas de TTL/retentão aprovadas; não permitir cruzar tenants por ID isolado, URL pública, cache ou arquivo de importação.
6. **Vínculos comerciais:** CNPJ semelhante não cria tenant, trial ou cortesia automaticamente; a mesma organização pode ter mais de um Site elegível, mas o benefício efetivo não acumula. O contrato/titularidade e o owner financeiro permanecem responsáveis pelo resultado.
7. **Remoção e retenção:** distinguir soft delete, desativação, retenção, expurgo legal e exclusão; cascata de FK não deve apagar históricos/fatos oficiais que precisem ser preservados. A política por família segue CORE06/LGPD/FIN/CON/IN-118.
8. **Concorrência e repetição:** integração e job duplicados ou fora de ordem devem respeitar integridade/idempotência contextual; o controle físico específico (índice, lock, transação, outbox, reconciliação) será definido com CORE03/INT.

**Alternativas a cotejar, não aprovadas:** identificadores opacos globalmente únicos vs compostos por tenant; constraints compostas para relações entre registros tenant-bound; tabela de histórico separada vs eventos versionados; índice de unicidade para vínculo ativo vs unicidade da relação lógica com transições. Exigir plano documentado de migração/backfill para dados existentes **somente se** o projeto atingir essa fase; nenhuma migração agora.

## 4. CORE01-D07-C — Matriz real de autorização e RLS por família/role/ação (PROPOSTA)

A especificação futura deve explicitar o casamento entre **Laravel (autorização de negócio)** e **PostgreSQL RLS (contenção de linhas)**, sem tratar RLS como substituta de segurança de aplicação.

| Dimensão do contrato | Pergunta a ser resolvida com owner técnico | Falha a impedir |
|---|---|---|
| **Entidade/operação** | Qual família é tenant-bound, qual é global por exceção, quais operações e critérios `SELECT/INSERT/UPDATE/DELETE` aplicam? | Lógica global expor dados comerciais |
| **Política de linha** | Quais condições de leitura e de escrita (`USING` e `WITH CHECK`) por classe/role, inclusive ações em lote? | Ler B ou transferir linha de A para B |
| **Identidade de conexão** | Quais roles do PostgreSQL são usados por aplicação, jobs, migração, backup, suporte, monitoramento e emergência? | Aplicação cotidiana usar owner/superuser/`BYPASSRLS` indevido |
| **Proteção do owner** | Onde `FORCE ROW LEVEL SECURITY` pode ser necessário e qual risco residual de dono/superusuário/bypass permanece? | Presumir que FORCE RLS protege superuser ou role BYPASSRLS |
| **Combinação de políticas** | Como combinar políticas permissivas/restritivas e revisar regras por operação e tenant? | Combinação permissiva `OR` ampliar leitura além do esperado |
| **Contexto de execução** | Como tenant/ator legitimado chega à operação SQL com integridade/escopo, transação e reset em pools e retries? | Confiar em header arbitrário, `SET` inseguro ou tenant residual A→B |
| **Serviços e agentes** | Como vínculo do serviço, delegação/mandato, entitlement, ferramenta, ação, recurso e risco são checados por autorizador independente? | n8n/IA ou API técnica virar superadmin |
| **Evidências externas** | Como arquivo, URL de download, índice de busca, exportação, cache, API e jobs aplicam autorização além da linha no banco? | RLS não proteger objeto fora de SQL |
| **Objetos privilegiados** | Como views, funções, relatórios, BI, manutenção, procedimentos e backups limitam desvio e canais indiretos? | `SECURITY DEFINER`, views ou logs contornarem políticas |
| **Falha e recuperação** | Em que classes a falha do autorizador/cofre/contexto bloqueia novo ato e quais rotinas internas têm delegação própria? | Bloqueio indiscriminado ou fail-open por indisponibilidade |

**Invariantes:** sem tenant/principal autorizados não deve haver leitura/escrita tenant-bound; não conceder `BYPASSRLS` a roles operacionais; revalidação antes de efeitos protegidos; mudança tenant e erro de transação não deixam contexto residual; leituras por campos ou exportações dependem de política CORE02, e RLS sozinha não garante isolamento de campos ou de objetos externos.

**Sem escolha física em D07-C:** nomes reais de roles, SQL de RLS, flags de tabela, `SET LOCAL`, `set_config`, esquema de conexão, pooler, login/método MFA, criptografia, escolha de produto de cofre e canal de transporte. **CORE01-D05, CORE03-D06, CORE02-D08, CORE09-D04..D10 e SEG-01/SEG-02 permanecem travas específicas.**

## 5. CORE01-D07-D — Contratos versionados, migração/recuperação futura e critérios de aceitação (PROPOSTA)

Organizar um **pacote de documentos técnicos a fechar**, com uma versão autoritativa por fronteira, para não gerar múltiplos esquemas ou motores:

| Artefato futuro | Conteúdo mínimo a exigir | Dependência |
|---|---|---|
| **Mapa ER e dicionário de dados** | Famílias globais/tenant, dono, relações, chaves, campos, FKs e exceções; classes pessoais e retenção | CORE01-D04/D06; CORE06, LGPD, FIN/CON |
| **Matriz papel/tenant/objeto/ação/campo** | Direitos Laravel, escopos, revogações, MFA, entitlement e negativa por padrão | CORE02-D04..D10, CORE04/07, SEG-01 |
| **Matriz RLS por tabela e operação** | Role, regra de acesso e escrita, `USING/WITH CHECK`, owner, exceções justificadas e avaliação de bypass | CORE01-D05, CORE03-D06, SEG-01/02 |
| **Conexões e worker** | Unit of work, início/fim do contexto, pool, nested tx, failover, retry, idempotência e contingência | CORE03-D04..D10, CORE09, ARQ-02/03 |
| **Operações externas e contratos comerciais** | Tipos de comando/evento, causalidade, titularidade, destino, owner do resultado, idempotência, estados e reconciliação | CORE08, FIN/CON, Portal/Sites, INT CT03/04 |
| **Auditoria/retentão e recuperação** | Classes de registros, LGPD, backup/restore, não ressuscitar revogação/expurgo, custeio e obrigações legais | CORE06, CORE09, IN-168 ARQ/SEG |
| **Plano de migração compatível** | Estratégia de versionamento, dados legados, compatibilidade de versões, rollback e recuperação a aprovar antes de desenvolvimento | Arquitetura, donos de dados, Segurança |
| **Pacote de verificação documental/futura** | IDs canônicos de cenários positivos/negativos, pré-condições sintéticas, resultados esperados, owner, evidência e critério NO-GO | CORE10-D04..D10, INT P0-ACC e G01..G06 |

**Regras de revisão:** toda diferença entre fonte canônica e minuta deve registrar conflito, dono, alternativa e decisão expressa; um checklist aprovado não significa que SQL está implementado; uma “migração desenhada” não é migração executada; nenhum caso de homologação é marcado como passado sem ambiente/versão/resultado reais posteriormente autorizados. Não há autorização para criar tag final de programação nesta etapa.

### 5.1. Casos adversos de homologação FUTURA — apenas documentados

| Ref | Cenário a mapear aos testes canônicos REV01/CORE01/INT | Condição esperada para aceite posterior |
|---|---|---|
| CORE01-D07-T01 | Usuário tenta acessar linha tenant B por ID, enquanto autorizado somente em A | Negação em Laravel e limite da RLS, sem vazamento de metadados |
| CORE01-D07-T02 | INSERT/UPDATE tenta associar `tenant_id=A` com FK de objeto B | Contrato de integridade nega vínculo cruzado |
| CORE01-D07-T03 | Worker de B reutiliza conexão anteriormente usada em A | Conexão não conserva contexto de A |
| CORE01-D07-T04 | Role cotidiano é owner ou recebeu privilégio `BYPASSRLS` | Configuração impede caminho de bypass e não é homologada |
| CORE01-D07-T05 | Políticas permissivas combinadas expõem dados extra | Critérios de política não ampliam acesso indevido |
| CORE01-D07-T06 | Admin Imagine sem mandato acessa dados de cliente por API/BI | Negação proporcional, auditoria e ausência de superadmin comercial |
| CORE01-D07-T07 | Serviço com token válido de A tenta operar tenant B | Direito por serviço/tenant/ação revalidado e negação |
| CORE01-D07-T08 | Convites simultâneos disputam último assento Básico | Não criar vínculo efetivo ou quota extra indevidos |
| CORE01-D07-T09 | Mesma identidade possui A/B; desativação de A | Novos efeitos de A negados; B e histórico oficial preservados |
| CORE01-D07-T10 | Arquivo/exportação de tenant A é exposto via URL/cache externo | Acesso ao objeto revalidado; RLS não considerada suficiente |
| CORE01-D07-T11 | Falha e retry de transação A seguido por B | Nenhum contexto residual ou duplicidade de efeito |
| CORE01-D07-T12 | Restore reintroduz papel revogado ou dado já expurgado legitimamente | Política de reconciliação segura impede ressuscitar privilégio/PII |
| CORE01-D07-T13 | Segundo site elegível de mesmo CNPJ pede tenant B e trial novo | Tenant B pode ser legítimo, mas trial continua único por CNPJ |
| CORE01-D07-T14 | Evento de pagamento HTTP 2xx tenta conceder direito CRM | Não conceder sem confirmação oficial FIN/CON |
| CORE01-D07-T15 | Mudança de Site↔CRM tenta redirecionar lead previamente admitido | Mantém destino oficial original; sem reenvio a conta diferente |
| CORE01-D07-T16 | Site antigo/cached page envia formulário sem destino CRM autorizado | Backend nega captação, sem armazenamento alternativo implícito |
| CORE01-D07-T17 | Tabela global de catálogo é consultada para resolver tenant comercial | Não inferir acesso aos clientes pelo catálogo global |
| CORE01-D07-T18 | Operação de manutenção legítima usa conta de backup fora da finalidade | Requer contrato/autorização específica, segregação e trilha de auditoria |

**Esses identificadores são referências de risco, não uma suíte executada:** reconciliar antes de homologação com CORE01-T, CORE03-T, CORE07/08/09-T, REV01 S, OPS-T, AUT-X e INT HT01..18 para eliminar duplicações. Estado **NÃO EXECUTADO** para todos.

## 6. Decisões CORE01-D07-A/B/C/D para manifestação expressa — AINDA NÃO APROVADAS

- **CORE01-D07-A — PROPOSTA:** aceitar a **classificação documental por famílias global/tenant-bound/integração** e a obrigação de dicionário de dados por proprietário, ação, finalidade, retenção, integridade e políticas; sem fixar tabelas e chaves reais.
- **CORE01-D07-B — PROPOSTA:** aceitar a **diretriz de integridade contextual/relacional multiempresa**, unicidade de vínculos, proteção de FKs/constraints/índices e não ressuscitar grants, com alternativas físicas a cotejar antes da escolha; sem criar schema agora.
- **CORE01-D07-C — PROPOSTA:** aceitar a necessidade de **matriz futura Laravel + PostgreSQL RLS por classe/role/ação, isolamento de transações, prevenção de bypass e cobertura de workers/serviços/objetos externos**; sem escolher SQL, roles nem pooler concretos.
- **CORE01-D07-D — PROPOSTA:** aceitar o **pacote documental futuro de contratos tipados, versionamento/migração/recuperação, auditoria, critérios de verificação e evidências por owner**, separado de qualquer teste/implantação real, reconciliado com CORE10, INT e G01..G06.

**Se eventualmente aprovadas, A/B/C/D seriam somente diretrizes técnicas documentais preliminares.** **CORE01-D07 permaneceria ABERTA** até fechar especificações físicas, schemas/keys/constraints, owners e contratos com CORE02/03/04/05/06/07/08/09, SEG-01/02, FIN/CON, INT, Portal/Sites, ARQ/AGT e aceite final. Aprovar D07-A/B/C/D não fecha D04, D05, D06, CORE-P0-01 ou a Etapa 2.

## 7. Bloqueios preservados e próximo fechamento documental

| Categoria | Situação até decisão específica posterior |
|---|---|
| **CORE01-D04/D05/D06** | Diretrizes preliminares A/B/C ou A/B/C/D aprovadas, **os três itens continuam ABERTOS** e dependentes de contratos dos módulos citados |
| **CORE01-D07** | **ABERTA**; as quatro diretrizes deste documento aguardam aceite |
| **CORE02–CORE10-D04..D10** | **ABERTAS**; matrizes, autorizações, contratos e owners dos módulos não homologados |
| **IN-168** | **SEG-01/02, ARQ-01/02/03, AGT-01 ABERTOS** |
| **INT/FIN/CON/Sites** | P0 OWN/SEC/CON/ACC e demais pacotes, G01..G06, licenciamento, custos, contratos e LGPD ABERTOS |
| **Inventário CORE Etapa 2** | **67 IDs técnicos ainda ABERTOS**, sem considerar as quatro subdiretrizes como baixas de pendência |

**Continuidade recomendada após eventual aceite:** reconciliar especialmente CORE01-D04..D07 com CORE02/03/07/08/09/SEG, começando por contrato de permissões/escopos e integridade de tenant antes de marcar qualquer item como fechado; escolher soluções físicas só com documentação e aprovação específica do responsável.

**Vedado:** programação/Codex, SQL/migration, testes reais, deploy, troca de permissões, uso de dados reais, implantação, provisionamento, contratação ou despesas. **Estado global: NÃO LIBERADO PARA PROGRAMAR.**
