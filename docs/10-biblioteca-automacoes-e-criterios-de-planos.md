# 10 — Biblioteca de Automações do CRM Imagine: arquitetura, catálogo e diferenciação por plano

> **Planejamento 2026-11-01 · IN-006 — Direção de produto registrada**  
> Registrar todos os modelos de automação antes da revisão final de quantidades por plano. **Alguns modelos com capacidade mais forte/avançada deverão ficar reservados a planos pagos**; a classificação de cada item nesta primeira versão é uma proposta, não o catálogo definitivo.
>
> Observação de linguagem: a orientação foi interpretada como **não liberar indiscriminadamente toda a biblioteca no Básico**, preservando automações mais poderosas nos planos pagos; validar a interpretação na revisão final da matriz comercial se necessário.
>
> **Sem implementação.** Repositório principal e planos vigentes não alterados nesta etapa.

## 1. Decisões e estado

1. **IN-003 aprovada:** Básico Cortesia de contratantes de Onepage/Landing Page é mais enxuto que o Essencial pago.
2. **IN-004 aprovada:** Básico inclui Agenda e Agendamentos; a extensão pública e limites continuam pendentes.
3. **IN-005 aprovada como configuração de planejamento atual:** biblioteca de modelos prontos, até **3 automações comerciais ativas por organização** no Básico. O usuário determinou **revisar essa quantidade e as dos demais planos após documentação integral do catálogo**. Não tratar três como teto comercial final imutável; não reduzir ou aumentar automaticamente sem posterior aprovação.
4. **IN-006 — diretriz registrada:** manter uma **biblioteca central com modelos classificados por disponibilidade e capacidade**, valorizando o Básico, mas reservando alguns modelos de maior poder/complexidade aos planos pagos. O catálogo inteiro e a alocação exata ainda estão em desenvolvimento.
5. **Referências anteriores que permanecem documentadas até revisão:** Essencial **5**, Profissional **20**, Empresarial **50** automações comerciais ativas simultaneamente; trial **2**. A revisão conjunta pode propor novos limites, mas não altera decisões anteriores sem aprovação explícita.
6. **Contagem por organização/tenant**, não por usuário, site, lead, execução ou botão de formulário. Um modelo pode ser executado milhares de vezes e ainda ocupar somente um slot de automação ativa, sujeito a política de uso justo separada.

## 2. Distinção estrutural obrigatória: três camadas

### A. Operações de sistema — não contam como automações comerciais configuráveis

Ações inerentes à operação do produto: recepção/validação de formulário, geração do lead no CRM, isolamento e deduplicação de eventos, confirmação de agendamento, bloqueio de dupla reserva, histórico/auditoria, atualização de situação, aplicação de permissões. O sistema precisa realizá-las mesmo quando o tenant usa zero automações comerciais.

Notificações **internas indispensáveis** da agenda podem ser regra de produto; envio externo de e-mail/WhatsApp, quantidade de lembretes e eventual quota ainda requerem aprovação própria. Não assumir que todo alerta é gratuito/ilimitado.

### B. Modelos de automação comercial da biblioteca — contam slots quando ativos

São recursos selecionáveis pelo cliente com gatilhos, condições e ações, ativados, pausados e auditados. Todo modelo terá ID estável, versão, grupo, condições e parâmetros permitidos, autorizações, oferta por plano, custo/risco, teste e documentação de UX.

No Básico: **somente modelos prontos habilitados para esse benefício**, sem editor genérico de lógica arbitrária, e com os três slots atuais sujeitos à revisão final.

### C. Integrações, agentes e canais — quotas e faturamento separados

Chatbots WhatsApp, envios oficiais pela API WhatsApp, agentes de IA Prospector/Comercial, conexões de calendários Google/Outlook, notificações externas e relatórios programados dependem de serviços, autorizações e custos específicos. Não entrar automaticamente na quota de automações comerciais nem serem considerados incluídos pelo mero acesso à biblioteca. Os fluxos do chatbot têm limite próprio previamente documentado.

## 3. Inventário inicial de candidatos da biblioteca (NÃO É CATÁLOGO FINAL APROVADO)

As funcionalidades subjacentes já têm decisões no Capítulo 04, mas **cada combinação concreta como template e sua elegibilidade por plano são propostas**, até validação editorial/comercial final.

