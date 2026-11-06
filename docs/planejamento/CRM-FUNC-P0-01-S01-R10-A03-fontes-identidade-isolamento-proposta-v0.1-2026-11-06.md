# FUNC-P0-01/S01-R10-A03 — Fontes de identidade, tenant e isolamento (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** PROPOSTA DOCUMENTAL PARA AVALIAÇÃO, NÃO APROVADA. **Não é contrato físico, SQL/RLS, código, teste ou autorização de ALLOW.**

## 1. Precedência e recorte

- A aprovação [R10-A02-D01..D08 A](CRM-DEC-FUNC-P0-01-S01-R10-A02-D01-D08-alternativas-A-2026-11-06.md) vale **apenas como diretriz documental preliminar**; R10-A03 não promove automaticamente essas diretrizes a contrato técnico homologado.
- Bases versionadas examinadas: [CORE01](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md) blob `ec7a03e38fedc3514eaa2c91c6def80d0ac6dba1`, [CORE02](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) blob `6222b2676f5ae59282c36ee607a3f9e62dd2ead6`, [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md) blob `3266eedea35f9d58f0b1fc85f4c991bc8400994a`, [CORE04](CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md) blob `adf09f4e790aefd8679735e62111489f3a74db68`, [R01 original](https://github.com/rafhael100/imagine-crm/blob/dev/fast-track-crm-2026-11-04/docs/desenvolvimento/FUNC-P0-01-S01-R01-campos-estados-seguranca-proposta-v0.1.md) blob `36a353caf695aa03aea3b0d6e0e13331a1fda5b3`.
- Contratos em análise: **C01 sessão/MFA; C02 pessoa/organização/vínculo; C03 autorização Laravel/RLS; C04 CAM01/CAM02**. C05..C08 são dependências externas, não novos campos S01.
- **CORE01-D01..D03, CORE02-D01..D03, CORE03-D01..D03 e CORE04-D01..D03** já possuem aceite como **base documental** nas fontes respectivas; D04+ e gates continuam abertos. A aprovação de A02 não os fecha.

## 2. Tabela de autoridades conceituais dos dados — não tabelas de banco

| Fato/objeto | Sistema/competência candidata da fonte | Serve para | Não substitui | Verificação futura requerida | Contrato |
|---|---|---|---|---|---|
| Identidade humana (principal) | IAM/identidade global CRM | Comprovar quem é a pessoa; referência interna não é segredo | E-mail/CNPJ isolado; perfil do Portal; token de serviço | Sessão vigente e revogação | C01/C02 |
| Organização CRM (tenant) | Cadastro de organizações CORE01/CRM | Limite lógico de dados e direitos CRM | Contrato de site, domínio, CNPJ, cabeçalho livre | Existência, situação técnica e relação com membership | C02/C03 |
| Membership própria | IAM/CORE01/CORE07 — autoridade de vínculos a detalhar | Pessoa + organização + estado do vínculo | Fatura paga, convite, nome do papel, outro tenant | Ativa, própria e versionada | C02 |
| Papéis e permissões | CORE02 e política do módulo (a homologar) | Direito de ação/campo/escopo, conforme veto e proveniência | Título do cargo ou tela disponível | Grant específico legítimo e nenhuma negação aplicável | C03 |
| Situação de contrato/plano | FIN/CON CRM e Portal conforme domínio | Entitlement comercial, separado de identidade | ACK, webhook sem conciliação ou status da UI | Fonte empresarial oficial e versão | C05/C06 |
| Nome da organização (CAM01) | Dado de exibição do cadastro de Organização CRM | Dado visível somente após autorização S01 | Contato de terceiro, título de site ou nome não sanitizado | Classificação/atualização/escaping do texto | C04 |
| Rótulo de vínculo (CAM02) | Derivado da própria membership ativa do IAM | Exibição mínima sem estado de terceiros | Cache desatualizado, vínculo de outra pessoa | Revogação e próprio tenant | C04 |
| Agentes, jobs, webhooks | CORE03/INT com principal técnico segregado | Executar operação técnica tipada e autorizada; não é pessoa humana | Prompt ou credencial do provider como grant | Contexto, origem, mandato e revalidação | C01/C03/C07 |
| Logs/auditoria | CORE06/privacidade por categoria | Provar negativa/fluxo com mínimo necessário | Payload integral, token, ID de terceiro em log aberto | Finalidade, retenção, acesso, descarte | C08 |

**Princípio de fonte autoritativa:** entidade lógica/owner de negócio não significa que um banco, schema, nome de tabela, evento ou API esteja homologado. Responsabilidade interna G01..G06 acumulada por um titular não cria revisão independente de SEG/DBA/LGPD/QA.

## 3. Fluxo semântico para avaliação — sem implementação

1. Verificar a **pessoa humana** e sua sessão/fatores exigíveis pela política de autenticação aplicável. Se serviço/agente, não usar fluxo humano S01.
2. Resolver no servidor a **organização CRM corrente** a partir da sessão/contexto autorizado e conferir **membership própria** com estado ativo/versionamento válido. Nunca escolher outro tenant por parâmetro livre.
3. Separar verificação de **identidade** da política **recurso + ação + campo + escopo**; estado pago ou cortesia não autentica nem concede direitos. Deny-by-default até política formal específica.
4. Exigir que Laravel e PostgreSQL ofereçam duas fronteiras conceituais complementares. Mecanismo físico de RLS, pool, roles e FKs fica para SEG/DBA e CORE01/03, sem SQL neste ato.
5. Só após regras futuras homologadas a projeção S01 poderia exibir **CAM01 nome da própria organização** e **CAM02 próprio vínculo ativo**, com tratamento seguro de texto; demais CAM03..CAM10 excluídos.
6. Na dúvida, ausência de prova, erro obrigatório ou revogação, **negação da autoconsulta S01** sem vazar tenant/terceiro. Preservar exportação, regularização e obrigações legais nos seus fluxos próprios; nenhum efeito comercial é criado por S01.

## 4. Casos de conflito e isolamento a esclarecer (provas futuras, não executadas)

| Situação | Autoridade a reconfirmar | Efeito conceitual esperado na S01 | Casos futuros preexistentes |
|---|---|---|---|
| Pessoa tem memberships em A e B, sessão corrente A, pedido aponta B | IAM/CORE01 e CORE02, nunca body/query | Não misturar ou consultar B a partir de A | S01-T04/T05; CORE01-T01/T02 |
| Membership A revogada, B ainda ativa | IAM/CORE07 | Negar S01 em A sem excluir B ou identidade global | S01-T06/T07; CORE01-T04 |
| Mesmo CNPJ tem dois tenants legítimos | CORE01 + contratos e autorizações de cada tenant | Nem fusão de dados nem direito automático ao outro tenant | S01-T05; CORE01-T05 |
| Site com benefício Básico aponta outro tenant por webhook | Portal/CRM vínculo oficial, IAM/CORE02 | Webhook não cria membership humana ou acesso S01 | CORE01-T06; S01-T14 |
| Pool com contexto anterior de outro tenant, ou role owner/BYPASSRLS | SEG/DBA + CORE01/03 | Exigir isolamento comprovado, jamais liberar S01 com contexto suspeito | S01-T16/T20; CORE01-T03 |
| Pagamento CRM conciliado, mas principal sem vínculo ou grant | FIN/CON prova pagamento, IAM prova vínculo e CORE02 direito | Não conceder S01 por pagamento | S01-T11/T15 |
| Nome de organização contém script/HTML/PII em MEI | Cadastro Organizações + CORE02/LGPD | Não executar script; mínimo permitido após classificação | S01-T01/T12/T19 |
| Banco/Redis/controle de segurança indisponível | CORE04/06/SEG | Falha segura na S01, sem cache de tenant antigo | S01-T17/T19 |

## 5. Oito decisões para avaliação posterior — A03-D01..D08

**As alternativas abaixo são novas propostas documentais. Ainda não foram aprovadas.** A ratificação de R10-A02 não é ratificação antecipada de A03.

| ID | Tema | Alternativa A recomendada | Alternativa B de contraste | Cxx | Bloqueios ainda existentes após A |
|---|---|---|---|---|---|
| **A03-D01** | Identidade humana | O CRM/IAM mantém uma identidade global com prova de autenticação e sessão para uso humano. Cada operação resolve principal efetivo e validade da sessão no servidor; IDs informados pelo navegador não autenticam. | Considerar identificador, e-mail, CNPJ, login no Portal ou token de integração suficientes para autenticar S01. | C01/C02 | CORE01-D04..D07; CORE04-D04..D10; SEG |
| **A03-D02** | Tenant corrente | Uma organização CRM corresponde a um tenant lógico; tenant é resolvido por vínculo ativo e contexto servidor, podendo a identidade legítima possuir memberships em vários tenants separados. S01 NÃO permite selecionar novo tenant por parâmetro. | Usar último tenant do cache, CNPJ comum, domínio do site ou ID arbitrário como fonte de tenant. | C02/C03 | CORE01/07; mecanismo de troca fora do recorte |
| **A03-D03** | Membership e revogação | Membership é relação separada entre identidade e organização; apenas próprio vínculo ativo e autenticado, com estado/versionamento confiável, é elegível para estudo de S01; revogação afeta esse tenant sem apagar outros direitos legítimos. | Derivar membership de assinatura CRM paga, site elegível, papel nominal ou convite pendente. | C02 | CORE01/07; latência/consistência da revogação e restore |
| **A03-D04** | Fonte de campos CAM01 e CAM02 | CAM01 (nome de apresentação da organização) provém do registro de Organização CRM; CAM02 é rótulo calculado exclusivamente do estado válido da própria membership. Não copiar contatos/CNPJ/status comercial/usuários ou enriquecer saída; tratar nomes potencialmente pessoais com minimização. | Derivar nome de campo livre do cliente, Portal, cobrança ou cache sem validação, ou mostrar identificadores/fatos comerciais. | C04 | CORE02-D04/D06; taxonomia de campos; LGPD; UX |
| **A03-D05** | Fronteira de autorização por operação | Sessão, membership e entitlement são pré-condições distintas: eventual direito efetivo para leitura depende também de recurso/ação/campo/escopo e política específica. Aplicar deny-by-default até contrato homologado, sem criação de ALLOW. | Conceder read_self implícito a todo usuário logado, organização paga ou titular do CNPJ. | C03/C04 | CORE02-D04..D10; CORE08/FIN/CON; estados EST05..12 |
| **A03-D06** | Isolamento SQL e reuso de conexões | Defesa em profundidade Laravel + PostgreSQL RLS para dados tenant-bound, com revisão futura de role não-owner/não-bypass, contexto transacional, pool, FKs e execução assíncrona; nada disso é implementado pela aprovação documental. | Confiar apenas no filtro da aplicação ou declarar política RLS física sem definir e verificar roles e conexões. | C03 | CORE01/03; SEG/DBA; PostgreSQL; prova em ambiente autorizado |
| **A03-D07** | Serviços, agentes e suporte | Service principals, webhooks, n8n, agentes e suporte excepcional usam competências próprias e mandatos autenticados/escopados; não impersonam principal humano S01 e não usam aprovação comercial como grant de acesso. | Reutilizar credencial de integração, cargo do suporte ou texto de prompt para consultar S01 de cliente. | C01/C03 | CORE03/05/09; SEG; INT; CORE02 |
| **A03-D08** | Falha, auditoria e fonte desatualizada | Ausência de fonte confiável, inconsistência de tenant, dúvida sobre revogação, cache obsoleto ou indisponibilidade de controle obrigatório deve negar S01 e gerar auditoria mínima protegida, sem dados excessivos; exportação/regularização obedecem a fluxos separados. | Responder com cache de outro contexto, divulgar se organização existe ou suspender portabilidade legítima apenas pelo bloqueio de S01. | C01..C04/C08 | CORE06; LGPD; CORE08; direitos de exportação; SEG |

## 6. Entregáveis concretos ainda pendentes antes de homologação

- **C01:** versão final de sessão web/API, cookies/token, renovação, MFA por perfil/ação, revogação, tratamento de falha e parecer de SEG para risco real. O timeout de 30 minutos já descrito para administração geral não se estende automaticamente aos demais.
- **C02:** cadastro de owners efetivos das entidades/campos, lifecycle de memberships, origem exata dos estados, versioneamento, limites de propagação e prova de contexto corrente, sem código.
- **C03:** matriz de recurso/ação/campo/escopo e precedência de veto, inventário de objetos e roles tenant-bound, mecanismo de contexto transacional, pool, bypass, FKs e não regressão; SQL real e testes PostgreSQL exigem autorização posterior.
- **C04:** fonte/normalização de CAM01, tratamento LGPD quando exibir nome pessoal, CAM02 derivado da membership própria, escaping, não enumeração, classificação de logs.
- **Dependências:** EST05..EST12 seguem a decidir por CORE08/FIN/CON/privacidade; S01-T01..T22 são cenários futuros não executados; IN092 histórica sem fonte original localizada, não reconstruir por palpite.

## 7. Pedido de decisão seguinte (não execução)

Submeter à Direção de Produto as **alternativas A de A03-D01 a A03-D08 exclusivamente como diretrizes documentais preliminares**, preservando eventuais conflitos para revisão individual. Isso não nomeia pareceristas independentes, não fecha C01..C04 e não autoriza código, testes, SQL, ALLOW ou deployment.

## 8. Limites

**R02-C01..C08 PENDENTES. 67 CORE técnicos, SEG01/02, AUT, ARQ/AGT, INT/G01..G06, FIN/CON, LGPD, CORE10/DEV-GATE e demais gates ABERTOS.** Funções de governança R08-Gxx ≠ gates INT-Gxx.
**Não** alterar `main`, PR #2/#21, branch `dev/fast-track-crm-2026-11-04`, VPS, produção, CI, código, schema, SQL, `ALLOW`, `GRANT`, testes funcionais, contratos reais ou cobranças. Somente documentação isolada.