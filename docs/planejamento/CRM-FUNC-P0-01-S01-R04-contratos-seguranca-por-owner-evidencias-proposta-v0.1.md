# FUNC-P0-01 / S01-R04 — Detalhamento dos contratos de segurança por responsável e evidência (PROPOSTA v0.1)

**Data:** 06/11/2026. **Estado:** **MINUTA NÃO APROVADA** para avaliação.  
**Antecedente:** as alternativas A de R01-D01..D05, R02-D01..D08 e R03-D01..D08 foram ratificadas **somente como diretrizes documentais preliminares**, registradas em [DEC-R01](CRM-DEC-FUNC-P0-01-S01-R01-alternativas-A-D01-D05-2026-11-06.md), [DEC-R02](CRM-DEC-FUNC-P0-01-S01-R02-D01-D08-alternativas-A-2026-11-06.md) e [DEC-R03](CRM-DEC-FUNC-P0-01-S01-R03-D01-D08-alternativas-A-2026-11-06.md).  
**Status dos contratos:** **R02-C01..R02-C08 todos PENDENTES**, não homologados, não transformados em autorização executável.  
**Escopo:** exclusivamente contratos **conceituais** para leitura humana da **própria** organização e do **próprio** vínculo (S01) e suas dependências transversais, sem criar tela comercial, login, serviço, código, API, banco ou concessão de acesso.

## 1. Princípios de fronteira

- O campo `organizacao_nome_exibicao` refere-se apenas à organização corrente validada; `proprio_vinculo_estado` refere-se apenas ao estado da associação da identidade autenticada **quando autorizado**. A nomenclatura é **conceitual**, não schema nem payload.
- Não revelar existência de tenant, conta, associação suspensa/revogada, lista de organizações, funcionários, dados cadastrais extensos ou dados comerciais por meio de falhas e diferenças de resposta.
- Um estado comercial **não substitui** autenticação, direito de consulta, membership, nem isolamento de banco; um estado de vínculo **não confirma** assinatura ou pagamento.
- `deny-by-default` é princípio documental: **nenhuma política ALLOW concreta foi aprovada**. Bloqueio de novas concessões inferidas não implica suspender arbitrariamente direitos já válidos.
- Owner abaixo é **candidato à revisão**; não designação operacional, nem autorização de delegação.

## 2. C01 — Autenticação humana e sessão confiável

**ID:** R02-C01. **Proprietários candidatos:** CORE04 / IAM / SEG-01/02.  
**Pré-condições a fechar:** identidade humana comprovada, sessão íntegra e válida, contexto de autenticação com força apropriada ao papel, verificação da exigência de MFA/step-up aplicável. Regra histórica: MFA obrigatório para administrador geral e administrador da empresa; para funcionário, decisão por política da organização. Não estender automaticamente 30 min de inatividade de administrador geral a todos os perfis.
**Resultado futuro desejável:** operação somente segue à autorização do próprio vínculo; falha, expiração, sessão trocada ou fator insuficiente não fornece campo protegido.
**Casos negativos documentais:** sessão inexistente ou revogada; principal diferente do vinculado; replay de sessão; segundo fator ausente em papel que o exige.
**Evidências pendentes:** fonte de identidade, autoridade de sessão, semântica de expiração/revogação, decisão de MFA, matriz por papel, protocolo de erro não enumerativo, prova de auditoria sem token bruto.

## 3. C02 — Organização corrente, principal e membership

**ID:** R02-C02. **Proprietários candidatos:** CORE01/02, Administração da Empresa, SEG.  
**Pré-condições:** organização corrente selecionada por fluxo autenticado e confiável, correspondência entre principal da sessão e membership, versão e estado válidos do próprio vínculo. Um usuário pode ter vínculos em mais de um tenant, mas cada operação mantém exatamente um contexto autorizado.
**Resultado futuro desejável:** leitura de atributos mínimos somente do próprio tenant e do próprio vínculo, sem permitir trocar tenant livremente via parâmetro de requisição.
**Casos negativos:** tenant de outra organização, membership de terceiro, vínculo revogado, mesma identidade com org A e B mas contexto fora da org corrente, convite não aceito.
**Evidências pendentes:** definição canônica de identidade/vínculo, seleção de tenant, proveniência do contexto, controle de versão e política de visibilidade do estado do vínculo; CORE01/02/SEG ainda abertos.

## 4. C03 — Isolamento do banco, revalidação e revogação concorrente

