# P0-CON-OP09 — Contratos documentais das nove operações complementares

**Data:** 2026-11-03. **Estado:** **PROPOSTA DOCUMENTAL PARA REVISÃO**. Conclui o **segundo lote de desdobramento das 24 fichas de referência OP24**; **não** representa aprovação definitiva de contratos por operação nem autorização de execução.
**Base:** [CON-MASTER aceita como instrumento documental](P0-DEC-aprovacao-CON-MASTER-base.md), [OP24 aceita como base](P0-DEC-aprovacao-OP24-base.md), [OP15 detalhadas em proposta](P0-CON-OP15-contratos-operacoes-impacto-elevado.md), [CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md) e [CT-01..11](P0-CON-MASTER-matriz-CT01-CT11-criterios-fechamento.md).
**Fase:** documentação apenas; nenhuma automação, integração real, código, teste, alteração de produção ou despesa.

## 1. Natureza do desdobramento

As nove operações abaixo já estavam catalogadas entre as 24 OP de referência; a presente ficha as **especializa em conceitos de contrato, precondições, autorização, resultado, falhas, reconciliação e lacunas**. Não acrescenta módulos, propostas de capacidades novas nem aprova alçadas por presunção.

- Identidade e Segurança, INT-02 e Central de Aprovações conservam decisões sobre identidade, escopos e alçadas.
- INT-11 governa homologação; INT-01 mantém conexão; INT-03 valida/publica mapeamentos; INT-05 rege aplicação/API pública; INT-06 rege webhook externo; INT-07 checkpoint; INT-10 diagnóstico; INT-12 investiga via Auditoria Geral.
- A API pública segue **indisponível no trial**, sem negar integrações nativas elegíveis; dados são segregados por tenant; mapeamento prévio não autoriza escrita.
- Logs técnicos/observabilidade e auditoria geral possuem competências diferentes. CT-03/04 mantêm comando/tentativa/resultados técnicos separados de fatos oficiais.
- Status desta especificação **não é um estado de API**, e os exemplos de campos/transições não definem schema aprovado.

## 2. Índice de operações do segundo lote

| OP | Tema | Famílias CT já relacionadas | TR da base OP24 | HT de referência | Estado |
|---|---|---|---|---|---|
| OP-01-CAD | Cadastrar ou atualizar conexão | CT-01/02/03/04/11 | TR-INT01-001..004 | HT-01/13/18 | Minuta; não homologada |
| OP-03-VAL | Validar esquema ou prévia de transformação | CT-02/03/04/07 | TR-INT03-001..003 | HT-03/07 | Minuta; não homologada |
| OP-03-VER | Publicar nova versão de mapeamento | CT-02/03/04/06/07 | TR-INT03-001/004 | HT-03/12 | Minuta; não homologada |
| OP-05-APP | Registrar ou alterar aplicação integrada | CT-01/02/04/11 | TR-INT05-001..003 | HT-04/13 | Minuta; não homologada |
| OP-05-API | Invocar operação pela API pública | CT-02/03/04/11 | TR-INT05-001..004 | HT-04/08/13 | Minuta; não homologada |
| OP-06-OUT | Entregar webhook externo autorizado | CT-02/04/05/09 | TR-INT06-002..004 | HT-05/13/16 | Minuta; não homologada |
| OP-07-SYNC | Sincronizar alterações incrementais | CT-02/03/04/06/09 | TR-INT07-001..004 | HT-06/08/16 | Minuta; não homologada |
| OP-10-DIA | Detectar e diagnosticar incidente de integração | CT-02/04/09/11 | TR-INT10-001/004/005 | HT-11/14/16 | Minuta; não homologada |
| OP-12-INV | Investigar operação com correlação de evidências | CT-02/04/09/11 | TR-INT12-001/002/005 | HT-13/14/17 | Minuta; não homologada |

## 3. Nove fichas de revisão

## OP-01-CAD — Cadastrar ou atualizar conexão

**Finalidade e risco:** Cadastrar ou atualizar conexão do tenant com conta externa e conector homologado, sem ativar direitos ou redirecionar operações passadas. **Domínio de revisão:** Configuração de conexão.

**Fonte da autoridade e separação de competências:** INT-01 detém a conexão; INT-11 homologa o conector; INT-02/Segurança controlam credenciais e identidade.

