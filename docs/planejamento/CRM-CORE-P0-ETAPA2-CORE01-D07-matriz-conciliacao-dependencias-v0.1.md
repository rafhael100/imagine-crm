# CRM Imagine — CORE01-D07: Matriz de conciliação e critérios de aceite documental (v0.1)

**Data:** 04/11/2026.  
**Estado:** **ANEXO DE REVISÃO DOCUMENTAL, NÃO APROVADO, NÃO DELIBERATIVO.** O documento canônico de pauta continua sendo [CORE01-D07 — modelo físico, integridade e RLS (v0.1)](CRM-CORE-P0-ETAPA2-CORE01-D07-modelo-fisico-integridade-RLS-proposta-v0.1.md).  
**Autorização:** o “ok” do responsável deu continuidade à organização documental, mas **NÃO** aprovou CORE01-D07-A/B/C/D, o checklist DEV-GATE-00 ou o GO para programação.  
**Vedações:** sem Codex, código, SQL, migrations, credenciais, banco de teste, casos executados, operações sobre dados reais, alterações de infraestrutura, contratação, cobrança ou implantação.

## 1. Escopo de conciliação — quatro diretrizes ainda pendentes

| Código | Proposta canônica a avaliar, SEM aprovação nesta matriz | Contrato/evidência documental a produzir no fechamento | Dependência que não pode ser marcada resolvida |
|---|---|---|---|
| **CORE01-D07-A** | Inventário de entidades globais, tenant-bound e de integração; dicionário por proprietário/finalidade | Matriz entidade×dono×tenancy×sensibilidade×operação×retenção×fonte oficial; exceções globais explícitas | CORE01-D04/D06, CORE02-D04, CORE06, CORE08, FIN/CON, LGPD |
| **CORE01-D07-B** | Integridade relacional e unicidade multiempresa; sem vínculos cross-tenant indevidos | Cardinalidades, chaves candidatas, relações entre tenant e entidade, integridade em gravação/restore, exceções, histórico e efeitos de concorrência documentados | CORE01-D04/D06, CORE07-D04/D05/D06, CORE03-D07, CORE08/INT |
| **CORE01-D07-C** | Matrizes coerentes de autorizador Laravel + RLS PostgreSQL por classe/role/ação | Fronteiras request/worker/service, direitos por objeto/campo, WITH CHECK/USING, role ownership/bypass, pool/transação/reset, exportação/arquivos e falha segura (sem SQL escolhido) | CORE01-D05, CORE02-D05/D06/D08, CORE03-D05/D06, CORE04, CORE05, CORE09, SEG-01/SEG-02 |
| **CORE01-D07-D** | Contratos de versionamento/evolução, auditoria, restauração e critérios de aceitação futura | Matriz fonte×operação×versão, backward compatibility, restore sem ressuscitar grants, evidências por proprietário, referência a casos canônicos não executados | CORE03/06/09/10, INT P0-CON/SEC/ACC/VER, ARQ-02/03, FIN/CON, G01..G06 |

**Importante:** aprovar A–D futuramente aceitaria somente a necessidade e o formato de completar esses artefatos; **não** decidiria nomes de tabela, PK/FK física, SQL de RLS, grants, token, provedor/cofre, pooler ou tecnologia extra, que exigem decisão e evidência próprias.

## 2. Inventário de classes a harmonizar — SEM definições físicas

| Família | Dono funcional lógico | Regra de isolamento a manter | Revisão crítica |
|---|---|---|---|
| Identidade e fatores de MFA | IAM/Segurança | Global protegida; autenticação ≠ direito de acesso a tenant | CORE04-D04..D10 |
| Organização/tenant, representação e membership | IAM/CORE, Contratos quando titularidade | Tenant isolado; vínculo por identidade×tenant; CNPJ não une dados | CORE01-D04/D06, CORE07 |
| Papéis, escopos, equipes e convites | IAM + proprietários dos módulos | Direitos por tenant, papel, ação, recurso, campo e entitlement; convite não é grant | CORE02-D04..D10, CORE07-D04..D10 |
| Leads, contatos, funis e atendimento | DAT/VEN/ATD | Dados tenant-bound inclusive relatórios, busca, export e anexos | REV03/DAT, CORE02 |
| Agenda e recursos do módulo | AGD | Profissional agendável não se confunde com assento de login | CORE07/08 e AGD |
| Arquivos, backup e import/export | IN-118, DAT/Segurança/Auditoria | Identificação tenant e permissão em operação e entrega; não mover dados por site novo | CORE02/06, LGPD |
| Sites, formulários e vínculo CRM | Portal/Sites + CRM/INT | Destino oficial validado; página estática sem CRM não capta; lead pendente não redirecionado implicitamente | IN127-R01..R06, CORE03/08, INT |
| Entitlements, trial, assinaturas e benefícios | FIN/CON/CORE08 | Estado financeiro separado de tenant, membership e contrato Site; cortesia por organização válida | REV02, CORE08-D04..D10, GRAT-P |
| Jobs, agentes, integrações e segredos | CORE03/09, INT e Segurança | Principal técnico e ator causal distintos; autorização por operação/ambiente/tenant | SEG-01/02, ARQ-02/03, AGT-01 |
| Auditoria e evidências | Auditoria Geral/CORE06 | Categoria, finalidade, retenção, ocultação de segredos e controle de acesso próprio | CORE06, INT-12, LGPD |

Matriz de classes **não aprova tabela nem privilégio**. A lista por campo, política final de retenção e dono específico de cada operação seguem pendentes.

