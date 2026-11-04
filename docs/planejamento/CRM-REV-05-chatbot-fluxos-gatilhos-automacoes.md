# CRM-REV-05 — Chatbot, Fluxos, Gatilhos, Condições e Automações

**Data:** 2026-11-03. **Estado atualizado:** **APROVADA COMO BASE DOCUMENTAL**, conforme [CRM-DEC de aceite](CRM-DEC-aprovacao-REV-05-base.md). Mantidas 14 pendências AUT-P01..14, 12 riscos AUT-D01..12, parâmetros, perfis e quotas finais a revisar. [CRM-REV-06](CRM-REV-06-vendas-funis-propostas-catalogo.md) está **em minuta, ainda não aprovada**.
**Escopo:** CRM-11, interfaces ATD/VEN/AGD/IA, motor central, canais, direitos por plano. A especificação **não** cria nova execução n8n nem substitui serviços oficiais existentes.
**Restrições:** somente documentação no GitHub; **sem programação, teste executado, simulação real, automação instalada, canal ativado, cobrança ou mudança em produção**.

## 1. Fontes conferidas e limites de proveniência

| Ref | Fonte | Regras relacionadas |
|---|---|---|
| LEG-04 | [04 — Empresas e usuários](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md) | Automações comerciais, limitação por plano, inatividade, distribuição, chatbot, editor, versões, biblioteca e integração Agenda |
| LEG-10 | [10 — Biblioteca de Automações](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/10-biblioteca-automacoes-e-criterios-de-planos.md) | Distinção sistema/comercial/canais, limites de referência, IN-005/006/010/013, inventário ainda candidato |
| LEG-01 | [01 — Central WhatsApp](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/01-visao-geral-e-arquitetura.md) | Posse de conversa, fila FIFO e bloqueio de envios automáticos em atendimento humano |
| REV04 | [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md) | Base documental aceita; contrato da Central de Atendimento e suas pendências |
| REV02 | [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) | Planos, quotas e condições de cobranças ainda em revisão final |
| REV03 | [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md) | Leads, campos e origem de submissões; modelo de dados proprietário |
| INT | [INT-R01](integracoes/INT-R01-governanca-contratos-homologacao.md) | Contratos externos, comando tipado, confirmação oficial e reconciliação |
| IN167 | [IN-167](IN-167-consolidacao-arquitetura-agentes-tecnicos.md) | Agentes técnicos e autonomia separada de agentes IA comerciais |

A [Biblioteca histórica LEG-10](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/10-biblioteca-automacoes-e-criterios-de-planos.md) registra **IN-005 como referência atual de 3 slots comerciais ativos no Básico** e **IN-006 como diretriz de diferenciação**. O próprio documento qualifica a lista de templates como **proposta, não catálogo final homologado**, e determina revisar as quantidades **após o inventário completo**. Não tratar a simples listagem como aprovação individual de templates Premium.

## 2. Separação estrutural dos três tipos de operação

| Camada | Exemplos | Consome slot comercial? | Titularidade e limite |
|---|---|---|---|
| **Sistema obrigatório** | Receber/confirmar lead, validar campo, verificar acesso, confirmar agenda, impedir dupla reserva, manter auditoria, alertas internos essenciais de agenda | **Não** | Módulo proprietário; continua operando com zero automações comerciais ativas |
| **Automação comercial configurável** | Tarefa após novo lead, acompanhamento por inatividade, notificações internas condicionais, distribuição facultativa e modelos elegíveis | **Sim, quando ativa**, segundo plano | Biblioteca e motor autorizado; gatilhos/ações tipados, direitos e quota da organização |
| **Canal, chatbot, IA ou integração especializada** | Fluxos WhatsApp, envio externo, agente IA, webhook, calendário externo, relatórios programados | **Não por mera equivalência** com automação comercial; contratos/quotas próprios | Entitlement e custo por canal, recurso e provedor, além de autorização contextual |

**No Básico**, avisos internos essenciais da agenda **não** ocupam slot comercial. A biblioteca Básico é orientada a **modelos prontos habilitados**, sem presumir editor de lógica arbitrária; slots vazios não concedem API oficial WhatsApp, agente IA, e-mail externo, agenda pública ou integrações Premium.

