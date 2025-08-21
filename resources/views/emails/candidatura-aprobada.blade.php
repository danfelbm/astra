<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatura Aprobada</title>
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
            color: #10b981;
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
        .success-container {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .success-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .success-label {
            font-size: 16px;
            color: #065f46;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .success-title {
            font-size: 24px;
            font-weight: bold;
            color: #065f46;
            margin: 10px 0;
        }
        .status-badge {
            background-color: #10b981;
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
            background-color: #f3f4f6;
            border-left: 4px solid #10b981;
            padding: 15px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .comentarios-title {
            font-size: 14px;
            font-weight: 600;
            color: #065f46;
            margin-bottom: 10px;
        }
        .comentarios-content {
            font-size: 14px;
            color: #374151;
            line-height: 1.6;
        }
        .button {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #059669;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }
        .celebration {
            font-size: 72px;
            margin: 20px 0;
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
        
        <div class="success-container">
            <div class="celebration">🎉</div>
            <div class="success-label">¡Felicitaciones!</div>
            <div class="success-title">Tu candidatura ha sido APROBADA</div>
            <div style="margin-top: 15px;">
                <span class="status-badge">✅ APROBADO</span>
            </div>
        </div>
        
        <div class="instructions">
            <p>Nos complace informarte que tu candidatura ha sido revisada y <strong>aprobada exitosamente</strong> por la comisión evaluadora.</p>
            
            <p>Esto significa que:</p>
            <ul style="color: #374151; line-height: 1.8;">
                <li>Tu perfil ha sido validado y cumple con todos los requisitos</li>
                <li>Ya puedes participar en las convocatorias</li>
                <li>Tu información está completa y verificada</li>
            </ul>
        </div>
        
        @if($comentarios)
        <div class="comentarios-box">
            <div class="comentarios-title">💬 Comentarios de la comisión:</div>
            <div class="comentarios-content">
                {!! $comentarios !!}
            </div>
        </div>
        @endif
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $platformUrl }}/candidaturas" class="button">
                Ver mi candidatura
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