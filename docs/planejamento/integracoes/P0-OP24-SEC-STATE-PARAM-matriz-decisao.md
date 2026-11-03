# P0-OP24-SEC-STATE-PARAM — Autorizações, estados e parâmetros por operação

**Data:** 2026-11-03.
**Estado:** **MINUTA DOCUMENTAL PARA REVISÃO** elaborada após [aceite contextual das 24 fichas especializadas como bases documentais](P0-DEC-aprovacao-OP15-OP09-bases.md). A nova matriz **não foi aprovada individualmente**.
**Escopo:** 24 casos de referência OP-01..12, [OP15](P0-CON-OP15-contratos-operacoes-impacto-elevado.md) + [OP09](P0-CON-OP09-contratos-operacoes-complementares.md). **Não constitui catálogo completo de todas as operações originalmente aprovadas**; isso depende de P0-DOC-01/P0-TR-01.
**Fontes:** [CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md), [P0-SEC](P0-SEC-politicas-autorizacao-CT02.md), [CT-03/04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md), [P0-VER](P0-VER-matriz-compatibilidade-evolucao-versoes.md), [CON-MASTER](P0-CON-MASTER-matriz-CT01-CT11-criterios-fechamento.md), [INT-R01](INT-R01-governanca-contratos-homologacao.md) e [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md).
**Restrição:** nenhum código, automação nova, homologação executada, migração, credencial/conta de teste criada, alteração de ambiente real, contratação ou despesa.

## 1. Natureza desta matriz

**Decisões funcionais preservadas** são distinguíveis das **transições candidatas e parâmetros pendentes**. Os rótulos “proposta”, “apta”, “em processo”, “suspensa”, “confirmada” etc. descrevem **dimensões conceituais diferentes**, não um enum único do sistema. Nenhuma aprovação humanamente obrigatória adicional é criada por esta minuta.

Princípios que a revisão **não pode alterar**:

- Identidade e Segurança é fonte de autorização interna; INT-02 mantém escopos/autorizações externas; IN-151 mantém cofre; Central de Aprovações governa decisão humana quando exigida. **Não criar autorizador paralelo**.
- Solicitante, aprovador, executor técnico e confirmador oficial são distintos; módulo proprietário tem competência exclusiva para afirmar pagamento, contrato, agendamento, venda, lead ou outro efeito.
- CT-03/04 preservam comando lógico, tentativa, transporte, resultado técnico, **resultado oficial** e estados inconclusivos. HTTP 2xx, n8n ou webhook ≠ fato de negócio.
- Nenhum retry de escrita/compensação de efeito desconhecido ocorre sem prévia reconciliação e garantia suficiente; **não presumir exactly-once distribuído**.
- Um conector homologado não equivale a uma conexão autorizada. Atualizações de versão não podem mudar instância/comando/arquivo/cursor ativos silenciosamente.
- IA permanece consultiva ou técnica **dentro** de ferramentas escopadas e playbooks homologados; não decide novas alçadas por prompt.
- IN-118 mantém portabilidade e backup de autoatendimento em todos os planos, incluindo Básico. API pública segue indisponível no trial; integrações nativas elegíveis não são sua evasão.
- Auditoria Geral segue fonte de evidências, e Observabilidade IN-159/160 permanece serviço central distinto de resultado de negócio; IN-162 não converte prazo de log técnico em retenção universal.

## 2. Matriz das 24 operações e parâmetros a decidir

