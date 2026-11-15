# Imagine CRM — homologação privada persistida

Este aplicativo é exclusivo da homologação fictícia em `crm-dev.imaginecomunica.com.br`.
Não é a liberação comercial do SaaS para clientes reais. O login é independente do Portal.
Laranja e cinza, logomarca Imagine, navegação responsiva e formulários acessíveis sem JavaScript externo.

## O que pode ser testado

| Fluxo | Comportamento |
|---|---|
| Cadastro | Login `lab-*`, empresa `EMPRESA_TESTE_*`, CNPJ de laboratório e termos simulados. |
| Verificação | OTP privado na própria sessão, 15 minutos, cinco tentativas, três emissões; nenhum e-mail/SMS enviado. |
| MFA | TOTP, códigos de recuperação de uso único, bloqueio de replay e revogação de sessões. |
| Trial | 14 dias, um por CNPJ fictício, histórico de chaves, 2 usuários, 20 leads, 2 funis e 2 automações. |
| CRM | Leads persistidos, edição de título, Kanban até ganho/perdido, controle de versão e auditoria. |
| Tarefas | Criação, vínculo ao lead, vencimento e conclusão persistidos. |
| Automações | Tarefa interna ao qualificar ou enviar para proposta; sem comunicação externa. |
| Equipe | Convite assinado, limitado à empresa, expiração e uso único; proprietário e membro com permissões diferentes. |
| CSV | Importação atômica até 20 KB, exportação e reimportação preservando etapas históricas; sem disparar automações retroativas. |
| Relatórios | Contagens e valores calculados dos registros da empresa autenticada. |
| Pedido | Plano, método fictício, aprovação, recusa e cancelamento simulados, com idempotência. |
| Administrador | Contas/trials/pedidos fictícios, suspensão, reativação com novo login e auditoria. Não abre leads de outras empresas. |
| Manutenção | Backup com chave independente, restauração em PostgreSQL separado, conferência de RLS e reversão do ingresso. |

Pagamento simulado **não** cria cobrança, nota fiscal, provisionamento, assinatura real ou direito de produção.
Após expirar o trial, os registros continuam legíveis e as alterações no CRM são bloqueadas,
mesmo que um pedido simulado tenha sido aprovado. Anexos, IA externa, mensagens e gateways reais
ficam expressamente indisponíveis; as franquias de 20 MB e IA 10 + 10 são referências do trial.

## Metodologia Imagine — Visitas → Leads → Clientes (homologação)

A versão de revisão da página comercial possui bloco trilingue (PT/EN/ES)
com os três pilares da Imagine e um formulário de simulação sem JavaScript:
visitas por semana, percentual hipotético de visitantes convertidos em
leads e percentual hipotético de leads que viram clientes.

O formulário realiza apenas uma solicitação GET da própria página.
`OpportunityScenario` aceita inteiros estritos com limites de 0 a
1.000.000 visitas e taxas de 0% a 100%; valores inválidos retornam
aos exemplos seguros. O cálculo arredonda a contagem de leads e,
em seguida, de clientes com aritmética inteira. Os parâmetros
não incluem nome, e-mail, CNPJ, consentimento ou contatos e não
são gravados no CRM. As hipóteses não representam médias de mercado,
estimativas contratuais ou garantias de conversão.

Dentro do CRM autenticado, o painel **Relatórios** possui agora a
assinatura da metodologia com dados obtidos somente da empresa
autenticada: quantidade de leads fictícios, negócios marcados
`won` (como indicação de clientes) e taxa de
`won / leads`. **Visitas permanecem “não medidas”**:
sem integração real de analytics não pode haver números fabricados.
A etapa `won` não prova contrato, pagamento ou cliente real.

O painel também lista um resumo **por funil da mesma empresa**, com
total de leads, ganhos e percentual de ganhos entre os leads daquele funil.
Um funil vazio apresenta zero, sem divisão por zero, e não são consultadas
oportunidades de outras organizações. O roteiro HTTP cria dois funis e
valida os dois resultados contra o estado real no PostgreSQL fictício.

