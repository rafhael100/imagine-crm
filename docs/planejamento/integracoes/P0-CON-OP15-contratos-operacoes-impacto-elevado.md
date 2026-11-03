# P0-CON-OP15 — Contratos documentais de 15 operações com impacto elevado

**Data:** 2026-11-03. **Estado atualizado:** **15 FICHAS ACEITAS COMO BASES DOCUMENTAIS** conforme [P0-DEC OP15/OP09](P0-DEC-aprovacao-OP15-OP09-bases.md). Isto **não aprova** schemas, alçadas, perfis, estados executáveis, políticas de provedor ou homologação; a [matriz nova de autorização/estados/parâmetros](P0-OP24-SEC-STATE-PARAM-matriz-decisao.md) segue **minuta para revisão**.
**Autorização da etapa:** [P0-DEC — CON-MASTER aceita como instrumento documental](P0-DEC-aprovacao-CON-MASTER-base.md); [P0-CON-MASTER](P0-CON-MASTER-matriz-CT01-CT11-criterios-fechamento.md) aceita como base de acompanhamento, sem aprovar condições técnicas pendentes.
**Fontes complementares:** [24 fichas OP24 aceitas como referência](P0-OP24-detalhamento-CT03-CT04-por-operacao.md), [CT-02 aceita como base](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md), [P0-SEC](P0-SEC-politicas-autorizacao-CT02.md), [CT-03/04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md), [CT-05..11](P0-CON-CT05-CT11-contratos-integracoes-minuta.md), [HT-01..18](P0-ACC-planos-homologacao-HT01-HT18.md).
**Escopo desta primeira rodada:** **15 das 24 operações de referência**, escolhidas por potencial impacto sobre **autorizações, dados, escrita externa, compensação, publicação e recuperação**. A classificação de prioridade é **de revisão documental**, não um novo nível N3 ou política de segurança.
**Proibição:** nenhuma programação, teste, automação, conexão real, pagamento, compensação, migração, alteração de infraestrutura/produção ou custo.

## 1. Regras de interpretação que não podem ser perdidas

1. As **11 famílias CT** foram aceitas como **bases documentais**, mas os direitos/roles, schemas, comandos, estados físicos, parâmetros de provedor e alçadas específicas continuam em revisão.
2. O **solicitante**, a **autoridade competente**, o **executor técnico** e o **confirmador oficial** podem ser atores distintos. Nenhuma INT de transporte/coordenação assume titularidade de FIN/CON/AGD/VEN/etc.
3. A autorização CT-02 considera tenant, principal, recurso/campo, finalidade, credencial/vínculo, versão, estado, aprovações específicas vigentes e política atual. **Revalidar contexto** não equivale a solicitar aprovação humana idêntica a cada retentativa.
4. CT-03 representa comando lógico tipado e suas tentativas, CT-04 representa resultados técnicos e oficiais separados. **HTTP 2xx, n8n executado ou webhook autenticado não confirmam efeito de negócio.**
5. Em resultado desconhecido após transmissão, **não** repetir escrita/estorno automaticamente nem presumir negação. Reconciliação é necessária com a fonte competente.
6. Versionamento de instância, conector, schema, política e conexão não muda silenciosamente operação em andamento. Retenção e limites de filas de leads não são política universal de todas as integrações.
7. INT-08 não absorve IN-118: portabilidade básica em todos os planos, inclusive Básico; franquia de anexos cheia não bloqueia exportação de dados existentes; troca técnica de vínculo Sites→CRM não migra histórico do cliente.
8. IA só atua dentro de identidade/escopo e playbook técnicos homologados; ação crítica ou compensação de negócio depende da autoridade competente. Auditoria Geral é a fonte oficial de evidências.

## 2. Índice de 15 operações priorizadas

| Grupo | OP de referência | Natureza | Famílias CT originalmente associadas | HT inicialmente vinculados | Estado |
|---|---|---|---|---|---|
| 01 | OP-01-CIC | Suspender/desativar conexão | CT-01/02/04/09/11 | HT-18/08 | Em proposta |
| 02 | OP-02-AUT | Vincular ou renovar autorização externa | CT-01/02/04/11 | HT-01/02 | Em proposta |
| 03 | OP-02-REV | Revogar autorização ou credencial | CT-02/04/09/11 | HT-02/13 | Em proposta |
| 04 | OP-04-ENV | Executar comando externo tipado | CT-01/02/03/04/09/11 | HT-08/12 | Em proposta |
| 05 | OP-04-RSL | Registrar resultado técnico e encaminhar confirmação | CT-03/04/09/11 | HT-08/14 | Em proposta |
| 06 | OP-06-IN | Receber e validar webhook externo | CT-02/04/05/09/11 | HT-05/16 | Em proposta |
| 07 | OP-07-RET | Retomar sincronização interrompida | CT-02/04/06/09 | HT-06/16 | Em proposta |
| 08 | OP-08-IMP | Importar ou migrar conjunto delimitado | CT-02/03/04/07/09/11 | HT-07/13 | Em proposta |
| 09 | OP-08-EXP | Exportar dados autorizados ou produzir pacote delimitado | CT-02/04/07/11 | HT-07/17 | Em proposta |
| 10 | OP-09-INI | Instanciar e executar processo multissistema | CT-02/03/04/08/09/11 | HT-08/09 | Em proposta |
| 11 | OP-09-COM | Solicitar e acompanhar compensação tipada | CT-02/03/04/08/09/10/11 | HT-10/15 | Em proposta |
| 12 | OP-10-REC | Aplicar recuperação técnica homologada | CT-02/03/04/09/11 | HT-08/11 | Em proposta |
| 13 | OP-11-HOM | Homologar e publicar versão de conector | CT-01/02/04/11 | HT-01/12 | Em proposta |
| 14 | OP-11-SUS | Suspender/revogar/descontinuar versão homologada | CT-01/02/04/09/11 | HT-12/18 | Em proposta |
| 15 | OP-12-AUD | Exportar evidência de auditoria sob controle | CT-02/04/11 | HT-14/17 | Em proposta |