## 3. Catálogo de 28 regras cotejadas — sem criar novas funcionalidades

| ID | Tema | Regra funcional e limite | Fonte |
|---|---|---|---|
| AUT-R01 | Três camadas distintas | Operações obrigatórias do sistema não ocupam slots comerciais; automações comerciais configuráveis contam somente quando ativas; chatbot WhatsApp, IA, conectores e canais podem ter quotas/contratações próprias. | LEG-10; CRM-REV04 |
| AUT-R02 | Automação comercial sem código | Cada empresa configura gatilhos, condições e ações permitidos; histórico, resultado, falhas e possibilidade de ativar, pausar e desativar. | LEG-04 |
| AUT-R03 | Gatilhos comerciais | Novo lead, mudança de etapa, tarefa vencida e proposta aceita/recusada, além de gatilhos temporais aprovados nos módulos. | LEG-04 |
| AUT-R04 | Condições comerciais | Responsável, estágio/etapa, valor, campos personalizados, tempo/inatividade e regras condicionais por evento. | LEG-04 |
| AUT-R05 | Ações comerciais | Criar tarefa, atribuir responsável, enviar notificação interna ou alterar campo quando autorizado; ação externa depende de canal e contratação. | LEG-04; LEG-10 |
| AUT-R06 | Tempo e inatividade | Contagem configurável em horas/dias para leads, oportunidades e propostas; nova interação elegível reinicia a contagem; evitar alertas repetidos. | LEG-04 |
| AUT-R07 | Distribuição/redistribuição comercial | Round Robin, menor carga, equipe/origem, responsável fixo ou manual; redistribuição por prazo somente para lead sem atendimento válido e com tentativas limitadas. | LEG-04 |
| AUT-R08 | Slots comerciais por organização | Referências de planejamento: Básico 3 modelos prontos ativos, Essencial 5, Profissional 20, Empresarial 50; trial 2. Números sujeitos a revisão final do catálogo. | LEG-10; LEG-04 |
| AUT-R09 | Rascunhos e downgrade | Rascunhos não contam slots; downgrade preserva definições/histórico, pausa excedentes e administrador escolhe as automações que permanecem elegíveis. | LEG-10; LEG-04 |
| AUT-R10 | Básico diferenciado | Biblioteca simples de modelos permitidos, sem editor genérico de lógica arbitrária no Básico; não liberar funções pagas via template. | LEG-10 |
| AUT-R11 | Avisos internos da agenda Básico | Confirmação/alteração de agenda e lembretes operacionais internos são ações necessárias do sistema e **não** consomem slot de automação comercial. | LEG-10; IN-013 |
| AUT-R12 | Canais externos não implícitos | E-mail externo, API oficial WhatsApp, chatbot, integrações externas, IA e relatórios programados dependem de entitlements, políticas e custos específicos. | LEG-10; CRM-REV02 |
| AUT-R13 | Biblioteca como direção de produto | Catálogo progressivo de modelos, com valor no Básico e modelos avançados reservados a planos elegíveis; lista de candidatos LEG-10 não é catálogo final aprovado. | LEG-10 |
| AUT-R14 | Identidade e versão de template | IDs/versionamento e critérios de elegibilidade por modelo devem ser definidos no catálogo, com dono, dependências e efeito descrito; modelo candidato não é automaticamente publicável. | LEG-10 |
| AUT-R15 | Bloqueio de limite sem compra automática | Ao exceder quota ou plano, informar quantidade/recursos e permitir pausar outra automação ou conhecer upgrade, sem assinar/cobrar automaticamente. | LEG-10 |
| AUT-R16 | Chatbot visual WhatsApp | Fluxos com mensagens, perguntas, menus, condições, espera, ações CRM, transferência e encerramento; podem funcionar sem IA conversacional. | LEG-04; CRM-REV04 |
| AUT-R17 | Gatilhos do chatbot | Primeira mensagem, palavra-chave, fora do expediente, origem de formulário/campanha, conversa reaberta ou início manual, com prioridade configurável. | LEG-04 |
| AUT-R18 | Posse humana prevalece | Chatbot, IA e automações de mensagens não enviam ao cliente em conversa assumida por humano; validar posse na tentativa efetiva e impedir envio acumulado na retomada. | CRM-REV04; LEG-01 |
| AUT-R19 | Chatbot em versões imutáveis | Rascunhos editáveis não alteram versão publicada; conversas iniciadas continuam na versão original, novas usam versão nova aprovada. | LEG-04 |
| AUT-R20 | Simulador sintético futuro | Editor de chatbot deve permitir simular menus/ações sem WhatsApp real, criação de leads ou alteração em dados reais; bloquear publicação de fluxo com erro crítico. | LEG-04 |
| AUT-R21 | Biblioteca de chatbots | Modelos de atendimento, qualificação, orçamento, suporte, fora do expediente e satisfação são copiáveis e personalizáveis; atualizar modelo central não sobrescreve cópia do cliente. | LEG-04 |
| AUT-R22 | Importação/exportação chatbot | JSON de definição sem credenciais/dados pessoais; importação com mapeamento de equipes/campos/tags, entra como rascunho, exige validação/publicação autorizada. | LEG-04 |
| AUT-R23 | Cotas específicas de chatbot | Fluxos chatbot ativos: Essencial 3, Profissional 10, Empresarial 30 como decisões históricas; rascunhos livres e downgrade pausa excedentes; quota independente das comerciais. | LEG-04 |
| AUT-R24 | Agendamento a partir do chatbot | Solicitação de horário, consulta e criação autorizada por Agenda, com validação de disponibilidade na confirmação, prevenção de reserva duplicada e mensagem somente pelo canal permitido. | LEG-04; CRM-REV04 |
| AUT-R25 | Falhas/reentrada | Respostas inválidas e ausência de resposta levam a repetição/aviso/transferência conforme regras; evitar loops, múltiplos disparos e recorrência infinita. | LEG-04 |
| AUT-R26 | Telemetria e auditoria | Contabilizar entradas, abandono, conclusão, transferências, falhas, tempo e conversões por tenant; origem oficial do negócio permanece no módulo proprietário. | LEG-04; INT-R01 |
| AUT-R27 | Orquestração sem superpoder | Motor de Eventos/Fluxos, Filas, Datas e Aprovações conserva competência própria; n8n e INT executam só comandos tipados, escopados e versionados, nunca sem validação de tenant. | LEG-10; INT-R01; CRM-REV01 |
| AUT-R28 | Alçadas e IA | Agente IA recomenda/atua somente conforme escopo da empresa, credenciais e ferramentas homologadas; não cria regra de aprovação nem concede plano, permissão, cobrança ou compensação. | CRM-REV01; IN-167 |

