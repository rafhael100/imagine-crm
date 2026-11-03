# P0-CON-OP24 — Índice consolidado das 24 fichas operacionais de contrato

**ATUALIZAÇÃO 03/11/2026:** As 24 fichas especializadas foram aceitas **como bases documentais** em [P0-DEC](P0-DEC-aprovacao-OP15-OP09-bases.md); os estados históricos 'proposta' abaixo referem-se à elaboração inicial. A [matriz SEC/STATE/PARAM](P0-OP24-SEC-STATE-PARAM-matriz-decisao.md) é **nova minuta para revisão**, sem parâmetros, alçadas ou schemas aprovados.

**Data:** 2026-11-03. **Situação:** **ÍNDICE DOCUMENTAL PARA REVISÃO**, consolidando o [primeiro lote com 15 fichas](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) e o [segundo lote com nove fichas](P0-CON-OP09-contratos-operacoes-complementares.md).
**Autorização:** [CON-MASTER foi aceita como instrumento documental](P0-DEC-aprovacao-CON-MASTER-base.md). As 24 operações originais [OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) foram anteriormente aceitas **como base de referência**, mas o detalhamento adicional **ainda é proposta sem aceite específico**.
**Fase:** somente planejamento GitHub. Não programar, criar automação, executar testes, mexer em produção, migrar dados, publicar conector ou contratar serviços.

## 1. Resultado da consolidação

- **15/15 fichas do primeiro lote** de maior impacto sobre autorização, movimentação de dados, efeito externo, compensação, recuperação, conectores e exportação de auditoria: **redigidas em proposta**.
- **9/9 fichas do segundo lote** de conexão, validação e versão, aplicação/API, webhook de saída, sync, diagnóstico e investigação: **redigidas em proposta**.
- **24/24 IDs OP da base de referência** cobertos **uma vez cada** pelos dois documentos; **24 IDs na matriz CT-02** e 24 na OP24 original.
- Os identificadores TR explícitos e intervalos citados nas 24 fichas OP de referência foram confrontados **quanto à existência** no inventário inicial dos 53 TR. **Não** se certifica o conteúdo literal das decisões originais nem cobertura exaustiva das INTs.
- **Não são 24 operações efetivamente homologadas, 24 novas funcionalidades ou o catálogo completo do CRM.** Nenhuma HT executada e nenhuma alçada concedida.

## 2. Matriz de 24 contratos propostos

| ID OP | Natureza | Famílias CT de referência | TR inicialmente associada | HT futuro inicialmente relacionado | Documento especializado | Estado |
|---|---|---|---|---|---|---|
| OP-01-CAD | Cadastrar ou atualizar conexão | CT-01/02/03/04/11 | TR-INT01-001..004 | HT-01/13 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-01-CIC | Suspender/desativar conexão | CT-01/02/04/09/11 | TR-INT01-002..004 | HT-18/08 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-02-AUT | Vincular ou renovar autorização externa | CT-01/02/04/11 | TR-INT02-001..004 | HT-01/02 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-02-REV | Revogar autorização ou credencial | CT-02/04/09/11 | TR-INT02-002..004 | HT-02/13 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-03-VAL | Validar esquema ou prévia de transformação | CT-02/03/04/07 | TR-INT03-001..003 | HT-03/07 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-03-VER | Publicar nova versão de mapeamento | CT-02/03/04/06/07 | TR-INT03-001/004 | HT-03/12 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-04-ENV | Executar comando externo tipado | CT-01/02/03/04/09/11 | TR-INT04-001..004 | HT-08/12 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-04-RSL | Registrar resultado técnico e encaminhar confirmação | CT-03/04/09/11 | TR-INT04-002/003 | HT-08/14 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-05-APP | Registrar ou alterar aplicação integrada | CT-01/02/04/11 | TR-INT05-001..003 | HT-04/13 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-05-API | Invocar operação pela API pública | CT-02/03/04/11 | TR-INT05-001..004 | HT-04/13 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-06-IN | Receber e validar webhook externo | CT-02/04/05/09/11 | TR-INT06-001..004 | HT-05/16 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-06-OUT | Entregar webhook externo autorizado | CT-02/04/05/09 | TR-INT06-002..004 | HT-05/16 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-07-SYNC | Sincronizar alterações incrementais | CT-02/03/04/06/09 | TR-INT07-001..004 | HT-06/08 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-07-RET | Retomar sincronização interrompida | CT-02/04/06/09 | TR-INT07-002..004 | HT-06/16 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-08-IMP | Importar ou migrar conjunto delimitado | CT-02/03/04/07/09/11 | TR-INT08-001..005 | HT-07/13 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-08-EXP | Exportar dados autorizados ou produzir pacote delimitado | CT-02/04/07/11 | TR-INT08-001/004/005 | HT-07/17 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-09-INI | Instanciar e executar processo multissistema | CT-02/03/04/08/09/11 | TR-INT09-001..004 | HT-08/09 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-09-COM | Solicitar e acompanhar compensação tipada | CT-02/03/04/08/09/10/11 | TR-INT09-004..006 | HT-10/15 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-10-DIA | Detectar e diagnosticar incidente de integração | CT-02/04/09/11 | TR-INT10-001/004/005 | HT-11/16 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-10-REC | Aplicar recuperação técnica homologada | CT-02/03/04/09/11 | TR-INT10-002..005 | HT-08/11 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-11-HOM | Homologar e publicar versão de conector | CT-01/02/04/11 | TR-INT11-001..003 | HT-01/12 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-11-SUS | Suspender/revogar/descontinuar versão homologada | CT-01/02/04/09/11 | TR-INT11-002/004 | HT-12/18 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |
| OP-12-INV | Investigar operação com correlação de evidências | CT-02/04/09/11 | TR-INT12-001/002/005 | HT-14 | [Lote 2](P0-CON-OP09-contratos-operacoes-complementares.md) | Proposta de revisão |
| OP-12-AUD | Exportar evidência de auditoria sob controle | CT-02/04/11 | TR-INT12-003..005 | HT-14/17 | [Lote 1](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) | Proposta de revisão |

