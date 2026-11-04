# CRM-CORE-P0-05 — Acesso excepcional da equipe Imagine a organizações de clientes (v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DOCUMENTAL PARA DELIBERAÇÃO; CORE-P0-05 ABERTO, D01–D03 NÃO APROVADAS.**  
**Fase:** exclusivamente análise e documentação no GitHub. Sem programação, testes reais, contas/credenciais, instalação, contratação, acesso efetivo a clientes ou mudanças de produção.  
**Objetivo:** permitir diagnóstico, assistência e recuperação legítimos sem transformar Administração Geral, suporte, IA ou serviço técnico em acesso irrestrito aos dados comerciais e pessoais dos clientes. Diferenciar telemetria da operação, acesso ao dado, mutação em nome de cliente e contenção emergencial. Não criar motor IAM, Central de Aprovações, Auditoria Geral ou perfis privilegiados paralelos.

## 1. Fontes, precedências e limitações

1. [LEG-03 — Administração Geral Imagine](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/03-administracao-geral.md): administração central com permissões por módulo/ação e **MFA obrigatório**; **sem acesso irrestrito automático** aos dados dos clientes; suporte excepcional requer autorização e auditoria. Ações críticas exigem confirmação pelo **mesmo administrador autorizado**, sem **segunda aprovação universal**; justificativa e auditoria por risco; sessão central expira após 30 minutos de inatividade.
2. [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) N-03/N-07/N-09, S02/S09: acesso de suporte somente por escopo/caso válido, expira e não vira bypass geral de RLS.
3. [CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): identidade global, memberships por tenant e isolamento Laravel + PostgreSQL RLS.
4. [CORE-P0-02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md): direitos cumulativos por ator/vínculo, tenant, papel, recurso, ação, campo e plano; **leitura, exportação, edição, exclusão e auditoria são poderes distintos**. Não conceder grants reais.
5. [CORE-P0-03 D01–D03](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md): trabalhos assíncronos com identidade causal/executor, revogação, revalidação e resultado oficial pelo módulo competente.
6. [CORE-P0-04 D01–D03](CRM-DEC-aprovacao-CORE-P0-04-D01-D03-base-autenticacao-2026-11-04.md): MFA administrativo e sessões, recuperação de acesso distinta por papel; **CORE04-D04..D10 ainda abertas**.
7. [CRM-REV-09](CRM-REV-09-infraestrutura-seguranca-auditoria-operacoes-centrais.md) OPS-R10/R19/R33: suporte excepcional precisa competência, propósito, escopo, expiração e auditoria; telemetria e auditorias não são leitura de negócio; portabilidade de dados IN-118 é autônoma no CRM para todos os planos.
8. [INT-P0-OWN-SEC](integracoes/P0-OWN-SEC-matriz-recursos-campos-OP24.md), OWN-Q05/Q06: delegação e eventual atuação em nome do cliente, identidade do solicitante/executor e alçadas **ainda dependem de deliberação específica**. [P0-SEC](integracoes/P0-SEC-politicas-autorizacao-CT02.md) e [P0-GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md) não homologam políticas físicas.
9. [IN-151](IN-151-gestao-inteligente-credenciais-chaves-integracao.md), [IN-167](IN-167-consolidacao-arquitetura-agentes-tecnicos.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md): identidades de serviço/cofre e agentes N1/N2/N3 são segregados, sem uso de segredos em claro ou autoconcessão.

**Nota de governança:** a presente revisão não fixa fundamento legal específico, consentimento universal do cliente, procedimento de emergência, prazos de mandato, retenções ou número de aprovadores. Demandas de titular/cliente, obrigações legais, contratos, incidentes e alçadas devem ser classificadas na especificação de CORE05-D04..D10, LGPD e demais módulos, sem inventar base jurídica.

## 2. Separação de competências já exigida pelas decisões históricas

