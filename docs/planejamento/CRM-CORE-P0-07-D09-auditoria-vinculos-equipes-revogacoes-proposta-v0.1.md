# CRM Imagine — CORE07-D09: auditoria de convites, vínculos, equipes e revogações — proposta v0.1

**Data:** 06/11/2026 (America/Sao_Paulo).  
**Estado:** **PROPOSTA DOCUMENTAL NÃO APROVADA — CORE07-D09 INTEGRAL ABERTA**.  
**Objetivo:** definir evidências auditáveis de quem iniciou, autorizou e efetivou alterações de participação e responsabilidade no CRM, aproveitando a auditoria central CORE06 e INT/SEG, **sem criar segundo motor de auditoria, executar testes ou definir retenções inventadas**.

## 1. Linha de base e decisões já ratificadas

- CORE07-D01..D03 aprovados como base documental: identidade global e membership por tenant, convites válidos por sete dias, histórico e autoria preservados, quota por organização.
- [CORE07-D04 Modelo B](CRM-DEC-ratificacao-explicita-CORE07-D04-modelo-B-renovacao-2026-11-06.md): renovação manual de convites invalida link anterior e gera prazo de sete dias, sem duas reservas.
- [CORE07-D05 Modelo A](CRM-DEC-ratificacao-explicita-CORE07-D05-modelo-A-reserva-assentos-2026-11-06.md): convite pendente válido reserva uma vaga; aceite confirmado ocupa uma vaga; expiração/cancelamento/recusa libera reserva.
- [CORE07-D06 Modelo A](CRM-DEC-ratificacao-explicita-CORE07-D06-modelo-A-transferencia-admin-2026-11-06.md): substituição atômica e validada do administrador principal, com limite de um administrador autenticado no Básico e MFA.
- [CORE07-D07 Modelo B](CRM-DEC-ratificacao-explicita-CORE07-D07-modelo-B-redistribuicao-hibrida-2026-11-06.md): automação de redistribuição somente sob contrato do módulo proprietário, senão revisão supervisionada.
- [CORE07-D08 Modelo B e D08-A/B/C/D](CRM-DEC-ratificacao-explicita-CORE07-D08-modelo-B-ABCD-2026-11-06.md): efeitos pessoais que dependam de autoridade revogada são bloqueados; rotinas autônomas legítimas podem continuar com fundamento independente.
- CORE02/CORE06, CORE03 e INT possuem catálogos e trilhas próprios. **CORE07 consome esses contratos, não replica suas tabelas de eventos/retenções.**

## 2. Propostas para eventual aceite preliminar — AINDA NÃO APROVADAS

### D09-A — Catálogo único de eventos e causalidade de mudanças

Documentar eventos de: convite criado/enviado/renovado/cancelado/expirado/aceito; reserva de vaga criada, convertida ou liberada; criação, atualização, suspensão, reativação e desligamento de membership; mudança de função/perfil, equipe, gestor, escopo e direitos delegados; transferência de administrador; pendência e conclusão de redistribuição de registros; revogação de sessão/artefato. Cada evento deve relacionar solicitação, autoridade, decisão e efeito oficial, sem declarar operação concluída por envio de e-mail, webhook, HTTP ou job aceito.

### D09-B — Proveniência, autoria e integridade de evidências

Exigir correlação com tenant, identidade/ator técnico, membership ou mandato, ação, proprietário funcional, escopo, identificador de operação, versão/política, instante, estado anterior/posterior e resultado, segundo privacidade; diferenciar quem iniciou, aprovou, executou e confirmou. Tentativa negada também pode gerar trilha proporcional. Preservar a autoria histórica dos leads/conversas mesmo após desligamento ou redistribuição. Proteção contra alteração/remoção não autorizada de registros segue CORE06/SEG.

### D09-C — Acesso, proteção, retenção e direitos do titular

Aplicar princípio de necessidade: não guardar token de convite, segredo, código MFA ou dados confidenciais em claro no log; anonimizar/mascarar informações pessoais quando permitido e guardar evidências exigíveis. Consulta/exportação de trilhas requer permissão específica e finalidade legítima — gestor não recebe leitura global automática, nem administrador de tenant pode apagar provas protegidas. **Retenção, descarte, backups, exportabilidade e incidentes precisam reconciliar com CORE06, LGPD e requisitos contratuais**, sem escolher novos prazos por analogia.

### D09-D — Reconciliação e homologação futura

Planejar verificações transversais para: reenvio de convite revoga token antigo e preserva uma única reserva; aceites simultâneos não duplicam assento; transferência do admin Básico não cria duas vagas; redistribuição VEN/ATD/AGD conserva dono/história; desligamento bloqueia download/export pendente, mas não interrompe rotina autônoma legítima; acesso ao tenant B continua sem vazamento. Cada prova futura deverá registrar dono, critérios, versão/SHA, ambiente, resultado e aceite, sem confundir roteiro com teste realizado ou fechamento do CORE07.

## 3. Exemplos de linhas de auditoria (conceituais)

| Evento | Prova essencial | Não registrar/afirmar |
|---|---|---|
| Convite renovado | Autor competente, convite/tenant, versão, token anterior revogado, prazo atual e vaga reservada válida | Token em claro, entrega de e-mail como aceite |
| Convite aceito | Identidade legitimada, tenant, política MFA, reserva convertida uma vez e membership ativada | Acesso a outro tenant ou assinatura comprada automaticamente |
| Admin transferido | Autoridades, identidade do sucessor, MFA, revogação anterior e resultado atômico | Duas contas administrativas Básico simultâneas |
| Funcionário desligado | Tenant, instante, escopo revogado, status e efeitos reavaliados | Exclusão da identidade global, eliminação do histórico |
| Lead redistribuído | Evento causal e resultado do VEN ou revisão humana competente, responsável anterior/novo, autoria preservada | Execução por um motor CORE07 universal |
| Export solicitado pelo ex-funcionário | Revalidação de autorização antes de disponibilização, recusa segura e origem da regra | Link público permanente ou dado sensível no log |

## 4. Pendências que permanecem abertas

- **CORE07-D09 integral ABERTA** até catalogar eventos oficiais por proprietário, campos retidos, fonte/versão/competência, controles reais de consulta e exportação, prazos de retenção legalmente fundamentados, armazenamento íntegro, reconciliação e testes.
- **CORE07-D04..D08/D10 integrais ABERTAS**, assim como CORE01..10 pendentes, os 67 IDs técnicos CORE, SEG-01/02, ARQ/AGT, INT P0/G01..G06, FIN/CON, LGPD e gate global. Aceites preliminares de D04..D08 não encerram o bloco.
- **Nenhuma programação/execução**: não criar motor novo, banco/schema, alterar acesso real, executar testes físicos, mudar CI, `main`, branch Codex, VPS, produção, nem iniciar compra/contratação.

## 5. Próxima decisão expressa solicitada

Se o responsável concordar, ratificar **CORE07-D09-A/B/C/D apenas como diretrizes documentais preliminares**, preservando CORE07-D09 integral aberta. Em seguida preparar CORE07-D10, que consolidará dependências, roteiros e condições de homologação do bloco sem fechamento automático.