**Referências existentes:** CT CT-01/02/03/04/11; TR TR-INT01-001..004; HT HT-01/13 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | Identidade solicitante/tenant, conta externa, provedor/conector/versão, finalidade, ambiente, estado atual e dependências da conexão |
| Autorização contextual, direitos e alçadas | Permissão para administrar conexão no tenant; verificação de versão/capacidade homologadas; aprovação da alteração apenas conforme risco e política já existente |
| Comando/precondições e efeitos permitidos | Cadastrar/alterar vínculo de conta dentro do escopo e sem migrar eventos, históricos de CRM ou instâncias pendentes automaticamente |
| Resultado técnico/oficial e sua proveniência | Estado/versão da conexão confirmado por INT-01, com origem, alcance e referência de autorizações externas; não é permissão automática para executar |
| Casos negativos e riscos | Repetição da mesma solicitação, tentativa de vínculo com conta de outro tenant, capacidade suspensa, credencial revogada, alteração concomitante de destino |
| Reconciliação/falha/continuidade | Conferir conexões/versionamento e filas pendentes; preservar referência de destino prévia dos comandos e formulários já recebidos |
| Pendências para homologação documental futura | Escopos e nomes de permissão, transições físicas de ligação, tipo de conta, política de troca de destino, aprovação por impacto e idempotência do cadastro |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Gestor autorizado da conexão no tenant; serviço interno escopado; autoridade: INT-01 / Identidade e Segurança; conector INT-11, credenciais INT-02; controle de acesso: Permissão de administrar conexão do próprio tenant; conexão/conta/versão corretas; política de revisão/alçada a definir: Requer alçada de alteração conforme risco; não pressupor aprovação adicional universal; impedimento adicional: Revisar tenant, destino, conector e vínculo antes de confirmar; não alterar destino de operações passadas. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-01/13/18. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.

---

## OP-03-VAL — Validar esquema ou prévia de transformação

**Finalidade e risco:** Validar e pré-visualizar transformação de esquema/mapeamento sem causar gravações nas entidades originais. **Domínio de revisão:** Validação de dados.

**Fonte da autoridade e separação de competências:** INT-03 valida mapeamento; proprietário de dados controla conteúdo, acesso e posterior escrita.

**Referências existentes:** CT CT-02/03/04/07; TR TR-INT03-001..003; HT HT-03/07 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | Tenant, fonte/destino, entidades/campos, classificação dos dados, versões de esquema/mapeamento, amostra sintética ou autorizada |
| Autorização contextual, direitos e alçadas | Direitos de leitura dos campos e finalidade; mascaramento, minimização e segregação; não derivar autorização de escrita da prévia |
| Comando/precondições e efeitos permitidos | Processar verificação tipada/preview sem mutação de registros, reportar incompatibilidades, regras de transformação e erros por campo |
| Resultado técnico/oficial e sua proveniência | Relatório de validação/rejeição de INT-03, com versão e referência de entrada; nenhuma confirmação de persistência de negócio |
| Casos negativos e riscos | Campo obrigatório ausente, tipo incompatível, transformação ambígua, dado sensível exposto ou amostra de tenant alheio |
| Reconciliação/falha/continuidade | Revalidar versão e correções de mapeamento, preservar falhas sem executar SQL/código livre e sem editar a fonte |
| Pendências para homologação documental futura | Tipos/formato e limites por campo, catálogo de transformações homologadas, erro padronizado e critérios de sensibilidade/mascaramento |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Usuário com permissão para visualizar campos de origem/destino; autoridade: INT-03; proprietário controla dados; controle de acesso: Leitura dos campos/classificações e ambiente autorizados; prévia não concede escrita; política de revisão/alçada a definir: Sem aprovação nova quando somente leitura autorizada; dados sensíveis podem exigir controle adicional; impedimento adicional: Máscara/minimização; nenhum ajuste de dado oficial por validação. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-03/07. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.

---

## OP-03-VER — Publicar nova versão de mapeamento

**Finalidade e risco:** Publicar versão de esquema/mapeamento homologada com impacto conhecido e sem troca silenciosa da versão consumida por instâncias. **Domínio de revisão:** Governança de versão.

**Fonte da autoridade e separação de competências:** INT-03 publica mapa; módulos de dados e consumidores INT-07/08 atestam compatibilidade das suas operações.

