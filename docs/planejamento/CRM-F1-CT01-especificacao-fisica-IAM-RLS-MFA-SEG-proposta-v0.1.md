# CRM Imagine — F1-CT01: Especificação física candidata IAM, PostgreSQL RLS, MFA e Segurança (v0.1)

**Data:** 04/11/2026.  
**Situação:** **MINUTA DE DECISÕES FÍSICAS PARA REVISÃO — SEM HOMOLOGAÇÃO, SEM GO F1**.  
**Autorização documental:** [DEC-F1-P0-01 — aceite da base do pacote F1](CRM-DEC-aceite-base-documental-F1-IAM-RLS-SEG-2026-11-04.md). A manifestação “aprovado” validou o **pacote F1 como base documental**; não selecionou cada alternativa física por força do silêncio. O responsável já autorizou F0 isolada via [DEC-FASTTRACK-01](CRM-DEC-FASTTRACK-autorizacao-desenvolvimento-controlado-2026-11-04.md).  
**Fonte de requisitos:** [F1-P0 — pacote de identidade/RLS/segurança](CRM-F1-IAM-RLS-SEG-PACOTE-TECNICO-GO-CHECKLIST-v0.1.md); [Etapa 2 R01](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md); CORE01/02/03/04/05/06/07/09, IN-168 SEG-01/02, INT-P0, LGPD.  
**Escopo:** escolher alternativas preferenciais e contratos verificáveis para especificação do **núcleo F1**, não publicar implementações, contas, credenciais, preços, serviços, APIs para clientes ou instalação. As etiquetas **RECOMENDAÇÃO** e **PENDENTE** diferenciam escolha candidata de regra já aprovada.  
**Proibições mantidas:** nenhum código, SQL executado, migration, teste, chave, SaaS externo, despesa, deploy, tratamento de dados reais ou uso de produção por este documento.

## 1. Critérios de arquitetura sem concessões implícitas

- **Uma identidade global protegida** pode possuir membership independente em diversos tenants. **Um tenant = uma organização CRM isolada**; mesmo CNPJ/e-mail não une dados. **Convite de 7 dias não é grant**.
- Backend Laravel valida autenticação, membership, entitlement, papel, ação/campo, escopo e estado. PostgreSQL RLS é **defesa em profundidade para linhas**, não substitui autorização funcional de negócios, consentimento, proteção de anexo, consultas fora de banco ou serviços externos.
- Deny by default; composição de perfis **P1 + P2 escopada**, conforme [decisão delegada preexistente](CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md). Um deny válido não veta universalmente outra empresa, objeto ou operação.
- Não conceder acesso comercial global ao Administrador Imagine ou suporte. Serviço/worker/IA operam sob identidade técnica e contrato próprio; **sem BYPASSRLS operacional genérico**.
- O catálogo F1 físico é **subconjunto** de IAM e segurança, não define por antecipação permissões de FIN, VEN, DAT, ATD, AGD, AUT ou IA.

## 2. CT-F1-01 — Modelo de entidades e chaves: alternativa preferencial

### 2.1 Decisões candidatas de estrutura

