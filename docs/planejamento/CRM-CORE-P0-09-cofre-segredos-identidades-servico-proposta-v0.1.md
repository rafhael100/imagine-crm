# CRM-CORE-P0-09 — Cofre, segredos, identidades de serviço e autorização de ferramentas (proposta v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DOCUMENTAL — CORE-P0-09 ABERTO. CORE09-D01, D02 e D03 NÃO APROVADAS por esta publicação; D04–D10 permanecem abertas.**  
**Natureza:** fechamento lógico de fronteiras de segurança do CRM Imagine e integrações, **não novo cofre, identidade digital emitida, motor IAM, compra de produto, implantação ou autorização de desenvolvimento**.  
**Fase:** documentação GitHub exclusivamente. Sem códigos, testes reais, provisionamento, criação de credenciais, acesso a contas, rotacionar segredos reais, contratar provedores ou executar alterações de infraestrutura.

## 1. Fontes de autoridade e precedência documental

| Fonte | Regra pré-existente preservada |
|---|---|
| [IN-151 — Credenciais e chaves](IN-151-gestao-inteligente-credenciais-chaves-integracao.md) | **Cofre central protegido** com criptografia, controle de acesso e auditoria; identidade mínima por serviço/agente/ambiente/cliente; inventário, rotação homologada e revogação controlada; segredos não entram em navegador, código público, prompts, memória IA ou logs. **Produto, porte, disponibilidade e custos não selecionados.** |
| [IN-167 — Arquitetura dos agentes](IN-167-consolidacao-arquitetura-agentes-tecnicos.md) | Sete especialistas e um Supervisor Técnico como **papéis lógicos**, N1 leitura/diagnóstico, N2 playbooks seguros homologados e N3 intervenção crítica com uma autorização humana específica quando exigida; **sem dupla aprovação universal**. Identidade da ferramenta e controle determinístico independem do texto do modelo/prompt |
| [IN-168 — Pendências de arquitetura](IN-168-matriz-pendencias-fechamento-arquitetura.md) | **SEG-01 P0:** matriz concreta agente×ferramenta×ação×serviço×cliente×ambiente×risco, autorizador independente, revogação e auditoria. **SEG-02 P0:** escolha e projeto do cofre, identidades técnicas, rotação/contingência. **AGT-01 P0:** playbooks. **ARQ-01 P0 parcial:** isolamento da infraestrutura dos agentes de n8n aprovado, mas topologia física, dimensionamento, custos e provedor ainda pendentes |
| [INT-02](integracoes/INT-02.md), [INT-R01](integracoes/INT-R01-governanca-contratos-homologacao.md), [P0-SEC](integracoes/P0-SEC-politicas-autorizacao-CT02.md) | Autorização **externa do provedor** ≠ RBAC/MFA do CRM ≠ posse de segredo. Credenciais externas e consentimentos/escopos pertencem à INT-02; Identidade/Segurança detém IAM; Central de Aprovações mantém decisões humanas; execução via contratos tipados sem SQL/código livre ou superprivilégios |
| [CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md), [CORE-P0-02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md) | Identidade humana global e membership por tenant; **identidade técnica distinta**, sem herdar login humano, admin SaaS, CNPJ ou permissão de outro tenant. Ato protegido exige escopo, entitlement, recurso, ação, campo e política do dono |
| [CORE-P0-03 D01–D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md) | A solicitação humana/automática, o serviço executor e o proprietário do resultado são atores diferentes. Filas/jobs/webhooks revalidam autorização em fronteiras protegidas; expiração/revogação de uma credencial não desfaz fato oficial já confirmado e não autoriza retry cego |
| [CORE-P0-04 D01–D03](CRM-DEC-aprovacao-CORE-P0-04-D01-D03-base-autenticacao-2026-11-04.md), [CORE-P0-05 D01–D03](CRM-DEC-aprovacao-CORE-P0-05-D01-D03-base-suporte-excepcional-2026-11-04.md) | MFA do Admin Imagine e do Admin Empresa, inclusive Básico; mandato de suporte excepcional por caso e prazo. Um serviço/IA não recebe acesso humano de administrador nem mandato de suporte invisível |
| [CORE-P0-06 D01–D03](CRM-DEC-aprovacao-CORE-P0-06-D01-D03-base-auditoria-2026-11-04.md) | Auditoria Geral como fonte oficial, logs/telemetria técnica separados; registrar referências seguras, nunca valor de token/segredo; acesso à auditoria por competência e finalidade |
| [CORE-P0-08 D01–D03](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md) | Vínculos Site↔tenant/trial/Básico/assinatura têm autoridades próprias; credencial de Site não cria usuário/benefício/cortesia nem decide cobrança |
| [Modelo CRM nativo × n8n interno](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md) | Motor próprio Laravel/PostgreSQL/filas executará automações configuráveis de clientes; n8n self-hosted reservado aos workflows internos controlados pela Imagine, sujeito a verificação de licenciamento. **Nenhum fluxo personalizável de cliente recebe credencial/execução direta do n8n como direito implícito.** |
| [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md), [INT P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) | Independência de CRM/Portal/Sites/n8n/agentes, proteção de segredos, monitoração e contratos; **P0 e G01–G06 continuam abertos**, evidências de homologação não executadas |

