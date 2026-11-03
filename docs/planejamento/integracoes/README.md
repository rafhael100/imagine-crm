# CRM Imagine — Integrações inteligentes (INT-01 a INT-12)

**Situação:** todas as INT-01 a INT-12, INT-R01 e INT-R02 aprovadas **funcionalmente**; documentação em consolidação P0 iniciada em 2026-11-02.
**Fase:** planejamento e documentação. **Proibido iniciar programação, implantação, mudanças de produção, contratação ou despesas nesta etapa.**
**Natureza dos blocos:** INT-01 a INT-12 são automações/capacidades aprovadas; INT-R01 e INT-R02 são **documentos transversais**, não INT-13 nem módulos novos.

## Índice
- [INT-01 — Cadastro Mestre e Governança Inteligente de Integrações, Provedores e Conexões](INT-01.md)
- [INT-02 — Gestão Inteligente de Autorizações, Credenciais, Escopos e Consentimentos de Integração](INT-02.md)
- [INT-03 — Esquemas, Mapeamentos e Transformações de Dados de Integrações](INT-03.md)
- [INT-04 — Operações Técnicas, Adaptadores e Capacidades de Integração](INT-04.md)
- [INT-05 — Aplicações Integradas e API Pública](INT-05.md)
- [INT-06 — Webhooks, Assinaturas e Entregas de Eventos Externos](INT-06.md)
- [INT-07 — Sincronizações Contínuas e Checkpoints](INT-07.md)
- [INT-08 — Gestão Inteligente de Importações, Exportações e Migrações Integradas de Dados](INT-08.md)
- [INT-09 — Orquestração Inteligente de Operações Externas e Processos Multissistema](INT-09.md)
- [INT-10 — Monitoramento Inteligente, Diagnóstico, Contingência e Recuperação de Integrações](INT-10.md)
- [INT-11 — Gestão Inteligente de Parceiros, Provedores e Conectores Homologados](INT-11.md)
- [INT-12 — Auditoria Inteligente, Rastreabilidade e Conformidade de Integrações](INT-12.md)
- [INT-R01 — Governança, contratos técnicos e homologação integrada](INT-R01-governanca-contratos-homologacao.md)
- [INT-R02 — Rastreabilidade, dependências e validação final](INT-R02-rastreabilidade-dependencias-validacao.md)
- [INT-P0 — Matriz de fechamento documental](INT-P0-fechamento-documental.md)
- [INT-P0 — Primeira matriz de requisitos rastreáveis](INT-P0-rastreabilidade-requisitos.md)
- [INT-P0 — Autoridades e contratos de fronteira](INT-P0-autoridades-contratos.md)
- [P0-REV-INT01-04 — Conferência cruzada e ressalvas de proveniência](P0-REV-INT01-04-conferencia-cruzada.md)
- [P0-REV-INT05-08 — Conferência cruzada e portabilidade IN-118](P0-REV-INT05-08-conferencia-cruzada.md)
- [P0-REV-INT09-12 — Conferência cruzada e fronteiras centrais](P0-REV-INT09-12-conferencia-cruzada.md)
- [P0-REC — Reconciliação transversal e critérios G01–G06](P0-REC-conciliacao-transversal.md)
- [DECISOES — Origem e precedência](DECISOES-origem-precedencia.md)
- [P0-ORIGEM — Transcrição de aceites disponíveis nesta conversa](P0-ORIGEM-aprovacoes-dialogo.md)
- [P0-CON — Minuta de contratos técnicos compartilhados](P0-CON-contratos-tecnicos-minuta.md)
- [P0-SEG — Minuta de matriz de autorizações e alçadas](P0-SEG-matriz-autorizacoes-proposta.md)
- [P0-TR53 — Vínculo dos 53 requisitos iniciais a contratos, invariantes e HT](P0-TR53-vinculo-contratos-aceite.md)
- [P0-OP — Catálogo de 12 casos operacionais de referência](P0-OP-casos-operacionais-contratos-alcadas.md)
- [P0-CON CT-03/CT-04 — Comandos, resultados, transições e reconciliação](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) — base documental aprovada
- [P0-DEC — Aceite expresso da especificação CT-03/CT-04](P0-DEC-aprovacao-CT03-CT04.md)
- [P0-OP24 — 24 fichas de referência por operação](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) — aceitas como base documental, parâmetros ainda em revisão
- [P0-DEC — Aceite contextual das fichas OP24](P0-DEC-aprovacao-OP24-base.md)
- [P0-CT02 — Autorizações, permissões e alçadas por 24 operações](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md) — aceita como base documental
- [P0-DEC — Aceite contextual de CT-02 como base](P0-DEC-aprovacao-CT02-base.md)
- [P0-SEC — Políticas de autorização contextual por operação](P0-SEC-politicas-autorizacao-CT02.md) — aprovada como base documental
- [P0-VER — Matriz de compatibilidade e evolução de versões](P0-VER-matriz-compatibilidade-evolucao-versoes.md) — aprovada como base documental
- [P0-DEC — Aprovação expressa SEC e VER como bases](P0-DEC-aprovacao-SEC-VER-base.md)
- [P0-ACC — Planos de homologação HT-01 a HT-18](P0-ACC-planos-homologacao-HT01-HT18.md) — 18 procedimentos aceitos como base documental, não executados
- [P0-ACC — Controle de evidências e aceite HT-01 a HT-18](P0-ACC-matriz-controle-evidencias-HT01-HT18.md) — base documental aceita, 0/18 testes executados
- [P0-DEC — Aceite dos planos HT como base](P0-DEC-aprovacao-ACC-HT01-HT18-base.md)
- [P0-TR-AUD — Conferência de 53 TR, 18 HT, 16 INV](P0-TR-AUD-auditoria-proveniencia-requisitos.md)
- [P0-DOC-TR — 28 lacunas candidatas de granularidade por fonte](P0-DOC-TR-deltas-candidatos-fonte-original.md) — revisão contra sínteses, fonte literal pendente
- [P0-DEP — Dependências e fronteiras críticas](P0-DEP-dependencias-criticas-e-fronteiras.md)
- [P0-GATE — Situação dos 11 P0 e G01–G06](P0-GATE-painel-fechamento-rastreabilidade-dependencias.md)

