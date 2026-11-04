# CRM-CORE-P0-03 — Ponte de autoridade em jobs, eventos, webhooks e filas (v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA DOCUMENTAL PARA DELIBERAÇÃO — NÃO APROVADA. CORE-P0-03 ABERTO.**  
**Fase:** planejamento e versionamento exclusivamente; sem código, testes reais, credenciais/integrações ativadas, provisionamento ou contratação.  
**Objetivo:** proteger a identidade, o contexto de tenant, a legitimidade de uma operação, seus efeitos e o retorno de resultado quando há processamento assíncrono ou comunicação entre sistemas, sem criar IAM, filas, motores ou auditorias paralelos.

## 1. Proveniência e hierarquia

**Aceites já expressos (não reabrir):**
- [CORE-P0-01 D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): identidade humana global, memberships por tenant, 1 organização CRM = 1 tenant lógico, verificação de autorização e isolamento Laravel + PostgreSQL RLS, sem schema físico homologado.
- [CORE-P0-02 D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md): autorização cumulativa por identidade, organização, entitlement, recurso/ação/campo e regras do módulo; vocabulário semântico de ações, matriz de famílias de atores, sem novas permissões concedidas.
- [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) N-01/02/06/12/13: jobs/integrações não podem escapar do tenant; desligamento revoga acesso do tenant, não a identidade global; provedores não criam direitos internos.
- [CRM-AUT-P0](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md): base documental aprovada de eventos, filas duráveis, outbox/inbox quando aplicáveis, idempotência, estado oficial, pausas/revalidação e tratamento inconclusivo; números, schemas e limites abertos.
- [Motor próprio CRM × n8n](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md): clientes configuram automações executadas pelo motor próprio do CRM; n8n limitado ao uso interno licenciado da Imagine; operação fundamental do CRM e ingresso de leads não dependem dele.
- [INT-R01](integracoes/INT-R01-governanca-contratos-homologacao.md), [INT-09](integracoes/INT-09.md), [CT-03/04](integracoes/P0-CON-CT03-CT04-comandos-resultados-transicoes.md), [P0-SEC](integracoes/P0-SEC-politicas-autorizacao-CT02.md): comandos/eventos tipados e versionados, autoridade separada de executor, entrega técnica ≠ fato de negócio, aprovação contextual, reconciliação, sem retry cego de efeito externo.
- [IN-148](IN-148-reconciliacao-inteligente-leads.md), [IN-149](IN-149-protocolo-entrega-confirmacao-leads.md) e **[IN-127-R01–R06](IN-127-R01-R06-ratificacoes-2026-11-03.md)**: contratos específicos Sites × CRM têm precedência sobre exemplos genéricos de fila. R01 T0 persistência durável na API; R02 72h +360h =432h corridas; R03 descarte dos payloads pendentes após prazo; R04 auditoria técnica mínima até 90×24h; R05 após vencimento consulta apenas, sem reenvio; R06 bloqueio de formulário sem destino CRM autorizado mesmo em página/cache. Sem replicar tais prazos em filas internas CRM.
- [IN-151](IN-151-gestao-inteligente-credenciais-chaves-integracao.md), [IN-167](IN-167-consolidacao-arquitetura-agentes-tecnicos.md), [IN-168](IN-168-matriz-pendencias-fechamento-arquitetura.md), [INT P0 GATE](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md): cofre, serviço, agentes, topologia e contratos granulares ainda contêm P0.

**Regra:** documento propõe o contrato transversal CORE para empregar serviços existentes; não se sobrepõe a FIN/CON, DAT, VEN, ATD, AGD, AUT, INT, Sites/Portal, Central de Eventos/Filas/Aprovações/Auditoria.

## 2. Classes de execução e autoridade — distinção fundamental

