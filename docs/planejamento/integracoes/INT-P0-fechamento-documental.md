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

## Regra para tratar divergências
Registrar ID, seção, fonte da aprovação, versão documental, conflito, impacto, decisão expressa necessária, responsável, situação e resolução. Não escolher silenciosamente entre decisões conflitantes.

## Exemplos de tensões a verificar (não são conflitos definitivamente constatados)
- IN-162 define retenção de logs técnicos; não aplicar automaticamente a contratos, pagamentos e evidências com regime próprio.
- IN-124/148/149 exigem confirmação de entrega efetiva de lead ao CRM; não equiparar a simples recebimento nas APIs.
- IN-167 estabelece níveis de autonomia e aprovação para agentes técnicos; INT-09/10 não criam outra Central de Aprovações nem aprovação dupla para a mesma decisão.
- Arquitetura n8n + filas + Laravel requer fronteiras técnicas e menor privilégio; não assumir infraestrutura contratada ou instalada.

## Regras de status
**Aprovação funcional** não implica **documentação literal conferida**. **Versão no GitHub** não implica **revisão final**. **Plano de teste** não implica **homologação realizada**. Não avançar para programação sem aprovação final do planejamento completo do CRM.
