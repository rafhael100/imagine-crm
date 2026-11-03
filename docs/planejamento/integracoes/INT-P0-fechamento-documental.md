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

## Decisão aprovada e detalhamento operacional — 02/11/2026

- [Aprovação CT-03/CT-04](P0-DEC-aprovacao-CT03-CT04.md): decisão expressa registrada, definindo a especificação como **base documental aprovada**, sem aprovação de schema físico, alçadas, timeout, quotas e versão executável.
- [P0-OP24 — 24 fichas operacionais de referência](P0-OP24-detalhamento-CT03-CT04-por-operacao.md): dois casos representativos por INT, com proprietário, pré-condição, autorização, resultado técnico/oficial, idempotência, falha, reconciliação e correspondência CT/TR/HT. **Não são novos módulos, nem catálogo exaustivo**.
- **Pendências P0 permanecem abertas:** o aceite documental da base CT-03/CT-04 não encerra P0-CON-01 (contratos executáveis completos), P0-TR-01 (origem integral), P0-OWN-01/SEC-01 (matriz granular), P0-VER-01 ou P0-ACC-01 (testes planejados ainda não executados).

## CT-02 e aceitação das 24 fichas como base documental (02/11/2026)

- [Decisão contextual P0-OP24](P0-DEC-aprovacao-OP24-base.md): a resposta “ok” valida o uso das 24 fichas como **base de documentação** e a continuidade ao CT-02. Não aprova direitos novos, alçadas, campos físicos ou limites.
- [P0-CT02 — Autorização contextual e alçadas por operação](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md): matriz **em proposta** com 24 operações, fronteiras de segurança/negócio, revalidação, impedimentos e 12 roteiros candidatos de validação negativa.
- **Continuam abertos:** P0-OWN-01/P0-SEC-01 (RBAC, perfis, alçadas, recurso/campo), P0-CON-01 (schema físico), P0-TR-01/P0-DOC-01 (cotejo integral), P0-ACC-01 (testes), P0-VER-01 (versões), P0-GIT-01 (aceite final). Nenhum teste executado.

## Política CT-02 e compatibilidade de versões — 03/11/2026

- [P0-DEC — CT-02 aceita como base documental](P0-DEC-aprovacao-CT02-base.md): aceite contextual **não aprova** direitos/perfis, MFA adicional, limites e alçadas por operação.
- [P0-SEC — Políticas de autorização contextual](P0-SEC-politicas-autorizacao-CT02.md): 24 operações com condições propostas de acesso/revalidação, competência do autorizador/proprietário e oito verificações documentais futuras. **P0-OWN-01 e P0-SEC-01 continuam abertos.**
- [P0-VER — Compatibilidade e evolução de versões](P0-VER-matriz-compatibilidade-evolucao-versoes.md): famílias CT-01..CT-11, cobertura dos 24 casos por INT, oito cenários futuros e regras propostas de evolução sem mutação silenciosa. **P0-VER-01 segue aberto.**
- A escrita de minutas ou aceite da base CT-02 não encerra P0-DOC/TR/CON/DEP/ACC/GIT, G01–G06 ou homologação, e não permite implementação.

## P0-SEC/P0-VER aprovadas como bases e planos P0-ACC criados (03/11/2026)

- [P0-DEC — Aceite expresso de SEC e VER](P0-DEC-aprovacao-SEC-VER-base.md): aprovação como **bases documentais**, com alçadas, perfis, MFA, parâmetros, versões executáveis e demais definições ainda pendentes.
- [P0-ACC — Planos HT-01 a HT-18](P0-ACC-planos-homologacao-HT01-HT18.md): **18 procedimentos de homologação redigidos em minuta**, com pré-condições, etapas futuras, verificações negativas, resultados, evidências e critérios de aceite; **nenhum teste executado**.
- [P0-ACC — Matriz de evidências e aceite](P0-ACC-matriz-controle-evidencias-HT01-HT18.md): status individual dos 18 planos, responsabilidades funcionais e evidências futuras, sem preenchimento de PASS/FAIL.
- **P0-ACC-01 passa a possuir documentação inicial estruturada, mas continua ABERTO/PARCIAL**, até conferir integralmente requisitos e aprovar os procedimentos finais. P0-DOC/TR/CON/SEC/OWN/DEP/VER/XREF/GIT e G01..G06 ainda exigem suas comprovações.

## Aceite dos planos HT e auditoria de rastreabilidade/dependências (03/11/2026)

