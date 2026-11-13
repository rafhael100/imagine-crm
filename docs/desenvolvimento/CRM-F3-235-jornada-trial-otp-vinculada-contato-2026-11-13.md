# F3-235 — Jornada de trial vinculando OTP a contato por HMAC

O script CLI `scripts/ci/emit-signup-trial-review-demo.php` já executava código real de OTP 6 dígitos/900s, janela de 14 dias, franquias ilustrativas, validação CNPJ e HMAC fiscal, sem rede nem persistência. Este recorte **substitui o binding arbitrário anterior** pela derivação `OfflineSignupContactHmacBinding` da F3-234 para e-mail de exemplo e telefone E.164 de exemplo. São gerados contextos distintos para as duas vias de confirmação sem exigir que ambas sejam usadas na política real de cadastro.

A execução confirma que um código correto para um contato não valida outro e-mail nem outro canal. Os códigos de erro são agregados no JSON fixo sem refletir endereço, telefone, digest HMAC, código transitório, segredo ou documento fiscal. O teste PHPUnit de subprocesso executa o script realmente, confere ambos os resultados negativos, estabilidade da saída e ausência de conteúdo sensível, além de verificar que não há envio de mensagens, endpoints, I/O, tabelas nem acesso a dados.

**Importante:** casamento criptográfico com um contexto opaco não é prova de entrega, posse do endereço ou número, identidade do solicitante, consentimento LGPD, CNPJ ativo, elegibilidade de um trial por empresa, autenticação, autorização de CRM ou antifraude. Todas as flags de permissão continuam `false` e a demonstração não libera cadastro nem WhatsApp. As decisões de canal de envio, rate-limit, armazenamento durável, auditoria, verificação de contato e a integração operacional aguardam SEG/DBA #27, CORE/LGPD #259 e revisão UX #416. Nenhum `main`, VPS ou produção é alterado.

**Aceite:** CI F0, PostgreSQL/RLS, Sites e Agentes verdes na HEAD exata, revisão do diff limitado ao CLI/testes/documentação e merge somente em DEV.
