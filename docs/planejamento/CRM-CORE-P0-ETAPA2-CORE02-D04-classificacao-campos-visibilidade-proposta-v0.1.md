# CRM Imagine — CORE02-D04: classificação de campos, visibilidade e exposição por operação (Etapa 2, proposta v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DOCUMENTAL NÃO APROVADA — CORE02-D04 ABERTA.** CORE02-D04-A/B/C/D são diretrizes candidatas, não aceitações, classificações finais de todos os campos ou permissões executáveis.  
**Autorização recebida:** o responsável disse "aprovado" após recomendação de **continuar o planejamento de CORE02**; isso permite preparar esta minuta, **não** aprovar automaticamente D04-A/B/C/D, D04 integral, o DEV-GATE-00, especificações físicas ou programação.  
**Fase:** exclusivamente planejamento/documentação. **Sem código, testes, schemas/migrações, políticas RLS aplicadas, conexões, credenciais, pagamentos ou implantação.**

## 1. Base documental, escopo e não duplicação

- [CORE02 v0.1](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md): **CORE02-D01..D03 aprovadas como base conceitual**, incluindo autorização cumulativa por identidade/vínculo, tenant, entitlement, papel, escopo, recurso, ação, campo, estado e risco; vocabulário semântico de operações; fronteiras de usuários, suporte, agentes e externos. **D04..D10 integralmente abertas**.
- [CORE01-D07](CRM-CORE-P0-ETAPA2-CORE01-D07-modelo-fisico-integridade-RLS-proposta-v0.1.md) e [decisão preliminar A/B/C/D](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md): inventário das famílias de dados e autorização Laravel+PostgreSQL RLS como referência; **CORE01-D04..D07 continuam abertos** até contratos físicos, integridade e dependências.
- [Consolidação Etapa 2 R01](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md) E2-X02: um contrato compartilhado ator×recurso×ação×campo×tenant×plano, sem duplicar IAM, Auditoria Geral ou motor de políticas.
- [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md), [IN-118](IN-118-central-backup-portabilidade-dados.md) e [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md): classificação e exposição devem respeitar titularidade, finalidade, portabilidade, políticas LGPD, segurança, contratos e planos, ainda com detalhes pendentes.
- **Já aprovado:** para anexos, uma única permissão de **visualizar/baixar** (IN-040), e operações distintas de upload/exclusão; não dividi-la em duas permissões. Recursos Básico e cotas seguem planos/contratos previamente aceitos, sem recursos novos por inferência.

**Objetivo delimitado:** definir **como** deverá ser elaborado e validado o catálogo de campos por domínio, objeto, operação e canal; o catálogo **final** e suas opções físicas continuam pendentes da validação dos donos funcionais e das decisões CORE02-D05..D10. Esta proposta não classifica automaticamente todos os dados nem inventa papéis de produção.

## 2. Classes conceituais de referência — não são enums ou políticas aprovadas

| Classe histórica CORE02 §6 | Famílias de informação a classificar por proprietário | Princípio de proteção candidato |
|---|---|---|
| **F-ID — identificadores/vínculos** | Tenant, identity, membership, IDs de objetos, versão e referências externas | ID conhecido não é autorização; alteração/vinculação só por contrato competente, sem apontar a outro tenant |
| **F-BIZ — dados operacionais** | Etapa, status, tarefa, produto/serviço, prioridade, catálogo, valores não sensíveis quando aplicável | Escopo por recurso/ação/proprietário/equipe/unidade, plano e estado |
| **F-PII — dados pessoais** | Nome, telefone, endereço, e-mail, conteúdo de mensagens, dados de clientes | Minimização, finalidade e direitos explícitos por operação; impedir vazamentos indiretos em filtros/relatórios |
| **F-RES — protegidos/confidenciais** | Notas restritas, informações pessoais sensíveis quando existentes, dados especialmente protegidos | Acesso limitado a finalidade e autorização; não expor por concessão geral sobre o cadastro |
| **F-CONS — consentimentos e finalidade** | Origem de consentimento, tipo de aceite, versão, hora e comprovação | Não inventar aceite ou editar livremente a evidência; reconhecer titularidade da fonte |
| **F-FIN — financeiros** | Valores de proposta VEN, condições de pagamento da venda, contratos do SaaS e faturamento Imagine | **Distinguir** financeiro comercial do cliente de administração financeira do próprio CRM; direitos não transitivos |
| **F-SEC — segredos e autenticação** | Senhas, tokens, chaves, MFA, credenciais de provedores | Segredo não vai a prompts, exportação, telemetria ou respostas em claro; propriedade de CORE04/09/SEG-02 |
| **F-EVID — evidências e auditoria** | Identidade causal, alterações, logs, provas de aceite, consentimento, decisões de segurança | Consulta por categoria, finalidade e competência; não permitir reescrever histórico oficial |
| **F-ART — artefatos e derivados** | Anexos, prévias, backups, exports, gravações e outros binários autorizados | Permissão conjunta ver/baixar, se aplicável e conforme módulo; upload/exclusão e estado de antimalware separados |