## 4. Contagem e direitos de automação — valores de referência de planejamento

| Plano | Automações comerciais ativas | Chatbot WhatsApp ativo | Observação |
|---|---:|---:|---|
| **Básico** | **3**, sujeito à revisão de catálogo | **Não presumir direito/quantidade** | Modelos habilitados pela biblioteca; avisos internos não ocupam slots |
| **Essencial** | **5** | **3** | Direitos de canal WhatsApp dependem de contratação elegível |
| **Profissional** | **20** | **10** | Slots distintos, com limites/canais separados |
| **Empresarial** | **50** | **30** | Dependem de permissões, canais, consumo e custos |
| **Trial** | **2** | Não presumir direito | Trial possui seus limites e elegibilidade próprios |

**Todos os números devem ser revisados e ratificados conjuntamente ao fim do planejamento**, sem alteração agora. “Automação ativa” é **por tenant/organização**, não por execução, lead, formulário, funcionário ou site. Rascunhos não consomem slot. O downgrade preserva dados e definições, pausando o excedente conforme seleção autorizada.

## 5. Modelos da Biblioteca: proposta histórica, não catálogo final

| Grupo de modelos históricos em LEG-10 | Status de interpretação |
|---|---|
| Novo lead, sem resposta, mudança de estágio, tarefa vencida | Capacidade funcional existe nas regras comerciais; template e plano ainda requerem revisão por modelo |
| Distribuição/redistribuição por equipe, rodízio ou menor carga | Capability já documentada; risco, requisito de equipe e oferta por plano não se presumem |
| Acompanhamento após agenda/no-show/serviço concluído | Separar **aviso interno de sistema** de **automação opcional de pós-atendimento** |
| Proposta aceita/recusada, relatório agendado e regras compostas | Dependências VEN/relatórios e plano exigem classificação final |
| WhatsApp, chatbot, IA e conectores externos | Camadas especializadas com assinatura, escopo, custo e autorização próprios; não contabilizar como automação comercial 1:1 |

