# P0-REC — Reconciliação transversal das INT-01 a INT-12

**Data:** 2026-11-02.
**Natureza:** fechamento da **rodada de conferência cruzada** documental P0, não fechamento integral das pendências P0.
**Estado:** rascunho de consolidação e encaminhamento para validação documental. As INT-01 a INT-12, INT-R01 e INT-R02 estão aprovadas funcionalmente; **não estão declaradas conferidas literalmente, completas ou homologadas**.
**Fase:** somente planejamento/documentação. Sem programação, criação de automações, implantação, alteração em produção ou contratação.

## 1. Evidência reunida

| Grupo | Relatório conferido | Cobertura da conferência cruzada | Limite de validação |
|---|---|---|---|
| INT-01 a INT-04 | [P0-REV-INT01-04](P0-REV-INT01-04-conferencia-cruzada.md) | 10 comparações com decisões INT-R01/R02 e documentos adjacentes | Aprovações integrais originais não cotejadas |
| INT-05 a INT-08 | [P0-REV-INT05-08](P0-REV-INT05-08-conferencia-cruzada.md) | 16 comparações; identificada necessidade de vínculo explícito INT-08/IN-118, já documentado | Originais ainda não integralmente cotejados |
| INT-09 a INT-12 | [P0-REV-INT09-12](P0-REV-INT09-12-conferencia-cruzada.md) | 22 comparações de autoridade, estados, observabilidade, conectores e auditoria | Referências imutáveis às mensagens integrais e tabelas específicas pendentes |

**Cobertura realizada: 48 verificações cruzadas documentadas.** Isso representa conferência de amostras/regras relacionadas, **não** 48 requisitos aprovados nem cobertura exaustiva de todas as decisões.

## 2. Matriz consolidada dos P0 (sem aprovação presumida)

| Identificador | Situação constatada | Evidência atual | Para declarar concluído |
|---|---|---|---|
| P0-DOC-01 | **Aberto** | INT-01..12 resumidas, três revisões cruzadas; ausência de cotejo literal integral | Capturar origem integral, conferir exceções, títulos, números, condições e corrigir eventuais divergências mediante decisão |
| P0-DOC-02 | **Parcial** | INT-R01/R02 aprovadas, versionadas e referenciadas | Conferir cláusula a cláusula com todas as INTs e serviços centrais |
| P0-TR-01 | **Parcial** | [53 requisitos iniciais com IDs](INT-P0-rastreabilidade-requisitos.md) | Incluir **todos** os requisitos granulares, fontes oficiais, versão, contrato, invariantes e HT; não presumir que 53 seja total final |
| P0-OWN-01 | **Parcial** | [Matriz de autoridades](INT-P0-autoridades-contratos.md) | Mapear operação/recurso/campo, tenant, autorizações, alçadas, executor e fonte oficial de confirmação |
| P0-CON-01 | **Aberto** | INT-R01 define campos conceituais de contratos | Especificar contratos tipados/versionados de comando, evento, resultado, erro e compensação, com semântica verificável |
| P0-SEC-01 | **Parcial** | Diretrizes e cruzamento IN-150/151/167 | Regras por operação/escopo/identidade, controle de revogação, MFA quando aplicável, segredos e isolamento |
| P0-DEP-01 | **Parcial** | Fronteiras INT-01..12 e motor central catalogadas | Verificação de ciclos, serviços duplicados, dependências críticas e políticas por domínio |
| P0-VER-01 | **Aberto** | Regras gerais de versionamento sem mutação silenciosa | Matriz de compatibilidade por conector, esquema, contrato, modelo e instância; migração/retirada versionada |
| P0-ACC-01 | **Parcial** | 18 cenários HT catalogados | Procedimentos de homologação com pré-condições, inputs, evidências e responsáveis; testes reais só futuramente |
| P0-XREF-01 | **Parcial** | Três relatórios de revisão com documentos IN correlatos | Revisão cruzada completa de IN/PORTAL/CRM/COM/FIN/CON e políticas aprovadas, sem interpretação conflitante |
| P0-GIT-01 | **Parcial** | Arquivos e commits verificáveis | Revisão final, versão congelada/referenciada e **aceite expresso**; commits de trabalho não equivalem a aprovação final |

Nenhuma das 11 pendências fica encerrada automaticamente por esta matriz. “Parcial” indica material documentado sem cumprimento do critério de aceite completo.

## 3. Fronteiras de maior atenção

### INT-01/02 × INT-11 × Identidade e Segurança
- **Conector publicado/homologado** ≠ **conexão cadastrada** ≠ **autorização concedida**.
- Cofre de segredos central da IN-151 não deve ser duplicado; autorizações externas subordinadas à identidade/segurança central.
- A mudança de CRM vinculado por chamado (IN-122/123) não migra dados e não redireciona silenciosamente as submissões anteriores (IN-124/149).

