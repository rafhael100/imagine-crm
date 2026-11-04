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
| CORE-P0-09 | Cofre e identidade de serviço | **Proposta v0.1 registrada; D01–D03 aguardam aceite; P0 ABERTO** | [CORE-P0-09 — cofre, segredos, identidades e ferramentas](CRM-CORE-P0-09-cofre-segredos-identidades-servico-proposta-v0.1.md); D04–D10 abertos, IN-168 SEG-01/02 não encerrados |
| CORE-P0-10 | Pacote de homologação do núcleo | **A iniciar** | Casos negativos, evidências e responsáveis; executar testes somente em fase autorizada |

## Gates preservados

- CORE-P0-01..10 não constituem dez novos módulos; são lacunas de especificação e fechamento de CORE.
- O que está aprovado funcionalmente não deve ser reaberto sem divergência material identificada e referenciada.
- Matriz INT P0, G01–G06, IN-168 (ARQ/SEG/AGT) e aprovação global não ficam automaticamente resolvidos por este painel.
- **Sem autorização para começar o Codex** até o planejamento global reconciliado e aceito em commit/tag final.
