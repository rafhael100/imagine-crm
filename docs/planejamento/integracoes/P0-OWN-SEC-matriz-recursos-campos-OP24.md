# P0-OWN-SEC — Propriedade de recursos, classes de campos e direitos por operação

**Data:** 2026-11-03.
**Estado atualizado:** **APROVADA COMO BASE DOCUMENTAL DE REVISÃO**, conforme o [aceite contextual P0-DEC](P0-DEC-aprovacao-OWN-SEC-base.md) de 2026-11-03. A aprovação **não concede** direitos reais por perfil/recurso/campo, alçadas, MFA, schemas nem execução. O detalhamento de parâmetros e decisões concretas permanece pendente.
**Âmbito:** [24 operações de referência](P0-CON-OP24-indice-consolidado-contratos-por-operacao.md) do bloco INT-01..12, com [CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md), [P0-SEC](P0-SEC-politicas-autorizacao-CT02.md), [CON-MASTER](P0-CON-MASTER-matriz-CT01-CT11-criterios-fechamento.md), [INT-R01](INT-R01-governanca-contratos-homologacao.md) e [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md) como fundamentos.
**Limite:** o material classifica **recursos e campos conceituais**; não institui novas tabelas, schemas, papéis IAM, permissões executáveis, direitos de colaboradores, alçadas, MFA universal, serviços ou autoridades. Propostas originais integrais INT-01..12 ainda precisam de cotejo literal em P0-DOC-01/P0-TR-01.
**Fase:** planejamento/versionamento apenas; não programar, executar teste, automatizar de novo, criar contas/credenciais reais, migrar dados, publicar conector, alterar produção ou contratar serviços.

## 1. Como interpretar “propriedade” e “direito”

Cada ação protegida deverá distinguir **quatro competências**:
1. **Fonte de identidade/autorização:** Identidade e Segurança verifica tenant, principal, escopo, papel efetivo, finalidade e direitos reais; INT-02 refere-se à autorização **externa**; IN-151 guarda segredos. Não criar motor IAM paralelo.
2. **Proprietário do objeto:** módulo detentor de conexão, conector, esquema, aplicação, sync, lote, instância, incidente, evidência ou recurso de negócio. A autorização do tenant não muda o dono do dado.
3. **Executor técnico:** INT-04, INT-06, INT-07, INT-08, INT-09, INT-10, serviços centrais ou provedor, **somente** dentro do contrato/autorização apropriado.
4. **Confirmação oficial:** módulo de negócio atesta efeito FIN/CON/AGD/VEN/ATD/CAT etc.; Auditoria Geral é fonte da trilha; INT-12 consulta, não retifica o fato.

**Uma operação pode exigir direitos diferentes para ler, alterar, suspender, exportar, aprovar e executar.** O nome “gestor”, “administrador” ou “serviço interno” nas fichas de referência é uma categoria **candidata de solicitante**, não privilégio global concedido. Atos da equipe Imagine em nome do cliente exigem competência específica e histórico/consentimento quando pertinente.

## 2. Classes de campos para futura política (rótulos propostos)

| Classe documental | Exemplos de campos conceituais | Condição de acesso a definir | Limite a preservar |
|---|---|---|---|
| CF-REF — identidade/referência | IDs de tenant, conexão, comando, etapa, versão, entidade e origem | Consulta segundo vínculo/necessidade; não expor referência que permita acesso a outro tenant | ID não é credencial nem autorização |
| CF-CFG — configuração | destino, mapeamento, esquema, assinatura, ambiente, política de conexão | Distinção entre leitura, mudança e publicação | Alteração de destino não redireciona operações antigas silenciosamente |
| CF-BIZ — conteúdo de negócio | leads, contatos, oportunidades, vendas, contratos, pagamentos e agendamentos | Proprietário define direitos por entidade/campo/ação | INT não confirma negócio nem opera fora da competência |
| CF-PERS — dados pessoais/classificados | contato pessoal, atributos confidenciais ou sensíveis conforme finalidade e legislação aplicável | Minimização, máscara, exportação e finalidade; proteger em logs e IA | Direito de leitura não implica exportação ou treino da IA |
| CF-SEC — segurança/segredos | token, chave, refresh token, segredo, referências seguras, escopos e vigência | **Segredo em claro não deve circular no contrato**, cofre IN-151 e acesso escopado | Não vazar em exportação, log, webhooks, frontend ou prompts |
| CF-STATE — estado/versão | status técnico/negócio separados, cursor, versão de conector, instância, fila e aprovação | Mudança somente pela fonte competente, com revalidação | Status técnico ≠ fato de negócio |
| CF-EVID — evidência/auditoria | trilha de acesso, resultado oficial, incidente, classe de retenção, prova de integridade | Consulta/extração por caso, escopo, categoria e necessidade | Auditoria Geral oficial; sem retenção universal |
| CF-ART — arquivos/artefatos | anexo, manifesto, pacote exportado, lote, evidência derivada | Upload/importação/exportação/download com direitos distintos | Limite de novos anexos não bloqueia leitura/exportação existente |

Estas classes são **rótulos para revisão**, não novos tipos físicos ou tabela de permissões implementada.

## 3. Matriz de titularidade de recursos

