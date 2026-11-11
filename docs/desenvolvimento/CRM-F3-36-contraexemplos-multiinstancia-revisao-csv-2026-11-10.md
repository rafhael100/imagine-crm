# F3-36 — Contraexemplos de revisão CSV em instâncias independentes

**Data:** 10/11/2026. **Estado:** evidência negativa de laboratório; **NÃO HOMOLOGA CONCORRÊNCIA REAL**. Referências: [F3 #35](https://github.com/rafhael100/imagine-crm/issues/35), [F3-32 (proposta não aprovada)](CRM-F3-32-contrato-proposto-revisao-autenticada-versao-confiavel-lote-2026-11-10.md), F3-33 (simulador em memória), F3-35 (janelas de falha), [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27).

## O que os testes de laboratório mostram

A classe `CsvReviewRaceFixture` funciona apenas dentro de **um único objeto PHP**. F3-35 acrescentou uma simulação em que um registro local é feito, mas a resposta se perde; repetir na mesma instância pode devolver `fixture_idempotent_replay_no_op`. Isso **não** demonstra que duas instâncias/servidores ou uma nova sessão conseguem detectar uma gravação anterior.

O arquivo `tests/Unit/CsvReviewCrossInstanceBoundaryTest.php` oferece **contraexemplos intencionais**, sem dados reais e sem threads/processos reais:

| Evidência | Resultado esperado no laboratório | Implicação para produção |
|---|---|---|
| Dois objetos com mesma matriz e mapeamento | Mesmo checksum público e versão inicial | Hash não identifica objeto confiável, servidor, humano ou tenant |
| Ambos recebem a mesma chave/mesma decisão/mesma versão | Ambos devolvem `fixture_recorded_only` em seus estados separados | **Falha** de deduplicação global; requer armazenamento e constraint confiáveis |
| Mesma chave com decisões diferentes em instâncias separadas | Ambos conseguem registrar localmente | Conflito só é detectado dentro da mesma instância |
| Um objeto incrementa versão | Outro objeto mantém versão antiga | Não existe compare-and-swap entre processos |
| Um objeto troca o lote | Outro continua com marcador antigo e pode registrar na própria memória | Não existe snapshot do servidor compartilhado |
| ACK perdido e reinício como novo objeto | Nova instância pode aceitar a mesma operação como nova | Sem recuperação idempotente durável |
| Volta aos mesmos bytes após duas alterações em uma instância | Seu contador cresce, mas outra instância não sabe disso | Versão monotônica apenas local, sem proteção contra restore global |

**Importante:** os testes `PASS` significam que estes comportamentos **inseguros para produção** foram reproduzidos como contraexemplos esperados. Eles **não** testam concorrência real, PostgreSQL, RLS, lock, deadlock, rollback, integridade de banco ou autorização de usuário. Não podem ser citados como "RV-08 aprovado" ou "segurança de idempotência comprovada".

## Requisitos mínimos para fechar os bloqueios, ainda NÃO EXECUTADOS

1. **Versão persistente monotônica:** identificador de lote e revisão definidos em backend confiável; compare-and-swap atômico pela organização, lote, versão, estado e política no PostgreSQL; a revisão não pode escolher tenant por parâmetro.
2. **Idempotência durável:** constraint única escopada, chave vinculada ao ator/organização/operação/payload, tratamento transacional de duplicidade e conflito, inclusive após perda de ACK, restart, failover, rollback e replay.
3. **Auditoria consistente:** evidência de decisão autenticada, versão e resultado dentro da mesma transação autorizada; falha na trilha deve impedir confirmação, não apenas produzir log posterior.
4. **Autorização forte:** sessão/membership em fonte confiável, revogação/MFA, escopo recurso/ação/campo por empresa, RLS de defesa adicional e nenhum `ALLOW` genérico.
5. **Homologação descartável real futura:** teste de *dois processos* competindo pela mesma versão/idem key, commit e ACK perdidos, encerramento de conexão, rollback e recovery, restauração de snapshot antigo, segregação por tenant e logs sanitizados.
6. **Aceite técnico:** revisor SEG/DBA independente e responsáveis por CORE/IAM/Privacidade/Produto aprovam contratos e evidências antes de habilitar qualquer escrita de negócio.

**Bloqueios:** não foi implementado nenhum item acima. A proposta F3-32-D01..D10 continua pendente de homologação. [PR #175](https://github.com/rafhael100/imagine-crm/pull/175) permanece DRAFT sem parecer independente; Central IN-118, S01/CORE, LGPD e SEG/DBA #27 seguem NO-GO.

## Limites do presente PR

Somente testes negativos de instâncias de **objetos PHP em memória** e este documento. Sem patch no autorizador, banco, migrations, API, grants, dados reais, leitura de CSV, upload/import/export, job, n8n, VPS, custos, `main` ou produção. O pipeline PostgreSQL/RLS, quando verde, executa a suíte de laboratório, **não** a concorrência real. Merge permitido apenas DEV após revisão do diff e workflows F0/PostgreSQL RLS verdes na HEAD exata.
