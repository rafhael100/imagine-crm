# P0-ACC — Planos documentais de homologação HT-01 a HT-18

**Data de registro:** 2026-11-03.
**Estado:** **18 procedimentos DOCUMENTAIS PROPOSTOS PARA REVISÃO**, elaborados sob aprovação expressa de [P0-SEC e P0-VER como bases documentais](P0-DEC-aprovacao-SEC-VER-base.md). As **categorias HT-01..18 já foram aprovadas** na [INT-R02](INT-R02-rastreabilidade-dependencias-validacao.md); os **procedimentos detalhados abaixo ainda não foram aceitos individualmente nem executados**.
**Proibição vigente:** NÃO executar testes, scripts, operações, cargas, migrações, chamadas a provedores, alterações de produção, contratação, novas automações ou programação. Este documento contém somente roteiro para futuro planejamento/homologação após autorização específica.
**Escopo:** INT-01 a INT-12, contratos CT-01..11, 24 fichas operacionais de referência, INV-01..16, Identidade e Segurança, Central de Aprovações, serviços centrais e módulos proprietários.

## 1. Método, ambientes e distinções obrigatórias

- **Homologação documental:** autor pode propor objetivo, precondições, entradas, procedimento, comportamento esperado, critérios de falha, evidências e aprovador. **Não é teste executado**.
- **Teste real futuro:** exige autorização expressa para fase de implementação/homologação, ambiente isolado compatível, identidades de teste, dados sintéticos, conectores de teste, observabilidade e recursos apropriados. Nenhum teste real está autorizado nesta fase.
- **Valores pendentes:** quotas, timeouts, intervalos de retentativa, concorrência, TTL, capacidade, versões e serviços de teste deverão ser definidos por contrato/provedor e aprovados. **Não inventar métricas**.
- **Propriedade dos resultados:** sucesso técnico/HTTP/webhook/n8n não equivale à confirmação de negócio; somente o módulo proprietário pode atestar o fato de sua competência.
- **Segurança:** Identidade e Segurança e INT-02 validam escopos, Central de Aprovações decide quando exigido; IA não amplia privilégios; arquivos, logs e evidências protegidos por tenant, ambiente e finalidade.
- **Evidência oficial:** Auditoria Geral mantém a trilha oficial e INT-12 fornece investigação/correlação. Logs de debug técnicos seguem IN-162 e não determinam retenção de fatos de negócio.
- **Estados de procedimento** (proposta documental, não enum de sistema): rascunho; em revisão; validado documentalmente; aguardando homologação; executado com evidência; aprovado; reprovado; bloqueado; dispensado com justificativa aprovada. No presente registro, todos estão **“rascunho documental / não executado”**.
- **Condições de interrupção futura:** risco de ação real fora do ambiente, autorização inválida, cross-tenant, segredo real, custo inesperado, versão não homologada ou operação de escrita insegura. Não contornar bloqueios por IA.
- **Não garantia exatamente-uma-vez:** validações usam identidade lógica, idempotência contextual e reconciliação. Não presumir ACID distribuído.

## 2. Modelo de ficha e pacote de evidências (a preparar, não preenchido com resultados)

Toda execução futura deverá registrar, **sem preencher antecipadamente**, ao menos:
- ID HT, revisão do procedimento, vínculo TR/INV/CT/OP, fundamento da decisão original e versão dos contratos.
- Nome/função do executor autorizado e aprovador de homologação, ambiente, tenant de teste, identidades técnicas, autorização e finalidade.
- Dataset fictício, origem/destino, estado inicial, versões de conector/esquema/modelo, pré-condições e resultados esperados.
- Registro das etapas, sinais de observabilidade, tentativas, comandos, correlação e resultado oficial do proprietário, quando for exigível.
- Evidência de caso negativo, ausência de vazamento e não duplicação de efeitos; registros de falhas/ressalvas e ticket do desvio.
- Decisão final **posterior à execução**, com PASS/FAIL/BLOQUEADO, responsável, data, evidências, reexecução e revisão, sem edição silenciosa da história.

**Responsável pela evidência documental:** arquitetura do módulo INT e responsáveis dos serviços centrais/proprietários no processo de revisão; pessoas, alçadas e signatários concretos ainda não definidos.

## 3. Índice e estado de todos os 18 planos

