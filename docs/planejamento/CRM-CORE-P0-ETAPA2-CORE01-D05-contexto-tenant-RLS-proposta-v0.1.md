# CRM-CORE-P0 — Etapa 2 / CORE01-D05: Contexto confiável de tenant, Laravel, PostgreSQL RLS, workers e suporte (v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE01-D05-A, B, C e D APROVADAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**, conforme [decisão expressa](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md). **CORE01-D05 permanece ABERTA**; D04, D06, D07, SEG-01/02, demais CORE/INT/ARQ/AGT e G01–G06 seguem abertos. As passagens abaixo que chamam A/B/C/D de “não aprovadas” retratam a minuta anterior ao aceite e são superadas exclusivamente nesse alcance.  
**Natureza:** alternativas, invariantes, riscos e decisões futuras, **não** aprovação de schema, SQL, role de banco, tecnologia, mecanismo de autenticação ou implantação.  
**Fase:** exclusivamente documentação. **Não programar, executar SQL ou testes, provisionar contas/servidores, conceder acessos, rotacionar segredos, implantar ou contratar**.

## 1. Precedência e contexto

- [CORE01-D01..D03 aprovadas](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): identidade global, memberships por organização/tenant e isolamento no Laravel + PostgreSQL RLS, sem modelo físico final.
- [CORE01-D04-A/B/C aceitas preliminarmente](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md): unicidade lógica de membership identidade×tenant, convite separado de grant e desligamento por tenant; **D04 continua aberta** até CORE02/04/07/08.
- [CORE01 D04..D07 — escopo da etapa](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md): D05 ainda exige escolher transmissão/validação de contexto confiável entre API, worker, conexão SQL e suporte.
- [CORE02](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md), [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md), [CORE04](CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md), [CORE05](CRM-CORE-P0-05-acesso-excepcional-imagine-mandatos-suporte-proposta-v0.1.md), [CORE07](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md), [CORE09](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md): autoridade por recurso/ação/campo, delegação, jobs, MFA, mandatos e segredos **ainda carecem de especificação executável**.
- [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md): SEG-01/02, ARQ-01/02/03 e AGT-01 continuam P0; [INT G01..G06](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) incompletos.
- Documentação de referência do PostgreSQL: https://www.postgresql.org/docs/current/ddl-rowsecurity.html e https://www.postgresql.org/docs/current/config-setting.html . Referências **de pesquisa**, não escolha de versão ou configuração de banco.

**Atenção a riscos da RLS:** RLS é uma barreira adicional, **não substitui autenticação, autorização por ação e proteção contra SQL injection**. Em PostgreSQL, superusuários e roles BYPASSRLS contornam RLS; proprietários de tabelas normalmente também contornam, salvo políticas de FORCE ROW LEVEL SECURITY. Políticas permissivas podem combinar condições com OR; permissões GRANT e políticas RLS não são sinônimos. Definição de contexto por variável de sessão/transação não prova, sozinha, que o tenant foi autorizado. Modelagem de funções, views, constraints, exportações e backups exige revisão própria.

## 2. Domínios e matriz de confiança para operações com dados de empresa

| Fronteira | Fonte confiável do tenant | O que NÃO comprova legitimidade |
|---|---|---|
| Requisição humana (API/web) | Principal autenticado + membership ativa no tenant selecionado + política CORE02 por ação/recurso/campo + entitlement e estado | `tenant_id` de header/body/query/local storage, e-mail, domínio ou CNPJ |
| Troca de empresa com login global | Backend reavalia membership e muda contexto válido de tenant sem herdar cache/papel | Último tenant usado, convite pendente, outra membership válida |
| Worker ou job diferido | Principal técnico executor + ator causal/mandato + tenant/objeto derivado de comando oficial versionado + revalidação de autoridade quando exigível | Conteúdo de fila, token antigo, claim do produtor, sucesso HTTP |
| Webhook/conector de provedor | Assinatura/procedência + vínculo oficial serviço/conta/tenant/destino + contrato tipado e direitos atuais | Provedor autenticou evento, ID arbitrário de cliente, conta externa compartilhada |
| Site e captação de lead | Vínculo site↔CRM oficialmente ativo e autorizado confirmado no backend, inclusive versões em cache | Campo `tenant_id` enviado pelo visitante, ID público de formulário, presença de site estático |
| n8n/IA/serviço Imagine | Identidade de serviço, segredo de menor privilégio, contexto autorizado por ação/tenant/ambiente/versão/playbook | Prompt, modelo, workflow, possuir credencial de provedor ou estar na VPS |
| Suporte excepcional | Mandato competente CORE05 por caso, recurso, ação, prazo e operador com autenticação apropriada | Admin Geral com papel elevado ou suposto “bypass de manutenção” |
| Auditoria/backups/admin infra | Conta/serviço de finalidade especial, escopo, registro e políticas restritas, separados do uso comercial | Permissão de backup/migração equivale a exportação de CRM por qualquer operador |

