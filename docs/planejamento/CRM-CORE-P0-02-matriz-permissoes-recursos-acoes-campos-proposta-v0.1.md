# CRM-CORE-P0-02 — Matriz canônica de autorização por ator × recurso × ação × campo (v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE02-D01–D03 APROVADAS COMO BASE DOCUMENTAL** conforme [decisão de aceite CORE02-D01 a D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md); **CORE-P0-02 PARCIAL/ABERTO**. CORE02-D04-A/B/C/D [aprovadas **somente preliminarmente**](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md), mantendo **D04 integral ABERTA** e D05..D10 pendentes. Não é matriz executável homologada, não ativa permissões, não altera planos e não autoriza programação. Seções com rótulo "PROPOSTA D01–D03" registram o estado histórico anterior ao aceite.  
**Sequência:** CORE-P0-01 D01–D03 aceitas como base lógica em [CRM-DEC](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md), preservando D04–D07. Esta proposta inicia CORE-P0-02, sem antecipar aceite do responsável.  
**Objetivo:** reconciliar identidade global / memberships por tenant e direitos existentes, para posteriormente fechar contratos por módulo, campos, planos, agentes e operações externas sem criar motor IAM adicional.

## 1. Fontes e precedências

- [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) N-01..13 e 10 CORE-P0; [CRM-DEC REV-01](CRM-DEC-aprovacao-REV-01-base.md) aceita como **base**, não matriz de permissões física.
- [CORE-P0-01](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md) e [CRM-DEC D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): identidade global, organização CRM como tenant lógico, memberships e isolamento Laravel + PostgreSQL RLS.
- [LEG-03 Administração Imagine](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md): administradores gerais com MFA, direitos centrais configuráveis, suporte excepcional auditado, sem acesso irrestrito a dados comerciais de clientes; ação crítica exige confirmação pelo **mesmo administrador autorizado**, sem impor segunda aprovação administrativa por padrão.
- [LEG-04 Empresas e Usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md): admin principal, administradores adicionais quando o plano permitir, funcionários, equipes múltiplas, RBAC por módulo/ação, escopos próprio/equipe/empresa, convites de 7 dias, MFA e exportação separada.
- [LEG-13 Matriz Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md): IN-020..32, 039..041; um único administrador autenticado no Básico, direitos específicos para leads/contatos, importação/exportação, arquivo, mesclagem e exclusão controlada; permissão **única de ver/baixar anexos** (IN-040).
- [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md), [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md), [CRM-REV-06](CRM-REV-06-vendas-funis-propostas-catalogo.md), [CRM-REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md), [CRM-REV-08](CRM-REV-08-agentes-ia-consumo-conhecimento-supervisao.md), [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md): bases funcionais aceitas, **ainda com lacunas**.
- [P0-OWN-SEC INT](integracoes/P0-OWN-SEC-matriz-recursos-campos-OP24.md), [P0-SEC CT-02](integracoes/P0-SEC-politicas-autorizacao-CT02.md): classificação conceitual de operações e campos, **sem grants reais aprovados**.
- [Gratuidade administrativa](CRM-DEC-gratuidade-administrativa-qualquer-plano.md): somente administrador geral Imagine autorizado pode conceder qualquer plano gratuito; **GRAT-P02** alçadas e step-up ainda pendentes.
- [IN-118](IN-118-central-backup-portabilidade-dados.md): portabilidade/backup autoatendidos para todos os planos; não conceder acesso a dados fora do próprio tenant.

**Regra:** decisão posterior e mais específica prevalece onde comprovada. Não misturar preço de plano, assento, limite técnico, recurso disponível, permissão do papel e autorização sobre registro específico.

## 2. Modelo de avaliação de direito — proposta CORE02-D01

**Decisão de referência PROPOSTA:** um ato só será autorizado quando TODOS os fatores aplicáveis forem válidos:

