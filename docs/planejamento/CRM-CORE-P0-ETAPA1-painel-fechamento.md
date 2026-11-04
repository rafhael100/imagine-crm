# CRM-CORE-P0 — Painel da Etapa 1 (fechamento do núcleo)

**Atualizado em:** 04/11/2026.  
**Natureza:** painel de acompanhamento DOCUMENTAL, não aprovação de escopo, programação, testes ou contratação.  
**Base aprovada:** [CRM-REV-01](CRM-REV-01-nucleo-identidade-multitenant-permissoes.md) aceita como referência documental e [decisão de aceite](CRM-DEC-aprovacao-REV-01-base.md).  
**Regra:** revisar CORE-P0-01 a CORE-P0-10 em sequência; preservar decisões funcionais, submeter escolhas novas a aceite expresso, registrar versão/precedência. Nenhum P0 deve ser marcado como encerrado por mera criação de minuta.

| Ordem | Lacuna do CRM-REV-01 | Estado 04/11 | Registro / próximo passo |
|---|---|---|---|
| CORE-P0-01 | Modelo canônico identidade/usuário/membership/tenant/organização/RLS | **Em revisão — proposta v0.1 registrada, não aprovada** | [Proposta de arquitetura lógica](CRM-CORE-P0-01-identidade-organizacoes-membership-RLS-proposta-v0.1.md); obter decisão sobre D01–D03; dependências D04–D07 em aberto |
| CORE-P0-02 | Perfis × recursos × ações × campos | **A iniciar** | Conciliar papéis admin Imagine/empresa/equipes/Básico, identidade técnica, suporte e auditoria; fonte INT P0-OWN/SEC |
| CORE-P0-03 | Autoridade em jobs/eventos/webhooks | **A iniciar** | Contratos contexto/tenant/revalidação, idempotência e revogação; integração Filas/Eventos/INT |
| CORE-P0-04 | Autenticação, MFA e recuperação por papel | **A iniciar** | Respeitar MFA admin e política opcional funcionários, sessão 30 min admin central; detalhar step-up/recuperação |
| CORE-P0-05 | Acesso excepcional da Imagine | **A iniciar** | Finalidade, autorização por caso, escopo, expiração e auditoria; sem bypass amplo |
| CORE-P0-06 | Auditoria e retenções | **A iniciar** | Categorias central/tenant/técnica, LGPD e acesso/exportação |
| CORE-P0-07 | Convites, assentos, equipes, desligamento | **A iniciar** | Convite de 7 dias, estados de membership, concorrência, revogação por empresa |
| CORE-P0-08 | Básico cortesia/organizações e trial | **A iniciar** | 1 trial por CNPJ ≠ múltiplas organizações Básico por contratos de site elegíveis; transições |
| CORE-P0-09 | Cofre e identidade de serviço | **A iniciar** | Catálogo de segredos, revogação, rotação e isolamento por agente/ambiente |
| CORE-P0-10 | Pacote de homologação do núcleo | **A iniciar** | Casos negativos, evidências e responsáveis; executar testes somente em fase autorizada |

## Gates preservados

- CORE-P0-01..10 não constituem dez novos módulos; são lacunas de especificação e fechamento de CORE.
- O que está aprovado funcionalmente não deve ser reaberto sem divergência material identificada e referenciada.
- Matriz INT P0, G01–G06, IN-168 (ARQ/SEG/AGT) e aprovação global não ficam automaticamente resolvidos por este painel.
- **Sem autorização para começar o Codex** até o planejamento global reconciliado e aceito em commit/tag final.
