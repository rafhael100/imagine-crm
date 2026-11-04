# IN-127 — Ratificações normativas R01 a R06 (Imagine Sites × CRM)

**Estado:** RATIFICADO EXPRESSAMENTE pelo responsável do projeto, decisão por decisão, em 03/11/2026.  
**Natureza:** exclusivamente especificação documental, funcional e arquitetural.  
**Abrangência:** Onepages/Landing Pages Imagine, API oficial de captação, API de contingência, filas de integração e entrega ao CRM Imagine.  
**Precedência:** nas seis matérias aqui ratificadas, esta consolidação posterior complementa e, quando houver incompatibilidade inequívoca, prevalece sobre redações anteriores de IN-124 a IN-129 e IN-147 a IN-149. Não revoga requisitos independentes nem concede autorização para execução.  
**Portões:** RF-PEND-01 (conferência completa de fontes) e RF-PEND-04 (homologação técnica) seguem ABERTOS. Nenhuma programação, teste, implantação, contratação ou homologação autorizada. Rollback automático AGI-11-RB01 DESABILITADO.

## Quadro de ratificações expressas

| Código | Alternativa ratificada | Decisão |
|---|---|---|
| IN127-R01 | B | T0 = primeira recepção durável oficialmente confirmada pelo backend Imagine |
| IN127-R02 | A | 72 horas iniciais + 360 horas complementares = 432 horas contínuas desde T0 |
| IN127-R03 | B | Esgotado o prazo sem entrega confirmada: encerrar processamento e excluir payload pessoal pendente das estruturas ativas e temporárias da Imagine; preservar registros já entregues ao CRM |
| IN127-R04 | B | Guardar exclusivamente metadados mínimos necessários à auditoria operacional por até 90 períodos de 24h após evento terminal aplicável |
| IN127-R05 | B | Reconciliação automática controlada; retentativa apenas com idempotência comprovada dentro de 432h; depois, consulta sem reenvio |
| IN127-R06 | B | Publicação estática permitida sem CRM, mas captação proibida sem destino oficial vinculado e autorizado, verificado no backend |

## IN127-R01 — Instante inicial

1. O instante T0 corresponde à primeira aceitação com **persistência durável confirmada** de uma submissão válida pelo backend oficial Imagine, seja na rota principal, seja na rota de contingência.
2. Clique do visitante, início de requisição, mera recepção de conexão e início de workflow não são T0.
3. Somente comunicar sucesso de recepção ao visitante depois da persistência durável; recepção na Imagine não comprova entrega ao CRM.
4. Retentativas, failover, reenfileiramento, reinício de serviço e reprocessamento devem preservar identidade e T0 originais.
5. Repetição idempotente da mesma submissão não cria novo prazo, evento lógico ou lead.

## IN127-R02 — Relógio contínuo

- Janela inicial: de T0 até imediatamente antes de **T0 + 72h**.
- Janela complementar de falhas: de T0 + 72h até imediatamente antes de **T0 + 432h** (mais 360h).
- A partir de T0 + 432h: prazo esgotado; não emitir **novas tentativas** de entrega.
- O relógio é contínuo, com 24h por dia; fins de semana, feriados, fusos de apresentação, queda de servidores, retentativas e mudanças de fila não suspendem nem reiniciam o prazo.
- Os serviços devem trabalhar com instantes inequívocos; a representação local da data não altera o vencimento.
- No limite, operações em curso ou resultados tardios devem seguir reconciliação segura, sem presumir cancelamento retroativo de entrega já efetivada no CRM.

## IN127-R03 — Encerramento e exclusão

1. Ao completar 432h sem entrega oficialmente confirmada, encerrar o processamento pendente, impedir novas tentativas e providenciar descarte seguro da **carga integral dos dados pessoais** nas estruturas **ativas e temporárias** de captação e entrega sob controle da Imagine.
2. O escopo inclui payload de formulário, cópias temporárias, filas, mensagens, réplicas e armazenamento auxiliar de integração identificáveis; não basta apagar somente a mensagem visível da fila.
3. **Não excluir por esta política** leads, contatos ou históricos já entregues ao CRM; seguem a política própria do CRM.
4. Backups protegidos e hipóteses legítimas de conservação obedecem a políticas específicas; não autorizam quarentena operacional informal, reenvio após vencimento nem retenção indefinida.
5. Disparo, processamento parcial, conclusão e exceções do descarte devem ser diferenciados; não declarar exclusão física instantânea quando ainda não comprovada.
6. Restaurar backup não pode ressuscitar um payload descartado no processamento ativo.
7. A exclusão de cópias temporárias de **leads entregues** após 24h da confirmação, aprovada anteriormente em IN-128, permanece regra complementar, sem exclusão do registro principal no CRM.