**Observação de rastreabilidade:** CT/TR/HT são os vínculos **iniciais** declarados em OP24, sem revisão literal integral. Se cláusula original exigir outro cenário ou contrato, registrar diferença e decisão, não sobrescrever silenciosamente esses vínculos.

## 3. Mapa de autoridade CT-02 por operação (responsabilidades candidatas)

| OP | Solicitante candidato | Fonte de autoridade/efeito prevista na CT-02 | Lacuna de decisão |
|---|---|---|---|
| OP-01-CAD | Gestor autorizado da conexão no tenant; serviço interno escopado | INT-01 / Identidade e Segurança; conector INT-11, credenciais INT-02 | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-01-CIC | Operador autorizado à gestão de conexão; intervenção técnica elegível | INT-01; efeitos pendentes pertencem aos módulos de negócio | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-02-AUT | Titular/delegado ou serviço habilitado a conduzir autorização | INT-02 e Identidade e Segurança; cofre central IN-151 | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-02-REV | Titular/operador de segurança autorizado; contenção técnica homologada | INT-02/Identidade e Segurança; provedor confirma efeito remoto | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-03-VAL | Usuário com permissão para visualizar campos de origem/destino | INT-03; proprietário controla dados | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-03-VER | Responsável autorizado à gestão de esquemas | INT-03 e módulos consumidores; políticas de homologação | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-04-ENV | Identidade humana/técnica com delegação efetiva | INT-04 transmite; proprietário do efeito autoriza/atesta | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-04-RSL | Principal de serviço confiável e módulo proprietário | INT-04 para transporte; módulo proprietário para negócio | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-05-APP | Administrador/delegado autorizado do tenant e serviço técnico escopado | INT-05, INT-02 e Identidade e Segurança | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-05-API | Aplicação/usuário autenticado com delegação válida | INT-05 fronteira; proprietário confirma efeito | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-06-IN | Provedor/assinatura identificados e validados no contexto de conexão | INT-06; motor de Eventos interno, proprietário de negócio | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-06-OUT | Serviço autorizado a notificar assinante habilitado | INT-06 entrega; COM para comunicações ao cliente | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-07-SYNC | Serviço habilitado sob delegação de fonte/destino; operador autorizado | INT-07 coordena; proprietários gravam/confirmam | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-07-RET | Serviço homologado com revalidação; operador de recuperação habilitado | INT-07; INT-10 para incidente; proprietários para dados | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-08-IMP | Usuário autorizado à portabilidade e gestores dos dados envolvidos | INT-08 com Central IN-118; módulos proprietários | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-08-EXP | Usuário/serviço autorizado a ler/exportar escopo do tenant | INT-08 e IN-118; proprietários dos dados; Arquivos central | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-09-INI | Operador/serviço autorizado pelo modelo/instância e pelo tenant | INT-09 coordena; cada dono decide efeito; motores centrais | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-09-COM | Responsável autorizado a solicitar reversão; executor competente | INT-09 coordena; FIN/CON/AGD/outro proprietário decidem e confirmam | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-10-DIA | Identidade técnica/funcionário com acesso de diagnóstico autorizado | INT-10; Observabilidade IN-159/160; Central de Incidentes | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-10-REC | Agente/operador escopado com playbook homologado | INT-10; proprietário dos efeitos; Identidade e Segurança | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-11-HOM | Revisor e publicador autorizados; parceiro apenas submete quando permitido | INT-11; Segurança e Central de Aprovações | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-11-SUS | Operador de catálogo/segurança com autoridade específica | INT-11 catálogo; INT-01 conexões; INT-02 escopos | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-12-INV | Funcionário autorizado a investigar escopo definido | INT-12 visão; Auditoria Geral fonte oficial | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |
| OP-12-AUD | Responsável com permissão específica de exportação | Auditoria Geral; INT-12 interface de consulta/exportação | Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes |

