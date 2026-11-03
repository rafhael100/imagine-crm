# P0-DOC-TR — Lacunas candidatas de granularidade e recuperação de fontes originais

**Data:** 2026-11-03.
**Estado:** **REGISTRO DE COMPARAÇÃO CONTRA SÍNTESES**, não verificação literal integral das propostas/aceites originais. Nenhuma das linhas representa uma nova regra funcional aprovada.
**Fontes examinadas:** [INT-01 a INT-12](README.md), [53 TR iniciais](INT-P0-rastreabilidade-requisitos.md), [rastreabilidade proposta TR→CT/HT](P0-TR53-vinculo-contratos-aceite.md), [auditoria TR](P0-TR-AUD-auditoria-proveniencia-requisitos.md), revisões P0-REV, decisões já documentadas IN e [registro de aceites](P0-ORIGEM-aprovacoes-dialogo.md).
**Fase:** somente planejamento; não programar, não executar teste, não criar nova automação e não reclassificar prioridades comerciais ou parâmetros.

## 1. O que foi efetivamente comparado

- **53 identificadores TR** existentes confrontados contra descrições dos 12 arquivos INT e normas/planos transversais.
- Todos os 12 arquivos INT declaram explicitamente ser **sínteses funcionais**, em vez de transcrições literais da deliberação.
- Há temas já mencionados nas sínteses com **múltiplas subcondições reunidas em poucos TR**. Portanto, é necessário avaliar se cada subcondição merece um requisito próprio com fonte primária e critério de aceitação específico.
- **28 verificações candidatas** abaixo: não são 28 novas funcionalidades, 28 contradições ou 28 requisitos já descobertos. São temas para cotejo literal e possível desdobramento controlado, somente **depois** de recuperar a decisão original.

## 2. Matriz de lacunas candidatas por módulo