**Precedência:** normas funcionais aprovadas são base, mas **não homologam mecanismo físico, vendor, tokens, segredos ou contas**. Documentação atual não altera IN-151, INT-02, IN-166/167/168, segurança de Sites/Portal, LGPD, as regras de custo nem a proibição de programação.

## 2. Entidades e fronteiras semânticas — sem schema, conta ou permissão nova

| Entidade lógica | Função no desenho | Limite obrigatório |
|---|---|---|
| **Identidade humana** | Admin Imagine, administrador/funcionário cliente, operador de suporte | MFA/sessão/membership; token de sessão não deve virar credencial fixa de worker |
| **Identidade técnica de serviço** | CRM API, worker/filas, Portal, API principal e contingência, n8n interno, agente/ferramenta, integração | Deve ter principal verificável próprio, ambiente, autoridade e escopo; nenhum superadmin técnico universal |
| **Autorização causal** | Pessoa, trigger, entidade contratual ou rotina de sistema que iniciou o job | Registrar separadamente executor e autor; contexto não é grant permanente |
| **Contrato de ferramenta/capacidade** | Ação tipada, versão, sistema/recurso/tenant/ambiente permitidos | Modelo IA, prompt, ticket ou ID de tarefa não concedem capacidade |
| **Credencial de serviço interna** | Autenticar servidores entre si ou acessar cofre/serviço interno | Separada de autorização funcional da ação e de token externo do provedor |
| **Credencial de provedor externo** | Acessar API externa com escopos e consentimentos próprios, quando aplicáveis | INT-02 governa vínculo/escopo; token válido não dá poder sobre objetos internos do CRM |
| **Segredo** | Valor protegido: token, chave privada, certificado, senha de serviço etc. | Valor jamais em GitHub, frontend, prompt, exportação, ticket, workflow público ou telemetria ordinária |
| **Referência segura ao segredo** | Identificador de cofre/versão, dono, finalidade e validade, sem conteúdo | Permissão de conhecer a referência não autoriza buscar valor ou executar operação |
| **Mandato de suporte** | Autorização excepcional restrita ao caso/tenant/ator/ação | Não é identidade de serviço nem permissão universal ao conteúdo dos clientes |
| **Decisão humana crítica** | Central de Aprovações aplica alçadas e vigência específicas | Aprovação só vale para ato compatível e não é código de acesso irrestrito |

**Distinção de fontes oficiais:** Identidade e Segurança decidem acesso interno; INT-02 gerencia autorização e consentimento do provedor; cofre guarda o valor secreto; serviço proprietário confirma efeitos de negócio. Nenhum desses papéis é substituível por outro.

## 3. CORE09-D01 — Inventário lógico de segredos e identidade por competência (PROPOSTA)

**Proposta para aprovação documental:** adotar um catálogo conceitual de **identidades de serviço e famílias de segredos**, com menor privilégio, segregando tenant, ambiente, origem e finalidade sem selecionar produto/infraestrutura.

### 3.1. Domínios e casos de referência

