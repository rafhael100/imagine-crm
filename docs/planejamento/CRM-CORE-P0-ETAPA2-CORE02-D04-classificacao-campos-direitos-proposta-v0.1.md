# CRM Imagine — Etapa 2 / CORE02-D04: classificação de campos e direitos por operação (proposta v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DOCUMENTAL NÃO APROVADA — CORE02-D04 ABERTA; D04-A/B/C/D aguardam deliberação.**
**Relação:** [Etapa 2 R01 — contratos compartilhados](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md), especialmente E2-X02; [CORE02 matriz canônica seção 6](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md); [CORE01-D07 aceite preliminar](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md); [SEG-01/02](IN-168-matriz-pendencias-fechamento-arquitetura.md).

**Propósito:** começar o fechamento **documental por decisão técnica**, sem atribuir nova permissão a usuário ou serviço e sem confundir classificação de campo com autorização efetiva, controle de linha RLS ou entitlement. Não criar motor paralelo de autorização, esquema físico, configuração, endpoints, SQL, automações, testes ou implantação.

## 1. Critério de projeto e fronteiras

O CRM Imagine deverá distinguir: (a) identidade/principal e tenant legitimado; (b) módulo/proprietário da operação; (c) recurso e campo; (d) ação, finalidade e canal; (e) papel, escopo de equipe/unidade, entitlement e estado; (f) política de acesso/negativa/MFA; (g) proteção de dado pessoal, segredo, auditoria e anexo. **Todos os fatores aplicáveis são cumulativos**, não grants automáticos.

RLS PostgreSQL protege linhas e não fornece, por si, visibilidade seletiva de campos, mascaramento em respostas, permissão de exportação, autorização de arquivo/blob, busca nem exposição em IA. Laravel/backend e controladores dos módulos devem aplicar essas verificações sem bypass por exportação, relatório, integração ou execução assíncrona.

Nenhuma classificação autoriza novo campo pessoal, nem presume finalidade legítima ou consentimento para compartilhar dados externamente. O esquema e a implementação dependem de CORE01-D07/CORE02-D08/SEG-01.

## 2. Classes documentais existentes a preservar, ainda sem catálogo físico

| Código | Categoria conceitual | Exemplos semânticos, não inventário aprovado | Fronteira mínima de proteção |
|---|---|---|---|
| **F-ID** | IDs e escopo | tenant, identidade, vínculo, IDs internos e versão | Não usar campo recebido como autoridade, proteger mudanças de owner e enumeração |
| **F-BIZ** | Dados operacionais | etapa comercial, tarefa, descrição de serviço, status | Autorização por ação, entidade, empresa/equipe/unidade e proprietário |
| **F-PII** | Dados pessoais | nome, e-mail, telefone, histórico de interação | Minimização e finalidade; busca, IA, uso por integração e exportação não herdam direito de visualização |
| **F-RES** | Informação restrita/sensível | notas confidenciais e dados pessoais especialmente protegidos | Critérios adicionais por finalidade, competência, mascaramento e políticas LGPD |
| **F-CONS** | Consentimento e prova | opt-in, origem, prova, data e versão da finalidade | Não criar ou alterar consentimento por inferência ou mero acesso ao cadastro |
| **F-FIN** | Informações financeiras | valor da proposta VEN, fatura e concessão CRM FIN/CON | Separar negociar venda do cliente de alterar mensalidade SaaS |
| **F-SEC** | Credenciais e fatores | token, segredo, chaves, MFA | Não expor valor em API, exportação de CRM, IA ou log; cofre CORE09/SEG-02 |
| **F-EVID** | Trilha/auditoria | referência de fato, executor/ator, decisão e proveniência | Consulta/exportação com alçada específica; imutabilidade conforme CORE06 |
| **F-ART** | Arquivos e derivados | anexo, prévia, relatório exportado, backup, mídia | Direito ver/baixar conforme política já aprovada, antimalware, retenção e ACL; não delegar por URL pública |