| Recurso | Fonte competente | Classes de campos/escopo | Ações que exigem decisão separada | Fronteira contra autorização implícita | OP de referência |
|---|---|---|---|---|---|
| RES-CON | Conexão, conta e vínculo de serviço externo | INT-01; INT-11 governa conector/versão | tenant, empresa/unidade/marca, conexão, conta externa, finalidade, ambiente, destino e versão; atributos de impacto | Ler configuração; criar/alterar vínculo; restringir/suspender; consultar impacto | Credenciais externas pertencem à INT-02/cofre; mudança de vínculo não migra dados ou comandos anteriores | OP-01-CAD, OP-01-CIC, OP-11-SUS |
| RES-AUT | Autorização externa, escopos e referências de credenciais | INT-02 e Identidade e Segurança; IN-151 guarda segredos | proprietário/delegado, escopos, status, vigência, provedor, conta, referência opaca a segredo; **nunca segredo em claro** | Iniciar vínculo; consultar metadados minimizados; renovar/revogar dentro de competência | Token não concede direitos internos; nunca retornar segredo via frontend, logs, exportação ou IA | OP-02-AUT, OP-02-REV |
| RES-MAP | Esquema, campos e transformação versionada | INT-03; módulos proprietários detêm dados mapeados | entidade, campo, classificação, tipo, regra de transformação, versão e compatibilidade, amostra mascarada | Ler campos autorizados; validar/pré-visualizar; propor/publicar versão homologada | A leitura em preview não concede escrita no dado de origem/destino | OP-03-VAL, OP-03-VER |
| RES-CMD | Comando de efeito e resultado | INT-04 transmite; módulo de negócio detém ato/resultado oficial | recurso/ação/campo alvo, command_ref, attempt_ref, versão, precondição, referência a prova e incerteza | Pedir ação tipada; registrar resposta técnica; consultar reconciliação | Sem efeito de negócio com base apenas em resposta HTTP, n8n ou webhook | OP-04-ENV, OP-04-RSL |
| RES-APP | Aplicação integrada e uso de API pública | INT-05; Identidade e Segurança rege acesso; proprietário rege dados | tenant, app/client principal, escopo, finalidade, plano/trial, recurso/campo pretendido, versão | Gerenciar app; consultar escopos; invocar endpoint autorizado por ato | Trial não libera API pública; app cadastrada não lê/escreve todos os dados | OP-05-APP, OP-05-API |
| RES-EVT | Webhook, assinatura e evento externo | INT-06; Motor Central de Eventos é dono do evento interno; COM comunica | evento ID/versão, tenant, conexão, assinatura, origem/destino, classe de payload, tentativa e correlação | Receber/verificar; configurar assinatura autorizada; entregar/reconciliar tecnicamente | Origem assinada não é autorização de negócio; destino deve ser permitido e escopado | OP-06-IN, OP-06-OUT |
| RES-SYNC | Configuração de sincronização e checkpoint | INT-07; INT-03 por mapa; proprietário por registros/campos | fonte, destino, direção, entidade/campo, mapeamento/versão, cursor/checkpoint e mudança lógica | Executar/pausar/retomar sync autorizada; consultar progresso; reconciliar conflitos | Não avançar checkpoint por ack; não deduzir exclusão por ausência de campo | OP-07-SYNC, OP-07-RET |
| RES-DATA | Registro de negócio, lead, contato e atributos estruturados | Módulo proprietário (ATD/VEN/CAT/FIN/CON/AGD etc.) | identidade do registro, tenant, campos pessoais/sensíveis conforme domínio, fonte, consentimentos e histórico | Ler/exportar/importar/escrever apenas conforme direito por entidade/campo e finalidade | INT-03/07/08 são processadores, não titulares da confirmação ou regras de mesclagem | OP-07-SYNC, OP-08-IMP, OP-08-EXP |
| RES-PORT | Arquivo, manifesto, lote e pacote de portabilidade | IN-118 é Central de Backup/Portabilidade; Arquivos guarda; INT-08 opera lote | manifesto, entidade/campos, origem/destino, arquivo, classificação, lote/registro, resultado, retenção temporária | Prévia sem efeito; importação/migração autorizada; exportação e download controlados | Franquia de anexos a 100% só restringe novos uploads de anexos, não exportações de dados existentes | OP-08-IMP, OP-08-EXP |
| RES-FLOW | Modelo, instância, etapa e compensação | INT-09 coordena; proprietários de FIN/CON/AGD e demais efeitos decidem atos | tenant, modelo/versão, instância, etapa, dependências, command_ref, efeito anterior, reversibilidade, estado | Iniciar etapa/instância; consultar execução; solicitar compensação a proprietário autorizado | Orquestrador não atesta pagamento nem executa rollback universal; cancelamento técnico não estorna | OP-09-INI, OP-09-COM |
| RES-INC | Incidente, sinais e playbook de recuperação | Observabilidade/Incidentes IN-159/160 são centrais; INT-10 especializa diagnóstico | ambiente/tenant, incidente, sinal/frescor, alvo, playbook/versão, principal executor, evidência técnica | Ler sinais de escopo; diagnosticar; executar playbook técnico elegível | Diagnóstico não concede recuperação; agente IA não se autoconcede privilégio ou ação N3 | OP-10-DIA, OP-10-REC |
| RES-CONN | Catálogo e ciclo de vida do conector homologado | INT-11; INT-01 por conexões existentes; INT-02 por credenciais | parceiro/provedor, artefato/versão, procedência, capacidades, homologação, tenant impactado, dependências | Homologar/publicar versão; suspender/revogar dentro da competência | Sem execução de código livre, autopublicação por IA ou migração automática de instância ativa | OP-11-HOM, OP-11-SUS |
| RES-AUD | Evidência oficial, investigação e exportação de auditoria | Auditoria Geral fonte oficial; INT-12 consulta/correlaciona; IN-162 só para logs técnicos | tenants/caso, sujeito, ação, comando, evento, campos mascarados, classificação, proveniência, retenção | Consultar por finalidade/caso; investigar; exportar evidência quando direito distinto existir | Leitura não concede exportação, auditoria da auditoria obrigatória onde aplicável, sem retenção universal | OP-12-INV, OP-12-AUD |

## 4. Matriz das 24 operações — direitos CANDIDATOS por recurso e campo