| Código | Categoria | Modelo/capacidade derivada | Trigger → ação principal | Disponibilidade inicial sugerida | Estado |
| --- | --- | --- | --- | --- | --- |
| LEA-01 | Leads | Novo lead: tarefa de atendimento | Lead entrou → criar tarefa e notificar responsável internamente | Básico + pagos | Proposto |
| LEA-02 | Leads | Lead sem resposta | Prazo sem interação → alerta/tarefa sem duplicar | Básico + pagos | Proposto |
| OPP-01 | Funil | Oportunidade parada | Tempo sem movimentação → tarefa/alerta | Básico + pagos | Proposto |
| AGD-01 | Agenda | Atendimento concluído: acompanhar | Agendamento realizado → tarefa de pós-atendimento | Básico + pagos | Proposto |
| TSK-01 | Tarefas | Tarefa vencida | Vencimento → alerta interno e tarefa de retorno conforme permissão | Básico + pagos, a avaliar | Proposto |
| OPP-02 | Propostas | Proposta aguardando resposta | Proposta sem resposta após prazo → tarefa de acompanhamento | Essencial ou acima | Proposto |
| OPP-03 | Propostas | Proposta aceita ou recusada | Atualização status → atribuição/tarefa/notificação/campo | Essencial ou acima | Proposto |
| LEA-03 | Distribuição | Rodízio de novos leads | Novo lead → selecionar vendedor ativo por rodízio | Planos pagos com equipe | Proposto |
| LEA-04 | Distribuição | Distribuição por menor carga | Lead novo → atribuir a vendedor disponível com menos leads | Planos pagos com equipe | Proposto |
| LEA-05 | Distribuição | Regra de campanha e equipe | Origem/UTM → responsável/equipe conforme regras | Essencial ou acima | Proposto |
| LEA-06 | Distribuição | Redistribuição por SLA | Sem atendimento no prazo → trocar responsável, com exceções | Profissional/Empresarial, a avaliar | Proposto |
| LEA-07 | Qualificação | Scoring de leads e ações condicionais | Pontuação muda de faixa → tarefa/atribuição/notificação | Essencial ou acima; avançado nos superiores | Proposto |
| OPP-04 | Funil | Mudança de etapa com múltiplas condições | Mudança de etapa → ações condicionais por valor/perfil | Profissional/Empresarial, a avaliar | Proposto |
| RPT-01 | Relatórios | Relatório agendado | Recorrência diária/semanal/mensal → geração/compartilhamento autorizado | Planos pagos; quota de relatórios própria a definir | Proposto (categoria distinta) |
| AGD-02 | Agenda | Ausência ou falta confirmada | Atendimento não compareceu → acompanhamento interno | Essencial ou acima; avaliar Básico | Proposto |
| AGD-03 | Agenda | Remarcação e cancelamento | Mudança de horário/cancelamento → ações internas | Núcleo de sistema + regras opcionais a definir | Proposto (classificação pendente) |
| INT-01 | Integrações | Acionar aplicativo externo | Evento validado → webhook/API de terceiro configurado | Planos pagos e com controle de custo | Hipótese futura; não aprovado como capacidade |
| MSG-01 | WhatsApp | Fluxo chatbot de qualificação | Mensagem recebida → fluxo + registro CRM | Recurso/planos pagos com quota de chatbot independente | Capacidade geral documentada; não é automação comercial 1:1 |
| MSG-02 | WhatsApp | Avisos transacionais externos | Evento autorizado → mensagem pela API oficial, template e consentimento | Serviço/canal pago ou adicional | Requer regras Meta, cobrança e quota próprias |
| IA-01 | Inteligência artificial | Qualificação/ação por agente IA | Evento autorizado → agente com quotas, permissões e custos | Contratação opcional e não coberta por Básico | Integração potencial; não template automático aprovado |

Este inventário é **incremental**, sem intenção de esgotar os módulos. Pode haver outros modelos quando documentarmos por completo leads, contatos, oportunidades, funil, propostas, tarefas, agenda, atendimento e integrações.

## 4. Princípios de diferenciação entre Básico e planos pagos

**Básico Cortesia:** automações de valor imediato e fácil configuração, relacionadas ao recebimento do formulário Onepage/Landing Page, acompanhamento e uma agenda simples. Biblioteca com modelos especificamente habilitados. Não pagar usuários adicionais, WhatsApp API, agente de IA ou integrações externas por efeito colateral de um template.

