# 12 — Limites por plano, mensagens contextuais e incentivo ao upgrade

> **Decisão IN-010 — APROVADA em 2026-11-01:** sempre que um cliente tentar executar uma ação que **não faz parte dos direitos de seu plano** ou **ultrapasse uma quota efetivamente aplicável**, o CRM Imagine deverá **informar claramente a condição**, explicar a restrição e **apresentar uma oportunidade pertinente de upgrade** (ou outra ampliação de capacidade contratável) quando houver solução comercial válida.  
> **Detalhes de interface, textos, limiares preventivos e catálogo/preços continuam propostas**, sujeitos à definição e aprovação posteriores. **Sem programação nesta fase.**

## 1. Regra transversal aprovada — para TODO o CRM

A regra vale para o **Básico Cortesia**, Essencial, Profissional e Empresarial, incluindo: leads, contatos, oportunidades, usuários/assentos, profissionais com agenda, serviços, agendas, autoagendamento público, automações comerciais, fluxos de chatbot, unidades, campos personalizados, armazenamento, relatórios, integrações, agentes IA e qualquer funcionalidade ou quota que venha a existir.

**Dois cenários de negócio diferentes:**
1. **Recurso não incluído:** a empresa tenta acessar ação ou configuração fora do plano, como publicar link de autoagendamento público no Básico. Mostrar que o plano atual permite apenas agenda manual, explicar a vantagem do recurso superior e permitir consultar/contratar plano elegível.
2. **Limite alcançado:** a empresa já usa o recurso, mas atingiu a quota, como ativar o 11º serviço quando o Básico permite 10 serviços ativos. Mostrar uso atual e limite, opções de **gerenciar/inativar/desocupar vagas** quando isso resolver e a opção de plano ou adicional que ofereça maior capacidade.

**Não confundir com restrição de segurança ou falha de sistema:** ausência de permissão do funcionário, MFA, suspeita de fraude, tenant errado, indisponibilidade técnica, erro de pagamento ou recurso não implementado não podem ser transformados em publicidade de upgrade. Nesses casos, exibir mensagem específica adequada, sem revelar informação de outra empresa.

## 2. Fluxo de interface e mensagens — orientação de implementação proposta

1. Cliente navega normalmente; o CRM pode exibir funcionalidade Premium identificada com clareza e visualização das vantagens, sem falsa promessa de acesso. Não interromper tarefas sem necessidade.
2. Ao clicar em ação bloqueada por entitlement/plano/quota, o backend verifica **organização ativa + plano efetivo + concessões (ex.: site_bundle_basic) + addons + assinatura paga + permissão + quota**.
3. UI exibe **título específico, causa e números reais quando disponíveis**; exemplos: `Você já utiliza 10 de 10 serviços ativos no Básico Cortesia`, `Agendamento online disponível somente nos planos pagos`.
4. A interface oferece alternativas pertinentes: `Gerenciar serviços` (liberar vaga), `Comparar planos` ou `Conhecer upgrade`; preço, se mostrado, deve vir do catálogo comercial efetivamente publicado, jamais de exemplos de documentação.
5. Usuário autorizado pode consultar comparação e prosseguir ao **checkout próprio do CRM**; apresentar escopo, preço total, ciclo, eventual pró-rata, impostos, addons, desconto elegível e data de início antes do consentimento.
6. **Nunca executar upgrade, gerar cobrança, alterar recursos efetivos ou vincular pagamento apenas porque clicou numa funcionalidade restrita.** A contratação só ocorrerá por confirmação expressa no fluxo financeiro aprovado e verificação de pagamento nos casos aplicáveis.
7. Se funcionário não puder comprar, permitir visualizar explicação e encaminhar convite/instrução para que o **administrador autorizado da empresa** faça upgrade; não apresentar um pagamento que ele não pode concluir.
8. Registrar evento de restrição e de intenção de upgrade para analytics de produto de forma minimizada e proporcional, **sem guardar dados sensíveis de leads, conteúdo de conversas ou dados pessoais desnecessários**.

