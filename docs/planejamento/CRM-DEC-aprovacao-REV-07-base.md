# CRM-DEC — Aprovação explícita da CRM-REV-07 como base documental

**Data:** 2026-11-03. **Manifestação literal:** “ok, aprovado”.
**Objeto:** [CRM-REV-07 — Agenda, Profissionais, Unidades e Agendamentos](CRM-REV-07-agenda-profissionais-unidades-agendamentos.md), recomendada como **base documental de revisão**, preservando 42 regras funcionais, seis exclusões/rejeições e 18 pendências AGD-P01..18.

## Escopo aprovado

- CRM-REV-07 aceita **como base documental**, sem homologar schema, APIs, provedores, preços, políticas finais de calendário ou parâmetros físicos.
- Permanecem as diferenças entre **Agenda Básico manual/interna** e autoagendamento dos planos elegíveis, além de limites atuais de 1 profissional, 10 serviços ativos, 1 usuário autenticado e ausência de teto comercial de agendamentos manuais do Básico.
- Preservar as seis restrições expressas: **não** processar pagamentos por serviços agendados, **não** permitir check-in autônomo do cliente, **não** criar prioridade manual da lista FIFO, **não** criar favoritos, **não** limitar numericamente reservas futuras do cliente e **não** encerrar atendimentos automaticamente apenas pelo horário previsto decorrido.
- **AGD-P01..18 e AGD-D01..16 permanecem abertos**, assim como CORE-P0, INT-P0, IN-168 e G01–G06.

## Nova decisão funcional independente na mesma manifestação

O responsável também determina que o **administrador da própria Imagine** possa habilitar **gratuitamente qualquer plano do CRM para uma empresa cliente específica**, inclusive Essencial, Profissional ou Empresarial. Essa é uma **decisão funcional nova e expressa**, registrada em [CRM-DEC — gratuidade administrativa por empresa e plano](CRM-DEC-gratuidade-administrativa-qualquer-plano.md). A nova regra não se limita à cortesia Básico por Site e não altera automaticamente a política de cobrança geral.

## Limites

A aprovação desta revisão e da nova capacidade **não autoriza** iniciar programação, efetivar concessões em contas reais, criar faturamento, testes, automações, cobranças ou alterar sistemas em produção. Finalizar o planejamento no GitHub e obter aprovação global antes da implementação com Codex.
