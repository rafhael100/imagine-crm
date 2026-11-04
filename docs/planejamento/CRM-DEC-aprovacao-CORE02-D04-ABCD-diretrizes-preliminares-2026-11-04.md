# CRM-DEC — Aprovação preliminar CORE02-D04-A, B, C e D — classificação de campos e direitos

**Data:** 04/11/2026.  
**Manifestação literal do responsável:** “Aprovo CORE02-D04-A, B, C e D como diretrizes técnicas documentais preliminares do CRM Imagine. Registre no GitHub, mantenha CORE02-D04 aberta até o fechamento do catálogo real de campos, permissões por módulo e dependências com CORE01, CORE02-D05 a D10, SEG-01, SEG-02, INT e Segurança. Preserve todos os demais bloqueios e prossiga com CORE02-D05, sem programação, testes ou implantação.”

**Estado:** **CORE02-D04-A/B/C/D APROVADAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**. **CORE02-D04 CONTINUA ABERTA**; não há classificação física homologada, grant ativo, mudança de campo ou licença, implementação ou testes executados.  
**Pauta deliberada:** [CORE02-D04 — classificação de campos e direitos por operação v0.1](CRM-CORE-P0-ETAPA2-CORE02-D04-classificacao-campos-direitos-proposta-v0.1.md), seção 4. O aceite substitui os rótulos históricos de “proposta não aprovada” somente quanto a essas quatro subdiretrizes, **sem fechar o item D04**.

## 1. Aprovação com alcance estritamente preliminar

1. **CORE02-D04-A — classes e proprietário:** manter como referência documental **F-ID, F-BIZ, F-PII, F-RES, F-CONS, F-FIN, F-SEC, F-EVID e F-ART**, com mapeamento real futuro por módulo, recurso, campo, proprietário, finalidade e regime de tratamento. Classe conceitual **não cria coluna/tabela nem direito de acesso**.
2. **CORE02-D04-B — autorização conforme operação e canal:** distinguir, quando aplicável, visualização, criação/edição, pesquisa, IA, integração/compartilhamento, exportação e acesso a anexo; cada operação requer autorização confiável de backend e módulo proprietário, tenant e entitlement válidos, escopo/finalidade. **Exceção preexistente preservada:** para anexos, há uma **única permissão conjunta de visualizar/baixar**, não duas autorizações comerciais independentes.
3. **CORE02-D04-C — proteções especiais:** **F-SEC** sob cofre e autoridade própria, sem segredos em prompts, interface, CSV/export ou logs; **F-EVID** com acesso/auditoria controlados, sem exportação comercial genérica; **F-ART** com acesso autenticado, ACL e antimalware/estados de segurança, sem publicar URL como autorização. Não substituir CORE06, CORE09 ou IN-040.
4. **CORE02-D04-D — campos personalizados e derivados:** especificar classificação/versão e verificação de competência para novos campos ou mudanças de classe, com propagação das restrições a API, busca, relatórios, exportações, agentes e integrações; não permitir rebaixamento de proteção por rótulo ou instrução do cliente, sem regra autorizada pelo proprietário.

**A aprovação acima não é:** consentimento para compartilhar dados pessoais, definição de campos concretos por plano ou módulo, lista final de papéis, precedência de perfis, autorização financeira, alçada, token de provedor, método físico de RLS, desenho do cofre ou novo motor de segurança.

## 2. CORE02-D04 permanece expressamente ABERTA

| Dependência | Especificação ainda necessária |
|---|---|
| **Catálogo efetivo por módulo** | Inventário de campos/recurso/ação de DAT/VEN/ATD/AGD/AUT/IA/FIN/CON/INT e interfaces Sites/Portal, classificação, finalidade, proprietário e exceções |
| **CORE01-D04..D07** | Identidades e memberships por tenant, dados globais/tenant-bound, contextos/roles Laravel+PostgreSQL RLS, integridade e portabilidade |
| **CORE02-D05..D10** | Combinação de perfis, negações, direitos por módulo, MFA/alçadas, enforcement/revogação, limites dos planos, evidências |
| **SEG-01/SEG-02** | Matriz de autorização serviços×agente×ferramenta×ação×tenant e segregação de segredos, identidade técnica e cofre |
| **INT P0-OWN/SEC/CON/ACC e Segurança** | Direitos efetivos por ação e campo, fonte de verdade, autorização externa, contratos versionados e evidências futuras |
| **Outras frentes** | CORE03/04/05/06/07/08/09, LGPD e governança de dados, owners, FIN/CON, critérios CORE10, G01–G06 |

**Estado de todos os outros itens preservado:** CORE01-D04..D07 continuam integralmente abertos, embora suas subdiretrizes preliminares A/B/C(/D) estejam aprovadas; **CORE02-D05..D10** e **CORE03..CORE10-D04..D10** seguem abertos. Os **67 identificadores técnicos da Etapa 2 continuam abertos**. Também continuam abertos IN-168 ARQ-01/02/03, SEG-01/02, AGT-01, INT P0, FIN/CON, LGPD, licenciamento, custos e G01..G06.

## 3. Continuidade autorizada

**Preparar exclusivamente CORE02-D05 em forma documental**, apresentando alternativas e critérios para combinação de perfis, privilégios, negações, herança, delegação e escopos por tenant/equipe, sem declarar D05 aprovada nem decidir automaticamente regras de precedência, modelo físico de papéis ou permissões de clientes.

**Nenhuma programação, testes, migrações, SQL, criação/alteração de papéis, contas, usuários, credenciais, operações financeiras, mudanças em produção, provisionamento, contratação ou implantação está autorizada.** Estado global do CRM Imagine: **NO-GO para programação**.
