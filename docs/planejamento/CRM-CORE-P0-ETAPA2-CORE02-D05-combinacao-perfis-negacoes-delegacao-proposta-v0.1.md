# CRM Imagine — Etapa 2 / CORE02-D05: combinação de perfis, negações, herança, delegação e escopo (proposta v0.1)

**Data:** 04/11/2026.  
**Estado atualizado em 04/11/2026:** **CORE02-D05-A/B/C/D aceitas apenas como diretrizes documentais preliminares** [por manifestação expressa](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md). **Decisão técnica delegada posterior selecionou a política-base P1 (união de grants válidos) + P2 (negações explícitas válidas estritamente escopadas)** [aqui](CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md). P3/P4 não são padrão. **CORE02-D05 integral continua ABERTA** até contratos, matriz física e validações com proprietários, SEG/INT, sem grants, SQL, programação ou testes. Textos seguintes que dizem “P1–P4 não escolhidas” são históricos anteriores à decisão delegada.
**Objetivo:** propor uma base de governança para conciliar múltiplos perfis, direitos positivos, bloqueios explícitos, funções customizadas, equipes/unidades, delegação e revogação **sem conceder permissões reais nem criar motor IAM independente**. Não decidir SQL, schema, papéis reais, política definitiva de prioridades ou funções de produtos por analogia.

## 1. Precedência das decisões existentes

- [CORE02-D01–D03 — base documental](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md) e [CORE02 original, seções 2, 4, 7 e 8](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md): autorização cumulativa por identidade, tenant, vínculo/mandato, entitlement, recurso, ação, campo, estado e dono. **Negação por padrão**. O tratamento concreto de *múltiplos grants e denies* foi reservado a CORE02-D05.
- [CORE02-D04-A/B/C/D — aceite preliminar](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md): classes F-ID/F-BIZ/F-PII/F-RES/F-CONS/F-FIN/F-SEC/F-EVID/F-ART, distinção por ação/canal, segurança de segredos, trilhas e artefatos, propagação de classificação; **CORE02-D04 permanece aberta** até catálogo real e direitos por módulo.
- [CORE01-D04-A/B/C](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md): membership efetiva única por identidade×tenant, convites não são grant, desligamento por tenant sem desligamento global.
- [CORE01-D05-A/B/C/D](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md) e [D07-A/B/C/D](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md): tenant validado por operação, Laravel + PostgreSQL RLS, revogação/contexto em workers e dados tenant-bound; **CORE01-D04..D07 ainda abertos**.
- [CORE07-D01..D03](CRM-DEC-aprovacao-CORE-P0-07-D01-D03-base-convites-assentos-equipes-2026-11-04.md): assentos, equipes, convites, desligamento; **CORE07-D04..D10** ainda precisam de políticas de reserva, admin único, visibilidade, atributos de equipe e revogação.
- [CORE04](CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md), [CORE05](CRM-CORE-P0-05-acesso-excepcional-imagine-mandatos-suporte-proposta-v0.1.md), [CORE09](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md): MFA/step-up, mandatos, agentes/identidades de serviço e SEG-01/02 não se resolvem por editar papel de usuário.
- [CORE08-D01..D03](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md): **Básico 1 administrador autenticado com MFA**; site cortesia e Básico pago têm mesmas capacidades do plano, não usuários adicionais. Trial e planos pagos usam direitos próprios; valores/quotas finais CORE08-D09 permanecem abertos.
- [Etapa 2 R01](CRM-CORE-P0-ETAPA2-R01-consolidacao-contratos-e-dependencias-tecnicas-v0.1.md), E2-X01, E2-X02, E2-X04 e E2-X09; [matriz da Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) — 67 IDs técnicos abertos; [INT P0](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) G01..G06 abertos.

**Separar conceitos:** ter dois perfis não equivale a dois assentos; papel customizado não cria módulo contratado; permissão `read` não implica `export`; conceder escopo da equipe não equivale a acessar toda a empresa; dono de registro não confere poderes globais; nem Administrador Geral Imagine nem agente assumem acesso aos dados comerciais sem autorização/mandato.

## 2. Modelo lógico de avaliação de autorização — comparação, não algoritmo final

**Critério cumulativo mínimo já aceito:** identidade/serviço autenticado → tenant/mandato válido → membership ativa quando aplicável → plano e entitlement → estado/regra do módulo → autorização por recurso/ação/campo e escopo → finalidade/MFA/alçada e aprovações quando necessárias → execução pelo proprietário → auditoria/revalidação.

