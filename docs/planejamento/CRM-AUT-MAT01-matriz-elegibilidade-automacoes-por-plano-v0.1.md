# CRM-AUT-MAT01 — Matriz de elegibilidade de automações por plano (v0.1)

**Data:** 04/11/2026.  
**Status atualizado:** **ACEITA COMO BASE DOCUMENTAL DE REVISÃO** em 04/11/2026 ([registro de aceite](CRM-DEC-aceite-base-AUT-MAT01-2026-11-04.md)); **as elegibilidades candidatas, os números finais e os contratos por plano não foram homologados nem aprovados individualmente**.  
**Fato já aprovado:** [CRM-AUT-CAT01 aceito como referência funcional](CRM-DEC-aceite-referencia-CAT01-2026-11-04.md), não como catálogo de produtos nem permissão executável; [motor próprio CRM](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md).  
**Objetivo:** avançar a elegibilidade de **Básico, Essencial, Profissional, Empresarial e Trial** por capacidades de gatilho/condição/ação, separando **direitos já documentados, referências numéricas pendentes e sugestões comerciais que exigem novo aceite**.

Não criar plano novo, API, canal, preço, serviço paralelo de filas ou aprovação silenciosa. Não programar, testar em sistemas reais, provisionar ou contratar.

## 1. Leitura da matriz: fontes, marcas e regras

A matriz não concede recurso só por constar aqui. **Direito real de execução =** organização com assinatura/benefício/trial elegível + recurso do plano + catálogo/versão homologada + slot/cota + papel/escopo + acesso ao módulo de negócio + canal/provedor quando aplicável + finalidade/consentimento + confirmação oficial da operação.

Sinais nas tabelas:
- **H — histórico/base documental:** requisito ou restrição já registrada nas REV/IN/CRM-DEC; isso **não** significa homologação de contrato executável.
- **P — proposta para aprovação:** sugestão de elegibilidade a validar comercialmente, **não direito concedido**.
- **C — condicionado:** depende de decisão comercial própria e de contrato/recurso/canal, mesmo nos planos pagos; **não incluído por presunção**.
- **N — não permitido:** proibido ou fora do escopo do editor; não liberar por plano.
- **SO — processo obrigatório do sistema:** fora da cobrança de slots de automações comerciais; submetido a direitos do módulo.
- **— — sem direito presumido:** ainda não demonstrado/decidido e não deve ser exibido como recurso liberado.

Preservar **Básico com catálogo de modelos prontos**, sem editor de lógica arbitrária; distinguir clientes com Básico **pago R$69/mês** de clientes com cortesia contratual elegível **sem alterar recursos**. As regras de preço final permanecem sujeitas à revisão global; a cortesia não dá direito a funções de outro plano.

## 2. Volumes de automações: números documentados, ainda sujeitos a revisão final

| Plano/estado | Referência de slots de automações comerciais ativas por organização | Chatbot/fluxos WhatsApp ativos — quota diferente | Direito associado |
|---|---:|---:|---|
| **Básico** | **3** (H, número a ratificar) | **—** (não presumir) | Só modelos prontos e elegíveis (H) |
| **Essencial** | **5** (H, a ratificar) | **3** (H, se canal/contrato elegível) | Editor de automações comerciais, alcance exato **P** |
| **Profissional** | **20** (H, a ratificar) | **10** (H, se canal/contrato elegível) | Editor mais completo, amplitude **P** |
| **Empresarial** | **50** (H, a ratificar) | **30** (H, se canal/contrato elegível) | Escala e permissões, amplitude **P** |
| **Teste gratuito** | **2** (H, trial de 14 dias; requisitos próprios) | **—** (não presumir) | Trial remete ao Profissional **com limites específicos**, sem habilitar automaticamente canais externos |

**Importante:** slot de automação **ativa** não é número de execuções, eventos/dia, funcionários, integrações, canais, contatos, leads, funis ou armazenamento. **Rascunhos não contam**; instâncias além da quota em downgrade ficam pausadas com histórico preservado e escolha autorizada. **SO não conta slot comercial**. Nenhum excesso ativa plano/compra/cobrança automaticamente.

**Itens comercialmente independentes:** chatbot/WhatsApp/API oficial, IA por créditos, serviços externos, webhooks e demais conectores, agenda pública e comunicações pagas; possuir slots e permissão de editar automação **não compra** os itens.