**Observação:** as categorias não substituem o catálogo real do módulo nem determinam automaticamente se um dado é “sensível” segundo a LGPD; essa análise precisa de finalidade, contexto, classificação concreta e revisão do proprietário.

## 3. Matriz de capacidades a especificar por módulo, sem concedê-las

Para cada módulo DAT/VEN/ATD/AGD/AUT/IA/FIN/CON/INT/Portal/Sites, descrever **somente os recursos que ele realmente possui**, preservando seu owner:

| Capacidade / canal | Decisão documental necessária | Negativa ou restrição |
|---|---|---|
| Ler em tela/API | Quem lê campo/recurso no tenant atual, com escopo e finalidade | Não confundir direito ver registro com ver todos os campos |
| Criar/editar/excluir | Quem altera campo/estado e qual consentimento/registro/versionamento é necessário | Não alterar titularidade, CRM entitlement, auditoria ou consentimento por edição de cadastro |
| Pesquisar/filtrar/listar/relatar | Quais valores podem ser utilizados/retornados em índices e consultas, inclusive autocomplete | Não vazar valores proibidos por busca, erro, contagem ou relatório |
| Exportar/importar/backup | Quais campos são portáveis, mascarados, omitidos e com qual contexto autenticado | Não incorporar segredos, trilhas protegidas ou outro tenant por exportação |
| Disponibilizar a agente/IA | Quais dados mínimos e ferramentas autorizadas para finalidade/tenant/caso | Prompt ou modelo não é autorizador; segredos e dados restritos não liberados por analogia |
| Compartilhar com provedor/canal | Qual conector/conta, finalidade, autorização externa e contrato de serviço | Token de provedor não gera grant interno ou consentimento |
| Anexo, prévia, download | Quais arquivos/derivados estão aprovados, com validação antimalware e acesso autenticado | URL/download não ignora a política por tenant/estado/quota |
| Suporte/auditoria | Quando existe mandato por caso e qual evidência/escopo a Auditoria Geral disponibiliza | Admin Geral não adquire leitura comercial universal |

**Campos personalizados:** classificar e versionar campos na criação/alteração com validação por competência, sem permitir que rótulo de cliente converta campo especialmente protegido em público. Limites, UX, taxonomia por domínio e exceções ficam pendentes e sujeitos a aceite específico de CORE02-D04 e donos de módulos.

## 4. Quatro diretrizes CORE02-D04 para eventual aprovação — PROPOSTAS NÃO APROVADAS

- **CORE02-D04-A — catálogo por classes e proprietários:** aprovar preliminarmente as classes F-ID/F-BIZ/F-PII/F-RES/F-CONS/F-FIN/F-SEC/F-EVID/F-ART como referência documental, exigindo mapeamento real por módulo/recurso/campo, owner, finalidade e regime de tratamento, sem inventar campos físicos.
- **CORE02-D04-B — diferenciação por operação/canal:** aprovar preliminarmente que ver, editar, pesquisar, usar IA, compartilhar via integração, exportar e baixar arquivos sejam **autorizações distintas quando aplicável**, verificadas por backend/owner com tenant/entitlement e finalidade; classificação não é concessão de direitos.
- **CORE02-D04-C — proteção de credenciais, auditoria e artefatos:** aprovar preliminarmente tratamento especializado para segredos F-SEC, evidências F-EVID e arquivos F-ART, sem segredos em IA/CSV/log, sem exportação geral de trilha e sem acesso público por URL; regras específicas de anexo e cofre prevalecem.
- **CORE02-D04-D — classificação de campos personalizados e derivados:** aprovar preliminarmente a necessidade de validação/versionamento da classe de campos adicionais e de propagação das restrições a busca, relatórios, derivados/IA e integrações, sem fixar algoritmo de classificação ou aprovar compartilhamento.

**Se aprovadas, D04-A/B/C/D permanecerão DIRETRIZES PRELIMINARES**, e **CORE02-D04 continuará ABERTA** até concluir catálogo real de cada módulo, masking e acesso por perfil/campo, direitos de campos customizados, consentimento, owners, contratos RLS e aceites finais.