### Exemplos de texto — NÃO são cópias finais aprovadas

**Recurso indisponível:**  
> **Agendamento online não está incluído no seu plano.** O Básico Cortesia permite agendamentos manuais no CRM. Para oferecer horários e reservas diretamente no seu site, conheça os planos pagos com autoagendamento.

**Quota atingida:**  
> **Limite de serviços ativos atingido.** Sua empresa utiliza 10 de 10 serviços ativos. Você pode inativar um serviço que não utiliza mais ou conhecer um plano com maior capacidade, se disponível.

**Usuário sem autorização para upgrade:**  
> **Esta contratação depende do administrador da empresa.** Você pode solicitar ao administrador que compare as opções disponíveis.

**Falha de infraestrutura ou manutenção:**  
> **Não foi possível concluir agora por indisponibilidade do serviço.** Tente novamente ou consulte o suporte.  
> Não insinuar que um upgrade resolverá um erro operacional.

## 3. Controle de direitos e limites — arquitetura proposta

- O CRM é **fonte de verdade** dos entitlements efetivos por empresa. O Portal Imagine pode exibir informações vindas da API do CRM, mas não calcular ou inventar cotas locais nem emitir upgrade do CRM.
- **Verificar no backend e não só na interface:** recursos protegidos, criação/ativação de entidades, importação em massa, chamadas de API e execução de automação passam pelos mesmos gates.
- Diferenciar `FEATURE_NOT_INCLUDED`, `QUOTA_EXCEEDED`, `PERMISSION_DENIED`, `SUBSCRIPTION_INACTIVE`, `TEMPORARY_UNAVAILABLE`, `VALIDATION_FAILED`. Os nomes são **proposta de contrato de erro**, não API implementada.
- Respostas de **negócio** podem incluir `feature_key`, `plan_id`, `quota_limit`, `quota_used`, `eligible_upgrade_options` e `can_purchase` sempre calculados no servidor e filtrados por permissões. O front não deve inferir o upgrade apenas pelo nome do plano.
- Operações concorrentes devem validar cotas **atomicamente** (ex.: dois cliques para ativar o 11º serviço; ativar 4ª automação; convidar usuário além dos assentos).
- Quotas não numéricas (recurso disponível ou não) e quotas dimensionais (serviços ativos, usuários, GB, automações ativas) usam mensagens adequadas. As quantidades aprovadas são observadas por plano/organização, mesmo se usuário participa de vários tenants.
- **Excedentes por downgrade:** preservar dados, histórico e configurações, permitir a seleção/regularização do que continuará ativo, inativar o excedente conforme o contrato e sugerir upgrade se o usuário desejar mais capacidade; nunca apagar dados automaticamente nem coagir compra para acesso/portabilidade legalmente garantidos.
- Ao atingir limites de armazenamento, API ou IA, não executar chamadas que gerem **cobrança extra automática** sem contratação/aceite. Quando existir addon autorizado que resolva a necessidade, oferecê-lo como alternativa ao upgrade, sem afirmar que só há solução mais cara.
- Entradas de formulário público Onepage/landing page e reservas já aceitas **não podem desaparecer silenciosamente** por quota atingida: respeitar políticas aprovadas de ingestão durável, uso justo, retenção e respostas corretas a visitante.
- Segurança, permissões, LGPD, alertas de fraude, isolamento multiempresa e controles antifraude são sempre exigidos e não podem ser contornados por upgrade.

## 4. Situações práticas do projeto