| OP | Recurso(s) | Campos/escopo a proteger | Direito/ação a confirmar (NÃO concedido) | Proprietário/autoridade a validar | Controle negativo na CT-02 | Perfil/alçada efetivos |
|---|---|---|---|---|---|---|
| OP-01-CAD | RES-CON | Conexão/conta/destino/versão | consultar configuração; cadastrar/alterar vínculo | INT-01 | Revisar tenant, destino, conector e vínculo antes de confirmar; não alterar destino de operações passadas | Não definida |
| OP-01-CIC | RES-CON | Conexão/estado/versão/impacto em filas | consultar impacto; restringir/suspender/desativar | INT-01 | Bloquear novas ações, preservar as já transmitidas, não produzir cancelamentos de negócio | Não definida |
| OP-02-AUT | RES-AUT | Escopos, validade e referência opaca à credencial | iniciar/renovar autorização; consultar metadados minimizados | INT-02 + Segurança | Não permitir credenciais em browser/IA/log; revalidar origem e delegação | Não definida |
| OP-02-REV | RES-AUT | Credencial/escopo exatos, bloqueio local versus remoto | restringir localmente; solicitar revogação do provedor | INT-02 + Segurança | Bloqueio local imediato de novos usos quando aplicável; sucesso remoto deve ser comprovado | Não definida |
| OP-03-VAL | RES-MAP, RES-DATA | Esquema, campo, classe de dado, amostra minimizada | consultar campos autorizados; pré-visualizar sem escrever | INT-03 + proprietário do dado | Máscara/minimização; nenhum ajuste de dado oficial por validação | Não definida |
| OP-03-VER | RES-MAP | Versão, compatibilidade, entidade/campo e consumidores | propor/publicar mapeamento conforme competência | INT-03 + consumidores proprietários | Proibir atualização silenciosa de instâncias e mapeamentos ativos; compatibilidade documentada | Não definida |
| OP-04-ENV | RES-CMD, RES-DATA | Comando/recurso/campo, escopo da ação e tentativa técnica | solicitar/encaminhar ação tipada; não editar estado oficial diretamente | INT-04 e dono do efeito | Revalidar no despacho e na escrita; resultado inconclusivo bloqueia retry arriscado | Não definida |
| OP-04-RSL | RES-CMD, RES-AUD | Resultado técnico, origem, prova/estado oficial distinto | registrar/transmitir evidência técnica; proprietário confirma negócio | INT-04 + proprietário | Resultado HTTP não modifica status de negócio; evento contraditório é investigado | Não definida |
| OP-05-APP | RES-APP, RES-AUT | App/tenant/plano/escopos e credencial referenciada | cadastrar/limitar app e escopos conforme plano | INT-05 + Segurança | Trial não libera API pública; não conceder acesso implícito a registros | Não definida |
| OP-05-API | RES-APP, RES-DATA, RES-CMD | Escopo por operação/recurso/campo e elegibilidade do plano | invocar ação tipada e ler somente campos autorizados | INT-05 fronteira; proprietário o efeito | Bloquear no trial, preservar integrações nativas permitidas; checar recurso individual | Não definida |
| OP-06-IN | RES-EVT, RES-CMD | Evento externo, assinatura/versão/payload/correlação | receber/verificar e encaminhar ao Motor Central de Eventos | INT-06 + Eventos | Sem segredo no payload/log; não assumir que webhook externo já é evento interno confirmado | Não definida |
| OP-06-OUT | RES-EVT | Assinante/destino cadastrado, finalidade e campos permitidos | enviar apenas notificação autorizada; gerenciar reentrega técnica | INT-06 | Impedir envio cross-tenant e vazamento de dados; HTTP de destino é aceite técnico | Não definida |
| OP-07-SYNC | RES-SYNC, RES-DATA | Fonte/destino/campo/direção/cursor e mudança lógica | ler alterações permitidas; gravar somente campos/direção autorizados | INT-07 + proprietário do dado | Não usar ausência como exclusão automática; checkpoint depois de confirmação apropriada | Não definida |
| OP-07-RET | RES-SYNC, RES-INC | Checkpoint/versão/credencial e efeitos incertos | retomar após conferir autorização e histórico de confirmação | INT-07/10 + proprietário do dado | Se estado incerto, reconciliar antes de avançar cursor ou repetir escrita | Não definida |
| OP-08-IMP | RES-PORT, RES-DATA | Arquivo/lote/registros/campos/destino e prévia | validar/preview; importar/migrar por escopo aprovado | INT-08 + IN-118 + proprietário | Não restaurar segredos de outro tenant; não mesclar/destruir automaticamente registros | Não definida |
| OP-08-EXP | RES-PORT, RES-DATA | Conjunto de campos, classificação, manifesto e link seguro | exportar dados autorizados; consulta e download controlados | IN-118/INT-08 + proprietário | Franquia cheia de anexos não bloqueia exportação dos dados existentes; auditoria da extração | Não definida |
| OP-09-INI | RES-FLOW, RES-CMD | Modelo/etapa/instância, dependências e recurso de cada efeito | iniciar/continuar processo dentro de modelo elegível | INT-09 + proprietários | Revalidar etapa assíncrona; não dispensar dependência por sucesso independente | Não definida |
| OP-09-COM | RES-FLOW, RES-CMD | Efeito original, reversibilidade, domínio, alçada e resultado próprio | solicitar compensação; executar apenas via proprietário competente | Proprietário FIN/CON/AGD/etc. + INT-09 coordena | Sem estorno/cancelamento automático por IA; compensação parcial continua explícita | Não definida |
| OP-10-DIA | RES-INC, RES-AUD | Telemetria/tenant/ambiente/hipótese/tempo e categoria sensível | consultar sinais de escopo; correlacionar/diagnosticar | Observabilidade/Incidentes; INT-10 | Não misturar operação interna Imagine e dados de tenants; telemetria ausente=desconhecida | Não definida |
| OP-10-REC | RES-INC, RES-CMD | Playbook/versão, ambiente/tenant e ação efetiva | executar ação técnica tipada homologada dentro da identidade | INT-10 + Segurança e dono do efeito | IA não concede privilégios; resultado técnico não confirma operação de negócio | Não definida |
| OP-11-HOM | RES-CONN | Versão/artefato/proveniência/capacidade e escopo de publicação | revisar/homologar/publicar versão via competência | INT-11 + Segurança/Aprovações | Não executar código livre de terceiros nem distribuir antes de homologação | Não definida |
| OP-11-SUS | RES-CONN, RES-CON | Conector/versão/abrangência/instâncias ativas | restringir versão; analisar conexões impactadas | INT-11 + INT-01/02 | Não migrar conexões ativas silenciosamente; notificar pelos serviços centrais competentes | Não definida |
| OP-12-INV | RES-AUD | Caso/tenant/identidade/classificação e trilhas | consultar evidência dentro de finalidade e campo permitido | Auditoria Geral; INT-12 consulta | Consultas também auditáveis, hipóteses separadas de fatos, sem manipular evidência | Não definida |
| OP-12-AUD | RES-AUD | Registro/caso/campo/manifesto e artefato de exportação | exportar evidência autorizada, com minimização e proteção | Auditoria Geral; INT-12 interface | Minimização e marca de proveniência; registrar a própria exportação; sem retenção ilimitada | Não definida |

