<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Comentario en tu Candidatura</title>
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
            color: #3b82f6;
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
        .message-container {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .message-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .message-label {
            font-size: 16px;
            color: #1e40af;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .message-title {
            font-size: 24px;
            font-weight: bold;
            color: #1e40af;
            margin: 10px 0;
        }
        .status-badge {
            background-color: #3b82f6;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        .instructions {
            font-size: 16px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .comentario-box {
            background-color: #f3f4f6;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .comentario-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .comentario-content {
            font-size: 15px;
            color: #374151;
            line-height: 1.8;
        }
        .button {
            display: inline-block;
            background-color: #3b82f6;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #2563eb;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
        .info-box {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-text {
            color: #92400e;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">{{ config('app.name', 'Sistema de Votaciones') }}</div>
            <div class="title">Notificación de Candidatura</div>
        </div>
        
        <div class="greeting">
            Hola <strong>{{ $userName }}</strong>,
        </div>
        
        <div class="message-container">
            <div class="message-icon">💬</div>
            <div class="message-label">Nuevo Comentario</div>
            <div class="message-title">La comisión ha agregado un comentario</div>
            <div style="margin-top: 15px;">
                <span class="status-badge">📝 COMENTARIO ADMINISTRATIVO</span>
            </div>
        </div>
        
        <div class="instructions">
            <p>La comisión evaluadora ha agregado un nuevo comentario a tu candidatura. Este comentario puede contener:</p>
            <ul style="color: #374151; line-height: 1.8;">
                <li>Información adicional sobre tu candidatura</li>
                <li>Aclaraciones sobre el proceso</li>
                <li>Sugerencias o recomendaciones</li>
                <li>Actualizaciones sobre el estado de revisión</li>
            </ul>
        </div>
        
        <div class="comentario-box">
            <div class="comentario-title">
                <span>💭</span>
                <span>Comentario de la comisión:</span>
            </div>
            <div class="comentario-content">
                {!! $comentario !!}
            </div>
        </div>

        <div class="info-box">
            <p class="info-text">
                <strong>📌 Importante:</strong> Este comentario no cambia el estado actual de tu candidatura. 
                Si requiere alguna acción de tu parte, te lo indicaremos claramente en el mensaje.
            </p>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $platformUrl }}/candidaturas" class="button">
                Ver mi candidatura completa
            </a>
        </div>
        
        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>Si tienes preguntas, accede a la plataforma para más información.</p>
            <p>© {{ date('Y') }} {{ config('app.name', 'Sistema de Votaciones') }}. Todos los derechos reservados.</p>
            <p>ID de candidatura: #{{ $candidaturaId }}</p>
        </div>
    </div>
</body>
</html>