## 3. Capacidades do editor e tipos de operação — referência de diferenciação

| Capacidade | Básico | Essencial | Profissional | Empresarial | Trial |
|---|---|---|---|---|---|
| Operações internas obrigatórias do CRM/Agenda elegíveis | **SO/H** | **SO/H** | **SO/H** | **SO/H** | **SO/H** se módulo do trial aplicável |
| Ativar modelos prontos e homologados da biblioteca | **H** — apenas modelos Básico | **H/P** — elegíveis | **H/P** — elegíveis | **H/P** — elegíveis | **P** — seleção limitada conforme trial |
| Criar fluxos comerciais com gatilhos/ações tipados | **N** — editor arbitrário | **P** | **P** | **P** | **P**, limitada por trial |
| Condições compostas avançadas/ramificações | **N** — como editor livre | **P** — conjunto a definir | **P** — conjunto a definir | **P** — conjunto a definir | **P**, sem assumir tudo |
| Versões, pausa, histórico, auditoria | **H** para modelos ativados | **H** | **H** | **H** | **H** no escopo elegível |
| Canais externos de envio (WhatsApp/e-mail) | **—** | **C** | **C** | **C** | **—/C**, sem presumir |
| Chatbot visual WhatsApp | **—** | **C** | **C** | **C** | **—**, sem presumir |
| Ações externas/integradores/webhooks | **—** | **C** | **C** | **C** | **—**, API pública desligada no trial |
| Usar agentes IA e crédito IA | **C** por recurso/plano, sem presumir | **C** | **C** | **C** | **C** com cotas trial específicas |
| Autoagendamento/publicação da agenda | **N** | **C** | **C** | **C** | **—**, não presumir |
| Exportar/importar dados pelo autoatendimento CRM | **H** dentro dos direitos próprios | **H** | **H** | **H** | **C/H** por limitações de trial |
| Ações PR/X (pagamento, benefício, acesso global, apagar auditoria) | **N** | **N** | **N** | **N** | **N** |

**Não deduzir que Empresarial inclui todos os serviços externos**, nem que Essencial/Profissional possuem editor avançado, API pública ou canal sem sua respectiva matriz comercial. Os **P** são as questões comerciais que ainda pedem deliberação.

## 4. Matriz resumida de gatilhos por domínio — IDs CAT01 T01–T25

Legenda coluna: **MP** = modelo pronto controlado pela biblioteca; **ED** = elegibilidade candidata para editor tipado (a confirmar). **C** = categoria com contrato/entitlement separado. Números e contratos definitivos pendentes.

| Faixa de IDs no CAT01 | Família/autoridade | Básico | Essencial | Profissional | Empresarial | Trial | Evidência/limite |
|---|---|---|---|---|---|---|---|
| **T01–T04** | DAT — lead, campo, responsável, scoring | **MP/P** em subconjunto autorizado | **ED/P** | **ED/P** | **ED/P** | **P** só subset trial | T01 exige lead confirmado no CRM; scoring sugerido IA não é fato |
| **T05–T07** | VEN — oportunidade, etapa, ganhou/perdeu | **—** não presumir módulo | **ED/P** se VEN incluído | **ED/P** | **ED/P** | **P** se VEN trial elegível | VEN confirma etapa/resultado e motivo |
| **T08–T10** | VEN — proposta enviada, aceita, validade | **—** | **ED/P** se proposta disponível | **ED/P** | **ED/P** | **P** se VEN trial elegível | Visualização link não é aceite |
| **T11–T13** | VEN — tarefa/atividade, prazo, inatividade | **MP/P** somente modelos Básico previstos | **ED/P** | **ED/P** | **ED/P** | **P** | Motor de Datas + VEN; sem execução em ciclo |
| **T14–T17** | ATD — WhatsApp, fila, posse, reabertura | **—** sem chatbot/canal presumido | **C** | **C** | **C** | **—** | WhatsApp contratável e posse humana prevalece |
| **T18** | ATD — CSAT recebido | **—** | **C/P** | **C/P** | **C/P** | **—** | Evento oficial e acesso a atendimento |
| **T19–T20** | AGD — status, cancelamento, reagendamento, no-show | **MP/P** somente aviso interno permitido | **ED/P** com agenda elegível | **ED/P** | **ED/P** | **P** se Agenda trial incluída | Agenda é autoridade do efeito; sem autoagendamento Básico |
| **T21** | AGD — lembretes internos 24h/1h | **SO/H** | **SO/H** conforme módulo | **SO/H** | **SO/H** | **SO** conforme módulo | Nunca contar slot nem enviar externamente por padrão |
| **T22** | AGD — vaga em lista de espera | **—** | **C/P** | **C/P** | **C/P** | **—** | FIFO/aceite e validação por Agenda |
| **T23** | INT — resultado de integração homologado | **—** | **C** | **C** | **C** | **—** | Sem webhook/entitlement presumido |
| **T24** | IA — resultado validado de análise | **—/C** sem direito presumido | **C** | **C** | **C** | **C** apenas cotas trial | Crédito e permissão independentes |
| **T25** | FIN/CON — status oficial de assinatura/pagamento | **SO/PR**; **N editor** | **N editor** | **N editor** | **N editor** | **N editor** | Só processos internos oficiais/fluxo financeiro |