| Domínio | Identidade/segredo possível a registrar | Não se presume |
|---|---|---|
| CRM Laravel/API e workers nativos | Principal de serviço/worker, credencial interna, acesso mínimo a recursos autorizados | Uso da conta do admin cliente, acesso irrestrito a todas as tabelas |
| Portal Imagine × CRM | Contrato oficial servidor-servidor, ambiente, assinatura/autorização dos eventos | Mesma credencial ou login compartilhado automaticamente; permissão de ler dados comerciais |
| API principal e API contingência de leads | Identidade e segredos técnicos próprios, destinos oficiais CRM, contratos por integração | Segredo privilegiado em página pública; uso do mesmo secret para eliminar separação de falhas |
| Sites Cloudflare Workers Static Assets | Identificadores públicos de formulário e configuração não secreta; segredo só em backend protegido quando necessário | API key administrativa embutida em HTML/JS ou no build público |
| n8n interno Imagine | Principal e credenciais limitadas a operações internas homologadas e por workflow/ambiente | Motor direto de automações pessoais de clientes, acesso universal ao CRM ou direito de executar código livre |
| Sete agentes técnicos e Supervisor | Principais/autorizações de ferramentas conforme papel N1/N2/N3, playbook, ambiente, escopo e risco | Supervisor com superprivilégios; agente recebe segredo bruto por prompt |
| Integrações de provedores | OAuth/token/chave do provedor conforme tenant/conta/conexão/escopo, geridos por INT-02 e cofre | Consentimento externo concede poder administrativo interno ou permite usar segredo de outra empresa |
| Backup, publicação, DNS e observabilidade | Credenciais isoladas por domínio, ambiente, serviço e operação de risco | Logs/alertas ou backup contêm valores em claro ou liberam exclusão/restore sem alçada |
| Operações humanas excepcionais | MFA e mandato de suporte com decisão própria | Chave de serviço como substituto de consentimento, autoridade e auditoria |

### 3.2. Metadados candidatos, não schema aprovado

`service_principal_ref`, `secret_ref` (nunca conteúdo), proprietário do domínio, classe de dado, provedor/conexão, tenant/conta quando aplicável, finalidade, ambiente, ferramenta/ação permitida, playbook/versão, origem de autorização, classificação de risco, data de concessão, vencimento, referência de rotação, estado de revogação, dependências, referência de auditoria e custo quando pertinente.

**Não** fixar agora nome de tabela, fornecedor de KMS, algoritmo, formato de tokens, time-to-live numérico, índice, preço ou infraestrutura. Metadados exigidos variam pela classe; não assumir todos obrigatórios em toda chamada.

### 3.3. Invariantes conceituais

- **Uma pessoa ≠ serviço**: se a sessão de funcionário expira, jobs de sistema obrigatórios continuam somente com autoridade própria, mas nova ação por iniciativa pessoal revogada não adquire autorização técnica por possuir worker.
- **Mesmo CNPJ ≠ mesmo escopo de segredo**: vínculo em tenant A não permite uso em B.
- **Mesmo provedor ≠ mesma credencial**: conexões e ambientes mantêm titularidade e escopos.
- **Chave pública ≠ segredo de servidor**: IDs de formulário visíveis não substituem validação do destino autorizado no backend.
- **N1/N2/N3 ≠ grants globais**: risco e competência do ato prevalecem sobre classificação do incidente ou modelo de IA.

## 4. CORE09-D02 — Fronteira do cofre e autorização determinística de uso (PROPOSTA)

**Proposta para aprovação documental:** definir o cofre como **armazenamento protegido de segredos com controle de acesso e auditoria**, separado da política que autoriza a operação de negócio; o uso de cada segredo requer contexto atual válido e concessão da ferramenta/serviço competente.

### 4.1. Caminho conceitual de execução segura

1. **Solicitação identificável:** ator humano ou técnico e serviço executor, tenant/conta, finalidade, ação tipada, recurso/campo, ambiente e versão; sem token bruto no envelope de negócio.
2. **Autenticação do executor:** principal técnico válido segundo mecanismo aprovado futuramente; não usar impersonação de cliente/admin geral.
3. **Avaliação independente:** Identidade/Segurança e proprietários verificam poder efetivo, contrato/entitlement, estado atual, política da ferramenta/playbook e autorização do provedor externo quando aplicável. Se ação crítica, exigência de aprovação específica na Central de Aprovações conforme risco.
4. **Resolução protegida de segredo:** serviço autorizado usa referência segura e somente obtém acesso necessário à operação. **Preferir que ferramentas protegidas executem atos sem entregar o valor do segredo ao modelo IA ou ao browser.**
5. **Execução pelo proprietário:** ferramenta tipada/versionada atua no sistema de destino; serviço dono confirma resultado oficial. Sucesso do n8n, HTTP 2xx ou posse de token **não é confirmação de negócio**.
6. **Registros minimizados:** autor causal, principal executor, ferramenta/ação, tenant, versão, decisão permitida/negada, referências do segredo e resultado, sem payload/secret bruto.
7. **Revalidação:** em retomadas e fases sensíveis, revogação, expiração de mandato, mudança de tenant, escopo, ferramenta, versão e credencial não autorizam continuação irrestrita.

