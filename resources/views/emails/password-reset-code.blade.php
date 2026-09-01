<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de recuperación</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f5fb; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f5fb; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background-color:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 8px 24px rgba(13,28,46,0.08);">
                    <tr>
                        <td style="background-color:#00647c; padding:28px 32px;">
                            <p style="margin:0; color:#ffffff; font-size:20px; font-weight:600;">{{ config('app.name') }}</p>
                            <p style="margin:4px 0 0; color:#cfe7ef; font-size:14px;">Recuperación de contraseña</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px; color:#0d1c2e; font-size:16px;">
                                Hola{{ $name ? ' '.$name : '' }},
                            </p>
                            <p style="margin:0 0 24px; color:#42474e; font-size:15px; line-height:1.6;">
                                Recibimos una solicitud para restablecer tu contraseña. Usa este código para continuar:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="background-color:#e6eeff; border-radius:16px; padding:20px;">
                                        <span style="display:inline-block; color:#00647c; font-size:34px; font-weight:700; letter-spacing:10px; font-family:'Courier New', Courier, monospace;">{{ $code }}</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0; color:#42474e; font-size:14px; line-height:1.6;">
                                El código caduca en <strong>{{ $minutes }} minutos</strong> y solo puede usarse una vez.
                            </p>
                            <p style="margin:16px 0 0; color:#73777f; font-size:13px; line-height:1.6;">
                                Si no pediste este cambio, puedes ignorar este mensaje: tu contraseña seguirá siendo la misma.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f7f9ff; padding:20px 32px; border-top:1px solid #e0e2ec;">
                            <p style="margin:0; color:#73777f; font-size:12px;">
                                Este es un mensaje automático, por favor no respondas a este correo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
