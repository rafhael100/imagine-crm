# Ledger de automações: recusa de alterações em permissões e política

Base de comparação: CRM #639, `403a2b735e77bd72b529e296b20e63eab1c00859`.
Somente desenvolvimento e CI com PostgreSQL 16 descartável. DRAFT / NO-MERGE / NO-DEPLOY.

## Problemas corrigidos

A migration incremental verificava a quantidade de políticas, mas não seu comando,
papéis ou modo permissivo. Um `tenant_scope` com `TO crm_homologation_owner`, por
exemplo, permanecia restrito ao owner após o `ALTER POLICY`; a aplicação perdia a
visibilidade do próprio tenant enquanto o script emitia `READY`.

O ajuste de grants revogava `UPDATE` e concedia apenas as operações necessárias,
mas não recusava `DELETE`, `TRUNCATE`, `REFERENCES` ou `TRIGGER` preexistentes. Uma
permissão adicional de exclusão podia sobreviver à reaplicação. Também não havia
comparação completa de destinatários e opções de delegação dos grants.

No PostgreSQL 16, revogar `UPDATE` da tabela também revoga os grants correspondentes
das colunas. Portanto grants diretos de `UPDATE` em colunas imutáveis **não** são
apresentados como permissões que sobreviviam ao código anterior. Eles entram agora
na regra de recusa explícita de alterações não revisadas, antes da normalização.
Referência: [PostgreSQL 16 — REVOKE](https://www.postgresql.org/docs/16/sql-revoke.html).

## Comportamento da candidata

A migration compara a política com uma política temporária de referência: comando,
modo permissivo, destinatários, `USING` e `WITH CHECK` devem coincidir. O caminho
novo cria a política esperada; a reaplicação de uma política alterada falha dentro
da transação, preservando o estado encontrado para diagnóstico.

As ACLs aceitam somente o owner e os direitos previstos da aplicação: `SELECT` e
`INSERT` na tabela, `UPDATE` nas colunas `state`, `attempts` e `due_at`, sem opção de
delegação. Grants para `PUBLIC`, outro papel ou uma coluna imutável são recusados.
A checagem final de direitos efetivos verifica todas as colunas imutáveis e as
operações adicionais de tabela, incluindo efeitos de memberships herdados.

Uma instalação com grants intencionais diferentes continua exigindo revisão
própria; o script não os apaga nem declara compatibilidade por inferência. Não há
alteração no autorizador CORE, criação de papéis operacionais ou ampliação de
direitos de negócio.

## Evidência executável

O workflow `central-auto-incremental-migration.yml` mantém o teste do schema
anterior, dos dados existentes, da reaplicação, do isolamento e das recusas por
constraints/tipos/defaults. A regressão adicional executa a migration anterior,
obtida de checkout preso à SHA #639, para reproduzir dois defeitos:

- `READY` mesmo com exclusão efetivamente permitida à aplicação; a exclusão de
  teste é revertida antes da próxima verificação.
- `READY` com política destinada ao owner e invisibilidade do registro do próprio
  tenant para a aplicação.

Em seguida, 28 fixtures de alteração de ACL/política devem ser recusadas. Cada
tentativa compara registros, flags RLS, políticas e ACLs antes/depois da falha,
restaura a fixture e repete a migration nominal. A checagem final executa uma
atualização permitida e 13 operações proibidas usando o papel real da aplicação.
Um segundo job inicializa o schema atual em PostgreSQL separado e aplica a
migration duas vezes, cobrindo compatibilidade de instalação nova.

Os resultados efetivos ficam registrados na PR e nos logs da SHA candidata;
este documento não antecipa sucesso da CI. O ambiente local não dispõe de servidor
PostgreSQL ou Docker; nele foram verificadas a sintaxe Python e a estrutura YAML.

## Limites preservados

Nenhuma migration foi aplicada à VPS. Atualização operacional ainda depende de
inventário real, backup e restauração aprovados, comparação de schema/permissões,
revisões independentes e janela autorizada. A migration permanece transacional,
sem `down` destrutivo e sem remoção dos registros existentes.
