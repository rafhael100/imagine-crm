# CRM-REV-01 — Núcleo multiempresa, identidade, usuários, permissões e isolamento

**Data:** 2026-11-03. **Status atualizado:** **ACEITA COMO BASE DOCUMENTAL**, conforme [aprovação explícita](CRM-DEC-aprovacao-REV-01-base.md). Os princípios funcionais N-01..13 são referências de revisão; as lacunas CORE-P0-01..10 e as políticas executáveis continuam pendentes. [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) preparada como **minuta não aprovada**.
**Objetivo:** primeira revisão dos módulos CRM-01/02/03 e interseção com CRM-17. Não cria decisões novas nem altera aprovações funcionais existentes. **Nenhum schema, permissão ou autorização é aprovado por este documento**.
**Origem:** capítulos históricos da branch `docs/integracao-site-crm-basico` e documentos `main` listados adiante. Uma seção histórica rotulada APROVADO indica decisão documentada, mas seu cotejo com texto original e decisões posteriores ainda é necessário.
**Restrições:** terminar todo planejamento, obter revisão/aceite final versionado e **só depois programar**. Sem testes, provisionamentos, usuários reais, credenciais, mudanças em produção ou custo.

## 1. Referências consultadas

| Código | Fonte verificável | Pertinência |
|---|---|---|
| LEG-01 | [01 — visão geral e arquitetura](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md) | RLS e tenant_id aparecem em seção explicitamente APROVADA após nota inicial obsoleta de não ratificação. |
| LEG-03 | [03 — Administração Geral Imagine](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md) | Ações críticas, MFA da administração geral, exceções de suporte, auditoria administrativa. |
| LEG-04 | [04 — empresas e usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Empresas, perfis/escopos, equipes, convites, MFA de funcionários e admin tenant, auditoria comercial. |
| LEG-13 | [13 — permissões Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) | Um usuário autenticado por organização Básico, admin da empresa, MFA obrigatório e limites específicos. |
| LEG-15 | [15 — organização CRM para novo site](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md) | Escolha nova/existente organização, isolamento e benefício por contrato, mesmo CNPJ quando elegível. |
| IN-151 | [IN-151 — Credenciais e chaves](IN-151-gestao-inteligente-credenciais-chaves-integracao.md) | Cofre/identidade e separação de segredos; detalhes de provedor/rotação pendentes. |
| IN-167 | [IN-167 — agentes técnicos](IN-167-consolidacao-arquitetura-agentes-tecnicos.md) | Agentes N1/N2/N3 em identidades técnicas e playbooks escopados; sem autoconcessão. |
| IN-168 | [IN-168 — pendências arquiteturais](IN-168-matriz-pendencias-fechamento-arquitetura.md) | ARQ-01/02/03, SEG-01/02, AGT-01 permanecem P0. |
| INT-CT | [P0-OWN-SEC INT](integracoes/P0-OWN-SEC-matriz-recursos-campos-OP24.md) | Classificação de 13 recursos e 24 OP aceita como base, não permissão executável. |
| INT-GATE | [P0-GATE Integrações](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) | G01..G06 não encerrados; P0-OWN/SEC/CON exigem revisão. |
| MASTER | [CRM-MASTER-01](CRM-MASTER-01-indice-modulos-fontes-status.md) | Localização de módulos e classificação do trabalho por área. |

## 2. Regras funcionais registradas que devem ser preservadas na revisão

| ID de cotejo (NÃO novo requisito) | Assunto | Texto funcional localizado nas fontes | Proveniência | Exige detalhar |
|---|---|---|---|---|
| N-01 | Arquitetura SaaS e identidade | Laravel REST + PostgreSQL; tenant_id nos registros de empresa e RLS PostgreSQL como defesa no banco, complementando verificação no Laravel | LEG-01 | Esquemas e política de RLS por tabela/papel/serviço continuam projeto técnico |
| N-02 | Usuário multiempresa | Mesma identidade de login pode participar de várias organizações autorizadas; alternância só entre associações ativas; dados, quotas e permissões são por organização | LEG-01, LEG-04 | Validação em sessões, jobs, APIs e tokens por tenant; não usar tenant_id arbitrário da requisição |
| N-03 | Administração central vs empresa | Admin Imagine e Administrador da Empresa são âmbitos distintos; administrador geral não acessa dados de negócio do cliente automaticamente | LEG-03, LEG-04 | Suporte excepcional exige autorização, finalidade, limitação e auditoria |
| N-04 | Perfis e escopos de visibilidade | Perfis configuráveis por ação/módulo; escopo próprio, equipe ou empresa para dados comerciais; ler, alterar, excluir, transferir e exportar são direitos diferentes | LEG-04 | Matriz exata de ação/recurso/campo ainda não está homologada por módulo |
| N-05 | Equipes e transferências | Usuário pode integrar várias equipes; gestores e integrantes conforme vínculo; transferências de leads/oportunidades preservam histórico | LEG-04 | Modelagem de equipe e alcance efetivo das políticas por operação |
| N-06 | Convites e desligamento | Convite por e-mail seguro válido 7 dias, vínculo de perfil; desligamento revoga acesso/sessões da organização preservando histórico e acesso a outras | LEG-04 | Idempotência, concorrência, reenvio/expiração, auditoria do convite e tokens |
| N-07 | MFA por domínio | Administradores gerais Imagine têm MFA obrigatório; administradores de empresa têm MFA obrigatório; funcionários clientes: MFA opcional por padrão, empresa pode exigir a todos | LEG-03, LEG-04 | Step-up por ação sensível, recuperação específica, políticas por papel e compatibilidade com passkeys |
| N-08 | Sessões e recuperação | Admin geral possui 30 min de inatividade como regra histórica; recuperação de conta com controles, sessões revogáveis e registros de tentativas; recuperação dos demais usuários exige método seguro | LEG-03, LEG-04 | Não propagar automaticamente timeout de 30min do admin geral a todos funcionários |
| N-09 | Auditoria por categoria | Auditoria administrativa geral tem padrão histórico de 5 anos; comercial por empresa padrão 12 meses ativa, imutável pelo admin da empresa; logs técnicos seguem IN-162 | LEG-03, LEG-04, IN-167 | LGPD, exceções legais, expurgo e classificação de categoria precisam validação |
| N-10 | Básico, assentos e segurança | Organização Básico contém 1 usuário autenticado, Administrador da Empresa com MFA; profissionais agendáveis são quota diferente e não viram contas de usuário | LEG-13 | Revisar modalidades de plano/extra sem criar assentos ou privilégio no Básico |
| N-11 | Trial vs múltiplas organizações Básico | Um trial por CNPJ é regra distinta do direito a nova organização Básico por novo contrato de site elegível mesmo CNPJ; sem criação por evento duplicado | LEG-03, LEG-15 | Contrato de provisionamento, titularidade, verificação e múltiplos vínculos |
| N-12 | Agentes e serviço | Agentes técnicos usam identidades limitadas, ferramenta/ação/ambiente/cliente, N2 homologado e aprovação humana N3 quando exigida; jamais herdam autorização de prompt | IN-167, IN-151, INT-CT | Ferramentas reais, conta de serviço/cofre e política por agente estão em IN-168 P0 |
| N-13 | Integrações e campos | Autorização externa do provedor é distinta do direito interno do CRM; um webhook/HTTP 2xx não é autorização ou confirmação de negócio | INT-CT, INT-GATE | Permissões por recurso/campo e contratos de resultado ainda P0 |

**Observação especial RLS:** o mesmo [capítulo 01](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md) registra RLS como “pendente” na seção inicial e depois como **APROVADO**, com `tenant_id` e PostgreSQL Row-Level Security complementado pelo Laravel. **A seção posterior é a referência mais recente dentro do arquivo**; não reapresentar RLS como escolha inédita sem prova de decisão mais recente que a revogue.

## 3. Dimensões de autorização — matriz conceitual (sem direitos concedidos)

| Tipo de ator | Âmbito | Privilégio já descrito historicamente | Limite crítico |
|---|---|---|---|
| Administrador Geral Imagine | Gestão central SaaS | Administrar configurações/empresas conforme direitos, suspender/reativar com justificativa | Sem acesso irrestrito automático a registros dos clientes; MFA obrigatório; acesso excepcional com escopo/auditoria |
| Administrador da Empresa | Uma ou mais organizações com membership efetiva | Administrar usuários, perfis e dados da própria empresa conforme plano | Não é administrador global; MFA obrigatório; operações críticas exigem direitos próprios |
| Funcionário/gestor de equipe | Tenant e equipe próprios | Permissões por módulo/ação; visibilidade própria/equipe/empresa conforme atribuição | MFA opcional por padrão para funcionários, exigível pela empresa; leitura ≠ alteração/exclusão/exportação |
| Único administrador do Básico | Organização Básico elegível | Admin da Empresa, direitos e limites do plano público/beneficiado | 1 usuário autenticado por organização; MFA obrigatório; nova org só por contrato elegível e consentimento |
| Identidade de serviço/agente/integração | Tenant/ambiente/recurso/ação autorizados | Ferramentas técnicas homologadas conforme escopo e versão | Não recebe superusuário, aprovação humana, segredos em claro, nem direitos de negócio só pelo acesso ao provedor |
| Acesso excepcional de suporte Imagine | Empresa/caso autorizados e prazo limitado | Investigar/assistir conforme finalidade e aprovação cabível | Sem bypass genérico de RLS/RBAC e sem persistir após revogação |
| Auditoria/investigação | Empresa/caso/categoria de dado | Consultar evidência oficial onde autorizado | Exportação pode exigir direito distinto; investigação não altera fato de negócio |

**Nota:** a tabela combina limites funcionais das fontes, mas **não especifica nomes finais de papéis ou política física de acesso**. Não criar privilégios novos para “gestor”, “agente” ou “suporte”.

## 4. Dez lacunas P0 do núcleo para futura decisão/especificação

| ID de revisão | Lacuna objetiva | Evidência necessária antes da implementação | Competência de revisão | Base preservada |
|---|---|---|---|---|
| CORE-P0-01 | Modelo canônico global de identidade, usuário, membership, tenant e organização | Aprovar entidades e chaves lógicas, escopo e ciclo de vida, exceções globais, aplicação real de RLS por papel/serviço | Arquitetura + Segurança | N-01/02/03/11 |
| CORE-P0-02 | Matriz exata perfis × recursos × ações × campos | Definir permissões diferenciadas para Admin Imagine, Admin Empresa, funcionários, agentes, apps, suporte, auditoria e cliente Básico | Proprietários de módulo + Segurança | N-03/04/10/12/13 |
| CORE-P0-03 | Ponte de autoridade nas operações assíncronas | Definir preservação e revalidação de tenant/contexto, job/evento/webhook, token, versão e revogação, sem vazamento de dados | Segurança + INT + Filas/Eventos | N-01/02/06/12/13 |
| CORE-P0-04 | Autenticação e recuperação por papel | Detalhar MFA, step-up crítico, recuperação e expiração por perfil, identidade externa, mecanismos e auditoria; não estender sessão admin a todos por analogia | Segurança + Administração Central/Empresa | N-06/07/08 |
| CORE-P0-05 | Acesso excepcional equipe Imagine | Definir autorização por caso, limite, justificativa, aprovação aplicável, janela, auditoria, revogação e evidência sem criar bypass universal | Administração Geral + Segurança/Privacidade | N-03/09 |
| CORE-P0-06 | Segregação de auditoria e retenções | Registrar por categoria autoridade, consulta/exportação, retenção, restrições LGPD, imutabilidade e recuperação; não igualar logs e fatos de negócio | Auditoria Geral + Segurança/Privacidade | N-09/13 |
| CORE-P0-07 | Convites, ocupação de assentos, equipes e desligamento | Estados/códigos e concorrência de convite, reenvio, adesão, perfil, troca de tenant e revogação de sessão por empresa | Administração por Empresa + Segurança | N-05/06/10 |
| CORE-P0-08 | Básico cortesia/contas e trial | Conciliar 1 trial/CNPJ e múltiplas organizações Básico por contratos distintos; política de elegibilidade/ciclo sem cobrança indevida | FIN/CON + Sites/CRM + Segurança | N-10/11 |
| CORE-P0-09 | Contrato técnico de cofre/identidade serviço | Escolher mecanismo/provedor de cofre, credenciais de integrações, autorização agente por ferramenta e ambiente, rotação e revogação | SEG-01/02 IN-168 + INT-02 + IN-151 | N-12/13 |
| CORE-P0-10 | Pacote de homologação documental do núcleo | Especificar casos negativos do isolamento, escalada de privilégio, exportações, desativação, MFA, múltiplas organizações, sem executar testes ainda | Arquitetura + Segurança + Auditoria | N-01..13 |

**Prioridades:** as lacunas acima são **propostas de classificação P0 para o núcleo**. Não alteram automaticamente a IN-168, o painel INT ou decisões de produto. Rever nível/risco e resolvê-las na matriz global antes de liberar implementação.

## 5. Casos negativos documentais para futuros critérios de aceite

| Caso conceitual | Exemplo de risco a validar em fase futura | Resultado esperado |
|---|---|---|
| S01 | Um usuário ligado às organizações A e B tenta usar tenant B em requisição da sessão A | Backend/RLS e escopo de recurso negam; sem confiar em tenant informado externamente |
| S02 | Admin Imagine sem autorização de suporte tenta ler lead de cliente | Negar e auditar; privilégios de operação não implicam leitura de dados do cliente |
| S03 | Administrador de tenant sem MFA tenta executar ação administrativa | MFA exigida segundo política de administrador; não substituir por upsell |
| S04 | Funcionário com MFA opcional da empresa tem seu acesso revogado durante job | Nova ação protegida negada, comandos já transmitidos mantêm resultado próprio e exigem reconciliação |
| S05 | Mesmo CNPJ com trial já utilizado contrata segundo site elegível e escolhe novo Básico | Regras distintas: não conceder novo trial, validar contrato e permitir organização Básico conforme decisão sem cobrança por erro |
| S06 | Uma organização Básico tenta convidar usuário adicional | Quota 1/1 e entitlement de plano prevalecem; sem habilitar usuário indevido |
| S07 | Usuario gestor autorizado a visualizar lead tenta exportar dados pessoais de equipe/tenant alheio | Permissão exportar independente; isolamento por recurso/campo e finalidade |
| S08 | Job n8n/INT com contexto de tenant A processa evento de B | Falha segura, sem misturar tenants; principal de serviço não ganha direitos genéricos |
| S09 | Acesso técnico de suporte autorizado expira mas sessão de suporte tenta continuar | Acesso excepcional cessado, sem abrir direitos persistentes; evidência auditável |
| S10 | Administrador da empresa tenta apagar auditoria comercial | Negar; auditoria oficial protegida, com regime de retenção pertinente |

**Nenhum teste foi executado.** Os casos S01..S10 não criam automaticamente HT-19 ou outra nova família de testes; são cenários para revisão dos critérios de aceitação do núcleo, com homologação após autorização futura da programação.

## 6. Fronteiras com os demais módulos

- **FIN/CON:** alterações de assinatura, plano, benefício Básico, valores e inadimplência pertencem às regras comerciais/proprietários. Admin de empresa não concede plano pago nem acesso só porque gerencia usuários.
- **ATD/VEN/CAT/AGD:** cada módulo protege sua entidade/campo/fato oficial. Membership define fronteira de acesso, não aprovação de qualquer ação de negócio.
- **INT/IN:** conectores, credenciais, orquestração, Webhook e agentes n8n continuam respeitando autorização e tenant sem criar uma autoridade concorrente de IAM.
- **Auditoria:** administrativa Imagine (histórico de 5 anos) e comercial tenant (histórico de 12 meses) são categorias distintas; técnicos IN-162 têm prazos próprios, e tudo depende de conformidade legal. Não generalizar um prazo.
- **Sites/Portal:** novo contrato elegível pode permitir nova organização Básico mesmo mesmo CNPJ, separada do trial; não significa gerar várias organizações por pagamento repetido.

## 7. Nova competência comercial do Administrador Geral Imagine (03/11/2026)

A [decisão funcional aprovada de gratuidade administrativa](CRM-DEC-gratuidade-administrativa-qualquer-plano.md) permite ao **Administrador Geral Imagine** conceder **qualquer plano do CRM gratuitamente a uma organização específica**, sem exigir site ou trial. **Isso não é permissão do Administrador da Empresa**, nem autoriza automações/IA ou agentes de suporte a conceder plano. A matriz efetiva de privilégios, MFA adicional por ato, auditoria, revogação e alçadas continuam pendentes (GRAT-P01..08 e CORE-P0).

## 8. Aprovação da base e continuidade para CRM-REV-02 (03/11/2026)

**CRM-REV-01 foi aceita como BASE DOCUMENTAL DE REVISÃO** dos 13 princípios localizados (N-01..13), dos sete tipos conceituais de atores e das dez lacunas CORE-P0-01..10, **sem aprovar schema físico, perfis, MFA adicional, alçadas, fornecedores ou parâmetros numéricos não decididos**. [Decisão](CRM-DEC-aprovacao-REV-01-base.md).

A [CRM-REV-02 — planos, trial, cobranças e cortesia](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) foi **redigida como proposta para revisão**, com preços de referência preservados e diferenças históricas registradas. Não abrir programação sem revisão final global.

**Situação:** a revisão de fontes do núcleo evoluiu documentalmente; **não fecha** os P0 globais, IN-168, INT G01..G06 nem concede autorização de implementação.