| Camada de decisão | Restrição irremovível por papel do cliente | Questão ainda em aberto |
|---|---|---|
| **Tenant/membership/mandato** | Nenhum perfil autoriza tenant de outra empresa, vínculo suspenso ou suporte sem mandato válido | Política física de contexto/erro em CORE01/CORE05/SEG |
| **Plano/entitlement/quotas** | Nenhum grant de papel habilita produto, canal ou usuário além do plano | Quotas finais e upgrades em CORE08-D09/FIN/CON |
| **Recurso/ação/campo/estado** | Operação precisa existir no dono do módulo; exclusão, exportação, consentimento, trilha e segredos têm controles próprios | Catálogo e classes por módulo CORE02-D04/D06 |
| **Perfis atribuídos** | Função pode conceder somente subconjunto já autorizado e escopado | Como agregar positivos/negações entre vários papéis CORE02-D05 |
| **Escopos organizacionais** | Membership só dá acesso ao tenant; regras de equipe, unidade, caixa de atendimento e posse ainda valem | Interseção/união de escopos por recurso, herança e vigência |
| **Ações sensíveis** | Não burlar MFA obrigatório, step-up/alçadas por ação ou regras de negócio | CORE02-D07/CORE04/CORE05/INT CT-02 |
| **Assíncronos/IA/suporte** | Token, prompt, workflow e sessão anterior não criam novo grant | Delegação/revalidação CORE03/CORE07/CORE09/SEG-01/02 |

Uma lista de perfis não é autorização suficiente: a mesma avaliação deve valer para API, pesquisa, dashboards, import/export, downloads, automações e jobs, conforme as respectivas capacidades.

## 3. Questão central ainda não decidida: composição de permissões

| Alternativa documental | Como funcionaria em tese | Benefícios | Cuidados e lacunas |
|---|---|---|---|
| **P1 — união de concessões válidas, limitada por regras superiores** | Mais de um perfil pode habilitar ações **somente** quando tenant/entitlement/risco/campo/escopo permitem; ausência de grant nega | Configuração compreensível de responsabilidades acumuladas | Pode ampliar acesso sem perceber; negações explícitas exigem política adicional e escopo granular |
| **P2 — negação explícita prevalece sobre concessão para a mesma ação/escopo** | Uma negativa expressa qualificada pode bloquear ação que outro perfil concederia, sem vencer direitos/obrigações mandatórias nem criar controle global | Ajuda a proteger restrições intencionais e funções incompatíveis | Duplo vínculo pode gerar bloqueio inesperado; é necessário definir prioridades, proveniência de deny, escopo, conflito e UX de diagnóstico |
| **P3 — interseção estrita entre perfis** | Todo perfil atribuído precisaria autorizar a ação | Reduz alargamento por acumulação | Pode ser impraticável e transformar papel auxiliar em veto de todos os direitos |
| **P4 — papel principal como fonte única** | Escolher um perfil principal e ignorar direitos de funções secundárias | Previsibilidade | Pode contrariar múltiplas equipes/responsabilidades já previstas e exigir exceções operacionais |

**Recomendação preliminar para discussão:** considerar **P1 com bloqueios mandatórios sempre prevalecendo** e **P2 para negações explícitas específicas, somente se a política de precedência for aprovada**. **NÃO é decisão já tomada** de que todo deny de qualquer perfil vence todos os grants. O efeito de deny proveniente de perfil, política de segurança, plano ou estado tem significados diferentes; negar serviço obrigatório, direitos de portabilidade ou acesso legítimo em outra organização por composição de perfis pode ser indevido. Exige exemplos normativos e deliberação separada.

**Modelo de resultado:** permitir / negar / exigir condição adicional conforme política competente; não criar categoria técnica final de resposta nesta etapa. Resultado inconclusivo ou falta de regra confiável não pode ampliar privilégio: negar a operação protegida e registrar causa adequada, sem bloquear indiscriminadamente rotinas autônomas de outros tenants.

## 4. CORE02-D05-A — Limites cumulativos e fontes de direito (PROPOSTA)

Adotar como base que nenhuma combinação de perfis supera **tenant/membership/mandato vigente, plano, estado, recurso/ação/campo, finalidade, posse/escopo e regras mandatórias do proprietário**. Direitos positivos só são elegíveis dentro desses limites; ausência de grant específico nega. **Papéis não são licenças**, e o Administrador Geral Imagine não obtém leitura comercial universal.

