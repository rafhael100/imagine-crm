# CRM → Agents: rascunho do owner em homologação

## Resultado e autorização

O recorte especificamente aprovado pelo responsável permite que o owner ativo da empresa crie e consulte um rascunho no Agents a partir de um planejamento comercial existente no CRM. A implementação continua em candidata DRAFT sobre `d3f06599cddfc244f6089d3dac01f29e5a45b8c0`, sem merge em DEV/main, deploy, dados de clientes ou chamadas de IA. Essa autorização funcional de homologação não altera os gates de produção ou o autorizador geral deny-by-default.

O CRM permanece responsável pela regra, identidade, empresa e permissão. A escolha inicial é o planejamento `followup_qualified` criado pelo motor nativo, pertencente ao owner atual e ainda coerente com a origem. Não há um editor ou motor comercial duplicado no Agents.

## Jornada humana

Na Central de automações, o owner encontra **Criar rascunho no Agents** junto de um planejamento próprio elegível. O formulário transmite somente o token CSRF e a referência de origem presente no caminho. Empresa, autor, regra, revisão e conteúdo são resolvidos no backend.

A página do rascunho mostra sua referência, regra, lead de origem, autor, planejamento, revisão e prazo original. Todas as aberturas consultam o Agents novamente com a autoridade atual. A Central oferece o link de retorno ao rascunho por um mapa local autorizado de referências; esse mapa não confirma a existência remota.

| Estado apresentado | Significado |
| --- | --- |
| Confirmado | O Agents devolveu o rascunho com resposta atual, assinada e vinculada à consulta |
| Pendente, resposta `ABSENT` | Houve consulta autenticada, mas o rascunho correspondente não foi encontrado |
| Pendente, HTTP 503 | A consulta não produziu confirmação válida; o estado remoto permanece indeterminado |
| Acesso recusado | A autoridade atual não permite a operação; não há projeção do rascunho |

Um registro local `confirmed` conserva a confirmação histórica; não substitui a consulta atual. Se o Agents devolver `ABSENT` para algo já confirmado, o CRM recusa uma recriação automática. O fluxo não aprova nem executa a ação do rascunho.

## Autoridade e origem

Criação **e consulta** exigem o guard humano do CRM, sessão e MFA válidos, conta `client` ativa, `team_role=owner` e participação ativa na empresa resolvida da conta. `member` é negado em ambas; admin global não recebe acesso comercial por seu papel administrativo. Uma alteração de papel vale também para o autor histórico.

Para criar ou efetivamente reenviar, o CRM verifica a origem persistida: ator, estado pendente, modelo, configuração atual, lead/versão e sessão causal. O `RuleDefinition` original reavalia o evento de mudança de etapa no instante original e compara o comando completo e sua chave idempotente. `settings_version=0` permanece válido quando legado; a versão do comando continua `max(1, settings_version)`. O prazo vem do comando original, não do `due_at` mutável usado em retries do motor nativo.

O motor não é chamado para enfileirar, processar ou cancelar nada durante criação/consulta do rascunho. Lead, tarefa, planejamento e configurações são preservados pelo novo fluxo. A leitura histórica dispensa a continuidade da elegibilidade da origem, mantendo a permissão atual do leitor.

## Transação, prazo e reconciliação

A primeira unidade verifica owner, sessão e participação e **commita a intenção antes de qualquer I/O**. A segunda unidade verifica novamente a autoridade e mantém os locks durante o transporte. Criação usa o prazo mínimo de sessão, limite já comitado, grant, trial e sessão causal; leitura usa sessão, limite já comitado e grant.

A renovação de idle feita numa unidade ainda não comitada não amplia o prazo da atestação. Leitura e retry também preparam e comitam uma janela antes do I/O, depois revalidam a autoridade. Os prazos são novamente conferidos no retorno e depois da auditoria. Essa disciplina não cria uma transação distribuída.

Timeout, resposta inválida ou falha na confirmação/auditoria local podem ocorrer depois do commit do Agents. A intenção continua pendente e reconciliável. **Consultar nunca envia CREATE.** O retry consulta primeiro; somente uma ausência autenticada, com origem ainda elegível e grants atuais, permite reenviar o mesmo rascunho. Identidade de negócio e autoria permanecem fixas; nonce e validade da tentativa são novos.

## Comunicação e persistência

Os únicos destinos são `https://agents-s2s:8443/private/owner-drafts/create` e `/read`. O cliente exige certificado servidor e mTLS, usa chaves distintas por operação e não encaminha cookies, senhas, tokens, headers de autoridade ou identificadores de sessão humana. O corpo, seus tipos e a resposta são contratos fechados, com JSON duplicado recusado, HMAC por domínio e vínculo de empresa/ator/operação/draft/hash/nonce/key ID.

O addon `infra/homologation/s2s/owner-drafts.sql` é executável somente no banco fictício e perfil de instalação esperado. Acrescenta intenção imutável e grants por empresa/operação sob RLS obrigatório. A aplicação altera apenas estado de confirmação e instante correspondente; não recebe UPDATE do conteúdo/autoria. O marcador técnico de lock da conta concede ao IAM somente UPDATE daquela coluna, sem editar papel, status ou empresa.

O Agents guarda a autoria `CRM_AUTHENTICATED_OWNER` em estrutura própria; esse registro é uma atestação do CRM sobre seu usuário, não uma assinatura pessoal nem uma reclassificação dos autores sintéticos anteriores.

## Verificação reproduzível

A CI dedicada verifica evento real de PR, base, HEAD e imagem exatos antes de criar o ambiente. O fornecedor Agents é fixado por SHA revisada e composto com a Central P30 `c08688b16e61d8d1ecc4d8c0ed4da8758a687ee8`; cada blob exportado é conferido. Certificados e segredos são novos, protegidos e descartados com o projeto Docker. O banco Agents e os endpoints técnicos não possuem portas publicadas no host.

A matriz HTTP usa login/MFA reais, criação da origem pelo motor nativo, mTLS, PostgreSQL, gravação remota e leitura. As falhas de ACK são introduzidas somente por wrappers de teste depois de o handler original produzir sua resposta; o runtime de produto não possui switch de falhas. A auditoria local é recusada por um trigger temporário da fixture para comprovar reconciliação após commit remoto.

Chromium cria sua própria origem na sessão atual, usa o formulário nativo e navega entre Central e detalhe. Verifica PT/EN/ES, larguras de celular/desktop, apresentação pendente e permissão member. Somente capturas verificadas sem credenciais são preservadas. Os workflows anteriores de jornada completa e leitura mínima também são executados na mesma candidata, com o novo perfil desativado.

Resultados, contagens e links de CI são registrados na PR da SHA executada. Sintaxe ou revisão textual local não são anunciadas como execução de PHP, banco, transporte ou navegador.

## Limites restantes

Este addon não foi aplicado a uma instalação persistente. Restauração e promoção precisam incluir as novas estruturas e preservar autoria, histórico de intenção e anti-replay. A existência de infraestrutura ou storage externo não é autorização para migrar estes registros. GO operacional, promoção de branches, custódia de credenciais reais e qualquer efeito executável da regra permanecem decisões separadas do recorte implementado.
