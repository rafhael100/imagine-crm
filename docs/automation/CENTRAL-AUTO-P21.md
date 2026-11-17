# CENTRAL-AUTO-P21 — confirmações duráveis e mensagens sem ambiguidade

## Problemas corrigidos

A candidata P19 recusava `stage_outbound` depois dos 120 segundos do envelope,
inclusive quando a outbox já estava `accepted`. A aplicação não chegava ao retorno
idempotente e informava erro para uma entrega anteriormente confirmada.

A candidata P21 permite repetir esse evento confirmado sem novo transporte. A
instalação sintética, a concessão de origem atual, o evento/lead e o tenant continuam
obrigatórios. Uma linha aceita precisa conservar um `ack_hash` hexadecimal de 64
caracteres. Essa checagem detecta recibos ausentes ou malformados; a prova de
autenticidade permanece na validação criptográfica anterior à gravação do recibo.

Um evento `pending` expirado continua recusado. O CRM não renova nonce, request,
payload ou janela: a falta do ACK pode significar que o destino recebeu o evento.
A recuperação dessa situação exige reconciliação autenticada bilateral e política
própria; não foi inventada uma confirmação ou nova permissão nesta correção.

Também havia `json_decode` direto nas mensagens S2S, que retém silenciosamente o
último valor quando o nome de um campo aparece mais de uma vez. `StrictJson`
mantém sintaxe, tipos, UTF-8 e profundidade do decodificador PHP e rejeita nomes
repetidos, inclusive escapados, antes de admitir a mensagem. O receiver usa o
validador antes de carregar chaves/conectar SQL. `AgentsAcknowledgement` usa o
mesmo validador antes das verificações existentes de schema, empresa, evento,
payload, dispatch, decisão e HMAC; somente então devolve o hash dos bytes do ACK.

## Evidência reproduzível

O workflow `central-auto-p19-s2s.yml` foi evoluído para P21, com dois jobs:

1. PHP 8.4: lint de S2S, regressões dos validadores reais e verificação do overlay.
2. Produtos reais separados em Docker/PG16/mTLS: todas as provas P19 anteriores,
   oito casos HTTP de JSON e as regressões de expiração da outbox.

`run_p21.py` verifica o SHA-256 original de `run_p19.py` antes de acrescentar as
provas. O arquivo P19 continua imutável. O overlay exige repositório público
exato, evento pull request e execução CI; os serviços são descartáveis e privados.

A prova de repetição desliga efetivamente o ingress dos Agents, envelhece apenas
fixtures do CI e exige sucesso sem mudar a outbox, inbox ou auditoria do destino.
Revogação, expiração de concessão, mudança de tenant e recibo inválido continuam
negados. O caso pendente expirado também chama a função SQL com o papel restrito
e exige o erro de janela, evitando interpretar uma falha de rede como prova de
autorização correta. As fixtures são restauradas antes das provas de backup/RLS.

As requisições ambíguas têm últimos valores e HMAC válidos, para demonstrar que a
negação decorre da duplicação. São verificados status HTTP e ausência de nonce e
auditoria SQL. A mensagem válida com whitespace continua admitida.

O inventário P20 fixa o commit de implementação P21 e seus hashes em um commit
seguinte, mantendo os pins Portal/Agents e o harness P19. Assim o ensaio nominal
Portal → CRM → Agents é reexecutável sobre a mesma candidata, sem aceitar arquivos
modificados arbitrariamente nem enfraquecer o guard de banco descartável.

Comandos sem serviços:

```sh
php scripts/ci/s2s/test-strict-json.php
php scripts/ci/s2s/test-agents-ack.php
python3 -m unittest discover -s scripts/ci/s2s -p 'test_*.py' -v
```

Os resultados efetivamente executados, SHA final e URLs de logs ficam na PR.
Este documento descreve implementação e critérios; não substitui os logs nem
declara uma execução concluída antes de seu resultado.

## Limites

Base: CRM #637 `4d6ccd842b9b9ee6adfbe317736ca94e4d5b7980`, descendente de #636.
Referência de acompanhamento: #557. Mudança de schema restrita ao candidato CI;
nunca aplicar `outbound-candidate.sql` à VPS como migration operacional.

DRAFT / NO-MERGE / NO-DEPLOY. Identidades, certificados, concessões e eventos
sintéticos. n8n dispatch permanece `DISABLED`; não há cobrança ou IA paga.
A revisão por outro agente de IA é revisão adicional de código e não satisfaz os
pareceres humanos SEG/OPS da #617. Homologação operacional, recuperação bilateral
de pendentes expirados e liberação comercial continuam pendentes.