Metadados sugeridos pelo histórico (template_id/versão, trigger, condição, ação, entitlements, dependências, classe de risco, idempotência, custo e critério de teste) permanecem **modelo de especificação**, não schema físico validado.

## 6. Distinção de estados e fontes oficiais

- **Modelo da biblioteca:** candidato, revisado, homologado/publicado, depreciado; o texto histórico não aprova os enums finais.
- **Instância da empresa:** rascunho, elegível, ativa, pausada, bloqueada por entitlement, desativada; respeita plano, versão e direitos.
- **Execução:** evento reconhecido, condição avaliada, ação autorizada/negada, pendente, sucesso **confirmado pelo proprietário**, parcial, inconclusivo ou falha. **Não declarar efeito comercial por job n8n/HTTP/Webhook.**
- **Chatbot:** versão publicada e versão fixada na conversa ativa; erro, tentativa, espera, transferência e pausa por posse humana são dimensões distintas.
- **Limites e autorização:** horário/inatividade/atualização de permissões precisam ser revalidados antes de ação externa/ação com efeito, inclusive em filas atrasadas.
- **Auditoria Geral:** preserva proveniência e correlação de modelo, instância, versão, ator, evento, comando, execução e resultado. Logs técnicos não são a fonte final do negócio.

As enumerações são **descrições para revisão**, não contrato executável nem decisão de arquitetura de novo motor.

## 7. Divergências e riscos a conciliar

| ID | Ponto de conferência |
|---|---|
| AUT-D01 | Biblioteca LEG-10 enumera modelos candidatos, mas disponibilidade e publicação de cada template não estão homologadas. |
| AUT-D02 | Básico com referência de 3 slots comerciais está sujeito à revisão final, sem autorizar ampliação/diminuição agora. |
| AUT-D03 | Slots ativos comerciais são distintos de limite de fluxos do chatbot e quantidade de execuções. |
| AUT-D04 | Operação de sistema obrigatória (lead, agenda, auditoria) não consome slot; notificação externa facultativa pode consumir quota/custo próprio. |
| AUT-D05 | Editor visual para chatbot aprovado não equivale automaticamente a editor livre de automação comercial no Básico. |
| AUT-D06 | Pausar mensagem automatizada ao cliente na posse humana não suspende tarefas internas autorizadas. |
| AUT-D07 | Simulação de chatbot é capacidade para o produto futuro; não é autorização para executar testes agora. |
| AUT-D08 | Versão de fluxo publicada e versão de modelo de biblioteca são conceitos distintos, com migração/reversão ainda a definir. |
| AUT-D09 | IA 24/7 e chatbot por regras são serviços distintos; pacotes/limites de IA não são automaticamente incluídos na biblioteca. |
| AUT-D10 | Ações externas podem ter custos e políticas variáveis; ativar slot não autoriza WhatsApp, webhook, e-mail externo ou agenda pública. |
| AUT-D11 | Distribuição comercial de lead não deve substituir a fila humana FIFO de conversas WhatsApp. |
| AUT-D12 | Inatividade do lead/oportunidade, do chatbot e de conversa ATD têm estados e prazos próprios; não unificar 24h/12h/72h sem cotejo. |

## 8. Pendências abertas para fechamento (não novas capacidades)