1. Ator humano ou `service_principal` autenticado, origem confiável e credencial vigente.
2. Tenant efetivo resolvido e vínculo ativo (ou mandato excepcional válido e escopado), sem confiar em `tenant_id` arbitrário do navegador, formulário, webhook ou IA.
3. Situação da organização e do serviço/plano/benefício/trial compatível com a operação; recurso incluído e cota/canal/crédito elegível quando aplicável.
4. Papel/escopo efetivos no tenant (ou mandato/credencial técnica) com autorização positiva para **recurso + ação + campos afetados**, no âmbito do próprio usuário, equipe, unidade, empresa, caso ou objeto, conforme o módulo.
5. Regras obrigatórias do **módulo proprietário** atendidas (status, posse da conversa, consentimento/finalidade, versão, conflitos, reserva, pagamento, aprovação, etc.); autorizar não confirma o resultado do negócio.
6. Revalidação na execução e antes de efeitos sensíveis/assíncronos, com auditoria proporcional e falha segura quando identidade, plano, vínculo ou contexto forem inválidos.

**Negação por padrão:** ausência de permissão explícita, campo sensível não liberado, cota/sistema bloqueado, tenant inexistente, concessão revogada, token vencido ou serviço não homologado = negar **o ato específico**, preservando operações internas obrigatórias não afetadas. Restrições de segurança nunca são exibidas como simples oportunidade de upsell. Configuração de UI não substitui autorização server-side.

**Importante:** o direito efetivo não é "a soma permissiva" de múltiplos papéis ou organizações; direitos positivos são submetidos aos bloqueios mandatórios por tenant/escopo/plano/recurso/risco. Política de precedência de perfis, negações explícitas, herança e atributos ainda precisa fechar CORE02-D05 (não criar grants implícitos).

## 3. Vocabulário conceitual de ações — PROPOSTA CORE02-D02

| Família | Direitos distintos a controlar | Nota sobre escopo/aprovação |
|---|---|---|
| Leitura e consulta | `list`, `read`, `search`, `view_history`, `view_report` | Campos e registros filtrados server-side; ver item não autoriza exportá-lo |
| Criação e atualização | `create`, `update`, `archive`, `reactivate`, `assign`, `transfer` | Propriedade, atribuição, etapa, unidade, privacidade e auditoria |
| Ato protegido | `request_delete`, `merge`, `approve`, `reject`, `reopen`, `publish`, `suspend`, `revoke` | Confirmação humana/competência de negócio conforme caso; não mesclar ações irreversíveis |
| Importação/portabilidade | `preview_import`, `commit_import`, `export_data`, `request_backup`, `restore_data`, `migrate_data` | Destino e fonte autorizados; geração, disponibilização e download revalidam direitos. Portabilidade essencial não depende de upsell |
| Comunicação/automação | `create_draft`, `activate`, `pause`, `trigger`, `send_external_message`, `manage_channel`, `approve_effect` | Mensagem externa, modelo de chatbot, IA e automação comercial têm direitos/controles distintos; não equiparar slot ativo a canal pago |
| Administração/contratação | `manage_users`, `manage_roles`, `manage_tenant`, `accept_purchase`, `manage_subscription`, `grant_free_plan`, `grant_free_extra` | Alteração comercial do CRM é FIN/CON; gratuidade Imagine central e adicionais específicos são atos separados |
| Segurança/auditoria | `manage_own_sessions`, `manage_mfa`, `view_audit`, `export_audit`, `authorize_support_case` | Não apagar/truncar trilha; leitura de auditoria ≠ exportação da auditoria |
| Anexos privados | **`view_or_download_attachment`**; `upload_attachment`, `delete_attachment` | **IN-040 já aprovou uma única permissão conjunta de visualizar/baixar**, não duas permissões comerciais separadas; upload/exclusão são operações diferentes |

Os identificadores são **nomes semânticos candidatos**, não endpoints, enums de banco, policies executáveis nem concessão de novas capacidades.

