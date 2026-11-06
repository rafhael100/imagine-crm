# CRM Imagine — CORE07-D07: equipes, escopos e redistribuição ao afastar/desligar funcionário — proposta v0.1

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **PAUTA DOCUMENTAL AINDA NÃO APROVADA — CORE07-D07 ABERTA.**  
**Objeto específico:** como reagir a desligamento, ausência, mudança de equipe ou perda de competência do responsável por um lead, oportunidade, atendimento, tarefa ou agenda. **Não reabrir decisões anteriores sobre distribuição comercial, SLA ou agenda, nem criar automações sem contrato do módulo.** Sem código, dados reais, teste ou implantação.

## 1. Decisões preexistentes e fronteiras

- CORE07-D01..D03: vínculo do funcionário ao tenant, equipes múltiplas, escopos próprio/equipe/empresa, transferência de leads e oportunidades por usuário habilitado, manutenção de histórico; desligamento de A não elimina identidade ou acesso legítimo a B.
- [CORE02-D05 — alternativa B, M2 e GOV02](CRM-DEC-ratificacao-explicita-CORE02-D05-GOV02-ABCD-2026-11-06.md): combinação de grants escopados, negações explícitas qualificadas, competência específica de delegar/atribuir, proibição de autoelevação, conflito e auditoria.
- [REV-06 / Vendas](CRM-REV-06-vendas-funis-propostas-catalogo.md): distribuição de novos leads por modalidades previstas (manual, rodízio, menor carga, equipe/origem/responsável fixo) e **redistribuição automática por SLA ou indisponibilidade quando autorizada**, com exceção para leads estratégicos, prevenção de loops e proibição de redistribuir lead já atendido validamente por mero atraso. Esta pauta não revoga essa decisão nem a amplia a outros módulos.
- REV-04 atendimento e conversas e REV-07 agenda possuem autoridades próprias: posse de conversa, status de atendimento, confirmação de agendamentos, disponibilidade, transferência e histórico não podem ser modificados só porque um usuário saiu de equipe.
- BASIC: administrador único com MFA; modelo A de substituição de titular aprovado documentalmente em CORE07-D06. Nenhuma política de equipe cria segundo assento ou ação fora do plano.
- Agentes, n8n e jobs seguem identidades causais e escopos específicos, sem replicar automaticamente direitos do funcionário desligado. Registros de autoria e fatos históricos são preservados.

## 2. Alternativas para fluxos após afastamento/desligamento — NÃO APROVADAS

| Modelo | Funcionamento | Vantagem | Risco/ressalva |
|---|---|---|---|
| **A — Somente redistribuição manual** | Bloqueia imediatamente o acesso do usuário removido; registros pendentes aguardam que gestor autorizado escolha sucessor | Simples para casos sensíveis | Pode deixar leads novos ou filas urgentes sem responsável durante ausência |
| **B — Híbrido com regras existentes + pendência supervisionada** | Revoga o acesso do usuário imediatamente. **Automatiza somente** redistribuições já autorizadas por política do módulo/empresa, com destino habilitado e proteção contra loop; casos não elegíveis ficam em **fila de pendências** para gestor competente, com avisos e reatribuição manual aprovada | Continuidade operacional sem transferir indevidamente conversas, agendamentos, leads estratégicos ou históricos | Exige classificar objetos, responsabilidades, estados e alertas com os proprietários |
| **C — Redistribuir automaticamente tudo** | Ao sair usuário, transfere todos os objetos a pessoa disponível, sem discriminar status ou risco | Menos trabalho aparente | Pode violar posse/consentimento, alterar agendamentos e prejudicar históricos e leads estratégicos |

**Recomendação para aprovação:** **Modelo B como diretriz documental preliminar**, subordinado às regras já aprovadas por módulo. Nunca reatribuir indiscriminadamente conversas em atendimento, agendamentos confirmados, fatos concluídos, evidências ou objetos com proteção especial.

## 3. Critérios sugeridos para o Modelo B