**Referências existentes:** CT CT-02/03/04/06/07; TR TR-INT03-001/004; HT HT-03/12 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | Versão anterior/nova, origem, diff de esquema, contratos/campos impactados, instâncias/cursores/lotes ativos e evidência de revisão |
| Autorização contextual, direitos e alçadas | Direito de publicar esquema para tenant/ambiente/entidades permitidos; revisão e aprovação conforme criticidade real, sem presumir aprovação humana universal |
| Comando/precondições e efeitos permitidos | Registrar/publicar versão elegível, compatibilidade e abrangência; instâncias em andamento continuam com versões fixadas até plano de transição aprovado |
| Resultado técnico/oficial e sua proveniência | INT-03 confirma publicação da versão exata; não implica migração, recriação de eventos ou alteração de dado existente |
| Casos negativos e riscos | Versão incompatível, evento com esquema antigo, cursor pendente, publicação concorrente ou execução por agente sem alçada |
| Reconciliação/falha/continuidade | Preservar acesso à versão anterior quando necessário, tratar incompatibilidade explicitamente e migrar somente mediante autorização/contrato |
| Pendências para homologação documental futura | Matriz V-C, limiares de incompatibilidade, validade de publicação, protocolo de rollback de disponibilidade e revisão de impacto |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Responsável autorizado à gestão de esquemas; autoridade: INT-03 e módulos consumidores; políticas de homologação; controle de acesso: Direito de publicar versão, ambiente e conjunto de entidades permitidos; política de revisão/alçada a definir: Revisão/aprovação quando versão incompatível ou impacto significativo, conforme governança; impedimento adicional: Proibir atualização silenciosa de instâncias e mapeamentos ativos; compatibilidade documentada. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-03/12. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.

---

## OP-05-APP — Registrar ou alterar aplicação integrada

**Finalidade e risco:** Registrar ou alterar aplicação integrada com direitos escopados e plano elegível, sem criar acesso implícito a todos os objetos. **Domínio de revisão:** Identidade de aplicação integrada.

**Fonte da autoridade e separação de competências:** INT-05 detém cadastro de aplicação; Identidade e Segurança e INT-02 controlam autorizações; FIN/CON detêm eventuais efeitos comerciais.

**Referências existentes:** CT CT-01/02/04/11; TR TR-INT05-001..003; HT HT-04/13 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | Tenant, aplicativo/identidade, plano/trial, finalidade, escopos, cliente/delegação, versão de capacidade e estado atual |
| Autorização contextual, direitos e alçadas | Gestor/delegado autorizado; elegibilidade de plano, ausência de acesso API pública no trial, aprovação de ampliação de escopos se a política exigir |
| Comando/precondições e efeitos permitidos | Cadastrar, restringir ou versionar aplicação e escopos mínimos pela interface homologada, sem criar cobrança nem conexão sem competência do proprietário |
| Resultado técnico/oficial e sua proveniência | Aplicativo e escopos registrados em INT-05 e autorização própria; nenhum dado de negócio automaticamente liberado |
| Casos negativos e riscos | App duplicado, tenant cruzado, ampliação indevida de escopo, token exposto, trial tentando habilitar API pública |
| Reconciliação/falha/continuidade | Conferir identidade e permissões reais com provedores/cofre aplicáveis, suspender acesso impróprio e preservar histórico/versões |
| Pendências para homologação documental futura | Contratos de aplicativo, fluxos de credenciamento, regras de plano e quota, rotação/revogação e alçada de escopo ampliado |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Administrador/delegado autorizado do tenant e serviço técnico escopado; autoridade: INT-05, INT-02 e Identidade e Segurança; controle de acesso: Permissões de apps e escopos, elegibilidade comercial/tenant; política de revisão/alçada a definir: Aprovação para acesso ampliado ou ação crítica, quando política exigir; contratação depende de FIN/CON; impedimento adicional: Trial não libera API pública; não conceder acesso implícito a registros. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-04/13. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.

---

## OP-05-API — Invocar operação pela API pública

**Finalidade e risco:** Exercer capacidade tipada autorizada da API pública sem contornar plano, tenant, autorização por recurso ou regras do domínio. **Domínio de revisão:** Invocação de API pública.

**Fonte da autoridade e separação de competências:** INT-05 controla fronteira/API; INT-04 transporta o comando aplicável; módulo proprietário confirma efeito.

