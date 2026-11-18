# Preparação P20 integrada — 16/11/2026

## Continuidade P22 — 17/11/2026

O inventário atual fixa o CRM no commit de implementação `91f2236a33f38ae0a4c95f835b248c1caac1ffe1` e o Agents no contrato de recibo #461, `8041c022776bbc1f998224a880726a8a3e69db37`. O Portal permanece em `c87f39c8c761c9a9c64d13d14bf45de889061a32`. São comparados também os dois consumidores PHP novos, o overlay P22 e os arquivos do endpoint de recibo Agents; os pins são registrados após o commit de implementação para evitar referência circular.

O ensaio P20 continua verificando autoridade nominal persistente do Portal e entrega/ACK/repetição. A recuperação de recibo expirado tem seu próprio ensaio P22 com a mesma dependência Agents. O harness original P19, os guards de destino e o contrato de autoridade P20 são preservados. Resultados efetivos desta candidata: consultar a PR e os checks da SHA concreta. Ver [CENTRAL-AUTO-P22](../../../docs/automation/CENTRAL-AUTO-P22.md).

## Registro histórico da continuidade P21 — 17/11/2026

O registro de preparação abaixo é histórico. O ensaio P20 foi posteriormente
executado com sucesso na [PR CRM #637](https://github.com/rafhael100/imagine-crm/pull/637),
HEAD `4d6ccd842b9b9ee6adfbe317736ca94e4d5b7980`,
[CI 43951158689](https://github.com/rafhael100/imagine-crm/actions/runs/43951158689).

Nesta continuação, `inspect_inputs.py` e `prepared-inputs.json` fixam o CRM no
commit de implementação P21 `1bf4a80afb4e40f3a03deebab9009f9375b03948`.
Além do SQL, workflow e harness, são conferidos os quatro arquivos runtime S2S:
receiver, sender, decodificador JSON e validador de ACK. Os pins Portal/Agents,
o guard de destinos e o hash original de `run_p19.py` são preservados. O inventário
continua recusando diferenças de bytes em relação ao commit fixado.

O workflow P19 evoluiu para a regressão P21; o workflow nominal P20 continua
separado. A execução da candidata atual deve ser consultada na PR de continuação;
o sucesso histórico da #637 não equivale a sucesso da nova SHA. Ver
[CENTRAL-AUTO-P21](../../../docs/automation/CENTRAL-AUTO-P21.md).

## Registro histórico da preparação inicial

**Estado: preparação local verificada; ensaio Docker e integração NÃO EXECUTADOS.** Nenhuma migration, conexão de negócio, banco ou serviço foi iniciado nesta tarefa. O runner ainda não foi acionado na validação local. O novo workflow foi preparado para `.github/workflows/central-auto-p20-integrated.yml`; nenhum workflow existente mudou.

## Runner e coordenação

GitHub reconfirmou `rafhael100/imagine-crm` com visibility=public. O P19 já executou no runner padrão `ubuntu-24.04`: [run 43927603208/job 133511121460](https://github.com/rafhael100/imagine-crm/actions/runs/43927603208/job/133511121460), completed/success. A [documentação oficial](https://docs.github.com/en/actions/reference/runners/github-hosted-runners) informa VM nova por job e uso gratuito/ilimitado de runners padrão em repositórios públicos. O workflow preparado recusa repositório privado, usa `contents: read`, checkout sem persistência de credenciais, sem secrets operacionais, artifacts/cache, runner maior ou provisionamento. Disponibilidade de fila não foi testada nesta rodada.

Não foi localizado ensaio integrado P20 equivalente nas buscas de PRs/issues. P19 comprova transporte/eventos com callback nominal sintético; P20 #908/#910 comprova autoridade com fonte em memória. P21 e Agents #451 não substituem esse ensaio.

## Pins e reaproveitamento

- Portal evoluiu de #910 `9eb947f455bd78805b66c8b94c064b48331bca9b` para **#914 `c87f39c8c761c9a9c64d13d14bf45de889061a32`**, por instrução explícita. `git merge-base --is-ancestor` retornou 0. Só o checkout separado do ensaio mudou.
- CRM #636 `cca5055608acff8c767b888b6322df77fe966558`; Agents #450 `94dcfeabd24f180113d2a378afe6232c67053664` preservados.
- `prepared-inputs.json` contém SHA256 das entradas verificadas. O guard #914 é vinculado por HEAD e hashes em `portal_guard_binding.json`; o adaptador usa os arquivos integrais, sem relaxar ou reimplementar suas validações.
- P18 histórico: árvore `5d700f82c0a92f8b9252e9d9465010123f758954`, reconfirmada nos logs P19, **não reexecutada aqui**. O workflow reutiliza o exportador/convergência existente, que aborta caso snapshots tenham mudado.

`prepare_harness.py` verifica o hash do harness P19 e aplica um overlay em memória, sem modificar o arquivo/workflow original. Mantém build/fixture/certificados/serviços privados P19, acrescenta os gates, troca o Portal para instância persistente local e reduz o escopo ao fluxo funcional benigno. A fonte transformada compila e pode ser inspecionada via `python3 scripts/ci/p20/prepare_harness.py --root .`. Não executar o arquivo renderizado diretamente; `run_integrated.py` verifica pins e CI público antes da execução.

## Destinos antes de escrita

**CRM/Agents:** `service_targets.py` exige Docker local via socket padrão e projeto aleatório sem containers/redes/volumes anteriores. Confere Compose privado, ausência de portas públicas, identidade/labels dos containers, rede interna, volume local novo e IP exato. Cruza `pg_controldata` com consulta SQL somente leitura no container exato. Depois `service-target.php`, dentro da imagem de aplicação, usa os DSNs/perfis efetivos do produto: recusa alterações, DNS divergente e overrides libpq antes de PDO; compara banco, endereço, porta, início, OID e identificador do cluster, TLS e role. Para SQL preparatório, valida e escreve pela **mesma conexão PDO**, sem reconexão. Revalida as roles restritas depois de criadas, sem lhes conceder acesso a pg_control_system.

**Portal:** imagem de laboratório contém PHP e PostgreSQL 18 no mesmo namespace/processos e UID. Cluster novo em `/tmp/p20-cluster`, conexão exclusivamente `127.0.0.1:55432`, sem porta publicada. A inicialização usa `initdb`, e `portal-create-database.php` reutiliza a evidência local do helper #914, cruza a identidade via SQL no banco bootstrap `postgres` e cria apenas o banco dedicado na mesma conexão. Esse passo não executa migrations. `--check` e `--migrate` são os entrypoints integrais do #914. Para a fixture de role já existente, `portal-role.php` consome `Gate::run` e executa o corpo da fixture pinada no mesmo objeto de conexão verificado, sem segundo bootstrap. Os bancos CRM/Agents são inicializados pelas imagens PostgreSQL em volumes novos; os guards antecedem todo SQL de aplicação do P19.

A configuração efetiva e a identidade real dos três clusters **ainda não foram observadas em execução**; os testes abaixo comprovam o comportamento dos gates com dados sintéticos, não a topologia Docker pronta.

## Cenários e evidências previstas

`PortalPrivateAuthorityTest.php`: senha sem MFA (zero chamadas à fonte), MFA+grant persistido (transporte real), outra empresa/grant revogado (zero chamadas), revogação após retorno do transporte real (estado não devolvido). A recusa antes de MFA atualmente não insere auditoria; o teste registra essa limitação, não declara cobertura completa. O overlay exige exatamente quatro testes sem falha/skip e delta de duas leituras confirmadas na auditoria CRM.

Em seguida reaproveita evento lead.created → Agents, ACK assinado e repetição após perda de ACK, exigindo inbox única. Não executa o conjunto de probes de assinatura/cookie/certificados nem testes de indisponibilidade/restore P19. Nenhum serviço n8n, fornecedor IA ou conexão de negócio externa é configurado.

Na execução futura, o harness registra HEAD própria, SHAs/hashes de produtos/fixtures/guard e versões PHP/PG/Composer e IDs/digests das imagens utilizadas. Dockerfiles ainda usam tags de base P19/PG18; **não são pins de digest de uma imagem já construída**. O build não foi realizado, portanto versões efetivas de pacotes/imagens permanecem desconhecidas.

## Validação local executada

```sh
P20_PORTAL_CHECKOUT=<checkout-do-portal> \
P20_TEST_PHP=<binario-php-8.4> \
PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s scripts/ci/p20/tests -v
```

12 métodos de teste aprovados. Incluem 30 processos PHP sintéticos (3 válidos e 27 recusados), com sentinela de escrita, sem SQL/socket real; invalidam DSN/perfil/role/DNS/libpq, banco/porta/diretório/cluster/TLS, resultados ausentes, exceção/fatal e `exit(0)` prematuro. Também verificam topologia fictícia, guards antes do SQL, rejeição de fonte P19 alterada, pin #914 e cópia integral do guard no contexto Portal. PHP 8.4.24; Python 3.12.14; lint PHP, AST Python, `bash -n` e `git diff --check` aprovados.

## Limites restantes

Docker/Compose ausentes no executor atual. Ainda faltam build real, validação integrada dos processos/PDO/PostgreSQL e quatro testes do Portal, convergência P18 atual e entrega/ACK/repetição CRM→Agents na mesma execução. O runner público é uma opção gratuita confirmada, mas não foi acionado: a tentativa autorizada de `git push -u origin test/p20-integrated-lab-preparation-2026-11-16` terminou com código 128 e `could not read Username for https://github.com: No such device or address`. Falta autenticação Git HTTPS no executor; não houve negativa de auto-review. `git ls-remote --heads` confirmou ausência da branch remota. Não foram modificadas credenciais nem tentadas outras rotas de publicação, conforme instrução expressa. O commit de preparação do workflow é `012c1df1e6cd0c3be0988037dd1f887cb7cc67df`; os 12 testes passaram novamente antes dessa tentativa. Nenhum PR/job novo existe. Não confundir os gates simulados aprovados com a garantia observada dos destinos no runner.

Sem alteração em migrations, autorização, regras de negócio, workflows existentes, main, rulesets, VPS ou infraestrutura compartilhada. DRAFT/NO-MERGE/NO-DEPLOY; homologação operacional permanece NO-GO.