| Atividade | Acesso conceitual necessário | O que NÃO se presume |
|---|---|---|
| **Operar plataforma Imagine** | Configurações globais e estado operacional conforme papel central | Leitura de contatos/leads, conversas, oportunidades, agenda, arquivos ou exportações comerciais de clientes |
| **Monitorar infraestrutura/saúde** | Métricas, disponibilidade, sinais técnicos e erros saneados | Acesso ao payload de lead, conteúdo de WhatsApp, senha, token, anexo ou segredo |
| **Analisar suporte de conta/assinatura** | Metadados mínimos de contrato, cobrança e configuração por competência específica | Poder para alterar plano gratuitamente, acessar dados de negócio ou migrar dados em massa |
| **Investigar incidente de segurança** | Evidência por incidente, finalidade e escopo autorizado | Direito a investigar todas as empresas ou exportar auditoria indiscriminadamente |
| **Diagnosticar dados de tenant** | Acesso excepcional **específico** a recurso/campo/caso e pelo tempo necessário, quando legitimamente autorizado | Acesso automático por ser Administrador Geral, por receber ticket ou por conhecer ID de cliente |
| **Executar reparo em dado de negócio** | Ato tipado com autorização da competência do módulo proprietário, confirmação e auditoria | "Suporte" genérico, edição direta por banco, impersonação invisível ou confirmação de negócio por sucesso técnico |
| **Executar playbook técnico N1/N2/N3** | Identidade de serviço, ambiente, ferramenta, playbook homologado, autorização e alçada quando aplicável | Segredo em prompt, bypass geral RLS, licença n8n presumida ou poder autônomo de agentes IA |
| **Ajudar na portabilidade/exportação** | Orientar autoatendimento IN-118; qualquer extração pelo operador exige direitos e mandato próprios | Impossibilidade de exportar por depender da equipe Imagine ou direito irrestrito de copiar bases |

As classes são **descrições do risco e da competência**; não criam nove papéis obrigatórios nem privilégios.

## 3. CORE05-D01 — Fronteira entre operação central e dados de clientes (PROPOSTA)

**Decisão proposta:** aprovar o princípio de que o **papel de Administração Geral, atendimento de suporte, operador de infraestrutura, auditor ou agente técnico não autoriza por si só acesso ao conteúdo comercial de tenant**. São fronteiras distintas:

- **Controle central:** empresas, planos, configurações, estado de serviço e indicadores conforme permissão real do painel, preferencialmente sem conteúdo de clientes.
- **Telemetria diagnóstica:** logs e métricas técnicos minimizados, protegidos e separados da trilha comercial; não transportar conteúdo pessoal indevidamente por debug.
- **Conteúdo de tenant:** registros, anexos, mensagens, relatórios, dados pessoais e histórico; exige mandato/credencial legalmente autorizado, recurso e ação exatos, conforme classificação e políticas.
- **Mutação de negócio:** diferente de consultar conteúdo; somente via operação tipada do módulo proprietário (ex.: retificação autorizada), sem SQL direto não controlado ou identidade humana do cliente emprestada.
- **Investigação e evidência:** propósito, caso e acesso próprio da Auditoria Geral; ler evidência não concede apagar, alterar ou exportar em lote.
- **Assistência por tela/compartilhamento pelo próprio cliente** (quando disponível) não é autorização implícita para ativar sessão de suporte ou reutilizar credenciais dele.

**Nenhum acesso físico novo é concedido.** Em especial, todos os dados tenant-bound continuam sujeitos ao contexto autorizado no Laravel e RLS PostgreSQL; a solução concreta de suporte sobre RLS é dependência técnica D06.

## 4. CORE05-D02 — Mandato excepcional específico por caso (PROPOSTA)

**Decisão proposta:** usar como referência lógica a figura de um **mandato de suporte restrito** (não uma membership irrestrita nem impersonação invisível), aprovado pela autoridade competente para aquele efeito, somente quando necessário e justificado.

