# CRM-CORE-P0-04 — Autenticação, MFA, recuperação e sessões — proposta v0.1

**Data:** 04/11/2026.  
**Estado:** **MINUTA PARA REVISÃO — nenhuma decisão CORE04-D01–D10 foi aprovada por este documento.**  
**Natureza:** fechamento documental do núcleo CRM Imagine, não implementação.  
**Precedência:** preservar os capítulos funcionais e aprovações posteriores. Qualquer mudança material necessita aceite expresso do responsável.  
**Restrições:** sem código, testes executáveis, credenciais, contas reais, provisionamento, custos ou mudanças de produção.

## 1. Fontes e decisões preservadas

- [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md): N-01..N-13, especialmente N-02/N-06/N-07/N-08/N-10; base documental aceita, não política executável.
- [CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): identidade de login global, memberships por organização e isolamento por tenant.
- [CORE-P0-02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md): autorização cumulativa por papel, ação, escopo, plano e recurso; negar por padrão, sem grants novos.
- [CORE-P0-03 D01–D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md): envelope e revalidação de autoridade em jobs/eventos/webhooks; sessão humana não confere poderes permanentes.
- [LEG-03 — Administração Geral](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md): MFA obrigatório, preferencialmente autenticador ou passkey; sessão central de 30 minutos de inatividade; gestão e revogação de sessões; recuperação com códigos únicos e, na perda simultânea, identificação por superadministrador/auditoria, não somente e-mail.
- [LEG-04 — Empresas e Usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md): MFA obrigatório para admins da empresa; opcional para funcionários, exigível por política do tenant; recuperação por e-mail verificado e link temporário; sessões e histórico de acessos.
- [LEG-13 — Básico](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/13-matriz-permissoes-basico-cortesia.md): uma identidade autenticada por organização, administrador com MFA obrigatório, direitos próprios de conta/sessão, sem desligar proteção ou criar outro login por recuperação.
- [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md): fronteiras de segurança, logs protegidos, acesso excepcional separado.
- [Painel CORE-P0 Etapa 1](CRM-CORE-P0-ETAPA1-painel-fechamento.md): CORE-P0-04 aberto; demais CORE-P0 e P0 transversais permanecem.

## 2. Propostas conceituais para decisão expressa (NÃO APROVADAS)

### CORE04-D01 — Política canônica de MFA por identidade, papel e organização — PROPOSTA

1. Aplicar autenticação na **identidade global** e determinar a exigência efetiva de MFA no **contexto da organização e do papel ativo**, sem transferir permissões entre tenants.
2. **Administrador Geral Imagine:** MFA obrigatório; preferir autenticador ou passkey conforme LEG-03; não presumir que possuir conta central dá acesso automático a dados dos clientes.
3. **Administrador da Empresa:** MFA obrigatório, inclusive o administrador único do Básico. Ele não pode desligar MFA para si ou contornar a política de segurança.
4. **Funcionário:** MFA opcional inicialmente, salvo se política da empresa o tornar obrigatório. Quando a pessoa acumula papéis/organizações, satisfazer a exigência mais restritiva do **contexto que pretende acessar**.
5. Entrada, mudança de papel, convite aceito, alternância de tenant, renovação/revogação de credencial e mudança de política devem **revalidar** as condições de acesso; sessão autenticada em um contexto não cria autorização automática em outro.
6. A falta de MFA exigido bloqueia o acesso/operação do contexto que o exige de maneira segura, sem converter falha de segurança em oferta de upgrade.

**Limite da proposta:** aprovar conceito não homologa algoritmo, fator, provedor, enrollment, passkey, autenticador, desafio, recuperação nem implementação.

### CORE04-D02 — Recuperação de credenciais e perda do segundo fator — PROPOSTA

1. **Senha esquecida:** fluxo por endereço de e-mail verificado, link temporário de uso limitado, mensagens neutras contra enumeração de contas e registro de eventos. Recuperar a identidade **não** altera memberships, papéis, titularidade da empresa nem quotas.
2. **Recuperação de MFA:** mecanismo que preserva MFA obrigatório. Para administradores centrais, manter códigos de recuperação de uso único; perda conjunta do fator e códigos demanda verificação de identidade por superadministrador competente e auditoria, **nunca somente e-mail**.
3. Para administradores de empresa, inclusive o único do Básico, propor fluxo equivalente em nível de segurança, com identificação/competência e trilha verificável, sem criar segundo usuário operacional, acesso de suporte permanente ou bypass amplo. O responsável e as evidências desse fluxo são **pendência técnica/operacional D07**.
4. Mudanças de e-mail, senha, segundo fator e identidade titular são ações sensíveis: exigem comprovação apropriada, notificação/registro, defesa contra sequestro de conta e revogação/reavaliação das sessões afetadas segundo política a detalhar.
5. Proibir exibir senha, tokens, códigos, segredos e evidências de recuperação em logs, prompts de IA, histórico comercial ou exportações.

**Limite da proposta:** não fixa tempos de expiração, verificação de identidade da empresa, janela de contestação, SLA de suporte ou prova documental sem aceite separado.

### CORE04-D03 — Sessões e autenticação reforçada para ações sensíveis — PROPOSTA