| Entidade lógica candidata | Escopo e campos/relacionamentos mínimos de revisão | Restrição/invariante a materializar | Owner / dependência |
|---|---|---|---|
| `identities` | Identificador global opaco, status, versão, contato de login verificado, metadados mínimos | Unicidade de identificador de acesso **verificado** segundo política IAM; não criar tenant por login | CORE01/04, LGPD |
| `tenants` | ID do tenant, estado, versão, referência a titularidade/contrato **separada** | CNPJ não é chave única de tenant; um titular pode ter vários tenants independentes | CORE01/08, FIN/CON |
| `memberships` | Tenant, identity global, status, versão, timestamps de revogação | **No máximo uma membership lógica por (tenant, identity)**; reentrada/restauração com histórico próprio; não unir memberships de tenants diferentes | CORE01/07 |
| `membership_history` | Tenant, membership, evento, origem e categoria de prova | Histórico não é grant ativo; retenções e autoria por owner | CORE06/07 |
| `invitations` | Tenant, destinatário, hash de token de aceite, expiração e estado, autor causal | Expiração 7 dias; consumo único/idempotente; não ativar membership por envio; capacidade conforme CORE07-D05 | CORE04/07 |
| `roles`, `role_assignments` | Tenant, role, membership, vigência, escopo e versão | Atribuições apenas da mesma organização; perfil não confere entitlement ou poder de autoelevação | CORE02/07 |
| `permission_rules` | Tenant, recurso, ação, classe de campo, escopo, effect allow/deny, origem, versão, validade | P1 positivo com limites superiores, P2 deny apenas escopada; integridade entre regra e papel/objeto | CORE02-D05/D06/D08 |
| `teams`, `team_memberships` | Tenant, equipe, membership, vigência, unidade/canal só quando módulo justificar | Evitar relações A→B, gestores e substitutos não ganham alcance universal | CORE07/ATD/VEN/AGD |
| `auth_sessions`, `mfa_factors`, `auth_recovery` | Identity global, tenant de sessão selecionado, segurança, histórico mínimo, revogação | MFA e recuperação separados do tenant comercial, sem segredo em claro; sessão não garante membership eterna | CORE04/09 |
| `service_principals`, `support_mandates` | Identidade técnica ou mandato de suporte, operação, escopo, tenant, finalidade e validade | Nenhum principal acessa tenant sem contrato; suporte só por mandato | CORE05/09, SEG-01/02 |
| `audit_events` | Tipo, principal causal/técnico, tenant quando aplicável, recurso, categoria e versão | Registro protegido por finalidade e retenção; não ser editável por admin de tenant | CORE06, LGPD |

**Alternativa preferencial a avaliar:** identificadores **UUIDv7** gerados/validados em camada de aplicação confiável e armazenados como `uuid`, mantendo `tenant_id` explícito em entidades tenant-bound. UUIDv7 facilita ordenação/indexação sem embutir dados comerciais. **Ainda é proposta, não decisão fechada de chave física**; cabe rever volume, collation/índices, bibliotecas suportadas, exposição de ID e compatibilidade com Laravel/PostgreSQL disponível.

**Integridade de relacionamento candidata:** relações entre duas entidades tenant-bound devem usar **(tenant_id, object_id)** e **FK/índices ou constraints equivalentes que assegurem tenant consistente**. O simples uso de UUID único **não prova** integridade entre tenants. Para referências a identidade global, manter FK para `identities` e prova de vínculo `memberships`; não exigir `tenant_id` artificial na identidade.

**Estados e exclusões ainda pendentes:** escopos e restrições para status pendente/ativo/suspenso/encerrado, histórico de readmissão, impossibilidade de reviver permissões após restore, retentões LGPD e políticas de remoção; não converter `soft delete` em concessão automática.

### 2.2 Condições para fechar CT-F1-01

Mapa ER completo por tabela, dicionário de campos e classes de proteção, PK/FK e unicidade contextual, concorrência de convite/assento, estados e referência a contrato, owners revisoras, custos/índices/migração e casos de erro documentados; revisão CORE01-D04/D07, CORE02-D04, CORE07-D04..D10, CORE06 e LGPD. **Não executar migration nesta fase documental.**

## 3. CT-F1-02 — PostgreSQL RLS: roles, conexão e contexto seguro

### 3.1 Papéis de banco: recomendação estrutural (nomes ilustrativos)