| Classe | Quem origina | Quem autoriza no momento de efeito | Exemplo / limite |
|---|---|---|---|
| **A — Ato humano diferido** | Pessoa autenticada e membership ativa no tenant ao pedir | Identidade/segurança + direitos atuais + regra do dono do negócio + aprovação específica se exigida | Exportação em lote, mesclagem autorizada, proposta, envio agendado; perder membership antes do efeito deve bloquear novos atos não autorizados |
| **B — Processo interno obrigatório do CRM** | Evento/fato oficial de módulo, sem depender do funcionário original permanecer logado | Identidade de serviço autorizada para a rotina oficial e tenant/estado atuais; módulo proprietário controla o efeito | Registro de evento, manutenção de consistência, avisos obrigatórios; **não parar toda operação oficial** porque o funcionário causal saiu da empresa |
| **C — Automação comercial do cliente** | Definição publicada pelo cliente e evento elegível do CRM | Motor próprio verifica versão ativa/instância, entitlement/cota, tenant, ação/objeto, permissões aplicáveis, canal/consentimento e estado atual | Tarefa, notificação comercial, mensagem externa; indisponibilidade do n8n não pode afetar operações obrigatórias |
| **D — Ingresso externo / webhook / API / site** | Sistema ou visitante fora da sessão CRM | Serviço oficial autentica/verifica origem, assinatura/provedor quando aplicável, contrato/site/formulário/destino e tenant autorizado; negócio confirmado só pelo CRM | Visitante **não precisa de membership CRM**, mas formulário só é recebido por API quando há destino oficial válido; token ou HTTP 2xx não concede autorização |
| **E — Operação técnica interna Imagine** | Agente autorizado, sistema de suporte, monitoramento ou n8n elegível | Identidade técnica + ambiente/tenant/ação/versão/playbook + alçada/autorizações pertinentes; dono do fato confirma negócio | Manutenção, diagnóstico, recuperação; N2 executa apenas playbooks homologados, N3 exige decisão humana específica onde aplicável; uso n8n depende de licenciamento confirmado |
| **F — Callback/resultado externo** | Provedor, conector, webhook de resposta | Validar origem, correlação com comando prévio, tenant, versão, estado esperado e confirmação competente | ACK, entrega de mensagem ou pagamento intermediário não é prova final de venda/pagamento/agendamento |

**Princípio de autoria causal:** registrar a pessoa ou processo que desencadeou o trabalho e a identidade técnica que o executou. **Não** reutilizar a sessão/token humano como credencial geral de worker; **não** presumir que toda tarefa do sistema perde legitimidade quando um funcionário é desativado. Cada classe precisa política de cancelamento/revalidação própria, controlada pelo proprietário do ato.

## 3. CORE03-D01 — Envelope lógico de contexto e identidade (PROPOSTA PARA ACEITE)

Toda unidade de trabalho protegida deve possuir contexto tipado e verificável; o quadro abaixo é **semântico e não define schema físico**.

| Campo conceitual | Finalidade | Fonte de confiança |
|---|---|---|
| `tenant_id`, `environment_id` | Delimitar organização e ambiente; não atravessar tenants sem contrato explícito | Resolução do backend/contrato oficial, nunca valor arbitrário recebido |
| `operation_id`, `operation_type`, `schema_version` | Identificar intenção estável, tipo de comando e contrato da versão | Motor/módulo proprietário, catálogo homologado |
| `initiator_type`, `initiator_ref`, `service_principal_ref` | Distinguir pessoa/serviço causal do executor efetivo | Identidade central, credencial técnica vigente |
| `authorization_ref`, `entitlement_ref`, `membership_ref` quando aplicável | Referenciar base válida de autorização sem transportar token secreto | Autorizador e proprietários de assinatura/vínculo |
| `resource_type`, `resource_ref`, `requested_action`, `field_scope` | Definir objeto exato, operação e campos, de modo revalidável | Módulo dono, política CORE02 |
| `source_ref`, `destination_ref`, `origin_proof_ref` | Verificar provedor/conta/site/formulário/destino e identidade da mensagem, quando aplicáveis | Catálogo de conexões, assinaturas e vínculo oficial de origem |
| `event_id`, `command_id`, `causation_id`, `correlation_id` | Separar evento, comando lógico, causa e trilha; evitar loop e duplicação | Emissor oficial, sem dar autoridade ao campo sozinho |
| `idempotency_ref`, `attempt_id`, `resource_version` | Deduplicar efeito lógico, distinguir tentativa, controlar concorrência e versão de recurso | Proprietário do efeito e contrato CT-03/04 |
| `created_at`, `eligible_at`, `expires_at`, `policy_version` | Marcar janela de elegibilidade e políticas de prazo quando existirem | Motor de Datas/serviço proprietário; prazos específicos por módulo |
| `payload_ref` ou campos minimizados autorizados | Vincular dados necessários sem copiar segredos/PII para todo log e fila | Armazenamento apropriado com ACL e proteção por tenant |

