# CRM-DEC — Aprovação preliminar CORE01-D07-A, B, C e D — dados, integridade, RLS e contratos

**Data:** 04/11/2026.
**Manifestação literal do responsável:** “Aprovo CORE01-D07-A, B, C e D como diretrizes técnicas documentais preliminares do CRM Imagine. Registre no GitHub, mantenha CORE01-D07 aberta até o fechamento das especificações físicas, contratos e dependências com CORE02, CORE03, CORE04, CORE05, CORE06, CORE07, CORE08, CORE09, SEG-01, SEG-02, INT e Segurança. Preserve CORE01-D04, D05, D06 e todos os demais bloqueios. Prossiga com a consolidação das pendências técnicas da Etapa 2, sem programação, testes ou implantação.”

**Estado:** **CORE01-D07-A/B/C/D APROVADAS EXCLUSIVAMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**. **CORE01-D07 permanece ABERTA** até especificações físicas, contratos versionados e dependências com CORE02, CORE03, CORE04, CORE05, CORE06, CORE07, CORE08, CORE09, SEG-01, SEG-02, INT e Segurança. Sem homologação física ou encerramento de CORE-P0-01.
**Fonte da pauta:** [CORE01-D07 v0.1](CRM-CORE-P0-ETAPA2-CORE01-D07-modelo-fisico-integridade-RLS-proposta-v0.1.md), especialmente seção 6. A aprovação não substitui nem amplia as decisões anteriores de CORE01-D01–D06.

## 1. Diretrizes preliminares aprovadas

1. **CORE01-D07-A — Inventário de entidades e fronteiras:** elaborar dicionário de dados e classificação das famílias **globais protegidas, tenant-bound e de integração**, com proprietário, finalidade, operações/campos, retenção, integridade, auditoria e autoridade de acesso; nenhuma tabela, campo, tipo de chave ou arquitetura física foi selecionada.
2. **CORE01-D07-B — Integridade contextual multiempresa:** garantir coerência entre entidades/foreign keys e tenant, unicidade por contexto de membership e vínculos, prevenção de relações tenant A→B, convites/assentos/benefícios sem duplicidade, histórico e recuperação sem restabelecer privilégios revogados. Alternativas de PK, FK/índices compostos, constraints e transações **continuam por decidir**.
3. **CORE01-D07-C — Contrato Laravel + PostgreSQL RLS:** exigir **matriz documentada** por família, papel/serviço, tenant, ação e operação, controles de leitura/escrita e `USING/WITH CHECK` quando aplicáveis, contexto transacional seguro, ausência de bypass operacional e cobertura de workers, arquivos, buscas, exportações e serviços externos. **Não** aprova SQL, roles de produção, `FORCE ROW LEVEL SECURITY`, `SET LOCAL`, pool ou secrets reais.
4. **CORE01-D07-D — Pacote de contratos e evidências:** documentar mapa ER/dicionário, autorização/RLS/entitlements, envelopes e transições, migração/rollback futuro, backup/retentão/LGPD, casos positivos/negativos, responsáveis, versões/commits e critérios de aceite conforme CORE10/INT/G01..G06. **Nenhum teste ou migração foi executado.**

**Limites:** aprovação de requisitos de projeto e de conteúdo dos artefatos futuros, não de sua adequação técnica concreta, implementação, testes, orçamento, licenciamento, provedor ou acesso de um operador.

## 2. CORE01-D07 e dependências técnicas expressamente ABERTAS

| Dependência | Contrato/decisão física não homologada |
|---|---|
| **CORE02-D04..D10** | Classificação de recursos e campos, combinação de papéis, autorização por ação/campo, alçada, entitlement, revogação/cache, testes futuros |
| **CORE03-D04..D10** | Envelopes/eventos tipados, autoridade causal e técnica, contextos nos workers, transações, filas/retries, reconciliação e confirmação oficial |
| **CORE04-D04..D10** | Autenticação/MFA, sessões, reautenticação e recuperação, impacto do contexto multiempresa |
| **CORE05-D04..D10** | Mandatos excepcionais de suporte por caso/tempo/recurso, sem `BYPASSRLS` irrestrito |
| **CORE06-D04..D10** | Auditoria, categoria de evidências, classificação de dados, retenções, backups, LGPD, restauração |
| **CORE07-D04..D10** | Convite de uso único, unicidade e concorrência de memberships/assentos, administrador único, teams, revogação |
| **CORE08-D04..D10, FIN/CON, Portal/Sites** | Contrato oficial, representação/CNPJ, trial, cortesia, benefícios, assinaturas, destino de lead, estados/aceites |
| **CORE09-D04..D10, SEG-01/SEG-02** | IAM técnico/cofre/segredos, matriz operacional de autorização por serviço×tenant×ferramenta, política RLS real, isolamento/contingência |
| **INT P0-OWN/SEC/CON/ACC etc. e G01..G06** | Fonte de verdade, comandos, eventos/reconciliação, versionamento, homologação futura, evidência e aceite global |
| **IN-168 ARQ-01/02/03, AGT-01 e CORE10-D04..D10** | Topologia, observabilidade, playbooks e critérios de fechamento documental integrados |

**CORE01-D04, D05 e D06 permanecem ABERTAS**, apesar das subdiretrizes preliminares aceitas (D04-A/B/C; D05-A/B/C/D; D06-A/B/C/D). **Nenhuma das 67 decisões técnicas da Etapa 2 foi encerrada**. Bases CORE-P0-01..10 continuam parciais; SEG, INT, FIN/CON, LGPD, licenciamento, custos e demais gates continuam abertos.

## 3. Continuidade documental autorizada

A partir deste aceite, **consolidar as pendências técnicas da Etapa 2** em matriz de contratos compartilhados, dependências, responsáveis por competência, ordem proposta de fechamento, critérios de prova, decisões pendentes e próximos grupos a deliberar. Não presumir aprovação de CORE02-D04..D10 ou de outro bloco, nem implementar ou executar qualquer teste.

**Proibições expressas:** programação/Codex, SQL, migrations, testes reais ou de homologação, configurar RLS de produção, contas/credenciais, infraestrutura, compra/contrato, cobrança, alterações de clientes, APIs, deploy ou implantação. **Estado do projeto: NO-GO para programação.**