| Classe de role | Competência candidata | Restrição indispensável |
|---|---|---|
| **Dono de schema/migrations** | Criar/alterar tabelas apenas em mecanismo controlado de alteração | Conta separada da aplicação, não usada em requests/jobs rotineiros |
| **Aplicação IAM** | Acesso mínimo a dados globais de identidade e memberships segundo função | Não receber acesso aos dados comerciais de outro tenant nem `BYPASSRLS` |
| **Aplicação tenant-bound** | Operações autorizadas em tabelas do tenant de contexto | Não ser dona das tabelas, não ter `SUPERUSER`/`BYPASSRLS`, matriz de `USING`/`WITH CHECK` |
| **Worker/serviço** | Executar operações tipadas, autenticadas, com tenant e autor causal verificados | Sem conta universal e sem executar autorização baseada só em payload do job |
| **Auditoria/segurança** | Consultas específicas com minimização, categoria e mandato próprio | Não é leitura global genérica; ver/baixar logs depende de finalidade/competência |

**Matriz mínima de política por classe tenant-bound a documentar:**

| Ação SQL | Regra que deve existir na especificação final | Caso negativo |
|---|---|---|
| `SELECT` | Política de leitura com tenant e actor/serviço legitimados, objeto e estado permitidos | Tenant A não vê linha B |
| `INSERT` | `WITH CHECK` para tenant e vínculo de destino autorizado | A não cria registro apontando a B |
| `UPDATE` | Filtrar linhas atuais por `USING` e validar resultado por `WITH CHECK`; tenant imutável sem migração autorizada | Alterar tenant_id para mover linha A→B é recusado |
| `DELETE` | `USING` e alçada de exclusão/retentão do proprietário | A não remove registro B; admin sem direito não apaga histórico |

**Contratos de risco de SQL privilegiado:** views, funções `SECURITY DEFINER`, triggers, backups, jobs de migração e extensões necessitam inventário próprio, owner separado e prova de que não abrem bypass. `FORCE ROW LEVEL SECURITY` deve ser avaliado por tabela e papel dono, não adotado como garantia isolada sem entender seu comportamento.

### 3.2 Contexto transacional — duas alternativas a cotejar

| Estratégia | Vantagem | Risco principal | Situação |
|---|---|---|---|
| **A — marcador de tenant por transação, validado por Laravel** (p.ex. contexto equivalente a `SET LOCAL`) | Modelo simples, compatível com pool e filas quando transações corretas | **Variável de sessão é alterável** por conexão com execução SQL; um atacante explorando SQL injection pode falsificar tenant se o banco confia nela isoladamente | **CANDIDATA, INSUFICIENTE SOZINHA** |
| **B — marcador transacional + atestado verificável de principal, tenant, operação, versão e validade com verificação do lado confiável do DB** | Pode reduzir abuso de valores adulterados e delimitar a autorização por operação | Gestão de segredo/assinatura, funções privilegiadas, replay, latência e dependência de cofre; desenho incorreto também é vulnerável | **PREFERÊNCIA PARA AVALIAÇÃO DE SEGURANÇA, NÃO HOMOLOGADA** |

**Requisito obrigatório qualquer que seja a escolha:** Laravel deve validar membership e autorização de negócio; conexão DB não pode portar direitos de outra operação; contexto recebido por cliente/URL/job não é confiável sem verificação de origem. Segurança/DBA devem demonstrar como se evita alteração maliciosa do tenant por SQL arbitrário, credencial de serviço, pool, transação aninhada, retry e `SECURITY DEFINER`; **não assumir que `SET LOCAL` torna a origem do contexto confiável por si só**.

**Fluxo candidato por operação:** request autenticada → tenant escolhido e membership validada → entitlement e ação/campo aprovados → contexto de operação vinculado a transação única (sem credencial longa em payload) → execução de comandos autorizados em role mínima → commit/rollback com limpeza efetiva do contexto → auditoria por owner. Revalidação em retries e novos efeitos. **Sem SQL de configuração executado**.

### 3.3 Casos negativos obrigatórios para futura homologação

1. SQL injection tenta modificar marcador `tenant_id` em transação existente.
2. Pool retorna conexão que acabou de processar tenant A para worker de B.
3. Transaction rollback/retry preserva marcador/identidade antiga.
4. Role do app cria/view/function que lê tabela ignorando RLS.
5. Job assíncrono reexecuta com autor causal revogado ou destino substituído.
6. Usuário com sessão ativa troca tenant sem membership correspondente.
7. FK de A aponta para linha de B.
8. Backup/restore reativa grant revogado.
9. Metadados protegidos por RLS geram URL pública de anexo.
10. Agente/serviço tenta ler tabela por conta superior ou spoof de origem.

