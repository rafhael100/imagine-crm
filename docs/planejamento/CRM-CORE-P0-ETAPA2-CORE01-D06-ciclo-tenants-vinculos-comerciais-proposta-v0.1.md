# CRM Imagine — Etapa 2: CORE01-D06 — Ciclo de vida das organizações, titularidade, Sites↔CRM e direitos comerciais (proposta v0.1)

**Data:** 04/11/2026.  
**Estado:** **PROPOSTA TÉCNICA DOCUMENTAL — CORE01-D06 ABERTA; D06-A/B/C/D AINDA NÃO APROVADAS**.  
**Escopo:** especificação conceitual de contratos de autoridade, vínculo, estado e reconciliação. **Não aprova esquema físico, fornecedor/gateway, operações reais, valor definitivo, grants, eventos executáveis nem código.**
**Continuidade autorizada:** [aceite preliminar CORE01-D05-A/B/C/D](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md), mantendo **CORE01-D05 aberta** com CORE02/03/04/05/09/SEG-01/02. CORE01-D04 A/B/C foram preliminarmente aceitas, mas D04 inteira continua aberta.

## 1. Fontes e prioridades documentais — não alterar decisões aprovadas

- [CORE01-D01..D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md): identidade global, um tenant por organização CRM, RLS e segregação; mesmo CNPJ não unifica dados ou direitos.
- [CORE08-D01..D03](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md), [CORE08 proposta](CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md) e [REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md): Básico público contratável independentemente, cortesia por contrato de Site elegível, trial único por CNPJ, ciclos e quotas por tenant.
- [IN-119](IN-119-gestao-cortesia-crm-basico.md), [IN-120](IN-120-encerramento-cortesia-continuidade-conta.md), [IN-121](IN-121-notificacoes-reativacao-automatica-cortesia.md), [IN-122](IN-122-multiplos-sites-contas-crm-cortesias.md): uma cortesia Básico por organização, vários sites sustentadores possíveis, cancelamento comum e transição 30 dias operacional, avisos 0/15/25/29/30, portabilidade, reativação condicionada.
- [COM-08](PORTAL-COM-08-inadimplencia-site-cortesia-crm-configuravel.md) e [COM-09](PORTAL-COM-09-transicao-cortesia-cancelamento-inadimplencia.md): elegibilidade para novas vendas definida por serviço/campanha/contrato; site suspenso após tolerância de 7 dias pode bloquear cortesia exclusiva; cancelamento formal por inadimplência gera **30 dias de transição restrita**, sem reativar operação plena.
- [IN-118](IN-118-central-backup-portabilidade-dados.md): autoatendimento de backup/exportação/importação e migração de dados realizada pelo cliente, com nova integração técnica gratuita via chamado quando muda o destino CRM.
- [IN-127 R01..R06](IN-127-R01-R06-ratificacoes-2026-11-03.md): destino CRM oficial e autorizado obrigatório para captação de formulário, inclusive cache; entrega de lead dura T0+72h+360h=432h, não se altera por troca de vínculo; sem redirecionamento implícito.
- [Gratuidade administrativa de qualquer plano](CRM-DEC-gratuidade-administrativa-qualquer-plano.md): concessão por Administrador Geral Imagine tem existência independente de Site e trial; adicionais grátis requerem ato distinto; precedência com outras concessões é **GRAT-P** ainda aberto.
- [CORE03](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md), [INT CT-03/04](integracoes/P0-CON-CT03-CT04-comandos-resultados-transicoes.md), [INT G01..G06](integracoes/P0-GATE-painel-fechamento-rastreabilidade-dependencias.md): emissor de evento, confirmação técnica e fonte oficial de negócio são distintos; todo contrato cross-sistema deverá ser versionado, autenticado e reconciliável.

**Precedência:** regras históricas exemplificativas não prevalecem sobre as decisões posteriores expressamente ratificadas, sobretudo IN127-R01..R06, COM-08/09 e concessão administrativa. Não reabrir preço/escopo por analogia.

## 2. Inventário lógico de objetos e autoridade — não é esquema de tabelas