**T01..T25 são identificadores de proposta, não lista de 25 gatilhos liberados.** Não promover automaticamente MP/P, ED/P ou C a recursos contratados.

## 5. Matriz de condições — IDs C01–C15

| Família de IDs | Classificação / acesso | Requisito comercial e proteção |
|---|---|---|
| **C01 (escopo/tenant)** | **SO** em todo plano | Restrição de identidade obrigatória; **jamais personalizável para escapar do tenant** |
| **C02–C07 (dados, etapa, moeda, score, tarefa, prazo)** | **Básico: somente embutidas em MP/P**; demais planos **ED/P** se módulo incluso; trial sujeito a limites | Campos e dados autorizados; operadores/complexidade por plano ainda a homologar |
| **C08 (posse/expediente ATD)** | **C** nos planos com atendimento/canal elegível | **Obrigatória no envio**, não depende de configuração voluntária |
| **C09 (propostas VEN)** | **ED/P** somente com recurso VEN elegível | Versão e aceite confirmados |
| **C10 (Agenda)** | **SO** para proteger reserva; no Básico apenas modelos de aviso interno; editor pago **P** | Agenda valida conflito/transação |
| **C11–C12 (consentimento, canal, plano, quota)** | **SO** em todo plano ao executar | O cliente **não pode desligar** validação de direito/consentimento |
| **C13–C14 (INT e IA)** | **C** se produtos/limites independentes contratados | Informação oficial e modelo homologado, sem liberdade de código |
| **C15 (E/OU/NÃO composto)** | **Básico sem editor livre**; Essencial/Profissional/Empresarial **P**; Trial **P limitado** | Validar tamanho, profundidade e proteção anti-loop, sem números presumidos |

## 6. Matriz de ações — IDs A01–A22

| IDs | Família de efeito | Básico | Essencial | Profissional | Empresarial | Trial | Competência/segurança |
|---|---|---|---|---|---|---|---|
| **A01–A04** | Criar tarefa, atribuir, aviso interno, atualizar campo permitido | **MP/P**, nunca poder livre | **ED/P** | **ED/P** | **ED/P** | **P** | Comando do módulo proprietário, dedupe e RBAC |
| **A05–A09** | Etapa VEN, observação, distribuição, controlar fluxo, agendar próxima tarefa | **—/MP** se modelo efetivamente elegível | **ED/P** | **ED/P** | **ED/P** | **P** | VEN/ATD/Motor de Datas autorizam o efeito |
| **A10–A11** | Consulta/solicitação de Agenda | **N** para agendamento externo; interno/manual não é ação comercial livre | **C** | **C** | **C** | **—** | Agenda pública somente se direito específico; nunca dupla reserva |
| **A12–A14** | Mensagem WhatsApp, e-mail/webhook, transferência humana | **—** | **C** | **C** | **C** | **—** | Canal/contrato próprio; sem mensagens automáticas sob posse humana |
| **A15–A17** | Análise IA, CSAT, comando de integração | **—/C** sem presumir | **C** | **C** | **C** | **C** somente onde recursos trial previstos | IA não altera negócio só pela sugestão; INT reconcilia |
| **A18–A22** | Aceite/proposta/cobrança, plano/crédito, dados protegidos, presença, script/SQL/n8n | **N** | **N** | **N** | **N** | **N** | Atos PR/X exclusivos de fluxos oficiais; **não expor no editor de automações** |

