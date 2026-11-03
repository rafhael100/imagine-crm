# CRM-DEC — Aceite da ARQ01-F05 como base documental

**Data:** 03/11/2026.
**Manifestação do responsável:** “ok”, após apresentação da proposta ARQ01-F05 e indicação de que a próxima decisão seria revisar F05 como base documental e aprofundar metas/custos de continuidade.
**Status:** **APROVADA SOMENTE COMO BASE DOCUMENTAL.**

## Conteúdo aceito

- Aceita a [ARQ01-F05](P0-ARQ01-F05-continuidade-rpo-rto-e-custos-por-servico.md) como ferramenta de comparação dos níveis E0 (desenvolvimento), E1 (produção econômica recuperável, ainda sem HA automática) e E2 (produção redundante).
- Conservam-se os requisitos de separação CRM/Portal/n8n/agentes e APIs de leads principal/contingência independentes, R2/Leaseweb Object Storage e PostgreSQL/PITR como candidatos de estudo.
- RPO de 15 minutos e RTO de 4 horas para PostgreSQL **continuam somente hipóteses**, não metas aprovadas, medidas, SLA ou promessa contratual. Periodicidade de 15 min de cópia de anexos não comprova RPO real.
- **Não aprovado ainda:** E1 como arquitetura definitiva para clientes reais; RPO/RTO por serviço, grau de redundância, aceite dos pontos únicos de falha, contrato de recuperação, equipe de plantão, provedores, preços, compras ou execução.
- Seguem pendentes F05-P01..P07, ARQ-01, CON-01, ARQ-02/03, SEG-01/02, AGT-01 e demais bloqueios. A [ARQ01-F06](P0-ARQ01-F06-proposta-metas-rpo-rto-custos-e-riscos.md) é apenas nova proposta para avaliação.

**Regra:** planejamento, documentação e revisão no GitHub apenas; nenhuma programação, contratação, provisionamento, instalação ou teste real autorizado.