| Objeto | Dono funcional da verdade | Invariante a preservar |
|---|---|---|
| **Identidade global** | IAM/CORE | Login único com memberships independentes, nenhuma permissão só por e-mail igual |
| **Pessoa jurídica/CNPJ** | Cadastro/Contratos com verificação de titularidade | Chave de elegibilidade do histórico de **um trial por CNPJ**, não chave de isolamento de dados |
| **Organização CRM / tenant** | CRM/CORE | Identificador independente, políticas, status, dados e entitlements próprios |
| **Membership e papéis** | IAM/Admin Empresa com direitos | Acesso humano por tenant, Básico com um administrador MFA; sem reativação por contrato de Site |
| **Contrato de Site e campanha** | Portal/Sites e FIN/CON conforme fato | Serviço/campanha/aceite determina se há benefício; edição futura não revoga concessão anterior legitimamente estabelecida |
| **Vínculo Site↔tenant CRM** | Portal↔CRM/Integrações com verificação bilateral | Destino explícito autorizado; troca por chamado, sem migração de dados |
| **Benefício Básico cortesia** | Entitlement CRM após comprovação do contrato elegível | Máximo **uma cortesia efetiva por tenant**, não acumula usuários, armazenamento ou créditos |
| **Trial CRM** | CRM/FIN/CON | Uma utilização de 14 dias por CNPJ, não renovável por Site, e-mail ou organização adicional |
| **Assinatura CRM paga** | FIN/CON CRM | Independente do Site: cobrança contratada e estados próprios |
| **Gratuidade administrativa** | Administração Geral Imagine + FIN/CON | Qualquer plano pode ser isento conforme ato legítimo, sem depender de Site; extras separados |
| **Formulário e lead** | API de recepção/CRM destino autorizado | Sem CRM oficialmente vinculado, formulário não pode captar; confirmador CRM decide entrega |
| **Auditoria e retenções** | Auditoria Geral + domínio proprietário | Regimes distintos: trial 60 dias após expiração; CRM 120 dias após cancelamento formal CRM; eventos de Site não são cancelamento CRM |

**Camadas que não devem ser fundidas:** estado do tenant, membership, Site, contrato Site, destino CRM, cortesia, trial, assinatura CRM paga, gratuidade administrativa, entitlement efetivo e elegibilidade de anexos/IA. Uma falha de transporte ou suspeita de evento não pode indiscriminadamente colocar todas as camadas no mesmo estado.

## 3. CORE01-D06-A — Fontes oficiais, entidades independentes e escolha do destino (PROPOSTA)

Diretriz preliminar candidata:

1. Definir **organização CRM com identidade estável e própria**, separada do CNPJ, contrato Sites, assinatura FIN/CON, benefício e trial. Um CNPJ pode ter vários tenants legítimos independentes.
2. Em **cada contratação de site elegível**, o cliente escolhe expressamente uma organização CRM existente que pode administrar ou nova organização; não resolver destino apenas por CNPJ, e-mail, telefone, URL, nome ou sessão anterior.
3. Vincular Site↔tenant com titularidade, autorização da organização destino, serviço/campanha elegível e **referência do contrato oficial**. Um evento técnico não concede acesso, criará tenant/benefício ou mudará direitos sem confirmação competente.
4. **Alteração de vínculo** deve passar por chamado autenticado e verificação das duas organizações e da autorização de novo destino. Imagine executa a nova integração técnica sem taxa, não copia históricos/arquivos, não transfere credenciais e não reatribui leads por conta própria. Dados históricos pertencem ao tenant onde foram legitimamente criados, e o cliente usa exportação/importação quando cabível.
5. Sites estáticos podem ser publicados sem CRM quando as condições já aprovadas forem atendidas, mas **formulários devem permanecer bloqueados no backend até destino CRM válido**, inclusive em cache ou versões antigas.

**Não decidido em D06-A:** esquema, campos/chaves, entidades físicas, migração, limitação de novas organizações, comprovação exata de titularidade, política antifraude, análise legal de representação e versão de contrato.

## 4. CORE01-D06-B — Ciclo de benefício, trial e assinatura por organização (PROPOSTA)

Diretriz preliminar candidata:

