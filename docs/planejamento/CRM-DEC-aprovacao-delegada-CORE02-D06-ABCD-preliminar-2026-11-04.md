# CRM-DEC — Decisão técnica delegada sobre CORE02-D06-A/B/C/D (04/11/2026)

**Data:** 04/11/2026.  
**Origem da delegação (texto literal do responsável nesta conversa):** “quero que vc aprove o que ainda falta pois precisamos inicir o desenvolvimento”.  
**Responsável pela avaliação técnica documental:** assistente ChatGPT, **por delegação geral do responsável para acelerar decisões de planejamento**. Esta é **uma avaliação técnica delegada**, não uma declaração de que o responsável aprovou nominalmente cada A/B/C/D, nem certificação de testes.  
**Pauta avaliada:** [CORE02-D06 — catálogo de ações por módulo e contratos de autorização](CRM-CORE-P0-ETAPA2-CORE02-D06-catalogo-acoes-por-modulo-proposta-v0.1.md), seções 1–7.  
**Resultado:** **CORE02-D06-A/B/C/D ACEITAS SOMENTE COMO DIRETRIZES TÉCNICAS DOCUMENTAIS PRELIMINARES**; **CORE02-D06 integral PERMANECE ABERTA**. Não se aprovou catálogo real de permissões, papéis de banco, endereços de API, cotas, planos, alçadas novas, tecnologia ou execução.

## 1. Decisão técnica fundamentada, restrita ao escopo das quatro diretrizes

| Diretriz | Deliberação delegada | Por que é válida apenas como princípio |
|---|---|---|
| **CORE02-D06-A — catálogo tipado com proprietário** | **Aceitar preliminarmente** matriz versionada por módulo, recurso, ação, campo, contexto, finalidade e dono do resultado; cada módulo deverá validar sua fonte funcional. | Impede nomes vagos de operações e grants sem contrato, sem afirmar que todos os recursos candidatos já existem. |
| **CORE02-D06-B — separar atos e resultados oficiais** | **Aceitar preliminarmente** distinção de consultar, editar, exportar, enviar, aprovar, excluir, importar e causar efeitos contratuais, conforme o caso. **Preservar a única permissão conjunta ver/baixar anexo.** | Evita efeito indevido por mera leitura ou confirmação técnica; não introduz dupla aprovação universal. |
| **CORE02-D06-C — contexto, entitlement e não elevação** | **Aceitar preliminarmente** matriz por tenant, membership/mandato, plano, recurso, campo, escopo, equipe/unidade, canal, estado e risco; IA, n8n e suporte não recebem direitos por analogia. | Não escolhe os direitos comerciais finais por plano, política P1–P4 de composição ou exceções de SEG. |
| **CORE02-D06-D — validação por proprietário e critérios futuros** | **Aceitar preliminarmente** fonte/versão, owner funcional, cenários negativos, rastreabilidade e evidências futuras coerentes com CORE10 e INT G01–G06. | Não equivale a aceite efetivo dos proprietários, execução de HT, resultado PASS ou homologação. |

**Verificação de compatibilidade:** o conjunto preserva [CORE02-D01–D03](CRM-DEC-aprovacao-CORE-P0-02-D01-D03-base-permissoes-2026-11-04.md), [D04 preliminar](CRM-DEC-aprovacao-CORE02-D04-ABCD-diretrizes-preliminares-2026-11-04.md), [D05 preliminar](CRM-DEC-aprovacao-CORE02-D05-ABCD-diretrizes-preliminares-2026-11-04.md), [CORE01-D07 preliminar](CRM-DEC-aprovacao-CORE01-D07-ABCD-diretrizes-preliminares-2026-11-04.md), FIN/CON, IN-118, IN-127 e IN-040. Rejeitada a interpretação de que uma lista de ações candidatas conceda acesso, plano ou serviço.

## 2. Pendências que impedem encerramento técnico e GO global

- **CORE02-D04/D05/D06/D07..D10 continuam ABERTAS**, incluindo campos finais, composição P1–P4, matriz real de recursos/atores, MFA/alçadas, enforcement Laravel/RLS e revogação, limites de plano e homologação documental.
- **CORE01-D04..D07** e **CORE03..CORE10-D04..D10** continuam abertos. **67 IDs CORE** totais ainda sem fechamento.
- **IN-168**: SEG-01/SEG-02, ARQ-01/02/03, AGT-01 são P0 abertos. **INT**: P0-DOC/TR/OWN/CON/SEC/DEP/VER/ACC/XREF/GIT, G01..G06 abertos; roteiros de homologação HT01..18 não executados.
- Contratos físicos, LGPD/FIN/CON, proprietários, fornecedores/licenças, custos, infraestrutura e baseline final não estão resolvidos.

## 3. Regra de registro por delegação e transparência

A delegação permite **revisar e decidir em lote matérias documentais para as quais há proposta concreta e justificativa verificável**. Ela **não é prova substitutiva** de segurança, fechamento de dependência, escolha operacional não especificada, revisão legal/financeira, autorização de gastos, testes ou aceite de implantação. **Não atribuir ao usuário uma citação literal que ele não proferiu.**

**Não existe autorização registrada neste ato para executar código, alterar sistemas, conectar dados reais, contratar fornecedor, conceder grants, testar em produção ou implantar.** O [DEV-GATE-00](CRM-DEV-GATE-00-checklist-unico-liberacao-desenvolvimento-v0.1.md) continua NO-GO para a liberação global de desenvolvimento até a decisão específica de modelo de liberação por fases.
