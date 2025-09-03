@component('mail::message')
# ✅ Tu solicitud ha sido aprobada

Hola **{{ $user->name }}**,

Nos complace informarte que tu solicitud de actualización de datos ha sido **aprobada exitosamente**.

## Cambios aplicados:

@if(isset($changes['email']))
### 📧 Email actualizado
- **Anterior:** {{ $changes['email']['current'] ?: 'No registrado' }}
- **Nuevo:** {{ $changes['email']['new'] }}
@endif

@if(isset($changes['telefono']))
### 📱 Teléfono actualizado
- **Anterior:** {{ $changes['telefono']['current'] ?: 'No registrado' }}
- **Nuevo:** {{ $changes['telefono']['new'] }}
@endif

@if(!isset($changes['email']) && !isset($changes['telefono']))
Tu solicitud fue procesada exitosamente aunque no incluía cambios en los datos de contacto.
@endif

@if($updateRequest->admin_notes)
## Nota del administrador:
> {{ $updateRequest->admin_notes }}
@endif

---

### Información de la solicitud:
- **ID de solicitud:** #{{ $updateRequest->id }}
- **Fecha de solicitud:** {{ $updateRequest->created_at->format('d/m/Y H:i') }}
- **Fecha de aprobación:** {{ $updateRequest->approved_at ? \Carbon\Carbon::parse($updateRequest->approved_at)->format('d/m/Y H:i') : 'Ahora' }}

Los cambios ya están reflejados en tu cuenta y puedes comenzar a utilizar tus nuevos datos de contacto inmediatamente.

@component('mail::button', ['url' => config('app.url')])
Ir al Sistema
@endcomponent

Si tienes alguna pregunta o necesitas asistencia adicional, no dudes en contactarnos.

Saludos cordiales,<br>
{{ config('app.name') }}

---

<small>Este es un correo automático, por favor no respondas a este mensaje. Si necesitas ayuda, contacta con soporte.</small>
@endcomponent