**Recomendação:** o envelope jamais funciona como permissão autossuficiente. Ao executar, o serviço destinatário valida a procedência e a autorização atual. Tokens, senhas, secrets de provedores, anexos sensíveis e dados pessoais desnecessários não entram em logs, mensagens públicas ou prompts. Se dados pessoais transitarem tecnicamente, aplicar minimização e criptografia/acesso apropriado por contrato. IDs definitivos, assinaturas, criptografia, formato, TTL e transporte seguem D04–D10.

## 4. CORE03-D02 — Cadeia de responsabilidade por fronteira (PROPOSTA PARA ACEITE)

**Fluxo de referência, reutilizando motores centrais existentes:**

1. **Origem legitimada:** evento interno só nasce de fato persistido/confirmado pelo módulo proprietário; solicitação externa deve ser autenticada e qualificada pelo serviço apropriado, com destino verificado.
2. **Persistência durável:** salvar estado/identidade da operação antes de anunciar seu recebimento como durável, de acordo com contrato; aplicar outbox transacional ou equivalente quando escrita de negócio e publicação de evento exigirem atomicidade local. Não utilizar apenas Redis/cache volátil como persistência oficial de trabalho durável.
3. **Entrega com rastreabilidade:** filas transportam intenções tipadas; reentrega pode ocorrer. Consumidor registra inbox/deduplicação por contexto e versão, sem bloquear fatos genuinamente distintos.
4. **Revalidação no destino:** antes do efeito, validar o serviço executor e contrato de delegação, tenant, recurso, estado do dado, plano/canal/consentimento e autorização atual aplicável à classe A–F; em caso de não autorização, bloquear novo efeito com evidência, sem apagar fato anterior.
5. **Operação com efeito:** emitir comando idempotente quando possível e controlar concorrência. Não tratar envio, ACK, resposta 2xx, job n8n concluído ou callback não confirmado como fato oficial.
6. **Confirmação:** módulo dono do negócio declara estado oficial; CT-04 separa resultado técnico, resultado inconclusivo e confirmação final.
7. **Recuperação:** timeout após transmissão implica consulta/reconciliação e, quando for seguro, retentativa sob a mesma intenção lógica e autorização válida; jamais repetir gravação não idempotente às cegas.
8. **Auditoria/observabilidade:** registrar decisões de autorização, tentativas, estados, causas, versões e resultado no sistema de auditoria/categorias existentes, sem construir trilha paralela e sem vazamento cross-tenant.
9. **Conclusão segura:** processos persistentes têm transições/versionamento compatíveis; finalização de execução ou expiração não muda retroativamente o fato do módulo proprietário.

**Limites:** oferta de negócios/créditos, pagamento, agenda, envio WhatsApp, importação/mesclagem, exclusão e concessão de plano são operações sujeitas a suas regras específicas; nenhuma etapa acima substitui consentimento, aprovação legítima ou efeito oficial.

