<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Voto</title>
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
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header .subtitle {
            margin-top: 10px;
            font-size: 16px;
            opacity: 0.95;
        }
        .content {
            padding: 40px;
        }
        .success-icon {
            text-align: center;
            margin-bottom: 30px;
        }
        .success-circle {
            display: inline-block;
            width: 80px;
            height: 80px;
            background-color: #10b981;
            border-radius: 50%;
            position: relative;
        }
        .checkmark {
            color: white;
            font-size: 48px;
            line-height: 80px;
            text-align: center;
        }
        .greeting {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .message {
            font-size: 16px;
            color: #4b5563;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .vote-details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 600;
        }
        .detail-value {
            font-size: 14px;
            color: #1f2937;
            text-align: right;
        }
        .token-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            padding: 25px;
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        .token-label {
            font-size: 14px;
            color: #ffffff;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        .token-code {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 2px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            padding: 10px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .verify-button {
            display: inline-block;
            padding: 14px 30px;
            background-color: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .verify-button:hover {
            background-color: #1d4ed8;
        }
        .security-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .security-notice-text {
            font-size: 14px;
            color: #92400e;
            margin: 0;
            line-height: 1.6;
        }
        .footer {
            text-align: center;
            padding: 30px;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0;
            line-height: 1.6;
        }
        .footer-links {
            margin-top: 15px;
        }
        .footer-link {
            color: #2563eb;
            text-decoration: none;
            margin: 0 10px;
            font-size: 14px;
        }
        @media only screen and (max-width: 600px) {
            .content {
                padding: 20px;
            }
            .header h1 {
                font-size: 24px;
            }
            .token-code {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🗳️ Voto Registrado Exitosamente</h1>
            <div class="subtitle">Sistema de Votación Digital</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Success Icon -->
            <div class="success-icon">
                <div class="success-circle">
                    <div class="checkmark">✓</div>
                </div>
            </div>

            <!-- Greeting -->
            <div class="greeting">
                Hola {{ $userName }},
            </div>

            <!-- Message -->
            <div class="message">
                Te confirmamos que tu voto ha sido registrado exitosamente en nuestro sistema. 
                Tu participación es muy importante para nosotros y ha sido procesada de forma segura y confidencial.
            </div>

            <!-- Vote Details -->
            <div class="vote-details">
                <div class="detail-row">
                    <span class="detail-label">Votación:</span>
                    <span class="detail-value">{{ $votacionTitulo }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Categoría:</span>
                    <span class="detail-value">{{ $votacionCategoria }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Fecha y Hora:</span>
                    <span class="detail-value">{{ $voteDateTime }}</span>
                </div>
            </div>

            <!-- Token Container -->
            <div class="token-container">
                <div class="token-label">Tu Token de Verificación</div>
                <div class="token-code">{{ $token }}</div>
            </div>

            <!-- Button -->
            <div class="button-container">
                <a href="{{ $verificationUrl }}" class="verify-button">
                    Verificar mi Voto
                </a>
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <p class="security-notice-text">
                    <strong>🔒 Importante sobre la seguridad:</strong><br>
                    • Este token es único e irrepetible<br>
                    • Guárdalo en un lugar seguro para futuras consultas<br>
                    • No compartas tu token con terceros<br>
                    • Tu voto es anónimo y no puede ser vinculado a tu identidad<br>
                    • El link de verificación es público y solo muestra información anonimizada
                </p>
            </div>

            <!-- Additional Info -->
            <div class="message">
                <strong>¿Qué puedes hacer con tu token?</strong><br>
                Con tu token único puedes:
                <ul style="margin-top: 10px;">
                    <li>Verificar que tu voto fue correctamente registrado</li>
                    <li>Consultar la validez del token en cualquier momento</li>
                    <li>Demostrar tu participación sin revelar el contenido de tu voto</li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Sistema de Votación Digital - Participación Ciudadana
            </p>
            <p class="footer-text">
                Este es un correo automático, por favor no responder.
            </p>
            <div class="footer-links">
                <a href="{{ url('/') }}" class="footer-link">Ir al Sistema</a>
                <a href="{{ $verificationUrl }}" class="footer-link">Verificar Token</a>
            </div>
            <p class="footer-text" style="margin-top: 15px; font-size: 12px; color: #9ca3af;">
                Si tienes alguna pregunta o necesitas ayuda, contacta al administrador del sistema.
            </p>
        </div>
    </div>
</body>
</html>