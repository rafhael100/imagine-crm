# P0-GATE — Painel de evidências para fechamento de rastreabilidade e dependências

**Data:** 2026-11-03. **Estado:** painel de acompanhamento documental e riscos para decisão futura. **Não é um certificado de aprovação final** do módulo INT.
**Base:** [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md), [P0-TR-AUD](P0-TR-AUD-auditoria-proveniencia-requisitos.md), [P0-DEP](P0-DEP-dependencias-criticas-e-fronteiras.md), [matriz original P0](INT-P0-fechamento-documental.md), [P0-ACC](P0-ACC-planos-homologacao-HT01-HT18.md) e aceites P0-DEC existentes.
**Restrição:** só planejamento/documentação, sem programação, teste real, nova automação, ambiente operacional, contratação ou alteração de produção.

## 1. Situação dos 11 bloqueios P0

| P0 | Exigência | Evidência hoje disponível | O que continua faltando | Dono FUNCIONAL proposto para fechamento (não pessoa designada) | Situação |
|---|---|---|---|---|---|
| P0-DOC-01 | Aprovações originais INT-01..12 | INTs sintetizadas, revisões cruzadas P0-REV e aceites parciais | Textos integrais das propostas/aceites e exceções, confronto literal cláusula a cláusula | Arquitetura documental INT + responsável pelas decisões | Não atendido |
| P0-DOC-02 | Originais INT-R01/R02 | Normas aprovadas e versionadas, resumos/aceites no GitHub | Confrontar as versões detalhadas originais e todos os contratos/invariantes | Arquitetura INT + proprietários de serviços centrais | Parcial |
| P0-TR-01 | Requisitos e fonte | 53 IDs correspondentes em dois documentos; 18 HT e 16 INV referenciados; auditoria P0-TR-AUD | Exaustividade e proveniência primária por requisito, correção de vínculos e exceções | Arquitetura INT + responsáveis funcionais das INTs | Parcial |
| P0-OWN-01 | Dono e autoridade | Matriz de domínios + 24 fichas OP; CT-02 base documental | Catálogo completo por ação/campo/recurso, alçadas e fonte oficial por efeito | Proprietários FIN/CON/VEN/AGD/ATD/CAT/COM/etc. + Segurança | Parcial |
| P0-CON-01 | Contratos técnicos tipados | CT-03/04 aceitos como base; 11 famílias CT e 24 operações | Esquemas/versionamento, estados/transições/erros/confirmadores por operação e CT-05..11 | INT-04, 09, 10 e serviços centrais/proprietários | Parcial |
| P0-SEC-01 | Autorização contextual | CT-02 e P0-SEC aprovadas como base; cenários negativos | Direitos reais por perfil/recurso/campo; MFA/alçadas/expiração/delegação | Identidade e Segurança + INT-02 + Central de Aprovações | Parcial |
| P0-DEP-01 | Dependências | P0-DEP: 12 INT, 11 riscos de sobreposição/circularidade, 9 serviços centrais | Arestas tipadas por fase, bootstrap, falha segura, sem loops não controlados | Arquitetura INT + serviços centrais + módulos proprietários | Parcial |
| P0-VER-01 | Compatibilidade | P0-VER aprovado como base; CT-01..11 versionáveis | Critérios por versão/operação, migrações/convivência, pin e mudanças de autorização | INT-11/03/04/09 + Segurança e proprietários | Parcial |
| P0-ACC-01 | Planos HT e evidências | 18/18 roteiros e matriz de evidências aceitos como base; 0/18 executados | Aceite documental final de procedimentos, fontes completas, responsáveis/ambientes/params; homologação técnica futura | Arquitetura INT + Segurança + Auditoria Geral + proprietários | Parcial |
| P0-XREF-01 | Políticas adjacentes | Cruzamento inicial com IN-118/124/148/149/151/159/160/162/167/168 | Vínculos exigíveis por TR/CT/OP e sem conflitos/duplicações nos demais blocos | Portal Imagine, Sites, CRM e serviços centrais | Parcial |
| P0-GIT-01 | Fechamento imutável | Múltiplos commits documentais GitHub verificados em main | Revisão final, aprovação expressa da versão completa, SHA imutável e gate G06 | Responsável pela arquitetura e repositório | Pendente |

**Nota:** os responsáveis são **competências e equipes lógicas de revisão**, não designações oficiais de pessoas, criação de cargos ou aprovação de alçadas. Cada P0 requer decisão final documentada e confirmação de sua evidência de conclusão. O estado “parcial” **não** libera G05/G06 nem programação.