## Situações distintas (não confundir)
1. **Aprovado funcionalmente:** decisão expressa do responsável; registrada em conversa de planejamento.
2. **Documentado:** texto consolidado e versionado no repositório.
3. **Validado documentalmente:** reconciliação com texto integral, dependências e conflitos concluída.
4. **Aguardando homologação:** critérios de teste especificados, sem execução.
5. **Homologado:** somente após testes reais futuros e evidências.

As sínteses INT-* existentes são **base inicial de documentação**, não declaração de que todas as deliberações originais foram conferidas palavra a palavra. A matriz P0 registra a conferência obrigatória.

## Ligações com documentos existentes
- [IN-167 — Arquitetura dos agentes técnicos](../IN-167-consolidacao-arquitetura-agentes-tecnicos.md)
- [IN-168 — Pendências de arquitetura dos agentes técnicos](../IN-168-matriz-pendencias-fechamento-arquitetura.md)
- [IN-124 — Integração de formulários/leads](../IN-124-integracao-inteligente-formularios-leads.md)
- [IN-148 — Reconciliação de leads](../IN-148-reconciliacao-inteligente-leads.md)
- [IN-149 — Protocolo de entrega e confirmação de leads](../IN-149-protocolo-entrega-confirmacao-leads.md)
- [IN-162 — Política de retenção de logs técnicos](../IN-162-politica-inteligente-retencao-armazenamento-logs.md)

Regras específicas dos documentos acima não se tornam automaticamente regras globais das INTs. Resolver qualquer divergência por precedência e decisão expressa, jamais silenciosamente.
