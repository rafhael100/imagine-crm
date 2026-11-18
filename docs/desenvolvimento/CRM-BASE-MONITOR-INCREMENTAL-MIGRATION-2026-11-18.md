# Migração candidata da base de 25 tabelas para o monitor de 33

Data: 2026-11-18. Escopo: desenvolvimento e PostgreSQL 16 descartável na CI pública
do CRM. DRAFT / NO-MERGE / NO-DEPLOY. Esta entrega não autoriza aplicação na VPS.

## Lacuna e contrato

Os quatro bootstraps S2S já definem o perfil de monitor, mas executar novamente
seus `CREATE` literais não constitui um procedimento de atualização que admita
o estado de origem, preserve os registros e reconheça uma reaplicação completa.
Esta candidata acrescenta um migrador específico para a transição abaixo.

| Propriedade | Origem admitida | Destino admitido |
| --- | --- | --- |
| Tabelas | 25 da base privada | As 25 originais e 8 de `crm_auto_service` |
| Papéis próprios | 5 da base | Os 5 originais e 4 novos `NOLOGIN` |
| Funções próprias | 0 | 9 de `crm_auto_service` |
| Sequências próprias | 0 | `crm_auto_service.audit_id_seq` |
| Ação do migrador | Aplicar o delta e verificar o destino antes do commit | Verificar e retornar `ALREADY_PRESENT` sem reaplicar o delta |

O perfil de 35 tabelas com drafts owner fica fora desta admissão. O módulo e os
ensaios anteriores de monitor33→owner35 permanecem separados. Não há alteração
nos seis arquivos históricos usados como fonte, no bootstrap owner, nos gates
F251/F252 ou na autoridade para executar operações owner.

## Fontes exatas e referências independentes

Todos os inputs vêm do commit
`96dbf1aed2d1cf3237bc3631c4594540a0168d2a`, com modo Git `100644`. O loader próprio
confere commit/caminho/blob e os bytes lidos; não utiliza um arquivo SQL fornecido
por parâmetro externo.

| Input | Blob Git |
| --- | --- |
| `infra/homologation/schema.sql` | `9dde1bb86d496083ccd163225d8dd0c79b391cdb` |
| `infra/homologation/20-runtime-roles.sh` | `ae5fb2f964cc672d681f4108c1aff7fff5ea5682` |
| `infra/homologation/s2s/install-candidate.sql` | `3cafbf27be3e3968d401dbf98beac0e63fd27bea` |
| `infra/homologation/s2s/outbound-candidate.sql` | `4166eeb7d8dd2eabec9392e94dff4b392650de52` |
| `infra/homologation/s2s/execution-feedback-candidate.sql` | `4804870196ad820007f616d260efb8ea9b64dc0c` |
| `infra/homologation/s2s/execution-monitor-candidate.sql` | `d6dd92bee6ae35ded0aa6655dbdbe858b0d9f181` |

A referência25 é criada pelo schema e pelo script de papéis. A referência33 é
criada em outro cluster pelos mesmos dois inputs, seguidos dos quatro SQLs S2S
inteiros, na ordem da tabela. Essa construção de referência não chama o novo
migrador. Cada alvo de teste começa em outro cluster com somente os dois inputs
da base. Nenhuma origem25 é produzida removendo objetos de um perfil33 ou35.

Essa base25 já contém o ledger definido em `schema.sql` de `96db`. O ensaio não afirma
equivalência com a aplicação da migration001 ao schema histórico `cc173`.
As provas 25→33 e 33→35 são etapas separadas; não representam uma única execução
25→35 observada nem justificam uma comparação não executada entre outras origens.

O delta do migrador conserva os corpos SQL dos quatro bootstraps e retira somente
seus envelopes exatos de execução para aplicá-los na mesma transação. A comparação
do catálogo e da segurança usa as referências independentes antes e depois do
delta. O inventário herdado abrange colunas, constraints, políticas, ACLs, owners,
atributos e memberships de papéis, funções, parâmetros de sequências, índices,
tipos e as propriedades de banco e configurações já cobertas pelo core662.

O par de referências é uma API interna de um builder confiável. A garantia de
origem vem da construção com os seis inputs pinados e os clusters segregados;
não se trata de autenticação de um JSON ou callback enviado por um usuário.

Além dos objetos adicionados em `crm_auto_service`, o delta de permissões em
`public` admite exatamente sete grants para `crm_auto_reader_owner`: `USAGE`
no schema e `SELECT` nas tabelas `crm_private_installation`,
`crm_private_automation_runs`, `crm_private_preferences`, `crm_private_events`,
`crm_private_trials` e `crm_private_leads`. Cada grant deve ter como grantor o
owner esperado do respectivo objeto na referência e não pode incluir grant
option. Ao projetar a referência33 sobre a referência25, o módulo retira
exatamente esses sete grants; todo o restante da projeção deve ser idêntico.

## Preservação dos dados e da sequência

O alvo positivo contém dados sintéticos relacionados em 12 das 25 tabelas:
instalação, identidades, organizações, memberships, contas, trials, funis, leads,
tasks, preferências, ledger e eventos. As outras 13 tabelas permanecem vazias.
O ensaio compara quantidade e hash dos registros de **todas as 25 tabelas**, com
projeção explícita das suas colunas, antes e depois da aplicação. Isso não equivale
a povoar ou exercitar todos os fluxos comerciais da base.

Na primeira aplicação, as oito tabelas adicionais devem nascer vazias; nenhuma
coluna da base é acrescentada ou removida. A sequência passa de ausente para
`last_value=1, is_called=false`. Os quatro papéis novos são
`crm_auto_reader_owner`, `crm_auto_transport`, `crm_auto_sender` e
`crm_auto_execution_reconciler`, todos `NOLOGIN`.

