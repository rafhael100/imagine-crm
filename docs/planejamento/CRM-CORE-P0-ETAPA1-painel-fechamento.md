# CRM-CORE-P0 — Painel da Etapa 1 (fechamento do núcleo)

**Atualizado em:** 04/11/2026.  
**Natureza:** painel de acompanhamento DOCUMENTAL, não aprovação de escopo, programação, testes ou contratação.  
**Base aprovada:** [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) aceita como referência documental e [decisão de aceite](CRM-DEC-aprovacao-REV-01-base.md).  
**Regra:** revisar CORE-P0-01 a CORE-P0-10 em sequência; preservar decisões funcionais, submeter escolhas novas a aceite expresso, registrar versão/precedência. Nenhum P0 deve ser marcado como encerrado por mera criação de minuta.

| Ordem | Lacuna do CRM-REV-01 | Estado 04/11 | Registro / próximo passo |
|---|---|---|---|
| CORE-P0-01 | Modelo canônico identidade/usuário/membership/tenant/organização/RLS | **Base lógica D01–D03 APROVADA; P0 ainda PARCIAL** | [CRM-DEC D01–D03](CRM-DEC-aprovacao-CORE-P0-01-D01-D03-base-logica-2026-11-04.md); [modelo lógico](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md); D04–D07 técnicos abertos |
| CORE-P0-02 | Perfis × recursos × ações × campos | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aprovação](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md); [proposta de matriz](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md); D04–D10 pendentes, INT P0-OWN/SEC parcial |
| CORE-P0-03 | Autoridade em jobs/eventos/webhooks | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aprovação](CRM-DEC-aprovacao-CORE-P0-03-D01-D03-base-operacoes-assincronas-2026-11-04.md); [modelo de operação](CRM-CORE-P0-03-autoridade-contexto-jobs-eventos-webhooks-v0.1.md); D04–D10 técnicos abertos |
| CORE-P0-04 | Autenticação, MFA e recuperação por papel | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aprovação](CRM-DEC-aprovacao-CORE-P0-04-D01-D03-base-autenticacao-2026-11-04.md); [minuta](CRM-CORE-P0-04-autenticacao-MFA-recuperacao-sessoes-proposta-v0.1.md); D04–D10 em aberto, prazo de 30 min somente central |
| CORE-P0-05 | Acesso excepcional da Imagine | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aceite](CRM-DEC-aprovacao-CORE-P0-05-D01-D03-base-suporte-excepcional-2026-11-04.md); [proposta](CRM-CORE-P0-05-acesso-excepcional-imagine-mandatos-suporte-proposta-v0.1.md); D04–D10 pendentes, nenhum grant real |
| CORE-P0-06 | Auditoria e retenções | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aceite](CRM-DEC-aprovacao-CORE-P0-06-D01-D03-base-auditoria-2026-11-04.md); [base documental](CRM-CORE-P0-06-auditoria-categorias-retencao-acesso-proposta-v0.1.md); D04–D10 abertos, prazos já aprovados preservados |
| CORE-P0-07 | Convites, assentos, equipes, desligamento | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aceite](CRM-DEC-aprovacao-CORE-P0-07-D01-D03-base-convites-assentos-equipes-2026-11-04.md); [modelo lógico](CRM-CORE-P0-07-convites-assentos-equipes-desligamento-proposta-v0.1.md); D04–D10 técnicos abertos, sem convites ou assentos reais |
| CORE-P0-08 | Básico público/cortesia, organizações e trial | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aceite](CRM-DEC-aprovacao-CORE-P0-08-D01-D03-base-basico-trial-2026-11-04.md); [proposta atualizada](CRM-CORE-P0-08-basico-publico-cortesia-organizacoes-trial-proposta-v0.1.md); D04–D10 abertos, sem criação de contas ou benefícios reais |
| CORE-P0-09 | Cofre e identidade de serviço | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aceite](CRM-DEC-aprovacao-CORE-P0-09-D01-D03-base-cofre-identidades-2026-11-04.md); [modelo lógico](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md); D04–D10 abertos, IN-168 SEG-01/02 não encerrados |
| CORE-P0-10 | Pacote de homologação documental do núcleo | **Base D01–D03 APROVADA; P0 PARCIAL/ABERTO** | [CRM-DEC de aceite](CRM-DEC-aprovacao-CORE-P0-10-D01-D03-base-homologacao-2026-11-04.md); [base e critérios](CRM-CORE-P0-10-pacote-homologacao-documental-nucleo-proposta-v0.1.md); D04–D10 ABERTAS, testes NÃO EXECUTADOS, G01–G06 abertos |


