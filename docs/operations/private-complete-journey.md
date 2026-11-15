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

O teste HTTP completo na suíte privada valida a calculadora
nos três idiomas, as proteções de entrada e o resultado persistido
do relatório após mover leads pelo funil; o teste Unit
`OpportunityScenarioTest` verifica também limites e
isolamento de campos não relacionados ao cenário.

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
