# F3-258 — Sequência OTP → trial sintético por HTTP PHPUnit

Esta entrega cria uma máquina de estados inerte em tests/Support/SignupTrialSequencedHttpLab.php e testes em tests/Feature/SignupTrialSequencedHttpLabTest.php. As rotas HTTP __ci só existem durante PHPUnit; o runtime Laravel publicado continua sem endpoint de cadastro, trial, e-mail, WhatsApp ou login.

## Comportamento provado

- Trial antes de uma observação OTP correta é negado, mesmo em memória; código incorreto também não libera a etapa.
- Observação de coincidência criptográfica consome o desafio no sandbox e gera um sinal local de uso único somente no canal correspondente (e-mail ou telefone fictício).
- O sinal local expira junto ao desafio OTP original (900 segundos); não vira sessão, login nem prova de identidade.
- Após observar o primeiro marcador sintético de empresa em array PHP, um segundo ciclo OTP não cria outro marcador para o mesmo CNPJ **fictício**, e não concede trial.
- JSON inválido, chaves repetidas e dados de aparência real são recusados; respostas HTTP invariáveis não expõem código, HMAC, CNPJ ou status interno.

## Fronteiras explícitas

Este fluxo **não** comprova propriedade real de e-mail/telefone, autenticação, elegibilidade, RLS de banco persistente, consumo atômico, unicidade concorrente no PostgreSQL, entrega de mensagem, consentimento, ativação trial, quota, cobrança ou privacidade da implantação. As evidências são somente de uma instância PHP no runner. A validação de duplicados por contagem de ':' vale apenas no microprotocolo sintético rígido e **não** é um parser JSON geral.

Branch filha da DEV, com no máximo dois arquivos PHP novos e esta documentação; não alterar main, VPS, segredos, rotas publicadas, migrations, armazenamento real ou provedores externos. Gates SEG/DBA #27 e CORE/LGPD #259 permanecem NO-GO. Antes de merge DEV exigir revisão de diff e workflows F0, PostgreSQL/RLS, Sites, AIA verdes na HEAD exata.
