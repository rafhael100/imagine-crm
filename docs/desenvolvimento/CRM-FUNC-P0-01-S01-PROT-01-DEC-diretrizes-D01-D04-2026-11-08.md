# CRM Imagine — S01-PROT-01 — Registro de orientação documental PROT-D01..D04

**Data:** 08/11/2026 (America/Sao_Paulo).  
**Manifestação:** após a apresentação das quatro recomendações PROT-D01..D04, a Direção respondeu **“ok”** e informou uma preferência explícita por **maior autonomia do ChatGPT para conduzir as próximas atividades sem solicitações repetitivas de aprovação**, perguntando qual é a próxima etapa do Imagine CRM.  
**Interpretação conservadora:** concordância com a direção proposta para planejamento de **S01-PROT-01** e preferência de governança por reduzir consultas rotineiras; **não há declaração expressa e verificável do novo alcance técnico** que substitua as proibições e gates das ordens anteriores.  
**Status:** diretrizes de planejamento ratificadas como orientação; **execução de código, branch executiva e PR de implementação ainda não liberados neste registro**.

## Diretrizes a observar

| ID | Orientação de planejamento | Limite |
| --- | --- | --- |
| **PROT-D01 — amplitude** | Preparar protótipo fictício com quatro telas P-01 Boas-vindas; P-02 Minha organização; P-03 Estados da tela; P-04 Sobre o protótipo. | Não incluir leads, planos, faturas, usuários, senha, autenticação, API ou banco. |
| **PROT-D02 — tecnologia** | Preferir HTML/CSS/JS estáticos autocontidos, sem dependências remotas e sem chamadas de rede. | Não conectar Laravel/CRM real, VPS, Portal, Cloudflare ou dados de clientes. |
| **PROT-D03 — checklist** | Usar UX-01..UX-08 como **critérios propostos** para a futura revisão de protótipo. | Não confundir avaliação UX com testes de homologação S01-T01..T22. |
| **PROT-D04 — execução** | Preservar gate de programação **separado**: proposta de ordem de código deve identificar branch/SHA, arquivos, testes, custo e proibições e ser avaliada em escopo autorizado. | Aprovação de diretrizes e autonomia para tarefas rotineiras não pode ser interpretada como consentimento para `ALLOW`, merge, VPS, produção ou ações financeiras. |

## Autonomia operacional sem novos riscos

A orientação de reduzir perguntas permite ao assistente organizar documentação, verificar repositório/PR/CI, preparar escopos, checklists, backlog e relatórios **dentro de permissões existentes**. Decisões já tomadas não precisam ser reabertas a cada turno. Qualquer ação nova deve respeitar autorização vigente e limites do repositório. Não afirmar acesso privilegiado, parecer independente, custos aprovados ou GO de deploy sem evidência.

## Próximo pacote executável proposto, ainda não autorizado

**S01-PROT-01-F1**: gerar demonstração visual offline com texto fixo “Empresa Exemplo” e “Seu vínculo: Ativo”, aviso permanente **“PROTÓTIPO — DADOS FICTÍCIOS”**, controles fictícios dos estados UI-01/02/03/04 e navegação entre quatro telas; validar somente UX-01..UX-08. O trabalho futuro deverá ocorrer **em branch filha isolada baseada no SHA conferido da DEV**, com PR DRAFT para revisão, se autorizado. Evitar Actions pagos por padrão e dependências externas. Sem alterações `main`, DEV original, VPS, produção, IAM, RLS, autorizações, migrations, dados reais, segredos ou endpoints.

## Gates preservados

`DenyByDefaultAuthorizer` continua deny-only; 67 IDs CORE, SEG-01/02, Issue #27 SEG/DBA, AUT, ARQ, INT, FIN, CON, LGPD, DEV-GATE e G01..G06 continuam abertos. PRs #2/#21/#23..#30 permanecem sujeitos a aprovação própria. Esta orientação não autoriza código funcional nem indica que o CRM esteja operacional.

**Fontes:** [Escopo PROT-01](CRM-FUNC-P0-01-S01-PROT-01-escopo-prototipo-navegavel-offline-proposta-v01-2026-11-08.md), [S01-R02](CRM-FUNC-P0-01-S01-R02-contratos-exibicao-estados-e-evidencias-proposta-2026-11-08.md), [Issue #22](https://github.com/rafhael100/imagine-crm/issues/22).