**Referências existentes:** CT CT-02/03/04/11; TR TR-INT05-001..004; HT HT-04/13 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | Tenant, app e principal, plano/trial, token/escopo, operação e contrato versionados, recurso/campo, referência lógica de solicitação |
| Autorização contextual, direitos e alçadas | Plano elegível, API pública desabilitada no trial; direitos por ação e recurso; aprovação de negócio do proprietário quando exigida |
| Comando/precondições e efeitos permitidos | Validar contrato e autorização por requisição, encaminhar somente ação tipada, preservar identidade lógica e precondições nas tentativas |
| Resultado técnico/oficial e sua proveniência | Resposta técnica da API distinta do resultado oficial do domínio; erro de autenticação/quota/plano não autoriza outro endpoint |
| Casos negativos e riscos | Token válido para conta errada, trial com API habilitada, alternativa por n8n, chamada repetida após timeout, campos não autorizados |
| Reconciliação/falha/continuidade | Consultar resultado no módulo proprietário quando necessário, preservar operações iniciadas e não reexecutar escrita inconclusiva |
| Pendências para homologação documental futura | Escopos/endpoints oficiais, quotas por plano, códigos de erro e autenticação, contrato de idempotência e versão da API |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Aplicação/usuário autenticado com delegação válida; autoridade: INT-05 fronteira; proprietário confirma efeito; controle de acesso: API habilitada no plano, tenant, app, token e operação/objeto/fields autorizados; política de revisão/alçada a definir: Aprovação do efeito de negócio quando exigida, não apenas autenticação de API; impedimento adicional: Bloquear no trial, preservar integrações nativas permitidas; checar recurso individual. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-04/08/13. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.

---

## OP-06-OUT — Entregar webhook externo autorizado

**Finalidade e risco:** Entregar notificação externa por assinatura e destino elegíveis sem expor campos de outro tenant ou presumir consumo de negócio. **Domínio de revisão:** Entrega de webhook externo.

**Fonte da autoridade e separação de competências:** INT-06 governa webhook externo; Eventos mantém fatos internos e COM detém comunicações dirigidas a clientes.

**Referências existentes:** CT CT-02/04/05/09; TR TR-INT06-002..004; HT HT-05/16 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | tenant, evento/versão, assinatura/assinante, destino previamente cadastrado, escopo de campos, finalidade, identidade lógica da entrega |
| Autorização contextual, direitos e alçadas | Assinatura ativa e destino homologado, dados minimizados, autenticação e tenant compatíveis; sem URL livre |
| Comando/precondições e efeitos permitidos | Gerar entrega técnica assinada por método homologado e correlacionar tentativas a evento e assinatura estáveis |
| Resultado técnico/oficial e sua proveniência | INT-06 atesta envio/ack/pendência/inconclusão; HTTP 2xx do assinante não é confirmação de aplicação de negócio |
| Casos negativos e riscos | Destino inseguro/SSRF, assinatura revogada, duplicata, falha pós-envio, payload com dado cross-tenant, callback fora de ordem |
| Reconciliação/falha/continuidade | Controlar reentregas por assinatura e evento conforme contrato seguro, investigar entrega inconclusiva sem recriar o fato de origem |
| Pendências para homologação documental futura | Protocolo de assinatura/headers por provedor, política de fila/retry, TTL/retenção por evento, direitos de campos e redirecionamento de destino |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Serviço autorizado a notificar assinante habilitado; autoridade: INT-06 entrega; COM para comunicações ao cliente; controle de acesso: Evento/assinante/destino/finalidade/escopo de campos e URL registrada autorizados; política de revisão/alçada a definir: Configuração de assinatura e entrega sujeitas a alçadas de mudanças sensíveis; impedimento adicional: Impedir envio cross-tenant e vazamento de dados; HTTP de destino é aceite técnico. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-05/13/16. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.

---

## OP-07-SYNC — Sincronizar alterações incrementais

**Finalidade e risco:** Aplicar mudanças autorizadas de fonte para destino mantendo autoridade por campo e checkpoint fiel à persistência real. **Domínio de revisão:** Sincronização incremental.

**Fonte da autoridade e separação de competências:** INT-07 detém sync/cursor; INT-03 esquema, INT-04 comando e proprietário confirma cada escrita.

