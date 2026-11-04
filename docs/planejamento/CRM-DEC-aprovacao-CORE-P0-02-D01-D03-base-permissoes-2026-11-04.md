# CRM-DEC — Aceite de CORE02-D01, D02 e D03 como base documental de permissões

**Data do aceite:** 04/11/2026.  
**Manifestação expressa do responsável:** “Aprovo as propostas CORE02-D01, D02 e D03 como base documental. Registre a aprovação no GitHub, mantenha CORE02-D04 a D10 e os demais bloqueios técnicos abertos e prossiga para o CORE-P0-03, sem iniciar programação.”  
**Status:** **D01–D03 APROVADAS COMO BASE DOCUMENTAL**. **CORE-P0-02 PARCIAL/ABERTO.** Nenhuma política executável/grant, matriz final por papel, schema, nova capacidade, contrato ou integração foi homologada.

## 1. Deliberações aceitas

1. **CORE02-D01 — Modelo cumulativo de autorização:** adotar como referência lógica a validação de sujeito humano/técnico autenticado, vínculo de organização (ou mandato excepcional legitimamente autorizado), tenant e ambiente, entitlement efetivo de plano/benefício/canal/quota, papel e escopo, recurso, ação, campos, finalidade e regras do módulo proprietário. A ausência de permissão efetiva bloqueia o ato; revalidar nas fronteiras de execução, inclusive após retomada/assincronia. Camadas Laravel + PostgreSQL RLS e princípios de CORE-P0-01 continuam vigentes.
2. **CORE02-D02 — Catálogo semântico de ações:** aceitar os tipos de operação da [proposta v0.1](CRM-CORE-P0-02-matriz-permissoes-recursos-acoes-campos-proposta-v0.1.md) como **vocabulário de contrato em planejamento**, sem criar endpoints, novas permissões comerciais ou nomes físicos definitivos. **IN-040 mantém uma única permissão de visualizar/baixar anexos**. Exportação, exclusão, mesclagem, envio externo, aprovação, finanças, auditoria, upload e cancelamento são competências distintas onde previsto.
3. **CORE02-D03 — Matriz conceitual de atores e fronteiras:** aprovar a referência das famílias de usuários e seus limites (Admin Geral Imagine, Admin Empresa, Admin único Básico, funcionários/equipes, IA, identidades técnicas, suporte excepcional, auditoria e atores externos por link escopado). O aceite da matriz cruzada **não concede nenhum acesso novo, perfil genérico com poderes, nem recurso ainda não incluído em plano**; cada módulo e o proprietário do fato mantêm regras/competência próprias.

## 2. Direitos históricos a preservar

- Básico: 1 usuário autenticado, Admin Empresa com MFA, permissões específicas aprovadas (leads/contatos, CSV/XLSX, mesclagem manual controlada, exclusão protegida, agenda interna, anexos) e ausência de concessão automática de recursos pagos.
- Admin Geral Imagine: gestão central com MFA e ações críticas auditadas; sem acesso irrestrito automático aos dados de cliente; gratuidade de qualquer plano é competência central autorizada, **não** de administrador de tenant; adicionais têm concessão independente.
- Funcionários e gestores: perfis configuráveis dentro da organização com visibilidade próprio/equipe/empresa e ações distintas. MFA opcional para funcionário por padrão, exigível pela empresa; MFA admin obrigatório.
- Serviços, agentes, integrações: identidade e autorização mínima; aprovação de ferramenta/credencial externa não suplanta o módulo proprietário; n8n interno não é motor das automações configuradas por clientes do CRM.

## 3. Pendências preservadas — D04 a D10 NÃO foram aprovadas

| Código | Permanência da lacuna | Competência relacionada |
|---|---|---|
| **CORE02-D04** | Inventário e classificação final de campos por módulo, máscara/minimização e campos personalizados | DAT/VEN/ATD/AGD/FIN/CON/IA/SEG |
| **CORE02-D05** | Precedência entre papéis e negações, delegação/autoelevação, perfil efetivo, visibilidade por equipe/unidade | CORE-P0-01/07; SEG-01 |
| **CORE02-D06** | Autorização granular e aceites por ação em cada módulo proprietário | DAT/VEN/ATD/AGD/AUT/IA/FIN/CON/INT |
| **CORE02-D07** | Contratos de ações sensíveis, step-up/MFA/confirmar e alçadas conforme competência; sem impor segunda aprovação universal | CORE-P0-04/05; GRAT-P02; INT CT-02 |
| **CORE02-D08** | Políticas físicas, cache/revogação, API/jobs/workers, RLS por tabela e evidências de isolamento | CORE-P0-01/03/07/09; SEG |
| **CORE02-D09** | Matriz de recursos de planos e Básico, quotas, canais e modelo de automações; revisão comercial final | CRM-REV-02; AUT-MAT01; FIN/CON |
| **CORE02-D10** | Evidências, responsáveis e cenários de homologação documental, auditoria e gates | CORE-P0-10; INT P0; G01–G06 |

**Continuam abertos:** dependências D04–D07 de CORE-P0-01; CORE-P0-03 a 10; INT P0-OWN/SEC/CON, IN-168 (ARQ-01/02/03, SEG-01/02, AGT-01) e G01–G06. Uma base funcional aceita não é prova de segurança executável.

## 4. Próxima etapa autorizada e proibições

**Autorizado:** produzir a proposta documental **CORE-P0-03 — autoridade/contexto em jobs, eventos, webhooks e filas**, distinguindo princípios já aprovados de deliberações novas.  
**Não autorizado:** desenvolver com Codex, programar Laravel/SQL/n8n, executar testes, ativar integrações, contratar serviços, criar credenciais ou alterar produção. Permanece válida a regra de aprovação **global e final do planejamento** como gate obrigatório antes da programação.
