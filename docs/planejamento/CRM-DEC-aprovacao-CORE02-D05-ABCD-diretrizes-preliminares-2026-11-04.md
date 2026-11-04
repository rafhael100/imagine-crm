# CRM-DEC — Aprovação preliminar CORE02-D05-A/B/C/D — perfis, negações, delegação e escopo

**Data:** 04/11/2026.  
**Manifestação literal do responsável:** “Aprovo CORE02-D05-A, B, C e D como diretrizes técnicas documentais preliminares do CRM Imagine. Registre no GitHub, mantenha CORE02-D04 e D05 abertas, preserve CORE02-D06 a D10, CORE01-D04 a D07, SEG-01, SEG-02 e todos os demais bloqueios técnicos. Prossiga com o planejamento documental de CORE02-D06, sem programação nem execução de testes.”

**Estado:** **CORE02-D05-A/B/C/D APROVADAS EXCLUSIVAMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES. CORE02-D05 PERMANECE ABERTA**, juntamente com CORE02-D04, D06..D10; CORE01-D04..D07; SEG-01/02 e todos os bloqueios restantes.  
**Pauta de referência:** [CORE02-D05 — combinação de perfis, negações, herança, delegação e escopo](CRM-CORE-P0-ETAPA2-CORE02-D05-combinacao-perfis-negacoes-delegacao-proposta-v0.1.md). Esta decisão tem precedência quanto ao **status** das subdiretrizes aprovadas sobre textos históricos do documento que ainda digam “NÃO APROVADAS”. **Não** aprova regras executáveis, grants, papéis físicos, SQL/RLS, precificação, vagas, licenciamento, programação ou testes.

## 1. Quatro diretrizes expressamente aceitas — sem ampliar escopo

1. **CORE02-D05-A — limites cumulativos e fontes de direito:** a avaliação de autorização deve respeitar de modo cumulativo a identidade e o tenant/membership legítimos, plano/entitlement, proprietário funcional, recurso, ação, campo, escopo, finalidade, estado e políticas de segurança. A existência de vários papéis não ultrapassa qualquer bloqueio mandatário nem concede direito a outra organização ou módulo não contratado.
2. **CORE02-D05-B — contrato determinístico para composição de direitos:** documentar e submeter a escolha futura da política efetiva de combinação de grants positivos, negações explícitas, herança e restrições mandatórias, incluindo precedência por origem, ação, campo, equipe, contexto e exceção. **As alternativas P1–P4 são opções de estudo, não escolhas aprovadas.** Não presumir que qualquer deny em qualquer perfil prevaleça universalmente, nem que união/interseção sempre seja o resultado.
3. **CORE02-D05-C — perfis personalizados e delegação com prevenção de autoelevação:** separar criar/editar perfil, atribuir, aprovar e delegar poderes segundo competência, tenant, plano, MFA e alçada. Não permitir que cliente, suporte, IA ou Administrador Geral concedam a si privilégios alheios ou gratuidade/recursos não autorizados. **Básico: um administrador humano autenticado com MFA**; transferência do último admin e quotas continuam CORE07/CORE04/CORE08.
4. **CORE02-D05-D — escopo, revogação e explicabilidade:** discriminar próprio/equipe/unidade/posse/caixa/canal quando aplicável, calcular direitos por tenant e por ação, documentar revogação segura em sessões/jobs/arquivos/automação e registrar diagnóstico/auditoria de autorizações e negações sem dados sensíveis excessivos. Não compartilhar grants entre tenants e agentes.

**Limites comuns:** não ratifica esquema de banco, roles, motor independente de IAM, tabela real de perfis, TTL, política P1/P2/P3/P4 ou hierarquia de negados, lista de permissões por módulo, grants de contas ou regras de cobrança.

## 2. Pendências que permanecem ABERTAS

| ID/conjunto | O que falta fechar efetivamente |
|---|---|
| **CORE02-D04** | Catálogo real por campo e recurso, classificação e proprietário de cada módulo, CORE01, SEG-01/02 e INT |
| **CORE02-D05** | Escolha/contrato real de P1–P4 e precedência de grants/denies/atributos, herança, política de equipe/unidade, perfis e delegação, owners, critérios de aceite |
| **CORE02-D06..D10** | Ações de cada proprietário DAT/VEN/ATD/AGD/AUT/IA/FIN/CON/INT/Sites, MFA/alçadas, políticas físicas/revogação, direitos por plano e evidências |
| **CORE01-D04..D07** | Membership e escopo, contexto seguro/isolamento RLS, ciclo de tenant, entidades/relações/constraints físicos |
| **CORE03/04/05/06/07/08/09/10** | Contratos de sessões, assíncronos, suporte, auditoria, convites, entitlements, cofre, homologação, todos D04..D10 abertos |
| **SEG-01/SEG-02; ARQ-01/02/03; AGT-01** | Políticas autorizadoras de IA/serviços e segredos, infraestrutura, monitoramento e operação |
| **INT P0 / G01..G06; FIN/CON; REV/IN; LGPD; custos/licenças** | Fontes, contratos, autorizações, rastreabilidade, revisão comercial e legal, gate final |

Na [matriz CORE Etapa 2](CRM-CORE-P0-ETAPA2-matriz-pendencias-tecnicas-plano-fechamento-v0.1.md), permanecem **67 identificadores técnicos abertos**; subdiretriz preliminar não encerra nenhum identificador. [DEV-GATE-00](CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md) continua **NO-GO** para desenvolvimento.

## 3. Continuidade autorizada

**Elaborar somente proposta documental CORE02-D06**, focalizada no catálogo **ação×recurso×campo×escopo×operação e proprietário** para DAT, VEN, ATD, AGD, AUT, IA, FIN/CON, INT, arquivos, Auditoria e Sites/Portal, respeitando planos e decisões funcionais existentes. Preparar alternativas e critérios a submeter a posterior aprovação **específica**; não conceder direitos, não presumir módulos do Básico, não definir novos planos/preços, nem implementar automações.

**Sem programação/Codex, sem execução de testes, configuração de ambiente, banco ou RLS, emissão de convites, credenciais, compra, contratação, implantação ou ações em dados reais.** Esta é uma decisão estritamente documental e parcial.