- [P0-DEC — HT como base](P0-DEC-aprovacao-ACC-HT01-HT18-base.md): 18 roteiros aceitos enquanto base, **nenhum teste executado**, P0-ACC-01 ainda aberto.
- [P0-TR-AUD](P0-TR-AUD-auditoria-proveniencia-requisitos.md): 53/53 IDs reconciliados entre inventários iniciais, 18 HT e 16 INV citados, sem comprovar exaustividade das aprovações originais.
- [P0-DEP](P0-DEP-dependencias-criticas-e-fronteiras.md): 12 INTs e 11 pontos de contratos/circularidade em revisão, sem afirmar falhas reais.
- [P0-GATE](P0-GATE-painel-fechamento-rastreabilidade-dependencias.md): 11 bloqueios P0 e G01–G06 com evidências faltantes, sem encerramento de gates.
- P0-DOC-01/02, P0-TR-01, P0-DEP-01 e demais P0 continuam abertos ou parciais; programação proibida.

## Investigação de lacunas entre sínteses e 53 TR (03/11/2026)

- [P0-DOC-TR](P0-DOC-TR-deltas-candidatos-fonte-original.md): **28 temas candidatos** a cotejo de granularidade, especialmente INT-08 (4) e INT-09 (4); **não** são novas funcionalidades aprovadas nem inconsistências comprovadas.
- A conferência da origem literal ainda depende das propostas/aceites integrais INT-01..12 e INT-R01/R02. Registros de texto preservado no GitHub e resumos de histórico não substituem verificação primária.
- **P0-DOC-01 e P0-TR-01 seguem ABERTOS/PARCIAIS**. Não emitir novos TR definitivos sem evidência de requisito efetivamente aprovado e revisão de sua relação CT/INV/HT.

## Conferência focal INT-08/INT-09 — 03/11/2026

- [P0-FOCO-INT08-09](P0-FOCO-INT08-09-confronto-11TR-8lacunas.md) verificou **8/8 lacunas candidatas** contra sínteses e decisões IN correlatas e preservou **11/11 TR existentes** (INT-08 5; INT-09 6) sem transformar perguntas de granularidade em requisitos novos.
- **Fonte primária ainda pendente:** a íntegra das propostas/aceites originais de INT-08/09 não foi obtida para cotejo literal. O relatório **não fecha** essas 8 verificações por literalidade e **não altera P0-DOC-01/P0-TR-01** para concluído.
- Fronteiras confirmadas em documentos aprovados independentes: INT-08 não substitui IN-118; mudança de vínculo CRM por chamado não migra dados automaticamente; INT-09 não substitui proprietários na confirmação de negócios, não realiza compensação livre nem contorna autorizações.

## CT-05 a CT-11 — Minutas de contratos de interoperabilidade (03/11/2026)

- [P0-CON CT-05 a CT-11](P0-CON-CT05-CT11-contratos-integracoes-minuta.md): sete fichas conceituais para webhooks/eventos, sync/checkpoint, lote/portabilidade, processo/instância, incidente/recuperação, compensação e auditoria, com estados **propostos**, falhas, reconciliação e referências a requisitos/invariantes/testes existentes.
- **Não houve aceite desta minuta**, criação de automação nem implementação. CT-02 e CT-03/04 continuam bases documentais aceitas; P0-SEC/P0-VER/ACC seguem bases documentais aprovadas nos respectivos limites.
- **P0-CON-01 permanece parcial/aberto**: detalhamento físico, semântica por operação/provedor, propriedades, políticas de erro, parâmetros, riscos/alçadas e cotejo literal de decisões ainda pendentes. G01–G06 e demais P0 não foram encerrados.

## Aceite CT-05..11 e conferência entre os 11 contratos (03/11/2026)

- [P0-DEC — aceite contextual CT-05 a CT-11](P0-DEC-aprovacao-CT05-CT11-base.md): bases documentais aprovadas, parâmetros e direitos ainda pendentes.
- [P0-CON — revisão cruzada CT-01 a CT-11](P0-CON-revisao-cruzada-CT01-CT11.md): 11 famílias, 105 vínculos inicialmente propostos entre 53 TR e CT, 13 riscos de fronteira a validar. **Sem 105 novos requisitos** e sem certeza de exaustividade.
- **CT-01 é ainda apenas família conceitual**, sem minuta especializada aceita; a associação TR-INT08-005→CT-10 não autoriza compensação pela INT-08.
- **P0-CON-01, P0-TR-01, P0-OWN-01, P0-SEC-01, P0-DEP-01, P0-VER-01 permanecem abertos/parciais**; G01–G06 não encerrados. Nenhum teste ou programação.

