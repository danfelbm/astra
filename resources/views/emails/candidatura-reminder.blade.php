<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Candidatura</title>
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
        .reminder-container {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 30px 0;
        }
        .reminder-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .reminder-label {
            font-size: 16px;
            color: #92400e;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .reminder-title {
            font-size: 24px;
            font-weight: bold;
            color: #92400e;
            margin: 10px 0;
        }
        .instructions {
            font-size: 16px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .cta-container {
            text-align: center;
            margin: 30px 0;
        }
        .cta-button {
            display: inline-block;
            background-color: #2563eb;
            color: #ffffff;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            border: none;
            cursor: pointer;
        }
        .cta-button:hover {
            background-color: #1d4ed8;
        }
        .warning {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-text {
            font-size: 14px;
            color: #1e40af;
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
        .status-badge {
            background-color: #fbbf24;
            color: #92400e;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Sistema de participación en línea</div>
            <h1 class="title">Recordatorio de Candidatura</h1>
            <p class="greeting">Hola {{ $userName }},</p>
        </div>

        <div class="instructions">
            Te recordamos que tu candidatura en nuestro sistema está en estado <strong>borrador</strong> y aún no ha sido completada.
        </div>

        <div class="reminder-container">
            <div class="reminder-icon">⚠️</div>
            <div class="reminder-label">Estado actual</div>
            <div class="status-badge">BORRADOR</div>
            <div class="reminder-title">Candidatura Incompleta</div>
        </div>

        <div class="instructions">
            Para que tu candidatura pueda ser revisada por nuestro equipo, es necesario que completes todos los campos requeridos y la envíes para revisión.
        </div>

        <div class="cta-container">
            <a href="{{ $platformUrl }}" class="cta-button">
                Completar mi Candidatura
            </a>
        </div>

        <div class="instructions">
            Al hacer clic en el botón anterior, podrás:
        </div>

        <ul style="color: #374151; line-height: 1.6; margin-left: 20px;">
            <li>Revisar la información ya ingresada</li>
            <li>Completar los campos faltantes</li>
            <li>Adjuntar documentos requeridos</li>
            <li>Enviar tu candidatura para revisión</li>
        </ul>

        <div class="warning">
            <p class="warning-text">
                <strong>Importante:</strong> Una vez que completes y envíes tu candidatura, nuestro equipo la revisará y te notificaremos sobre su estado. 
                Solo podrás enviar tu Candidatura hasta el <b>21 de agosto</b> a media noche. Si tienes dudas escribe a <b>soporte@colombiahumana.co</b>
            </p>
        </div>

        <div class="footer">
            <p class="footer-text">Sistema de Participación Digital</p>
            <p class="footer-text">Este es un email automático, no responder.</p>
            <p class="footer-text">
                Si tienes problemas o necesitas ayuda, contacta al administrador del sistema.
            </p>
        </div>
    </div>
</body>
</html>