### INT-03/04 × INT-06/07/08
- Mapeamento transforma dados sem decidir resultados de negócio; operações técnicas tipadas exigem autorização e confirmação proprietária.
- Webhook/HTTP 2xx/202 e checkpoints não equivalem à persistência final em CRM.
- INT-07 controla sincronização contínua; INT-08 as movimentações delimitadas.
- **IN-118 assegura portabilidade essencial por autoatendimento em todos os planos, inclusive Básico:** importação/exportação CSV/XLSX, backup completo, restauração compatível e migração autorizada, sem cobrança adicional por operação. INT-08 deve interoperar sem absorver ou duplicar toda a Central de Backup.
- Quota de anexos cheia não bloqueia exportação de registros existentes; arquivos temporários de backup e dados estruturados têm regras próprias.

### INT-09 × INT-10 × INT-11 × INT-12
- INT-09 coordena dependências e compensações aprovadas sem tomar poderes de FIN, CON, CAT, VEN, AGD, ATD, COM, PVD, RET, MKT, CMS e demais domínios.
- INT-10 especializa monitoramento e recuperação usando IN-159/160 e a estrutura central de incidentes. IA não decide ações críticas por conta própria; executor determinístico pode seguir playbook homologado/autorizado conforme políticas técnicas.
- INT-11 homologa conectores por versão, sem publicação livre de terceiros nem mudança unilateral de provedor crítico.
- INT-12 correlaciona evidências e investiga pela Auditoria Geral; não manter trilha paralela nem reaplicar indistintamente retenções de debug/telemetria a eventos oficiais de negócio.

## 4. Precedência por tipo de registro

| Categoria | Autoridade/princípio | Não misturar com |
|---|---|---|
| Payload temporário de lead de Sites Imagine | IN-124/127/128/147/148/149 e retenções específicas | Dados definitivamente persistidos no CRM |
| Logs, traces e métricas técnicas | IN-159/160/162, categoria, finalidade e retenção específica | Registros estruturados obrigatórios de decisões/auditoria de negócio |
| Evidências de auditoria e resultados proprietários | Auditoria Geral e políticas específicas do domínio/obrigações aplicáveis | Logs de debug e prazo técnico genérico |
| Backups de cliente/portabilidade | IN-118 + Arquivos e INT-08 quando aplicável | Backups operacionais do SaaS ou dos Sites Imagine |
| Arquivos/anexos comerciais | Franquias 1/5/20/50 GB; alertas 80/95%; aos 100% só novos anexos | Registros estruturados e operações essenciais |
| IA e agentes técnicos | Supervisão, playbooks, ferramentas e autorizações existentes (IN-167/168 etc.) | Autonomia de negócio irrestrita ou autorização criada por prompt |

## 5. Verificação G01–G06

| Critério de encerramento INT-R02 | Status nesta rodada | Evidência/falta |
|---|---|---|
| G01 — Decisões rastreadas | **Parcial** | 53 requisitos iniciais e relatórios cruzados; faltam todas as referências originais e cobertura exaustiva |
| G02 — Documentos completos | **Parcial** | 12 sínteses + R01/R02 versionadas; falta documentação integral literal |
| G03 — Dependências verificadas | **Parcial** | Fronteiras de competências coerentes na amostra; contratos e riscos de duplicação a concluir |
| G04 — Pendências classificadas | **Parcial** | Matriz P0 detalhada, prioridades P1/P2 conhecidas; faltam responsáveis e desfechos precisos por item |
| G05 — Aceite documental final | **Pendente** | Não houve deliberação final de aceite da versão integral do módulo |
| G06 — Versionamento final confirmado | **Pendente** | Commits de trabalho existem; falta versão aprovada/referência de fechamento final |

**Consequência:** o conjunto INT não pode ser declarado **documentalmente encerrado** ou **tecnicamente homologado**. A aprovação funcional continua válida sem reabrir decisões anteriormente tomadas.

## 6. Ordem objetiva dos próximos trabalhos P0
1. **Proveniência e integralidade (P0-DOC-01, P0-TR-01):** recuperar os textos originais das INTs e vincular IDs, inclusive todas as enumerações de estado INT-09/10/11/12. Não inventar fonte imutável não disponível.
2. **Contratos por operação (P0-OWN-01, P0-CON-01, P0-SEC-01):** especificar as classes tipadas, autorização contextual, estado esperado, idempotência, resultado e referência de confirmação; manter política técnica em documento, sem código.
3. **Versionamento, dependências e auditoria (P0-VER-01, P0-DEP-01, P0-XREF-01):** resolver matriz de compatibilidade e classificação de retenção; impedir motores e trilhas paralelos.
4. **Planos de teste (P0-ACC-01):** detalhar HT-01 a HT-18 com critérios e evidências, não executá-los.
5. **Fechamento controlado (G01–G06/P0-GIT-01):** conferir versão e solicitar aceite expresso somente após os critérios acima. O projeto CRM geral permanece bloqueado para programação até aprovação final de todo o planejamento.

## 7. Documentos adicionais P0 em minuta