1. **Segurança antes da redistribuição:** ao desligar/remover membership ou escopo, revogar imediatamente o acesso relevante no tenant, sessões e futuras ações protegidas, sem impedir serviços autônomos legalmente autorizados e sem apagar histórico.
2. **Triagem por tipo e status:** módulo proprietário determina se um registro pode ser movido, se tem atendimento válido, lead estratégico, conversa em posse exclusiva, prazo de retorno, agendamento vinculado, aprovação em curso ou outra restrição. Autorizar a transferência não significa negócio concluído.
3. **Automação somente onde já autorizada:** executar a política oficial vigente de VEN/ATD/AGD/AUT nos casos elegíveis e com destino capaz; identificar causa, responsável e versão, com idempotência, prevenção de atribuição circular e revalidação no momento do efeito. Não ativar regras novas ou distribuição geral pelo simples aceite CORE07.
4. **Fila de supervisão:** quando não existir destino elegível, a regra for manual ou o objeto for sensível, registrar pendência de reatribuição para gestor autorizado, com status e lembretes proporcionais; sem exposição de dados protegidos fora do escopo. Nenhum objeto some da base por ficar pendente.
5. **Preservação e visibilidade:** preservar autor, atendente histórico, origem, eventos, propostas, agendamentos, contatos, evidências e metas oficialmente apuradas. Atualizar somente a atribuição futura, conforme regra específica, e recalcular escopo/permissões sem vazamento entre equipes/tenants.
6. **Equipes/unidades e substitutos:** aceitar múltiplas equipes por usuário com escopos próprios. Gestor habilitado pode administrar atribuições apenas no escopo de sua competência. Ausências temporárias, redistribuição comercial por SLA, trocas de gestor e desligamento definitivo possuem gatilhos distintos.
7. **Auditoria, exceções e contestação:** identificar quem/política autorizou, motivo, origem/destino, estado do objeto, versão e resultado; definir futuramente prioridades de alerta, conflitos concorrentes, SLA, reversão e notificações com os donos dos módulos. Sem implantar rotina de auditoria duplicada.

## 4. Exemplos para homologação futura — NÃO EXECUTADOS

| Situação | Resultado esperado como princípio |
|---|---|
| Vendedor sai com lead novo sem atendimento, elegível à política de distribuição vigente | Reavaliar destino e redistribuir sob regra comercial autorizada |
| Vendedor sai com lead estratégico protegido | Não mover automaticamente; abrir pendência supervisionada |
| Lead já recebeu atendimento válido, mas venceu SLA de primeiro contato | Não redistribuir **por atraso de primeiro atendimento**; eventual saída do responsável requer regra distinta de transferência |
| Atendente em conversa ativa perde membership | Revogar acesso, preservar conversa; direcionar handoff conforme política do ATD, sem enviar mensagem por IA automaticamente |
| Profissional com agendamento futuro é desligado | Preservar compromisso e histórico; tratamento de substituição/cancelamento apenas por fluxo AGD e confirmação de disponibilidade |
| Funcionário em equipes Nordeste e Sul perde só uma delas | Reavaliar escopos Nordeste, preservar direitos válidos em Sul, sem conceder visão geral |
| Gestor tenta reatribuir registro de outro tenant ou campo confidencial sem direito | Negar e auditar conforme CORE02/SEG |
| Worker/IA tenta atuar com grant obsoleto após desligamento | Revalidar; negar novo efeito sem autorização |
| Vários candidatos recebem o mesmo objeto simultaneamente | Apenas um resultado confirmado pelo dono do módulo; registrar conflitos e evitar loops |

## 5. Pendências preservadas

- **CORE07-D07 integral permanece ABERTA**, mesmo que o Modelo B seja preliminarmente ratificado. Faltam catálogo por módulo, matriz de atribuições, regras detalhadas de gestor/caixa/unidade/posse, SLA de pendências, destino de fallback, política de exceções, regras físicas de concurrency e provas de homologação.
- Não confundir redistribuição por saída de usuário com a política já aprovada de redistribuição por SLA de atendimento. Não substituir decisões históricas de Vendas, Atendimento ou Agenda.
- CORE07-D04/D05/D06/D08..D10, CORE02-D04..D10, demais CORE01..10, SEG-01/02, FIN/CON, LGPD, INT/G01..G06 e baseline global permanecem abertos.
- Sem SQL, API, rota, job executável, credencial, acesso a usuários reais, mudança de plano, teste físico, compra, deploy ou merge; preservar `main`, VPS, produção e branch Codex `dev/fast-track-crm-2026-11-04`.

## 6. Pergunta para decisão expressa

Aprovar o **Modelo B** apenas como diretriz documental para **CORE07-D07**, preservando decisões existentes por módulo, deixando os aspectos operacionais e a homologação para etapas posteriores.