## 2. Critérios formais G01–G06

| Gate | Necessidade INT-R02 | Evidência parcial | Bloqueio específico | Situação |
|---|---|---|---|---|
| G01 | Decisões com fonte | P0-TR-AUD, P0-ORIGEM, P0-DEC | Faltam propostas integrais INT-01..12, R01/R02 e fonte por requisito | Parcial / não satisfaz gate |
| G02 | Documentação integral | INT-01..12, R01/R02 e fichas P0 | As sínteses ainda não são equivalentes aos textos completos; contratos granulares pendentes | Parcial / não satisfaz gate |
| G03 | Dependências/competências | P0-DEP, P0-OWN, P0-CON, SEC/VER | 11 riscos de contrato e circularidade; faltam arestas tipadas e falha segura | Parcial / não satisfaz gate |
| G04 | Pendências e responsáveis | Esta matriz, INT-P0 e IN-168 (independente) | Papéis funcionais propostos; ainda não há responsáveis concretos, impactos fechados e decisões por item | Parcial / não satisfaz gate |
| G05 | Aceite documental final | Aceites parciais CT-02, CT-03/04, SEC/VER/ACC | Falta aceite expresso do módulo completo em versão final revisada | Pendente |
| G06 | Referência imutável final | Commits GitHub de trabalho | Não foi fixada versão/commit final aprovado como fechamento | Pendente |

**Resumo:** nenhum dos seis gates foi declarado cumprido integralmente por esta revisão. G01..04 possuem evidência parcial; G05/G06 permanecem pendentes.

## 3. Sequência de fechamento proposta para a próxima fase DOCUMENTAL

1. **Origem e escopo — P0-DOC-01/02 e P0-TR-01.** Recuperar deliberações integrais anteriores e associar cada regra/exceção/versão a TR estável, resolvendo divergências entre fonte e síntese sem editar a intenção aprovada.
2. **Competência e contratos — P0-OWN/CON/SEC.** Completar recurso/campo/operação, enum/versionamento, autorização real por perfil e alçada quando exigida, preservando módulos proprietários e serviços centrais.
3. **Dependências e compatibilidade — P0-DEP/VER/XREF.** Especificar arestas por fase e contratos de contingência, versionamento de instâncias e diferenciação de INs adjacentes sem duplicar motores.
4. **Homologação documental — P0-ACC.** Obter revisão/aceite dos roteiros HT aprovados como base, definindo responsáveis funcionais, evidências e ambientes futuros sem executar nada.
5. **Revisão final — G01..06/P0-GIT-01.** Somente quando requisitos e pendências estiverem resolvidos, identificar o commit final e solicitar aceite expresso. Todo o projeto CRM continua sem implementação até aprovação global de planejamento.

## 4. Pendências P1/P2 que continuam existindo

O fato de uma necessidade estar documentada não altera a prioridade original. INT-R02 considera em **P1** parâmetros de quota, concorrência, dimensionamento, monitoramento/recuperação, versionamento, retenção especializada e custos; em **P2** distribuição comercial avançada e documentação complementar. Elementos P1/P2 que bloqueiem segurança, contrato ou arquitetura para implementação deverão ser encaminhados à revisão competente; **não** reclassificar ou aprovar por inferência.

## 5. Investigações candidatas de escopo e requisito (03/11/2026)

- [P0-DOC-TR — 28 lacunas candidatas](P0-DOC-TR-deltas-candidatos-fonte-original.md): comparação com os arquivos de síntese INT-01..12, sem inventar 28 requisitos novos nem declarar 28 contradições.
- [P0-ORIGEM — texto de aceites preservados](P0-ORIGEM-aprovacoes-dialogo.md): a autenticidade literal de cada transcrição frente ao registro original ainda requer fonte primária verificável.
- **Bloqueios P0-DOC-01 / P0-TR-01:** não encerrados; a ação seguinte é recuperar e cotejar textos originais, registrar versões e corrigir somente diferenças comprovadas mediante decisão.

## 6. Aprofundamento focal INT-08/INT-09 — 03/11/2026

- [P0-FOCO-INT08-09](P0-FOCO-INT08-09-confronto-11TR-8lacunas.md) analisa **8 lacunas candidatas** distribuídas em 4 da INT-08 e 4 da INT-09 e preserva 5+6 TR originais sem alterar sua redação.
- **Evidência positiva:** cotejo com IN-118/122/123/124/148/149, sínteses de INT-08/09, CT-02/03/04 e planos HT. **Evidência ainda faltante:** propostas e aprovações integrais originais com referência verificável.
- **G01, G02 e P0-DOC-01/P0-TR-01 permanecem parciais/abertos**; nenhum gate final foi encerrado por essa análise.

