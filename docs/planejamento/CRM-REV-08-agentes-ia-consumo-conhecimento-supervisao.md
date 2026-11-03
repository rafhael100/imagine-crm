# CRM-REV-08 — Agentes de IA, Créditos, Base de Conhecimento e Supervisão

**Data:** 2026-11-03. **Estado:** **MINUTA DOCUMENTAL PARA REVISÃO — NÃO APROVADA**.
**Contexto:** produzida após [CRM-REV-07 aprovada como base](CRM-DEC-aprovacao-REV-07-base.md) e nova [decisão de gratuidade administrativa](CRM-DEC-gratuidade-administrativa-qualquer-plano.md), que **não aprovam automaticamente esta REV-08**.
**Âmbito:** CRM-13 IA comercial de clientes (Prospector, Comercial, atendimento, resumos/memória, consumo, Base de Conhecimento, supervisão), interfaces com CRM-04/05/10/11/14/17 e agentes técnicos Imagine IN-130..168.
**Fase:** apenas planejamento/versionamento. **Sem programação, testes executados, API de IA ativada, cobrança, compra de créditos, modelos em produção, alteração de prompt ou credenciais reais**.

## 1. Fontes examinadas e limites

| Ref | Documento | Pertinência |
|---|---|---|
| LEG-05 | [05 — Agentes de inteligência artificial](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/05-agentes-de-ia.md) | Agentes opcionais, cotas, histórico, custo, IA 24/7 e posse de conversa |
| REV02 | [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) | Planos/trial, créditos e divergência de prazo de pacote já identificada |
| REV04 | [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md) | Posse humana, IA 24/7 e transparência |
| REV05 | [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md) | Slots de automação e chatbot distintos de crédito IA |
| GRAT | [CRM-DEC — gratuidade administrativa](CRM-DEC-gratuidade-administrativa-qualquer-plano.md) | Plano concedido gratuitamente, limites extras/IA ainda em aberto |
| IN157 | [IN-157](IN-157-gestao-inteligente-modelos-ia-contingencia.md) | Modelos dos agentes técnicos; não são quotas/identidades do cliente |
| IN158 | [IN-158](IN-158-monitoramento-inteligente-custos-consumo-agentes.md) | Segregação de custos técnicos internos vs créditos de clientes CRM |
| IN163 | [IN-163](IN-163-ciclo-vida-conhecimento-agentes.md) | Conhecimento técnico estruturado e retenção própria |
| IN164 | [IN-164](IN-164-curadoria-inteligente-conhecimento-agentes.md) | Curadoria de conhecimentos técnicos, não de cliente por analogia |
| IN165 | [IN-165](IN-165-versionamento-homologacao-instrucoes-agentes.md) | Versionamento de instruções e políticas técnicas |
| IN166 | [IN-166](IN-166-gestao-inteligente-ferramentas-permissoes-agentes.md) | Autorização fora do texto do modelo |
| IN167 | [IN-167](IN-167-consolidacao-arquitetura-agentes-tecnicos.md) | Separação de agentes técnicos e produtos comerciais do CRM |

**Precedência:** o [LEG-05](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/05-agentes-de-ia.md) diz que saldo adicional comprado permanece até consumo, mas [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) registra referência a **decisão posterior de pacotes com 90 dias de validade**. **O texto original integral dessa aprovação posterior não foi conferido neste passo**. Não estabelecer um vencimento final até reconciliar fonte primária, versões e compra contratada.

## 2. Regras funcionais identificadas — 28 itens de cotejo