**Classificação é orientadora, não presunção de conteúdo:** um campo pode exigir múltiplas restrições simultâneas (ex.: documento pessoal que também é prova contratual), e o dono deve definir precedência de classificação. Não considerar toda nota como dado sensível legal ou toda cifra VEN como informação do faturamento Imagine.

## 3. Matriz de campos por proprietário — a ser detalhada, não homologada

| Domínio | Exemplos de recursos/campos candidatos | Propriedade de validação e riscos específicos |
|---|---|---|
| **IAM / CORE** | Conta de acesso global, nome/login, MFA, membership, convite, papel, equipe, estado, metadados de sessão | CORE01/02/04/07; identidade global protegida, participação tenant-bound, administrador Básico com MFA |
| **DAT / Leads e Contatos** | Nome, contato, origem, observações, campos personalizados, consentimento, anexos, importação e mesclagem | DAT/REV03; consentimento e dados pessoais, sem mesclagem automática, separar ler/editar/exportar |
| **VEN / Vendas** | Etapas, valores de oportunidade, propostas, desconto, aceites, histórico, responsável | VEN; aprovar/emitir proposta distinto de visualizar/editar, direito de desconto por operação |
| **ATD / Atendimento** | Mensagens, canais, número atendido, histórico, classificação, notas internas, anexos | ATD; equipes/caixas/atribuição e integridade de histórico, sem leitura ampla por somente ser usuário |
| **AGD / Agenda** | Agendamento, cliente, profissional, recurso/unidade, disponibilidade, dados especiais de atendido | AGD; link público/visitante ≠ membership; profissional agendável ≠ assento humano |
| **AUT / IA** | Gatilho, condição, ação, execução, histórico/versão, prompts, resumo e inferências | AUT/IA; editor nativo do CRM por plano, n8n Imagine interno, nenhuma autorização por prompt |
| **FIN/CON / Admin Imagine** | Plano, assinatura, benefício cortesia, trial, crédito IA, cobranças, pagamentos, nota fiscal, concessão | FIN/CON; separação entre financeiro SaaS e operação comercial do tenant, sem gratuidade não autorizada |
| **INT / Portal/Sites** | Destino de formulário, conexão, segredo referenciado, autorização de evento, import/export, vinculação | INT/Sites; não captar sem CRM autorizado, não redirecionar lead pendente, cofre sem segredo em claro |
| **AUD / Arquivos** | Eventos e provas, logs, upload, prévia, quarentena, exportação, download, backup | Auditoria/IN-118/ARQ; LGPD e retenções próprias, estado de segurança e direitos por ação |

**Nenhuma enumeração exemplificativa é suficiente para encerrar D04**: antes do fechamento deve existir campo real e proprietário declarado por entidade/versão, com fonte aprovada. Profissionais de agenda, agentes técnicos, convites pendentes, usuários humanos e tokens externos não recebem a mesma categoria por analogia.

## 4. Quatro diretrizes CORE02-D04-A/B/C/D propostas para deliberação posterior

### CORE02-D04-A — Dicionário obrigatório de campo/entidade, classe e proprietário (PROPOSTA)

Para cada módulo/proprietário, manter **um catálogo versionado por entidade e campo**, com identificação lógica, origem, tipo semântico, classe(s) de proteção, tenant/global, finalidade, owner de decisão, consentimento/base aplicável quando relevante, estados, retenção e vínculos com o catálogo de ação. Dados de uso comum que se tornem sensíveis por contexto devem ser reclassificáveis mediante autorização, trilha e versão; nenhuma exceção global presumida.

**Não aprova:** nomes SQL, campos físicos completos, enum/índice ou novos dados a coletar; depende de CORE01-D07, proprietários, LGPD.

### CORE02-D04-B — Exposição por operação, escopo e canal (PROPOSTA)

O direito por campo não decorre somente de “pode ver o registro”: distinguir, segundo o módulo, **ler, criar, editar, pesquisar/filtrar, usar em automação/IA, compartilhar/enviar, importar, exportar/baixar e excluir**, com nível próprio/equipe/unidade/organização, ação e finalidade. **Backend** é fonte de proteção também para API, filas, busca, relatório, CSV/XLSX, anexos e material entregue por integração; UI mascarada não equivale a bloqueio no servidor.

**Não aprova:** regras de papéis múltiplos, catálogo final de permissões, middleware, schema RLS ou concessões; essas escolhas dependem CORE02-D05/D06/D08, CORE03/SEG.

### CORE02-D04-C — Campos personalizados, derivados e dados em exportação (PROPOSTA)

Campos personalizados **não podem escolher categoria inferior** de proteção para dados que devam ser restritos: o dono da plataforma deve prever categoria/política/validação por tenant e competência, contemplando importação, cópia de dados, resumo IA, índices de busca, relatórios, histórico, versões e derivados. Campos/artefatos exportados devem respeitar as mesmas restrições pertinentes ao resultado, e **não carregar valores ocultos por meio de campos extras**. Anexos seguem permissão conjunta ver/baixar e ainda podem ficar bloqueados por malware/segurança mesmo com permissão.