| Evento/condição comprovada | Resultado esperado no domínio competente |
|---|---|
| Primeiro Site ativo e elegível vinculado a tenant A | A pode ter **uma** cortesia Básico, se não houver restrição/precedência contratual impeditiva |
| Segundo Site elegível também vinculado a A | Sustenta a mesma cortesia de A: **sem duplicação** de franquias, usuários, créditos ou descontos |
| Segundo Site elegível vinculado a novo tenant B autorizado (mesmo CNPJ) | B pode ter seu próprio benefício Básico; não compartilha usuários, dados, assinatura ou trial com A |
| Novo tenant/Site para CNPJ que já usou trial | **Não criar segundo trial**; cortesia legítima independente pode existir |
| Site único deixa de ser elegível, mas tenant tem CRM pago válido | **Não suspender plano pago** por contrato de Site |
| Pagamento do CRM está em atraso | Aplicar **apenas** ciclo financeiro CRM, distinto da inadimplência de Site e do fim da cortesia; tolerância/cancelamento conforme contrato |
| Admin Geral concede isenção de mensalidade de plano | Ato específico por tenant independente do Site, com vigência determinada ou indeterminada, sem conceder adicionais automaticamente |
| Plano superior ativo e Site elegível para Básico | Não deduzir crédito/desconto automático do Básico contra plano superior |
| Expiração de gratuidade ou cortesia | **Nunca contratar nem cobrar CRM automaticamente**; continuidade depende de aceite expresso |

**Ainda depende de decisão:** precedência entre isenção administrativa, benefício de site, assinatura paga, descontos e adicionais, planos superiores, mudanças no meio do ciclo, créditos/estornos, casos conflitantes e estados físicos (CORE08-D05/D07/D09, GRAT-P, FIN/CON). **Nenhuma prioridade normativa de desconto está sendo aprovada aqui.**

## 5. CORE01-D06-C — Estados independentes, marcos formais e transições (PROPOSTA)

A separação de estados deve obedecer aos **efeitos já aprovados**, sem inventar máquina física:

| Marco oficial | Situação documentada a preservar | Risco negado |
|---|---|---|
| Site sustenta uma única cortesia e entra em atraso, ainda sem suspensão formal | Avaliar regras próprias de tolerância de Site; não considerar mera tentativa de cobrança uma suspensão confirmada | Suspensão prematura por erro HTTP ou fatura em aberto não confirmada |
| Site único sustentador é formalmente **suspenso por dívida após 7 dias de tolerância** | Acesso operacional do Básico cortesia fica **restrito**, preservando portabilidade/regularização e dados; se outro site elegível sustenta tenant, cortesia permanece | Bloquear CRM pago ou tenant de outro contrato |
| **Cancelamento comum** do último Site elegível | Iniciar **30 dias de transição a partir do cancelamento formal**, com CRM Básico **operando normalmente** e avisos 0/15/25/29/30 | Suspender imediatamente, cobrar CRM sem contratação ou iniciar retenção de 120 dias de cancelamento CRM |
| **Cancelamento formal por inadimplência** após suspensão do último Site elegível | Iniciar **30 dias de transição restrita desde cancelamento formal**, mantendo bloqueio operacional anterior e permitindo portabilidade/contratação expressa | Reativar operação só porque o site passou de suspenso a cancelado |
| Outro Site elegível válido já sustenta tenant | Manter/reativar cortesia conforme contratos; cancelar avisos/suspensão que perderam causa | Iniciar transição indevida pela perda de apenas um Site |
| Cliente contrata CRM pago voluntariamente | Conceder direitos apenas após confirmação FIN/CON e preservar dados, sem perder tenant | Cobrar por fim da cortesia sem consentimento |
| Transição termina sem Site elegível nem plano pago | Restringir operação do Básico, manter acesso autenticado a portabilidade e contratação nos limites já aprovados | Eliminar dados imediatamente ou conceder uso pleno implícito |
| Assinatura **CRM** é formalmente cancelada | Apenas então considerar relógio próprio de **120 dias de retenção pós-cancelamento CRM**, respeitando LGPD/backup/obrigações | Tratar suspensão ou cancelamento de Site como cancelamento CRM |
| Trial expira após 14 dias | Aplicar suspensão própria e retenção histórica de **60 dias após expiração**, sem novo trial por outra organização | Usar contagem do trial para encerrar contratos Sites ou outro tenant |