Aprovar D05-A **não** escolheria ainda regras finais de união/interseção entre múltiplos perfis nem reclassificação de campos.

## 5. CORE02-D05-B — Precedência de grants, denies e herança (PROPOSTA)

Adotar como diretriz preliminar a obrigação de uma **tabela determinística de precedência** por tipo de direito e fonte de restrição. Proposta de princípios para futura decisão:

1. Bloqueios mandatórios de segurança, tenant, campo, plano, consentimento/finalidade, quotas e suspensão **não podem ser anulados** por grant de cliente.
2. Considerar combinação de grants positivos válidos apenas para **a mesma empresa e escopo**, nunca replicando privilégios cruzados por múltiplas memberships/tenants.
3. **Negação explícita de perfil** exige definição de proprietário, abrangência, prioridade e impacto com outros perfis; testar P1/P2/P3/P4 antes de fixar se deny de papel sempre prevalece. **Não aprovar universalidade de deny por esta diretriz**.
4. Herança entre função de equipe/gestor e papel customizado só onde catalogada; **sem** inferir que gestor tem `export`, `manage_roles`, `manage_subscription` ou permissão de caixa de atendimento de outra equipe.
5. Regras mais restritivas de um objeto (dados confidenciais, malware, processamento de consentimento e alçadas) valem mesmo com grant válido para o recurso geral.

**D05-B, se aceita, aprovaria apenas o compromisso de fechar essa tabela, não uma política de precedência efetiva.**

## 6. CORE02-D05-C — Perfis personalizados, delegação e prevenção de autoelevação (PROPOSTA)

Propor o contrato de administração de permissões:

- Administrador competente do tenant cria/edita perfis customizados **dentro de ações/recursos disponíveis no plano e no escopo que lhe foi autorizado delegar**, não qualquer ação que ele apenas consiga executar por um mandato excepcional.
- Separar **criar/editar papel**, **atribuir/revogar papel**, **definir delegação** e **aprovar ação crítica** como competências diferentes. Para todo grant alterado, validar que o delegante possui **poder legítimo de conceder** o direito e não só de usá-lo.
- Proibir **autoelevação**, alteração de política global Imagine, criação de administrador geral, concessão de assinatura/plano pago ou gratuito, bypass de MFA/RLS e acesso a tenant diverso. Qualquer atribuição excepcional exige fluxo próprio CORE04/05/07/FIN, sem presumir segundo aprovador obrigatório para todos os atos.
- Manter identidade global separada de **membership efetiva**. Revogar um papel na empresa A não altera outra membership B. Delegações de duração limitada, justificadas por caso/objeto, se admitidas, precisam de expiração, fonte/escopo e auditoria; **tempos concretos não definidos**.
- Garantir que o **Básico permanece com um único administrador humano autenticado** e MFA, sem inventar segundo usuário por substituição provisória, perfil técnico ou convite.

**Não decidido:** quantos perfis são permitidos, se usuário pode se atribuir um papel previamente homologado, política de aprovação de alteração de função, formato de delegação, UX do construtor, política de exceções e custos por usuário. São CORE02-D05/CORE07-D05/D06/CORE08-D09 e owner da Segurança.

## 7. CORE02-D05-D — Equipes, unidades, posse, revogação e explicabilidade (PROPOSTA)

Propor exigir um **escopo de autorização verificável por operação**, sem supor que a soma de equipes = visão da empresa:

- Dimensões candidatas **próprio, equipe, unidade e organização inteira** conforme módulo/plano; em ATD/AGD, também canal/número/caixa, profissional e caso, sob regras específicas do proprietário.
- Participação em múltiplas equipes pode gerar demandas legítimas de acesso distintas; a política de união/interseção de escopos, visibilidade de históricos, gestores substitutos, ausências e redistribuições fica **pendente de decisão específica** de CORE07-D07 e módulos.
- Quando perfil/membership/entitlement/atribuição/posse muda, invalidar novas avaliações baseadas em direito obsoleto, respeitando revogação de sessão, cache, tokens, exportações e jobs (**CORE03/04/07/SEG**). **Não** reverter eventos de negócio confirmados legitimamente.
- Dar diagnóstico de **por que uma ação foi negada** a atores administrativos competentes de modo seguro, sem revelar campos restritos, segredos, dados do tenant errado ou política confidencial. Histórico auditável por categoria CORE06 e privacidade aplicáveis.
- Agentes de IA, n8n e serviços não herdam perfis humanos livremente; só podem agir com identidade técnica, contexto, ferramenta, versão e mandato autorizados.

