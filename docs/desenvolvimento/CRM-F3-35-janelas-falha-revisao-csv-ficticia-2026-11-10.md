# F3-35 — Injeção de falhas antes do registro e após perda do ACK, somente em memória

**Data:** 10/11/2026. **Estado:** laboratório sintético na DEV. **Referências:** [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35), F3-33 (simulação de versão/idempotência), [F3-32 proposta não aprovada](CRM-F3-32-contrato-proposto-revisao-autenticada-versao-confiavel-lote-2026-11-10.md), [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27).

## Por que testar dois momentos de falha

Revisões reais precisam de transação, trilha e idempotência duráveis, mas o experimento `CsvReviewRaceFixture` tem apenas contadores na memória de **uma instância PHP**. Antes de criar qualquer mecanismo real, os testes negativos devem evidenciar uma distinção: **abortada antes do registro** versus **executada, mas sem confirmação de recebimento**. Nenhuma dessas simulações cria autorização para importar contatos.

Foi adicionado à função `submit(..., string $syntheticFault = 'fixture_none')` um parâmetro **optativo e restrito ao laboratório** que admite somente três valores:

| Modo fictício | Comportamento modelado | Impacto local |
|---|---|---|
| `fixture_none` | Comportamento anterior, inalterado | A primeira revisão válida pode acrescentar 1 ao contador da instância; nunca aprova importação |
| `fixture_abort_before_record` | Após as validações, impede o passo de registro local | Retorna `fixture_abort_no_op`, não consome chave nem incrementa versão; tentar novamente com a mesma chave pode registrar uma única operação |
| `fixture_lost_ack_after_record` | Registra **somente na instância de teste** e depois simula a perda da resposta | Lança `RuntimeException` genérica sem expor dados; a versão e o evento **já foram incrementados**; uma repetição com mesma chave/payload retorna `fixture_idempotent_replay_no_op` |

Se a solicitação tiver versão obsoleta, marcador divergente ou chave de idempotência conflitante, as regras de rejeição existentes têm precedência. Uma injeção de falha **não** contorna a validação das linhas sinalizadas, a verificação de versão ou a lista fechada de status. Modos desconhecidos são rejeitados antes de qualquer operação local.

`tests/Unit/CsvReviewRaceFaultWindowsTest.php` executa ambos os momentos de falha, repetição e recuperação, alteração da matriz após confirmação perdida, detecção de chave repetida com payload divergente, versão antiga após evento e ausência de vazamentos de dados nas mensagens. O teste comprova exclusivamente o comportamento **deste objeto em memória**.

## Semântica de recuperação real: NÃO IMPLEMENTADA

Uma falha ao emitir o resultado para a aplicação **não prova rollback**. Em um futuro banco transacional, a idempotência teria de ser escopada por organização, lote, operação, versão e principal, com restrição única, log de decisões e auditoria na **mesma transação confiável**; o consumidor teria de consultar o resultado persistido na reconexão, sem repetir gravações. Em falha de auditoria antes do commit, o comando deveria abortar atomicamente, mas o experimento F3-35 **não implementa auditoria nem a prova atômica**.

Da mesma forma, duas instâncias distintas podem manter estados independentes e ambas registrar a mesma operação inventada. Não há lock PostgreSQL, transação, RLS autêntica, identificador confiável de tenant, autenticação humana, MFA, versão persistente, recuperação durável ou mecanismo de compensação. O código não lê CSV real, não produz logs reais de revisões, não cria fila, não chama API, não escreve em banco, não carrega usuário e **jamais** autoriza `ALLOW`, lead/contact ou importação.

**Gates NO-GO:** [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27) e [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) (DRAFT sem parecer independente), decisões pendentes F3-32-D01..D10, CORE/IAM, S01, LGPD, Central IN-118, importação/backup/restore e revisão de infraestrutura. Nenhuma modificação em `main`, VPS, custo, migração, credencial ou produção é permitida. Integração deste incremento experimental somente DEV após revisão de diff e workflows F0/PostgreSQL RLS verdes na HEAD exata.