| OP | Ato | CT de referência | Dimensão de estado candidata | Campos/limites a decidir | HT existentes | Status de autorização |
|---|---|---|---|---|---|---|
| OP-01-CAD | Gestor autorizado da conexão no tenant; serviço interno escopado | INT-01 / Identidade e Segurança; conector INT-11, credenciais INT-02 | Conexão proposta → cadastrada/alterada; elegibilidade de uso avaliada separadamente de cadastro | Escopo conta/domínio/unidade; impacto de mudança de vínculo; versão e concorrência de cadastro; alçada por criticidade |  | Pendente de política específica |
| OP-01-CIC | Operador autorizado à gestão de conexão; intervenção técnica elegível | INT-01; efeitos pendentes pertencem aos módulos de negócio | Conexão ativa/elegível → restrita/suspensa/desativada; comandos já transmitidos têm estado próprio | Momento/alcance da suspensão, emergência, dispositivos e filas atingidos, reativação e alçada |  | Pendente de política específica |
| OP-02-AUT | Titular/delegado ou serviço habilitado a conduzir autorização | INT-02 e Identidade e Segurança; cofre central IN-151 | Autorização solicitada → concedida/limitada/inconclusiva; conexão permanece objeto distinto | Modelo de escopos/consentimento/delegação por provedor, renovação, validade, cofre, aprovações adicionais |  | Pendente de política específica |
| OP-02-REV | Titular/operador de segurança autorizado; contenção técnica homologada | INT-02/Identidade e Segurança; provedor confirma efeito remoto | Revogação local registrada → remota confirmada/pendente/inconclusiva; efeitos anteriores independentes | Alcance da revogação, política de bloqueio imediato, emergência, referência a filas e escopos compartilhados |  | Pendente de política específica |
| OP-03-VAL | Usuário com permissão para visualizar campos de origem/destino | INT-03; proprietário controla dados | Mapeamento candidato → prévia válida/com erros/incompatível; estado de dados oficiais NÃO alterado | Campos visíveis e mascarados, tipos/coerções admitidos, versão do esquema, tamanho de amostra e erro por campo |  | Pendente de política específica |
| OP-03-VER | Responsável autorizado à gestão de esquemas | INT-03 e módulos consumidores; políticas de homologação | Versão de mapeamento proposta → revisada/publicada/suspensa; consumidores ativos fixados na versão original | Matriz compatibilidade, transições de disponibilização, teste futuro, janela de convivência e alçada para incompatível |  | Pendente de política específica |
| OP-04-ENV | Identidade humana/técnica com delegação efetiva | INT-04 transmite; proprietário do efeito autoriza/atesta | Comando autorizado/preparado → transmitido/aceito/inconclusivo; resultado oficial separado | Schema de pedido, identidade lógica e da tentativa, garantias externas, concorrência e política de retry por efeito |  | Pendente de política específica |
| OP-04-RSL | Principal de serviço confiável e módulo proprietário | INT-04 para transporte; módulo proprietário para negócio | Evidência técnica recebida → verificada/contraditória/inconclusiva → fato oficial somente no proprietário | Prova da origem e autenticidade, taxonomia de estados/erros, versionamento de resultado e reconciliação |  | Pendente de política específica |
| OP-05-APP | Administrador/delegado autorizado do tenant e serviço técnico escopado | INT-05, INT-02 e Identidade e Segurança | Aplicativo proposto → cadastrado/restrito/revogado; acesso à API avaliado à parte | Fluxo de credenciamento, escopos por recurso, elegibilidade de planos, validade/rotação e aprovação de ampliação |  | Pendente de política específica |
| OP-05-API | Aplicação/usuário autenticado com delegação válida | INT-05 fronteira; proprietário confirma efeito | Pedido da API → negado/aceito tecnicamente/inconclusivo → fato do proprietário | Versão de endpoint/ação, quotas comerciais, limites de campos, erros, idempotência e direitos por recurso |  | Pendente de política específica |
| OP-06-IN | Provedor/assinatura identificados e validados no contexto de conexão | INT-06; motor de Eventos interno, proprietário de negócio | Evento externo recebido → autenticado/rejeitado → encaminhado/pendente; efeito de negócio separado | Algoritmo de assinatura, event_id e versão, janela anti-replay, fila/ack, retenção especializada e ordering |  | Pendente de política específica |
| OP-06-OUT | Serviço autorizado a notificar assinante habilitado | INT-06 entrega; COM para comunicações ao cliente | Entrega preparada → enviada/ack/pendente/inconclusiva; processamento no destino permanece desconhecido | Destino homologado, assinatura e versão, escopo de campos, taxa/timeout, retry e TTL por assinatura |  | Pendente de política específica |
| OP-07-SYNC | Serviço habilitado sob delegação de fonte/destino; operador autorizado | INT-07 coordena; proprietários gravam/confirmam | Alteração recebida → aplicada/confirmada/pedente-conflito; cursor só avança após confirmação | Direção/campo, tipo de cursor, tombstone, ordenação, concorrência e versões de mapeamento |  | Pendente de política específica |
| OP-07-RET | Serviço homologado com revalidação; operador de recuperação habilitado | INT-07; INT-10 para incidente; proprietários para dados | Sincronização pausada/inconclusiva → reconciliada/apta → retomada por checkpoint seguro | Política de retomada, lacunas da sequência, idempotência por mudança, tempo de checkpoint e revalidação |  | Pendente de política específica |
| OP-08-IMP | Usuário autorizado à portabilidade e gestores dos dados envolvidos | INT-08 com Central IN-118; módulos proprietários | Arquivo recebido/validado → prévia/aceite → lotes parciais/confirmados/rejeitados → encerramento com ressalva | Formatos/manifesto, antimalware, aprovação de sobrescrita, batch/concurrency, temporários, conflitos e retenção |  | Pendente de política específica |
| OP-08-EXP | Usuário/serviço autorizado a ler/exportar escopo do tenant | INT-08 e IN-118; proprietários dos dados; Arquivos central | Pedido de exportação → autorizado/negado → pacote preparado/disponível/expirado | Escopo por entidade/campo e finalidade, formato, classificação, retenção, link de acesso, volume e auditoria |  | Pendente de política específica |
| OP-09-INI | Operador/serviço autorizado pelo modelo/instância e pelo tenant | INT-09 coordena; cada dono decide efeito; motores centrais | Modelo elegível → instância versionada → etapas em progresso/pendentes → conclusão/reconciliação | Grafo/dependências, limites de ciclo, convergência, prazos por domínio, aprovações por efeito e estado parcial |  | Pendente de política específica |
| OP-09-COM | Responsável autorizado a solicitar reversão; executor competente | INT-09 coordena; FIN/CON/AGD/outro proprietário decidem e confirmam | Compensação proposta → elegibilidade/autoridade → comandada/inconclusiva → confirmada/parcial/negada | Matriz de compensabilidade por efeito, alçadas reais, irreversibilidade, versão, chaves idempotentes e provas |  | Pendente de política específica |
| OP-10-DIA | Identidade técnica/funcionário com acesso de diagnóstico autorizado | INT-10; Observabilidade IN-159/160; Central de Incidentes | Sinal observado/ausente → incidente investigado/causa hipótese ou confirmada | Frescor/qualidade de sinal, classificação de incidente, acesso minimizado, retenção de logs e correlação |  | Pendente de política específica |
| OP-10-REC | Agente/operador escopado com playbook homologado | INT-10; proprietário dos efeitos; Identidade e Segurança | Recuperação proposta → playbook elegível/autorizado → intervenção → verificação técnica | N1/N2/N3 por ato, limites seguros de ferramentas, retries e escopo, revisão humana, medição de saúde |  | Pendente de política específica |
| OP-11-HOM | Revisor e publicador autorizados; parceiro apenas submete quando permitido | INT-11; Segurança e Central de Aprovações | Conector versão candidata → avaliada/rejeitada/homologada → publicação controlada | Critérios de homologação, integridade, versão, homologação isolada, responsáveis e migração/compatibilidade |  | Pendente de política específica |
| OP-11-SUS | Operador de catálogo/segurança com autoridade específica | INT-11 catálogo; INT-01 conexões; INT-02 escopos | Versão publicada → restrita/suspensa/descontinuada; instâncias ativas preservadas como fatos distintos | Alcance da contenção, emergência, aviso, convivência, validação de instâncias e retrocompatibilidade |  | Pendente de política específica |
| OP-12-INV | Funcionário autorizado a investigar escopo definido | INT-12 visão; Auditoria Geral fonte oficial | Consulta solicitada → autorizada/negada → correlacionada com lacunas explícitas | Direitos por tenant/caso/campo, finalidade, proveniência, classificação de retenção e auditoria da consulta |  | Pendente de política específica |
| OP-12-AUD | Responsável com permissão específica de exportação | Auditoria Geral; INT-12 interface de consulta/exportação | Exportação solicitada → autorizada/negada → artefato disponibilizado/expirado com auditoria | Perfil de exportação, minimização, pacote seguro, integridade, expiração, retenção e trilha de acesso |  | Pendente de política específica |

