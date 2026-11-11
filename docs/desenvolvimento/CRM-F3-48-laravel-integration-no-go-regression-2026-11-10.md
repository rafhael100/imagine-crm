# CRM F3-48 — integração fechada também no runtime Laravel (NO-GO)

**Data:** 10/11/2026. **Escopo:** testes de aplicação, sem concessão de acesso.

## Motivação

As suítes F3-37 e F3-44..F3-47 cobrem partes estáticas e simulações independentes. Um PR inadvertido poderia expor uma rota PHP/Laravel operacional, vincular outra implementação do autorizador ou ativar o perfil experimental mesmo enquanto os protocolos sintéticos continuam passando.

## Nova barreira

`tests/Feature/IntegrationBoundariesRemainClosedTest.php` inicializa a **aplicação Laravel real no ambiente de testes**, exigindo:

1. `Authorizer::class` resolvido via container seja `DenyByDefaultAuthorizer` (sem ALLOW de negócio).
2. Perfil de identidade experimental desabilitado por padrão.
3. A coleção real de rotas do Laravel continue expondo somente `/up` (sem rotas de CRM, importação ou integração).
4. GET e POST de caminhos de Portal, Agents, importação/exportação de leads retornem 404, sem depender de token ou tenant declarado.
5. O armazenamento local privado mantenha desativado `serve` e não publique a rota automática de arquivos.

O teste **não cria** endpoints, credenciais, dados, grants, migrations ou configuração de produção. É uma verificação negativa de regressão; não substitui análise de IAM/SQL/RLS, revisão de segurança nem homologação funcional.

## Critérios de integração

F0 e PostgreSQL/RLS verdes na SHA exata do PR, sem conflitos com DEV e sem alteração de main. Manter as tarefas Portal/Agents (#209..#218 e #221) em seus gates próprios, sem interpretar CI verde como liberação de dados reais.

**Gates:** CORE/SEG/DBA/AUT/IN-118/LGPD e autorização operacional seguem abertos. Nenhum custo, VPS ou deploy.