## 4. Famílias de atores — preserve direitos e distinga PROPOSTO de APROVADO

| Ator conceitual | Competências históricas aprovadas / referência | Proibições e fronteiras obrigatórias | Pendente CORE02-D03 |
|---|---|---|---|
| **Administrador Geral Imagine** | Gerir empresas/configurações centrais segundo permissão própria; conceder gratuitamente plano específico quando autorizado; MFA obrigatório; ações críticas auditadas | **Sem leitura universal automática** de dados comerciais dos clientes; não atuar sob tenant sem mandato legítimo; concessão não dá extras automáticos | Matriz granular: quem concede plano/extra, quem suspende, exporta relatório global, consulta evidências e quais atos exigem step-up/alçada |
| **Administrador da Empresa (planos elegíveis)** | Admin principal/adições conforme plano: convidar/remover funcionários, atribuir papéis, gerir funções e dados do **próprio tenant**, MFA obrigatório | Sem função global Imagine, sem poder revogar políticas centrais ou auto-conceder plano/preço/gratuidade; não ignora classificação de campos ou limitações comerciais | Catálogo de funções administrativas por entidade, campo, unidade, filial e operações críticas |
| **Administrador único do Básico** | **1 usuário autenticado por organização**, MFA obrigatório; ver/editar cadastros da organização; leads/contatos, observações, arquivamento, mesclagem manual e exclusão protegida; CSV/XLSX e Agenda manual nos limites aprovados | **Não incluir segundo usuário**, administração de outras empresas, editor livre de automação, autoagendamento público, WhatsApp/IA premium por presunção | Fechar classe de campos/capacidades específicas do Básico e casos especiais de recuperação/troca do único administrador |
| **Funcionário / vendedor / gestor** | Perfis configuráveis por ação/módulo; visibilidade própria/equipe/empresa conforme vínculo; pode pertencer a múltiplas equipes; MFA opcional por padrão ou obrigatório por política da empresa | Cargo de "gestor" ou "financeiro" é rótulo funcional, **não superusuário** nem garantia automática de exportação, desconto, cancelamento, acesso a canal ou histórico de toda empresa | Matriz de atribuições por papel/configuração de equipe, unidade, status e perfil customizado |
| **Agente comercial de IA** | Sugestões e ações em ferramentas explicitamente autorizadas, conforme recurso do tenant/crédito/canal; dados e versões isolados | Não obtém direitos de um prompt, não altera plano, concede gratuidade, aceita pagamento ou lê outro tenant; posse humana bloqueia envio externo indevido | Manifesto ação × recurso × campo × tenant × limite por agente/modelo, com revisão humana quando exigida |
| **Identidade técnica, job e integração** | Operações tipadas por escopo e versão, credencial própria e cofre; INT-02 valida autorização externa separadamente | n8n, webhook autenticado e API com token válido **não** recebem privilégios de negócio universais; sem alterar entidade/fato oficial sem contrato proprietário | Matriz de serviço por ferramenta/operação e ciclo de revogação, CORE-P0-03/09 e INT P0 |
| **Suporte excepcional Imagine** | Acesso a caso autorizado, finalidade, dados mínimos, janela limitada e auditoria | Não criar perfil persistente com bypass geral de RLS/tenant; necessidade de suporte não prova consentimento nem direito de leitura | Aprovação/mandato por caso e limites finais em CORE-P0-05 |
| **Auditoria/investigação autorizada** | Consulta de evidências conforme categoria, finalidade, escopo e necessidade | Direito de investigação **não** autoriza editar fatos, apagar logs ou exportar todo conjunto sem direito próprio | Catálogo de consulta/exportação por categoria, CORE-P0-06 |
| **Usuário externo de link/portal de agendamento/proposta** | Pode praticar somente ato externo especificamente previsto no módulo (ex.: aceitar proposta da versão exata, reagendar por link válido) | Link de cliente não cria login CRM, membership, privilégio para gestão da empresa ou consulta global | Contratos de token escopado e visibilidade por módulo VEN/AGD, não tratar como usuário CRM |

