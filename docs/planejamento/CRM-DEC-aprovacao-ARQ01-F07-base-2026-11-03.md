# CRM-DEC — Aceite da ARQ01-F07 como base documental

**Data:** 03/11/2026.
**Manifestação do responsável:** “ok”, em resposta à ARQ01-F07 apresentada como minuta para revisão.
**Estado:** **APROVADA COMO GUIA DOCUMENTAL DE FECHAMENTO — NÃO HOMOLOGADA.**

A [ARQ01-F07](P0-ARQ01-F07-fechamento-orcamento-riscos-e-recuperacao.md) foi aceita como consolidação das etapas F01–F06, matriz de riscos e pauta objetiva para fechar o planejamento da infraestrutura do CRM Imagine.

## Alcance do aceite

- Preserva a estratégia aprovada de custo progressivo: sem novas contratações durante planejamento; Leaseweb como prioridade de estudo, VPS 1 para desenvolvimento futuro e VPS 2 para backend de produção como hipóteses, com PostgreSQL separado.
- Preserva Cloudflare na borda, anexos em armazenamento privado independente, cópia de segurança externa, banco com backup/recuperação pontual a avaliar e separação Portal/CRM/n8n/agentes/APIs de leads.
- **E1B permanece candidato preferencial de análise**, não arquitetura final escolhida; E2 deve ser comparado se o E1B não atender às exigências de recuperação/risco.
- **RPO 15 min e RTO 4 h não foram homologados**, medidos ou transformados em SLA.
- Continuam pendentes: F07-D01 (E1B versus E2), D02 (limite de orçamento mensal do CRM e ecossistema), D03 (região depois de cotação/latência), D04 (metas condicionais e aceite de riscos). A aprovação de F07 como guia atende apenas ao alcance documental de D05.
- **ARQ-01 permanece P0 parcial**. Também permanecem CON-01, ARQ-02/03, SEG-01/02, AGT-01 e demais critérios de prontidão para desenvolvimento.

## Encaminhamento

Concentrar futuras deliberações na própria [F07](P0-ARQ01-F07-fechamento-orcamento-riscos-e-recuperacao.md), acrescentando as decisões concretas e cotações quando disponíveis. **Não abrir automaticamente F08/F09** para reapresentar a mesma matriz.

**Não autorizado:** contratação, provisionamento, acesso administrativo a servidores, programação, testes reais, migração ou mudança de infraestrutura. A liberação do desenvolvimento exige a aprovação global e expressa da documentação final.