| ID | Evidência ou decisão a completar | Status |
|---|---|---|
| AUT-P01 | Inventário de gatilhos oficiais por módulo, com evento real, proprietário e causalidade | Pendente de especificação/aceite |
| AUT-P02 | Contrato de condições, tipos de campo, comparação, proteção de dados e escopo | Pendente de especificação/aceite |
| AUT-P03 | Lista de ações tipadas, permissões do executor e confirmação oficial do dono do efeito | Pendente de especificação/aceite |
| AUT-P04 | Prevenção de ciclos, recursão, concorrência, duplicidade e reprocessamento seguro | Pendente de especificação/aceite |
| AUT-P05 | Política de publicação/versionamento de modelos, fluxos e instâncias ativas | Pendente de especificação/aceite |
| AUT-P06 | Catálogo final de templates e classificação Básico/Essencial/Profissional/Empresarial | Pendente de especificação/aceite |
| AUT-P07 | Revisão final dos slots 3/5/20/50, 2 trial, e 3/10/30 de chatbot, com dependências/custos | Pendente de especificação/aceite |
| AUT-P08 | Esquema de quota e entitlements separados por automação, canal, chatbot, agente IA, API e plano | Pendente de especificação/aceite |
| AUT-P09 | Compatibilidade em importação JSON de chatbot, reassociação de campos/tags/equipe sem segredos | Pendente de especificação/aceite |
| AUT-P10 | Motor Central versus INT-09/n8n, Filas, Prazos, Aprovações, Webhooks e recuperação | Pendente de especificação/aceite |
| AUT-P11 | Direitos de criação/publicação/pausa/desativação, escopo tenant/equipe, auditoria e suporte | Pendente de especificação/aceite |
| AUT-P12 | Estados de inatividade/encerramento/reabertura próprios de ATD, agenda e VEN, sem relógio global | Pendente de especificação/aceite |
| AUT-P13 | Limites de custo, templates externos e revisão do provedor WhatsApp/Meta no lançamento | Pendente de especificação/aceite |
| AUT-P14 | Cotejo do inventário LEG-10 com aprovações originais e catálogos funcionais ATD/VEN/AGD/IA | Pendente de especificação/aceite |

## 9. Casos de aceitação futuros — nenhum teste executado

| Caso | Comportamento esperado na homologação futura |
|---|---|
| AUT-T01 | Recepção de lead operando mesmo com zero automações comerciais ativas |
| AUT-T02 | Aviso interno de agenda Básico não consome slot comercial |
| AUT-T03 | Ativação de quarto modelo Básico com referência de 3 slots é negada até alteração expressa da política |
| AUT-T04 | Modelo pago tenta ser acionado por URL/API em tenant Básico: autorização negada |
| AUT-T05 | Downgrade com mais automações ativas preserva definições e pausa excedentes sem excluir histórico |
| AUT-T06 | Evento repetido tenta criar tarefas idênticas: deduplicar conforme identidade lógica |
| AUT-T07 | Fluxo dispara evento que o inicia novamente: proteger contra loop e escalonar ocorrência |
| AUT-T08 | Humano assume conversa e bot tem mensagem pendente: não enviar ao cliente |
| AUT-T09 | Publicação da v2 não troca v1 de conversa em andamento |
| AUT-T10 | Importação JSON com identificador de equipe inexistente exige remapeamento e permanece em rascunho |
| AUT-T11 | Simulador de chatbot tenta agendar/cadastrar lead real: só dados sintéticos e sem efeitos |
| AUT-T12 | Chatbot tenta confirmar reserva já ocupada: Agenda rejeita, sem dupla reserva |
| AUT-T13 | Ativação de automação externa sem consentimento, plano ou canal elegível: bloquear sem cobrar |
| AUT-T14 | Dois tenants com IDs semelhantes não compartilham gatilhos, configurações, segredos ou execuções |

**Nenhum dos 14 cenários foi executado.** Eles são critérios documentais candidatos, não nova automação instalada nem plano HT executado.

## 10. Situação e próxima decisão

A REV-05 coteja **28 regras**, separa as três camadas operacionais e registra **12 diferenças**, **14 pendências** e **14 cenários futuros**. Foi **aprovada como base documental de revisão**, mas os contratos, quotas finais, políticas e pendências continuam sem homologação executável. O catálogo definitivo de modelos, direitos e limites por plano depende da revisão conjunta após inventariar os módulos comerciais.