**Não aprova:** algoritmo de detecção automática de dados sensíveis, classificação legal universal, revisão automática por IA, novo limite comercial ou exceção de export; detalhes por módulo e LGPD continuam abertos.

### CORE02-D04-D — Evidências de negação, exceções e reconciliação (PROPOSTA)

Cada proprietário de campo/ação deverá documentar **exemplos autorizados e negados**, como usuário A tentar ler B, gestor ler nota privada de outra equipe, exportar campo mascarado, consulta externa usando token de agendamento, agente tentar ler segredo, funcionário modificar consentimento sem autoridade e admin Imagine sem mandato. Exceções justificadas por suporte/auditoria devem ser tipadas e limitadas; a prova documental inclui fonte, versão, responsável e casos de homologação **futura**. Não duplicar sistemas de auditoria, testes ou políticas do CORE10.

**Não aprova:** matriz definitiva, autoridade excepcional, política de erro, testes realizados nem qualquer “PASS”. Dependências CORE02-D05..D10, CORE05/06, INT SEC/ACC e SEG-01/02.

## 5. Distinguir decisões pendentes — proibição de fechamento indireto

| ID | Parte do contrato de acesso que falta escolher/validar | Estado |
|---|---|---|
| **CORE02-D04** | Classificação final campo×ação×escopo×proprietário e regras de exposição por todos módulos | **ABERTA; A–D propostas NÃO APROVADAS** |
| **CORE02-D05** | Precedência/negação explícita, herança de papéis múltiplos, perfil customizado, autoelevação, equipe/unidade | ABERTA |
| **CORE02-D06** | Catálogo final de operações/ações por DAT/VEN/ATD/AGD/AUT/IA/FIN/CON/INT/Sites | ABERTA |
| **CORE02-D07** | Ações sensíveis, MFA/step-up, segunda aprovação quando já prevista, alçadas e riscos | ABERTA |
| **CORE02-D08** | Contratos físicos Laravel/RLS, sincronização de revogações e autorizações backend/cache/jobs | ABERTA |
| **CORE02-D09** | Direitos/quotas finais por plano (Básico, Essencial, Profissional, Empresarial) | ABERTA |
| **CORE02-D10** | Roteiros canônicos, evidências, donos, gates e versão de homologação documental | ABERTA |

Além disso, **CORE01-D04/D05/D06/D07 integrais ABERTAS**, mesmo tendo suas subdiretrizes aceitas preliminarmente. **SEG-01/SEG-02, ARQ-01/02/03, AGT-01, INT P0 e G01..G06, FIN/CON, LGPD, licenças, custos e DEV-GATE-00 NO-GO** preservados.

## 6. Critérios negativos para homologação futura — nenhuma execução

| Cenário | Resultado esperado, ainda sem PASS |
|---|---|
| Utilizador autorizado a ler lead A tenta recuperar registro B pelo ID ou pesquisa | Negar acesso cross-tenant inclusive índices, arquivos e relatórios |
| Gestor vê campo genérico de oportunidade, mas não nota restrita de outra equipe | Negar campo protegido sem quebrar autorização legítima ao restante |
| Export CSV permite ler contato, mas inclui CPF ou nota de classe mais restrita | Restringir campo segundo política; não vazar por serializer |
| Campo personalizado é nomeado “observações”, mas armazena informação especialmente protegida | Tratar revisão/classificação contextual; nome não reduz proteção |
| Funcionário recebe link compartilhado de anexo sujeito a bloqueio antimalware | Negar conteúdo e derivados independentemente de permissão |
| Agente recebe instrução para usar valor confidencial em prompt externo | Negar acesso não autorizado e preservar categoria/auditoria |
| Admin Imagine sem mandato consulta ficha comercial de tenant | Negar, sem bypass pelo papel central |
| Usuário tem permissão de visualizar, mas tenta alterar prova de consentimento | Negar edição sem competência específica |
| Exportação/backup preparada antes de desligamento é baixada depois da revogação | Revalidar direito no fornecimento conforme CORE03/07 |

Referenciar CORE02-T, CORE01-T, CRM-REV01 S01..10, OPS-T e INT HT existentes antes de criar suites novas. Todos os testes continuam **NÃO EXECUTADOS**. A aprovação de critérios não equivale a homologação técnica.

## 7. Encaminhamento de deliberação e estado documental

**Pauta futura para manifestação expressa:** aprovar ou ajustar **CORE02-D04-A, B, C e D SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**, mantendo **CORE02-D04 aberta** até a lista real de campos/entidades e a reconciliação CORE02-D05..D10, CORE01-D04..D07, SEG-01/02, INT/FIN/LGPD. Próximo eixo proposto depois de aceite: **CORE02-D05 — herança/precedência/negações e perfis**, sem escolher implicitamente uma política.

**Estado atual:** nenhuma CORE02-D04-A/B/C/D foi aprovada nesta minuta; nenhuma permissão, campo físico, regra de segurança implementada, infraestrutura, assinatura ou produto contratado. O progresso é exclusivamente documentação GitHub.