**Nota metodológica:** os vínculos CT/TR/HT usados em cada ficha foram extraídos das fichas OP24 aprovadas **como referência de trabalho**, sem reivindicar origem literal exaustiva. O bloco de autorização reproduz categorias de CT-02 como propostas, não políticas executáveis. Não se criaram IDs OP ou HT novos.

## 3. Fichas operacionais propostas — identidade, autorização, resultado e evidência

### OP-01-CIC — Suspender/desativar conexão

**Importância para revisão:** Mudança que pode impedir operações de vários usuários ou manter efeitos pendentes

**Autoridades e fronteiras:** INT-01 registra e confirma o estado cadastral; INT-10 diagnostica; proprietários mantêm efeitos já produzidos.

**Referências existentes, ainda não exaustivas:** contratos CT-01/02/04/09/11; requisitos TR-INT01-002..004; cenários HT-18/08 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant, conexão, versão/estado esperado, motivo, alcance, identidade solicitante, referência de instâncias e fila impactadas |
| Quem pode solicitar e autorizar | Permissão de suspensão no tenant/conta corretos; avaliar alcance por empresa/unidade; aprovação adicional somente se política de risco aplicável |
| Precondição, ação tipada e limite do executor | Analisar dependências, restringir novos usos elegíveis da conexão e registrar decisão; não aplicar cancelamento ao negócio por efeito da suspensão |
| Resultado e fonte oficial | Estado e alcance confirmados pela INT-01; lista de comandos já enviados e respostas oficiais permanece independente |
| Falha, abuso ou condição negativa | Falha ao persistir suspensão, operação enviada entre decisão e atualização, evento tardio ou destino antigo de lead |
| Reconciliação e retomada autorizada | Impedir uso novo quando a contenção for válida; reconciliar conexão/filas e efeitos por fonte oficial, sem reemitir comandos desconhecidos |
| Decisões de implementação ainda faltantes | Controle de concorrência e janela de efetividade, política de emergência, alçada, notificação, autorização em situações multi-site |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Operador autorizado à gestão de conexão; intervenção técnica elegível; competência: INT-01; efeitos pendentes pertencem aos módulos de negócio; avaliação/escopo: Poder explícito de suspender/desativar a conexão e avaliar impacto de instâncias; alçada candidata: Aprovação conforme criticidade/efeitos; contenção segura segue política existente. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-08/18 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-02-AUT — Vincular ou renovar autorização externa

**Importância para revisão:** Concessão ou alteração de escopos com potencial acesso a recursos de provedores

**Autoridades e fronteiras:** INT-02 detém vínculo/autorização externa, Identidade e Segurança identidade interna, IN-151 guarda segredo, provedor confirma escopo concedido.

**Referências existentes, ainda não exaustivas:** contratos CT-01/02/04/11; requisitos TR-INT02-001..004; cenários HT-01/02 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant, conexão, provedor, conta, escopos mínimos, finalidade, titular/delegação, versão e referência segura de credencial |
| Quem pode solicitar e autorizar | Consentimento/base legal quando exigíveis; identidade e direito de vincular, sem implicar aprovação universal nem impor consentimento indevido |
| Precondição, ação tipada e limite do executor | Solicitar/renovar escopo específico pelo fluxo homologado e guardar somente referência segura; operação protegida posterior requer autorização própria |
| Resultado e fonte oficial | Escopo/validade/provedor confirmados; INT-02 registra referência sem expor segredo |
| Falha, abuso ou condição negativa | Token obtido com escopo amplo, autorização expirada, divergência de conta, resultado remoto inconclusivo |
| Reconciliação e retomada autorizada | Negar novo uso sem escopo comprovado; verificar provedor/cofre e corrigir por fluxo legítimo, sem imprimir segredo |
| Decisões de implementação ainda faltantes | Vocabulário de escopo real por provedor, consentimento/delegação, TTL/renovação, alçadas e auditoria granular |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Titular/delegado ou serviço habilitado a conduzir autorização; competência: INT-02 e Identidade e Segurança; cofre central IN-151; avaliação/escopo: Escopo mínimo do provedor, finalidade e validade, com base legal/consentimento quando necessário; alçada candidata: Aprovações/consentimentos exigidos conforme tipo, sem inventar consentimento universal. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-01/02 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-02-REV — Revogar autorização ou credencial