## 5. Revisão individual e negativas por operação

### OP-01-CAD — Configurar conexão

**Classes de recurso e campo:** RES-CON — Conexão/conta/destino/versão. **Ações candidatas:** consultar configuração; cadastrar/alterar vínculo.

**Fonte de autoridade:** INT-01; **solicitante contextual da CT-02:** Gestor autorizado da conexão no tenant; serviço interno escopado. **Critério proposto de acesso:** Permissão de administrar conexão do próprio tenant; conexão/conta/versão corretas.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Cadastro/alteração pode exigir revisão de mudança crítica; não afeta operações anteriores Requer alçada de alteração conforme risco; não pressupor aprovação adicional universal

**Negativa obrigatória na revisão documental:** cross-tenant, versão incompatível, conta externa errada. **Salvaguarda preexistente de referência:** Revisar tenant, destino, conector e vínculo antes de confirmar; não alterar destino de operações passadas.

**Evidência e teste futuro:** HT-01,HT-13; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-01-CIC — Suspender conexão

**Classes de recurso e campo:** RES-CON — Conexão/estado/versão/impacto em filas. **Ações candidatas:** consultar impacto; restringir/suspender/desativar.

**Fonte de autoridade:** INT-01; **solicitante contextual da CT-02:** Operador autorizado à gestão de conexão; intervenção técnica elegível. **Critério proposto de acesso:** Poder explícito de suspender/desativar a conexão e avaliar impacto de instâncias.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Alçada para suspensão de grande impacto ou emergência a definir por política existente Aprovação conforme criticidade/efeitos; contenção segura segue política existente

**Negativa obrigatória na revisão documental:** conexão suspensa com operação já enviada, afetar conta alheia. **Salvaguarda preexistente de referência:** Bloquear novas ações, preservar as já transmitidas, não produzir cancelamentos de negócio.

**Evidência e teste futuro:** HT-18,HT-08; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-02-AUT — Vincular/renovar autorização externa

**Classes de recurso e campo:** RES-AUT — Escopos, validade e referência opaca à credencial. **Ações candidatas:** iniciar/renovar autorização; consultar metadados minimizados.

**Fonte de autoridade:** INT-02 + Segurança; **solicitante contextual da CT-02:** Titular/delegado ou serviço habilitado a conduzir autorização. **Critério proposto de acesso:** Escopo mínimo do provedor, finalidade e validade, com base legal/consentimento quando necessário.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Consenso/consentimento externo conforme necessidade real; autorização de uso por ato permanece separada Aprovações/consentimentos exigidos conforme tipo, sem inventar consentimento universal

**Negativa obrigatória na revisão documental:** token com escopo mais amplo, segredo exposto, principal sem delegação. **Salvaguarda preexistente de referência:** Não permitir credenciais em browser/IA/log; revalidar origem e delegação.

**Evidência e teste futuro:** HT-01,HT-02; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-02-REV — Revogar autorização externa

**Classes de recurso e campo:** RES-AUT — Credencial/escopo exatos, bloqueio local versus remoto. **Ações candidatas:** restringir localmente; solicitar revogação do provedor.

**Fonte de autoridade:** INT-02 + Segurança; **solicitante contextual da CT-02:** Titular/operador de segurança autorizado; contenção técnica homologada. **Critério proposto de acesso:** Permissão de revogação sobre a credencial e conta certas; escopo e impacto.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Revogação disruptiva/emergencial precisa de competência específica; não presumir confirmação remota Revogação potencialmente disruptiva/critica exige alçada aplicável; contenção previamente autorizada pode ter tratamento próprio

**Negativa obrigatória na revisão documental:** revogação incerta, filas pendentes, credencial vinculada a outro serviço. **Salvaguarda preexistente de referência:** Bloqueio local imediato de novos usos quando aplicável; sucesso remoto deve ser comprovado.

**Evidência e teste futuro:** HT-02,HT-13; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-03-VAL — Validar mapeamento/prévia

**Classes de recurso e campo:** RES-MAP, RES-DATA — Esquema, campo, classe de dado, amostra minimizada. **Ações candidatas:** consultar campos autorizados; pré-visualizar sem escrever.

**Fonte de autoridade:** INT-03 + proprietário do dado; **solicitante contextual da CT-02:** Usuário com permissão para visualizar campos de origem/destino. **Critério proposto de acesso:** Leitura dos campos/classificações e ambiente autorizados; prévia não concede escrita.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Permissão de leitura não se converte em direito de alterar campo; mascaramento conforme classificação Sem aprovação nova quando somente leitura autorizada; dados sensíveis podem exigir controle adicional

**Negativa obrigatória na revisão documental:** prévia exibindo dado sensível/tenant alheio, transformação executa escrita. **Salvaguarda preexistente de referência:** Máscara/minimização; nenhum ajuste de dado oficial por validação.

**Evidência e teste futuro:** HT-03,HT-07; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-03-VER — Publicar versão de mapeamento

**Classes de recurso e campo:** RES-MAP — Versão, compatibilidade, entidade/campo e consumidores. **Ações candidatas:** propor/publicar mapeamento conforme competência.

**Fonte de autoridade:** INT-03 + consumidores proprietários; **solicitante contextual da CT-02:** Responsável autorizado à gestão de esquemas. **Critério proposto de acesso:** Direito de publicar versão, ambiente e conjunto de entidades permitidos.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Publicação incompatível exige rito de homologação/aceite competente quando aplicável Revisão/aprovação quando versão incompatível ou impacto significativo, conforme governança

**Negativa obrigatória na revisão documental:** instância ativa muda versão silenciosamente. **Salvaguarda preexistente de referência:** Proibir atualização silenciosa de instâncias e mapeamentos ativos; compatibilidade documentada.