**Nenhum desses casos está marcado PASS**; criar dados sintéticos e rodar testes somente sob autorização e ambiente apropriados.

## 4. CT-F1-03 — Autorização Laravel, papéis e revogação

**Base documental selecionada:** composição positiva **P1** de roles válidos limitada por políticas mandatórias; negativa qualificada **P2** que prevalece somente na interseção validada de tenant/recurso/ação/campo/escopo. Falta esquema concreto de grants/denies e exceções por módulo.

| Operação IAM mínima futura | Atores elegíveis (candidatos, sujeitos a matriz) | Travas obrigatórias |
|---|---|---|
| `list_my_tenants` | identidade autenticada | Somente memberships próprias e status permitido, sem listar terceiros |
| `select_tenant` | identidade com membership válida | Verificar status e direito atual; não recuperar tenant por CNPJ/e-mail |
| `invite_member` | admin do tenant competente e plano elegível | MFA, assentos/quota, expiração 7 dias, sem grant por convite |
| `assign_role` | quem possui **poder específico de delegar** | Não criar escopo/role superior ao que pode atribuir; não autoelevar |
| `revoke_membership` | quem tem direito por tenant | Revogar sessões/tokens/novos jobs conforme CORE03/07; preservar membership em outros tenants |
| `transfer_last_admin` | fluxo especial autenticado | Último administrador/Básico com 1 humano, MFA/recovery e sem bypass |
| `view_security_audit` | pessoal competente por finalidade/categoria | Não revelar segredos, tokens, PII de outro tenant ou histórico sem direito |
| `support_access` | suporte com mandato válido por caso/tenant | Sem superuser, sem BYPASSRLS e com revogação/notificação |

**Representação candidata de regra:** `principal + tenant + permission_code + resource_type + field_class + scope + effect + source + valid_from/to + version`. Isso é **modelo lógico de revisão, não coluna/tabela SQL aprovada**. Matriz real por operação/campo e atribuição de owners precisam fechamento CORE02-D04..D10 e dos módulos proprietários.

**Revogação:** alteração de membership, papel, entitlement, sessão e mandato exige invalidar decisões em cache e revalidar novas operações e fornecimento de arquivos/export; **não** desfazer efeitos oficiais já concluídos legitimamente. TTL, outbox e forma física de invalidação dependem CORE03/04/07/SEG.

## 5. CT-F1-04 — MFA, autenticação, sessões e recuperação

| Item | Direção técnica candidata | Decisão física ou risco aberto |
|---|---|---|
| **Credencial primária** | e-mail/login verificado e senha protegida por KDF robusta, com antifraude/rate limiting; **passkeys/WebAuthn** como opção forte onde suportadas | cadastro, compatibilidade e recuperação; CORE04-D04/06 |
| **MFA de administradores** | **TOTP por aplicativo autenticador**, com suporte futuro a **WebAuthn/passkeys resistentes a phishing**; MFA obrigatório também no Básico 1/1 | política de inscrição, recuperação e step-up; CORE04-D04/D07/D08 |
| **Segredos de recuperação** | códigos de recuperação únicos **armazenados com hash**, usados uma vez, regeneração com reautenticação; sem SMS como bypass genérico | prova de titularidade, comunicação, incidentes, proteção do último admin |
| **Sessão web** | cookies `HttpOnly`, `Secure`, `SameSite` apropriado, CSRF, rotação de session ID, revogação, sessão associada a identidade e contexto de tenant | prazos de expiração por papel, concorrência e remembered devices; CORE04-D05 |
| **Step-up** | reautenticação recente para alterações de credenciais/roles, transferir admin e ações críticas designadas pelo dono | janela exata e quais ações por risco; CORE02-D07/CORE04-D08 |
| **Convites** | token aleatório opaco com apenas hash persistido, expira **7 dias**, consumo idempotente e domínio/retorno seguro | assento reservado, readmissão, fluxo de e-mail alterado, convite revogado; CORE07-D04/D05 |
| **Sessão e membership** | autenticar não concede acesso permanente: conferir membership/tenant ao início de operação e em efeito sensível | política de invalidação entre request/worker, cache, versões e gateway |

