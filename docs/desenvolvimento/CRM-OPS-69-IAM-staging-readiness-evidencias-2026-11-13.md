# CRM OPS-69 — IAM de produção simulada e preparação de staging privado (13/11/2026)

## Resultado distinto de aprovação e de infraestrutura

**DEV verificada:** c26e4d0091081d9e0695160d904b8880deb448a6. **main preservada:** e85e5dae2e8bd7a6af1e5c8d131f212fdc19c012.

### Entrega integrada: F3-262, PR #516

[PR #516](https://github.com/rafhael100/imagine-crm/pull/516), mesclada **somente em DEV** no commit c26e4d0091081d9e0695160d904b8880deb448a6; HEAD validada 3f78686aac8362b1d6e3aaa472b2ed1d6b71258b.

A prova CLI em runner GitHub efêmero usa APP_ENV=production **simulada**, CRM_AUTH_EXPERIMENTAL=true proposital, SQLite :memory:, mailer array e Composer --no-dev. Exige que o guard/provider CRM permaneça ausente, que um principal não verificado continue negado, Authorizer siga deny-by-default e que o container recuse ExperimentalAuthenticator e LaboratoryProvisioner. Prova isolada, não habilita login ou signup.

Cinco checks da PR na SHA testada, todos completed/success:
- [F0 PHPUnit](https://github.com/rafhael100/imagine-crm/actions/runs/43515855197)
- [PostgreSQL/RLS](https://github.com/rafhael100/imagine-crm/actions/runs/43515855169)
- [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43515855181)
- [Agents](https://github.com/rafhael100/imagine-crm/actions/runs/43515855264)
- [No-dev + flag IAM hostil](https://github.com/rafhael100/imagine-crm/actions/runs/43515855163)

O commit de merge DEV também passou F0 [#43516309408](https://github.com/rafhael100/imagine-crm/actions/runs/43516309408), PostgreSQL/RLS [#43516309250](https://github.com/rafhael100/imagine-crm/actions/runs/43516309250), Sites [#43516309757](https://github.com/rafhael100/imagine-crm/actions/runs/43516309757) e Agents [#43516309467](https://github.com/rafhael100/imagine-crm/actions/runs/43516309467).

### Candidato de infraestrutura ainda NÃO integrado: F3-263, PR #517

[PR #517](https://github.com/rafhael100/imagine-crm/pull/517), **DRAFT / NO-MERGE / NO-DEPLOY**, HEAD 71796f53d14e363acd6e44615123a7b7569a97cf. Proposta de PHP-FPM/Nginx com bind 127.0.0.1:18080, rede interna, SQLite em memória, mailer e sessão array, auth experimental false; sem Postgres real, credenciais, dados ou porta de acesso remoto. **Nenhum container foi construído ou iniciado**.

Cinco checks concluídos success na HEAD da proposta:
- [F0 PHPUnit (contrato fonte)](https://github.com/rafhael100/imagine-crm/actions/runs/43516932626)
- [PostgreSQL/RLS (regressão separada)](https://github.com/rafhael100/imagine-crm/actions/runs/43516932634)
- [Sites](https://github.com/rafhael100/imagine-crm/actions/runs/43516932760)
- [Agents](https://github.com/rafhael100/imagine-crm/actions/runs/43516932796)
- [Docker Compose config --quiet](https://github.com/rafhael100/imagine-crm/actions/runs/43516932800)

**Alcance real**: os checks de F3-263 demonstram apenas sintaxe da configuração e invariantes estáticas de origem, não execução, imagens auditadas, TCP/HTTPS, rede Docker, segredos, banco PostgreSQL, RLS, migrations, backup ou segurança da VPS.

### Gates pendentes (não resolvidos por CI)

- [SEG/DBA #27](https://github.com/rafhael100/imagine-crm/issues/27): revisão independente de IAM, RLS, papéis, criptografia, backup/rollback, isolamento de infra.
- [CORE/LGPD #259](https://github.com/rafhael100/imagine-crm/issues/259): identidade real, consentimento, tenant, recebimento Portal→CRM, retenção e tratamento de dados.
- [UX #416](https://github.com/rafhael100/imagine-crm/issues/416): revisão visual para cadastro e interface Imagine.
- Imagens com digest pin, prova de build e runtime, redes, TLS, fail-closed de ambiente, scripts de rollback e implantação isolada na VPS ainda não executados. Nenhum processo da VPS modificado.

**NO-GO:** não há autorização para merge de #517, deploy, alteração main, publicação URL, migration operacional, criação de usuário, trial ou cadastro real, cobrança, gastos ou uso de dados de clientes. Próxima tarefa é revisão do pacote de staging por infraestrutura/SEG para liberar uma homologação privada real com escopo controlado.