**Estado de todas as 24 operações:** a base documental OP15/OP09 foi aceita; **as propostas específicas desta matriz continuam abertas**, inclusive papéis reais, enumeração de estados, transições, retentativas, limites e alçadas.

## 3. Fichas de avaliação contextual por operação

### OP-01-CAD — Gestor autorizado da conexão no tenant; serviço interno escopado

**CT e rastreabilidade:** INT-01 / Identidade e Segurança; conector INT-11, credenciais INT-02; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Gestor autorizado da conexão no tenant; serviço interno escopado |
| Autoridade por recurso/escopo | INT-01 / Identidade e Segurança; conector INT-11, credenciais INT-02; Permissão de administrar conexão do próprio tenant; conexão/conta/versão corretas |
| Alçada/revisão dependente do risco | Requer alçada de alteração conforme risco; não pressupor aprovação adicional universal |
| Dimensão de estado proposta | Conexão proposta → cadastrada/alterada; elegibilidade de uso avaliada separadamente de cadastro |
| Condição de restrição/negação | Divergência de conta/tenant, versão não homologada ou mudança concorrente: recusar ativação não válida |
| Parâmetros e decisões ainda abertos | Escopo conta/domínio/unidade; impacto de mudança de vínculo; versão e concorrência de cadastro; alçada por criticidade |
| Fonte de confirmação e limites | Cadastro INT-01; homologação INT-11; credenciais INT-02; fatos de negócio fora de CT-01 |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-01-CIC — Operador autorizado à gestão de conexão; intervenção técnica elegível

**CT e rastreabilidade:** INT-01; efeitos pendentes pertencem aos módulos de negócio; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Operador autorizado à gestão de conexão; intervenção técnica elegível |
| Autoridade por recurso/escopo | INT-01; efeitos pendentes pertencem aos módulos de negócio; Poder explícito de suspender/desativar a conexão e avaliar impacto de instâncias |
| Alçada/revisão dependente do risco | Aprovação conforme criticidade/efeitos; contenção segura segue política existente |
| Dimensão de estado proposta | Conexão ativa/elegível → restrita/suspensa/desativada; comandos já transmitidos têm estado próprio |
| Condição de restrição/negação | Revogação/suspensão bloqueia novos atos, mas comando pendente não vira rejeitado ou compensado |
| Parâmetros e decisões ainda abertos | Momento/alcance da suspensão, emergência, dispositivos e filas atingidos, reativação e alçada |
| Fonte de confirmação e limites | INT-01 confirma estado cadastral; INT-10 investiga pendências; proprietários confirmam efeitos |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-02-AUT — Titular/delegado ou serviço habilitado a conduzir autorização

**CT e rastreabilidade:** INT-02 e Identidade e Segurança; cofre central IN-151; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Titular/delegado ou serviço habilitado a conduzir autorização |
| Autoridade por recurso/escopo | INT-02 e Identidade e Segurança; cofre central IN-151; Escopo mínimo do provedor, finalidade e validade, com base legal/consentimento quando necessário |
| Alçada/revisão dependente do risco | Aprovações/consentimentos exigidos conforme tipo, sem inventar consentimento universal |
| Dimensão de estado proposta | Autorização solicitada → concedida/limitada/inconclusiva; conexão permanece objeto distinto |
| Condição de restrição/negação | Escopo ou consentimento externo inválido/expirado: não executar; não exibir segredo nem presumir aprovação remota |
| Parâmetros e decisões ainda abertos | Modelo de escopos/consentimento/delegação por provedor, renovação, validade, cofre, aprovações adicionais |
| Fonte de confirmação e limites | INT-02/Segurança e IN-151; provedor confirma escopo externo; proprietário valida cada ato protegido |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-02-REV — Titular/operador de segurança autorizado; contenção técnica homologada

