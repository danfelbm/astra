<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso a Videoconferencia</title>
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
        .access-container {
            background-color: #f0f9ff;
            border: 2px solid #0ea5e9;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .access-button {
            display: inline-block;
            background-color: #0ea5e9;
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            margin: 20px 0;
            transition: background-color 0.3s;
        }
        .access-button:hover {
            background-color: #0284c7;
        }
        .meeting-details {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .detail-label {
            font-weight: bold;
            color: #374151;
        }
        .detail-value {
            color: #6b7280;
        }
        .registrant-id {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 8px 12px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #1f2937;
            text-align: center;
            margin: 10px 0;
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
        .important-info {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .important-text {
            font-size: 14px;
            color: #991b1b;
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
        .emoji {
            font-size: 18px;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Plataforma de participación</div>
            <h1 class="title"><span class="emoji">🎥</span>Tu Acceso a la Videoconferencia</h1>
            <p class="greeting">Hola {{ $userName }},</p>
        </div>

        <div class="instructions">
            ¡Tu registro para la videoconferencia se ha completado exitosamente! A continuación encontrarás toda la información que necesitas para participar en la asamblea.
        </div>

        <div class="meeting-details">
            <h3 style="margin-top: 0; color: #1f2937;">📅 Detalles de la Asamblea</h3>
            <div class="detail-row">
                <span class="detail-label">Asamblea:</span>
                <span class="detail-value">{{ $asambleaNombre }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha de inicio:</span>
                <span class="detail-value">{{ $fechaInicioFormateada }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Fecha de fin:</span>
                <span class="detail-value">{{ $fechaFinFormateada }}</span>
            </div>
        </div>

        <div class="access-container">
            <h3 style="margin-top: 0; color: #0ea5e9;">🔗 Enlace de Acceso Personal</h3>
            <p style="margin: 15px 0; color: #374151;">Haz clic en el siguiente botón para unirte a la videoconferencia:</p>
            <a href="{{ $maskedUrl }}" class="access-button">Unirse a la Videoconferencia</a>
            
            <div style="margin-top: 20px;">
                <p style="color: #6b7280; font-size: 14px; margin: 5px 0;">Tu código de registro personal:</p>
                <div class="registrant-id">{{ $zoomRegistrantId }}</div>
            </div>
        </div>

        <div class="instructions">
            <h3 style="color: #1f2937;">📋 Instrucciones de Acceso</h3>
            <ul style="color: #374151; line-height: 1.6;">
                <li>El enlace estará disponible <strong>15 minutos antes</strong> del inicio de la asamblea</li>
                <li>Guarda este email para poder acceder fácilmente el día de la asamblea</li>
                <li>Si tienes problemas técnicos, presenta tu código de registro al moderador</li>
                <li>Asegúrate de tener una conexión estable a internet</li>
            </ul>
        </div>

        <div class="important-info">
            <p class="important-text">
                <strong>🚨 MUY IMPORTANTE:</strong><br>
                • <strong>NO compartas</strong> este enlace con nadie más<br>
                • Solo <strong>un dispositivo</strong> puede conectarse por enlace<br>
                • Si compartes tu enlace, podrías <strong>perder tu acceso</strong> a la asamblea
            </p>
        </div>

        <div class="warning">
            <p class="warning-text">
                <strong>Nota de Seguridad:</strong> Este enlace es personal e intransferible. 
                Si no solicitaste este acceso o crees que este email te llegó por error, 
                contacta inmediatamente al administrador del sistema.
            </p>
        </div>

        <div class="footer">
            <p class="footer-text"><strong>Plataforma de participación</strong></p>
            <p class="footer-text">Este es un email automático, no responder.</p>
            <p class="footer-text">
                Si tienes problemas técnicos o preguntas, contacta a: <b>soporte@colombiahumana.co</b>
            </p>
            <p class="footer-text" style="margin-top: 15px; font-size: 12px;">
                Tu privacidad es importante. Este enlace es único y seguro.
            </p>
        </div>
    </div>
</body>
</html>