### 4.2. Fronteiras explícitas

- O cofre **não é IAM** e a INT-02 **não é cofre paralelo**. O CRM usa componentes existentes e interfaces contratadas; "cofre central" não implica um único supersegredo acessível por qualquer sistema.
- **Separar produção e homologação**, tenant/cliente quando apropriado, provedores, contas, ambientes e serviços; contêiner, VPS, workflow n8n e agente não herdam permissões só por compartilhar infraestrutura/rede.
- Não armazenar valores em GitHub, variáveis públicas de builds, assets Cloudflare estáticos, prompt/memória de IA, ticket, arquivo CSV, logs/traces, corpo de evento genérico ou backup em claro.
- Falha do cofre/autorizador deve **negar novos atos privilegiados** sem afetar indevidamente leitura pública legítima ou recepção durável que possa funcionar com credenciais/contingência **homologadas e ainda válidas**. Não autorizar caching informal de segredos em código/cliente para contornar pane.
- Permissões externas do provedor, compliance e consentimento não substituem a autorização interna por tenant, finalidade, recurso e ação. Falha de provedor pode exigir reconciliação, não repetição cega.
- Nenhum provedor/algoritmo/método de obtenção de segredo fica aprovado por esta diretriz, inclusive OIDC, IAM role, mTLS, TLS, Vault, KMS, HSM, Redis, Docker secrets ou tokens de curta duração (opções D04–D10).

## 5. CORE09-D03 — Ciclo de vida lógico de segredos, rotação, revogação e contingência (PROPOSTA)

**Proposta para aprovação documental:** organizar o ciclo conceitual de credenciais e referências de segredo sem executar trocas reais:

`necessidade comprovada → solicitação e autorização competente → criação/importação protegida → ativação limitada → observação/expiração programada → renovação ou rotação autorizada → verificação das dependências → revogação/encerramento e auditoria`.

- **Inventário antes de rotacionar:** conferir proprietário do segredo, conexões, ambiente/tenant, workflows, APIs, workers, integração e dependências, sem expor valor do segredo à IA.
- **Rotação ordinária:** executar autonomamente apenas procedimento **previamente homologado**, dentro de risco e alçada, com verificação de dependências. Quando provedor permitir sobreposição segura, usar transição controlada; não presumir que todo provedor aceite duas chaves ativas.
- **Comprometimento:** incidente registrado; executar contenção homologada ou obter **uma autorização humana específica** para ação crítica quando exigida, com alerta e investigação, antes de restabelecer acesso. Troca de token não concede privilégio extra.
- **Revogação:** invalidar credenciais e novos acessos no escopo afetado, reavaliar sessões técnicas, caches, jobs pendentes, webhooks e conexões; preservar trilha/efeitos confirmados. **Revogação interna e revogação confirmada no provedor são fatos diferentes.**
- **Falha do cofre:** não presumir acesso privilegiado ilimitado ou parada total de todos os serviços; planejar contingência por serviço e risco, com falha segura, monitoramento independente e autoridade para transições. Detalhes de HA, restauração e caches seguros ficam D08.
- **Agentes e n8n:** nenhum modelo/IA cria, escolhe, modifica ou divulga segredo por texto; podem **solicitar** ferramenta homologada e registrar resultados auditáveis, sem alterar por conta própria orçamento, contas ou permissões.
- **Auditoria:** registrar identidade técnica, causalidade, caso/incidente, ação, tenant/ambiente, referência/versão não sensível, aprovação aplicável, instante e desfecho sem segredo. Investigações oficiais e políticas de retenção seguem CORE-P0-06.
- **Custos/licenças:** cofre, KMS, redes, rotas, backups e provedor exigem dimensionamento/aceite expresso; esta proposta não autoriza contratação nem escolha.

## 6. Exemplos de aplicação