| ID | Assunto | Regra funcional e limite de autoridade |
|---|---|---|
| IA-R01 | Agentes opcionais por empresa | IA Prospector, IA Comercial e outros agentes comerciais da biblioteca são serviços sujeitos a entitlement, autorização e limites próprios. |
| IA-R02 | Atendimento IA opcional 24/7 | A empresa pode configurar IA fora do expediente humano; posse humana prevalece e não permite retorno automático ao término do horário. |
| IA-R03 | Transparência e humano | IA se identifica como virtual; cliente pode pedir humano; quando humano assume, suspender envios automáticos ao cliente na conversa. |
| IA-R04 | Tenant e permissões | Cada agente opera no escopo empresa, equipe, canal, lead e ferramenta autorizados; nunca lê/escreve outros tenants. |
| IA-R05 | Cotas por empresa | Consumo medido por agente/operação, isolado entre empresas, com cotas mensais que não acumulam e regras de planos a homologar. |
| IA-R06 | Crédito adicional autorizado | Pacotes adicionais só podem ser comprados com autorização expressa; nenhum consumo ou saldo negativo autoriza cobrança automática. |
| IA-R07 | Pacotes: prazo em conflito histórico | A branch inicial cita saldo comprado até consumo; registros de decisões posteriores apontam **validade de 90 dias**. Cotejar aprovação original posterior e versão comercial antes de fixar regra. |
| IA-R08 | Consumo contabilizado | Registrar empresa, agente, modelo, operação, versão, créditos debitados, custo estimado/confirmado, tempo, resultado, falha, correlação e idempotência. |
| IA-R09 | Bloqueio de esgotamento | Impedir novas operações faturáveis ao zerar crédito/limite; preservar dados, acesso humano e funções CRM não dependentes de IA. |
| IA-R10 | Recarga automática opcional | A eventual reposição automática depende de configuração e consentimento comercial específicos, nunca é inferida do uso ou de uma concessão de plano. |
| IA-R11 | Limite interno de custo | Limite por empresa/agente/modelo/tarefa e alertas de consumo anômalo; orçamento preventivo não se confunde com créditos do cliente. |
| IA-R12 | Gratuidade do plano não é IA ilimitada | Concessão de plano grátis por Administrador Geral Imagine concede somente os créditos/recursos incluídos no plano, sem presumir pacotes adicionais ou custo externo gratuito. |
| IA-R13 | Resumos de atendimento versionados | Resumos de IA ficam em histórico interno, com versão, autor, fonte e acesso restrito a funcionários autorizados; não viram mensagem ao cliente. |
| IA-R14 | Memória entre atendimentos | Uso de contexto entre contatos somente com finalidade, permissão e origem rastreável, sem copiar dados de outras empresas ou classes protegidas indevidamente. |
| IA-R15 | Conhecimento por níveis e fontes | Base da empresa pode combinar conteúdo cadastrado, importado e fontes autorizadas em níveis de acesso; origem, frescor, versão e revogação importam. |
| IA-R16 | Ciclo de revisão da Base de Conhecimento | Atualizações, descontinuação, curadoria, falhas de fonte e revisão periódica com acesso de responsáveis; frequência exata a cotejar com decisão original. |
| IA-R17 | Sugestão de dados e scoring | IA pode recomendar atualização cadastral, qualidade, prioridade e próximas ações; alteração de CRM é ato separado e depende de direitos. |
| IA-R18 | IA e vendas | Prospector/Comercial ajudam na qualificação e acompanhamento de oportunidades, mas não confirmam receita, proposta aceita ou pagamento por texto. |
| IA-R19 | Atendimento híbrido | Agente pode distribuir/encaminhar e criar tarefa autorizada; posse humana, SLA, fila FIFO e confirmação do dono de atendimento continuam. |
| IA-R20 | Autonomia limitada por ferramenta | Diagnosticar/recomendar/agir dentro da política; atos críticos ou sensíveis exigem decisão da Central de Aprovações quando cabível, sem conceder a própria alçada. |
| IA-R21 | Modelo e contingência | Gerenciador de Modelos mantém modelos homologados por tarefa, versões, capacidade, segurança, custo e fallback sem ampliar privilégios. |
| IA-R22 | Separação da IA técnica Imagine | Agentes comerciais dos clientes possuem contabilidade/dados isolados; agentes técnicos IN-130..168 têm identidades, custos e conhecimento próprios. |
| IA-R23 | Conhecimento técnico não é conhecimento do cliente | Playbooks de infraestrutura IN-163/164/165 e logs técnicos IN-162 não devem ser copiados para a Base de Conhecimento comercial/cliente. |
| IA-R24 | Prompts/instruções versionados | Versões de configuração, ferramentas e políticas de autorização são independentes; texto recuperado de site/cliente não altera privilégio. |
| IA-R25 | Supervisão humana e auditoria | Relatórios de qualidade, falhas, alucinação/resultado não conclusivo, contenção, revisão humana e trilha de ações, não aprovação autônoma da IA. |
| IA-R26 | Conselhos não são ações oficiais | Pontuação/sugestão/resumo são evidência consultiva; alteração, envio, agenda, venda, cancelamento ou compensação dependem do módulo proprietário. |
| IA-R27 | Avaliação de qualidade | Analisar acurácia, atendimento, custo, satisfação, intervenção e falhas por empresa e tarefa respeitando privacidade e amostragem autorizada. |
| IA-R28 | Bloqueio e recuperação | Suspender agente/modelo/ferramenta comprometida sem apagar históricos; retorno somente com validação e autoridade competente. |

## 3. Quatro contas e controles que não devem ser misturados