**Importância para revisão:** Interrompe capacidade de novas operações com possível impacto em processos assíncronos

**Autoridades e fronteiras:** INT-02/Segurança detêm bloqueio local; provedor confirma revogação remota; proprietários confirmam efeitos existentes.

**Referências existentes, ainda não exaustivas:** contratos CT-02/04/09/11; requisitos TR-INT02-002..004; cenários HT-02/13 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant, provedor, conexão, identificação segura da credencial e escopos, ator, justificativa, vínculos/instâncias ativos |
| Quem pode solicitar e autorizar | Ator autorizado sobre credencial e tenant exatos, política de emergência e aprovação para operação disruptiva quando cabível |
| Precondição, ação tipada e limite do executor | Registrar intenção e alcance da revogação, restringir usos novos e acionar fluxo seguro do provedor; guardar estados local/remoto separadamente |
| Resultado e fonte oficial | Decisão local registrada e evidência de confirmação remota quando disponível; comando já enviado continua aguardando resultado próprio |
| Falha, abuso ou condição negativa | Provedor indisponível, revogação remota inconclusiva, credencial compartilhada legitimamente por múltiplos recursos, execução em fila |
| Reconciliação e retomada autorizada | Impedir despacho protegido local, classificar remoto como incerto e consultar fonte; não presumir reversão de atos anteriores |
| Decisões de implementação ainda faltantes | Procedimento por provedor, alcance da revogação, credenciais técnicas, alçada emergencial, política de atualização de sessões |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Titular/operador de segurança autorizado; contenção técnica homologada; competência: INT-02/Identidade e Segurança; provedor confirma efeito remoto; avaliação/escopo: Permissão de revogação sobre a credencial e conta certas; escopo e impacto; alçada candidata: Revogação potencialmente disruptiva/critica exige alçada aplicável; contenção previamente autorizada pode ter tratamento próprio. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-02/13 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-04-ENV — Executar comando externo tipado

**Importância para revisão:** Pode causar escrita ou efeito financeiro, contratual, operacional ou cadastral

**Autoridades e fronteiras:** INT-04 transmite; módulo proprietário é a autoridade sobre intenção e efeito de negócio; INT-09 apenas coordena se houver processo.

**Referências existentes, ainda não exaustivas:** contratos CT-01/02/03/04/09/11; requisitos TR-INT04-001..004; cenários HT-08/12 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant, operação tipada, recurso/entidade/campos autorizados, conta, conexão, versão de contrato, command_ref lógico, attempt_ref, precondição e estado esperado |
| Quem pode solicitar e autorizar | CT-02 atual e aprovação específica do ato quando exigida; conector/versão/capacidade homologados, destino certo, dono competente |
| Precondição, ação tipada e limite do executor | Preparar comando imutável de intenção lógica; verificar idempotência contextual e estado/versão antes de transmissão; cada tentativa mantém relação causal |
| Resultado e fonte oficial | INT-04 atesta envio/aceitação técnica; **somente proprietário** confirma pagamento, contrato, lead, reserva ou outro efeito |
| Falha, abuso ou condição negativa | Timeout após envio, confirmação HTTP sem negócio, falha de fila/ack, concorrência de pedidos e revogação após enfileirar |
| Reconciliação e retomada autorizada | Não reenviar escrita inconclusiva por padrão; usar consulta homologada e reconciliação da fonte, preservando pedido lógico e tentativas |
| Decisões de implementação ainda faltantes | Schema/erro/precondição por operação e provedor, chave de idempotência contextual, retries e concorrência numéricos, autorizações por efeito |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Identidade humana/técnica com delegação efetiva; competência: INT-04 transmite; proprietário do efeito autoriza/atesta; avaliação/escopo: Operação, entidade, recurso/campo e conexão autorizados; CT-03 válido; alçada candidata: Aprovação do ato de negócio na alçada competente quando exigida. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-08/13 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-04-RSL — Registrar resultado técnico e encaminhar confirmação

**Importância para revisão:** Pode alterar percepção de conclusão de negócio sem ter sido realmente confirmado

**Autoridades e fronteiras:** INT-04 detém evidência técnica; módulo proprietário valida/atesta fato oficial; Auditoria Geral preserva proveniência.

**Referências existentes, ainda não exaustivas:** contratos CT-03/04/09/11; requisitos TR-INT04-002/003; cenários HT-08/14 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | command_ref, attempt_ref, versão, fonte autenticada, tipo de retorno, correlation_ref, fato técnico e eventuais comprovantes externos |
| Quem pode solicitar e autorizar | Permissão do principal de serviço, origem/correlação verificáveis e limite de autoridade para não escrever negócio alheio |
| Precondição, ação tipada e limite do executor | Registrar retorno técnico com tipo e incerteza, encaminhar ao proprietário, sem promover HTTP 2xx a conclusão oficial |
| Resultado e fonte oficial | Separar recebido/aceito/aplicado tecnicamente, confirmado oficialmente, rejeitado, parcial e inconclusivo de acordo com contrato |
| Falha, abuso ou condição negativa | Webhook tardio, retorno contraditório, duplicata, assinatura inválida, versão de resposta diferente |
| Reconciliação e retomada autorizada | Confrontar fontes e comando original, manter incerteza até decisão proprietária, versionar retificação sem apagar trilha |
| Decisões de implementação ainda faltantes | Taxonomia física de resultados, evento de correlação, fonte oficial por efeito, prova de integridade e tratamento de conflitos |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Principal de serviço confiável e módulo proprietário; competência: INT-04 para transporte; módulo proprietário para negócio; avaliação/escopo: Fonte autenticada, correlação e estado compatíveis, sem poder de inventar confirmação; alçada candidata: Sem nova aprovação para registrar fato técnico legítimo; retificação de negócio segue proprietário. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-08/14 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-06-IN — Receber e validar webhook externo

