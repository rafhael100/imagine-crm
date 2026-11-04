# CRM-DEC — Aprovação CORE06-D01, D02 e D03 como base documental de auditoria

**Data:** 04/11/2026.  
**Manifestação literal do responsável:** “Aprovo CORE06-D01, D02 e D03 como base documental do CRM Imagine. Registre a aprovação no GitHub, mantenha CORE06-D04 a D10 e os demais bloqueios técnicos abertos e prossiga para o CORE-P0-07, sem iniciar programação.”  
**Situação:** **CORE06-D01–D03 APROVADAS COMO BASE DOCUMENTAL**; **CORE-P0-06 PARCIAL/ABERTO**, não homologado tecnicamente nem pronto para implementação.  
**Fonte deliberada:** [CORE-P0-06 v0.1](CRM-CORE-P0-06-auditoria-categorias-retencao-acesso-proposta-v0.1.md).

## 1. Deliberações aprovadas

1. **CORE06-D01 — Separação de categorias e fontes oficiais:** adotar como referência lógica a distinção entre auditoria administrativa Imagine, comercial por tenant, segurança, integração, recepção/filas de leads, observabilidade técnica, fatos/históricos de negócio, conhecimento técnico e backups. A **Auditoria Geral é a fonte oficial de evidências**; os módulos proprietários confirmam e mantêm seus fatos de negócio, enquanto **INT-12 apenas correlaciona/investiga**, sem gerar motor ou trilhas paralelas. Nenhum único prazo vale automaticamente para todas as categorias.
2. **CORE06-D02 — Envelope proporcional de evidência, acesso e rastreabilidade:** adotar, quando pertinente, identidade causal/executor, organização, ação, recurso/escopo, referência a versões/autorizações, instante, decisão e resultado, correlação, proveniência e integridade, com minimização e segregação. **Consultar, exportar, corrigir e eliminar** são competências separadas; administradores da empresa não alteram/apagam trilha oficial. A auditoria da auditoria é preservada. A decisão existente **IN-040 de não registrar individualmente cada visualização/download ordinário de anexo** permanece; nenhuma auditoria invasiva nova foi aprovada por analogia.
3. **CORE06-D03 — Regimes de retenção diferenciados já vigentes:** referência dos prazos históricos **administração central 5 anos**, **comercial por tenant 12 meses enquanto ativa**, **debug 7 dias**, **operacionais e traces 30 dias**, **métricas 90 dias**, **auditoria técnica de filas de leads até 90×24h após evento terminal específico**, **incidentes críticos/eventos técnicos de segurança 180 dias**; conhecimento técnico arquivado **24 meses** segundo IN-163. **IN127-R01..R06** prevalece nas filas de leads: janela contínua 72+360=432h desde T0 para tentativas autorizadas; destino CRM oficialmente válido; descarte de payload pendente ao vencimento sem confirmação; cópias temporárias entregues com limpeza IN-128 após 24h; metadados mínimos conforme regra específica. Não confundir esses regimes com retenção de registros oficiais de contatos, conversas, documentos fiscais, contratos, backups e arquivos, que têm políticas próprias e dependências legais/LGPD. Não executar limpeza real.

## 2. Decisões e especificações NÃO APROVADAS

| Código em aberto | Escopo a detalhar com o proprietário |
|---|---|
| **CORE06-D04** | Catálogo completo de eventos, fontes, campos antes/depois, classificação de segurança e dados pessoais |
| **CORE06-D05** | Esquema e mecanismo físico da Auditoria Geral, integridade/proveniência, criptografia, armazenamento e custo |
| **CORE06-D06** | Direitos concretos de consulta, investigação, exportação, justificativa, mascaramento e auditoria da auditoria |
| **CORE06-D07** | Prazos legais/contratuais de fatos oficiais, retenção após cancelamento, documentos fiscais, anexos, backups e legal holds |
| **CORE06-D08** | Implementação de expurgo seguro, prova de descarte, tratamento de réplicas, backups, restore e bloqueios legítimos |
| **CORE06-D09** | Capacidade, monitoramento, indicadores, custos, alertas e quotas técnicas |
| **CORE06-D10** | Responsáveis, casos de homologação futuros e fechamento documental cruzado com INT/G01–G06 |

**Outros bloqueios preservados:** CORE01-D04..D07, CORE02-D04..D10, CORE03-D04..D10, CORE04-D04..D10, CORE05-D04..D10, CORE-P0-07..10; INT P0-OWN/SEC/CON/ACC, IN-168 (ARQ/SEG/AGT), G01–G06 e aprovação global do planejamento. Nem esta aprovação nem o registro no GitHub encerram qualquer P0.

## 3. Continuidade autorizada

Prosseguir **apenas com a documentação CORE-P0-07 — convites, assentos, equipes e desligamento**, respeitando LEG-04 e a separação entre identidade global, membership por tenant, direitos por plano, convites de 7 dias, MFA por papel e revogação da organização sem destruir acessos legítimos a outras.

**Não iniciar programação, execução de testes, alterações de banco/infraestrutura, provisionamento, operações reais, exclusão de registros, contratação nem despesas.**