## Minuta CT-01 especializada — 03/11/2026

- [P0-CON CT-01](P0-CON-CT01-conectores-conexoes-capacidades-minuta.md): especificação documental proposta para parceiros/provedores, conexões, conector homologado, capacidade tipada e autorização separada; mantém regras dos proprietários INT-01/02/04/11 e a segurança central.
- **Cobertura nominal CT-01..11:** agora há ficha especializada para CT-01 em **minuta**, CT-02/03/04 e CT-05..11 aceitas como bases documentais; **sem schema final por operação, sem parâmetros aprovados e sem teste**.
- **P0-CON-01 continua parcial/aberto**, assim como P0-OWN/SEC/DEP/VER/DOC/TR/ACC e G01–G06. A apresentação da ficha não é homologação nem cria sistemas.

## CT-01 aceita como base e matriz mestre dos 11 contratos (03/11/2026)

- [P0-DEC CT-01](P0-DEC-aprovacao-CT01-base.md): CT-01 aceita como **base documental**, junto às CT-02..11 anteriormente aceitas. Nenhum contrato físico completo ou nova automação foi aprovado.
- [P0-CON-MASTER](P0-CON-MASTER-matriz-CT01-CT11-criterios-fechamento.md): consolida 11 famílias, 53 TR iniciais, 105 associações propostas, critérios de consistência e evidências E-CON01..09; documento **proposto para revisão**.
- **P0-CON-01 permanece aberto/parcial**: fonte primária, contratos por operação, permissão/alçada, versão/retentativas, dependências e aceite documental final ainda faltam. **G01–G06 não encerrados; nenhum teste executado ou programação iniciada.**

## CON-MASTER aceita e 15 operações de maior impacto detalhadas (03/11/2026)

- [P0-DEC CON-MASTER](P0-DEC-aprovacao-CON-MASTER-base.md): matriz das 11 famílias **aceita como instrumento de acompanhamento documental**, sem aprovar esquemas, perfis, parâmetros ou operações executáveis.
- [P0-CON-OP15](P0-CON-OP15-contratos-operacoes-impacto-elevado.md): 15 fichas priorizadas (autorização/revogação, escrita externa, leads/eventos, portabilidade, orquestração/compensações, recuperação, conectores e auditoria); **propostas para revisão**, sem execução. Outras 9 OP24 ainda precisam de especialização.
- **P0-CON-01, P0-SEC/OWN/DEP/VER/ACC/TR/DOC permanecem abertos/parciais**, G01–G06 não concluídos, e 0/18 testes executados.

## Contratos especializados — 24 operações de referência cobertas (03/11/2026)

- [15 fichas de maior impacto](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) e [9 complementares](P0-CON-OP09-contratos-operacoes-complementares.md) estão **redigidas como propostas**, cobrindo os 24 IDs OP já aceitos como referência, sem criar novas operações ou alçadas.
- [Índice operacional OP24](P0-CON-OP24-indice-consolidado-contratos-por-operacao.md) consolida CT/TR/HT e competências de autorização **candidatas**, distingue base de especificação por operação e registra as lacunas.
- **P0-CON-01/OWN-01/SEC-01/TR-01/ACC-01 e G01..G06 permanecem abertos/parciais**, porque documentação especializada não equivale a aceite final ou contrato físico por provedor. **0/18 HT executados; sem programação.**

## Regra para tratar divergências
Registrar ID, seção, fonte da aprovação, versão documental, conflito, impacto, decisão expressa necessária, responsável, situação e resolução. Não escolher silenciosamente entre decisões conflitantes.

## Exemplos de tensões a verificar (não são conflitos definitivamente constatados)
- IN-162 define retenção de logs técnicos; não aplicar automaticamente a contratos, pagamentos e evidências com regime próprio.
- IN-124/148/149 exigem confirmação de entrega efetiva de lead ao CRM; não equiparar a simples recebimento nas APIs.
- IN-167 estabelece níveis de autonomia e aprovação para agentes técnicos; INT-09/10 não criam outra Central de Aprovações nem aprovação dupla para a mesma decisão.
- Arquitetura n8n + filas + Laravel requer fronteiras técnicas e menor privilégio; não assumir infraestrutura contratada ou instalada.

## Regras de status
**Aprovação funcional** não implica **documentação literal conferida**. **Versão no GitHub** não implica **revisão final**. **Plano de teste** não implica **homologação realizada**. Não avançar para programação sem aprovação final do planejamento completo do CRM.