**Invariante:** sem principal validado, contexto competente e prova do tenant específico, **negar** novas consultas e alterações tenant-bound. Mesmo principal com múltiplas memberships opera em **uma organização explicitamente autorizada por vez**, salvo operação global especializada auditada e formalmente contratada, não presumida.

## 3. Alternativas técnicas ainda sem escolha final

### 3.1. Contexto de tenant na aplicação e no banco

| Alternativa | Benefício potencial | Risco/condição para uso |
|---|---|---|
| **A — Contexto por operação e transação com autorização no backend e RLS** (diretriz preferencial de investigação) | Reduz risco de resíduo no pool e permite política clara de entrada/saída de transação | Exige fronteiras transacionais consistentes, definição concreta de roles, propagação para workers, tratamento de erros/retry e segurança contra SQL arbitrário |
| B — Contexto de sessão reutilizável sob controle estrito | Pode integrar workloads existentes com maior flexibilidade | Alto risco de tenant residual em pools/uso concorrente; deve provar reset absoluto, não inferir isolamento por facilidade |
| C — Roles distintos por serviço ou escopo e políticas de acesso por classe | Pode fortalecer segregação entre operações técnicas | Gestão de roles e credenciais, escalabilidade, concessões e risco de roles privilegiados; não resolve autorização dinâmica de recursos/campos sozinha |
| D — Isolamento físico específico por tenant onde alguma exigência futura legitimar | Reduz alguns tipos de risco de mistura | Complexidade, custo e divergência da arquitetura de referência; **não substituir RLS/Laravel já aprovados sem nova decisão expressa** |

**Preferência técnica preliminar para discussão:** estudar **A**, com contexto **de escopo transacional** se compatível com pool/driver e sem confiar na variável de sessão como prova de autoridade. A escolha de mecanismo exato, incluindo `SET LOCAL` ou `set_config(..., true)`, roles separadas, conexão/transação e política SQL fica **aberta**; não aprovar apenas por citar a técnica.

### 3.2. RLS: riscos que precisam de contrato concreto

1. **Proprietário/superusuário/BYPASSRLS:** o role de aplicação não deve adquirir capacidade de contornar políticas por erro de grants/ownership. Designar roles de migração/backup distinta e exceções legítimas justificadas; confirmar eventual `FORCE ROW LEVEL SECURITY` por tabela/role, sem assumir que cobre superusuário/BYPASSRLS.
2. **Políticas permissivas e restritivas:** selecionar semântica positiva/negação por tabela e operação; múltiplas políticas permissivas podem ampliar acesso via OR. `USING` (visibilidade/linhas-alvo) e `WITH CHECK` (novas linhas escritas) requerem análise separada.
3. **Contexto não autenticador:** `current_setting`/parâmetro customizado é contexto fornecido à conexão; não substitui autenticação por principal, SQL injection defense, validação pelo backend nem proteção de funções `SECURITY DEFINER`. Não delegar criação arbitrária do tenant ao usuário.
4. **FKs/constraints e canais laterais:** integridade pode gerar efeitos observáveis fora da RLS. Regras de chave/relacionamento tenant-bound, erros minimizados e não enumeração são requisitos explícitos para CORE01-D07.
5. **Pool, job e concorrência:** troca A→B, transações aninhadas, exception, retry, exportação longa e worker paralelo não devem carregar contexto ou cache do outro tenant.
6. **Operações não cobertas só por RLS:** backup/restauração, `TRUNCATE`, manutenção, funções privilegiadas, relatórios externos, índices de busca, arquivos, exportações e cache exigem políticas e contratos específicos de autoridade.
7. **Separação de ambientes:** homologação e produção não compartilham segredos/roles; testes futuros usam dados fictícios ou apropriadamente anonimizados e só quando autorizados.