| Controle | Escopo/autoridade | Restrição |
|---|---|---|
| **Plano/benefício do tenant** | Assinatura CRM e concessão administrada pela Imagine, conforme módulo FIN/CON | Benefício de plano não emite créditos extras nem elimina controles de segurança |
| **Cotas e crédito IA de cliente** | Empresa, agente, operação, franquia e pacote contratado/validado | Não compartilhar entre tenants, não debitar duas vezes, sem recarga automática não consentida |
| **Custos internos dos agentes técnicos Imagine** | Centro de custo interno, modelos/serviços de observabilidade e infraestrutura IN-158 | **Não** consumir cota de IA de empresas clientes |
| **Ferramentas/permissões e alçadas** | Identidade e Segurança, proprietários de negócio, Central de Aprovações | Crédito disponível não é autorização de enviar, editar, pagar, conceder plano ou acessar dados |

**Gratuidade administrativa:** mesmo quando Administrador Geral Imagine concede **Profissional/Empresarial gratuitamente**, o benefício confirmado é a **mensalidade base e os recursos incluídos no plano**. Pacotes de IA, recargas e consumo externo excepcional seguem **GRAT-P05** até decisão expressa.

## 4. Ciclos conceituais para revisão — não contratos executáveis

1. **Cliente habilita agente elegível** conforme plano, conta, finalidade, documentação de privacidade e controle de ferramentas.
2. **Autorizador valida** tenant, recurso, ação, canal, versão, papel, alçadas e orçamento/credito em camada independente da saída do modelo.
3. **Orquestração seleciona modelo homologado**, rastreando versão, custo/latência e fallback permitido. **Troca de modelo nunca troca identidade**.
4. **Agente recupera conhecimento minimizado** com proveniência, isolamento, validade, finalidade e filtros por permissão; texto externo é dado não confiável.
5. **Execução tipada** comunica resultado consultivo ou pedido de ação ao módulo proprietário, sem considerar job/HTTP como fato de negócio.
6. **Medir consumo e registrar resultado idempotente**: saldo, reserva, débito/estorno, sucesso/erro/inconclusivo a definir por contrato; evitar dupla tarifação.
7. **Guardar resumo/histórico quando elegível**, versão e autor/entidade, com acesso restrito, sem confundir com log técnico ou conversa externa.
8. **Supervisão/contingência** avalia anomalia/custo/qualidade e suspende dentro de política aprovada, com tratamento de recuperação e auditoria.

## 5. Riscos de interpretação e diferenças a conciliar

| ID | Ponto de conferência |
|---|---|
| IA-D01 | Documento antigo informa créditos comprados até consumo; histórico posterior prevê expiração 90 dias: não adotar qualquer interpretação sem cotejo completo. |
| IA-D02 | Créditos mensais e adicionais têm validade/prioridade diferentes; não usar saldo antigo como prova de dinheiro a receber. |
| IA-D03 | Concessão de plano Empresarial gratuita não significa IA ilimitada ou créditos extras pagos gratuitos. |
| IA-D04 | Conta do cliente e infraestrutura Imagine nunca compartilham créditos nem custos internos de operação técnica. |
| IA-D05 | Alterar modelo/versão de prompt não altera permissão, escopo ou aprovação humana. |
| IA-D06 | Histórico/resumo de IA não substitui auditoria geral nem mensagem real do cliente. |
| IA-D07 | Conteúdo externo ingerido é dado não confiável, sem força para criar instrução administrativa. |
| IA-D08 | IA e automação comercial/fluxo podem coexistir, mas não duplicar envio ao cliente sob posse humana. |
| IA-D09 | Recomendação de fechamento de negócio não constitui confirmação em VEN/FIN/CON. |
| IA-D10 | Sugestão de mesclagem de cadastro não autoriza merge automático ou alteração de consentimentos. |
| IA-D11 | O prazo de curadoria do conhecimento técnico IN-163 não deve reger memória comercial por analogia. |
| IA-D12 | Sem backend determinístico de créditos a resposta do modelo não decide cobrança, saldo ou limites de plano. |

## 6. Pendências de decisão para fechamento (sem aprovação implícita)

