# CRM F3-112 — Evolução descritiva do lead scoring

**Data:** 11/11/2026 · **Escopo:** código PHP puro e testes na DEV · **Referência:** CRM-REV-06 VEN-R28/R29/R30

## Entrega técnica

`SyntheticScoreTrendPreview` recebe de 0 a 16 amostras **fictícias** de pontuação 0–100 associadas a instantes UTC estritos, com formato `YYYY-MM-DDTHH:mm:ssZ`. Valida sequenciamento crescente, unicidade temporal, campos e tipos fechados, além de datas calendáricas reais. Qualquer score ou data inválida, repetição, inversão, campo adicional ou identidade causa recusa sem refletir dados.

O resumo retorna apenas total de amostras, pontuações inicial/final, variação final-inicial, menor/maior score, faixas iniciais/finais (frio/morno/quente), quantas evoluções subiram/caíram/ficaram estáveis e quantas vezes a faixa mudou. Integra o classificador `LeadScoringBands` já existente, aceitando seus limites ilustrativos opcionais sem alegar que a política do tenant foi aprovada.

Nenhum texto de evento, horário original, ID, lead, contato, vendedor, empresa ou origem é retornado. `history_verified=false`, `band_policy_verified=false`, `authorized=false` e as capacidades de reclassificar, notificar, automatizar e gravar são todas `false`.

## Evidência executada em CI

`SyntheticScoreTrendPreviewTest` cobre seis registros com duas altas, duas quedas, uma estabilidade e três mudanças de faixa; lista vazia; limites 0/100; faixas personalizadas; formatos UTC não canônicos, datas inválidas, eventos fora de ordem/duplicados, mais de 16, tipos incorretos, anexação de dados pessoais e esquema de retorno sem identificação.

**Não é histórico auditável nem scoring real:** as amostras são controladas pelo laboratório e não provam eventos de um lead ou política de score por tenant. Para VEN-R30 operacional são necessários governança de regras, origem verificável, eventos persistidos, autorização por empresa, direito por campo e auditoria. CORE/SEG/DBA/LGPD continuam NO-GO.

Não cria API, job, scheduler, n8n, email, WhatsApp, autorização, SQL, despesas, VPS, `main` ou produção.