**É proibido usar condição combinada, importação de JSON, IA, API, template ou reprocessamento para contornar ações PR/X, RBAC, conta/canal ou direitos do plano.**

## 7. Matriz de modelos de biblioteca (primeira proposta de curadoria)

A **biblioteca final de templates ainda não está homologada**; esta seção é apenas uma proposta de cobertura inicial para discutir.

| Exemplo candidato (CAT01) | Básico (modelo pronto) | Essencial | Profissional | Empresarial | Trial | Condição de homologação |
|---|---|---|---|---|---|---|
| Lead confirmado → tarefa interna | **P prioritário** | **P** | **P** | **P** | **P** | Fonte T01 CRM oficial; A01 idempotente |
| Lead sem interação → alerta interno | **P prioritário**, se o Básico tiver o recurso de acompanhamento elegível | **P** | **P** | **P** | **P** | Prazo/inatividade validado pelo proprietário |
| Oportunidade muda etapa → tarefa/aviso | **—** | **P**, se VEN elegível | **P** | **P** | **P**, se trial VEN | T06/C03/A01–A03, sem loops |
| Proposta aceita → tarefa pós-venda | **—** | **P**, se propostas elegíveis | **P** | **P** | **P** se trial VEN | Aceite oficial da versão exata |
| Agenda Básico → aviso interno 24h/1h | **SO/H**, não template comercial | **SO** conforme módulo | **SO** | **SO** | **SO** se agenda incluída | Nunca enviar mensagem externa automaticamente |
| Atendimento fora do expediente → transferência/fila | **—** | **C** | **C** | **C** | **—** | Canal contratado, fila FIFO e posse |
| Agendamento → WhatsApp ao cliente | **—** | **C** | **C** | **C** | **—** | Contrato WhatsApp, consentimento/template, status oficial AGD |
| Score confirmado alto → tarefa prioritária | **—/P**, caso Básico permita score | **P** | **P** | **P** | **P** se recurso trial | Scoring oficial DAT/VEN e direitos do módulo |

**Não deduzir que os dois primeiros já estão aprovados no Básico**; sugeri como candidatos por combinar valor comercial e execução interna sem custos de canais. A disponibilidade final por modelo e por plano deve ser deliberada.

## 8. Segurança, limites técnicos, suspensão e comportamento do cliente

- A plataforma só lista itens elegíveis por plano, módulo e autorização; a API revalida tudo inclusive em execuções tardias, filas e retries.
- Excedeu slots? Interface explica total e permite pausar automação ativa para liberar outro slot, ou conhecer upgrade. **Não contratar automaticamente**.
- Downgrade/benefício vencido/suspensão? Preservar definições, versões, execuções e auditoria conforme retenções; bloquear efeitos futuros incompatíveis sem travar SO.
- Ausência de saldo IA, canal WhatsApp, modelo homologado ou conexão autorizada? Suspender apenas efeito dependente daquele recurso; não converter em crédito negativo nem comprar pacote.
- Consumidor chega por n8n? **Não**: n8n fica restrito a operações internas licenciadas. O motor próprio processa automações de clientes; integrações e eventos técnicos seguem CORE/INT.
- Antiabuso, concorrência, limites de disparo/execuções, complexidade, número de nós e retomada de falhas precisam de **parâmetros futuros**, sujeitos a custo, capacidade e segurança. Não misturar slots com throughput.
- Status de mensagem/HTTP não confirma efeito oficial; posse humana impede disparos automáticos em conversa assumida por humano; agendamento validado transacionalmente pelo AGD; receita, assinatura e benefícios pelo FIN/CON.
- Trial de 14 dias possui um conjunto limitado de uso, não herda automaticamente todos os direitos funcionais do Profissional nem acesso à Universidade Imagine, que requer serviço elegível efetivamente contratado.

## 9. Pendências e decisões comerciais a submeter em bloco