## 5. CORE03-D03 — Revogação, falha segura e tratamento de operações pendentes (PROPOSTA PARA ACEITE)

**Princípio proposto:** a legitimidade de uma intenção na entrada **não é autorização permanente para todos os efeitos futuros**. Ao retomar, recuperar, reenfileirar ou executar ação externa, confirmar direitos atuais compatíveis com a **classe A–F** e o efeito concreto.

| Ocorrência | Conduta lógica sugerida, preservando competências |
|---|---|
| Funcionário perde acesso ao tenant antes de sua exportação ser gerada | Bloquear nova extração/entrega não autorizada, proteger arquivo temporário e registrar motivo. Direitos passados não autorizam download posterior |
| Funcionário desligado havia causado evento de criação de lead já confirmado | Preservar o fato legítimo; rotinas internas obrigatórias por identidade de serviço continuam, se elegíveis e independentes da sessão humana |
| Empresa perde direito a automação comercial/IA/canal durante espera | Pausar/bloquear **efeitos futuros dependentes desse direito**, preservar histórico; não suspender obrigações centrais do CRM |
| Suporte excepcional expirou | Impedir nova operação em nome daquele mandato, invalidar acesso atual; suporte não herda privilégios contínuos |
| Credencial externa revogada, trocada ou provedor inativo | Restringir ações externas dependentes; confirmar revogação remota separadamente, manter consistência de estados já oficiais |
| Webhook repetido, não assinado, origem incompatível ou corpo forjado | Bloquear/descartar segundo política do receptor, sem efeitos de negócio; auditar evidência minimizada, não criar falso vínculo |
| HTTP timeout após comando financeiro, de reserva ou mensagem | **Resultado inconclusivo**; consultar estado oficial antes de qualquer novo comando, nunca reenviar cegamente |
| Tenant A e B produzem mesmo identificador de objeto | Escopar operação por tenant e IDs lógicos; um evento de A nunca pode atuar em B |
| Queda do autorizador ou contexto incompleto | Falha segura **na operação afetada**, com alerta/reconciliação, não inferir permissão; evitar travar desnecessariamente operações internas essenciais de outros tenants |
| Cliente muda destino de formulário/site | Revalidar o vínculo oficial no backend para **novos recebimentos**; submissões já aceitas mantêm origem, T0 e regras específicas; não redirecionar payload automaticamente |
| Prazo de leads chega a T0+432h sem confirmação CRM | Encerrar novas tentativas e cumprir [IN-127-R01–R06](IN-127-R01-R06-ratificacoes-2026-11-03.md), excluindo payload pessoal pendente das estruturas ativas/temporárias nos limites aprovados; após prazo, apenas reconciliação consultiva minimizada |
| Evento de sistema obrigatório chega quando fila comercial está pausada | A rotina obrigatória elegível não depende do slot comercial nem do n8n; preservar prioridades e isolamento de filas |

**Não presumir:** expiração numérica universal de token/contexto, cancelamento automático de todos os trabalhos após desligamento, prazo único de retenção de filas, "exactly-once" distribuído, RPO/RTO, segundo aprovador obrigatório para todo ato, rollback reversível de negócio.

## 6. Estados, responsabilidade por domínio e interoperabilidade

