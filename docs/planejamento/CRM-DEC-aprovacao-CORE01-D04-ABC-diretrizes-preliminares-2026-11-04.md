# CRM-DEC — Aceite preliminar de CORE01-D04-A, B e C (sem fechamento de CORE01-D04)

**Data:** 04/11/2026.  
**Manifestação literal do responsável:** “Aprovo CORE01-D04-A, B e C como diretrizes técnicas documentais preliminares do CRM Imagine. Registre no GitHub, mantenha CORE01-D04 aberta até fechar suas dependências com CORE02, CORE04, CORE07 e CORE08, preserve todos os demais bloqueios e prossiga com CORE01-D05, sem programação nem testes.”

**Status da decisão:** **CORE01-D04-A/B/C APROVADAS APENAS COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**. **CORE01-D04 PERMANECE ABERTA**. Esta aprovação não é homologação de esquema físico, regra de assento, autoridade real, RLS, convites, MFA, contratos nem encerramento de CORE01-P0.  
**Fonte proposta:** [CORE01-D04 — membership, estados e unicidade v0.1](CRM-CORE-P0-ETAPA2-CORE01-D04-decisao-membership-estados-unicidade-proposta-v0.1.md), e [especificação transversal CORE01-D04 a D07](CRM-CORE-P0-01-D04-D07-especificacao-isolamento-proposta-v0.1.md).

## 1. Deliberação aprovada com alcance restrito

- **CORE01-D04-A — Unicidade de vínculo:** uma membership **lógica e efetiva** por par identidade global × organização CRM (tenant), com histórico de mudanças e readmissão controlada, sem memberships efetivas simultâneas duplicadas, sem vincular dados de tenants diferentes. A representação física da relação e histórico continua sem escolha.
- **CORE01-D04-B — Convite separado de autorização:** convite individual, com **validade de 7 dias** já aprovada anteriormente, é intenção sem direito operacional. O aceite precisa confirmar identidade, destino/organização, papel, quota/assento e MFA conforme contrato atual, antes de ativar membership; impedir duplicação/reutilização. **Não** foi decidido se convites pendentes reservam assentos, nem método de token, concorrência ou formato.
- **CORE01-D04-C — Desligamento por tenant:** suspensão ou desativação de membership impede **novos acessos e efeitos dependentes** naquela organização; identidade global, vínculos válidos com outras empresas, históricos e fatos de negócio oficiais continuam preservados. Reativação requer validação atual de autorização, papel, capacidade e MFA, sem restauração silenciosa de privilégios antigos.

**Distinção indispensável:** a aprovação dessas três diretrizes não aprova automaticamente implementação, modelo físico de dados, tabela de estados completa, política de combinações de papéis, concessão de assentos, recuperação do administrador único, contratos de sessão ou revogação técnica.

## 2. CORE01-D04 — impedimentos que mantêm o item ABERTO

| Dependência | Confirmação a obter em documento especializado |
|---|---|
| **CORE02-D04/D05/D08** | catálogo campos/ações, regras de perfis múltiplos, precedência e negações, versionamento/revogação |
| **CORE04-D05/D07/D10** | MFA/sessões, recuperação do administrador, invalidação no tenant e efeitos em tokens |
| **CORE07-D04/D05/D06/D08** | estados e uso único de convites, reserva/contagem de assento, último administrador, revogação em serviços e jobs |
| **CORE08-D04/D05/D09** | entitlements por plano/tenant, direitos financeiros/benefícios e quota final dos usuários |
| **CORE01-D07/SEG-01/02, CORE03-D06** | unicidade física, integridade cross-tenant, contratos seguros de contexto/RLS em workers e conectores |

**Gate:** CORE01-D04 só poderá evoluir para fechamento mediante conciliação explícita dessas dependências, especificação verificável, responsáveis, critérios documentais de aceitação e nova aprovação do responsável. **Não foi encerrado neste aceite.**

## 3. Demais pendências preservadas sem modificação

CORE01-D05 a D07 continuam abertos; CORE02..CORE10-D04..D10 continuam abertos; IN-168 **SEG-01, SEG-02**, ARQ-01/02/03, AGT-01; INT P0-DOC/TR/OWN/SEC/CON/DEP/VER/ACC/XREF/GIT, G01..G06, FIN/CON, LGPD, preços, custos, licenciamento n8n e aceite global. O inventário da [Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md) continua com **67 IDs abertos** — A/B/C são subdiretrizes preliminares e não reduzem essa contagem.

**Próximo passo autorizado:** apenas **planejamento documental de CORE01-D05 — contexto confiável do tenant e proteção Laravel + PostgreSQL RLS**, com alternativas e riscos, sem aprovar mecanismos físicos por inferência.

**VEDADO:** programação/Codex, scripts, testes reais, SQL/policies em servidor, credenciais, criação/alteração de dados/usuários/contas, infraestrutura, provisionamento, licenças, contratos, despesas, deploy ou implantação.