**Aceite registrado:** CRM-REV-05 foi **aprovada como base documental**, conforme [CRM-DEC](CRM-DEC-aprovacao-REV-05-base.md), preservando a diferenciação entre slots comerciais, sistema interno, chatbot/canais/IA, e mantendo AUT-P01..14 abertos. Não aprovar agora novos preços, schemas, quotas finais, canal Meta/BSP, automações executáveis ou datas de programação.

**Etapa posterior preparada:** [CRM-REV-06 — Vendas, Funis, Propostas e Catálogo](CRM-REV-06-vendas-funis-propostas-catalogo.md), em **minuta para revisão**, para completar o inventário de gatilhos comerciais e depois voltar ao catálogo final de modelos. **CORE-P0, INT-P0, IN-168 e G01–G06 continuam abertos; não iniciar desenvolvimento.**

## Decisão posterior — Motor de automações próprio e n8n interno (04/11/2026)

**Diretriz de arquitetura aprovada:** [CRM-DEC — Motor próprio do CRM e uso interno do n8n](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md). As automações de clientes configuradas nos construtores de Fluxos, Gatilhos, Condições e Ações serão **definidas e executadas no motor próprio do CRM**, utilizando os motores já previstos de Eventos, Filas, Datas e Aprovações. **Não gerar ou editar workflows n8n em nome de clientes pela Community Edition** como forma de implementar o construtor. **Não duplicar os serviços centrais** aprovados.

O n8n continua ferramenta **de orquestração de processos internos Imagine** permitidos pela licença aplicável, sem ser dependência única dos fluxos essenciais; o enquadramento efetivo da licença precisa ser **validado formalmente antes da implantação**. Operações externas seguem INT com comandos tipados, identidades escopadas, auditoria, idempotência e confirmação no serviço proprietário. Os requisitos AUT-P01..14/AUT-D01..12, preços, quotas, direitos por plano e homologações continuam pendentes; **nenhuma programação ou contratação autorizada**.


## Complemento de execução segura — AUT-P0 v0.1 (04/11/2026)

A [CRM-AUT-P0 v0.1 — Execução segura do motor próprio](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md) foi **aceita somente como base documental**, conforme [CRM-DEC de aceite](CRM-DEC-aceite-base-AUT-P0-execucao-segura-2026-11-04.md). O escopo aceito é: reutilizar os motores centrais, separar operações obrigatórias/comerciais, proteger isolamento entre empresas, deduplicação, confirmação oficial de efeitos e recuperação auditável. A hipótese de workers do CRM E1B evita uma VPS exclusiva **apenas se os testes de capacidade e segurança homologarem**. Detalhes de contratos, schemas, quotas, prazos, tecnologia de fila, homologação e **AUT-P01..14 continuam abertos**, sem implementação ou compra autorizada.


## Catálogo AUT-CAT01 — Proposta de gatilhos, condições e ações por módulo (04/11/2026)

A [minuta CRM-AUT-CAT01 v0.1](CRM-AUT-CAT01-catalogo-gatilhos-condicoes-acoes-por-modulo-v0.1.md) foi preparada **para revisão**, após o responsável autorizar o próximo passo do planejamento. Inventaria **25 gatilhos candidatos**, **15 famílias de condições** e **22 ações (incluindo atos protegidos proibidos no editor comercial)**, com competência dos módulos DAT, VEN, ATD, AGD, IA, FIN/CON, COM/INT e IAM, além de critérios de permissões, quotas e falhas. **Não foi aprovada como catálogo definitivo**; IDs CAT não são schemas/contratos executáveis; **AUT-P01..14 permanecem abertos**. Respeita motor próprio aprovado e decisão de não usar n8n como backend das automações de clientes; sem programação, implementação nem despesas.


## Fechamento parcial AUT-CAT01 e proposta de direitos AUT-MAT01 (04/11/2026)