| Fronteira | Fonte de autorização | Estado / confirmação oficial | Observação |
|---|---|---|---|
| **IAM CORE/SEG** | Identidade, membership, plano/mandato, escopo e ação (CORE01/02) | decisão de autorizar/negar, não resultado comercial | CORE-P0-04/05/07/09 e SEG pendentes |
| **Motor Central de Eventos/Filas/Datas/Ações** | serviço/contrato interno homologado | evento/tarefa/execução técnica durável | Não afirmar pagamento, venda ou entrega com ACK da fila |
| **AUT/IA comercial** | direitos de plano e autorização para modelo/canal/ação | instância de automação e ação proprietária | Motor próprio CRM; quota técnica, saldo e versões separados |
| **DAT/ATD/VEN/AGD** | cliente/usuário/serviço autorizados por recurso e ato | lead, conversa/mensagem, oportunidade, reserva confirmados pelo módulo | Prazos e estados especializados prevalecem |
| **FIN/CON** | consumidor autorizado + plano/contrato e autorização de cobrança/benefício | cobrança, assinatura e concessão conforme fonte financeira | Ninguém concede plano por executar workflow |
| **INT-01..12 / CT-02..04** | contratos externos autorizados, menor privilégio e provedor | transporte, autorização externa e resultado técnico, com consulta ao proprietário | INT-09 coordena; INT-10 reconcilia; INT-12 consulta Auditoria Geral |
| **Sites/Portal / APIs dupla recepção** | destino oficial da organização autorizado no backend, site/formulário validado | recepção durável em API separada da entrega confirmada no CRM | IN-127-R01..R06 controla prazo e descarte, não fila CRM |
| **Agentes técnicos/n8n** | principal técnico, playbook, ambiente, supervisão e licença compatível | diagnóstico/ação técnica; negócio confirmado pelo proprietário | Não expor banco do CRM ou segredo irrestritamente |

Não criar motor global de estados ou política de recuperação paralela a esses proprietários.

## 7. Casos documentais de homologação futura (NÃO EXECUTADOS)

| Código | Cenário | Critério observável |
|---|---|---|
| CORE03-T01 | Job humano em A, membership revogada antes da extração | Nenhum novo dado gerado/baixado sem autorização atual; auditoria correta |
| CORE03-T02 | Contexto SQL/RLS de A é reutilizado para job B | Recontextualização segura, sem vazamento ou acesso a A |
| CORE03-T03 | Evento duplicado entregue à mesma automação duas vezes | Um único efeito lógico por dedupe/ID, com tentativas distintas |
| CORE03-T04 | Dois eventos legítimos com mesmo telefone/conteúdo | Não mesclar como duplicata técnica de submissão |
| CORE03-T05 | Worker morre após confirmar negócio e antes do ACK | Estado reconciliado; sem repetir efeito de negócio |
| CORE03-T06 | Timeout após transmitir comando a provedor externo | Resultado inconclusivo; consulta/recuperação segura, sem reenvio cego |
| CORE03-T07 | Webhook assinado mas sem contrato/tenant/destino elegível | Origem sozinha não autoriza ato ou muda tenant |
| CORE03-T08 | Plano comercial cai, mas rotina obrigatória continua elegível | Bloquear automação comercial incompatível e manter sistema obrigatório |
| CORE03-T09 | IA tenta executar workflow n8n em nome do cliente | Negar sem contrato/licença/autoridade; usar motor próprio para automação comercial |
| CORE03-T10 | Cliente troca vinculação do site enquanto entrega antiga está pendente | Não redirecionar envio antigo ao novo tenant; preservar proveniência e IN-127 |
| CORE03-T11 | Falta CRM vinculado, mas página estática antiga ainda tem formulário | Backend não aceita o payload, não declara T0/sucesso e não cria armazenamento alternativo |
| CORE03-T12 | T0+432h expira com falha e confirmação incerta | Bloquear reenvio, descarte e consulta nos termos R01–R06; sem excluir lead CRM já confirmado |
| CORE03-T13 | Acesso de suporte expira com job em fila | Negar novo efeito fora do mandato; preservar evidências minimizadas |
| CORE03-T14 | Replay de evento após restore com versões diferentes | Comparar versões/estado antes de efeito; sem duplicar negócio |
| CORE03-T15 | Processos N2/N3 de agente técnico e humano solicitam mesma operação | Não duplicar ato; manter alçada válida específica, proprietário e auditoria |

