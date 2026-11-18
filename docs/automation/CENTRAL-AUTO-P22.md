# CENTRAL-AUTO-P22 — recuperar um recebimento sem reenviar o evento

## Problema e contrato

O envelope CRM → Agents tem prazo de transporte. Se a resposta HTTP se perder
depois de o destino persistir o evento, esse prazo pode vencer enquanto a outbox
do CRM continua pendente. Recriar nonce, request ou datas não resolve a dúvida
sobre entrega e conflita com a identidade durável exigida pelo receiver.

Esta candidata consome a consulta privada implementada na
[PR Agents #461](https://github.com/rafhael100/imagine-ai-agents/pull/461), fixada em
`8041c022776bbc1f998224a880726a8a3e69db37`. A consulta usa
`POST /private/events/receipt`, autenticação mTLS, assinatura com domínio próprio
e grant independente `event.receipt.read`. O contrato do destino também exige
grant de ingresso, vínculo empresa/tenant e chave de ingresso atuais.

O recibo aceito contém `RECEIVED`, `DISABLED` e `NOT_DISPATCHED`. Confirma somente
recebimento. Não atesta processamento nem habilita n8n, IA externa ou nova ação
comercial. A CI do Agents dessa dependência passou em
[44192247253](https://github.com/rafhael100/imagine-ai-agents/actions/runs/44192247253);
a integração consumidora exige sua própria execução na SHA do CRM.

## Implementação no CRM

`AgentsReceiptReconciler::reconcile(company, event)` é um caminho explícito,
limitado a staging e aos dois perfis sintéticos de S2S/consulta. A função SQL
`receipt_candidate` exige instalação, grant de origem, evento/lead e tenant
válidos; só lê uma outbox existente, sem inserir ou renovar envelope.

A transação mantém os locks do grant e da outbox durante consulta, validação e
confirmação. O prazo do grant é revalidado depois da espera por lock e ao gravar
a confirmação. A consulta tem request/nonce próprios, usados exclusivamente para
vincular a resposta, e não modifica as referências originais do evento.

`AgentsReceipt` valida JSON sem nomes repetidos, o conjunto exato de campos,
tipos, emissor/destinatário, empresa, tenant, evento, hash do payload, request,
nonce, chave, janela temporal e HMAC. A chave de resposta é independente da chave
de ingresso. Apenas o hash dos bytes de um recibo válido é gravado em `ack_hash`,
a coluna de confirmação durável existente.

O estado aceito pode ser repetido sem nova consulta, inclusive com destino fora
do ar, preservando validação atual de origem. Recibo ausente, resposta inválida,
revogação ou indisponibilidade deixam a pendência sem confirmação. O caminho
original de envio continua recusando envelopes pendentes expirados.

A função existente `ack_outbound` também passa a recusar explicitamente SQL NULL;
a avaliação ternária anterior permitia criar estado aceito sem digest. O
consumidor PHP já fornecia um digest validado; a correção fecha a lacuna da função
exposta ao papel de serviço.

O envio original também recalcula o relógio depois de obter o lock da outbox.
Assim, um grant ou prazo de envio que vence durante a espera não é admitido com
o horário anterior. O laboratório verifica esses dois casos com bloqueio real,
PID observado e erro de domínio específico; um timeout não passa como recusa.

## Provas executáveis

O workflow S2S executa os validadores PHP reais e os overlays Python antes de
iniciar produtos separados, certificados novos e bancos PostgreSQL descartáveis.
Os vetores fixos de HMAC de consulta e resposta foram calculados independentemente
com Python. As respostas negativas incluem mensagens corretamente assinadas com
escopo errado, campos ambíguos, tempos/tipos inválidos e troca de request/nonce.

`run_p22.py` reaproveita `run_p21.py` e o `run_p19.py` original, protegido pelo
mesmo SHA-256. Verifica também os hashes dos cinco arquivos do endpoint Agents.
Mantém as provas anteriores de JSON, outbox, isolamento, concorrência e restore.

O cenário bilateral exige:

- Grant de ingresso sozinho não autoriza a consulta; grant de leitura separado
  permite resposta assinada, sem alteração na inbox ou auditoria de ingresso.
- Revogações, expiração, chave/vínculo desabilitados, outra empresa/tenant/hash,
  JSON duplicado, cookies, Authorization e certificado ausente são recusados.
- Um evento persistido no destino, com ACK perdido e envelope expirado, passa a
  aceito por reconciliação concorrente, preservando todos os campos do envelope.
- Evento nunca enviado permanece pendente; consultar antes de staging não cria
  uma outbox. Nenhum teste infere recebimento da ausência de erro de transporte.
- Repetir confirmação com ingress do destino parado não faz nova entrega.
- SQL NULL é recusado sob o papel real `crm_auto_sender`, sem alterar a outbox.

As datas das fixtures são envelhecidas de forma explícita nos dois bancos para
evitar uma espera artificial de 120 segundos. O cenário restaura seus registros
antes da prova de backup P19 com três eventos. Essa restauração P19 ainda é em
outro banco do mesmo cluster; a prova em servidor separado é outra entrega.

## Pins, execução e limites

O ensaio P20 de autoridade nominal Portal → CRM → Agents é mantido separadamente.
Seu inventário é atualizado num commit posterior ao de implementação, permitindo
comparar bytes com uma SHA imutável, incluindo os dois novos consumidores e os
arquivos de recibo no Agents. Os guards de destino e o pin do Portal permanecem.

Os resultados efetivos de PHP, PostgreSQL e HTTP/mTLS ficam registrados na PR e
nos logs da SHA candidata. Este documento não antecipa sucesso da integração.

Não há novo handler público, worker automático, tentativa infinita ou mudança de
credenciais operacionais. O CLI de ensaio permanece no harness descartável. A
instalação SQL é exclusivamente de laboratório; perfis e grants não constituem
autorização operacional. DRAFT / NO-MERGE / NO-DEPLOY; #557/#617 e as revisões
humanas continuam independentes.