**Importância para revisão:** Evento assinado pode iniciar fluxos internos e efeitos indevidos se interpretado como autorização

**Autoridades e fronteiras:** INT-06 governa recepção externa, Motor Central de Eventos publica internos, proprietários governam fatos e atos.

**Referências existentes, ainda não exaustivas:** contratos CT-02/04/05/09/11; requisitos TR-INT06-001..004; cenários HT-05/16 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | conexão/assinatura, origem, event_ref, versão, payload referenciado seguro, autenticidade, tenant e causation/correlation_ref |
| Quem pode solicitar e autorizar | Assinatura/origem válida, escopo de provedor, anti-replay e tenant vinculado; **assinatura não autoriza efeito de negócio** |
| Precondição, ação tipada e limite do executor | Receber, verificar autenticidade/versão e durabilidade aplicável, deduplicar pelo identificador lógico, transmitir evento ao Motor Central somente quando elegível |
| Resultado e fonte oficial | Recebimento/encaminhamento registrados pela INT-06; fato resultante somente pelo proprietário após autorização |
| Falha, abuso ou condição negativa | Replay, evento duplicado ou tardio, ordem invertida, payload com dados de outro tenant, falha de fila após aceite técnico |
| Reconciliação e retomada autorizada | Manter mesma identidade lógica, recuperar encaminhamento sem duplicar efeito e classificar desconhecido quando origem não for verificável |
| Decisões de implementação ainda faltantes | Algoritmos de assinatura por provedor, janela anti-replay, versões/event schemas, contratos de fila, reentregas e retenção por origem |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Provedor/assinatura identificados e validados no contexto de conexão; competência: INT-06; motor de Eventos interno, proprietário de negócio; avaliação/escopo: Validação de origem/autenticidade, tenant, evento, versão, proteção anti-replay; alçada candidata: Nenhuma assinatura de webhook concede aprovação do ato de negócio. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-05/16 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-07-RET — Retomar sincronização interrompida

**Importância para revisão:** Pode repetir ou suprimir escrita e comprometer autoridade sobre campos

**Autoridades e fronteiras:** INT-07 detém cursor/checkpoint; INT-10 diagnostica; proprietários atestam registros persistidos.

**Referências existentes, ainda não exaustivas:** contratos CT-02/04/06/09; requisitos TR-INT07-002..004; cenários HT-06/16 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant, fonte/destino, entidade/campos/direção, versão de mapeamento, checkpoint/cursor, identidade da mudança e últimas confirmações |
| Quem pode solicitar e autorizar | Revalidar identidade, credencial, conexão/versão e autoridade por campo; verificar alçada para recuperação de risco quando aplicável |
| Precondição, ação tipada e limite do executor | Analisar checkpoint durável e últimas alterações confirmadas, retomar em contrato/versão compatível, sem avançar posição por mero ACK |
| Resultado e fonte oficial | INT-07 atesta progresso confiável do cursor; proprietário confirma cada escrita; incidentes separados |
| Falha, abuso ou condição negativa | Cursor divergente, versão incompatível, credencial revogada, escrita confirmada sem ack, exclusão/conflito de duas fontes |
| Reconciliação e retomada autorizada | Reconciliar alteração por alteração com fonte de verdade; impedir avanço/retry de escrita desconhecida e preservar itens pendentes |
| Decisões de implementação ainda faltantes | Semântica de cursor por provedor, estratégia de conflito e tombstone, granularidade de checkpoint, ordem, limites de retentativa |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Serviço homologado com revalidação; operador de recuperação habilitado; competência: INT-07; INT-10 para incidente; proprietários para dados; avaliação/escopo: Checkpoint íntegro, token/conexão/versão ainda válidos, efeitos não duplicáveis; alçada candidata: Retomada de risco aumenta alçada conforme política, sem dupla aprovação para mesma decisão. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-06/16 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-08-IMP — Importar ou migrar conjunto delimitado

**Importância para revisão:** Pode escrever/sobrescrever registros em massa ou cruzar contas/tenants

**Autoridades e fronteiras:** INT-08 administra operação/lote, IN-118 é Central de Portabilidade, Arquivos detém arquivo, proprietário persiste/atesta dado.