- [P0-ORIGEM](P0-ORIGEM-aprovacoes-dialogo.md): transcrições literais de cinco mensagens de aceite disponíveis; melhor rastreabilidade parcial, ainda sem ID de origem imutável ou proposta integral.
- [P0-CON](P0-CON-contratos-tecnicos-minuta.md): classes CT-01..CT-11, envelope conceitual, estados de resultado e categorias de erro **propostas para validação**.
- [P0-SEG](P0-SEG-matriz-autorizacoes-proposta.md): dimensões de autorização e pontos de revalidação **para revisão**.

O estado permanece **parcial/aberto** para P0-DOC-01, P0-TR-01, P0-OWN-01, P0-CON-01, P0-SEC-01. Os critérios G01..G06 não mudam para concluído por mera existência das minutas.

## 8. Detalhamento por requisito e por caso operacional

- [P0-TR53](P0-TR53-vinculo-contratos-aceite.md): todas as **53 linhas iniciais** da matriz TR foram associadas, de modo **proposto**, a CT-01..11, INV e HT, com evidência esperada. Não equivale a fonte literal/cobertura exaustiva.
- [P0-OP](P0-OP-casos-operacionais-contratos-alcadas.md): **12 casos ilustrativos** com fronteira de autoridade, pré-condição, confirmação proprietária e falha negativa esperada. Não representam todas as operações do módulo.
- O registro documental não altera nenhum status final dos 11 P0 ou G01..G06. Continuam exigidas as propostas originais, matriz completa de autorizações por operação, schemas e aceite expresso.

## 9. Minuta detalhada CT-03/CT-04

- [P0-CON CT-03/CT-04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md) detalha comandos/resultados, separa quatro dimensões conceituais (transporte, processamento técnico, negócio e reconciliação), propõe condições de transição e 12 casos documentais de aceite.
- **As dimensões e transições são propostas de representação, não um motor central paralelo, nem validação final.** Regras já aprovadas: autorização contextual, idempotência, resultado desconhecido exige reconciliação, módulo proprietário confirma atos, IA sem autonomia crítica.
- Todos os G01–G06 permanecem no estágio já indicado; nenhum critério foi concluído por escrever a minuta.

## 10. Avanço documental CT-03/CT-04 e fichas operacionais

- [Aceite literal CT-03/CT-04](P0-DEC-aprovacao-CT03-CT04.md): princípios e representação documental de comandos/resultados passam a **aprovados**, sem autorização de implementação ou de parâmetros numéricos.
- [24 fichas operacionais](P0-OP24-detalhamento-CT03-CT04-por-operacao.md): propostas para decompor responsabilidades e resultados, sem ampliar escopo ou considerar exaustividade.
- Os 11 P0 e os critérios G01..G06 ainda não foram encerrados. Contratos executáveis, vínculo literal a todas as decisões e testes técnicos continuam pendentes.

## 11. Matriz contextual CT-02 e validação da base OP24

- [Decisão OP24](P0-DEC-aprovacao-OP24-base.md): 24 operações de referência aceitas como base documental, sem conceder privilégios ou encerrar pendências P0.
- [CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md): proposta de permissões/alçadas contextuais para 24 operações, com casos candidatos de validação negativa, não testados.
- P0-OWN-01, P0-SEC-01, P0-CON-01, P0-TR-01, P0-DOC-01, P0-ACC-01 e G01–G06 **não** se tornam concluídos por existir a matriz.

## 12. CT-02 aceita como base e matrizes complementares (03/11/2026)

- [Decisão contextual CT-02](P0-DEC-aprovacao-CT02-base.md): princípios aceitos como base, parâmetros de segurança/alçadas ainda sujeitos a decisão.
- [P0-SEC](P0-SEC-politicas-autorizacao-CT02.md): 24 casos de revisão contextual, revalidação de atos protegidos e cenários candidatos, **sem conceder novas permissões**.
- [P0-VER](P0-VER-matriz-compatibilidade-evolucao-versoes.md): comparação de CT-01..11 e das 24 fichas, sem migrações implícitas nem publicação autônoma.
- **Estados preservados:** P0-SEC-01/P0-OWN-01/P0-VER-01 seguem abertos/parciais; G01..G06 inalterados. Nenhum código e nenhum teste técnico realizado.

## 13. Aceite SEC/VER e avanço de P0-ACC (03/11/2026)

- [Aprovação expressa SEC/VER como bases](P0-DEC-aprovacao-SEC-VER-base.md), mantendo escolhas específicas de segurança/versionamento pendentes.
- [18 planos HT](P0-ACC-planos-homologacao-HT01-HT18.md): procedimentos em minuta, **não executados** e ainda não validados como roteiros finais.
- [Controle das evidências HT](P0-ACC-matriz-controle-evidencias-HT01-HT18.md): matriz de responsáveis funcionais, dependências e campos a completar, sem resultados de teste.
- **P0-ACC-01 continua parcial/aberto e G01–G06 permanecem pendentes**; aceite funcional de cenários não é homologação técnica ou liberação da implementação.

## 14. Restrição permanente
**Esta conciliação é trabalho documental, não nova INT, motor ou serviço.** Sem alteração de regras comerciais, automações, módulos proprietários, infraestrutura ou dados de produção.