## IN127-R04 — Evidência mínima e retenção

1. Conservar somente os **metadados estritamente necessários** para auditoria operacional, com minimização, segregação por empresa, controle de acesso e exclusão no vencimento.
2. Retenção ordinária máxima: **90 períodos contínuos de 24 horas** contados do evento terminal aplicável (entrega oficialmente confirmada ou expiração da janela); confirmar detalhes de outras situações pelos contratos específicos.
3. Exemplos de metadados condicionados à necessidade: referência técnica da submissão, empresa/site, versão do formulário, T0, vencimentos, número de tentativas, categoria saneada da falha, situação terminal, referência de confirmação e prova mínima do descarte.
4. A evidência ordinária **não deve reproduzir** nome, telefone, e-mail, conteúdo do formulário, tokens, senhas ou respostas brutas contendo dados pessoais.
5. Correção auditável posterior não reinicia automaticamente os 90 dias.
6. Exceções só quando houver fundamento legítimo, finalidade e autorização documentados; não considerar 90 dias permissão genérica para outros logs, CRM ou backups.
7. Identificadores pseudonimizados continuam sujeitos à proteção aplicável quando permitirem vinculação a uma pessoa.

## IN127-R05 — Resultado incerto, reconciliação e confirmação tardia

1. Timeout ou resposta intermediária não é confirmação de falha nem de entrega. Um HTTP 2xx/202 que apenas aceitou trabalho assíncrono não comprova persistência no CRM.
2. Diante da incerteza, consultar o **estado oficial do CRM** e reconciliar identidade da submissão, destino autorizado, tentativa e resultado antes de repetir.
3. Permitir retentativa **somente dentro das 432h**, mediante segurança, idempotência efetiva no destino e prevenção de concorrência/duplicidade comprovadas; em ambiguidade não resolvida, bloquear repetição automática e escalar à equipe humana.
4. No vencimento sem confirmação, encerrar processamento e aplicar R03, distinguindo "esgotado sem confirmação" de "falha comprovada"; não declarar sucesso imaginado.
5. Após o vencimento, reconciliação apenas **consultiva**, com metadados mínimos autorizados, sem novo envio, restauração de payload descartado ou reinício da retenção R04.
6. Confirmação tardia pode corrigir a classificação auditável de uma entrega **efetivamente ocorrida antes do vencimento**, sem criar novo lead. Se foi entregue efetivamente após o limite, registrar ocorrência/incidente de prazo, sem reabrir a janela.
7. Idempotência técnica não autoriza mesclagem automática de submissões comerciais distintas com campos iguais.

## IN127-R06 — Site estático sem CRM e controle do destino

1. Onepages e Landing Pages **estáticas** podem ser publicadas mesmo sem CRM, desde que exista **aceite expresso** e todas as demais condições de publicação e privacidade pertinentes sejam satisfeitas.
2. É **proibida a ativação e o recebimento de formulários públicos** sem destino CRM oficialmente vinculado, autorizado, válido e integrado.
3. O backend oficial de captação deve validar autorização, empresa, site, formulário, vínculo, elegibilidade e condições aplicáveis **em cada aceitação**, inclusive requisições vindas de página antiga/cache. Ocultar formulário no frontend não é controle suficiente.
4. Sem destino autorizado, não aceitar uma submissão como lead recebido, não iniciar T0 nem exibir sucesso; não armazenar payload clandestinamente no Portal, nem encaminhar por e-mail ou a outra conta CRM como fallback implícito.
5. Quando o CRM já autorizado estiver **temporariamente indisponível**, poderá funcionar a recepção durável, com R01 a R05; indisponibilidade não equivale a ausência/revogação de destino.
6. Novos recebimentos após revogação ou perda de autorização devem ser bloqueados. Submissões já validamente aceitas preservam T0 e histórico, sujeitas às políticas e à avaliação de autorização do destino; nunca redirecionar automaticamente ao CRM novo.
7. Mudança de vínculo CRM mediante chamado não equivale a migração de registros entre contas; contatos externos por links (WhatsApp/mailto) não constituem formulário hospedado pela Imagine.

