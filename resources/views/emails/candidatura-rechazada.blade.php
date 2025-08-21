<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatura Requiere Ajustes</title>
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
            color: #ef4444;
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
        .alert-container {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border: 2px solid #ef4444;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .alert-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .alert-label {
            font-size: 16px;
            color: #991b1b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .alert-title {
            font-size: 22px;
            font-weight: bold;
            color: #991b1b;
            margin: 10px 0;
        }
        .status-badge {
            background-color: #ef4444;
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
        .comentarios-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        .comentarios-title {
            font-size: 16px;
            font-weight: 700;
            color: #991b1b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .comentarios-content {
            font-size: 15px;
            color: #374151;
            line-height: 1.8;
        }
        .comentarios-content p {
            margin: 10px 0;
        }
        .comentarios-content ul, .comentarios-content ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .comentarios-content li {
            margin: 5px 0;
        }
        .button {
            display: inline-block;
            background-color: #ef4444;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #dc2626;
        }
        .importante-box {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .importante-title {
            font-size: 14px;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 8px;
        }
        .importante-text {
            font-size: 14px;
            color: #78350f;
            line-height: 1.6;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
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
        
        <div class="alert-container">
            <div class="alert-icon">📝</div>
            <div class="alert-label">Requiere Ajustes</div>
            <div class="alert-title">Tu candidatura necesita correcciones</div>
            <div style="margin-top: 15px;">
                <span class="status-badge">❌ RECHAZADA</span>
            </div>
        </div>
        
        <div class="instructions">
            <p>Tu candidatura ha sido revisada por la comisión evaluadora y <strong>requiere algunos ajustes</strong> para poder ser aprobada.</p>
            
            <p>Por favor, revisa cuidadosamente los comentarios de la comisión que se detallan a continuación:</p>
        </div>
        
        <div class="comentarios-box">
            <div class="comentarios-title">
                📌 Comentarios de la comisión evaluadora:
            </div>
            <div class="comentarios-content">
                {!! $comentarios !!}
            </div>
        </div>
        
        <div class="importante-box">
            <div class="importante-title">⚠️ Importante:</div>
            <div class="importante-text">
                Tu candidatura ha vuelto al estado de <strong>borrador</strong>. Esto significa que puedes ingresar a la plataforma, realizar las correcciones necesarias y volver a enviarla para revisión.
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $platformUrl }}/candidaturas" class="button">
                Corregir mi candidatura
            </a>
        </div>
        
        <div class="footer">
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
            <p>© {{ date('Y') }} {{ config('app.name', 'Sistema de Votaciones') }}. Todos los derechos reservados.</p>
            <p>ID de candidatura: #{{ $candidaturaId }}</p>
        </div>
    </div>
</body>
</html>