**Essencial:** começar a permitir biblioteca mais ampla e, quando ratificado, um maior número de automações ativas. Modelos que dependem de funcionalidades comerciais pagas devem respeitar os direitos efetivos da empresa. Não presumir editor livre até a matriz ser definida.

**Profissional:** modelos orientados a múltiplos vendedores/equipes, distribuição inteligente, integrações e regras compostas, sujeitos ao módulo e pacote realmente contratados.

**Empresarial:** automações e integrações de maior abrangência, operação em diversas unidades, relatórios e recursos administrativos/complexos, com privilégios específicos e limites de custo.

**Critério final para decidir o plano de um template:** valor comercial, dependência de módulos, quantidade de usuários/equipes, risco de abuso, custo de infraestrutura/API, complexidade técnica, volume de execução, necessidade de revisão e diferenciação de mercado. “Mais forte” não equivale apenas a mais execuções: pode significar mais permissões, integrações externas, efeitos mais amplos ou regras mais complexas.

## 5. Modelo de metadados do catálogo (PROPOSTO, NÃO IMPLEMENTADO)

Cada template deve especificar:
- `template_id`, `version`, `category`, `name`, `summary`, `trigger_schema`, `condition_schema`, `action_schema`, `config_schema`;
- `required_entitlements`, `eligible_plans`, `requires_channel`, `requires_extra_purchase`, `estimated_cost_band`;
- `status` (proposto, homologado, ativo, depreciado), `is_system_action`, `counts_commercial_slot`, `supports_draft`;
- `audit_level`, `idempotency_policy`, `throttle_policy`, `test_case_set`, `template_owner`.

**Gates de ativação:** conferir identidade e tenant, plano efetivo/benefício, módulos requeridos, slot livre e orçamento, permissão para executar ações, canais autorizados e validação da configuração. Toda edição/reativação revalida o gate. Em downgrade, preservar definições/históricos e pausar excedentes/inelegíveis com aviso, sem apagar dados ou burlar limitações.

**UX sugerida:** uma única Biblioteca de Automações com categorias, busca, “Disponível no seu plano”, “Requer upgrade”, prévia do efeito, configuração guiada e indicador de vagas ativas. Nunca permitir ao usuário iniciar um template Premium por link direto à API se o plano não inclui o entitlement.

## 6. Roteiro de fechamento da documentação, antes de rever quantidades

1. Inventariar todos os gatilhos/eventos possíveis dos módulos aprovados e novos módulos conforme forem documentados.
2. Converter apenas os fluxos úteis em **modelos de biblioteca**, cada um com descrição, ações, critérios de aceite, custos e teste.
3. Distinguir claramente automações comerciais, operações de sistema, notificações, fluxos de chatbot WhatsApp, automações de agenda e agentes IA (contagem e direitos separados onde necessário).
4. Para cada modelo, marcar **candidatos Básico, Essencial, Profissional, Empresarial**, com dependências e justificativa; aprovar cada categoria na matriz final.
5. Inventariar **quantidade total de modelos realmente homologáveis**, medir quantos justificam plano pago, avaliar custos por execução e estimar cenários de uso; não usar quantidade de modelos para inferir diretamente quantidade de slots necessários.
6. **Somente depois** revisar e ratificar conjuntamente as quotas de automações **ativas simultaneamente por organização**: referências atuais Básico 3, Essencial 5, Profissional 20, Empresarial 50. Registrar aprovação expressa antes de alterar limites já existentes.
7. Antes da programação, concluir contrato/API, modelo de versionamento, sandbox de testes, fila/idempotência, downgrade, observabilidade, auditoria, prevenção de loops, orçamento, migração e testes de isolamento multiempresa.

## 7. Próxima tarefa sugerida

Seguir a documentação módulo a módulo, começando pela **Agenda Básico Cortesia e sua eventual página pública de agendamentos** — escopo e limites ainda pendentes. Alimentar este catálogo conforme novas regras forem aprovadas. A revisão dos números por plano é uma **etapa final**, não uma pergunta a repetir agora.

**Status:** estratégia da biblioteca progressiva e segmentação entre Basic e planos pagos registrada como diretriz; quotas atuais apenas referências para planejamento até revisão; catálogo inicial explicitamente preliminar. **Nenhuma alteração de código, produção, cobrança ou limites em ambiente real.**
