-- ====================================
-- IMPORTACIÓN MASIVA DE USUARIOS DESDE wp_users.csv
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

-- 2. Importar CSV a tabla temporal
LOAD DATA LOCAL INFILE '/Users/testuser/Herd/votaciones/public/wp_users.csv'
INTO TABLE temp_wp_users
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(wp_id, user_login, user_email, cedula, departamento, municipio);

-- 3. Limpiar datos (convertir "NULL" string a NULL real)
UPDATE temp_wp_users SET 
    cedula = NULLIF(cedula, 'NULL'),
    departamento = NULLIF(departamento, 'NULL'),
    municipio = NULLIF(municipio, 'NULL'),
    cedula = NULLIF(cedula, ''),
    departamento = NULLIF(departamento, ''),
    municipio = NULLIF(municipio, '');

-- 4. Normalizar nombres para matching flexible
-- Función para normalizar (quitar acentos, minúsculas, sin espacios)
DELIMITER //
CREATE FUNCTION IF NOT EXISTS normalize_name(input_name VARCHAR(255))
RETURNS VARCHAR(255)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE result VARCHAR(255);
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

-- 5. Agregar columnas para IDs mapeados
ALTER TABLE temp_wp_users 
ADD COLUMN departamento_id BIGINT,
ADD COLUMN municipio_id BIGINT;

-- 6. Mapear departamentos usando normalización flexible
UPDATE temp_wp_users t
JOIN departamentos d ON normalize_name(d.nombre) = normalize_name(t.departamento)
SET t.departamento_id = d.id
WHERE t.departamento IS NOT NULL AND d.activo = 1;

-- 7. Mapear municipios (requiere departamento correcto)
UPDATE temp_wp_users t
JOIN municipios m ON normalize_name(m.nombre) = normalize_name(t.municipio)
JOIN departamentos d ON m.departamento_id = d.id AND normalize_name(d.nombre) = normalize_name(t.departamento)
SET t.municipio_id = m.id
WHERE t.municipio IS NOT NULL 
  AND t.departamento IS NOT NULL 
  AND m.activo = 1 
  AND d.activo = 1;

-- 8. Estadísticas de mapeo
SELECT 
    COUNT(*) as total_registros,
    COUNT(departamento_id) as con_departamento,
    COUNT(municipio_id) as con_municipio,
    COUNT(CASE WHEN cedula IS NOT NULL THEN 1 END) as con_cedula
FROM temp_wp_users;

-- 9. Mostrar algunos casos de mapeo fallido para debug
SELECT 'Departamentos no mapeados:' as debug_info;
SELECT DISTINCT departamento, COUNT(*) as cantidad
FROM temp_wp_users 
WHERE departamento IS NOT NULL AND departamento_id IS NULL
GROUP BY departamento
ORDER BY cantidad DESC
LIMIT 10;

SELECT 'Municipios no mapeados:' as debug_info;
SELECT DISTINCT municipio, departamento, COUNT(*) as cantidad
FROM temp_wp_users 
WHERE municipio IS NOT NULL AND municipio_id IS NULL
GROUP BY municipio, departamento
ORDER BY cantidad DESC
LIMIT 10;

-- 10. Importar/actualizar usuarios usando cedula como clave principal
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
    1 as tenant_id,                                    -- Organización Principal
    COALESCE(user_login, CONCAT('Usuario_', wp_id)) as name,
    user_email,
    cedula as documento_identidad,
    departamento_id,
    municipio_id,
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' as password, -- password = 'password'
    1 as activo,
    NOW() as created_at,
    NOW() as updated_at
FROM temp_wp_users
WHERE user_email IS NOT NULL 
  AND user_email != ''
  AND user_email NOT LIKE '%@%@%'  -- Filtrar emails malformados
ON DUPLICATE KEY UPDATE
    -- Actualizar usando email como clave si cedula causa conflicto
    name = VALUES(name),
    documento_identidad = COALESCE(VALUES(documento_identidad), users.documento_identidad),
    departamento_id = COALESCE(VALUES(departamento_id), users.departamento_id),
    municipio_id = COALESCE(VALUES(municipio_id), users.municipio_id),
    updated_at = NOW();

-- 11. Manejo de duplicados de cedula - actualizar por email si la cedula ya existe
UPDATE users u1
JOIN temp_wp_users t ON u1.email = t.user_email
SET 
    u1.name = COALESCE(t.user_login, u1.name),
    u1.departamento_id = COALESCE(t.departamento_id, u1.departamento_id),
    u1.municipio_id = COALESCE(t.municipio_id, u1.municipio_id),
    u1.updated_at = NOW()
WHERE t.cedula IS NOT NULL
  AND EXISTS (
      SELECT 1 FROM users u2 
      WHERE u2.documento_identidad = t.cedula 
      AND u2.id != u1.id
  );

-- 12. Estadísticas finales
SELECT 'ESTADÍSTICAS FINALES:' as info;
SELECT 
    (SELECT COUNT(*) FROM temp_wp_users) as registros_csv,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1) as usuarios_total,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1 AND documento_identidad IS NOT NULL) as con_cedula,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1 AND departamento_id IS NOT NULL) as con_departamento,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1 AND municipio_id IS NOT NULL) as con_municipio;

-- 13. Limpiar tabla temporal
DROP TABLE temp_wp_users;
DROP FUNCTION IF EXISTS normalize_name;

SELECT '✅ Importación completada exitosamente' as resultado;