**Referências existentes, ainda não exaustivas:** contratos CT-02/03/04/07/09/11; requisitos TR-INT08-001..005; cenários HT-07/13 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant e contas de origem/destino, finalidade, manifesto e arquivo, versão/formato, mapeamento INT-03, previsualização, lote/registro e escopo |
| Quem pode solicitar e autorizar | Usuário autorizado e direitos suficientes na origem/destino; políticas de proteção de dados; aprovação específica para alteração destrutiva quando exigida |
| Precondição, ação tipada e limite do executor | Validar, simular/pré-visualizar **sem escrita**, confrontar conflitos, aplicar lotes aprovados e registrar id lógico por registro; preservar dados já confirmados |
| Resultado e fonte oficial | INT-08 informa resultado por lote; proprietário comprova cada registro persistido; não marcar sucesso global por processamento parcial |
| Falha, abuso ou condição negativa | Formato inválido, dado sensível, colisão, arquivo malicioso, timeout após persistência, tentativa de mesclagem entre contas |
| Reconciliação e retomada autorizada | Quarentenar/rejeitar conforme política existente; reconciliar por registro, não repetir lote inteiro nem migrar segredo; preservar retorno de erro |
| Decisões de implementação ainda faltantes | Esquemas/formatos/antimalware de fluxo, estados por lote, aprovação de sobrescrita, limites por plano/conta, retenção de temporários |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Usuário autorizado à portabilidade e gestores dos dados envolvidos; competência: INT-08 com Central IN-118; módulos proprietários; avaliação/escopo: Direitos na origem e destino, entidade/campo e arquivo; prévia/manifesto e separação entre tenants; alçada candidata: Confirmação/aceite para operação destrutiva ou conflito conforme política de proteção. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-07/13 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-08-EXP — Exportar dados autorizados ou produzir pacote delimitado

**Importância para revisão:** Pode expor dados pessoais ou sigilosos em volume e afetar direitos de portabilidade

**Autoridades e fronteiras:** INT-08 gera pacote delimitado, IN-118 mantém autoatendimento, Arquivos protege artefato, donos governam dados.

**Referências existentes, ainda não exaustivas:** contratos CT-02/04/07/11; requisitos TR-INT08-001/004/005; cenários HT-07/17 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant, principal, finalidade, entidades/campos/intervalo, formato, classificação, versão de manifesto e destinação autorizada |
| Quem pode solicitar e autorizar | Direito de exportar além de visualizar quando requerido; minimização e segregação, sem negar exportação essencial do Básico |
| Precondição, ação tipada e limite do executor | Selecionar dados permitidos, produzir manifesto com escopo/versão, armazenar e disponibilizar com acesso seguro e expiração conforme política |
| Resultado e fonte oficial | Pacote íntegro e sua extração auditada; não transfere vínculo de site nem histórico para outra conta automaticamente |
| Falha, abuso ou condição negativa | Campo sensível não autorizado, link expirado, exportação parcial, cota de anexos 100%, acesso cross-tenant |
| Reconciliação e retomada autorizada | Negar/limitar apenas o indevido, preservar portabilidade autorizada e explicar lacunas/integridade sem fornecer dado alheio |
| Decisões de implementação ainda faltantes | Política de link/download, retenção do pacote, volumes, formato por entidade, alçada para exportação sensível |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Usuário/serviço autorizado a ler/exportar escopo do tenant; competência: INT-08 e IN-118; proprietários dos dados; Arquivos central; avaliação/escopo: Escopo de consulta e exportação, campos sensíveis, finalidade e duração do link; alçada candidata: Exportação sensível/em massa pode exigir confirmação reforçada conforme política, sem revogar autoatendimento essencial. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-07/17 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-09-INI — Instanciar e executar processo multissistema

**Importância para revisão:** Aciona efeitos em vários módulos e pode aparentar conclusão apesar de pendências

**Autoridades e fronteiras:** INT-09 mantém modelo/instância/etapas; cada módulo mantém autoridade sobre seu negócio; eventos/filas/tarefas/aprovações são centrais.

**Referências existentes, ainda não exaustivas:** contratos CT-02/03/04/08/09/11; requisitos TR-INT09-001..004; cenários HT-08/09 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant, modelo/versão fixada, instância, etapas, dependências, comandos por proprietário, causation/correlation_ref e aprovadores |
| Quem pode solicitar e autorizar | Modelo e etapa elegíveis, conexão/versão, autorização por efeito, precondições, dependências e alçada aplicável |
| Precondição, ação tipada e limite do executor | Criar instância versionada, avançar somente etapas elegíveis, distinguir coordenação de negócio, preservar decisões humanas sem duplicá-las |
| Resultado e fonte oficial | INT-09 confirma estado de coordenação; cada proprietário confirma seu efeito; fim técnico do workflow não encerra negócio pendente |
| Falha, abuso ou condição negativa | Ramos paralelos, dependência inconclusiva, evento tardio, loop, modelo alterado com instância ativa, falha de orquestrador |
| Reconciliação e retomada autorizada | Reconstituir etapas por IDs e evidências oficiais, suspender ramos dependentes e retomar ato seguro, sem rollback global |
| Decisões de implementação ainda faltantes | Grafo/versionamento, limites de ciclos, enum por etapa/instância, alçada por ato, critérios de convergência e contingência |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Operador/serviço autorizado pelo modelo/instância e pelo tenant; competência: INT-09 coordena; cada dono decide efeito; motores centrais; avaliação/escopo: Modelo/versionamento, autorização de cada etapa, recurso, aprovação e dependências; alçada candidata: Aprovações específicas dos efeitos críticos, não aprovação universal pelo orquestrador. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-09/15 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-09-COM — Solicitar e acompanhar compensação tipada