**Nota:** a linha de ator é uma família/limite documental, **não** a criação de nove papéis automáticos com poderes atribuídos. Não alterar a regra do Básico pela existência de papéis nos planos superiores.

## 5. Matriz cruzada de recursos, campos e operações — REFERÊNCIA PARA COMPLETAR

Legenda: **H** = regra histórica aprovada, ainda sem schema final; **C** = condicionado a direito/contrato/módulo/plano; **P** = especificação proposta que exige aceite; **N** = vedado conforme base. Sem H/C específico não há grant.

| Recurso / autoridade | Competências do administrador de tenant | Funcionário/equipe | Imagine / serviços / terceiros | Campos e restrições |
|---|---|---|---|---|
| **IAM / associação, usuários, equipes, perfis** | **H/C** administrar dentro do plano e tenant; Básico não pode ocupar 2º assento | **C** direitos personalizados sem autoconcessão | Imagine mantém identidades globais e administração central separadas; serviços escopados | E-mail de acesso, MFA, convites, sessões, membership e permissões; segredos jamais exibidos |
| **FIN/CON do CRM e concessões** | **H/C** consultar plano/quotas, upgrade voluntário expresso; sem gratuidade administrativa | **C** ler/agir apenas se papel financeiro habilitado | **H** administrador geral autorizado concede isenção de qualquer plano; extras gratuitos por ato separado | Meio de pagamento/token, histórico fiscal, benefícios e cobrança não equivalem a VEN do cliente |
| **DAT — lead, contato, observações, campos** | **H** Básico pode criar/ler/editar/arquivar, mesclar manualmente e solicitar excluir com proteção; campos personalizados conforme plano | **H/C** leitura/escrita por próprio/equipe/empresa e ação; import/export distinto | Sites/integrador entrega somente a tenant oficialmente vinculado; agente sugere se autorizado | PII, origem, consentimento, campos protegidos, classificação, histórico; não mesclar por detecção automática |
| **VEN / oportunidades, proposta, tarefas, catálogo** | **C** por plano/funcionalidade e direito do módulo; não atribuir VEN completo ao Básico | **H/C** posse e escopo comercial, ações separadas de ver, editar, aprovar, enviar, desconto, exportar | IA é consultiva até comando validado; FIN não recebe pagamento por aceite de VEN | Etapa, valores de proposta, preço/desconto, motivo de perda, versão aceita e histórico |
| **ATD / WhatsApp e conversas** | **C** por canal/provedor/plano e equipe, não presumir no Básico | **C** visibilidade por número, equipe, posse humana, estado e finalidade | IA/chatbot só podem enviar se canal/controles e posse permitirem; provedor confirma mensagem, não negócio | Conteúdo de mensagem, mídia, notas internas e confidencialidade da equipe; direito de ver lead ≠ conversa |
| **AGD / agenda, profissional, serviço, unidade** | **H** Básico apenas agenda manual interna, 1 profissional agendável, até 10 serviços; demais módulos conforme plano | **C** por unidade/profissional/status; somente funcionário habilitado marca presença | Cliente por link específico pode agir no agendamento se plano e política autorizarem, sem membership CRM | Dados de cliente/dependente, saúde se houver, horários privados, restrições de acesso por unidade; evitar dupla reserva |
| **AUT / fluxo, gatilho, ação** | **H/C** templates específicos Básico; editor comercial por plano e catálogo ainda pendente; direitos de publicar/pausar separados | **C** criar/editar/publicar conforme direito e cota; sem superar papel | Motor próprio do CRM; n8n interno não é editor oculto das automações do cliente | Definição e execução versionadas; não usar gatilhos para contornar direitos financeiros/segurança |
| **IA / base de conhecimento, créditos e resumos** | **C** acesso a configurações permitidas pelo plano e agentes/limites próprios | **C** consultar resumos versionados somente dentro dos direitos de atendimento e conhecimento | Agentes técnicos Imagine têm domínios separados dos agentes comerciais; nada de autoconcessão | Fontes, prompt/versionamento, consentimentos, resumos internos, informações pessoais, saldo |
| **ARQ / anexos e arquivos privados** | **H/C** ver/baixar via permissão conjunta; fazer upload/exclusão conforme módulo e quota | **C** ver/baixar conjunto, upload e excluir somente onde autorizado | Storage e antimalware são serviços técnicos sem acesso comercial genérico | Anexo privado, MIME, estado de segurança, bloqueio/quarentena, 5MB por arquivo, franchise do tenant |
| **INT / conexões, webhooks, exportação e migração** | **C** dentro do plano/conector, autorização e contrato de usuário/tenant; IN-118 portabilidade essencial todos os planos | **C** exportar/importar/backup conforme direitos de origem/destino | Técnicos executam, não confirmam negócio; provedor externo não cria grants internos | Credenciais ocultas no cofre; campos pessoal/sensível protegidos; vínculo site↔tenant não alterável por visitante |
| **AUD / auditoria e relatórios** | **H/C** acesso a relatórios da própria empresa conforme módulo; admin tenant nunca modifica/apaga trilha oficial | **C** view/export separados, mínimo necessário | Administração/segurança/auditoria consultar por caso e direito, sem leitura cruzada universal | PII mascarada, prova de consentimento, trilha íntegra; retenções por categoria, não uma política universal |
| **PORTAL/SITES → CRM** | **H/C** escolher destino existente autorizado ou novo tenant elegível, com confirmação; troca posterior via chamado | **C** representante legítimo quando direito comprovado | Portal valida contrato e destino, CRM confirma o fato recebido; n8n não altera vínculo por dedução | Contrato/site/form_id, consentimento, CNPJ, tenant de destino e origem rastreável; sem criação de tenant por clique duplicado |

