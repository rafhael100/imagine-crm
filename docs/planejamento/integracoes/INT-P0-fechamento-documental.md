# INT — Matriz P0 de Fechamento Documental

**Fase:** consolidação iniciada em 2026-11-02; nenhuma programação, implantação, despesa ou alteração de produção autorizada.
**Escopo:** somente P0 do módulo INT (INT-01 a INT-12, INT-R01 e INT-R02), preservando outras matrizes, como IN-168.
**Prioridade:** P0 = bloqueia validação documental final; P1/P2 ficam listadas na INT-R02.

| ID | P0 obrigatório | Estado inicial nesta consolidação | Evidência de conclusão |
|---|---|---|---|
| P0-DOC-01 | Conferir texto **integral** das aprovações INT-01 a INT-12 vs sínteses, incluindo exceções | **Pendente de conferência literal** | Matriz de diferenças fechada, texto reconciliado, fonte referenciada |
| P0-DOC-02 | Conferir integralmente aprovação INT-R01/INT-R02 e cruzar requisitos | **Sínteses versionadas; revisão cruzada pendente** | Revisão cruzada documentada |
| P0-TR-01 | Atribuir ID TR-INTxx-### a cada requisito e ligar à decisão de origem | **Pendente** | Cobertura integral dos requisitos e fontes |
| P0-OWN-01 | Matriz de autoridades por operação protegida: módulo, identidade, finalidade e confirmação | **Matriz de domínio inicial criada; granularidade pendente** | Contratos e alçadas verificados |
| P0-CON-01 | Contrato de comando/evento/resultado compartilhado com esquema, estados e códigos | **Diretrizes aprovadas; campos executáveis/códigos pendentes** | Especificação versionada e revisada |
| P0-SEC-01 | Matriz contextual RBAC, MFA, revogação, revalidação, isolamento e aprovações | **Diretrizes aprovadas; verificação por operação pendente** | Revisão de todas operações protegidas |
| P0-DEP-01 | Resolver sobreposições INT/serviços centrais e dependências cíclicas ou não resolvidas | **Fronteiras gerais definidas; verificação completa pendente** | Matriz de dependências sem conflitos críticos |
| P0-VER-01 | Regras de compatibilidade entre versões e instâncias persistentes | **Diretrizes aprovadas; matriz exata pendente** | Compatibilidade e migração documentadas |
| P0-ACC-01 | Critérios e evidências HT-01 a HT-18 e aceite documental G01 a G06 | **Cenários catalogados; procedimentos pendentes** | Planos de teste documentados; testes reais só no futuro |
| P0-XREF-01 | Conciliação com IN-124/148/149, IN-151/162, IN-167/168 e demais políticas centrais | **Referências identificadas; revisão de consistência pendente** | Relações e exceções registradas |
| P0-GIT-01 | Versionamento verificável e revisão final de texto consolidado | **Commit inicial a verificar; fechamento pendente** | Commit/revisão final expressamente aceita |

## Entregas complementares da P0

- [Matriz inicial de 53 requisitos rastreáveis](INT-P0-rastreabilidade-requisitos.md): inicia P0-TR-01; ainda requer conferência integral das fontes e inclusão de granularidade.
- [Matriz de autoridades e contratos de fronteira](INT-P0-autoridades-contratos.md): inicia P0-OWN-01/P0-CON-01; ainda exige contratos executáveis, alçadas e revisão por operação.

Esses documentos **não concluem automaticamente** as pendências, que permanecem em revisão.

## Conferência cruzada INT-01 a INT-04 — realizada parcialmente em 02/11/2026

- [Revisão cruzada P0-REV-INT01-04](P0-REV-INT01-04-conferencia-cruzada.md): confronta as sínteses com documentos correlatos IN-124/148/149/150/151/167/168 e a governança INT-R01/R02.
- Não foi localizado o texto integral original das aprovações INT-01 a INT-04. **P0-DOC-01 permanece aberto**, sem declaração de verificação literal nem de ausência definitiva de conflitos.
- INT-01..04 exigem ainda contratos técnicos completos, permissões por operação e rastreabilidade individual (P0-TR/OWN/CON/SEC).

## Conferência cruzada INT-05 a INT-08 — realizada parcialmente em 02/11/2026

- [Revisão cruzada P0-REV-INT05-08](P0-REV-INT05-08-conferencia-cruzada.md): relaciona INT-05/06/07/08 às políticas IN-118, IN-122/123, IN-124/125, IN-127/128/147/148/149, IN-150/151 e IN-162.
- **Lacuna de referência identificada:** o resumo INT-08 deve vincular expressamente a Central de Backup e Portabilidade IN-118, com autoatendimento essencial em todos os planos (inclusive Básico); não significa alterar sua competência ou as regras comerciais aprovadas.
- **P0-DOC-01 permanece pendente** da comparação literal de todas as quatro aprovações originais; não marcar como concluída. Contratos por operação, quotas, autorização, portabilidade e critérios HT permanecem em especificação.