**Referências existentes:** CT CT-02/03/04/06/09; TR TR-INT07-001..004; HT HT-06/08 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | tenant, fonte/destino, entidade/campo, direção, esquema/versão, cursor, mudança lógica, estado/versão anteriores |
| Autorização contextual, direitos e alçadas | Direitos por campo e direção com conexão/autorização vigente; nenhum cross-tenant, exclusão ou mesclagem presumidos |
| Comando/precondições e efeitos permitidos | Aplicar somente mudança tipada e elegível, com identidade lógica/idempotência e checkpoint após confirmação apropriada |
| Resultado técnico/oficial e sua proveniência | INT-07 registra progresso; módulo proprietário atesta cada alteração, conflito ou rejeição |
| Casos negativos e riscos | Mudança fora de ordem, ausência tratada como exclusão, duplicidade, escrita confirmada sem ack, cursor promovido cedo, conflito de fontes |
| Reconciliação/falha/continuidade | Reconstituir checkpoint confirmado, consultar destino/fonte, preservar conflito para decisão do proprietário e retomar sem escrita duplicada |
| Pendências para homologação documental futura | Tipos de cursor por provedor, conflitos e tombstones, janelas de sync, concorrência, proteção de versão e garantias de ordenação |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Serviço habilitado sob delegação de fonte/destino; operador autorizado; autoridade: INT-07 coordena; proprietários gravam/confirmam; controle de acesso: Entidade/campos/direção/fonte/destino e conexão vigentes; política de revisão/alçada a definir: Conflitos ou alterações sensíveis exigem decisão do proprietário conforme política; impedimento adicional: Não usar ausência como exclusão automática; checkpoint depois de confirmação apropriada. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-06/08/16. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.

---

## OP-10-DIA — Detectar e diagnosticar incidente de integração

**Finalidade e risco:** Reconhecer e correlacionar sinais de incidente sem confundir ausência de telemetria com saúde nem diagnosticar fatos de negócio por hipótese. **Domínio de revisão:** Diagnóstico técnico.

**Fonte da autoridade e separação de competências:** INT-10 especialista, Observabilidade IN-159/160 e Central de Incidentes como serviços existentes, proprietários para efeitos.

**Referências existentes:** CT CT-02/04/09/11; TR TR-INT10-001/004/005; HT HT-11/16 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | componente/ambiente/tenant, incident_ref, telemetria/fonte/tempo, operação/command/attempt correlacionados, hipóteses e evidências |
| Autorização contextual, direitos e alçadas | Acesso mínimo de diagnóstico, minimização, segregação interna/tenant e autoridade para consultar fontes; sem permissão implícita de executar reparo |
| Comando/precondições e efeitos permitidos | Consultar sinais oficiais, correlacionar estados e apresentar hipótese separada de causa confirmada; escalar ao serviço competente |
| Resultado técnico/oficial e sua proveniência | Estado técnico observado ou desconhecido; incidente e evidência verificável no serviço central; nenhum negócio considerado concluído |
| Casos negativos e riscos | Falta de sinal tratada como saudável, mistura de dados entre tenants, debug expirado tratado como prova negativa, hipótese de IA assumida como fato |
| Reconciliação/falha/continuidade | Rever fontes oficiais e atrasos, classificar pendências e encaminhar a recuperação autorizada apenas se houver playbook/permissão aplicável |
| Pendências para homologação documental futura | Frescor e severidade de telemetria, acesso técnico por papel, categorias de registro, limiares e procedimentos de investigação |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Identidade técnica/funcionário com acesso de diagnóstico autorizado; autoridade: INT-10; Observabilidade IN-159/160; Central de Incidentes; controle de acesso: Acesso mínimo a telemetria por ambiente, serviço e tenant; dados minimizados; política de revisão/alçada a definir: Monitoramento de baixo risco dentro das permissões existentes; nenhuma aprovação de negócio implícita; impedimento adicional: Não misturar operação interna Imagine e dados de tenants; telemetria ausente=desconhecida. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-11/14/16. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.

---

## OP-12-INV — Investigar operação com correlação de evidências

**Finalidade e risco:** Investigar cadeia causal de comandos, permissões e negócios sem editar ou fabricar fatos oficiais. **Domínio de revisão:** Investigação auditável.

**Fonte da autoridade e separação de competências:** Auditoria Geral única fonte oficial; INT-12 interface especializada de consulta/correlação; proprietários atestam fatos.

**Referências existentes:** CT CT-02/04/09/11; TR TR-INT12-001/002/005; HT HT-14 (cenários planejados, não executados).. A referência não substitui cotejo literal original.