**CT e rastreabilidade:** INT-02/Identidade e Segurança; provedor confirma efeito remoto; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Titular/operador de segurança autorizado; contenção técnica homologada |
| Autoridade por recurso/escopo | INT-02/Identidade e Segurança; provedor confirma efeito remoto; Permissão de revogação sobre a credencial e conta certas; escopo e impacto |
| Alçada/revisão dependente do risco | Revogação potencialmente disruptiva/critica exige alçada aplicável; contenção previamente autorizada pode ter tratamento próprio |
| Dimensão de estado proposta | Revogação local registrada → remota confirmada/pendente/inconclusiva; efeitos anteriores independentes |
| Condição de restrição/negação | Sem confirmação remota, declarar restrição local e incerteza remota, não concluir que provedor apagou credencial |
| Parâmetros e decisões ainda abertos | Alcance da revogação, política de bloqueio imediato, emergência, referência a filas e escopos compartilhados |
| Fonte de confirmação e limites | Segurança/INT-02 bloqueiam localmente; provedor confirma revogação; módulos proprietários preservam resultados |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-03-VAL — Usuário com permissão para visualizar campos de origem/destino

**CT e rastreabilidade:** INT-03; proprietário controla dados; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Usuário com permissão para visualizar campos de origem/destino |
| Autoridade por recurso/escopo | INT-03; proprietário controla dados; Leitura dos campos/classificações e ambiente autorizados; prévia não concede escrita |
| Alçada/revisão dependente do risco | Sem aprovação nova quando somente leitura autorizada; dados sensíveis podem exigir controle adicional |
| Dimensão de estado proposta | Mapeamento candidato → prévia válida/com erros/incompatível; estado de dados oficiais NÃO alterado |
| Condição de restrição/negação | Falha por campo ou amostra sensível impede publicação de versão válida sem correção, jamais escrita automática |
| Parâmetros e decisões ainda abertos | Campos visíveis e mascarados, tipos/coerções admitidos, versão do esquema, tamanho de amostra e erro por campo |
| Fonte de confirmação e limites | INT-03 confirma validade de prévia; proprietário mantém autoridade de leitura/gravação dos dados |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-03-VER — Responsável autorizado à gestão de esquemas

**CT e rastreabilidade:** INT-03 e módulos consumidores; políticas de homologação; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Responsável autorizado à gestão de esquemas |
| Autoridade por recurso/escopo | INT-03 e módulos consumidores; políticas de homologação; Direito de publicar versão, ambiente e conjunto de entidades permitidos |
| Alçada/revisão dependente do risco | Revisão/aprovação quando versão incompatível ou impacto significativo, conforme governança |
| Dimensão de estado proposta | Versão de mapeamento proposta → revisada/publicada/suspensa; consumidores ativos fixados na versão original |
| Condição de restrição/negação | Publicação de v2 não reescreve cursor, lote, instância nem invalida silenciosamente v1 |
| Parâmetros e decisões ainda abertos | Matriz compatibilidade, transições de disponibilização, teste futuro, janela de convivência e alçada para incompatível |
| Fonte de confirmação e limites | INT-03 publica versão; INT-07/08 e proprietários decidem adoção na operação/instância |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-04-ENV — Identidade humana/técnica com delegação efetiva

**CT e rastreabilidade:** INT-04 transmite; proprietário do efeito autoriza/atesta; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Identidade humana/técnica com delegação efetiva |
| Autoridade por recurso/escopo | INT-04 transmite; proprietário do efeito autoriza/atesta; Operação, entidade, recurso/campo e conexão autorizados; CT-03 válido |
| Alçada/revisão dependente do risco | Aprovação do ato de negócio na alçada competente quando exigida |
| Dimensão de estado proposta | Comando autorizado/preparado → transmitido/aceito/inconclusivo; resultado oficial separado |
| Condição de restrição/negação | Timeout após envio e autorização revogada vedam reenvio cego ou migração automática a outro provedor |
| Parâmetros e decisões ainda abertos | Schema de pedido, identidade lógica e da tentativa, garantias externas, concorrência e política de retry por efeito |
| Fonte de confirmação e limites | INT-04 transporta, CT-03 descreve comando; proprietário confirma eventual negócio; INT-10 reconcilia tecnicamente |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-04-RSL — Principal de serviço confiável e módulo proprietário

**CT e rastreabilidade:** INT-04 para transporte; módulo proprietário para negócio; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Principal de serviço confiável e módulo proprietário |
| Autoridade por recurso/escopo | INT-04 para transporte; módulo proprietário para negócio; Fonte autenticada, correlação e estado compatíveis, sem poder de inventar confirmação |
| Alçada/revisão dependente do risco | Sem nova aprovação para registrar fato técnico legítimo; retificação de negócio segue proprietário |
| Dimensão de estado proposta | Evidência técnica recebida → verificada/contraditória/inconclusiva → fato oficial somente no proprietário |
| Condição de restrição/negação | Retorno HTTP/webhook e evento tardio não certificam negócio; nem erro de transporte descarta efeito já aplicado |
| Parâmetros e decisões ainda abertos | Prova da origem e autenticidade, taxonomia de estados/erros, versionamento de resultado e reconciliação |
| Fonte de confirmação e limites | INT-04 registra transporte; proprietário confirma efeito; Auditoria Geral preserva a trilha |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-05-APP — Administrador/delegado autorizado do tenant e serviço técnico escopado

