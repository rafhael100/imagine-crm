# CRM-CORE-P0-01 — Identidade, organizações, memberships e isolamento RLS (v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE01-D01, D02 e D03 APROVADAS COMO BASE LÓGICA DOCUMENTAL**, conforme [CRM-DEC — aprovação CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md). **CORE-P0-01 permanece PARCIAL/ABERTO** para D04–D07 e especificação executável. As passagens abaixo que indicam pendência de voto D01–D03 são a redação histórica anterior ao aceite.  
**Fase:** planejamento exclusivo. Sem programação, alteração de infraestrutura, execução de testes, contratação ou implantação.  
**Escopo:** primeiro dos dez CORE-P0 do [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md). Não reabre o que foi aprovado anteriormente nem substitui CORE-P0-02..10, SEG, INT, FIN/CON e IN-168.

## 1. Fontes e precedência conferidas

1. [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) e seu [aceite como base](CRM-DEC-aprovacao-REV-01-base.md): N-01/02/03/10/11 e CORE-P0-01..10. O aceite NÃO resolveu o modelo lógico/físico.
2. [LEG-01 — Visão e arquitetura](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md): na seção posterior **"Isolamento multiempresa no PostgreSQL — APROVADO"**, tenant_id + RLS complementado por Laravel é decisão vigente, superando a seção inicial do mesmo arquivo que o chamava pendente.
3. [LEG-03 — Administração Geral](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md): separação de papéis e sem acesso automático irrestrito a dados de clientes.
4. [LEG-04 — Empresas e usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md): login multiempresa, administrador principal, convites, escopos e revogação por vínculo.
5. [LEG-13 — Permissões Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md): 1 usuário autenticado por organização Básico, administrador com MFA; profissionais agendáveis não são assentos de login.
6. [IN-117 — Escolha de organização por site](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/15-in117-escolha-crm-por-novo-site.md): cada novo site com contrato elegível pode originar organização Básico independente inclusive sob mesmo CNPJ, por escolha expressa e verificação, mantendo login reutilizável; múltiplos sites na mesma organização não somam franquias.
7. [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md), [IN-151](IN-151-gestao-inteligente-credenciais-chaves-integracao.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) e [painel P0 INT](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md): SEG/INT/autorizações e contratos continuam pendentes.

**Regra de leitura:** aprovado funcionalmente ≠ schema físico/contrato homologado. Esta proposta não cria aprovação automática.

## 2. Premissas já aprovadas — preservar, não votar de novo

- CRM Imagine é SaaS Laravel + PostgreSQL; entidades pertencentes a organizações usam tenant_id, salvo exceções globais formalmente modeladas; RLS no PostgreSQL e RBAC/autorização no Laravel são camadas complementares.
- Uma identidade de login pode estar vinculada a várias organizações autorizadas, alternando apenas entre vínculos ativos; login compartilhado NÃO compartilha bases de negócio, capacidades, permissões, assinaturas ou franquias.
- Administração Geral Imagine é plano de controle distinto da Administração da Empresa, sem leitura irrestrita de dados comerciais por ser operador central; suporte excepcional exige caso, finalidade, alçada e auditoria.
- Identidade, CNPJ/pessoa jurídica, contrato de site, benefício Básico, assinatura CRM paga e organização CRM são conceitos diferentes.
- Mesmo CNPJ não impede organizações segregadas por site elegível; múltiplos sites vinculados à mesma organização não criam quotas acumuladas. Um Básico tem 1 usuário autenticado por organização; a mesma identidade pode ocupar uma vaga em organizações distintas.
- Nenhum identificador de tenant informado livremente pelo cliente, visitante, webhook ou workflow pode conceder acesso ou determinar destino de dados sem confirmação autorizada pelo backend.

## 3. Modelo lógico de referência PROPOSTO (não schema/tabelas homologados)