**Uso desta tabela:** os termos “solicitante candidato” e “fonte de autoridade” vêm da matriz CT-02 **como base documental**; não criam perfis, permissões, cargos, superadministradores, aprovação N3, meios de autenticação ou IAM novo. Cada competência de negócio permanece no seu módulo proprietário.

## 4. Critérios para futura revisão/aceite das fichas

| Critério | Evidência documental a confirmar | Situação |
|---|---|---|
| OP24-R01 — Origem | Texto original INT/IN, cláusula, decisão e versões aplicáveis, com diferença de síntese explicitada | **P0-DOC/TR pendentes** |
| OP24-R02 — Contratos | CT aplicáveis por ato, tipos, campos, precondições, erros, estados, resultado técnico versus oficial | **P0-CON parcial** |
| OP24-R03 — Autorizações | Tenant, solicitante, escopo por recurso/campo, alçada/validade e revogação | **P0-SEC/OWN pendentes** |
| OP24-R04 — Concorrência | Idempotência por efeito, retry, reconciliação e tratamento do resultado inconclusivo | **P0-CON/DEP pendentes** |
| OP24-R05 — Versionamento | Conector, schema, credencial, instância, suspensão, convivência/migração sem mudança silenciosa | **P0-VER pendente** |
| OP24-R06 — Serviços centrais | Sem duplicar Filas, Eventos, Tarefas, Aprovações, Observabilidade, Arquivos, Portabilidade, Auditoria Geral | **P0-XREF/DEP pendentes** |
| OP24-R07 — Homologação documental | Revisor competente e HT específico, entradas negativas, evidências, critérios de aceite documental | **P0-ACC parcial; 0 testes executados** |
| OP24-R08 — Aceite global | Referência versionada no GitHub, aprovação expressa de fichas especializadas e decisões ainda abertas | **P0-GIT e G01–G06 pendentes** |

## 5. Próxima consolidação sem programação

1. Revisar as 24 fichas com proprietários/Segurança e verificar parâmetros e decisões não fechadas, **sem exigir aprovação duplicada da mesma decisão**.
2. Recuperar o conteúdo integral das propostas e aprovações INT-01..12 e R01/R02 para aferir se o catálogo tem operações adicionais e quais regras faltam.
3. Obter aceite das fichas especializadas **como bases documentais**, se o responsável concordar, mantendo os detalhes ainda não aprovados. A aceitação futura de bases não resolve os itens OP24-R01..08.
4. Continuar a documentação até P0-CON-01/G01–G06 atenderem a suas evidências e aceite final. **Não liberar implementação antes da conclusão de todo o planejamento.**

**Status:** 24/24 fichas especializadas **propostas e versionadas**, 11/11 CT aceitas como bases documentais, 0/18 HT executados, P0-CON-01 e G01–G06 ainda abertos.