**D05-D não cria política física de cache TTL, fila, versão de permissão, índices, equipe de suporte ou escopo de produto.**

## 8. Exemplos operacionais e casos negativos para homologação futura — NÃO EXECUTADOS

| ID | Situação | Resposta esperada no contrato futuro |
|---|---|---|
| CORE02-D05-T01 | Funcionário com perfil de vendas e financeiro tenta conceder plano CRM gratuito | Negar: competência Imagine/FIN, não união de papéis do cliente |
| CORE02-D05-T02 | Pessoa tem memberships A e B, administrador em A e leitor em B | Direitos de A não se propagam a B |
| CORE02-D05-T03 | Gestor de equipe A vê lead de B por ID direto ou relatório | Negar se escopo não autoriza B; Laravel+RLS e campo |
| CORE02-D05-T04 | Admin Básico tenta atribuir segundo usuário por criar outro papel | Não criar segundo assento nem login operacional |
| CORE02-D05-T05 | Funcionário se atribui papel que concede `export_data` | Negar autoelevação sem competência apropriada |
| CORE02-D05-T06 | Dois perfis dão grant e deny conflitantes para a mesma ação | Aplicar tabela de precedência **ainda a deliberar**, nunca soma permissiva cega |
| CORE02-D05-T07 | Perfil concede `read`, outra condição exige MFA/consentimento para exportação | `read` não dispensa ação/finalidade/MFA apropriados |
| CORE02-D05-T08 | Pessoa integra equipes A e B, mas canal ATD pertence apenas à caixa A | Não atribuir visibilidade de B por analogia; contrato de canal prevalece |
| CORE02-D05-T09 | Convite pendente contém papel administrativo, pessoa ainda não aceitou | Não conceder acesso; CORE01-D04/CORE07-D04 e quota |
| CORE02-D05-T10 | Role revogado durante job de exportação | Revalidar antes de disponibilizar ou novo efeito |
| CORE02-D05-T11 | Suporte Imagine usa conta técnica para contornar autorização da empresa | Negar sem mandato específico; sem BYPASSRLS genérico |
| CORE02-D05-T12 | Negação detalhada revela que registro confidencial de B existe | Explicar de maneira segura e sem vazamento |
| CORE02-D05-T13 | Administrador muda atribuição de equipe, histórico é perdido | Preservar histórico oficial e autoria; não apagar fatos já realizados |
| CORE02-D05-T14 | Agente IA recebe instrução para “agir como admin” | Negar sem serviço/ferramenta/escopo e autorização independente |

**Não executar casos, não criar suíte redundante:** mapear CORE02-D05-T aos CORE02-T01..T14, CORE01-T, REV01 S01..S10, INT HT01..18, SEG/AGT, CORE10-D06. Todos **NÃO EXECUTADOS**; quem aprovar um princípio não estará homologando seu funcionamento.

## 9. Dependências abertas e não decisões

| ID | Critérios de fechamento que permanecem pendentes |
|---|---|
| **CORE02-D04** | Inventário de campos, proteção, direito por módulo, máscaras, customizados e exportação; aceito apenas preliminarmente A/B/C/D |
| **CORE02-D05** | Política final P1..P4, prioridade de deny por origem, catálogo de perfis, delegação, herança, escopo de equipe/unidade, responsabilidades e critérios físicos |
| **CORE02-D06** | Ações e autoridades específicas por DAT/VEN/ATD/AGD/AUT/IA/FIN/CON/INT/Sites |
| **CORE02-D07** | Confirmações, MFA/step-up, alçadas, separação de autorizar vs executar, exceções humanas |
| **CORE02-D08** | Enforcements Laravel/RLS, roles, policy version/TTL, cache, workers e revogação |
| **CORE02-D09** | Entitlements/quotas por plano, Básico, trial, assentos, adicionais e revisão comercial final |
| **CORE02-D10** | Roteiros de aceitação/documentação, owners efetivos, evidência, gates CORE/INT/SEG |
| **CORE01-D04..D07; CORE03..CORE10-D04..D10** | Especificações físicas, contratos cruzados e responsáveis pendentes, ainda que princípios preliminares de CORE01 D04..D07 tenham sido aceitos |
| **SEG-01/02, INT P0/CT, FIN/CON, G01..G06 e IN-168 ARQ/AGT** | Segurança/autoridade independente, infra e licenças, integrações, catálogo financeiro, homologação futura e autorização global |