**Nota de precisão:** os direitos detalhados do Básico são os das IN específicas e não equivalem a permissões livres no editor. O nome do módulo na tabela não autoriza recurso fora do plano, nem direitos de todos os campos.

## 6. Classificação de campos e visibilidade — PROPOSTA CORE02-D04

A classificação serve à matriz de autorização, não cria dados pessoais adicionais:

| Classe de referência | Exemplos | Regras candidatas |
|---|---|---|
| **F-ID** — identificadores/escopo | tenant_id, identity_id, membership_id, IDs de registro, versão, vínculos | Não editáveis livremente; máscara do identificador não substitui validação de tenant/objeto |
| **F-BIZ** — dados operacionais comuns | status comercial, tarefa, descrição de serviço, prioridade, campos de catálogo | Por módulo/ação, próprio/equipe/unidade/empresa, sem ultrapassar posse/estado |
| **F-PII** — dados pessoais | nome, telefone, e-mail, mensagens, histórico de clientes | Minimização por função e finalidade; leitura, busca, uso IA e exportação exigem verificações adequadas |
| **F-RES** — dados pessoais sensíveis/confidenciais | informações especialmente protegidas, notas internas restritas, dados de atendidos | Não disponibilizar em exportações/relatórios/agentes por simples direito ao cadastro; classificar por finalidade e necessidade |
| **F-CONS** — finalidade/consentimento | status de opt-in, evidência de aceite, origem e data | Alteração sujeita a prova e autoridade; não marcar consentimento por inferência |
| **F-FIN** — valores e dados financeiros | valor/desconto de VEN vs fatura/benefício CRM FIN/CON | Direitos diferentes; gerir proposta cliente não concede mudar mensalidade do SaaS |
| **F-SEC** — credenciais e segurança | senhas, tokens, refresh tokens, MFA, segredos e chaves | Cofre/escopo, valores não devolvidos em texto claro, não exportados, não apresentados à IA |
| **F-EVID** — auditoria/evidências | antes/depois protegido, identidade, operação, IP e evento | Consulta/exportação diferenciadas; sem edição/apagamento por admin do tenant |
| **F-ART** — artefatos/anexos | anexo, prévia, derivado, relatório exportado, backup | Não há leitura pública por URL; permissão conjunta ver/baixar; análise antimalware e bloqueios prevalecem |