## 7. Origem focal INT-08/09 — verificação de histórico e commits

- [P0-ORIGEM-INT08-09](P0-ORIGEM-INT08-09-localizadores-historicos.md) aponta referências contextuais de mensagens históricas e os commits de consolidação da INT-08/09, distinguindo fonte primária de síntese.
- **P0-DOC-01 e P0-TR-01 seguem abertos:** proposta e aceite integrais de INT-08 e INT-09 ainda não foram recuperados para comparação cláusula a cláusula. Localizadores não equivalem à origem literal.

## 8. Avanço de minutas CT-05..11 — 03/11/2026

- [P0-CON CT-05 a CT-11](P0-CON-CT05-CT11-contratos-integracoes-minuta.md) detalha sete famílias já previstas na governança, sem criar nova automação, motor ou infraestrutura. Inclui referências a OP24, TR, INV e HT e critérios de revisão contratual.
- **Evidência acrescentada em P0-CON-01:** representação conceitual dos objetos, marcos, falhas e reconciliação para CT-05..11. **Ainda falta:** schemas/estados definitivos, contratos por provedor/operação, alçadas, compatibilidade, parâmetros, evidências técnicas e aceite dos proprietários.
- **P0-DOC-01 e P0-TR-01 permanecem abertos** até cotejo integral das fontes originais. Nenhuma nova HT, programação, teste ou gate concluído.

## 9. Revisão cruzada de CT-01 a CT-11 — 03/11/2026

- [Decisão CT-05..11 como bases](P0-DEC-aprovacao-CT05-CT11-base.md): aceita a estrutura documental das sete famílias, **sem homologação técnica**.
- [P0-CON Revisão CT-01..11](P0-CON-revisao-cruzada-CT01-CT11.md): 11 contratos revisados, 105 associações propostas de 53 TR às CT, 13 riscos de fronteira documentais, incluindo leitura restritiva do vínculo CT-10/INT-08.
- **Bloqueio de P0-CON-01:** CT-01 precisa de especificação própria; para CT-02..11 faltam schemas, permissões, estados por operação, parâmetros, provedores e aceite/homologação final. Contratos **não** significam motores novos.
- Nenhum dos gates G01–G06 muda de estado por esta revisão; P0-DOC-01/P0-TR-01 permanecem pendentes de texto original.

## 10. CT-01 especializada — 03/11/2026

- [P0-CON CT-01 — minuta especializada](P0-CON-CT01-conectores-conexoes-capacidades-minuta.md): cobre catálogo, conexão, conta, conector homologado, capacidade, autorizações separadas e proteção de operações pendentes com **11 TR iniciais e 9 roteiros negativos propostos**.
- **P0-CON-01 evoluiu documentalmente**, mas CT-01 ainda aguarda aprovação como base, esquemas físicos, critérios de homologação por provedor, alçadas, parâmetros e rastreabilidade das fontes originais. **Nenhum gate G01–G06 concluído.**

## 11. CT-01 aceita e pacote P0-CON-01 organizado (03/11/2026)

- [P0-DEC CT-01](P0-DEC-aprovacao-CT01-base.md): agora todas as 11 famílias CT estão aceitas **como bases documentais**, sem homologação técnica e sem esquemas físicos aprovados.
- [P0-CON-MASTER](P0-CON-MASTER-matriz-CT01-CT11-criterios-fechamento.md): reúne responsáveis/efeitos de 11 CT, 53 TR iniciais / 105 vínculos documentais, nove evidências E-CON01..09 de fechamento e vinculação aos gates G01..G06.
- **P0-CON-01 segue ABERTO/PARCIAL**; o aceite da base não significa aprovação final de contratos por operação ou prontidão para programação. P0-DOC/TR, OWN/SEC, DEP/VER, ACC e G01..G06 permanecem com suas pendências.

## 12. Aceite CON-MASTER e 15 fichas operacionais (03/11/2026)