**CT e rastreabilidade:** INT-05, INT-02 e Identidade e Segurança; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Administrador/delegado autorizado do tenant e serviço técnico escopado |
| Autoridade por recurso/escopo | INT-05, INT-02 e Identidade e Segurança; Permissões de apps e escopos, elegibilidade comercial/tenant |
| Alçada/revisão dependente do risco | Aprovação para acesso ampliado ou ação crítica, quando política exigir; contratação depende de FIN/CON |
| Dimensão de estado proposta | Aplicativo proposto → cadastrado/restrito/revogado; acesso à API avaliado à parte |
| Condição de restrição/negação | Cadastro do app, token ou escopo não pode burlar bloqueio de API pública no trial |
| Parâmetros e decisões ainda abertos | Fluxo de credenciamento, escopos por recurso, elegibilidade de planos, validade/rotação e aprovação de ampliação |
| Fonte de confirmação e limites | INT-05 detém aplicativo; Segurança/INT-02 acesso; FIN/CON apenas se ato comercial exigível |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-05-API — Aplicação/usuário autenticado com delegação válida

**CT e rastreabilidade:** INT-05 fronteira; proprietário confirma efeito; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Aplicação/usuário autenticado com delegação válida |
| Autoridade por recurso/escopo | INT-05 fronteira; proprietário confirma efeito; API habilitada no plano, tenant, app, token e operação/objeto/fields autorizados |
| Alçada/revisão dependente do risco | Aprovação do efeito de negócio quando exigida, não apenas autenticação de API |
| Dimensão de estado proposta | Pedido da API → negado/aceito tecnicamente/inconclusivo → fato do proprietário |
| Condição de restrição/negação | App válido em tenant A não acessa B; trial não chama API pública, mas integrações nativas elegíveis continuam |
| Parâmetros e decisões ainda abertos | Versão de endpoint/ação, quotas comerciais, limites de campos, erros, idempotência e direitos por recurso |
| Fonte de confirmação e limites | INT-05 na fronteira, INT-04 no transporte; proprietário decide e confirma negócio |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-06-IN — Provedor/assinatura identificados e validados no contexto de conexão

**CT e rastreabilidade:** INT-06; motor de Eventos interno, proprietário de negócio; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Provedor/assinatura identificados e validados no contexto de conexão |
| Autoridade por recurso/escopo | INT-06; motor de Eventos interno, proprietário de negócio; Validação de origem/autenticidade, tenant, evento, versão, proteção anti-replay |
| Alçada/revisão dependente do risco | Nenhuma assinatura de webhook concede aprovação do ato de negócio |
| Dimensão de estado proposta | Evento externo recebido → autenticado/rejeitado → encaminhado/pendente; efeito de negócio separado |
| Condição de restrição/negação | Assinatura válida não dá poder de executar ato; replay ou ordem tardia não duplica efeitos |
| Parâmetros e decisões ainda abertos | Algoritmo de assinatura, event_id e versão, janela anti-replay, fila/ack, retenção especializada e ordering |
| Fonte de confirmação e limites | INT-06 autentica entrada; Motor Central de Eventos distribui; proprietário confirma efeito |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-06-OUT — Serviço autorizado a notificar assinante habilitado

**CT e rastreabilidade:** INT-06 entrega; COM para comunicações ao cliente; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Serviço autorizado a notificar assinante habilitado |
| Autoridade por recurso/escopo | INT-06 entrega; COM para comunicações ao cliente; Evento/assinante/destino/finalidade/escopo de campos e URL registrada autorizados |
| Alçada/revisão dependente do risco | Configuração de assinatura e entrega sujeitas a alçadas de mudanças sensíveis |
| Dimensão de estado proposta | Entrega preparada → enviada/ack/pendente/inconclusiva; processamento no destino permanece desconhecido |
| Condição de restrição/negação | URL arbitrária/SSRF ou assinatura revogada deve impedir envio; HTTP 2xx não prova negócio no destino |
| Parâmetros e decisões ainda abertos | Destino homologado, assinatura e versão, escopo de campos, taxa/timeout, retry e TTL por assinatura |
| Fonte de confirmação e limites | INT-06 entrega webhook; Eventos detém evento interno; COM comunica ao cliente quando aplicável |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-07-SYNC — Serviço habilitado sob delegação de fonte/destino; operador autorizado

**CT e rastreabilidade:** INT-07 coordena; proprietários gravam/confirmam; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Serviço habilitado sob delegação de fonte/destino; operador autorizado |
| Autoridade por recurso/escopo | INT-07 coordena; proprietários gravam/confirmam; Entidade/campos/direção/fonte/destino e conexão vigentes |
| Alçada/revisão dependente do risco | Conflitos ou alterações sensíveis exigem decisão do proprietário conforme política |
| Dimensão de estado proposta | Alteração recebida → aplicada/confirmada/pedente-conflito; cursor só avança após confirmação |
| Condição de restrição/negação | Não tratar ausência como exclusão nem avançar checkpoint com simples ack; conflito exige dono do campo |
| Parâmetros e decisões ainda abertos | Direção/campo, tipo de cursor, tombstone, ordenação, concorrência e versões de mapeamento |
| Fonte de confirmação e limites | INT-07 controla checkpoint; proprietário confirma dado; INT-03/04 fazem transformação/transporte |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-07-RET — Serviço homologado com revalidação; operador de recuperação habilitado