| ID | Especificação ou decisão ainda necessária | Estado |
|---|---|---|
| IA-P01 | Catálogo de agentes comerciais inicialmente ofertados (8 modelos de referência) e direitos por módulo/plano | Pendente de cotejo e aceite |
| IA-P02 | Cotas gratuitas por plano/trial, cálculo por operação e crédito por empresa | Pendente de cotejo e aceite |
| IA-P03 | Resolver divergência sobre validade de **90 dias** versus “até consumo” dos pacotes comprados com fonte original | Pendente de cotejo e aceite |
| IA-P04 | Preços, pacotes, recarga automática autorizada, impostos, estorno e limites financeiros | Pendente de cotejo e aceite |
| IA-P05 | Matriz de modelos homologados por agente/tarefa, provedores, fallback e teto | Pendente de cotejo e aceite |
| IA-P06 | Esquema de operação tipada, deduplicação do consumo, inconclusivos, falhas e estornos de crédito | Pendente de cotejo e aceite |
| IA-P07 | Níveis de autonomia e autoridade para criar/editar dados, enviar mensagens e agir em nome da empresa | Pendente de cotejo e aceite |
| IA-P08 | Base de Conhecimento por empresa/nível, fontes, consentimento e atualização/revisão periódica | Pendente de cotejo e aceite |
| IA-P09 | Memória e resumos versionados, direitos de campo, classificação e prazo de retenção | Pendente de cotejo e aceite |
| IA-P10 | Treinamento/avaliação, medição de qualidade, métricas e alertas de uso anormal | Pendente de cotejo e aceite |
| IA-P11 | Contingência segura e monitor independente do modelo, sem interromper funções não IA | Pendente de cotejo e aceite |
| IA-P12 | Fronteiras IA comercial vs IN-163/164/165/166/167/168 técnicos e orçamento interno Imagine | Pendente de cotejo e aceite |
| IA-P13 | Compatibilidade com o benefício administrativo de qualquer plano; extras de IA não incluídos automaticamente | Pendente de cotejo e aceite |
| IA-P14 | Rastrear aprovações integrais da biblioteca de agentes, crédito 90 dias, níveis, modelos e resumos | Pendente de cotejo e aceite |
| IA-P15 | Separar treinamento/memória do cliente, LGPD, fonte original, opt-out e portabilidade de dados | Pendente de cotejo e aceite |

**Os 15 itens IA-P01..15 são perguntas de fechamento**, não a aprovação de novos fornecedores, pacote, preço ou prazo. Os regimes de IN-163/164/165/166 para agentes **técnicos** não passam automaticamente para a IA de atendimento do cliente.

## 7. Casos futuros de homologação — NENHUM EXECUTADO

| Caso | Restrição a verificar futuramente |
|---|---|
| IA-T01 | Agente tenta acessar registro de outra organização: bloquear e auditar |
| IA-T02 | Modelo alternativo é acionado após falha: manter escopo e créditos corretamente contabilizados |
| IA-T03 | Dois retries da mesma operação faturável: não debitar duas vezes |
| IA-T04 | Pacote adquirido perto da validade discutida: depender da decisão exata, não inventar expiração |
| IA-T05 | Crédito da empresa termina no meio de operação: resultado/política tratados sem cobrança automática |
| IA-T06 | Administrador Imagine concede plano gratuito: manter somente franquias IA incluídas, sem crédito extra implícito |
| IA-T07 | Humano assume WhatsApp e IA tem resposta na fila: impedir envio ao cliente |
| IA-T08 | IA recebe instrução maliciosa em site/documento e tenta elevar privilégio: negar |
| IA-T09 | Base de Conhecimento revoga fonte sensível: impedir uso futuro indevido e controlar derivados |
| IA-T10 | Resumo gera conteúdo sensível: proteger acesso, histórico, versão e auditoria |
| IA-T11 | IA recomenda mesclar pessoas com mesmo telefone: não efetivar alteração sem fluxo autorizado |
| IA-T12 | Agente comercial tenta obter playbook técnico de infraestrutura: negar |
| IA-T13 | Modelo e ferramentas não homologados são selecionados por IA: bloquear |
| IA-T14 | Alerta de custo interno aciona contenção sem consumir crédito de cliente |
| IA-T15 | Agente IA tenta cancelar serviço financeiro por texto: exigir dono FIN/CON e alçada |
| IA-T16 | Credencial revogada enquanto comando está na fila: negar nova ação e reconciliar efeito anterior |

## 8. Próxima aprovação e continuidade

**Proposta:** aprovar CRM-REV-08 **como base documental de reconciliação**, reconhecendo 28 itens, 12 divergências, 15 pendências e 16 cenários futuros, sem homologar modelos, preços, prazos de créditos, permissões, perfis, dados de treinamento, APIs, custos ou programação.

**Depois:** CRM-REV-09 — Infraestrutura, Segurança, Auditoria e Operações Centrais; manter foco no fechamento transversal P0. A referência à gratuidade por plano já é requisito funcional aprovado, não depende do aceite desta REV-08.

**Situação global:** CRM-REV-01..07 bases aprovadas; CRM-REV-08 **minuta não aprovada**. CORE-P0, INT-P0, IN-168 e G01–G06 continuam abertos; nenhuma implementação, implantação ou teste executado.
