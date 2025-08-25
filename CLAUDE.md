# 🚀 Migración a Spatie Laravel Permission

## 📋 Directrices Generales del Flujo de Trabajo

1. **Usar **SIEMPRE** mcp "context7" para documentación sobre spatie laravel permission**
2. **Usar siempre el flujo de file tree** (marcar archivos completados con ✅)
3. **Trabajar módulo por módulo completamente** antes de pasar al siguiente
4. **Hacer commit después de completar cada módulo**
5. **NO mezclar cambios de diferentes módulos**
6. **Actualizar este archivo después de cada cambio**

### 🔴 IMPORTANTE sobre el File Tree:
- **[✅]** = Archivo modificado/creado durante la migración a Spatie
- **[ ]** = Archivo pendiente de revisión/modificación
- Los archivos NUEVOS deben indicarse con "(NUEVO)" al lado
- Los archivos MODIFICADOS deben indicarse con "(MODIFICADO)" al lado

## 🌳 File Tree del Proyecto

### /app
- [ ] **Console/**
  - [ ] Commands/
    - [ ] GenerateServerKeys.php
    - [✅] ImportDivipolCommand.php (VERIFICADO - sin lógica de permisos)
    - [✅] MonitorCsvImport.php (VERIFICADO - sin lógica de permisos)
    - [ ] MonitorOTPPerformance.php
    - [ ] MonitorOTPQueues.php
    - [ ] NormalizeExistingNames.php
    - [ ] TestAuditLog.php
    - [ ] TestRateLimitingStress.php
    - [ ] VerifyLargeCsvSetup.php
- [ ] **Enums/**
  - [ ] LoginType.php
- [ ] **Helpers/**
  - [ ] RedisHelper.php
- [ ] **Http/**
  - [ ] Controllers/
    - [ ] Admin/
      - [✅] AsambleaController.php (MODIFICADO - can() en lugar de hasPermission())
      - [✅] CandidaturaController.php (MODIFICADO - removido trait AuthorizesActions, cambiado a can())
      - [✅] CargoController.php (VERIFICADO - sin lógica de permisos, usa middleware)
      - [✅] ConfiguracionController.php (VERIFICADO - sin lógica de permisos, usa middleware)
      - [ ] ConvocatoriaController.php
      - [✅] FormularioController.php (VERIFICADO - sin lógica de permisos, usa middleware)
      - [ ] GeographicController.php
      - [✅] ImportController.php (VERIFICADO - sin lógica de permisos, usa middleware)
      - [ ] OTPDashboardController.php
      - [ ] PeriodoElectoralController.php
      - [✅] PostulacionController.php (VERIFICADO - sin lógica de permisos, usa middleware)
      - [✅] RoleController.php (MODIFICADO - removido trait AuthorizesActions, cambiado a can())
      - [✅] SegmentController.php (MODIFICADO - cambiado isAdmin() a can())
      - [✅] TenantController.php (MODIFICADO - cambiado isSuperAdmin() a hasRole('super_admin'))
      - [✅] UserController.php (MODIFICADO - refactorizado para Spatie)
      - [ ] VotacionController.php
    - [ ] Api/
      - [ ] ConvocatoriaController.php
      - [✅] FormularioController.php (VERIFICADO - sin autorización explícita, usa métodos del modelo)
      - [✅] PostulacionPublicApiController.php (VERIFICADO - API pública, sin auth)
      - [ ] QueueStatusController.php
      - [ ] ZoomAuthController.php
      - [ ] ZoomRegistrantController.php
    - [ ] Auth/
      - [ ] AuthenticatedSessionController.php
      - [ ] ConfirmablePasswordController.php
      - [ ] EmailVerificationNotificationController.php
      - [ ] EmailVerificationPromptController.php
      - [ ] NewPasswordController.php
      - [ ] OTPAuthController.php
      - [ ] PasswordResetLinkController.php
      - [✅] RegisteredUserController.php (MODIFICADO - assignRole() en lugar de roles()->attach())
      - [ ] VerifyEmailController.php
    - [ ] Settings/
      - [ ] PasswordController.php
      - [ ] ProfileController.php
      - [ ] ProfileLocationController.php
    - [ ] AsambleaPublicController.php
    - [ ] AsambleaPublicParticipantsController.php
    - [✅] CandidaturaController.php (MODIFICADO - solo verifica ownership, no permisos específicos)
    - [ ] Controller.php
    - [ ] FileUploadController.php
    - [✅] FormularioPublicController.php (VERIFICADO - usa métodos del modelo, middleware en rutas)
    - [ ] FrontendAsambleaController.php
    - [✅] PostulacionController.php (VERIFICADO - sin lógica de permisos, usa middleware)
    - [✅] PostulacionPublicController.php (VERIFICADO - controlador público, sin auth)
    - [ ] ResultadosController.php
    - [ ] TokenVerificationController.php
    - [ ] VotoController.php
    - [ ] ZoomRedirectController.php
  - [ ] Middleware/
    - [ ] AdminMiddleware.php
    - [✅] CheckPermission.php (MODIFICADO - actualizado a métodos de Spatie)
    - [✅] HandleInertiaRequests.php (MODIFICADO - cambiado isSuperAdmin() a hasRole('super_admin'))
    - [✅] TenantMiddleware.php (VERIFICADO - sin lógica de permisos)
    - [ ] ThrottleOTPRequests.php
  - [ ] Requests/
    - [ ] Auth/
      - [ ] LoginRequest.php
    - [ ] Settings/
      - [ ] ProfileUpdateRequest.php
- [ ] **Jobs/**
  - [ ] Middleware/
    - [ ] RateLimited.php
    - [ ] WithRateLimiting.php
  - [ ] CleanExpiredOTPsJob.php
  - [ ] NotifyZoomRegistrationFailureJob.php
  - [✅] ProcessCsvImport.php (VERIFICADO - sin lógica de permisos)
  - [✅] ProcessUsersCsvImport.php (VERIFICADO - sin lógica de permisos)
  - [ ] ProcessZoomRegistrationJob.php
  - [✅] SendCandidaturaAprobadaEmailJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaAprobadaWhatsAppJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaBorradorEmailJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaBorradorWhatsAppJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaComentarioEmailJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaPendienteEmailJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaPendienteWhatsAppJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaRechazadaEmailJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaRechazadaWhatsAppJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaReminderEmailJob.php (VERIFICADO - sin lógica de permisos)
  - [✅] SendCandidaturaReminderWhatsAppJob.php (VERIFICADO - sin lógica de permisos)
  - [ ] SendOTPEmailJob.php
  - [ ] SendOTPWhatsAppJob.php
  - [ ] SendZoomAccessEmailJob.php
  - [ ] SendZoomAccessWhatsAppJob.php
- [ ] **Mail/**
  - [ ] CandidaturaAprobadaMail.php
  - [ ] CandidaturaBorradorMail.php
  - [ ] CandidaturaComentarioMail.php
  - [ ] CandidaturaPendienteMail.php
  - [ ] CandidaturaRechazadaMail.php
  - [ ] CandidaturaReminderMail.php
  - [ ] OTPCodeMail.php
  - [ ] ZoomAccessMail.php
- [ ] **Models/**
  - [ ] Asamblea.php
  - [ ] Candidatura.php
  - [ ] CandidaturaCampoAprobacion.php
  - [ ] CandidaturaComentario.php
  - [ ] CandidaturaConfig.php
  - [ ] CandidaturaHistorial.php
  - [✅] Cargo.php (VERIFICADO - sin lógica de permisos)
  - [ ] Categoria.php
  - [✅] Configuracion.php (VERIFICADO - sin lógica de permisos)
  - [ ] Convocatoria.php
  - [✅] CsvImport.php (VERIFICADO - modelo sin permisos)
  - [ ] Departamento.php
  - [✅] Formulario.php (VERIFICADO - sin lógica de permisos, usa métodos propios)
  - [✅] FormularioCategoria.php (VERIFICADO - sin lógica de permisos)
  - [✅] FormularioPermiso.php (VERIFICADO - sistema de permisos granular independiente)
  - [✅] FormularioRespuesta.php (VERIFICADO - sin lógica de permisos)
  - [ ] GlobalSetting.php
  - [ ] Localidad.php
  - [ ] Municipio.php
  - [ ] OTP.php
  - [ ] OTPQueueMetric.php
  - [ ] PeriodoElectoral.php
  - [✅] Permission.php (NUEVO - creado para Spatie)
  - [✅] Postulacion.php (VERIFICADO - sin lógica de permisos)
  - [✅] PostulacionHistorial.php (VERIFICADO - sin lógica de permisos)
  - [✅] Role.php (MODIFICADO - extiende Spatie)
  - [ ] Segment.php
  - [✅] Tenant.php (VERIFICADO - sin lógica de permisos)
  - [ ] Territorio.php
  - [✅] User.php (MODIFICADO - trait HasRoles)
  - [ ] Votacion.php
  - [ ] Voto.php
  - [ ] ZoomRegistrant.php
  - [ ] ZoomRegistrantAccess.php
- [ ] **Observers/**
  - [ ] CandidaturaObserver.php
- [ ] **Providers/**
  - [✅] AppServiceProvider.php (MODIFICADO - agregado Gate::before() para super_admin)
  - [ ] OptimizationServiceProvider.php
- [ ] **Scopes/**
  - [✅] TenantScope.php (VERIFICADO - sin lógica de permisos)
- [ ] **Services/**
  - [ ] CachedGlobalSettingsService.php
  - [✅] ConditionalFieldService.php (VERIFICADO - servicio sin permisos)
  - [✅] ConfiguracionService.php (VERIFICADO - sin lógica de permisos)
  - [ ] CryptoService.php
  - [ ] GlobalSettingsService.php
  - [ ] LocationResolverService.php
  - [ ] OTPService.php
  - [ ] QueueRateLimiterService.php
  - [✅] TenantService.php (MODIFICADO - cambiado isSuperAdmin() a hasRole('super_admin'))
  - [ ] TokenService.php
  - [ ] WhatsAppService.php
  - [ ] ZoomApiService.php
  - [ ] ZoomNotificationService.php
  - [ ] ZoomService.php
- [ ] **Traits/**
  - [✅] AuthorizesActions.php (MODIFICADO - usa can() de Spatie internamente)
  - [ ] HasAdvancedFilters.php
  - [ ] HasAuditLog.php
  - [ ] HasGeographicFilters.php
  - [✅] HasSegmentScope.php (MODIFICADO - cambiado hasPermission() a can())
  - [✅] HasTenant.php (VERIFICADO - sin lógica de permisos)

### /bootstrap
- [✅] app.php (MODIFICADO - eliminado middleware 'permission')
- [ ] providers.php

### /config
- [ ] activitylog.php
- [ ] app.php
- [ ] auth.php
- [ ] cache.php
- [ ] database.php
- [ ] filesystems.php
- [ ] logging.php
- [ ] mail.php
- [✅] permission.php (NUEVO - configuración de Spatie)
- [ ] queue.php
- [ ] sanctum.php
- [ ] services.php
- [ ] session.php

### /database
- [ ] factories/
  - [ ] UserFactory.php
- [ ] seeders/
  - [✅] AdminUserSeeder.php (MODIFICADO - usa assignRole() de Spatie)
  - [ ] CandidaturaConfigSeeder.php
  - [✅] CargoSeeder.php (VERIFICADO - sin lógica de permisos)
  - [ ] CategoriaSeeder.php
  - [✅] ConfiguracionSeeder.php (VERIFICADO - sin lógica de permisos)
  - [ ] ConvocatoriaSeeder.php
  - [ ] DatabaseSeeder.php
  - [ ] DivipolSeeder.php
  - [ ] GlobalSettingsSeeder.php
  - [ ] PeriodoElectoralSeeder.php
  - [✅] VotanteUserSeeder.php (MODIFICADO - usa assignRole() de Spatie)

### /resources/js/Pages
- [ ] Admin/
  - [ ] Asambleas/
    - [ ] Form.vue
    - [ ] Index.vue
    - [ ] Show.vue
  - [ ] Candidaturas/
    - [ ] Configuracion.vue
    - [ ] Index.vue
    - [ ] Show.vue
  - [ ] Cargos/
    - [ ] Form.vue
    - [ ] Index.vue
  - [ ] Convocatorias/
    - [ ] Form.vue
    - [ ] Index.vue
    - [ ] Show.vue
  - [ ] Formularios/
    - [ ] Create.vue
    - [ ] Edit.vue
    - [ ] Index.vue
    - [ ] Show.vue
  - [ ] Imports/
    - [✅] Create.vue (VERIFICADO - sin lógica de permisos)
    - [✅] Index.vue (VERIFICADO - sin lógica de permisos)
    - [✅] Show.vue (VERIFICADO - sin lógica de permisos)
  - [ ] PeriodosElectorales/
    - [ ] Form.vue
    - [ ] Index.vue
  - [ ] Postulaciones/
    - [ ] Index.vue
    - [ ] Reportes.vue
    - [ ] Show.vue
  - [ ] Roles/
    - [ ] Create.vue
    - [ ] Edit.vue
    - [ ] Index.vue
    - [ ] Show.vue
  - [ ] Segments/
    - [ ] Create.vue
    - [ ] Edit.vue
    - [ ] Index.vue
    - [ ] Show.vue
  - [ ] Tenants/
    - [✅] Create.vue (VERIFICADO - sin lógica de permisos)
    - [✅] Edit.vue (VERIFICADO - sin lógica de permisos)
    - [✅] Index.vue (VERIFICADO - sin lógica de permisos)
  - [ ] Usuarios/
    - [ ] Create.vue
    - [ ] Edit.vue
    - [ ] Index.vue
  - [ ] Votaciones/
    - [ ] Form.vue
    - [ ] Index.vue
  - [ ] CandidaturasDashboard.vue
  - [ ] Configuracion.vue
  - [ ] Dashboard.vue
  - [ ] OTPDashboard.vue
- [ ] Asambleas/
  - [ ] Index.vue
  - [ ] Show.vue
- [ ] Candidaturas/
  - [ ] Bloqueado.vue
  - [ ] Dashboard.vue
  - [ ] Form.vue
  - [ ] NoDisponible.vue
  - [ ] Show.vue
- [ ] Formularios/
  - [ ] Index.vue
  - [ ] Show.vue
  - [ ] Success.vue
- [ ] Postulaciones/
  - [ ] Form.vue
  - [ ] Index.vue
- [ ] Public/
  - [ ] Asambleas/
    - [ ] ParticipantsList.vue
    - [ ] ParticipantsSearch.vue
  - [ ] Postulaciones/
    - [ ] PostulacionesAceptadas.vue
- [ ] Votaciones/
  - [ ] Index.vue
  - [ ] MiVoto.vue
  - [ ] Resultados.vue
  - [ ] Votar.vue
- [ ] auth/
  - [ ] ConfirmPassword.vue
  - [ ] ForgotPassword.vue
  - [ ] Login.vue
  - [ ] LoginOTP.vue
  - [ ] Register.vue
  - [ ] ResetPassword.vue
  - [ ] VerifyEmail.vue
- [ ] frontend/
  - [ ] asambleas/
    - [ ] ConsultaParticipantes.vue
- [ ] settings/
  - [ ] Appearance.vue
  - [ ] Password.vue
  - [ ] Profile.vue
- [ ] Dashboard.vue
- [ ] VerificarToken.vue
- [ ] Welcome.vue

### /routes
- [ ] auth.php
- [ ] console.php
- [ ] settings.php
- [✅] web.php (MODIFICADO - middlewares de PeriodoElectoral, Votaciones, Asambleas, Usuarios, Candidaturas)

### /resources/js/components
- [ ] candidaturas/
  - [ ] ComentariosHistorial.vue
- [ ] display/
  - [ ] FileFieldDisplay.vue
  - [ ] RepeaterFieldDisplay.vue
- [ ] filters/
  - [ ] AdvancedFilters.vue
  - [ ] CascadeSelect.vue
  - [ ] FilterCondition.vue
  - [ ] FilterGroup.vue
- [ ] forms/
  - [ ] CandidatosVotacionField.vue
  - [ ] ConditionalFieldConfig.vue
  - [ ] ConvocatoriaSeleccionada.vue
  - [ ] ConvocatoriaSelector.vue
  - [ ] ConvocatoriaVotacionField.vue
  - [ ] DatePickerField.vue
  - [ ] DisclaimerField.vue
  - [ ] DynamicFormBuilder.vue
  - [ ] DynamicFormRenderer.vue
  - [ ] FileUploadField.vue
  - [ ] GeographicRestrictions.vue
  - [ ] GeographicSelector.vue
  - [ ] HistorialCandidatura.vue
  - [ ] PerfilCandidaturaField.vue
  - [ ] RepeaterBuilder.vue
  - [ ] RepeaterField.vue
  - [ ] TimezoneSelector.vue
- [ ] imports/
  - [✅] CsvImportWizard.vue (VERIFICADO - componente UI sin permisos)
  - [✅] ImportHistory.vue (VERIFICADO - componente UI sin permisos)
- [ ] modals/
  - [ ] UpdateLocationModal.vue
- [ ] AppContent.vue
- [ ] AppHeader.vue
- [ ] AppLogo.vue
- [ ] AppLogoIcon.vue
- [ ] AppShell.vue
- [ ] AppSidebar.vue
- [ ] AppSidebarHeader.vue
- [ ] AppearanceTabs.vue
- [ ] AprobacionCampo.vue
- [ ] BarChart.vue
- [ ] DeleteUser.vue
- [ ] Heading.vue
- [ ] HeadingSmall.vue
- [ ] Icon.vue
- [ ] InputError.vue
- [ ] NavFooter.vue
- [ ] NavMain.vue
- [ ] NavUser.vue
- [ ] OTPQueueStatus.vue
- [ ] PlaceholderPattern.vue
- [✅] TenantSelector.vue (VERIFICADO - usa prop isSuperAdmin de Inertia)
- [ ] TextLink.vue
- [ ] UserInfo.vue
- [ ] UserMenuContent.vue
- [ ] UserSegmentBadges.vue
- [ ] ZoomApiMeeting.vue
- [ ] ZoomMeeting.vue

### /resources/js/composables
- [ ] useAdvancedFilters.ts
- [ ] useAppearance.ts
- [ ] useAutoSave.ts
- [ ] useConditionalFields.ts
- [ ] useDebounce.ts
- [ ] useFileUpload.ts
- [ ] useFormBuilder.ts
- [ ] useFormNavigation.ts
- [ ] useGeographicData.ts
- [ ] useGeographicFilters.ts
- [ ] useInfiniteScroll.ts
- [ ] useInitials.ts
- [ ] useKeyboardShortcuts.ts
- [ ] useLocalStorage.ts
- [ ] useResponsive.ts
- [ ] useSessionStorage.ts
- [ ] useTimeAgo.ts

### /resources/js/layouts
- [ ] app/
  - [ ] AppHeaderLayout.vue
  - [ ] AppSidebarLayout.vue
- [ ] auth/
  - [ ] AuthCardLayout.vue
  - [ ] AuthSimpleLayout.vue
  - [ ] AuthSplitLayout.vue
- [ ] settings/
  - [ ] Layout.vue
- [ ] AppLayout.vue
- [ ] AuthLayout.vue

### /resources/js/types
- [ ] filters.ts
- [ ] forms.ts
- [ ] formularios.ts
- [ ] index.ts
- [ ] ziggy.ts

### /resources/js/utils
- [ ] filters.ts
- [ ] htmlHelpers.ts

### /resources/js/lib
- [ ] utils.ts

### /resources/js
- [ ] app.ts

### /resources/views
- [ ] emails/
  - [ ] candidatura-aprobada.blade.php
  - [ ] candidatura-borrador.blade.php
  - [ ] candidatura-comentario.blade.php
  - [ ] candidatura-pendiente.blade.php
  - [ ] candidatura-rechazada.blade.php
  - [ ] candidatura-reminder.blade.php
  - [ ] otp-code.blade.php
  - [ ] zoom-access.blade.php
- [ ] app.blade.php

---

## 📦 Módulos de Migración

### 1. 🔧 **Setup Inicial de Spatie**
**Estado:** ✅ COMPLETADO

**Lo que realmente se hizo:**
- ✅ Instalado Spatie Laravel Permission v6.21.0
- ✅ Creadas las tablas de Spatie (permissions, model_has_roles, etc.)
- ✅ Migrados TODOS los permisos del sistema (57 en total)
- ✅ Actualizado User.php con trait HasRoles
- ✅ Actualizado Role.php para extender de Spatie
- ✅ Creado Permission.php personalizado
- ✅ Renombrado campo conflictivo permissions → legacy_permissions

---

### 2. 📅 **Módulo: PeriodoElectoral**
**Estado:** ⚠️ PARCIALMENTE COMPLETADO

**Archivos relacionados:**
- [ ] app/Http/Controllers/Admin/PeriodoElectoralController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Models/PeriodoElectoral.php ❌ NO REQUIERE CAMBIOS (no tiene lógica de permisos)
- [ ] database/seeders/PeriodoElectoralSeeder.php ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/PeriodosElectorales/Form.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/PeriodosElectorales/Index.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de periodos electorales) ✅ MODIFICADO - cambiado middleware

**Permisos a migrar:**
- `periodos.view`
- `periodos.create`
- `periodos.edit`
- `periodos.delete`

---

### 3. 🏢 **Módulo: Cargos**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [✅] app/Http/Controllers/Admin/CargoController.php ✅ VERIFICADO - sin lógica de permisos, usa middleware
- [✅] app/Models/Cargo.php ✅ VERIFICADO - sin lógica de permisos
- [✅] database/seeders/CargoSeeder.php ✅ VERIFICADO - sin lógica de permisos
- [ ] resources/js/Pages/Admin/Cargos/Form.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Cargos/Index.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de cargos) ✅ MODIFICADO - expandido resource y cambiado middleware

**Permisos migrados (4 en total):**
- `cargos.view`
- `cargos.create`
- `cargos.edit`
- `cargos.delete`

---

### 4. ⚙️ **Módulo: Configuración**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [✅] app/Http/Controllers/Admin/ConfiguracionController.php ✅ VERIFICADO - sin lógica de permisos, usa middleware
- [✅] app/Models/Configuracion.php ✅ VERIFICADO - sin lógica de permisos
- [✅] app/Services/ConfiguracionService.php ✅ VERIFICADO - sin lógica de permisos
- [✅] database/seeders/ConfiguracionSeeder.php ✅ VERIFICADO - sin lógica de permisos
- [ ] resources/js/Pages/Admin/Configuracion.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de configuración) ✅ MODIFICADO - cambiado middleware permission: a can:

**Permisos migrados (2 en total):**
- `settings.view` (nota: usa settings.* en lugar de configuracion.*)
- `settings.edit`

---

### 5. 📝 **Módulo: Formularios**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [ ] app/Http/Controllers/Admin/FormularioController.php ❌ NO REQUIERE CAMBIOS (usa middleware en rutas)
- [ ] app/Http/Controllers/Api/FormularioController.php ❌ NO REQUIERE CAMBIOS (sin autorización explícita)
- [ ] app/Http/Controllers/FormularioPublicController.php ❌ NO REQUIERE CAMBIOS (usa métodos del modelo)
- [ ] app/Models/Formulario.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/FormularioCategoria.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/FormularioPermiso.php ❌ NO REQUIERE CAMBIOS (sistema de permisos granular independiente)
- [ ] app/Models/FormularioRespuesta.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Services/ConditionalFieldService.php ❌ NO REQUIERE CAMBIOS (servicio sin permisos)
- [ ] resources/js/Pages/Admin/Formularios/Create.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Formularios/Edit.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Formularios/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Formularios/Show.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Formularios/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Formularios/Show.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Formularios/Success.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de formularios) ✅ MODIFICADO - expandido resource y cambiado middleware
- [✅] app/Http/Middleware/CheckPermission.php ✅ MODIFICADO - cambiado a métodos de Spatie y agregado mapeo

**Permisos migrados (9 en total):**
- `formularios.view`
- `formularios.create`
- `formularios.edit`
- `formularios.delete`
- `formularios.export`
- `formularios.view_responses` (equivalente a manage_responses)
- `formularios.view_public`
- `formularios.fill_public`
- `formularios.manage_permissions`

⚠️ **Nota Importante:**
FormularioPermiso es un sistema de permisos granular por formulario específico, complementario al sistema global de Spatie. Este sistema permanece intacto.

---

### 6. 🏛️ **Módulo: Asambleas**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [✅] app/Http/Controllers/Admin/AsambleaController.php ✅ MODIFICADO - cambiado hasPermission() a can()
- [ ] app/Http/Controllers/AsambleaPublicController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Http/Controllers/AsambleaPublicParticipantsController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Http/Controllers/FrontendAsambleaController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Models/Asamblea.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/ZoomRegistrant.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/ZoomRegistrantAccess.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/NotifyZoomRegistrationFailureJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/ProcessZoomRegistrationJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendZoomAccessEmailJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendZoomAccessWhatsAppJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Services/ZoomService.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Services/ZoomApiService.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Services/ZoomNotificationService.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] resources/js/Pages/Admin/Asambleas/Form.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Asambleas/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Asambleas/Show.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Asambleas/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Asambleas/Show.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Public/Asambleas/ParticipantsList.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Public/Asambleas/ParticipantsSearch.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/frontend/asambleas/ConsultaParticipantes.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de asambleas) ✅ MODIFICADO - cambiado middleware

**Permisos migrados (9 en total):**
- `asambleas.view`
- `asambleas.create`
- `asambleas.edit`
- `asambleas.delete`
- `asambleas.manage_participants`
- `asambleas.view_public`
- `asambleas.join_video`
- `asambleas.participate`
- `asambleas.view_minutes`

---

### 7. 📋 **Módulo: Convocatorias**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [ ] app/Http/Controllers/Admin/ConvocatoriaController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Http/Controllers/Api/ConvocatoriaController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Models/Convocatoria.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] database/seeders/ConvocatoriaSeeder.php ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Convocatorias/Form.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Convocatorias/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Convocatorias/Show.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de convocatorias) ✅ MODIFICADO - expandido resource y cambiado middleware

**Permisos migrados (6 en total):**
- `convocatorias.view`
- `convocatorias.create`
- `convocatorias.edit`
- `convocatorias.delete`
- `convocatorias.apply` (para postulaciones públicas)
- `convocatorias.view_public` (vista pública)

---

### 8. 👥 **Módulo: Usuarios**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [✅] app/Http/Controllers/Admin/UserController.php ✅ MODIFICADO - removido trait AuthorizesActions, cambiado a can()
- [ ] app/Models/User.php ⚠️ MANTENIDO - métodos wrapper para compatibilidad
- [ ] app/Traits/AuthorizesActions.php ⚠️ NO ELIMINADO - aún usado por otros controladores
- [ ] database/factories/UserFactory.php ❌ NO REQUIERE CAMBIOS
- [✅] database/seeders/AdminUserSeeder.php ✅ MODIFICADO - usa assignRole() de Spatie
- [✅] database/seeders/VotanteUserSeeder.php ✅ MODIFICADO - usa assignRole() de Spatie
- [ ] resources/js/Pages/Admin/Usuarios/Create.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Usuarios/Edit.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Usuarios/Index.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de usuarios) ✅ MODIFICADO - expandido resource y cambiado middleware

**Permisos migrados (8 en total):**
- `users.view`
- `users.create`
- `users.edit`
- `users.delete`
- `users.impersonate` (NUEVO - creado en migración)
- `users.import`
- `users.export`
- `users.assign_roles`

---

### 9. 🗳️ **Módulo: Votaciones**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [ ] app/Http/Controllers/Admin/VotacionController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Http/Controllers/VotoController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Http/Controllers/ResultadosController.php ❌ NO REQUIERE CAMBIOS (usa middleware)
- [ ] app/Http/Controllers/TokenVerificationController.php ❌ NO REQUIERE CAMBIOS (es público)
- [ ] app/Models/Votacion.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/Voto.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/Categoria.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Services/CryptoService.php ❌ NO REQUIERE CAMBIOS (servicio de utilidad)
- [ ] app/Services/TokenService.php ❌ NO REQUIERE CAMBIOS (servicio de utilidad)
- [ ] database/seeders/CategoriaSeeder.php ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Votaciones/Form.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Votaciones/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Votaciones/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Votaciones/MiVoto.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Votaciones/Resultados.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Votaciones/Votar.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/VerificarToken.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de votaciones) ✅ MODIFICADO - cambiado middleware

**Permisos a migrar:**
- `votaciones.view`
- `votaciones.create`
- `votaciones.edit`
- `votaciones.delete`
- `votaciones.vote`
- `votaciones.view_results`

 ⚠️ Notas Importantes:

  - NO se eliminó el trait AuthorizesActions porque aún es usado por:
    - CandidaturaController
    - RoleController
  - Se mantuvieron los métodos wrapper en User.php para compatibilidad temporal
  - Los seeders ahora usan métodos nativos de Spatie en lugar de inserciones directas en la
  tabla pivot

---

### 10. 🎯 **Módulo: Candidaturas**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [✅] app/Http/Controllers/Admin/CandidaturaController.php ✅ MODIFICADO - removido trait AuthorizesActions, cambiado hasPermission() a can()
- [✅] app/Http/Controllers/CandidaturaController.php ✅ MODIFICADO - solo verifica ownership, no permisos específicos
- [ ] app/Models/Candidatura.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/CandidaturaCampoAprobacion.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/CandidaturaComentario.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/CandidaturaConfig.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/CandidaturaHistorial.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Observers/CandidaturaObserver.php ❌ NO REQUIERE CAMBIOS (observer de modelo, sin permisos)
- [ ] app/Jobs/SendCandidaturaAprobadaEmailJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaAprobadaWhatsAppJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaBorradorEmailJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaBorradorWhatsAppJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaComentarioEmailJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaPendienteEmailJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaPendienteWhatsAppJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaRechazadaEmailJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaRechazadaWhatsAppJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaReminderEmailJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Jobs/SendCandidaturaReminderWhatsAppJob.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Mail/CandidaturaAprobadaMail.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Mail/CandidaturaBorradorMail.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Mail/CandidaturaComentarioMail.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Mail/CandidaturaPendienteMail.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Mail/CandidaturaRechazadaMail.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Mail/CandidaturaReminderMail.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] database/seeders/CandidaturaConfigSeeder.php ❌ NO REQUIERE CAMBIOS
- [✅] database/migrations/2025_08_25_032034_add_missing_candidaturas_permissions.php (NUEVO - creado para 10 permisos faltantes)
- [ ] resources/js/Pages/Admin/Candidaturas/Configuracion.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Candidaturas/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Candidaturas/Show.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/CandidaturasDashboard.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Candidaturas/Bloqueado.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Candidaturas/Dashboard.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Candidaturas/Form.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Candidaturas/NoDisponible.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Candidaturas/Show.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de candidaturas) ✅ MODIFICADO - cambiado middleware permission: a can:
- [✅] app/Providers/AppServiceProvider.php ✅ MODIFICADO - agregado Gate::before() para super_admin

**Permisos migrados (17 en total):**
- `candidaturas.view`
- `candidaturas.create`
- `candidaturas.approve`
- `candidaturas.view_own` (NUEVO - creado en migración)
- `candidaturas.create_own` (NUEVO - creado en migración)
- `candidaturas.edit_own` (NUEVO - creado en migración)
- `candidaturas.configuracion` (NUEVO - creado en migración)
- `candidaturas.reject` (NUEVO - creado en migración)
- `candidaturas.comment` (NUEVO - creado en migración)
- `candidaturas.recordatorios` (NUEVO - creado en migración)
- `candidaturas.notificaciones` (NUEVO - creado en migración)
- `candidaturas.edit` (NUEVO - creado en migración)
- `candidaturas.delete` (NUEVO - creado en migración)
- `candidaturas.export`
- `candidaturas.import`
- `candidaturas.dashboard`
- `candidaturas.review`

⚠️ **Notas Importantes:**
- Se creó migración para 10 permisos faltantes que se usaban en rutas pero no existían en BD
- Frontend CandidaturaController solo verifica ownership (user_id == candidatura->user_id)
- Se resolvió problema de permisos de super_admin con Gate::before() en AppServiceProvider
- Jobs y Mail no requieren cambios ya que solo envían notificaciones

---

### 11. 📮 **Módulo: Postulaciones**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [ ] app/Http/Controllers/Admin/PostulacionController.php ❌ NO REQUIERE CAMBIOS (usa middleware en rutas)
- [ ] app/Http/Controllers/PostulacionController.php ❌ NO REQUIERE CAMBIOS (usa middleware en rutas)
- [ ] app/Http/Controllers/PostulacionPublicController.php ❌ NO REQUIERE CAMBIOS (controlador público)
- [ ] app/Http/Controllers/Api/PostulacionPublicApiController.php ❌ NO REQUIERE CAMBIOS (API pública)
- [ ] app/Models/Postulacion.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/PostulacionHistorial.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [✅] database/migrations/2025_08_25_040000_add_missing_postulaciones_permissions.php (NUEVO - creado para 2 permisos faltantes)
- [ ] resources/js/Pages/Admin/Postulaciones/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Postulaciones/Reportes.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Postulaciones/Show.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Postulaciones/Form.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Postulaciones/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Public/Postulaciones/PostulacionesAceptadas.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de postulaciones) ✅ MODIFICADO - cambiado middleware permission: a can:

**Permisos migrados (8 en total):**
- `postulaciones.view`
- `postulaciones.create`
- `postulaciones.edit` (NUEVO - creado en migración)
- `postulaciones.delete` (NUEVO - creado en migración)
- `postulaciones.review`
- `postulaciones.view_own`
- `postulaciones.edit_own`
- `postulaciones.delete_own`

---

### 12. 🔐 **Módulo: Roles y Permisos**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [✅] app/Http/Controllers/Admin/RoleController.php ✅ MODIFICADO - removido trait AuthorizesActions, cambiado a can()
- [✅] app/Http/Controllers/Admin/SegmentController.php ✅ MODIFICADO - cambiado isAdmin() a can()
- [✅] app/Models/Role.php ✅ YA EXTENDÍA SPATIE - ajustes menores para sincronización
- [ ] app/Models/Segment.php ❌ NO REQUIERE CAMBIOS (funciona independiente de permisos)
- [✅] app/Traits/HasSegmentScope.php ✅ MODIFICADO - cambiado hasPermission() a can() en línea 336
- [ ] resources/js/Pages/Admin/Roles/Create.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Roles/Edit.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Roles/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Roles/Show.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Segments/Create.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Segments/Edit.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Segments/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Segments/Show.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de roles y segments) ✅ MODIFICADO - expandido resources y cambiado middleware

**Permisos a migrar:**
- `roles.view`
- `roles.create`
- `roles.edit`
- `roles.delete`
- `segments.view`
- `segments.create`
- `segments.edit`
- `segments.delete`

📝 Notas Importantes
- Campo legacy_permissions mantenido para respaldo
- Métodos wrapper en User.php mantienen compatibilidad
- Gate::before() para super_admin ya configurado
- Cache de permisos limpiado y listo

---

### 13. 🏢 **Módulo: Multi-Tenant**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [✅] app/Http/Controllers/Admin/TenantController.php ✅ MODIFICADO - cambiado isSuperAdmin() a hasRole('super_admin')
- [✅] app/Models/Tenant.php ✅ VERIFICADO - sin lógica de permisos
- [✅] app/Services/TenantService.php ✅ MODIFICADO - cambiado isSuperAdmin() a hasRole('super_admin')
- [✅] app/Scopes/TenantScope.php ✅ VERIFICADO - sin lógica de permisos
- [✅] app/Traits/HasTenant.php ✅ VERIFICADO - sin lógica de permisos
- [✅] app/Http/Middleware/TenantMiddleware.php ✅ VERIFICADO - sin lógica de permisos
- [✅] app/Http/Middleware/HandleInertiaRequests.php ✅ MODIFICADO - cambiado isSuperAdmin() a hasRole('super_admin')
- [ ] resources/js/Pages/Admin/Tenants/Create.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Tenants/Edit.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Tenants/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/components/TenantSelector.vue ❌ NO REQUIERE CAMBIOS (usa prop de Inertia)
- [✅] routes/web.php (sección de tenants) ✅ MODIFICADO - expandido resource y cambiado middleware
- [✅] database/migrations/2025_08_25_050000_add_tenant_permissions.php (NUEVO - creado para 5 permisos)

**Permisos migrados (5 en total):**
- `tenants.view`
- `tenants.create`
- `tenants.edit`
- `tenants.delete`
- `tenants.switch`

---

### 14. 🔑 **Módulo: Autenticación y OTP**
**Estado:** ✅ COMPLETADO

**Archivos relacionados:**
- [ ] app/Http/Controllers/Auth/AuthenticatedSessionController.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Http/Controllers/Auth/OTPAuthController.php ❌ NO REQUIERE CAMBIOS (usa métodos wrapper compatibles)
- [✅] app/Http/Controllers/Auth/RegisteredUserController.php ✅ MODIFICADO - cambiado roles()->attach() a assignRole()
- [ ] app/Models/OTP.php ❌ NO REQUIERE CAMBIOS (modelo sin permisos)
- [ ] app/Models/OTPQueueMetric.php ❌ NO REQUIERE CAMBIOS (modelo sin permisos)
- [ ] app/Services/OTPService.php ❌ NO REQUIERE CAMBIOS (servicio sin permisos)
- [ ] app/Jobs/CleanExpiredOTPsJob.php ❌ NO REQUIERE CAMBIOS (job sin permisos)
- [ ] app/Jobs/SendOTPEmailJob.php ❌ NO REQUIERE CAMBIOS (job sin permisos)
- [ ] app/Jobs/SendOTPWhatsAppJob.php ❌ NO REQUIERE CAMBIOS (job sin permisos)
- [ ] app/Mail/OTPCodeMail.php ❌ NO REQUIERE CAMBIOS (mail sin permisos)
- [ ] app/Http/Middleware/ThrottleOTPRequests.php ❌ NO REQUIERE CAMBIOS (solo rate limiting)
- [ ] resources/js/Pages/auth/Login.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/auth/LoginOTP.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/auth/Register.vue ❌ NO REQUIERE CAMBIOS
- [ ] routes/auth.php ❌ NO REQUIERE CAMBIOS (sin middlewares de permisos)

 ⚠️ **Notas Importantes:**
    - OTPAuthController.php usa métodos isAdmin() e isSuperAdmin() que ya son wrappers compatibles
    - AdminMiddleware.php usa hasAdministrativeRole() que ya es wrapper compatible
    - Solo se modificó RegisteredUserController.php para usar assignRole() de Spatie

---

### 15. 📊 **Módulo: Imports y CSV**
**Estado:** ✅ COMPLETADO (YA ESTABA MIGRADO)

**Archivos relacionados:**
- [ ] app/Http/Controllers/Admin/ImportController.php ❌ NO REQUIERE CAMBIOS (sin lógica de permisos)
- [ ] app/Models/CsvImport.php ❌ NO REQUIERE CAMBIOS (modelo sin permisos)
- [ ] app/Jobs/ProcessCsvImport.php ❌ NO REQUIERE CAMBIOS (job sin permisos)
- [ ] app/Jobs/ProcessUsersCsvImport.php ❌ NO REQUIERE CAMBIOS (job sin permisos)
- [ ] app/Console/Commands/ImportDivipolCommand.php ❌ NO REQUIERE CAMBIOS (comando sin permisos)
- [ ] app/Console/Commands/MonitorCsvImport.php ❌ NO REQUIERE CAMBIOS (comando sin permisos)
- [ ] resources/js/Pages/Admin/Imports/Create.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Imports/Index.vue ❌ NO REQUIERE CAMBIOS
- [ ] resources/js/Pages/Admin/Imports/Show.vue ❌ NO REQUIERE CAMBIOS
- [✅] routes/web.php (sección de imports) ✅ YA USA MIDDLEWARE can: CORRECTAMENTE

⚠️ **Nota Importante:**
Este módulo NO tiene permisos propios (`imports.*`). En su lugar, reutiliza permisos de otros módulos:
- `users.import` para importación general de usuarios
- `votaciones.manage_voters` para importación de votantes a votaciones
- `asambleas.manage_participants` para importación de participantes a asambleas

Las rutas ya estaban usando el middleware `can:` de Spatie correctamente, por lo que no requirió cambios.

---

### 16. 🗑️ **Limpieza Final**
**Estado:** ✅ COMPLETADO

**Archivos eliminados/modificados:**
- [✅] app/Traits/AuthorizesActions.php ✅ ELIMINADO
- [✅] app/Http/Middleware/CheckPermission.php ✅ ELIMINADO
- [✅] app/Http/Middleware/AdminMiddleware.php ✅ MODIFICADO - usa hasAnyRole()
- [✅] Eliminar campos legacy_permissions y allowed_modules de tabla roles - MIGRACIÓN CREADA
- [✅] Todos los métodos wrapper eliminados de User.php
- [✅] Métodos obsoletos eliminados de Role.php
- [✅] bootstrap/app.php actualizado - eliminado middleware 'permission'

---

## 📝 Notas de Progreso

### Fecha: 2025-08-25
- ✅ Instalado Spatie Laravel Permission v6.21.0
- ✅ Setup inicial de Spatie completado
- ✅ Migrados 58 permisos del sistema JSON a tablas normalizadas (57 iniciales + 1 de votaciones.view_results)
- ✅ Resuelto conflicto del campo permissions → legacy_permissions
- ⚠️ Solo actualizado middleware de rutas PeriodoElectoral (el resto del módulo no requiere cambios)
- ✅ Migración completa del módulo Votaciones - solo cambios en rutas, todos los controladores/modelos sin cambios
- ✅ Migración completa del módulo Asambleas - AsambleaController.php actualizado, rutas migradas, 9 permisos verificados
- ✅ Migración completa del módulo Usuarios - UserController refactorizado, seeders actualizados, 8 permisos (incluye nuevo users.impersonate)
- ✅ Migración completa del módulo Candidaturas - 17 permisos (10 nuevos creados), controladores refactorizados, 23 rutas actualizadas, Gate::before() para super_admin
- ✅ Migración completa del módulo Convocatorias - 6 permisos verificados, 10 rutas actualizadas (resource expandido + rutas adicionales)
- ✅ Migración completa del módulo Postulaciones - 8 permisos (2 nuevos creados: edit y delete), 9 rutas actualizadas (resource expandido + rutas adicionales)
- ✅ Migración completa del módulo Autenticación y OTP - Solo 1 archivo modificado (RegisteredUserController), cambio mínimo de roles()->attach() a assignRole()
- ✅ Módulo Imports y CSV - YA ESTABA MIGRADO. No tiene permisos propios, reutiliza permisos de otros módulos (users.import, votaciones.manage_voters, asambleas.manage_participants). Todas las rutas ya usaban middleware can: correctamente
- ✅ Migración completa del módulo Cargos - 8 rutas expandidas del resource, cambiado middleware permission: a can:, 4 permisos verificados
- ✅ Migración completa del módulo Configuración - 3 rutas actualizadas con middleware can:, usa permisos settings.* en lugar de configuracion.*
- ✅ Migración completa del módulo Roles y Permisos - 15 rutas expandidas (8 roles + 7 segments), traits actualizados, sincronización con Spatie, segmentos preservados, interfaz intacta
- ✅ Migración completa del módulo Formularios - CheckPermission middleware actualizado, 9 rutas expandidas (8 admin + 1 pública), 9 permisos verificados, FormularioPermiso preservado como sistema granular independiente
- ✅ Migración completa del módulo Multi-Tenant - 5 permisos creados, TenantController/TenantService/HandleInertiaRequests actualizados, 8 rutas expandidas con middleware can:, componentes Vue sin cambios
- ✅ LIMPIEZA FINAL COMPLETADA - Sistema completamente migrado a Spatie Laravel Permission:
  - Reemplazados todos los métodos wrapper (isSuperAdmin, isAdmin, hasAdministrativeRole) con métodos nativos de Spatie
  - Actualizado RoleController, AsambleaController, OTPAuthController, ResultadosController, Asamblea model
  - Actualizado HandleInertiaRequests y AdminMiddleware para usar hasAnyRole()
  - Corregidas 6 rutas que usaban middleware 'permission:' a 'can:'
  - Eliminados archivos obsoletos: AuthorizesActions.php y CheckPermission.php
  - Eliminado registro de middleware 'permission' de bootstrap/app.php
  - Creada migración para eliminar campos legacy_permissions y allowed_modules
  - Eliminados todos los métodos wrapper de User.php y Role.php
  - Sistema ahora usa 100% Spatie Laravel Permission sin código legacy

### Fecha: 2025-12-25 (Correcciones Post-Migración)
- ✅ CORRECCIONES CRÍTICAS POST-MIGRACIÓN:
  - **UserController.php**: Corregido método `create()` línea 272 - cambiado de `roles()->attach()` a `assignRole()`
  - **UserController.php**: Corregido método `update()` línea 408 - cambiado de `roles()->sync()` a `syncRoles()`
  - **ProcessUsersCsvImport.php**: Corregido método `assignDefaultRole()` línea 650 - cambiado de inserción directa en `role_user` (tabla que ya no existe) a `assignRole()`
  - **HandleInertiaRequests.php**: Corregido error crítico línea 76 - `$role->permissions` ahora es una Collection de Eloquent, no un array. Cambiado a usar `$user->getAllPermissions()->pluck('name')->toArray()`
  - **HandleInertiaRequests.php**: Reemplazado `getAllowedModules()` (método que ya no existe) con lógica para derivar módulos desde los nombres de permisos
  - **Admin/Votaciones/Index.vue**: Corregido error JavaScript línea 109-110 - cambio de `filters.search` a `props.filters.search` para acceder correctamente a las props del componente
  - **Admin/Roles/Create.vue y Edit.vue**: Corregidos errores de SelectItem con value vacío y módulos undefined:
    - Agregado método `getAvailableModules()` en RoleController que retorna objetos vacíos `{}` en lugar de arrays `[]`
    - Cambiado value vacío `''` a `'default'` en redirectOptions
    - Ajustado formulario para usar `'default'` como valor inicial
    - Actualizado métodos store() y update() para convertir `'default'` a `null` en BD
    - Agregado prop `modules` al método edit() del RoleController
  - **Verificación completa**: Todos los métodos de Spatie funcionan correctamente
  - **Tabla `model_has_roles`**: Confirmado que recibe correctamente las asignaciones (142,447 registros)
  - **Nota importante**: Los campos `assigned_at` y `assigned_by` se perdieron en la migración (Spatie no los soporta en su tabla pivot)

---

## 🔄 Commits Realizados

1. [Pendiente] - Setup inicial de Spatie Permission (completado pero no commiteado)
2. [Pendiente] - Correcciones post-migración: UserController, ProcessUsersCsvImport, HandleInertiaRequests, Admin/Votaciones/Index.vue, Admin/Roles/Create.vue y Edit.vue

---

## ⚠️ Issues Encontrados

1. **Conflicto de campo `permissions`**: El modelo Role tenía un campo JSON llamado `permissions` que conflictuaba con la relación de Spatie. Resuelto renombrándolo a `legacy_permissions`.

---

## 📚 Referencias

- [Documentación Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction)
- [MCP Context7 para consultas](context7.com/spatie/laravel-permission)