| Módulo | ID de investigação (não TR) | Tema a conferir | TR atualmente relacionadas | Tipo de lacuna no inventário inicial | Fonte secundária encontrada |
|---|---|---|---|---|---|
| INT-01 | P0-GAP-001 | Estados e transições de conexão por tenant/unidade/marca | TR-INT01-001/002 | Parcial: ciclo de vida listado, transições finas ausentes | INT-01.md, cadastro e ciclo de vida |
| INT-01 | P0-GAP-002 | Impacto de alteração/desativação sobre comandos antigos e destinos de lead | TR-INT01-004 | Parcial: preservação de pendências sem matriz de casos | INT-01.md + IN-124/149 |
| INT-02 | P0-GAP-003 | Consentimentos exigidos, revogação, rotação e expiração por provedor e finalidade | TR-INT02-001/003 | Parcial: critérios por provedor não explicitados | INT-02.md |
| INT-02 | P0-GAP-004 | Reuso de aprovação humana e mudança de escopo durante execução assíncrona | TR-INT02-004/003 | Parcial: sem alçadas e vigência por ato | INT-02.md + P0-SEC |
| INT-03 | P0-GAP-005 | Taxonomia de erros de validação campo-a-campo, enums, normalização e compatibilidade | TR-INT03-001/002/004 | Parcial: família de transformações resumida | INT-03.md |
| INT-03 | P0-GAP-006 | Previews sem escrita para operações futuras e fontes distintas | TR-INT03-003 | Parcial: resultados de prévia e permissões não categorizados | INT-03.md |
| INT-04 | P0-GAP-007 | Catálogo de operações e capacidades homologadas por recurso/efeito/versão | TR-INT04-001/004 | Parcial: campos conceituais, sem operações exaustivas | INT-04.md + P0-OP24 |
| INT-04 | P0-GAP-008 | Estados de transporte/negócio e resultado desconhecido, retry seguro por provedor | TR-INT04-002/003 | Parcial: CT-03/04 base aprovada, contratos por provedor pendentes | INT-04.md + CT-03/04 |
| INT-05 | P0-GAP-009 | Ciclo de vida/versionamento de aplicação, identidade da API e alterações de escopo | TR-INT05-001/003 | Parcial: permissões do app não detalhadas por ação | INT-05.md |
| INT-05 | P0-GAP-010 | Elegibilidade por plano/trial e exceção das integrações nativas | TR-INT05-002 | Explícito como princípio; lista de endpoints/identidades nativas falta | INT-05.md + IN-124/150 |
| INT-06 | P0-GAP-011 | Fluxo de assinatura/webhook de saída, URL homologada e escopo dos campos | TR-INT06-001/004 | Parcial: recepção, entrega e assinatura agregadas | INT-06.md |
| INT-06 | P0-GAP-012 | Replay/assinaturas, ordenação, prazos, filas de falha e resultado oficial externo | TR-INT06-001/002/003 | Parcial: campos/estados por evento e destino pendentes | INT-06.md + INT-R01 |
| INT-07 | P0-GAP-013 | Direção uni/bidirecional de sincronização, origem oficial por campo, exclusões e conflitos | TR-INT07-001/003/004 | Parcial: não há matriz por entidade/campo | INT-07.md |
| INT-07 | P0-GAP-014 | Retomada por cursor/checkpoint, eventos fora de ordem, ciclos e falha inconclusiva | TR-INT07-002/003 | Parcial: política de reconciliação por origem e destino pendente | INT-07.md |
| INT-08 | P0-GAP-015 | Etapas planejamento/configuração/revisão/aprovação/simulação/suspensão/reconciliação/aceite/encerramento | TR-INT08-001/002/003 | Potencial lacuna de granularidade: TR resumem muitos estados | INT-08.md |
| INT-08 | P0-GAP-016 | Autorizações de movimentações por lote, arquivo, registro, conta, origem/destino e período | TR-INT08-001/003/004 | Parcial: escopos exemplificados, matriz granular ausente | INT-08.md + P0-SEC |
| INT-08 | P0-GAP-017 | Distinção entre importação/exportação/migração, backup integral IN-118 e vínculo de CRM | TR-INT08-001/004/005 | Explícito como fronteira; contrato entre Portabilidade e INT-08 pendente | INT-08.md + IN-118/122/123 |
| INT-08 | P0-GAP-018 | Diferentes formatos CSV/XLSX/JSON e regras de prévia/validação por tipo | TR-INT08-002 | Parcial: suporte depende de contrato e política aprovada, sem presumir compatibilidade universal | INT-08.md |
| INT-09 | P0-GAP-019 | Estados próprios de modelo, instância, etapa, comando, aprovação e compensação | TR-INT09-001/003/005 | Potencial lacuna granular: TR têm estados agregados | INT-09.md + P0-REV-INT09-12 |
| INT-09 | P0-GAP-020 | Caminhos condicionais/paralelos, ciclos/iterações limitadas e convergência oficial | TR-INT09-002 | Parcial: limites e transições por dependência pendentes | INT-09.md |
| INT-09 | P0-GAP-021 | Contingência de provedor externo homologado, autorizado e compatível | TR-INT09-003/004/006 | Potencial sub-requisito não explícito entre 6 TR iniciais | INT-09.md |
| INT-09 | P0-GAP-022 | Compensação irreversível/parcial e cancelamento técnico vs efeito confirmado | TR-INT09-004/005 | Parcial: matriz de atos proprietários pendente | INT-09.md + P0-CON |
| INT-10 | P0-GAP-023 | Saúde desconhecida por telemetria ausente, estados de incidente e recuperação graduada | TR-INT10-001/004 | Parcial: estados e prova temporal pendentes | INT-10.md + IN-159/160 |
| INT-10 | P0-GAP-024 | Playbook autorizado vs decisão autônoma de IA e aprovação humana quando exigida | TR-INT10-002/005 | Parcial: limites por tipo de recuperação e nível N2/N3 pendentes | INT-10.md + IN-167/168 |
| INT-11 | P0-GAP-025 | Estados/versionamento da homologação, manutenção, suspensão e descontinuação | TR-INT11-001/002/004 | Parcial: transições e impacto de versão por conexão pendentes | INT-11.md |
| INT-11 | P0-GAP-026 | Parceiro ≠ provedor ≠ conector ≠ conexão, distribuição sem marketplace/publicação livre | TR-INT11-001/003 | Parcial: contratos por objeto e comercialização pendentes | INT-11.md |
| INT-12 | P0-GAP-027 | Auditoria de auditoria, consultas e exportações com perfis, finalidade e trilhas oficiais | TR-INT12-001/003/004 | Parcial: matrizes de acesso/retenção por categoria pendentes | INT-12.md |
| INT-12 | P0-GAP-028 | Proveniência, integridade, evidência desconhecida e distinção entre auditoria de negócio e log técnico | TR-INT12-001/002/005 | Parcial: política de retenção/ classificação por negócio não concluída | INT-12.md + IN-162 |