Os testes reais serão executados **somente na fase autorizada de desenvolvimento/homologação**, e estes casos serão reconciliados com S01..S10 de REV-01, AUT-X01..18, CT34-AC, HT-01..18 e G01..G06, sem redundâncias.

## 8. Deliberações propostas agora — NÃO APROVADAS

- **CORE03-D01:** aceitar o **envelope lógico** de identidade/contexto (seção 3) como referência de interoperabilidade, sem schema, tokens persistidos ou permissões automáticas.
- **CORE03-D02:** aceitar a **cadeia de responsabilidade** origem legítima → persistência durável → transporte → revalidação → comando do proprietário → confirmação oficial → reconciliação/auditoria (seção 4), reutilizando motores existentes e vedando sucesso por mero ACK/HTTP/n8n.
- **CORE03-D03:** aceitar a **distinção entre classes A–F e política de revogação/falha segura** (seções 2 e 5): autoria humana ≠ executor técnico; rotinas obrigatórias não ficam dependentes de sessão do usuário; direitos mutáveis são revalidados antes de novos efeitos; resultados incertos são reconciliados e dados de tenants não se misturam.

## 9. Especificações e contratos pendentes após eventual aceite D01–D03

| Código | Ponto ainda aberto / dono da revisão | Relação |
|---|---|---|
| **CORE03-D04** | Contratos físicos de envelope, schema/versionamento, mensagens de erro, validação de origem e proteção/transporte de credenciais; CORE/SEG/INT | CORE-P0-09, P0-CON/SEC |
| **CORE03-D05** | Regras exatas de delegação/revalidação por classe de execução, perfis, mudanças de membership, sessão, concessão, assinatura e consentimento; CORE/FIN/CON | CORE-P0-02/04/07/08, GRAT-P |
| **CORE03-D06** | Implementação segura do contexto de tenant no worker, conexão e transação PostgreSQL RLS; cache e reset de contexto, unit of work e segurança do pool; Arquitetura/SEG | CORE01-D05/D07, SEG-01/02 |
| **CORE03-D07** | Escolha/contrato das filas duráveis, outbox/inbox, idempotência/concorrência, estado, DLQ, retry e replay **por domínio**, sem prazos universais; Eventos/Filas/AUT/INT | AUT-P01..14, INT CT-03/04, ARQ-02 |
| **CORE03-D08** | Envelopes específicos por Sites/CRM, WhatsApp, FIN/CON, Agenda, IA e integrações; conferência IN-127-R01..R06, sem alterar prazo; proprietários e INT | IN-124/148/149; ATD/VEN/AGD |
| **CORE03-D09** | Limites, cotas, métricas, alertas, auditoria e retenções de jobs/estados/PII; cofre e monitoramento independente; Segurança/Auditoria/Operações | CORE-P0-06/09, IN-159/162/168 |
| **CORE03-D10** | Matriz de autoridade por operação/ator/recurso e testes futuros por fronteira, com provas de negação/revogação/restore e aceite documental final; proprietários e Segurança | CORE-P0-10, INT P0-OWN/SEC/ACC, G01..06 |

**Critério de fechamento real do P0:** esquemas, autorizações, protocolos e provas documentais consolidados, revisados por proprietários e compatíveis com CORE-P0-01/02, INT/SEG e aprovações comerciais. Aceitar o modelo lógico D01–D03 não conclui D04–D10.

## 10. Controle de estado

- **CORE-P0-01:** D01–D03 base aprovada; D04–D07 e P0 ainda abertos.
- **CORE-P0-02:** D01–D03 base aprovada; D04–D10 e P0 ainda abertos.
- **CORE-P0-03:** esta v0.1 é apenas proposta para apreciação; D01–D03 **NÃO aprovadas**, D04–D10 abertas.
- **Demais CORE-P0, INT P0, ARQ/SEG/AGT e G01..06:** não modificados por este documento.
- **Nenhuma programação, execução real, homologação física, contratação ou autorização final do Codex.**
