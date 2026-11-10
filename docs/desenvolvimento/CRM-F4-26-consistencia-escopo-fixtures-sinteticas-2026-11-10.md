# F4-26 — Consistência declarada de escopo em fixtures de follow-up

## Motivação

Os testes negativos F4-25 demonstraram que a união acidental de registros de empresas fictícias com **IDs locais repetidos** já é rejeitada. Contudo, duas fontes podem ter **IDs diferentes** e ainda ser indevidamente misturadas. Para tornar esse erro visível em cenários controlados, o laboratório agora pode exigir que todos os registros tenham a mesma etiqueta **de fixture**, verificada explicitamente antes de calcular totais.

## Contrato técnico

`FollowUpFixtureScopeConsistency::project(string $fixtureScope, array $observations)`:

- aceita somente etiquetas artificiais no formato `fixture-...` (minúsculas, números, sublinhado e hífen), **não** CNPJ, UUID real, `tenant_id` de cliente, IDs verificados nem tokens secretos;
- recebe lista limitada a 31 observações com exatamente `at`, `scope` e `items`;
- recebe até 100 itens por observação com exatamente `id`, `due_at`, `scope`;
- rejeita rótulos diferentes entre parâmetro, observação e item — **mesmo quando os IDs não colidem**;
- recusa campos extras de identificação, contato e autoridade;
- remove os rótulos em memória e delega o cálculo ao `FollowUpObservationSeries`, respeitando tempo ISO-8601 com offset, faixas e movimentação já testados;
- retorna somente timestamps e agregados numéricos; não retorna rótulo nem IDs.

Os testes abrangem duas empresas fictícias independentes, IDs locais iguais, variações por faixa, etiquetas divergentes, rótulos operacionais, campos imprevistos e limite de volume.

## Limitação fundamental — NÃO É AUTORIZAÇÃO

**Etiquetas são declaradas pelo chamador** e podem ser falsificadas. Um invasor ou integração incorreta poderia fornecer a mesma etiqueta fictícia para linhas de fontes diferentes. A classe não dispõe de identidade autenticada, sessão, vínculo ativo, papel, política P1/P2, origem de consulta, direitos de campo, transação, RLS nem auditoria. Portanto **não valida acesso, não prova isolamento de tenant e não é apta para uso com dados reais**. Nem mesmo agregados sem IDs são automaticamente anônimos ou divulgáveis.

O tripwire F4-25 foi ampliado para impedir o uso direto desta classe nos entrypoints Laravel enquanto gates persistirem abertos. Ela existe exclusivamente no namespace `App\\Support\\Synthetic` com entrada de fixture.

## Gates

O uso real só poderá começar após decisões/homologações de CORE, privacidade e SEG/DBA independente ([issue #27](https://github.com/rafhael100/imagine-crm/issues/27)) e desenho de autorização de leitura por empresa, usuário, ação, campo e escopo, com testes em base segura. Não ativar `ALLOW`, API, rota, fila, migration, storage, import/export, VPS, custo ou produção. A `main` é intocada; merge apenas DEV após CI F0 e PostgreSQL RLS F1 verdes.