| ID | Cenário aprovado na INT-R02 | Componentes principais | Estado atual |
|---|---|---|---|
| [HT-01](#ht-01) | Conexão autorizada e capacidade homologada | INT-01, INT-02, INT-04, INT-11, Identidade e Segurança | Planejado; não executado |
| [HT-02](#ht-02) | Revogação de credencial durante fluxo | INT-01, INT-02, INT-10, Identidade e Segurança, cofre IN-151 | Planejado; não executado |
| [HT-03](#ht-03) | Transformação de dados tipada e reprodutível | INT-03, INT-04, módulos proprietários | Planejado; não executado |
| [HT-04](#ht-04) | API pública desabilitada no trial, integrações nativas preservadas | INT-05, Identidade e Segurança, serviço nativo de formulários | Planejado; não executado |
| [HT-05](#ht-05) | Webhook duplicado, tardio ou fora de ordem | INT-06, Motor Central de Eventos, INT-04 e módulo proprietário | Planejado; não executado |
| [HT-06](#ht-06) | Sincronização interrompida e retomada por checkpoint | INT-07, INT-10, INT-03, proprietários dos dados | Planejado; não executado |
| [HT-07](#ht-07) | Importação parcialmente válida e portabilidade por lotes | INT-03, INT-08, Central de Backup/Portabilidade IN-118, Arquivos, proprietários | Planejado; não executado |
| [HT-08](#ht-08) | Escrita externa com resposta inconclusiva | INT-04, INT-09, INT-10 e módulo proprietário, INT-12 | Planejado; não executado |
| [HT-09](#ht-09) | Orquestração paralela e convergência de dependências | INT-09, Motor Central de Eventos, Tarefas, Aprovações, proprietários | Planejado; não executado |
| [HT-10](#ht-10) | Compensação parcial condicionada à competência | INT-09, INT-10, FIN/CON/AGD ou outro proprietário, Aprovações | Planejado; não executado |
| [HT-11](#ht-11) | Indisponibilidade de provedor e recuperação gradual | INT-01, INT-09, INT-10, Central de Observabilidade IN-159/160 | Planejado; não executado |
| [HT-12](#ht-12) | Mudança incompatível de esquema, conector ou modelo | INT-03, INT-04, INT-09, INT-11, Segurança | Planejado; não executado |
| [HT-13](#ht-13) | Tentativa de acesso entre empresas/tenants | Todas INT-01 a INT-12, Identidade e Segurança, Auditoria Geral | Planejado; não executado |
| [HT-14](#ht-14) | Auditoria ponta a ponta e proveniência | INT-01 a INT-12, Auditoria Geral, INT-12, Analytics | Planejado; não executado |
| [HT-15](#ht-15) | IA recomenda ação crítica sem permissão de execução | INT-09 a INT-12, Central de Aprovações, Identidade e Segurança, IN-167 | Planejado; não executado |
| [HT-16](#ht-16) | Falha de filas e do Motor Central de Eventos | INT-06 a INT-10, Eventos, Filas, Observabilidade | Planejado; não executado |
| [HT-17](#ht-17) | Consulta e exportação de evidências sob controle | INT-12, Auditoria Geral, Identidade e Segurança, Arquivos | Planejado; não executado |
| [HT-18](#ht-18) | Conector homologado suspenso ou revogado | INT-01, INT-02, INT-10, INT-11, Aprovações | Planejado; não executado |

## 4. Procedimentos HT-01 a HT-18

## HT-01 — Conexão autorizada e capacidade homologada

**Objetivo:** Comprovar que a homologação do conector e a existência da conexão não substituem autorização específica do tenant e escopo da operação.

**Componentes e autoridade:** INT-01, INT-02, INT-04, INT-11, Identidade e Segurança.  
**Rastreabilidade candidata:** CT-01, CT-02, CT-03, CT-04; INV-01/02/03/10; TR-INT01-001, TR-INT02-001, TR-INT04-001, TR-INT11-001. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Dois tenants fictícios A/B; conector de teste homologado em versão determinada; conexão válida para A; identidade autorizada e outra sem autorização; credenciais simuladas, sem segredos reais.

**Procedimento futuro proposto:**
1. Preparar conexões e versões distintas em ambiente isolado, com permissões de teste documentadas.
2. Solicitar operação tipada autorizada de A e observar consulta aos direitos do tenant, conexão, versão e capacidade.
3. Repetir com conector não homologado, conexão suspensa e/ou escopo inadequado, variando uma condição por execução.
4. Registrar separadamente aceite técnico da operação e confirmação por proprietário quando houver efeito de negócio.

**Verificação negativa obrigatória:** A tentativa de B usar a conexão de A e a tentativa com versão/escopo não homologado não devem produzir acesso nem efeito protegido.

**Resultado esperado:** Somente combinação elegível de conector, conexão, identidade e escopo admite a operação; competência de negócio permanece proprietária.

**Evidências a recolher futuramente:** Versões do conector/contrato, referências de identidade/tenant/conexão, decisão de autorização, CT-03/04 correlacionados e evidência oficial do efeito ou da recusa.

**Critério de aceite específico:** Aceitar somente quando nenhuma conexão não elegível executar e todas as decisões e resultados distinguirem transporte de negócio.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-02 — Revogação de credencial durante fluxo

**Objetivo:** Validar que uma revogação pertinente bloqueia novas operações protegidas mesmo se uma tentativa anterior já foi enfileirada ou transmitida.

**Componentes e autoridade:** INT-01, INT-02, INT-10, Identidade e Segurança, cofre IN-151.  
**Rastreabilidade candidata:** CT-02, CT-03, CT-04, CT-09; INV-03/04/08/12; TR-INT02-002/003, TR-INT10-002. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Credencial fictícia válida para tenant A; operação antes e depois da revogação; uma etapa já transmitida com resultado ainda não esclarecido.

**Procedimento futuro proposto:**
1. Registrar escopo, prazo e tenant de credencial simulada, sem publicar segredo.
2. Criar situação de comando pendente e revogar o escopo por autoridade de teste competente.
3. Tentar iniciar nova ação protegida e retomar uma tarefa assíncrona com contexto anterior.
4. Separar impedimento das novas ações da investigação de possíveis efeitos previamente transmitidos.

**Verificação negativa obrigatória:** Nenhum agente, retry ou evento pode reativar credencial, ampliar escopo ou concluir que o efeito anterior foi automaticamente revertido.

**Resultado esperado:** Novos atos protegidos são bloqueados; pendências anteriores permanecem rastreáveis e reconciliáveis conforme proprietário.

**Evidências a recolher futuramente:** Evento de revogação e seu alcance, decisões CT-02 antes/depois, comando original, fila/attempts, diagnóstico e referência de confirmação externa se existir.

**Critério de aceite específico:** Revogação efetiva e segregação comprovadas; nenhum retry com privilégio revogado; efeito anterior não foi perdido ou inventado.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-03 — Transformação de dados tipada e reprodutível

**Objetivo:** Garantir integridade de esquemas e mapeamentos versionados sem conceder autorização de escrita ou mesclagem destrutiva.

**Componentes e autoridade:** INT-03, INT-04, módulos proprietários.  
**Rastreabilidade candidata:** CT-03, CT-04, CT-07; INV-02/10/11/14; TR-INT03-001/002/003, TR-INT04-001. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Esquemas fictícios origem/destino com campos válidos, ausentes, incompatíveis e dados sensíveis sintéticos.

**Procedimento futuro proposto:**
1. Fixar versões de mapeamento e definir entradas conhecidas e resultados esperados campo a campo.
2. Produzir prévia de conversão sem modificar registro oficial.
3. Introduzir campo obrigatório ausente, tipo incompatível e versão de esquema divergente.
4. Comparar rastreabilidade, origem e saída, verificando que eventual escrita seria ato separado autorizado pelo proprietário.

**Verificação negativa obrigatória:** Transformação inválida ou não homologada não executa SQL/código livre, não mescla clientes nem grava dados por efeito colateral.

**Resultado esperado:** Mesma versão e entrada geram saída tipada consistente; erro é localizado; sem escrita inesperada.

**Evidências a recolher futuramente:** Versões e manifesto de esquema, entrada sintética, prévia, validações por campo, erros, correlação e comprovação de ausência de escrita.

**Critério de aceite específico:** Mapeamentos válidos reproduzíveis e incompatibilidades barradas sem perda de proveniência ou mutação não autorizada.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-04 — API pública desabilitada no trial, integrações nativas preservadas

**Objetivo:** Testar limitação da API pública no trial sem bloquear indevidamente integrações nativas elegíveis e autorizadas.

**Componentes e autoridade:** INT-05, Identidade e Segurança, serviço nativo de formulários.  
**Rastreabilidade candidata:** CT-02, CT-03, CT-04; INV-01/03/16; TR-INT05-001/002/003. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Tenant fictício em trial ativo e tenant fictício de plano elegível; aplicação pública e integração nativa de formulário, cada qual com identidade própria.

**Procedimento futuro proposto:**
1. Solicitar operação da API pública usando aplicativo de tenant em trial.
2. Registrar negação sem expor dados de outra conta.
3. Exercitar caminho separado de integração nativa autorizada e provar que não usa autorização pública indevida.
4. Revalidar operação de aplicativo elegível no plano correspondente e sua confirmação proprietária.

**Verificação negativa obrigatória:** Uso de token técnico, n8n ou URL alternativa não deve burlar bloqueio da API pública do trial.

**Resultado esperado:** API pública permanece bloqueada no trial; capacidades nativas aprovadas continuam sob suas políticas de autorização.

**Evidências a recolher futuramente:** Estado de plano/trial, tipo de integração, decisão CT-02, resposta de acesso, trilhas minimizadas e confirmação do serviço nativo se aplicável.

**Critério de aceite específico:** Bloqueio consistente sem negar funcionalidade nativa aprovada nem abrir rota de evasão.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-05 — Webhook duplicado, tardio ou fora de ordem

**Objetivo:** Comprovar autenticidade e deduplicação de eventos externos, mantendo recebimento distinto de efeito de negócio.

**Componentes e autoridade:** INT-06, Motor Central de Eventos, INT-04 e módulo proprietário.  
**Rastreabilidade candidata:** CT-04, CT-05; INV-05/07/14; TR-INT06-001/002/003/004. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Evento sintético assinado de origem autorizada; cópias do mesmo event_id, evento novo com payload idêntico, evento inválido e ordem temporal invertida.

**Procedimento futuro proposto:**
1. Receber evento autorizado e registrar evidência de ingresso por INT-06.
2. Reenviar a mesma identidade de evento e verificar ausência de segunda aplicação de efeito.
3. Entregar evento tardio/fora de ordem e correlacionar com estado oficial corrente.
4. Enviar evento sem autenticidade e outro legítimo distinto com dados coincidentes.

**Verificação negativa obrigatória:** Evento inválido não executa; réplica não duplica efeito; conteúdo semelhante não elimina eventos legitimamente distintos.

**Resultado esperado:** Deduplicação contextual, causalidade, bloqueio de replay e confirmação por proprietário.

**Evidências a recolher futuramente:** event_id, versão e autenticação sem segredos, hora/correlação, aceites técnicos, referências de resultado oficial e decisão de deduplicação.

**Critério de aceite específico:** Nenhuma duplicidade indevida, perda de evento distinto ou conclusão falsa de negócio.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-06 — Sincronização interrompida e retomada por checkpoint

**Objetivo:** Verificar que retomada usa checkpoint durável e mantém autoridade por campo sem pular ou replicar escrita já aplicada.

**Componentes e autoridade:** INT-07, INT-10, INT-03, proprietários dos dados.  
**Rastreabilidade candidata:** CT-02, CT-04, CT-06, CT-09; INV-03/07/08/10; TR-INT07-001/002/003/004. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Fonte/destino sintéticos versionados, alterações incrementais com checkpoint entre lotes e pausa forçada em etapa não concluída.

**Procedimento futuro proposto:**
1. Definir direção, campos permitidos, cursor inicial, estado do destino e contrato aplicável.
2. Aplicar apenas parte dos itens de um lote, registrando confirmações apropriadas.
3. Simular falha técnica e divergência de confirmação após transmissão.
4. Retomar sob autorização revalidada e comparar o estado final com o conjunto lógico esperado.

**Verificação negativa obrigatória:** Cursor não avança além de alterações confirmadas; campos sem autoridade não são sobrescritos, e versão incompatível impede retomada insegura.

**Resultado esperado:** Continuação reprodutível com itens pendentes reconciliados, sem escrita duplicada ou perda lógica não tratada.

**Evidências a recolher futuramente:** Cursor/checkpoints antes/depois, IDs de itens, versões, tentativas, confirmação do proprietário e registro da falha/retomada.

**Critério de aceite específico:** Estado final e pendências explicáveis por evidências; nenhum efeito ignorado ou aplicado duas vezes.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-07 — Importação parcialmente válida e portabilidade por lotes

**Objetivo:** Avaliar prévia, validação, importação parcial, recuperação e autoatendimento inclusive no plano Básico sem mesclagem destrutiva.

**Componentes e autoridade:** INT-03, INT-08, Central de Backup/Portabilidade IN-118, Arquivos, proprietários.  
**Rastreabilidade candidata:** CT-02, CT-04, CT-07; INV-02/07/15/16; TR-INT08-001/002/003/004/005. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Tenant Básico fictício; arquivo sintético CSV/XLSX com registros válidos, inválidos e duplicidades lógicas delimitadas; destino vazio e depois destino com registros.

**Procedimento futuro proposto:**
1. Validar arquivo, esquema/manifesto e capacidade de exportação/importação do plano sem cobrança operacional adicional.
2. Gerar prévia de inconsistências e autorização de operação, sem escrita.
3. Simular lotes com alguns registros aplicados, outros rejeitados ou inconclusivos.
4. Retomar/reconciliar cada lote e confirmar apenas os registros efetivamente persistidos no proprietário.
5. Verificar que backup completo e exportação simples continuam categorias distintas.

**Verificação negativa obrigatória:** Não copiar credenciais de outro tenant, não mesclar/excluir automaticamente registros existentes nem bloquear exportação por franquia cheia de anexos.

**Resultado esperado:** Contagens e estados por registro/lote fiéis à persistência; resolução controlada dos conflitos e autoatendimento essencial preservado.

**Evidências a recolher futuramente:** Versão/manifesto do arquivo, validação/previsão, autorização, recibo por lote e registro, conflitos, rejeições, histórico e evidência de plano.

**Critério de aceite específico:** Nenhuma gravação destrutiva sem autoridade, nenhum lote marcado como totalmente concluído quando parcial e portabilidade essencial mantida.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-08 — Escrita externa com resposta inconclusiva

**Objetivo:** Demonstrar que timeout após transmissão não autoriza repetição cega de efeito de negócio nem conclusão prematura.

**Componentes e autoridade:** INT-04, INT-09, INT-10 e módulo proprietário, INT-12.  
**Rastreabilidade candidata:** CT-03, CT-04, CT-09, CT-11; INV-05/06/07/08; TR-INT04-002/003, TR-INT09-003/004, TR-INT10-003. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Provedor simulado que pode aplicar efeito e perder resposta; operações fictícias de reserva/pagamento/lead, cada uma isolada e sem dinheiro real.

**Procedimento futuro proposto:**
1. Registrar comando lógico, versão, chave contextual e autorizações de origem.
2. Simular transmissão e aplicação parcial/total com resposta perdida.
3. Classificar resultado inconclusivo e acionar consulta de reconciliação homologada.
4. Diferenciar caso confirmado pelo proprietário, rejeitado e ainda desconhecido.
5. Avaliar retry somente depois de comprovar elegibilidade do contrato e ausência de duplicação.

**Verificação negativa obrigatória:** Não criar pagamento/reserva/lead adicional por tentativa; HTTP de aceite não é confirmação do proprietário.

**Resultado esperado:** Identidade preservada e nenhuma segunda escrita arriscada sem segurança/reconciliação; resultado oficial comprovável.

**Evidências a recolher futuramente:** operation/command/attempt IDs, logs de envio minimizados, evidência externa de efeito, decisão de reconciliação e fonte oficial do proprietário.

**Critério de aceite específico:** História causal demonstra quando foi ou não seguro reenviar; nenhum efeito duplo ou confirmação inventada.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-09 — Orquestração paralela e convergência de dependências

**Objetivo:** Conferir que ramificações paralelas não dispensam dependências obrigatórias e cada etapa aguarda confirmação por fonte competente.

**Componentes e autoridade:** INT-09, Motor Central de Eventos, Tarefas, Aprovações, proprietários.  
**Rastreabilidade candidata:** CT-02, CT-03, CT-04, CT-08; INV-02/04/05/07/10; TR-INT09-001/002/003. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Modelo fictício versionado com duas tarefas independentes e etapa convergente dependente de ambas; uma ramificação atrasada e uma aprovação pendente.

**Procedimento futuro proposto:**
1. Instanciar modelo autorizado e registrar versão fixa/dependências.
2. Simular sucesso técnico na primeira ramificação, mantendo efeito de negócio não confirmado na outra.
3. Tentar avançar convergência e verificar bloqueio por dependência não confirmada.
4. Confirmar cada fato com seu proprietário e observar a liberação da etapa seguinte.
5. Simular evento atrasado e mudança de versão sem alterar instância ativa.

**Verificação negativa obrigatória:** Não concluir processo porque n8n terminou, uma ramificação foi aceita ou IA recomendou avanço.

**Resultado esperado:** Convergência apenas após evidências oficiais de todas as pré-condições obrigatórias e autorizações válidas.

**Evidências a recolher futuramente:** Versão de modelo/instância/etapas, dependências, decisões da Central de Aprovações, eventos causais e confirmações dos proprietários.

**Critério de aceite específico:** Nenhuma etapa obrigatória pulada ou concluída por aceite exclusivamente técnico.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-10 — Compensação parcial condicionada à competência

**Objetivo:** Garantir que efeitos compensáveis/irreversíveis sejam classificados corretamente, com autorização e confirmação próprias.

**Componentes e autoridade:** INT-09, INT-10, FIN/CON/AGD ou outro proprietário, Aprovações.  
**Rastreabilidade candidata:** CT-02, CT-04, CT-08, CT-10; INV-02/04/08/09; TR-INT09-004/005, TR-INT10-003. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Fluxo sintético com três efeitos: um reversível, um dependente de aprovação e um irreversível ou sem mecanismo de compensação.

**Procedimento futuro proposto:**
1. Registrar os efeitos originais oficialmente confirmados e suas versões.
2. Propor compensação tipada para cada efeito, com reversibilidade e risco documentados.
3. Simular aprovação da operação elegível e rejeição/pendência das demais conforme competência.
4. Simular execução parcial da compensação e registrar confirmação própria por efeito.
5. Reconciliar pendências sem apresentar rollback global.

**Verificação negativa obrigatória:** INT-09/10/IA não executa estorno, cancelamento ou desprovisionamento sem proprietário e autorização.

**Resultado esperado:** Estados específicos por compensação; efeitos não revertidos continuam explícitos.

**Evidências a recolher futuramente:** Fonte oficial dos efeitos originais, decisão de elegibilidade/aprovação, comandos de compensação, resultado técnico/oficial e pendências.

**Critério de aceite específico:** Nenhuma compensação indevida, invisibilização de efeito remanescente ou conclusão global falsa.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-11 — Indisponibilidade de provedor e recuperação gradual

**Objetivo:** Comprovar classificação correta da falha e recuperação autorizada sem confundir saúde da conexão com operações finalizadas.

**Componentes e autoridade:** INT-01, INT-09, INT-10, Central de Observabilidade IN-159/160.  
**Rastreabilidade candidata:** CT-01, CT-04, CT-09; INV-03/05/08/13/14; TR-INT10-001/002/003/004/005. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Provedor simulado alternando saudável, degradado, indisponível e telemetria ausente; processos com comandos pendentes.

**Procedimento futuro proposto:**
1. Registrar telemetria temporal e saúde observada, incluindo ausência de dados.
2. Simular indisponibilidade e acionar o procedimento técnico de contenção previamente homologado no cenário.
3. Restabelecer comunicação e revalidar permissões, versão e condições de retomada.
4. Confrontar cada operação pendente com sua fonte oficial, sem encerramento em lote por saúde técnica.

**Verificação negativa obrigatória:** Não tratar ausência de telemetria como saudável ou trocar provedor crítico autonomamente pela IA.

**Resultado esperado:** Incidente verificável, recuperação técnica gradual e negócio pendente até confirmação apropriada.

**Evidências a recolher futuramente:** Sinais IN-159/160, causa, limites autorizados do playbook, decisões e auditoria, teste de saúde e lista reconciliada de operações.

**Critério de aceite específico:** Incidente e status por comando tecnicamente fundamentados, sem recuperação declarativa não comprovada.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-12 — Mudança incompatível de esquema, conector ou modelo

**Objetivo:** Verificar versionamento e pinagem de instâncias, evitando atualização incompatível silenciosa.

**Componentes e autoridade:** INT-03, INT-04, INT-09, INT-11, Segurança.  
**Rastreabilidade candidata:** CT-01, CT-03, CT-04, CT-08; INV-03/10/11/14; TR-INT03-004, TR-INT04-004, TR-INT09-001, TR-INT11-001. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Conector e modelo de teste v1 em uso; v2 fictícia com incompatibilidade semântica demonstrável; instância e checkpoint pendentes.

**Procedimento futuro proposto:**
1. Registrar capacidades e contratos v1 em conexão/instância ativa.
2. Submeter v2 a verificação de compatibilidade e homologação, sem habilitá-la para todos por padrão.
3. Simular tentativa de processar instância v1 com interpretação v2 e verificar proteção.
4. Definir, documentalmente, opção de convivência/recusa/migração que exigiria aprovação e teste.
5. Provar que histórico e referências v1 permanecem interpretáveis.

**Verificação negativa obrigatória:** Não herdar homologação/autorização automaticamente para v2 nem atualizar versão de comando/payload já enviado.

**Resultado esperado:** Incompatibilidade identificada; instâncias preservadas ou transição controlada e expressamente autorizada.

**Evidências a recolher futuramente:** Manifestos de versão, matriz V-C, inventário de instâncias afetadas, aprovação/negação e resultados de compatibilidade.

**Critério de aceite específico:** Nenhum efeito ou dado muda de semântica silenciosamente e as versões ativas são rastreáveis.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-13 — Tentativa de acesso entre empresas/tenants

**Objetivo:** Validar isolamento efetivo em criação, leitura, escrita, execução, credenciais, filas, arquivos, exportação e evidências.

**Componentes e autoridade:** Todas INT-01 a INT-12, Identidade e Segurança, Auditoria Geral.  
**Rastreabilidade candidata:** CT-01..CT-11 conforme operação; INV-01/02/03/11/12/14; TR-INT05-001, TR-INT08-004, TR-INT12-003. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Dois tenants fictícios A e B com objetos e identidades separadas, incluindo um usuário com acesso legítimo a ambos em contextos distintos.

**Procedimento futuro proposto:**
1. Definir matriz de operações representativas com recurso de A e identidade/credencial restrita a B.
2. Tentar consultas, comandos, eventos, downloads e exportações cruzados, sem acesso privilegiado global.
3. Trocar contexto do usuário multiempresa e tentar reusar autorização de A em B.
4. Inspecionar evidências minimizadas de bloqueio e ausência de vazamento, inclusive na API, n8n e auditoria.

**Verificação negativa obrigatória:** Nenhum sucesso por substituir tenant_id do payload, reutilizar URL assinada fora do escopo ou usar identidade técnica geral.

**Resultado esperado:** Isolamento e negação consistentes; acesso válido apenas dentro do contexto autorizado.

**Evidências a recolher futuramente:** Matriz tenant/ator/recurso, decisão de CT-02, negações sem exposição, rastros de recurso e ensaio de ausência de efeitos.

**Critério de aceite específico:** Nenhum vazamento, escrita ou visualização cruzada indevida nas fronteiras contempladas.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-14 — Auditoria ponta a ponta e proveniência

**Objetivo:** Demonstrar reconstrução verificável de eventos de integração, tentativas, autorizações, comandos, efeitos, incidentes e compensações pela fonte oficial.

**Componentes e autoridade:** INT-01 a INT-12, Auditoria Geral, INT-12, Analytics.  
**Rastreabilidade candidata:** CT-03, CT-04, CT-11; INV-05/07/14/15; TR-INT12-001/002/004/005. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Cadeia sintética de processo com versões, autorizações, erro inconclusivo, recuperação e confirmação em módulo proprietário.

**Procedimento futuro proposto:**
1. Definir linha do tempo esperada para cada tipo de evidência e sua proveniência.
2. Gerar futuramente eventos sintéticos no cenário isolado e consultar a Auditoria Geral.
3. Reconstruir causalidade/versões por INT-12 sem alterar dados oficiais.
4. Simular ausência de uma fonte e verificar identificação honesta da lacuna.
5. Conferir que Analytics e logs técnicos não substituem prova oficial de negócios.

**Verificação negativa obrigatória:** Não inventar evidência, não apagar fato contraditório, não criar banco de auditoria paralelo nem usar prazo de debug para registros obrigatórios.

**Resultado esperado:** Histórico coerente ou lacunas explícitas, com fontes verificáveis, acesso auditável e retenção por categoria.

**Evidências a recolher futuramente:** IDs de correlação e tentativa, versões, referências de autorização, proveniência, integridade, classificação de retenção e consulta auditada.

**Critério de aceite específico:** Reconstituição satisfatória de fatos provados, com limites explicitamente assinalados.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-15 — IA recomenda ação crítica sem permissão de execução

**Objetivo:** Provar que sugestão de IA não outorga poder de alteração crítica, compensação, publicação ou troca de provedor.

**Componentes e autoridade:** INT-09 a INT-12, Central de Aprovações, Identidade e Segurança, IN-167.  
**Rastreabilidade candidata:** CT-02, CT-03, CT-09, CT-10; INV-03/04/09/11/12/13; TR-INT02-004, TR-INT09-006. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Agente de teste sem direito de execução crítica, uma recomendação de compensação ou troca de provedor, um playbook N2 seguro e caso N3 crítico.

**Procedimento futuro proposto:**
1. Registrar recomendação de IA com contexto e classificação proposta, sem executá-la.
2. Tentar invocar ação crítica diretamente e verificar negação por autorizador independente do prompt.
3. Repetir com decisão humana legítima e válida no escopo exato, separando aprovação e execução técnica.
4. Confirmar que playbook N2 homologado só executa sua ação segura e não vira autorização N3.
5. Verificar que uma aprovação do mesmo ato não é exigida duas vezes sem suprimir autorização de ato distinto.

**Verificação negativa obrigatória:** Prompt, supervisor ou workflow não altera classificação de risco, política do proprietário ou direitos de ferramenta.

**Resultado esperado:** Recomendação auditável, bloqueio do ato sem autoridade, execução apenas quando autorizada e confirmação proprietária.

**Evidências a recolher futuramente:** Prompt/recomendação minimizados sem segredo, identidade de agente, regra/versão de autorização, decisão humana, comando e resultado.

**Critério de aceite específico:** Nenhuma ação crítica autônoma nem dupla aprovação indevida da mesma decisão.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-16 — Falha de filas e do Motor Central de Eventos

**Objetivo:** Verificar persistência, tratamento de duplicidades, atrasos, falhas e reconciliação sem promessa de entrega exatamente uma vez.

**Componentes e autoridade:** INT-06 a INT-10, Eventos, Filas, Observabilidade.  
**Rastreabilidade candidata:** CT-04, CT-05, CT-06, CT-08, CT-09; INV-05/06/07/08/14; TR-INT06-004, TR-INT07-002, TR-INT10-005. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Filas e eventos sintéticos com falha após persistir, antes de ack, consumidor indisponível e mensagens fora de ordem.

**Procedimento futuro proposto:**
1. Registrar IDs estáveis, versão, estado de persistência e consumidor elegível.
2. Simular falha de enfileiramento/ack e reinício do consumidor, em ambiente de teste futuro.
3. Observar reentregas, deduplicação contextual, filas de falhas e incidentes.
4. Conferir que checkpoint/comando só avançou mediante confirmação apropriada e autorização ainda válida.
5. Reconciliar mensagens inconclusivas antes de reexecutar escrita com efeito.

**Verificação negativa obrigatória:** Não apagar operação ainda não comprovada nem converter erro de fila em conclusão de negócio.

**Resultado esperado:** Estados e pendências preservados, recuperação limitada e não duplicação de efeitos quando houver garantia comprovada.

**Evidências a recolher futuramente:** Outbox/filas/checkpoints e tentativas sintéticos, erros/ack, eventos de incidente, confirmação proprietária e trilhas de auditoria.

**Critério de aceite específico:** Nenhuma perda silenciosa de responsabilidade; estados e decisões justificáveis por evidência.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-17 — Consulta e exportação de evidências sob controle

**Objetivo:** Garantir acesso e exportação com autorização própria, minimização, integridade e auditoria da auditoria.

**Componentes e autoridade:** INT-12, Auditoria Geral, Identidade e Segurança, Arquivos.  
**Rastreabilidade candidata:** CT-02, CT-07, CT-11; INV-01/03/14/15/16; TR-INT08-004, TR-INT12-003/004. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Evidências sintéticas com níveis distintos de confidencialidade, duas empresas e perfis com leitura distinta de exportação.

**Procedimento futuro proposto:**
1. Selecionar consulta no tenant correto e registrar finalidade, escopo, identidade e período.
2. Tentar exportação com apenas direito de leitura e verificar controle específico exigível.
3. Produzir pacote sintético autorizado, com proveniência e política de expiração de acesso ainda a parametrizar.
4. Executar consulta cruzada e verificar negação e registro auditável.
5. Confrontar integridade e trilha do próprio acesso/exportação na Auditoria Geral.

**Verificação negativa obrigatória:** Sem acesso cross-tenant, sem segredos no arquivo, sem omitir evidência de exportação e sem bloqueio geral por quota cheia de anexos.

**Resultado esperado:** Somente registros permitidos e minimizados são exportados e a própria consulta/exportação é rastreável.

**Evidências a recolher futuramente:** Decisão de acesso, manifesto de dados sintéticos, hash/identidade de artefato se contratualizado, histórico de consulta/download e política de retenção.

**Critério de aceite específico:** Escopo correto, integridade e auditoria comprováveis; nenhuma informação de outro tenant.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**

## HT-18 — Conector homologado suspenso ou revogado

**Objetivo:** Verificar restrição de novas ativações após suspensão/revogação e tratamento correto de conexões/instâncias pendentes.

**Componentes e autoridade:** INT-01, INT-02, INT-10, INT-11, Aprovações.  
**Rastreabilidade candidata:** CT-01, CT-02, CT-04, CT-09; INV-03/05/08/10/13; TR-INT01-002/004, TR-INT11-002/004. Os TR vinculados continuam sujeitos à conferência das aprovações originais.  
**Pré-condições/dados sintéticos:** Conector fictício v1 homologado com conexões A/B, operações ativas e v2 candidata ainda não homologada.

**Procedimento futuro proposto:**
1. Registrar versão, capacidades, conexões e operações pendentes antes da suspensão.
2. Simular ato autorizado de suspensão de v1 e tentar nova conexão/novo despacho.
3. Tratar separadamente comando já enviado e ainda inconclusivo.
4. Avaliar alternativa v2 somente depois de homologação, compatibilidade, autorização e alçada exigidas.
5. Registrar comunicação técnica, incidente, impacto e preservação dos fatos passados.

**Verificação negativa obrigatória:** Não atualizar silenciosamente a versão ativa nem auto-reemitir escrita no provedor alternativo por decisão da IA.

**Resultado esperado:** Novas ativações bloqueadas, instâncias/pendências conservadas e migração somente controlada.

**Evidências a recolher futuramente:** Decisão de suspensão, catálogo/versionamento, conexões impactadas, CT-02/03/04, reconciliações, auditoria e plano de contingência.

**Critério de aceite específico:** Nenhuma ativação incompatível e nenhum resultado anteriormente incerto apagado ou duplicado.

**Critério de falha:** qualquer ação negada executada, efeito duplicado/indetectável, conclusão sem fonte oficial ou evidência essencial ausente deve impedir “aprovado”, com incidente/desvio registrado. Aplicar também os critérios gerais deste documento.

**Situação:** **procedimento redigido em minuta; execução NÃO realizada; sem resultado PASS/FAIL, sem evidência de sistema ou homologação.**


## 5. Rastreabilidade dos 16 invariantes aprovados — matriz inicial (não exaustiva)

| Invariante INT-R02 | HT com cobertura inicial planejada |
|---|---|
| INV-01 — Isolamento multiempresa | HT-01, HT-04, HT-13, HT-17 |
| INV-02 — Competência proprietária | HT-03, HT-09, HT-10 |
| INV-03 — Autorização contextual | HT-01, HT-02, HT-13, HT-15 |
| INV-04 — Aprovação diferente de execução | HT-10, HT-15 |
| INV-05 — Confirmação oficial | HT-05, HT-08, HT-09, HT-11 |
| INV-06 — Sem exactly-once/ACID distribuído | HT-08, HT-16 |
| INV-07 — Idempotência/concorrência/reconciliação | HT-05, HT-06, HT-08, HT-16 |
| INV-08 — Retentativa segura | HT-02, HT-08, HT-10, HT-18 |
| INV-09 — Compensação elegível/proprietária | HT-10, HT-15 |
| INV-10 — Pin e compatibilidade de versões | HT-03, HT-12, HT-18 |
| INV-11 — Sem código/SQL/URL livres | HT-01, HT-03, HT-12, HT-15 |
| INV-12 — n8n sem superprivilégio | HT-02, HT-13, HT-15 |
| INV-13 — IA consultiva, sem poder autoconcedido | HT-11, HT-15, HT-18 |
| INV-14 — Auditoria Geral fonte oficial | HT-13, HT-14, HT-17 |
| INV-15 — Políticas distintas para arquivo/estruturado | HT-07, HT-14, HT-17 |
| INV-16 — Limite comercial sem impedir controles essenciais | HT-04, HT-07, HT-17 |

## 6. Integração com outras decisões documentais

- [P0-SEC](P0-SEC-politicas-autorizacao-CT02.md): autorizações e revalidação por operação, aceita como **base documental**, com perfis/alçadas em aberto.
- [P0-VER](P0-VER-matriz-compatibilidade-evolucao-versoes.md): compatibilidade e preservação de versões/instâncias, aceita como **base documental**, com critérios exatos ainda pendentes.
- [P0-CON CT-03/04](P0-CON-CT03-CT04-comandos-resultados-transicoes.md): comandos/resultado/reconciliação **aprovados como base documental**, sem schemas físicos aprovados.
- [P0-OP24](P0-OP24-detalhamento-CT03-CT04-por-operacao.md): operações representativas aceitas como base de trabalho; não são o catálogo integral do CRM.
- [IN-118](../IN-118-central-backup-portabilidade-dados.md): portabilidade essencial por autoatendimento em todos os planos, inclusive Básico.
- [IN-124](../IN-124-integracao-inteligente-formularios-leads.md), [IN-148](../IN-148-reconciliacao-inteligente-leads.md), [IN-149](../IN-149-protocolo-entrega-confirmacao-leads.md): recepção durável em API ≠ entrega confirmada ao CRM; retenção especializada de filas de leads não vira norma de todas as INTs.
- [IN-151](../IN-151-gestao-inteligente-credenciais-chaves-integracao.md), [IN-167](../IN-167-consolidacao-arquitetura-agentes-tecnicos.md), [IN-168](../IN-168-matriz-pendencias-fechamento-arquitetura.md): segredos centralizados e agentes limitados por playbooks/identidades/autorização independente.
- [IN-159](../IN-159-central-inteligente-observabilidade-saude-operacional.md), [IN-160](../IN-160-gestao-inteligente-logs-metricas-rastreamento-distribuido.md), [IN-162](../IN-162-politica-inteligente-retencao-armazenamento-logs.md): INT-10 reutiliza observabilidade central; INT-12 utiliza Auditoria Geral, sem confundir retenção de logs técnicos e evidências de negócio.

## 7. Requisitos adicionais para fechar P0-ACC-01 e os gates G01–G06

| Item | Pendência | Situação |
|---|---|---|
| ACC-P01 | Conferir todos os 18 planos contra texto original integral das aprovações e seus requisitos TR completos | **Pendente** |
| ACC-P02 | Definir ambiente futuro isolado, provedores de teste, dados, permissões, versões e responsáveis | **Pendente de especificação** |
| ACC-P03 | Definir parâmetros exatos e critérios técnicos por operação, com aprovação das alçadas aplicáveis | **Pendente** |
| ACC-P04 | Formalizar captura/armazenamento de evidências e política de retenção adequada | **Pendente** |
| ACC-P05 | Identificar owner e aprovador de cada HT (inclusive dos módulos proprietários e Segurança) | **Pendente** |
| ACC-P06 | Revisar cobertura integral de TR/INV/CT/OP, incluindo requisitos não contemplados pelas 53 linhas iniciais | **Pendente** |
| ACC-P07 | Obter aceite documental expresso dos procedimentos e suas revisões | **Pendente** |
| ACC-P08 | Execução futura de testes, evidências, aceites e regressões — **não autorizada agora** | **Não executada** |

**Fechamento deste documento:** planos HT-01 a HT-18 **redigidos e versionados como minutas**, ainda não homologados, não aprovados como procedimento definitivo, nem executados. **P0-ACC-01 continua parcialmente atendida/aberta** até a validação documental integral e a execução técnica futura autorizada. Os critérios G01 a G06 permanecem pendentes do fechamento de todo o módulo, sem antecipação de programação.