O teste HTTP completo na suíte privada valida a calculadora
nos três idiomas, as proteções de entrada e o resultado persistido
do relatório após mover leads pelo funil; o teste Unit
`OpportunityScenarioTest` verifica também limites e
isolamento de campos não relacionados ao cenário.
A suíte Chromium preenche e envia o formulário sem JavaScript de
aplicação, confirma os cálculos em PT/EN/ES, procura indevidamente
campos de nome/e-mail/CNPJ e inspeciona o layout em 320, 390, 768 e
1.440 pixels. A CI só aprova a revisão depois desse fluxo e da
conferência dos acessos separados de cliente e administrador.

**Este recorte não cria lead magnet real, captação de PII, envio
de e-mail ou automação de nutrição.** Qualquer serviço comercial
com esses recursos depende de consentimento, retenção, LGPD e
gates de produção independentes.

## Instalação privada na VPS

Checkout dedicado: `/srv/imagine/crm-private-candidate`.
Segredos e evidências: `/srv/imagine/private-crm-journey`, modo 0700; ambiente e cofres em 0600.
Nenhum segredo pertence ao Git. O aplicativo não recebe o usuário proprietário nem a chave de backup.
PostgreSQL e Redis têm rede interna exclusiva. A única porta publicada é `127.0.0.1:18086`.

1. Selecionar a revisão exata da branch de revisão, sem alterar `main` ou outros projetos.
2. Executar `python3 scripts/ops/deploy-private-crm.py prepare`.
   Isso compila a imagem, inicia apenas o projeto privado, compila as telas e prova a restauração antes dos dados persistidos.
3. Executar `python3 scripts/ops/deploy-private-crm.py verify`.
   As credenciais automatizadas ficam apenas em `data/fixtures.json`; o teste nunca imprime senhas, fatores ou páginas de autenticação.
4. Confirmar a CI `Private CRM complete journey - isolated synthetic staging` **verde para o mesmo SHA**.
   Ela inclui HTTP real, Chromium desktop/mobile, backup/restauração e falhas/recuperação.
5. Pelo serviço CLI `tools`, executar `scripts/ops/seed-private-crm-fixtures.php revoke`.
   A revogação elimina o cofre de testes automatizados e conserva os registros de auditoria.
6. Executar `python3 scripts/ops/deploy-private-crm.py access`. Esse comando revoga o cofre automatizado, cria os acessos de revisão quando ausentes e testa senha, MFA, perfil e saída dos dois acessos.
   Os dois acessos de revisão são criados uma única vez em `data/review-access.json`, sem exibição em logs.
   O operador entrega os acessos por canal privado. Não publicar o cofre nem incluí-lo nos artefatos da CI.
7. Executar `python3 scripts/ops/publish-private-crm.py ID_DA_CI_VERDE`.
   O comando exige os comprovantes da VPS e a CI do SHA exato antes de trocar exclusivamente o host do CRM.
   Preserva o certificado e o BasicAuth existentes. Portal, Comercial e os demais hosts permanecem na configuração compartilhada.
8. Provar reversão e retorno: `python3 scripts/ops/switch-private-crm-ingress.py rollback`,
   seguido de `python3 scripts/ops/switch-private-crm-ingress.py resume`.
   Os comandos conferem as assinaturas dos arquivos, testam Nginx e verificam TLS/BasicAuth.
   Não alteram o banco nem removem registros.

Para usar o serviço CLI, informar `--env-file /srv/imagine/private-crm-journey/runtime.env`,
`-p imagine-crm-private-journey` e `-f infra/homologation/compose.yaml` ao `docker compose run --rm -T tools`.
Os scripts recusam sobrescrever credenciais ou configurações inesperadas.

## Acesso e roteiro de revisão

Abra o endereço do CRM, passe pela proteção privada existente e clique em **Entrar**.
O cliente é levado a `/painel`; o administrador, a `/admin`, após senha e MFA.
O cadastro de um novo trial também pode ser percorrido pela página inicial.