| Componente conceitual do mandato | Conteúdo de referência | Fronteira |
|---|---|---|
| `support_case_ref`, motivo/finalidade | Chamado/incidente/solicitação comprovável, categoria e necessidade legítima | Abrir chamado **não** equivale automaticamente a conceder acesso |
| `tenant_ref`, alvos autorizados | Uma organização e recursos/objetos específicos, nunca "todos os clientes" por padrão | Mesmo CNPJ não autoriza entrar em outras organizações |
| `requester_ref`, `authority_ref` | Quem pediu e quem legitimamente pode autorizar; vincular aceites aplicáveis | Não presumir consentimento automático do usuário nem segunda aprovação universal |
| `operator_ref`, `service_ref` | Funcionário Imagine/identidade técnica que efetivamente atuará | Operador identificado; nunca conta/senha do cliente emprestada |
| `actions`, `field_scope`, `purpose` | Leitura, diagnóstico, ajuste tipado, exportação específica, categoria de campos e finalidade | Visualizar não concede exportar ou modificar; dado protegido exige regra própria |
| `authorization_state`, `policy_version` | Ato de autorização e versão da política competente | Solicitação pendente não produz direitos |
| `issued_at`, `expires_at`, `revoked_at` | Janela limitada de vigência, revogação e validade do contexto | Número de minutos e prazos ainda **não aprovados** |
| `correlation_ref`, `audit_ref` | Vínculo entre chamadas, operações, prova e resultado | Auditoria minimizada, sem duplicar a Auditoria Geral |

**Proposta de avaliação:** a autorização só vale se identidade do operador, MFA conforme contexto, caso, finalidade, tenant, objetos, ações/campos, status e janela permanecerem elegíveis. Prazos/aceite cliente/alçadas e meio técnico ainda D04–D10. Não exigir aprovação humana repetida para a **mesma decisão já válida**, mas **atos diferentes** ou ampliação de escopo exigem suas competências. Sem delegação genérica ou indefinida por contrato de suporte.

## 5. CORE05-D03 — Ciclo de vida, revogação e auditoria do acesso excepcional (PROPOSTA)

**Decisão proposta:** adotar um ciclo lógico diferenciado de solicitação, autorização, atuação e encerramento sem confundir estado de acesso com resultado de suporte.

### Estados conceituais candidatos — não enum físico homologado

`solicitado → em validação → (negado | autorizado com escopo/validade) → em uso → (expirado | revogado | encerrado)`.

- **Abertura:** registrar caso, ator, finalidade e organização; antes de autorização, nenhum direito adicional.
- **Aprovação competente:** conceder somente o escopo necessário conforme política e legitimidade; evitar autoaprovação de autoridade indevida. Se uma ação já exigia aprovação, a autorização de suporte não a substitui.
- **Execução:** registrar operador humano/técnico, recursos consultados/alterados, ação, hora, motivo, resultado e referência ao caso de forma proporcional; **não registrar senhas, segredos, payload integral ou transcrição de conversa por padrão**.
- **Limites:** negar ações fora do tenant, recurso, campo, finalidade, janela e alçada. Não autorizar uma exportação inteira apenas porque leitura pontual foi aprovada. Impedir automações, IA ou n8n de ampliar o mandato.
- **Expiração/revogação:** cortar novos efeitos, inclusive sessão de suporte, link, download temporário e jobs ainda pendentes que dependem do mandato. Conclusões anteriores do módulo proprietário não são apagadas; resultado inconclusivo é reconciliado via CORE-P0-03.
- **Encerramento:** registrar resultado do suporte, mudança oficial quando realmente confirmada, responsável, tempo de acesso e evidências mínimas; separação entre atendimento resolvido, técnico concluído e fato de negócio.
- **Auditoria da auditoria:** consultas e exportações da trilha protegidas conforme CORE-P0-06/INT-12. Administrador de tenant não deve apagar o histórico oficial.

**Não aprova** aviso automático a cliente, janela temporal, gravação integral de sessão, retenção de dados de diagnóstico, criação de portal de mandatos, acesso a todos os dados para suporte ou operação de emergência irrestrita. Essas escolhas ficam para deliberação.

## 6. Exemplos de aplicação — condição de autorização, não grants