**Importância para revisão:** Reversão/estorno/cancelamento pode ser irreversível ou ter consequências financeiras e contratuais

**Autoridades e fronteiras:** INT-09 coordena pedido; FIN/CON/AGD ou outro proprietário decide, executa e atesta compensação.

**Referências existentes, ainda não exaustivas:** contratos CT-02/03/04/08/09/10/11; requisitos TR-INT09-004..006; cenários HT-10/15 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | efeito original e confirmação, módulo dono, elegibilidade/reversibilidade, estado atual, tipo de compensação, autorização, idempotency_ref próprio |
| Quem pode solicitar e autorizar | Autoridade e alçada **do ato compensatório**; decisão original não autoriza reversão universal; IA não executa sozinha |
| Precondição, ação tipada e limite do executor | Distinguir proposta, elegibilidade, aprovação, comando tipado, tentativa, resultado técnico e atestado oficial da compensação |
| Resultado e fonte oficial | Compensação confirmada só pelo proprietário; parcial/irreversível/inconclusiva preservadas com efeitos remanescentes |
| Falha, abuso ou condição negativa | Pagamento já compensado, efeito irreversível, aprovação ausente, falha após estorno, duas tentativas concorrentes |
| Reconciliação e retomada autorizada | Reconciliar efeito original e reversão separadamente; bloquear nova compensação incerta; registrar saldo/obrigações remanescentes |
| Decisões de implementação ainda faltantes | Mapa por efeito/tipo, competência financeira/contratual, alçada, regras de reversão, estado físico e reexecução segura |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Responsável autorizado a solicitar reversão; executor competente; competência: INT-09 coordena; FIN/CON/AGD/outro proprietário decidem e confirmam; avaliação/escopo: Elegibilidade de reversão, efeito original, risco, escopo e estado atual; alçada candidata: Aprovação específica do ato de compensação na alçada proprietária. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-10/15 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-10-REC — Aplicar recuperação técnica homologada

**Importância para revisão:** Automação técnica pode se transformar indevidamente em execução crítica de negócio

**Autoridades e fronteiras:** INT-10 executa diagnóstico/recuperação técnica escopada; Observabilidade e Incidentes centrais; proprietários validam efeitos.

**Referências existentes, ainda não exaustivas:** contratos CT-02/03/04/09/11; requisitos TR-INT10-002..005; cenários HT-08/11 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | incidente, componente/tenant/ambiente, playbook/versão, executor técnico, pré-condição/estado alvo, tentativas e risco |
| Quem pode solicitar e autorizar | Playbook homologado e autorização do executor, limites de N2; N3 crítico exige decisão humana quando política aplicável |
| Precondição, ação tipada e limite do executor | Verificar causa e condições, executar ação técnica restrita, verificar saúde com fonte observável; operação de negócio pendente continua pendente |
| Resultado e fonte oficial | INT-10/Observabilidade comprovam saúde técnica; proprietário confirma efeito de dados/contrato/pagamento |
| Falha, abuso ou condição negativa | Telemetria ausente, playbook vencido, acesso revogado, nova execução duplicativa, recuperação com efeitos laterais |
| Reconciliação e retomada autorizada | Interromper ação de risco, reconciliar operações potencialmente aplicadas, escalar proprietário e registrar incidente/evidência |
| Decisões de implementação ainda faltantes | Classificação de criticidade, limiar de sinais, timeout, número de tentativas, permissões de ferramenta e alçadas por playbook |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Agente/operador escopado com playbook homologado; competência: INT-10; proprietário dos efeitos; Identidade e Segurança; avaliação/escopo: Ação e ambiente específicos permitidos; limites e pré-condições do playbook; alçada candidata: N2 homologado/seguro pode seguir aprovação prévia; intervenção crítica requer aprovação humana específica quando exigida. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-08/11 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-11-HOM — Homologar e publicar versão de conector

**Importância para revisão:** Publicação pode habilitar capacidades de terceiros ou afetar muitas conexões

**Autoridades e fronteiras:** INT-11 publica catálogo e versão; Segurança/Aprovações aplicáveis; INT-01 não concede conexão só pela homologação.

