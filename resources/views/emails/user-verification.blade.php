<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de Verificación</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 30px;
            margin-top: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
        }
        .content {
            background-color: white;
            border-radius: 6px;
            padding: 25px;
            margin-bottom: 20px;
        }
        .code-container {
            background-color: #f3f4f6;
            border: 2px dashed #d1d5db;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #1f2937;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .channel-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .info {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 30px;
        }
        .warning {
            color: #dc2626;
            font-weight: 500;
        }
        h1 {
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 10px;
        }
        p {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Sistema de Votaciones</div>
        </div>
        
        <div class="content">
            <h1>Código de Verificación</h1>
            
            <p>Hola <strong>{{ $userName }}</strong>,</p>
            
            <p>Has solicitado verificar tu registro en nuestro sistema. A continuación tu código de verificación:</p>
            
            <div class="code-container">
                <div class="channel-label">Código para {{ strtoupper($channel) }}</div>
                <div class="code">{{ $code }}</div>
            </div>
            
            <div class="info">
                <strong>Importante:</strong>
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li>Este código es único para el canal de {{ $channel }}</li>
                    <li>El código expira en <strong>15 minutos</strong></li>
                    <li>No compartas este código con nadie</li>
                    <li>Si no solicitaste este código, ignora este mensaje</li>
                </ul>
            </div>
            
            @if($channel === 'whatsapp')
            <p class="warning">
                ⚠️ Si recibiste este correo por error, verifica que tu número de WhatsApp esté correctamente registrado.
            </p>
            @endif
            
            <p>Si tienes problemas con el código o necesitas ayuda, contacta al administrador del sistema.</p>
        </div>
        
        <div class="footer">
            <p>Este es un mensaje automático, por favor no respondas a este correo.</p>
            <p>© {{ date('Y') }} Sistema de Votaciones. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>