| Caso | Resultado de referência |
|---|---|
| Agente Segurança detecta chave de integração possivelmente exposta | Registrar incidente e classificar risco; fazer contenção **somente** sob playbook/autoridade válida, rotacionar com procedimento homologado ou submeter operação crítica à aprovação aplicável |
| n8n Imagine quer reprocessar operação de cliente | Autenticar como serviço interno limitado; verificar tenant/ação/contrato e estado oficial antes de novo efeito; n8n não vira conta admin do cliente |
| Cliente configura automação no CRM | Motor **nativo do CRM** avalia a política do cliente e executa ação homologada; não expor credencial n8n interna ao cliente ou executar seu workflow dentro do n8n por padrão |
| Agente Infraestrutura investiga lentidão do CRM | Recebe sinais técnicos permitidos, não direito automático de exportar banco comercial ou consultar anexos privados |
| Segundo tenant do mesmo CNPJ tenta usar credencial OAuth da primeira organização | Negar por tenant, titularidade, conexão e escopo; mesmo CNPJ não prova autorização |
| Worker recebe evento de formulário sem CRM oficialmente vinculado | Bloquear captação/entrega, sem autorizar por conhecer key ou site ID; preservar IN127-R06 |
| Troca de senha/segredo após incidente | Não colocar segredo em relatório; revogar referências antigas e revalidar jobs/tokens; auditar a confirmação real da rotação |
| Cofre indisponível durante recuperação de API | Novo ato privilegiado não deve ser permitido por falha de política; optar só por credenciais de contingência ainda válidas e homologadas |
| Backup restaurado após incidente | Não reativar automaticamente credenciais revogadas/usuários/autorizações antigas; exigir validação e recuperação controlada |
| Agente Supervisor aprova sozinho operação N3 | Negar: Supervisor não é aprovador humano nem superprivilégio; aprovação competente específica quando aplicável |

## 7. Casos negativos para homologação futura — NÃO EXECUTADOS

| ID | Tentativa | Invariante esperada |
|---|---|---|
| CORE09-T01 | Segredo administrativo é exposto em HTML do Site, cliente JS, prompt, ticket ou log | Negar/publicação bloqueada; incident response segundo risco, nenhum segredo em claro |
| CORE09-T02 | Principal técnico de tenant A tenta acessar segredo/objeto de B | Negar por tenant, conexão, ambiente e escopo |
| CORE09-T03 | Worker com credencial de fila tenta conceder plano pago/gratuidade | Negar sem competência FIN/CON/admin autorizada |
| CORE09-T04 | Agente N2 usa ferramenta fora de versão/playbook homologado | Negar; nenhum grant via IA/prompt |
| CORE09-T05 | Supervisão IA tenta executar ação crítica N3 sem autorização humana exigida | Negar e escalar |
| CORE09-T06 | Permissão interna revogada mas cache/job tenta novo efeito com token antigo | Revalidar; negar novo efeito, preservar fatos anteriores |
| CORE09-T07 | Token revogado internamente é tratado como revogado no provedor sem confirmação | Registrar estado remoto inconclusivo e reconciliar |
| CORE09-T08 | Rotação de credencial da API principal interrompe contingência independente | Identificar dependências, preservar separação e evitar suspensão indevida |
| CORE09-T09 | n8n recebe credencial genérica de acesso a todas as tabelas/tenants | Negar superprivilégio e limitar operações internas tipadas |
| CORE09-T10 | Troca de prompt/modelo concede acesso a cofre ou ferramenta | Negar: autorização externa ao modelo é determinística |
| CORE09-T11 | Falha do cofre leva agente a gravar token em GitHub/variável pública para emergências | Negar improvisação e seguir contingência homologada |
| CORE09-T12 | Login humano vira credencial permanente de job técnico | Negar; identidade técnica própria com contexto causal |
| CORE09-T13 | Serviço com referência de segredo, sem autorização atual, tenta obter valor | Negar lookup: referência não é grant |
| CORE09-T14 | Execução técnica retorna HTTP 200 mas dono do negócio não confirmou | Não declarar sucesso oficial; reconciliação controlada |
| CORE09-T15 | Suporte Imagine sem mandato tenta usar credencial de serviço para ler dados de cliente | Negar IAM, caso e recurso; auditar |
| CORE09-T16 | Credencial de homologação executa em produção | Negar por ambiente |
| CORE09-T17 | Rotação altera escopos externos sem consentimento/decisão competente | Negar escalada e exigir autoridade INT-02 |
| CORE09-T18 | IA gera compra de serviço/cofre ao superar limite | Negar despesa sem autorização financeira expressa |
| CORE09-T19 | Sistema restaurado de backup reativa segredo já revogado | Preservar revogação e reconciliação oficial |
| CORE09-T20 | Falha em revogação/rotação não gera evidência de diagnóstico | Registrar incidente/inconclusivo, não sucesso falso |