## Conferência cruzada INT-09 a INT-12 e conciliação transversal — 02/11/2026

- [P0-REV-INT09-12](P0-REV-INT09-12-conferencia-cruzada.md): 22 verificações das quatro INTs, incluindo estados, compensações, observabilidade, homologação e auditoria.
- [P0-REC](P0-REC-conciliacao-transversal.md): estado de todos os P0 e critérios G01–G06, consolidando **48 verificações cruzadas** dos três grupos.
- **Nenhuma pendência P0 foi declarada encerrada**: documentação literal integral, contratos por operação, matriz de permissões e evidências de aceite permanecem abertas.
- A Central de Observabilidade IN-159/160 não é duplicada pela INT-10. A retenção técnica IN-162 não define automaticamente a retenção de fatos de negócio sob Auditoria Geral.

## Complementos de especificação e proveniência — 02/11/2026

- [P0-CON — Contratos técnicos compartilhados (minuta)](P0-CON-contratos-tecnicos-minuta.md): 11 famílias de contrato propostas, campos conceituais, estados e classes candidatas de erro. **P0-CON-01 segue aberto** para schemas finais, transições e parâmetros por operação.
- [P0-SEG — Autorizações e alçadas (minuta)](P0-SEG-matriz-autorizacoes-proposta.md): critérios de autorização contextual, revalidação e divisão de competência. **P0-OWN-01 e P0-SEC-01 seguem abertos** até matriz granular por ação/campo/identidade.
- [P0-ORIGEM — Aceites disponíveis no diálogo](P0-ORIGEM-aprovacoes-dialogo.md): transcrição de aprovações expressas de INT-10, INT-11, INT-12, INT-R01 e INT-R02, sem fonte imutável de mensagem e sem cotejo integral das propostas. **P0-DOC-01 e P0-TR-01 seguem abertos.**
- Nenhum nome de campo, enum, código de erro, política numérica ou alçada adicional das minutas deve ser tratado como aprovado sem deliberação expressa.

## Detalhamento de P0-TR / P0-OWN / P0-CON / P0-SEC — 02/11/2026

- [P0-TR53 — Vínculos dos 53 requisitos iniciais](P0-TR53-vinculo-contratos-aceite.md): **53/53 requisitos previamente identificados** receberam CT candidatas, INV, HT e evidência esperada. **Não** atesta proveniência original exaustiva, validação de 53 requisitos do projeto completo ou homologação.
- [P0-OP — 12 casos operacionais de referência](P0-OP-casos-operacionais-contratos-alcadas.md): para cada INT, descreve ato representativo, proprietário, precondições, fonte da confirmação, recusa e CT/HT. São casos de referência, **não** catálogo completo de APIs ou ações e não constituem módulos novos.
- **P0-TR-01, P0-OWN-01, P0-CON-01, P0-SEC-01 permanecem abertos/parciais**; faltam textos originais, exaustividade, operações granulares, schemas e parâmetros finais, alçadas por ação, códigos e evidência de aceite.

## CT-03/CT-04 — comandos e resultados (02/11/2026)

- [P0-CON CT-03/CT-04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md): minuta detalhada com identidades, dimensões de estados, transições **propostas**, retentativas condicionadas, controle de concorrência, reconciliação e 12 exemplos de aceite documental.
- **Ainda em aberto:** P0-CON-01 (schema/enum final), P0-SEC-01 e P0-OWN-01 (permissões/alçadas por operação), P0-VER-01 (compatibilidade), P0-ACC-01 (planos de teste), P0-DOC-01 e P0-TR-01 (integralidade das origens).
- **Aprovação do avanço de etapa não equivale à aprovação automática das escolhas detalhadas nesta minuta.** Nenhum teste foi executado.

## Regra para tratar divergências
Registrar ID, seção, fonte da aprovação, versão documental, conflito, impacto, decisão expressa necessária, responsável, situação e resolução. Não escolher silenciosamente entre decisões conflitantes.

## Exemplos de tensões a verificar (não são conflitos definitivamente constatados)
- IN-162 define retenção de logs técnicos; não aplicar automaticamente a contratos, pagamentos e evidências com regime próprio.
- IN-124/148/149 exigem confirmação de entrega efetiva de lead ao CRM; não equiparar a simples recebimento nas APIs.
- IN-167 estabelece níveis de autonomia e aprovação para agentes técnicos; INT-09/10 não criam outra Central de Aprovações nem aprovação dupla para a mesma decisão.
- Arquitetura n8n + filas + Laravel requer fronteiras técnicas e menor privilégio; não assumir infraestrutura contratada ou instalada.

## Regras de status
**Aprovação funcional** não implica **documentação literal conferida**. **Versão no GitHub** não implica **revisão final**. **Plano de teste** não implica **homologação realizada**. Não avançar para programação sem aprovação final do planejamento completo do CRM.