**Prazos de sessão candidatos para estudo, NÃO HOMOLOGADOS:** inatividade de 15–30 min para sessões privilegiadas, expiração absoluta 12–24 h, step-up de 5–15 min, com políticas específicas por risco e necessidades de UX. A aceitação desta minuta **não aprova** esses números. Exigir validação de segurança, UX, acessibilidade, suporte e risco de dispositivos.

## 6. CT-F1-05 — SEG-01, SEG-02, infraestrutura mínima e contingência

### 6.1 SEG-01 — autorizador independente de agentes e serviços

**Matriz F1 mínima proposta:**

| Principal/serviço | Ferramentas e alvos F1 | Postura inicial | Condição para qualquer exceção |
|---|---|---|---|
| App IAM | Entidades IAM sob contexto seguro | **Deny** até contrato físico revisto | Origem autenticada, recurso/ação/tenant, MFA/entitlement e auditoria |
| Worker de jobs IAM | Jobs tipados de convite/revogação e sem entrega externa real em F1 inicial | **Deny** para dados comerciais; permitir só eventos internos após contrato | Service principal, causal actor, retry seguro e verificação de autoridade |
| Admin Imagine | Painel central administrativo próprio | **Deny** leitura comercial por padrão | Mandato específico validado e auditado |
| Suporte | Telemetria mínima de caso | **Deny** acesso a cadastros e dados sem mandato | Autorização por caso/recurso/tenant/prazo e alçada CORE05 |
| Agentes IA e n8n | **Nenhum uso privilegiado** na primeira implementação F1 | **Deny integral** sobre execução externa/financeiro/segredo | SEG-01/02 e AGT-01 homologados antes da ativação |
| Integrações externas / Sites | **Nenhuma operação externa real em F1** | **Deny** | INT/ARQ/SEG e vínculo oficiais; API idempotente com prova de fonte |

Autorizador de ação não é o modelo de IA; resultado não pode ser "autorizado" por prompt, sucesso do n8n, HTTP 200 ou webhook isolado. Dono do negócio confirma o efeito. **Matriz detalhada por serviço e ferramenta, TTL/risco, owners, casos de falha e auditoria continua SEG-01 P0 aberto**.

### 6.2 SEG-02 — alternativas de cofre

| Estratégia | Quando cabível | Riscos e custo |
|---|---|---|
| **Gerenciado com identidade por workload e auditoria** (candidato preferencial para produção) | Serviços distribuídos, rotação formal e segregação por ambiente | Provedor/região/cobrança/licenças/identidade e restauração ainda sem aprovação |
| **Autogerido centralizado com HA/backup** | Exigência operacional específica e competência de operação | Responsabilidade de disponibilidade, rotacionar/root/unseal, backup/restore, sobrecarga e custos |
| **Variáveis de ambiente isoladas apenas em desenvolvimento inicial** | Scaffold F0 local com valores fictícios/nenhuma conta real | **Não substitui cofre de produção**, exige governança quando houver segredo real |

**Recomendação para avaliação:** cofre gerenciado e autenticação curta por workload, isolando `dev/staging/prod`, sem segredos em GitHub, prompts, logs, job payload, exportação ou modelos. **Não aprova produto/preço/plano, credencial, instalação ou compra**. CORE09-D04..D10 e SEG-02 permanecem abertos.