**Notificações:** IN-121 aprovou painel/e-mail em dias 0/15/25/29/30 para fim definitivo do último vínculo; em transição restrita por inadimplência o conteúdo deve informar que a operação **já está bloqueada**. Esse cronograma não é aplicado automaticamente à mera suspensão temporária por dívida. A reativação automática por nova elegibilidade deve respeitar impedimentos contratuais/autoridade, com avisos pendentes anulados somente quando a causa foi oficialmente resolvida.

**Não decididos em D06-C:** relógio exato, fuso, granularidade, estados de exceção/contestação, provedor de avisos, forma de cobrança, retry de pagamentos, condições de reativação de contrato CRM formalmente cancelado, tratamento de concorrência e eventos tardios.

## 6. CORE01-D06-D — Reconciliação, idempotência e auditoria (PROPOSTA)

Diretriz preliminar candidata para os futuros contratos tipados Portal/CRM/FIN/CON/INT:

1. Cada operação de criar tenant, vincular/desvincular Site, ativar/revogar cortesia, consumir trial, iniciar transição, mudar assinatura e reativar deve possuir **identidade de comando/contexto/versionamento**, responsável pelo efeito, origem/autorização e registro de resultado oficial.
2. **Evento repetido ou fora de ordem** não cria segundo tenant, segundo trial, segundo benefício, cobrança duplicada ou mudança de destino. Ações reversíveis e reprocessos exigem idempotência contextual, controle de concorrência e verificação do estado oficial antes de repetir.
3. **ACK/HTTP 2xx, status n8n, pagamento iniciado, webhook recebido ou fatura solicitada não são resultados comerciais oficiais.** Confirmador é o proprietário do domínio, com critérios ainda a concretizar.
4. Se troca de destino Site ocorrer com **leads em trânsito**, não redirecionar payload pendente automaticamente para B só porque o vínculo atual aponta B; distinguir autorização/destino comprovados no aceite original de novas submissões. Preservar T0/432h e confirmação oficial do CRM; sem enviar para tenant não autorizado.
5. Suspensão, desligamento de membership, cancelamento de Site, concessão de cortesia e cancelamento do CRM são eventos distintos. Restauração/replay de backup, duplicação de evento e mudança de titularidade não ressuscitam autorizações revogadas nem eliminam históricos.
6. A auditoria registra proveniência, proprietário, ator causal, tenant, contrato e versão, evento/marco, transição, exceção, decisão de autorização e resultado oficial sem expor dados pessoais além do necessário, chaves ou segredos.
7. Automações/IA/n8n não têm autoridade para conceder plano, contratar, alterar titularidade, cobrar ou criar destino CRM por inferência; motor próprio do CRM e integrações respeitam direitos/contratos oficiais.

**Não decididos em D06-D:** schema do envelope, chaves/algoritmos, mecanismos de reconciliação, fila física, locks/serialização, contratos CT/OP, retry, TTL, topologia, auditoria de categoria, responsabilidades humanas e comportamento de falha do provedor.

## 7. Cenários adversos de homologação futura — NENHUM EXECUTADO