**Evidência e teste futuro:** HT-12,HT-03; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-04-ENV — Enviar comando tipado

**Classes de recurso e campo:** RES-CMD, RES-DATA — Comando/recurso/campo, escopo da ação e tentativa técnica. **Ações candidatas:** solicitar/encaminhar ação tipada; não editar estado oficial diretamente.

**Fonte de autoridade:** INT-04 e dono do efeito; **solicitante contextual da CT-02:** Identidade humana/técnica com delegação efetiva. **Critério proposto de acesso:** Operação, entidade, recurso/campo e conexão autorizados; CT-03 válido.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Alçada por ato (FIN/CON/AGD/etc.) sem autorização universal do transportador Aprovação do ato de negócio na alçada competente quando exigida

**Negativa obrigatória na revisão documental:** comando para recurso não autorizado, retry de escrita inconclusiva. **Salvaguarda preexistente de referência:** Revalidar no despacho e na escrita; resultado inconclusivo bloqueia retry arriscado.

**Evidência e teste futuro:** HT-08,HT-13; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-04-RSL — Registrar/encaminhar resultado

**Classes de recurso e campo:** RES-CMD, RES-AUD — Resultado técnico, origem, prova/estado oficial distinto. **Ações candidatas:** registrar/transmitir evidência técnica; proprietário confirma negócio.

**Fonte de autoridade:** INT-04 + proprietário; **solicitante contextual da CT-02:** Principal de serviço confiável e módulo proprietário. **Critério proposto de acesso:** Fonte autenticada, correlação e estado compatíveis, sem poder de inventar confirmação.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Registrar prova técnica não requer aprovação humana nova por padrão; retificar negócio só pelo dono Sem nova aprovação para registrar fato técnico legítimo; retificação de negócio segue proprietário

**Negativa obrigatória na revisão documental:** HTTP aceito convertido em pagamento/lead confirmado. **Salvaguarda preexistente de referência:** Resultado HTTP não modifica status de negócio; evento contraditório é investigado.

**Evidência e teste futuro:** HT-08,HT-14; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-05-APP — Gerenciar aplicativo integrado

**Classes de recurso e campo:** RES-APP, RES-AUT — App/tenant/plano/escopos e credencial referenciada. **Ações candidatas:** cadastrar/limitar app e escopos conforme plano.

**Fonte de autoridade:** INT-05 + Segurança; **solicitante contextual da CT-02:** Administrador/delegado autorizado do tenant e serviço técnico escopado. **Critério proposto de acesso:** Permissões de apps e escopos, elegibilidade comercial/tenant.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Ampliação de escopos/aplicações e efeitos comerciais dependem de donos/políticas vigentes Aprovação para acesso ampliado ou ação crítica, quando política exigir; contratação depende de FIN/CON

**Negativa obrigatória na revisão documental:** API pública habilitada no trial, app obtém dados globais. **Salvaguarda preexistente de referência:** Trial não libera API pública; não conceder acesso implícito a registros.

**Evidência e teste futuro:** HT-04,HT-13; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-05-API — Chamar API pública

**Classes de recurso e campo:** RES-APP, RES-DATA, RES-CMD — Escopo por operação/recurso/campo e elegibilidade do plano. **Ações candidatas:** invocar ação tipada e ler somente campos autorizados.

**Fonte de autoridade:** INT-05 fronteira; proprietário o efeito; **solicitante contextual da CT-02:** Aplicação/usuário autenticado com delegação válida. **Critério proposto de acesso:** API habilitada no plano, tenant, app, token e operação/objeto/fields autorizados.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Autenticação da API não equivale à aprovação do ato de negócio Aprovação do efeito de negócio quando exigida, não apenas autenticação de API

**Negativa obrigatória na revisão documental:** token A acessa tenant B; trial usa API pública. **Salvaguarda preexistente de referência:** Bloquear no trial, preservar integrações nativas permitidas; checar recurso individual.

**Evidência e teste futuro:** HT-04,HT-13; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-06-IN — Receber webhook externo

**Classes de recurso e campo:** RES-EVT, RES-CMD — Evento externo, assinatura/versão/payload/correlação. **Ações candidatas:** receber/verificar e encaminhar ao Motor Central de Eventos.

**Fonte de autoridade:** INT-06 + Eventos; **solicitante contextual da CT-02:** Provedor/assinatura identificados e validados no contexto de conexão. **Critério proposto de acesso:** Validação de origem/autenticidade, tenant, evento, versão, proteção anti-replay.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Assinatura não substitui autorização sobre recurso de negócio Nenhuma assinatura de webhook concede aprovação do ato de negócio

**Negativa obrigatória na revisão documental:** replay, assinatura inválida, payload cruzado. **Salvaguarda preexistente de referência:** Sem segredo no payload/log; não assumir que webhook externo já é evento interno confirmado.

**Evidência e teste futuro:** HT-05,HT-16; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-06-OUT — Entregar webhook ao destino

**Classes de recurso e campo:** RES-EVT — Assinante/destino cadastrado, finalidade e campos permitidos. **Ações candidatas:** enviar apenas notificação autorizada; gerenciar reentrega técnica.

**Fonte de autoridade:** INT-06; **solicitante contextual da CT-02:** Serviço autorizado a notificar assinante habilitado. **Critério proposto de acesso:** Evento/assinante/destino/finalidade/escopo de campos e URL registrada autorizados.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Configurar assinatura/destino sensível depende de autoridade, não de URL livre Configuração de assinatura e entrega sujeitas a alçadas de mudanças sensíveis

**Negativa obrigatória na revisão documental:** SSRF, dado pessoal de outro tenant, 2xx que não comprova consumo. **Salvaguarda preexistente de referência:** Impedir envio cross-tenant e vazamento de dados; HTTP de destino é aceite técnico.

**Evidência e teste futuro:** HT-05,HT-13; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-07-SYNC — Executar sincronização incremental

**Classes de recurso e campo:** RES-SYNC, RES-DATA — Fonte/destino/campo/direção/cursor e mudança lógica. **Ações candidatas:** ler alterações permitidas; gravar somente campos/direção autorizados.

