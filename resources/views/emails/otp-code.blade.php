<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .title {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        .greeting {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 30px;
        }
        .otp-container {
            background-color: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #2563eb;
            letter-spacing: 8px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }
        .otp-label {
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .instructions {
            font-size: 16px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-text {
            font-size: 14px;
            color: #92400e;
            margin: 0;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0;
        }
        .expiry-time {
            font-weight: bold;
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Sistema de participación en línea</div>
            <h1 class="title">Código de Verificación</h1>
            <p class="greeting">Hola {{ $userName }},</p>
        </div>

        <div class="instructions">
            Has solicitado acceder al Sistema de participación digital. Utiliza el siguiente código de verificación para completar tu autenticación:
        </div>

        <div class="otp-container">
            <div class="otp-label">Tu código de verificación</div>
            <div class="otp-code">{{ $otpCode }}</div>
        </div>

        <div class="instructions">
            Ingresa este código en la página de login para acceder a tu cuenta y participar en la plataforma.
        </div>

        <div class="warning">
            <p class="warning-text">
                <strong>Importante:</strong> Este código es válido por <span class="expiry-time">{{ $expiresInMinutes }} minutos</span> únicamente. 
                No compartas este código con nadie. Si no solicitaste este código, puedes ignorar este email.
            </p>
        </div>

        <div class="footer">
            <p class="footer-text">Sistema de Participación Digital</p>
            <p class="footer-text">Este es un email automático, no responder.</p>
            <p class="footer-text">
                Si tienes problemas, contacta al administrador del sistema.
            </p>
        </div>
    </div>
</body>
</html>