- [P0-DEC CON-MASTER](P0-DEC-aprovacao-CON-MASTER-base.md) registra aceite da matriz como **instrumento documental**, não como liberação de contratos executáveis.
- [P0-CON-OP15](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) detalha 15 operações de alto impacto como propostas, com riscos, fontes oficiais, pré-condições, falhas, reconciliação e decisões em aberto. **Nove OP24 ainda aguardam especialização**, sem prejuízo dos requisitos adicionais que surgirem do cotejo integral.
- Evidência em P0-CON-01: documentação funcional avançou; **P0-CON-01 e G01–G06 seguem sem fechamento**. Não programar, executar HT ou conceder permissões por esta etapa.

## 13. Desdobramento operacional completo da base OP24 (03/11/2026)

- [OP15](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) e [OP09](P0-CON-OP09-contratos-operacoes-complementares.md) **redigidas em proposta**, com [índice de 24 contratos por operação](P0-CON-OP24-indice-consolidado-contratos-por-operacao.md).
- **24/24 operações de referência cobertas** por detalhamento documental e CT-02 por operação, sem aprovar schemas físicos, perfis, alçadas, versões, parâmetros de provedor ou resultados de negócio; pode haver outros atos nas aprovações originais não recuperadas.
- **P0-CON-01 continua parcial/aberto**, assim como P0-DOC/TR, OWN/SEC/DEP/VER/XREF/ACC e G01–G06. **Nenhum teste HT realizado, nenhuma programação.**

## 14. Bases OP24 aceitas e detalhamento de segurança/estados (03/11/2026)

- [P0-DEC OP15/OP09](P0-DEC-aprovacao-OP15-OP09-bases.md): aceite das 24 fichas especializadas **como bases documentais**, sem parâmetros executáveis aprovados.
- [P0-OP24 SEC/STATE/PARAM](P0-OP24-SEC-STATE-PARAM-matriz-decisao.md): minuta com 24 controles por operação; permissões, alçadas, estados físicos, versionamento e parâmetros por provedor **pendentes de decisão**.
- **P0-CON/OWN/SEC/VER/DEP/DOC/TR/ACC continuam abertos**, G01–G06 ainda não encerrados; zero testes e nenhuma programação.

## 14. Matriz SEC/STATE/PARAM aceita e P0-OWN-SEC criada (03/11/2026)

- [P0-DEC — SEC/STATE/PARAM](P0-DEC-aprovacao-OP24-SEC-STATE-PARAM-instrumento.md) registra aprovação **como instrumento documental** de decisões ainda abertas.
- [P0-OWN-SEC](P0-OWN-SEC-matriz-recursos-campos-OP24.md) identifica 13 classes de recursos e direitos candidatos em 24 operações, com 12 perguntas OWN-Q01..12 e critérios de fechamento; **minuta não aprovada**, sem concessão de poderes.
- **P0-OWN-01/P0-SEC-01 seguem ABERTOS/PARCIAIS** até catálogo verdadeiro de perfil/recurso/campo, revisão pelos proprietários, alçadas e fonte literal. G01–G06 não foram encerrados.

## 15. Aprovação de P0-OWN-SEC como base e avaliação da prontidão global (03/11/2026)

- [P0-DEC — OWN-SEC como base documental](P0-DEC-aprovacao-OWN-SEC-base.md): 13 grupos de recurso/24 operações aceitos como **base de revisão**, sem concessão de direitos reais, perfis ou alçadas.
- [Diagnóstico de prontidão do CRM](../STATUS-PRONTIDAO-INICIO-DESENVOLVIMENTO-2026-11-03.md): o módulo INT tem documentação avançada, mas **P0-OWN-01, P0-SEC-01 e os demais P0/G01..G06 não foram encerrados**, e a especificação global do CRM permanece distribuída por branches e documentos de planejamento.
- A avaliação recomenda reconciliar o produto completo antes da implementação, **sem prometer data ou autorizar Codex**.

## 16. Controle de aprovação e execução

- **P0-SEC e P0-VER:** aprovados como **bases documentais**, com políticas específicas pendentes.
- **CT-03 e CT-04:** aprovados como **base documental**, sem esquema final de implementação.
- **CT-02, OP24 e planos HT-01..18:** aceitos como bases documentais de trabalho; testes **0 de 18**.
- **53 requisitos:** identificadores conciliados em duas matrizes, ainda sem comprovação de exaustividade ou cotejo integral das fontes.
- **11 riscos de dependência:** catalogados para resolução, **não** erros de código ou deadlocks comprovados.
- **Sem aprovação final:** G01–G06 não concluídos, sem alteração da proibição de programação.

**Conclusão:** revisão P0 de rastreabilidade e dependências realizada e registrada; fechamento final do módulo INT **segue bloqueado por pendências explícitas**.