## 5. Não antecipar decisões de CORE02-D05..D10

| Item ainda aberto | Tema reservado para decisão especializada |
|---|---|
| **CORE02-D05** | Combinação de papéis, negação/precedência, perfis customizados, herança/delegação e equipes |
| **CORE02-D06** | Catálogo completo por ação e módulo proprietário, inclusive finance, agenda, leads, atendimento e IA |
| **CORE02-D07** | Alçadas, MFA/step-up e confirmações de ações críticas conforme política específica |
| **CORE02-D08** | Permissões/roles reais, enforcement por backend e RLS, cache, versionamento, revogação e worker |
| **CORE02-D09** | Fronteiras de plano/quotas/adicionais/direitos do Básico, trial e demais planos, valores finais |
| **CORE02-D10** | Evidência, donos e homologação futura, matriz CORE/SEG/INT e critérios para gates |

Também **CORE01-D04..D07 permanecem todos abertos**, apesar das subdiretrizes preliminares aprovadas. Não fechar CORE02-D04 por classificação conceitual apenas.

## 6. Casos documentais de verificação futura — NÃO EXECUTADOS

| Ref | Caso negativo | Critério futuro |
|---|---|---|
| CORE02-D04-T01 | Usuário pode ver lead, tenta exportar campo restrito | Exportação nega/omite conforme escopo, sem inventar autorização |
| CORE02-D04-T02 | Campo pessoal acessível na interface aparece sem direito no mecanismo de busca/IA | Filtragem/mascara por canal e finalidade, não pelo frontend |
| CORE02-D04-T03 | Usuário altera campo de consentimento sem fato oficial | Negação, prova e auditoria |
| CORE02-D04-T04 | Operador conhece token de provedor, tenta acessar F-SEC por API | Negar retorno do valor do segredo |
| CORE02-D04-T05 | Backup/CSV de cliente inclui F-EVID de outra organização | Negar por tenant, classe e finalidade |
| CORE02-D04-T06 | URL pré-assinada de anexo B tenta ser reutilizada em A ou depois de revogação | Acesso sujeito a contrato de objeto/estado, sem cache indevido |
| CORE02-D04-T07 | Administrador do cliente cria campo personalizado e o marca público para contornar classe | Não permitir rebaixamento de proteção sem autorização e regras próprias |
| CORE02-D04-T08 | Admin Imagine usa perfil global para ler nota restrita de empresa sem mandato | Negar segundo CORE05 e contexto |
| CORE02-D04-T09 | Perfil pode gerir proposta de venda mas altera concessão de mensalidade SaaS | Negar ação de FIN/CON |
| CORE02-D04-T10 | Conector autorizado recebe campo fora da finalidade | Minimizar/omitir e auditar, sem confiar apenas em token |

Reutilizar casos CORE02-T, CORE01-D07-T, REV01-S, INT HT e CORE10; **não criar uma bateria executável paralela**. Nenhum cenário foi executado.

## 7. Limites, owners e continuidade

**Dependências bloqueantes:** CORE01-D05/D07, CORE02-D05/D06/D08/D09, CORE03 (jobs), CORE04/05 (MFA/suporte), CORE06 (auditoria/LGPD), CORE07 (membership/assentos), CORE08 (planos), CORE09 (segredos/IA), SEG-01/02, INT P0-OWN/SEC/CON e G01..G06, FIN/CON, Portal/Sites, owners de cada módulo.

**Todas as 67 decisões técnicas CORE da Etapa 2 continuam ABERTAS.** A proposta deste arquivo não aprova D04-A/B/C/D por silêncio, não cria permissão, não valida schema, não executa testes e não altera a regra de **NO-GO para programação**.

**Vedações:** programação, migrations, SQL de RLS, criar/alterar roles, scripts, testes de software, provisionamento, gastos, mudanças produtivas, uso de credenciais/dados de clientes ou implantação.
