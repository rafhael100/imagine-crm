# CRM-DEC — Ratificação expressa CORE02-D08-A/B/C/D como diretrizes preliminares

**Data:** 05/11/2026 (America/Sao_Paulo).  
**Origem:** o responsável respondeu **“aprovado”** à pergunta explícita “Você aprova D08-A, B, C e D para continuarmos?”, após apresentação das quatro diretrizes do [CORE02-D08](CRM-CORE-P0-ETAPA2-CORE02-D08-enforcement-revogacao-consistencia-proposta-v0.1.md).  
**Estado:** **CORE02-D08-A/B/C/D APROVADAS EXCLUSIVAMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES; CORE02-D08 INTEGRAL PERMANECE ABERTA.**

## Diretrizes ratificadas

1. **D08-A — aplicação coerente e server-side**: toda superfície de operação protegida deve depender de avaliação efetiva no backend por principal, tenant, membership/mandato, plano, recurso, ação, campo, finalidade e escopo. UI é auxiliar; não substitui controles na API, consultas, relatórios, anexos, exportações, automações ou jobs. **Não criar segundo motor IAM.**
2. **D08-B — defesa complementar de dados e contexto**: isolamento tenant-bound em Laravel + PostgreSQL RLS, com contexto validado pelo servidor e integridade de conexões/pools/transações/roles, sem aceitar `tenant_id`/GUC fornecido como prova de autenticação. Políticas SQL, roles e grants físicos dependem de CORE01/SEG e homologação.
3. **D08-C — revogação efetiva e assíncronos**: mudanças de membership, papel, equipe, MFA, plano, mandato, token ou conexão devem bloquear **novos efeitos protegidos não autorizados**, incluindo jobs, exportações e links de anexos. Revalidar nos marcos exigidos pelo proprietário, sem `allow` por fallback e sem desfazer fatos de negócio já confirmados legitimamente. Prazos, política de cache/retry e compensações concretas permanecem pendentes.
4. **D08-D — evidências e critérios negativos**: exigir matriz versionada por principal×tenant×papel×plano×recurso×ação×campo×escopo×fase, trilha/auditoria sanitizadas e futuros casos de teste para acesso cruzado, revogação, fila, exportação, anexo, APIs e suporte/agentes. Nenhuma suíte foi executada em virtude desta aprovação.

## Preservações e pendências

- **CORE02-D08 continua ABERTA** até especificação física, direitos reais por módulo e plano, política de perfis/negações P1–P4, controles de sessão/MFA, critérios negativos executados, auditoria e homologação por proprietários e Segurança.
- **CORE02-D04..D07 e D09..D10**, CORE01-D04..D07, CORE03..CORE10-D04..D10, SEG-01/02, ARQ-01/02/03, AGT-01, INT P0/G01..G06, FIN/CON, LGPD e o baseline global continuam abertos. Os 67 identificadores CORE representam decisões técnicas pendentes, não encerradas por subdiretrizes.
- A aprovação **não** ratifica código, novas permissões, SQL/RLS, algoritmo de cache, prazo de revogação, implantação, contratação, custos ou testes. **Codex fast-track é uma frente autônoma** com autorizações próprias. Não modificar `main` nem VPS.
- Próxima pauta documental: **CORE02-D09 — direitos por plano, quotas e adicionais**, em articulação com CORE08, REV02 e FIN/CON, sem inferir tabela comercial final.

**Registro de controle:** integrar por PR documental revisável, inicialmente DRAFT e sem merge automático.