**AUT-MAT-P01** — Aprovar/rever slots 3/5/20/50 e trial 2 sem confundir chatbot 3/10/30.  
**AUT-MAT-P02** — Validar catálogo exato de modelos prontos do Básico e os que podem ser ativados, sem editor arbitrário.  
**AUT-MAT-P03** — Definir as capacidades exatas de edição simples, condições compostas, ramificações, importação/exportação, compartilhamento e simulação por Essencial/Profissional/Empresarial/trial.  
**AUT-MAT-P04** — Confirmar o inventário DAT/VEN/AGD/ATD/IA/INT de T/C/A permitidos por plano, módulo e direito real; evitar dependências externas não contratadas.  
**AUT-MAT-P05** — Validar cobertura e contratação de WhatsApp, chatbot, e-mail externo, webhook, agenda pública, conectores e IA por plano; distinguir recurso incluído x adicional contratado.  
**AUT-MAT-P06** — Fixar limites técnicos por tenant (simultaneidade, custo/execução, eventos, tentativas, complexidade, retenção), separados dos slots.  
**AUT-MAT-P07** — Conciliar entitlements, suspensões, downgrades, trial, gratuidade administrativa e benefício Básico por contrato de Site; revalidar no momento da ação.  
**AUT-MAT-P08** — Publicar matriz de permissões verbais/atributos: quem cria, edita, publica, pausa, executa, exporta e audita; CORE-P0-02 e SEG-01.  
**AUT-MAT-P09** — Reconciliar LEG-10, AUT-P01..14, AUT-CAT01 v0.1, módulos VEN/ATD/AGD/IA/INT e catálogo final de templates; definir proprietários e evidências.  
**AUT-MAT-P10** — Homologar os contratos de eventos, condições e comandos por item e os casos de teste sem duplicar HT/G01..G06.

**Critérios eliminatórios:** atos PR/X jamais no editor; nenhum recurso pago concedido por slot genérico; sem mensagens ao cliente durante posse humana; Básico sem autoagendamento/chatbot não contratado; IA sem saldo/permite não executa; canais e contratos separados; persistência de leads independente do motor.

## 10. Proposta de decisão para próximo aceite (não concedido nesta versão)

Recomenda-se aceitar **esta matriz como base de segmentação comercial e técnica para revisão**, preservando a política aprovada de **Básico simples por modelos** e **motor próprio de automações**, sem:
- declarar o catálogo/templating de cada plano aprovado,
- criar direitos ou limites novos,
- decidir fornecedor/contrato/stack,
- declarar concluídos AUT-P01..14, CORE/INT-P0/SEG/G01..G06,
- contratar serviços nem iniciar programação.

**Status atualizado após aceite documental de 04/11/2026:** AUT-CAT01 é **inventário funcional de referência aceito**; AUT-MAT01 é **base de revisão comercial aceita**, mas toda marcação **P/C/—**, quotas finais, templates habilitados, contratos, direitos e permissões aguardam deliberação específica. A especificação documental não autoriza provisionamento, testes reais, desenvolvimento ou cobrança.

## Fontes

- [CAT01 e inventário candidato](CRM-AUT-CAT01-catalogo-gatilhos-condicoes-acoes-por-modulo-v0.1.md)
- [Aceite do inventário](CRM-DEC-aceite-referencia-CAT01-2026-11-04.md)
- [CRM-REV-05 e parâmetros de automações](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md)
- [CRM-REV-02 e planos](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md)
- [CRM-REV-07 e agenda](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md)
- [CRM-AUT-P0 v0.1 e contrato seguro](CRM-AUT-P0-execucao-segura-motor-proprio-automacoes-v0.1.md)
- [CRM-DEC motor próprio / n8n interno](CRM-DEC-motor-proprio-automacoes-crm-n8n-interno-2026-11-04.md)


## 11. Registro de aceite limitado (04/11/2026)

O responsável respondeu “ok” ao avanço da **matriz por plano**, registrado na [CRM-DEC AUT-MAT01](CRM-DEC-aceite-base-AUT-MAT01-2026-11-04.md). **A aprovação refere-se à utilização desta matriz como base de revisão**, não à contratação ou habilitação automática de modelos, ações, canais ou editor. Mantêm-se **AUT-MAT-P01..P10, AUT-P01..14, CORE/INT/SEG e demais P0**. Nenhum código, teste, gasto ou ativação autorizados.
