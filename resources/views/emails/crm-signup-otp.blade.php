<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Imagine CRM | Verificacao de cadastro</title>
</head>
<body style="margin:0;padding:24px 12px;background:#f7f7f8;font-family:Arial,Helvetica,sans-serif;color:#171b19">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:560px;margin:0 auto;border-collapse:collapse">
        <tr>
            <td style="padding:22px 28px;background:#171b19;border-radius:12px 12px 0 0">
                <strong style="font-size:25px;letter-spacing:.05em;color:#ffffff">IMAGINE</strong>
                <div style="font-size:13px;color:#f9941d;margin-top:3px">CRM Imagine · Cadastro seguro</div>
            </td>
        </tr>
        <tr>
            <td style="padding:28px;background:#ffffff;border:1px solid #e5e5e7;border-top:4px solid #f9941d">
                <h1 style="font-size:22px;line-height:1.3;margin:0 0 14px;color:#171b19">Confirme seu e-mail</h1>
                <p style="font-size:16px;line-height:1.6;margin:0 0 18px">
                    Use o código abaixo para continuar a confirmação do seu cadastro.
                </p>
                <div aria-label="Código de verificação" style="padding:18px 12px;text-align:center;background:#fff3e5;border:1px solid #f4c58f;border-radius:10px;color:#171b19;font-size:34px;font-weight:800;letter-spacing:.16em">
                    {{ $otpCode }}
                </div>
                <p style="font-size:15px;line-height:1.6;margin:18px 0 0;color:#4c5750">
                    Este código é válido por <strong>{{ $validityMinutes }} minutos</strong>. Não o compartilhe com ninguém.
                </p>
                <p style="font-size:14px;line-height:1.6;margin:16px 0 0;color:#4c5750">
                    Se você não solicitou este código, ignore esta mensagem.
                </p>
            </td>
        </tr>
        <tr>
            <td style="padding:17px 24px;text-align:center;color:#555;font-size:12px">
                Imagine Comunicação Digital · Mensagem automática de confirmação
            </td>
        </tr>
    </table>
</body>
</html>