## 3. Conferência focal registrada para oito investigações

- [P0-FOCO-INT08-09 — 11 requisitos iniciais / 8 lacunas candidatas](P0-FOCO-INT08-09-confronto-11TR-8lacunas.md) detalha **P0-GAP-015..018** (INT-08) e **P0-GAP-019..022** (INT-09), com fontes secundárias e decisões IN aprovadas relacionadas.
- **Status das oito:** comparadas documentalmente; **nenhuma encerrada mediante cotejo literal de proposta/aceite original**. Não gerar novos TR estáveis sem recuperar as fontes primárias.

## 4. Regras para tratar cada linha sem inventar decisões

| Resultado possível do cotejo futuro | Tratamento previsto |
|---|---|
| Decisão original já está integralmente representada por TR existente | Marcar a linha como coberta e citar a cláusula/versão de origem |
| Decisão original contém subcondição adicional ainda não destacada | Propor TR nova e ligação à origem/CT/HT; manter aprovação original válida |
| Requisito da síntese vai além do texto aprovado | Suspender interpretação ampliada e submeter diferença ao responsável |
| Há divergência entre duas decisões originais válidas | Registrar cronologia, escopo, precedência, impacto e necessidade de decisão expressa |
| Fonte primária integral inacessível | Marcar **FONTE PENDENTE**, não declarar regra reconstruída como citação literal |
| Parâmetro técnico não foi definido | Marcar **PARÂMETRO PENDENTE**, não preencher números por estimativa |
| Foi confirmado somente em decisão transversal posterior | Referenciar decisão posterior distinta, sem reescrever a aprovação anterior |

## 4. Proveniência: disponibilidade real nesta etapa

- Os arquivos **INT-01..12** conferidos são secundários; nenhum contém a transcrição completa da proposta original.
- [P0-ORIGEM](P0-ORIGEM-aprovacoes-dialogo.md) possui textos de aceite de INT-10/11/12 e INT-R01/R02, mas **não** as propostas inteiras às quais esses aceites se referem.
- Há **indícios históricos** de mensagens de aprovação INT-01, INT-07, INT-08 e INT-09 no histórico conversacional de 02/11/2026. A recuperação atual encontrou metadados/resumos e trechos, **não** corpos integrais verificáveis adequados à transcrição literal; portanto **nenhuma** dessas propostas/aceites é tratada aqui como recuperada por completo.
- Não atribuir a uma mensagem original URL, hash, transcrição ou exata data/hora que não foram verificados.
- Os **aceites de bases documentais posteriores** (CT-03/04, CT-02, OP24, P0-SEC, P0-VER, P0-ACC) continuam válidos e registrados; não preenchem o lugar dos textos originais das INTs.

## 5. Atividades necessárias para fechar P0-DOC-01 e P0-TR-01

1. Recuperar a proposta e a mensagem de aceite de cada INT-01..12, bem como as propostas integrais INT-R01/INT-R02.
2. Garantir referência primária estável ou cópia documental verificável do texto, identificando origem e versão (sem expor dados sensíveis).
3. Confrontar cláusula a cláusula com a síntese e com cada TR, resolvendo as 28 verificações candidatas e procurando outras.
4. Atribuir TR estável somente a um requisito real identificado, com fonte precisa, proprietário, contrato CT, invariantes INV, plano HT e evidência.
5. Registrar eventuais conflitos no histórico documental para decisão expressa; **não presumir aprovação pelo silêncio**.
6. Conferir a matriz integral e obter aceite final; não iniciar a programação até o planejamento total do CRM estar aprovado.

## 6. Resultado

- **28 verificações de granularidade cadastradas**: INT-01 2; INT-02 2; INT-03 2; INT-04 2; INT-05 2; INT-06 2; INT-07 2; INT-08 4; INT-09 4; INT-10 2; INT-11 2; INT-12 2.
- **Nenhuma reivindicação de inconsistência literal comprovada**.
- **P0-DOC-01 e P0-TR-01 permanecem ABERTOS/PARCIAIS**. 53 TR iniciais são ponto de partida, não inventário final.
- Não há nova INT, contrato, preço, permissão, automação, implementação, teste ou alteração de produção criada por esta revisão.
