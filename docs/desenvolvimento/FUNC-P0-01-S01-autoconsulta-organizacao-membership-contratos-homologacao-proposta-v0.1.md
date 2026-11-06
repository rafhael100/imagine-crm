# CRM Imagine — FUNC-P0-01 / S01: contratos e homologação da autoconsulta (proposta v0.1)

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Situação:** **RECORTE A EXPRESSAMENTE APROVADO para DETALHAMENTO DOCUMENTAL**, conforme manifestação do responsável na [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22). **S01 ainda NÃO aprovada**; as regras abaixo são propostas para nova avaliação.  
**Destino documental:** `dev/fast-track-crm-2026-11-04`; sem código ou alterações da branch `main`.  
**Referências:** [FUNC-P0-01 — proposta anterior](FUNC-P0-01-contratos-primeiro-recorte-funcional-proposta-v0.1.md), [F1-10 — baseline e 67 CORE](F1-10-consolidacao-gates-seguranca.md), [PR #20](https://github.com/rafhael100/imagine-crm/pull/20), [PR documental CORE02 #21](https://github.com/rafhael100/imagine-crm/pull/21), documentos CORE01/02/04/06/07/08/09/10, SEG-01/02 e LGPD.

> **Delimitação obrigatória:** a direção aprovou SOMENTE escolher o recorte A para aprofundar seus contratos e critérios. Não aprovou esta especificação, programação funcional, rotas, SQL, novos grants/`ALLOW`, login real, testes de homologação, merge de PR #2/#21, `main`, VPS ou produção. `DenyByDefaultAuthorizer` continua único e deny-only. Todos os **67 IDs técnicos CORE**, SEG-01/02, AUT-P01..14, ARQ/INT/FIN/CON/LGPD e G01..G06 ficam **ABERTOS**.

## 1. Propósito e delimitação de produto

O primeiro recorte deve permitir, **somente após homologações e autorização futuras**, que uma pessoa já autenticada no **CRM Imagine** consulte (i) o resumo mínimo da organização/tenant **atual verificado no servidor** e (ii) **apenas o seu vínculo ativo** nessa organização. Autoconsulta é **leitura sem efeitos**; não deve ser usada para testar inclusão/gestão de usuários, cadastro de organização, contas de pagamento, campanhas, CRM de leads ou integração com Portal.

**Códigos semânticos candidatos (não rotas, não grants):**
- `organization.read_own_summary` (owner proposto: Organizações/IAM; confirmar).
- `membership.read_self` (owner proposto: IAM/Membership; confirmar).

**Dentro do recorte:** modelo lógico de consulta, apresentação interna ao CRM, campos candidatos mínimos, estados e recusas, critérios de segurança/privacidade, matriz de homologação, rastreabilidade dos gates e desenho de aprovação.

**Fora do recorte:** autenticação pública e onboarding; edição/convites/roles, delegação M2/GOV02, outros membros, CNPJ/documentos, e-mail/telefone, suporte/administrador Imagine em conta de cliente, plano/preço/cobrança, exportação/importação, dados de leads, dados financeiros, anexos, painel externo/API pública, jobs, n8n/IA, webhooks, escrita, notificações e integração Portal↔CRM. Login unificado Portal/CRM **não é pressuposto** deste pacote.

## 2. Contratos de leitura propostos, sujeitos à aprovação

### S01-C01 — Entrada lógica confiável

Operação invocada na área interna da conta CRM de pessoa **legitimamente autenticada**, com sessão server-side íntegra e membership atual ativa. O tenant corrente será derivado de **contexto autenticado do servidor**, não de `tenant_id` arbitrário em query, header, campo oculto, JWT não verificado ou GUC PostgreSQL. Identidade global ≠ vínculo na organização. Troca de organização é **outra função, não incluída** nesta fatia; não implementar catálogo de múltiplas memberships nem seletor neste pacote.

Não admitir impersonação, service principal, IA, staff Imagine, prompt, credencial de integração ou mandato de suporte como equivalente ao próprio usuário. Ausência de qualquer fonte verificada => deny. Sem estabelecimento real de sessão/tenant neste documento.

### S01-C02 — Resposta semântica mínima

**Candidatos de campos, não DTO/API/SQL definidos.** Só dados pertencentes à organização efetivamente selecionada e ao próprio vínculo do sujeito. Antes da homologação exigir fonte oficial, owner, classificação CORE02-D04, base/finalidade e política de exibição por estado:

| Campo lógico candidato | Exibição proposta | Classificação preliminar; confirmar com owner/LGPD | Observação |
|---|---|---|---|
| `organization.display_name` | Nome de apresentação da própria organização | F-BIZ; pode conter PII e exige revisão | Minimizar caracteres, normalizar/escapar na interface; nunca confiar em HTML do cadastro |
| `organization.opaque_ref` | **Somente referência interna**, não necessariamente visível na UI | F-ID/F-BIZ; classificação pendente | ID opaco não é autenticação, senha, token ou filtro confiável |
| `membership.status_label` | Rótulo amigável do vínculo **ativo** quando autorizado | F-ID/F-RES; classificação pendente | Não divulgar razões de suspensão/revogação de outros usuários |
| `membership.role_label` | **Opcional**, somente se catálogo/escopo já homologados | F-ID/F-RES; classificação pendente | Nome de papel não é prova de autorização ou competência M2 |
| `membership.opaque_ref` | **Preferencialmente só interno**, se necessário | F-ID/F-RES; classificação pendente | Nunca expor para consultar vínculo de outra pessoa |
| `organization.status_label` | **Condicional e não aprovado** | F-BIZ/F-FIN? revisar | Estado de conta/assinatura pode revelar informação comercial; adiar até CORE08/FIN/CON |

**Proposta de saída visível V0:** apenas `organization.display_name` e a indicação genérica `Vínculo ativo`; `role_label` opcional mediante revisão de CORE02/CORE07. Identificadores opacos, campos de suporte e versões podem permanecer internos, se legitimamente necessários. **Não incluir** CNPJ, dados de terceiros, contatos, plano/valor, pagamento, status financeiro, trilhas, segredos, outros tenants nem listas de usuários.

Formato final de objeto, wire JSON, códigos HTTP, política de cache e endpoints **pendem de especificação/homologação**. Esta tabela é catálogo documental, não payload ativado.

### S01-C03 — Regra de legitimidade e decisão

Antes de uma leitura real futura seriam cumulativamente requeridos:
1. principal humano autenticado e sessão atual verificável (MFA/step-up segundo classe de risco CORE04, sem inventar TTL);
2. tenant corrente resolvido por fonte confiável e membership efetiva, ativa e do **mesmo tenant**;
3. contrato do módulo, direitos por ação/campo/escopo e condições de estado e entitlement aplicável confirmados pelo owner;
4. bloqueios mandatórios superiores (RLS, revogação, segurança, proteção de dados, políticas do módulo);
5. **CORE02-D05 alternativa B**, ratificada apenas documentalmente: união de grants *válidos* no subescopo permitido (P1) com deny explícito *válido* prevalecendo na **interseção** de recurso/ação/campo/escopo correspondente (P2). Ausência de grant não é veto explícito global, mas resulta em negação por padrão;
6. acesso aos registros pela fonte oficial, com isolamento por tenant e controle de campos, logs mínimos e resultado seguro.

**Nenhum passo acima habilita `ALLOW` nesta fase.** O algoritmo de grants/denies, schemas, proveniência, delegação e `AUTHORIZE` executável permanecem gates abertos. O Modelo M2 de CORE02-D05-GOV01 foi ratificado documentalmente, mas esta fatia **não permite gerenciar nem delegar perfis**. GOV02 A–D segue **não aprovado**. Nenhuma regra da organização Básico com único administrador MFA autoriza segundo assento ou bypass de recuperação.

### S01-C04 — Estados e revogações

| Situação de origem (a provar) | Expectativa proposta, ainda não operacional |
|---|---|
| Sessão válida + membership ativa + regras homologadas | **Candidato a leitura** somente após futura autorização de código, checks e gates; por enquanto sempre DENY |
| Sem sessão / sessão expirada / MFA ausente quando obrigatório | Deny; resposta genérica sem revelar existência da organização |
| Convite pendente, membership suspensa, revogada ou inexistente | Deny da autoconsulta; não fornecer nome, ID, papel ou outro tenant |
| Organização/conta desativada ou estado de contrato incerto | **Sem suposição**: deny do recorte até política de estado do owner; direitos legítimos de suporte à conta/portabilidade são fluxos separados |
| Trial/cortesia/pago/suspenso/cancelado | Exigir especificação CORE08/FIN/CON dos direitos mínimos legítimos; **não** cobrar ou converter automaticamente |
| Troca de organização na mesma identidade | Não transportar contexto da leitura antiga; seleção e autorização de nova organização exigem contrato próprio |
| Revogação entre abertura de tela e consulta/resposta | Revalidar nas fronteiras pertinentes, impedir leitura nova sob autoridade antiga e invalidar cache; sem prometer atomicidade global sem evidência |
| Falha de PostgreSQL, Redis, auditoria mandatória ou GUC contaminada | Deny/fail-closed; não usar cache cross-tenant nem resultados sintéticos |

Não inventar TTL de sessão, refresh, cache, período de retenção, política de step-up, granularidade de bloqueio ou exceção de acesso em estado suspenso. Tais decisões exigem owners e contratos específicos.

### S01-C05 — Persistência, RLS, apresentação e privacidade

- Sem migração nesta etapa. Futuro estudo de SQL/roles exige `FORCE ROW LEVEL SECURITY`, grants mínimos não-owner/non-BYPASSRLS, chaves tenant-bound, validação de integridade, transações e limpeza da GUC em pool. `app.tenant_id` é contexto, **não autoridade**; backend + sessão + membership devem ser comprovados independentemente.
- A apresentação do CRM terá somente estado vazio/negado/carregando/sucesso seguro a decidir, sem enumerar tenants, membership de terceiros, exceções SQL ou detalhes internos no UI. Não expor associação entre IDs e usuários por mensagem de erro ou latência explorável.
- Sem cache compartilhado ou armazenamento no browser que atravesse contas/tenants; especificar `no-store` ou opção mais adequada somente após revisão do fluxo pelo owner; sem implementar.
- Exigir classificação LGPD dos campos, base/finalidade legítima, minimização, visibilidade, retenção de telemetria, proteção de logs e controle sobre quem consulta auditoria. Nenhuma gravação de histórico comercial, PII ou sink novo agora.
- Fluxo de autenticação independente do Portal não requer SSO; este documento não implanta login nem altera a arquitetura do Portal/Universidade/Performance.

### S01-C06 — Falhas e observabilidade

Definir apenas as **categorias documentais** `session_unproven`, `membership_inactive`, `tenant_mismatch`, `policy_unproven`, `field_not_authorized`, `entitlement_unknown`, `dependency_unavailable`, `context_contaminated`, `audit_unavailable`. Mensagens ao usuário devem ser minimizadas, sem revelar tenant, identidade, existência de recurso, campos protegidos, SQL, logs, políticas internas ou motivo de segurança específico. Códigos/tabelas de erro não são implementados neste documento.

Separar evento de segurança/negação (módulo IAM/CORE06) de efeito de negócio (nenhum neste recorte). Observabilidade sintética de F1-07 não é sink/registro oficial.

## 3. Matriz de homologação PROPOSTA, não executada

Os IDs abaixo são **cenários de teste futuros**, não tickets para Codex, não certificação. Todos os passos positivos devem permanecer **pendentes** até que exista código e `ALLOW` restrito especificamente aprovado.

| ID | Cenário | Esperado futuro | Dependência |
|---|---|---|---|
| S01-T01 | Pessoa legítima de organização A, sessão e vínculo ativos, somente campo `display_name` autorizado | Resposta mínima exclusivamente A, sem PII/terceiros | CORE01/02/04/SEG |
| S01-T02 | Consulta do próprio vínculo ativo em A | Apenas o vínculo próprio sem listar colegas | CORE01/07 |
| S01-T03 | Sessão ausente ou adulterada | Deny sem identificação de A | CORE04/SEG |
| S01-T04 | Header/query/cookie tenta trocar tenant de A para B | Deny; GUC jamais usada como autenticação | CORE01/SEG/RLS |
| S01-T05 | Mesmo humano com vínculos A e B, mas contexto ativo A | Resposta não mistura B; sem seleção por ID arbitrário | CORE01/07 |
| S01-T06 | Membership pendente, suspensa ou revogada | Deny e nenhuma enumeração | CORE01/07 |
| S01-T07 | Membership revogada depois da abertura da tela, antes da nova leitura | Nova leitura negada; cache e sessão invalidados conforme contrato | CORE01/04/07 |
| S01-T08 | MFA insuficiente para o nível de risco aprovado | Deny, sem contornar step-up | CORE04/02-D07 |
| S01-T09 | Grant de leitura válido no subescopo A e nenhum em B | Só recursos autorizados de A; sem direitos cruzados | CORE02-D05 B/D08 |
| S01-T10 | Deny explícito qualificado ao mesmo campo/ação/escopo do grant | Deny na interseção, não veto global de outro escopo | CORE02-D05 B/D10 |
| S01-T11 | Ausência de grant, mas sem deny explícito cadastrado | Deny-by-default, sem simular deny administrativo global | CORE02-D05 |
| S01-T12 | Tentativa de incluir `role_label` ou `organization.status_label` sem classe aprovada | Campo omitido/negado, sem payload adicional | CORE02-D04/D06, LGPD |
| S01-T13 | Usuário tenta listar colaboradores, organizações de terceiros ou outras memberships | Negado, sem endpoint alternativo | CORE02/07 |
| S01-T14 | Agente IA, n8n, staff Imagine, suporte ou serviço técnico tenta simular `read_self` | Deny; não falsificar principal humano | CORE03/05/09/SEG |
| S01-T15 | Plano/cortesia/trial/suspensão sem contrato de leitura aprovado | Deny do recorte até política; fluxos legais mínimos separados | CORE08/FIN/CON/LGPD |
| S01-T16 | Connection pool com GUC herdada; role owner/BYPASSRLS | Nenhuma leitura sob contexto ou role impróprio | CORE01/SEG/RLS |
| S01-T17 | Falha de banco/Redis ou observabilidade mandatória | Deny/fail-closed, sem resposta em cache incompatível | CORE04/06/SEG |
| S01-T18 | Tentativa de escrita, export, convite ou mudança de papel em superfície de autoconsulta | Nenhum efeito de negócio; bloqueio explícito | CORE02/07/08 |
| S01-T19 | Erro lançado e resposta renderizada | Sem SQL, segredo, IDs de terceiros, stack trace ou regra interna | CORE06/LGPD |
| S01-T20 | Múltiplas requisições/conexões PostgreSQL concorrentes, incluindo revogação | Isolamento transacional/revalidação proporcional e provas dos limites | CORE01/04/SEG/QA |
| S01-T21 | API ou outro canal não aprovado tenta a mesma leitura | Deny; interface não é controle de segurança único | CORE02-D08/10 |
| S01-T22 | Estado da organização mudou antes de nova leitura | Revalidar regra do owner, não confiar no estado mostrado anteriormente | CORE08/FIN/CON |

**Evidência de homologação futura:** owner, decisão aprovada, caso/ID, versão de contrato/código, Git SHA, ambiente explicitamente autorizado, configuração de tenant/roles, matriz esperada vs. real, logs sanitizados, falhas/reteste, relatório de risco, teste PostgreSQL 16/Redis e aceite assinado/registrado por competências apropriadas. Nenhum `passed` atual das suítes F1 aprova estes T01..T22.

## 4. Pacotes de contratos e gates — proposta de ordem

| Fase documental proposta | Entrega específica | Donos por competência, ainda não designados | Pendente |
|---|---|---|---|
| S01-D01: escopo/UX | Confirmar códigos semânticos, tela interna única e exclusões | Direção, Produto/Organizações, IAM | Catálogo/UX |
| S01-D02: atributos/resposta | Campos exatos, fonte oficial e classificação por campo | Owner organização/membership, Segurança, Privacidade | CORE02-D04/D06, LGPD |
| S01-D03: identidade/tenant | Sessão humana, membership, contexto seguro, troca de tenant fora do recorte | IAM, Segurança, DBA | CORE01/04/07/SEG |
| S01-D04: decisão | Exceções e precedência B+M2 somente como base, negatórias, escopos e veto | IAM, Produto, Segurança | CORE02-D05/D08; GOV02 ainda pendente |
| S01-D05: estado comercial | Direitos de leitura por trial/cortesia/pago/suspenso/cancelado | Produto, CORE08, FIN/CON, Privacidade | CORE02-D09 |
| S01-D06: segurança/privacidade | Threat model, SQL/RLS, auditoria e dados mínimos | Segurança, DBA, CORE06, LGPD | SEG, CORE01/06/10 |
| S01-D07: homologação | Matriz S01-T01..T22, owners, critérios e evidências de aceite | QA, Segurança, donos funcionais | CORE02-D10, CORE10/G01..G06 |
| S01-D08: futura autorização | Definir branch, review, PR, ambiente, rollback e decisão expressa sobre **se/quando** pode haver `ALLOW` limitado | Direção, Arquitetura, Segurança | DEV-GATE, SEG, CORE10 |

**NO-GO atual:** ninguém foi nomeado owner real, não há catálogo de campos e políticas finais; falta decisão sobre entitlement/estado, MFA/step-up, modelo físico de grants/RLS/auditoria, aceites de segurança e base LGPD. Os **67 CORE técnicos, SEG-01/02, AUT-P01..14, ARQ/INT/FIN/CON/LGPD e G01..G06 continuam abertos**. A escolha de S01 não encerra esses gates.

## 5. Próxima deliberação à direção — NÃO presumir aceite

Solicitar manifestação expressa separada sobre **somente diretrizes documentais S01**:
1. **S01-D01 — superfície:** primeira tela de autoconsulta **somente dentro do CRM**, mostrando o próprio tenant corrente e o próprio vínculo ativo, sem seletor de empresa nessa fatia;
2. **S01-D02 — mínimo visível:** propor **nome de apresentação da organização + indicação de vínculo ativo**; rótulo de papel, status comercial, identificadores e qualquer dado adicional ficam fora até homologação;
3. **S01-D03 — segurança:** exigir sessão humana comprovada, vínculo/tenant server-side, política de MFA proporcional a definir, RLS não-bypass, revalidação e deny-by-default até futura autorização específica;
4. **S01-D04..D08 — governança:** aceitar apenas a *necessidade* dos contratos e da matriz S01-T01..T22, sem aprovar seus detalhes físicos, `ALLOW`, SQL, sessão pública, política de plano, riscos e homologações.

**Se a direção aprovar estas diretrizes**, registrar o aceite **somente documental**, preservar todos os gates e seguir para detalhamento documental dos itens ainda não decididos. **Não gerar tarefa de código nem executar Codex sem nova ordem expressa.** Nenhuma abertura de cliente ou produção implícita.
