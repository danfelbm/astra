@component('mail::message')
# ❌ Solicitud de actualización rechazada

Hola **{{ $user->name }}**,

Lamentamos informarte que tu solicitud de actualización de datos ha sido **rechazada**.

@if($adminNotes)
## Motivo del rechazo:

@component('mail::panel')
{{ $adminNotes }}
@endcomponent
@endif

## Datos de tu solicitud:

### Información solicitada:
@if($updateRequest->new_email)
- **Email solicitado:** {{ $updateRequest->new_email }}
@endif

@if($updateRequest->new_telefono)
- **Teléfono solicitado:** {{ $updateRequest->new_telefono }}
@endif

@if($updateRequest->documentos_soporte && count($updateRequest->documentos_soporte) > 0)
- **Documentos adjuntos:** {{ count($updateRequest->documentos_soporte) }} archivo(s)
@endif

---

### Información de la solicitud:
- **ID de solicitud:** #{{ $updateRequest->id }}
- **Fecha de solicitud:** {{ $updateRequest->created_at->format('d/m/Y H:i') }}
- **Fecha de revisión:** {{ $updateRequest->rejected_at ? \Carbon\Carbon::parse($updateRequest->rejected_at)->format('d/m/Y H:i') : 'Ahora' }}

## ¿Qué puedes hacer ahora?

1. **Revisa el motivo del rechazo** indicado arriba
2. **Corrige la información** según las observaciones
3. **Envía una nueva solicitud** con los datos correctos

Para realizar una nueva solicitud de actualización:

@component('mail::button', ['url' => url('/confirmar-registro')])
Nueva Solicitud
@endcomponent

## ¿Necesitas ayuda?

Si consideras que ha habido un error o necesitas asistencia adicional, puedes:
- Contactar con el soporte técnico
- Revisar la documentación requerida
- Solicitar una revisión manual de tu caso

Estamos aquí para ayudarte a completar exitosamente tu actualización de datos.

Saludos cordiales,<br>
{{ config('app.name') }}

---

<small>Este es un correo automático, por favor no respondas a este mensaje. Si necesitas ayuda, contacta con soporte.</small>
@endcomponent