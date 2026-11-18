# Acompanhamentos respeitam a barreira da sessão causal

Estado: candidato privado de desenvolvimento. Os resultados HTTP/PostgreSQL
precisam ser confirmados na CI da SHA publicada. Sem homologação operacional.

## Defeito e contrato existente

`NativeFollowupEngine::process` conferia o estado `active` e os prazos da sessão
que criou o agendamento. O IAM já impõe condições adicionais por meio de
`DatabaseSessionAuthority::usable`: uma referência com `security_hold` não
resolvido, ou cuja operação de origem não terminou em `completed`, não pode
servir como prova de sessão.

Isso é um estado real do protocolo. `IamOperationRunner` grava e commita uma
intenção `prepared`, um evento `attempted` e o hold antes de executar o núcleo
da operação e acessar os demais recursos. Quando o núcleo e a compensação
falham, a referência pode continuar `active` com um hold persistido. Outra
sessão válida do mesmo ator não remove essa barreira da sessão causal.

O contrato já está descrito em `docs/desenvolvimento/F1-04-iam-hardening-audit.md`,
na seção de falhas parciais e consistência, e exercitado por
`tests/Feature/IamHardeningTest.php`. O recorte nativo e a revalidação da sessão
causal estão em `docs/automation/CENTRAL-AUTO-P1.md`.

## Correção

O motor resolve o `DatabaseSessionAuthority` já configurado no container e
confirma que ele usa exatamente a conexão IAM da unidade transacional atual.
Depois de bloquear a sessão causal, reaproveita `usable()` para conferir a
operação de origem e exige explicitamente `security_hold === null`.

A exceção que permite ao próprio IAM trabalhar sob o hold de sua operação
não se aplica ao processamento de tarefas. O motor não altera contexto IAM,
holds, operações ou referências de sessão.

O relógio é lido novamente depois do lock da sessão. Uma espera não conserva
uma decisão de validade baseada no instante anterior ao bloqueio. Os checks
existentes de ator, empresa, lead/versão, configuração, comando, prazo e ação
permitida continuam aplicados.

Quando a origem não pode fornecer autoridade, permanece o tratamento existente
de comando causal inválido: cancelar a pendência, sem tarefa e sem reescrever
os campos causais. O caso nominal continua gravando tarefa, resultado e
auditoria na mesma transação.

## Regressão HTTP com falha IAM real

O cenário `assert_native_causal_session` usa o cliente B sintético, após o
ensaio de supersessão e antes do ensaio de preferências:

1. Mantém a sessão HTTP A e autentica uma sessão B independente do mesmo ator.
   A sessão B usa o código de recuperação 2; os códigos 0 e 1 continuam
   reservados aos ensaios posteriores de HTTP e Chromium.
2. Pela UI HTTP existente, configura atraso de 30 segundos e cria dois leads e
   dois comandos: um causal à sessão A e outro causal à sessão B. Confere os
   IDs distintos de sessão, o mesmo ator e os comandos futuros persistidos.
3. Instala um trigger temporário que nega somente a mudança de `state` da
   sessão A. O trigger permite o UPDATE de `security_hold` feito pelo prepare.
4. Executa `POST /logout` em A. `logoutCore()` chama `changing()` antes do UPDATE
   recusado. A compensação tenta revogar a mesma referência e também falha.
   O próprio IAM produz a intenção e a evidência persistidas; o fixture não
   insere uma operação nem atribui um hold diretamente.
5. Remove os objetos temporários em `finally` e exige, antes de processar:
   sessão A ainda `active`, hold não nulo, operação referenciada pelo hold com
   ação `logout`, estado `prepared`, alvo correto e somente resultado
   `attempted` na auditoria IAM.
6. Confirma que a operação original de login de A continua `completed` e
   inalterada. A operação incompleta é a de logout referenciada pelo hold;
   o teste não rebaixa uma operação já concluída para fabricar o caso.
7. A navegação de A é negada e B continua autenticada. Depois de uma espera
   limitada pelos vencimentos persistidos, B processa ambos os comandos.
   O comando de A deve ficar cancelado sem tarefa; o de B deve concluir com
   exatamente a sua tarefa e uma tentativa.
8. Uma repetição não produz novo efeito nem auditoria. Os comandos antigos,
   leads, preferências e dados da outra empresa/ator permanecem preservados.
   O hold e a operação IAM de A permanecem idênticos à evidência da falha.
9. O atraso anterior é recomposto por uma alteração normal de preferências.
   O harness continua com a sessão B válida. A referência A nunca é restaurada
   como sessão utilizável; o teardown normal revoga as referências das fixtures.

A prova negativa não depende apenas de um status HTTP de erro. Ela exige o
estado causal exato no PostgreSQL e também a conclusão nominal do outro comando
na mesma chamada de processamento.

## Limites do fault injection

Os modos CLI `automation-deny-causal-logout` e
`automation-restore-causal-logout` exigem:

- Projeto exato `imagine-crm-journey-ci` e opt-in
  `CRM_PRIVATE_CI_CAUSAL_SESSION_PROFILE=native-causal-session-hold-v1`.
- Ambiente staging, instalação sintética, owner/banco esperados, ingresso
  privado fechado e vault de fixtures já protegido.
- Marcador 0600, sem symlink, com UUIDs validados e actor/empresa iguais ao
  cliente B do vault. A instalação exige marcador recente, execução futura
  pendente e sessão originalmente utilizável.
- Trigger limitado à combinação exata de sessão e ator. Nenhum grant é removido
  de outros clientes ou da conexão IAM inteira.

Função e trigger recebem uma marca exclusiva do probe. A limpeza recusa
objetos com outra marca e verifica a remoção de ambos. Não limpa holds, não
marca operações como `completed` e não oferece handler HTTP de falha ou de
recuperação. Caso a limpeza falhe, o teste falha e conserva o marcador até o
teardown do ambiente descartável.

Não há alteração da política IAM, de mecanismos de recuperação, de schema
operacional, de preços ou de permissões comerciais. Não há worker, integração
externa, merge, deploy, VPS ou dado real neste recorte.
