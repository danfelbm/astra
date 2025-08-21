<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatura Recibida</title>
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
        .success-container {
            background-color: #d1fae5;
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
        .timeline-container {
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 30px 0;
            border-radius: 4px;
        }
        .timeline-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
        }
        .timeline-item {
            margin: 10px 0;
            padding-left: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '•';
            position: absolute;
            left: 0;
            color: #3b82f6;
            font-size: 20px;
        }
        .timeline-item.active {
            font-weight: bold;
            color: #1f2937;
        }
        .timeline-item.pending {
            color: #6b7280;
        }
        .info-box {
            background-color: #dbeafe;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-text {
            font-size: 14px;
            color: #1e40af;
            margin: 0;
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
        .contact-info {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .contact-item {
            font-size: 14px;
            color: #4b5563;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Sistema de participación en línea</div>
            <h1 class="title">¡Candidatura Recibida!</h1>
            <p class="greeting">Hola {{ $userName }},</p>
        </div>

        <div class="success-container">
            <div class="success-icon">✅</div>
            <div class="success-label">Estado actual</div>
            <div class="status-badge">PENDIENTE DE REVISIÓN</div>
            <div class="success-title">Tu postulación ha sido recibida exitosamente</div>
        </div>

        <div class="instructions">
            Nos complace informarte que tu candidatura ha sido enviada correctamente y ahora se encuentra en proceso de revisión por parte de nuestro equipo evaluador.
        </div>

        <div class="info-box">
            <p class="info-text">
                <strong>Importante:</strong> Durante el período de revisión, tu candidatura no podrá ser modificada. Si necesitas realizar algún cambio urgente, por favor contáctanos inmediatamente.
            </p>
        </div>

        <div class="cta-container">
            <a href="{{ $platformUrl }}" class="cta-button">
                Ver Estado de mi Candidatura
            </a>
        </div>

        <div class="contact-info">
            <div class="contact-item"><strong>¿Necesitas ayuda?</strong></div>
            <div class="contact-item">📧 Email: soporte@colombiahumana.co</div>
        </div>
        
        <div class="footer">
            <p class="footer-text">Sistema de Participación Digital</p>
            <p class="footer-text">Este es un email automático de confirmación.</p>
            <p class="footer-text">
                Por favor no respondas a este correo. Si necesitas asistencia, utiliza los datos de contacto proporcionados arriba.
            </p>
            <p class="footer-text" style="margin-top: 15px;">
                <small>ID de Candidatura: #{{ $candidaturaId }}</small>
            </p>
        </div>
    </div>
</body>
</html>