A [AUT-CAT01 v0.1](CRM-AUT-CAT01-catalogo-gatilhos-condicoes-acoes-por-modulo-v0.1.md) foi **aceita como inventário de referência funcional**, conforme [CRM-DEC](CRM-DEC-aceite-referencia-CAT01-2026-11-04.md); isso não homologa itens nem implica liberação por plano. A [CRM-AUT-MAT01 v0.1](CRM-AUT-MAT01-matriz-elegibilidade-automacoes-por-plano-v0.1.md) apresenta **proposta para revisão** com critérios de Básico (somente modelos autorizados), Essencial, Profissional, Empresarial e Trial; slots 3/5/20/50 e trial 2 continuam **referências a ratificar**. Chatbot 3/10/30 e canais são direitos separados; ações protegidas não entram no editor. **AUT-P01..14 continuam abertos** e não há programação, contratação ou publicação de workflows.


## Aceite limitado da matriz de elegibilidade AUT-MAT01 (04/11/2026)

[CRM-AUT-MAT01 v0.1](CRM-AUT-MAT01-matriz-elegibilidade-automacoes-por-plano-v0.1.md) e [registro de aceite](CRM-DEC-aceite-base-AUT-MAT01-2026-11-04.md): **base documental aceita para comparação dos planos**, mantendo Básico com modelos homologados, separação SO/AC/CE e bloqueio das ações protegidas no editor. **Referências de slots 3/5/20/50 e 2 trial; chatbot 3/10/30 permanecem sujeitas à ratificação comercial final**; não foi aprovado catálogo de modelos ou acesso a canais/IA/agenda pública adicionais. AUT-P01..14 e AUT-MAT-P01..P10 abertos; sem código, contratação ou implantação.


## Biblioteca e diferenciação por plano — AUT-LIB01 em proposta (04/11/2026)

A [CRM-AUT-LIB01 v0.1](CRM-AUT-LIB01-biblioteca-modelos-basico-editores-por-plano-v0.1.md) foi elaborada para **revisão**, sem aceite nem homologação. O planejamento distingue **Básico com modelos prontos parametrizados** dos editores comerciais tipados de planos pagos, cuja diferenciação de complexidade ainda depende de decisão. **O editor visual de chatbot WhatsApp já previsto é distinto do editor de automações comerciais**, com quotas/canais separados. O modelo histórico **OPP-01 não será presumido no Básico** sem confirmar direito ao VEN; avisos internos obrigatórios da Agenda seguem SO fora de slots. Preservar AUT-P01..14 e LIB-P01..P12; sem programação, contratação ou automações ativadas.


## AUT-LIB01 aceita como base e AUT-LIB02 em conferência individual (04/11/2026)

[CRM-DEC de aceite LIB01](CRM-DEC-aceite-base-AUT-LIB01-2026-11-04.md) e [CRM-AUT-LIB02 v0.1](CRM-AUT-LIB02-revisao-individual-modelos-basico-v0.1.md): **a estrutura da biblioteca foi aceita como base de planejamento**, mas nenhum modelo pronto específico, efeito A01 ou nível de editor pago ganhou aprovação automática. A revisão individual evidencia que **notas/histórico manual** do Básico não comprovam direito genérico a **tarefas comerciais automatizadas**; priorizar LEA-01/02 como candidatos de aviso interno **sob autorização e contrato específicos**, sem duplicar notificações da Agenda já incluídas como SO. Preservar AUT-P01..14, LIB-P01..P12, LIB02-P01..P08 e demais gates P0; sem programação ou contratos.


## Complemento LEA-01/02 — critérios de contato e inatividade (04/11/2026)

[CRM-AUT-LIB04 v0.1](CRM-AUT-LIB04-criterios-interacao-prazos-antiduplicidade-LEA01-LEA02-v0.1.md): **proposta para revisão**, contendo distinção entre "sem atendimento pela empresa" e "sem resposta do cliente", matriz de interação válida, prazos candidatos (4/12/24/48/72h, default sugerido 24h), idempotência de avisos e 14 cenários futuros de teste. **Não aprova** esses tempos, gatilhos oficiais ou publicação dos modelos. A [TAR-A](CRM-DEC-escolha-TAR-A-avisos-internos-LEA01-LEA02-2026-11-04.md) permanece aprovada para **avisos internos sem tarefas automáticas**. Preservar AUT-P01..P14 e LIB04-P01..P09, com sem implementação/custo.