**CT e rastreabilidade:** INT-07; INT-10 para incidente; proprietários para dados; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Serviço homologado com revalidação; operador de recuperação habilitado |
| Autoridade por recurso/escopo | INT-07; INT-10 para incidente; proprietários para dados; Checkpoint íntegro, token/conexão/versão ainda válidos, efeitos não duplicáveis |
| Alçada/revisão dependente do risco | Retomada de risco aumenta alçada conforme política, sem dupla aprovação para mesma decisão |
| Dimensão de estado proposta | Sincronização pausada/inconclusiva → reconciliada/apta → retomada por checkpoint seguro |
| Condição de restrição/negação | Credencial revogada, cursor não comprovado ou versão incompatível impedem retomar escrita |
| Parâmetros e decisões ainda abertos | Política de retomada, lacunas da sequência, idempotência por mudança, tempo de checkpoint e revalidação |
| Fonte de confirmação e limites | INT-07 restabelece cursor; INT-10 investiga; proprietário atesta registros efetivos |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-08-IMP — Usuário autorizado à portabilidade e gestores dos dados envolvidos

**CT e rastreabilidade:** INT-08 com Central IN-118; módulos proprietários; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Usuário autorizado à portabilidade e gestores dos dados envolvidos |
| Autoridade por recurso/escopo | INT-08 com Central IN-118; módulos proprietários; Direitos na origem e destino, entidade/campo e arquivo; prévia/manifesto e separação entre tenants |
| Alçada/revisão dependente do risco | Confirmação/aceite para operação destrutiva ou conflito conforme política de proteção |
| Dimensão de estado proposta | Arquivo recebido/validado → prévia/aceite → lotes parciais/confirmados/rejeitados → encerramento com ressalva |
| Condição de restrição/negação | Sem mesclagem destrutiva, migração de segredos, escrita entre contas sem direitos ou sucesso total de lote parcial |
| Parâmetros e decisões ainda abertos | Formatos/manifesto, antimalware, aprovação de sobrescrita, batch/concurrency, temporários, conflitos e retenção |
| Fonte de confirmação e limites | INT-08 opera lote; IN-118 oferece portabilidade; Arquivos armazena; proprietário confirma cada registro |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-08-EXP — Usuário/serviço autorizado a ler/exportar escopo do tenant

**CT e rastreabilidade:** INT-08 e IN-118; proprietários dos dados; Arquivos central; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Usuário/serviço autorizado a ler/exportar escopo do tenant |
| Autoridade por recurso/escopo | INT-08 e IN-118; proprietários dos dados; Arquivos central; Escopo de consulta e exportação, campos sensíveis, finalidade e duração do link |
| Alçada/revisão dependente do risco | Exportação sensível/em massa pode exigir confirmação reforçada conforme política, sem revogar autoatendimento essencial |
| Dimensão de estado proposta | Pedido de exportação → autorizado/negado → pacote preparado/disponível/expirado |
| Condição de restrição/negação | Direito de leitura não implica exportar dados sensíveis; cota cheia de anexos não bloqueia portabilidade essencial |
| Parâmetros e decisões ainda abertos | Escopo por entidade/campo e finalidade, formato, classificação, retenção, link de acesso, volume e auditoria |
| Fonte de confirmação e limites | INT-08 delimita pacote; IN-118 responde por portabilidade; proprietário define dados e Arquivos protege artefato |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-09-INI — Operador/serviço autorizado pelo modelo/instância e pelo tenant

**CT e rastreabilidade:** INT-09 coordena; cada dono decide efeito; motores centrais; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Operador/serviço autorizado pelo modelo/instância e pelo tenant |
| Autoridade por recurso/escopo | INT-09 coordena; cada dono decide efeito; motores centrais; Modelo/versionamento, autorização de cada etapa, recurso, aprovação e dependências |
| Alçada/revisão dependente do risco | Aprovações específicas dos efeitos críticos, não aprovação universal pelo orquestrador |
| Dimensão de estado proposta | Modelo elegível → instância versionada → etapas em progresso/pendentes → conclusão/reconciliação |
| Condição de restrição/negação | Não avançar etapa por HTTP ou final de workflow; instância v1 não migra para modelo v2 automaticamente |
| Parâmetros e decisões ainda abertos | Grafo/dependências, limites de ciclo, convergência, prazos por domínio, aprovações por efeito e estado parcial |
| Fonte de confirmação e limites | INT-09 coordena; Eventos/Filas/Tarefas/Aprovações centrais; proprietários atestam cada negócio |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-09-COM — Responsável autorizado a solicitar reversão; executor competente

**CT e rastreabilidade:** INT-09 coordena; FIN/CON/AGD/outro proprietário decidem e confirmam; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Responsável autorizado a solicitar reversão; executor competente |
| Autoridade por recurso/escopo | INT-09 coordena; FIN/CON/AGD/outro proprietário decidem e confirmam; Elegibilidade de reversão, efeito original, risco, escopo e estado atual |
| Alçada/revisão dependente do risco | Aprovação específica do ato de compensação na alçada proprietária |
| Dimensão de estado proposta | Compensação proposta → elegibilidade/autoridade → comandada/inconclusiva → confirmada/parcial/negada |
| Condição de restrição/negação | Não compensar efeito irreversível, sem alçada, ou resultado original incerto; cancelamento técnico não é estorno |
| Parâmetros e decisões ainda abertos | Matriz de compensabilidade por efeito, alçadas reais, irreversibilidade, versão, chaves idempotentes e provas |
| Fonte de confirmação e limites | INT-09 coordena; FIN/CON/AGD ou proprietário decide/executa/confirma; Aprovações só quando exigidas |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-10-DIA — Identidade técnica/funcionário com acesso de diagnóstico autorizado