| Exemplo | Conduta de referência |
|---|---|
| Cliente informa erro ao abrir agenda | Equipe Imagine começa com logs técnicos e configurações saneadas; visualizar cliente/agendamento específico exige mandato quando conteúdo de tenant for necessário |
| Agente detecta falha de API e aciona suporte | Diagnóstico da infraestrutura pode seguir competência técnica; não abre leitura de leads ou conversas dos tenants automaticamente |
| Operador precisa examinar lead específico para investigar bug | Obter autorização legítima por caso, tenant, ação e campos; sessão/logs específicos; não exportar base inteira |
| Operador pede cópia da tabela de clientes para "facilitar o suporte" | Negar como procedimento geral; portabilidade é autoatendida via IN-118; exceção dependeria de autoridade específica independente |
| Cliente quer trocar o CRM vinculado ao seu site | Seguir chamado/autorização de vínculo Site↔CRM; não migrar leads entre contas e não obter leitura implícita dos históricos |
| Suporte recebe senha de usuário | Não utilizar credencial emprestada para se passar por usuário; orientar recuperação segura e autenticação própria do suporte |
| Suspeita de comprometimento exige contenção imediata | Usar ações técnicas **previamente homologadas e autorizadas** conforme risco; acesso emergencial a dados do cliente, alçada e justificativa específicos precisam política de emergência D07 |
| Mandato vence enquanto há tarefa de reparo pendente | Revalidar antes de novo efeito; negar após expiração, preservar fato anterior e evidência, reconciliar resultados inconclusivos |
| Uma pessoa possui função Admin Geral e perfil de suporte | Não somar automaticamente poderes em tenant; checar permissão efetiva e mandato do caso |

## 7. Casos negativos documentais para homologação futura — NÃO EXECUTADOS

| Caso | Tentativa | Resultado necessário |
|---|---|---|
| CORE05-T01 | Admin Geral sem mandato consulta lead de tenant | Negar e auditar, sem RLS bypass |
| CORE05-T02 | Suporte com mandato A consulta objeto da empresa B ou do mesmo CNPJ em outra org | Negar por tenant e recurso |
| CORE05-T03 | Mandato permite ler 1 lead mas tenta exportar todos | Negar por ação, campo e escopo |
| CORE05-T04 | Operador recebe ticket mas não houve autorização competente | Não gerar acesso excepcional |
| CORE05-T05 | Sessão de suporte tenta continuar após expiração/revogação | Negar e auditar |
| CORE05-T06 | Job de reparo pendente é retomado após revogação | Revalidar antes de novo efeito e manter estado oficial anterior |
| CORE05-T07 | Agente técnico/n8n tenta elevar permissão do mandato via texto de tarefa | Negar autoconcessão |
| CORE05-T08 | Admin empresa tenta apagar prova de operação da equipe Imagine | Negar alteração de auditoria oficial |
| CORE05-T09 | Operador conhece token/senha de cliente e tenta acessar por impersonação não autorizada | Negar método; autenticação própria e caso válido |
| CORE05-T10 | Acesso a telemetria usa log com conteúdo pessoal ou segredo por padrão | Minimização e proteção; não converter log em banco de dados comercial |
| CORE05-T11 | Uma intervenção crítica requereu alçada do proprietário, mas existe mandato genérico de suporte | Mandato não dispensa ato/aprovação específicos |
| CORE05-T12 | Suporte declara problema corrigido apenas por retorno HTTP de integração | Exigir evidência de resultado do módulo proprietário |
| CORE05-T13 | Operador decide entrar em modo emergencial sem mecanismo homologado | Negar elevação improvisada e escalar; contenções já autorizadas seguem seus playbooks |
| CORE05-T14 | Exportação de auditoria usa mandato de leitura de evidência | Requer direito de exportação separado |

Reconciliar com S02/S09 REV-01, CORE01/02/03/04, OPS-T02, INT OWN-Q05/Q06 e critérios HT/G01–G06 sem criar testes redundantes. **Nenhum teste executado nesta fase.**

