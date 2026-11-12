# CRM F3-130 — Pontuação de leads por regras, sem IA

**Data:** 11/11/2026 · **Escopo:** somente Core PHP e testes em DEV · **Rastreabilidade:** CRM-REV-06 VEN-R28/R29/R30, VEN-P08.

## Implementação

`SyntheticLeadRuleScorePreview` calcula um **score de laboratório** de 0 a 100, a partir de cinco sinais estritamente booleanos e de cinco pesos inteiros fornecidos pelo chamador. Os códigos são `profile_complete`, `recent_engagement`, `requested_quote`, `scheduled_demo` e `stale_response`. Nenhum sinal traz nome, e-mail, telefone, identificador, conversa, evento real ou dado pessoal; os pesos de -100 a +100 são **inteiramente ilustrativos**, não homologados.

O método soma o valor base de 0..100 e os pesos somente dos sinais `true`, separa pontos positivos e negativos, e **limita o resultado final ao intervalo [0, 100]**. A classificação reutiliza `LeadScoringBands` já presente no CRM, com faixas iniciais frio 0–39, morno 40–69, quente 70–100; limites customizáveis podem ser passados somente como exemplo. O resultado relata pontuação bruta, pontuação limitada, faixa e total de sinais ativos, **sem valores unitários de evidência**.

A aplicação é determinística e não usa IA, processamento de mensagens, texto de cliente, serviços externos ou custo de créditos.

## Testes e composição

`SyntheticLeadRuleScorePreviewTest` cobre soma exata 65 (morno), limite superior e inferior, política de faixas alternativa, tipos e chaves inesperadas, pesos acima dos limites, dados pessoais, saída fechada e integração de um score 65→80 com o componente existente `SyntheticScoreTrendPreview` (morno→quente, mudança de faixa = 1). A integração entre módulos também mantém a execução automática de ações sempre desabilitada.

## Segurança e decisão pendente

**Não são métricas ou comportamentos reais de leads.** A palavra `complete` e a flag de qualquer sinal são alegações fictícias, sem origem auditada. `signals_verified`, `weight_policy_verified`, `band_policy_verified`, `authorized`, `can_update_lead`, `can_trigger_automation`, `can_redistribute`, `can_notify` e `can_persist` são sempre `false`.

A configuração real dos sinais, fontes, pesos, faixas, recalibração e ações depende de VEN-P08 e CORE/SEG/DBA/LGPD. Não há API, SQL, alteração de lead, n8n, automação, IA paga, dados reais, custo, VPS, `main` ou produção.
