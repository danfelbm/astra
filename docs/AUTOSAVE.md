# Sistema de Autoguardado de Candidaturas

## Descripción General

El sistema de autoguardado permite guardar automáticamente los cambios realizados en el formulario de candidaturas cada vez que el usuario modifica un campo. Esto evita la pérdida de información en caso de problemas de conexión, cierre accidental del navegador o cualquier otra interrupción.

## Características Principales

### 1. Guardado Automático con Debounce
- Los cambios se guardan automáticamente 3 segundos después de que el usuario deja de escribir
- Evita múltiples peticiones al servidor mientras el usuario está escribiendo activamente
- Se aplica a todos los tipos de campos: texto, archivos, selects, checkboxes, etc.

### 2. Respaldo en LocalStorage
- Los datos se guardan localmente en el navegador como respaldo
- Si falla la conexión al servidor, los datos permanecen seguros localmente
- Los borradores locales se mantienen hasta 24 horas
- Al recuperar el formulario, se restauran automáticamente los datos guardados

### 3. Estados del Autoguardado
- **Idle**: Sistema listo para guardar
- **Saving**: Guardando cambios en el servidor
- **Saved**: Cambios guardados exitosamente
- **Error**: Error al guardar (pero guardado localmente)

### 4. Notificaciones Discretas
- Usa `vue-sonner` para mostrar notificaciones no intrusivas
- Notifica cuando se guarda automáticamente
- Alerta cuando hay errores pero los datos están seguros localmente
- Informa cuando se recupera un borrador guardado

## Arquitectura Técnica

### Backend (Laravel)

#### Rutas
```php
// Autoguardado de candidatura nueva
Route::post('candidaturas/autosave', [CandidaturaController::class, 'autosave']);

// Autoguardado de candidatura existente
Route::post('candidaturas/{candidatura}/autosave', [CandidaturaController::class, 'autosaveExisting']);
```

#### Métodos del Controlador
- `autosave()`: Crea o actualiza una candidatura en estado borrador
- `autosaveExisting()`: Actualiza una candidatura existente
- `limpiarDatosAutoguardado()`: Limpia y normaliza los datos antes de guardar

#### Base de Datos
- Campo `ultimo_autoguardado`: Timestamp del último guardado automático
- Estados compatibles: `borrador`, `borrador_auto`, `rechazado`

### Frontend (Vue.js)

#### Composable useAutoSave
```typescript
const {
    state,
    isSaving,
    hasSaved,
    hasError,
    saveNow,
    startWatching,
    stopAutoSave,
    restoreDraft,
    clearLocalStorage
} = useAutoSave(formData, options);
```

#### Opciones de Configuración
- `url`: Endpoint para el autoguardado
- `candidaturaId`: ID de candidatura existente (opcional)
- `debounceTime`: Tiempo de espera antes de guardar (default: 3000ms)
- `showNotifications`: Mostrar notificaciones (default: true)
- `useLocalStorage`: Usar respaldo local (default: true)
- `localStorageKey`: Clave para localStorage

## Flujo de Trabajo

### Crear Nueva Candidatura
1. Usuario accede a `/candidaturas/create`
2. Sistema verifica si hay borrador local guardado
3. Si existe, pregunta al usuario si desea recuperarlo
4. Inicia el watcher para detectar cambios
5. Cada cambio se guarda automáticamente después de 3 segundos
6. Al enviar exitosamente, se limpia el localStorage

### Editar Candidatura Existente
1. Usuario accede a `/candidaturas/{id}/edit`
2. Solo se activa autoguardado si el estado es `borrador` o `rechazado`
3. Los cambios se guardan en la candidatura existente
4. No se permite autoguardado en estados `pendiente` o `aprobado`

## Indicadores Visuales

El sistema muestra el estado del autoguardado en la interfaz:

- **🔄 Guardando automáticamente...**: Cuando está guardando
- **✅ Guardado automáticamente a las HH:MM:SS**: Guardado exitoso
- **⚠️ Guardado localmente (sin conexión)**: Error de conexión
- **⏰ Autoguardado activado**: Estado inicial

## Manejo de Errores

### Errores de Red
- Los datos se guardan automáticamente en localStorage
- Se muestra notificación indicando que está guardado localmente
- Al recuperar conexión, se puede intentar guardar manualmente

### Validación Relajada
- El autoguardado NO valida campos requeridos
- Permite guardar formularios parcialmente completados
- La validación completa solo ocurre al enviar el formulario

## Consideraciones de Seguridad

1. **Autenticación**: Solo usuarios autenticados pueden usar autoguardado
2. **Autorización**: Solo el dueño de la candidatura puede autoguardar
3. **CSRF**: Todas las peticiones incluyen token CSRF
4. **Limpieza de Datos**: Se sanitizan los datos antes de guardar

## Limitaciones

1. No funciona en navegadores con localStorage deshabilitado
2. Los archivos no se suben hasta el envío final del formulario
3. El autoguardado no está disponible para candidaturas en estado `pendiente` o `aprobado`
4. Los datos locales se eliminan después de 24 horas

## Mantenimiento

### Logs
Los errores de autoguardado se registran en:
- Backend: `storage/logs/laravel.log`
- Frontend: Consola del navegador

### Monitoreo
Revisar periódicamente:
- Cantidad de autoguardados por día
- Errores frecuentes de autoguardado
- Uso de espacio en localStorage

## Futuras Mejoras

1. **Sincronización Offline**: Queue de cambios para sincronizar cuando vuelva la conexión
2. **Compresión**: Comprimir datos antes de guardar en localStorage
3. **Versionado**: Mantener múltiples versiones de borradores
4. **Colaboración**: Permitir múltiples usuarios editando (con bloqueos)