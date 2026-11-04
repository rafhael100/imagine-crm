# CRM-CORE-P0-04 — Autenticação, MFA, sessões e recuperação por papel (v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DOCUMENTAL PARA DELIBERAÇÃO — NÃO APROVADA. CORE-P0-04 ABERTO.**  
**Fase:** somente planejamento e registro GitHub; sem programação, testes executados, contas reais, credenciais, alterações de produção, contratação ou custos.  
**Objetivo:** diferenciar autenticar uma identidade, autorizar uma organização/ação, exigir MFA, confirmar um ato crítico, recuperar acesso e revogar uma sessão, preservando decisões históricas e sem criar IAM/central de segurança paralelos.

## 1. Fontes confrontadas e precedência

| Fonte | Decisões documentadas / limites |
|---|---|
| [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md), N-02/03/06/07/08/10 | Login multiempresa com vínculos distintos; MFA central/empresa obrigatório; funcionários opcionais conforme política; sesssões e recuperação; privilégios segregados |
| [LEG-03 — Administração Geral Imagine](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md) | MFA obrigatório, preferencialmente autenticador ou passkey; **30 min de inatividade da Administração Geral**; sessões revogáveis; recuperação com códigos de uso único; perda total de fatores exige verificação por superadministrador e auditoria, **não apenas e-mail**; confirmação de ação crítica pelo mesmo administrador autorizado, **sem dupla aprovação universal** |
| [LEG-04 — Empresas, usuários, equipes](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Admin principal e adicionais conforme direitos com MFA obrigatório; funcionários MFA opcional por padrão e configurável como obrigatório pela empresa; senha recuperável por e-mail verificado/link temporário; sessões em outros dispositivos podem ser encerradas; convites de 7 dias; desligamento revoga acesso somente daquela empresa |
| [LEG-13 — Básico e segurança](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md) | Uma única identidade autenticada por organização Básico; único Administrador da Empresa com MFA obrigatório; mudança de e-mail, senha e MFA exige verificação de identidade; não permitir desabilitar MFA ou segundo usuário no Básico; recuperação do único admin ainda requer fluxo específico |
| [CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md) | Uma identidade global pode ter memberships independentes; tenant lógico por organização; isolamento Laravel/RLS |
| [CORE-P0-02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md) | Autorização cumulativa por identidade, vínculo, tenant, plano, perfil, recurso/ação/campo; permissões distintas; sem acesso global presumido |
| [CORE-P0-03 D01–D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md) | Contexto lógico e execução revalidável; distinguir autoria humana e principal técnico; sessão humana não é credencial de worker |
| [P0-SEC INT](integracoes/P0-SEC-politicas-autorizacao-CT02.md), [IN-151](IN-151-gestao-inteligente-credenciais-chaves-integracao.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md) | Reautorização contextual, segredos protegidos, identidades técnicas escopadas; step-up, alçadas, cofre, política física e testes ainda P0 |

**Interpretação obrigatória:** os **30 minutos são regra histórica específica da sessão da Administração Geral**, não tempo padrão de todos os funcionários nem tempo de validade de convites, links de recuperação, MFA ou jobs. Nenhum parâmetro numérico novo é aprovado por esta minuta.

## 2. Quadro normativo já aprovado — não submeter como decisão nova

| Contexto | MFA vigente | Sessões/recuperação historicamente definidos | Condição que falta especificar |
|---|---|---|---|
| **Administrador Geral Imagine** | **Obrigatório**; preferência histórica por aplicativo autenticador ou passkey | **30 minutos de inatividade** em Administração Geral; consultar/revogar sessões; códigos de recuperação de uso único; perda simultânea dos fatores/códigos exige verificação pelo superadministrador e auditoria, não apenas e-mail | Implementação de autenticação, risco, step-up por ato, revogação de sessão global, recuperação quando não existe segundo superadmin |
| **Administrador da Empresa** | **Obrigatório** | Fluxos seguros de senha/e-mail/MFA, encerramento de sessões em outros dispositivos; não existe regra histórica que imponha 30 minutos universalmente | Regras técnicas de sessão/recuperação, dispositivos, autenticação reforçada em operações sensíveis |
| **Administrador único do Básico** | **Obrigatório**, jamais desativado por simples configuração | Um login/assento; pode mudar credenciais mediante verificação e encerrar suas sessões conforme direitos | Recuperação do único administrador sem segundo usuário indevido e sem transferir titularidade por e-mail isolado |
| **Funcionário/gestor da empresa** | **Opcional por padrão**, mas a própria empresa pode exigir MFA para todos os funcionários | Recuperação de senha por link temporário enviado ao e-mail verificado; histórico de acessos/tentativas e encerramento de sessões | Tipos de fatores, política de imposição/adesão e o que ocorre se possuir vínculo de admin em outra organização |
| **Identidade de serviço, agente, webhook e integração** | **Não equiparar automaticamente a usuário humano**; autenticação técnica segue chave/certificado/token delegado e cofre, por contrato | Acesso mínimo por serviço/ação/ambiente/tenant; revogação técnica e rotação | CORE-P0-09/SEG-02 definem mecanismo e parâmetros; não utilizar sessão humana como credencial genérica |
| **Visitante por link público de agenda/proposta/formulário** | Não possui membership CRM por usar link ou formulário | Ato público só na capacidade, prazo e objeto específico autorizado pelo módulo | Token/link e riscos do módulo; não exigir MFA de funcionário por analogia nem criar login CRM do visitante |

### Dois limites aprovados a não contrariar

- **Ação crítica da Administração Geral:** confirmar e auditar pelo **mesmo administrador autorizado**, salvo regra de operação específica que exija outro interveniente; não introduzir uma segunda aprovação administrativa obrigatória para todos os atos.
- **Revogação por empresa:** desligar um funcionário na organização A não deve excluir sua identidade global nem revogar automaticamente o membership legítimo na organização B. Por outro lado, um acesso central Imagine não gera leitura universal de dados comerciais de clientes.

## 3. CORE04-D01 — Política de MFA dependente do contexto e do vínculo (PROPOSTA)

Aceitar como **modelo lógico** uma identidade única com exigência de segurança determinada pela ação e pelos papéis **atuais**:

1. Ao autenticar o usuário, identificar os vínculos de tenant ativos e eventual função de Administração Geral, sem assumir que login = autorização de atuação em qualquer organização.
2. Antes de entrar no painel de Administração Geral ou executar atos reservados a administrador de tenant, exigir **comprovação vigente do MFA obrigatório**. Se o usuário também for funcionário de outra organização, essa outra empresa continua podendo tratar MFA como opcional **somente nas operações que não exigirem MFA obrigatório por papel/política**.
3. Quando a empresa exigir MFA a seus funcionários, o sistema impedirá acesso às operações protegidas daquela organização sem o fator exigido, sem habilitar bypass por alternância de tenant, papel ou conta.
4. Um usuário que tem papel administrativo em qualquer tenant não ganha permissão central, nem um privilégio administrativo permanece válido depois que o seu membership ou papel foi revogado.
5. **Não impor obrigatoriedade universal de MFA a todos os funcionários**, nem reduzir MFA dos administradores em função do plano, cortesia, trial ou dispositivo usado.
6. Modalidades possíveis de segundo fator, credenciais resistentes a phishing, garantia de autenticação e eventuais exceções técnicas são objeto de decisão posterior, sem impor autenticação por SMS ou e-mail como segundo fator sem homologação.

**Observação:** esta proposta descreve **quando** avaliar a segurança do usuário; não especifica se o fator ocorre no login global, no ingresso do tenant, na troca de papel ou por step-up. Frequência, validade e transição pertencem a D04/D05.

## 4. CORE04-D02 — Ciclo lógico de sessão, troca de tenant e revogação (PROPOSTA)

A sessão e a autorização devem ser tratadas como conceitos distintos:

- A **sessão de identidade** prova a autenticação; o **contexto da organização** determina o membership, o papel e as permissões; o **contexto da Administração Geral** possui regras próprias e não pode ser invocado só por possuir cookie/token comum.
- Trocar de empresa A para B deve revalidar o vínculo ativo e os direitos de B. Conteúdo de A armazenado na interface, relatórios, URLs, downloads e jobs não pode reaparecer ou ser utilizado por B indevidamente. Persistência e invalidação física de contexto/cache são pendentes.
- Em Administração Geral Imagine, preservar **expiração após 30 minutos de inatividade** conforme legado. Em outros papéis/sessões, determinar posteriormente tempos adequados por perfil/risco, sem copiar 30 minutos universalmente.
- Permitir consultar sessões da própria identidade e encerrá-las nos outros dispositivos nos limites já aprovados. Revogação por desligamento de uma organização exige **bloquear uso do tenant revogado imediatamente**, sem interromper por mera suposição o acesso a outros tenants legítimos; logout global e revogação por dispositivo são atos diferentes.
- Mudança de senha, e-mail, MFA ou funções sensíveis pode exigir revogação das demais sessões conforme avaliação de risco. A opção histórica de oferecer revogação de demais sessões ao mudar a senha do Admin Geral permanece.
- Suspensão de conta, possível comprometimento ou recuperação de MFA precisam impedir sessões remanescentes de manter privilégios indevidos, respeitando contratos de negócios já concluídos e CORE-P0-03.
- Nenhum token, identificador de sessão, cookie, segredo, código ou link de recuperação deve aparecer em logs ou ser reutilizado como autorização de workers.
- Estados/TTL, expiração absoluta, timeouts para funcionários, reconhecimento de dispositivos, rate limiting e critérios de bloqueio são pendentes.

## 5. CORE04-D03 — Recuperação proporcional e resistente a tomada de conta (PROPOSTA)

Adotar **segregação por domínio e risco**, sem inventar um fluxo de recuperação universal:

| Evento | Fluxo lógico proposto | Condição já aprovada / limite |
|---|---|---|
| Funcionário esquece senha e controla e-mail verificado | Link seguro, temporário e vinculado à identidade, com validação e auditoria; preservar o tenant/escopo | Fluxo por e-mail verificado consta do LEG-04; prazo/limites concretos não definidos |
| Administrador da Empresa perde senha, mas consegue provar MFA | Verificação de identidade apropriada antes de redefinir credencial e recuperar atos administrativos | MFA continua obrigatório; não contornar com mero perfil de funcionário |
| Mudança de e-mail de acesso ou telefone de recuperação | Comprovar controle do fator/canal anterior e do novo conforme risco; alertar alterações sensíveis e registrar evidência | No Básico, IN-022 exige verificação de identidade; detalhes técnicos e exceções pendentes |
| Usuário perde fator MFA, mas mantém outro método seguro ou códigos válidos | Usar via de recuperação previamente homologada, de uso único quando cabível, e renovar fatores com auditoria; revalidar sessão e papéis | LEG-03 aprova códigos de recuperação de uso único para Admin Geral; não supor que o mesmo método já foi aprovado em todos os domínios |
| Administrador Geral perde MFA e códigos | **Não recuperar apenas por e-mail**. Seguir verificação de identidade por superadministrador e auditoria | Decisão explícita LEG-03; definir no futuro segurança se não houver outro superadministrador, sem bypass informal |
| Único administrador do Básico perde todo acesso | Criar **proposta de recuperação de titularidade/identidade verificada**, sem conceder segundo usuário permanente nem permitir sequestro via CNPJ/e-mail isolados | Recuperação não pode automaticamente habilitar assento extra, transferir empresa ou eliminar dados; CORE-P0-07/08 |
| Usuário participa de várias organizações e perde credenciais | Recuperação da identidade requer prova adequada e revalidação de membership; não mesclar direitos de organizações ou emitir convite como transferência de titularidade | CORE-P0-01 D01/02 e CORE-P0-07 (convites) |
| Conta de integração ou agente perde credencial | Rotação/revogação via cofre e principal técnico, **não** por link de recuperação humana | CORE-P0-09/SEG-02; autenticação do provedor externo ≠ permissão interna |

**Sem parâmetros implícitos:** esta proposta não determina duração de link, número de tentativas, documentos exigidos, uso obrigatório de telefone/SMS, função de suporte que aprova, janela de recuperação nem prazo de retenção da evidência. Tais escolhas precisam de contrato e aceite.

## 6. Matriz de ações sensíveis — inventário para futura decisão, não step-up obrigatório já aprovado

| Operação sensível potencial | Proteção preexistente | Ponto para decidir em CORE04-D04/D07 |
|---|---|---|
| Conceder plano ou adicional gratuito a tenant | Apenas Admin Geral Imagine autorizado; MFA obrigatório e auditoria | Necessidade de autenticação recente/step-up, confirmação, autorização específica; sem segunda aprovação universal |
| Alterar CNPJ/representante legal/organização do cliente | Validação específica e legitimidade obrigatórias | Identificação, evidências, eventual intervenção humana e proteção contra takeover |
| Trocar senha, e-mail, método MFA e recuperar usuário administrador | Verificação de identidade e MFA onde obrigatório | Que fator é suficiente, reautenticação, revogação de sessões e avisos |
| Atribuir/remover administrador, criar perfil superior ou delegar direitos | Controle por plano e papel; vedada autoelevação não autorizada | Step-up por delegação, alçada e bloqueio se admin único inacessível |
| Exportar volume de dados, backup/restore/migração | Permissão de exportação própria, IN-118 de portabilidade e LGPD | Reautenticação para ações sensíveis, finalidade, fontes/destinos, arquivo temporário |
| Excluir cadastros protegidos ou históricos elegíveis | Confirmação explícita, requisitos legais, proteção de vínculos e auditoria | Fatores de autenticação reforçada por risco, sem impedir direitos legais por falta de plano |
| Trocar conta CRM vinculada a site Imagine | Chamado autenticado e execução administrativa pela Imagine, sem autosserviço | Identidade do solicitante/destino, aceite, autoridade e expiração do mandato |
| Alterar chaves de API, escopos e integrações | INT-02, IN-151 e CORE-P0-09 com direitos mínimos | Verificação adicional e revogação/rotação; sem segredo em browser ou log |
| Acesso excepcional de suporte Imagine | Caso autorizado, finalidade, prazo, auditoria, sem bypass RLS universal | CORE-P0-05 detalha competência e risco |
| Operação técnica crítica de agente N3 | Aprovação humana **específica** quando exigida; sem aprovação duplicada do mesmo ato | Identidade e autenticação do aprovador, janela, versão da autorização e evidências |

**Precedência:** a matriz acima não aprova step-up para todas as linhas; identifica candidatos para classificação por risco. Operações já aprovadas com confirmação explícita não podem ser impedidas arbitrariamente por novos bloqueios não homologados.

## 7. Controles transversais candidatos — ainda sem decisão efetiva de parâmetros

1. **Fator de autenticação:** proteger a recuperação e a configuração de MFA contra troca fraudulenta, uso de fatores inseguros e comprometimento de e-mail; estudar preferência por autenticadores/passkeys no Admin Geral, conforme legado.
2. **Proteção de login:** detecção e limitação de tentativas abusivas, eventos suspeitos e tomada de conta; limiares/rotina de desbloqueio ainda pendentes e devem permitir atendimento legítimo.
3. **Sessões:** cookies/tokens com armazenamento seguro, renovação e revogação; evitar exposição de sessão a scripts, logs ou URL; arquitetura específica fica para projeto técnico.
4. **Trilha oficial:** registrar sucesso/falha de acesso, alteração de MFA, recuperação, troca de vínculo, dispositivo e revogação por categoria de auditoria; não registrar códigos, senhas ou tokens em claro. Retenção e detalhes pessoais dependem CORE-P0-06/LGPD.
5. **Privacidade:** avisos de segurança suficientes sem divulgar se e-mail/CNPJ/identidade já existem em plataforma; impedir enumeração e vazamento cross-tenant.
6. **Experiência:** recuperar acesso e impor MFA com procedimentos claros, sem permitir que o estado de segurança seja interpretado como necessidade de upgrade ou cobrança.
7. **Integrações:** tokens de provedor, service principals, API e OAuth não são MFA de funcionário nem se convertem em direitos por possuir um segredo; CORE-P0-09/INT-02 possui contrato separado.
8. **SSO/conta única Portal/CRM:** identidade global **dentro do CRM** não significa, por si, SSO Portal↔CRM/Performance/Universidade; definir contrato específico entre produtos sem presumir compartilhamento de banco/cookie/credencial.

## 8. Casos negativos para homologação futura — NÃO EXECUTADOS

| Caso | Tentativa ou condição | Resposta requerida para o contrato futuro |
|---|---|---|
| CORE04-T01 | Admin Imagine tenta acessar painel central sem MFA | Negar painel/ato até prova obrigatória |
| CORE04-T02 | Sessão central fica inativa por mais de 30 minutos | Encerrar acesso central conforme prazo histórico |
| CORE04-T03 | Funcionário de empresa sem MFA obrigatório acessa módulo básico autorizado | Não impor MFA administrativo por analogia |
| CORE04-T04 | Mesmo usuário é funcionário em A e administrador em B | Exigir MFA quando agir como admin B; sem transferir poderes para A |
| CORE04-T05 | Tenant A desativa membership e usuário mantém outra empresa B | Bloquear ações em A, preservar B se ainda autorizado |
| CORE04-T06 | Usuário tenta trocar para tenant não autorizado com sessão válida | Negar por membership e escopo CORE-P0-01/02 |
| CORE04-T07 | Admin Básico tenta desativar MFA | Negar; não comprometer admin único |
| CORE04-T08 | Atacante controla só e-mail do Admin Geral sem MFA nem códigos | Não recuperar acesso central por e-mail isolado |
| CORE04-T09 | Link de recuperação expirado/reutilizado | Negar replay, registrar tentativa mínima |
| CORE04-T10 | Usuário altera credencial mas sessão revogada tenta ação crítica | Revalidar, negar quando exigido e auditar |
| CORE04-T11 | Convite pendente é usado como credencial de acesso antes do aceite | Negar sessão/vínculo indevido |
| CORE04-T12 | Usuário de portal externo possui link público e tenta entrar no CRM | Negar membership não autorizado |
| CORE04-T13 | Agente usa token técnico para acessar área humana de MFA | Não equiparar credenciais e direitos |
| CORE04-T14 | Único admin Básico perde acesso | Recuperação deve validar titularidade sem segundo assento não autorizado |
| CORE04-T15 | Timeout de Admin Geral encerra sessão e deixa comando crítico em execução | Impedir novos efeitos não autorizados, reconciliar efeitos anteriores conforme CORE-P0-03 sem apagar fatos oficiais |

Consolidar esses cenários nos critérios S01..S10 da REV-01 e INT/SEG, sem criar suíte redundante nem executar testes antes de autorização global.

## 9. Deliberações propostas AGORA — NÃO APROVADAS

- **CORE04-D01 — PROPOSTA:** aprovar como base lógica a **exigência contextual de MFA por função e tenant** da seção 3, preservando MFA obrigatório para Admin Geral e Admin Empresa (inclusive Básico) e MFA opcional por padrão para funcionários, exigível pela empresa; a alternância de tenant não pode reduzir exigência administrativa.
- **CORE04-D02 — PROPOSTA:** aprovar como referência a **separação de sessão de identidade, contexto de tenant e sessão de Administração Geral**, com revogação proporcional e **30 minutos de inatividade apenas no âmbito central já aprovado**, sem replicar o prazo para todos.
- **CORE04-D03 — PROPOSTA:** aprovar como modelo lógico **recuperação segura diferenciada por contexto e risco**, preservando recuperação de senha do funcionário por e-mail verificado/link temporário e recuperação do Admin Geral sem MFA/códigos via verificação por superadministrador com auditoria, **nunca apenas e-mail**; admin único do Básico exige fluxo específico que não cria segundo usuário/tenant indevido.

## 10. Dependências que permanecerão abertas mesmo após D01–D03

| Código | Lacuna não resolvida / competência |
|---|---|
| **CORE04-D04** | Classificar operações críticas por risco e decidir quando exigir step-up/reautenticação, janela de validade e alçadas. CORE02-D07, FIN/CON, GRAT-P02, INT-CT02, CORE-P0-05 |
| **CORE04-D05** | Parâmetros efetivos: sessão por perfil, expiração absoluta/inatividade de não centrais, dispositivo confiável, timeout de MFA, fatores permitidos e segurança de passkeys, tentativas/limiares. Segurança/IAM |
| **CORE04-D06** | Fluxos concretos de recuperação, validação da titularidade, verificações e responsáveis; caso sem segundo superadministrador, único admin Básico, troca de e-mail e falhas de todos os fatores. CORE-P0-07/08/05 |
| **CORE04-D07** | Matriz de ações sensíveis por campo, recurso, tenant e operação; confirmação adequada sem segunda aprovação global automática. CORE-P0-02/05, GRAT-P02, INT P0-OWN/SEC |
| **CORE04-D08** | Esquemas de identidade e sessões, armazenamento/rotação/revogação de tokens, cookies, chaves, APIs, auditoria e proteção concreta a sequestro; relacionar Laravel/RLS, cofre e serviços. CORE-P0-01/03/09, SEG-01/02 |
| **CORE04-D09** | Contrato de identidade e login entre Portal, CRM e outros produtos, inclusive opção de SSO; separar integração comercial e autenticação de serviço. CRM/Portal/Performance/INT |
| **CORE04-D10** | Responsáveis, testes negativos, limites de alerta/risco, evidências e homologação documental; retenção por categoria de log e gates integrações/segurança. CORE-P0-06/10, INT P0, G01–G06 |

**Fechamento real de CORE-P0-04 exige:** política completa de MFA por papel e operação, estado/verificação de sessões, recuperação verificável para admin único/central/funcionários, reautenticação para atos críticos, mecanismos e parâmetros concretos, cenários de falha testáveis e compatibilidade com as decisões já aprovadas. Aceite de D01–D03 não encerra CORE-P0-04.

## 11. Estado e limites

- CORE-P0-01: D01–D03 base aprovada, D04–D07 abertas.
- CORE-P0-02: D01–D03 base aprovada, D04–D10 abertas.
- CORE-P0-03: D01–D03 base aprovada, D04–D10 abertas.
- CORE-P0-04: v0.1 proposta, D01–D03 aguardando manifestação; D04–D10 sem aprovação e abertas.
- CORE-P0-05..10, INT P0, G01–G06, ARQ/SEG/AGT permanecem como estavam.
- **Nenhuma programação, teste executado, implantação, conta real, contratação ou aprovação global de desenvolvimento.**
