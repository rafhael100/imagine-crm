# P0-OP24 — Contratos CT-03/CT-04 detalhados por operação

**Data:** 2026-11-02. **Fase:** planejamento e documentação somente.
**Estado:** **proposta técnica documental para revisão**, após aprovação pelo responsável da especificação CT-03/CT-04 enquanto base de arquitetura. Esta lista de 24 casos refina os 12 casos anteriores; **não são 24 novas funcionalidades/INTs e não é inventário exaustivo das operações aprovadas**.
**Decisão de origem:** [aprovação documental CT-03/CT-04](P0-DEC-aprovacao-CT03-CT04.md). **Fonte de estrutura:** [CT-03/04 aprovado como base documental](P0-CON-CT03-CT04-comandos-resultados-transicoes.md), [P0-OP de 12 casos](P0-OP-casos-operacionais-contratos-alcadas.md), [P0-TR53](P0-TR53-vinculo-contratos-aceite.md), [INT-R01/R02](INT-R01-governanca-contratos-homologacao.md).
**Premissa de rigor:** o fato de existir um caso ou ID de contrato não significa endpoint, job, interface, microsserviço, permissão, política numérica ou código aprovado/implementado. A legalidade, segurança e semântica definitiva dependem dos módulos proprietários e da revisão final das propostas originais.

## 1. Regras que se aplicam a todos os casos
- Cada comando **CT-03** pertence a um tenant, operação lógica, recurso/efeito, proprietário, versão, contexto/identidade, finalidade, permissões e pré-condições verificadas. Nem toda consulta exige escrita ou todos os campos conceituais; obrigatoriedade por operação continua pendente.
- O resultado **CT-04** separa transporte, processamento, fato de negócio e reconciliação. **Resposta HTTP 200/202, webhook ou n8n concluído não é confirmação de negócio**.
- Retentativas preservam identidade de pedido/comando e registram tentativas distintas; uma escrita transmitida com resposta desconhecida não pode ser repetida sem reconciliação apropriada.
- INT-02/Identidade e Segurança e Central de Aprovações governam autorizações; INT-11 homologa versões; proprietários governam efeitos; INT-10 monitora/recupera; INT-12 correlaciona via Auditoria Geral.
- Sem SQL, URL ou scripts livres, identidade entre tenants intercambiável, cofre de segredos paralelo, central de eventos/observabilidade/auditoria paralela ou decisão financeira/contratual/crítica concedida a IA.
- Limites numéricos e políticas por provedor/contrato, escopos por perfil/campo, schemas executáveis, erros, retenção, timeouts e compatibilidade ficam **pendentes**.

## 2. Índice dos 24 casos de revisão