**Critérios de desenho:** filtragem e máscara devem ocorrer no backend, inclusive filtros, pesquisa, relatórios, CSV/XLSX e serialização de API; ver um dado em tela não concede acesso por integração, IA, webhook ou download indireto.

## 7. Travas do Básico e direitos já aprovados — não alterar implicitamente

1. **Um usuário autenticado, administrador com MFA** por organização Básico. A mesma identidade pode acessar outras organizações por memberships distintos; isso não amplia assentos do Básico original.
2. **Leads e contatos:** criar, consultar, editar, observações, arquivar; identificar duplicados sem fusão automática; mesclar **manual e expressamente** com escolha principal/conflitos; excluir definitivamente apenas cadastro elegível mediante confirmação e proteção de vínculos. Origem manual/formulário preservada.
3. **CSV/XLSX Básico:** importar com prévia/mapa/validação/alerta; exportar os registros autorizados ou filtrados de leads/contatos, auditando. Portabilidade essencial IN-118 permanece disponível, sem ampliar a exportação comercial para módulos premium.
4. **Agenda Básico:** manual, interna; um profissional agendável e até dez serviços; não abrir agenda pública, link/cliente, chatbot/WhatsApp de marcação automática. Avisos essenciais 24h/1h internos não consomem slot de automação.
5. **Anexos:** mesma lista de tipos dos planos pagos, limite 5MB por arquivo, quota Básico 1GB; visualização e download **uma só permissão**; novos uploads paralisados na quota 100%, leitura e dados estruturados preservados. Sem public URLs.
6. **Automações:** biblioteca de modelos elegíveis (incluindo LEA-01/02 em fechamento individual) distinta de editor arbitrário. Não presumir WhatsApp, canal, IA, agente, webhooks, campanhas ou cota final sem aprovação da matriz AUT.
7. **Direito de upgrade:** mostrar opções conforme recurso ausente e aceitar compra voluntária; não converter violação de segurança em mensagem de upgrade ou cobrar silenciosamente.
8. **Plano Básico pago ou cortesia por site:** mesmos recursos da modalidade Básico, com elegibilidade financeira diferente. Gratuidade administrativa de plano superior entrega recursos do plano superior escolhido, mas extras só por concessão separada.

## 8. Regras de criação/edição de papéis e dos campos — especificação pendente

- **Papéis por organização:** um administrador elegível pode cadastrar perfis customizados dentro de funções disponíveis em seu plano; não conceder atribuição superior à competência autorizada nem criar papel global Imagine.
- **Visibilidade:** próprio/equipe/empresa (LEG-04); em Agenda/Atendimento incluir dimensões unidade, profissional, número/caixa de entrada, posse e caso, mas **sem automaticamente converter em permissão extra**.
- **Permissão de campos:** a definição de campo personalizado por cliente não concede acesso a dados protegidos, consentimentos, credenciais ou cobrança; atributo pode exigir classe protegida e revisão, inclusive para importações e API.
- **Negação explícita/precedência de papéis:** escolher em CORE02-D05 se negar explícito vence grant e como tratar múltiplos perfis, sem alterar os direitos históricos aprovados.
- **Delegação:** ninguém pode conferir poder global/financeiro a si mesmo por editar o próprio papel; separar criar perfil, atribuir perfil e elevar a própria permissão, com controle de autoelevação.
- **Sessões e jobs:** após desativação de membership, revogar direitos do tenant; workflows/retentativas não mantêm autorização histórica para novos efeitos. Conciliar CORE-P0-03/04/07.
- **Admin único do Básico:** transferência/recuperação do responsável precisa fluxo protegido; não criar segundo usuário operacional temporário por suposição.