## 4. Diretrizes CORE01-D05 para futura manifestação explícita — PROPOSTAS NÃO APROVADAS

- **CORE01-D05-A — validação de contexto por operação:** o backend deverá identificar principal, organização autorizada, membership/mandato, entitlement, recurso/ação/campo e estado, sem utilizar `tenant_id` arbitrário como autorização; negativa por padrão quando faltar qualquer elemento indispensável.
- **CORE01-D05-B — isolamento em unidade transacional e reuso seguro:** usar como diretriz **contexto de tenant mínimo e de duração delimitada à operação/transação**, com prova de encerramento/reset para conexões reutilizadas, erros, retries, exportações e alternância A↔B, além da RLS obrigatória. `SET LOCAL` é alternativa de implementação futura **não homologada nesta aprovação**.
- **CORE01-D05-C — papéis/serviços/mandatos sem bypass:** separar serviço executor de ator causal, limitar roles de banco, segredos, n8n/IA e suporte excepcional; nenhum operador/serviço cotidiano obtém superuser ou BYPASSRLS para consultar clientes. Fluxos de manutenção/backup precisam alçada, trilha e contrato específicos.
- **CORE01-D05-D — resultado oficial/revalidação:** trabalhadores assíncronos, conectores e ferramentas devem revalidar autorização aplicável antes de novos efeitos sensíveis, impedir replay/revogações obsoletas e deixar confirmação oficial ao módulo proprietário, com trilha no CORE06, preservando trabalho legítimo autônomo já confirmado.

**Aprovar D05-A/B/C/D como diretrizes preliminares não resolverá CORE01-D05 fisicamente**, nem CORE01-D07/CORE02/03/05/07/09/SEG-01/02. Os métodos definitivos e sua validação seguem decisão própria.

## 5. Especificação a exigir antes de considerar CORE01-D05 fechada

| Artefato/contrato | Conteúdo mínimo exigível | Estado |
|---|---|---|
| Matriz de atores × objetos × operações × tenants | Identidade humana/técnica/mandato, recurso/ação/campo, atributos de competência, versão, exemplos de negativa e owner | **Pendente CORE02/05/07/09** |
| Tabela/família global vs tenant-bound | RLS por classe, requisitos de integridade/campos, exceções, GRANT, ownership, operações sem RLS | **Pendente CORE01-D07** |
| Especificação de fronteira Laravel→DB | Mecanismo autenticado de contexto por transação, roles e pool, reuso/rollback/retry, error handling, resets | **Pendente SEG-01/02 e CORE03-D06** |
| Contrato assíncrono | Ator causal, executor técnico, idempotência, versão, expiração, confirmação oficial, revogação, DLQ e reconciliação | **Pendente CORE03-D04..D10, INT CT03/04** |
| Serviços/manutenção/suporte | Mandato CORE05, service principal, segregação de credenciais/ambientes, autorização independente da IA | **Pendente CORE05/09, SEG-01/02** |
| Auditoria/telemetria | Trilha sem segredo, classificação/retentão e consulta, evidência de negação e incidentes | **Pendente CORE06** |
| Pacote de casos negativos | Requisitos e esperados por caso, ambiente futuro, owner e fontes canônicas; nenhum caso executado | **Pendente CORE10/INT P0-ACC** |

## 6. Cenários adversos a reconciliar (NÃO EXECUTADOS)

