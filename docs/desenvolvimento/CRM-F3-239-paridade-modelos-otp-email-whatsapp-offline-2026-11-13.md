# F3-239 — paridade da apresentação do OTP por e-mail e WhatsApp no cadastro sintético

Integra, no mesmo CLI de revisão `scripts/ci/emit-signup-trial-review-demo.php`, o código real de confirmação OTP da F3-226, a composição `OfflineSignupOtpMail` da F3-236 e a proposta de mensagem `OfflineSignupOtpWhatsAppDraft` da F3-238, preservando o vínculo por contato fictício da F3-235.

O teste executa PHP real e demonstra que o **código de seis dígitos** chega sem perda de zeros à esquerda ao objeto de apresentação do e-mail e à prévia WhatsApp **somente na memória do processo**. Verifica que ambos usam os mesmos 900 segundos/15 minutos, que WhatsApp não tem template ou provedor aprovado e que a jornada continua `REVIEW_ONLY_DENIED`. O JSON de saída inclui **somente quatro indicadores booleanos adicionais** e nunca imprime código, endereço, telefone, digest HMAC, mensagem completa, template, segredo nem CNPJ.

Sem Mail::send, Mail::to, HTTP, DNS, assinatura de destinatário, autorização de cadastro, sessão, conta, fila, SQL, pagamento ou plano ativo; nenhuma mensagem é entregue. Tests PHPUnit exercitam o subprocesso e impõem ausência de dados sensíveis na saída. A aprovação de templates, provedor, rate limits, consentimento, titularidade e segurança do armazenamento continuam pendentes. Gates SEG/DBA #27, CORE/LGPD #259, visual #416 seguem **NO-GO**. `main`/VPS/produção intocadas.

Integrar somente DEV após 4/4 workflows verdes na HEAD revisada, diff e base atual verificados.