**ID:** R02-C03. **Proprietários candidatos:** CORE01/03/04, SEG-01/02 e arquitetura PostgreSQL.  
**Pré-condições:** decisão de aplicação por ação+recurso, tenant resolvido confiavelmente e controle de acesso em banco por política RLS específica e homologada. Contexto derivado de parâmetros de cliente, GUC sem autenticação, cache ou snapshot não constitui prova de identidade.
**Resultado futuro desejável:** acesso a linhas/atributos alheios negado; revogação e mudança de tenant revalidadas nos pontos sensíveis conforme contrato especializado.
**Casos negativos:** IDOR, vínculo revogado entre duas leituras, query fora do tenant, role privilegiada ignorando RLS, job/cache servindo dado de outro tenant.
**Evidências pendentes:** matriz de roles/tabelas/sessões, autorização Laravel+RLS por operação, ordem de verificação, revogação, testes negativos **para execução futura mediante autorização própria**. Não escrever SQL nem executar testes agora.

## 5. C04 — Catálogo mínimo de campos e não enumeração

**ID:** R02-C04. **Proprietários candidatos:** CORE01/02, responsável por UX de S01, LGPD/SEG.  
**Pré-condições:** finalidade e classificação de cada campo, autorização específica para a projeção da própria organização e vínculo; mascaramento e erro uniforme quando a política negar.
**Saída candidata de S01 (somente após homologação):** `organizacao_nome_exibicao`, `proprio_vinculo_estado`. O estado próprio pode ser exibido **apenas se a consulta já foi autorizada**; registro suspenso, ausente ou revogado não gera direito à visibilidade.
**Casos negativos:** tentar incluir CNPJ completo, faturas, lista de empregados, outras organizações, dados de sessão, dados de cobrança; enumerar vínculos por mensagens de erro.
**Evidências pendentes:** catálogo classificado, condições de exibição por estado/ator, redação dos erros, revisão de minimização; não inferir formato físico da resposta.

## 6. C05 — Proveniência dos fatos comerciais (fora da saída S01)

**ID:** R02-C05. **Proprietários candidatos:** FIN/CON CRM, Portal Sites, INT, CORE08.  
**Pré-condições:** contrato comercial oficial identifica origem, tenant, contrato de produto, versão, momento de eficácia, evento, autenticidade e estado de reconciliação. Diferenciar fato Portal de fato CRM, sem copiar estados financeiros para IAM.
**Resultado futuro desejável:** estados de trial, CRM pago, cortesia e operacionalidade apenas referenciam fatos oficiais de sua autoridade; nenhum campo comercial entra em S01 por esta minuta.
**Casos negativos:** evento duplicado, site de outro tenant, webhook 2xx tratado como pagamento confirmado, evento de cancelamento recebido fora de ordem, crédito/manual não autorizado.
**Evidências pendentes:** catálogo dos fatos por domínio, carimbo de versão, contratos Portal↔CRM, origem confiável, precedência, exceções, finanças/privacidade.

## 7. C06 — Direito de operação separado de estado comercial

**ID:** R02-C06. **Proprietários candidatos:** CORE02/08, FIN/CON, SEG e donos de módulo.  
**Pré-condições:** direito por principal+tenant+ação+recurso+vigência, condicionado à política de autorização e ao entitlement confirmado, sem ampliar o alcance de S01.
**Resultado futuro desejável:** assinatura vigente ou benefício Básico não se confunde com membership ativo, nem com permissão genérica de exportação, cadastro ou cobrança; condições comerciais são insumos independentes.
**Casos negativos:** plano `ativo` gera acesso global, site dá permissão de usuário extra, falha financeira bloqueia membership de outro tenant, tela `ativo` reativa cliente.
**Evidências pendentes:** matriz de precedência/negações CORE02, limites/quota CORE08 e FIN/CON, ações/recursos permitidos por módulo, revisão de incompatibilidades. Nenhum grant/ALLOW nesta fase.

## 8. C07 — Conciliação, duplicidade e mudança concorrente

**ID:** R02-C07. **Proprietários candidatos:** CORE03, FIN/CON, INT, SEG, auditoria.  
**Pré-condições:** fatos com correlação, identidade do evento, versão/ordem, replay controlado e análise por owner competente; **nenhuma implementação de fila/idempotência agora**.
**Resultado futuro desejável:** divergência = estado **não verificado** para novas concessões/cobranças inferidas; eventos atrasados não restauram benefícios indevidamente; impacto em direitos previamente confirmados requer regra especializada.
**Casos negativos:** duas confirmações de pagamento, revogação simultânea à leitura, mesmo evento repetido após troca de tenant, restauração de benefício por evento obsoleto, sistema sem conexão com fonte oficial.
**Evidências pendentes:** estados de conciliação, autoridade da decisão, ordens possíveis, política de compensação e incidentes, critérios de notificação, contratos INT/FIN/CON.