**Fonte de autoridade:** INT-07 + proprietário do dado; **solicitante contextual da CT-02:** Serviço habilitado sob delegação de fonte/destino; operador autorizado. **Critério proposto de acesso:** Entidade/campos/direção/fonte/destino e conexão vigentes.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Dono decide conflito e exclusão; capacidade bidirecional não autoriza toda mutação Conflitos ou alterações sensíveis exigem decisão do proprietário conforme política

**Negativa obrigatória na revisão documental:** exclusão por ausência, checkpoint sem persistência. **Salvaguarda preexistente de referência:** Não usar ausência como exclusão automática; checkpoint depois de confirmação apropriada.

**Evidência e teste futuro:** HT-06,HT-08; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-07-RET — Retomar sincronização

**Classes de recurso e campo:** RES-SYNC, RES-INC — Checkpoint/versão/credencial e efeitos incertos. **Ações candidatas:** retomar após conferir autorização e histórico de confirmação.

**Fonte de autoridade:** INT-07/10 + proprietário do dado; **solicitante contextual da CT-02:** Serviço homologado com revalidação; operador de recuperação habilitado. **Critério proposto de acesso:** Checkpoint íntegro, token/conexão/versão ainda válidos, efeitos não duplicáveis.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Retomada não reaproveita aprovação fora do contexto nem repete ato arriscado Retomada de risco aumenta alçada conforme política, sem dupla aprovação para mesma decisão

**Negativa obrigatória na revisão documental:** retry duplicado, cursor inconsistente, token revogado. **Salvaguarda preexistente de referência:** Se estado incerto, reconciliar antes de avançar cursor ou repetir escrita.

**Evidência e teste futuro:** HT-06,HT-16; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-08-IMP — Importar/migrar/restaurar dados delimitados

**Classes de recurso e campo:** RES-PORT, RES-DATA — Arquivo/lote/registros/campos/destino e prévia. **Ações candidatas:** validar/preview; importar/migrar por escopo aprovado.

**Fonte de autoridade:** INT-08 + IN-118 + proprietário; **solicitante contextual da CT-02:** Usuário autorizado à portabilidade e gestores dos dados envolvidos. **Critério proposto de acesso:** Direitos na origem e destino, entidade/campo e arquivo; prévia/manifesto e separação entre tenants.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Direitos efetivos na origem/destino; sobrescrita/mesclagem destrutiva não presumida Confirmação/aceite para operação destrutiva ou conflito conforme política de proteção

**Negativa obrigatória na revisão documental:** arquivo inseguro, efeito parcial, cross-account, sobrescrita. **Salvaguarda preexistente de referência:** Não restaurar segredos de outro tenant; não mesclar/destruir automaticamente registros.

**Evidência e teste futuro:** HT-07,HT-13; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-08-EXP — Exportar dados/pacote

**Classes de recurso e campo:** RES-PORT, RES-DATA — Conjunto de campos, classificação, manifesto e link seguro. **Ações candidatas:** exportar dados autorizados; consulta e download controlados.

**Fonte de autoridade:** IN-118/INT-08 + proprietário; **solicitante contextual da CT-02:** Usuário/serviço autorizado a ler/exportar escopo do tenant. **Critério proposto de acesso:** Escopo de consulta e exportação, campos sensíveis, finalidade e duração do link.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Exportar pode requerer direito separado de ler; autoatendimento essencial deve permanecer viável Exportação sensível/em massa pode exigir confirmação reforçada conforme política, sem revogar autoatendimento essencial

**Negativa obrigatória na revisão documental:** campo sensível vazado, quota de anexo bloqueando exportação. **Salvaguarda preexistente de referência:** Franquia cheia de anexos não bloqueia exportação dos dados existentes; auditoria da extração.

**Evidência e teste futuro:** HT-17,HT-13; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-09-INI — Instanciar/processar fluxo multissistema

**Classes de recurso e campo:** RES-FLOW, RES-CMD — Modelo/etapa/instância, dependências e recurso de cada efeito. **Ações candidatas:** iniciar/continuar processo dentro de modelo elegível.

**Fonte de autoridade:** INT-09 + proprietários; **solicitante contextual da CT-02:** Operador/serviço autorizado pelo modelo/instância e pelo tenant. **Critério proposto de acesso:** Modelo/versionamento, autorização de cada etapa, recurso, aprovação e dependências.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Cada etapa crítica tem autoridade de seu efeito; nenhuma aprovação universal do orquestrador Aprovações específicas dos efeitos críticos, não aprovação universal pelo orquestrador

**Negativa obrigatória na revisão documental:** fim técnico sem conclusão do negócio; ramo com dependência faltante. **Salvaguarda preexistente de referência:** Revalidar etapa assíncrona; não dispensar dependência por sucesso independente.

**Evidência e teste futuro:** HT-09,HT-15; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-09-COM — Solicitar compensação

**Classes de recurso e campo:** RES-FLOW, RES-CMD — Efeito original, reversibilidade, domínio, alçada e resultado próprio. **Ações candidatas:** solicitar compensação; executar apenas via proprietário competente.

**Fonte de autoridade:** Proprietário FIN/CON/AGD/etc. + INT-09 coordena; **solicitante contextual da CT-02:** Responsável autorizado a solicitar reversão; executor competente. **Critério proposto de acesso:** Elegibilidade de reversão, efeito original, risco, escopo e estado atual.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Aprovação do ato compensatório é distinta da original quando exigida Aprovação específica do ato de compensação na alçada proprietária

**Negativa obrigatória na revisão documental:** estorno sem alçada, efeito irreversível, timeout de compensação. **Salvaguarda preexistente de referência:** Sem estorno/cancelamento automático por IA; compensação parcial continua explícita.

**Evidência e teste futuro:** HT-10,HT-15; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-10-DIA — Investigar incidente

**Classes de recurso e campo:** RES-INC, RES-AUD — Telemetria/tenant/ambiente/hipótese/tempo e categoria sensível. **Ações candidatas:** consultar sinais de escopo; correlacionar/diagnosticar.