## 9. Casos negativos documentais para futuros testes (NÃO EXECUTADOS)

| ID | Tentativa | Critério de recusa |
|---|---|---|
| CORE02-T01 | Administrador Imagine sem mandato abre lead cliente | Negar consulta, sem bypass RLS |
| CORE02-T02 | Usuário de tenant A conhece ID do contato B | Negar, mesmo tendo ação `read` em A |
| CORE02-T03 | Funcionário pode ver lead mas tenta exportar lote | Exigir `export_data` e escopo/campos adequados |
| CORE02-T04 | Funcionário tenta editar papel próprio e atribuir permissão proibida | Negar autoelevação |
| CORE02-T05 | Gestor de equipe A acessa nota confidencial da equipe B | Negar por escopo/posse/classe de campo |
| CORE02-T06 | Administrador Básico tenta adicionar 2º login | Negar pela vaga 1/1, não por falta de MFA |
| CORE02-T07 | Básico tenta ativar editor arbitrário ou WhatsApp externo via API | Negar sem entitlement/canal/ação próprios |
| CORE02-T08 | IA tenta emitir proposta com desconto sem aprovação de VEN | Negar; sugestão não é ato oficial |
| CORE02-T09 | Funcionário com permissão de ver/baixar anexo tenta ver arquivo bloqueado por malware | Bloquear conteúdo/derivados conforme estado de segurança |
| CORE02-T10 | Usuário vê registro, mas exportação inclui campo sensível mascarado em tela | Exportação deve manter restrição de campo |
| CORE02-T11 | Admin empresa tenta conceder Empresarial gratuito à própria empresa | Negar; poder é Imagine com autorização central |
| CORE02-T12 | Suporte Imagine continua lendo dados após expirar mandato | Negar, log de incidente/segurança conforme política |
| CORE02-T13 | Job conserva perfil antigo após revogação | Revalidar e negar novo efeito, sem desfazer fato oficialmente concluído |
| CORE02-T14 | Cliente de agenda pública tenta consultar lista de clientes CRM | Token externo não cria membership ou permissões CRM |

Reaproveitar S01..S10 de CRM-REV-01 e planos HT/INT quando equivalentes: **não criar suíte redundante nem executar teste agora**.

## 10. Aprovações propostas ao responsável e pendências ainda abertas

### Para aceite da base lógica (não de grants físicos)
- **CORE02-D01 — PROPOSTA:** aprovar o **modelo cumulativo de autorização** da seção 2: identidade + vínculo/mandato + tenant + entitlement + papel/ação/recurso/campo + regras do dono do negócio + revalidação e auditoria, com negação por padrão.
- **CORE02-D02 — PROPOSTA:** adotar o **catálogo semântico de ações** da seção 3 como base de contratos, preservando permissão conjunta ver/baixar anexos IN-040 e direitos distintos de exportação, envio externo, aprovação, exclusão, finanças e auditoria.
- **CORE02-D03 — PROPOSTA:** aceitar a **matriz de famílias de atores e fronteiras** da seção 4 e a matriz cruzada da seção 5 como **referência documental**, sem conceder papéis, permissões ou funcionalidades além das aprovadas.