| Dimensão | Conteúdo documental sugerido, sem schema físico nem permissão nova |
|---|---|
| Identificadores, versões e dados mínimos | tenant/caso/escopo, principal e finalidade, intervalo, versões/IDs de comando/tentativa/efeito, integridade/classificação e evidências faltantes |
| Autorização contextual, direitos e alçadas | Direito específico de consultar por tenant/caso/campo, minimização e finalidade; exportação permanece permissão distinta quando exigida |
| Comando/precondições e efeitos permitidos | Consultar e correlacionar somente registros oficialmente disponíveis, identificar fontes e lacunas sem alterar efeito ou retificação fora do proprietário |
| Resultado técnico/oficial e sua proveniência | Linha do tempo investigativa com fatos e hipóteses claramente separados e consulta auditada na Auditoria Geral |
| Casos negativos e riscos | Investigador tenta acessar tenant B, evento sem proveniência, log expirado, IA inventa prova, consulta tenta executar reconciliação de negócio |
| Reconciliação/falha/continuidade | Verificar integridade/origem, consultar proprietário ou INT-10 para diagnóstico, registrar lacuna explícita e encaminhar divergência ao responsável |
| Pendências para homologação documental futura | Modelo de consulta por classe, auditoria da auditoria, classificação e retenção por categoria, acesso por caso e perfis de revisão |

**Regra CT-02 já proposta na base de referência:** solicitante provável: Funcionário autorizado a investigar escopo definido; autoridade: INT-12 visão; Auditoria Geral fonte oficial; controle de acesso: Acesso por tenant, caso, classificação e finalidade, inclusive segregação interna; política de revisão/alçada a definir: Alçada de investigação/visão sensível segundo política; não presumir gestor com acesso total; impedimento adicional: Consultas também auditáveis, hipóteses separadas de fatos, sem manipular evidência. São propostas da CT-02, **não** definição definitiva de perfis ou ampliação de privilégios.

**Cenários HT relacionados (não executados):** HT-13/14/17. Revisar sem executar, com dados sintéticos e evidências específicas depois da etapa autorizada.

**Situação:** detalhamento operacional **proposto para revisão**; políticas, schemas e parâmetros não aprovados.


## 4. Controles transversais de pendência

| ID de revisão | Pergunta ainda a definir | Dono funcional a envolver | Resultado necessário antes de implementar |
|---|---|---|---|
| OP9-P01 | Quais papéis possuem permissão por recurso/campo, tenant e ato? | Identidade e Segurança, módulos proprietários, Central de Aprovações | Matriz CT-02 revisada e aprovada por OP |
| OP9-P02 | Quais tipos/enums, versões, formatos e erros a operação aceita? | INT-03/04/05/06/07/10/12 conforme OP | Contrato tipado específico, sem endpoint/execução livres |
| OP9-P03 | Quais estados e efeitos permanecem válidos durante suspensão, atualização ou falha? | INT-01/03/05/06/07 e proprietários | Transições, migrações, convivência e reconciliação contratadas |
| OP9-P04 | Quais garantias de deduplicação, retry e concorrência efetivamente existem? | Provedor, INT-04/06/07/10, Filas | Políticas verificáveis por ação sem exactly-once presumido |
| OP9-P05 | Qual confirmação oficial e retenção comprova resultado? | Módulo proprietário e Auditoria Geral | Fonte de verdade e evidência auditável com finalidade/retention correta |
| OP9-P06 | Como validar o escopo da decisão original? | Arquitetura INT e responsável pelas aprovações | Requisitos TR completos confrontados com texto original integral |
| OP9-P07 | Qual cenário HT prova o contrato negativo? | Arquitetura, Segurança e proprietários | Roteiros/documentação aceitos; execução somente na fase autorizada |

## 5. Situação de fechamento

- O **segundo lote de nove operações** está documentado **como proposta**; as **15 do primeiro lote** permanecem igualmente propostas, embora as OP24 e CT-01..11 tenham aceites anteriores como bases documentais.
- A soma das fichas de especialização é **24/24 OP de referência**, ainda **não** o catálogo exaustivo de todas as operações originais INT-01..12.
- Ainda não foram decididos schemas/autorizações definitivos por operação, valores de timeout/TTL/quotas/retries, migrações por provedor, assinatura/retention detalhados, alçadas, rastreabilidade integral ou aceite das fichas.
- **P0-CON-01, P0-TR-01, P0-DOC-01/02, P0-OWN-01, P0-SEC-01, P0-DEP-01, P0-VER-01, P0-ACC-01 e G01..G06 seguem abertos/parciais.**
- **HT-01..18:** 0 testes executados. **Nenhuma programação iniciada.**