**Total da Etapa 2 permanece 67 identificadores CORE abertos**, inclusive CORE02-D04 e D05. Os itens de CORE02-D05-A/B/C/D **não estão aprovados** por esta minuta.

## 10. Quatro decisões para eventual aceite — PROPOSTAS NÃO APROVADAS

- **CORE02-D05-A — PROPOSTA:** aceitar como diretriz preliminar o **limite cumulativo de autoridade por tenant, membership, plano, dono do módulo, recurso/ação/campo, escopo, finalidade e segurança**, independentemente do número de perfis, sem grants automáticos.
- **CORE02-D05-B — PROPOSTA:** aceitar **a necessidade de contrato determinístico para compor grants, denies explícitos, herança e bloqueios mandatórios**, estudando P1–P4 sem fixar ainda que “qualquer deny de qualquer perfil vence todos os grants”.
- **CORE02-D05-C — PROPOSTA:** aceitar **governança de perfis customizados e delegações por competência**, distinguindo editar/atribuir/aprovar, prevenindo autoelevação, mantendo MFA/admin único Básico e sem acesso global de Imagine por analogia.
- **CORE02-D05-D — PROPOSTA:** aceitar **escopo efetivo por equipe/unidade/posse/canal quando aplicável, revogação segura e auditoria/diagnóstico de negativa**, sem criar política física ou compartilhar permissões entre tenants e agentes.

Mesmo se todas aprovadas como diretrizes preliminares, **CORE02-D05 permanecerá ABERTA** até definição/validação de P1–P4, owners, contratos CORE01/03/04/05/07/08/09, SEG-01/02, INT P0-OWN/SEC/CON, critérios e aceite de segurança.

## 11. Próximo passo e proibições

Somente após aceite expresso A/B/C/D, registrar CRM-DEC de CORE02-D05, atualizar matriz, preservar D05 e todos os gates abertos, prosseguir com CORE02-D06 exclusivamente documental. **Não tratar a aprovação como escolha de política de deny/grant, não atribuir papéis reais, nem ativar permissões.**

**VEDADO:** programação, Codex, testes executáveis, SQL/RLS, grants, criar/alterar perfis em contas reais, credenciais, implantação, serviços, migração, produção, compras, custos/contratações, processamento de dados de clientes.

## 10. Aditamento de aceite parcial CORE02-D05 A/B/C/D — 04/11/2026

O responsável [aprovou expressamente](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md) como **diretrizes técnicas documentais preliminares**: (A) limites cumulativos por tenant/plano/recursos; (B) necessidade de política determinística de composição, **sem escolher P1/P2/P3/P4**; (C) governança de perfis e delegação sem autoelevação; (D) escopos próprios/equipe/unidade/posse, revogação e auditabilidade. **CORE02-D05 continua ABERTA**, dependente de contrato efetivo e escolhas de precedência, owners, autorização e integração com CORE01/03/04/05/07/08/09, SEG-01/02, INT P0 e Segurança. Próximo trabalho autorizado: **proposta documental CORE02-D06** de direitos por módulo, sem código nem testes. CORE02-D04/D06..D10, CORE01-D04..D07 e demais P0 continuam abertos; DEV-GATE-00 mantém NO-GO.

## 11. Escolha técnica delegada da precedência de perfis (04/11/2026)

Foi selecionada como **política-base documental para especificação** a combinação **P1 de grants positivos válidos dentro de limites mandatórios** com **P2 de negações explícitas qualificadas que prevalecem apenas na interseção de tenant, ação, recurso, campo e escopo**, conforme [decisão técnica delegada](CRM-DEC-TECNICA-DELEGADA-CORE02-D05-precedencia-P1-P2-escopada-v0.1.md). A regra **não** equivale a que “qualquer deny de qualquer perfil vence qualquer grant”; P3/P4 não são padrão. Exceções legítimas e escopos técnicos autônomos têm contrato próprio. **CORE02-D05 integral permanece ABERTA**, sem catálogo físico, grants, automação, testes, SEG-01/02 ou INT resolvidos, e sem autorização de programação.