**Fonte de autoridade:** Observabilidade/Incidentes; INT-10; **solicitante contextual da CT-02:** Identidade técnica/funcionário com acesso de diagnóstico autorizado. **Critério proposto de acesso:** Acesso mínimo a telemetria por ambiente, serviço e tenant; dados minimizados.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Direito de diagnóstico não autoriza recuperação nem acesso irrestrito a dados Monitoramento de baixo risco dentro das permissões existentes; nenhuma aprovação de negócio implícita

**Negativa obrigatória na revisão documental:** ausência de telemetria tida por saudável, acesso interno indiscriminado. **Salvaguarda preexistente de referência:** Não misturar operação interna Imagine e dados de tenants; telemetria ausente=desconhecida.

**Evidência e teste futuro:** HT-11,HT-16; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-10-REC — Executar recuperação técnica

**Classes de recurso e campo:** RES-INC, RES-CMD — Playbook/versão, ambiente/tenant e ação efetiva. **Ações candidatas:** executar ação técnica tipada homologada dentro da identidade.

**Fonte de autoridade:** INT-10 + Segurança e dono do efeito; **solicitante contextual da CT-02:** Agente/operador escopado com playbook homologado. **Critério proposto de acesso:** Ação e ambiente específicos permitidos; limites e pré-condições do playbook.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** N2 limitado a playbooks homologados; N3 crítico depende de aprovação humana quando exigida N2 homologado/seguro pode seguir aprovação prévia; intervenção crítica requer aprovação humana específica quando exigida

**Negativa obrigatória na revisão documental:** IA altera alçada, retry de escrita que não provou falha. **Salvaguarda preexistente de referência:** IA não concede privilégios; resultado técnico não confirma operação de negócio.

**Evidência e teste futuro:** HT-11,HT-08; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-11-HOM — Homologar/publicar conector

**Classes de recurso e campo:** RES-CONN — Versão/artefato/proveniência/capacidade e escopo de publicação. **Ações candidatas:** revisar/homologar/publicar versão via competência.

**Fonte de autoridade:** INT-11 + Segurança/Aprovações; **solicitante contextual da CT-02:** Revisor e publicador autorizados; parceiro apenas submete quando permitido. **Critério proposto de acesso:** Permissão de homologar versão/artefato e ambiente; isolamento de terceiros.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Publicação controlada; submissão de parceiro não é aprovação Publicação controlada exige aprovação humana adequada; não herdar aceite da versão anterior

**Negativa obrigatória na revisão documental:** terceiro executa código livre, versão não homologada publicada. **Salvaguarda preexistente de referência:** Não executar código livre de terceiros nem distribuir antes de homologação.

**Evidência e teste futuro:** HT-12,HT-18; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-11-SUS — Suspender/revogar conector

**Classes de recurso e campo:** RES-CONN, RES-CON — Conector/versão/abrangência/instâncias ativas. **Ações candidatas:** restringir versão; analisar conexões impactadas.

**Fonte de autoridade:** INT-11 + INT-01/02; **solicitante contextual da CT-02:** Operador de catálogo/segurança com autoridade específica. **Critério proposto de acesso:** Direito de restrição por conector/versão e abrangência de clientes.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Alçada de contenção por impacto; não cancela automaticamente efeitos anteriores Aprovação conforme risco; contenção urgente homologada segue política técnica existente

**Negativa obrigatória na revisão documental:** migração silenciosa, efeito anterior inconclusivo. **Salvaguarda preexistente de referência:** Não migrar conexões ativas silenciosamente; notificar pelos serviços centrais competentes.

**Evidência e teste futuro:** HT-18,HT-12; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-12-INV — Consultar/investigar auditoria

**Classes de recurso e campo:** RES-AUD — Caso/tenant/identidade/classificação e trilhas. **Ações candidatas:** consultar evidência dentro de finalidade e campo permitido.

**Fonte de autoridade:** Auditoria Geral; INT-12 consulta; **solicitante contextual da CT-02:** Funcionário autorizado a investigar escopo definido. **Critério proposto de acesso:** Acesso por tenant, caso, classificação e finalidade, inclusive segregação interna.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Investigador autorizado não tem acesso universal; consulta auditável Alçada de investigação/visão sensível segundo política; não presumir gestor com acesso total

**Negativa obrigatória na revisão documental:** investigação lê tenant B ou altera fonte. **Salvaguarda preexistente de referência:** Consultas também auditáveis, hipóteses separadas de fatos, sem manipular evidência.

**Evidência e teste futuro:** HT-14,HT-17; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.

### OP-12-AUD — Exportar evidências auditáveis

**Classes de recurso e campo:** RES-AUD — Registro/caso/campo/manifesto e artefato de exportação. **Ações candidatas:** exportar evidência autorizada, com minimização e proteção.

**Fonte de autoridade:** Auditoria Geral; INT-12 interface; **solicitante contextual da CT-02:** Responsável com permissão específica de exportação. **Critério proposto de acesso:** Direito de acesso e exportação por escopo, registro, período, finalidade, retenção.

**Limite de alçada, a confirmar com o proprietário e a Segurança:** Direito de exportar pode ser distinto da leitura e sujeito a alçada real Confirmação reforçada e aprovação específica quando exigidas, sem download indiscriminado

**Negativa obrigatória na revisão documental:** exportação em massa indevida, link público ou sem trilha. **Salvaguarda preexistente de referência:** Minimização e marca de proveniência; registrar a própria exportação; sem retenção ilimitada.

**Evidência e teste futuro:** HT-17,HT-14; documentar permissão por recurso/campo, identidade e escopo, versão/estado, revalidação no ponto protegido, decisão legítima da Central de Aprovações quando aplicável e fonte oficial do efeito. **HT ainda não executado.**

**Pendências:** codificar direitos existentes reais, valores/estados autorizados, MFA por ato, nível de acesso interno/tenant, condições de aprovação e exceções; **não presumir concessão de permissão**.


## 6. Questões de decisão P0-OWN-01 / P0-SEC-01