**Referências existentes, ainda não exaustivas:** contratos CT-01/02/04/11; requisitos TR-INT11-001..003; cenários HT-01/12 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | artefato/versionamento/proveniência, capacidades, dependências, relatório de risco/testes isolados, revisor e decisão |
| Quem pode solicitar e autorizar | Permissão de homologar/publicar versão e aprovação humana aplicável; sem código livre e sem autopublicação por IA |
| Precondição, ação tipada e limite do executor | Verificar integridade, compatibilidade e riscos; registrar aceite do artefato e publicar versão elegível sem habilitar todas conexões |
| Resultado e fonte oficial | INT-11 confirma publicação da **versão exata**; tenant só usa após CT-01/02 e operação tipada |
| Falha, abuso ou condição negativa | Artefato não homologado, assinatura inválida, conector malicioso, incompatibilidade com consumidores ativos |
| Reconciliação e retomada autorizada | Reprovar/suspender distribuição, preservar versões antigas e evidências, sem ativar código ou migrar clientes silenciosamente |
| Decisões de implementação ainda faltantes | Catálogo de critérios e aprovadores, sandbox futuro, classes de riscos, critérios de coexistência/revogação |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Revisor e publicador autorizados; parceiro apenas submete quando permitido; competência: INT-11; Segurança e Central de Aprovações; avaliação/escopo: Permissão de homologar versão/artefato e ambiente; isolamento de terceiros; alçada candidata: Publicação controlada exige aprovação humana adequada; não herdar aceite da versão anterior. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-12/18 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-11-SUS — Suspender/revogar/descontinuar versão homologada

**Importância para revisão:** Bloqueia novas operações em diversas empresas e pode afetar processos pendentes

**Autoridades e fronteiras:** INT-11 registra status/versionamento do conector; INT-01 conexões; INT-02 escopos; proprietários efeitos.

**Referências existentes, ainda não exaustivas:** contratos CT-01/02/04/09/11; requisitos TR-INT11-002/004; cenários HT-12/18 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | conector/versão, motivo, autoridade, abrangência, inventário de conexões/instâncias afetadas e estado esperado |
| Quem pode solicitar e autorizar | Poder de suspender versão precisa de escopo, avaliação de risco e aprovação quando exigida; contenção urgente segue norma existente |
| Precondição, ação tipada e limite do executor | Restringir novas ativações/despachos incompatíveis, preservar versões/pendências, comunicar pelos serviços centrais competentes |
| Resultado e fonte oficial | INT-11 atesta versão suspensa; instâncias/efeitos já enviados exigem reconciliação individual |
| Falha, abuso ou condição negativa | Versionamento incompatível, nova versão não homologada, efeito desconhecido antes da suspensão, perda de resposta de provedor |
| Reconciliação e retomada autorizada | Evitar reexecução por alternativo sem prova, revalidar contas/versões/autorizações antes de eventual migração |
| Decisões de implementação ainda faltantes | Regras por severidade e alcance, janelas de suporte/convivência, aviso e suspensão emergencial, alçada por risco |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Operador de catálogo/segurança com autoridade específica; competência: INT-11 catálogo; INT-01 conexões; INT-02 escopos; avaliação/escopo: Direito de restrição por conector/versão e abrangência de clientes; alçada candidata: Aprovação conforme risco; contenção urgente homologada segue política técnica existente. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-12/18 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


---

### OP-12-AUD — Exportar evidência de auditoria sob controle

**Importância para revisão:** Extração de logs oficiais pode revelar dados sensíveis, inclusive de outra empresa

**Autoridades e fronteiras:** Auditoria Geral é fonte oficial e controla integridade/retenção; INT-12 consulta/exporta com autorização.

**Referências existentes, ainda não exaustivas:** contratos CT-02/04/11; requisitos TR-INT12-003..005; cenários HT-14/17 (cenários planejados, não executados).. [Ficha OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md) e [autorização CT-02](P0-CT02-autorizacoes-permissoes-alcadas-por-operacao.md).

| Dimensão da operação | Contrato conceitual a conferir — sem estabelecer schema executável |
|---|---|
| Envelope do pedido / campos de referência | tenant, caso/finalidade, recorte de registros/período, classificação/sensibilidade, identidade solicitante, manifesto e acesso ao artefato |
| Quem pode solicitar e autorizar | Direito específico de exportar, restrição multiempresa, minimização/mascaramento, aprovação adicional somente quando exigida |
| Precondição, ação tipada e limite do executor | Validar escopo e finalidade, consultar fonte oficial, produzir pacote íntegro, disponibilizar por meio protegido, auditar a própria consulta/exportação |
| Resultado e fonte oficial | Auditoria Geral confirma proveniência/integridade e acesso; INT-12 não muda fatos; log de debug não substitui trilha oficial |
| Falha, abuso ou condição negativa | Leitura sem exportação, acesso a tenant B, artefato truncado, expiração de log técnico mas prova oficial obrigatória |
| Reconciliação e retomada autorizada | Negar escopo ilegítimo, registrar decisão, reconstruir apenas evidência permitida e sinalizar lacunas sem inventá-las |
| Decisões de implementação ainda faltantes | Regimes de retenção por tipo, política de exportação em massa, formato/assinatura de artefato, direitos e alçada concretos |

**Pontos de aprovação CT-02 já propostos na matriz original:** solicitante provável: Responsável com permissão específica de exportação; competência: Auditoria Geral; INT-12 interface de consulta/exportação; avaliação/escopo: Direito de acesso e exportação por escopo, registro, período, finalidade, retenção; alçada candidata: Confirmação reforçada e aprovação específica quando exigidas, sem download indiscriminado. **Atenção:** estes são textos de referência, **não** designação de perfil, concessão de privilégio ou nova exigência universal de aprovação humana.

