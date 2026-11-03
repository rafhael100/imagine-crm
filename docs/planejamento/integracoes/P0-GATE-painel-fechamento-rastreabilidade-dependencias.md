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

## 7. Controle de aprovação e execução

- **P0-SEC e P0-VER:** aprovados como **bases documentais**, com políticas específicas pendentes.
- **CT-03 e CT-04:** aprovados como **base documental**, sem esquema final de implementação.
- **CT-02, OP24 e planos HT-01..18:** aceitos como bases documentais de trabalho; testes **0 de 18**.
- **53 requisitos:** identificadores conciliados em duas matrizes, ainda sem comprovação de exaustividade ou cotejo integral das fontes.
- **11 riscos de dependência:** catalogados para resolução, **não** erros de código ou deadlocks comprovados.
- **Sem aprovação final:** G01–G06 não concluídos, sem alteração da proibição de programação.

**Conclusão:** revisão P0 de rastreabilidade e dependências realizada e registrada; fechamento final do módulo INT **segue bloqueado por pendências explícitas**.