**Ambiente F1:** conter custos por serviço, dados sintéticos exclusivamente, PostgreSQL separado/isolado, CI sem secrets de produção, backups e observabilidade independentes, política de acesso à VPS central Imagine; essas decisões cruzam ARQ-01/02/03 e documentação INF-DEV e devem ser reconciliadas **antes de provisionar**.

## 7. Registro de decisões para fechamento posterior — critérios objetivos

| Bloco | Decisão de referência candidata | Autoridade de revisão requerida | Evidência para mudar estado |
|---|---|---|---|
| **IAM-DB** | UUIDv7 + tenant explícito, FK contextual composta, histórico próprio | Backend/DBA/IAM + donos funcionais | ER e dicionário exatos, constraints/índices, prova de ausência de referência A→B |
| **IAM-RLS** | Role de app sem BYPASS, `USING`/`WITH CHECK` e contexto transacional verificado, alternativa A vs B | DBA/Segurança/IAM | Política completa por entidade/operação, revisão da confiança de contexto e pool |
| **IAM-ACL** | Política P1+P2 escopada, owner do módulo e cache/revogação | Segurança/IAM, CORE02/03/07 e owners | Matriz efetiva papel×recurso×campo×escopo, regras negativas e logs |
| **IAM-AUTH** | TOTP como MFA inicial de administrador; WebAuthn candidato forte, recovery codes hash | Segurança/IAM/UX/Operação | Fluxos e prazos concretos, threat model MFA/recovery, aceite |
| **SEG-01** | Serviço/IA sem privilégios iniciais e autorização tipada independente | Segurança/CORE09/INT/AGT | Matriz ferramenta×ação×tenant×risco, validação de falha segura e auditoria |
| **SEG-02** | Cofre gerenciado como preferencial, segregação de workspaces e credenciais curtas | Segurança/Infra/FIN | Produto/região/custos/licença, rotação, incidentes e restore aprovada |
| **ARQ-F1** | Ambiente de desenvolvimento isolado e serviços mínimos sem produção | Infra/Operação/FIN | Topologia/capacidade, custo, segregação/backup/observabilidade e GO de gastos se houver |
| **VALIDAÇÃO** | Matriz de casos negativos e controle de versões/owners | Segurança/DBA/Qualidade | Roteiros aprovados; execução depois do GO correspondente, não antes |
| **GO F1** | Nenhuma implementação sensível nem merge sem evidência | Responsável do projeto + segurança | Baseline SHA, contratos necessários revisados e autorização textual do escopo |

**Competências são sugestões de revisão, não pessoas designadas ou revisões já efetuadas.**

## 8. Próxima etapa e gate de liberação

1. Finalizar CT-F1-01 (ER/schema) e CT-F1-02 (RLS/contexto) com seleção física por DBA/Segurança e inventário de tabelas; em paralelo finalizar CT-F1-03/04 (IAM/MFA/sessões), pois as entidades dependem umas das outras.
2. Aprovar por pacote **somente** as alternativas verificadas com dependências e riscos resolvidos; rejeitar ou rediscutir as que criem bypass, vazamento, insegurança de MFA ou custo.
3. SEG-01/02 continuam **P0** até matriz real e cofre/identidade de serviço revisados. A exceção F1 "sem agente, sem integrações reais" é isolamento de escopo, **não fecha** os P0 transversais.
4. Preparar GO F1 delimitado após contratos, segurança, evidências documentais e SHA. F0 em branch já está autorizado conforme DEC-FASTTRACK-01; desenvolvimento de código F1 com dados sensíveis e integrações reais **não** foi autorizado por este arquivo.
5. Executar testes com dados exclusivamente sintéticos **após** liberação aplicável, registrar resultados verdadeiros e manter homologação/produção sob autorização posterior.

**Estado final deste documento:** proposta técnica física **PENDENTE DE HOMOLOGAÇÃO**, 67 identificadores CORE **ABERTOS**, SEG-01/SEG-02/ARQ/INT P0 e G01..G06 ainda **abertos**, F0 GO existente, F1 GO pendente. Nenhum teste executado e nenhum código criado por este ato.