Cliente: criar lead → editar título → avançar no Kanban → concluir tarefa → consultar relatório →
exportar/reimportar CSV → convidar segundo usuário → simular pedido/pagamento → sair e entrar novamente.
Administrador: consultar contas e pedidos → suspender cliente → verificar sessão revogada → reativar →
exigir novo login → conferir auditoria. Os controles de empresa/perfil são verificados no servidor e no PostgreSQL.

## Evidências

A CI preserva apenas PNGs sem credenciais das telas comerciais e dos painéis desktop/mobile.
Os relatórios e cofres da VPS não são arquivos públicos. O operador deve consultar os JSONs protegidos
`prepare-verified`, `http-verified`, `access-verified`, `published`, `ingress-rollback-verified` e `ingress-resume-verified`
para afirmar o estado efetivamente alcançado; código preparado não equivale a publicação concluída.

## F248 — preparação de interoperabilidade com o Agents sem abrir rota

A integração Ed25519 de laboratório está na [PR #602](https://github.com/rafhael100/imagine-crm/pull/602), de uma árvore F0 sem rotas comerciais. Esta PR privada possui rotas legítimas de cliente e administrador: **não é correto fundir as duas árvores diretamente**, nem aplicar os gates de rota F245 da F0 ao CRM navegável.

A F248 inicia uma portabilidade explícita e pequena, mantendo o limite **NO-GO**:
`app/Support/Synthetic/AgentsSignedIngressCandidate.php` adapta o **envelope de entrada sintética, sem autorização**, sem definir rota, binding do container, token, credencial, grants, migrations ou operações de lead. Em runtime privado e produção, a callback nunca é invocada. Mesmo com a flag exata da CI, um envelope sintético com assinatura válida ou inválida sempre recebe **HTTP 404**, `DENIED_OR_NOT_FOUND`, `projection=null`, `real_authorization=false`, `external_calls=0`, cache desabilitado e nenhum cookie. Cabeçalhos de role, organização, tenant ou autorização são rejeitados antes da callback, impedindo que valores externos sejam confundidos com identidade.

A workflow `ci-crm-aia-f248-private.yml` baixa o verificador **original** do Imagine AI Agents na revisão imutável
`fe79e7e9a55e4d6728ab42c2ef578e40d9bd68ba` (não copia nem altera o algoritmo original). Confere a SHA, assina uma mensagem artificial com chave gerada deterministicamente só para teste e exercita a primeira prova, replay, alteração de conteúdo, assinatura forjada, cabeçalhos conflitantes e callback com exceção. O teste não registra rota no Laravel e verifica que nenhum endpoint real foi publicado.

**Essa entrega não inclui:** chave privada real, mTLS, cadastro operacional de peer, origem confiável da empresa, nonce PG distribuído, consentimento LGPD, auditoria de dados reais, política de acesso a leads, deploy ou `ALLOW`. F242–F247 continuam condicionadas à revisão/portabilidade individual e à autorização SEG/CORE/IAM/DBA/LGPD; não há nenhuma ativação da integração operacional.

## F249 — regressão de rotas Agents na aplicação privada real

A F248 garante que o adaptador sintético não foi registrado em roteamento;
a F249 complementa isso com um **teste HTTP de verdade no contêiner
isolado do CRM privado**, executado por
`scripts/ops/test-private-crm-http.py`.

O teste faz GET/POST com envelope artificial e cabeçalhos falsos para
endereços representativos de serviço (`/api/aia/lead/read`,
`/api/agents/op01/leads`, `/api/crm/agents/leads/*`,
`/internal/aia/op01/leads` e a rota inexistente F248).
Repete a matriz com (1) visitante, (2) cliente logado por
senha+MFA e (3) administrador global logado por senha+MFA.
Todos devem receber 404 e nenhuma resposta pode conter
referência de lead, principal, assinatura falsa ou `access_token`.
O teste não usa clientes reais, não cria rotas, não desabilita
CSRF e não tenta ler nenhum dado de empresa; ele procura
regressões que poderiam escapar dos testes F0, pois a homologação
possui rotas próprias legítimas.

**Os resultados de F248/F249 não substituem autorização específica,
identidade de serviço verdadeira, revisão SEG/CORE/IAM/DBA/LGPD,
mTLS e testes de invasão independentes.** Todos os endpoints
Agents e leituras reais continuam no estado NO-GO.

**Correção do gate de isolamento:** o primeiro candidato residia em `app/Homologation`; os testes gerais F0/PG16 recusaram corretamente a referência a `App\\Support\\Synthetic` num arquivo de runtime. O código foi movido para `app/Support/Synthetic`, a classe antiga excluída e o workflow passou a validar o caminho explicitamente sintético. O tripwire `SyntheticRuntimeIsolationTest` foi preservado sem exceções ou relaxamento.

## F250 — bloquear confusão entre identidade do serviço e sessão humana

A camada `App\\Homologation\\PrivateHeaders` do CRM privado nega
explicitamente as seguintes sugestões de identidade de serviço nos
**endpoints humanos**: `X-Aia-Claims`,
`X-Aia-Signature`, `X-Aia-Key-Id` e
`X-Service-Identity`. Tais campos não são login, cookie,
membership, MFA nem autorização. A presença de qualquer um
deles em rotas comerciais humanas resulta em HTTP 400 antes de
execução do controller.

No teste HTTP da pilha real, isso é verificado sobre `/login`
de visitante, `/painel` do cliente com sessão válida e
`/admin` do administrador com senha + MFA, garantindo
que nenhum desses perfis seja elevado ou redirecionado para
uma identidade de serviço apenas por headers. As regras de
negação de rotas Agents e a prova Ed25519 de laboratório F248
continuam independentes.

**O bloqueio não autoriza uma rota S2S futura**, não cria tokens,
não abre leitura de leads e não identifica provedores externos.
Uma eventual integração real requer um ingresso separado e
homologado que nunca reutilize login/CSRF/sessão humanos.

## F251 — análise de manutenção da VPS, somente leitura

A primeira publicação privada da aplicação utilizou a revisão
`ea5d320addaeb7ab970bdfa3931d55ddea37afac`. Desde então, a PR
de homologação recebeu modificações no controlador, na validação de
cabeçalhos, na calculadora e nos relatórios; **não é mais uma mudança
exclusivamente de views**. O utilitário anterior
`update-private-crm-marketing.py` rejeita corretamente essa
nova revisão pelo limite de arquivos permitidos.

A F251 acrescenta `scripts/ops/private-crm-release-preflight.py`
e testes de política, sem efetuar qualquer implantação. O programa
compara a revisão publicada com uma candidata **exata** e rejeita:
SHA fora da árvore publicada, checkout alterado, caminhos não
previstos, renomes, exclusões, mudanças em migrations/roles/schema,
rotas, bootstrap, provider, Composer e infraestrutura. Exige uma
diferença efetiva de aplicação privada e apresenta somente a lista de
arquivos de escopo revisável. Mesmo com a lista aprovada, o resultado
é sempre `MANUAL_REVIEW_REQUIRED`, `deploy_approved=false` e
`no_deploy_executed=true`.

A CI `ci-private-crm-release-preflight.yml` usa checkout completo
somente leitura, verifica ancestralidade da revisão privada anterior,
executa testes negativos da política e comprova que o diff atual
permanece **candidato a revisão**, não autorização de publicação.

**Gates operacionais ainda necessários:** o operador deve validar as
17+ CI do SHA completo e obter revisão independente para mudanças
de middleware e integração; conferir env/roles/tabelas da VPS,
backup criptografado/restauração, rollback da imagem anterior, TLS,
BasicAuth, isolamento de outros hosts e smoke pós-publicação.
**A F251 não instala, reinicia, publica, emite mensagem ou acessa
a VPS.** O script `update-private-crm-marketing.py` continua restrito
a views; sua lista não foi ampliada artificialmente para contornar
o bloqueio desta atualização funcional.

## F252 — atualização conservadora da aplicação privada já publicada (sem GO automático)

A revisão anteriormente publicada é ea5d320addaeb7ab970bdfa3931d55ddea37afac.
Este procedimento existe porque o deploy inicial recusa sobrescrever um ingresso existente, e
o utilitário anterior de manutenção aceita apenas views. **Nenhum script deste pacote
realiza SSH, cron, deploy automático, merge em main ou liberação comercial.**

**Escopo F252:** atualizar somente a imagem da aplicação privada sob
/srv/imagine/crm-private-candidate, mantendo o mesmo PostgreSQL, Redis, rede,
porta 127.0.0.1:18086, configurações Nginx, TLS, BasicAuth e demais projetos.
O classificador F251 deve aceitar o diff **somente como MANUAL_REVIEW_REQUIRED**.
Mudanças em migrations, papéis, rotas, providers, Compose, Composer ou caminhos
não classificados interrompem o processo.

### Bloqueios obrigatórios antes de operar na VPS

1. Inspeção independente de segurança e operações, com duas pessoas identificadas,
   seus registros de evidência e autorização manual da Direção. A presença de um JSON
   não prova a independência nem substitui a revisão dos documentos.
2. CI da revisão exata concluída em sucesso: jornada completa, preflight F251 e
   contrato de atualização F252, além de **todas as checks** daquele SHA.
3. Backup criptografado e restauração íntegra em PostgreSQL separado e sem rede;
   o banco operacional não será restaurado automaticamente, pois não há migrações.
4. Acesso legítimo do operador à VPS, sem copiar segredos para GitHub ou chat.
   O arquivo runtime.env, o cofre de revisão e os registros são protegidos (root/0600).
5. Identidade da implantação verificada na VPS: published.json, runtime.env,
   imagem Docker em execução, hashes das configurações, HTTPS/BasicAuth dos hosts
   CRM, Portal e Comercial, e ausência de fixtures automatizadas.

### Registro de aceite (fora do Git, root/0600)

O operador registra em
/srv/imagine/private-crm-journey/approvals/private-upgrade-SHA_CANDIDATA.json
um objeto no formato abaixo, substituindo os valores ilustrativos por evidências
verificadas e mantendo a revisão dentro do diretório protegido. **Não criar
aprovação fictícia para satisfazer o script.**

```json
{
  "published_sha": "SHA_REAL_DA_VERSAO_PUBLICADA",
  "candidate_sha": "SHA_REAL_DA_CANDIDATA",
  "scope": "private-synthetic-only",
  "authorized_by": "IDENTIFICACAO_DO_RESPONSAVEL",
  "manual_private_release": true,
  "reviews": {
    "security": {
      "reviewer": "PESSOA_RESPONSAVEL_SEGURANCA",
      "decision": "approved",
      "evidence": "REGISTRO_DE_REVISAO_VERIFICAVEL"
    },
    "operations": {
      "reviewer": "PESSOA_RESPONSAVEL_OPERACOES",
      "decision": "approved",
      "evidence": "REGISTRO_DE_REVISAO_VERIFICAVEL"
    }
  }
}
```

Os dois revisores devem ser distintos. A revisão independente de SEG/DBA e o
aceite CORE/LGPD de produção continuam como gates próprios; **nenhum
aceite de homologação fictícia os substitui.**

### Sequência de comandos do operador (somente após satisfazer todos os gates)

Acessar por canal protegido a VPS autorizada, confirmar que o checkout está na
revisão exata e que os arquivos não foram alterados. Não executar o candidato
em outro projeto ou aplicar o Compose antigo na porta 18080.

```bash
cd /srv/imagine/crm-private-candidate
sudo python3 scripts/ops/private-crm-upgrade-f252.py plan ID_DA_CI_EXATA
sudo python3 scripts/ops/private-crm-upgrade-f252.py apply ID_DA_CI_EXATA
```

O comando plan faz inspeção e não atualiza contêineres. O apply exige aprovação
privada, classificações F251, checks completas, backup e restore, compila a
imagem candidata, recria somente app/web do CRM, verifica as telas HTTP e
autenticação cliente/admin (sem imprimir fatores), confirma HTTPS/BasicAuth
e faz novo backup/restauração. Em erro durante o upgrade, tenta voltar à imagem
anterior, sem tocar banco, segredos ou Nginx. Em falha de reversão, o operador
deve interromper a liberação e seguir o plano de incidente.

Reversão operacional deliberada (somente se a revisão atual ainda for F252
e os registros de release estiverem íntegros):

```bash
cd /srv/imagine/crm-private-candidate
sudo python3 scripts/ops/private-crm-upgrade-f252.py rollback
```

**Aceite final na VPS após o apply:** navegador humano atravessando BasicAuth,
cadastro/OTP fictício/trial, login cliente, MFA/recuperação, leads e Kanban,
tarefas, automação interna, CSV, convite, pedido simulado, relatório,
acesso admin separado, suspensão/revogação e reativação; conferir RLS de duas
organizações, backup e isolamento dos outros hosts. O operador registra o
resultado na issue CRM-LAUNCH-01 e na evidência protegida. Nenhum desses
itens permite cadastro real, SMTP/SMS, Asaas/C6, NFS-e, captação Sites/Portal,
IA externa ou lançamento do SaaS sem homologações independentes.

**Limites desta entrega:** o código e o workflow F252 só podem demonstrar
política e CI sem acesso SSH. A atualização efetiva exige operador com acesso
à VPS e revisões independentes, sem promessas de deploy a partir do GitHub.

## F253 — aceitação funcional obrigatória após troca de imagem

A F252 já oferece ao operador comandos protegidos para
`plan`, `apply` e `rollback`; **nenhum foi executado por esta PR**.
A F253 acrescenta um teste de aceitação que deve passar **após a
instalação do candidato e antes de alterar os marcadores de revisão
publicada**. O arquivo `scripts/ops/private-crm-f253-canary.py`
consulta somente a aplicação privada em `127.0.0.1:18086`, com
`Host: crm-dev.imaginecomunica.com.br`, sem credenciais nem cookies.

Critérios verificados:

- Página inicial e login HTTP 200, `no-store` e `noindex`;
  bloco real da metodologia Visitas → Leads → Clientes;
- Calculadora funcional em português, inglês e espanhol:
  2.500 visitas, 8% de conversão em leads, 13% de leads em
  clientes gera **200 leads e 26 clientes**, sem campos para
  nome, e-mail ou CNPJ;
- Entradas artificiais nos endereços Agents ainda não publicados
  devem permanecer HTTP 400/404 e não podem refletir identificadores,
  chave, assinatura ou token;
- Cabeçalhos `X-Aia-Claims` nos endpoints humanos
  `/login`, `/cadastro` e `/recuperar` devem retornar HTTP 400.

A aplicação deste canário ao fluxo F252 coloca
`verify_candidate_canary()` **entre** `local_smoke()`
e `review_access(new_env)`. Se o teste falha, o modo
`apply` aborta e tenta a reversão protegida da imagem anterior.
**Não** adicionamos canário da nova versão ao modo rollback,
pois a versão antiga não possui os recursos mais novos.

A CI F253 executa testes sem rede, e a suíte de jornada
privada executa o canário **contra o contêiner real em loopback**
antes do encerramento dos testes. A rotina de publicação F252
passa a exigir um check independente
`private-f253-post-switch-canary` no SHA exato além dos demais.

F253 não é aprovação de release: mantém
`MANUAL_REVIEW_REQUIRED`, pareceres SEG/OPS, backup/restore,
reversibilidade, TLS e BasicAuth dos demais hosts, validação
de usuário e administrador com MFA, revisão humana do navegador
e bloqueio de dados reais, faturamento ou integração Agents.
