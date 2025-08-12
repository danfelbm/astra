# DivipolSeeder - Importador de División Política

## Descripción
Este seeder importa automáticamente la división política de Colombia desde el archivo `divipol.csv` que contiene la estructura jerárquica de:
- Territorios (País)
- Departamentos  
- Municipios
- Localidades (comunas, corregimientos, etc.)

## Uso

### Ejecución individual
```bash
php artisan db:seed --class=DivipolSeeder
```

### Ejecución en migración fresh
El seeder ya está incluido en `DatabaseSeeder.php`, por lo que se ejecutará automáticamente con:
```bash
php artisan migrate:fresh --seed
```

## Estructura del CSV
El archivo `divipol.csv` debe estar en la raíz del proyecto y tener las siguientes columnas:
1. **País**: Nombre del país (Colombia)
2. **Departamento**: Nombre del departamento
3. **Ciudad o Municipio**: Nombre del municipio
4. **Localidad**: Nombre de la localidad (comuna, corregimiento, "No Aplica" para municipios pequeños)
5. **Departamento con código**: Formato "NOMBRE - CODIGO"
6. **Municipio con código**: Formato "NOMBRE - CODIGO"

## Características
- **Transaccional**: Usa transacciones para garantizar integridad
- **Idempotente**: No duplica registros si se ejecuta múltiples veces
- **Cacheo en memoria**: Optimiza rendimiento evitando consultas repetidas
- **Manejo de errores**: Rollback automático en caso de error
- **Feedback visual**: Muestra progreso y resumen al finalizar

## Estadísticas de importación
Al ejecutar con el archivo actual se crean:
- 2 Territorios
- 35 Departamentos
- 1,306 Municipios
- 1,349 Localidades

## Notas importantes
- Las localidades con valor "No Aplica" no se importan
- Los códigos se extraen automáticamente del formato "NOMBRE - CODIGO"
- Todos los registros se crean con `activo = true` por defecto
- El territorio Colombia se crea con código "CO"

## Estructura de base de datos

### Territorios
- `id`: bigint (PK)
- `nombre`: string (unique)
- `codigo`: string(10) nullable
- `activo`: boolean default true
- `timestamps`

### Departamentos
- `id`: bigint (PK)
- `territorio_id`: bigint (FK)
- `nombre`: string
- `codigo`: string(10) nullable
- `activo`: boolean default true
- `timestamps`
- Unique: `[territorio_id, nombre]`

### Municipios
- `id`: bigint (PK)
- `departamento_id`: bigint (FK)
- `nombre`: string
- `codigo`: string(10) nullable
- `activo`: boolean default true
- `timestamps`
- Unique: `[departamento_id, nombre]`

### Localidades
- `id`: bigint (PK)
- `municipio_id`: bigint (FK)
- `nombre`: string
- `codigo`: string(10) nullable
- `activo`: boolean default true
- `timestamps`
- Unique: `[municipio_id, nombre]`

## Mantenimiento
Para actualizar los datos:
1. Reemplazar el archivo `divipol.csv` con la versión actualizada
2. Ejecutar: `php artisan db:seed --class=DivipolSeeder`
3. El seeder detectará y creará solo los registros nuevos