## 3. Questões de decisão ainda necessárias por fronteira

| Fronteira | Decisão não resolvida | Obrigação de evidência no fechamento |
|---|---|---|
| Entidade global × tenant | Campos e relacionamentos realmente globais vs escopados | Exceção e justificativa por entidade, com owner |
| Membership × convite × assento | Unicidade física, concorrência no último assento, readmissão e troca de admin | Contratos CORE07-D04/05/06 com regra de estado e auditoria |
| Laravel × PostgreSQL | Contexto de tenant autenticamente resolvido, papéis de DB, owner/BYPASSRLS, funções privilegiadas, USING/WITH CHECK | Matriz por família, recurso e operação; riscos e falha segura |
| Pool/transações/jobs | Propagação e término de contexto A↔B, transações aninhadas/retry/rollback e revalidação | Especificação CORE03-D06, CORE01-D05, SEG |
| Arquivos/exportações | Autorização no início e antes da entrega, revogação de URL/token, dados temporários | Contrato de arquivos, download, auditoria e retenção |
| Backup/restore/migração | Não reativar acesso/segredo revogado, nem juntar tenants no restore/import | Critérios de integridade, rollback, responsabilização |
| Site↔CRM/FIN | Entitlement e destino autorizados; mudança não migra históricos; CRM pago independente | Contratos FIN/CON, CORE08, INT, IN-127 |
| Agentes/serviço/suporte | Autoridade independente do modelo e do n8n, operação tipada, cofre, mandato de suporte | SEG-01/02, CORE05/09, INT OWN/SEC |
| Matriz de homologação futura | Casos canônicos e dados sintéticos planejados, responsáveis, NO-GO | CORE10, INT P0-ACC/G01..G06; execução somente pós-autorização |

## 4. Casos adversos para rastreabilidade de critérios futuros — NÃO EXECUTADOS

| Risco | Resultado esperado em homologação futura | Fonte primária de decisão |
|---|---|---|
| Usuário do tenant A conhece ID de objeto de B | Não há visibilidade/efeito em B por API, RLS, export, arquivo ou busca | CORE01/02 |
| Papel com leitura, mas sem exportação | Exportação deve ser negada mesmo quando leitura normal é permitida | CORE02 |
| Conexão do pool usada em A e depois B | Não herdar role, tenant nem cache de A | CORE01-D05, CORE03-D06 |
| Job é retomado após revogação de membership | Novos efeitos dependentes de poder pessoal são revalidados/negados | CORE03/04/07 |
| FK tenta relacionar entidade de A ao tenant B | Integridade impede associação indevida sem exposição de dados sensíveis | CORE01-D07-B |
| Serviço de IA/n8n tenta ler tenant por prompt | Negar sem principal/ação/tenant/mandato, com trilha | SEG-01/02, CORE09 |
| Restore com grants revogados no snapshot antigo | Não reativar concessões/segredos sem autoridade atual | CORE06/09 |
| Dois Sites do mesmo CNPJ com CRM distintos | Dados, usuários, quotas, trials e cortesia seguem regras próprias | CORE01-D06, CORE08 |
| Site estático sem destino CRM autorizado | Captação bloqueada no backend, inclusive cache | IN127-R06 |
| Webhook ou ACK de provedor afirma sucesso comercial | Resultado não confirmado até fonte oficial do proprietário | INT CT03/04, CORE03 |

**Os casos são referências de risco para conciliar CORE01-T, REV01-S, OPS-T e INT HT.** Não são execução, aprovação de teste, nova suíte ou indicador PASS.

## 5. Critérios para fechamento documental posterior de CORE01-D07

Uma deliberação preliminar sobre D07-A/B/C/D **não significa fechamento**. A conclusão integral de D07 exigirá, no mínimo:

- Fontes canônicas de atributos, entidades e responsabilidade por dados e campos validadas.
- Uma alternativa **física e implementável** escolhida para chaves, FKs/constraints, isolamento RLS por papel/ação, conexão e worker, sem proibição indevida de serviços legítimos.
- Regras de segurança de backend e banco compatíveis com CORE02/03/04/05/07/09 e IN-168 SEG-01/02, inclusive falhas e exceções.
- Versionamento, migrações e restauração documentados com riscos/reversão e LGPD.
- Critérios de aceitação futura por requisito, evidências e responsáveis, separados de testes executados.
- Análise e aceite explícitos do responsável sobre dependências e bloqueios restantes, conforme [DEV-GATE-00](CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md).

**Não criar código/migrations, testar SQL/RLS, provisionar banco ou fixar parâmetros sem autorização própria.**

## 6. Pauta recomendada para próxima manifestação

Submeter ao responsável a **proposta original CORE01-D07-A/B/C/D como diretrizes técnicas documentais preliminares**, nos significados da [proposta canônica](CRM-CORE-P0-ETAPA2-CORE01-D07-modelo-fisico-integridade-RLS-proposta-v0.1.md), com aceitação **expressa e restrita**. Não reinterpretar “ok” como voto.

Após eventual aprovação, manter **CORE01-D04/D05/D06/D07 ABERTAS**, todos os **67 IDs CORE**, **SEG-01/02**, ARQ-01/02/03, AGT-01, INT P0, G01..G06, FIN/CON, revisão comercial e liberação global **não resolvidos**. Próximo eixo recomendado: fechar contratos de **CORE02 (direitos/escopos/campos)** e **CORE07 (convites/assentos)** em consonância com o modelo de dados, sem programar.

**Estado final:** anexo de revisão criado, nenhuma pendência baixada e nenhuma implementação/teste executado.