## Continuidade — Etapa 2 de fechamento documental (04/11/2026)

**Situação:** as 10 bases CORE D01–D03 foram aceitas, mas **todos os 10 blocos CORE-P0 continuam PARCIAIS/ABERTOS**. Para a Etapa 2 foi criada a [matriz das pendências e dependências](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md): **67 IDs pendentes** (CORE01 D04..D07 e CORE02..10 D04..D10), alguns sobrepostos aos mesmos bloqueios transversais; não converter contagem em riscos independentes nem fechar itens por criação de minuta.

**Primeiro aprofundamento documental (não aprovado):** [CORE01-D04..D07 — proposta de isolamento, memberships, tenant/RLS e vínculos](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md). Os quatro IDs **continuam abertos** e requerem decisões futuras explícitas. Preservar **SEG-01, SEG-02**, ARQ-01/02/03, AGT-01, INT P0, FIN/CON, LGPD, licenças e G01–G06. **Nenhuma programação, teste executável, infraestrutura, credencial ou compra autorizada**.


## Etapa 2 — Diretrizes preliminares CORE01-D04 e CORE01-D05 (04/11/2026)

- [**CRM-DEC CORE01-D04-A/B/C**](CRM-DEC-aprovacao-CORE01-D04-ABC-diretrizes-preliminares-2026-11-04.md): aceita como **diretriz técnica documental preliminar**, **não** como fechamento integral de CORE01-D04, que continua dependente de CORE02, CORE04, CORE07 e CORE08.
- [**CORE01-D05 — contexto confiável e RLS**](CRM-CORE-P0-ETAPA2-CORE01-D05-contexto-tenant-RLS-proposta-v0.1.md): D05-A/B/C/D [**aceitas somente preliminarmente**](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md); D05 permanece ABERTA, e mecanismos de banco, roles, transações, workers e suporte não homologados.
- [Matriz Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md): **67 identificadores técnicos permanecem abertos**. IN-168 SEG-01/SEG-02, ARQ-01/02/03, AGT-01, INT P0 e G01..G06 também não foram encerrados. **Sem programação, testes ou implantação**.

## Gates preservados

- CORE-P0-01..10 não constituem dez novos módulos; são lacunas de especificação e fechamento de CORE.
- O que está aprovado funcionalmente não deve ser reaberto sem divergência material identificada e referenciada.
- Matriz INT P0, G01–G06, IN-168 (ARQ/SEG/AGT) e aprovação global não ficam automaticamente resolvidos por este painel.
- **Sem autorização para começar o Codex** até o planejamento global reconciliado e aceito em commit/tag final.

## Etapa 2 — CORE01-D05 preliminar aprovado, CORE01-D06 em proposta (04/11/2026)

- [**Aceite CORE01-D05-A/B/C/D**](CRM-DEC-aprovacao-CORE01-D05-ABCD-diretrizes-preliminares-2026-11-04.md): diretrizes documentais preliminares aceitas, **CORE01-D05 integralmente ABERTA**; CORE01-D04 igualmente aberta e CORE01-D07 não aprovada.
- [**CORE01-D06 — proposta canônica de ciclos de tenant, vínculo Site↔CRM, cortesia, trial e contratos**](CRM-CORE-P0-ETAPA2-CORE01-D06-ciclo-organizacoes-vinculos-comerciais-proposta-v0.1.md): diretrizes candidatas A/B/C/D **NÃO APROVADAS**. [Matriz Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) mantida.
- **CORE01-D04..D07, CORE02..CORE10-D04..D10 e 67 decisões pendentes não foram encerradas**. IN-168 **SEG-01 e SEG-02**, ARQ-01/02/03, AGT-01, INT P0 e G01..G06 continuam abertos. Proibidos programação, testes, credenciais, operações reais, contratos e despesas.