| ID | Tema | Pergunta a resolver antes de finalizar contratos executáveis | Competência de revisão (não pessoa designada) |
|---|---|---|---|
| OWN-Q01 | Identidade e papel efetivo | Quais papéis e identidades técnicas já aprovadas existem, sua segregação e competência por empresa/unidade/marca? | Identidade e Segurança, responsáveis dos módulos |
| OWN-Q02 | Direito por ação | Para cada OP/recurso: visualizar, criar, editar, excluir, executar, publicar, suspender, consultar, importar, exportar ou aprovar — quais são diferentes? | Proprietário do recurso com Segurança |
| OWN-Q03 | Granularidade do campo | Que campos são públicos no tenant, internos, pessoais/sensíveis, credenciais/metadados seguros, provas restritas; quando mascarar? | Proprietário dos dados, Segurança, privacidade |
| OWN-Q04 | Relação de conta/tenant | Como validar vínculo e existência do recurso, inclusive origem e destino de portabilidade ou mudança de CRM? | INT-01/02, IN-118, módulo proprietário |
| OWN-Q05 | Delegação/impersonação | Quando a equipe Imagine pode atuar em nome de cliente e como comprovar autorização/limites/expiração? | Identidade e Segurança, suporte e proprietário |
| OWN-Q06 | Alçada e MFA | Que efeitos demandam MFA/step-up, aprovação humana, segregação entre solicitante/aprovador/executor? Qual reuso válido? | Segurança, Central de Aprovações e proprietário |
| OWN-Q07 | Credenciais/cofre | Quais metadados da autorização externa podem ser lidos e por quem; segredos sempre no IN-151? | INT-02, Segurança, IN-151 |
| OWN-Q08 | Revogação e fila | Como revogação/suspensão afeta novos atos, comandos anteriores, tentativas inconclusivas e versões ativas? | INT-01/02/04/09/10 e proprietários |
| OWN-Q09 | Auditoria de acesso | Quais consultas/exportações e atos sensíveis são rastreados, com retenções por classe e acesso à evidência? | Auditoria Geral, INT-12, Segurança |
| OWN-Q10 | Portabilidade autoatendida | Como assegurar exportação/importação/backup/recuperação essenciais em todos os planos sem ampliar acesso a outra empresa? | IN-118, INT-08, Segurança, proprietários |
| OWN-Q11 | Conflitos e compensações | Quem resolve conflito de dados, autoriza reversão, e confirma fato oficial sem poderes transferidos às INTs? | Módulos proprietários FIN/CON/AGD/VEN/ATD/CAT etc. |
| OWN-Q12 | Proveniência literal | Quais permissões/ações foram realmente aprovadas nas INT-01..12 e R01/R02, versus exemplos propostos? | Arquitetura INT e responsáveis pelas decisões |

## 7. Regras transversais de negativa e continuidade (para revisão)

- A consulta autorizada de um campo **não** concede sua escrita; a escrita não concede exportação em massa; uma identidade técnica não recebe poderes de administrador do tenant.
- Revalidação na fronteira protegida e após revogação/alteração de vínculo mantém a distinção entre **ato novo** e comando **já transmitido**. Resposta externa inconclusiva requer reconciliação, sem retry cego.
- A Central de Aprovações mantém o **mesmo ato/escopo/validade** na decisão autorizadora. Não exigir **aprovação humana duplicada** da mesma decisão; não aproveitar aprovação de outro efeito ou risco.
- Agentes IA usam identidades limitadas e playbooks homologados; nenhum prompt, recomendação, diagnóstico ou aprovação de outro agente cria alçada.
- Portabilidade da **IN-118** continua disponível em todos os planos; não bloquear exportação de dados existentes por quota de anexos cheia. Transferência entre contas exige direitos apropriados em **origem e destino**.
- Importação/exportação e webhook devem usar somente campos efetivamente autorizados para a finalidade e destino. Classe de dado pessoal ou prova sensível exige revisão de minimização e mascaramento.
- Acesso à Auditoria Geral não deve cruzar tenants/casos; consulta não implica exportação. Logs técnicos IN-162 têm retenção própria, distinta da evidência oficial.
- **Cada negativa deve ser testada futuramente** pelos HT-01..18 aplicáveis; os planos existem, mas **0 testes foram executados**.

## 8. Critérios de aceite documental futuro e status

| Critério | Evidência que falta | Situação |
|---|---|---|
| OWN-A01 — titularidade | Confirmar donos por recurso/campo e categorias derivadas, inclusive submódulos de FIN/CON/AGD/ATD/VEN/CAT | **Proposta parcial** |
| OWN-A02 — papéis e direitos | Referenciar matriz efetiva de roles, escopos, delegações e ações por empresa/unidade/marca e tenant | **Pendente** |
| OWN-A03 — alçada/MFA | Fixar quando e por quem aplicar alçada, step-up e confirmação, com validade, risco e ausência de duplicação indevida | **Pendente** |
| OWN-A04 — contratos tipados | Definir campos físicos/estados/erros e precondições por operação e provedor | **P0-CON aberto** |
| OWN-A05 — compatibilidade/retomada | Definir revalidação em instâncias antigas, fila, revogação e efeitos inconclusivos | **P0-VER/DEP abertos** |
| OWN-A06 — proteção/portabilidade | Confirmar campos mascarados, exportações, consentimentos aplicáveis, IN-118, IN-151, IN-162 e Auditoria Geral | **P0-XREF aberto** |
| OWN-A07 — origem/aceite | Cotejar propostas/aceites originais; revisar por proprietários e obter aceite expresso do escopo final | **P0-DOC/TR e G01–G06 abertos** |
| OWN-A08 — planos HT | Conectar negativas e evidências às 18 homologações futuras e aos responsáveis por revisá-las | **P0-ACC parcial; 0 HT executados** |

**Conclusão:** classes de recursos/campos e direitos candidatos **mapeados para 24/24 operações de referência**, sem aprovação de perfis, alçadas, políticas executáveis, contratos físicos ou programação. **P0-OWN-01 e P0-SEC-01 permanecem ABERTOS/PARCIAIS**, bem como P0-CON/VER/DEP/TR/DOC/ACC/GIT e critérios G01–G06.