| Ref documental | Condição de risco | Esperado quando a homologação for autorizada |
|---|---|---|
| CORE01-D06-T01 | Mesmo CNPJ tem Sites A/B e escolhe tenants X/Y | Dois tenants sem vazamento, no máximo uma cortesia em cada |
| CORE01-D06-T02 | Dois Sites elegíveis apontam ao mesmo tenant X | Uma cortesia única, quotas não acumuladas |
| CORE01-D06-T03 | CNPJ usou trial, cria nova organização e novo Site | Não renovar trial; avaliar cortesia por contrato separado |
| CORE01-D06-T04 | Evento de vínculo/assinatura chega duplicado/fora de ordem | Não duplicar conta, benefício, trial ou cobrança |
| CORE01-D06-T05 | Site sem benefício contratual tenta ligar cortesia | Negar sem provar elegibilidade |
| CORE01-D06-T06 | Perda de Site único com CRM pago ativo | Manter CRM pago e dados |
| CORE01-D06-T07 | Último Site é cancelado ordinariamente | 30 dias operacionais, avisos corretos e sem cobrança |
| CORE01-D06-T08 | Último Site suspenso por dívida é formalmente cancelado | 30 dias restritos, sem reativar operação, portabilidade possível |
| CORE01-D06-T09 | Segundo Site sustenta mesma cortesia e primeiro é cancelado | Não iniciar transição |
| CORE01-D06-T10 | Cliente altera destino Site↔CRM por chamado, com lead pendente | Novas submissões usam vínculo autorizado; antigas não são redirecionadas implicitamente |
| CORE01-D06-T11 | Site estático em cache usa formulário sem CRM autorizado | Backend bloqueia recepção |
| CORE01-D06-T12 | Cancelamento do Site tenta iniciar retenção CRM de 120 dias | Não: marco somente cancelamento formal CRM |
| CORE01-D06-T13 | Gratuidade administrativa independente existe, Site cancela | Não revogar isenção por evento do Site |
| CORE01-D06-T14 | Uma conta suspensa por fim de cortesia pede portabilidade | Permitir direitos restritos de export/download conforme IN-118/120 |
| CORE01-D06-T15 | Evento de pagamento tem sucesso técnico mas resultado financeiro inconclusivo | Não liberar entitlement por ACK; reconciliar com FIN/CON |
| CORE01-D06-T16 | Serviço tenta alterar Site de X para Y apenas por mesmo CNPJ | Negar sem chamado e autorização bilateral |

Esses casos são **roteiros textuais** a reconciliar com CORE08-T, CORE01-T, REV01 S01..10 e INT HT01..18; não criam uma suíte redundante ou testes a executar agora.

## 8. Decisões para manifestação expressa — AINDA NÃO APROVADAS

- **CORE01-D06-A — PROPOSTA:** aceitar a separação de organização/tenant, titularidade/CNPJ, contrato/campanha Sites, vínculo autorizado Site↔CRM e resultado oficial de cada proprietário, incluindo escolha explícita do destino e migração somente por iniciativa do cliente.
- **CORE01-D06-B — PROPOSTA:** aceitar a segregação de fontes de direito (Básico público, cortesia, trial único por CNPJ, CRM pago, gratuidade administrativa e adicionais) por organização, sem cumular cortesias ou cobrar automaticamente.
- **CORE01-D06-C — PROPOSTA:** aceitar as transições lógico-contratuais distintas para suspensão por dívida do Site e cancelamentos comuns/por inadimplência, transições de 30 dias normais/restritas, avisos, acesso restrito e retenções em marcos próprios, sem implementar máquinas de estados.
- **CORE01-D06-D — PROPOSTA:** aceitar confirmação oficial, idempotência/reconciliação, preservação de origem/destino de leads em trânsito e auditoria por contrato, sem admitir eventos técnicos como autorização ou efeito definitivo.

**O aceite futuro dessas quatro diretrizes seria somente documental preliminar: não concluiria CORE01-D06**, que depende do fechamento das regras/contratos CORE08-D04..D10, FIN/CON, Portal/Sites, IN-117..122, INT/SEG/ARQ, SEG-01/02 e decisões dos proprietários.

## 9. Pendências e gates mantidos ABERTOS

- **CORE01-D04** continua aberta apesar de A/B/C preliminarmente aceitas; depende CORE02/04/07/08. **CORE01-D05** continua aberta apesar de A/B/C/D preliminarmente aceitas; depende CORE02/03/04/05/09 e SEG-01/02. **CORE01-D06 e D07** não aprovadas e abertas.
- **CORE02..CORE10-D04..D10** e **67 IDs técnicos** na [matriz da Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) permanecem abertos.
- **INT P0-OWN/SEC/CON/DOC/TR/DEP/VER/ACC/XREF/GIT, G01..G06, IN-168 ARQ-01/02/03, SEG-01/02, AGT-01, FIN/CON, GRAT-P, LGPD, custos/provedores/licenciamento** não são encerrados.
- **NO-GO**: nenhuma programação, Codex, teste, cobrança, criação de tenant, vínculo real, credencial, mudança de plano, implantação, compra ou contrato até aprovação futura expressa do planejamento global e das especificações necessárias.

**Próximo passo condicionado a deliberação:** registrar aceite preliminar D06-A/B/C/D, se expresso pelo responsável, e prosseguir a CORE01-D07 somente como proposta técnica documental, preservando bloqueios e gates.
