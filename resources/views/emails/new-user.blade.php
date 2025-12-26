<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Reserva de Espacios</title>
    <style>
        /* Reset básico y estilos compatibles con clientes de correo */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f4f7;
            color: #51545e;
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: none;
            width: 100% !important;
        }

        .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #f4f4f7;
        }

        .email-content {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .email-masthead {
            padding: 25px 0;
            text-align: center;
        }

        .email-masthead_name {
            font-size: 24px;
            font-weight: bold;
            color: #2F3133;
            /* Gris oscuro elegante (#333) */
            text-decoration: none;
            text-shadow: 0 1px 0 white;
        }

        /* Body */
        .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            border-radius: 8px;
            /* Bordes suaves */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            /* Sombra sutil premium */
        }

        .email-body_inner {
            width: 570px;
            margin: 0 auto;
            padding: 40px;
            box-sizing: border-box;
        }

        h1 {
            color: #333333;
            font-size: 24px;
            font-weight: bold;
            text-align: left;
            margin-top: 0;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            line-height: 1.6;
            /* Mejor legibilidad */
            color: #51545e;
            margin-bottom: 20px;
        }

        /* Botón de Acción (Call to Action) - Estilo Premium */
        .button {
            display: inline-block;
            background-color: #4F46E5;
            /* Indigo moderno (Tailwind style) */
            color: #ffffff !important;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
            margin: 20px 0;
        }

        .button:hover {
            background-color: #4338ca;
        }

        /* Footer */
        .email-footer {
            width: 570px;
            margin: 0 auto;
            padding: 25px;
            text-align: center;
        }

        .email-footer p {
            font-size: 13px;
            color: #6b6e76;
            margin-bottom: 10px;
        }

        /* Responsividad */
        @media only screen and (max-width: 600px) {

            .email-body_inner,
            .email-footer {
                width: 100% !important;
                padding: 20px !important;
            }
        }
    </style>
</head>

<body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="email-content" width="100%" cellpadding="0" cellspacing="0" role="presentation">
                    <!-- Header Logo -->
                    <tr>
                        <td class="email-masthead">
                            <a href="{{ config('app.url') }}" class="email-masthead_name">
                                {{ config('app.name') }}
                            </a>
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td class="email-body" width="570" cellpadding="0" cellspacing="0">
                            <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell">
                                        <h1>¡Hola, {{ $user->name }}! 👋</h1>

                                        <p>Gracias por registrarte en nuestra plataforma de Reserva de Espacios. Estamos emocionados de tenerte a bordo.</p>

                                        <p>Para garantizar la seguridad de tu cuenta y comenzar a reservar espacios exclusivos, necesitamos que verifiques tu dirección de correo electrónico.</p>

                                        <!-- Action Button -->
                                        <table width="100%" border="0" cellspacing="0" cellpadding="0" role="presentation">
                                            <tr>
                                                <td align="center">
                                                    <a href="{{ $activationUrl ?? '#' }}" class="button" target="_blank">
                                                        Activar mi Cuenta
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin-top: 30px; font-size: 14px; color: #718096;">
                                            Si no creaste esta cuenta, puedes ignorar este mensaje con seguridad. Este enlace expirará en 24 horas.
                                        </p>

                                        <p>Saludos,<br>El equipo de {{ config('app.name') }}</p>

                                        <!-- Subcopy (Link textual si el botón falla) -->
                                        <table class="body-sub" role="presentation">
                                            <tr>
                                                <td style="padding-top: 25px; border-top: 1px solid #e8e5ef;">
                                                    <p style="font-size: 12px;">Si tienes problemas para hacer clic en el botón "Activar mi Cuenta", copia y pega la siguiente URL en tu navegador:</p>
                                                    <p style="font-size: 12px; word-break: break-all;">
                                                        <a href="{{ $activationUrl ?? '#' }}" style="color: #4F46E5;">{{ $activationUrl ?? '' }}</a>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td>
                            <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td class="content-cell" align="center">
                                        <p class="f-fallback sub align-center">
                                            &copy; {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
                                        </p>
                                        <p class="f-fallback sub align-center">
                                            Has recibido este correo porque te registraste en nuestra plataforma.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>