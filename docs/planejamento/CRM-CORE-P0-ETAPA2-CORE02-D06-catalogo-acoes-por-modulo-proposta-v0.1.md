# CRM Imagine — Etapa 2 / CORE02-D06: catálogo de ações por módulo e contratos de autorização (proposta v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE02-D06-A/B/C/D ACEITAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES EM AVALIAÇÃO DELEGADA**, conforme [decisão técnica registrada](CRM-DEC-aprovacao-delegada-CORE02-D06-ABCD-preliminar-2026-11-04.md). **CORE02-D06 integral permanece ABERTA**. Nenhum direito foi concedido, catálogo real por módulo aprovado ou teste executado. Rótulos históricos de A/B/C/D como não aprovadas precedem a decisão delegada.  
**Origem e autorização:** aceite expresso **apenas preliminar** de [CORE02-D05-A/B/C/D](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md) com ordem para seguir planejando D06, **sem programação nem execução de testes**.  
**Limite:** proposta semântica de contratos de recurso, ação, campo, escopo, titularidade e fonte de resultado; **não** concede permissão a usuário/IA/suporte, nem cria rotas, policies, tabelas, colunas, filas, tokens, papéis físicos, novos serviços ou preços.

## 1. Precedência e requisitos a conservar

1. [CORE02-D01..D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md): direitos exigem identidade/mandato, tenant, membership/entitlement, função, recurso, ação, campo, estado, escopo, finalidade e condições de risco, com **negação por padrão** e autorização pelo proprietário.
2. [CORE02-D04-A/B/C/D](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md): classes F-ID/F-BIZ/F-PII/F-RES/F-CONS/F-FIN/F-SEC/F-EVID/F-ART e direitos por operação/canal **aceitos só preliminarmente**. D04 integral continua aberta até mapear o campo real de cada módulo; para anexos há **uma única permissão ver/baixar** quando prevista, distinta de upload/exclusão.
3. [CORE02-D05-A/B/C/D](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md): limites cumulativos, composição determinística futura, perfis delegáveis sem autoelevação e escopos/revogação. **D05 continua aberta**: não foi escolhida P1/P2/P3/P4 ou a precedência de grants e negações explícitas.
4. [CORE01-D04..D07](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md), [R01/E2-X02](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md) e [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md): identidade global ≠ membership do tenant, Laravel+PostgreSQL RLS e principal técnico separado do autor causal; worker e exportação revalidam direitos para novos efeitos.
5. [DAT/REV03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [ATD/REV04](CRM-REV-04-atendimento-whatsapp-conversas.md), [FIN/REV02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), [CRM-CORE02 matriz conceitual](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) e [INT-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md): fontes funcionais, direitos, limites, contratos e alçadas pendentes por módulo; **não** ampliar recursos do Básico por analogia.
6. [IN-118](IN-118-central-backup-portabilidade-dados.md): portabilidade/importação/exportação essenciais sem dependência do suporte, com direitos e validação. [IN-127-R01..R06](IN-127-R01-R06-ratificacoes-2026-11-03.md): Sites podem publicar estáticos sem CRM com aceite, porém backend proíbe captação sem destino CRM oficialmente vinculado e autorizado; leads em trânsito não são redirecionados silenciosamente.
7. [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md), [CORE09](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md): **SEG-01/02** e cofre/identidade técnica ainda P0; agentes, n8n e provedores não recebem poder por prompt, token técnico ou sucesso de workflow.

**Esta minuta não substitui o catálogo funcional de cada proprietário nem a definição final por plano.** Ação proposta como candidato de autorização **não é** afirmação de que o produto oferece esse recurso em todos os planos.

## 2. Contrato semântico único para qualquer operação — campos lógicos de revisão, não schema físico

| Campo lógico | Por que o proprietário deverá especificá-lo | Exigência contra acesso indevido |
|---|---|---|
| `domain.owner` e `resource.kind` | Módulo dono, entidade e versão contratual | Não criar segundo autorizador ou dono do mesmo fato |
| `operation.code`, versão e efeito | Nome semântico tipado, ato de leitura/escrita/externo/sensível | Não agrupar `read` com `export`, `delete`, `approve` ou `send` |
| `principal`, `tenant`, `membership/mandate` | Autor humano/serviço legítimo e contexto verificado | Mesmo CPF/CNPJ/e-mail, token externo ou admin central não são grants |
| `entitlement`, contrato e estado | Plano, canal, quota, benefício e vigência | Papel da empresa não contrata plano, muda fatura ou libera canal |
| `scope` e `record.owner` | Próprio, equipe, unidade, canal/caixa, empresa, posse e estado quando aplicável | Tenant A nunca dá acesso a B; equipe não é sinônimo de empresa |
| `field.classification`, finalidade, consentimento | Campos permitidos, proteção de PII, segredos, histórico e derivados | UI mascarada não substitui filtragem em backend/API/exportação/IA |
| `risk/MFA/alçada` e delegação | Step-up, confirmação ou aprovação específica já exigida | Não inventar segunda aprovação universal nem ignorar ação crítica |
| `decision/effect.owner` | Proprietário confirma resultado oficial da operação | HTTP 200, mensagem entregue e sucesso n8n não provam negócio |
| `audit/ref/version` | Evidências, motivo de recusa, autoria e versões | Não guardar segredo em claro nem expor dados pessoais na negativa |

**Fluxo lógico de avaliação candidato, sem engine escolhida:** principal validado → tenant/mandato legítimo → entitlement em vigor → recurso/campo/ação/escopo → política do proprietário e estado → MFA/alçada se aplicáveis → execução autorizada → resultado oficial/auditoria. A ordem física e precedência de perfis/negações pertencem CORE02-D05/D08.

## 3. Catálogo candidato por domínio — nenhuma concessão de recurso por simples listagem

| Proprietário | Recurso / atos separados que o catálogo deverá distinguir | Casos que não podem ser autorizados por analogia |
|---|---|---|
| **IAM/CORE — identidade, usuários, equipes** | `view_membership`, `invite_user`, `accept_invite`, `manage_roles`, `assign_team`, `deactivate_membership`, `transfer_admin`; consultar trilha distinto de alterar | Convite não é participação ativa; Básico 1 admin humano com MFA; papéis por tenant, sem autoconcessão, último admin/assento CORE07 |
| **DAT — leads/contatos/campos** | `read_contact`, `create_contact`, `update_contact`, `add_note`, `archive_contact`, `merge_contact_manual`, `delete_contact_controlled`, `import_contacts`, `export_contacts`, `manage_custom_field`, `read_consent_record`, `update_consent_evidence` conforme contrato da origem | Consulta não implica exportação; mesclagem manual, sem junção automática; consentimento não presumido; arquivo e história não removidos por simples arquivo principal |
| **VEN — funis/oportunidades/propostas/catálogo** | `read_opportunity`, `edit_opportunity`, `transfer_owner`, `read_proposal`, `edit_proposal`, `approve_proposal`, `send_proposal`, `apply_discount`, `export_sales`, `manage_catalog` condicionados a produto e contrato | Editar proposta não autoriza desconto, envio externo, aceite do cliente ou alterar mensalidade Imagine; não presumir VEN completo no Básico |
| **ATD — conversas/WhatsApp/equipe/canais** | `read_conversation`, `assign_conversation`, `transfer_conversation`, `reply_conversation`, `send_template`, `close_conversation`, `view_restricted_note`, `manage_channel`, `view_csat`, `export_conversation` conforme canal e fonte oficial | Ver lead não autoriza conversar; canal/provider e posse humana exigem direito, IA não envia sem autoridade; BSP entrega técnica não fecha atendimento |
| **AGD — agenda/profissionais/serviços/unidades** | `read_schedule`, `create_booking`, `reschedule_booking`, `cancel_booking`, `manage_professional`, `manage_service`, `manage_unit`, `confirm_attendance`, `publish_public_booking` quando elegível | Profissional agendável não ocupa assento de login; link público não vira usuário CRM; Básico agenda manual, 1 profissional, 10 serviços, sem autoagendamento público |
| **AUT — automações nativas** | `read_flow`, `draft_flow`, `edit_flow`, `simulate_flow`, `approve_flow`, `publish_flow`, `pause_flow`, `resume_flow`, `view_execution`, `manage_trigger` dentro da matriz final AUT | Editor de cliente usa motor próprio; n8n interno Imagine não é motor de permissões do cliente; `publish` distinto de `edit`, simulação não gera efeito real |
| **IA/AGT — consumo, assistentes e ferramentas** | `view_ai_summary`, `request_ai_analysis`, `run_approved_ai_action`, `approve_agent_action`, `manage_knowledge_scope`, `view_credits` se recurso contratado | Prompt não delega finanças/segredos; resumos de atendimento restritos aos autorizados; saldo de créditos não concede ação proibida; segregação SEG-01/02 |
| **FIN/CON — SaaS Imagine** | `read_subscription`, `request_upgrade`, `request_cancel`, `manage_payment_method`, `view_invoice`, `grant_free_plan`, `grant_free_extra`, `apply_refund`, `change_entitlement` e provas oficiais separadas | Financeiro do cliente não implica administrador Imagine; plano superior gratuito/extras só por autoridade central e ato próprio, sem cobrança por silêncio; operação VEN ≠ FIN/CON |
| **INT — conexões, importação e exportação** | `connect_provider`, `revoke_connection`, `read_connection_state`, `send_external_command`, `receive_webhook`, `export_data`, `import_data`, `run_migration`, `reconcile_operation` com contratos por operação e parceiro | Conector autenticado não confirma evento de negócio, nem transfere tenant; comando tipado, fonte oficial, idempotência e consentimento/escopo específicos; credencial em cofre |
| **Sites/Portal — Site↔CRM/formulário** | `choose_crm_destination`, `request_link_change`, `activate_lead_form`, `receive_lead`, `deliver_lead`, `review_source` condicionados a contrato oficial | Captura sem destino autorizado proibida mesmo com site publicado/cache; mudança de destino via chamado, sem migração ou redirecionamento implícito |
| **ARQ / Anexos e portabilidade** | `view_or_download_attachment` **única**, `upload_attachment`, `delete_attachment`, `export_data`, `import_data`, `create_backup`, `restore_backup` conforme plano/módulo | Arquivo com malware/bloqueio é inacessível mesmo com permissão; export/backup não expõem dado de outro tenant; quota cheia bloqueia só novos uploads |
| **AUD / Compliance / Suporte excepcional** | `view_audit_entry`, `export_audit_evidence`, `request_support_mandate`, `approve_support_mandate`, `view_support_scope`, `revoke_mandate` por categoria/caso/autoridade | Admin tenant não apaga trilha oficial; suporte Imagine sem mandato não lê dados comerciais; mandatos e evidências de segurança não criam BYPASSRLS irrestrito |

**Nota:** nomes entre backticks são **candidatos semânticos de revisão**, não contratos técnicos ratificados, nomes finais de permissões/rotas/métodos, API disponível ou features implícitas; listas finais por módulo devem ser produzidas pelos seus responsáveis funcionais.

## 4. Propostas CORE02-D06-A/B/C/D para futura manifestação expressa

### CORE02-D06-A — Catálogo tipado por proprietário, recurso, ação, campo e contexto (PROPOSTA)

Exigir que cada módulo proprietário entregue **matriz versionada de recursos e ações** com fonte original, classes de campo CORE02-D04, escopos e operações autorizáveis, finalidade, efeito, contratos e restrições, compatível com CORE02-D05 e CORE01-D07. Um nome de ação sem dono, parâmetro, escopo, operação/efeito e evidência é insuficiente para conceder poder.

**Não aprova:** códigos de rota ou permissão concretos, produto novo, UI, novos campos, política física ou qualquer conteúdo não ratificado pelos donos.

### CORE02-D06-B — Distinção obrigatória entre ações e efeitos oficiais (PROPOSTA)

**Leitura, edição, exportação/portabilidade, compartilhamento externo, aprovação, execução, envio, exclusão, importação e mudança de contrato/financeiro** devem ser verificadas separadamente quando houver efeitos diferentes. A confirmação de negócio é do módulo dono (FIN, VEN, ATD, AGD, CRM/Portal etc.); sucesso n8n, SDK, webhook ou IA não é fonte oficial de conclusão. Preservar a permissão conjunta **visualizar/baixar anexo** como exceção expressa e não dividir por automatismo.

**Não aprova:** criar segunda aprovação universal, criar duas permissões de anexo, exigir pagamento/consentimento novo, deixar qualquer funcionário exportar dados, definir todas as alçadas; CORE02-D07/INT/CORE03 continuam pendentes.

### CORE02-D06-C — Contratos por plano, escopo, ator e canal, sem autoelevação (PROPOSTA)

Exigir para cada ação a matriz **papel/membership/mandato × tenant × plano/entitlement × próprio/equipe/unidade/posse/canal × categoria de dados × estado × finalidade/risco**, inclusive usuários externos, agentes, suporte e integrações, com deny by default. Os direitos comerciais do Básico pago/cortesia não são automaticamente os de plano superior; gratuidade administrativa e extras têm contratos próprios; a mesma identidade pode atuar em várias empresas sem unificá-las. Ações de cliente, IA, n8n ou suporte não podem elevar privilégios por se declararem “técnicas”.

**Não aprova:** valores e franquias finais, papéis definitivos, semântica P1–P4 de grants/denies, novas funções do Básico, segredos, roles RLS ou provedores (CORE02-D05/D09/CORE08/SEG).

### CORE02-D06-D — Aceite funcional por módulo, rastreabilidade e critérios negativos futuros (PROPOSTA)

Cada proprietário funcional precisa confirmar versão, recurso/ação/campo/escopo, resultados oficiais, negativas e exceções, roteiros de homologação **futura** (não executados), responsáveis, finalidade/LGPD e compatibilidade com INT CT-02/03/04, CORE10, R01 e SEG-01/02. Evitar catálogo de permissões paralelo: **um catálogo semântico único**, com dados do proprietário e matriz central de autorização por consumo, sem duplicar motor IAM ou Auditoria Geral.

**Não aprova:** cobertura completa, owners nominalmente designados, evidência de teste, liberação de integração ou fechamento de CORE02-D06.

## 5. Matriz de revisões obrigatórias antes de fechar CORE02-D06

| Bloqueio | Evidência ainda a produzir | Responsável por competência (não pessoa nomeada) |
|---|---|---|
| **CORE02-D04/D05** | Campos reais e classificação por módulo; composição de perfis/negações P1–P4 e escopos | IAM/Segurança + donos de módulos |
| **CORE02-D07/D08** | Risco, MFA/step-up, alçada, confirmação, enforcement Laravel/RLS/API/worker, revogação | Segurança/IAM/Arquitetura |
| **CORE02-D09** | Produtos/recursos/planos, assentos/quotas adicionais, comercial e identidade financeira | CORE08, FIN/CON, Comercial |
| **CORE02-D10/CORE10** | Referências canônicas de testes, owners e evidências futuras, documentação controlada | Homologação/Auditoria Geral |
| **CORE01-D04..D07, CORE03–CORE09** | Tenant/membership, sessão, jobs, suporte, arquivo, times, histórico, segredos e resultado | Proprietários de cada domínio |
| **INT P0-OWN/SEC/CON/ACC, SEG-01/SEG-02** | Autorização por recurso/campo e operação, contratos tipados, ferramentas/agentes e cofre | Segurança, Integrações, proprietários |
| **Portal/Sites/FIN/CON/LGPD** | Titularidade de vinculação/benefício, dados, portabilidade, autorizações e retenções | Portal/Sites, Financeiro, Privacidade |

**Todo domínio listado deverá ser cotejado com sua fonte primária e os aceites específicos**, distinguindo o recurso funcional já aprovado de funções candidatas a validação. O plano de fechamento não oferece funcionalidades novas, não presume direitos de canais e não homologa provedores.

## 6. Exemplos negativos de homologação futura — NÃO EXECUTADOS

| Caso | Resposta requerida em futura homologação |
|---|---|
| Usuário tem `read_contact` mas solicita `export_contacts` com dados protegidos | Verificar exportação e classe do campo, negar o que não estiver autorizado |
| Funcionário pode editar oportunidade, tenta aprovar desconto ou mudar cobrança CRM | Negar por ações e módulos distintos |
| Empregado tem acesso a lead, mas não à conversa WhatsApp ou nota privada | Negar por canal, equipe, posse e direito ATD independente |
| Cliente Básico habilita link de agendamento público ou editor arbitrário AUT | Negar sem entitlement e contrato; não inventar upgrade |
| Agente IA pede segredo ou `grant_free_plan` invocando prompt | Negar sem principal/alçada/escopo, conforme SEG-01/02 |
| Conector recebe webhook assinado e tenta mover lead para tenant não vinculado | Verificar fonte oficial e destino autorizado, negar efeito cruzado |
| Admin central sem mandato tenta abrir dados comerciais tenant | Negar acesso de negócio apesar de função administrativa |
| Export de arquivos de A inclui anexo/backup de B com mesmo nome | Bloquear por tenant/recurso/ação e integridade de vínculo |
| Job reexecuta operação após desligamento da pessoa que a autorizou | Revalidar direitos se forem pessoalmente exigíveis para novo efeito; não reverter fato legitimamente confirmado |
| Cliente com permissão de ver/baixar anexo encontra arquivo bloqueado por malware | Negar conteúdo pelo estado de segurança, sem suprimir permissão conjunta |

Reutilizar CORE02-T, CORE01-T, REV01-S, ATD/VEN/AGD/AUT-T, OPS-T e INT-HT, sem criar suíte paralela; **nenhum teste foi executado**.

## 7. Limites expressos e próximo eixo

**CORE02-D06-A/B/C/D estão apenas PROPOSTAS**, até aceite literal específico. Mesmo se aceitas preliminarmente, **CORE02-D06 continuará ABERTA** até catálogo efetivo por ação/entidade/campo/escopo, proprietários, planos e segurança homologados. CORE02-D04/D05/D07..D10, CORE01-D04..D07, CORE03..10-D04..D10, **SEG-01/02**, IN-168 ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, licenças/custos e baseline final permanecem ABERTOS. **67 IDs técnicos CORE abertos; DEV-GATE-00 NO-GO**.

**Próxima pauta se o responsável aprovar preliminarmente D06-A/B/C/D:** elaborar somente proposta **CORE02-D07** sobre ações sensíveis, MFA/step-up, aprovações e alçadas por operação, sem inventar duas aprovações obrigatórias para tudo nem autorização de programação.

**Nenhuma programação, código, SQL/RLS, teste executável, credencial, pagamento, contrato, ativação de permissões, operação de cliente ou implantação foi realizada ou autorizada.**

## 8. Aditamento — aceite técnico delegado preliminar CORE02-D06 (04/11/2026)

Conforme [CRM-DEC delegada](CRM-DEC-aprovacao-delegada-CORE02-D06-ABCD-preliminar-2026-11-04.md), D06-A/B/C/D foram avaliadas e aceitas **somente como diretrizes técnicas documentais preliminares**, sem atribuir ao responsável uma manifestação literal específica que ele não deu. Elas exigem catálogo por proprietário, ações distintas, matriz contextual de direitos e validação por módulos, **não** provam catálogo real/owners, planos, grants, políticas RLS, política P1–P4 de perfis ou homologação. **CORE02-D04/D05/D06/D07..D10 seguem ABERTAS**, CORE01-D04..D07, SEG-01/02, ARQ/AGT, INT P0/G01..G06, FIN/CON e os 67 IDs técnicos CORE em aberto. DEV-GATE-00 permanece NO-GO para liberação global.