**Homologação futura correspondente:** HT-14/17 (cenários aprovados, **nenhum teste executado**). A preparação exigirá dataset sintético, autenticidade, versão, decisão de autorização, identificação lógica, registro da tentativa, evidência técnica e confirmação oficial do proprietário, além de negativa de acesso/efeito quando pertinente.

**Status da ficha:** **DETALHAMENTO PROPOSTO PARA REVISÃO**. Aprovação das CT/OP24 é base de arquitetura, não homologação das regras físicas acima.


## 4. Nove operações complementares do segundo lote (já documentadas posteriormente)

**Atualização de 03/11/2026:** as nove fichas listadas abaixo foram **redigidas em proposta documental** em [P0-CON-OP09](P0-CON-OP09-contratos-operacoes-complementares.md). O status histórico 'pendente de especialização' na tabela abaixo corresponde ao momento em que este primeiro lote foi produzido. Consulte o [índice consolidado dos 24 casos](P0-CON-OP24-indice-consolidado-contratos-por-operacao.md) para a situação atual. Nenhuma das 24 fichas especializadas recebeu aceite definitivo.



| OP | Por que ficou no segundo lote | Status |
|---|---|---|
| OP-01-CAD | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |
| OP-03-VAL | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |
| OP-03-VER | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |
| OP-05-APP | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |
| OP-05-API | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |
| OP-06-OUT | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |
| OP-07-SYNC | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |
| OP-10-DIA | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |
| OP-12-INV | Requer detalhamento complementar de contrato/autoridade, sem deixar de existir na base OP24 | Pendente de especialização; OP24 já aceita como base |

Os nove casos restantes não são considerados de risco irrelevante ou dispensados. A priorização desta rodada tem finalidade exclusivamente de **sequenciamento documental**; nenhuma operação se torna liberada para implementação antes da cobertura integral das decisões e regras aplicáveis.

## 5. Matriz de decisão ainda não tomada para as 15 operações

| Controle pendente | Quem tem competência de definição/revisão | Resultado documental exigível | Situação |
|---|---|---|---|
| OP-P01 — Perfis/ação/recurso/campo | Identidade e Segurança e cada proprietário do recurso | Poder do solicitante, delegações, segregação entre tenants, permissões condicionais | Aberto |
| OP-P02 — MFA e alçada por efeito | Segurança, proprietários e Central de Aprovações | Regra específica de quando pedir aprovação, validade e reuso legítimo | Aberto |
| OP-P03 — Contrato físico por OP | INT-04/01/02/03 e proprietário por ato | Schema de comando/resultado, precondições, tipos/erros, evidência oficial | Aberto |
| OP-P04 — Garantias e retentativas | Adaptador/provedor e orquestradores competentes | Concorrência/idempotência segura por efeito, resultado inconclusivo, reconciliação | Aberto |
| OP-P05 — Contratos de versão | INT-11/03/09, Segurança e proprietários | Compatibilidade, pin de instância, suspensão, convivência/migração | Aberto |
| OP-P06 — Efeito de compensação | FIN/CON/AGD e donos por operação | Direito de reversão, irreversibilidade, alçada e prova do estorno/ato | Aberto |
| OP-P07 — Evidência e retenção | Auditoria Geral e políticas proprietárias | Proveniência, retenção por categoria, minimização, consulta/exportação | Aberto |
| OP-P08 — Origem da regra e critérios HT | Arquitetura INT, dono, Segurança e Auditoria | Cotejo literal das decisões, plano HT específico e critérios aceitos | Aberto |
| OP-P09 — Contratos entre serviços centrais | Eventos, Filas, Tarefas, Datas, Aprovações, Arquivos, Observabilidade e Auditoria | Arestas tipadas, falha segura, ciclo de eventos, sem duplicar motores | Aberto |

## 6. Resultados e portas de fechamento P0-CON-01

- **Concluído neste documento:** primeira rodada de **15 fichas conceituais propostas**, com referência às OP24 e a CT-02, pré-condições, evidências, falhas e pendências por tipo de efeito.
- **Não concluído:** aceite definitivo de contratos físicos por operação, fontes originais integrais, direitos, schemas, políticas de retry/provedor, alçadas, confirmação específica dos proprietários e testes. As nove fichas complementares também foram aceitas **como bases documentais** no [mesmo P0-DEC](P0-DEC-aprovacao-OP15-OP09-bases.md).
- A [matriz CON-MASTER](P0-CON-MASTER-matriz-CT01-CT11-criterios-fechamento.md) permanece **aprovada como instrumento documental**; este desdobramento foi **aceito como base documental**, mas os contratos de implementação permanecem sujeitos a revisão.
- **P0-CON-01, P0-OWN-01, P0-SEC-01, P0-DEP-01, P0-VER-01, P0-TR-01, P0-DOC-01/02, P0-XREF-01, P0-ACC-01 e G01–G06 permanecem abertos/parciais.**
- **HT-01..18 não foram executados.** A fase de documentação é separada da futura fase autorizada de implementação e homologação.

**Conclusão atual:** 15 operações detalhadas e **aceitas como bases documentais**, sem conceder permissões, aprovar schemas ou afirmar prontidão para programação.