1. A sessão é ligada à identidade autenticada e mantém **contexto de tenant/membership vigente**; trocas de organização exigem nova resolução de direitos no servidor, sem confiar em tenant arbitrário da interface ou token.
2. **Administração Geral Imagine:** expirar a sessão após **30 minutos de inatividade**, regra já registrada. Não estender automaticamente esse limite a funcionários ou a administradores de empresas.
3. Usuário deve poder consultar suas sessões e encerrar outras sessões/dispositivos conforme direito próprio; revogações críticas devem surtir efeito no backend e em credenciais derivadas pertinentes. Um desligamento **por tenant** remove acesso àquele tenant sem eliminar por padrão memberships legítimos em outros.
4. Para operação crítica, avaliar **reauth/step-up** proporcionais ao risco, direitos vigentes e confirmação explícita pelo mesmo operador autorizado. **Não impor dupla aprovação universal**; manter alçadas/aceites especiais apenas quando o fluxo previamente aprovado exigir.
5. Revogar/revalidar acessos afetados por desligamento, suspensão, recuperação, comprometimento, troca de credenciais, retirada de papel ou política de MFA, preservando fatos e obrigações sistêmicas legítimas de CORE03-D03.
6. Logs de acesso registram eventos e metadados mínimos pertinentes, conforme retenção e acesso autorizados; IP/dispositivo e tentativas malsucedidas no histórico central; jamais armazenar token em claro.

**Limite da proposta:** timings dos demais papéis, tipo de sessão, refresh token, propagação da revogação, step-up por ação e retenção permanecem D04–D10.

## 3. Dependências técnicas e deliberações abertas — não considerar aprovadas

| ID | Pendência | A reconciliar com |
|---|---|---|
| **CORE04-D04** | Métodos de login, senha, autenticador, passkeys, verificação inicial de e-mail, antifraude, interoperabilidade e acessibilidade | CORE01, SEG-01/02, UX |
| **CORE04-D05** | Sessão web/API, cookies/tokens, expiração absoluta/inatividade por papel, rotação, revalidação, dispositivos e concorrência | CORE01/02, SEG-01/02 |
| **CORE04-D06** | Limites de tentativas, proteção contra credential stuffing, mensagens neutras, bloqueios proporcionais, desbloqueio, alertas e mitigação de abuso | Segurança, Observabilidade, IN-159/162 |
| **CORE04-D07** | Recuperação de MFA e titularidade por papel, competência para verificação de identidade, provas, alçadas, notificações, contestação e usuário único do Básico | CORE-P0-05/07/08, LEG-03/04/13 |
| **CORE04-D08** | Matriz precisa de ações que exigem step-up, reautenticação ou aprovação própria, janela de validade e impacto de mudança de risco | CORE02-D07, CORE-P0-05, INT CT-02 |
| **CORE04-D09** | Auditoria de autenticação, mascaramento, retenção, integridade, exportação e acesso a histórico de segurança | CORE-P0-06, IN-162, LGPD |
| **CORE04-D10** | Contratos de revogação em sessões, integrações, APIs, agentes e jobs, homologação dos casos negativos e consistência entre serviços | CORE03-D04..D10, CORE-P0-09/10, SEG-01/02 |

**Outros bloqueios preservados:** CORE01-D04..D07, CORE02-D04..D10, CORE03-D04..D10; CORE-P0-05..10; INT P0-OWN/SEC/CON; IN-168 (ARQ/SEG/AGT); G01–G06. Nenhuma base aprovada resolve automaticamente esses itens.

## 4. Cenários de homologação a especificar futuramente (não executar agora)

- CORE04-T01: admin central sem MFA não acessa função central; inatividade de 30 min encerra sessão.
- CORE04-T02: admin da empresa sem MFA é barrado; funcionário em tenant A com MFA opcional não atravessa tenant B com MFA obrigatório.
- CORE04-T03: único administrador do Básico recupera acesso sem gerar um segundo assento nem desabilitar MFA.
- CORE04-T04: token/identidade com membership removido não reutiliza sessões, API ou troca de tenant para ler registros.
- CORE04-T05: perda de MFA+códigos do administrador central não é recuperada apenas por e-mail; exige verificação competente e auditoria.
- CORE04-T06: mudança de credencial ou suspeita de comprometimento invalida sessões afetadas nos canais relevantes; tentativas abusivas não revelam existência de conta.
- CORE04-T07: ações críticas submetidas a step-up/confirmacão quando homologadas; nenhuma dupla aprovação universal inadvertida.
- CORE04-T08: desconectar da empresa A não elimina acesso legítimo à empresa B; fatos/jobs autônomos ainda seguem CORE03, sem credencial pessoal obsoleta.
- CORE04-T09: logs de segurança têm dados mínimos e nenhum segredo/token/código exposto.
- CORE04-T10: indisponibilidade de verificação obrigatória falha de modo seguro, com contingência controlada e auditável.

## 5. Pedido de deliberação e próxima etapa

Submeter **CORE04-D01, CORE04-D02 e CORE04-D03** à aprovação **como base conceitual e documental**, mantendo **CORE04-D04 a D10** e todas as dependências abertas. Só após aceite expresso criar registro de decisão de aprovação. Próximo passo da sequência: **CORE-P0-05 — acesso excepcional da Imagine**, não iniciar automaticamente nem considerar CORE-P0-04 encerrado. 

**Proibição vigente:** sem programação, Codex, testes reais, ambientes, credenciais, migração, contratação ou produção até fechamento global aprovado.