| Ação | Regra aprovada | Exemplo de tratamento recomendado |
| --- | --- | --- |
| Básico tenta criar 11º serviço ativo | IN-009 permite até 10 ativos | Informar 10/10, permitir inativar outro e oferecer plano que efetivamente tenha quota maior (quando aprovada) |
| Básico tenta habilitar link público de agenda | IN-007 reserva aos pagos | Explicar agenda manual versus reserva online, mostrar planos CRM pagos que incluem autoagendamento |
| Básico tenta ativar 4ª automação comercial | IN-005 baseline atual: 3 ativas; revisão numérica posterior IN-006 | Informar 3/3, permitir pausar outra ou comparar planos elegíveis |
| Básico tenta cadastrar 2º profissional | IN-008 permite 1 profissional agendável | Informar 1/1 e oferecer comparação dos planos com agenda para equipe conforme matriz aprovada |
| Essencial atinge limite de automações | Referência atual 5 ativas | Mostrar 5/5, permitir pausar automação e sugerir Profissional ou outro plano que amplie a quota |
| Usuário não administrador tenta upgrade | Permissão insuficiente | Direcionar ao admin autorizado; não tentar cobrar nem permitir contornar RBAC |
| Sistema fora do ar ou falha na integração | Erro técnico | Exibir erro honesto e suporte/repetir; **não** sugerir que upgrade resolve |

## 5. Avisos de aproximação de quota e conversão — PROPOSTOS, NÃO APROVADOS

- Mostrar uso atual e limite em seção `Meu Plano` e dentro do módulo correspondente, quando relevante e não intrusivo.
- Poder alertar em percentuais configuráveis (ex.: 80% e 100%) **somente quando fizer sentido para a quota**; não criar notificações repetitivas, urgência falsa nem contadores de cobrança.
- Permitir comparação por recursos efetivamente existentes e elegíveis, incluindo valor de assinatura, cobrança pro rata se aplicável, e benefícios exclusivos. Não inventar preços ou prometer recursos ainda não homologados.
- Permitir ao cliente continuar usando o que está incluído no plano; upgrade deve ser voluntário, não um bloqueio artificial além das quotas aprovadas.
- A eventual recomendação de plano pode ser contextual, mas não usar informações confidenciais de clientes ou perfis privados de funcionários para segmentação abusiva.

## 6. Critérios de aceite propostos

1. Tentativa de recurso não incluído não executa a operação; mostra motivo e caminho de consulta de planos elegíveis.
2. Tentativa de ultrapassar quota é rejeitada de modo transacional, com valores de quota reais para o tenant autorizado.
3. Se há forma gratuita legítima de liberar vaga, ela é oferecida junto com upgrade.
4. Funcionário sem permissão de compra não pode contratar plano; não há exposição de dados financeiros fora de sua autorização.
5. Confirmar upgrade depende de consentimento expresso e pagamento conforme regras aprovadas; clique em recurso bloqueado não gera assinatura, cobrança ou troca de plano.
6. Impedimento por segurança/permissão/erro técnico não mostra upsell enganoso.
7. A mesma operação por API, job e interface respeita entitlements; não basta ocultar botão.
8. Limite de usuário/organização não é confundido entre empresas; sem vazamento de quotas e métricas de outra empresa.
9. Downgrade preserva dados e configuração acima do limite, sujeita a regularização e bloqueios de novas operações elegíveis.
10. Leads enviados por formulários públicos e reservas previamente aceitas não se perdem silenciosamente por gating de plano.
11. Regras de upgrade não alteram a cobrança do site no Portal Imagine. O plano pago CRM é contratação separada, feita no próprio CRM.

## 7. Pendências finais

- Textos de UX, formato de aviso (modal, banner discreto, inline) e visibilidade de itens bloqueados.
- Sinalização preventiva de consumo de quota, preferência de comunicação, quotas não cumulativas e disponibilidade de addons.
- Matriz final de recursos e números de cada plano, inclusive revisão futura de automações após inventário da biblioteca.
- Catálogo de preços atualizado, cálculo pró-rata e tratamento de empresas com preços negociados.
- Estados de elegibilidade e status de cobrança/trial e casos-limite do CRM Básico cortesia.
- Políticas específicas quando módulos dependem de custos externos (WhatsApp, IA, calendário etc.).

**Estado:** IN-010 é **diretriz funcional aprovada para o CRM inteiro**; os detalhes de apresentação e implementação são propostas. Nenhuma programação foi iniciada.