| Entidade conceitual | Chave lógica proposta | Significado e fronteira |
|---|---|---|
| Identidade humana global | `identity_id` | Principal autenticável do CRM, independente da organização; pode ter diferentes memberships; segredos e métodos de login fora dos registros comerciais dos tenants. Não inferir SSO Portal↔CRM sem contrato próprio. |
| Organização CRM / tenant | `tenant_id` | Limite primário de dados, direitos de plano, franquias, billing e logs de negócio. **Proposta:** uma organização CRM corresponde a um tenant lógico estável; várias organizações podem existir para o mesmo CNPJ. |
| Vínculo de participação | `membership_id` com `identity_id, tenant_id` | Associação autorizada e seu ciclo de vida, independentemente da identidade; desativar em uma organização não apaga identidade nem memberships nas demais. |
| Atribuições de papel/escopo | `membership_id` + vínculo a papéis escopados | Liga ator aos direitos do módulo/recurso/campo; permissão efetiva vem do CORE-P0-02, não da simples existência de membership. |
| Representação legal/empresarial | referência própria à pessoa jurídica/CNPJ | Identifica titularidade e legitimidade do contrato quando aplicável, **não é chave de isolamento nem concessão automática de acesso**. Contrato físico com CNPJ e modelo da entidade ficam em CORE-P0-08/FIN. |
| Vínculo Site↔organização CRM | `site_id`, contrato/versão, `tenant_id` autorizado | Relação versionada e auditada do Portal/Sites com uma organização de destino por vez, jamais seleção implícita pelo e-mail/CNPJ. Criação do tenant e concessão de cortesia dependem de evento oficial, autorização e idempotência. |
| Identidade técnica/serviço | `service_principal_id` + ambiente/escopo | Não se confunde com identidade humana nem com cargo; agente, INT e job recebem delegação mínima e contextual. Contrato do cofre permanece CORE-P0-09. |
| Mandato excepcional de suporte | referência a caso/tenant/ator/prazo/autorização | Não é membership irrestrita nem bypass geral de RLS; mecanismo exato e alçadas pertencem ao CORE-P0-05. |
| Evidência de auditoria oficial | identidade do ator/tenant/recurso/correlação/resultado | Trilha protegida conforme categoria; retenções e permissão de exportar pertencem ao CORE-P0-06 e Auditoria Geral. |

**Decisão ainda solicitada:** validar o modelo de uma organização CRM = um tenant lógico isolado, com identidade global e membership por tenant. Nomes dos campos são semânticos propostos; UUID/ULID, PK/FK, índices e schema físico não estão aprovados.

## 4. Invariantes de isolamento PROPOSTOS para detalhamento técnico

**I01 — deny by default:** toda leitura/escrita de registro tenant-bound exige tenant efetivo autorizado; na ausência de contexto válido, negar; não inferir pelo CNPJ, último tenant, site recebido ou body arbitrário.

**I02 — resolução de contexto:** em requisições humanas, identificar identidade autenticada + membership ativa + tenant selecionado explicitamente + papel/ação/recurso/campo. Checar autorizações no servidor em cada operação sensível. Troca de organização não troca o proprietário dos registros e exige nova resolução de contexto.

**I03 — dupla proteção:** Laravel valida direitos de negócio; PostgreSQL RLS impede acesso SQL fora do tenant. Papéis comuns da aplicação não podem contar com bypass genérico de RLS; políticas por classe de tabela/serviço e mecanismo físico serão detalhados no desenho executável.

**I04 — transações, conexões e workers:** contexto de tenant em operações SQL deve ter duração bem definida e ser resetado sem vazamento em pool/reuso de conexão; tarefas, filas, exportações, importações e automações têm contexto autenticado, correlação, revisão de permissão e falha segura. A opção técnica específica, inclusive SET LOCAL/variáveis transacionais ou mecanismo equivalente, continua sujeita ao CORE-P0-03/SEG.

**I05 — integridade entre tenants:** relações entre registros de negócio pertencentes a um tenant não podem ligar acidentalmente objeto de outro; prever integridade contextual também para FKs, cópias, arquivos, filtros, pesquisas, relatórios, logs e eventos. Desenho de constraints e índices ainda pendente.

**I06 — objetos globais por exceção explícita:** inventariar e autorizar separadamente identidades, catálogo de planos, provedores, configurações globais e evidências da Administração Geral; ter tabela global NÃO atribui acesso geral a dados dos clientes. Definir políticas e donos dos objetos globais antes de schema final.

**I07 — identidade ≠ entitlement:** permitir o login, estar no mesmo CNPJ, aceitar convite ou pagar site não significa que qualquer recurso CRM foi concedido; vigência/cortesia e custos são reconhecidos por FIN/CON e contratos Sites/CRM.

**I08 — suporte e serviços:** operador Imagine, agente técnico, IA, API de captação, webhook ou n8n só usam operações tipadas e escopadas quando legitimamente autorizadas; não usam prompts, segredo de provedor ou HTTP 2xx como atalho para alterar destino/autoridade.

**I09 — alterações de ciclo de vida:** suspender/cancelar/reativar tenant, membership, vínculo de site ou plano não apaga automaticamente dados, histórico ou outras organizações. Transições e retenções pertencem aos módulos responsáveis.

**I10 — revisão e testes futuros:** especificar testes de negação e de concorrência antes de codificar; execução será feita apenas após autorização posterior da fase de desenvolvimento.

## 5. Casos documentais de aceite para homologação futura — NÃO EXECUTADOS