**CT e rastreabilidade:** INT-10; Observabilidade IN-159/160; Central de Incidentes; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Identidade técnica/funcionário com acesso de diagnóstico autorizado |
| Autoridade por recurso/escopo | INT-10; Observabilidade IN-159/160; Central de Incidentes; Acesso mínimo a telemetria por ambiente, serviço e tenant; dados minimizados |
| Alçada/revisão dependente do risco | Monitoramento de baixo risco dentro das permissões existentes; nenhuma aprovação de negócio implícita |
| Dimensão de estado proposta | Sinal observado/ausente → incidente investigado/causa hipótese ou confirmada |
| Condição de restrição/negação | Ausência de telemetria não equivale a normalização; investigação não permite recuperação automática crítica |
| Parâmetros e decisões ainda abertos | Frescor/qualidade de sinal, classificação de incidente, acesso minimizado, retenção de logs e correlação |
| Fonte de confirmação e limites | IN-159/160 Observabilidade e Incidentes centrais; INT-10 diagnóstico, proprietário para negócio |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-10-REC — Agente/operador escopado com playbook homologado

**CT e rastreabilidade:** INT-10; proprietário dos efeitos; Identidade e Segurança; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Agente/operador escopado com playbook homologado |
| Autoridade por recurso/escopo | INT-10; proprietário dos efeitos; Identidade e Segurança; Ação e ambiente específicos permitidos; limites e pré-condições do playbook |
| Alçada/revisão dependente do risco | N2 homologado/seguro pode seguir aprovação prévia; intervenção crítica requer aprovação humana específica quando exigida |
| Dimensão de estado proposta | Recuperação proposta → playbook elegível/autorizado → intervenção → verificação técnica |
| Condição de restrição/negação | Playbook expirado ou risco N3 sem alçada impede execução; saúde restabelecida não conclui comandos antigos |
| Parâmetros e decisões ainda abertos | N1/N2/N3 por ato, limites seguros de ferramentas, retries e escopo, revisão humana, medição de saúde |
| Fonte de confirmação e limites | INT-10 recuperação escopada; Segurança/Aprovações; Observabilidade comprova saúde, proprietário negócio |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-11-HOM — Revisor e publicador autorizados; parceiro apenas submete quando permitido

**CT e rastreabilidade:** INT-11; Segurança e Central de Aprovações; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Revisor e publicador autorizados; parceiro apenas submete quando permitido |
| Autoridade por recurso/escopo | INT-11; Segurança e Central de Aprovações; Permissão de homologar versão/artefato e ambiente; isolamento de terceiros |
| Alçada/revisão dependente do risco | Publicação controlada exige aprovação humana adequada; não herdar aceite da versão anterior |
| Dimensão de estado proposta | Conector versão candidata → avaliada/rejeitada/homologada → publicação controlada |
| Condição de restrição/negação | Versão publicada não concede conexão, escopo nem acesso ao CRM; sem código livre/autopublicação de IA |
| Parâmetros e decisões ainda abertos | Critérios de homologação, integridade, versão, homologação isolada, responsáveis e migração/compatibilidade |
| Fonte de confirmação e limites | INT-11 detém catálogo/versionamento; Segurança e Aprovações competentes |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-11-SUS — Operador de catálogo/segurança com autoridade específica

**CT e rastreabilidade:** INT-11 catálogo; INT-01 conexões; INT-02 escopos; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Operador de catálogo/segurança com autoridade específica |
| Autoridade por recurso/escopo | INT-11 catálogo; INT-01 conexões; INT-02 escopos; Direito de restrição por conector/versão e abrangência de clientes |
| Alçada/revisão dependente do risco | Aprovação conforme risco; contenção urgente homologada segue política técnica existente |
| Dimensão de estado proposta | Versão publicada → restrita/suspensa/descontinuada; instâncias ativas preservadas como fatos distintos |
| Condição de restrição/negação | Revogar conector não apaga comandos já transmitidos nem permite mudar versão de processos pendentes |
| Parâmetros e decisões ainda abertos | Alcance da contenção, emergência, aviso, convivência, validação de instâncias e retrocompatibilidade |
| Fonte de confirmação e limites | INT-11 suspende versão; INT-01/02 reavaliam conexões/credenciais; proprietários efeitos |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-12-INV — Funcionário autorizado a investigar escopo definido

**CT e rastreabilidade:** INT-12 visão; Auditoria Geral fonte oficial; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Funcionário autorizado a investigar escopo definido |
| Autoridade por recurso/escopo | INT-12 visão; Auditoria Geral fonte oficial; Acesso por tenant, caso, classificação e finalidade, inclusive segregação interna |
| Alçada/revisão dependente do risco | Alçada de investigação/visão sensível segundo política; não presumir gestor com acesso total |
| Dimensão de estado proposta | Consulta solicitada → autorizada/negada → correlacionada com lacunas explícitas |
| Condição de restrição/negação | Investigação sem prova não pode fabricar fato; hipótese de IA jamais substitui auditoria oficial |
| Parâmetros e decisões ainda abertos | Direitos por tenant/caso/campo, finalidade, proveniência, classificação de retenção e auditoria da consulta |
| Fonte de confirmação e limites | INT-12 consulta; Auditoria Geral detém evidências; proprietário confirma negócio |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**

### OP-12-AUD — Responsável com permissão específica de exportação

**CT e rastreabilidade:** Auditoria Geral; INT-12 interface de consulta/exportação; **TR de referência:** Autoridade por recurso/campo, fonte oficial e alçada efetiva ainda pendentes; **HT de referência:**  (**não executado**).

