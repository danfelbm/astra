<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatura Devuelta a Borrador</title>
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
            color: #f97316;
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
        .info-container {
            background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
            border: 2px solid #f97316;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .info-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .info-label {
            font-size: 16px;
            color: #7c2d12;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .info-title {
            font-size: 22px;
            font-weight: bold;
            color: #7c2d12;
            margin: 10px 0;
        }
        .status-badge {
            background-color: #f97316;
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
        .motivo-box {
            background-color: #fff7ed;
            border-left: 4px solid #f97316;
            padding: 20px;
            margin: 30px 0;
            border-radius: 0 8px 8px 0;
        }
        .motivo-title {
            font-size: 16px;
            font-weight: 700;
            color: #7c2d12;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .motivo-content {
            font-size: 15px;
            color: #374151;
            line-height: 1.8;
        }
        .motivo-content p {
            margin: 10px 0;
        }
        .motivo-content ul, .motivo-content ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .motivo-content li {
            margin: 5px 0;
        }
        .info-box {
            background-color: #f0f9ff;
            border: 1px solid #3b82f6;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .info-box-title {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }
        .info-box-text {
            font-size: 14px;
            color: #1e3a8a;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            background-color: #f97316;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #ea580c;
        }
        .actions-list {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .actions-title {
            font-size: 16px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 15px;
        }
        .actions-list ul {
            margin: 0;
            padding-left: 20px;
            color: #4b5563;
        }
        .actions-list li {
            margin: 8px 0;
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
        
        <div class="info-container">
            <div class="info-icon">↩️</div>
            <div class="info-label">Actualización de Estado</div>
            <div class="info-title">Tu candidatura ha sido devuelta a BORRADOR</div>
            <div style="margin-top: 15px;">
                <span class="status-badge">📝 BORRADOR</span>
            </div>
        </div>
        
        <div class="instructions">
            <p>Te informamos que tu candidatura ha sido <strong>devuelta al estado de borrador</strong> por la comisión evaluadora.</p>
            
            <p>Esto significa que ahora puedes:</p>
        </div>
        
        <div class="actions-list">
            <div class="actions-title">✏️ Acciones disponibles:</div>
            <ul>
                <li>Editar y actualizar toda la información de tu candidatura</li>
                <li>Agregar o modificar documentos adjuntos</li>
                <li>Corregir cualquier dato que necesite ajustes</li>
                <li>Volver a enviar tu candidatura cuando esté lista</li>
            </ul>
        </div>
        
        @if($motivo)
        <div class="motivo-box">
            <div class="motivo-title">
                📌 Motivo del cambio de estado:
            </div>
            <div class="motivo-content">
                {!! $motivo !!}
            </div>
        </div>
        @endif
        
        <div class="info-box">
            <div class="info-box-title">ℹ️ Información importante:</div>
            <div class="info-box-text">
                Tu candidatura permanecerá en estado borrador hasta que realices los cambios necesarios y la envíes nuevamente para revisión. No hay límite de tiempo para realizar estas modificaciones.
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $platformUrl }}/candidaturas" class="button">
                Editar mi candidatura
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