## Regras complementares anteriores não revogadas

- IN-128: limpeza de cópias temporárias de leads já confirmados no CRM após **24 horas**, salvo fundamento legítimo de conservação.
- IN-128/IN-147: alerta preventivo quando faltarem **72 horas para o fim das 432h** (T0 + 360h) e alerta crítico no vencimento; sem exclusão silenciosa.
- IN-147: **sem quarentena operacional adicional de 30 dias** depois das 432h.
- IN-124: duas rotas/APIs independentes e recepção confirmada apenas após persistência durável; a escolha física de infraestrutura continua sujeita ao planejamento específico.
- IN-125: formulários observam finalidades, transparência, consentimentos quando aplicáveis e demais exigências de segurança/privacidade.
- IN-148/IN-149: confirmação de entrega significa comprovação efetiva pelo CRM; não presumir sucesso por aceitação intermediária.
- Interpretação das exceções: obrigação legal de guarda ou bloqueio de integridade exige registro e tratamento controlados, sem alterar o prazo operacional ou conceder reenvio após ele.

## Contratos lógicos a reconciliar (não implementados)

- **Recepção:** `submission_id`, `site_id`, `empresa_id`, `form_version`, `accepted_at`, `idempotency_key`, `destination_account_id`, `authorization_ref`.
- **Prazos:** `initial_window_ends_at` (T0+72h), `delivery_deadline_at` (T0+432h), versão da política.
- **Entrega:** `delivery_operation_id`, `attempt_id`, `crm_confirmation_ref`, `crm_delivery_at`, `confirmation_observed_at`, estado oficial e referência de incidente.
- **Descarte:** escopo de cópias, evento de vencimento, solicitação de descarte, conclusão e exceção legitimamente autorizada.
- **Auditoria mínima:** resultado terminal, evento terminal, vencimento de retenção, integridade e referência técnica não derivada dos contatos.
- **Captação autorizada:** estado oficial, versão do formulário, vínculo e permissão de destino, checagem obrigatória no backend.

Nomes de campos são convenções **documentais candidatas**, não schemas ou APIs homologados.

## Pendências sem fechamento automático

1. **RF-PEND-01:** conferir todas as fontes, branches, versões, aprovações e precedências; a comparação parcial não encerra a pendência.
2. **RF-PEND-04:** normas R01–R06 estão ratificadas, mas implementação, testes, contratos finais, concorrência, integridade e homologação técnica não foram executados.
3. Segurança, limpeza de réplicas e backups; prova de idempotência e confirmação do CRM; manejo de exceções; alertas e integrações continuam sujeitos a especificação e validação.
4. **Não** converter aprovação documental em autorização de programação, homologação de Cloudflare/fornecedores ou habilitação do rollback automático **AGI-11-RB01**.

## Fontes documentais históricas (reconciliar)

- [IN-124](IN-124-integracao-inteligente-formularios-leads.md), [IN-125](IN-125-gestao-inteligente-formularios.md), [IN-126](IN-126-monitoramento-recuperacao-leads.md), [IN-127](IN-127-retencao-seguranca-filas-leads.md), [IN-128](IN-128-limpeza-segura-auditoria-filas-leads.md), [IN-147](IN-147-exclusao-leads-nao-entregues-apos-18-dias.md), [IN-148](IN-148-reconciliacao-inteligente-leads.md), [IN-149](IN-149-protocolo-entrega-confirmacao-leads.md).
- As decisões expressas R01–R06 foram ratificadas no ChatGPT em 03/11/2026; este documento preserva seu conteúdo, mas não afirma ter auditado integralmente todos os registros originais.

**Status final:** seis ratificações normativas **aprovadas**; conferência global e homologação **pendentes**. Documentação somente; nenhum código, teste ou automação operacional.