| Caso | Entrada adversa | Resultado esperado |
|---|---|---|
| CORE01-T01 | Identidade associada a A e B consulta recurso de B enquanto opera em A | Negação no backend e isolamento RLS |
| CORE01-T02 | Usuário informa tenant_id de terceiro em body/query/header | Ignorar como autorização; negar operação cruzada |
| CORE01-T03 | Mesma identidade alterna A→B em conexão reutilizada | Contexto de A não persiste; B vê só B |
| CORE01-T04 | Job/exportação iniciado em A é retomado após revogação de membership | Revalidar antes de novos efeitos; bloquear acesso indevido |
| CORE01-T05 | Dois tenants distintos do mesmo CNPJ recebem sites diferentes | Quotas, dados e admin são independentes; sem merge automático |
| CORE01-T06 | Formulário externo aponta site_id válido mas tenta forçar tenant_id | Destino é vínculo oficial autorizado no backend, não campo do visitante |
| CORE01-T07 | Admin Imagine sem mandato tenta ler dados comerciais de cliente | Negar e registrar tentativa sem bypass |
| CORE01-T08 | Agente/service principal de A tenta atualizar dados de B | Negar por tenant, permissão e identidade técnica |
| CORE01-T09 | Registro e FK misturam IDs pertencentes a tenants A e B | Integridade nega vínculo cruzado |
| CORE01-T10 | Tenant suspenso e usuário mantém sessão autenticada | Política de direito é revalidada; sem apagar dados nem afetar outra organização |

Estes cenários devem ser reunidos aos critérios S01..S10 do CRM-REV-01 e INT/SEG, **sem inventar nova bateria redundante**.

## 6. Pontos de aprovação e dependências ainda em aberto

- **CORE01-D01 — deliberação nova:** aceitar como arquitetura lógica de referência identidade global → memberships separadas por tenant → papéis/escopos atribuídos à membership, sem conta duplicada por organização.
- **CORE01-D02 — deliberação nova:** padronizar um tenant lógico por organização CRM, permitindo várias organizações por mesma pessoa jurídica/CNPJ; contrato de site e assinatura financeira são vínculos externos, não identidade do tenant.
- **CORE01-D03 — deliberação nova:** ratificar como guia de detalhamento as invariantes I01–I10 (especialmente negar sem tenant válido, isolamento entre tenants e contas técnicas escopadas), preservando RLS já aprovado.
- **CORE01-D04 — dependência de CORE-P0-02/07:** matriz de permissões, memberships e estados dos convites, alteração de papéis e contagem de assentos devem receber contrato executável próprio.
- **CORE01-D05 — dependência CORE-P0-03/04/05/09:** mecanismo exato de contexto RLS, sessão/SSO, service principals, cofre, suporte excepcional e ações assíncronas continuam pendentes.
- **CORE01-D06 — dependência FIN/CON e CORE-P0-08:** estados/ciclo de tenant e vínculos comerciais, representação legal, criação idempotente e contratação/benefício Básico por site.
- **CORE01-D07 — especificação executável futura:** tabelas/relacionamentos, identificadores, índices/constraints, esquemas globais versus tenant, políticas RLS por tabela, comandos/eventos, versionamento/migração e matriz de testes com aceite formal.

**Recomendação:** submeter D01–D03 ao responsável como **aceite da referência lógica v0.1**; então integrar CORE-P0-02 em sequência. D04–D07 NÃO devem ser marcadas como resolvidas apenas porque D01–D03 foram aceitas. Fechar CORE-P0-01 somente quando suas dependências críticas tiverem documentação/versões reconciliadas e a especificação executável do núcleo for validada; evitar confundir 'base aceita' com 'P0 encerrado'.

## 7. Controle de mudança

- `CRM-REV-01` permanece base documental aprovada; este arquivo é uma proposta derivada, **sem voto adicional** até manifestação expressa do responsável.
- Outras pendências CORE-P0-02..10, INT P0, SEG-01/02, ARQ-01/02/03, AGT-01 e G01..G06 permanecem abertas.
- Este registro não concede permissões, não altera assinaturas, não muda infraestrutura, não libera programação, não executa teste e não substitui a autorização final global.

## 8. Aditamento de decisão expressa (04/11/2026)

O responsável **aprovou CORE01-D01, CORE01-D02 e CORE01-D03 como base documental do modelo lógico** conforme [CRM-DEC — aprovação CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md). Os modelos lógicos e invariantes I01–I10 na seção 4 são aceitos apenas como referência de especificação, **não** como schema, política física RLS, novo privilégio, prova de segurança ou implantação.

**D04–D07 permanecem abertas**, como também CORE-P0-02 a 10, INT P0, IN-168 e gates G01–G06. **Próximo bloco: CORE-P0-02**, sem programação. Esta seção prevalece sobre os rótulos históricos anteriores que solicitavam aprovação D01–D03.