| Aspecto | Regra documental a confirmar |
|---|---|
| Solicitante candidato | Responsável com permissão específica de exportação |
| Autoridade por recurso/escopo | Auditoria Geral; INT-12 interface de consulta/exportação; Direito de acesso e exportação por escopo, registro, período, finalidade, retenção |
| Alçada/revisão dependente do risco | Confirmação reforçada e aprovação específica quando exigidas, sem download indiscriminado |
| Dimensão de estado proposta | Exportação solicitada → autorizada/negada → artefato disponibilizado/expirado com auditoria |
| Condição de restrição/negação | Consulta autorizada não é exportação autorizada; vedar cross-tenant e divulgação de segredos |
| Parâmetros e decisões ainda abertos | Perfil de exportação, minimização, pacote seguro, integridade, expiração, retenção e trilha de acesso |
| Fonte de confirmação e limites | Auditoria Geral fonte e acesso; INT-12 prepara exportação especializada quando autorizado |

**Regra de continuidade:** a decisão de acesso à operação **não altera efeitos já produzidos**; verificar identidade, tenant, finalidade, fonte do fato, versão e permissões nas fronteiras protegidas, preservando incertezas e trilha na Auditoria Geral. A autorização humana adicional só se aplica quando exigida pela política vigente e pela natureza do ato, evitando aprovação duplicada do mesmo ato.

**Aceite documental futuro:** confirmar o proprietário do ato, estado e exceções com as fontes originais, completar campos e limites autorizados, associar a prova/negação à HT pertinente, e registrar responsabilidade do aceite. **Não preencher resultado de teste, dados reais ou parâmetro numérico aqui.**


## 4. Categorias de parâmetros a decidir, sem inventar valores

| Classe de decisão | Onde se aplica | Regra para fechamento |
|---|---|---|
| SEC-24-A — Identidades e perfis | Todos os atos protegidos | Mapear papel/perfil real existente, tenant/recurso/campo e revogação; sem superusuário implícito |
| SEC-24-B — MFA e alçadas | Ações sensíveis segundo política | Decidir por ato, risco e proprietário; aprovação não se repete para mesma decisão válida |
| STA-24-A — Objeto/estado | Conexão, credencial, esquema, app, evento, cursor, lote, instância, incidente, conector, evidência | Definir em **dimensões separadas**, com fonte oficial e transições admitidas; não criar motor paralelo |
| STA-24-B — Operação transmitida | Comandos, webhooks, compensações e processos | Distinguir preparação, tentativa, ack, aplicação, confirmação oficial, parcialidade e inconclusão |
| PAR-24-A — Versão e compatibilidade | Conector, esquema, comando, instância, fluxo, app, cursor, arquivo | Nomear versões suportadas/convivência, impacto em pendências e regras de migração homologadas |
| PAR-24-B — Idempotência/concorrência | Escritas, lotes, sync, eventos e compensação | Contrato real por operação/provedor, sem presunção de exactly-once |
| PAR-24-C — Timeout/retries/backoff | Transporte, fila, incidente, sync e webhook | Só definir números após capacidade/risco e aprovação competente; não reexecutar escrita de estado desconhecido |
| PAR-24-D — Quota/volume/custo | API, anexos, exportações, lotes, filas, telemetria | Respeitar planos e regras específicas, dimensionar sem criar bloqueio indevido do autoatendimento |
| PAR-24-E — Retenção/privacidade | Arquivos, evidências, logs, webhooks e dados de leads | Aplicar norma por categoria; não transportar prazos de fila especial para toda integração |
| PAR-24-F — Recuperação/compensação | INT-09/10 e proprietários | Limitar playbook/efeito/reversibilidade/autoridade e confirmação; nunca rollback universal |

## 5. Plano documental de validação cruzada (sem testes executados)

- **S-01 — Escopo da aprovação:** conferir cada frase desta matriz com as fontes originais INT/IN e a CON-MASTER; registrar se é regra já aprovada, inferência ou proposta em aberto.
- **S-02 — Autoridade:** cada OP deve ter solicitante elegível, dono de autorização, executor e fonte de confirmação do efeito **sem sobreposição indevida**.
- **S-03 — Transições:** verificar que os estados por objeto, transporte e negócio não se contradizem e que suspensão/revogação preservam tentativas anteriores.
- **S-04 — Falha e retomada:** documentar retry/reconciliação e evidência de inconclusão por operação e provedor, sem configurar valores numéricos arbitrários.
- **S-05 — Compatibilidade:** referenciar P0-VER para não migrar versão de instância ativa sem decisão própria.
- **S-06 — Aceite e HT:** cada regra negativa tem ao menos um cenário HT-01..18 correspondente e função de revisão. **Nenhum HT foi executado**.
- **S-07 — Decisão:** obter aceite expresso do responsável para a matriz e suas revisões, **sem confundir aceite documental com esquema de implementação**.

## 6. Situação P0 e próxima decisão

- **24/24 fichas especializadas anteriores aceitas como bases documentais** em [P0-DEC](P0-DEC-aprovacao-OP15-OP09-bases.md).
- **24/24 registros desta matriz novos e propostos para revisão**, sem aprovação dos parâmetros, estados físicos ou alçadas.
- **P0-CON-01, P0-SEC-01, P0-OWN-01, P0-VER-01, P0-DEP-01, P0-TR-01, P0-DOC-01/02, P0-ACC-01, P0-XREF-01, P0-GIT-01 e G01–G06 continuam abertos/parciais.**
- Próxima decisão recomendada: aceitar este documento **como instrumento de decisão documental**, mantendo pendências reais em aberto, e continuar a matriz de proprietários/recursos/campos e parâmetros por operação/provedor antes de fechar P0-CON-01.
- **0/18 testes executados; nenhuma programação iniciada.**