## 8. Decisões propostas agora — sujeitas a aceite expresso

- **CORE05-D01 — PROPOSTA:** adotar a **fronteira obrigatória entre Administração Geral/telemetria e leitura/alteração de dados dos tenants** (seção 3). Nenhum papel, agente ou operador Imagine recebe acesso a conteúdo comercial de cliente apenas por administrar o SaaS ou solucionar incidentes.
- **CORE05-D02 — PROPOSTA:** adotar o **modelo lógico de mandato excepcional por caso, tenant, finalidade, recurso/campo, ação, ator autorizado e janela** (seção 4), sujeito à autoridade competente. O ticket não autoriza automaticamente a operação; não presumir aprovação do cliente em todos os casos nem impor segunda aprovação administrativa universal.
- **CORE05-D03 — PROPOSTA:** adotar como **guia lógico** o ciclo de solicitação, autorização, execução, expiração/revogação, auditoria e encerramento (seção 5), com bloqueio de novos atos após fim do mandato e preservação/reconciliação dos fatos anteriores.

## 9. Pendências deliberativas e técnicas que permanecerão abertas

| Código | Dependência não encerrada | Competência |
|---|---|---|
| **CORE05-D04** | Catálogo exato de operações de suporte que usam só telemetria e das que exigem acesso a recurso, objeto ou campo; níveis sensíveis | Administração/Suporte, CORE02-D04/D06, Segurança |
| **CORE05-D05** | Titularidade e aprovação: quando o cliente autoriza, representantes elegíveis, hipóteses legal/contratualmente permitidas, alçadas e possibilidade de atuação sem aceite do cliente; política de minimização | Privacidade/LGPD, Contratos, CORE04-D08, INT OWN-Q05/Q06 |
| **CORE05-D06** | Projeto executável de mandato técnico: RLS sem bypass universal, identidade do operador, contexto do tenant, tokens, sessões, revogação e integração entre sistemas | CORE01-D05/D07, CORE03-D04/D06, CORE04-D05, SEG-01/02 |
| **CORE05-D07** | Procedimento de emergência/break-glass: quais medidas são pré-homologadas, autoridade de acionamento, duração, resposta, verificação, notificações e revisão posterior; vedar modo irrestrito improvisado | Segurança, Incidentes, Central de Aprovações, IN-167/168 |
| **CORE05-D08** | Comunicação com cliente e painéis de transparência, status do chamado, consulta de acessos, revogação e contestação, sem expor dados de outras contas | Suporte/Privacidade/UX, contratos |
| **CORE05-D09** | Auditoria classificada por categoria, campos, retenção, relatórios, acesso e exportação, incluindo auditoria de investigação e operação | CORE-P0-06, Auditoria Geral, INT-12, LGPD |
| **CORE05-D10** | Contratos formais por recurso/ação, critérios de homologação, responsáveis e testes negativos; reconciliação com gates globais | CORE-P0-10, INT P0-OWN/SEC/CON, G01–G06 |

**Sem fechamento por aceite parcial:** CORE05-D04 a D10, CORE01-D04..D07, CORE02-D04..D10, CORE03-D04..D10, CORE04-D04..D10, CORE-P0-06..10, INT P0 e IN-168 permanecem abertos. O próximo bloco documental da sequência será **CORE-P0-06 — categorias de auditoria e retenções**, após deliberação do responsável sobre esta base.

## 10. Controle de mudança

Esta proposta **não habilita acesso a qualquer cliente**, nem altera a regra de 30 min da Administração Geral, nem substitui self-service de dados IN-118, autorizações dos proprietários, LGPD/contrato ou a Central de Incidentes/Aprovações existente. Ações de suporte e agentes técnicos continuam restritas à autoridade que já possuem, sem "impersonation" invisível nem cadastro de superusuário universal.

**Proibido programar, criar recursos, testar em ambientes reais, provisionar, comprar ou implantar.** Somente documentação, revisão e decisões expressamente aprovadas. A programação depende do fechamento integral do planejamento do CRM Imagine e do gate final global.
