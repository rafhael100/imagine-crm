# CRM F3-240 — protótipo de cadastro OTP offline com identidade Imagine original

Protótipo navegável estritamente **estático e offline** em `prototypes/f3-240-signup-otp/index.html`, acompanhado de `assets/logo-imagine.png` copiada **sem alteração binária** do repositório canônico Imagine Portal Node. O teste de proveniência confere o SHA de blob Git `678adeceb3a8ecdaeb6b576b9ee18d1bf0396f77` do logo original. Design utiliza exatamente a paleta Imagine: laranja `#F9941D`, grafite `#171B19`, branco e fundo `#F7F7F8`.

A interface mostra duas empresas **fictícias** (CNPJ numérico ou alfanumérico), permite alternar entre canal e-mail/WhatsApp **sem fornecer destinatários**, simular entrega **sem transmitir mensagem**, digitar o código fixo `012345`, demonstrar erro, expiração simulada de 15 minutos, reiniciar estado de tela e exibir painel de revisão. O botão **Concluir cadastro** permanece desabilitado mesmo quando o exemplo coincide. Não existe formulário que possa ser submetido, login, route/controller Laravel, envio, rede, database, cobrança, sessão ou armazenamento. Uma CSP local bloqueia conexões e envio de formulários.

Experiência visual utiliza espaços generosos, cartões claros, menu de etapas, foco visível, teclado nativo, textos acessíveis, status com `aria-live`, coluna de marca adaptável e grades de resposta para 880px/440px e 320px. A navegação/estados são limitados à memória do documento, sem registrar dados pessoais; não digitar dados reais.

**Aceite:** esta PR permanece **DRAFT/NO-MERGE** até revisão humana visual comparativa da [issue #416](https://github.com/rafhael100/imagine-crm/issues/416). O browser smoke em Chromium deve exercitar desktop e mobile com o logo local. Nada desta interface altera o runtime Laravel ou libera endpoint. Gates SEG/DBA #27 e CORE/LGPD #259 continuam abertos; `main`/VPS/produção não são tocadas.

## F3-254 — Prévia navegável de trial e paridade com o Core (13/11/2026)

O protótipo agora inclui a quarta etapa **Trial de 14 dias** após a confirmação do código fictício. O usuário pode selecionar empresa numérica/alfanumérica, escolher o canal, digitar o exemplo `012345`, chegar à revisão e abrir um painel elegante de **sete franquias** (usuários 2; leads 20; automações 2; funis 2; anexos 20 MB; IA inicial 10 e bônus 10). Dois controles demonstram, exclusivamente no navegador, a duplicidade de trial e a transição ao fim dos 14 dias. A opção de **ativar trial real** permanece explicitamente desabilitada.

O PHPUnit agora executa o CLI real `scripts/ci/emit-composite-signup-trial-lab.php` e compara, para cada `data-meter`, os limites exibidos com `trial_quota_illustrative_limits` produzidos pelo Core. Assim, uma mudança no plano de teste que torne a interface incoerente **reprova automaticamente a CI**, em vez de deixar números estáticos desatualizados. O navegador Chromium exercita a quarta etapa, seus estados de duplicidade/expiração, foco por teclado e a ausência de cadastro em 1100, 550 e 320 CSS px.

Para revisão sem backend, o fragmento `#previsualizar-trial` do arquivo local inicia **apenas os cliques fictícios da própria interface**, permitindo capturas da quarta etapa. O workflow gera screenshots do início em 1100/550/320 px e do trial em 1100/320 px, retidas por 7 dias. A identidade Imagine/PNG original permanece byte a byte inalterada, com CSP offline e nenhum formulário, rede, cookies, sessão, banco, envio, cobrança ou persistência.

**Gate visual #416 ainda exige aceite humano** da legibilidade, contraste e navegação. Esta PR permanece DRAFT/NO-MERGE mesmo após 5/5 CI. Não existe aprovação para publicar o protótipo na VPS ou ativar login, cadastro ou trial.

## Pacote navegável para revisão (sem VPS)

O job Chromium da PR agora publica também o artifact **`f3-240-imagine-cadastro-trial-navegavel`**, contendo `index.html`, a logomarca original em `assets/logo-imagine.png`, `LEIA-ME.txt` com a jornada e `manifest.sha256`. Basta baixar e extrair o ZIP e abrir `index.html` no navegador. A sequência percorre empresa e canal fictícios, código `012345`, revisão, prévia do trial de 14 dias, simulação de duplicidade e encerramento; **todos os botões que criariam conta ou trial reais estão desabilitados**. O fragmento local `#previsualizar-trial` abre diretamente a etapa final para revisão de layout. Nenhum servidor, localhost, VPN ou endpoint público é necessário.

O pacote é sanitizado, imutável para fins de homologação e retido por sete dias; a CI verifica seu SHA-256. Uma verificação unitária impede que a automação passe a fazer deploy, transmitir secrets ou habilitar SMTP sem revisão. A publicação do artifact em GitHub Actions **não é publicação da aplicação**. A PR permanece DRAFT e exige o aceite humano da issue #416 antes de qualquer merge visual.