| Código de trabalho | Operação representativa | INT | Natureza do ato | Referência |
|---|---|---|---|---|
| OP-01-CAD | Cadastrar ou atualizar conexão | INT-01 | configuração de conexão; não altera registros de negócio | [Detalhe](#op-01-cad) |
| OP-01-CIC | Suspender/desativar conexão | INT-01 | controle de elegibilidade de operações novas | [Detalhe](#op-01-cic) |
| OP-02-AUT | Vincular ou renovar autorização externa | INT-02 | autorização/credencial do provedor, não privilégios internos livres | [Detalhe](#op-02-aut) |
| OP-02-REV | Revogar autorização ou credencial | INT-02 | impede novas operações protegidas, sem reverter automaticamente efeitos anteriores | [Detalhe](#op-02-rev) |
| OP-03-VAL | Validar esquema ou prévia de transformação | INT-03 | validação sem escrita em registro oficial | [Detalhe](#op-03-val) |
| OP-03-VER | Publicar nova versão de mapeamento | INT-03 | troca de versão disponível para operações futuras elegíveis | [Detalhe](#op-03-ver) |
| OP-04-ENV | Executar comando externo tipado | INT-04 | pedido de efeito externo; resultado de negócio separado | [Detalhe](#op-04-env) |
| OP-04-RSL | Registrar resultado técnico e encaminhar confirmação | INT-04 | atualiza rastreamento técnico, não o estado oficial alheio | [Detalhe](#op-04-rsl) |
| OP-05-APP | Registrar ou alterar aplicação integrada | INT-05 | configuração de acesso externo por tenant/escopo/plano | [Detalhe](#op-05-app) |
| OP-05-API | Invocar operação pela API pública | INT-05 | pode solicitar ato de negócio via contrato permitido | [Detalhe](#op-05-api) |
| OP-06-IN | Receber e validar webhook externo | INT-06 | ingresso de evento externo, não mutação automática de fato oficial | [Detalhe](#op-06-in) |
| OP-06-OUT | Entregar webhook externo autorizado | INT-06 | notificação técnica ao destino externo autorizado | [Detalhe](#op-06-out) |
| OP-07-SYNC | Sincronizar alterações incrementais | INT-07 | movimentação contínua de alterações autorizadas de dados | [Detalhe](#op-07-sync) |
| OP-07-RET | Retomar sincronização interrompida | INT-07 | continuação controlada, não recriação indiscriminada de escritas | [Detalhe](#op-07-ret) |
| OP-08-IMP | Importar ou migrar conjunto delimitado | INT-08 | escritas em lote com aceite por registro/entidade | [Detalhe](#op-08-imp) |
| OP-08-EXP | Exportar dados autorizados ou produzir pacote delimitado | INT-08 | leitura/extração auditável, não troca automática de vinculação | [Detalhe](#op-08-exp) |
| OP-09-INI | Instanciar e executar processo multissistema | INT-09 | coordenação de comandos de diferentes proprietários | [Detalhe](#op-09-ini) |
| OP-09-COM | Solicitar e acompanhar compensação tipada | INT-09 | reversão possível e específica de efeito anterior autorizado | [Detalhe](#op-09-com) |
| OP-10-DIA | Detectar e diagnosticar incidente de integração | INT-10 | observabilidade técnica, sem escrita de negócio | [Detalhe](#op-10-dia) |
| OP-10-REC | Aplicar recuperação técnica homologada | INT-10 | correção técnica escopada; não compensa negócio unilateralmente | [Detalhe](#op-10-rec) |
| OP-11-HOM | Homologar e publicar versão de conector | INT-11 | governança de artefato/versionamento, não autorização por tenant | [Detalhe](#op-11-hom) |
| OP-11-SUS | Suspender/revogar/descontinuar versão homologada | INT-11 | restringe novos usos da versão; trata conexões afetadas | [Detalhe](#op-11-sus) |
| OP-12-INV | Investigar operação com correlação de evidências | INT-12 | consulta especializada, não mudança de fatos oficiais | [Detalhe](#op-12-inv) |
| OP-12-AUD | Exportar evidência de auditoria sob controle | INT-12 | extração documentada de dados de auditoria, sem alterar fatos | [Detalhe](#op-12-aud) |

## 3. Fichas detalhadas por operação
### INT-01

#### OP-01-CAD — Cadastrar ou atualizar conexão

**Natureza do efeito:** configuração de conexão; não altera registros de negócio.  
**Propriedade e confirmação:** INT-01 cadastra vínculo; INT-11 certifica conector; INT-02 controla credenciais.  
**Autoridade contextual requerida (sem perfil novo presumido):** Administrador ou identidade técnica com permissão específica para conexão e tenant; escopo do provedor/conector compatível.  
**Pré-condições para CT-03:** Tenant e conta de destino válidos, capacidade/versão homologadas, propriedades e vínculos revisados, autorizações vigentes.  
**Resultado CT-04 e evidência oficial:** Conexão cadastrada/alterada com versão, referência e status próprio; não é acesso automaticamente liberado.  
**Identidade lógica, idempotência e concorrência:** Evitar duas conexões por repetição do mesmo pedido lógico; alteração de vínculo deve usar versão/estado esperado.  
**Falha, suspensão ou estado inconclusivo:** Vínculo incompatível, credencial revogada ou capacidade suspensa → negar/suspender; preservar registros de mudança.  
**Tratamento de reconciliação/recuperação:** Consultar cadastro oficial e dependências ativas; não redirecionar operações já recebidas para outro CRM.  
**Famílias documentais associadas:** CT-01/02/03/04/11; **TR**: TR-INT01-001..004; **HT**: HT-01/13 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-01-CIC — Suspender/desativar conexão

**Natureza do efeito:** controle de elegibilidade de operações novas.  
**Propriedade e confirmação:** INT-01 para estado da conexão; cada proprietário para efeitos de negócio pendentes.  
**Autoridade contextual requerida (sem perfil novo presumido):** Ator habilitado a suspender, com eventual aprovação conforme risco e impacto.  
**Pré-condições para CT-03:** Impactos e filas pendentes identificados; estado/versionamento e escopo de tenant compatíveis.  
**Resultado CT-04 e evidência oficial:** Conexão com estado restrito/suspenso/desativado; sem pressupor cancelamento de ações já aplicadas.  
**Identidade lógica, idempotência e concorrência:** Mesmo pedido de suspensão sem efeitos duplicados; guarda de motivo/versão.  
**Falha, suspensão ou estado inconclusivo:** Tentativas de continuar a usar a conexão devem ser bloqueadas; incertezas em comandos enviados persistem.  
**Tratamento de reconciliação/recuperação:** INT-10 identifica pendências técnicas; proprietário decide compensação ou conclusão de efeitos.  
**Famílias documentais associadas:** CT-01/02/04/09/11; **TR**: TR-INT01-002..004; **HT**: HT-18/08 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-02

#### OP-02-AUT — Vincular ou renovar autorização externa

**Natureza do efeito:** autorização/credencial do provedor, não privilégios internos livres.  
**Propriedade e confirmação:** INT-02; Identidade e Segurança conserva identidades e permissões; cofre IN-151 mantém segredos.  
**Autoridade contextual requerida (sem perfil novo presumido):** Ator e fluxo de autorização legítimos, escopos mínimos, consentimento quando aplicável; aprovação competente se exigida.  
**Pré-condições para CT-03:** Provedor e conexão identificados, finalidade e expiração compatíveis; nenhuma chave exposta à UI ou à IA.  
**Resultado CT-04 e evidência oficial:** Referência segura da autorização/escopo/vigência, nunca conteúdo bruto de segredo em log.  
**Identidade lógica, idempotência e concorrência:** Evitar duplicação de vínculo para o mesmo escopo; versionar renovação e histórico.  
**Falha, suspensão ou estado inconclusivo:** Expiração, acesso negado ou permissão excessiva → não prosseguir com operação protegida.  
**Tratamento de reconciliação/recuperação:** Consulta ao estado autorizado do provedor/cofre; eventual recuperação só por fluxo legítimo.  
**Famílias documentais associadas:** CT-01/02/04/11; **TR**: TR-INT02-001..004; **HT**: HT-01/02 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-02-REV — Revogar autorização ou credencial

**Natureza do efeito:** impede novas operações protegidas, sem reverter automaticamente efeitos anteriores.  
**Propriedade e confirmação:** INT-02/Identidade e Segurança; provedor confirma revogação externa quando aplicável.  
**Autoridade contextual requerida (sem perfil novo presumido):** Ator competente, razão e autorização de contenção/alteração conforme risco e política.  
**Pré-condições para CT-03:** Identificar tenant, conexão, credencial, escopos e instâncias afetadas.  
**Resultado CT-04 e evidência oficial:** Revogação com estado e alcance comprovados; em caso remoto inconclusivo, não alegar revogação integral.  
**Identidade lógica, idempotência e concorrência:** Mesmo pedido de revogação é estável por identidade/escopo; tentativas são auditadas.  
**Falha, suspensão ou estado inconclusivo:** Falha de provedor → restringir novos usos locais, investigar estado externo.  
**Tratamento de reconciliação/recuperação:** INT-10 acompanha recuperação, mas não amplia privilégios; efeitos pendentes conservados.  
**Famílias documentais associadas:** CT-02/04/09/11; **TR**: TR-INT02-002..004; **HT**: HT-02/13 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-03

#### OP-03-VAL — Validar esquema ou prévia de transformação

**Natureza do efeito:** validação sem escrita em registro oficial.  
**Propriedade e confirmação:** INT-03 valida; módulo proprietário aceita/rejeita eventual efeito de dados.  
**Autoridade contextual requerida (sem perfil novo presumido):** Identidade autorizada a acessar campos sob finalidade, com ocultação dos sensíveis.  
**Pré-condições para CT-03:** Origem/destino e versões de esquema conhecidos; dados mínimos adequados.  
**Resultado CT-04 e evidência oficial:** Prévia/validação documentada com erros e referência de mapeamento; sem afirmar persistência.  
**Identidade lógica, idempotência e concorrência:** Mesmo esquema/dados/versão gera operação de validação rastreável; não exige efeito de escrita.  
**Falha, suspensão ou estado inconclusivo:** Campo incompatível ou ambíguo → recusa da validação, nunca coerção destrutiva silenciosa.  
**Tratamento de reconciliação/recuperação:** Corrigir proposta de mapeamento pela competência; reavaliar sem aplicar no domínio.  
**Famílias documentais associadas:** CT-02/03/04/07; **TR**: TR-INT03-001..003; **HT**: HT-03/07 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-03-VER — Publicar nova versão de mapeamento

**Natureza do efeito:** troca de versão disponível para operações futuras elegíveis.  
**Propriedade e confirmação:** INT-03 publica esquema; módulo proprietário controla uso por domínio e instância.  
**Autoridade contextual requerida (sem perfil novo presumido):** Permissão/ aprovação de alteração relevante conforme risco.  
**Pré-condições para CT-03:** Compatibilidade e amostras verificadas; impactos em sync/importações identificados.  
**Resultado CT-04 e evidência oficial:** Versão publicada e referenciada; instâncias em andamento preservam versão contratada até migração legítima.  
**Identidade lógica, idempotência e concorrência:** Publicar a mesma versão não replica ou troca silenciosamente objetos.  
**Falha, suspensão ou estado inconclusivo:** Versão incompatível → não promover operação ativa; manter origem versionada.  
**Tratamento de reconciliação/recuperação:** Reverter disponibilização conforme fluxo aprovado; não desfazer efeito já aplicado sem proprietário.  
**Famílias documentais associadas:** CT-02/03/04/06/07; **TR**: TR-INT03-001/004; **HT**: HT-03/12 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-04

#### OP-04-ENV — Executar comando externo tipado

**Natureza do efeito:** pedido de efeito externo; resultado de negócio separado.  
**Propriedade e confirmação:** INT-04 transmite; módulos de negócio mantêm decisão/ confirmação.  
**Autoridade contextual requerida (sem perfil novo presumido):** Identidade válida, operação homologada, conexão e escopo autorizados, aprovação quando exigida.  
**Pré-condições para CT-03:** Contrato/esquema/versão, estado esperado, chave de idempotência e destino verificados.  
**Resultado CT-04 e evidência oficial:** CT-03 registra comando; CT-04 comprova enviado/aceito/aplicado quando houver evidência.  
**Identidade lógica, idempotência e concorrência:** Chave por tenant/pedido/efeito; retry apenas se contrato do provedor sustentar segurança.  
**Falha, suspensão ou estado inconclusivo:** Timeout após transmissão → inconclusivo; não repetir escrita perigosa.  
**Tratamento de reconciliação/recuperação:** INT-10 consulta fonte sem alterar fatos; proprietário confirma resultado.  
**Famílias documentais associadas:** CT-01/02/03/04/09/11; **TR**: TR-INT04-001..004; **HT**: HT-08/12 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-04-RSL — Registrar resultado técnico e encaminhar confirmação

**Natureza do efeito:** atualiza rastreamento técnico, não o estado oficial alheio.  
**Propriedade e confirmação:** INT-04 registra evidência de adaptador; módulo proprietário confirma fato.  
**Autoridade contextual requerida (sem perfil novo presumido):** Executor/origem legítimos; resultado verificável e correlacionado com comando.  
**Pré-condições para CT-03:** Comando e versão existentes, autenticidade do retorno, estado anterior e causalidade.  
**Resultado CT-04 e evidência oficial:** CT-04 distingue aceite/efeito técnico do ato oficial; preserva parcial/inconclusivo.  
**Identidade lógica, idempotência e concorrência:** Não reprocessar resultados duplicados nem substituir confirmação oficial por status remoto.  
**Falha, suspensão ou estado inconclusivo:** Evento tardio ou contraditório → registrar divergência e investigar.  
**Tratamento de reconciliação/recuperação:** Consultar comprovantes e vincular confirmação emitida pelo proprietário.  
**Famílias documentais associadas:** CT-03/04/09/11; **TR**: TR-INT04-002/003; **HT**: HT-08/14 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-05

#### OP-05-APP — Registrar ou alterar aplicação integrada

**Natureza do efeito:** configuração de acesso externo por tenant/escopo/plano.  
**Propriedade e confirmação:** INT-05 governa aplicação; Segurança e INT-02 autorizam; INT-11 conecta homologação se aplicável.  
**Autoridade contextual requerida (sem perfil novo presumido):** Administrador autorizado a vincular aplicação e contratar capacidade se houver política comercial válida.  
**Pré-condições para CT-03:** Tenant elegível e plano compatível; escopos mínimos e identidade da aplicação.  
**Resultado CT-04 e evidência oficial:** Cadastro/versionamento do app; nenhuma permissão implícita a dados de negócio.  
**Identidade lógica, idempotência e concorrência:** Evitar registros duplicados; rotacionar autorização sem expor segredo.  
**Falha, suspensão ou estado inconclusivo:** Trial/versão/permissão incompatível → bloquear acesso à API pública.  
**Tratamento de reconciliação/recuperação:** Identificar aplicações e autorizações afetadas sem conceder permissões autônomas.  
**Famílias documentais associadas:** CT-01/02/04/11; **TR**: TR-INT05-001..003; **HT**: HT-04/13 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-05-API — Invocar operação pela API pública

**Natureza do efeito:** pode solicitar ato de negócio via contrato permitido.  
**Propriedade e confirmação:** INT-05 controla fronteira API; INT-04 operacionaliza; proprietário confirma efeito.  
**Autoridade contextual requerida (sem perfil novo presumido):** Aplicação/cliente autorizado e tenant válido; API pública desabilitada no trial.  
**Pré-condições para CT-03:** Plano, quota, versão de API, operação tipada, recurso/escopo e estado esperado.  
**Resultado CT-04 e evidência oficial:** Aceite/execução técnica distintos da confirmação de módulo de negócio.  
**Identidade lógica, idempotência e concorrência:** Retentativa deve preservar intenção e não duplicar efeitos.  
**Falha, suspensão ou estado inconclusivo:** Revogado, quota, plano/trial ou autorização → negar; não impedir integrações nativas autorizadas.  
**Tratamento de reconciliação/recuperação:** Consulta de operação oficial e logs sem acesso a outros tenants.  
**Famílias documentais associadas:** CT-02/03/04/11; **TR**: TR-INT05-001..004; **HT**: HT-04/13 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-06

#### OP-06-IN — Receber e validar webhook externo

**Natureza do efeito:** ingresso de evento externo, não mutação automática de fato oficial.  
**Propriedade e confirmação:** INT-06 ingressa; Motor Central de Eventos governa evento interno; proprietário aplica efeito.  
**Autoridade contextual requerida (sem perfil novo presumido):** Autenticidade/origem/assinatura, tenant e provedor vinculados.  
**Pré-condições para CT-03:** Versão e assinatura elegíveis, replay/deduplicação, escopo e causalidade.  
**Resultado CT-04 e evidência oficial:** Recebimento validado e encaminhamento técnico; confirmação de negócio em fonte proprietária.  
**Identidade lógica, idempotência e concorrência:** Mesmo evento lógico não provoca processamento duplicado do efeito.  
**Falha, suspensão ou estado inconclusivo:** Replay, assinatura inválida, fora de ordem → rejeitar/segregar/investigar, conforme política.  
**Tratamento de reconciliação/recuperação:** Relacionar eventos com comando/instância; recuperar lacunas sem afirmar conclusão.  
**Famílias documentais associadas:** CT-02/04/05/09/11; **TR**: TR-INT06-001..004; **HT**: HT-05/16 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-06-OUT — Entregar webhook externo autorizado

**Natureza do efeito:** notificação técnica ao destino externo autorizado.  
**Propriedade e confirmação:** INT-06 entrega; COM para comunicações dirigidas a clientes.  
**Autoridade contextual requerida (sem perfil novo presumido):** Tenant, assinante e evento elegíveis; finalidade e campos do payload autorizados.  
**Pré-condições para CT-03:** Inscrição válida, URL de destino registrada/homologada, versão, minimização e política de fila.  
**Resultado CT-04 e evidência oficial:** Entrega tentada/aceita tecnicamente; sem presumir processamento de negócio do destinatário.  
**Identidade lógica, idempotência e concorrência:** Mesmo evento/assinatura mantém chave de entrega; tentativas separadas.  
**Falha, suspensão ou estado inconclusivo:** Timeout/4xx/5xx conforme contrato → retentativas seguras e limitadas ou suspensão.  
**Tratamento de reconciliação/recuperação:** Acompanhar status da entrega; nunca reenviar efeitos de negócio indevidos.  
**Famílias documentais associadas:** CT-02/04/05/09; **TR**: TR-INT06-002..004; **HT**: HT-05/16 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-07

#### OP-07-SYNC — Sincronizar alterações incrementais

**Natureza do efeito:** movimentação contínua de alterações autorizadas de dados.  
**Propriedade e confirmação:** INT-07 gerencia checkpoints; cada módulo proprietário controla seus campos.  
**Autoridade contextual requerida (sem perfil novo presumido):** Fonte/destino, entidade/campo, propósito e conexão com autorização vigente.  
**Pré-condições para CT-03:** Versão, cursor inicial, filtros, política de conflito e autoridade por campo.  
**Resultado CT-04 e evidência oficial:** Checkpoint de itens aplicados/confirmados; divergências e pendências permanecem explícitas.  
**Identidade lógica, idempotência e concorrência:** Mesma mudança lógica não gera segunda escrita; eventos fora de ordem preservam causalidade.  
**Falha, suspensão ou estado inconclusivo:** Incerteza de gravação → não avançar cursor além do estado comprovado.  
**Tratamento de reconciliação/recuperação:** Consulta de dados e efeitos por fonte autorizada; comparação de versões e confirmação.  
**Famílias documentais associadas:** CT-02/03/04/06/09; **TR**: TR-INT07-001..004; **HT**: HT-06/08 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-07-RET — Retomar sincronização interrompida

**Natureza do efeito:** continuação controlada, não recriação indiscriminada de escritas.  
**Propriedade e confirmação:** INT-07 coordena cursor; INT-10 auxilia incidente; proprietário atesta dados.  
**Autoridade contextual requerida (sem perfil novo presumido):** Revalidação de conexão, escopo, versão e identidades após pausa.  
**Pré-condições para CT-03:** Checkpoint durável e último estado oficialmente comprovado; conflitos pendentes identificados.  
**Resultado CT-04 e evidência oficial:** Retomada com progresso e rastreamento de versão, sem afirmar correção de todos registros.  
**Identidade lógica, idempotência e concorrência:** Preservar IDs e chaves da operação lógica; cada tentativa nova é registrada.  
**Falha, suspensão ou estado inconclusivo:** Cursor inconsistente/versão suspensa → bloquear e escalar.  
**Tratamento de reconciliação/recuperação:** Reconstruir posição confiável a partir de evidências e módulos proprietários.  
**Famílias documentais associadas:** CT-02/04/06/09; **TR**: TR-INT07-002..004; **HT**: HT-06/16 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-08

#### OP-08-IMP — Importar ou migrar conjunto delimitado

**Natureza do efeito:** escritas em lote com aceite por registro/entidade.  
**Propriedade e confirmação:** INT-08 administra operação; Arquivos central armazena; módulo proprietário grava e confirma.  
**Autoridade contextual requerida (sem perfil novo presumido):** Cliente autorizado; plano elegível conforme regras aprovadas (inclui Básico para portabilidade essencial).  
**Pré-condições para CT-03:** Arquivo/manifesto válido, mapeamento, prévia sem escrita, autorização, análise de conflitos, versão.  
**Resultado CT-04 e evidência oficial:** Lotes com estados individualizados e confirmação de dados efetivos, não total automático.  
**Identidade lógica, idempotência e concorrência:** Reprocessar somente itens não confirmados ou reconciliados, mantendo IDs estáveis.  
**Falha, suspensão ou estado inconclusivo:** Lote parcial, duplicidade ou esquema incompatível → suspender/apontar/recuperar sem mesclagem destrutiva.  
**Tratamento de reconciliação/recuperação:** Reconciliar por registro e referência proprietária; migração entre contas exige autorização de ambas conforme política.  
**Famílias documentais associadas:** CT-02/03/04/07/09/11; **TR**: TR-INT08-001..005; **HT**: HT-07/13 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-08-EXP — Exportar dados autorizados ou produzir pacote delimitado

**Natureza do efeito:** leitura/extração auditável, não troca automática de vinculação.  
**Propriedade e confirmação:** INT-08 exporta dados tipados; IN-118 é Central de Backup e Portabilidade; domínios detêm dados.  
**Autoridade contextual requerida (sem perfil novo presumido):** Ator/perfil com permissão sobre dados e período; proteção contra exportação fora do tenant.  
**Pré-condições para CT-03:** Entidades/campos/escopo e formato compatíveis, política de sensibilidade e arquivo temporário seguro.  
**Resultado CT-04 e evidência oficial:** Pacote/arquivo com inventário e rastreabilidade, sem copiar segredos ou cobranças de outro contrato.  
**Identidade lógica, idempotência e concorrência:** Repetir solicitação pode gerar artefato distinto sem novo efeito de negócio; auditoria por solicitação.  
**Falha, suspensão ou estado inconclusivo:** Cota de anexos cheia não bloqueia exportação de dados já existentes; limites técnicos de processamento pendentes.  
**Tratamento de reconciliação/recuperação:** Conferir integridade do pacote e manifestações de completude; restituição/backup integral pertence à IN-118.  
**Famílias documentais associadas:** CT-02/04/07/11; **TR**: TR-INT08-001/004/005; **HT**: HT-07/17 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-09

#### OP-09-INI — Instanciar e executar processo multissistema

**Natureza do efeito:** coordenação de comandos de diferentes proprietários.  
**Propriedade e confirmação:** INT-09 coordena; cada módulo proprietário decide e confirma seu ato.  
**Autoridade contextual requerida (sem perfil novo presumido):** Modelo homologado, identidade, tenant, aprovações e escopos por etapa válidos.  
**Pré-condições para CT-03:** Versão congelada de modelo/instância, dependências, pré-condições e limite de repetição.  
**Resultado CT-04 e evidência oficial:** Estado de instância/etapa separado dos resultados oficiais dos comandos.  
**Identidade lógica, idempotência e concorrência:** Cada comando tem identidade lógica e tentativas diferenciadas; não repetir efeito só por falha do orquestrador.  
**Falha, suspensão ou estado inconclusivo:** Etapa inconclusiva → suspender ramo dependente, reconciliar sem avançar por mera confirmação técnica.  
**Tratamento de reconciliação/recuperação:** INT-10 ajuda diagnóstico, proprietários confirmam fatos e INT-12 correlaciona evidências.  
**Famílias documentais associadas:** CT-02/03/04/08/09/11; **TR**: TR-INT09-001..004; **HT**: HT-08/09 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-09-COM — Solicitar e acompanhar compensação tipada

**Natureza do efeito:** reversão possível e específica de efeito anterior autorizado.  
**Propriedade e confirmação:** INT-09 coordena; FIN/CON/AGD e outros proprietários decidem, executam e confirmam seu efeito.  
**Autoridade contextual requerida (sem perfil novo presumido):** Elegibilidade, reversibilidade, contexto, alçada e aprovação particular da compensação.  
**Pré-condições para CT-03:** Efeito original identificado, estado atual verificado, versões/risco/conexões válidos.  
**Resultado CT-04 e evidência oficial:** Compensação autorizada, solicitada, aplicada/confirmada por proprietário ou parcial/inconclusiva.  
**Identidade lógica, idempotência e concorrência:** Mesma compensação lógica não cria estornos/cancelamentos duplicados.  
**Falha, suspensão ou estado inconclusivo:** Compensação falha ou parcial → manter efeitos e pendência; sem rollback global.  
**Tratamento de reconciliação/recuperação:** Consulta oficial sobre efeito original e reversão, com cadeia causal e auditoria.  
**Famílias documentais associadas:** CT-02/03/04/08/09/10/11; **TR**: TR-INT09-004..006; **HT**: HT-10/15 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-10

#### OP-10-DIA — Detectar e diagnosticar incidente de integração

**Natureza do efeito:** observabilidade técnica, sem escrita de negócio.  
**Propriedade e confirmação:** INT-10 investiga; IN-159/160 fornece observabilidade; Central de Incidentes existente.  
**Autoridade contextual requerida (sem perfil novo presumido):** Identidade de diagnóstico e acesso a dados minimizados, isolados por tenant.  
**Pré-condições para CT-03:** Telemetria com instante/fonte, correlação com comandos/filas, sem tomar ausência como saudável.  
**Resultado CT-04 e evidência oficial:** Incidente, hipóteses e evidências separadas; sem declarar falha de negócio não comprovada.  
**Identidade lógica, idempotência e concorrência:** Agrupar sinais do mesmo incidente conforme correlação, preservando eventos distintos.  
**Falha, suspensão ou estado inconclusivo:** Telemetria ausente → estado desconhecido; escalar, não inventar normalização.  
**Tratamento de reconciliação/recuperação:** Consultar eventos e fontes oficiais e encaminhar ação recuperadora autorizada.  
**Famílias documentais associadas:** CT-02/04/09/11; **TR**: TR-INT10-001/004/005; **HT**: HT-11/16 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-10-REC — Aplicar recuperação técnica homologada

**Natureza do efeito:** correção técnica escopada; não compensa negócio unilateralmente.  
**Propriedade e confirmação:** INT-10 gerencia recuperação; proprietários validam quaisquer efeitos de negócio.  
**Autoridade contextual requerida (sem perfil novo presumido):** Playbook específico seguro/homologado com alçada aplicável; ação crítica exige aprovação humana competente.  
**Pré-condições para CT-03:** Estado/versão/risco/restrições de tenant, idempotência e efeito da ação confirmados.  
**Resultado CT-04 e evidência oficial:** Saúde técnica restabelecida/verificada com evidência; operações pendentes continuam pendentes se não confirmadas.  
**Identidade lógica, idempotência e concorrência:** Ação repetida exige garantia de não duplicar efeito; tentativa registrada.  
**Falha, suspensão ou estado inconclusivo:** Inconclusão ou risco de escrita duplicada → interromper e reconciliar/escalar.  
**Tratamento de reconciliação/recuperação:** Confirmar recuperação por fonte real, não só suposição de IA; INT-12 audita.  
**Famílias documentais associadas:** CT-02/03/04/09/11; **TR**: TR-INT10-002..005; **HT**: HT-08/11 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-11

#### OP-11-HOM — Homologar e publicar versão de conector

**Natureza do efeito:** governança de artefato/versionamento, não autorização por tenant.  
**Propriedade e confirmação:** INT-11 mantém catálogo; Segurança e Aprovações revisam quando exigido.  
**Autoridade contextual requerida (sem perfil novo presumido):** Revisor/publicador autorizado, testes/avaliação de segurança e aprovação humana pertinente.  
**Pré-condições para CT-03:** Pacote identificado, origem, compatibilidade, testes isolados, riscos e dependências verificados.  
**Resultado CT-04 e evidência oficial:** Homologação/publicação da versão exata, com histórico e condições; não habilita conexões automaticamente.  
**Identidade lógica, idempotência e concorrência:** Versão já aprovada não duplica publicação; nova versão requer novo ciclo próprio.  
**Falha, suspensão ou estado inconclusivo:** Artefato inseguro/incompatível → reprovar/restringir, sem execução de código livre em tenant.  
**Tratamento de reconciliação/recuperação:** Tratar impacto sobre versões atuais; não atualizar instâncias silenciosamente.  
**Famílias documentais associadas:** CT-01/02/04/11; **TR**: TR-INT11-001..003; **HT**: HT-01/12 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-11-SUS — Suspender/revogar/descontinuar versão homologada

**Natureza do efeito:** restringe novos usos da versão; trata conexões afetadas.  
**Propriedade e confirmação:** INT-11 governa catálogo; INT-01 conexões e INT-02 credenciais.  
**Autoridade contextual requerida (sem perfil novo presumido):** Autoridade e aprovação exigidas pelo risco/impacto, segurança e condições contratuais.  
**Pré-condições para CT-03:** Identificar versão, clientes/conexões impactadas e operações ativas.  
**Resultado CT-04 e evidência oficial:** Estado atualizado com data/alcance, auditoria e plano de transição; não reclassifica resultado passado.  
**Identidade lógica, idempotência e concorrência:** Mesma ordem de suspensão não cria migração em cascata não prevista.  
**Falha, suspensão ou estado inconclusivo:** Instâncias incompatíveis ou sem alternativa homologada permanecem bloqueadas/pendentes.  
**Tratamento de reconciliação/recuperação:** INT-10 diagnostica saúde; proprietários validam efeitos e alternativas homologadas.  
**Famílias documentais associadas:** CT-01/02/04/09/11; **TR**: TR-INT11-002/004; **HT**: HT-12/18 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

### INT-12

#### OP-12-INV — Investigar operação com correlação de evidências

**Natureza do efeito:** consulta especializada, não mudança de fatos oficiais.  
**Propriedade e confirmação:** INT-12 fornece visão; Auditoria Geral mantém fonte oficial.  
**Autoridade contextual requerida (sem perfil novo presumido):** Perfil, finalidade, tenant, dados sensíveis e acesso justificados e auditáveis.  
**Pré-condições para CT-03:** IDs e trilhas existentes, versões, autorizações, origem temporal verificáveis.  
**Resultado CT-04 e evidência oficial:** Linha do tempo de comandos, aprovações, tentativas, incidentes e confirmação oficial ou lacuna explícita.  
**Identidade lógica, idempotência e concorrência:** A consulta não repete efeitos de negócio; cada acesso é auditável.  
**Falha, suspensão ou estado inconclusivo:** Prova faltante → destacar lacuna/hipótese, não fabricar evidência.  
**Tratamento de reconciliação/recuperação:** INT-10 ou proprietário avaliam correção; INT-12 não resolve efeitos por autoridade própria.  
**Famílias documentais associadas:** CT-02/04/09/11; **TR**: TR-INT12-001/002/005; **HT**: HT-14 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

#### OP-12-AUD — Exportar evidência de auditoria sob controle

**Natureza do efeito:** extração documentada de dados de auditoria, sem alterar fatos.  
**Propriedade e confirmação:** Auditoria Geral garante integridade/retenção; INT-12 fornece consulta/exportação.  
**Autoridade contextual requerida (sem perfil novo presumido):** Ator com permissão específica, finalidade, minimização, tenant, escopo e aprovações se exigidas.  
**Pré-condições para CT-03:** Dados elegíveis, classificação/retenção aplicáveis, mascaramento e finalidade de saída.  
**Resultado CT-04 e evidência oficial:** Pacote com proveniência e controle de acesso; exportação também registrada na Auditoria Geral.  
**Identidade lógica, idempotência e concorrência:** Repetir exportação é nova ação de acesso auditada, não mesma permissão ilimitada.  
**Falha, suspensão ou estado inconclusivo:** Falta de autorização ou retenção inaplicável → negar/limitar, sem ocultar decisão.  
**Tratamento de reconciliação/recuperação:** Revalidar integridade do artefato e preservação legal/contratual quando aplicável.  
**Famílias documentais associadas:** CT-02/04/11; **TR**: TR-INT12-003..005; **HT**: HT-14/17 (cenários planejados, não executados).

**Pendente antes de homologação documental:** esquemas e parâmetros por operação, ator autorizado exato, retentativas seguras e provas de idempotência, estados/transições por fonte, referência original da aprovação, critérios completos de teste e aceite.

## 4. Contratos de saída e guardrails de passagem

| Situação observada | Registro CT-04 permitido | O que permanece proibido |
|---|---|---|
| Transmissão sem resposta | Enviado e pendente/inconclusivo conforme evidência | Chamar a operação de rejeitada ou confirmada sem prova |
| HTTP 2xx/202 sem verificação do dono | Aceito tecnicamente (se o contrato provar aceite) | Confirmar ato FIN/CON/AGD/VEN |
| Webhook duplicado/tardio | Recebimento/evento correlacionado; possível investigação | Reaplicar automaticamente efeito de negócio |
| Resultado externo parcial | Cada efeito comprovado, pendências por recurso | Rollback universal presumido |
| Tentativa repetida da mesma operação | Novo attempt_ref, comando lógico persistente | Transformar retry em compra/lead/reserva distinta |
| Revogação durante fluxo | Restringir próximas ações; preservar evidência dos efeitos anteriores | Reativar escopo por atalho |
| Provedor restabelecido | Saúde técnica verificada e incidente atualizado | Encerrar automaticamente todos os comandos de negócio |
| Informação incompleta em auditoria | Lacuna identificada e hipótese claramente marcada | Inventar evidência ou apagar histórico |

## 5. Relacionamento com contratos, autorização e módulos
- **CT-01/02:** elegibilidade de conector, conexão e autorização; não constituem novo autorizador, nem cofre de segredos.
- **CT-03/04:** envelope e resultados compartilhados já aprovados documentalmente em suas diretrizes, agora mapeados como **propostas por operação**.
- **CT-05/06/07:** webhooks, checkpoints e lotes conservam máquinas de estado e limites próprios.
- **CT-08/09/10/11:** orquestração, recuperação, compensação e evidência técnica são consumidos sem sobrepor competência do dono de negócio.
- **Central de Portabilidade IN-118:** permite autoatendimento essencial inclusive no Básico; INT-08 só executa sua competência delimitada e nunca substitui toda a Central.
- **Política de retenção IN-162:** registros técnicos por categoria; não define automaticamente retenção de fatos de negócio/auditoria geral.

## 6. Checklist de revisão documental por operação
| Item | Necessário para finalizar a ficha OP | Situação atual |
|---|---|---|
| OP-A | Origem literal do requisito aprovado, escopo e exceções | Não conferido integralmente |
| OP-B | Responsável pelo efeito, confirmador oficial, auditoria e alçada | Proprietários indicados; granularidade pendente |
| OP-C | CT-03 e CT-04 com campos e obrigatoriedade condicionada | Famílias e semântica aprovadas; schema físico pendente |
| OP-D | Estado inicial/transições válidas, falhas e encerramento | Exemplos propostos; transições por entidade pendentes |
| OP-E | Garantias reais de idempotência/retry e concorrência | Diretrizes aprovadas; prova por provedor pendente |
| OP-F | Autorizações por perfil, tenant, recurso, campo, finalidade e MFA aplicável | Pendente |
| OP-G | Versionamento, compatibilidade e política de migração | Pendente por conector/instância |
| OP-H | Planos de teste e aceite ligados a HT-01..18 | Cenários propostos, não executados |

**Resultado:** 24 fichas documentais de operações representativas criadas para apreciação, sem alterar os requisitos funcionais originais. P0-DOC-01, P0-TR-01, P0-OWN-01, P0-CON-01, P0-SEC-01, P0-VER-01, P0-ACC-01 continuam abertos/parciais. Nenhuma programação ou teste real autorizado.