Esses critérios são **somente planejamento** para CORE-P0-10, INT HT-01..18, IN-168 SEG-01/02 e AGT-01. Nenhum teste foi executado; selecionar ambientes e ferramentas requer autorização posterior.

## 8. Propostas CORE09-D01, D02 e D03 — NÃO APROVADAS

- **CORE09-D01 — PROPOSTA:** adotar como referência lógica **catálogo de identidades técnicas e famílias de segredos por serviço, cliente/tenant, ambiente, ferramenta e finalidade**, separados de identidades humanas, assinaturas e vínculos de provedores, sem fixar schema, vendor, token ou nova permissão.
- **CORE09-D02 — PROPOSTA:** adotar **fronteira entre cofre protegido, autorização determinística de ferramenta e confirmação por módulo proprietário**, com referência a segredo em vez de valor em operações, controle de menor privilégio, segregação produção/homologação e vedação de segredos em frontend, prompts, GitHub e logs; sem motor novo e sem n8n como superadministrador.
- **CORE09-D03 — PROPOSTA:** adotar **ciclo lógico de solicitação, ativação, rotação, detecção de comprometimento, revogação, contingência e auditoria**, homologando ações no futuro e exigindo decisão humana específica para intervenção crítica conforme política, sem aprovar rotinas, limiares ou provedor agora.

## 9. CORE09-D04 a D10 — BLOQUEIOS TÉCNICOS/DELIBERATIVOS ABERTOS

| Código | Tema pendente | Responsabilidade/integração |
|---|---|---|
| **CORE09-D04** | Produto/provedor/cofre e topologia física (KMS/HSM/gerenciado/autogerido, alta disponibilidade, região, custos, recuperação, suporte, capacidade e criptografia/segurança) | IN-168 SEG-02, ARQ-01/02, IN-151, Operações/FIN |
| **CORE09-D05** | Mecanismo de identidade entre servidores e credenciais por ambiente, bootstrap seguro, renovação, isolamento, segredos por tenant, autenticação técnica e cadeia de confiança | SEG-02, IAM CORE01/04, Portal/API/Cloudflare |
| **CORE09-D06** | Matriz executável agente/serviço×ferramenta×ação×alvo×tenant×ambiente×risco, avaliação independente e política de erro/negação | IN-168 SEG-01, AGT-01, IN-166, CORE02-D04..D10, INT P0-SEC |
| **CORE09-D07** | Prazos/TTL e versões, método de rotação e revogação local/remota, proveniência, índices, concorrência, propagação de mudança a workers/jobs, idempotência/reconciliação | IN-151, INT-02, CORE03-D04..D10, CORE04-D10 |
| **CORE09-D08** | Política física de desastre/backup/restore de cofre, chaves de recuperação, falta de cofre, failover seguro entre API principal/contingência e vigilância independente | IN-143/145/151/159, IN-168 ARQ-01/02/03 |
| **CORE09-D09** | Governança de acesso ao cofre e aprovações de mudanças críticas, integridade/auditoria/retencão, resposta a incidentes, LGPD, documentação e gestão de custos/licenças | CORE05/06, IN-137/138/142/162, INT-12 |
| **CORE09-D10** | Contratos técnicos finais, critérios de homologação negativos/positivos, evidência, responsáveis e reconciliação cross-sistema; encerramento condicionado aos gates | CORE-P0-10, INT P0-OWN/SEC/CON/ACC, IN-168 SEG-01/02 AGT-01, G01–G06 |

**Demais pendências intactas:** CORE01-D04..D07, CORE02-D04..D10, CORE03-D04..D10, CORE04-D04..D10, CORE05-D04..D10, CORE06-D04..D10, CORE07-D04..D10, CORE08-D04..D10; CORE-P0-10; INT P0 (OWN/SEC/CON/ACC/DOC/TR etc.), IN-168 ARQ-01/02/03, SEG-01/02, AGT-01, G01–G06 e fechamento de arquitetura e programação. A aceitação apenas de D01–D03 **não encerrará** CORE-P0-09, SEG-01 ou SEG-02.

## 10. Próximo passo e proibições

Somente após aceite expresso de CORE09-D01, D02 e D03 registrar decisão de aprovação e seguir **CORE-P0-10 — pacote documental de homologação do núcleo**, sem testes físicos. Todos os custos, infra e contratos físicos deverão ser aprovados antes de implementação.

**Nenhuma programação, Codex, testes reais, rotação real, geração/exposição de credenciais, acesso a cliente, provisionamento, compra ou contratação foi autorizada.**
