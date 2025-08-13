-- ====================================
-- IMPORTACIÓN MASIVA DE USUARIOS DESDE wp_users.csv
-- Versión sin LOAD DATA LOCAL INFILE
-- ====================================

-- 1. Crear tabla temporal para importar CSV
DROP TABLE IF EXISTS temp_wp_users;
CREATE TABLE temp_wp_users (
    wp_id INT,
    user_login VARCHAR(255),
    user_email VARCHAR(255),
    cedula VARCHAR(50),
    departamento VARCHAR(255),
    municipio VARCHAR(255)
);

-- 2. Habilitar local_infile para esta sesión
SET GLOBAL local_infile = 1;

-- 3. Importar CSV usando ruta absoluta sin LOCAL
LOAD DATA INFILE '/Users/testuser/Herd/votaciones/public/wp_users.csv'
INTO TABLE temp_wp_users
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(wp_id, user_login, user_email, cedula, departamento, municipio);

-- 4. Limpiar datos (convertir "NULL" string a NULL real)
UPDATE temp_wp_users SET 
    cedula = NULLIF(cedula, 'NULL'),
    departamento = NULLIF(departamento, 'NULL'),
    municipio = NULLIF(municipio, 'NULL'),
    cedula = NULLIF(cedula, ''),
    departamento = NULLIF(departamento, ''),
    municipio = NULLIF(municipio, '');

-- 5. Normalizar nombres para matching flexible
-- Función para normalizar (quitar acentos, minúsculas, sin espacios)
DELIMITER //
DROP FUNCTION IF EXISTS normalize_name //
CREATE FUNCTION normalize_name(input_name VARCHAR(255))
RETURNS VARCHAR(255)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE result VARCHAR(255);
    IF input_name IS NULL THEN
        RETURN NULL;
    END IF;
    SET result = LOWER(input_name);
    SET result = REPLACE(result, 'á', 'a');
    SET result = REPLACE(result, 'é', 'e');
    SET result = REPLACE(result, 'í', 'i');
    SET result = REPLACE(result, 'ó', 'o');
    SET result = REPLACE(result, 'ú', 'u');
    SET result = REPLACE(result, 'ñ', 'n');
    SET result = REPLACE(result, 'ü', 'u');
    SET result = REGEXP_REPLACE(result, '[^a-z0-9]', '');
    RETURN result;
END //
DELIMITER ;

-- 6. Agregar columnas para IDs mapeados
ALTER TABLE temp_wp_users 
ADD COLUMN departamento_id BIGINT,
ADD COLUMN municipio_id BIGINT;

-- 7. Mapear departamentos usando normalización flexible
UPDATE temp_wp_users t
JOIN departamentos d ON normalize_name(d.nombre) = normalize_name(t.departamento)
SET t.departamento_id = d.id
WHERE t.departamento IS NOT NULL AND d.activo = 1;

-- 8. Mapear municipios (requiere departamento correcto)
UPDATE temp_wp_users t
JOIN municipios m ON normalize_name(m.nombre) = normalize_name(t.municipio)
JOIN departamentos d ON m.departamento_id = d.id AND normalize_name(d.nombre) = normalize_name(t.departamento)
SET t.municipio_id = m.id
WHERE t.municipio IS NOT NULL 
  AND t.departamento IS NOT NULL 
  AND m.activo = 1 
  AND d.activo = 1;

-- 9. Estadísticas de mapeo
SELECT 
    COUNT(*) as total_registros,
    COUNT(departamento_id) as con_departamento,
    COUNT(municipio_id) as con_municipio,
    COUNT(CASE WHEN cedula IS NOT NULL THEN 1 END) as con_cedula
FROM temp_wp_users;

-- 10. Mostrar algunos casos de mapeo fallido para debug
SELECT 'Departamentos no mapeados:' as debug_info;
SELECT DISTINCT departamento, COUNT(*) as cantidad
FROM temp_wp_users 
WHERE departamento IS NOT NULL AND departamento_id IS NULL
GROUP BY departamento
ORDER BY cantidad DESC
LIMIT 5;

-- 11. Importar/actualizar usuarios 
-- Primero insertar usuarios nuevos (que no existen por email)
INSERT INTO users (
    tenant_id,
    name,
    email,
    documento_identidad,
    departamento_id,
    municipio_id,
    password,
    activo,
    created_at,
    updated_at
)
SELECT 
    1 as tenant_id,
    COALESCE(NULLIF(user_login, ''), CONCAT('Usuario_', wp_id)) as name,
    user_email,
    cedula as documento_identidad,
    departamento_id,
    municipio_id,
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password,
    1 as activo,
    NOW() as created_at,
    NOW() as updated_at
FROM temp_wp_users
WHERE user_email IS NOT NULL 
  AND user_email != ''
  AND user_email NOT LIKE '%@%@%'
  AND NOT EXISTS (SELECT 1 FROM users WHERE email = temp_wp_users.user_email);

-- 12. Actualizar usuarios existentes (por email)
UPDATE users u
JOIN temp_wp_users t ON u.email = t.user_email
SET 
    u.name = COALESCE(NULLIF(t.user_login, ''), u.name),
    u.documento_identidad = CASE 
        WHEN t.cedula IS NOT NULL AND t.cedula != '' THEN t.cedula
        ELSE u.documento_identidad
    END,
    u.departamento_id = COALESCE(t.departamento_id, u.departamento_id),
    u.municipio_id = COALESCE(t.municipio_id, u.municipio_id),
    u.updated_at = NOW()
WHERE t.user_email IS NOT NULL 
  AND t.user_email != '';

-- 13. Estadísticas finales
SELECT 'ESTADÍSTICAS FINALES:' as info;
SELECT 
    (SELECT COUNT(*) FROM temp_wp_users WHERE user_email IS NOT NULL AND user_email != '') as registros_validos_csv,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1) as usuarios_total_tenant,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1 AND documento_identidad IS NOT NULL) as con_cedula,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1 AND departamento_id IS NOT NULL) as con_departamento,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1 AND municipio_id IS NOT NULL) as con_municipio;

-- 14. Limpiar
DROP TABLE temp_wp_users;
DROP FUNCTION normalize_name;

SELECT '✅ Importación completada exitosamente' as resultado;