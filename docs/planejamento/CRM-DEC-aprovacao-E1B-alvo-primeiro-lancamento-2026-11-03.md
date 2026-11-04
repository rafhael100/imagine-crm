# CRM-DEC — E1B como arquitetura-alvo do primeiro lançamento do CRM Imagine

**Data:** 03/11/2026.
**Manifestação do responsável:** “ok” em resposta à pergunta expressa “Você aprova o E1B como nosso modelo inicial de referência, sujeito à homologação?”.
**Estado:** **DECISÃO DE DIREÇÃO ARQUITETURAL APROVADA — NÃO HOMOLOGADA PARA PRODUÇÃO.**

## Deliberação

- Aprovar **E1B — produção econômica reforçada como ARQUITETURA-ALVO para o primeiro lançamento** do CRM Imagine, respeitando o orçamento inicial reduzido e a estratégia de gastos progressivos.
- E1B inclui, como desenho candidato: backend Laravel em servidor/VPS exclusivo da aplicação CRM; PostgreSQL em instância independente; arquivos/anexos privados em Object Storage; backup do banco externo com capacidade PITR a homologar; segunda cópia dos anexos em domínio de falha independente; monitoramento externo e procedimentos documentados de recuperação e reposição.
- A **Leaseweb é fornecedora prioritária para estudo/cotação**, não contratada. VPS 2 para Laravel e outra VPS para banco seguem hipóteses dimensionáveis; Cloudflare na camada de proteção; Cloudflare R2 Standard para anexos e Leaseweb Object Storage On Demand para réplica são candidatos, não ferramentas definitivamente homologadas.
- **Se E1B não atender aos critérios de segurança, durabilidade, disponibilidade ou recuperação exigidos na homologação, avaliar/elevar para E2 antes de receber clientes reais.** A direção E1B não autoriza relaxar requisitos.
- **RPO 15 minutos e RTO 4 horas continuam apenas metas candidatas**, sem garantia técnica, SLA, cronograma ou aprovação numérica.
- O modelo E1B pode conter **pontos únicos de falha** na aplicação e no banco. O aceite concreto desses riscos antes de produção continua pendente, acompanhado de restauração comprovável e contratos de incidentes.
- Manter independência Portal Imagine, CRM, Sites, n8n, agentes, APIs de captação principal/contingência com persistência própria, monitoramento e autorizações já aprovadas.
- Manter **zero contratação nova no planejamento**; contratação, implantação e programação dependem de autorizações posteriores. 
- **F07-D01 fica decidido no nível de arquitetura-alvo:** E1B em preferência a E2 no primeiro lançamento, com cláusula de revisão obrigatória se os critérios de homologação falharem. **F07-D02 (orçamento), D03 (região), D04 (metas condicionais e aceitação de riscos) continuam pendentes.**

## Referências e não efeitos

- [ARQ01-F07 — Ficha de fechamento e pendências](P0-ARQ01-F07-fechamento-orcamento-riscos-e-recuperacao.md).
- [ARQ01-F06 — Modelos E0/E1A/E1B/E2](P0-ARQ01-F06-proposta-metas-rpo-rto-custos-e-riscos.md).
- [P0-ARQ-01 — Contratos de arquitetura e fechamento](P0-ARQ-01-topologia-fronteiras-plano-fechamento.md).
- [IN-168 — Pendências e dependências](IN-168-matriz-pendencias-fechamento-arquitetura.md).

**ARQ-01 permanece P0 parcial; CON-01 e outros gates P0 seguem abertos.** Nenhuma programação, contratação, provisionamento, teste real ou alteração de infraestrutura autorizada.