| Caso documental | Ataque/falha | Resultado esperado |
|---|---|---|
| CORE01-D05-T01 | Usuário com A e B envia `tenant_id=B` enquanto contexto ativo é A | Negar sem autorização efetiva de B; nunca selecionar apenas pelo parâmetro |
| CORE01-D05-T02 | Conexão em pool foi usada por A, depois por B | Nenhuma linha/cache/role de A visível a B; contexto resetado |
| CORE01-D05-T03 | Worker retoma job depois de desligamento do solicitante em A | Bloquear novos atos dependentes da autoridade pessoal, preservar atos autônomos legítimos |
| CORE01-D05-T04 | Role de aplicação é owner ou BYPASSRLS por engano | Fluxo de app não pode contornar RLS; diagnóstico de configuração deve impedir aprovação |
| CORE01-D05-T05 | Política permissiva combina permissões com OR e expõe linhas | Não conceder acesso fora do direito cumulativo esperado |
| CORE01-D05-T06 | Tentativa de INSERT/UPDATE leva linha para tenant B por atribuição fraudulenta | `WITH CHECK`/integridade/autoridade evitam alteração cruzada |
| CORE01-D05-T07 | Falha da transação/rollback seguida de retry de tenant diferente | Não reter contexto anterior |
| CORE01-D05-T08 | Admin Imagine pede lead sem mandato válido e tenta rota de manutenção | Negar e auditar sem BYPASSRLS geral |
| CORE01-D05-T09 | Um conector autenticado fornece ID de outra conta | Negar segundo contrato de conta/destino e tenant, mesmo com assinatura válida |
| CORE01-D05-T10 | Exportação longa é disponibilizada após revogação de membership | Revalidar autorização antes de disponibilizar resultado |
| CORE01-D05-T11 | Restauração traz vínculo/segredo revogado | Não reativar credencial nem acesso automaticamente |
| CORE01-D05-T12 | Site em cache tenta enviar lead sem CRM autorizado | Backend nega recepção mesmo com identificador público válido |

Esses casos são **referências documentais** a reconciliar com CORE01-T, CORE03-T, REV01 S01..S10, INT HT01..18 e CORE10-D06, não uma nova suíte independente ou executável.

## 7. Bloqueios e pedido de aprovação

**CORE01-D05 A/B/C/D — NÃO APROVADAS** até manifestação expressa do responsável. **CORE01-D05 permanece ABERTA** mesmo com aceite preliminar, até especificação física, compatibilidade dos contratos e homologação documental por módulo e Segurança. **CORE01-D04 permanece ABERTA** apesar do aceite preliminar A/B/C; D06 e D07 continuam abertas.

**Seguem ABERTAS:** CORE02..CORE10-D04..D10, SEG-01/02, ARQ-01/02/03, AGT-01, INT P0/CTs/OP24/G01..G06, FIN/CON, LGPD, licenciamento n8n, budgets, infraestrutura, aceite global. A matriz da [Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) mantém **67 IDs técnicos abertos**.

**Sem programação, testes, scripts, SQL real, credenciais, contorno de RLS, compras ou implantação**. Próxima revisão após eventual aceite: CORE01-D06 sem encerrar D04/D05 por inferência.

## 8. Aditamento — decisão preliminar CORE01-D05-A/B/C/D (04/11/2026)

O responsável aprovou expressamente como **diretrizes técnicas documentais preliminares**: (A) contexto e autorização por operação; (B) delimitação transacional e reset seguro de conexões, com Laravel+PostgreSQL RLS; (C) identidades técnicas, agentes, n8n e suporte sem bypass amplo; (D) revalidação de autoridade e resultado oficial pelo proprietário. [Registro literal](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md). **Não** se selecionou SQL/SET LOCAL, roles, esquema, token, pool, mecanismo de autenticação ou provedor. CORE01-D04..D07 seguem ABERTAS, incluindo D05 integralmente, assim como SEG-01/02 e todos os gates. Continuidade autorizada: criar **proposta documental CORE01-D06** sobre ciclo de tenant, titularidade, contratos e benefícios; **sem programação nem testes**.


## 8. Aditamento de aceite preliminar CORE01-D05-A/B/C/D — 04/11/2026

O responsável [aprovou expressamente](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md) as diretrizes **A** (contexto confiável por operação), **B** (escopo delimitado por transação e reuso seguro), **C** (identidades técnicas, suporte e manutenção sem bypass geral) e **D** (revalidação de autorização e resultado oficial por proprietário). O aceite **não selecionou política RLS física, SQL, roles de banco, GUC, `SET LOCAL`, pool, filas, meios de autenticação nem tecnologia do cofre**. **CORE01-D05 permanece ABERTA**, dependente especificamente de CORE02, CORE03, CORE04, CORE05, CORE09, SEG-01, SEG-02 e da especificação CORE01-D07. **CORE01-D04, D06, D07 e todas as demais pendências seguem abertas.**

**Continuidade autorizada:** somente [CORE01-D06 — proposta documental de ciclo de organizações/tenants e vínculos comerciais](CRM-CORE-P0-ETAPA2-CORE01-D06-ciclo-tenants-vinculos-comerciais-proposta-v0.1.md), ainda não aprovada. Sem programação, testes ou implantação.
