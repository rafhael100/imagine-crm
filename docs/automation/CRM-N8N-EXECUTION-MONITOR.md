# Acompanhamento privado das execuções n8n

## Objetivo e dependência

Esta candidata apresenta, na área privada do CRM, o acompanhamento persistido
pela [PR #650](https://github.com/rafhael100/imagine-crm/pull/650), base exata
`be9c1243a45ab867309a89f8c40d61dc44b7a7f4`. O retorno assinado do n8n foi comprovado
pela [PR Agents #472](https://github.com/rafhael100/imagine-ai-agents/pull/472),
HEAD `8e7f1f21a2927bde3e337edaace0e1e6dffdc29a`.

A tela lê o estado já salvo no CRM. Ela não consulta o Agents, não reconcilia o
evento, não chama o n8n e não altera leads, tarefas, regras ou a fila. O último
retorno é uma observação histórica, com horário explícito; não é uma promessa
de atualização contínua ou de conclusão comercial.

## Navegação e apresentação

O menu **Execuções n8n** aparece somente quando o runtime privado já está
habilitado e `CRM_N8N_EXECUTION_MONITOR=synthetic-private-v1`. A flag fica vazia
por padrão. O caminho reutiliza a rota privada existente:

- `/painel?section=n8n`: até 50 eventos da própria empresa, do mais recente ao
  mais antigo, com totais identificados como referentes à lista exibida.
- `/painel?section=n8n&event=<uuid>`: último retorno e até 20 observações,
  ordenadas pela sequência persistida, com a contagem total do histórico.

Os parâmetros admitidos nesta seção são `section`, `event` e `lang`. Não há
parâmetro que selecione empresa, identidade, grant ou URL de serviço. Uma seleção
inexistente ou de outra empresa retorna a mesma indisponibilidade. A navegação
entre PT, EN e ES preserva o evento selecionado e os links de retorno preservam
o idioma.

A interface diferencia:

| Apresentação | Fato correspondente |
| --- | --- |
| Sem retorno registrado | Não existe projeção local; não implica NOT_STAGED |
| Aguardando preparação | NOT_STAGED confirmado por uma observação |
| Aguardando início | NOT_STARTED |
| Início registrado | STARTED: intenção durável, sem resultado comprovado |
| Resultado recebido | RESULT: preparo técnico, confirmação pendente |
| Confirmação registrada | ACKED: preparo e confirmação persistidos |
| Resultado incerto | UNKNOWN: exige análise, sem repetir automaticamente |

O estado da entrega do evento continua separado do estado da execução. A fila
encerrada e o resultado incerto recebem indicação textual de análise. A cor é
apoio visual, e não o único meio de identificar uma situação. Não há botões de
executar, repetir, liberar ou alterar workflows nesta seção.

Os horários estão explicitamente em UTC. A referência do evento pode ser usada
para localizar o registro; payloads, assinaturas, hashes, chaves e grants não
integram o modelo de apresentação nem o HTML.

## Fronteira de autorização

`Workspace::run('view')` reaproveita a cadeia já existente: ambiente e conexão
privados, principal autenticado com sessão/MFA atuais, conta ativa de cliente,
organização resolvida pelo servidor e membership ativo sob lock transacional.
O novo reader é chamado dentro dessa mesma operação autorizada. O perfil
administrativo não passa a ter acesso aos dados comerciais das empresas.

`app.tenant_id` continua sendo contexto transacional, nunca identidade ou
autorização por si só. A função SQL depende da autorização humana feita antes
pela aplicação. Ela não recebe um identificador de empresa do navegador e não
redefine esse contexto. A conexão conserva os controles existentes contra
contexto residual, ownership e roles privilegiadas.

O login web não recebe a senha do reconciliador, certificados S2S ou chaves de
assinatura. No schema de serviço, recebe somente USAGE e EXECUTE da função
`crm_auto_service.read_execution_monitor(uuid)`, sem acesso direto às tabelas
ou às funções que gravam observações, enviam ou confirmam eventos.

O grant de acompanhamento S2S autoriza buscar novos retornos no Agents. A
revogação desse grant não apaga nem oculta automaticamente o histórico local de
uma pessoa cuja sessão e vínculo com a empresa continuam válidos. Revogação
da sessão, conta ou membership humano continua impedindo a leitura.

## Consulta local e consistência

O SQL opcional `execution-monitor-candidate.sql` exige a instalação sintética e
a extensão de acompanhamento anterior. Não cria tabelas, roles ou logins.

A função tem owner restrito, `SECURITY DEFINER`, `search_path` fixo, referências
qualificadas e EXECUTE revogado de PUBLIC. É `STABLE` e contém apenas leituras:
usa o mesmo snapshot da consulta para lista, projeção, histórico e contagens.
Não chama `execution_candidate` ou `read_execution_projection`, que pertencem
ao reconciliador e tomam locks sobre o transporte.

O reader verifica a origem local e os vínculos das observações antes de expor
campos de apresentação. Divergência de fonte, projeção ou histórico resulta em
indisponibilidade; não é convertida em uma lista vazia ou sucesso. A classe PHP
valida novamente o contrato fechado, empresa, identificadores, limites,
estados, sequência e coerência da seleção antes de renderizar o Blade.

Referências da semântica PostgreSQL 16:
[volatilidade e snapshots](https://www.postgresql.org/docs/16/xfunc-volatility.html)
e [funções SECURITY DEFINER](https://www.postgresql.org/docs/16/sql-createfunction.html).

## Backup e testes

O perfil opcional `synthetic-private-s2s-execution-monitor-v1` acrescenta a
função de leitura ao inventário fechado do perfil anterior. A restauração
preserva o owner, a definição e os grants, mantendo as roles inertes. Não há
relaxamento das allowlists de atualização F251/F252.

A validação desta candidata separa duas evidências:

1. A PR #472 já comprovou n8n nativo, retorno mTLS assinado, persistência real e
   recuperação na base #650. Essa prova não se transforma em teste da nova tela.
2. A nova CI usa o runtime privado real do CRM com fixtures de apresentação
   sintéticas, sessão, MFA, banco e navegador reais. Testa leitura por empresa,
   recusas de acesso, fonte adulterada, ausência de retorno, estados,
   navegação, limites, isolamento e ausência de alterações nos registros de
   negócio e serviço. Screenshots incluem apenas dados fictícios de teste.

As regressões anteriores da jornada privada rodam com a nova flag desligada.
O ensaio de backup cobre o perfil novo além dos perfis base, S2S e execução.
Os resultados executados e as capturas pertencem à SHA exata registrada na PR;
este documento não antecipa aprovação da CI nem aceite visual humano.

## Limites da entrega

DRAFT / NO-MERGE / NO-DEPLOY. Somente a aplicação privada sintética e runners
descartáveis. Esta candidata não libera uma rota de produção, acesso a dados
de clientes, novas regras comerciais, edição do n8n, credenciais operacionais,
VPS ou custos. A liberação operacional e a revisão dos gates permanecem
independentes dos testes deste recorte.