### Pendências que o aceite D01–D03 NÃO encerra
- **CORE02-D04 — PENDENTE:** classificar o inventário final de campos por módulo e recurso, incluindo mascaramento e campos personalizados; seção 6 é proposta candidata.
- **CORE02-D05 — PENDENTE:** política efetiva de combinações de perfis (múltiplos perfis/negações, delegação, herança), matriz por papel real e isolamento entre equipes/unidades, inclusive quem pode atribuir permissões.
- **CORE02-D06 — PENDENTE:** validar ações específicas por cada módulo proprietário DAT/VEN/ATD/AGD/AUT/IA/FIN/CON/INT/Sites; implementar direitos de planos apenas após revisão final de entitlements.
- **CORE02-D07 — PENDENTE:** contratos de ações sensíveis: MFA/step-up, confirmação, alçadas e eventual segunda aprovação só quando já exigida pelo fluxo específico. **Admin Geral LEG-03 não exige segunda aprovação universal.** CORE-P0-04/05/08, GRAT-P02 e INT CT-02 responsáveis.
- **CORE02-D08 — PENDENTE:** políticas físicas (nomes finais de permissões, versionamento, consistência de revogações, cache, RLS por tabela, API/worker, provas de isolamento); CORE-P0-01/03/07/09.
- **CORE02-D09 — PENDENTE:** catálogo por ação/valor/canal no Básico e por plano, incluindo o fechamento comercial final e AUT-MAT01; não conceder direitos por analogia.
- **CORE02-D10 — PENDENTE:** critérios e responsáveis pelos testes futuros, trilha/auditoria por categoria e evidências para gates CORE/SEG/INT/G01–G06; teste real só na fase autorizada.

**Critério de fechamento de CORE-P0-02:** revisão e aceite de contratos por recurso/ação/campo e dos controles executáveis de negação, nenhuma contradição com Básico, módulos, segurança, IN-118, IN-040, INT CT-02, gratuidade Imagine e CORE-P0-01. **O aceite da base documental não encerra CORE-P0-02**.

## 11. Regra de processo

Esta proposta **não modifica os documentos históricos**, não concede permissões novas, não altera preço/cota, não cria novo motor IAM, não habilita n8n/IA para uso personalizado do cliente e não libera Codex. Somente após o responsável aprovar D01–D03 registrar decisão expressa e continuar o fechamento das dependências em sequência.

## 12. Aditamento — aceite expresso D01 a D03 (04/11/2026)

O responsável **aprovou CORE02-D01, D02 e D03 como base documental**, conforme [decisão de aceite CORE02-D01 a D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md). A validade do aceite limita-se ao modelo cumulativo de autorização, catálogo semântico de ações e matriz conceitual de atores/fronteiras; não transforma exemplos em permissões concedidas. **CORE02-D04 a D10 permanecem abertos**, inclusive classificação física de campos, direitos efetivos, regras de papéis, alçadas, contratos e cenários de aceite. Sem programação ou testes. Autorizado iniciar a documentação CORE-P0-03. Esta seção prevalece sobre a formulação histórica que solicitava votação D01–D03.


## 13. CORE02-D04 preliminar aprovada e CORE02-D05 em proposta (04/11/2026)

- O responsável [aprovou CORE02-D04-A/B/C/D](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md) como **diretrizes técnicas documentais preliminares**, preservando **CORE02-D04 ABERTA** por catálogo real, permissões por módulo, dependências CORE01, CORE02-D05..D10, SEG-01/02, INT e Segurança. As antigas expressões de “CORE02-D04 PENDENTE” devem ser lidas como **pendência integral de fechamento técnico**, não pendência do aceite A/B/C/D.
- Preparada [**CORE02-D05 — proposta de composição de perfis, grants/denies e delegação**](CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md), com D05-A/B/C/D **NÃO APROVADAS**. A política final de precedência de perfis, negações e herança continua por decidir; não presumir que qualquer deny sempre prevalece, nem autorizar autoelevação.
- **CORE01-D04..D07, CORE02-D04..D10, CORE03..10-D04..D10 e 67 IDs técnicos** seguem ABERTOS, assim como IN-168 SEG-01/02, ARQ/AGT, INT P0/G01..G06 e gate global. Nenhuma programação, teste, grant real, operação de banco ou implantação.