## 9. C08 — Auditoria, privacidade e retenção por categoria

**ID:** R02-C08. **Proprietários candidatos:** SEG, responsável por auditoria, LGPD e donos de dados.  
**Pré-condições:** registrar decisão/tentativa de forma mínima e segura, finalidade, categoria de informação, organização quando permitido, versão da política, correlação e motivo genérico. Retenção e acesso não presumidos a partir de qualquer prazo comercial.
**Resultado futuro desejável:** trilha rastreável sem armazenamento de tokens, segredos ou conteúdo pessoal excessivo, com consulta/exportação autorizadas separadamente.
**Casos negativos:** expor estado do tenant alheio em log, salvar payload completo, permitir que administrador do cliente altere auditoria oficial, confundir retenção de 12 meses/5 anos com todas as categorias.
**Evidências pendentes:** mapa de finalidades, classificações e perfis de acesso, prazo por categoria, política LGPD, mascaramento, eliminação e rastreabilidade de alterações no registro.

## 10. Deliberações documentais R04-D01..D08 — TODAS PENDENTES

| ID | Contrato relacionado | Alternativa A proposta (sem homologar o contrato) | Alternativa B de contraste |
|---|---|---|---|
| **R04-D01** | C01 | **A:** registrar requisitos de sessão/MFA/step-up por papel e evidências; política especializada continua aberta | B: presumir que sessão isolada equivale à autorização final |
| **R04-D02** | C02 | **A:** exigir principal+tenant+membership correspondentes e versionados; nenhuma seleção arbitrária | B: confiar no tenant informado livremente pela interface |
| **R04-D03** | C03 | **A:** aplicação+RLS com revalidação/revogação e critérios negativos futuros; sem SQL agora | B: considerar filtro visual suficiente |
| **R04-D04** | C04 | **A:** catálogo de dois campos e estados próprios somente quando autorizados, sem enumeração | B: listar registros e estados ocultos a quem solicitar |
| **R04-D05** | C05 | **A:** fatos oficiais por domínio com proveniência/conciliação; sem usá-los para liberar S01 | B: aceitar evento não confirmado como prova comercial |
| **R04-D06** | C06 | **A:** separar direitos, memberships, entitlements e estados; matriz de precedência ainda pendente | B: assumir que status `ativo` libera todas as ações |
| **R04-D07** | C07 | **A:** divergência sob conciliação por owner, sem concessão/cobrança inferida nem suspensão arbitrária | B: escolher estado automaticamente sem fonte oficial |
| **R04-D08** | C08 | **A:** auditoria mínima, acesso segregado e retenção por finalidade/categoria a homologar | B: registrar payload integral com acesso amplo |

**Atenção:** aprovação futura das alternativas A, caso venha a ocorrer, **não** aprovará automaticamente C01..C08 nem encerrará SEG/CORE. Para cada contrato: submeter versões, owner competente, regras de precedência, evidências de homologação e critérios de aceite específicos em fase posterior.

## 11. Checklist para futura revisão de segurança (documental, não executar)

- Identificação de autoridade do estado por domínio IAM/CORE, FIN/CON, Portal, INT, auditoria/LGPD.
- Classificação campo × sujeito × organização × operação; negação não enumerativa.
- Contexto de tenant confiável e revogação, inclusive em cache/eventos, sem ALLOW implícito.
- Relógios trial (14 dias; retenção 60 após expiração), CRM pago (tolerância 7; cancelamento 30 dias de atraso; referência retenção 120 após cancelamento) e benefício Básico (transição 30 dias normal/restrita) **separados**, todos sujeitos a cotejo e homologação FIN/CON.
- Matriz de testes negativos **para futura elaboração/executação somente após autorização específica**, sem testes agora.
- Textos de precedência CORE02, CORE08, SEG-01/02, REV02-D01..12, Portal IN-119..123/COM-08/09, INT e LGPD ainda abertos; recuperar a pauta literal R01.

## 12. Controle de alcance

**Mantêm-se ABERTOS os 67 IDs técnicos CORE, SEG-01/02, AUT, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD, DEV-GATE e todos os demais gates.**  
**Nenhum** código, migrations, SQL, GRANT, ALLOW, teste funcional, rota, CI, alteração comercial, contratação, credencial, dados reais, VPS ou deploy.  
**Sem intervenção em** `main`, **PR #2**, **PR #21**, branch `dev/fast-track-crm-2026-11-04` ou produção.

**Saída desta etapa:** documento R04 v0.1 para deliberação sobre R04-D01..D08 (alternativas A propostas). Os contratos R02-C01..C08 continuam integralmente PENDENTES.