Depois da aplicação, o ensaio insere registros em cada uma das oito tabelas:
`grants`, `nonces`, `audit`, `outbound_grants`, `outbox`, `execution_read_grants`,
`execution_observations` e `execution_projections`. A inserção em `audit` consome
a identidade e exige `last_value=1, is_called=true`. A reaplicação nesse perfil33
deve retornar `ALREADY_PRESENT`, preservar o inventário, todos os registros e
esse estado da sequência. Nesse momento são 20 tabelas povoadas e 13 vazias.
Os registros de monitor são fixtures de armazenamento com relações reais;
não representam recibos verificados ou execução de serviços externos.

## Recusas e rollback tardio

As três negativas preservam o estado já alterado para diagnóstico; não esperam
que o migrador repare a fixture.

| Fixture | Estado antes da tentativa | Resultado exigido |
| --- | --- | --- |
| Instalação parcial | Base25 mais `install-candidate.sql` inteiro: 28 tabelas | `BASE_MONITOR_MIGRATION_SCHEMA_DRIFT` e captura idêntica antes/depois |
| Atributo de papel divergente | Base25 com `crm_homologation_app INHERIT` | Mesmo diagnóstico, sem restaurar o atributo |
| Grant adicional | Monitor33 povoado com `DELETE` em `crm_auto_service.audit` concedido à aplicação | Mesmo diagnóstico, sem revogar o grant |

O teste de rollback usa um quarto alvo25. Uma sessão bloqueadora cria
`crm_auto_sender` e mantém a transação aberta, sem commit. Uma captura feita por
outra sessão deve continuar idêntica à base25. O migrador então tenta criar o
mesmo papel pelo `CREATE ROLE` literal do bootstrap outbound, após passar pelo
bootstrap install na sua transação.

A prova não aceita somente um código de saída. Antes de aceitar o timeout, o
harness exige observação de `pg_stat_activity` e `pg_locks`: query literal de
`CREATE ROLE crm_auto_sender`, espera `ShareLock` sobre o transaction ID real da
sessão bloqueadora e `pg_blocking_pids` contendo exatamente seu backend PID.
Em seguida exige SQLSTATE `55P03`, encerramento do backend da migração, rollback
da sessão bloqueadora e encerramento do seu backend. A captura final deve ser
idêntica à inicial, sem `crm_auto_service`, sem os quatro papéis adicionados e
sem a sequência. Esses resultados só podem ser declarados depois da execução
real na CI; a leitura do código não substitui a observação do bloqueio.

O bloqueio provocado é um ensaio de falha durante a aplicação do delta. Ele não
prova disponibilidade online, reaplicação concorrente ou atualização de uma
instalação com tráfego. A preservação da sequência existente é exercitada no
no-op33; o teste de falha parte de uma base que ainda não possui essa sequência.

## Isolamento, integração e evidência

O harness `scripts/ops/test-private-crm-base-monitor-migration.py` cria duas
referências e quatro alvos PostgreSQL 16 descartáveis. Usa a identidade local
observada da imagem, `--network none`, zero portas e dados efêmeros em tmpfs.
Não sobe aplicação, interface HTTP, navegador, worker n8n ou outro serviço.

O transporte de transações e a captura de inventário reutilizam o core662 sem
modificá-lo. O nome de container conserva o prefixo exigido por esse core e
acrescenta `base-monitor-`; a label adicional
`imagine.base_monitor_migration.scope=base25-monitor33-v1` distingue este ensaio.
A limpeza exige essa label e os valores reais de HEAD, run e attempt, além de
conferir a identidade do container. A admissão continua ligada ao evento real
da PR, à branch candidata e à ancestralidade exigida pelo guard do CRM.

O workflow owner existente recebe a etapa nova depois da prova e da limpeza
monitor33→owner35, seguida de limpeza própria com `always()`. O timeout global
de 30 minutos permanece. As provas HTTP, navegador, n8n e restauração já presentes
continuam com seus contratos; esta entrega não acrescenta outra rodada delas.

O resumo `CRM_BASE_MONITOR_INCREMENTAL_MIGRATION_PASS_NO_GO` registra a HEAD
real, run/attempt, fonte `96db`, imagem PostgreSQL observada, hashes das referências
e dados, contagens de tabelas povoadas/vazias, resultado da primeira aplicação e
da reaplicação, observação do bloqueio e confirmação da limpeza. A saída mantém
`go_for_installation=false`, `human_crm_session_verified=false`,
`owner_execution=false`, `new_http_or_n8n_execution=false` e
`distributed_recovery=false`.

Até os logs da nova SHA demonstrarem o resultado, a execução PostgreSQL desta
candidata permanece pendente. As verificações locais de sintaxe, imports,
estrutura do workflow e bytes de fontes não representam execução PostgreSQL.
Falhas são reportadas por códigos fechados; SQL, senhas e dados privados não são
incluídos no diagnóstico.

## Limites operacionais

O migrador é uma API de laboratório para os dois perfis exatos, com um único
delta conhecido. Não seleciona instalações legadas por aproximação, não executa
reparo automático e não tenta de novo uma aplicação que falhou. Uma atualização
operacional continua dependendo de inventário autorizado, backup e restauração,
comparação de objetos e permissões, revisão independente e janela aprovada.

As provas de rollback tratam falhas antes de `COMMIT`. Se o servidor efetivar
um commit e a confirmação se perder, o cliente pode ficar com resultado
indeterminado; o ensaio não promete desfazer um commit já concluído. A admissão
exata do destino permite uma avaliação posterior seguida de reaplicação no-op,
sem retry automático e sem concessão de nova autoridade operacional.
