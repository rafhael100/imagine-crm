# CRM-REV-06 — Vendas, Funis, Propostas e Catálogo Comercial

**Data:** 2026-11-03. **Estado atualizado:** **APROVADA COMO BASE DOCUMENTAL**, conforme [CRM-DEC de aceite](CRM-DEC-aprovacao-REV-06-base.md). VEN-P01..16, diferenças e requisitos de implementação continuam abertos. [CRM-REV-07](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md) foi preparada **como minuta ainda não aprovada**.
**Origem da etapa:** [aceite da CRM-REV-05 como base](CRM-DEC-aprovacao-REV-05-base.md), que **não antecipa** a aprovação desta nova revisão.
**Âmbito:** VEN, CAT e fronteiras com dados comerciais, atividades, tarefas, relatórios, ATD/IA, FIN/CON e INT. **Controle de estoque fora do escopo inicial**, segundo capítulo histórico.
**Fase:** exclusivamente documentação GitHub; **sem programação, testes executados, criação de proposta real, aceite externo, envio, assinatura, faturamento ou produção**.

## 1. Proveniência e interpretação

Referência documental principal: [Capítulo histórico 04 — Empresas, Usuários e módulos CRM](https://github.com/rafhael100/imagine-crm/blob/docs/integracao-site-crm-basico/docs/04-empresas-e-usuarios.md), com seções rotuladas **APROVADO**. Complementos: [CRM-REV-03](CRM-REV-03-leads-contatos-campos-historicos-portabilidade.md), [CRM-REV-04](CRM-REV-04-atendimento-whatsapp-conversas.md), [CRM-REV-05](CRM-REV-05-chatbot-fluxos-gatilhos-automacoes.md), [CRM-REV-02](CRM-REV-02-planos-trial-assinaturas-cobrancas-basico.md) e [INT-R01](integracoes/INT-R01-governanca-contratos-homologacao.md).

**Limite de rastreabilidade:** fontes históricas podem sintetizar aprovações, mas não se recuperou neste passo o texto original integral de todos os blocos VEN-01..07 nem se certificou cobertura exaustiva de decisões posteriores. As 33 linhas abaixo são **itens de cotejo**, não 33 novas funcionalidades ou contratos físicos finalizados.

## 2. Regras localizadas — 33 itens de cotejo

| ID | Regra funcional a preservar na revisão |
|---|---|
| VEN-R01 | Funis Kanban por empresa, múltiplos e isolados |
| VEN-R02 | Etapas personalizadas, ordenadas, com histórico de movimentação |
| VEN-R03 | Campos obrigatórios e direitos por etapa validados no backend |
| VEN-R04 | Ganha/Perdida com confirmação e motivo obrigatório em Perdida |
| VEN-R05 | Motivos de perda iniciais e personalização por tenant |
| VEN-R06 | Reabertura protegida de ganho/perda, com justificativa e histórico |
| VEN-R07 | Catálogo por empresa de produtos/serviços, sem estoque inicial |
| VEN-R08 | Múltiplos itens por oportunidade, quantidade, preço e total |
| VEN-R09 | Descontos por item/total sujeitos à permissão e política comercial |
| VEN-R10 | Propostas geradas da oportunidade com PDF e numeração por empresa |
| VEN-R11 | Ciclo de proposta: Rascunho, Enviada, Aceita, Recusada, Expirada |
| VEN-R12 | Aprovação gerencial opcional para desconto ou preço fora da política |
| VEN-R13 | Proposta pendente de aprovação não pode ser enviada |
| VEN-R14 | Nova versão de proposta exige novo aceite |
| VEN-R15 | Link seguro da versão exata, validade, código opcional e revogação |
| VEN-R16 | Visualização do link pode notificar vendedor, mas não é aceite |
| VEN-R17 | Aceite/recusa com autor, data, versão e motivo de recusa |
| VEN-R18 | Aceite comercial no CRM não é assinatura eletrônica formal ou pagamento |
| VEN-R19 | Aceite pode marcar Ganha ou requerer confirmação conforme empresa |
| VEN-R20 | Atividades comerciais por contato/lead/oportunidade sem tarefa prévia |
| VEN-R21 | Tarefas com prioridade, prazo, estado e recorrência configurável |
| VEN-R22 | Anotações internas com menções e anexos não são mensagem ao cliente |
| VEN-R23 | Metas de vendedor/equipe/empresa por período sem dupla contagem |
| VEN-R24 | Dashboard com conversão, previsão simples/ponderada, perdas e metas |
| VEN-R25 | Relatórios de vendas e exportação CSV/XLSX/PDF autorizada |
| VEN-R26 | Relatórios personalizados por campos, agregação e visibilidade |
| VEN-R27 | Relatórios agendados via link seguro para destinatário autorizado |
| VEN-R28 | Lead Scoring 0–100 por regras, sem IA obrigatória |
| VEN-R29 | Faixas iniciais frio 0–39, morno 40–69, quente 70–100, personalizáveis |
| VEN-R30 | Histórico de evolução da pontuação com data, motivo e origem |
| VEN-R31 | Automações VEN por gatilho/condição/ação tipados e direito por plano |
| VEN-R32 | Distribuição/redistribuição de leads por modalidades aprovadas |
| VEN-R33 | VEN/CAT detêm fatos comerciais; FIN/CON detêm cobrança/contrato |

## 3. Autoridades e fronteiras obrigatórias

| Fato/objeto | Autoridade oficial | Restrição |
|---|---|---|
| Lead/contato e dados pessoais | Módulo de dados/ATD, conforme propriedade e autorização | Não mesclar por simples igualdade de nome/telefone |
| Funil, etapa, oportunidade, resultado ganho/perdido | VEN e permissões da empresa | API/n8n/IA não contornam etapa obrigatória ou justificativa |
| Catálogo, itens, quantidade, desconto | CAT/VEN | Preço do catálogo do cliente **não é** preço do plano CRM/Portal Imagine |
| Proposta, revisão, aprovação, aceite comercial | VEN e aprovador autorizado por empresa | Visualizar link não é aceitar; v1 aceita não abrange v2 |
| Contrato assinado, assinatura, cobrança, NFS-e e pagamento | FIN/CON e seus comprovantes | O aceite comercial no CRM **não confirma** esses fatos |
| Meta, scoring, previsão, relatório | VEN por eventos confirmados e campos permitidos | Reabertura/duplicata não infla vendas nem revela dados privados |
| Histórico de negócio e prova/auditoria | Proprietário do negócio e Auditoria Geral, respectivamente | Log técnico não substitui evento comercial oficial |

## 4. Estados conceituais — não enums aprovados

- **Oportunidade:** aberta, em etapa, ganha/perdida e reaberta com trilha preservada; campos obrigatórios e regras de transição próprias.
- **Proposta:** Rascunho, Enviada, Aceita, Recusada, Expirada, com **versão específica** e validade; aprovação gerencial pode ser requisito adicional por política.
- **Link público de proposta:** disponível/revogado/expirado; o acesso registra visualização, não aceitação.
- **Tarefa/atividade:** pendente, em andamento, concluída ou cancelada conforme seu domínio; atividade comercial não prova envio de mensagem.
- **Pontuação e faixas:** 0–100 e classificação, com histórico da regra e origem; valores por empresa não representam fatos fiscais.
- **Metas e relatórios:** apuração por período e autorização, evitando recontagem por reaberturas.

## 5. Divergências e riscos a conciliar

| ID | Questão |
|---|---|
| VEN-D01 | Proposta comercial aceita não é assinatura formal, fatura nem pagamento confirmado |
| VEN-D02 | Transição para Ganha pode ser opcional conforme configuração de aceite |
| VEN-D03 | Catálogo do cliente não é catálogo de ofertas/preços da Imagine/Portal |
| VEN-D04 | Reabertura de negócio não duplica vendas, metas ou relatórios |
| VEN-D05 | Vendas CRM não implicam integração fiscal/contábil |
| VEN-D06 | Scoring por regras não exige agente IA nem consumo de crédito |
| VEN-D07 | Faixas são ajustáveis, e pesos não estão homologados |
| VEN-D08 | Etapa do funil, status da oportunidade e ciclo de proposta são objetos diferentes |
| VEN-D09 | Ver, editar, aprovar, enviar e exportar proposta são direitos distintos |
| VEN-D10 | Relatório agendado possui autorização e custo próprios, não slot comercial presumido |
| VEN-D11 | Distribuição de lead não equivale à fila humana FIFO do WhatsApp |
| VEN-D12 | Automações não podem contornar aprovação de preço/desconto |
| VEN-D13 | Limites numéricos por plano/funil ainda sujeitos a cotejo |
| VEN-D14 | Histórico da branch não substitui fonte original integral VEN-01..07 |

## 6. Pendências de especificação para o módulo

| ID | Evidência necessária | Situação |
|---|---|---|
| VEN-P01 | Dicionário das entidades, referências, chaves, relações e modelo de tenant | Pendente |
| VEN-P02 | Permissões por recurso/campo/ação, alçada de descontos e aprovação | Pendente |
| VEN-P03 | Transições de funil e oportunidade, concorrência e campos obrigatórios | Pendente |
| VEN-P04 | Cálculo de preço, impostos aplicáveis, moeda, descontos e previsão | Pendente |
| VEN-P05 | Versão/validade/aceite exatos da proposta e revogação de link | Pendente |
| VEN-P06 | Critérios de alçada e decisões por política comercial da empresa | Pendente |
| VEN-P07 | Reabertura, eventos de venda e não duplicação de metas/receita | Pendente |
| VEN-P08 | Scoring, pesos, reavaliação e faixas por empresa | Pendente |
| VEN-P09 | Distribuição/redistribuição e prevenção de corrida e loops | Pendente |
| VEN-P10 | Modelo de proposta PDF, proteção, acesso externo e LGPD | Pendente |
| VEN-P11 | Metas, períodos, congelamento histórico e correção | Pendente |
| VEN-P12 | Relatórios personalizados/agendados, escopo, retenção e acesso | Pendente |
| VEN-P13 | Entitlements dos planos e limites de funis/propostas/catálogo | Pendente |
| VEN-P14 | Contratos VEN/CAT/ATD/AGD/FIN/CON/INT/IA e fontes oficiais | Pendente |
| VEN-P15 | Cotejo literal das aprovações originais VEN-01..07 | Pendente |
| VEN-P16 | Aceite documental, cenários negativos e requisitos de homologação | Pendente |

**Esses 16 pontos são lacunas de revisão**, não novos recursos, novos perfis ou autorizações implícitas. Devem ser relacionados ao Índice Mestre do CRM e aos gates P0 existentes, sem duplicar controles.

## 7. Critérios de aceitação futuros — NÃO executados

| ID | Resultado negativo ou limite a verificar futuramente |
|---|---|
| VEN-T01 | Mover oportunidade com campo obrigatório vazio → negar |
| VEN-T02 | Duas alterações de etapa simultâneas → preservar concorrência e trilha |
| VEN-T03 | Marcar Perdida sem motivo → negar |
| VEN-T04 | Reabrir Ganha → preservar ganho anterior sem duplicar meta |
| VEN-T05 | Desconto além da alçada → exigir aprovação competente |
| VEN-T06 | Proposta pendente tenta envio por automação → negar |
| VEN-T07 | Link visualizado → não concluir aceite |
| VEN-T08 | Link revogado/expirado → negar visualização |
| VEN-T09 | Versão v2 substitui v1 → v1 não cobre novo aceite |
| VEN-T10 | Proposta expirada → não permitir aceitar sem renovação |
| VEN-T11 | Aceite comercial → não gerar cobrança sem FIN/CON |
| VEN-T12 | Acesso cruzado a relatório/PDF de outra empresa → negar |
| VEN-T13 | Relatório solicita campo sensível sem direito → negar campo |
| VEN-T14 | Evento duplicado de ganho → não duplicar meta |
| VEN-T15 | Lead scoring muda de faixa → recalcular e preservar histórico |
| VEN-T16 | Distribuição simultânea de lead → um responsável efetivo |
| VEN-T17 | Lead já atendido → não redistribuir por atraso |
| VEN-T18 | Destinatário perde direito antes do relatório agendado → negar entrega |

**0 testes executados.** Os casos VEN-T01..18 são cenários documentais propostos, não novos HT aplicados agora.

## 8. Anexo de proveniência: seções originais do capítulo histórico

As seções abaixo são transcrições **do próprio repositório privado** para confronto de diferenças e aprovação, não uma declaração de que tenham sido homologados schemas, alçadas ou versões executáveis. Em divergência com decisão mais recente, registrar a precedência expressa antes de substituir a versão.

### Fonte histórica — Anotações e comentários internos — APROVADO

**Título original:** Anotações e comentários internos — APROVADO
- Usuários autorizados podem criar anotações internas em leads, contatos e oportunidades, com texto, menções a colegas (@usuário) e anexos.
- Visualização limitada a funcionários com acesso ao respectivo registro, conforme permissões.
- Menções podem gerar notificações internas.
- Edições e exclusões de anotações são registradas no histórico.
- Anotações são exibidas na linha do tempo comercial e **não são enviadas automaticamente aos clientes**.

---

### Fonte histórica — Tarefas e lembretes comerciais — APROVADO

**Título original:** Tarefas e lembretes comerciais — APROVADO
- Tarefas vinculadas a leads, contatos e oportunidades, com título, descrição, responsável, prioridade e prazo.
- Status: pendente, em andamento, concluída ou cancelada.
- Lembretes e notificações de vencimento.
- Tarefas recorrentes, com periodicidade configurável (detalhes de regras ainda a especificar).
- Visualização em lista e calendário.
- Registrar criação, alterações e conclusão na linha do tempo comercial.
- Respeitar isolamento por empresa e permissões de acesso.

---

### Fonte histórica — Registro de atividades comerciais — APROVADO

**Título original:** Registro de atividades comerciais — APROVADO
- Registrar atividades realizadas sem exigir tarefa prévia, vinculadas a leads, contatos ou oportunidades.
- Tipos: ligação, WhatsApp, e-mail, reunião, visita e outro (atividade personalizada).
- Cada atividade pode conter data, horário, responsável, descrição e resultado.
- Exibir na linha do tempo comercial, respeitando permissões e isolamento por empresa.

---

### Fonte histórica — Funis de vendas (Kanban) — APROVADO

**Título original:** Funis de vendas (Kanban) — APROVADO
- Cada empresa pode criar e administrar múltiplos funis de vendas, isolados por tenant.
- Etapas personalizadas: criar, renomear e reordenar.
- Visualização Kanban com movimentação de oportunidades entre etapas.
- Oportunidades podem ter vendedor responsável, valor e previsão de fechamento.
- Registrar todas as mudanças de etapa no histórico comercial.
- Permissões específicas para visualização e alteração dos funis e oportunidades.
- Estados finais **Ganha** e **Perdida**, com motivos de perda configuráveis por empresa.

---

### Fonte histórica — Regras de movimentação de oportunidades no funil — APROVADO

**Título original:** Regras de movimentação de oportunidades no funil — APROVADO
- Cada empresa pode configurar, opcionalmente por funil, campos obrigatórios por etapa e permissões para movimentação de oportunidades.
- Impedir movimentações quando requisitos obrigatórios não forem atendidos.
- Permitir retorno a etapas anteriores, registrando a movimentação no histórico.
- Exigir confirmação ao marcar oportunidade como **Ganha** ou **Perdida**; ao marcar **Perdida**, exigir motivo da perda.
- Aplicar regras e validações no backend, inclusive em movimentações por integrações e automações.

---

### Fonte histórica — Catálogo de produtos e serviços nas oportunidades — APROVADO

**Título original:** Catálogo de produtos e serviços nas oportunidades — APROVADO
- Cada empresa mantém catálogo próprio de produtos e serviços, com nome, código, descrição, preço e status.
- Cada oportunidade pode conter múltiplos itens, com quantidade e preço unitário.
- Descontos por item ou no total sujeitos a permissões.
- Valor total calculado automaticamente; valor dos itens pode alimentar o valor da oportunidade e a previsão ponderada.
- Registrar alterações de itens e valores no histórico comercial.
- Controle de estoque fora do escopo inicial.

---

### Fonte histórica — Propostas comerciais — APROVADO

**Título original:** Propostas comerciais — APROVADO
- Gerar propostas comerciais diretamente de oportunidades, usando modelos personalizados por empresa.
- Incluir dados do cliente, produtos, serviços, valores e descontos.
- Gerar PDF para download e atribuir numeração única por empresa.
- Permitir data de validade e preservar histórico de versões/revisões.
- Status: **Rascunho, Enviada, Aceita, Recusada e Expirada**.
- Compartilhar por e-mail ou link seguro, respeitando permissões de acesso.
- Na primeira etapa, registrar aceitação no CRM sem integração de assinatura eletrônica.

---

### Fonte histórica — Aprovação de propostas comerciais — APROVADO

**Título original:** Aprovação de propostas comerciais — APROVADO
- Fluxo de aprovação **opcional e configurável por empresa**.
- Propostas dentro da política comercial podem ser enviadas diretamente por usuários autorizados.
- Descontos acima do limite permitido e alterações de preço fora da política exigem aprovação de gestor autorizado.
- Propostas pendentes de aprovação não podem ser enviadas.
- Registrar aprovação ou rejeição com responsável, data e justificativa.
- Empresa pode desativar a exigência do fluxo de aprovação.
- Regras devem ser aplicadas no backend, inclusive em envios por integrações e automações.

---

### Fonte histórica — Compartilhamento e acompanhamento de propostas — APROVADO

**Título original:** Compartilhamento e acompanhamento de propostas — APROVADO
- Gerar link seguro exclusivo para visualização de cada proposta, vinculado à versão correta.
- Validade do link configurável por empresa e possibilidade de exigir código de verificação para acesso.
- Registrar visualizações e permitir notificar o vendedor quando a proposta for aberta.
- Permitir revogar/desativar o link a qualquer momento.
- A visualização **não** equivale à aceitação da proposta.
- Verificações de acesso e segurança devem ocorrer no backend.

---

### Fonte histórica — Aceitação e recusa de propostas comerciais — APROVADO

**Título original:** Aceitação e recusa de propostas comerciais — APROVADO
- Ao aceitar proposta, registrar data, responsável e versão aceita.
- Ao recusar, registrar o motivo da recusa.
- Propostas expiradas não podem receber novo aceite até que sua validade seja renovada.
- Alterações/revisões de proposta exigem nova aceitação da versão revisada.
- Aceitação e recusa atualizam automaticamente o histórico da oportunidade vinculada.
- Configuração por empresa: aceitar proposta pode marcar oportunidade como **Ganha** automaticamente ou exigir confirmação do vendedor.
- Aceite é confirmação comercial no CRM e **não equivale a assinatura eletrônica formal**.

---

### Fonte histórica — Motivos de perda de oportunidades — APROVADO

**Título original:** Motivos de perda de oportunidades — APROVADO
- Lista inicial por empresa: **Preço, Concorrência, Sem orçamento, Sem interesse, Prazo, Sem retorno e Outro**.
- Cada empresa pode personalizar seus motivos de perda.
- Ao marcar oportunidade como **Perdida**, selecionar um motivo é obrigatório; observação complementar é opcional.
- Utilizar motivos de perda em relatórios comerciais para análise das causas das perdas.

---

### Fonte histórica — Reabertura de oportunidades ganhas ou perdidas — APROVADO

**Título original:** Reabertura de oportunidades ganhas ou perdidas — APROVADO
- Usuários autorizados podem reabrir oportunidades com status **Ganha** ou **Perdida**, com justificativa obrigatória.
- Preservar encerramento anterior e histórico integral da negociação.
- Na reabertura, selecionar etapa de retorno no funil.
- Recalcular valores ponderados e probabilidade conforme etapa selecionada, respeitando regras de ajuste manual autorizado.
- Relatórios devem preservar histórico de ganhos/perdas sem contagem duplicada.

---

### Fonte histórica — Metas comerciais — APROVADO

**Título original:** Metas comerciais — APROVADO
- Metas de vendas individuais por vendedor, coletivas por equipe comercial e gerais por empresa.
- Períodos: mensal, trimestral e anual.
- Indicadores: valor vendido, quantidade de vendas e oportunidades ganhas.
- Acompanhar meta definida, resultado alcançado, percentual de atingimento e evolução.
- Gestores autorizados configuram metas; vendedores visualizam apenas as metas autorizadas.
- Apuração baseada em oportunidades efetivamente ganhas, sem duplicar vendas em caso de reabertura.

---

### Fonte histórica — Dashboard comercial — APROVADO

**Título original:** Dashboard comercial — APROVADO
- Painel por empresa com indicadores atualizados automaticamente, respeitando isolamento de dados e permissões de visualização.
- Indicadores: leads totais/novos/convertidos; oportunidades abertas/ganhas/perdidas; quantidade e valor por etapa do funil; previsão de vendas total e ponderada; taxas de conversão entre etapas; metas, resultados e percentual atingido; tarefas pendentes/vencidas/concluídas; desempenho por vendedor e equipe; principais motivos de perda.
- Exibir gráficos e indicadores resumidos.
- Filtros por período, funil, vendedor e equipe, conforme autorização.

---

### Fonte histórica — Relatórios comerciais — APROVADO

**Título original:** Relatórios comerciais — APROVADO
- Relatórios iniciais: vendas por período, conversão de leads, desempenho por vendedor, desempenho por equipe, funil de vendas, motivos de perda, atividades comerciais, metas e previsão de vendas (valores previstos e ponderados).
- Filtros por período, funil, vendedor e equipe.
- Exportação nos formatos CSV, Excel (XLSX) e PDF.
- Respeitar permissões de cada usuário e isolamento por empresa.
- Valores de vendas referem-se a dados comerciais registrados no CRM, sem implicar integração contábil ou fiscal.

---

### Fonte histórica — Relatórios personalizados — APROVADO

**Título original:** Relatórios personalizados — APROVADO
- Cada empresa pode criar e salvar relatórios personalizados a partir dos módulos e campos disponíveis, inclusive campos personalizados.
- Permitir seleção e reordenação de colunas, combinação de filtros, agrupamentos por vendedor, equipe, período ou etapa, e agregações de contagem, soma e média.
- Salvar configurações para reutilização e compartilhar relatórios de modo privado ou com usuários autorizados.
- Exportar relatórios em CSV, XLSX e PDF.
- Respeitar isolamento por empresa, permissões de visualização de registros e campos e permissões de exportação; não expor dados não autorizados.

---

### Fonte histórica — Agendamento e envio automático de relatórios — APROVADO

**Título original:** Agendamento e envio automático de relatórios — APROVADO
- Permitir agendamento de relatórios com frequência diária, semanal ou mensal.
- Destinatários limitados a usuários autorizados da própria empresa.
- Formatos de geração: PDF, XLSX e CSV, usando filtros e configurações salvos do relatório.
- Enviar por e-mail link seguro para download, em vez de expor dados diretamente.
- Validar permissões e isolamento por empresa no momento da geração e entrega.
- Permitir pausar, editar ou cancelar agendamentos.
- Registrar histórico de execuções, entregas e falhas.
- Respeitar limites e permissões da empresa, sem compartilhamento com destinatários não autorizados.

---

### Fonte histórica — Distribuição automática de leads — APROVADO

**Título original:** Distribuição automática de leads — APROVADO
- Cada empresa pode configurar modalidades de atribuição de novos leads: rodízio (round-robin), menor carga de leads ativos, equipe ou origem/campanha, responsável fixo e distribuição manual por gestor.
- Distribuição automática considera apenas vendedores ativos e habilitados a receber leads.
- Atribuições respeitam permissões, isolamento por empresa e são registradas no histórico comercial.

---

### Fonte histórica — Redistribuição automática de leads sem atendimento — APROVADO

**Título original:** Redistribuição automática de leads sem atendimento — APROVADO
- Recurso opcional e configurável por empresa.
- Prazo de primeiro atendimento configurável em minutos ou horas; notificar o vendedor responsável por lead ainda não atendido.
- Ao exceder o prazo, permitir redistribuição automática a outro vendedor ativo e habilitado, também em caso de indisponibilidade do responsável.
- Limite de tentativas de redistribuição configurável, evitando ciclos infinitos.
- Registrar responsáveis anteriores, datas e motivos no histórico comercial.
- Permitir exceções para bloquear a redistribuição de leads estratégicos.
- Não redistribuir leads que já receberam atendimento válido.
- Respeitar permissões e regras de distribuição previamente definidas.

---

### Fonte histórica — Lead Scoring — Qualificação e pontuação de leads — APROVADO

**Título original:** Lead Scoring — Qualificação e pontuação de leads — APROVADO
- Pontuação de leads na escala de 0 a 100, calculada inicialmente por regras configuráveis por empresa, sem exigir inteligência artificial.
- Critérios: origem (campanha ou canal), perfil (segmento, região e porte da empresa), engajamento (respostas e interações) e redução de pontos por inatividade.
- Classificação dos leads como frio, morno ou quente, com critérios personalizados por empresa.
- Permitir uso da pontuação em automações de atribuição de responsável, criação de tarefas e notificações.
- Respeitar isolamento por empresa e permissões aplicáveis.
- Limiares de classificação e pesos exatos ainda não foram definidos.

---

### Fonte histórica — Faixas de classificação do Lead Scoring — APROVADO

**Título original:** Faixas de classificação do Lead Scoring — APROVADO
- Faixas padrão iniciais: **Frio = 0 a 39 pontos**, **Morno = 40 a 69 pontos**, **Quente = 70 a 100 pontos**.
- Cada empresa pode personalizar os limites das três faixas, sem sobreposição e cobrindo a escala 0–100.
- A classificação do lead deve ser recalculada automaticamente quando sua pontuação mudar.

---

### Fonte histórica — Histórico e gráfico de evolução do Lead Scoring — APROVADO

**Título original:** Histórico e gráfico de evolução do Lead Scoring — APROVADO
- Registrar cada alteração de pontuação do lead, com valor anterior, novo valor, regra/motivo, data e horário e origem do evento ou automação.
- Disponibilizar histórico no cadastro do lead, separado da auditoria técnica.
- Exibir gráfico da evolução da pontuação ao longo do tempo.
- Respeitar permissões de visualização do lead e isolamento por empresa.

## 9. Decisão proposta e sequência

**CRM-REV-06 foi aprovada como base documental de reconciliação** conforme [registro de aceite](CRM-DEC-aprovacao-REV-06-base.md), preservando os 33 itens e distinções VEN/CAT/FIN/CON, sem aprovar schemas, perfis, alçadas, valores finais, assinatura eletrônica, dinheiro, automações executáveis ou programação. **VEN-P01..16 continuam abertos**.

A [CRM-REV-07 — Agenda, Profissionais, Unidades e Agendamentos](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md) foi preparada **como minuta ainda não aprovada**, preservando o Básico manual/interno e as páginas públicas dos planos elegíveis.

**Situação global atualizada:** CRM-REV-01..06 bases aceitas, REV-07 ainda **minuta não aprovada**, CORE-P0/INT-P0/IN-168 e G01–G06 continuam abertos. Nenhum código iniciado.
