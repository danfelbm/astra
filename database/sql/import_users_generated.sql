-- ====================================
-- IMPORTACIÓN MASIVA DE USUARIOS - GENERADO AUTOMÁTICAMENTE
-- Generado: 2025-08-13 06:23:56
-- ====================================

-- Función de normalización
DELIMITER //
DROP FUNCTION IF EXISTS normalize_name //
CREATE FUNCTION normalize_name(input_name VARCHAR(255))
RETURNS VARCHAR(255)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE result VARCHAR(255);
    IF input_name IS NULL THEN RETURN NULL; END IF;
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

-- Insertar usuarios nuevos (que no existen por email)
INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'aflandorffer@gmail.com', 'aflandorffer@gmail.com', '95990', 1, 52, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fer126721@gmail.com', 'fer126721@gmail.com', '413376', 9, 529, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'villanariel73@gmail.com', 'villanariel73@gmail.com', '785371', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaimemontoya612@gmail.com', 'jaimemontoya612@gmail.com', '2773757', 1, 37, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'naisercu@hotmail.com', 'naisercu@hotmail.com', '3017841', 26, 1062, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miguelernestorg@hotmail.com', 'miguelernestorg@hotmail.com', '3028913', 9, 465, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'crearsiglo21@gmail.com', 'crearsiglo21@gmail.com', '3128990', 9, 500, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'raulandresmedinam@gmail.com', 'raulandresmedinam@gmail.com', '3171475', 9, 517, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Carloshuertas12@yahoo.es', 'Carloshuertas12@yahoo.es', '3249034', 9, 487, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nachoestrada0509@gmail.com', 'nachoestrada0509@gmail.com', '3365387', 1, 54, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, '1239@gmail.com', '1239@gmail.com', '3586622', 1, 30, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'escoalvaro27@gmail.com', 'escoalvaro27@gmail.com', '3718866', 2, 127, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'delwinvg@hotmail.com', 'delwinvg@hotmail.com', '3731139', 7, 406, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'azocalure@gmail.com', 'azocalure@gmail.com', '3738025', 2, 136, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'docente.gustavolara@gmail.com', 'docente.gustavolara@gmail.com', '3741306', 2, 137, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jose-orozco815@hotmail.com', 'jose-orozco815@hotmail.com', '3762279', 2, 138, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pedrojjuliobeltran@gmail.com', 'pedrojjuliobeltran@gmail.com', '3771685', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorge_abo62@hotmail.com', 'jorge_abo62@hotmail.com', '3815119', 3, 154, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'acotera01@hotmail.com', 'acotera01@hotmail.com', '3824734', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eduardoatencia@gmail.com', 'eduardoatencia@gmail.com', '3843914', 19, 868, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carcamovegaorlando@gmail.com', 'carcamovegaorlando@gmail.com', '3876860', 3, 161, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leon25xlx@hotmail.com', 'leon25xlx@hotmail.com', '3961723', 3, 178, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'etnicocaribe@gmail.com', 'etnicocaribe@gmail.com', '4008681', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'boy.boy.humana@gmail.com', 'boy.boy.humana@gmail.com', '4061617', 4, 199, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ingluisbaezceron@gmail.com', 'ingluisbaezceron@gmail.com', '4079329', 4, 218, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'escobarildebrando@gmail.com', 'escobarildebrando@gmail.com', '4087424', 24, 1002, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sacama1504@gmail.com', 'sacama1504@gmail.com', '4104064', 24, 1013, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pizreyes@gmail.com', 'pizreyes@gmail.com', '4121497', 4, 226, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'benjobull@gmail.com', 'benjobull@gmail.com', '4130046', 4, 274, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vicentelopezraba@gmail.com', 'vicentelopezraba@gmail.com', '4146724', 4, 241, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josevelascolopez@hotmail.com', 'josevelascolopez@hotmail.com', '4148151', 4, 312, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hurtadoperezcarlos9@gmail.com', 'hurtadoperezcarlos9@gmail.com', '4190429', 24, 1018, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisurbanom69@gmail.com', 'luisurbanom69@gmail.com', '4243804', 4, 247, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vitebenitezortiz26@gmail.com', 'vitebenitezortiz26@gmail.com', '4270283', 24, 1015, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'agro20.tib@gmail.com', 'agro20.tib@gmail.com', '4275384', 4, 298, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'victormanuelmartinezreina@hotmail.es', 'victormanuelmartinezreina@hotmail.es', '4292327', 24, 1001, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abogadonelsonj@gmail.com', 'abogadonelsonj@gmail.com', '4380844', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jramirezx9@gmail.com', 'jramirezx9@gmail.com', '4406551', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cg0408733@gmail.com', 'cg0408733@gmail.com', '4442407', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, '1357dwke@gmail.com', '1357dwke@gmail.com', '4580032', 15, 716, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jairoalbertobolanosmamian@gmail.com', 'jairoalbertobolanosmamian@gmail.com', '4697357', 6, 357, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edadalca@hotmail.com', 'edadalca@hotmail.com', '4779827', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wilbero95@gmail.com', 'wilbero95@gmail.com', '4782039', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nisegu4813@gmail.com', 'nisegu4813@gmail.com', '4813640', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgecaicedojordan4@gmail.com', 'jorgecaicedojordan4@gmail.com', '4831098', 11, 564, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elchocopidio@gmail.com', 'elchocopidio@gmail.com', '4831389', 11, 568, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sistocortes123@gmail.com', 'sistocortes123@gmail.com', '4852302', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yamilantonio4861@gmail.com', 'yamilantonio4861@gmail.com', '4861861', 11, 577, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jairoivantrujillo20@gmail.com', 'jairoivantrujillo20@gmail.com', '4920045', 12, 597, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arperque610@yahoo.es', 'arperque610@yahoo.es', '4949440', 12, 614, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luanvequi01@gmail.com', 'luanvequi01@gmail.com', '5030611', 7, 393, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luiskarloslopez8@gmail.com', 'luiskarloslopez8@gmail.com', '5092096', 7, 397, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Adalberto0226@hotmail.com', 'Adalberto0226@hotmail.com', '5095096', 13, 636, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juliustar2000@gmail.com', 'juliustar2000@gmail.com', '5269797', 14, 673, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anyilsandybetsanchezmora@gmail.com', 'anyilsandybetsanchezmora@gmail.com', '5346851', 13, 623, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ricardoarboleda391@gmail.com', 'ricardoarboleda391@gmail.com', '5364793', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'saracruz756@hotmail.com', 'saracruz756@hotmail.com', '5453438', 16, 743, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'comunalcarrascal@gmail.com', 'comunalcarrascal@gmail.com', '5470525', 16, 758, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'segundorgonzalezc@gmail.com', 'segundorgonzalezc@gmail.com', '5577360', 18, 779, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jotaoscar1916@yahoo.es', 'jotaoscar1916@yahoo.es', '5577426', 18, 779, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'senum18@yahoo.es', 'senum18@yahoo.es', '5743555', 18, 796, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aliplatac@gmail.com', 'aliplatac@gmail.com', '5795834', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alderecho@hotmail.com', 'alderecho@hotmail.com', '5825354', 20, 919, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'piarfat@gmail.com', 'piarfat@gmail.com', '5884258', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juceoru13@gmail.com', 'juceoru13@gmail.com', '5906589', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pascualguerrero38@gmail.com', 'pascualguerrero38@gmail.com', '6083179', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'oscar.molano236@gmail.com', 'oscar.molano236@gmail.com', '6254321', 6, 357, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlos1954londono@gmail.com', 'carlos1954londono@gmail.com', '6264638', 21, 951, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hoperadomari@gmail.com', 'hoperadomari@gmail.com', '6301599', 21, 949, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'adaman07@hotmail.com', 'adaman07@hotmail.com', '6314201', 21, 949, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'emirosaavedra@hotmail.com', 'emirosaavedra@hotmail.com', '6316164', 21, 954, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'xamantadiaz9@gmail.com', 'xamantadiaz9@gmail.com', '6531921', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'calveiro1969@gmail.com', 'calveiro1969@gmail.com', '6537032', 21, 974, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'euquico@gmail.com', 'euquico@gmail.com', '6754062', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gustavoramirezr1962@gmail.com', 'gustavoramirezr1962@gmail.com', '6773706', 4, 244, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgebettin@gmail.com', 'jorgebettin@gmail.com', '6810358', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'narvaezvergaragabriel@gmail.com', 'narvaezvergaragabriel@gmail.com', '6810478', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'linomurillo230955@gmail.com', 'linomurillo230955@gmail.com', '6816667', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tomas.barreto.rincon@gmail.com', 'tomas.barreto.rincon@gmail.com', '6818945', 19, 878, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'salvadorromerocoley@hotmail.com', 'salvadorromerocoley@hotmail.com', '6819085', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abogadolucianoramirez@gmail.com', 'abogadolucianoramirez@gmail.com', '6820004', 3, 150, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rafamarrugo@hotmail.com', 'rafamarrugo@hotmail.com', '6820791', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'robertoyances@hotmail.es', 'robertoyances@hotmail.es', '6872858', 8, 430, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'duqueedil@gmail.com', 'duqueedil@gmail.com', '6883138', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'felixbaza@gmail.com', 'felixbaza@gmail.com', '6894953', 1, 25, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eljprivera@gmail.com', 'eljprivera@gmail.com', '7186810', 4, 191, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alenfega@gmail.com', 'alenfega@gmail.com', '7215746', 24, 1005, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nesmar123@hotmail.com', 'nesmar123@hotmail.com', '7220571', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'virila2001@yahoo.es', 'virila2001@yahoo.es', '7221400', 24, 1013, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nicolasmaestre@yahoo.com', 'nicolasmaestre@yahoo.com', '7224754', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'adelfo1955@hotmail.com', 'adelfo1955@hotmail.com', '7230237', 24, 1018, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maurofabiansalazar@gmail.com', 'maurofabiansalazar@gmail.com', '7253321', 4, 264, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'franchescoparra@hotmail.com', 'franchescoparra@hotmail.com', '7309843', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'salomedavid25@hotmail.com', 'salomedavid25@hotmail.com', '7361683', 24, 1009, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'farfanjorge42@gmail.com', 'farfanjorge42@gmail.com', '7363845', 24, 1017, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amauryayazovelasquez@gmail.com', 'amauryayazovelasquez@gmail.com', '7381151', 8, 433, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jesushernandez2326@hotmail.com', 'jesushernandez2326@hotmail.com', '7381600', 8, 433, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hermeslara03@gmail.com', 'hermeslara03@gmail.com', '7450392', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'razon.derecho@gmail.com', 'razon.derecho@gmail.com', '7453399', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lemarz@gmail.com', 'lemarz@gmail.com', '7453524', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'robertolugo7@yahoo.com', 'robertolugo7@yahoo.com', '7471307', 8, 417, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carince27@hotmail.com', 'carince27@hotmail.com', '7473513', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joselenaro@gmail.com', 'joselenaro@gmail.com', '7490123', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'augustomisse72@gmail.com', 'augustomisse72@gmail.com', '7559102', 17, 763, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lorenzoperezgonzalez@hotmail.com', 'lorenzoperezgonzalez@hotmail.com', '7591515', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pedropertuz96@yahoo.es', 'pedropertuz96@yahoo.es', '7592814', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rhonald29@hotmail.com', 'rhonald29@hotmail.com', '7595463', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anovercrespo@gmail.com', 'anovercrespo@gmail.com', '7596891', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luiscastellararrieta26@gmail.com', 'luiscastellararrieta26@gmail.com', '7929478', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'acelcontracion@gimail.com', 'acelcontratacion@gmail.com', '8001037', 18, 793, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'esgohe@gmail.com', 'esgohe@gmail.com', '8028419', 1, 69, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Camilausuga0305@gmail.com', 'Camilausuga0305@gmail.com', '8075124', 1, 14, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hilariogonzalezteheran1@gmail.com', 'hilariogonzalezteheran1@gmail.com', '8172450', 1, 97, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanpatino2705@gmail.com', 'juanpatino2705@gmail.com', '8205264', 1, 67, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'widove27@hotmail.com', 'widove27@hotmail.com', '8323561', 1, 13, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alonrramirezp@gmail.com', 'alonrramirezp@gmail.com', '8359014', 35, 1149, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'waltovelez@gmail.com', 'waltovelez@gmail.com', '8403316', 1, 14, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zapatagil.ethelberto@gmail.com', 'zapatagil.ethelberto@gmail.com', '8425998', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hernangomez0456@gmail.com', 'hernangomez0456@gmail.com', '8470609', 25, 1021, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kasolpadyd@outlook.com', 'kasolpadyd@outlook.com', '8632596', 2, 142, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luruakhumana@gmail.com', 'luruakhumana@gmail.com', '8635797', 2, 132, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'adalbertyguerrero@hotmail.com', 'adalbertyguerrero@hotmail.com', '8675297', 2, 129, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aasga@hotmail.com', 'aasga@hotmail.com', '8680593', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zurdo.225@hotmail.com', 'zurdo.225@hotmail.com', '8685323', 13, 630, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'asopiten.ascopi@gmail.com', 'asopiten.ascopi@gmail.com', '8685427', 18, 835, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'oscarherreradelgado@hotmail.es', 'oscarherreradelgado@hotmail.es', '8707222', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dgiraldo_99@yahoo.vom', 'dgiraldo_99@yahoo.com', '8713759', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wildernavarroquintero@gmail.com', 'wildernavarroquintero@gmail.com', '8729274', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisescorcialeon2023@hotmail.com', 'luisescorcialeon2023@hotmail.com', '8742348', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fernandoferrer1956@gmail.com', 'fernandoferrer1956@gmail.com', '8750444', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arceco0719@hotmail.com', 'arceco0719@hotmail.com', '8753514', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejorestrepot@gmail.com', 'alejorestrepot@gmail.com', '8755472', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mauri71101@hotmail.com', 'mauri71101@hotmail.com', '8776256', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alfonsohu49@yahoo.com', 'alfonsohu49@yahoo.com', '9070385', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'omeralfonso@hotmail.es', 'omeralfonso@hotmail.es', '9077590', 3, 185, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'livdan0607@gmail.com', 'livdan0607@gmail.com', '9084334', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jeracero@gmail.com', 'jeracero@gmail.com', '9089933', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'uwaquijote@gmail.com', 'uwaquijote@gmail.com', '9094503', 16, 748, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vergara.maximo@hotmail.com', 'vergara.maximo@hotmail.com', '9137468', 3, 161, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dcr00123@gmail.com', 'dcr00123@gmail.com', '9139370', 13, 640, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'narciso.torresperez@gmail.com', 'narciso.torresperez@gmail.com', '9152765', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'javierfaciolince@hotmail.com', 'javierfaciolince@hotmail.com', '9262374', 3, 166, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Carlosmoralesfarelo14@gmail.com', 'Carlosmoralesfarelo14@gmail.com', '9271605', 13, 638, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisponce_1010@hotmail.com', 'luisponce_1010@hotmail.com', '9298605', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vpflorez21@hotmail.com', 'vpflorez21@hotmail.com', '9310204', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorozaga@yahoo.es', 'jorozaga@yahoo.es', '9313566', 1, 97, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'asoafroelroble5@gmail.com', 'asoafroelroble5@gmail.com', '9313876', 19, 868, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wilcame05864@gmail.com', 'wilcame05864@gmail.com', '9314088', 8, 417, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'olmosjoel@gmail.com', 'olmosjoel@gmail.com', '9431616', 24, 1003, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'omar.uscategui@gmail.com', 'omar.uscategui@gmail.com', '9514696', 4, 195, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bayonafdo@hotmail.com', 'bayonafdo@hotmail.com', '9519084', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'araquerincon@gmail.com', 'araquerincon@gmail.com', '9523732', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hcastro311@gmail.com', 'hcastro311@gmail.com', '9525311', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elfardaniloleal@gmail.com', 'elfardaniloleal@gmail.com', '9653244', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'frescosiempre1@gmail.com', 'frescosiempre1@gmail.com', '10002299', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wilsonpadilla55@hotmail.com', 'wilsonpadilla55@hotmail.com', '10088732', 7, 390, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dhocvel331@gmail.com', 'dhocvel331@gmail.com', '10101485', 28, 1074, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucho6219@yahoo.es', 'lucho6219@yahoo.es', '10106779', 21, 942, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jucdma@yahoo.com', 'jucdma@yahoo.com', '10109738', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'victorsuarez2030@gmail.com', 'victorsuarez2030@gmail.com', '10139756', 15, 715, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fernandogarciacaceres@gmail.com', 'fernandogarciacaceres@gmail.com', '10166186', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rafaeleduardobetancourtarias@gmail.com', 'rafaeleduardobetancourtarias@gmail.com', '10254745', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miguelangelsoto724@gmail.com', 'miguelangelsoto724@gmail.com', '10257346', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgegiraldo341@hotmail.com', 'jorgegiraldo341@hotmail.com', '10258678', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hugofnotificaciones@gmail.com', 'hugofnotificaciones@gmail.com', '10289753', 6, 359, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leonardoillera@gmail.com', 'leonardoillera@gmail.com', '10299202', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'haroldhruiz2020@gmail.com', 'haroldhruiz2020@gmail.com', '10325217', 6, 344, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'orlandorolon30@gmail.com', 'orlandorolon30@gmail.com', '10531020', 16, 762, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hugoh290silva@gmail.com', 'hugoh290silva@gmail.com', '10531139', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'roquerealpe79@gemail.com', 'roquerealpe79@gemail.com', '10531477', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'adalbertonarvaez77@gmail.com', 'adalbertonarvaez77@gmail.com', '10535446', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jairoe43@gmail.com', 'jairoe43@gmail.com', '10536815', 14, 642, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hugodario2@hotmail.com', 'hugodario2@hotmail.com', '10546998', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgeortizperez1010@gmail.com', 'jorgeortizperez1010@gmail.com', '10556900', 6, 367, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fjog6018@gmail.com', 'fjog6018@gmail.com', '10880550', 19, 881, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisalfonsoballe9@gmail.com', 'luisalfonsoballe9@gmail.com', '10897247', 8, 436, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Sammyflower1010@gmail.com', 'Sammyflower1010@gmail.com', '10932778', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'enriquenavarrodiaz@hotmail.com', 'enriquenavarrodiaz@hotmail.com', '10937256', 8, 421, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'germanlovi@hotmail.com', 'germanlovi@hotmail.com', '10995787', 1, 13, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Valentin_delabarrera@yahoo.es', 'Valentin_delabarrera@yahoo.es', '11170765', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fransuacolombia@hotmail.com', 'fransuacolombia@hotmail.com', '11228358', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lopezdelgadoomar@gmail.com', 'lopezdelgadoomar@gmail.com', '11341360', 9, 548, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edmurisan@gmail.com', 'edmurisan@gmail.com', '11374289', 22, 979, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edgkabezas@gmail.com', 'edgkabezas@gmail.com', '11428829', 9, 522, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'martinpalacios2011@gmail.com', 'martinpalacios2011@gmail.com', '11429846', 9, 451, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'largacha2181@outlook.com', 'largacha2181@outlook.com', '11620727', 11, 556, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joserutiliorivas1@gmail.com', 'joserutiliorivas1@gmail.com', '11636803', 11, 564, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juancquininez12@gmail.com', 'juancquininez12@gmail.com', '11705813', 11, 568, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rocome1@hotmail.com', 'rocome1@hotmail.com', '11787600', 11, 550, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'GIOMECO20@GMAIL.COM', 'GIOMECO20@GMAIL.COM', '11791100', 11, 550, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eugeniomosquerah11@gmail.com', 'eugeniomosquerah11@gmail.com', '11795121', 11, 550, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hemersonmayo1970@hotmail.com', 'hemersonmayo1970@hotmail.com', '11798856', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'triunfadorexitoso@outlook.com', 'triunfadorexitoso@outlook.com', '11801285', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'valenciajuridica@hotmail.com', 'valenciajuridica@hotmail.com', '11803346', 1, 43, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hectoremiliomosquera6@gmail.com', 'hectoremiliomosquera6@gmail.com', '11936369', 11, 559, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'orlanloji@gmail.com', 'orlanloji@gmail.com', '12112652', 12, 603, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rojassanchez.efren1@gmail.com', 'rojassanchez.efren1@gmail.com', '12132182', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fredyossa@gmail.com', 'fredyossa@gmail.com', '12138295', 21, 968, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'parraindio@hotmail.es', 'parraindio@hotmail.es', '12188485', 12, 1306, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'campopedrito2018@gmail.com', 'campopedrito2018@gmail.com', '12194561', 12, 581, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'abogadojpe@gmail.com', 'abogadojpe@gmail.com', '12206407', 12, 587, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'olmertovar@yahoo.com', 'olmertovar@yahoo.com', '12233322', 12, 600, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'JOLUPEDU82@GMAIL.COM', 'JOLUPEDU82@GMAIL.COM', '12504001', 7, 399, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anyijulianyjuliana03@gmail.com', 'anyijulianyjuliana03@gmail.com', '12523013', 7, 396, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mindiolareinaj7456@outlook.es', 'mindiolareinaj7456@outlook.es', '12534374', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'manuelpegonzalez@gmail.com', 'manuelpegonzalez@gmail.com', '12556441', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'enriquemanuel.jimenez@gmail.com', 'enriquemanuel.jimenez@gmail.com', '12581995', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'samflomar03@gmail.com', 'samflomar03@gmail.com', '12583841', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'martinroma22@hotmail.com', 'martinroma22@hotmail.com', '12602770', 13, 637, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leanderdejesus03@gmail.com', 'leanderdejesus03@gmail.com', '12636046', 8, 420, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bonethm20@gmail.com', 'bonethm20@gmail.com', '12722199', 7, 398, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'roymana8@gmail.com', 'roymana8@gmail.com', '12723255', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'reirodriguez57@gmail.com', 'reirodriguez57@gmail.com', '12723441', 13, 623, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alvarovalenciatenorio@gmail.com', 'alvarovalenciatenorio@gmail.com', '12915337', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'norbertodiazojeda@gmail.com', 'norbertodiazojeda@gmail.com', '12983729', 31, 1097, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alvarocordoba50@hotmail.com', 'alvarocordoba50@hotmail.com', '12984798', 1, 53, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maplocmv@gmail.com', 'maplocmv@gmail.com', '13063583', 14, 642, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lansaedu@gmail.com', 'lansaedu@gmail.com', '13071775', 14, 666, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'RSanabria@minvivienda.gov.co', 'RSanabria@minvivienda.gov.co', '13175885', 16, 739, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'salvadoralbarracinjauregui@gmail.com', 'salvadoralbarracinjauregui@gmail.com', '13243890', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, '1955acuario63@gmail.com', '1955acuario63@gmail.com', '13256278', 16, 762, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bolivariano356@gmail.com', 'bolivariano356@gmail.com', '13256299', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lsrozowilches@hotmail.com', 'lsrozowilches@hotmail.com', '13348094', 12, 607, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eduardocriado2024@outlook.com', 'eduardocriado2024@outlook.com', '13357344', 16, 732, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'torradover@gmail.com', 'torradover@gmail.com', '13361418', 16, 724, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hugolopez0480@hotmeil.com', 'hugolopez0480@hotmeil.com', '13379928', 16, 758, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'porritas1971@gmail.com', 'porritas1971@gmail.com', '13389039', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanlamusm@hotmail.com', 'juanlamusm@hotmail.com', '13443198', 18, 796, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'brujulaurbana@yahoo.es', 'brujulaurbana@yahoo.es', '13451268', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cadrazcoleonhernanmanuel@gmail.com', 'cadrazcoleonhernanmanuel@gmail.com', '13452910', 19, 879, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alrodantodoatento@gmail.com', 'alrodantodoatento@gmail.com', '13487906', 9, 543, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jbz19bar@gmail.com', 'jbz19bar@gmail.com', '13513032', 7, 405, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'willy.mxy79@hotmail.com', 'willy.mxy79@hotmail.com', '13715966', 32, 1102, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'heluar73@gmail.com', 'heluar73@gmail.com', '13817132', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'orlanduribes@gmail.com', 'orlanduribes@gmail.com', '13825783', 15, 716, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'efrancom@hotmail.com', 'efrancom@hotmail.com', '13833444', 18, 854, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jacintoiguaran@yahoo.es', 'jacintoiguaran@yahoo.es', '13844310', 18, 780, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jhonpolanco16@hotmail.com', 'jhonpolanco16@hotmail.com', '13854211', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'santoaga79@gmail.com', 'santoaga79@gmail.com', '13865386', 3, 1305, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jjrojasc13c@hotmail.com', 'jjrojasc13c@hotmail.com', '13875225', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'produccionesmia@hotmail.com', 'produccionesmia@hotmail.com', '13958361', 18, 821, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alcirore@hotmail.com', 'alcirore@hotmail.com', '14010292', 20, 901, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alfonsogutierrezr@hotmail.com', 'alfonsogutierrezr@hotmail.com', '14105664', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'homolano@hotmail.com', 'homolano@hotmail.com', '14139749', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'oscarya80@hotmail.com', 'oscarya80@hotmail.com', '14213219', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisarminvanegas@gmail.com', 'luisarminvanegas@gmail.com', '14222243', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'narcifull@gmail.com', 'narcifull@gmail.com', '14317290', 20, 907, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Zivan17@hotmail.com', 'Zivan17@hotmail.com', '14474857', 21, 950, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'filloel23@hotmail.com', 'filloel23@hotmail.com', '14568509', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'CarlosNaranjo1a@gmail.com', 'CarlosNaranjo1a@gmail.com', '14570842', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nixonbravo@hotmail.com', 'nixonbravo@hotmail.com', '14576041', 21, 950, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ediagro11@gmail.com', 'ediagro11@gmail.com', '14652586', 21, 954, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'albertpopo@hotmail.com', 'albertpopo@hotmail.com', '14795848', 21, 970, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ferlzc_8@hotmail.com', 'ferlzc_8@hotmail.com', '14881060', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'agon98@hotmail.com', 'agon98@hotmail.com', '14885115', 21, 942, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jairovelasco244@gmail.com', 'jairovelasco244@gmail.com', '14963863', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'argemioplaza@gmail.com', 'argemioplaza@gmail.com', '14970045', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rudafo@gmail.com', 'rudafo@gmail.com', '14986687', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'agonzaleza_2259@hotmail.com', 'agonzaleza_2259@hotmail.com', '15022005', 8, 417, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'j.g.hernandezllorente@gmail.com', 'j.g.hernandezllorente@gmail.com', '15030648', 2, 129, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'walbergu_66@hotmal.com', 'walbergu_66@hotmail.com', '15045247', 8, 427, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mateoescobar1950@gmail.com', 'mateoescobar1950@gmail.com', '15072197', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'felixhumbertoa@gmail.com', 'felixhumbertoa@gmail.com', '15208140', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'silugiba@hotmail.com', 'silugiba@hotmail.com', '15303427', 1, 35, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'melquin11776@gmail.com', 'melquin11776@gmail.com', '15329059', 8, 434, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hegio1111@gmail.com', 'hegio1111@gmail.com', '15438684', 1, 85, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hernanrua61@gmail.com', 'hernanrua61@gmail.com', '15508215', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alexander.algomas@gmail.com', 'alexander.algomas@gmail.com', '15510428', 1, 40, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hugoalonsogallegoacevedo@gmail.com', 'hugoalonsogallegoacevedo@gmail.com', '15529321', 1, 80, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'palajhon@gmail.com', 'palajhon@gmail.com', '15535772', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leduher22@gmail.com', 'leduher22@gmail.com', '15607836', 8, 434, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anbaga71@gmail.com', 'anbaga71@gmail.com', '15618407', 8, 429, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rmartiso@hotmail.com', 'rmartiso@hotmail.com', '15673095', 1, 109, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Franciscojavierpena1965@gmail.com', 'Franciscojavierpena1965@gmail.com', '15905661', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eduardopat@utp.edu.co', 'eduardopat@utp.edu.co', '16071911', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rahum80@hotmail.com', 'rahum80@hotmail.com', '16187185', 23, 984, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cachis.siempre.amigos@gmail.con', 'cachis.siempre.amigos@gmail.com', '16231667', 21, 946, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diegofdoesencial@gmail.com', 'diegofdoesencial@gmail.com', '16232872', 35, 1148, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miangel.1980@hotmail.com', 'miangel.1980@hotmail.com', '16289052', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andresvera51@gmail.com', 'andresvera51@gmail.com', '16289268', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ricardoquinteroe7@gmail.com', 'ricardoquinteroe7@gmail.com', '16353494', 3, 182, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rob_llen@hotmail.com', 'rob_llen@hotmail.com', '16456740', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yesid120779@hotmail.com', 'yesid120779@hotmail.com', '16459488', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pedro1valenciaramirez@hotmail.com', 'pedro1valenciaramirez@hotmail.com', '16473220', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'doninfame1@gmail.com', 'doninfame1@gmail.com', '16473671', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rolandorivasarboleda7@gmail.com', 'rolandorivasarboleda7@gmail.com', '16476967', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rodolfo1161@hotmail.com', 'rodolfo1161@hotmail.com', '16484240', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gustavorenteriajaramillo@gmail.com', 'gustavorenteriajaramillo@gmail.com', '16489052', 11, 556, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'henry2469@hotmail.com', 'henry2469@hotmail.com', '16496650', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'FUNDIPA04@GMAIL.COM', 'FUNDIPA04@GMAIL.COM', '16501254', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'krikeiman2020@gmail.com', 'krikeiman2020@gmail.com', '16503265', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Donaldmatamba@hotmail.com', 'Donaldmatamba@hotmail.com', '16507835', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hazkary@gmail.com', 'hazkary@gmail.com', '16510869', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jose1953raul@gmai.com', 'jose1953raul@gmail.com', '16605891', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'afroscols@hotmail.com', 'afroscols@hotmail.com', '16613110', 22, 977, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'viveelbarriocol@gmail.com', 'viveelbarriocol@gmail.com', '16629238', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'franklinlegro@gmail.com', 'franklinlegro@gmail.com', '16639993', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jhernanmunoz@gmail.com', 'jhernanmunoz@gmail.com', '16650244', 21, 950, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fernando.giraldo2410@gmail.com', 'fernando.giraldo2410@gmail.com', '16679678', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jarrison.martinezc@gmail.com', 'jarrison.martinezc@gmail.com', '16684987', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'serraniagua@gmail.com', 'serraniagua@gmail.com', '16690947', 21, 953, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wior65c@gmail.com', 'wior65c@gmail.com', '16692325', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'danielmconcejo@hotmail.com', 'danielmconcejo@hotmail.com', '16695937', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joteroca260664@hotmail.com', 'joteroca260664@hotmail.com', '16697647', 21, 962, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marolujan@yahoo.com', 'marolujan@yahoo.com', '16730338', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'javito196510@gmail.com', 'javito196510@gmail.com', '16739896', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gabrielgiron914@gmail.com', 'gabrielgiron914@gmail.com', '16750388', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'estebanjedc@hotmail.com', 'estebanjedc@hotmail.com', '16790282', 6, 376, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Jarbingomezpossu@gmail.com', 'Jarbingomezpossu@gmail.com', '16823354', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jrmorenoa11@gmail.com', 'jrmorenoa11@gmail.com', '16864414', 21, 947, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'manfreyo@hotmail.com', 'manfreyo@hotmail.com', '16890278', 21, 949, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'victorherney7@gmail.com', 'victorherney7@gmail.com', '16944570', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fvanegas21@gmail.com', 'fvanegas21@gmail.com', '17156925', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gvelandi@hotmail.com', 'gvelandi@hotmail.com', '17199322', 9, 437, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jama2306@gmail.com', 'jama2306@gmail.com', '17321783', 28, 1074, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'atilagalindo@gmail.com', 'atilagalindo@gmail.com', '17344810', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tidabe16@hotnail.com', 'tidabe16@hotnail.com', '17545574', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dojeda0781@gmail.com', 'dojeda0781@gmail.com', '17594219', 22, 979, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lerimar29@yahoo.com', 'lerimar29@yahoo.com', '17624610', 23, 984, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'felixmurcia1970@gmail.com', 'felixmurcia1970@gmail.com', '17645293', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rolis.huergo@gmail.com', 'rolis.huergo@gmail.com', '17652371', 23, 984, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'faibersotovargas@gmail.com', 'faibersotovargas@gmail.com', '17685188', 23, 987, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'daniloupel2020@gmail.com', 'daniloupel2020@gmail.com', '17829394', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jd0538489@gmail.com', 'jd0538489@gmail.com', '17900722', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dalgeryamithmendoza@gmail.com', 'dalgeryamithmendoza@gmail.com', '17901488', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgemallama@hotmail.com', 'jorgemallama@hotmail.com', '18112958', 31, 1094, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yil425@hotmail.com', 'yil425@hotmail.com', '18122785', 12, 587, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edwin.villota1979@gmail.com', 'edwin.villota1979@gmail.com', '18128171', 31, 1089, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arielrojastomedes@gmail.com', 'arielrojastomedes@gmail.com', '18261025', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alex.trujillo@gmail.com', 'alex.trujillo@gmail.com', '18400302', 32, 1102, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vadimircc@gmail.com', 'vadimircc@gmail.com', '18401212', 33, 1110, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juntanzaspetropresidente@gmail.com', 'juntanzaspetropresidente@gmail.com', '18492021', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'godimarache10@gmail.com', 'godimarache10@gmail.com', '18503919', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mezacoordenadasur@gmail.com', 'mezacoordenadasur@gmail.com', '18615223', 15, 716, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'goezsalcedoluisgregorio@gmail.com', 'goezsalcedoluisgregorio@gmail.com', '18857659', 19, 879, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'esperanza.p55@hotmail.com', 'esperanza.p55@hotmail.com', '18859066', 19, 868, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nederes76@gmail.com', 'nederes76@gmail.com', '18880146', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ariel.llain@gmail.com', 'ariel.llain@gmail.com', '18915756', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edinsonpeinadogarcia3@gmail.com', 'edinsonpeinadogarcia3@gmail.com', '18921709', 18, 839, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hermessuarezflorez@gmail.com', 'hermessuarezflorez@gmail.com', '18927433', 7, 383, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'asesoresenlopublico@gmail.com', 'asesoresenlopublico@gmail.com', '18969961', 7, 388, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luchoai13@hotmail.com', 'luchoai13@hotmail.com', '19002173', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'didierg1928@hotmail.com', 'didierg1928@hotmail.com', '19002845', 27, 1035, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edgarmontenegro1148@gmail.com', 'edgarmontenegro1148@gmail.com', '19060425', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'corsocial1998@gmail.com', 'corsocial1998@gmail.com', '19086359', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'francomoreu@gmail.com', 'francomoreu@gmail.com', '19088147', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alenumar@yahoo.es', 'alenumar@yahoo.es', '19104015', 9, 511, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cmlasso@hotmail.com', 'cmlasso@hotmail.com', '19118500', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucapec11@gmail.com', 'lucapec11@gmail.com', '19128030', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leofrank2007@gmail.com', 'leofrank2007@gmail.com', '19130685', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'helsar502@gmail.com', 'helsar502@gmail.com', '19130805', 6, 381, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'emprend2012@gmail.com', 'emprend2012@gmail.com', '19145364', 30, 1077, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'efrasilva@gmail.com', 'efrasilva@gmail.com', '19183802', 35, 1279, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pfernandg@gmail.com', 'pfernandg@gmail.com', '19186924', 9, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josegu06@hotmail.com', 'josegu06@hotmail.com', '19215693', 9, 548, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'magilbarna@gmail.com', 'magilbarna@gmail.com', '19218988', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lfg.ma.eco@gmail.com', 'lfg.ma.eco@gmail.com', '19269961', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gj.movil56@gmail.com', 'gj.movil56@gmail.com', '19277444', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ecos2012@gmail.com', 'ecos2012@gmail.com', '19287415', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'javigalvisval@hotmail.com', 'javigalvisval@hotmail.com', '19292565', 22, 978, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rabusco4000@yahoo.es', 'rabusco4000@yahoo.es', '19295439', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pachonjv@gmail.com', 'pachonjv@gmail.com', '19315865', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mecaospina55@gmail.com', 'mecaospina55@gmail.com', '19316747', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aalbertpaz707@gmail.com', 'aalbertpaz707@gmail.com', '19325603', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jesuslara75@hotmail.com', 'jesuslara75@hotmail.com', '19337889', 3, 166, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zabaletajose274@gmail.com', 'zabaletajose274@gmail.com', '19348470', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'profeguapo.guarinposada@gmail.com', 'profeguapo.guarinposada@gmail.com', '19349776', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'federagroariari@gmail.com', 'federagroariari@gmail.com', '19356106', 26, 1054, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, '55germanmoreno@gmail.com', '55germanmoreno@gmail.com', '19356650', 9, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sanchezfierrogentil@gmail.com', 'sanchezfierrogentil@gmail.com', '19387341', 12, 608, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gonchapa@gmail.com', 'gonchapa@gmail.com', '19396944', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nelsonj.garzonb@gmail.com', 'nelsonj.garzonb@gmail.com', '19399778', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marlobenjamingomez@gmail.com', 'marlobenjamingomez@gmail.com', '19409933', 9, 456, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josealcidesalbalaverde@gmail.com', 'josealcidesalbalaverde@gmail.com', '19430235', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'h.otalora.md@gmail.com', 'h.otalora.md@gmail.com', '19434155', 1, 53, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pedroleveau@gmail.com', 'pedroleveau@gmail.com', '19435172', 21, 945, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'concejaljosecuesta@gmail.com', 'concejaljosecuesta@gmail.com', '19456865', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'garciajose144@yahoo.es', 'garciajose144@yahoo.es', '19458336', 1, 53, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bustosbustoscesar@gmail.com', 'bustosbustoscesar@gmail.com', '19490025', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'willisger@yahoo.com', 'willisger@yahoo.com', '19494600', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alimis76@live.com', 'alimis76@live.com', '19517986', 13, 626, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pipelon_romero@hotmail.com', 'pipelon_romero@hotmail.com', '19580038', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaicastigre@hotmail.com', 'jaicastigre@hotmail.com', '19610585', 13, 618, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'delapenanuneza@gmail.com', 'delapenanuneza@gmail.com', '19774849', 3, 185, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ruth.1947@hotmail.com', 'ruth.1947@hotmail.com', '20610866', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzelbabeltranb@gmail.com', 'luzelbabeltranb@gmail.com', '20939968', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Adelamontoyarojas@gmail.com', 'Adelamontoyarojas@gmail.com', '21824082', 1, 61, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rojovillamaria@gmail.com', 'rojovillamaria@gmail.com', '21970253', 1, 86, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luz70salgar@gmail.com', 'luz70salgar@gmail.com', '21979096', 1, 88, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariaarangopaniagua2@gmail.com', 'mariaarangopaniagua2@gmail.com', '22201099', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'herediamery@yahoo.com.co', 'herediamery@yahoo.com.co', '22366773', 2, 127, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edith-hp2948@hotmail.com', 'edith-hp2948@hotmail.com', '22387502', 2, 127, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'denisa0296@hotmail.com', 'denisa0296@hotmail.com', '22426350', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'profeara@hotmail.com', 'profeara@hotmail.com', '22454291', 2, 127, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'astridcoronado@gmail.com', 'astridcoronado@gmail.com', '22455867', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nilda.rsz@hotmail.com', 'nilda.rsz@hotmail.com', '22537819', 2, 142, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lidacolpas@hotmail.com', 'lidacolpas@hotmail.com', '22545726', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yulietta78@gmail.com', 'yulietta78@gmail.com', '22645092', 8, 435, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nellyscordoba@hotmail.com', 'nellyscordoba@hotmail.com', '22697147', 2, 146, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rosaare244@gmail.com', 'rosaare244@gmail.com', '22819209', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'derineldaqui@gmail.com', 'derineldaqui@gmail.com', '22831294', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vitaliacallegomez@gmail.com', 'vitaliacallegomez@gmail.com', '23008756', 3, 185, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lummefu0621@gmail.com', 'lummefu0621@gmail.com', '23553242', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elcy62@hotmail.com', 'elcy62@hotmail.com', '23606359', 4, 229, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lustellaroa@hitmail.com', 'lustellaroa@hotmail.com', '23622533', 4, 231, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'terereinaf@gmail.com', 'terereinaf@gmail.com', '23690241', 4, 241, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cam1seb2@hotmail.com', 'cam1seb2@hotmail.com', '23694759', 4, 242, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'patriciamore1608@gmail.com', 'patriciamore1608@gmail.com', '23710160', 24, 1003, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'llangua@hotmail.com', 'llangua@hotmail.com', '23741795', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'caritoclaudia2018@gmail.com', 'caritoclaudia2018@gmail.com', '23764522', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'olgamariaperilla62@gmail.com', 'olgamariaperilla62@gmail.com', '24227336', 24, 1005, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'isabelecheverri@yahoo.com', 'isabelecheverri@yahoo.com', '24603707', 17, 765, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucellytatama@yahoo.com', 'lucellytatama@yahoo.com', '24999485', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'judithchaux@hotmail.com', 'judithchaux@hotmail.com', '25277681', 6, 374, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lilianavaldes850@gmail.com', 'lilianavaldes850@gmail.com', '25281190', 35, 1280, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'julianamercedesmayosanto@gmail.com', 'julianamercedesmayosanto@gmail.com', '26257067', 11, 550, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dhcolombia1812@gmail.com', 'dhcolombia1812@gmail.com', '26331215', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'albertiniavalenciategaiza@gmail.com', 'albertiniavalenciategaiza@gmail.com', '26338853', 11, 556, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'esperanzaamaris725@gmail.com', 'esperanzaamaris725@gmail.com', '26725480', 7, 385, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucas_19969@hotmail.com', 'lucas_19969@hotmail.com', '26914332', 2, 133, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'refealro@hotmail.com', 'refealro@hotmail.com', '26944879', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mayerliscamelo-80@hotmail.com', 'mayerliscamelo-80@hotmail.com', '26946172', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nellyruizd69@gmail.com', 'nellyruizd69@gmail.com', '26984363', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maylaojeda2015@gmail.com', 'maylaojeda2015@gmail.com', '26985635', 25, 1026, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arelisbrito2024@gmail.com', 'arelisbrito2024@gmail.com', '26988434', 25, 1026, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josefinafreile63@gmail.com', 'josefinafreile63@gmail.com', '26989450', 25, 1023, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ml.rojasceron@gmail.com', 'ml.rojasceron@gmail.com', '27090600', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luce2maria@gmail.com', 'luce2maria@gmail.com', '27123306', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rosalbariascose@hotmail.com', 'rosalbariascose@hotmail.com', '27185908', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anamariacuellarcubillos@gmail.com', 'anamariacuellarcubillos@gmail.com', '27353765', 31, 1096, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yosija2411@hotmail.com', 'yosija2411@hotmail.com', '28098855', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rosarioacevedo28@hotmail.com', 'rosarioacevedo28@hotmail.com', '28099461', 18, 844, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'margarita.carrenocanizares@gmail.com', 'margarita.carrenocanizares@gmail.com', '28238986', 18, 810, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lusupe09@gmail.com', 'lusupe09@gmail.com', '28311854', 18, 839, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'evapati0308@hotmail.com', 'evapati0308@hotmail.com', '28386578', 18, 846, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'evitariverach@hotmail.con', 'evitariverach@hotmail.com', '28946733', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'g.catalina42@yahoo.com', 'g.catalina42@yahoo.com', '29181911', 35, 1217, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maritza1468@hotmail.com', 'maritza1468@hotmail.com', '29185562', 21, 940, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mcgarzon518@gemail.com', 'mcgarzon518@gemail.com', '29417089', 6, 374, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'omairacorrales07@gmail.com', 'omairacorrales07@gmail.com', '29498570', 21, 949, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marlida90@hotmail.com', 'marlida90@hotmail.com', '29503125', 21, 949, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisagabyvergara@gmail.com', 'luisagabyvergara@gmail.com', '29678859', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dorita08@hotmail.es', 'dorita08@hotmail.es', '29741716', 21, 963, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rosaog30@gmail.com', 'rosaog30@gmail.com', '29807609', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yelmioro73@gmail.com', 'yelmioro73@gmail.com', '29940724', 35, 1217, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juliethvivas2014@gmail.com', 'juliethvivas2014@gmail.com', '29974207', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'visapersonalshopper@gmail.com', 'visapersonalshopper@gmail.com', '29974961', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yalilegarcia29@gmail.com', 'yalilegarcia29@gmail.com', '30232860', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'claudiaandreac472@gmail.com', 'claudiaandreac472@gmail.com', '30338255', 15, 716, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'emanosalvar22@gmail.com', 'emanosalvar22@gmail.com', '30396252', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nayibibeltran@gmail.com', 'nayibibeltran@gmail.com', '30509158', 12, 593, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'glagmontenegro@gmail.com', 'glagmontenegro@gmail.com', '30728626', 14, 642, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ilianlopez1969@hotmail.com', 'ilianlopez1969@hotmail.com', '30744250', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ignaciaasprillahurtado@gmail.com', 'ignaciaasprillahurtado@gmail.com', '30771032', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mirtoji@hotmail.com', 'mirtoji@hotmail.com', '30772965', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'olimpia.gomez.contadorapublica@gmail.com', 'olimpia.gomez.contadorapublica@gmail.com', '30773737', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jupiter2600603-@Hotmail.com', 'jupiter2600603-@Hotmail.com', '31263589', 21, 945, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kaladia1954@gmail.com', 'kaladia1954@gmail.com', '31266919', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lopezdevelez1@gmail.com', 'lopezdevelez1@gmail.com', '31267006', 21, 936, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cristoyeliana1957@gmail.com', 'cristoyeliana1957@gmail.com', '31289564', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gloriacomunal07@hotmail.com', 'gloriacomunal07@hotmail.com', '31296099', 21, 956, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'valenciapenagloriastella@gmail.com', 'valenciapenagloriastella@gmail.com', '31491088', 21, 976, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sjauxf@gmail.com', 'sjauxf@gmail.com', '31710167', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'irmosbe1955@gmail.com', 'irmosbe1955@gmail.com', '31834803', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lavinia14k@gmail.com', 'lavinia14k@gmail.com', '31862956', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lilianalenguaje.monteverdi@gmail.com', 'lilianaestupinan81@gmail.com', '31946614', 9, 532, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'patriciamendezgutierrez1@gmail.com', 'patriciamendezgutierrez1@gmail.com', '31979694', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lubisaraujo9@gmail.com', 'lubisaraujo9@gmail.com', '32272860', 1, 15, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nubialopez2017123@gmail.com', 'nubialopez2017123@gmail.com', '32323478', 1, 14, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carmengomes1709@gmail.com', 'carmengomes1709@gmail.com', '32350846', 19, 878, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'b.bedoyaj@gmail.com', 'b.bedoyaj@gmail.com', '32533827', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'saraltamararias@yahoo.es', 'saraltamararias@yahoo.es', '32660636', 8, 409, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'merlhyberrio2730@gmail.com', 'merlhyberrio2730@gmail.com', '32692734', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yusdi_sc13@hotmail.com', 'yusdi_sc13@hotmail.com', '32751114', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yamicarrillo1029@gmail.com', 'yamicarrillo1029@gmail.com', '32776775', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ricardoyronaldo@hotmail.com', 'ricardoyronaldo@hotmail.com', '32877338', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aliesguinard@gmail.com', 'aliesguinard@gmail.com', '33151909', 35, 1176, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'isaarrietamartinez@gmail.com', 'isaarrietamartinez@gmail.com', '33197341', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dimilocy1985@gmail.com', 'dimilocy1985@gmail.com', '34326544', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fundifamilia@hotmail.com', 'fundifamilia@hotmail.com', '34511926', 6, 367, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jesosiluis06@gmail.com', 'jesosiluis06@gmail.com', '34534983', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cristiandaniel007@hotmail.com', 'cristiandaniel007@hotmail.com', '34547755', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucerocerquera59@gmail.com', 'lucerocerquera59@gmail.com', '34551072', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jacquelinechacon91@gmail.com', 'jacquelinechacon91@gmail.com', '34554405', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sandra-vanessa34@hotmail.com', 'sandra-vanessa34@hotmail.com', '34562297', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sotanarosa53@yahoo.com', 'sotanarosa53@yahoo.com', '34592113', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'orgenivierabetancourth@gmail.com', 'orgenivierabetancourth@gmail.com', '34603859', 6, 348, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'paulaenriquez8@gmail.com', 'paulaenriquez8@gmail.com', '34607242', 6, 381, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jcecilia1575@gmail.com', 'jcecilia1575@gmail.com', '34610677', 21, 956, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'licenciadasilviayazo@gmail.com', 'licenciadasilviayazo@gmail.com', '34984033', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejandrinapacheco91@gmail.com', 'alejandrinapacheco91@gmail.com', '34992284', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yamileolartepava@gmail.com', 'yamileolartepava@gmail.com', '35252307', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lefabiolaro@gmail.com', 'lefabiolaro@gmail.com', '35375753', 9, 446, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ybeltrangarzon@gmail.com', 'ybeltrangarzon@gmail.com', '35416651', 9, 548, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gazajuanes@gmail.com', 'gazajuanes@gmail.com', '35510705', 9, 442, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elizabethpimentelperdomo@gmail.com', 'elizabethpimentelperdomo@gmail.com', '35511626', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gallagoalba@gmail.com', 'albagallegoq4@gmail.com', '35522454', 9, 451, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'beslonpa05@hotmail.com', 'beslonpa05@hotmail.com', '35602706', 11, 551, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nopapo23@gmail.com', 'nopapo23@gmail.com', '35892477', 11, 550, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camaycarpi@gmail.com', 'camaycarpi@gmail.com', '36158205', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'darlitelco@hotmail.com', 'darlitelco@hotmail.com', '36640237', 13, 627, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'guerramoni7@gmail.com', 'guerramoni7@gmail.com', '36759736', 14, 642, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'belencc1969@gmail.com', 'belencc1969@gmail.com', '37179168', 16, 762, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'caceresjuliet342@gmail.com', 'caceresjuliet342@gmail.com', '37179618', 16, 741, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rosangelacorredor@gmail.com', 'rosangelacorredor@gmail.com', '37506503', 16, 729, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zaraymanuela_29@hotmail.com', 'zaraymanuela_29@hotmail.com', '37550106', 18, 810, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'naydu102014@gmail.com', 'naydu102014@gmail.com', '37616038', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'erikpassosl@hotmail.com', 'erikpassosl@hotmail.com', '37713959', 18, 839, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gladyscelisrueda@gmail.com', 'gladyscelisrueda@gmail.com', '37895974', 18, 844, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jsscali2010@gmail.com', 'jsscali2010@gmail.com', '38228702', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'emna2028@hotmail.com', 'emna2028@hotmail.com', '38469590', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'karolinalenis@yahoo.com', 'karolinalenis@yahoo.com', '38556139', 14, 642, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'isabel.zuleta@senado.gov.co', 'isabel.zuleta@senado.gov.co', '38790547', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dollyasesorias@hotmail.com', 'dollyasesorias@hotmail.com', '38940648', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nohoraospi23@gmail.com', 'nohoraospi23@gmail.com', '39008098', 35, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'asimanca04@yahoo.es', 'asimanca04@yahoo.es', '39011452', 13, 623, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'afcasa2011@gmail.com', 'afcasa2011@gmail.com', '39354141', 2, 137, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'inghelizanagz@gmail.com', 'inghelizanagz@gmail.com', '39461606', 7, 403, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mirellaludy@hotmail.es', 'mirellaludy@hotmail.es', '39529370', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Magyate2024@gmail.com', 'Magyate2024@gmail.com', '39569260', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanitacamargog@gmail.com', 'juanitacamargog@gmail.com', '39617076', 9, 456, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'karina@orjuela.co', 'karina@orjuela.co', '39628816', 9, 456, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'malutete1961@gmail.com', 'malutete1961@gmail.com', '39632071', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'losiramsas@gmail.com', 'losiramsas@gmail.com', '39645174', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'livitm104@gmail.com', 'livitm104@gmail.com', '39662584', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mafe-florez@hotmail.com', 'mafe-florez@hotmail.com', '39818823', 9, 520, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gaavilamotta@gmail.com', 'gaavilamotta@gmail.com', '40017766', 4, 247, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ruthbeatrizvargas@gmail.com', 'ruthbeatrizvargas@gmail.com', '40018649', 4, 191, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'garefe3@gmail.com', 'garefe3@gmail.com', '40031839', 4, 303, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yudyrocha466@gmail.com', 'yudyrocha466@gmail.com', '40325832', 24, 1009, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucyquinones860@gmail.com', 'lucyquinones860@gmail.com', '40373621', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ilbafedemeta@yahoo.es', 'ilbafedemeta@yahoo.es', '40382838', 26, 1054, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'deissyquinoneslozano@gmail.com', 'deissyquinoneslozano@gmail.com', '40388930', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pitalitohumana@gmail.com', 'pitalitohumana@gmail.com', '40392164', 12, 600, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yorni.78@hotmail.com', 'yorni.78@hotmail.com', '40626607', 23, 986, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariat_gonzalezcaicedo@hotmail.com', 'mariat_gonzalezcaicedo@hotmail.com', '40725966', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yundlah@gmail.com', 'yundlah@gmail.com', '40784528', 23, 996, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzmaigbr@gmail.com', 'luzmaigbr@gmail.com', '40793660', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leticampos6724@gmail.com', 'leticampos6724@gmail.com', '40916923', 7, 386, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'brievaingris@gmail.com', 'brievaingris@gmail.com', '40923839', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vargasdorafanny@gmail.com', 'vargasdorafanny@gmail.com', '40927617', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'iguaranluisana611@gmail.com', 'iguaranluisana611@gmail.com', '40944408', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lendysdm@hotmail.com', 'lendysdm@hotmail.com', '40981275', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'culchactello.mariamarleny@gmail.com', 'culchactello.mariamarleny@gmail.com', '41117178', 31, 1100, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'itaca500@gmail.com', 'itaca500@gmail.com', '41492951', 35, 1233, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'asesoriaplena@gmail.com', 'asesoriaplena@gmail.com', '41631612', 18, 779, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zera10@hotmail.com', 'zera10@hotmail.com', '41665076', 35, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gladysrojas20000@hotmail.com', 'gladysrojas20000@hotmail.com', '41669452', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anateresabernal@yahoo.es', 'anateresabernal@yahoo.es', '41688001', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariaisabelvillate@gmail.com', 'mariaisabelvillate@gmail.com', '41719901', 9, 442, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariaisaurah79@gmail.com', 'mariaisaurah79@gmail.com', '41768614', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sasamar3@gmail.com', 'sasamar3@gmail.com', '41782450', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'soamca1960@gmail.com', 'soamca1960@gmail.com', '42062499', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'colrda200@hotmail.com', 'colrda200@hotmail.com', '42086010', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'paulitaayala30@gmail.com', 'paulitaayala30@gmail.com', '42129543', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elaine79canal@gmail.com', 'elaine79canal@gmail.com', '42132179', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rodriroorosa123@gmail.com', 'rodriroorosa123@gmail.com', '42207169', 19, 864, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'obduliadoriacaro@gmail.com', 'obduliadoriacaro@gmail.com', '42494807', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'larroyaveabogada@hotmail.com', 'larroyaveabogada@hotmail.com', '42785432', 1, 123, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'stella.restrepo.osorio62@gmail.com', 'stella.restrepo.osorio62@gmail.com', '42821694', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amparocastro51@gmail.com', 'amparocastro51@gmail.com', '42877402', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'inmaculadarodriguezusma@gmail.com', 'inmaculadarodriguezusma@gmail.com', '42964542', 1, 64, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mvrasaj@hotmail.com', 'mvrasaj@hotmail.com', '43072752', 1, 48, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amhurtadobernal64@gmail.com', 'amhurtadobernal64@gmail.com', '43080986', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cpetrodoria5@gmail.com', 'cpetrodoria5@gmail.com', '43145152', 1, 13, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marcela.saldavi@gmail.com', 'marcela.saldavi@gmail.com', '43188161', 1, 78, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'field.icefly@gmail.com', 'field.icefly@gmail.com', '43280276', 1, 108, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'adrarias@gmail.com', 'adrarias@gmail.com', '43435528', 1, 53, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'enorisserna@yahoo.es', 'enorisserna@yahoo.es', '43444200', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'doralba65@gmail.com', 'doralba65@gmail.com', '43549004', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anetvivy@gmail.com', 'anetvivy@gmail.com', '43568064', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nancymilenas@gmail.com', 'nancymilenas@gmail.com', '43595946', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sorayarivas43@gmail.com', 'sorayarivas43@gmail.com', '43692783', 1, 45, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mayito131901@gmail.com', 'mayito131901@gmail.com', '43693751', 1, 45, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yolylopezh071975@outlook.es', 'yolylopezh071975@outlook.es', '43818133', 8, 409, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gloriaelo.2007@yahoo.es', 'gloriaelo.2007@yahoo.es', '43831817', 1, 42, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luni_11@hotmail.com', 'luni_11@hotmail.com', '45429011', 8, 415, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'canachury@gmail.com', 'canachury@gmail.com', '45448391', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gilsantamaria26@gmail.com', 'gilsantamaria26@gmail.com', '45462434', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jac18deenero@yahoo.com', 'jac18deenero@yahoo.com', '45464033', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dione.astudillo.orozco@gmail.com', 'dione.astudillo.orozco@gmail.com', '45476552', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'claudiamezavillalba34@gmail.com', 'claudiamezavillalba34@gmail.com', '45484172', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'isabel.gomezmedina13@gmail.com', 'isabel.gomezmedina13@gmail.com', '45577028', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'LUMSILGADO@GMAIL.COM', 'LUMSILGADO@GMAIL.COM', '45693712', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'azucena301268@gmail.com', 'azucena301268@gmail.com', '46363720', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dianaruiz80@hotmail.com', 'dianaruiz80@hotmail.com', '46452047', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ianapao@gmail.com', 'ianapao85@outlook.com', '46457377', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marlen196506@gmail.com', 'marlen196506@gmail.com', '46664244', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alvarezdexy47@gmail.com', 'alvarezdexy47@gmail.com', '49552581', 2, 127, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jujugay@hotmail.com', 'jujugay@hotmail.com', '49655647', 7, 383, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lolinamartinez2016@gmail.com', 'lolinamartinez2016@gmail.com', '49729636', 7, 390, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzstellarojashinojosa@gmail.com', 'luzstellarojashinojosa@gmail.com', '49744546', 7, 386, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edilsacharris@gmail.com', 'edilsacharris@gmail.com', '49744849', 25, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bettymejiagamarra988@gmail.com', 'bettymejiagamarra988@gmail.com', '49746809', 7, 392, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariage.stilo@gmail.com', 'mariage.stilo@gmail.com', '49757114', 7, 383, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nerytorrenegra@gmail.com', 'nerytorrenegra@gmail.com', '49762687', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anagerardino48@gmail.com', 'anagerardino48@gmail.com', '49768852', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'osmanyvanessa@gmail.com', 'osmanyvanessa@gmail.com', '50908999', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anamilenaacevedogomez39@gmail.com', 'anamilenaacevedogomez39@gmail.com', '50957971', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariaemmaramirezrodriguez20@gmail.com', 'mariaemmaramirezrodriguez20@gmail.com', '51561898', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'marinareyesvivas@gmail.com', 'marinareyesvivas@gmail.com', '51571730', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nohorasuarezcuellar8@gmail.com', 'nohorasuarezcuellar8@gmail.com', '51574032', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elsaparra623@gmail.com', 'elsaparra623@gmail.com', '51576005', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gloriastellamorenof2017@gmail.com', 'gloriastellamorenof2017@gmail.com', '51590081', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elizblanc128@gmail.com', 'elizblanc128@gmail.com', '51638804', 9, 445, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diana.penarete@gmail.com', 'diana.penarete@gmail.com', '51682884', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'guerraortizluzyamile@gmail.com', 'guerraortizluzyamile@gmail.com', '51717039', 9, 477, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luznmona@hotmail.com', 'luznmona@hotmail.com', '51724248', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'blankines06@gmail.com', 'blankines06@gmail.com', '51735022', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'negretmonica@hotmail.com', 'negretmonica@hotmail.com', '51766280', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mvictoriacordoba@gmail.com', 'mvictoriacordoba@gmail.com', '51782012', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fajoal65@gmail.com', 'fajoal65@gmail.com', '51784999', 9, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Janethzabaleta@gmail.com', 'Janethzabaleta@gmail.com', '51789099', 9, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'odiliamoncadaariza@gmail.com', 'odiliamoncadaariza@gmail.com', '51819649', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'martha.castellanos@gmail.com', 'martha.castellanos@gmail.com', '51839772', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'notoma2000@yahoo.com', 'notoma2000@yahoo.com', '51846572', 21, 937, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abogdamparo@gmail.com', 'abogdamparo@gmail.com', '51854050', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'blanca.ballen03@gmail.com', 'blanca.ballen03@gmail.com', '51892262', 18, 841, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hadagoju@gmail.com', 'hadagoju@gmail.com', '51901977', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'daditis12@gmail.com', 'daditis12@gmail.com', '51903291', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nechy.montana@gmail.com', 'nechy.montana@gmail.com', '51905849', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'albanidiacortes77@gmail.com', 'albanidiacortes77@gmail.com', '51915047', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rodriguezmorenonubia@gmail.com', 'rodriguezmorenonubia@gmail.com', '51977370', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lilimonf@gmail.com', 'lilimonf@gmail.com', '51978238', 20, 913, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'delitamari@gmail.com', 'delitamari@gmail.com', '51988627', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'neramirezortiz@gmail.com', 'neramirezortiz@gmail.com', '51992756', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sandrajubelly@gmail.com', 'sandrajubelly@gmail.com', '52008464', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'utopia19col@hotmail.com', 'utopia19col@hotmail.com', '52036260', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Luzstelladiaz2909@gmail.com', 'Luzstelladiaz2909@gmail.com', '52076996', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mastegari1129@hotmail.com', 'mastegari1129@hotmail.com', '52108110', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'claragcabrales@gmail.com', 'claragcabrales@gmail.com', '52115218', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, '52myriam@gmail.com', '52myriam@gmail.com', '52161159', 26, 1044, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'myriamstellapg@hotmail.com', 'myriamstellapg@hotmail.com', '52170545', 35, 1160, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zaidani@gmail.com', 'zaidani@gmail.com', '52231115', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzmaritzaparra2016@gmail.com', 'luzmaritzaparra2016@gmail.com', '52282729', 12, 605, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yenlygalindoj@gmail.com', 'yenlygalindoj@gmail.com', '52289235', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'adeportesayd@gmail.com', 'adeportesayd@gmail.com', '52295717', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'confisistemas@gmail.com', 'confisistemas@gmail.com', '52311774', 24, 1009, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'd.aldana@hotmail.com', 'd.aldana@hotmail.com', '52319815', 9, 447, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camacholuzdary40@gmail.com', 'camacholuzdary40@gmail.com', '52339011', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hermosacharlotte@gmail.com', 'hermosacharlotte@gmail.com', '52344276', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'catherinecastellanos@gmail.com', 'catherinecastellanos@gmail.com', '52381309', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'moninaarp@gmail.com', 'moninaarp@gmail.com', '52409453', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anniemaya7@gmail.com', 'anniemaya7@gmail.com', '52515077', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'redlideresasdecolombia@gmail.com', 'redlideresasdecolombia@gmail.com', '52705609', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisafhergc@gmail.com', 'luisafhergc@gmail.com', '52728315', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'johannaangel179@gmail.com', 'johannaangel179@gmail.com', '52741825', 4, 235, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'martharengifomontealegre@gmail.com', 'martharengifomontealegre@gmail.com', '52778547', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carcovar@gmail.com', 'carcovar@gmail.com', '52849517', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'julika.sanchez09@gmail.com', 'julika.sanchez09@gmail.com', '52914839', 18, 841, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ingrid.juliana.pabon@gmail.com', 'ingrid.juliana.pabon@gmail.com', '52951847', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'trabajosocialcomunitario2014@gmail.com', 'trabajosocialcomunitario2014@gmail.com', '52960241', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'julianamariarodriguez@gmail.com', 'julianamariarodriguez@gmail.com', '53000622', 9, 548, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lorenaurrea2023@gmail.com', 'lorenaurrea2023@gmail.com', '53003679', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mujer.productiva.imparable@gmail.com', 'mujer.productiva.imparable@gmail.com', '53040064', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'karenjb1984@gmail.com', 'karenjb1984@gmail.com', '53053495', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dianampayan21@gmail.com', 'dianampayan21@gmail.com', '53129421', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'taticaamado7@gmail.com', 'taticaamado7@gmail.com', '53907390', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ecastiblanco31@gmail.com', 'ecastiblanco31@gmail.com', '53911686', 9, 442, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'espinosagema14@gmail.com', 'espinosagema14@gmail.com', '55157552', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elisych30@gmail.com', 'elisych30@gmail.com', '56059132', 7, 392, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fabiola.me@hotmail.com', 'fabiola.me@hotmail.com', '57421123', 13, 618, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pablahidalgo05@gmail.com', 'pablahidalgo05@gmail.com', '57425241', 7, 396, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'medelcae.43@gmail.com', 'medelcae.43@gmail.com', '59665207', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'glopiduri2006@hotmail.com', 'glopiduri2006@hotmail.com', '60252097', 16, 748, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tamaramongui7@gmail.com', 'tamaramongui7@gmail.com', '60253283', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzstellacontrerassanchez@gmail.com', 'luzstellacontrerassanchez@gmail.com', '60292926', 16, 762, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edelramirez06@gmail.com', 'edelramirez06@gmail.com', '60348070', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'blancaacevedo352@gmail.com', 'blancaacevedo352@gmail.com', '60405697', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'santodomigogeraldina@gmail.com', 'santodomigogeraldina@gmail.com', '63288063', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nellybenua@hotmail.com', 'nellybenua@hotmail.com', '63301454', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gilyova@hotmail.com', 'gilyova@hotmail.com', '63303157', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hermanita.0216@gmail.com', 'hermanita.0216@gmail.com', '63326547', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'LIBADI50@GMAIL.COM', 'LIBADI50@GMAIL.COM', '63346386', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'chrispam70@gmail.com', 'chrispam70@gmail.com', '63352088', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'consultora.aponte.claudia@gmail.com', 'consultora.aponte.claudia@gmail.com', '63357302', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mildrethsuarezdiaz@gmail.com', 'mildrethsuarezdiaz@gmail.com', '63366262', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'annapatino541@gmail.com', 'annapatino541@gmail.com', '63439533', 18, 835, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fatima401971@hotmail.com', 'fatima401971@hotmail.com', '63460127', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'malenajai@gmail.com', 'malenajai@gmail.com', '63511544', 18, 810, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amiramoma@hotmail.com', 'amiramoma@hotmail.com', '64515584', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vivigarpadi@gmail.com', 'vivigarpadi@gmail.com', '64552033', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marthaferia490@gmail.com', 'marthaferia490@gmail.com', '64564152', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'chamorrodiana954@gmail.com', 'chamorrodiana954@gmail.com', '64696954', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Mayosuaresgamarra@gmail.com', 'Mayosuaresgamarra@gmail.com', '64717678', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anasotelo784@gmail.com', 'anasotelo784@gmail.com', '64721991', 19, 878, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariadelrosarioborja@gmail.com', 'mariadelrosarioborja@gmail.com', '65740043', 20, 924, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diajor1969@hotmail.com', 'diajor1969@hotmail.com', '65743853', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carolinabed9@gmail.com', 'carolinabed9@gmail.com', '65779526', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nubiagallo07@gmail.com', 'nubiagallo07@gmail.com', '66679329', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariaoque1566@gmail.com', 'mariaoque1566@gmail.com', '66710798', 21, 970, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'toteolmedo@gmail.com', 'toteolmedo@gmail.com', '66733955', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'clauposs1973@gmail.com', 'clauposs1973@gmail.com', '66751845', 21, 959, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'assiluycoquito@gmail.com', 'assiluycoquito@gmail.com', '66777328', 21, 942, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'idesmarinaramirez48@gmail.com', 'idesmarinaramirez48@gmail.com', '66831384', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'uribe1937@hotmail.com', 'uribe1937@hotmail.com', '66837798', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'martha.gonzalez.240605@gmail.com', 'martha.gonzalez.240605@gmail.com', '66838314', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzmarym2471@gmail.com', 'luzmarym2471@gmail.com', '66850644', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'buga.federacion21@gmail.com', 'buga.federacion21@gmail.com', '66862430', 21, 942, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzenith714@gmail.com', 'luzenith714@gmail.com', '66871457', 21, 940, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'cordobajohac@gmail.com', 'cordobajohac@gmail.com', '66873726', 21, 965, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'p.adrymorales28@gmail.com', 'p.adrymorales28@gmail.com', '66905706', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'katherinemejia415@gmail.com', 'katherinemejia415@gmail.com', '66914672', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'proyectomjrl@gmail.com', 'proyectomjrl@gmail.com', '66925219', 21, 959, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Sugeymaria12nela@gmail.com', 'Sugeymaria12nela@gmail.com', '66940270', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lilibaro15@gmail.com', 'lilibaro15@gmail.com', '66983716', 21, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ubaldinaguilar82@gmail.com', 'ubaldinaguilar82@gmail.com', '68306256', 22, 981, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nubiaenriquez1977@gmail.com', 'nubiaenriquez1977@gmail.com', '69007315', 31, 1097, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'monicavaron81@gmail.com', 'monicavaron81@gmail.com', '69029449', 31, 1089, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Saravedo70@gmail.com', 'Saravedo70@gmail.com', '70030310', 1, 14, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgemejiama@gmail.com', 'jorgemejiama@gmail.com', '70037431', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kbedoyac@yahoo.es', 'kbedoyac@yahoo.es', '70087401', 1, 117, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josebarrios910@hotmail.com', 'josebarrios910@hotmail.com', '70111282', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juancato1962@gmail.com', 'juancato1962@gmail.com', '70133556', 1, 18, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jjaimeposadagomez@gmail.com', 'jjaimeposadagomez@gmail.com', '70163005', 1, 90, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ferdorozco@gmail.com', 'ferdorozco@gmail.com', '70500823', 1, 52, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'quirozqalonso@gmail.com', 'quirozqalonso@gmail.com', '70502009', 1, 52, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elmer_obregon01@hotmail.com', 'elmer_obregon01@hotmail.com', '70523204', 1, 15, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fabergarcia2802@gmail.com', 'fabergarcia2802@gmail.com', '70728896', 1, 64, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaimemongo1@gmail.com', 'jaimemongo1@gmail.com', '71082777', 1, 105, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'movimientoriosvivoscolombia@gmail.com', 'movimientoriosvivoscolombia@gmail.com', '71375811', 1, 112, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'danilosuarezg@gmail.com', 'danilosuarezg@gmail.com', '71385374', 1, 15, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'osbaylopez@gmail.com', 'osbaylopez@gmail.com', '71411247', 1, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gabrieljaime3175@gmail.com', 'gabrieljaime3175@gmail.com', '71578658', 1, 34, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'iocampo473@hotmail.com', 'iocampo473@hotmail.com', '71606473', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'njra2@yahoo.es', 'njra2@yahoo.es', '71647903', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ghrcontador@gmail.com', 'ghrcontador@gmail.com', '71694602', 8, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'danielgaleanorojas@gmail.com', 'danielgaleanorojas@gmail.com', '71699269', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'conra2car@yahoo.com', 'conra2car@yahoo.com', '71704588', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'davidramirezmontoya@gmail.com', 'davidramirezmontoya@gmail.com', '71778075', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'info@jhonlemos.com', 'info@jhonlemos.com', '71782656', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Jotama85@gmail.com', 'Jotama85@gmail.com', '71801531', 1, 112, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dariolopezperez@gmail.com', 'dariolopezperez@gmail.com', '71944853', 33, 1109, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'manuelpenabogado@gmail.com', 'manuelpenabogado@gmail.com', '72012665', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cacs1227@gmail.com', 'cacs1227@gmail.com', '72018024', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'evertorregrosa@hotmail.com', 'evertorregrosa@hotmail.com', '72070698', 2, 132, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jrangulop@yahoo.es', 'jrangulop@yahoo.es', '72126847', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fredisdiaz@hotmail.com', 'fredisdiaz@hotmail.com', '72155590', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'algutil72@gmail.com', 'algutil72@gmail.com', '72196960', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'neyltorres12345@gmail.com', 'neyltorres12345@gmail.com', '72199522', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luismanuelmercado@yahoo.com', 'luismanuelmercado@yahoo.com', '72211989', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dustindonado@gmail.com', 'dustindonado@gmail.com', '72428085', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisedat@hotmail.com', 'luisedat@hotmail.com', '73091895', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pedroantonioortiz@yahoo.es', 'pedroantonioortiz@yahoo.es', '73094529', 8, 417, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carretaljmo@gmail.com', 'carretaljmo@gmail.com', '73106371', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marcosvecar@gmail.com', 'marcosvecar@gmail.com', '73121832', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gonimajosefe@gmail.com', 'gonimajosefe@gmail.com', '73126801', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'olick-6917@hotmail.com', 'olick-6917@hotmail.com', '73130669', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dominguez22omar22@gmail.com', 'lgbtiqamontesdemaria@gmail.com', '73139816', 3, 150, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arparo270@hotmail.com', 'arparo270@hotmail.com', '73141210', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisalbertobarriossuarez@hotmail.com', 'luisalbertobarriosuarez@hotmail.com', '73226301', 3, 177, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aramisrafaelpaez@gmail.com', 'aramisrafaelpaez@gmail.com', '73269062', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josehilariopadillas@gmail.com', 'josehilariopadillas@gmail.com', '73562322', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marrugopolojorge@gmail.com', 'marrugopolojorge@gmail.com', '73581233', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'martinroblesrodriguez56@gmail.com', 'martinroblesrodriguez56@gmail.com', '73593649', 7, 391, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'johnaza2009@gmail.com', 'johnaza2009@gmail.com', '74357335', 4, 206, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edwinsagliano79@gmail.com', 'edwinsagliano79@gmail.com', '74374042', 9, 518, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edwinfgomezsisa@gmail.com', 'edwinfgomezsisa@gmail.com', '74376360', 4, 309, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'krlos0327@gmail.com', 'krlos0327@gmail.com', '74849372', 24, 1008, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eivaryair2012@gmail.com', 'eivaryair2012@gmail.com', '74866517', 24, 1017, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rodolfocuellar353@gmail.com', 'rodolfocuellar353@gmail.com', '76271277', 6, 365, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlospazs@unicauca.edu.co', 'carlospazs@unicauca.edu.co', '76316597', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andremaiz@outlook.es', 'andremaiz@outlook.es', '76319863', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jemq0624@hotmail.com', 'jemq0624@hotmail.com', '76334035', 6, 380, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'borismoscote08@gmail.com', 'borismoscote08@gmail.com', '77039601', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'racs1970@gmail.com', 'racs1970@gmail.com', '77154205', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'negrosdelasierranevada@gmail.com', 'negrosdelasierranevada@gmail.com', '77173185', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luismparenilla@gmail.com', 'luismparenilla@gmail.com', '78108317', 8, 408, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jsaenzpadilla@hotmail.com', 'jsaenzpadilla@hotmail.com', '78290032', 8, 420, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yoms123@gmail.com', 'yoms123@gmail.com', '78381385', 8, 435, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miguelandreslopez7106@gmail.com', 'miguelandreslopez7106@gmail.com', '78675234', 8, 415, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dberriocogollo@gmail.com', 'dberriocogollo@gmail.com', '78696027', 8, 434, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eliasnaturismo2@gmail.com', 'eliasnaturismo2@gmail.com', '78698209', 1, 94, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'judicialhunter@hotmail.com', 'judicialhunter@hotmail.com', '78740096', 8, 427, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'apicoolivos05@gmail.com', 'apicoolivos05@gmail.com', '79104503', 1, 124, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlosmasso079@gmail.com', 'carlosmasso079@gmail.com', '79108181', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'germanviana1@hotmail.com', 'germanviana1@hotmail.com', '79113138', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'felixamin@gmail.com', 'felixamin@gmail.com', '79113973', 1, 13, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ramaclaro2@gmail.com', 'ramaclaro2@gmail.com', '79118950', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'albate2k@gmail.com', 'albate2k@gmail.com', '79150340', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'info_relpro@yahoo.com', 'info_relpro@yahoo.com', '79189275', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sara_paola_2011@outlook.com', 'sara_paola_2011@outlook.com', '79208861', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'catfelino01@gmail.com', 'catfelino01@gmail.com', '79242215', 9, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tangovida@gmail.com', 'tangovida@gmail.com', '79243657', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gerrota62@gmail.com', 'gerrota62@gmail.com', '79257976', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joalberth2009@gmail.com', 'joalberth2009@gmail.com', '79265493', 22, 978, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luferna2005@yahoo.es', 'luferna2005@yahoo.es', '79309882', 35, 1280, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kelmeallist@gmail.com', 'kelmeallist@gmail.com', '79328285', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'colombiahumanlajaguadeibirico@gmail.com', 'pyk_company@yahoo.es', '79341837', 7, 396, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'buenergesv@gmail.com', 'buenergesv@gmail.com', '79348466', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josedario037@gmail.com', 'josedario037@gmail.com', '79369813', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fercente65@gmail.com', 'fercente65@gmail.com', '79381036', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edgarroblesfonnegra@gmail.com', 'edgarroblesfonnegra@gmail.com', '79388409', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'orlandosilvafigueroa@hotmail.com', 'orlandosilvafigueroa@hotmail.com', '79419283', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'facevedosor@gmail.com', 'facevedosor@gmail.com', '79430615', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'canoreyesjuandejesus@gmail.com', 'juandejesuscanoreyes@hotmail.com', '79525852', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rafanturiospina@gmail.com', 'rafanturiospina@gmail.com', '79536005', 23, 987, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'willquer71@gmail.com', 'willquer71@gmail.com', '79540499', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sevencompany1a@hotmail.com', 'sevencompany1a@hotmail.com', '79544235', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hmoscar027@gmail.com', 'hmoscar027@gmail.com', '79546027', 24, 1013, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'jhonpe99@hotmail.com', 'jhonpe99@hotmail.com', '79573001', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'manuel.roab@gmail.com', 'manuel.roab@gmail.com', '79576515', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mauriciohernandez.ruiz@gmail.com', 'mauriciohernandez.ruiz@gmail.com', '79597810', 9, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'henrypandales@hotmail.com', 'henrypandales@hotmail.com', '79652761', 11, 550, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dr.productosyservicios@gmail.com', 'dr.productosyservicios@gmail.com', '79663442', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'acuariano762011@gmail.com', 'acuariano762011@gmail.com', '79741230', 9, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'davidinterventorias@gmail.com', 'davidinterventorias@gmail.com', '79746334', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pigoyed7@gmail.com', 'pigoyed7@gmail.com', '79768388', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'colectivoorvin@gmail.com', 'colectivoorvin@gmail.com', '79770112', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pazcienciaycultura@gmail.com', 'pazcienciaycultura@gmail.com', '79796613', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lasemilladelcambio@gmail.com', 'lasemilladelcambio@gmail.com', '79829137', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rodriguezlandazuriwilmer@gmail.com', 'rodriguezlandazuriwilmer@gmail.com', '79833998', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josegura23@hotmail.com', 'josegura23@hotmail.com', '79836012', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abraxasjota@hotmail.com', 'abraxasjota@hotmail.com', '79856276', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'creacionespopulares@gmail.com', 'creacionespopulares@gmail.com', '79890378', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'calarka@gmail.com', 'calarka@gmail.com', '79904194', 24, 1012, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jzabala2004@gmail.com', 'jzabala2004@gmail.com', '79907360', 20, 922, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'georgeluis0107@gmail.com', 'georgeluis0107@gmail.com', '79951910', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luiscastillo2772@gmail.com', 'luiscastillo2772@gmail.com', '79960151', 33, 1111, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nelsoncely1@gmail.com', 'nelsoncely1@gmail.com', '80016728', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jdojeda@concejobogota.gov.co', 'jdojeda@concejobogota.gov.co', '80084230', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ivan.buitrago@correounivalle.edu.co', 'ivan.buitrago@correounivalle.edu.co', '80092795', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'davidrojasb38@gmail.com', 'davidrojasb38@gmail.com', '80094741', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanfloriansilva@gmail.com', 'juanfloriansilva@gmail.com', '80097915', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'antonioiberlucea@gmail.com', 'antonioiberlucea@gmail.com', '80102823', 21, 967, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fredyvanegas22050@gmail.com', 'fredyvanegas22050@gmail.com', '80112013', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eduarabogados@gmail.com', 'eduarabogados@gmail.com', '80130551', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leonardomolinas@gmail.com', 'leonardomolinas@gmail.com', '80206568', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'moshereforma@gmail.com', 'moshereforma@gmail.com', '80214523', 9, 454, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 's0ydarwinco@gmail.com', 's0ydarwinco@gmail.com', '80223555', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'atodometal@gmail.com', 'atodometal@gmail.com', '80225277', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pazjulianj@gmail.com', 'pazjulianj@gmail.com', '80257057', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'goldhyn@hotmail.com', 'goldhyn@hotmail.com', '80396824', 9, 445, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juan.dh.ong@gmail.com', 'juan.dh.ong@gmail.com', '80433633', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'davortegon@gmail.com', 'davortegon@gmail.com', '80504511', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'daralsanco@hotmail.es', 'daralsanco@hotmail.es', '80550033', 9, 548, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'faguaivanch@gmail.com', 'faguaivanch@gmail.com', '80725205', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juliroz10@gmail.com', 'juliroz10@gmail.com', '80765198', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gerardo.olarte@hotmail.com', 'gerardo.olarte@hotmail.com', '80811405', 18, 815, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'armatom27@hotmail.com', 'armatom27@hotmail.com', '80820566', 35, 1172, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'acgesquivel@gmail.com', 'acgesquivel@gmail.com', '80871583', 9, 512, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yamiid.ramirez@gmail.com', 'yamiid.ramirez@gmail.com', '80896712', 20, 894, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bogotaconaltura@gmail.com', 'bogotaconaltura@gmail.com', '80903349', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'unadgro@gmail.com', 'unadgro@gmail.com', '80932082', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'francysalamandramartinez@gmail.com', 'francysalamandramartinez@gmail.com', '82140747', 11, 566, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gut82@live.com.mx', 'gut82@live.com.mx', '82382709', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alfonsomicro@hotmail.com', 'alfonsomicro@hotmail.com', '83087869', 12, 608, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'manuelg210@hotmail.com', 'manuelg210@hotmail.com', '83229640', 12, 603, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fredyrojas.fr@hotmail.com', 'fredyrojas.fr@hotmail.com', '83237476', 12, 615, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Carogamaliel845@gmail.com', 'Carogamaliel845@gmail.com', '83241992', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jose_1583@hotmail.com', 'jose_1583@hotmail.com', '84094146', 8, 420, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'claco100@gmail.com', 'claco100@gmail.com', '84096569', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'huberto1982@hotmail.com', 'huberto1982@hotmail.com', '84451133', 13, 617, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ronabric@gmail.com', 'ronabric@gmail.com', '84453599', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'heberto.2mil8@gmail.com', 'heberto.2mil8@gmail.com', '85436346', 13, 623, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edwingil69@yahoo.es', 'edwingil69@yahoo.es', '85436370', 7, 383, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gustavomartinezb2210@gmail.com', 'gustavomartinezb2210@gmail.com', '85464429', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Johnm66@hotmail.es', 'Johnm66@hotmail.es', '86060796', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jemejiac@unal.edu.co', 'jemejiac@unal.edu.co', '86071034', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jgv.asesorsalud@hotmail.com', 'jgv.asesorsalud@hotmail.com', '87190620', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'soportejuridicovirtual@gmail.com', 'soportejuridicovirtual@gmail.com', '87573461', 14, 642, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ujh008@gmail.com', 'ujh008@gmail.com', '88000931', 16, 736, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miguelrobertonavarro11@gmail.com', 'miguelrobertonavarro11@gmail.com', '88136658', 16, 732, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Fernelbayonaamaya@gmail.com', 'Fernelbayonaamaya@gmail.com', '88149848', 16, 753, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sancalixto15@hotmail.com', 'sancalixto15@hotmail.com', '88150140', 16, 753, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jesushernanbayonagarcia09@gmail.com', 'jesushernanbayonagarcia09@gmail.com', '88183763', 16, 747, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'john29godoy@gmail.com', 'john29godoy@gmail.com', '88202860', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'johnparra1712@gmail.com', 'johnparra1712@gmail.com', '88209486', 35, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wilson.sepulveda2022@gmail.com', 'wilson.sepulveda2022@gmail.com', '88276984', 18, 822, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luarpaca61@gmail.com', 'luarpaca61@gmail.com', '91011617', 23, 986, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'betoariasconcejo47@hotmail.com', 'betoariasconcejo47@hotmail.com', '91101838', 18, 844, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cutmaster1964@hotmail.com', 'cutmaster1964@hotmail.com', '91104073', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'augusto.corzo@hotmail.com', 'augusto.corzo@hotmail.com', '91104122', 18, 860, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pejari7@gmail.com', 'pejari7@gmail.com', '91156918', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mantillaxsantander@gmail.com', 'mantillaxsantander@gmail.com', '91186063', 18, 810, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanjaimes1059@gmail.com', 'juanjaimes1059@gmail.com', '91202158', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgebenitez1306@gmail.com', 'jorgebenitez1306@gmail.com', '91204998', 8, 413, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'armandoe8a@gmail.com', 'armandoe8a@gmail.com', '91210885', 18, 810, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'osedor@hotmail.com', 'osedor@hotmail.com', '91218755', 18, 830, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'antonioverdugopsuv@gmail.com', 'antonioverdugopsuv@gmail.com', '91226862', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'salvadoravila8787@gmail.com', 'salvadoravila8787@gmail.com', '91278996', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'colombiahumanasangil@gmail.com', 'colombiahumanasangil@gmail.com', '91284822', 18, 844, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juancarojas31@gmail.com', 'juancarojas31@gmail.com', '91299955', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bernalrafaelalberto218@gmail.com', 'bernalrafaelalberto218@gmail.com', '91360852', 18, 821, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bealga@gmail.com', 'bealga@gmail.com', '91420953', 4, 308, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jhonvergaraprada123@gmail.com', 'jhonvergaraprada123@gmail.com', '91426275', 3, 182, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joseluis.mezamafiol@gmail.com', 'joseluis.mezamafiol@gmail.com', '91429093', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fabiansandooval@gmail.com', 'fabiansandooval@gmail.com', '91519950', 18, 835, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanaureliogomezosorio19@gmail.com', 'juanaureliogomezosorio19@gmail.com', '91539781', 7, 386, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgeiriacu@hotmail.com', 'jorgeiriacu@hotmail.com', '92029315', 19, 884, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mangregor21@hotmail.com', 'mangregor21@hotmail.com', '92227650', 19, 886, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorge.1956@live.com', 'jorge.1956@live.com', '92255061', 19, 878, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hermidessuarez15@gmail.com', 'hermidessuarez15@gmail.com', '92257435', 19, 868, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aristalco.abogado@gmail.com', 'aristalco.abogado@gmail.com', '92275601', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jcma1709@gmail.com', 'jcma1709@gmail.com', '92496626', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'roqueruizchavez@gmail.com', 'roqueruizchavez@gmail.com', '92498618', 19, 881, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Wilmer003@gmail.com', 'Wilmer003@gmail.com', '92500924', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tecnonando_98@hotmail.com', 'tecnonando_98@hotmail.com', '92501118', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rembertobenitez@hotmail.com', 'rembertobenitez@hotmail.com', '92502681', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kikeir.64@gmail.com', 'kikeir.64@gmail.com', '92504295', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'asovictimasd@gmail.com', 'asovictimasd@gmail.com', '92512050', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Manuruiz1322@gmail.com', 'Manuruiz1322@gmail.com', '92520918', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'orafae2@gmail.com', 'orafae2@gmail.com', '92527006', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'osile2009@gmail.com', 'osile2009@gmail.com', '92549585', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fabjojies@gmail.com', 'fabjojies@gmail.com', '92558060', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dava-5@hotmail.com', 'dava-5@hotmail.com', '92559015', 19, 868, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hernando942@gmail.com', 'hernando942@gmail.com', '93035036', 20, 917, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ramirezjaviercali@gmail.com', 'ramirezjaviercali@gmail.com', '93080935', 20, 905, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorge3204579771@gmail.com', 'jorge3204579771@gmail.com', '93201051', 20, 922, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gs.colombia@yahoo.com.co', 'gs.colombia@yahoo.com.co', '93202395', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'finosyaromas@gmail.com', 'finosyaromas@gmail.com', '93295081', 12, 584, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ingdario16@gmail.com', 'ingdario16@gmail.com', '93412281', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marisca1468@gmail.com', 'maritza1468@gmail.com', '94153686', 21, 940, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Juanbiocafe@gmail.com', 'Juanbiocafe@gmail.com', '94262156', 21, 952, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anibal.acosta_2627@yahoo.es', 'anibal.acosta_2627@yahoo.es', '94294483', 21, 945, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josegregoriodiazgrajales4@gmail.com', 'josegregoriodiazgrajales4@gmail.com', '94351032', 21, 939, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'huberney62@gmail.com', 'huberney62@gmail.com', '94365722', 21, 940, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'millanrengifodiego@gmail.com', 'millanrengifodiego@gmail.com', '94387055', 21, 940, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andretunay@gmail.com', 'andretunay@gmail.com', '94387971', 21, 940, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaimealbertoariastrivino43@gmail.com', 'jaimealbertoariastrivino43@gmail.com', '94389721', 21, 965, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ederleyvargascalle@gmail.com', 'ederleyvargascalle@gmail.com', '94391784', 21, 943, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bolidosr648@gmail.com', 'bolidosr648@gmail.com', '94462805', 21, 944, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sanchezherley@gmail.com', 'sanchezherley@gmail.com', '94480933', 35, 1160, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alexgg12@gmail.com', 'alexgg12@gmail.com', '94519595', 21, 962, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abelardo7609@hotmail.com', 'abelardo7609@hotmail.com', '97446294', 31, 1091, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wilsonarteaga54@gmail.com', 'wilsonarteaga54@gmail.com', '98395205', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'haderalegria@hotmail.com', 'haderalegria@hotmail.com', '98431287', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanmasos21@gmail.com', 'juanmasos21@gmail.com', '98494216', 1, 14, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gabrieljaimeurrego03@gmail.com', 'gabrieljaimeurrego03@gmail.com', '98549502', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nautraldiortiz@gmail.com', 'nautraldiortiz@gmail.com', '98574919', 28, 1072, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joseflorezal810@gmail.com', 'joseflorezal810@gmail.com', '98654327', 1, 97, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanerzhy@gmail.com', 'juanerzhy@gmail.com', '98765912', 1, 48, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alexandertinez1@gmail.com', 'alexandertinez1@gmail.com', '1000049285', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jdtunjop2015t@gmail.com', 'jdtunjop2015t@gmail.com', '1000119462', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camilo.vargasmoncada21@gmail.com', 'camilo.vargasmoncada21@gmail.com', '1000136012', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaircup02@outlook.com', 'jaircup02@outlook.com', '1000379960', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'romarofu.jal@gmail.com', 'romarofu.jal@gmail.com', '1000721826', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andrew3597.afvs@gmail.com', 'andrew3597.afvs@gmail.com', '1000854996', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'stevearmero@gmail.com', 'stevearmero@gmail.com', '1001043004', 35, 1120, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'castanomonsalvea@gmail.com', 'castanomonsalvea@gmail.com', '1001468514', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josecarloshear15@gmail.com', 'josecarloshear15@gmail.com', '1001904538', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sharick.maria.17@gmail.com', 'sharick.maria.17@gmail.com', '1001934680', 2, 146, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Jtinoco@unicesar.edu.co', 'Jtinoco@unicesar.edu.co', '1002187308', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camilogm2019@gmail.com', 'camilogm2019@gmail.com', '1002207029', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jdra26@outlook.es', 'jdra26@outlook.es', '1002247837', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'm.j.m.t@outlook.com', 'm.j.m.t@outlook.com', '1002390370', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ximeavellar@gmail.com', 'ximeavellar@gmail.com', '1002437119', 4, 215, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jninocaicedo@gmail.com', 'jninocaicedo@gmail.com', '1002457175', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'salguerodaniel1002@gmail.com', 'salguerodaniel1002@gmail.com', '1002959707', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'santiagoguerrero148@gmail.com', 'santiagoguerrero148@gmail.com', '1002967714', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'valenciayurani520@gmail.com', 'valenciayurani520@gmail.com', '1004877387', 21, 956, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'obando.0129@gmail.com', 'obando.0129@gmail.com', '1004879569', 16, 732, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mendozavanessa567@gmail.com', 'mendozavanessa567@gmail.com', '1005045419', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'theylorvillegas2511@gmail.com', 'theylorvillegas2511@gmail.com', '1005045465', 16, 759, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'javier.econtreras89@gmail.com', 'javier.econtreras89@gmail.com', '1005181798', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'garciaclavijojuanfelipe147@gmail.com', 'garciaclavijojuanfelipe147@gmail.com', '1005483686', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'natalia.tascon.casas@gmail.com', 'natalia.tascon.casas@gmail.com', '1005705832', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yersoncamilomd@gmail.com', 'yersoncamilomd@gmail.com', '1006459428', 23, 992, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tapisr@hotmail.com', 'tapisr@hotmail.com', '1007110366', 1, 60, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Pamelaramoz.292001@gmail.com', 'Pamelaramoz.292001@gmail.com', '1007684908', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jacomerangelalvaro@gmail.com', 'jacomerangelalvaro@gmail.com', '1007959673', 16, 724, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elchupagiraldo@gmail.com', 'elchupagiraldo@gmail.com', '1010090420', 35, 1176, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jgomezvidal30@outlook.es', 'jgomezvidal30@outlook.es', '1010128989', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jcpereira@unicesar.edu.co', 'jcpereira@unicesar.edu.co', '1010134433', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abadia.comunicaciones@gmail.com', 'abadia.comunicaciones@gmail.com', '1010196031', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'paula-2111@hotmail.com', 'paula-2111@hotmail.com', '1010226624', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Instrueoz@gmail.com', 'Instrueoz@gmail.com', '1012320600', 32, 1102, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejosanchez1904@gmail.com', 'alejosanchez1904@gmail.com', '1012374464', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ruth_6816@hotmail.com', 'ruth_6816@hotmail.com', '1012397797', 1, 14, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'crof656@hotmail.com', 'crof656@hotmail.com', '1012428337', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edferroa@unal.edu.co', 'edferroa@unal.edu.co', '1014227393', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vladimirmalambosarmiento@gmail.com', 'vladimirmalambosarmiento@gmail.com', '1014237086', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fab.cast.ard@gmail.com', 'fab.cast.ard@gmail.com', '1014247093', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diegoaroca25@gmail.com', 'diegoaroca25@gmail.com', '1014297051', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'avsalamancam@unal.edu.co', 'avsalamancam@unal.edu.co', '1015423761', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nicromante87@gmail.com', 'nicromante87@gmail.com', '1015457600', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'victorparede15@gmail.com', 'victorparede15@gmail.com', '1017234927', 1, 75, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maesar87@gmail.com', 'maesar87@gmail.com', '1018414159', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'casierramo88@gmail.com', 'casierramo88@gmail.com', '1018418263', 9, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eamacondo@gmail.com', 'eamacondo@gmail.com', '1018471511', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alexsanderforero@gmail.com', 'alexsanderforero@gmail.com', '1018486123', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'msalcedom@unal.edu.co', 'msalcedom@unal.edu.co', '1019012072', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'geanbu@hotmail.com', 'geanbu@hotmail.com', '1019012952', 20, 922, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vela.lina@gmail.com', 'vela.lina@gmail.com', '1019020101', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lyineth.hernandezr@gmail.com', 'lyineth.hernandezr@gmail.com', '1019022853', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gomezmanriqueabogada@gmail.com', 'gomezmanriqueabogada@gmail.com', '1020445747', 1, 18, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sorenrf92@gmail.com', 'sorenrf92@gmail.com', '1020450681', 1, 14, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'saskiavasquezm@gmail.com', 'saskiavasquezm@gmail.com', '1020828223', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'valentina-nb@hotmail.com', 'valentina-nb@hotmail.com', '1020843981', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorangel003@gmail.com', 'jorangel003@gmail.com', '1022348821', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lalisarual90@gmail.com', 'lalisarual90@gmail.com', '1022366000', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgeeliezerdelcastillo@gmail.com', 'jorgeeliezerdelcastillo@gmail.com', '1022396213', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gersoncanong@gmail.com', 'gersoncanong@gmail.com', '1022405115', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, '3MALDANAM@GMAIL.COM', '3MALDANAM@GMAIL.COM', '1022930175', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tejadaaleyedinson@gmail.com', 'tejadaaleyedinson@gmail.com', '1022955919', 23, 993, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vmaleja.avm@gmail.com', 'vmaleja.avm@gmail.com', '1023003795', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rocioguerrerolopez1@gmail.com', 'rocioguerrerolopez1@gmail.com', '1023909365', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kattaeap@hotmail.com', 'kattaeap@hotmail.com', '1024497338', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'redinterdejovlgbtiq@gmail.com', 'redinterdejovlgbtiq@gmail.com', '1024509591', 9, 456, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yeseniamr1991@gmail.com', 'yeseniamr1991@gmail.com', '1024526677', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'dignidadabolicionista@gmail.com', 'dignidadabolicionista@gmail.com', '1026262842', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abogadasanabria23@gmail.com', 'abogadasanabria23@gmail.com', '1026292145', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'stiff9503@gmail.com', 'stiff9503@gmail.com', '1026582238', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'christiandreypineda@gmail.com', 'christiandreypineda@gmail.com', '1026594275', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'giovannyrincons@gmail.com', 'giovannyrincons@gmail.com', '1030554418', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juacrodriguezmar@unal.edu.co', 'juacrodriguezmar@unal.edu.co', '1030576521', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cgppdiazn@gmail.com', 'cgppdiazn@gmail.com', '1030580427', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lfelipefajardosegura@gmail.com', 'lfelipefajardosegura@gmail.com', '1030594262', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ssdianac1@gmail.com', 'ssdianac1@gmail.com', '1030648843', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juliancifuentes.8896@gmail.com', 'juliancifuentes.8896@gmail.com', '1030669197', 9, 479, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jhdiaz961@gmail.com', 'jhdiaz961@gmail.com', '1030669786', 26, 1043, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mfespitiat97@gmail.com', 'mfespitiat97@gmail.com', '1031169858', 9, 461, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hjsalamanca@hotmail.com', 'hjsalamanca@hotmail.com', '1032440652', 9, 487, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aponte.william.lic@gmail.com', 'aponte.william.lic@gmail.com', '1032443498', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hdariotriana2024@gmail.com', 'hdariotriana2024@gmail.com', '1032460476', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sebastianbetancourt1994@gmail.com', 'sebastianbetancourt1994@gmail.com', '1032463575', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'omclopezlo@gmail.com', 'omclopezlo@gmail.com', '1032471774', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'florezdavid277@gmail.com', 'florezdavid277@gmail.com', '1035305419', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'brittozapatanicolas@gmail.com', 'brittozapatanicolas@gmail.com', '1035305565', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'conexionareiza@gmail.com', 'conexionareiza@gmail.com', '1035427340', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aleaguasalada@gmail.com', 'aleaguasalada@gmail.com', '1035431016', 1, 40, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miguelmedba@gmail.com', 'miguelmedba@gmail.com', '1035441022', 1, 14, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzadiela96@gmail.com', 'luzadiela96@gmail.com', '1035873408', 1, 57, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'victormanuelcastroq@gmail.com', 'victormanuelcastroq@gmail.com', '1035878214', 1, 57, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yeiorozco2009@gmail.com', 'yeiorozco2009@gmail.com', '1036399995', 1, 33, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'srivera959@gmail.com', 'srivera959@gmail.com', '1037613544', 1, 53, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'colombiagrupovida@gmail.com', 'colombiagrupovida@gmail.com', '1040737017', 1, 64, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'migna1999@gmail.com', 'migna1999@gmail.com', '1042461912', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camiloescorciaalvarez@hotmail.com', 'camiloescorciaalvarez@hotmail.com', '1043447903', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Juandviloria51@gmail.com', 'Juandviloria51@gmail.com', '1043931566', 2, 147, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'paodelahoz30@gmail.com', 'paodelahoz30@gmail.com', '1045703763', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edwin30-@hotmail.com', 'edwin30-@hotmail.com', '1047417766', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Carolinaquintero2802@gmail.com', 'Carolinaquintero2802@gmail.com', '1049644075', 4, 191, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angie21vega@gmail.com', 'angie21vega@gmail.com', '1049657405', 4, 191, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lekaort@hotmail.com', 'lekaort@hotmail.com', '1050170637', 4, 199, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vivizsuescun77@gmail.com', 'vivizsuescun77@gmail.com', '1052392667', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dialesch@hotmail.com', 'dialesch@hotmail.com', '1052396325', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leonardo.holguin77@gmail.com', 'leonardo.holguin77@gmail.com', '1053587287', 4, 250, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'julianburguz@gmail.com', 'julianburguz@gmail.com', '1053833480', 14, 658, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'RAULANDRES.MSA04@GMAIL.COM', 'RAULANDRES.MSA04@GMAIL.COM', '1053851698', 26, 1040, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pmjimenez33@gmail.com', 'pmjimenez33@gmail.com', '1054555392', 1, 65, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ivetterodpa@gmail.com', 'ivetterodpa@gmail.com', '1057465798', 4, 266, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angelatrujillo.87@gmail.com', 'angelatrujillo.87@gmail.com', '1057573672', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dumarandres1@gmail.com', 'dumarandres1@gmail.com', '1057603330', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arcedelgadoandresfelipe@gmail.com', 'arcedelgadoandresfelipe@gmail.com', '1059063103', 6, 360, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josericardoortega3@gmail.com', 'josericardoortega3@gmail.com', '1059363288', 6, 343, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gozya2024@gmail.com', 'gozya2024@gmail.com', '1059594959', 6, 361, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'isaactopin2@gmail.com', 'isaactopin2@gmail.com', '1059597142', 6, 361, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arboledag9@gmail.com', 'arboledag9@gmail.com', '1059601509', 6, 373, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leocm368@gmail.com', 'leocm368@gmail.com', '1060801458', 6, 346, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elkinagredo8914@gmail.com', 'elkinagredo8914@gmail.com', '1060871382', 6, 350, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wilderlopeznavarro@gmail.com', 'wilderlopeznavarro@gmail.com', '1060876807', 6, 350, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mauricioguevara717@gmail.com', 'mauricioguevara717@gmail.com', '1060879528', 6, 350, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hecmau5503@gmail.com', 'hecmau5503@gmail.com', '1061698087', 6, 370, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maferj@gmail.com', 'maferj@gmail.com', '1061710317', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nododetecnologia@gmail.com', 'nododetecnologia@gmail.com', '1061725399', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mosriascos@gmail.com', 'mosriascos@gmail.com', '1061737204', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'funbico@gmail.com', 'funbico@gmail.com', '1061738786', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejojeferjulia@gmail.com', 'alejojeferjulia@gmail.com', '1061740146', 31, 1091, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tatianajh29@gmail.com', 'tatianajh29@gmail.com', '1061769857', 12, 600, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bmueses.17@gmail.com', 'bmueses.17@gmail.com', '1061773963', 6, 357, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'guztrock@gmail.com', 'guztrock@gmail.com', '1061776500', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'duvansanchez0613@gmail.com', 'duvansanchez0613@gmail.com', '1061790213', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sofiagomezcabrera25@gmail.com', 'sofiagomezcabrera25@gmail.com', '1061800561', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'generacionhumanaalmaguer@gmail.com', 'generacionhumanaalmaguer@gmail.com', '1061986151', 6, 341, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'xamirgaviria28@gmail.com', 'xamirgaviria28@gmail.com', '1061989690', 6, 341, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nelsonlopez26@hotmail.es', 'nelsonlopez26@hotmail.es', '1062323926', 6, 381, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'SALOME.GONZALEZ@CORREOUNIVALLE.EDU.CO', 'SALOME.GONZALEZ@CORREOUNIVALLE.EDU.CO', '1062328666', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rafaelconcejoph@gmail.com', 'rafaelconcejoph@gmail.com', '1062330663', 6, 381, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andresgrupohappy@gmail.com', 'andresgrupohappy@gmail.com', '1063301182', 8, 420, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rosullune@gmail.com', 'rosullune@gmail.com', '1064429360', 6, 370, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'klingerjavier@gmail.com', 'klingerjavier@gmail.com', '1064488046', 6, 375, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josepad89@gmail.com', 'josepad89@gmail.com', '1064791478', 7, 390, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yosergiojoaquin@outlook.es', 'yosergiojoaquin@outlook.es', '1064839506', 16, 732, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'francisco.nl20@outlook.com', 'francisco.nl20@outlook.com', '1064842474', 7, 401, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'danilomarquez12pallares@gmail.com', 'danilomarquez12pallares@gmail.com', '1065575611', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andreacarolinapolietica@gmail.com', 'andreacarolinapolietica@gmail.com', '1065647888', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'danilson2290@hotmail.com', 'danilson2290@hotmail.com', '1066086354', 7, 398, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marlychate3@gmail.com', 'marlychate3@gmail.com', '1067530907', 6, 376, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arlizonestiberpardo@gmail.com', 'arlizonestiberpardo@gmail.com', '1069761143', 9, 456, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eduardomora2024@outlook.com', 'eduardomora2024@outlook.com', '1070605356', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'julisegu18@gmail.com', 'julisegu18@gmail.com', '1070916060', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anfecupaz96@gmail.com', 'anfecupaz96@gmail.com', '1070977054', 9, 451, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sergiopovedaleon21@outlook.com', 'sergiopovedaleon21@outlook.com', '1070979522', 9, 451, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'santiagoromero1v@gmail.com', 'santiagoromero1v@gmail.com', '1073158115', 9, 484, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diegovet24@gmail.com', 'diegovet24@gmail.com', '1073255186', 9, 487, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'natalita0221@hotmail.com', 'natalita0221@hotmail.com', '1073507976', 9, 454, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diputadofrankfierro@gmail.com', 'diputadofrankfierro@gmail.com', '1075221011', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yentilg@gmail.com', 'yentilg@gmail.com', '1075245708', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amvar555@gmail.com', 'amvar555@gmail.com', '1075254437', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dngonzalocenaprov@gmail.com', 'dngonzalocenaprov@gmail.com', '1075260180', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'openmindpeoplecolombia@gmail.com', 'openmindpeoplecolombia@gmail.com', '1075263816', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejo.pabon.m@hotmail.com', 'alejo.pabon.m@hotmail.com', '1075276540', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'casb130@hotmail.com', 'casb130@hotmail.com', '1075276657', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edsoncasagua@hotmail.com', 'edsoncasagua@hotmail.com', '1075276901', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sebastianrodriguezramirez76@gmail.com', 'sebastianrodriguezramirez76@gmail.com', '1075297170', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariguve30@gmail.com', 'mariguve30@gmail.com', '1075598634', 12, 602, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dailasofia@hotmail.com', 'dailasofia@hotmail.com', '1075657408', 9, 548, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dairo4343@gmail.com', 'dairo4343@gmail.com', '1076984489', 12, 584, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yalogi10@gmail.com', 'yalogi10@gmail.com', '1077423271', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'harryjaviersernacorrea1991@gmail.com', 'harryjaviersernacorrea1991@gmail.com', '1077453490', 11, 554, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fuapapa@hotmail.com', 'fuapapa@hotmail.com', '1077453929', 11, 550, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nanajovi66@gmail.com', 'nanajovi66@gmail.com', '1077845637', 12, 1306, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'worozcoc@hotmail.com', 'worozcoc@hotmail.com', '1079884683', 13, 629, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'osfaboto1988@gmail.com', 'osfaboto1988@gmail.com', '1080261048', 6, 373, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejaos899@hotmail.com', 'alejaos899@outlook.com', '1081395445', 12, 593, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diegofer900211@gmail.com', 'diegofer900211@gmail.com', '1081404370', 12, 593, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fontalvojesus993@gmail.com', 'fontalvojesus993@gmail.com', '1081762938', 13, 624, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'duberneydia@unicauca.edu.co', 'duberneydia@unicauca.edu.co', '1082776492', 12, 605, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alvaroe.jimenez@hotmail.com', 'alvaroe.jimenez@hotmail.com', '1082980774', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'neato-16@hotmail.com', 'neato-16@hotmail.com', '1083908114', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'brayanstiven2021@gmail.com', 'brayanstiven2021@gmail.com', '1083918667', 12, 600, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wodr85@live.com', 'wodr85@live.com', '1085245562', 14, 704, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andruusss6000@hotmail.com', 'andruusss6000@hotmail.com', '1085287910', 14, 678, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dilan_m@live.com', 'dilan_m@live.com', '1085299794', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maralar@protonmail.com', 'maralar@protonmail.com', '1085319459', 35, 1176, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jito.13@hotmail.com', 'jito.13@hotmail.com', '1085326738', 14, 642, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mafetimana28@gmail.com', 'mafetimana28@gmail.com', '1085332002', 35, 1233, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kddulce@unicauca.edu.co', 'kddulce@unicauca.edu.co', '1085343403', 14, 642, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'danielitha1996dj@gmail.com', 'danielitha1996dj@gmail.com', '1085690108', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jocampa12@gmail.com', 'jocampa12@gmail.com', '1085902642', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'm-fercha29@hotmail.com', 'm-fercha29@hotmail.com', '1087412175', 14, 707, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'victorgil248@gmail.com', 'victorgil248@gmail.com', '1087490554', 15, 715, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marcelomarinsierra@gmail.com', 'marcelomarinsierra@gmail.com', '1087491207', 15, 715, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dianamarcela.247@gmail.com', 'dianamarcela.247@gmail.com', '1088265065', 35, 1280, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'flor.maria1986@hotmail.com', 'flor.maria1986@hotmail.com', '1089000059', 14, 684, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ygranadoc@unal.edu.co', 'ygranadoc@unal.edu.co', '1089798792', 14, 662, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abogadojoanunab@gmail.com', 'abogadojoanunab@gmail.com', '1090178857', 16, 736, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cleiro@hotmail.com', 'cleiro@hotmail.com', '1090388393', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'publiplastgraphiccucuta@gmail.com', 'publiplastgraphiccucuta@gmail.com', '1090411529', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lauracristinaam1989@gmail.com', 'lauracristinaam1989@gmail.com', '1090419583', 16, 729, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jeffer19_05@hotmail.com', 'jeffer19_05@hotmail.com', '1090449912', 16, 729, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ing.juandavidgm@gmail.com', 'ing.juandavidgm@gmail.com', '1090484127', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'isaacjgar@gmail.com', 'isaacjgar@gmail.com', '1090485667', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'heinermartinez236@gmail.com', 'heinermartinez236@gmail.com', '1090502506', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hkaterinquintero@gmail.com', 'hkaterinquintero@gmail.com', '1090516877', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'morenoduran2020@hotmail.com', 'morenoduran2020@hotmail.com', '1091382448', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'evelio166@gmail.com', 'evelio166@gmail.com', '1091534826', 16, 740, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nllozano23@gmail.com', 'nllozano23@gmail.com', '1091652598', 7, 401, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jekcsan89@protonmail.com', 'jekcsan89@protonmail.com', '1091661014', 16, 724, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mireyapino9@hotmail.com', 'mireyapino9@hotmail.com', '1091680113', 16, 732, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'harol.gonzalez@aol.com', 'harol.gonzalez@aol.com', '1093221832', 35, 1176, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'felox7656@hotmail.com', 'felox7656@hotmail.com', '1093909509', 18, 844, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'felsomina2012@hotmail.com', 'felsomina2012@hotmail.com', '1094240313', 22, 981, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fresalomejor@gmail.com', 'fresalomejor@gmail.com', '1094277184', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'qoyeraldine@gmail.com', 'qoyeraldine@gmail.com', '1094581261', 16, 724, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luztizzas@gmail.com', 'luztizzas@gmail.com', '1096194989', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'geralmendo8830@gmail.com', 'geralmendo8830@gmail.com', '1096195748', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anyel.duran@unipaz.edu.co', 'anyel.duran@unipaz.edu.co', '1096211983', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'caritolopeznoriega@gmail.com', 'caritolopeznoriega@gmail.com', '1096219940', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angieliney20@hotmail.com', 'angieliney20@hotmail.com', '1096235106', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yoanzasa2014@gmail.com', 'yoanzasa2014@gmail.com', '1097392717', 17, 764, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'doryvelrojas@gmail.com', 'doryvelrojas@gmail.com', '1098151318', 18, 791, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alexisecheverri@hotmail.com', 'alexisecheverri@hotmail.com', '1098313322', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yemerson.hdz@gmail.com', 'yemerson.hdz@gmail.com', '1098623258', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sherzy.19@hotmail.com', 'sherzy.19@hotmail.com', '1098652136', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juancarlosgonzalez_ortiz@hotmail.com', 'juancarlosgonzalez_ortiz@hotmail.com', '1098662406', 18, 825, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ivancrojasm@hotmail.com', 'ivancrojasm@hotmail.com', '1098666083', 18, 844, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jffq_09@hotmail.com', 'jffq_09@hotmail.com', '1098680569', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aparra1808@gmail.com', 'aparra1808@gmail.com', '1098747136', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wilmeralfonsocaballerovillegas@gmail.com', 'wilmeralfonsocaballerovillegas@gmail.com', '1099342984', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, '5784contreras@gmail.com', '5784contreras@gmail.com', '1099990947', 19, 868, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ecastroc@superservicios.gov.co', 'ecastroc@superservicios.gov.co', '1100395695', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juridicosergiolopez@gmail.com', 'juridicosergiolopez@gmail.com', '1100957745', 18, 844, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gutia1034@gmail.com', 'gutia1034@gmail.com', '1101260269', 18, 795, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yos_mire@hotmail.com', 'yos_mire@hotmail.com', '1102812289', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juandavidcortesdiaz@gmail.com', 'juandavidcortesdiaz@gmail.com', '1102853111', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angelagonossa@gmail.com', 'angelagonossa@gmail.com', '1102873068', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'narvaezlarajesus@gmail.com', 'narvaezlarajesus@gmail.com', '1103741009', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jcuestamontes@gmail.com', 'jcuestamontes@gmail.com', '1104131078', 18, 839, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'deinaluzdiaz@gmail.com', 'deinaluzdiaz@gmail.com', '1104412081', 19, 881, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ospinalozanoc@gmail.com', 'ospinalozanoc@gmail.com', '1106398432', 20, 922, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'prietofabian201@gmail.com', 'prietofabian201@gmail.com', '1110174107', 20, 917, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andreamanjarres.26@hotmail.com', 'andreamanjarres.26@hotmail.com', '1110553573', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sebastianamezquita05@gmail.com', 'sebastianamezquita05@gmail.com', '1110553597', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cespedesd11@gmail.com', 'cespedesd11@gmail.com', '1110557004', 20, 922, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sterogu98@gmail.com', 'sterogu98@gmail.com', '1110592128', 20, 888, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rosero0618@gmail.com', 'rosero0618@gmail.com', '1111813716', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jimenezgaviriah@gmail.com', 'jimenezgaviriah@gmail.com', '1112151596', 21, 974, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'darwinrojasfranco2@gmail.com', 'darwinrojasfranco2@gmail.com', '1112224020', 21, 962, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cristiandavidgalvis94@gmail.com', 'cristiandavidgalvis94@gmail.com', '1112463803', 21, 956, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juandavidguapachabolivar@gmail.com', 'juandavidguapachabolivar@gmail.com', '1112766556', 21, 946, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dianafernanda42@gmail.com', 'dianafernanda42@gmail.com', '1113520300', 35, 1136, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jesuselias83@gmail.com', 'jesuselias83@gmail.com', '1113618716', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'isabelverazap@gmail.com', 'isabelverazap@gmail.com', '1113694459', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maesda1715@gmail.com', 'maesda1715@gmail.com', '1113788745', 21, 965, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sergioospina090@gmail.com', 'sergioospina090@gmail.com', '1114058158', 21, 966, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'daviddaza13@gmail.com', 'daviddaza13@gmail.com', '1116801342', 22, 978, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'a.santamaria@udla.edu.co', 'a.santamaria@udla.edu.co', '1117524439', 23, 984, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nicolasrodriguezmelendez2007@gmail.com', 'nicolasrodriguezmelendez2007@gmail.com', '1117814962', 23, 992, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nancy.5.1@hotmail.com', 'nancy.5.1@hotmail.com', '1118540242', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'galvismagdalena28@gmail.com', 'galvismagdalena28@gmail.com', '1118824999', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'shniken14@gmail.com', 'shniken14@gmail.com', '1119180241', 22, 981, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'migdonia.2021@gmail.com', 'migdonia.2021@gmail.com', '1119891394', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Renasdiaz@gmail.com', 'Renasdiaz@gmail.com', '1120575305', 28, 1072, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leidyines0901@hotmail.com', 'leidyines0901@hotmail.com', '1120584056', 28, 1072, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'duqueoscar823@gmail.com', 'duqueoscar823@gmail.com', '1121829807', 33, 1109, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rudenagm07@gmail.com', 'rudenagm07@gmail.com', '1121870042', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanksf@hotmail.com', 'juanksf@hotmail.com', '1121888608', 9, 450, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'estefa19tea@hotmail.com', 'estefa19tea@hotmail.com', '1121901586', 21, 970, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'alejandra_baquero@hotmail.com', 'alejandra_baquero@hotmail.com', '1121926629', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dianahuertash@gmail.com', 'dianahuertash@gmail.com', '1121930884', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'albertocortessantos@gmail.com', 'albertocortessantos@gmail.com', '1121932595', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dayanacubillos96@gmail.com', 'dayanacubillos96@gmail.com', '1121934917', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yisethdayana30@gmail.com', 'yisethdayana30@gmail.com', '1121954398', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'forero1071@gmail.com', 'forero1071@gmail.com', '1122237216', 18, 777, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yamidortega86@gmail.com', 'yamidortega86@gmail.com', '1122336664', 31, 1095, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'auraepiayu2@gmail.com', 'auraepiayu2@gmail.com', '1122808604', 25, 1021, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maxtorresnegocios@gmail.com', 'maxtorresnegocios@gmail.com', '1122810063', 25, 1021, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'estivenromero1988@gmail.com', 'estivenromero1988@gmail.com', '1122811612', 25, 1021, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marcohernandez2599@gmail.com', 'marcohernandez2599@gmail.com', '1123515134', 26, 1043, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ybrios@uniguajira.edu.co', 'ybrios@uniguajira.edu.co', '1123994814', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'johanna3832@hotmail.com', 'johanna3832@hotmail.com', '1123998350', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jairjose-14@hotmail.com', 'jairjose-14@hotmail.com', '1124001964', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eihasy@gmail.com', 'eihasy@gmail.com', '1124007761', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ninosladailinlopez@gmail.com', 'ninosladailinlopez@gmail.com', '1124566744', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yuskmontiel@gmail.com', 'yuskmontiel@gmail.com', '1124567845', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'benitez.puertogaitan@gmail.com', 'benitez.puertogaitan@gmail.com', '1124820992', 26, 1058, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mjusa22@hotmail.es', 'mjusa22@hotmail.es', '1124857700', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ardilaoswaldo10@gmail.com', 'ardilaoswaldo10@gmail.com', '1124991211', 33, 1110, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'supervisorhse8809.transdepet@gmail.com', 'supervisorhse8809.transdepet@gmail.com', '1125409103', 31, 1099, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angievargas2014@hotmail.com', 'angievargas2014@hotmail.com', '1125469318', 33, 1110, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'culturizandojusticia@gmail.com', 'culturizandojusticia@gmail.com', '1127044126', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jennifer_9004@yahoo.com', 'jennifer_9004@yahoo.com', '1128432939', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'katrina98giron@gmail.com', 'katrina98giron@gmail.com', '1130650797', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hernandezperezcristian34@gmail.com', 'hernandezperezcristian34@gmail.com', '1133795261', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'guillermocastroo@hotmail.com', 'guillermocastroo@hotmail.com', '1140415879', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jeanhernandezbaq@gmail.com', 'jeanhernandezbaq@gmail.com', '1140890202', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cccantilloyepes@gmail.com', 'cccantilloyepes@gmail.com', '1143346985', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sandycabrera1209@gmail.com', 'sandycabrera1209@gmail.com', '1143428607', 3, 165, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tefabenavides@gmail.com', 'tefabenavides@gmail.com', '1144085932', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'samira.ortega@correounivalle.edu.co', 'samira.ortega@correounivalle.edu.co', '1144103555', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lenisleyer@gmail.com', 'lenisleyer@gmail.com', '1144141707', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kevin.mancilla@correounivalle.edu.co', 'kevin.mancilla@correounivalle.edu.co', '1144171799', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'paolapulgarin06@gmail.com', 'paolapulgarin06@gmail.com', '1152692447', 35, 1120, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisazuleta16@gmail.com', 'luisazuleta16@gmail.com', '1152713471', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'moyaha0820@gmail.com', 'moyaha0820@gmail.com', '1193064627', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Camilabarajasp12345@gmail.com', 'Camilabarajasp12345@gmail.com', '1193067407', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carnunez20@gmail.com', 'carnunez20@gmail.com', '1193465806', 4, 211, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mamoribe27@gmail.com', 'mamoribe27@gmail.com', '1193541300', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hectores2001@hotmail.com', 'hectores2001@hotmail.com', '1193551893', 1, 52, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mauricionogales75@gmail.com', 'mauricionogales75@gmail.com', '1193598781', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'munozgiraldojuandavid9@gmail.com', 'munozgiraldojuandavid9@gmail.com', '1214722968', 1, 29, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angiesofiacharit111414@gmail.com', 'angiesofiacharit111414@gmail.com', '1233493804', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andreavargasbarranquilla@gmail.com', 'andreavargasbarranquilla@gmail.com', '1234094675', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ferney.silva@senado.gov.co', 'ferney.silva@senado.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alex.florez@senado.gov.co', 'alex.florez@senado.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'esmeralda.hernandez@senado.gov.co', 'esmeralda.hernandez@senado.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gloria.florez@senado.gov.co', 'gloria.florez@senado.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'catalina.perez@senado.gov.co', 'catalina.perez@senado.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorge.bastidas@camara.gov.co', 'jorge.bastidas@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andres.cancimance@camara.gov.co', 'andres.cancimance@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maria.carrascal@camara.gov.co', 'maria.carrascal@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'agmeth.escaf@camara.gov.co', 'agmeth.escaf@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'susana.gomez@camara.gov.co', 'susana.gomez@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mary.perdomo@camara.gov.co', 'mary.perdomo@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariam.pizarro@camara.gov.co', 'mariam.pizarro@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carmen.ramirez@camara.gov.co', 'carmen.ramirez@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pedro.suarez@camara.gov.co', 'pedro.suarez@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leider.vasquez@camara.gov.co', 'leider.vasquez@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejandro.toro@camara.gov.co', 'alejandro.toro@camara.gov.co', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nataliagordillo2014@gmail.com', 'nataliagordillo2014@gmail.com', NULL, NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hugoguamanga@1965gimail.com', 'hugoguamanga@1965gimail.com', '4766053', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'quindoalexander11@gmail.com', 'quindoalexander11@gmail.com', '4788795', 6, 377, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'murillogustavo1964@gmail.com', 'murillogustavo1964@gmail.com', '6138110', 21, 940, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gabovasquez.psi@gmail.com', 'gabovasquez.psi@gmail.com', '6560951', 21, 976, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bolivar5828contador@yahoo.es', 'bolivar5828contador@yahoo.es', '8679131', 13, 623, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tribais2018@gmail.com', 'tribais2018@gmail.com', '8751637', 35, 1300, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jlcaballero76@gmail.com', 'jlcaballero76@gmail.com', '9292195', 3, 156, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ramirodesarrolloz@hotmail.com', 'ramirodesarrolloz@hotmail.com', '10554738', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edullanes@hotmail.com', 'edullanes@hotmail.com', '12530320', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'emiroropsuarez87@gmail.com', 'emiroropsuarez87@gmail.com', '13461523', 16, 762, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'epimeniomedinaquiroga@gmail.com', 'epimeniomedinaquiroga@gmail.com', '13956589', 18, 779, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lavozinternacional@gmail.com', 'lavozinternacional@gmail.com', '14965342', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ericsanantero@hotmail.com', 'ericsanantero@hotmail.com', '15615216', 8, 429, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'frajago01@gmail.com', 'frajago01@gmail.com', '16271951', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arnulfomunoz2009@gmail.com', 'arnulfomunoz2009@gmail.com', '16696568', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'haroldwilsonlopezlopez@gmail.com', 'haroldwilsonlopezlopez@gmail.com', '18126727', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlosrodrigo.montes@gmail.com', 'carlosrodrigo.montes@gmail.com', '18462911', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'INTERNETCARLOSYJAVIER@GMAIL.COM', 'INTERNETCARLOSYJAVIER@GMAIL.COM', '19146505', 21, 956, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arridiogenes@gmail.com', 'arridiogenes@gmail.com', '19158875', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marin.itaka@gmail.com', 'marin.itaka@gmail.com', '19234788', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fhectorhernando@yahoo.com', 'fhectorhernando@yahoo.com', '19295337', 35, 1233, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arq.saul.cs@gmail.com', 'arq.saul.cs@gmail.com', '19389898', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ricardogestionhumana@yahoo.com', 'ricardogestionhumana@yahoo.com', '19452060', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eliecerpolanco60@gmail.com', 'eliecerpolanco60@gmail.com', '19789561', 3, 156, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'blancainesperez7@gmail.com', 'blancainesperez7@gmail.com', '22191780', 1, 116, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nellyhurtado0205@gmail.com', 'nellyhurtado0205@gmail.com', '31378078', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'glucypiedad@gmail.com', 'glucypiedad@gmail.com', '34544579', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'emurillo58@gmail.com', 'emurillo58@gmail.com', '38941105', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zulebarros72@gmail.com', 'zulebarros72@gmail.com', '40924701', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leidysubaque@hotmail.com', 'leidysubaque@hotmail.com', '45542506', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'claraeplazas@gmail.com', 'claraeplazas@gmail.com', '51563137', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'narimazulcerinza@hotmail.com', 'narimazulcerinza@hotmail.com', '51755465', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'biancacegob@gmail.com', 'biancacegob@gmail.com', '52158156', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'taniarbr712@gmail.com', 'taniarbr712@gmail.com', '52582802', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cristinaalvarado@msn.com', 'cristinaalvarado@msn.com', '63301233', 35, 1176, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sandrapbarajas@hotmail.com', 'sandrapbarajas@hotmail.com', '63483458', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'belmiraisabel@hotmail.com', 'belmiraisabel@hotmail.com', '64549071', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josemarnol@hotmail.com', 'josemarnol@hotmail.com', '76334834', 6, 344, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'wrchacon@misena.edu.co', 'wrchacon@misena.edu.co', '77101703', 7, 390, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Perrocriollo113@hotmail.com', 'Perrocriollo113@hotmail.com', '79243157', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cesarmoto@gmail.com', 'cesarmoto@gmail.com', '79267036', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgeezuletaz12@gmail.com', 'jorgeezuletaz12@gmail.com', '79357295', 7, 404, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tapieromol@hotmail.com', 'tapieromol@hotmail.com', '79467821', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luichioso1@gmail.com', 'luichioso1@gmail.com', '80143313', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'johorta24@gmail.com', 'johorta24@gmail.com', '80251290', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hermeenriquemartines@gmail.com', 'hermeenriquemartines@gmail.com', '84037445', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dimas_172@hotmail.com', 'dimas_172@hotmail.com', '88279011', 16, 741, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'areatiga1602@gmail.com', 'areatiga1602@gmail.com', '91154391', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'german.tarazona68@gmail.com', 'german.tarazona68@gmail.com', '91258843', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'a.sergioalarcon@gmail.com', 'a.sergioalarcon@gmail.com', '94331716', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dsgonzalez@upn.edu.co', 'dsgonzalez@upn.edu.co', '1001638247', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'deylergueto16@gmail.com', 'deylergueto16@gmail.com', '1002193375', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carolinaparra1026@gmail.com', 'carolinaparra1026@gmail.com', '1005322033', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'biciverso1@gmail.com', 'biciverso1@gmail.com', '1013615605', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'MPENALOZAB@UNAL.EDU.CO', 'MPENALOZAB@UNAL.EDU.CO', '1018482767', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juaramirezpi@unal.edu.co', 'juaramirezpi@unal.edu.co', '1032471632', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andresdelcauca@gmail.com', 'andresdelcauca@gmail.com', '1061723646', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ocontrerasm@ufpso.edu.co', 'ocontrerasm@ufpso.edu.co', '1066063010', 7, 394, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ankaduarte2024@gmail.com', 'ankaduarte2024@gmail.com', '1082957771', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mvsorozcov@gmail.com', 'mvsorozcov@gmail.com', '1083007932', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alvare@utp.edu.co', 'alvare@utp.edu.co', '1088323187', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juliethpadilla5681@gmail.com', 'juliethpadilla5681@gmail.com', '1096197141', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eliecersierra00@gmail.com', 'eliecersierra00@gmail.com', '1101782120', 19, 863, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'MALEJA.CABRERA13@GMAIL.COM', 'MALEJA.CABRERA13@GMAIL.COM', '1121930499', 16, 762, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camicardenas442@gmail.com', 'camicardenas442@gmail.com', '1123991646', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ftimms@uniguajira.edu.co', 'ftimms@uniguajira.edu.co', '1124042932', 25, 1027, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaiandrest@gmail.com', 'jaiandrest@gmail.com', '1144027584', 21, 972, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vergara11795@gmail.com', 'vergara11795@gmail.com', '1151477298', 19, 884, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gabrielfeliperosero.9.3@gmail.com', 'gabrielfeliperosero.9.3@gmail.com', '1233189435', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'deiyerg4@gmail.com', 'deiyerg4@gmail.com', '1090174639', 16, 736, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fernandomurillo11@outlook.com', 'fernandomurillo11@outlook.com', '16940026', 21, 940, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edilvelascoruge@yahoo.es', 'edilvelascoruge@yahoo.es', '79318576', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gonzalezherney90@gmail.com', 'gonzalezherney90@gmail.com', '1000992908', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'casaseliza72@hotmail.com', 'casaseliza72@hotmail.com', '52121562', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ametesa@yah00.com', 'ametesa@yah00.com', '41337358', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dimasca01@gmail.com', 'dimasca01@gmail.com', '1007885262', 16, 740, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'josejuliancardenasz@gmail.com', 'josejuliancardenasz@gmail.com', '79108533', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jpulido_garcia@hotmail.com', 'jpulido_garcia@hotmail.com', '16508353', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diana.cardenas@correounivalle.edu.co', 'diana.cardenas@correounivalle.edu.co', '1143980284', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kalejarosero99@gmail.com', 'kalejarosero99@gmail.com', '1124867384', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'julianfajardo598@gmail.com', 'julianfajardo598@gmail.com', '1114338604', 21, 963, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'corazonvalientecorazon@gmail.com', 'corazonvalientecorazon@gmail.com', '2774682', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ruizxiomara606@gmail.com', 'ruizxiomara606@gmail.com', '25273466', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'abdenagoaguilar16@gmail.com', 'abdenagoaguilar16@gmail.com', '73106965', 9, 532, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kantojas69@gmail.com', 'kantojas69@gmail.com', '71396791', 1, 27, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'santialugoreyes@gmail.com', 'santialugoreyes@gmail.com', '1073177697', 9, 484, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlosgarzonconcejal1@gmail.com', 'carlosgarzonconcejal1@gmail.com', '1003704515', 9, 465, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Juanjosevargaspaspur@gmail.com', 'Juanjosevargaspaspur@gmail.com', '1002955974', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tapiagomezlaura@gmail.com', 'tapiagomezlaura@gmail.com', '1073237441', 9, 487, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ogpayares02@gmail.com', 'ogpayares02@gmail.com', '19146159', 8, 415, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'natalyjimenezts@gmail.com', 'natalyjimenezts@gmail.com', '52974993', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'afroteatrodigitaldetumaco@gmail.com', 'afroteatrodigitaldetumaco@gmail.com', '29509788', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gerardo610701@hotmail.com', 'gerardo610701@hotmail.com', '10539543', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'manuel.cuadrado@hotmail.com', 'manuel.cuadrado@hotmail.com', '11078810', 8, 414, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'npnuse@gmail.com', 'npnuse@gmail.com', '51832604', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'victoriabarcoyamil@gmail.com', 'victoriabarcoyamil@gmail.com', '31588580', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'viajeconalejo@gmail.com', 'viajeconalejo@gmail.com', '3250100', 9, 447, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carmenrochaperez60@gmail.com', 'carmenrochaperez60@gmail.com', '22968545', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'casavargaspublicidad@gmail.com', 'casavargaspublicidad@gmail.com', '72177780', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'colombia_sam@hotmail.com', 'colombia_sam@hotmail.com', '29700895', 21, 962, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'antoniolentesur@gmail.com', 'antoniolentesur@gmail.com', '3355199', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'veroholguib@gmail.com', 'veroholguib@gmail.com', '3434403', 1, 31, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'c.josefagua@gmail.com', 'c.josefagua@gmail.com', '6768077', 4, 220, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'diegoojeda04@yahoo.com', 'diegoojeda04@yahoo.com', '13071091', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alalimon@yahoo.es', 'alalimon@yahoo.es', '32323071', 1, 92, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucyministerial@yahoo.es', 'lucyministerial@yahoo.es', '36543504', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yoissypolitica@yahoo.es', 'yoissypolitica@yahoo.es', '39175534', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dianalopez@gmail.com', 'dianalopez@gmail.com', '42690349', 1, 40, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sandralezcano29@gmail.com', 'sandralezcano29@gmail.com', '42938682', 1, 105, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marlenysparra.6@gmail.com', 'marlenysparra.6@gmail.com', '43141868', 1, 32, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'paolamerlano@yahoo.es', 'paolamerlano@yahoo.es', '43277700', 1, 85, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'stellamimaddre@gmail.com', 'stellamimaddre@gmail.com', '43611443', 1, 85, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dianarinro@gmail.com', 'dianarinro@gmail.com', '43742107', 1, 33, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jujigay@hotmail.com', 'jujigay@hotmail.com', '49655647', 7, 383, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nohorasuarez123@yahoo.com', 'nohorasuarez123@yahoo.com', '51574032', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amandy.0227@gmail.com', 'amandy.0227@gmail.com', '59820467', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'melagret@outlook.es', 'melagret@outlook.es', '1000194843', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yornelidisja@gmail.com', 'yornelidisja@gmail.com', '1001548579', 1, 35, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sebastianpolifonia@gmail.com', 'sebastianpolifonia@gmail.com', '1026148303', 1, 27, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'estrada_0520@hotmail.com', 'estrada_0520@hotmail.com', '1041150747', 1, 54, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nasnermonica@gmail.com', 'nasnermonica@gmail.com', '1087417855', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'brigitte.ariasb@hotmail.com', 'brigitte.ariasb@hotmail.com', '1121960979', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'karina.contrerasve@amigo.edu.co', 'karina.contrerasve@amigo.edu.co', '1128434100', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejandra16250@hotmail.com', 'alejandra16250@hotmail.com', '1144135830', 21, 962, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maviclo99@gmail.com', 'maviclo99@gmail.com', '1233194378', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ubercondes@gmail.com', 'ubercondes@gmail.com', '1977546', 16, 758, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cheovelasquezp@hotmail.com', 'cheovelasquezp@hotmail.com', '2376176', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rafaellvillanuevas@gmail.com', 'rafaellvillanuevas@gmail.com', '3771374', 2, 147, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elyermanito@gmail.com', 'elyermanito@gmail.com', '4351385', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luishernanramirez54@gmail.com', 'luishernanramirez54@gmail.com', '4663800', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luciak2531@gmail.com', 'luciak2531@gmail.com', '4774785', 6, 374, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vidalrojasdiego@gmail.com', 'vidalrojasdiego@gmail.com', '4851790', 11, 555, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cesajaramillo26@hotmail.com', 'cesajaramillo26@hotmail.com', '4939964', 12, 609, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgepeinado21@gmail.com', 'jorgepeinado21@gmail.com', '5591543', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'panfinioramirez0821@gmail.com', 'panfinioramirez0821@gmail.com', '5671243', 18, 822, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leomogotes75@gmail.com', 'leomogotes75@gmail.com', '5690061', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'antoniorojas79@outlook.com', 'antoniorojas79@outlook.com', '5765378', 18, 854, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eugenio.rodriguez.lievano@gmail.com', 'eugenio.rodriguez.lievano@gmail.com', '5933024', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'carlosamurielrojas_1@hotmail.com', 'carlosamurielrojas_1@hotmail.com', '6110430', 21, 970, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sixtotul617@hotmail.com', 'sixtotul617@hotmail.com', '6175787', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marcos-dani.perez@hotmail.com', 'marcos-dani.perez@hotmail.com', '6228319', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fidel.arias753@gmail.com', 'fidel.arias753@gmail.com', '6298067', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'franciscojcifuentes@gmail.com', 'franciscojcifuentes@gmail.com', '6318579', 21, 955, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'javy926@gmail.com', 'javy926@gmail.com', '6342645', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'inkary@hotmail.com', 'inkary@hotmail.com', '6423138', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rafaelguerra1324@gmail.com', 'rafaelguerra1324@gmail.com', '6661821', 8, 422, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'monocat012957@gmail.com', 'monocat012957@gmail.com', '6760331', 24, 1006, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arambal@live.com', 'arambal@live.com', '7010875', 4, 214, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ulises020871@gmail.com', 'ulises020871@gmail.com', '7164402', 4, 191, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marcosdaniel07@gmail.com', 'marcosdaniel07@gmail.com', '7305668', 4, 206, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ulisesnino67@gmail.com', 'ulisesnino67@gmail.com', '7361787', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rafaeljulio030@hotmail.com', 'rafaeljulio030@hotmail.com', '7462630', 24, 1007, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edorpenen@gmail.com', 'edorpenen@gmail.com', '7479539', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alvarolargorada@gmail.com', 'alvarolargorada@gmail.com', '7508823', 17, 763, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amoral21@hotmail.com', 'amoral21@hotmail.com', '7520839', 35, 1255, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgeluisternera@hotmail.com', 'jorgeluisternera@hotmail.com', '7592452', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'collazosedgardo177@gmail.com', 'collazosedgardo177@gmail.com', '7598299', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'casimirodera@gmail.com', 'casimirodera@gmail.com', '7630187', 18, 836, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'williguevara35@gmail.com', 'williguevara35@gmail.com', '7709927', 23, 996, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'oscaralfaroreales@gmail.com', 'oscaralfaroreales@gmail.com', '7958564', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'benitoalcalde@gmail.com', 'benitoalcalde@gmail.com', '8045578', 1, 35, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'argonconstrucciones@yahoo.es', 'argonconstrucciones@yahoo.es', '8171986', 1, 13, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'renevillau@gmail.com', 'renevillau@gmail.com', '8400069', 1, 55, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sumoexisto@gmail.com', 'sumoexisto@gmail.com', '8405489', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dreyes1801@hotmail.com', 'dreyes1801@hotmail.com', '8633462', 2, 142, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'emilianomejia875@gmail.com', 'emilianomejia875@gmail.com', '8686014', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'omarbv1901@gmail.com', 'omarbv1901@gmail.com', '8739126', 2, 127, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'evarolando1957@gmail.com', 'evarolando1957@gmail.com', '8752019', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cassereschavez1004@gmail.com', 'cassereschavez1004@gmail.com', '9085950', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'clubdebeisbolsanjose@yahoo.es', 'clubdebeisbolsanjose@yahoo.es', '9092027', 8, 429, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'williamgarcia1754@gmail.com', 'williamgarcia1754@gmail.com', '9309125', 19, 864, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cesargiovanny@gmail.com', 'cesargiovanny@gmail.com', '9399612', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amigosdeyopal82@hotmail.com', 'amigosdeyopal82@hotmail.com', '9431123', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jhmenan@gmail.com', 'jhmenan@gmail.com', '9930241', 17, 774, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ofcr356@gmail.com', 'ofcr356@gmail.com', '10082363', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucarga@gmail.com', 'lucarga@gmail.com', '10098737', 35, 1280, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nicolasarielocampo1@gmail.com', 'nicolasarielocampo1@gmail.com', '10247006', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgepizo@gmail.com', 'jorgepizo@gmail.com', '10299344', 6, 377, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'albertoe15@hotmail.com', 'albertoe15@hotmail.com', '10347852', 6, 360, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'silviomedina39@gmail.com', 'silviomedina39@gmail.com', '10476367', 8, 420, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pardito1254@gmail.com', 'pardito1254@gmail.com', '10522258', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luchoafinamiento@gmail.com', 'luchoafinamiento@gmail.com', '10536038', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arcesioquinonez7@gmail.com', 'arcesioquinonez7@gmail.com', '10690981', 6, 372, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edilsonangulo22@gmail.com', 'edilsonangulo22@gmail.com', '10692536', 6, 344, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisfernando_ballesteros@hotmail.com', 'luisfernando_ballesteros@hotmail.com', '10766008', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'benycruz06@gmail.com', 'benycruz06@gmail.com', '11292499', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgebarriosgutie@gmail.com', 'jorgebarriosgutie@gmail.com', '11294380', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'j.erinzonruiz@gmail.com', 'j.erinzonruiz@gmail.com', '11405903', 24, 1015, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carmelovalencia@hotmail.com', 'carmelovalencia@hotmail.com', '11788516', 11, 550, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'huilahumana@gmail.com', 'huilahumana@gmail.com', '12105800', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carbajaldelfin@yahoo.com', 'carbajaldelfin@yahoo.com', '12189313', 12, 1306, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leomuco310@gmail.com', 'leomuco310@gmail.com', '12270837', 12, 593, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ceravegagabriel@gmail.com', 'ceravegagabriel@gmail.com', '12536067', 13, 639, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'karulramirez_70@hotmail.com', 'karulramirez_70@hotmail.com', '12618335', 13, 639, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bayarruiz0@gmail.com', 'bayarruiz0@gmail.com', '12978869', 14, 646, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'willmarquiroz2018@gmail.com', 'willmarquiroz2018@gmail.com', '12996182', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tobal59@hotmail.com', 'tobal59@hotmail.com', '13445001', 18, 859, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jamerchan83@gmail.com', 'jamerchan83@gmail.com', '13466836', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'riosjuagro@gmail.com', 'riosjuagro@gmail.com', '13566560', 1, 83, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tiinfred_@hotmail.com', 'tiinfred_@hotmail.com', '13620528', 18, 829, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miguel.garcia20@gmail.com', 'miguel.garcia20@gmail.com', '13835235', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tecnorotativos@yahoo.com', 'tecnorotativos@yahoo.com', '13846918', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisalejandrorangel0210@gmail.com', 'luisalejandrorangel0210@gmail.com', '13880745', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gabrielmoyapedrozo@gmail.com', 'gabrielmoyapedrozo@gmail.com', '13883188', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'laeb23@hotmail.com', 'laeb23@hotmail.com', '13883385', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alvaradoseduard@gmail.com', 'alvaradoseduard@gmail.com', '13907011', 18, 791, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'petronicpjp1@gmail.com', 'petronicpjp1@gmail.com', '14449411', 21, 956, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fexpovalle2019@gmail.com', 'fexpovalle2019@gmail.com', '14839434', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisfesalazar@hotmail.com', 'luisfesalazar@hotmail.com', '14894954', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'algonso7@gmail.com', 'algonso7@gmail.com', '14947178', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luiseduardojimenezbedoya@gmail.com', 'luiseduardojimenezbedoya@gmail.com', '14987736', 21, 974, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejofreitass74@gmail.com', 'alejofreitass74@gmail.com', '15877884', 30, 1077, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joaleochoa@gmail.com', 'joaleochoa@gmail.com', '16253650', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jairotovarichoctubre1917@gmail.com', 'jairotovarichoctubre1917@gmail.com', '16263538', 21, 962, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jmoncadaesquivel@outlook.com', 'jmoncadaesquivel@outlook.com', '16348723', 21, 970, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gabrielcusi1954@gmail.com', 'gabrielcusi1954@gmail.com', '16447732', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camiyeso0504@gmail.com', 'camiyeso0504@gmail.com', '16447871', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'blaimiralarcon@gmail.com', 'blaimiralarcon@gmail.com', '16448266', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arseniojusto1962@hotmail.com', 'arseniojusto1962@hotmail.com', '16482296', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'che.saavedra@hotmail.com', 'che.saavedra@hotmail.com', '16580742', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisfernandocaballos@yahoo.com', 'luisfernandocaballos@yahoo.com', '16582233', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lmoreno.ipk41643@gmail.com', 'lmoreno.ipk41643@gmail.com', '16605745', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juaneleon24@gmail.com', 'juaneleon24@gmail.com', '16614118', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'magimaya@hotmail.es', 'magimaya@hotmail.es', '16633640', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ledesfred60@gmail.com', 'ledesfred60@gmail.com', '16657247', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cano230363@gmail.com', 'cano230363@gmail.com', '16681753', 21, 950, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jhsaenz63@gmail.com', 'jhsaenz63@gmail.com', '16686208', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisalf2164@hotmail.com', 'luisalf2164@hotmail.com', '16857445', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jhozap841@outlook.com', 'jhozap841@outlook.com', '16897841', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'perezequiceno@gmail.com', 'perezequiceno@gmail.com', '17099518', 15, 712, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'romanicoltda@gmail.com', 'romanicoltda@gmail.com', '17186820', 9, 439, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'efradelirio@yahoo.es', 'efradelirio@yahoo.es', '17315181', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'electrocacique@gmail.com', 'electrocacique@gmail.com', '17555169', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gp18toro@gmail.com', 'gp18toro@gmail.com', '18186942', 31, 1101, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tolozavictor40@gmail.com', 'tolozavictor40@gmail.com', '18970529', 7, 388, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'claudio.quembah@gmail.com', 'claudio.quembah@gmail.com', '19069508', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alvarohmejia@hotmail.com', 'alvarohmejia@hotmail.com', '19109017', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'macarios51@hotmail.com', 'macarios51@hotmail.com', '19167392', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'acb.395@hotmail.com', 'acb.395@hotmail.com', '19226395', 9, 496, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'encinardemanrre.elrefugio@gmail.com', 'encinardemanrre.elrefugio@gmail.com', '19232248', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'grobayodef20@gmail.com', 'grobayodef20@gmail.com', '19300354', 9, 544, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nohelindaromero@yahoo.es', 'nohelindaromero@yahoo.es', '19393908', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlos.vlopez1960@gmail.com', 'carlos.vlopez1960@gmail.com', '19462290', 9, 468, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edil17@gmail.com', 'edil17@gmail.com', '19403044', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'magnasi@gmail.com', 'magnasi@gmail.com', '21223663', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bayuelomartha12@gmail.com', 'bayuelomartha12@gmail.com', '22726945', 2, 127, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'henapao@hotmail.com', 'henapao@hotmail.com', '23229677', 3, 187, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hildalulu@gmail.com', 'hildalulu@gmail.com', '24047149', 4, 299, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mis2tesoritossj.ypgv@gmail.com', 'mis2tesoritossj.ypgv@gmail.com', '26231544', 8, 434, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zoilarosas054@gmail.com', 'zoilarosas054@gmail.com', '26900868', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'negrasoy1958@gmail.com', 'negrasoy1958@gmail.com', '27258066', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'florca33.2015@gmail.com', 'florca33.2015@gmail.com', '27592111', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dulceyjudith@gmail.com', 'dulceyjudith@gmail.com', '28338877', 18, 802, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ortizramirezana@gmail.com', 'ortizramirezana@gmail.com', '28494687', 18, 854, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sandravasquez3000@gmail.com', 'sandravasquez3000@gmail.com', '28496706', 18, 854, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lireti123@gmail.com', 'lireti123@gmail.com', '28688090', 20, 901, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'giraldonelly59@gmail.com', 'giraldonelly59@gmail.com', '29702490', 21, 962, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ana_maya_alegrias@hotmail.com', 'ana_maya_alegrias@hotmail.com', '29703957', 21, 962, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'huellitasdeamorj@gmail.com', 'huellitasdeamorj@gmail.com', '30335854', 5, 316, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bibianapc2012@gmail.com', 'bibianapc2012@gmail.com', '30659697', 8, 417, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'asojupmpal@gmail.com', 'asojupmpal@gmail.com', '31141669', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lucha2824@hotmail.com', 'lucha2824@hotmail.com', '31281329', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marcerolces54@gmail.com', 'marcerolces54@gmail.com', '31468760', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'oliverrodrigu843@gmail.com', 'oliverrodrigu843@gmail.com', '31794475', 21, 942, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nancysanchez50028@yahoo.com', 'nancysanchez50028@yahoo.com', '31847907', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sanchezn205@gmail.com', 'sanchezn205@gmail.com', '31867470', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'blam_68@msn.com', 'blam_68@msn.com', '31981920', 35, 1167, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ginamariagomez16@gmail.com', 'ginamariagomez16@gmail.com', '31983459', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luciadelsocorro@yahoo.es', 'luciadelsocorro@yahoo.es', '32487158', 1, 83, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'noiramargarita.perez@gmail.com', 'noiramargarita.perez@gmail.com', '32632972', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carmendelahoz2211@gmail.com', 'carmendelahoz2211@gmail.com', '32698066', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'candanozarocioisabel@gmail.com', 'candanozarocioisabel@gmail.com', '32864034', 2, 145, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'astwoodalejandra@gmail.com', 'astwoodalejandra@gmail.com', '32892658', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'velascopbe75@gmail.com', 'velascopbe75@gmail.com', '34528950', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anromoca59@hotmail.com', 'anromoca59@hotmail.com', '35314944', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dolisesthergutierrez@gmail.com', 'dolisesthergutierrez@gmail.com', '36577172', 13, 637, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fabiolasilvatorres2013@gmail.com', 'fabiolasilvatorres2013@gmail.com', '37625730', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lejomer@protonmail.com', 'lejomer@protonmail.com', '37864683', 18, 835, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'misabelgarcia.50@hotmail.com', 'misabelgarcia.50@hotmail.com', '37889251', 18, 795, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzmape2023@gmail.com', 'luzmape2023@gmail.com', '39015679', 13, 623, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anabejarano86@hotmail.com', 'anabejarano86@hotmail.com', '39527696', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marthacecilia_1004@hotmail.com', 'marthacecilia_1004@hotmail.com', '40271580', 24, 1015, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jepavi1955@gmail.com', 'jepavi1955@gmail.com', '40330403', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angelamonzon1@hotmail.com', 'angelamonzon1@hotmail.com', '40412088', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ayubibeatriz@gmail.com', 'ayubibeatriz@gmail.com', '40762278', 23, 984, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'elisabeth_csjd_1983@hotmail.com', 'elisabeth_csjd_1983@hotmail.com', '41061044', 35, 1251, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mstella.hs@gmail.com', 'mstella.hs@gmail.com', '41578409', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alisiapv2020@gmail.com', 'alisiapv2020@gmail.com', '42756546', 1, 52, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rmontalvomonroy@gmail.com', 'rmontalvomonroy@gmail.com', '45437089', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kandykandy3235@gmail.com', 'kandykandy3235@gmail.com', '45490473', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'viajacontraveltour@gmail.com', 'viajacontraveltour@gmail.com', '47429117', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'albac.caro@hotmail.com', 'albac.caro@hotmail.com', '49719600', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yelisbuelvaspitre@gmail.com', 'yelisbuelvaspitre@gmail.com', '49732594', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'barbaraortizgutierrez@gmail.com', 'barbaraortizgutierrez@gmail.com', '49746388', 7, 390, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anasabogalhumana@gmail.com', 'anasabogalhumana@gmail.com', '51592305', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'barreroluzangela@gmail.com', 'barreroluzangela@gmail.com', '51644671', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bettypenal@hotmail.com', 'bettypenal@hotmail.com', '51654973', 3, 155, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juordy65@hotmail.com', 'juordy65@hotmail.com', '51771958', 12, 597, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'claudia.cabrera352@gmail.com', 'claudia.cabrera352@gmail.com', '51851371', 24, 1006, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'revistam.buchelly@gmail.com', 'revistam.buchelly@gmail.com', '52040266', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'menchisana@gmail.com', 'menchisana@gmail.com', '52052118', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sanabriasandra27@gmail.com', 'sanabriasandra27@gmail.com', '52219936', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carmenzatorres2018@gmail.com', 'carmenzatorres2018@gmail.com', '52363841', 26, 1051, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'linapaolaalza@gmail.com', 'linapaolaalza@gmail.com', '52703525', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jmontaezt@gmail.com', 'jmontaezt@gmail.com', '52719144', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carolinagarciaquiroga@gmail.com', 'carolinagarciaquiroga@gmail.com', '52786137', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'francyamelia@gmail.com', 'francyamelia@gmail.com', '53009076', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edithparada@gmail.com', 'edithparada@gmail.com', '53124146', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rubygomez1419945@gmail.com', 'rubygomez1419945@gmail.com', '57301696', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'monigarabata@gmail.com', 'monigarabata@gmail.com', '57432690', 35, 1114, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'eangul68@gmail.com', 'eangul68@gmail.com', '59666217', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lopezgladis7459@yahoo.es', 'lopezgladis7459@yahoo.es', '59824956', 31, 1095, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lunaverde19@gmail.com', 'lunaverde19@gmail.com', '63354130', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rochy251093@gmail.com', 'rochy251093@gmail.com', '63366557', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'inesorqui3015@gmail.com', 'inesorqui3015@gmail.com', '63442888', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'chatica2497@gmail.com', 'chatica2497@gmail.com', '63493122', 18, 854, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carmencovo56@gmail.com', 'carmencovo56@gmail.com', '64541994', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanita.j.contreras.s@gmail.com', 'juanita.j.contreras.s@gmail.com', '64891932', 19, 876, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sandralilianacupitra@gmail.com', 'sandralilianacupitra@gmail.com', '65790950', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nanaville662@gmail.com', 'nanaville662@gmail.com', '66782313', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lapita246@gmail.com', 'lapita246@gmail.com', '70036512', 18, 854, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rosalioarroyo7@gmail.com', 'rosalioarroyo7@gmail.com', '70527141', 1, 32, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vivianavargas0902@gmail.com', 'vivianavargas0902@gmail.com', '71876722', 21, 942, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jberdugo.ledger@gmail.com', 'jberdugo.ledger@gmail.com', '72021095', 2, 127, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'isloma66@hotmail.com', 'isloma66@hotmail.com', '72139139', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hayder2782@gmail.com', 'hayder2782@gmail.com', '72295027', 2, 129, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'asopisan@hotmail.es', 'asopisan@hotmail.es', '73095021', 13, 639, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edwinalexanderf452@gmail.com', 'edwinalexanderf452@gmail.com', '74082270', 24, 1001, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hriveraxx@gmail.com', 'hriveraxx@gmail.com', '74300212', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leandroadamemvz@gmail.com', 'leandroadamemvz@gmail.com', '74380429', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alcibiadesrojasmarino@gmail.com', 'alcibiadesrojasmarino@gmail.com', '74846473', 24, 1017, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andresrosascristancho18@gmail.com', 'andresrosascristancho18@gmail.com', '74857324', 24, 1010, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lfenriquez1@misena.edu.co', 'lfenriquez1@misena.edu.co', '76311121', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jagonu4851@gmail.com', 'jagonu4851@gmail.com', '77154851', 7, 384, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edcastellano2015@hotmail.com', 'edcastellano2015@hotmail.com', '77156816', 7, 384, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'emeilhidalgo82@gmail.com', 'emeilhidalgo82@gmail.com', '77176504', 7, 389, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'asodeviaicaesal@gmail.com', 'asodeviaicaesal@gmail.com', '77188432', 8, 436, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carvajal7869@gmail.com', 'carvajal7869@gmail.com', '78691271', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'jahiramp@yahoo.com.mx', 'jahiramp@yahoo.com.mx', '79255137', 9, 454, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'henry.comercio9@gmail.com', 'henry.comercio9@gmail.com', '79272602', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'saxofon_armando@hotmail.com', 'saxofon_armando@hotmail.com', '79303049', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ricardomurcia99@gmail.com', 'ricardomurcia99@gmail.com', '79344098', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jah7565@gmail.com', 'jah7565@gmail.com', '79366154', 9, 439, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marquezabdul512@gmail.com', 'marquezabdul512@gmail.com', '79374339', 7, 379, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'erikwercan@gmail.com', 'erikwercan@gmail.com', '79503065', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'amelo74@misena.edu.co', 'amelo74@misena.edu.co', '79542647', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jabca2020@gmail.com', 'jabca2020@gmail.com', '79804381', 11, 564, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'salamancapena10851225@gmail.com', 'salamancapena10851225@gmail.com', '80387964', 24, 1011, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'potogalindo@hotmail.com', 'potogalindo@hotmail.com', '80471156', 35, 1168, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camilobolanosmunoz@gmail.com', 'camilobolanosmunoz@gmail.com', '83028111', 23, 996, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'danielsalazarmolina@hotmail.com', 'danielsalazarmolina@hotmail.com', '83218275', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'docentealbertomunoz@gmail.com', 'docentealbertomunoz@gmail.com', '84032063', 25, 1019, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pushainasilver76@gmail.com', 'pushainasilver76@gmail.com', '84062614', 25, 1021, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joseluisortegaaponte@gmail.com', 'joseluisortegaaponte@gmail.com', '84450687', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'artunduaga.dio@hotmail.com', 'artunduaga.dio@hotmail.com', '86069945', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlosangulogongora07@gmail.com', 'carlosangulogongora07@gmail.com', '87943201', 14, 706, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'osmarsep49@gmail.com', 'osmarsep49@gmail.com', '88200359', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jramirezfuentes582@gmail.com', 'jramirezfuentes582@gmail.com', '88203918', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'javierojas090@gmail.com', 'javierojas090@gmail.com', '88247045', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlosgomez7812@gmail.com', 'carlosgomez7812@gmail.com', '88278021', 16, 741, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlos.alons29@gmail.com', 'carlos.alons29@gmail.com', '91071444', 18, 795, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luchoarenas57@hotmail.com', 'luchoarenas57@hotmail.com', '91100582', 18, 793, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'chatoski4@gmail.com', 'chatoski4@gmail.com', '91220715', 18, 854, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'arojas136312@yahoo.es', 'arojas136312@yahoo.es', '91227670', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'lisbeliaduran62@gmail.com', 'lisbeliaduran62@gmail.com', '91237627', 18, 802, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'edinsondiosabca@gmail.com', 'edinsondiosabca@gmail.com', '91269014', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'javierperhdz@yahoo.es', 'javierperhdz@yahoo.es', '91421469', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'nelsonarmesto@hotmail.com', 'nelsonarmesto@hotmail.com', '91425199', 13, 637, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaimevillalobos178@gmail.com', 'jaimevillalobos178@gmail.com', '91425765', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisc1548@hotmail.com', 'luisc1548@hotmail.com', '91427083', 18, 781, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jpabonbarajas@gmail.com', 'jpabonbarajas@gmail.com', '91465919', 18, 802, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisga2004@gmail.com', 'luisga2004@gmail.com', '91480881', 18, 795, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tulycysar18@yahoo.es', 'tulycysar18@yahoo.es', '92496207', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mariaauxiliadora24@hotmail.com', 'mariaauxiliadora24@hotmail.com', '92517105', 19, 855, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'singes.h.s.e.q.consultoria@gmail.com', 'singes.h.s.e.q.consultoria@gmail.com', '92556211', 4, 264, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'geovaldysalcedo@hotmail.com', 'geovaldysalcedo@hotmail.com', '92600147', 19, 863, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'acosta_asoabog@hotmail.com', 'acosta_asoabog@hotmail.com', '93151110', 20, 926, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'garzonballesteros@gmail.com', 'garzonballesteros@gmail.com', '93392600', 5, 316, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'tino.murillo@hotmail.com', 'tino.murillo@hotmail.com', '94320421', 11, 556, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rodolfogholguin@hotmail.com', 'rodolfogholguin@hotmail.com', '94499752', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'actorarteaga@hotmail.com', 'actorarteaga@hotmail.com', '94517726', 21, 935, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carmol0374@gmail.com', 'carmol0374@gmail.com', '97446185', 4, 191, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'osckrmb@hotmail.com', 'osckrmb@hotmail.com', '97481163', 31, 1093, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'petrugo1976@hotmail.com', 'petrugo1976@hotmail.com', '98628097', 1, 52, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wvsogamoso@gmail.com', 'wvsogamoso@gmail.com', '1000517089', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ivanna.vargas.22@gmail.com', 'ivanna.vargas.22@gmail.com', '1002030933', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jeankrv51@gmail.com', 'jeankrv51@gmail.com', '1002035009', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zulypin09@gmail.com', 'zulypin09@gmail.com', '1002460114', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ingrid.barrera@uptc.edu.co', 'ingrid.barrera@uptc.edu.co', '1002559724', 24, 1005, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mayragarzon75@gmail.com', 'mayragarzon75@gmail.com', '1003690267', 9, 484, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hamiltoncarpiomembache@gmail.com', 'hamiltoncarpiomembache@gmail.com', '1003787268', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'rodeca1994@gmail.com', 'rodeca1994@gmail.com', '1004122635', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cesara0307@gmail.com', 'cesara0307@gmail.com', '1005237692', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'zharito16@hotmail.com', 'zharito16@hotmail.com', '1005345380', 18, 822, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzadrianamunozviloria@gmail.com', 'luzadrianamunozviloria@gmail.com', '1005581998', 19, 873, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'barrera.luis@correounivalle.edu.co', 'barrera.luis@correounivalle.edu.co', '1005744411', 21, 941, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wildervaldezrosero@gmail.com', 'wildervaldezrosero@gmail.com', '1006787959', 31, 1095, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'k28535516@gmail.com', 'k28535516@gmail.com', '1007190552', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgetami1935@gmail.com', 'jorgetami1935@gmail.com', '1007196941', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'julieth.lamus26042000@gmail.com', 'julieth.lamus26042000@gmail.com', '1007323048', 18, 796, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mayerlinpaz11@gmail.com', 'mayerlinpaz11@gmail.com', '1007440605', 6, 347, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alejandravides03@gmail.com', 'alejandravides03@gmail.com', '1007570617', 13, 623, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'adelarueda1624@gmail.com', 'adelarueda1624@gmail.com', '1007890260', 2, 136, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anderson.rivas@correounivalle.edu.co', 'anderson.rivas@correounivalle.edu.co', '1007954452', 21, 969, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jorgeluisurianaepiayu@gmail.com', 'jorgeluisurianaepiayu@gmail.com', '1010015311', 25, 1026, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'vathoryangel@hotmail.com', 'vathoryangel@hotmail.com', '1014185620', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joseluisbohorquezlopez@hotmail.com', 'joseluisbohorquezlopez@hotmail.com', '1014246607', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juankos94@hotmail.com', 'juankos94@hotmail.com', '1014252994', 4, 193, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'candidaturatefasanchez@gmail.com', 'candidaturatefasanchez@gmail.com', '1017196268', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leyes.higuita@gmail.com', 'leyes.higuita@gmail.com', '1020395220', 1, 73, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanita.arbelaez@hotmail.com', 'juanita.arbelaez@hotmail.com', '1020755697', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aleja.1192@hotmail.com', 'aleja.1192@hotmail.com', '1022944946', 4, 289, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joseterdel@hotmail.com', 'joseterdel@hotmail.com', '1026261774', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leidycardenas852@hotmail.com', 'leidycardenas852@hotmail.com', '1031135811', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'oscwil@gmail.com', 'oscwil@gmail.com', '1032372962', 31, 1088, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joserperio@gmail.com', 'joserperio@gmail.com', '1032388015', 10, 549, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kristiansneyderodriguez@gmail.com', 'kristiansneyderodriguez@gmail.com', '1032499013', 9, 519, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mauriciopalacio68@gmail.com', 'mauriciopalacio68@gmail.com', '1035423924', 28, 1073, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jopse86@gmail.com', 'jopse86@gmail.com', '1037577070', 1, 1, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mosquerajhonjairo02@gmail.com', 'mosquerajhonjairo02@gmail.com', '1038804957', 11, 561, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'maritzacpino@gmail.com', 'maritzacpino@gmail.com', '1046340376', 2, 143, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'pasogutan@gmail.com', 'pasogutan@gmail.com', '1052414937', 4, 222, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camachorafael21@hotmail.com', 'camachorafael21@hotmail.com', '1053331431', 4, 202, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camilito8a236@gmail.com', 'camilito8a236@gmail.com', '1053605284', 4, 256, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jennyalejandratobon84@gmail.com', 'jennyalejandratobon84@gmail.com', '1053786574', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'javier.pira@hotmail.com', 'javier.pira@hotmail.com', '1056482830', 4, 270, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'alonrop@gmail.com', 'alonrop@gmail.com', '1057464051', 4, 266, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camilita625@gmail.com', 'camilita625@gmail.com', '1057584205', 4, 288, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'verdugom786@gmail.com', 'verdugom786@gmail.com', '1058430848', 4, 296, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ericamd.1989@gmail.com', 'ericamd.1989@gmail.com', '1061018117', 6, 359, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'karenyuliethmamian@gmail.com', 'karenyuliethmamian@gmail.com', '1061694594', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carolina-cabanillas@hotmail.com', 'carolina-cabanillas@hotmail.com', '1061700285', 6, 346, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jhonhedertfernandezlopez@gmail.com', 'jhonhedertfernandezlopez@gmail.com', '1061727183', 6, 359, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'hoyosjhon8@gmail.com', 'hoyosjhon8@gmail.com', '1061738075', 6, 342, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andresramirez137@hotmail.com', 'andresramirez137@hotmail.com', '1062297274', 6, 347, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miluchaapenascomienza89@gmail.com', 'miluchaapenascomienza89@gmail.com', '1062807321', 7, 386, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'margaritamariotarra@gmail.com', 'margaritamariotarra@gmail.com', '1063080334', 8, 414, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'gabbymu0496@gmail.com', 'gabbymu0496@gmail.com', '1063817034', 6, 374, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

INSERT IGNORE INTO users (
    tenant_id, name, email, documento_identidad, departamento_id, municipio_id,
    password, activo, created_at, updated_at
) VALUES
(1, 'brayanoteroyen@gmail.com', 'brayanoteroyen@gmail.com', '1067869533', 8, 407, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jjx39062@gmail.com', 'jjx39062@gmail.com', '1075299906', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andresfelipeatiasarias@gmail.com', 'andresfelipeatiasarias@gmail.com', '1075300690', 12, 580, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camandc@hotmail.com', 'camandc@hotmail.com', '1075673929', 9, 548, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'samirderecho@gmail.com', 'samirderecho@gmail.com', '1077200072', 11, 569, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'karina24yeraldin@gmail.com', 'karina24yeraldin@gmail.com', '1077840154', 12, 581, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'csrodmar@gmail.com', 'csrodmar@gmail.com', '1079914488', 13, 631, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angelapatriciazunigaortiz@gmail.com', 'angelapatriciazunigaortiz@gmail.com', '1082214208', 12, 615, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ismargy@gmail.com', 'ismargy@gmail.com', '1082854071', 13, 626, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'montecristo.castilla@gmail.com', 'montecristo.castilla@gmail.com', '1082956969', 13, 616, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sv10901415@gmail.com', 'sv10901415@gmail.com', '1082957247', 13, 630, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andrescutiva24@gmail.com', 'andrescutiva24@gmail.com', '1084330697', 6, 340, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yonatanpantoja181@gmail.com', 'yonatanpantoja181@gmail.com', '1085245481', 14, 654, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'manuelcai456@hotmail.com', 'manuelcai456@hotmail.com', '1085938650', 31, 1100, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'magueguenavas@hotmail.com', 'magueguenavas@hotmail.com', '1088009015', 15, 715, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ingcarloso@gmail.com', 'ingcarloso@gmail.com', '1088279100', 15, 709, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'haver9054@gmail.com', 'haver9054@gmail.com', '1089480903', 14, 678, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'dodyjamirsanguino@gmail.com', 'dodyjamirsanguino@gmail.com', '1090178912', 16, 736, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'bhsotom@gmail.com', 'bhsotom@gmail.com', '1090375884', 35, 1255, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Yinaduque08@gmail.com', 'Yinaduque08@gmail.com', '1093213242', 12, 602, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'kesa281@hotmail.com', 'kesa281@hotmail.com', '1094242400', 13, 640, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'anaestefaniaaepb@gmail.com', 'anaestefaniaaepb@gmail.com', '1094285409', 16, 723, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mileidy.segurahe@gmail.com', 'mileidy.segurahe@gmail.com', '1095806390', 18, 807, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'm.andres.m.castillo30987@gmail.com', 'm.andres.m.castillo30987@gmail.com', '1095911277', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'sharilynvilla@gmail.com', 'sharilynvilla@gmail.com', '1095937587', 18, 810, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaime.tovar09@hotmail.com', 'jaime.tovar09@hotmail.com', '1098620762', 18, 775, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ximenarenas92@gmail.com', 'ximenarenas92@gmail.com', '1098728455', 18, 829, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'julianestevez9333@gmail.com', 'julianestevez9333@gmail.com', '1098738065', 18, 839, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'darlymendozatello@gmail.com', 'darlymendozatello@gmail.com', '1098785847', 18, 823, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juan2170736@correo.uis.edu.co', 'juan2170736@correo.uis.edu.co', '1098818928', 4, 223, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ender_ortega@hotmail.com', 'ender_ortega@hotmail.com', '1100690395', 19, 868, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'xcmiguelcx10@gmail.com', 'xcmiguelcx10@gmail.com', '1100971696', 18, 844, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzdarylr@hotmail.com', 'luzdarylr@hotmail.com', '1102354395', 18, 835, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisssangel25@gmail.com', 'luisssangel25@gmail.com', '1103095124', 19, 862, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yoelpd21@gmail.com', 'yoelpd21@gmail.com', '1103980694', 19, 873, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ferneyfigueroa12@gmail.com', 'ferneyfigueroa12@gmail.com', '1110178586', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'leydijhoanamejiauribe@gmail.com', 'leydijhoanamejiauribe@gmail.com', '1113310293', 21, 967, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luzangelamartinezvillamil@gmail.com', 'luzangelamartinezvillamil@gmail.com', '1113690678', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mari_velez_15@hotmail.com', 'mari_velez_15@hotmail.com', '1114730792', 21, 950, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marisolsale11@gmail.com', 'marisolsale11@gmail.com', '1114821520', 21, 947, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'luisatr2017@gmail.com', 'luisatr2017@gmail.com', '1114898492', 21, 949, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'yuliana-2102@hotmail.com', 'yuliana-2102@hotmail.com', '1115065401', 21, 942, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fernandezmaikel8@gmail.com', 'fernandezmaikel8@gmail.com', '1115722888', 18, 841, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'riveritalmrr@gmail.com', 'riveritalmrr@gmail.com', '1115910132', 24, 1016, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'joroscomayan@gmail.com', 'joroscomayan@gmail.com', '1115911690', 24, 1010, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jaivercabrera30@gmail.com', 'jaivercabrera30@gmail.com', '1117807329', 23, 992, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angelhomero2017@gmail.com', 'angelhomero2017@gmail.com', '1118167933', 24, 1000, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jadrianarodriguez9@gmail.com', 'jadrianarodriguez9@gmail.com', '1118288224', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'andressolano645@gmail.com', 'andressolano645@gmail.com', '1118306157', 6, 342, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juanchopoloe@gmail.com', 'juanchopoloe@gmail.com', '1121926956', 26, 1030, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'cpdj20695@gmail.com', 'cpdj20695@gmail.com', '1129504155', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'aherrerat45@gmail.com', 'aherrerat45@gmail.com', '1129570063', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'camila.palacio.m18@gmail.com', 'camila.palacio.m18@gmail.com', '1140824841', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jesusferrersar@gmail.com', 'jesusferrersar@gmail.com', '1140854680', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ponlaw301@gmail.com', 'ponlaw301@gmail.com', '1140864579', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jennyfuentesgrijalba@gmail.com', 'jennyfuentesgrijalba@gmail.com', '1144044846', 6, 351, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'wilmerjuniorg@gmail.com', 'wilmerjuniorg@gmail.com', '1152939974', 13, 645, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'omar.acendra45@hotmail.com', 'omar.acendra45@hotmail.com', '1152943557', 13, 645, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'borreroov7@gmail.com', 'borreroov7@gmail.com', '1193292469', 21, 975, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'everthleon1018@gmail.com', 'everthleon1018@gmail.com', '1193525072', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jmilena769@gmail.com', 'jmilena769@gmail.com', '1193545701', 2, 126, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'valentina0985@hotmail.com', 'valentina0985@hotmail.com', '1193547282', 21, 957, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'madelpi57@gmail.com', 'madelpi57@gmail.com', '30286734', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Brayanvergara17185@gmail.com', 'Brayanvergara17185@gmail.com', '10538672798', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Vcardona515@gmail.com', 'Vcardona515@gmail.com', '10252507', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'jurepomor@hotmail.com', 'jurepomor@hotmail.com', '10251395', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ugonzalez@misena.edu.com', 'ugonzalez@misena.edu.com', '10237467', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'Carlosandrescruzdelgadillo@gmail.com', 'Carlosandrescruzdelgadillo@gmail.com', '75089269', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'solocuandoelultimoarbolsea@gmail.com', 'solocuandoelultimoarbolsea@gmail.com', '80097847', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'marthatobon7@gmail.com', 'marthatobon7@gmail.com', '25379681', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'olgatamayo762@gmail.com', 'olgatamayo762@gmail.com', '30300011', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'mencharuiz89@gmail.com', 'mencharuiz89@gmail.com', '30312398', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'soto.segra2016@gmail.com', 'soto.segra2016@gmail.com', '1054858152', 5, 314, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'martac.caba@gmail.com', 'martac.caba@gmail.com', '36550593', 35, 1153, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'fanylealquiros@gmail.com', 'fanylealquiros@gmail.com', '63347250', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'carlosfbaron@gmail.com', 'carlosfbaron@gmail.com', '13437734', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ligiamonbe@hotmail.com', 'ligiamonbe@hotmail.com', '41578171', 1, 13, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'acamargo@corefex.net', 'acamargo@corefex.net', '19462416', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'juliethsanchezs.10@hotmail.com', 'juliethsanchezs.10@hotmail.com', '1077862938', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'delfincarv@hotmail.com', 'delfincarv@hotmail.com', '12189313', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'ancabeju@hotmail.com', 'ancabeju@hotmail.com', '41943618', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'evelioperezgalvis@gmail.com', 'evelioperezgalvis@gmail.com', '7520713', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'japalacios@uqvirtual.edu.co', 'japalacios@uqvirtual.edu.co', '1099683329', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'miguelangelgrisales143@gmail.com', 'miguelangelgrisales143@gmail.com', '1094949626', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'locayo1334@gmail.com', 'locayo1334@gmail.com', '1104697385', 12, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'deisonzl53@gmail.com', 'deisonzl53@gmail.com', '12021810', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'angela123acevedo@gmail.com', 'angela123acevedo@gmail.com', '1030573131', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'deisonzll30@gmail.com', 'deisonzll30@gmail.com', '12021810', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW()),
(1, 'osbaylopez20@gmail.com', 'osbaylopez20@gmail.com', '71411247', NULL, NULL, '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, NOW(), NOW());

-- Actualizar usuarios existentes usando email como clave
-- Este UPDATE manejará conflictos de cedula duplicada
UPDATE users SET name = 'aflandorffer@gmail.com', documento_identidad = '95990', departamento_id = 1, municipio_id = 52, updated_at = NOW() WHERE email = 'aflandorffer@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fer126721@gmail.com', documento_identidad = '413376', departamento_id = 9, municipio_id = 529, updated_at = NOW() WHERE email = 'fer126721@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'villanariel73@gmail.com', documento_identidad = '785371', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'villanariel73@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jaimemontoya612@gmail.com', documento_identidad = '2773757', departamento_id = 1, municipio_id = 37, updated_at = NOW() WHERE email = 'jaimemontoya612@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'naisercu@hotmail.com', documento_identidad = '3017841', departamento_id = 26, municipio_id = 1062, updated_at = NOW() WHERE email = 'naisercu@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'miguelernestorg@hotmail.com', documento_identidad = '3028913', departamento_id = 9, municipio_id = 465, updated_at = NOW() WHERE email = 'miguelernestorg@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'crearsiglo21@gmail.com', documento_identidad = '3128990', departamento_id = 9, municipio_id = 500, updated_at = NOW() WHERE email = 'crearsiglo21@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'raulandresmedinam@gmail.com', documento_identidad = '3171475', departamento_id = 9, municipio_id = 517, updated_at = NOW() WHERE email = 'raulandresmedinam@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Carloshuertas12@yahoo.es', documento_identidad = '3249034', departamento_id = 9, municipio_id = 487, updated_at = NOW() WHERE email = 'Carloshuertas12@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'nachoestrada0509@gmail.com', documento_identidad = '3365387', departamento_id = 1, municipio_id = 54, updated_at = NOW() WHERE email = 'nachoestrada0509@gmail.com' AND tenant_id = 1;
UPDATE users SET name = '1239@gmail.com', documento_identidad = '3586622', departamento_id = 1, municipio_id = 30, updated_at = NOW() WHERE email = '1239@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'escoalvaro27@gmail.com', documento_identidad = '3718866', departamento_id = 2, municipio_id = 127, updated_at = NOW() WHERE email = 'escoalvaro27@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'delwinvg@hotmail.com', documento_identidad = '3731139', departamento_id = 7, municipio_id = 406, updated_at = NOW() WHERE email = 'delwinvg@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'azocalure@gmail.com', documento_identidad = '3738025', departamento_id = 2, municipio_id = 136, updated_at = NOW() WHERE email = 'azocalure@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'docente.gustavolara@gmail.com', documento_identidad = '3741306', departamento_id = 2, municipio_id = 137, updated_at = NOW() WHERE email = 'docente.gustavolara@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jose-orozco815@hotmail.com', documento_identidad = '3762279', departamento_id = 2, municipio_id = 138, updated_at = NOW() WHERE email = 'jose-orozco815@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pedrojjuliobeltran@gmail.com', documento_identidad = '3771685', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'pedrojjuliobeltran@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorge_abo62@hotmail.com', documento_identidad = '3815119', departamento_id = 3, municipio_id = 154, updated_at = NOW() WHERE email = 'jorge_abo62@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'acotera01@hotmail.com', documento_identidad = '3824734', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'acotera01@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eduardoatencia@gmail.com', documento_identidad = '3843914', departamento_id = 19, municipio_id = 868, updated_at = NOW() WHERE email = 'eduardoatencia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carcamovegaorlando@gmail.com', documento_identidad = '3876860', departamento_id = 3, municipio_id = 161, updated_at = NOW() WHERE email = 'carcamovegaorlando@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leon25xlx@hotmail.com', documento_identidad = '3961723', departamento_id = 3, municipio_id = 178, updated_at = NOW() WHERE email = 'leon25xlx@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'etnicocaribe@gmail.com', documento_identidad = '4008681', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'etnicocaribe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'boy.boy.humana@gmail.com', documento_identidad = '4061617', departamento_id = 4, municipio_id = 199, updated_at = NOW() WHERE email = 'boy.boy.humana@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ingluisbaezceron@gmail.com', documento_identidad = '4079329', departamento_id = 4, municipio_id = 218, updated_at = NOW() WHERE email = 'ingluisbaezceron@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'escobarildebrando@gmail.com', documento_identidad = '4087424', departamento_id = 24, municipio_id = 1002, updated_at = NOW() WHERE email = 'escobarildebrando@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sacama1504@gmail.com', documento_identidad = '4104064', departamento_id = 24, municipio_id = 1013, updated_at = NOW() WHERE email = 'sacama1504@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pizreyes@gmail.com', documento_identidad = '4121497', departamento_id = 4, municipio_id = 226, updated_at = NOW() WHERE email = 'pizreyes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'benjobull@gmail.com', documento_identidad = '4130046', departamento_id = 4, municipio_id = 274, updated_at = NOW() WHERE email = 'benjobull@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vicentelopezraba@gmail.com', documento_identidad = '4146724', departamento_id = 4, municipio_id = 241, updated_at = NOW() WHERE email = 'vicentelopezraba@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josevelascolopez@hotmail.com', documento_identidad = '4148151', departamento_id = 4, municipio_id = 312, updated_at = NOW() WHERE email = 'josevelascolopez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hurtadoperezcarlos9@gmail.com', documento_identidad = '4190429', departamento_id = 24, municipio_id = 1018, updated_at = NOW() WHERE email = 'hurtadoperezcarlos9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisurbanom69@gmail.com', documento_identidad = '4243804', departamento_id = 4, municipio_id = 247, updated_at = NOW() WHERE email = 'luisurbanom69@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vitebenitezortiz26@gmail.com', documento_identidad = '4270283', departamento_id = 24, municipio_id = 1015, updated_at = NOW() WHERE email = 'vitebenitezortiz26@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'agro20.tib@gmail.com', documento_identidad = '4275384', departamento_id = 4, municipio_id = 298, updated_at = NOW() WHERE email = 'agro20.tib@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'victormanuelmartinezreina@hotmail.es', documento_identidad = '4292327', departamento_id = 24, municipio_id = 1001, updated_at = NOW() WHERE email = 'victormanuelmartinezreina@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'abogadonelsonj@gmail.com', documento_identidad = '4380844', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'abogadonelsonj@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jramirezx9@gmail.com', documento_identidad = '4406551', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'jramirezx9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cg0408733@gmail.com', documento_identidad = '4442407', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'cg0408733@gmail.com' AND tenant_id = 1;
UPDATE users SET name = '1357dwke@gmail.com', documento_identidad = '4580032', departamento_id = 15, municipio_id = 716, updated_at = NOW() WHERE email = '1357dwke@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jairoalbertobolanosmamian@gmail.com', documento_identidad = '4697357', departamento_id = 6, municipio_id = 357, updated_at = NOW() WHERE email = 'jairoalbertobolanosmamian@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edadalca@hotmail.com', documento_identidad = '4779827', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'edadalca@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wilbero95@gmail.com', documento_identidad = '4782039', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'wilbero95@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nisegu4813@gmail.com', documento_identidad = '4813640', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'nisegu4813@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgecaicedojordan4@gmail.com', documento_identidad = '4831098', departamento_id = 11, municipio_id = 564, updated_at = NOW() WHERE email = 'jorgecaicedojordan4@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elchocopidio@gmail.com', documento_identidad = '4831389', departamento_id = 11, municipio_id = 568, updated_at = NOW() WHERE email = 'elchocopidio@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sistocortes123@gmail.com', documento_identidad = '4852302', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'sistocortes123@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yamilantonio4861@gmail.com', documento_identidad = '4861861', departamento_id = 11, municipio_id = 577, updated_at = NOW() WHERE email = 'yamilantonio4861@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jairoivantrujillo20@gmail.com', documento_identidad = '4920045', departamento_id = 12, municipio_id = 597, updated_at = NOW() WHERE email = 'jairoivantrujillo20@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arperque610@yahoo.es', documento_identidad = '4949440', departamento_id = 12, municipio_id = 614, updated_at = NOW() WHERE email = 'arperque610@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'luanvequi01@gmail.com', documento_identidad = '5030611', departamento_id = 7, municipio_id = 393, updated_at = NOW() WHERE email = 'luanvequi01@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luiskarloslopez8@gmail.com', documento_identidad = '5092096', departamento_id = 7, municipio_id = 397, updated_at = NOW() WHERE email = 'luiskarloslopez8@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Adalberto0226@hotmail.com', documento_identidad = '5095096', departamento_id = 13, municipio_id = 636, updated_at = NOW() WHERE email = 'Adalberto0226@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juliustar2000@gmail.com', documento_identidad = '5269797', departamento_id = 14, municipio_id = 673, updated_at = NOW() WHERE email = 'juliustar2000@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anyilsandybetsanchezmora@gmail.com', documento_identidad = '5346851', departamento_id = 13, municipio_id = 623, updated_at = NOW() WHERE email = 'anyilsandybetsanchezmora@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ricardoarboleda391@gmail.com', documento_identidad = '5364793', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'ricardoarboleda391@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'saracruz756@hotmail.com', documento_identidad = '5453438', departamento_id = 16, municipio_id = 743, updated_at = NOW() WHERE email = 'saracruz756@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'comunalcarrascal@gmail.com', documento_identidad = '5470525', departamento_id = 16, municipio_id = 758, updated_at = NOW() WHERE email = 'comunalcarrascal@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'segundorgonzalezc@gmail.com', documento_identidad = '5577360', departamento_id = 18, municipio_id = 779, updated_at = NOW() WHERE email = 'segundorgonzalezc@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jotaoscar1916@yahoo.es', documento_identidad = '5577426', departamento_id = 18, municipio_id = 779, updated_at = NOW() WHERE email = 'jotaoscar1916@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'senum18@yahoo.es', documento_identidad = '5743555', departamento_id = 18, municipio_id = 796, updated_at = NOW() WHERE email = 'senum18@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'aliplatac@gmail.com', documento_identidad = '5795834', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'aliplatac@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alderecho@hotmail.com', documento_identidad = '5825354', departamento_id = 20, municipio_id = 919, updated_at = NOW() WHERE email = 'alderecho@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'piarfat@gmail.com', documento_identidad = '5884258', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'piarfat@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juceoru13@gmail.com', documento_identidad = '5906589', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'juceoru13@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pascualguerrero38@gmail.com', documento_identidad = '6083179', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'pascualguerrero38@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'oscar.molano236@gmail.com', documento_identidad = '6254321', departamento_id = 6, municipio_id = 357, updated_at = NOW() WHERE email = 'oscar.molano236@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlos1954londono@gmail.com', documento_identidad = '6264638', departamento_id = 21, municipio_id = 951, updated_at = NOW() WHERE email = 'carlos1954londono@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hoperadomari@gmail.com', documento_identidad = '6301599', departamento_id = 21, municipio_id = 949, updated_at = NOW() WHERE email = 'hoperadomari@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'adaman07@hotmail.com', documento_identidad = '6314201', departamento_id = 21, municipio_id = 949, updated_at = NOW() WHERE email = 'adaman07@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'emirosaavedra@hotmail.com', documento_identidad = '6316164', departamento_id = 21, municipio_id = 954, updated_at = NOW() WHERE email = 'emirosaavedra@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'xamantadiaz9@gmail.com', documento_identidad = '6531921', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'xamantadiaz9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'calveiro1969@gmail.com', documento_identidad = '6537032', departamento_id = 21, municipio_id = 974, updated_at = NOW() WHERE email = 'calveiro1969@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'euquico@gmail.com', documento_identidad = '6754062', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'euquico@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gustavoramirezr1962@gmail.com', documento_identidad = '6773706', departamento_id = 4, municipio_id = 244, updated_at = NOW() WHERE email = 'gustavoramirezr1962@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgebettin@gmail.com', documento_identidad = '6810358', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'jorgebettin@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'narvaezvergaragabriel@gmail.com', documento_identidad = '6810478', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'narvaezvergaragabriel@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'linomurillo230955@gmail.com', documento_identidad = '6816667', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'linomurillo230955@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tomas.barreto.rincon@gmail.com', documento_identidad = '6818945', departamento_id = 19, municipio_id = 878, updated_at = NOW() WHERE email = 'tomas.barreto.rincon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'salvadorromerocoley@hotmail.com', documento_identidad = '6819085', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'salvadorromerocoley@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'abogadolucianoramirez@gmail.com', documento_identidad = '6820004', departamento_id = 3, municipio_id = 150, updated_at = NOW() WHERE email = 'abogadolucianoramirez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rafamarrugo@hotmail.com', documento_identidad = '6820791', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'rafamarrugo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'robertoyances@hotmail.es', documento_identidad = '6872858', departamento_id = 8, municipio_id = 430, updated_at = NOW() WHERE email = 'robertoyances@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'duqueedil@gmail.com', documento_identidad = '6883138', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'duqueedil@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'felixbaza@gmail.com', documento_identidad = '6894953', departamento_id = 1, municipio_id = 25, updated_at = NOW() WHERE email = 'felixbaza@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eljprivera@gmail.com', documento_identidad = '7186810', departamento_id = 4, municipio_id = 191, updated_at = NOW() WHERE email = 'eljprivera@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alenfega@gmail.com', documento_identidad = '7215746', departamento_id = 24, municipio_id = 1005, updated_at = NOW() WHERE email = 'alenfega@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nesmar123@hotmail.com', documento_identidad = '7220571', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'nesmar123@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'virila2001@yahoo.es', documento_identidad = '7221400', departamento_id = 24, municipio_id = 1013, updated_at = NOW() WHERE email = 'virila2001@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'nicolasmaestre@yahoo.com', documento_identidad = '7224754', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'nicolasmaestre@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'adelfo1955@hotmail.com', documento_identidad = '7230237', departamento_id = 24, municipio_id = 1018, updated_at = NOW() WHERE email = 'adelfo1955@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maurofabiansalazar@gmail.com', documento_identidad = '7253321', departamento_id = 4, municipio_id = 264, updated_at = NOW() WHERE email = 'maurofabiansalazar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'franchescoparra@hotmail.com', documento_identidad = '7309843', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'franchescoparra@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'salomedavid25@hotmail.com', documento_identidad = '7361683', departamento_id = 24, municipio_id = 1009, updated_at = NOW() WHERE email = 'salomedavid25@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'farfanjorge42@gmail.com', documento_identidad = '7363845', departamento_id = 24, municipio_id = 1017, updated_at = NOW() WHERE email = 'farfanjorge42@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'amauryayazovelasquez@gmail.com', documento_identidad = '7381151', departamento_id = 8, municipio_id = 433, updated_at = NOW() WHERE email = 'amauryayazovelasquez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jesushernandez2326@hotmail.com', documento_identidad = '7381600', departamento_id = 8, municipio_id = 433, updated_at = NOW() WHERE email = 'jesushernandez2326@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hermeslara03@gmail.com', documento_identidad = '7450392', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'hermeslara03@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'razon.derecho@gmail.com', documento_identidad = '7453399', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'razon.derecho@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lemarz@gmail.com', documento_identidad = '7453524', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'lemarz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'robertolugo7@yahoo.com', documento_identidad = '7471307', departamento_id = 8, municipio_id = 417, updated_at = NOW() WHERE email = 'robertolugo7@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'carince27@hotmail.com', documento_identidad = '7473513', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'carince27@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joselenaro@gmail.com', documento_identidad = '7490123', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'joselenaro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'augustomisse72@gmail.com', documento_identidad = '7559102', departamento_id = 17, municipio_id = 763, updated_at = NOW() WHERE email = 'augustomisse72@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lorenzoperezgonzalez@hotmail.com', documento_identidad = '7591515', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'lorenzoperezgonzalez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pedropertuz96@yahoo.es', documento_identidad = '7592814', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'pedropertuz96@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'rhonald29@hotmail.com', documento_identidad = '7595463', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'rhonald29@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anovercrespo@gmail.com', documento_identidad = '7596891', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'anovercrespo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luiscastellararrieta26@gmail.com', documento_identidad = '7929478', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'luiscastellararrieta26@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'acelcontracion@gimail.com', documento_identidad = '8001037', departamento_id = 18, municipio_id = 793, updated_at = NOW() WHERE email = 'acelcontratacion@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'esgohe@gmail.com', documento_identidad = '8028419', departamento_id = 1, municipio_id = 69, updated_at = NOW() WHERE email = 'esgohe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Camilausuga0305@gmail.com', documento_identidad = '8075124', departamento_id = 1, municipio_id = 14, updated_at = NOW() WHERE email = 'Camilausuga0305@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hilariogonzalezteheran1@gmail.com', documento_identidad = '8172450', departamento_id = 1, municipio_id = 97, updated_at = NOW() WHERE email = 'hilariogonzalezteheran1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanpatino2705@gmail.com', documento_identidad = '8205264', departamento_id = 1, municipio_id = 67, updated_at = NOW() WHERE email = 'juanpatino2705@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'widove27@hotmail.com', documento_identidad = '8323561', departamento_id = 1, municipio_id = 13, updated_at = NOW() WHERE email = 'widove27@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alonrramirezp@gmail.com', documento_identidad = '8359014', departamento_id = 35, municipio_id = 1149, updated_at = NOW() WHERE email = 'alonrramirezp@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'waltovelez@gmail.com', documento_identidad = '8403316', departamento_id = 1, municipio_id = 14, updated_at = NOW() WHERE email = 'waltovelez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zapatagil.ethelberto@gmail.com', documento_identidad = '8425998', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'zapatagil.ethelberto@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hernangomez0456@gmail.com', documento_identidad = '8470609', departamento_id = 25, municipio_id = 1021, updated_at = NOW() WHERE email = 'hernangomez0456@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kasolpadyd@outlook.com', documento_identidad = '8632596', departamento_id = 2, municipio_id = 142, updated_at = NOW() WHERE email = 'kasolpadyd@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'luruakhumana@gmail.com', documento_identidad = '8635797', departamento_id = 2, municipio_id = 132, updated_at = NOW() WHERE email = 'luruakhumana@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'adalbertyguerrero@hotmail.com', documento_identidad = '8675297', departamento_id = 2, municipio_id = 129, updated_at = NOW() WHERE email = 'adalbertyguerrero@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aasga@hotmail.com', documento_identidad = '8680593', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'aasga@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zurdo.225@hotmail.com', documento_identidad = '8685323', departamento_id = 13, municipio_id = 630, updated_at = NOW() WHERE email = 'zurdo.225@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'asopiten.ascopi@gmail.com', documento_identidad = '8685427', departamento_id = 18, municipio_id = 835, updated_at = NOW() WHERE email = 'asopiten.ascopi@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'oscarherreradelgado@hotmail.es', documento_identidad = '8707222', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'oscarherreradelgado@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'dgiraldo_99@yahoo.vom', documento_identidad = '8713759', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'dgiraldo_99@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'wildernavarroquintero@gmail.com', documento_identidad = '8729274', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'wildernavarroquintero@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisescorcialeon2023@hotmail.com', documento_identidad = '8742348', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'luisescorcialeon2023@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fernandoferrer1956@gmail.com', documento_identidad = '8750444', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'fernandoferrer1956@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arceco0719@hotmail.com', documento_identidad = '8753514', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'arceco0719@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejorestrepot@gmail.com', documento_identidad = '8755472', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'alejorestrepot@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mauri71101@hotmail.com', documento_identidad = '8776256', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'mauri71101@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alfonsohu49@yahoo.com', documento_identidad = '9070385', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'alfonsohu49@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'omeralfonso@hotmail.es', documento_identidad = '9077590', departamento_id = 3, municipio_id = 185, updated_at = NOW() WHERE email = 'omeralfonso@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'livdan0607@gmail.com', documento_identidad = '9084334', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'livdan0607@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jeracero@gmail.com', documento_identidad = '9089933', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'jeracero@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'uwaquijote@gmail.com', documento_identidad = '9094503', departamento_id = 16, municipio_id = 748, updated_at = NOW() WHERE email = 'uwaquijote@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vergara.maximo@hotmail.com', documento_identidad = '9137468', departamento_id = 3, municipio_id = 161, updated_at = NOW() WHERE email = 'vergara.maximo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dcr00123@gmail.com', documento_identidad = '9139370', departamento_id = 13, municipio_id = 640, updated_at = NOW() WHERE email = 'dcr00123@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'narciso.torresperez@gmail.com', documento_identidad = '9152765', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'narciso.torresperez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'javierfaciolince@hotmail.com', documento_identidad = '9262374', departamento_id = 3, municipio_id = 166, updated_at = NOW() WHERE email = 'javierfaciolince@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Carlosmoralesfarelo14@gmail.com', documento_identidad = '9271605', departamento_id = 13, municipio_id = 638, updated_at = NOW() WHERE email = 'Carlosmoralesfarelo14@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisponce_1010@hotmail.com', documento_identidad = '9298605', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'luisponce_1010@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vpflorez21@hotmail.com', documento_identidad = '9310204', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'vpflorez21@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorozaga@yahoo.es', documento_identidad = '9313566', departamento_id = 1, municipio_id = 97, updated_at = NOW() WHERE email = 'jorozaga@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'asoafroelroble5@gmail.com', documento_identidad = '9313876', departamento_id = 19, municipio_id = 868, updated_at = NOW() WHERE email = 'asoafroelroble5@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wilcame05864@gmail.com', documento_identidad = '9314088', departamento_id = 8, municipio_id = 417, updated_at = NOW() WHERE email = 'wilcame05864@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'olmosjoel@gmail.com', documento_identidad = '9431616', departamento_id = 24, municipio_id = 1003, updated_at = NOW() WHERE email = 'olmosjoel@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'omar.uscategui@gmail.com', documento_identidad = '9514696', departamento_id = 4, municipio_id = 195, updated_at = NOW() WHERE email = 'omar.uscategui@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bayonafdo@hotmail.com', documento_identidad = '9519084', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'bayonafdo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'araquerincon@gmail.com', documento_identidad = '9523732', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'araquerincon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hcastro311@gmail.com', documento_identidad = '9525311', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'hcastro311@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elfardaniloleal@gmail.com', documento_identidad = '9653244', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'elfardaniloleal@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'frescosiempre1@gmail.com', documento_identidad = '10002299', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'frescosiempre1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wilsonpadilla55@hotmail.com', documento_identidad = '10088732', departamento_id = 7, municipio_id = 390, updated_at = NOW() WHERE email = 'wilsonpadilla55@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dhocvel331@gmail.com', documento_identidad = '10101485', departamento_id = 28, municipio_id = 1074, updated_at = NOW() WHERE email = 'dhocvel331@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lucho6219@yahoo.es', documento_identidad = '10106779', departamento_id = 21, municipio_id = 942, updated_at = NOW() WHERE email = 'lucho6219@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'jucdma@yahoo.com', documento_identidad = '10109738', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'jucdma@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'victorsuarez2030@gmail.com', documento_identidad = '10139756', departamento_id = 15, municipio_id = 715, updated_at = NOW() WHERE email = 'victorsuarez2030@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fernandogarciacaceres@gmail.com', documento_identidad = '10166186', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'fernandogarciacaceres@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rafaeleduardobetancourtarias@gmail.com', documento_identidad = '10254745', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'rafaeleduardobetancourtarias@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'miguelangelsoto724@gmail.com', documento_identidad = '10257346', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'miguelangelsoto724@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgegiraldo341@hotmail.com', documento_identidad = '10258678', updated_at = NOW() WHERE email = 'jorgegiraldo341@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hugofnotificaciones@gmail.com', documento_identidad = '10289753', departamento_id = 6, municipio_id = 359, updated_at = NOW() WHERE email = 'hugofnotificaciones@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leonardoillera@gmail.com', documento_identidad = '10299202', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'leonardoillera@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'haroldhruiz2020@gmail.com', documento_identidad = '10325217', departamento_id = 6, municipio_id = 344, updated_at = NOW() WHERE email = 'haroldhruiz2020@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'orlandorolon30@gmail.com', documento_identidad = '10531020', departamento_id = 16, municipio_id = 762, updated_at = NOW() WHERE email = 'orlandorolon30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hugoh290silva@gmail.com', documento_identidad = '10531139', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'hugoh290silva@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'roquerealpe79@gemail.com', documento_identidad = '10531477', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'roquerealpe79@gemail.com' AND tenant_id = 1;
UPDATE users SET name = 'adalbertonarvaez77@gmail.com', documento_identidad = '10535446', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'adalbertonarvaez77@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jairoe43@gmail.com', documento_identidad = '10536815', departamento_id = 14, municipio_id = 642, updated_at = NOW() WHERE email = 'jairoe43@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hugodario2@hotmail.com', documento_identidad = '10546998', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'hugodario2@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgeortizperez1010@gmail.com', documento_identidad = '10556900', departamento_id = 6, municipio_id = 367, updated_at = NOW() WHERE email = 'jorgeortizperez1010@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fjog6018@gmail.com', documento_identidad = '10880550', departamento_id = 19, municipio_id = 881, updated_at = NOW() WHERE email = 'fjog6018@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisalfonsoballe9@gmail.com', documento_identidad = '10897247', departamento_id = 8, municipio_id = 436, updated_at = NOW() WHERE email = 'luisalfonsoballe9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Sammyflower1010@gmail.com', documento_identidad = '10932778', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'Sammyflower1010@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'enriquenavarrodiaz@hotmail.com', documento_identidad = '10937256', departamento_id = 8, municipio_id = 421, updated_at = NOW() WHERE email = 'enriquenavarrodiaz@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'germanlovi@hotmail.com', documento_identidad = '10995787', departamento_id = 1, municipio_id = 13, updated_at = NOW() WHERE email = 'germanlovi@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Valentin_delabarrera@yahoo.es', documento_identidad = '11170765', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'Valentin_delabarrera@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'fransuacolombia@hotmail.com', documento_identidad = '11228358', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'fransuacolombia@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lopezdelgadoomar@gmail.com', documento_identidad = '11341360', departamento_id = 9, municipio_id = 548, updated_at = NOW() WHERE email = 'lopezdelgadoomar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edmurisan@gmail.com', documento_identidad = '11374289', departamento_id = 22, municipio_id = 979, updated_at = NOW() WHERE email = 'edmurisan@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edgkabezas@gmail.com', documento_identidad = '11428829', departamento_id = 9, municipio_id = 522, updated_at = NOW() WHERE email = 'edgkabezas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'martinpalacios2011@gmail.com', documento_identidad = '11429846', departamento_id = 9, municipio_id = 451, updated_at = NOW() WHERE email = 'martinpalacios2011@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'largacha2181@outlook.com', documento_identidad = '11620727', departamento_id = 11, municipio_id = 556, updated_at = NOW() WHERE email = 'largacha2181@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'joserutiliorivas1@gmail.com', documento_identidad = '11636803', departamento_id = 11, municipio_id = 564, updated_at = NOW() WHERE email = 'joserutiliorivas1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juancquininez12@gmail.com', documento_identidad = '11705813', departamento_id = 11, municipio_id = 568, updated_at = NOW() WHERE email = 'juancquininez12@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rocome1@hotmail.com', documento_identidad = '11787600', departamento_id = 11, municipio_id = 550, updated_at = NOW() WHERE email = 'rocome1@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'GIOMECO20@GMAIL.COM', documento_identidad = '11791100', departamento_id = 11, municipio_id = 550, updated_at = NOW() WHERE email = 'GIOMECO20@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'eugeniomosquerah11@gmail.com', documento_identidad = '11795121', departamento_id = 11, municipio_id = 550, updated_at = NOW() WHERE email = 'eugeniomosquerah11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hemersonmayo1970@hotmail.com', documento_identidad = '11798856', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'hemersonmayo1970@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'triunfadorexitoso@outlook.com', documento_identidad = '11801285', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'triunfadorexitoso@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'valenciajuridica@hotmail.com', documento_identidad = '11803346', departamento_id = 1, municipio_id = 43, updated_at = NOW() WHERE email = 'valenciajuridica@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hectoremiliomosquera6@gmail.com', documento_identidad = '11936369', departamento_id = 11, municipio_id = 559, updated_at = NOW() WHERE email = 'hectoremiliomosquera6@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'orlanloji@gmail.com', documento_identidad = '12112652', departamento_id = 12, municipio_id = 603, updated_at = NOW() WHERE email = 'orlanloji@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rojassanchez.efren1@gmail.com', documento_identidad = '12132182', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'rojassanchez.efren1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fredyossa@gmail.com', documento_identidad = '12138295', departamento_id = 21, municipio_id = 968, updated_at = NOW() WHERE email = 'fredyossa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'parraindio@hotmail.es', documento_identidad = '12188485', departamento_id = 12, municipio_id = 1306, updated_at = NOW() WHERE email = 'parraindio@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'campopedrito2018@gmail.com', documento_identidad = '12194561', departamento_id = 12, municipio_id = 581, updated_at = NOW() WHERE email = 'campopedrito2018@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'abogadojpe@gmail.com', documento_identidad = '12206407', departamento_id = 12, municipio_id = 587, updated_at = NOW() WHERE email = 'abogadojpe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'olmertovar@yahoo.com', documento_identidad = '12233322', departamento_id = 12, municipio_id = 600, updated_at = NOW() WHERE email = 'olmertovar@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'JOLUPEDU82@GMAIL.COM', documento_identidad = '12504001', departamento_id = 7, municipio_id = 399, updated_at = NOW() WHERE email = 'JOLUPEDU82@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'anyijulianyjuliana03@gmail.com', documento_identidad = '12523013', departamento_id = 7, municipio_id = 396, updated_at = NOW() WHERE email = 'anyijulianyjuliana03@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mindiolareinaj7456@outlook.es', documento_identidad = '12534374', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'mindiolareinaj7456@outlook.es' AND tenant_id = 1;
UPDATE users SET name = 'manuelpegonzalez@gmail.com', documento_identidad = '12556441', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'manuelpegonzalez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'enriquemanuel.jimenez@gmail.com', documento_identidad = '12581995', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'enriquemanuel.jimenez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'samflomar03@gmail.com', documento_identidad = '12583841', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'samflomar03@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'martinroma22@hotmail.com', documento_identidad = '12602770', departamento_id = 13, municipio_id = 637, updated_at = NOW() WHERE email = 'martinroma22@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leanderdejesus03@gmail.com', documento_identidad = '12636046', departamento_id = 8, municipio_id = 420, updated_at = NOW() WHERE email = 'leanderdejesus03@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bonethm20@gmail.com', documento_identidad = '12722199', departamento_id = 7, municipio_id = 398, updated_at = NOW() WHERE email = 'bonethm20@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'roymana8@gmail.com', documento_identidad = '12723255', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'roymana8@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'reirodriguez57@gmail.com', documento_identidad = '12723441', departamento_id = 13, municipio_id = 623, updated_at = NOW() WHERE email = 'reirodriguez57@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alvarovalenciatenorio@gmail.com', documento_identidad = '12915337', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'alvarovalenciatenorio@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'norbertodiazojeda@gmail.com', documento_identidad = '12983729', departamento_id = 31, municipio_id = 1097, updated_at = NOW() WHERE email = 'norbertodiazojeda@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alvarocordoba50@hotmail.com', documento_identidad = '12984798', departamento_id = 1, municipio_id = 53, updated_at = NOW() WHERE email = 'alvarocordoba50@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maplocmv@gmail.com', documento_identidad = '13063583', departamento_id = 14, municipio_id = 642, updated_at = NOW() WHERE email = 'maplocmv@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lansaedu@gmail.com', documento_identidad = '13071775', departamento_id = 14, municipio_id = 666, updated_at = NOW() WHERE email = 'lansaedu@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'RSanabria@minvivienda.gov.co', documento_identidad = '13175885', departamento_id = 16, municipio_id = 739, updated_at = NOW() WHERE email = 'RSanabria@minvivienda.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'salvadoralbarracinjauregui@gmail.com', documento_identidad = '13243890', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'salvadoralbarracinjauregui@gmail.com' AND tenant_id = 1;
UPDATE users SET name = '1955acuario63@gmail.com', documento_identidad = '13256278', departamento_id = 16, municipio_id = 762, updated_at = NOW() WHERE email = '1955acuario63@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bolivariano356@gmail.com', documento_identidad = '13256299', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'bolivariano356@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lsrozowilches@hotmail.com', documento_identidad = '13348094', departamento_id = 12, municipio_id = 607, updated_at = NOW() WHERE email = 'lsrozowilches@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eduardocriado2024@outlook.com', documento_identidad = '13357344', departamento_id = 16, municipio_id = 732, updated_at = NOW() WHERE email = 'eduardocriado2024@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'torradover@gmail.com', documento_identidad = '13361418', departamento_id = 16, municipio_id = 724, updated_at = NOW() WHERE email = 'torradover@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hugolopez0480@hotmeil.com', documento_identidad = '13379928', departamento_id = 16, municipio_id = 758, updated_at = NOW() WHERE email = 'hugolopez0480@hotmeil.com' AND tenant_id = 1;
UPDATE users SET name = 'porritas1971@gmail.com', documento_identidad = '13389039', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'porritas1971@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanlamusm@hotmail.com', documento_identidad = '13443198', departamento_id = 18, municipio_id = 796, updated_at = NOW() WHERE email = 'juanlamusm@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'brujulaurbana@yahoo.es', documento_identidad = '13451268', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'brujulaurbana@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'cadrazcoleonhernanmanuel@gmail.com', documento_identidad = '13452910', departamento_id = 19, municipio_id = 879, updated_at = NOW() WHERE email = 'cadrazcoleonhernanmanuel@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alrodantodoatento@gmail.com', documento_identidad = '13487906', departamento_id = 9, municipio_id = 543, updated_at = NOW() WHERE email = 'alrodantodoatento@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jbz19bar@gmail.com', documento_identidad = '13513032', departamento_id = 7, municipio_id = 405, updated_at = NOW() WHERE email = 'jbz19bar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'willy.mxy79@hotmail.com', documento_identidad = '13715966', departamento_id = 32, municipio_id = 1102, updated_at = NOW() WHERE email = 'willy.mxy79@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'heluar73@gmail.com', documento_identidad = '13817132', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'heluar73@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'orlanduribes@gmail.com', documento_identidad = '13825783', departamento_id = 15, municipio_id = 716, updated_at = NOW() WHERE email = 'orlanduribes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'efrancom@hotmail.com', documento_identidad = '13833444', departamento_id = 18, municipio_id = 854, updated_at = NOW() WHERE email = 'efrancom@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jacintoiguaran@yahoo.es', documento_identidad = '13844310', departamento_id = 18, municipio_id = 780, updated_at = NOW() WHERE email = 'jacintoiguaran@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'jhonpolanco16@hotmail.com', documento_identidad = '13854211', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'jhonpolanco16@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'santoaga79@gmail.com', documento_identidad = '13865386', departamento_id = 3, municipio_id = 1305, updated_at = NOW() WHERE email = 'santoaga79@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jjrojasc13c@hotmail.com', documento_identidad = '13875225', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'jjrojasc13c@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'produccionesmia@hotmail.com', documento_identidad = '13958361', departamento_id = 18, municipio_id = 821, updated_at = NOW() WHERE email = 'produccionesmia@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alcirore@hotmail.com', documento_identidad = '14010292', departamento_id = 20, municipio_id = 901, updated_at = NOW() WHERE email = 'alcirore@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alfonsogutierrezr@hotmail.com', documento_identidad = '14105664', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'alfonsogutierrezr@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'homolano@hotmail.com', documento_identidad = '14139749', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'homolano@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'oscarya80@hotmail.com', documento_identidad = '14213219', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'oscarya80@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisarminvanegas@gmail.com', documento_identidad = '14222243', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'luisarminvanegas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'narcifull@gmail.com', documento_identidad = '14317290', departamento_id = 20, municipio_id = 907, updated_at = NOW() WHERE email = 'narcifull@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Zivan17@hotmail.com', documento_identidad = '14474857', departamento_id = 21, municipio_id = 950, updated_at = NOW() WHERE email = 'Zivan17@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'filloel23@hotmail.com', documento_identidad = '14568509', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'filloel23@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'CarlosNaranjo1a@gmail.com', documento_identidad = '14570842', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'CarlosNaranjo1a@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nixonbravo@hotmail.com', documento_identidad = '14576041', departamento_id = 21, municipio_id = 950, updated_at = NOW() WHERE email = 'nixonbravo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ediagro11@gmail.com', documento_identidad = '14652586', departamento_id = 21, municipio_id = 954, updated_at = NOW() WHERE email = 'ediagro11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'albertpopo@hotmail.com', documento_identidad = '14795848', departamento_id = 21, municipio_id = 970, updated_at = NOW() WHERE email = 'albertpopo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ferlzc_8@hotmail.com', documento_identidad = '14881060', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'ferlzc_8@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'agon98@hotmail.com', documento_identidad = '14885115', departamento_id = 21, municipio_id = 942, updated_at = NOW() WHERE email = 'agon98@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jairovelasco244@gmail.com', documento_identidad = '14963863', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'jairovelasco244@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'argemioplaza@gmail.com', documento_identidad = '14970045', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'argemioplaza@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rudafo@gmail.com', documento_identidad = '14986687', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'rudafo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'agonzaleza_2259@hotmail.com', documento_identidad = '15022005', departamento_id = 8, municipio_id = 417, updated_at = NOW() WHERE email = 'agonzaleza_2259@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'j.g.hernandezllorente@gmail.com', documento_identidad = '15030648', departamento_id = 2, municipio_id = 129, updated_at = NOW() WHERE email = 'j.g.hernandezllorente@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'walbergu_66@hotmal.com', documento_identidad = '15045247', departamento_id = 8, municipio_id = 427, updated_at = NOW() WHERE email = 'walbergu_66@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mateoescobar1950@gmail.com', documento_identidad = '15072197', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'mateoescobar1950@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'felixhumbertoa@gmail.com', documento_identidad = '15208140', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'felixhumbertoa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'silugiba@hotmail.com', documento_identidad = '15303427', departamento_id = 1, municipio_id = 35, updated_at = NOW() WHERE email = 'silugiba@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'melquin11776@gmail.com', documento_identidad = '15329059', departamento_id = 8, municipio_id = 434, updated_at = NOW() WHERE email = 'melquin11776@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hegio1111@gmail.com', documento_identidad = '15438684', departamento_id = 1, municipio_id = 85, updated_at = NOW() WHERE email = 'hegio1111@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hernanrua61@gmail.com', documento_identidad = '15508215', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'hernanrua61@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alexander.algomas@gmail.com', documento_identidad = '15510428', departamento_id = 1, municipio_id = 40, updated_at = NOW() WHERE email = 'alexander.algomas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hugoalonsogallegoacevedo@gmail.com', documento_identidad = '15529321', departamento_id = 1, municipio_id = 80, updated_at = NOW() WHERE email = 'hugoalonsogallegoacevedo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'palajhon@gmail.com', documento_identidad = '15535772', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'palajhon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leduher22@gmail.com', documento_identidad = '15607836', departamento_id = 8, municipio_id = 434, updated_at = NOW() WHERE email = 'leduher22@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anbaga71@gmail.com', documento_identidad = '15618407', departamento_id = 8, municipio_id = 429, updated_at = NOW() WHERE email = 'anbaga71@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rmartiso@hotmail.com', documento_identidad = '15673095', departamento_id = 1, municipio_id = 109, updated_at = NOW() WHERE email = 'rmartiso@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Franciscojavierpena1965@gmail.com', documento_identidad = '15905661', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'Franciscojavierpena1965@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eduardopat@utp.edu.co', documento_identidad = '16071911', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'eduardopat@utp.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'rahum80@hotmail.com', documento_identidad = '16187185', departamento_id = 23, municipio_id = 984, updated_at = NOW() WHERE email = 'rahum80@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cachis.siempre.amigos@gmail.con', documento_identidad = '16231667', departamento_id = 21, municipio_id = 946, updated_at = NOW() WHERE email = 'cachis.siempre.amigos@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'diegofdoesencial@gmail.com', documento_identidad = '16232872', departamento_id = 35, municipio_id = 1148, updated_at = NOW() WHERE email = 'diegofdoesencial@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'miangel.1980@hotmail.com', documento_identidad = '16289052', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'miangel.1980@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andresvera51@gmail.com', documento_identidad = '16289268', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'andresvera51@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ricardoquinteroe7@gmail.com', documento_identidad = '16353494', departamento_id = 3, municipio_id = 182, updated_at = NOW() WHERE email = 'ricardoquinteroe7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rob_llen@hotmail.com', documento_identidad = '16456740', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'rob_llen@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yesid120779@hotmail.com', documento_identidad = '16459488', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'yesid120779@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pedro1valenciaramirez@hotmail.com', documento_identidad = '16473220', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'pedro1valenciaramirez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'doninfame1@gmail.com', documento_identidad = '16473671', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'doninfame1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rolandorivasarboleda7@gmail.com', documento_identidad = '16476967', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'rolandorivasarboleda7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rodolfo1161@hotmail.com', documento_identidad = '16484240', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'rodolfo1161@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gustavorenteriajaramillo@gmail.com', documento_identidad = '16489052', departamento_id = 11, municipio_id = 556, updated_at = NOW() WHERE email = 'gustavorenteriajaramillo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'henry2469@hotmail.com', documento_identidad = '16496650', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'henry2469@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'FUNDIPA04@GMAIL.COM', documento_identidad = '16501254', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'FUNDIPA04@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'krikeiman2020@gmail.com', documento_identidad = '16503265', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'krikeiman2020@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Donaldmatamba@hotmail.com', documento_identidad = '16507835', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'Donaldmatamba@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hazkary@gmail.com', documento_identidad = '16510869', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'hazkary@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jose1953raul@gmai.com', documento_identidad = '16605891', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'jose1953raul@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'afroscols@hotmail.com', documento_identidad = '16613110', departamento_id = 22, municipio_id = 977, updated_at = NOW() WHERE email = 'afroscols@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'viveelbarriocol@gmail.com', documento_identidad = '16629238', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'viveelbarriocol@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'franklinlegro@gmail.com', documento_identidad = '16639993', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'franklinlegro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jhernanmunoz@gmail.com', documento_identidad = '16650244', departamento_id = 21, municipio_id = 950, updated_at = NOW() WHERE email = 'jhernanmunoz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fernando.giraldo2410@gmail.com', documento_identidad = '16679678', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'fernando.giraldo2410@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jarrison.martinezc@gmail.com', documento_identidad = '16684987', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'jarrison.martinezc@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'serraniagua@gmail.com', documento_identidad = '16690947', departamento_id = 21, municipio_id = 953, updated_at = NOW() WHERE email = 'serraniagua@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wior65c@gmail.com', documento_identidad = '16692325', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'wior65c@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'danielmconcejo@hotmail.com', documento_identidad = '16695937', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'danielmconcejo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joteroca260664@hotmail.com', documento_identidad = '16697647', departamento_id = 21, municipio_id = 962, updated_at = NOW() WHERE email = 'joteroca260664@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marolujan@yahoo.com', documento_identidad = '16730338', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'marolujan@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'javito196510@gmail.com', documento_identidad = '16739896', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'javito196510@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gabrielgiron914@gmail.com', documento_identidad = '16750388', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'gabrielgiron914@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'estebanjedc@hotmail.com', documento_identidad = '16790282', departamento_id = 6, municipio_id = 376, updated_at = NOW() WHERE email = 'estebanjedc@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Jarbingomezpossu@gmail.com', documento_identidad = '16823354', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'Jarbingomezpossu@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jrmorenoa11@gmail.com', documento_identidad = '16864414', departamento_id = 21, municipio_id = 947, updated_at = NOW() WHERE email = 'jrmorenoa11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'manfreyo@hotmail.com', documento_identidad = '16890278', departamento_id = 21, municipio_id = 949, updated_at = NOW() WHERE email = 'manfreyo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'victorherney7@gmail.com', documento_identidad = '16944570', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'victorherney7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fvanegas21@gmail.com', documento_identidad = '17156925', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'fvanegas21@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gvelandi@hotmail.com', documento_identidad = '17199322', departamento_id = 9, municipio_id = 437, updated_at = NOW() WHERE email = 'gvelandi@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jama2306@gmail.com', documento_identidad = '17321783', departamento_id = 28, municipio_id = 1074, updated_at = NOW() WHERE email = 'jama2306@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'atilagalindo@gmail.com', documento_identidad = '17344810', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'atilagalindo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tidabe16@hotnail.com', documento_identidad = '17545574', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'tidabe16@hotnail.com' AND tenant_id = 1;
UPDATE users SET name = 'dojeda0781@gmail.com', documento_identidad = '17594219', departamento_id = 22, municipio_id = 979, updated_at = NOW() WHERE email = 'dojeda0781@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lerimar29@yahoo.com', documento_identidad = '17624610', departamento_id = 23, municipio_id = 984, updated_at = NOW() WHERE email = 'lerimar29@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'felixmurcia1970@gmail.com', documento_identidad = '17645293', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'felixmurcia1970@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rolis.huergo@gmail.com', documento_identidad = '17652371', departamento_id = 23, municipio_id = 984, updated_at = NOW() WHERE email = 'rolis.huergo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'faibersotovargas@gmail.com', documento_identidad = '17685188', departamento_id = 23, municipio_id = 987, updated_at = NOW() WHERE email = 'faibersotovargas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'daniloupel2020@gmail.com', documento_identidad = '17829394', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'daniloupel2020@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jd0538489@gmail.com', documento_identidad = '17900722', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'jd0538489@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dalgeryamithmendoza@gmail.com', documento_identidad = '17901488', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'dalgeryamithmendoza@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgemallama@hotmail.com', documento_identidad = '18112958', departamento_id = 31, municipio_id = 1094, updated_at = NOW() WHERE email = 'jorgemallama@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yil425@hotmail.com', documento_identidad = '18122785', departamento_id = 12, municipio_id = 587, updated_at = NOW() WHERE email = 'yil425@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edwin.villota1979@gmail.com', documento_identidad = '18128171', departamento_id = 31, municipio_id = 1089, updated_at = NOW() WHERE email = 'edwin.villota1979@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arielrojastomedes@gmail.com', documento_identidad = '18261025', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'arielrojastomedes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alex.trujillo@gmail.com', documento_identidad = '18400302', departamento_id = 32, municipio_id = 1102, updated_at = NOW() WHERE email = 'alex.trujillo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vadimircc@gmail.com', documento_identidad = '18401212', departamento_id = 33, municipio_id = 1110, updated_at = NOW() WHERE email = 'vadimircc@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juntanzaspetropresidente@gmail.com', documento_identidad = '18492021', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'juntanzaspetropresidente@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'godimarache10@gmail.com', documento_identidad = '18503919', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'godimarache10@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mezacoordenadasur@gmail.com', documento_identidad = '18615223', departamento_id = 15, municipio_id = 716, updated_at = NOW() WHERE email = 'mezacoordenadasur@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'goezsalcedoluisgregorio@gmail.com', documento_identidad = '18857659', departamento_id = 19, municipio_id = 879, updated_at = NOW() WHERE email = 'goezsalcedoluisgregorio@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'esperanza.p55@hotmail.com', documento_identidad = '18859066', departamento_id = 19, municipio_id = 868, updated_at = NOW() WHERE email = 'esperanza.p55@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nederes76@gmail.com', documento_identidad = '18880146', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'nederes76@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ariel.llain@gmail.com', documento_identidad = '18915756', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'ariel.llain@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edinsonpeinadogarcia3@gmail.com', documento_identidad = '18921709', departamento_id = 18, municipio_id = 839, updated_at = NOW() WHERE email = 'edinsonpeinadogarcia3@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hermessuarezflorez@gmail.com', documento_identidad = '18927433', departamento_id = 7, municipio_id = 383, updated_at = NOW() WHERE email = 'hermessuarezflorez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'asesoresenlopublico@gmail.com', documento_identidad = '18969961', departamento_id = 7, municipio_id = 388, updated_at = NOW() WHERE email = 'asesoresenlopublico@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luchoai13@hotmail.com', documento_identidad = '19002173', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'luchoai13@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'didierg1928@hotmail.com', documento_identidad = '19002845', departamento_id = 27, municipio_id = 1035, updated_at = NOW() WHERE email = 'didierg1928@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edgarmontenegro1148@gmail.com', documento_identidad = '19060425', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'edgarmontenegro1148@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'corsocial1998@gmail.com', documento_identidad = '19086359', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'corsocial1998@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'francomoreu@gmail.com', documento_identidad = '19088147', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'francomoreu@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alenumar@yahoo.es', documento_identidad = '19104015', departamento_id = 9, municipio_id = 511, updated_at = NOW() WHERE email = 'alenumar@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'cmlasso@hotmail.com', documento_identidad = '19118500', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'cmlasso@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lucapec11@gmail.com', documento_identidad = '19128030', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'lucapec11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leofrank2007@gmail.com', documento_identidad = '19130685', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'leofrank2007@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'helsar502@gmail.com', documento_identidad = '19130805', departamento_id = 6, municipio_id = 381, updated_at = NOW() WHERE email = 'helsar502@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'emprend2012@gmail.com', documento_identidad = '19145364', departamento_id = 30, municipio_id = 1077, updated_at = NOW() WHERE email = 'emprend2012@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'efrasilva@gmail.com', documento_identidad = '19183802', departamento_id = 35, municipio_id = 1279, updated_at = NOW() WHERE email = 'efrasilva@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pfernandg@gmail.com', documento_identidad = '19186924', departamento_id = 9, updated_at = NOW() WHERE email = 'pfernandg@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josegu06@hotmail.com', documento_identidad = '19215693', departamento_id = 9, municipio_id = 548, updated_at = NOW() WHERE email = 'josegu06@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'magilbarna@gmail.com', documento_identidad = '19218988', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'magilbarna@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lfg.ma.eco@gmail.com', documento_identidad = '19269961', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'lfg.ma.eco@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gj.movil56@gmail.com', documento_identidad = '19277444', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'gj.movil56@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ecos2012@gmail.com', documento_identidad = '19287415', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'ecos2012@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'javigalvisval@hotmail.com', documento_identidad = '19292565', departamento_id = 22, municipio_id = 978, updated_at = NOW() WHERE email = 'javigalvisval@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rabusco4000@yahoo.es', documento_identidad = '19295439', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'rabusco4000@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'pachonjv@gmail.com', documento_identidad = '19315865', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'pachonjv@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mecaospina55@gmail.com', documento_identidad = '19316747', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'mecaospina55@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aalbertpaz707@gmail.com', documento_identidad = '19325603', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'aalbertpaz707@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jesuslara75@hotmail.com', documento_identidad = '19337889', departamento_id = 3, municipio_id = 166, updated_at = NOW() WHERE email = 'jesuslara75@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zabaletajose274@gmail.com', documento_identidad = '19348470', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'zabaletajose274@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'profeguapo.guarinposada@gmail.com', documento_identidad = '19349776', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'profeguapo.guarinposada@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'federagroariari@gmail.com', documento_identidad = '19356106', departamento_id = 26, municipio_id = 1054, updated_at = NOW() WHERE email = 'federagroariari@gmail.com' AND tenant_id = 1;
UPDATE users SET name = '55germanmoreno@gmail.com', documento_identidad = '19356650', departamento_id = 9, updated_at = NOW() WHERE email = '55germanmoreno@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sanchezfierrogentil@gmail.com', documento_identidad = '19387341', departamento_id = 12, municipio_id = 608, updated_at = NOW() WHERE email = 'sanchezfierrogentil@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gonchapa@gmail.com', documento_identidad = '19396944', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'gonchapa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nelsonj.garzonb@gmail.com', documento_identidad = '19399778', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'nelsonj.garzonb@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marlobenjamingomez@gmail.com', documento_identidad = '19409933', departamento_id = 9, municipio_id = 456, updated_at = NOW() WHERE email = 'marlobenjamingomez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josealcidesalbalaverde@gmail.com', documento_identidad = '19430235', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'josealcidesalbalaverde@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'h.otalora.md@gmail.com', documento_identidad = '19434155', departamento_id = 1, municipio_id = 53, updated_at = NOW() WHERE email = 'h.otalora.md@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pedroleveau@gmail.com', documento_identidad = '19435172', departamento_id = 21, municipio_id = 945, updated_at = NOW() WHERE email = 'pedroleveau@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'concejaljosecuesta@gmail.com', documento_identidad = '19456865', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'concejaljosecuesta@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'garciajose144@yahoo.es', documento_identidad = '19458336', departamento_id = 1, municipio_id = 53, updated_at = NOW() WHERE email = 'garciajose144@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'bustosbustoscesar@gmail.com', documento_identidad = '19490025', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'bustosbustoscesar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'willisger@yahoo.com', documento_identidad = '19494600', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'willisger@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'alimis76@live.com', documento_identidad = '19517986', departamento_id = 13, municipio_id = 626, updated_at = NOW() WHERE email = 'alimis76@live.com' AND tenant_id = 1;
UPDATE users SET name = 'pipelon_romero@hotmail.com', documento_identidad = '19580038', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'pipelon_romero@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jaicastigre@hotmail.com', documento_identidad = '19610585', departamento_id = 13, municipio_id = 618, updated_at = NOW() WHERE email = 'jaicastigre@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'delapenanuneza@gmail.com', documento_identidad = '19774849', departamento_id = 3, municipio_id = 185, updated_at = NOW() WHERE email = 'delapenanuneza@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ruth.1947@hotmail.com', documento_identidad = '20610866', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'ruth.1947@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzelbabeltranb@gmail.com', documento_identidad = '20939968', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'luzelbabeltranb@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Adelamontoyarojas@gmail.com', documento_identidad = '21824082', departamento_id = 1, municipio_id = 61, updated_at = NOW() WHERE email = 'Adelamontoyarojas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rojovillamaria@gmail.com', documento_identidad = '21970253', departamento_id = 1, municipio_id = 86, updated_at = NOW() WHERE email = 'rojovillamaria@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luz70salgar@gmail.com', documento_identidad = '21979096', departamento_id = 1, municipio_id = 88, updated_at = NOW() WHERE email = 'luz70salgar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mariaarangopaniagua2@gmail.com', documento_identidad = '22201099', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'mariaarangopaniagua2@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'herediamery@yahoo.com.co', documento_identidad = '22366773', departamento_id = 2, municipio_id = 127, updated_at = NOW() WHERE email = 'herediamery@yahoo.com.co' AND tenant_id = 1;
UPDATE users SET name = 'edith-hp2948@hotmail.com', documento_identidad = '22387502', departamento_id = 2, municipio_id = 127, updated_at = NOW() WHERE email = 'edith-hp2948@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'denisa0296@hotmail.com', documento_identidad = '22426350', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'denisa0296@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'profeara@hotmail.com', documento_identidad = '22454291', departamento_id = 2, municipio_id = 127, updated_at = NOW() WHERE email = 'profeara@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'astridcoronado@gmail.com', documento_identidad = '22455867', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'astridcoronado@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nilda.rsz@hotmail.com', documento_identidad = '22537819', departamento_id = 2, municipio_id = 142, updated_at = NOW() WHERE email = 'nilda.rsz@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lidacolpas@hotmail.com', documento_identidad = '22545726', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'lidacolpas@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yulietta78@gmail.com', documento_identidad = '22645092', departamento_id = 8, municipio_id = 435, updated_at = NOW() WHERE email = 'yulietta78@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nellyscordoba@hotmail.com', documento_identidad = '22697147', departamento_id = 2, municipio_id = 146, updated_at = NOW() WHERE email = 'nellyscordoba@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rosaare244@gmail.com', documento_identidad = '22819209', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'rosaare244@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'derineldaqui@gmail.com', documento_identidad = '22831294', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'derineldaqui@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vitaliacallegomez@gmail.com', documento_identidad = '23008756', departamento_id = 3, municipio_id = 185, updated_at = NOW() WHERE email = 'vitaliacallegomez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lummefu0621@gmail.com', documento_identidad = '23553242', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'lummefu0621@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elcy62@hotmail.com', documento_identidad = '23606359', departamento_id = 4, municipio_id = 229, updated_at = NOW() WHERE email = 'elcy62@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lustellaroa@hitmail.com', documento_identidad = '23622533', departamento_id = 4, municipio_id = 231, updated_at = NOW() WHERE email = 'lustellaroa@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'terereinaf@gmail.com', documento_identidad = '23690241', departamento_id = 4, municipio_id = 241, updated_at = NOW() WHERE email = 'terereinaf@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cam1seb2@hotmail.com', documento_identidad = '23694759', departamento_id = 4, municipio_id = 242, updated_at = NOW() WHERE email = 'cam1seb2@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'patriciamore1608@gmail.com', documento_identidad = '23710160', departamento_id = 24, municipio_id = 1003, updated_at = NOW() WHERE email = 'patriciamore1608@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'llangua@hotmail.com', documento_identidad = '23741795', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'llangua@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'caritoclaudia2018@gmail.com', documento_identidad = '23764522', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'caritoclaudia2018@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'olgamariaperilla62@gmail.com', documento_identidad = '24227336', departamento_id = 24, municipio_id = 1005, updated_at = NOW() WHERE email = 'olgamariaperilla62@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'isabelecheverri@yahoo.com', documento_identidad = '24603707', departamento_id = 17, municipio_id = 765, updated_at = NOW() WHERE email = 'isabelecheverri@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'lucellytatama@yahoo.com', documento_identidad = '24999485', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'lucellytatama@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'judithchaux@hotmail.com', documento_identidad = '25277681', departamento_id = 6, municipio_id = 374, updated_at = NOW() WHERE email = 'judithchaux@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lilianavaldes850@gmail.com', documento_identidad = '25281190', departamento_id = 35, municipio_id = 1280, updated_at = NOW() WHERE email = 'lilianavaldes850@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'julianamercedesmayosanto@gmail.com', documento_identidad = '26257067', departamento_id = 11, municipio_id = 550, updated_at = NOW() WHERE email = 'julianamercedesmayosanto@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dhcolombia1812@gmail.com', documento_identidad = '26331215', updated_at = NOW() WHERE email = 'dhcolombia1812@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'albertiniavalenciategaiza@gmail.com', documento_identidad = '26338853', departamento_id = 11, municipio_id = 556, updated_at = NOW() WHERE email = 'albertiniavalenciategaiza@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'esperanzaamaris725@gmail.com', documento_identidad = '26725480', departamento_id = 7, municipio_id = 385, updated_at = NOW() WHERE email = 'esperanzaamaris725@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lucas_19969@hotmail.com', documento_identidad = '26914332', departamento_id = 2, municipio_id = 133, updated_at = NOW() WHERE email = 'lucas_19969@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'refealro@hotmail.com', documento_identidad = '26944879', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'refealro@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mayerliscamelo-80@hotmail.com', documento_identidad = '26946172', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'mayerliscamelo-80@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nellyruizd69@gmail.com', documento_identidad = '26984363', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'nellyruizd69@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maylaojeda2015@gmail.com', documento_identidad = '26985635', departamento_id = 25, municipio_id = 1026, updated_at = NOW() WHERE email = 'maylaojeda2015@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arelisbrito2024@gmail.com', documento_identidad = '26988434', departamento_id = 25, municipio_id = 1026, updated_at = NOW() WHERE email = 'arelisbrito2024@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josefinafreile63@gmail.com', documento_identidad = '26989450', departamento_id = 25, municipio_id = 1023, updated_at = NOW() WHERE email = 'josefinafreile63@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ml.rojasceron@gmail.com', documento_identidad = '27090600', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'ml.rojasceron@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luce2maria@gmail.com', documento_identidad = '27123306', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'luce2maria@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rosalbariascose@hotmail.com', documento_identidad = '27185908', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'rosalbariascose@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anamariacuellarcubillos@gmail.com', documento_identidad = '27353765', departamento_id = 31, municipio_id = 1096, updated_at = NOW() WHERE email = 'anamariacuellarcubillos@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yosija2411@hotmail.com', documento_identidad = '28098855', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'yosija2411@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rosarioacevedo28@hotmail.com', documento_identidad = '28099461', departamento_id = 18, municipio_id = 844, updated_at = NOW() WHERE email = 'rosarioacevedo28@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'margarita.carrenocanizares@gmail.com', documento_identidad = '28238986', departamento_id = 18, municipio_id = 810, updated_at = NOW() WHERE email = 'margarita.carrenocanizares@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lusupe09@gmail.com', documento_identidad = '28311854', departamento_id = 18, municipio_id = 839, updated_at = NOW() WHERE email = 'lusupe09@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'evapati0308@hotmail.com', documento_identidad = '28386578', departamento_id = 18, municipio_id = 846, updated_at = NOW() WHERE email = 'evapati0308@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'evitariverach@hotmail.con', documento_identidad = '28946733', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'evitariverach@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'g.catalina42@yahoo.com', documento_identidad = '29181911', departamento_id = 35, municipio_id = 1217, updated_at = NOW() WHERE email = 'g.catalina42@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'maritza1468@hotmail.com', documento_identidad = '29185562', departamento_id = 21, municipio_id = 940, updated_at = NOW() WHERE email = 'maritza1468@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mcgarzon518@gemail.com', documento_identidad = '29417089', departamento_id = 6, municipio_id = 374, updated_at = NOW() WHERE email = 'mcgarzon518@gemail.com' AND tenant_id = 1;
UPDATE users SET name = 'omairacorrales07@gmail.com', documento_identidad = '29498570', departamento_id = 21, municipio_id = 949, updated_at = NOW() WHERE email = 'omairacorrales07@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marlida90@hotmail.com', documento_identidad = '29503125', departamento_id = 21, municipio_id = 949, updated_at = NOW() WHERE email = 'marlida90@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisagabyvergara@gmail.com', documento_identidad = '29678859', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'luisagabyvergara@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dorita08@hotmail.es', documento_identidad = '29741716', departamento_id = 21, municipio_id = 963, updated_at = NOW() WHERE email = 'dorita08@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'rosaog30@gmail.com', documento_identidad = '29807609', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'rosaog30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yelmioro73@gmail.com', documento_identidad = '29940724', departamento_id = 35, municipio_id = 1217, updated_at = NOW() WHERE email = 'yelmioro73@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juliethvivas2014@gmail.com', documento_identidad = '29974207', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'juliethvivas2014@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'visapersonalshopper@gmail.com', documento_identidad = '29974961', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'visapersonalshopper@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yalilegarcia29@gmail.com', documento_identidad = '30232860', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'yalilegarcia29@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'claudiaandreac472@gmail.com', documento_identidad = '30338255', departamento_id = 15, municipio_id = 716, updated_at = NOW() WHERE email = 'claudiaandreac472@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'emanosalvar22@gmail.com', documento_identidad = '30396252', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'emanosalvar22@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nayibibeltran@gmail.com', documento_identidad = '30509158', departamento_id = 12, municipio_id = 593, updated_at = NOW() WHERE email = 'nayibibeltran@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'glagmontenegro@gmail.com', documento_identidad = '30728626', departamento_id = 14, municipio_id = 642, updated_at = NOW() WHERE email = 'glagmontenegro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ilianlopez1969@hotmail.com', documento_identidad = '30744250', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'ilianlopez1969@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ignaciaasprillahurtado@gmail.com', documento_identidad = '30771032', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'ignaciaasprillahurtado@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mirtoji@hotmail.com', documento_identidad = '30772965', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'mirtoji@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'olimpia.gomez.contadorapublica@gmail.com', documento_identidad = '30773737', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'olimpia.gomez.contadorapublica@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jupiter2600603-@Hotmail.com', documento_identidad = '31263589', departamento_id = 21, municipio_id = 945, updated_at = NOW() WHERE email = 'jupiter2600603-@Hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kaladia1954@gmail.com', documento_identidad = '31266919', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'kaladia1954@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lopezdevelez1@gmail.com', documento_identidad = '31267006', departamento_id = 21, municipio_id = 936, updated_at = NOW() WHERE email = 'lopezdevelez1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cristoyeliana1957@gmail.com', documento_identidad = '31289564', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'cristoyeliana1957@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gloriacomunal07@hotmail.com', documento_identidad = '31296099', departamento_id = 21, municipio_id = 956, updated_at = NOW() WHERE email = 'gloriacomunal07@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'valenciapenagloriastella@gmail.com', documento_identidad = '31491088', departamento_id = 21, municipio_id = 976, updated_at = NOW() WHERE email = 'valenciapenagloriastella@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sjauxf@gmail.com', documento_identidad = '31710167', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'sjauxf@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'irmosbe1955@gmail.com', documento_identidad = '31834803', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'irmosbe1955@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lavinia14k@gmail.com', documento_identidad = '31862956', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'lavinia14k@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lilianalenguaje.monteverdi@gmail.com', documento_identidad = '31946614', departamento_id = 9, municipio_id = 532, updated_at = NOW() WHERE email = 'lilianaestupinan81@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'patriciamendezgutierrez1@gmail.com', documento_identidad = '31979694', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'patriciamendezgutierrez1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lubisaraujo9@gmail.com', documento_identidad = '32272860', departamento_id = 1, municipio_id = 15, updated_at = NOW() WHERE email = 'lubisaraujo9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nubialopez2017123@gmail.com', documento_identidad = '32323478', departamento_id = 1, municipio_id = 14, updated_at = NOW() WHERE email = 'nubialopez2017123@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carmengomes1709@gmail.com', documento_identidad = '32350846', departamento_id = 19, municipio_id = 878, updated_at = NOW() WHERE email = 'carmengomes1709@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'b.bedoyaj@gmail.com', documento_identidad = '32533827', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'b.bedoyaj@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'saraltamararias@yahoo.es', documento_identidad = '32660636', departamento_id = 8, municipio_id = 409, updated_at = NOW() WHERE email = 'saraltamararias@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'merlhyberrio2730@gmail.com', documento_identidad = '32692734', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'merlhyberrio2730@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yusdi_sc13@hotmail.com', documento_identidad = '32751114', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'yusdi_sc13@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yamicarrillo1029@gmail.com', documento_identidad = '32776775', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'yamicarrillo1029@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ricardoyronaldo@hotmail.com', documento_identidad = '32877338', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'ricardoyronaldo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aliesguinard@gmail.com', documento_identidad = '33151909', departamento_id = 35, municipio_id = 1176, updated_at = NOW() WHERE email = 'aliesguinard@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'isaarrietamartinez@gmail.com', documento_identidad = '33197341', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'isaarrietamartinez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dimilocy1985@gmail.com', documento_identidad = '34326544', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'dimilocy1985@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fundifamilia@hotmail.com', documento_identidad = '34511926', departamento_id = 6, municipio_id = 367, updated_at = NOW() WHERE email = 'fundifamilia@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jesosiluis06@gmail.com', documento_identidad = '34534983', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'jesosiluis06@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cristiandaniel007@hotmail.com', documento_identidad = '34547755', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'cristiandaniel007@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lucerocerquera59@gmail.com', documento_identidad = '34551072', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'lucerocerquera59@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jacquelinechacon91@gmail.com', documento_identidad = '34554405', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'jacquelinechacon91@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sandra-vanessa34@hotmail.com', documento_identidad = '34562297', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'sandra-vanessa34@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sotanarosa53@yahoo.com', documento_identidad = '34592113', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'sotanarosa53@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'orgenivierabetancourth@gmail.com', documento_identidad = '34603859', departamento_id = 6, municipio_id = 348, updated_at = NOW() WHERE email = 'orgenivierabetancourth@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'paulaenriquez8@gmail.com', documento_identidad = '34607242', departamento_id = 6, municipio_id = 381, updated_at = NOW() WHERE email = 'paulaenriquez8@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jcecilia1575@gmail.com', documento_identidad = '34610677', departamento_id = 21, municipio_id = 956, updated_at = NOW() WHERE email = 'jcecilia1575@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'licenciadasilviayazo@gmail.com', documento_identidad = '34984033', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'licenciadasilviayazo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejandrinapacheco91@gmail.com', documento_identidad = '34992284', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'alejandrinapacheco91@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yamileolartepava@gmail.com', documento_identidad = '35252307', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'yamileolartepava@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lefabiolaro@gmail.com', documento_identidad = '35375753', departamento_id = 9, municipio_id = 446, updated_at = NOW() WHERE email = 'lefabiolaro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ybeltrangarzon@gmail.com', documento_identidad = '35416651', departamento_id = 9, municipio_id = 548, updated_at = NOW() WHERE email = 'ybeltrangarzon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gazajuanes@gmail.com', documento_identidad = '35510705', departamento_id = 9, municipio_id = 442, updated_at = NOW() WHERE email = 'gazajuanes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elizabethpimentelperdomo@gmail.com', documento_identidad = '35511626', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'elizabethpimentelperdomo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gallagoalba@gmail.com', documento_identidad = '35522454', departamento_id = 9, municipio_id = 451, updated_at = NOW() WHERE email = 'albagallegoq4@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'beslonpa05@hotmail.com', documento_identidad = '35602706', departamento_id = 11, municipio_id = 551, updated_at = NOW() WHERE email = 'beslonpa05@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nopapo23@gmail.com', documento_identidad = '35892477', departamento_id = 11, municipio_id = 550, updated_at = NOW() WHERE email = 'nopapo23@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camaycarpi@gmail.com', documento_identidad = '36158205', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'camaycarpi@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'darlitelco@hotmail.com', documento_identidad = '36640237', departamento_id = 13, municipio_id = 627, updated_at = NOW() WHERE email = 'darlitelco@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'guerramoni7@gmail.com', documento_identidad = '36759736', departamento_id = 14, municipio_id = 642, updated_at = NOW() WHERE email = 'guerramoni7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'belencc1969@gmail.com', documento_identidad = '37179168', departamento_id = 16, municipio_id = 762, updated_at = NOW() WHERE email = 'belencc1969@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'caceresjuliet342@gmail.com', documento_identidad = '37179618', departamento_id = 16, municipio_id = 741, updated_at = NOW() WHERE email = 'caceresjuliet342@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rosangelacorredor@gmail.com', documento_identidad = '37506503', departamento_id = 16, municipio_id = 729, updated_at = NOW() WHERE email = 'rosangelacorredor@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zaraymanuela_29@hotmail.com', documento_identidad = '37550106', departamento_id = 18, municipio_id = 810, updated_at = NOW() WHERE email = 'zaraymanuela_29@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'naydu102014@gmail.com', documento_identidad = '37616038', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'naydu102014@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'erikpassosl@hotmail.com', documento_identidad = '37713959', departamento_id = 18, municipio_id = 839, updated_at = NOW() WHERE email = 'erikpassosl@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gladyscelisrueda@gmail.com', documento_identidad = '37895974', departamento_id = 18, municipio_id = 844, updated_at = NOW() WHERE email = 'gladyscelisrueda@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jsscali2010@gmail.com', documento_identidad = '38228702', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'jsscali2010@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'emna2028@hotmail.com', documento_identidad = '38469590', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'emna2028@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'karolinalenis@yahoo.com', documento_identidad = '38556139', departamento_id = 14, municipio_id = 642, updated_at = NOW() WHERE email = 'karolinalenis@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'isabel.zuleta@senado.gov.co', documento_identidad = '38790547', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'isabel.zuleta@senado.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'dollyasesorias@hotmail.com', documento_identidad = '38940648', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'dollyasesorias@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nohoraospi23@gmail.com', documento_identidad = '39008098', departamento_id = 35, updated_at = NOW() WHERE email = 'nohoraospi23@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'asimanca04@yahoo.es', documento_identidad = '39011452', departamento_id = 13, municipio_id = 623, updated_at = NOW() WHERE email = 'asimanca04@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'afcasa2011@gmail.com', documento_identidad = '39354141', departamento_id = 2, municipio_id = 137, updated_at = NOW() WHERE email = 'afcasa2011@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'inghelizanagz@gmail.com', documento_identidad = '39461606', departamento_id = 7, municipio_id = 403, updated_at = NOW() WHERE email = 'inghelizanagz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mirellaludy@hotmail.es', documento_identidad = '39529370', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'mirellaludy@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'Magyate2024@gmail.com', documento_identidad = '39569260', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'Magyate2024@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanitacamargog@gmail.com', documento_identidad = '39617076', departamento_id = 9, municipio_id = 456, updated_at = NOW() WHERE email = 'juanitacamargog@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'karina@orjuela.co', documento_identidad = '39628816', departamento_id = 9, municipio_id = 456, updated_at = NOW() WHERE email = 'karina@orjuela.co' AND tenant_id = 1;
UPDATE users SET name = 'malutete1961@gmail.com', documento_identidad = '39632071', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'malutete1961@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'losiramsas@gmail.com', documento_identidad = '39645174', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'losiramsas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'livitm104@gmail.com', documento_identidad = '39662584', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'livitm104@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mafe-florez@hotmail.com', documento_identidad = '39818823', departamento_id = 9, municipio_id = 520, updated_at = NOW() WHERE email = 'mafe-florez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gaavilamotta@gmail.com', documento_identidad = '40017766', departamento_id = 4, municipio_id = 247, updated_at = NOW() WHERE email = 'gaavilamotta@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ruthbeatrizvargas@gmail.com', documento_identidad = '40018649', departamento_id = 4, municipio_id = 191, updated_at = NOW() WHERE email = 'ruthbeatrizvargas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'garefe3@gmail.com', documento_identidad = '40031839', departamento_id = 4, municipio_id = 303, updated_at = NOW() WHERE email = 'garefe3@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yudyrocha466@gmail.com', documento_identidad = '40325832', departamento_id = 24, municipio_id = 1009, updated_at = NOW() WHERE email = 'yudyrocha466@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lucyquinones860@gmail.com', documento_identidad = '40373621', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'lucyquinones860@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ilbafedemeta@yahoo.es', documento_identidad = '40382838', departamento_id = 26, municipio_id = 1054, updated_at = NOW() WHERE email = 'ilbafedemeta@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'deissyquinoneslozano@gmail.com', documento_identidad = '40388930', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'deissyquinoneslozano@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pitalitohumana@gmail.com', documento_identidad = '40392164', departamento_id = 12, municipio_id = 600, updated_at = NOW() WHERE email = 'pitalitohumana@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yorni.78@hotmail.com', documento_identidad = '40626607', departamento_id = 23, municipio_id = 986, updated_at = NOW() WHERE email = 'yorni.78@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mariat_gonzalezcaicedo@hotmail.com', documento_identidad = '40725966', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'mariat_gonzalezcaicedo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yundlah@gmail.com', documento_identidad = '40784528', departamento_id = 23, municipio_id = 996, updated_at = NOW() WHERE email = 'yundlah@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzmaigbr@gmail.com', documento_identidad = '40793660', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'luzmaigbr@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leticampos6724@gmail.com', documento_identidad = '40916923', departamento_id = 7, municipio_id = 386, updated_at = NOW() WHERE email = 'leticampos6724@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'brievaingris@gmail.com', documento_identidad = '40923839', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'brievaingris@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vargasdorafanny@gmail.com', documento_identidad = '40927617', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'vargasdorafanny@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'iguaranluisana611@gmail.com', documento_identidad = '40944408', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'iguaranluisana611@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lendysdm@hotmail.com', documento_identidad = '40981275', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'lendysdm@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'culchactello.mariamarleny@gmail.com', documento_identidad = '41117178', departamento_id = 31, municipio_id = 1100, updated_at = NOW() WHERE email = 'culchactello.mariamarleny@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'itaca500@gmail.com', documento_identidad = '41492951', departamento_id = 35, municipio_id = 1233, updated_at = NOW() WHERE email = 'itaca500@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'asesoriaplena@gmail.com', documento_identidad = '41631612', departamento_id = 18, municipio_id = 779, updated_at = NOW() WHERE email = 'asesoriaplena@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zera10@hotmail.com', documento_identidad = '41665076', departamento_id = 35, updated_at = NOW() WHERE email = 'zera10@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gladysrojas20000@hotmail.com', documento_identidad = '41669452', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'gladysrojas20000@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anateresabernal@yahoo.es', documento_identidad = '41688001', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'anateresabernal@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'mariaisabelvillate@gmail.com', documento_identidad = '41719901', departamento_id = 9, municipio_id = 442, updated_at = NOW() WHERE email = 'mariaisabelvillate@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mariaisaurah79@gmail.com', documento_identidad = '41768614', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'mariaisaurah79@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sasamar3@gmail.com', documento_identidad = '41782450', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'sasamar3@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'soamca1960@gmail.com', documento_identidad = '42062499', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'soamca1960@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'colrda200@hotmail.com', documento_identidad = '42086010', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'colrda200@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'paulitaayala30@gmail.com', documento_identidad = '42129543', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'paulitaayala30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elaine79canal@gmail.com', documento_identidad = '42132179', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'elaine79canal@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rodriroorosa123@gmail.com', documento_identidad = '42207169', departamento_id = 19, municipio_id = 864, updated_at = NOW() WHERE email = 'rodriroorosa123@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'obduliadoriacaro@gmail.com', documento_identidad = '42494807', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'obduliadoriacaro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'larroyaveabogada@hotmail.com', documento_identidad = '42785432', departamento_id = 1, municipio_id = 123, updated_at = NOW() WHERE email = 'larroyaveabogada@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'stella.restrepo.osorio62@gmail.com', documento_identidad = '42821694', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'stella.restrepo.osorio62@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'amparocastro51@gmail.com', documento_identidad = '42877402', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'amparocastro51@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'inmaculadarodriguezusma@gmail.com', documento_identidad = '42964542', departamento_id = 1, municipio_id = 64, updated_at = NOW() WHERE email = 'inmaculadarodriguezusma@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mvrasaj@hotmail.com', documento_identidad = '43072752', departamento_id = 1, municipio_id = 48, updated_at = NOW() WHERE email = 'mvrasaj@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'amhurtadobernal64@gmail.com', documento_identidad = '43080986', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'amhurtadobernal64@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cpetrodoria5@gmail.com', documento_identidad = '43145152', departamento_id = 1, municipio_id = 13, updated_at = NOW() WHERE email = 'cpetrodoria5@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marcela.saldavi@gmail.com', documento_identidad = '43188161', departamento_id = 1, municipio_id = 78, updated_at = NOW() WHERE email = 'marcela.saldavi@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'field.icefly@gmail.com', documento_identidad = '43280276', departamento_id = 1, municipio_id = 108, updated_at = NOW() WHERE email = 'field.icefly@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'adrarias@gmail.com', documento_identidad = '43435528', departamento_id = 1, municipio_id = 53, updated_at = NOW() WHERE email = 'adrarias@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'enorisserna@yahoo.es', documento_identidad = '43444200', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'enorisserna@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'doralba65@gmail.com', documento_identidad = '43549004', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'doralba65@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anetvivy@gmail.com', documento_identidad = '43568064', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'anetvivy@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nancymilenas@gmail.com', documento_identidad = '43595946', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'nancymilenas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sorayarivas43@gmail.com', documento_identidad = '43692783', departamento_id = 1, municipio_id = 45, updated_at = NOW() WHERE email = 'sorayarivas43@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mayito131901@gmail.com', documento_identidad = '43693751', departamento_id = 1, municipio_id = 45, updated_at = NOW() WHERE email = 'mayito131901@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yolylopezh071975@outlook.es', documento_identidad = '43818133', departamento_id = 8, municipio_id = 409, updated_at = NOW() WHERE email = 'yolylopezh071975@outlook.es' AND tenant_id = 1;
UPDATE users SET name = 'gloriaelo.2007@yahoo.es', documento_identidad = '43831817', departamento_id = 1, municipio_id = 42, updated_at = NOW() WHERE email = 'gloriaelo.2007@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'luni_11@hotmail.com', documento_identidad = '45429011', departamento_id = 8, municipio_id = 415, updated_at = NOW() WHERE email = 'luni_11@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'canachury@gmail.com', documento_identidad = '45448391', updated_at = NOW() WHERE email = 'canachury@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gilsantamaria26@gmail.com', documento_identidad = '45462434', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'gilsantamaria26@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jac18deenero@yahoo.com', documento_identidad = '45464033', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'jac18deenero@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'dione.astudillo.orozco@gmail.com', documento_identidad = '45476552', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'dione.astudillo.orozco@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'claudiamezavillalba34@gmail.com', documento_identidad = '45484172', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'claudiamezavillalba34@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'isabel.gomezmedina13@gmail.com', documento_identidad = '45577028', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'isabel.gomezmedina13@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'LUMSILGADO@GMAIL.COM', documento_identidad = '45693712', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'LUMSILGADO@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'azucena301268@gmail.com', documento_identidad = '46363720', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'azucena301268@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dianaruiz80@hotmail.com', documento_identidad = '46452047', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'dianaruiz80@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ianapao@gmail.com', documento_identidad = '46457377', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'ianapao85@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'marlen196506@gmail.com', documento_identidad = '46664244', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'marlen196506@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alvarezdexy47@gmail.com', documento_identidad = '49552581', departamento_id = 2, municipio_id = 127, updated_at = NOW() WHERE email = 'alvarezdexy47@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jujugay@hotmail.com', documento_identidad = '49655647', departamento_id = 7, municipio_id = 383, updated_at = NOW() WHERE email = 'jujugay@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lolinamartinez2016@gmail.com', documento_identidad = '49729636', departamento_id = 7, municipio_id = 390, updated_at = NOW() WHERE email = 'lolinamartinez2016@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzstellarojashinojosa@gmail.com', documento_identidad = '49744546', departamento_id = 7, municipio_id = 386, updated_at = NOW() WHERE email = 'luzstellarojashinojosa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edilsacharris@gmail.com', documento_identidad = '49744849', departamento_id = 25, updated_at = NOW() WHERE email = 'edilsacharris@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bettymejiagamarra988@gmail.com', documento_identidad = '49746809', departamento_id = 7, municipio_id = 392, updated_at = NOW() WHERE email = 'bettymejiagamarra988@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mariage.stilo@gmail.com', documento_identidad = '49757114', departamento_id = 7, municipio_id = 383, updated_at = NOW() WHERE email = 'mariage.stilo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nerytorrenegra@gmail.com', documento_identidad = '49762687', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'nerytorrenegra@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anagerardino48@gmail.com', documento_identidad = '49768852', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'anagerardino48@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'osmanyvanessa@gmail.com', documento_identidad = '50908999', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'osmanyvanessa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anamilenaacevedogomez39@gmail.com', documento_identidad = '50957971', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'anamilenaacevedogomez39@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mariaemmaramirezrodriguez20@gmail.com', documento_identidad = '51561898', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'mariaemmaramirezrodriguez20@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marinareyesvivas@gmail.com', documento_identidad = '51571730', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'marinareyesvivas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nohorasuarezcuellar8@gmail.com', documento_identidad = '51574032', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'nohorasuarezcuellar8@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elsaparra623@gmail.com', documento_identidad = '51576005', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'elsaparra623@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gloriastellamorenof2017@gmail.com', documento_identidad = '51590081', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'gloriastellamorenof2017@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elizblanc128@gmail.com', documento_identidad = '51638804', departamento_id = 9, municipio_id = 445, updated_at = NOW() WHERE email = 'elizblanc128@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'diana.penarete@gmail.com', documento_identidad = '51682884', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'diana.penarete@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'guerraortizluzyamile@gmail.com', documento_identidad = '51717039', departamento_id = 9, municipio_id = 477, updated_at = NOW() WHERE email = 'guerraortizluzyamile@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luznmona@hotmail.com', documento_identidad = '51724248', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'luznmona@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'blankines06@gmail.com', documento_identidad = '51735022', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'blankines06@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'negretmonica@hotmail.com', documento_identidad = '51766280', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'negretmonica@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mvictoriacordoba@gmail.com', documento_identidad = '51782012', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'mvictoriacordoba@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fajoal65@gmail.com', documento_identidad = '51784999', departamento_id = 9, updated_at = NOW() WHERE email = 'fajoal65@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Janethzabaleta@gmail.com', documento_identidad = '51789099', departamento_id = 9, updated_at = NOW() WHERE email = 'Janethzabaleta@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'odiliamoncadaariza@gmail.com', documento_identidad = '51819649', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'odiliamoncadaariza@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'martha.castellanos@gmail.com', documento_identidad = '51839772', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'martha.castellanos@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'notoma2000@yahoo.com', documento_identidad = '51846572', departamento_id = 21, municipio_id = 937, updated_at = NOW() WHERE email = 'notoma2000@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'abogdamparo@gmail.com', documento_identidad = '51854050', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'abogdamparo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'blanca.ballen03@gmail.com', documento_identidad = '51892262', departamento_id = 18, municipio_id = 841, updated_at = NOW() WHERE email = 'blanca.ballen03@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hadagoju@gmail.com', documento_identidad = '51901977', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'hadagoju@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'daditis12@gmail.com', documento_identidad = '51903291', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'daditis12@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nechy.montana@gmail.com', documento_identidad = '51905849', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'nechy.montana@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'albanidiacortes77@gmail.com', documento_identidad = '51915047', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'albanidiacortes77@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rodriguezmorenonubia@gmail.com', documento_identidad = '51977370', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'rodriguezmorenonubia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lilimonf@gmail.com', documento_identidad = '51978238', departamento_id = 20, municipio_id = 913, updated_at = NOW() WHERE email = 'lilimonf@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'delitamari@gmail.com', documento_identidad = '51988627', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'delitamari@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'neramirezortiz@gmail.com', documento_identidad = '51992756', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'neramirezortiz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sandrajubelly@gmail.com', documento_identidad = '52008464', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'sandrajubelly@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'utopia19col@hotmail.com', documento_identidad = '52036260', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'utopia19col@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Luzstelladiaz2909@gmail.com', documento_identidad = '52076996', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'Luzstelladiaz2909@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mastegari1129@hotmail.com', documento_identidad = '52108110', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'mastegari1129@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'claragcabrales@gmail.com', documento_identidad = '52115218', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'claragcabrales@gmail.com' AND tenant_id = 1;
UPDATE users SET name = '52myriam@gmail.com', documento_identidad = '52161159', departamento_id = 26, municipio_id = 1044, updated_at = NOW() WHERE email = '52myriam@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'myriamstellapg@hotmail.com', documento_identidad = '52170545', departamento_id = 35, municipio_id = 1160, updated_at = NOW() WHERE email = 'myriamstellapg@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zaidani@gmail.com', documento_identidad = '52231115', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'zaidani@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzmaritzaparra2016@gmail.com', documento_identidad = '52282729', departamento_id = 12, municipio_id = 605, updated_at = NOW() WHERE email = 'luzmaritzaparra2016@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yenlygalindoj@gmail.com', documento_identidad = '52289235', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'yenlygalindoj@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'adeportesayd@gmail.com', documento_identidad = '52295717', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'adeportesayd@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'confisistemas@gmail.com', documento_identidad = '52311774', departamento_id = 24, municipio_id = 1009, updated_at = NOW() WHERE email = 'confisistemas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'd.aldana@hotmail.com', documento_identidad = '52319815', departamento_id = 9, municipio_id = 447, updated_at = NOW() WHERE email = 'd.aldana@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camacholuzdary40@gmail.com', documento_identidad = '52339011', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'camacholuzdary40@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hermosacharlotte@gmail.com', documento_identidad = '52344276', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'hermosacharlotte@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'catherinecastellanos@gmail.com', documento_identidad = '52381309', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'catherinecastellanos@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'moninaarp@gmail.com', documento_identidad = '52409453', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'moninaarp@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anniemaya7@gmail.com', documento_identidad = '52515077', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'anniemaya7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'redlideresasdecolombia@gmail.com', documento_identidad = '52705609', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'redlideresasdecolombia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisafhergc@gmail.com', documento_identidad = '52728315', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'luisafhergc@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'johannaangel179@gmail.com', documento_identidad = '52741825', departamento_id = 4, municipio_id = 235, updated_at = NOW() WHERE email = 'johannaangel179@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'martharengifomontealegre@gmail.com', documento_identidad = '52778547', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'martharengifomontealegre@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carcovar@gmail.com', documento_identidad = '52849517', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'carcovar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'julika.sanchez09@gmail.com', documento_identidad = '52914839', departamento_id = 18, municipio_id = 841, updated_at = NOW() WHERE email = 'julika.sanchez09@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ingrid.juliana.pabon@gmail.com', documento_identidad = '52951847', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'ingrid.juliana.pabon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'trabajosocialcomunitario2014@gmail.com', documento_identidad = '52960241', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'trabajosocialcomunitario2014@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'julianamariarodriguez@gmail.com', documento_identidad = '53000622', departamento_id = 9, municipio_id = 548, updated_at = NOW() WHERE email = 'julianamariarodriguez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lorenaurrea2023@gmail.com', documento_identidad = '53003679', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'lorenaurrea2023@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mujer.productiva.imparable@gmail.com', documento_identidad = '53040064', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'mujer.productiva.imparable@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'karenjb1984@gmail.com', documento_identidad = '53053495', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'karenjb1984@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dianampayan21@gmail.com', documento_identidad = '53129421', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'dianampayan21@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'taticaamado7@gmail.com', documento_identidad = '53907390', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'taticaamado7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ecastiblanco31@gmail.com', documento_identidad = '53911686', departamento_id = 9, municipio_id = 442, updated_at = NOW() WHERE email = 'ecastiblanco31@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'espinosagema14@gmail.com', documento_identidad = '55157552', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'espinosagema14@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elisych30@gmail.com', documento_identidad = '56059132', departamento_id = 7, municipio_id = 392, updated_at = NOW() WHERE email = 'elisych30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fabiola.me@hotmail.com', documento_identidad = '57421123', departamento_id = 13, municipio_id = 618, updated_at = NOW() WHERE email = 'fabiola.me@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pablahidalgo05@gmail.com', documento_identidad = '57425241', departamento_id = 7, municipio_id = 396, updated_at = NOW() WHERE email = 'pablahidalgo05@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'medelcae.43@gmail.com', documento_identidad = '59665207', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'medelcae.43@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'glopiduri2006@hotmail.com', documento_identidad = '60252097', departamento_id = 16, municipio_id = 748, updated_at = NOW() WHERE email = 'glopiduri2006@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tamaramongui7@gmail.com', documento_identidad = '60253283', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'tamaramongui7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzstellacontrerassanchez@gmail.com', documento_identidad = '60292926', departamento_id = 16, municipio_id = 762, updated_at = NOW() WHERE email = 'luzstellacontrerassanchez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edelramirez06@gmail.com', documento_identidad = '60348070', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'edelramirez06@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'blancaacevedo352@gmail.com', documento_identidad = '60405697', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'blancaacevedo352@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'santodomigogeraldina@gmail.com', documento_identidad = '63288063', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'santodomigogeraldina@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nellybenua@hotmail.com', documento_identidad = '63301454', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'nellybenua@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gilyova@hotmail.com', documento_identidad = '63303157', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'gilyova@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hermanita.0216@gmail.com', documento_identidad = '63326547', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'hermanita.0216@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'LIBADI50@GMAIL.COM', documento_identidad = '63346386', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'LIBADI50@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'chrispam70@gmail.com', documento_identidad = '63352088', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'chrispam70@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'consultora.aponte.claudia@gmail.com', documento_identidad = '63357302', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'consultora.aponte.claudia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mildrethsuarezdiaz@gmail.com', documento_identidad = '63366262', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'mildrethsuarezdiaz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'annapatino541@gmail.com', documento_identidad = '63439533', departamento_id = 18, municipio_id = 835, updated_at = NOW() WHERE email = 'annapatino541@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fatima401971@hotmail.com', documento_identidad = '63460127', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'fatima401971@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'malenajai@gmail.com', documento_identidad = '63511544', departamento_id = 18, municipio_id = 810, updated_at = NOW() WHERE email = 'malenajai@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'amiramoma@hotmail.com', documento_identidad = '64515584', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'amiramoma@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vivigarpadi@gmail.com', documento_identidad = '64552033', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'vivigarpadi@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marthaferia490@gmail.com', documento_identidad = '64564152', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'marthaferia490@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'chamorrodiana954@gmail.com', documento_identidad = '64696954', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'chamorrodiana954@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Mayosuaresgamarra@gmail.com', documento_identidad = '64717678', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'Mayosuaresgamarra@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anasotelo784@gmail.com', documento_identidad = '64721991', departamento_id = 19, municipio_id = 878, updated_at = NOW() WHERE email = 'anasotelo784@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mariadelrosarioborja@gmail.com', documento_identidad = '65740043', departamento_id = 20, municipio_id = 924, updated_at = NOW() WHERE email = 'mariadelrosarioborja@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'diajor1969@hotmail.com', documento_identidad = '65743853', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'diajor1969@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carolinabed9@gmail.com', documento_identidad = '65779526', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'carolinabed9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nubiagallo07@gmail.com', documento_identidad = '66679329', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'nubiagallo07@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mariaoque1566@gmail.com', documento_identidad = '66710798', departamento_id = 21, municipio_id = 970, updated_at = NOW() WHERE email = 'mariaoque1566@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'toteolmedo@gmail.com', documento_identidad = '66733955', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'toteolmedo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'clauposs1973@gmail.com', documento_identidad = '66751845', departamento_id = 21, municipio_id = 959, updated_at = NOW() WHERE email = 'clauposs1973@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'assiluycoquito@gmail.com', documento_identidad = '66777328', departamento_id = 21, municipio_id = 942, updated_at = NOW() WHERE email = 'assiluycoquito@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'idesmarinaramirez48@gmail.com', documento_identidad = '66831384', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'idesmarinaramirez48@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'uribe1937@hotmail.com', documento_identidad = '66837798', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'uribe1937@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'martha.gonzalez.240605@gmail.com', documento_identidad = '66838314', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'martha.gonzalez.240605@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzmarym2471@gmail.com', documento_identidad = '66850644', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'luzmarym2471@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'buga.federacion21@gmail.com', documento_identidad = '66862430', departamento_id = 21, municipio_id = 942, updated_at = NOW() WHERE email = 'buga.federacion21@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzenith714@gmail.com', documento_identidad = '66871457', departamento_id = 21, municipio_id = 940, updated_at = NOW() WHERE email = 'luzenith714@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cordobajohac@gmail.com', documento_identidad = '66873726', departamento_id = 21, municipio_id = 965, updated_at = NOW() WHERE email = 'cordobajohac@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'p.adrymorales28@gmail.com', documento_identidad = '66905706', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'p.adrymorales28@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'katherinemejia415@gmail.com', documento_identidad = '66914672', updated_at = NOW() WHERE email = 'katherinemejia415@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'proyectomjrl@gmail.com', documento_identidad = '66925219', departamento_id = 21, municipio_id = 959, updated_at = NOW() WHERE email = 'proyectomjrl@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Sugeymaria12nela@gmail.com', documento_identidad = '66940270', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'Sugeymaria12nela@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lilibaro15@gmail.com', documento_identidad = '66983716', departamento_id = 21, updated_at = NOW() WHERE email = 'lilibaro15@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ubaldinaguilar82@gmail.com', documento_identidad = '68306256', departamento_id = 22, municipio_id = 981, updated_at = NOW() WHERE email = 'ubaldinaguilar82@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nubiaenriquez1977@gmail.com', documento_identidad = '69007315', departamento_id = 31, municipio_id = 1097, updated_at = NOW() WHERE email = 'nubiaenriquez1977@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'monicavaron81@gmail.com', documento_identidad = '69029449', departamento_id = 31, municipio_id = 1089, updated_at = NOW() WHERE email = 'monicavaron81@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Saravedo70@gmail.com', documento_identidad = '70030310', departamento_id = 1, municipio_id = 14, updated_at = NOW() WHERE email = 'Saravedo70@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgemejiama@gmail.com', documento_identidad = '70037431', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'jorgemejiama@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kbedoyac@yahoo.es', documento_identidad = '70087401', departamento_id = 1, municipio_id = 117, updated_at = NOW() WHERE email = 'kbedoyac@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'josebarrios910@hotmail.com', documento_identidad = '70111282', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'josebarrios910@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juancato1962@gmail.com', documento_identidad = '70133556', departamento_id = 1, municipio_id = 18, updated_at = NOW() WHERE email = 'juancato1962@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jjaimeposadagomez@gmail.com', documento_identidad = '70163005', departamento_id = 1, municipio_id = 90, updated_at = NOW() WHERE email = 'jjaimeposadagomez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ferdorozco@gmail.com', documento_identidad = '70500823', departamento_id = 1, municipio_id = 52, updated_at = NOW() WHERE email = 'ferdorozco@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'quirozqalonso@gmail.com', documento_identidad = '70502009', departamento_id = 1, municipio_id = 52, updated_at = NOW() WHERE email = 'quirozqalonso@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elmer_obregon01@hotmail.com', documento_identidad = '70523204', departamento_id = 1, municipio_id = 15, updated_at = NOW() WHERE email = 'elmer_obregon01@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fabergarcia2802@gmail.com', documento_identidad = '70728896', departamento_id = 1, municipio_id = 64, updated_at = NOW() WHERE email = 'fabergarcia2802@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jaimemongo1@gmail.com', documento_identidad = '71082777', departamento_id = 1, municipio_id = 105, updated_at = NOW() WHERE email = 'jaimemongo1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'movimientoriosvivoscolombia@gmail.com', documento_identidad = '71375811', departamento_id = 1, municipio_id = 112, updated_at = NOW() WHERE email = 'movimientoriosvivoscolombia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'danilosuarezg@gmail.com', documento_identidad = '71385374', departamento_id = 1, municipio_id = 15, updated_at = NOW() WHERE email = 'danilosuarezg@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'osbaylopez@gmail.com', documento_identidad = '71411247', departamento_id = 1, updated_at = NOW() WHERE email = 'osbaylopez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gabrieljaime3175@gmail.com', documento_identidad = '71578658', departamento_id = 1, municipio_id = 34, updated_at = NOW() WHERE email = 'gabrieljaime3175@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'iocampo473@hotmail.com', documento_identidad = '71606473', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'iocampo473@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'njra2@yahoo.es', documento_identidad = '71647903', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'njra2@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'ghrcontador@gmail.com', documento_identidad = '71694602', departamento_id = 8, updated_at = NOW() WHERE email = 'ghrcontador@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'danielgaleanorojas@gmail.com', documento_identidad = '71699269', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'danielgaleanorojas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'conra2car@yahoo.com', documento_identidad = '71704588', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'conra2car@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'davidramirezmontoya@gmail.com', documento_identidad = '71778075', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'davidramirezmontoya@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'info@jhonlemos.com', documento_identidad = '71782656', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'info@jhonlemos.com' AND tenant_id = 1;
UPDATE users SET name = 'Jotama85@gmail.com', documento_identidad = '71801531', departamento_id = 1, municipio_id = 112, updated_at = NOW() WHERE email = 'Jotama85@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dariolopezperez@gmail.com', documento_identidad = '71944853', departamento_id = 33, municipio_id = 1109, updated_at = NOW() WHERE email = 'dariolopezperez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'manuelpenabogado@gmail.com', documento_identidad = '72012665', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'manuelpenabogado@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cacs1227@gmail.com', documento_identidad = '72018024', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'cacs1227@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'evertorregrosa@hotmail.com', documento_identidad = '72070698', departamento_id = 2, municipio_id = 132, updated_at = NOW() WHERE email = 'evertorregrosa@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jrangulop@yahoo.es', documento_identidad = '72126847', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'jrangulop@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'fredisdiaz@hotmail.com', documento_identidad = '72155590', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'fredisdiaz@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'algutil72@gmail.com', documento_identidad = '72196960', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'algutil72@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'neyltorres12345@gmail.com', documento_identidad = '72199522', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'neyltorres12345@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luismanuelmercado@yahoo.com', documento_identidad = '72211989', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'luismanuelmercado@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'dustindonado@gmail.com', documento_identidad = '72428085', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'dustindonado@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisedat@hotmail.com', documento_identidad = '73091895', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'luisedat@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pedroantonioortiz@yahoo.es', documento_identidad = '73094529', departamento_id = 8, municipio_id = 417, updated_at = NOW() WHERE email = 'pedroantonioortiz@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'carretaljmo@gmail.com', documento_identidad = '73106371', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'carretaljmo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marcosvecar@gmail.com', documento_identidad = '73121832', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'marcosvecar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gonimajosefe@gmail.com', documento_identidad = '73126801', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'gonimajosefe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'olick-6917@hotmail.com', documento_identidad = '73130669', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'olick-6917@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dominguez22omar22@gmail.com', documento_identidad = '73139816', departamento_id = 3, municipio_id = 150, updated_at = NOW() WHERE email = 'lgbtiqamontesdemaria@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arparo270@hotmail.com', documento_identidad = '73141210', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'arparo270@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisalbertobarriossuarez@hotmail.com', documento_identidad = '73226301', departamento_id = 3, municipio_id = 177, updated_at = NOW() WHERE email = 'luisalbertobarriosuarez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aramisrafaelpaez@gmail.com', documento_identidad = '73269062', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'aramisrafaelpaez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josehilariopadillas@gmail.com', documento_identidad = '73562322', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'josehilariopadillas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marrugopolojorge@gmail.com', documento_identidad = '73581233', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'marrugopolojorge@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'martinroblesrodriguez56@gmail.com', documento_identidad = '73593649', departamento_id = 7, municipio_id = 391, updated_at = NOW() WHERE email = 'martinroblesrodriguez56@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'johnaza2009@gmail.com', documento_identidad = '74357335', departamento_id = 4, municipio_id = 206, updated_at = NOW() WHERE email = 'johnaza2009@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edwinsagliano79@gmail.com', documento_identidad = '74374042', departamento_id = 9, municipio_id = 518, updated_at = NOW() WHERE email = 'edwinsagliano79@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edwinfgomezsisa@gmail.com', documento_identidad = '74376360', departamento_id = 4, municipio_id = 309, updated_at = NOW() WHERE email = 'edwinfgomezsisa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'krlos0327@gmail.com', documento_identidad = '74849372', departamento_id = 24, municipio_id = 1008, updated_at = NOW() WHERE email = 'krlos0327@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eivaryair2012@gmail.com', documento_identidad = '74866517', departamento_id = 24, municipio_id = 1017, updated_at = NOW() WHERE email = 'eivaryair2012@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rodolfocuellar353@gmail.com', documento_identidad = '76271277', departamento_id = 6, municipio_id = 365, updated_at = NOW() WHERE email = 'rodolfocuellar353@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlospazs@unicauca.edu.co', documento_identidad = '76316597', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'carlospazs@unicauca.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'andremaiz@outlook.es', documento_identidad = '76319863', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'andremaiz@outlook.es' AND tenant_id = 1;
UPDATE users SET name = 'jemq0624@hotmail.com', documento_identidad = '76334035', departamento_id = 6, municipio_id = 380, updated_at = NOW() WHERE email = 'jemq0624@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'borismoscote08@gmail.com', documento_identidad = '77039601', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'borismoscote08@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'racs1970@gmail.com', documento_identidad = '77154205', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'racs1970@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'negrosdelasierranevada@gmail.com', documento_identidad = '77173185', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'negrosdelasierranevada@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luismparenilla@gmail.com', documento_identidad = '78108317', departamento_id = 8, municipio_id = 408, updated_at = NOW() WHERE email = 'luismparenilla@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jsaenzpadilla@hotmail.com', documento_identidad = '78290032', departamento_id = 8, municipio_id = 420, updated_at = NOW() WHERE email = 'jsaenzpadilla@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yoms123@gmail.com', documento_identidad = '78381385', departamento_id = 8, municipio_id = 435, updated_at = NOW() WHERE email = 'yoms123@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'miguelandreslopez7106@gmail.com', documento_identidad = '78675234', departamento_id = 8, municipio_id = 415, updated_at = NOW() WHERE email = 'miguelandreslopez7106@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dberriocogollo@gmail.com', documento_identidad = '78696027', departamento_id = 8, municipio_id = 434, updated_at = NOW() WHERE email = 'dberriocogollo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eliasnaturismo2@gmail.com', documento_identidad = '78698209', departamento_id = 1, municipio_id = 94, updated_at = NOW() WHERE email = 'eliasnaturismo2@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'judicialhunter@hotmail.com', documento_identidad = '78740096', departamento_id = 8, municipio_id = 427, updated_at = NOW() WHERE email = 'judicialhunter@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'apicoolivos05@gmail.com', documento_identidad = '79104503', departamento_id = 1, municipio_id = 124, updated_at = NOW() WHERE email = 'apicoolivos05@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlosmasso079@gmail.com', documento_identidad = '79108181', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'carlosmasso079@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'germanviana1@hotmail.com', documento_identidad = '79113138', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'germanviana1@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'felixamin@gmail.com', documento_identidad = '79113973', departamento_id = 1, municipio_id = 13, updated_at = NOW() WHERE email = 'felixamin@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ramaclaro2@gmail.com', documento_identidad = '79118950', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'ramaclaro2@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'albate2k@gmail.com', documento_identidad = '79150340', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'albate2k@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'info_relpro@yahoo.com', documento_identidad = '79189275', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'info_relpro@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'sara_paola_2011@outlook.com', documento_identidad = '79208861', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'sara_paola_2011@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'catfelino01@gmail.com', documento_identidad = '79242215', departamento_id = 9, updated_at = NOW() WHERE email = 'catfelino01@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tangovida@gmail.com', documento_identidad = '79243657', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'tangovida@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gerrota62@gmail.com', documento_identidad = '79257976', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'gerrota62@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joalberth2009@gmail.com', documento_identidad = '79265493', departamento_id = 22, municipio_id = 978, updated_at = NOW() WHERE email = 'joalberth2009@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luferna2005@yahoo.es', documento_identidad = '79309882', departamento_id = 35, municipio_id = 1280, updated_at = NOW() WHERE email = 'luferna2005@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'kelmeallist@gmail.com', documento_identidad = '79328285', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'kelmeallist@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'colombiahumanlajaguadeibirico@gmail.com', documento_identidad = '79341837', departamento_id = 7, municipio_id = 396, updated_at = NOW() WHERE email = 'pyk_company@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'buenergesv@gmail.com', documento_identidad = '79348466', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'buenergesv@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josedario037@gmail.com', documento_identidad = '79369813', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'josedario037@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fercente65@gmail.com', documento_identidad = '79381036', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'fercente65@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edgarroblesfonnegra@gmail.com', documento_identidad = '79388409', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'edgarroblesfonnegra@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'orlandosilvafigueroa@hotmail.com', documento_identidad = '79419283', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'orlandosilvafigueroa@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'facevedosor@gmail.com', documento_identidad = '79430615', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'facevedosor@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'canoreyesjuandejesus@gmail.com', documento_identidad = '79525852', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'juandejesuscanoreyes@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rafanturiospina@gmail.com', documento_identidad = '79536005', departamento_id = 23, municipio_id = 987, updated_at = NOW() WHERE email = 'rafanturiospina@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'willquer71@gmail.com', documento_identidad = '79540499', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'willquer71@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sevencompany1a@hotmail.com', documento_identidad = '79544235', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'sevencompany1a@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hmoscar027@gmail.com', documento_identidad = '79546027', departamento_id = 24, municipio_id = 1013, updated_at = NOW() WHERE email = 'hmoscar027@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jhonpe99@hotmail.com', documento_identidad = '79573001', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'jhonpe99@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'manuel.roab@gmail.com', documento_identidad = '79576515', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'manuel.roab@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mauriciohernandez.ruiz@gmail.com', documento_identidad = '79597810', departamento_id = 9, updated_at = NOW() WHERE email = 'mauriciohernandez.ruiz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'henrypandales@hotmail.com', documento_identidad = '79652761', departamento_id = 11, municipio_id = 550, updated_at = NOW() WHERE email = 'henrypandales@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dr.productosyservicios@gmail.com', documento_identidad = '79663442', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'dr.productosyservicios@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'acuariano762011@gmail.com', documento_identidad = '79741230', departamento_id = 9, updated_at = NOW() WHERE email = 'acuariano762011@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'davidinterventorias@gmail.com', documento_identidad = '79746334', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'davidinterventorias@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pigoyed7@gmail.com', documento_identidad = '79768388', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'pigoyed7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'colectivoorvin@gmail.com', documento_identidad = '79770112', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'colectivoorvin@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pazcienciaycultura@gmail.com', documento_identidad = '79796613', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'pazcienciaycultura@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lasemilladelcambio@gmail.com', documento_identidad = '79829137', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'lasemilladelcambio@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rodriguezlandazuriwilmer@gmail.com', documento_identidad = '79833998', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'rodriguezlandazuriwilmer@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josegura23@hotmail.com', documento_identidad = '79836012', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'josegura23@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'abraxasjota@hotmail.com', documento_identidad = '79856276', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'abraxasjota@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'creacionespopulares@gmail.com', documento_identidad = '79890378', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'creacionespopulares@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'calarka@gmail.com', documento_identidad = '79904194', departamento_id = 24, municipio_id = 1012, updated_at = NOW() WHERE email = 'calarka@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jzabala2004@gmail.com', documento_identidad = '79907360', departamento_id = 20, municipio_id = 922, updated_at = NOW() WHERE email = 'jzabala2004@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'georgeluis0107@gmail.com', documento_identidad = '79951910', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'georgeluis0107@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luiscastillo2772@gmail.com', documento_identidad = '79960151', departamento_id = 33, municipio_id = 1111, updated_at = NOW() WHERE email = 'luiscastillo2772@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nelsoncely1@gmail.com', documento_identidad = '80016728', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'nelsoncely1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jdojeda@concejobogota.gov.co', documento_identidad = '80084230', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'jdojeda@concejobogota.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'ivan.buitrago@correounivalle.edu.co', documento_identidad = '80092795', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'ivan.buitrago@correounivalle.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'davidrojasb38@gmail.com', documento_identidad = '80094741', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'davidrojasb38@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanfloriansilva@gmail.com', documento_identidad = '80097915', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'juanfloriansilva@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'antonioiberlucea@gmail.com', documento_identidad = '80102823', departamento_id = 21, municipio_id = 967, updated_at = NOW() WHERE email = 'antonioiberlucea@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fredyvanegas22050@gmail.com', documento_identidad = '80112013', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'fredyvanegas22050@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eduarabogados@gmail.com', documento_identidad = '80130551', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'eduarabogados@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leonardomolinas@gmail.com', documento_identidad = '80206568', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'leonardomolinas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'moshereforma@gmail.com', documento_identidad = '80214523', departamento_id = 9, municipio_id = 454, updated_at = NOW() WHERE email = 'moshereforma@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 's0ydarwinco@gmail.com', documento_identidad = '80223555', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 's0ydarwinco@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'atodometal@gmail.com', documento_identidad = '80225277', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'atodometal@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pazjulianj@gmail.com', documento_identidad = '80257057', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'pazjulianj@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'goldhyn@hotmail.com', documento_identidad = '80396824', departamento_id = 9, municipio_id = 445, updated_at = NOW() WHERE email = 'goldhyn@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juan.dh.ong@gmail.com', documento_identidad = '80433633', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'juan.dh.ong@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'davortegon@gmail.com', documento_identidad = '80504511', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'davortegon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'daralsanco@hotmail.es', documento_identidad = '80550033', departamento_id = 9, municipio_id = 548, updated_at = NOW() WHERE email = 'daralsanco@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'faguaivanch@gmail.com', documento_identidad = '80725205', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'faguaivanch@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juliroz10@gmail.com', documento_identidad = '80765198', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'juliroz10@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gerardo.olarte@hotmail.com', documento_identidad = '80811405', departamento_id = 18, municipio_id = 815, updated_at = NOW() WHERE email = 'gerardo.olarte@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'armatom27@hotmail.com', documento_identidad = '80820566', departamento_id = 35, municipio_id = 1172, updated_at = NOW() WHERE email = 'armatom27@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'acgesquivel@gmail.com', documento_identidad = '80871583', departamento_id = 9, municipio_id = 512, updated_at = NOW() WHERE email = 'acgesquivel@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yamiid.ramirez@gmail.com', documento_identidad = '80896712', departamento_id = 20, municipio_id = 894, updated_at = NOW() WHERE email = 'yamiid.ramirez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bogotaconaltura@gmail.com', documento_identidad = '80903349', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'bogotaconaltura@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'unadgro@gmail.com', documento_identidad = '80932082', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'unadgro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'francysalamandramartinez@gmail.com', documento_identidad = '82140747', departamento_id = 11, municipio_id = 566, updated_at = NOW() WHERE email = 'francysalamandramartinez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gut82@live.com.mx', documento_identidad = '82382709', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'gut82@live.com.mx' AND tenant_id = 1;
UPDATE users SET name = 'alfonsomicro@hotmail.com', documento_identidad = '83087869', departamento_id = 12, municipio_id = 608, updated_at = NOW() WHERE email = 'alfonsomicro@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'manuelg210@hotmail.com', documento_identidad = '83229640', departamento_id = 12, municipio_id = 603, updated_at = NOW() WHERE email = 'manuelg210@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fredyrojas.fr@hotmail.com', documento_identidad = '83237476', departamento_id = 12, municipio_id = 615, updated_at = NOW() WHERE email = 'fredyrojas.fr@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Carogamaliel845@gmail.com', documento_identidad = '83241992', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'Carogamaliel845@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jose_1583@hotmail.com', documento_identidad = '84094146', departamento_id = 8, municipio_id = 420, updated_at = NOW() WHERE email = 'jose_1583@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'claco100@gmail.com', documento_identidad = '84096569', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'claco100@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'huberto1982@hotmail.com', documento_identidad = '84451133', departamento_id = 13, municipio_id = 617, updated_at = NOW() WHERE email = 'huberto1982@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ronabric@gmail.com', documento_identidad = '84453599', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'ronabric@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'heberto.2mil8@gmail.com', documento_identidad = '85436346', departamento_id = 13, municipio_id = 623, updated_at = NOW() WHERE email = 'heberto.2mil8@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edwingil69@yahoo.es', documento_identidad = '85436370', departamento_id = 7, municipio_id = 383, updated_at = NOW() WHERE email = 'edwingil69@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'gustavomartinezb2210@gmail.com', documento_identidad = '85464429', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'gustavomartinezb2210@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Johnm66@hotmail.es', documento_identidad = '86060796', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'Johnm66@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'jemejiac@unal.edu.co', documento_identidad = '86071034', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'jemejiac@unal.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'jgv.asesorsalud@hotmail.com', documento_identidad = '87190620', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'jgv.asesorsalud@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'soportejuridicovirtual@gmail.com', documento_identidad = '87573461', departamento_id = 14, municipio_id = 642, updated_at = NOW() WHERE email = 'soportejuridicovirtual@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ujh008@gmail.com', documento_identidad = '88000931', departamento_id = 16, municipio_id = 736, updated_at = NOW() WHERE email = 'ujh008@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'miguelrobertonavarro11@gmail.com', documento_identidad = '88136658', departamento_id = 16, municipio_id = 732, updated_at = NOW() WHERE email = 'miguelrobertonavarro11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Fernelbayonaamaya@gmail.com', documento_identidad = '88149848', departamento_id = 16, municipio_id = 753, updated_at = NOW() WHERE email = 'Fernelbayonaamaya@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sancalixto15@hotmail.com', documento_identidad = '88150140', departamento_id = 16, municipio_id = 753, updated_at = NOW() WHERE email = 'sancalixto15@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jesushernanbayonagarcia09@gmail.com', documento_identidad = '88183763', departamento_id = 16, municipio_id = 747, updated_at = NOW() WHERE email = 'jesushernanbayonagarcia09@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'john29godoy@gmail.com', documento_identidad = '88202860', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'john29godoy@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'johnparra1712@gmail.com', documento_identidad = '88209486', departamento_id = 35, updated_at = NOW() WHERE email = 'johnparra1712@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wilson.sepulveda2022@gmail.com', documento_identidad = '88276984', departamento_id = 18, municipio_id = 822, updated_at = NOW() WHERE email = 'wilson.sepulveda2022@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luarpaca61@gmail.com', documento_identidad = '91011617', departamento_id = 23, municipio_id = 986, updated_at = NOW() WHERE email = 'luarpaca61@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'betoariasconcejo47@hotmail.com', documento_identidad = '91101838', departamento_id = 18, municipio_id = 844, updated_at = NOW() WHERE email = 'betoariasconcejo47@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cutmaster1964@hotmail.com', documento_identidad = '91104073', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'cutmaster1964@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'augusto.corzo@hotmail.com', documento_identidad = '91104122', departamento_id = 18, municipio_id = 860, updated_at = NOW() WHERE email = 'augusto.corzo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pejari7@gmail.com', documento_identidad = '91156918', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'pejari7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mantillaxsantander@gmail.com', documento_identidad = '91186063', departamento_id = 18, municipio_id = 810, updated_at = NOW() WHERE email = 'mantillaxsantander@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanjaimes1059@gmail.com', documento_identidad = '91202158', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'juanjaimes1059@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgebenitez1306@gmail.com', documento_identidad = '91204998', departamento_id = 8, municipio_id = 413, updated_at = NOW() WHERE email = 'jorgebenitez1306@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'armandoe8a@gmail.com', documento_identidad = '91210885', departamento_id = 18, municipio_id = 810, updated_at = NOW() WHERE email = 'armandoe8a@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'osedor@hotmail.com', documento_identidad = '91218755', departamento_id = 18, municipio_id = 830, updated_at = NOW() WHERE email = 'osedor@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'antonioverdugopsuv@gmail.com', documento_identidad = '91226862', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'antonioverdugopsuv@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'salvadoravila8787@gmail.com', documento_identidad = '91278996', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'salvadoravila8787@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'colombiahumanasangil@gmail.com', documento_identidad = '91284822', departamento_id = 18, municipio_id = 844, updated_at = NOW() WHERE email = 'colombiahumanasangil@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juancarojas31@gmail.com', documento_identidad = '91299955', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'juancarojas31@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bernalrafaelalberto218@gmail.com', documento_identidad = '91360852', departamento_id = 18, municipio_id = 821, updated_at = NOW() WHERE email = 'bernalrafaelalberto218@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bealga@gmail.com', documento_identidad = '91420953', departamento_id = 4, municipio_id = 308, updated_at = NOW() WHERE email = 'bealga@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jhonvergaraprada123@gmail.com', documento_identidad = '91426275', departamento_id = 3, municipio_id = 182, updated_at = NOW() WHERE email = 'jhonvergaraprada123@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joseluis.mezamafiol@gmail.com', documento_identidad = '91429093', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'joseluis.mezamafiol@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fabiansandooval@gmail.com', documento_identidad = '91519950', departamento_id = 18, municipio_id = 835, updated_at = NOW() WHERE email = 'fabiansandooval@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanaureliogomezosorio19@gmail.com', documento_identidad = '91539781', departamento_id = 7, municipio_id = 386, updated_at = NOW() WHERE email = 'juanaureliogomezosorio19@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgeiriacu@hotmail.com', documento_identidad = '92029315', departamento_id = 19, municipio_id = 884, updated_at = NOW() WHERE email = 'jorgeiriacu@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mangregor21@hotmail.com', documento_identidad = '92227650', departamento_id = 19, municipio_id = 886, updated_at = NOW() WHERE email = 'mangregor21@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorge.1956@live.com', documento_identidad = '92255061', departamento_id = 19, municipio_id = 878, updated_at = NOW() WHERE email = 'jorge.1956@live.com' AND tenant_id = 1;
UPDATE users SET name = 'hermidessuarez15@gmail.com', documento_identidad = '92257435', departamento_id = 19, municipio_id = 868, updated_at = NOW() WHERE email = 'hermidessuarez15@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aristalco.abogado@gmail.com', documento_identidad = '92275601', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'aristalco.abogado@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jcma1709@gmail.com', documento_identidad = '92496626', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'jcma1709@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'roqueruizchavez@gmail.com', documento_identidad = '92498618', departamento_id = 19, municipio_id = 881, updated_at = NOW() WHERE email = 'roqueruizchavez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Wilmer003@gmail.com', documento_identidad = '92500924', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'Wilmer003@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tecnonando_98@hotmail.com', documento_identidad = '92501118', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'tecnonando_98@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rembertobenitez@hotmail.com', documento_identidad = '92502681', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'rembertobenitez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kikeir.64@gmail.com', documento_identidad = '92504295', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'kikeir.64@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'asovictimasd@gmail.com', documento_identidad = '92512050', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'asovictimasd@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Manuruiz1322@gmail.com', documento_identidad = '92520918', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'Manuruiz1322@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'orafae2@gmail.com', documento_identidad = '92527006', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'orafae2@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'osile2009@gmail.com', documento_identidad = '92549585', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'osile2009@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fabjojies@gmail.com', documento_identidad = '92558060', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'fabjojies@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dava-5@hotmail.com', documento_identidad = '92559015', departamento_id = 19, municipio_id = 868, updated_at = NOW() WHERE email = 'dava-5@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hernando942@gmail.com', documento_identidad = '93035036', departamento_id = 20, municipio_id = 917, updated_at = NOW() WHERE email = 'hernando942@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ramirezjaviercali@gmail.com', documento_identidad = '93080935', departamento_id = 20, municipio_id = 905, updated_at = NOW() WHERE email = 'ramirezjaviercali@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorge3204579771@gmail.com', documento_identidad = '93201051', departamento_id = 20, municipio_id = 922, updated_at = NOW() WHERE email = 'jorge3204579771@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gs.colombia@yahoo.com.co', documento_identidad = '93202395', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'gs.colombia@yahoo.com.co' AND tenant_id = 1;
UPDATE users SET name = 'finosyaromas@gmail.com', documento_identidad = '93295081', departamento_id = 12, municipio_id = 584, updated_at = NOW() WHERE email = 'finosyaromas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ingdario16@gmail.com', documento_identidad = '93412281', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'ingdario16@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marisca1468@gmail.com', documento_identidad = '94153686', departamento_id = 21, municipio_id = 940, updated_at = NOW() WHERE email = 'maritza1468@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Juanbiocafe@gmail.com', documento_identidad = '94262156', departamento_id = 21, municipio_id = 952, updated_at = NOW() WHERE email = 'Juanbiocafe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anibal.acosta_2627@yahoo.es', documento_identidad = '94294483', departamento_id = 21, municipio_id = 945, updated_at = NOW() WHERE email = 'anibal.acosta_2627@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'josegregoriodiazgrajales4@gmail.com', documento_identidad = '94351032', departamento_id = 21, municipio_id = 939, updated_at = NOW() WHERE email = 'josegregoriodiazgrajales4@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'huberney62@gmail.com', documento_identidad = '94365722', departamento_id = 21, municipio_id = 940, updated_at = NOW() WHERE email = 'huberney62@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'millanrengifodiego@gmail.com', documento_identidad = '94387055', departamento_id = 21, municipio_id = 940, updated_at = NOW() WHERE email = 'millanrengifodiego@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andretunay@gmail.com', documento_identidad = '94387971', departamento_id = 21, municipio_id = 940, updated_at = NOW() WHERE email = 'andretunay@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jaimealbertoariastrivino43@gmail.com', documento_identidad = '94389721', departamento_id = 21, municipio_id = 965, updated_at = NOW() WHERE email = 'jaimealbertoariastrivino43@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ederleyvargascalle@gmail.com', documento_identidad = '94391784', departamento_id = 21, municipio_id = 943, updated_at = NOW() WHERE email = 'ederleyvargascalle@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bolidosr648@gmail.com', documento_identidad = '94462805', departamento_id = 21, municipio_id = 944, updated_at = NOW() WHERE email = 'bolidosr648@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sanchezherley@gmail.com', documento_identidad = '94480933', departamento_id = 35, municipio_id = 1160, updated_at = NOW() WHERE email = 'sanchezherley@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alexgg12@gmail.com', documento_identidad = '94519595', departamento_id = 21, municipio_id = 962, updated_at = NOW() WHERE email = 'alexgg12@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'abelardo7609@hotmail.com', documento_identidad = '97446294', departamento_id = 31, municipio_id = 1091, updated_at = NOW() WHERE email = 'abelardo7609@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wilsonarteaga54@gmail.com', documento_identidad = '98395205', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'wilsonarteaga54@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'haderalegria@hotmail.com', documento_identidad = '98431287', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'haderalegria@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanmasos21@gmail.com', documento_identidad = '98494216', departamento_id = 1, municipio_id = 14, updated_at = NOW() WHERE email = 'juanmasos21@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gabrieljaimeurrego03@gmail.com', documento_identidad = '98549502', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'gabrieljaimeurrego03@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nautraldiortiz@gmail.com', documento_identidad = '98574919', departamento_id = 28, municipio_id = 1072, updated_at = NOW() WHERE email = 'nautraldiortiz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joseflorezal810@gmail.com', documento_identidad = '98654327', departamento_id = 1, municipio_id = 97, updated_at = NOW() WHERE email = 'joseflorezal810@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanerzhy@gmail.com', documento_identidad = '98765912', departamento_id = 1, municipio_id = 48, updated_at = NOW() WHERE email = 'juanerzhy@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alexandertinez1@gmail.com', documento_identidad = '1000049285', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'alexandertinez1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jdtunjop2015t@gmail.com', documento_identidad = '1000119462', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'jdtunjop2015t@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camilo.vargasmoncada21@gmail.com', documento_identidad = '1000136012', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'camilo.vargasmoncada21@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jaircup02@outlook.com', documento_identidad = '1000379960', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'jaircup02@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'romarofu.jal@gmail.com', documento_identidad = '1000721826', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'romarofu.jal@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andrew3597.afvs@gmail.com', documento_identidad = '1000854996', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'andrew3597.afvs@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'stevearmero@gmail.com', documento_identidad = '1001043004', departamento_id = 35, municipio_id = 1120, updated_at = NOW() WHERE email = 'stevearmero@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'castanomonsalvea@gmail.com', documento_identidad = '1001468514', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'castanomonsalvea@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josecarloshear15@gmail.com', documento_identidad = '1001904538', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'josecarloshear15@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sharick.maria.17@gmail.com', documento_identidad = '1001934680', departamento_id = 2, municipio_id = 146, updated_at = NOW() WHERE email = 'sharick.maria.17@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Jtinoco@unicesar.edu.co', documento_identidad = '1002187308', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'Jtinoco@unicesar.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'camilogm2019@gmail.com', documento_identidad = '1002207029', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'camilogm2019@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jdra26@outlook.es', documento_identidad = '1002247837', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'jdra26@outlook.es' AND tenant_id = 1;
UPDATE users SET name = 'm.j.m.t@outlook.com', documento_identidad = '1002390370', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'm.j.m.t@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'ximeavellar@gmail.com', documento_identidad = '1002437119', departamento_id = 4, municipio_id = 215, updated_at = NOW() WHERE email = 'ximeavellar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jninocaicedo@gmail.com', documento_identidad = '1002457175', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'jninocaicedo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'salguerodaniel1002@gmail.com', documento_identidad = '1002959707', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'salguerodaniel1002@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'santiagoguerrero148@gmail.com', documento_identidad = '1002967714', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'santiagoguerrero148@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'valenciayurani520@gmail.com', documento_identidad = '1004877387', departamento_id = 21, municipio_id = 956, updated_at = NOW() WHERE email = 'valenciayurani520@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'obando.0129@gmail.com', documento_identidad = '1004879569', departamento_id = 16, municipio_id = 732, updated_at = NOW() WHERE email = 'obando.0129@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mendozavanessa567@gmail.com', documento_identidad = '1005045419', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'mendozavanessa567@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'theylorvillegas2511@gmail.com', documento_identidad = '1005045465', departamento_id = 16, municipio_id = 759, updated_at = NOW() WHERE email = 'theylorvillegas2511@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'javier.econtreras89@gmail.com', documento_identidad = '1005181798', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'javier.econtreras89@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'garciaclavijojuanfelipe147@gmail.com', documento_identidad = '1005483686', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'garciaclavijojuanfelipe147@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'natalia.tascon.casas@gmail.com', documento_identidad = '1005705832', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'natalia.tascon.casas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yersoncamilomd@gmail.com', documento_identidad = '1006459428', departamento_id = 23, municipio_id = 992, updated_at = NOW() WHERE email = 'yersoncamilomd@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tapisr@hotmail.com', documento_identidad = '1007110366', departamento_id = 1, municipio_id = 60, updated_at = NOW() WHERE email = 'tapisr@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Pamelaramoz.292001@gmail.com', documento_identidad = '1007684908', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'Pamelaramoz.292001@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jacomerangelalvaro@gmail.com', documento_identidad = '1007959673', departamento_id = 16, municipio_id = 724, updated_at = NOW() WHERE email = 'jacomerangelalvaro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elchupagiraldo@gmail.com', documento_identidad = '1010090420', departamento_id = 35, municipio_id = 1176, updated_at = NOW() WHERE email = 'elchupagiraldo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jgomezvidal30@outlook.es', documento_identidad = '1010128989', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'jgomezvidal30@outlook.es' AND tenant_id = 1;
UPDATE users SET name = 'jcpereira@unicesar.edu.co', documento_identidad = '1010134433', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'jcpereira@unicesar.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'abadia.comunicaciones@gmail.com', documento_identidad = '1010196031', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'abadia.comunicaciones@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'paula-2111@hotmail.com', documento_identidad = '1010226624', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'paula-2111@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Instrueoz@gmail.com', documento_identidad = '1012320600', departamento_id = 32, municipio_id = 1102, updated_at = NOW() WHERE email = 'Instrueoz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejosanchez1904@gmail.com', documento_identidad = '1012374464', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'alejosanchez1904@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ruth_6816@hotmail.com', documento_identidad = '1012397797', departamento_id = 1, municipio_id = 14, updated_at = NOW() WHERE email = 'ruth_6816@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'crof656@hotmail.com', documento_identidad = '1012428337', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'crof656@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edferroa@unal.edu.co', documento_identidad = '1014227393', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'edferroa@unal.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'vladimirmalambosarmiento@gmail.com', documento_identidad = '1014237086', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'vladimirmalambosarmiento@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fab.cast.ard@gmail.com', documento_identidad = '1014247093', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'fab.cast.ard@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'diegoaroca25@gmail.com', documento_identidad = '1014297051', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'diegoaroca25@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'avsalamancam@unal.edu.co', documento_identidad = '1015423761', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'avsalamancam@unal.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'nicromante87@gmail.com', documento_identidad = '1015457600', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'nicromante87@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'victorparede15@gmail.com', documento_identidad = '1017234927', departamento_id = 1, municipio_id = 75, updated_at = NOW() WHERE email = 'victorparede15@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maesar87@gmail.com', documento_identidad = '1018414159', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'maesar87@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'casierramo88@gmail.com', documento_identidad = '1018418263', departamento_id = 9, updated_at = NOW() WHERE email = 'casierramo88@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eamacondo@gmail.com', documento_identidad = '1018471511', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'eamacondo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alexsanderforero@gmail.com', documento_identidad = '1018486123', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'alexsanderforero@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'msalcedom@unal.edu.co', documento_identidad = '1019012072', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'msalcedom@unal.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'geanbu@hotmail.com', documento_identidad = '1019012952', departamento_id = 20, municipio_id = 922, updated_at = NOW() WHERE email = 'geanbu@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vela.lina@gmail.com', documento_identidad = '1019020101', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'vela.lina@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lyineth.hernandezr@gmail.com', documento_identidad = '1019022853', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'lyineth.hernandezr@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gomezmanriqueabogada@gmail.com', documento_identidad = '1020445747', departamento_id = 1, municipio_id = 18, updated_at = NOW() WHERE email = 'gomezmanriqueabogada@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sorenrf92@gmail.com', documento_identidad = '1020450681', departamento_id = 1, municipio_id = 14, updated_at = NOW() WHERE email = 'sorenrf92@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'saskiavasquezm@gmail.com', documento_identidad = '1020828223', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'saskiavasquezm@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'valentina-nb@hotmail.com', documento_identidad = '1020843981', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'valentina-nb@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorangel003@gmail.com', documento_identidad = '1022348821', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'jorangel003@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lalisarual90@gmail.com', documento_identidad = '1022366000', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'lalisarual90@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgeeliezerdelcastillo@gmail.com', documento_identidad = '1022396213', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'jorgeeliezerdelcastillo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gersoncanong@gmail.com', documento_identidad = '1022405115', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'gersoncanong@gmail.com' AND tenant_id = 1;
UPDATE users SET name = '3MALDANAM@GMAIL.COM', documento_identidad = '1022930175', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = '3MALDANAM@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'tejadaaleyedinson@gmail.com', documento_identidad = '1022955919', departamento_id = 23, municipio_id = 993, updated_at = NOW() WHERE email = 'tejadaaleyedinson@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vmaleja.avm@gmail.com', documento_identidad = '1023003795', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'vmaleja.avm@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rocioguerrerolopez1@gmail.com', documento_identidad = '1023909365', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'rocioguerrerolopez1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kattaeap@hotmail.com', documento_identidad = '1024497338', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'kattaeap@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'redinterdejovlgbtiq@gmail.com', documento_identidad = '1024509591', departamento_id = 9, municipio_id = 456, updated_at = NOW() WHERE email = 'redinterdejovlgbtiq@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yeseniamr1991@gmail.com', documento_identidad = '1024526677', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'yeseniamr1991@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dignidadabolicionista@gmail.com', documento_identidad = '1026262842', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'dignidadabolicionista@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'abogadasanabria23@gmail.com', documento_identidad = '1026292145', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'abogadasanabria23@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'stiff9503@gmail.com', documento_identidad = '1026582238', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'stiff9503@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'christiandreypineda@gmail.com', documento_identidad = '1026594275', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'christiandreypineda@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'giovannyrincons@gmail.com', documento_identidad = '1030554418', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'giovannyrincons@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juacrodriguezmar@unal.edu.co', documento_identidad = '1030576521', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'juacrodriguezmar@unal.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'cgppdiazn@gmail.com', documento_identidad = '1030580427', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'cgppdiazn@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lfelipefajardosegura@gmail.com', documento_identidad = '1030594262', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'lfelipefajardosegura@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ssdianac1@gmail.com', documento_identidad = '1030648843', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'ssdianac1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juliancifuentes.8896@gmail.com', documento_identidad = '1030669197', departamento_id = 9, municipio_id = 479, updated_at = NOW() WHERE email = 'juliancifuentes.8896@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jhdiaz961@gmail.com', documento_identidad = '1030669786', departamento_id = 26, municipio_id = 1043, updated_at = NOW() WHERE email = 'jhdiaz961@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mfespitiat97@gmail.com', documento_identidad = '1031169858', departamento_id = 9, municipio_id = 461, updated_at = NOW() WHERE email = 'mfespitiat97@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hjsalamanca@hotmail.com', documento_identidad = '1032440652', departamento_id = 9, municipio_id = 487, updated_at = NOW() WHERE email = 'hjsalamanca@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aponte.william.lic@gmail.com', documento_identidad = '1032443498', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'aponte.william.lic@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hdariotriana2024@gmail.com', documento_identidad = '1032460476', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'hdariotriana2024@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sebastianbetancourt1994@gmail.com', documento_identidad = '1032463575', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'sebastianbetancourt1994@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'omclopezlo@gmail.com', documento_identidad = '1032471774', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'omclopezlo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'florezdavid277@gmail.com', documento_identidad = '1035305419', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'florezdavid277@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'brittozapatanicolas@gmail.com', documento_identidad = '1035305565', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'brittozapatanicolas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'conexionareiza@gmail.com', documento_identidad = '1035427340', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'conexionareiza@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aleaguasalada@gmail.com', documento_identidad = '1035431016', departamento_id = 1, municipio_id = 40, updated_at = NOW() WHERE email = 'aleaguasalada@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'miguelmedba@gmail.com', documento_identidad = '1035441022', departamento_id = 1, municipio_id = 14, updated_at = NOW() WHERE email = 'miguelmedba@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzadiela96@gmail.com', documento_identidad = '1035873408', departamento_id = 1, municipio_id = 57, updated_at = NOW() WHERE email = 'luzadiela96@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'victormanuelcastroq@gmail.com', documento_identidad = '1035878214', departamento_id = 1, municipio_id = 57, updated_at = NOW() WHERE email = 'victormanuelcastroq@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yeiorozco2009@gmail.com', documento_identidad = '1036399995', departamento_id = 1, municipio_id = 33, updated_at = NOW() WHERE email = 'yeiorozco2009@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'srivera959@gmail.com', documento_identidad = '1037613544', departamento_id = 1, municipio_id = 53, updated_at = NOW() WHERE email = 'srivera959@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'colombiagrupovida@gmail.com', documento_identidad = '1040737017', departamento_id = 1, municipio_id = 64, updated_at = NOW() WHERE email = 'colombiagrupovida@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'migna1999@gmail.com', documento_identidad = '1042461912', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'migna1999@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camiloescorciaalvarez@hotmail.com', documento_identidad = '1043447903', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'camiloescorciaalvarez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Juandviloria51@gmail.com', documento_identidad = '1043931566', departamento_id = 2, municipio_id = 147, updated_at = NOW() WHERE email = 'Juandviloria51@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'paodelahoz30@gmail.com', documento_identidad = '1045703763', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'paodelahoz30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edwin30-@hotmail.com', documento_identidad = '1047417766', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'edwin30-@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Carolinaquintero2802@gmail.com', documento_identidad = '1049644075', departamento_id = 4, municipio_id = 191, updated_at = NOW() WHERE email = 'Carolinaquintero2802@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angie21vega@gmail.com', documento_identidad = '1049657405', departamento_id = 4, municipio_id = 191, updated_at = NOW() WHERE email = 'angie21vega@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lekaort@hotmail.com', documento_identidad = '1050170637', departamento_id = 4, municipio_id = 199, updated_at = NOW() WHERE email = 'lekaort@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vivizsuescun77@gmail.com', documento_identidad = '1052392667', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'vivizsuescun77@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dialesch@hotmail.com', documento_identidad = '1052396325', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'dialesch@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leonardo.holguin77@gmail.com', documento_identidad = '1053587287', departamento_id = 4, municipio_id = 250, updated_at = NOW() WHERE email = 'leonardo.holguin77@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'julianburguz@gmail.com', documento_identidad = '1053833480', departamento_id = 14, municipio_id = 658, updated_at = NOW() WHERE email = 'julianburguz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'RAULANDRES.MSA04@GMAIL.COM', documento_identidad = '1053851698', departamento_id = 26, municipio_id = 1040, updated_at = NOW() WHERE email = 'RAULANDRES.MSA04@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'pmjimenez33@gmail.com', documento_identidad = '1054555392', departamento_id = 1, municipio_id = 65, updated_at = NOW() WHERE email = 'pmjimenez33@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ivetterodpa@gmail.com', documento_identidad = '1057465798', departamento_id = 4, municipio_id = 266, updated_at = NOW() WHERE email = 'ivetterodpa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angelatrujillo.87@gmail.com', documento_identidad = '1057573672', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'angelatrujillo.87@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dumarandres1@gmail.com', documento_identidad = '1057603330', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'dumarandres1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arcedelgadoandresfelipe@gmail.com', documento_identidad = '1059063103', departamento_id = 6, municipio_id = 360, updated_at = NOW() WHERE email = 'arcedelgadoandresfelipe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josericardoortega3@gmail.com', documento_identidad = '1059363288', departamento_id = 6, municipio_id = 343, updated_at = NOW() WHERE email = 'josericardoortega3@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gozya2024@gmail.com', documento_identidad = '1059594959', departamento_id = 6, municipio_id = 361, updated_at = NOW() WHERE email = 'gozya2024@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'isaactopin2@gmail.com', documento_identidad = '1059597142', departamento_id = 6, municipio_id = 361, updated_at = NOW() WHERE email = 'isaactopin2@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arboledag9@gmail.com', documento_identidad = '1059601509', departamento_id = 6, municipio_id = 373, updated_at = NOW() WHERE email = 'arboledag9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leocm368@gmail.com', documento_identidad = '1060801458', departamento_id = 6, municipio_id = 346, updated_at = NOW() WHERE email = 'leocm368@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elkinagredo8914@gmail.com', documento_identidad = '1060871382', departamento_id = 6, municipio_id = 350, updated_at = NOW() WHERE email = 'elkinagredo8914@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wilderlopeznavarro@gmail.com', documento_identidad = '1060876807', departamento_id = 6, municipio_id = 350, updated_at = NOW() WHERE email = 'wilderlopeznavarro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mauricioguevara717@gmail.com', documento_identidad = '1060879528', departamento_id = 6, municipio_id = 350, updated_at = NOW() WHERE email = 'mauricioguevara717@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hecmau5503@gmail.com', documento_identidad = '1061698087', departamento_id = 6, municipio_id = 370, updated_at = NOW() WHERE email = 'hecmau5503@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maferj@gmail.com', documento_identidad = '1061710317', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'maferj@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nododetecnologia@gmail.com', documento_identidad = '1061725399', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'nododetecnologia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mosriascos@gmail.com', documento_identidad = '1061737204', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'mosriascos@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'funbico@gmail.com', documento_identidad = '1061738786', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'funbico@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejojeferjulia@gmail.com', documento_identidad = '1061740146', departamento_id = 31, municipio_id = 1091, updated_at = NOW() WHERE email = 'alejojeferjulia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tatianajh29@gmail.com', documento_identidad = '1061769857', departamento_id = 12, municipio_id = 600, updated_at = NOW() WHERE email = 'tatianajh29@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bmueses.17@gmail.com', documento_identidad = '1061773963', departamento_id = 6, municipio_id = 357, updated_at = NOW() WHERE email = 'bmueses.17@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'guztrock@gmail.com', documento_identidad = '1061776500', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'guztrock@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'duvansanchez0613@gmail.com', documento_identidad = '1061790213', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'duvansanchez0613@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sofiagomezcabrera25@gmail.com', documento_identidad = '1061800561', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'sofiagomezcabrera25@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'generacionhumanaalmaguer@gmail.com', documento_identidad = '1061986151', departamento_id = 6, municipio_id = 341, updated_at = NOW() WHERE email = 'generacionhumanaalmaguer@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'xamirgaviria28@gmail.com', documento_identidad = '1061989690', departamento_id = 6, municipio_id = 341, updated_at = NOW() WHERE email = 'xamirgaviria28@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nelsonlopez26@hotmail.es', documento_identidad = '1062323926', departamento_id = 6, municipio_id = 381, updated_at = NOW() WHERE email = 'nelsonlopez26@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'SALOME.GONZALEZ@CORREOUNIVALLE.EDU.CO', documento_identidad = '1062328666', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'SALOME.GONZALEZ@CORREOUNIVALLE.EDU.CO' AND tenant_id = 1;
UPDATE users SET name = 'rafaelconcejoph@gmail.com', documento_identidad = '1062330663', departamento_id = 6, municipio_id = 381, updated_at = NOW() WHERE email = 'rafaelconcejoph@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andresgrupohappy@gmail.com', documento_identidad = '1063301182', departamento_id = 8, municipio_id = 420, updated_at = NOW() WHERE email = 'andresgrupohappy@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rosullune@gmail.com', documento_identidad = '1064429360', departamento_id = 6, municipio_id = 370, updated_at = NOW() WHERE email = 'rosullune@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'klingerjavier@gmail.com', documento_identidad = '1064488046', departamento_id = 6, municipio_id = 375, updated_at = NOW() WHERE email = 'klingerjavier@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josepad89@gmail.com', documento_identidad = '1064791478', departamento_id = 7, municipio_id = 390, updated_at = NOW() WHERE email = 'josepad89@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yosergiojoaquin@outlook.es', documento_identidad = '1064839506', departamento_id = 16, municipio_id = 732, updated_at = NOW() WHERE email = 'yosergiojoaquin@outlook.es' AND tenant_id = 1;
UPDATE users SET name = 'francisco.nl20@outlook.com', documento_identidad = '1064842474', departamento_id = 7, municipio_id = 401, updated_at = NOW() WHERE email = 'francisco.nl20@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'danilomarquez12pallares@gmail.com', documento_identidad = '1065575611', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'danilomarquez12pallares@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andreacarolinapolietica@gmail.com', documento_identidad = '1065647888', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'andreacarolinapolietica@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'danilson2290@hotmail.com', documento_identidad = '1066086354', departamento_id = 7, municipio_id = 398, updated_at = NOW() WHERE email = 'danilson2290@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marlychate3@gmail.com', documento_identidad = '1067530907', departamento_id = 6, municipio_id = 376, updated_at = NOW() WHERE email = 'marlychate3@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arlizonestiberpardo@gmail.com', documento_identidad = '1069761143', departamento_id = 9, municipio_id = 456, updated_at = NOW() WHERE email = 'arlizonestiberpardo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eduardomora2024@outlook.com', documento_identidad = '1070605356', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'eduardomora2024@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'julisegu18@gmail.com', documento_identidad = '1070916060', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'julisegu18@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anfecupaz96@gmail.com', documento_identidad = '1070977054', departamento_id = 9, municipio_id = 451, updated_at = NOW() WHERE email = 'anfecupaz96@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sergiopovedaleon21@outlook.com', documento_identidad = '1070979522', departamento_id = 9, municipio_id = 451, updated_at = NOW() WHERE email = 'sergiopovedaleon21@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'santiagoromero1v@gmail.com', documento_identidad = '1073158115', departamento_id = 9, municipio_id = 484, updated_at = NOW() WHERE email = 'santiagoromero1v@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'diegovet24@gmail.com', documento_identidad = '1073255186', departamento_id = 9, municipio_id = 487, updated_at = NOW() WHERE email = 'diegovet24@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'natalita0221@hotmail.com', documento_identidad = '1073507976', departamento_id = 9, municipio_id = 454, updated_at = NOW() WHERE email = 'natalita0221@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'diputadofrankfierro@gmail.com', documento_identidad = '1075221011', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'diputadofrankfierro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yentilg@gmail.com', documento_identidad = '1075245708', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'yentilg@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'amvar555@gmail.com', documento_identidad = '1075254437', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'amvar555@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dngonzalocenaprov@gmail.com', documento_identidad = '1075260180', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'dngonzalocenaprov@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'openmindpeoplecolombia@gmail.com', documento_identidad = '1075263816', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'openmindpeoplecolombia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejo.pabon.m@hotmail.com', documento_identidad = '1075276540', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'alejo.pabon.m@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'casb130@hotmail.com', documento_identidad = '1075276657', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'casb130@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edsoncasagua@hotmail.com', documento_identidad = '1075276901', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'edsoncasagua@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sebastianrodriguezramirez76@gmail.com', documento_identidad = '1075297170', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'sebastianrodriguezramirez76@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mariguve30@gmail.com', documento_identidad = '1075598634', departamento_id = 12, municipio_id = 602, updated_at = NOW() WHERE email = 'mariguve30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dailasofia@hotmail.com', documento_identidad = '1075657408', departamento_id = 9, municipio_id = 548, updated_at = NOW() WHERE email = 'dailasofia@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dairo4343@gmail.com', documento_identidad = '1076984489', departamento_id = 12, municipio_id = 584, updated_at = NOW() WHERE email = 'dairo4343@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yalogi10@gmail.com', documento_identidad = '1077423271', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'yalogi10@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'harryjaviersernacorrea1991@gmail.com', documento_identidad = '1077453490', departamento_id = 11, municipio_id = 554, updated_at = NOW() WHERE email = 'harryjaviersernacorrea1991@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fuapapa@hotmail.com', documento_identidad = '1077453929', departamento_id = 11, municipio_id = 550, updated_at = NOW() WHERE email = 'fuapapa@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nanajovi66@gmail.com', documento_identidad = '1077845637', departamento_id = 12, municipio_id = 1306, updated_at = NOW() WHERE email = 'nanajovi66@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'worozcoc@hotmail.com', documento_identidad = '1079884683', departamento_id = 13, municipio_id = 629, updated_at = NOW() WHERE email = 'worozcoc@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'osfaboto1988@gmail.com', documento_identidad = '1080261048', departamento_id = 6, municipio_id = 373, updated_at = NOW() WHERE email = 'osfaboto1988@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejaos899@hotmail.com', documento_identidad = '1081395445', departamento_id = 12, municipio_id = 593, updated_at = NOW() WHERE email = 'alejaos899@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'diegofer900211@gmail.com', documento_identidad = '1081404370', departamento_id = 12, municipio_id = 593, updated_at = NOW() WHERE email = 'diegofer900211@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fontalvojesus993@gmail.com', documento_identidad = '1081762938', departamento_id = 13, municipio_id = 624, updated_at = NOW() WHERE email = 'fontalvojesus993@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'duberneydia@unicauca.edu.co', documento_identidad = '1082776492', departamento_id = 12, municipio_id = 605, updated_at = NOW() WHERE email = 'duberneydia@unicauca.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'alvaroe.jimenez@hotmail.com', documento_identidad = '1082980774', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'alvaroe.jimenez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'neato-16@hotmail.com', documento_identidad = '1083908114', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'neato-16@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'brayanstiven2021@gmail.com', documento_identidad = '1083918667', departamento_id = 12, municipio_id = 600, updated_at = NOW() WHERE email = 'brayanstiven2021@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wodr85@live.com', documento_identidad = '1085245562', departamento_id = 14, municipio_id = 704, updated_at = NOW() WHERE email = 'wodr85@live.com' AND tenant_id = 1;
UPDATE users SET name = 'andruusss6000@hotmail.com', documento_identidad = '1085287910', departamento_id = 14, municipio_id = 678, updated_at = NOW() WHERE email = 'andruusss6000@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dilan_m@live.com', documento_identidad = '1085299794', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'dilan_m@live.com' AND tenant_id = 1;
UPDATE users SET name = 'maralar@protonmail.com', documento_identidad = '1085319459', departamento_id = 35, municipio_id = 1176, updated_at = NOW() WHERE email = 'maralar@protonmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jito.13@hotmail.com', documento_identidad = '1085326738', departamento_id = 14, municipio_id = 642, updated_at = NOW() WHERE email = 'jito.13@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mafetimana28@gmail.com', documento_identidad = '1085332002', departamento_id = 35, municipio_id = 1233, updated_at = NOW() WHERE email = 'mafetimana28@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kddulce@unicauca.edu.co', documento_identidad = '1085343403', departamento_id = 14, municipio_id = 642, updated_at = NOW() WHERE email = 'kddulce@unicauca.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'danielitha1996dj@gmail.com', documento_identidad = '1085690108', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'danielitha1996dj@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jocampa12@gmail.com', documento_identidad = '1085902642', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'jocampa12@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'm-fercha29@hotmail.com', documento_identidad = '1087412175', departamento_id = 14, municipio_id = 707, updated_at = NOW() WHERE email = 'm-fercha29@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'victorgil248@gmail.com', documento_identidad = '1087490554', departamento_id = 15, municipio_id = 715, updated_at = NOW() WHERE email = 'victorgil248@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marcelomarinsierra@gmail.com', documento_identidad = '1087491207', departamento_id = 15, municipio_id = 715, updated_at = NOW() WHERE email = 'marcelomarinsierra@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dianamarcela.247@gmail.com', documento_identidad = '1088265065', departamento_id = 35, municipio_id = 1280, updated_at = NOW() WHERE email = 'dianamarcela.247@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'flor.maria1986@hotmail.com', documento_identidad = '1089000059', departamento_id = 14, municipio_id = 684, updated_at = NOW() WHERE email = 'flor.maria1986@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ygranadoc@unal.edu.co', documento_identidad = '1089798792', departamento_id = 14, municipio_id = 662, updated_at = NOW() WHERE email = 'ygranadoc@unal.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'abogadojoanunab@gmail.com', documento_identidad = '1090178857', departamento_id = 16, municipio_id = 736, updated_at = NOW() WHERE email = 'abogadojoanunab@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cleiro@hotmail.com', documento_identidad = '1090388393', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'cleiro@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'publiplastgraphiccucuta@gmail.com', documento_identidad = '1090411529', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'publiplastgraphiccucuta@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lauracristinaam1989@gmail.com', documento_identidad = '1090419583', departamento_id = 16, municipio_id = 729, updated_at = NOW() WHERE email = 'lauracristinaam1989@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jeffer19_05@hotmail.com', documento_identidad = '1090449912', departamento_id = 16, municipio_id = 729, updated_at = NOW() WHERE email = 'jeffer19_05@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ing.juandavidgm@gmail.com', documento_identidad = '1090484127', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'ing.juandavidgm@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'isaacjgar@gmail.com', documento_identidad = '1090485667', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'isaacjgar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'heinermartinez236@gmail.com', documento_identidad = '1090502506', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'heinermartinez236@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hkaterinquintero@gmail.com', documento_identidad = '1090516877', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'hkaterinquintero@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'morenoduran2020@hotmail.com', documento_identidad = '1091382448', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'morenoduran2020@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'evelio166@gmail.com', documento_identidad = '1091534826', departamento_id = 16, municipio_id = 740, updated_at = NOW() WHERE email = 'evelio166@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nllozano23@gmail.com', documento_identidad = '1091652598', departamento_id = 7, municipio_id = 401, updated_at = NOW() WHERE email = 'nllozano23@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jekcsan89@protonmail.com', documento_identidad = '1091661014', departamento_id = 16, municipio_id = 724, updated_at = NOW() WHERE email = 'jekcsan89@protonmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mireyapino9@hotmail.com', documento_identidad = '1091680113', departamento_id = 16, municipio_id = 732, updated_at = NOW() WHERE email = 'mireyapino9@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'harol.gonzalez@aol.com', documento_identidad = '1093221832', departamento_id = 35, municipio_id = 1176, updated_at = NOW() WHERE email = 'harol.gonzalez@aol.com' AND tenant_id = 1;
UPDATE users SET name = 'felox7656@hotmail.com', documento_identidad = '1093909509', departamento_id = 18, municipio_id = 844, updated_at = NOW() WHERE email = 'felox7656@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'felsomina2012@hotmail.com', documento_identidad = '1094240313', departamento_id = 22, municipio_id = 981, updated_at = NOW() WHERE email = 'felsomina2012@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fresalomejor@gmail.com', documento_identidad = '1094277184', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'fresalomejor@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'qoyeraldine@gmail.com', documento_identidad = '1094581261', departamento_id = 16, municipio_id = 724, updated_at = NOW() WHERE email = 'qoyeraldine@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luztizzas@gmail.com', documento_identidad = '1096194989', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'luztizzas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'geralmendo8830@gmail.com', documento_identidad = '1096195748', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'geralmendo8830@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anyel.duran@unipaz.edu.co', documento_identidad = '1096211983', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'anyel.duran@unipaz.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'caritolopeznoriega@gmail.com', documento_identidad = '1096219940', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'caritolopeznoriega@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angieliney20@hotmail.com', documento_identidad = '1096235106', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'angieliney20@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yoanzasa2014@gmail.com', documento_identidad = '1097392717', departamento_id = 17, municipio_id = 764, updated_at = NOW() WHERE email = 'yoanzasa2014@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'doryvelrojas@gmail.com', documento_identidad = '1098151318', departamento_id = 18, municipio_id = 791, updated_at = NOW() WHERE email = 'doryvelrojas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alexisecheverri@hotmail.com', documento_identidad = '1098313322', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'alexisecheverri@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yemerson.hdz@gmail.com', documento_identidad = '1098623258', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'yemerson.hdz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sherzy.19@hotmail.com', documento_identidad = '1098652136', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'sherzy.19@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juancarlosgonzalez_ortiz@hotmail.com', documento_identidad = '1098662406', departamento_id = 18, municipio_id = 825, updated_at = NOW() WHERE email = 'juancarlosgonzalez_ortiz@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ivancrojasm@hotmail.com', documento_identidad = '1098666083', departamento_id = 18, municipio_id = 844, updated_at = NOW() WHERE email = 'ivancrojasm@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jffq_09@hotmail.com', documento_identidad = '1098680569', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'jffq_09@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aparra1808@gmail.com', documento_identidad = '1098747136', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'aparra1808@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wilmeralfonsocaballerovillegas@gmail.com', documento_identidad = '1099342984', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'wilmeralfonsocaballerovillegas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = '5784contreras@gmail.com', documento_identidad = '1099990947', departamento_id = 19, municipio_id = 868, updated_at = NOW() WHERE email = '5784contreras@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ecastroc@superservicios.gov.co', documento_identidad = '1100395695', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'ecastroc@superservicios.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'juridicosergiolopez@gmail.com', documento_identidad = '1100957745', departamento_id = 18, municipio_id = 844, updated_at = NOW() WHERE email = 'juridicosergiolopez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gutia1034@gmail.com', documento_identidad = '1101260269', departamento_id = 18, municipio_id = 795, updated_at = NOW() WHERE email = 'gutia1034@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yos_mire@hotmail.com', documento_identidad = '1102812289', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'yos_mire@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juandavidcortesdiaz@gmail.com', documento_identidad = '1102853111', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'juandavidcortesdiaz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angelagonossa@gmail.com', documento_identidad = '1102873068', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'angelagonossa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'narvaezlarajesus@gmail.com', documento_identidad = '1103741009', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'narvaezlarajesus@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jcuestamontes@gmail.com', documento_identidad = '1104131078', departamento_id = 18, municipio_id = 839, updated_at = NOW() WHERE email = 'jcuestamontes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'deinaluzdiaz@gmail.com', documento_identidad = '1104412081', departamento_id = 19, municipio_id = 881, updated_at = NOW() WHERE email = 'deinaluzdiaz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ospinalozanoc@gmail.com', documento_identidad = '1106398432', departamento_id = 20, municipio_id = 922, updated_at = NOW() WHERE email = 'ospinalozanoc@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'prietofabian201@gmail.com', documento_identidad = '1110174107', departamento_id = 20, municipio_id = 917, updated_at = NOW() WHERE email = 'prietofabian201@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andreamanjarres.26@hotmail.com', documento_identidad = '1110553573', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'andreamanjarres.26@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sebastianamezquita05@gmail.com', documento_identidad = '1110553597', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'sebastianamezquita05@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cespedesd11@gmail.com', documento_identidad = '1110557004', departamento_id = 20, municipio_id = 922, updated_at = NOW() WHERE email = 'cespedesd11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sterogu98@gmail.com', documento_identidad = '1110592128', departamento_id = 20, municipio_id = 888, updated_at = NOW() WHERE email = 'sterogu98@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rosero0618@gmail.com', documento_identidad = '1111813716', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'rosero0618@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jimenezgaviriah@gmail.com', documento_identidad = '1112151596', departamento_id = 21, municipio_id = 974, updated_at = NOW() WHERE email = 'jimenezgaviriah@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'darwinrojasfranco2@gmail.com', documento_identidad = '1112224020', departamento_id = 21, municipio_id = 962, updated_at = NOW() WHERE email = 'darwinrojasfranco2@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cristiandavidgalvis94@gmail.com', documento_identidad = '1112463803', departamento_id = 21, municipio_id = 956, updated_at = NOW() WHERE email = 'cristiandavidgalvis94@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juandavidguapachabolivar@gmail.com', documento_identidad = '1112766556', departamento_id = 21, municipio_id = 946, updated_at = NOW() WHERE email = 'juandavidguapachabolivar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dianafernanda42@gmail.com', documento_identidad = '1113520300', departamento_id = 35, municipio_id = 1136, updated_at = NOW() WHERE email = 'dianafernanda42@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jesuselias83@gmail.com', documento_identidad = '1113618716', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'jesuselias83@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'isabelverazap@gmail.com', documento_identidad = '1113694459', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'isabelverazap@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maesda1715@gmail.com', documento_identidad = '1113788745', departamento_id = 21, municipio_id = 965, updated_at = NOW() WHERE email = 'maesda1715@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sergioospina090@gmail.com', documento_identidad = '1114058158', departamento_id = 21, municipio_id = 966, updated_at = NOW() WHERE email = 'sergioospina090@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'daviddaza13@gmail.com', documento_identidad = '1116801342', departamento_id = 22, municipio_id = 978, updated_at = NOW() WHERE email = 'daviddaza13@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'a.santamaria@udla.edu.co', documento_identidad = '1117524439', departamento_id = 23, municipio_id = 984, updated_at = NOW() WHERE email = 'a.santamaria@udla.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'nicolasrodriguezmelendez2007@gmail.com', documento_identidad = '1117814962', departamento_id = 23, municipio_id = 992, updated_at = NOW() WHERE email = 'nicolasrodriguezmelendez2007@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nancy.5.1@hotmail.com', documento_identidad = '1118540242', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'nancy.5.1@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'galvismagdalena28@gmail.com', documento_identidad = '1118824999', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'galvismagdalena28@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'shniken14@gmail.com', documento_identidad = '1119180241', departamento_id = 22, municipio_id = 981, updated_at = NOW() WHERE email = 'shniken14@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'migdonia.2021@gmail.com', documento_identidad = '1119891394', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'migdonia.2021@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Renasdiaz@gmail.com', documento_identidad = '1120575305', departamento_id = 28, municipio_id = 1072, updated_at = NOW() WHERE email = 'Renasdiaz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leidyines0901@hotmail.com', documento_identidad = '1120584056', departamento_id = 28, municipio_id = 1072, updated_at = NOW() WHERE email = 'leidyines0901@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'duqueoscar823@gmail.com', documento_identidad = '1121829807', departamento_id = 33, municipio_id = 1109, updated_at = NOW() WHERE email = 'duqueoscar823@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rudenagm07@gmail.com', documento_identidad = '1121870042', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'rudenagm07@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanksf@hotmail.com', documento_identidad = '1121888608', departamento_id = 9, municipio_id = 450, updated_at = NOW() WHERE email = 'juanksf@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'estefa19tea@hotmail.com', documento_identidad = '1121901586', departamento_id = 21, municipio_id = 970, updated_at = NOW() WHERE email = 'estefa19tea@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejandra_baquero@hotmail.com', documento_identidad = '1121926629', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'alejandra_baquero@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dianahuertash@gmail.com', documento_identidad = '1121930884', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'dianahuertash@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'albertocortessantos@gmail.com', documento_identidad = '1121932595', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'albertocortessantos@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dayanacubillos96@gmail.com', documento_identidad = '1121934917', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'dayanacubillos96@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yisethdayana30@gmail.com', documento_identidad = '1121954398', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'yisethdayana30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'forero1071@gmail.com', documento_identidad = '1122237216', departamento_id = 18, municipio_id = 777, updated_at = NOW() WHERE email = 'forero1071@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yamidortega86@gmail.com', documento_identidad = '1122336664', departamento_id = 31, municipio_id = 1095, updated_at = NOW() WHERE email = 'yamidortega86@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'auraepiayu2@gmail.com', documento_identidad = '1122808604', departamento_id = 25, municipio_id = 1021, updated_at = NOW() WHERE email = 'auraepiayu2@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maxtorresnegocios@gmail.com', documento_identidad = '1122810063', departamento_id = 25, municipio_id = 1021, updated_at = NOW() WHERE email = 'maxtorresnegocios@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'estivenromero1988@gmail.com', documento_identidad = '1122811612', departamento_id = 25, municipio_id = 1021, updated_at = NOW() WHERE email = 'estivenromero1988@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marcohernandez2599@gmail.com', documento_identidad = '1123515134', departamento_id = 26, municipio_id = 1043, updated_at = NOW() WHERE email = 'marcohernandez2599@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ybrios@uniguajira.edu.co', documento_identidad = '1123994814', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'ybrios@uniguajira.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'johanna3832@hotmail.com', documento_identidad = '1123998350', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'johanna3832@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jairjose-14@hotmail.com', documento_identidad = '1124001964', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'jairjose-14@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eihasy@gmail.com', documento_identidad = '1124007761', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'eihasy@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ninosladailinlopez@gmail.com', documento_identidad = '1124566744', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'ninosladailinlopez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yuskmontiel@gmail.com', documento_identidad = '1124567845', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'yuskmontiel@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'benitez.puertogaitan@gmail.com', documento_identidad = '1124820992', departamento_id = 26, municipio_id = 1058, updated_at = NOW() WHERE email = 'benitez.puertogaitan@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mjusa22@hotmail.es', documento_identidad = '1124857700', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'mjusa22@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'ardilaoswaldo10@gmail.com', documento_identidad = '1124991211', departamento_id = 33, municipio_id = 1110, updated_at = NOW() WHERE email = 'ardilaoswaldo10@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'supervisorhse8809.transdepet@gmail.com', documento_identidad = '1125409103', departamento_id = 31, municipio_id = 1099, updated_at = NOW() WHERE email = 'supervisorhse8809.transdepet@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angievargas2014@hotmail.com', documento_identidad = '1125469318', departamento_id = 33, municipio_id = 1110, updated_at = NOW() WHERE email = 'angievargas2014@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'culturizandojusticia@gmail.com', documento_identidad = '1127044126', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'culturizandojusticia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jennifer_9004@yahoo.com', documento_identidad = '1128432939', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'jennifer_9004@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'katrina98giron@gmail.com', documento_identidad = '1130650797', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'katrina98giron@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hernandezperezcristian34@gmail.com', documento_identidad = '1133795261', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'hernandezperezcristian34@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'guillermocastroo@hotmail.com', documento_identidad = '1140415879', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'guillermocastroo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jeanhernandezbaq@gmail.com', documento_identidad = '1140890202', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'jeanhernandezbaq@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cccantilloyepes@gmail.com', documento_identidad = '1143346985', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'cccantilloyepes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sandycabrera1209@gmail.com', documento_identidad = '1143428607', departamento_id = 3, municipio_id = 165, updated_at = NOW() WHERE email = 'sandycabrera1209@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tefabenavides@gmail.com', documento_identidad = '1144085932', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'tefabenavides@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'samira.ortega@correounivalle.edu.co', documento_identidad = '1144103555', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'samira.ortega@correounivalle.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'lenisleyer@gmail.com', documento_identidad = '1144141707', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'lenisleyer@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kevin.mancilla@correounivalle.edu.co', documento_identidad = '1144171799', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'kevin.mancilla@correounivalle.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'paolapulgarin06@gmail.com', documento_identidad = '1152692447', departamento_id = 35, municipio_id = 1120, updated_at = NOW() WHERE email = 'paolapulgarin06@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisazuleta16@gmail.com', documento_identidad = '1152713471', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'luisazuleta16@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'moyaha0820@gmail.com', documento_identidad = '1193064627', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'moyaha0820@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Camilabarajasp12345@gmail.com', documento_identidad = '1193067407', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'Camilabarajasp12345@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carnunez20@gmail.com', documento_identidad = '1193465806', departamento_id = 4, municipio_id = 211, updated_at = NOW() WHERE email = 'carnunez20@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mamoribe27@gmail.com', documento_identidad = '1193541300', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'mamoribe27@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hectores2001@hotmail.com', documento_identidad = '1193551893', departamento_id = 1, municipio_id = 52, updated_at = NOW() WHERE email = 'hectores2001@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mauricionogales75@gmail.com', documento_identidad = '1193598781', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'mauricionogales75@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'munozgiraldojuandavid9@gmail.com', documento_identidad = '1214722968', departamento_id = 1, municipio_id = 29, updated_at = NOW() WHERE email = 'munozgiraldojuandavid9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angiesofiacharit111414@gmail.com', documento_identidad = '1233493804', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'angiesofiacharit111414@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andreavargasbarranquilla@gmail.com', documento_identidad = '1234094675', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'andreavargasbarranquilla@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ferney.silva@senado.gov.co', updated_at = NOW() WHERE email = 'ferney.silva@senado.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'alex.florez@senado.gov.co', updated_at = NOW() WHERE email = 'alex.florez@senado.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'esmeralda.hernandez@senado.gov.co', updated_at = NOW() WHERE email = 'esmeralda.hernandez@senado.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'gloria.florez@senado.gov.co', updated_at = NOW() WHERE email = 'gloria.florez@senado.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'catalina.perez@senado.gov.co', updated_at = NOW() WHERE email = 'catalina.perez@senado.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'jorge.bastidas@camara.gov.co', updated_at = NOW() WHERE email = 'jorge.bastidas@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'andres.cancimance@camara.gov.co', updated_at = NOW() WHERE email = 'andres.cancimance@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'maria.carrascal@camara.gov.co', updated_at = NOW() WHERE email = 'maria.carrascal@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'agmeth.escaf@camara.gov.co', updated_at = NOW() WHERE email = 'agmeth.escaf@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'susana.gomez@camara.gov.co', updated_at = NOW() WHERE email = 'susana.gomez@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'mary.perdomo@camara.gov.co', updated_at = NOW() WHERE email = 'mary.perdomo@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'mariam.pizarro@camara.gov.co', updated_at = NOW() WHERE email = 'mariam.pizarro@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'carmen.ramirez@camara.gov.co', updated_at = NOW() WHERE email = 'carmen.ramirez@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'pedro.suarez@camara.gov.co', updated_at = NOW() WHERE email = 'pedro.suarez@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'leider.vasquez@camara.gov.co', updated_at = NOW() WHERE email = 'leider.vasquez@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'alejandro.toro@camara.gov.co', updated_at = NOW() WHERE email = 'alejandro.toro@camara.gov.co' AND tenant_id = 1;
UPDATE users SET name = 'nataliagordillo2014@gmail.com', updated_at = NOW() WHERE email = 'nataliagordillo2014@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hugoguamanga@1965gimail.com', documento_identidad = '4766053', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'hugoguamanga@1965gimail.com' AND tenant_id = 1;
UPDATE users SET name = 'quindoalexander11@gmail.com', documento_identidad = '4788795', departamento_id = 6, municipio_id = 377, updated_at = NOW() WHERE email = 'quindoalexander11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'murillogustavo1964@gmail.com', documento_identidad = '6138110', departamento_id = 21, municipio_id = 940, updated_at = NOW() WHERE email = 'murillogustavo1964@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gabovasquez.psi@gmail.com', documento_identidad = '6560951', departamento_id = 21, municipio_id = 976, updated_at = NOW() WHERE email = 'gabovasquez.psi@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bolivar5828contador@yahoo.es', documento_identidad = '8679131', departamento_id = 13, municipio_id = 623, updated_at = NOW() WHERE email = 'bolivar5828contador@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'tribais2018@gmail.com', documento_identidad = '8751637', departamento_id = 35, municipio_id = 1300, updated_at = NOW() WHERE email = 'tribais2018@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jlcaballero76@gmail.com', documento_identidad = '9292195', departamento_id = 3, municipio_id = 156, updated_at = NOW() WHERE email = 'jlcaballero76@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ramirodesarrolloz@hotmail.com', documento_identidad = '10554738', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'ramirodesarrolloz@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edullanes@hotmail.com', documento_identidad = '12530320', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'edullanes@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'emiroropsuarez87@gmail.com', documento_identidad = '13461523', departamento_id = 16, municipio_id = 762, updated_at = NOW() WHERE email = 'emiroropsuarez87@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'epimeniomedinaquiroga@gmail.com', documento_identidad = '13956589', departamento_id = 18, municipio_id = 779, updated_at = NOW() WHERE email = 'epimeniomedinaquiroga@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lavozinternacional@gmail.com', documento_identidad = '14965342', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'lavozinternacional@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ericsanantero@hotmail.com', documento_identidad = '15615216', departamento_id = 8, municipio_id = 429, updated_at = NOW() WHERE email = 'ericsanantero@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'frajago01@gmail.com', documento_identidad = '16271951', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'frajago01@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arnulfomunoz2009@gmail.com', documento_identidad = '16696568', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'arnulfomunoz2009@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'haroldwilsonlopezlopez@gmail.com', documento_identidad = '18126727', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'haroldwilsonlopezlopez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlosrodrigo.montes@gmail.com', documento_identidad = '18462911', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'carlosrodrigo.montes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'INTERNETCARLOSYJAVIER@GMAIL.COM', documento_identidad = '19146505', departamento_id = 21, municipio_id = 956, updated_at = NOW() WHERE email = 'INTERNETCARLOSYJAVIER@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'arridiogenes@gmail.com', documento_identidad = '19158875', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'arridiogenes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marin.itaka@gmail.com', documento_identidad = '19234788', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'marin.itaka@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fhectorhernando@yahoo.com', documento_identidad = '19295337', departamento_id = 35, municipio_id = 1233, updated_at = NOW() WHERE email = 'fhectorhernando@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'arq.saul.cs@gmail.com', documento_identidad = '19389898', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'arq.saul.cs@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ricardogestionhumana@yahoo.com', documento_identidad = '19452060', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'ricardogestionhumana@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'eliecerpolanco60@gmail.com', documento_identidad = '19789561', departamento_id = 3, municipio_id = 156, updated_at = NOW() WHERE email = 'eliecerpolanco60@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'blancainesperez7@gmail.com', documento_identidad = '22191780', departamento_id = 1, municipio_id = 116, updated_at = NOW() WHERE email = 'blancainesperez7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nellyhurtado0205@gmail.com', documento_identidad = '31378078', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'nellyhurtado0205@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'glucypiedad@gmail.com', documento_identidad = '34544579', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'glucypiedad@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'emurillo58@gmail.com', documento_identidad = '38941105', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'emurillo58@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zulebarros72@gmail.com', documento_identidad = '40924701', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'zulebarros72@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leidysubaque@hotmail.com', documento_identidad = '45542506', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'leidysubaque@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'claraeplazas@gmail.com', documento_identidad = '51563137', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'claraeplazas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'narimazulcerinza@hotmail.com', documento_identidad = '51755465', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'narimazulcerinza@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'biancacegob@gmail.com', documento_identidad = '52158156', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'biancacegob@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'taniarbr712@gmail.com', documento_identidad = '52582802', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'taniarbr712@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cristinaalvarado@msn.com', documento_identidad = '63301233', departamento_id = 35, municipio_id = 1176, updated_at = NOW() WHERE email = 'cristinaalvarado@msn.com' AND tenant_id = 1;
UPDATE users SET name = 'sandrapbarajas@hotmail.com', documento_identidad = '63483458', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'sandrapbarajas@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'belmiraisabel@hotmail.com', documento_identidad = '64549071', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'belmiraisabel@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josemarnol@hotmail.com', documento_identidad = '76334834', departamento_id = 6, municipio_id = 344, updated_at = NOW() WHERE email = 'josemarnol@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wrchacon@misena.edu.co', documento_identidad = '77101703', departamento_id = 7, municipio_id = 390, updated_at = NOW() WHERE email = 'wrchacon@misena.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'Perrocriollo113@hotmail.com', documento_identidad = '79243157', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'Perrocriollo113@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cesarmoto@gmail.com', documento_identidad = '79267036', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'cesarmoto@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgeezuletaz12@gmail.com', documento_identidad = '79357295', departamento_id = 7, municipio_id = 404, updated_at = NOW() WHERE email = 'jorgeezuletaz12@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tapieromol@hotmail.com', documento_identidad = '79467821', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'tapieromol@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luichioso1@gmail.com', documento_identidad = '80143313', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'luichioso1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'johorta24@gmail.com', documento_identidad = '80251290', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'johorta24@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hermeenriquemartines@gmail.com', documento_identidad = '84037445', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'hermeenriquemartines@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dimas_172@hotmail.com', documento_identidad = '88279011', departamento_id = 16, municipio_id = 741, updated_at = NOW() WHERE email = 'dimas_172@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'areatiga1602@gmail.com', documento_identidad = '91154391', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'areatiga1602@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'german.tarazona68@gmail.com', documento_identidad = '91258843', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'german.tarazona68@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'a.sergioalarcon@gmail.com', documento_identidad = '94331716', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'a.sergioalarcon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dsgonzalez@upn.edu.co', documento_identidad = '1001638247', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'dsgonzalez@upn.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'deylergueto16@gmail.com', documento_identidad = '1002193375', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'deylergueto16@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carolinaparra1026@gmail.com', documento_identidad = '1005322033', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'carolinaparra1026@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'biciverso1@gmail.com', documento_identidad = '1013615605', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'biciverso1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'MPENALOZAB@UNAL.EDU.CO', documento_identidad = '1018482767', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'MPENALOZAB@UNAL.EDU.CO' AND tenant_id = 1;
UPDATE users SET name = 'juaramirezpi@unal.edu.co', documento_identidad = '1032471632', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'juaramirezpi@unal.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'andresdelcauca@gmail.com', documento_identidad = '1061723646', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'andresdelcauca@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ocontrerasm@ufpso.edu.co', documento_identidad = '1066063010', departamento_id = 7, municipio_id = 394, updated_at = NOW() WHERE email = 'ocontrerasm@ufpso.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'ankaduarte2024@gmail.com', documento_identidad = '1082957771', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'ankaduarte2024@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mvsorozcov@gmail.com', documento_identidad = '1083007932', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'mvsorozcov@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alvare@utp.edu.co', documento_identidad = '1088323187', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'alvare@utp.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'juliethpadilla5681@gmail.com', documento_identidad = '1096197141', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'juliethpadilla5681@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eliecersierra00@gmail.com', documento_identidad = '1101782120', departamento_id = 19, municipio_id = 863, updated_at = NOW() WHERE email = 'eliecersierra00@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'MALEJA.CABRERA13@GMAIL.COM', documento_identidad = '1121930499', departamento_id = 16, municipio_id = 762, updated_at = NOW() WHERE email = 'MALEJA.CABRERA13@GMAIL.COM' AND tenant_id = 1;
UPDATE users SET name = 'camicardenas442@gmail.com', documento_identidad = '1123991646', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'camicardenas442@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ftimms@uniguajira.edu.co', documento_identidad = '1124042932', departamento_id = 25, municipio_id = 1027, updated_at = NOW() WHERE email = 'ftimms@uniguajira.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'jaiandrest@gmail.com', documento_identidad = '1144027584', departamento_id = 21, municipio_id = 972, updated_at = NOW() WHERE email = 'jaiandrest@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vergara11795@gmail.com', documento_identidad = '1151477298', departamento_id = 19, municipio_id = 884, updated_at = NOW() WHERE email = 'vergara11795@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gabrielfeliperosero.9.3@gmail.com', documento_identidad = '1233189435', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'gabrielfeliperosero.9.3@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'deiyerg4@gmail.com', documento_identidad = '1090174639', departamento_id = 16, municipio_id = 736, updated_at = NOW() WHERE email = 'deiyerg4@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fernandomurillo11@outlook.com', documento_identidad = '16940026', departamento_id = 21, municipio_id = 940, updated_at = NOW() WHERE email = 'fernandomurillo11@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'edilvelascoruge@yahoo.es', documento_identidad = '79318576', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'edilvelascoruge@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'gonzalezherney90@gmail.com', documento_identidad = '1000992908', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'gonzalezherney90@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'casaseliza72@hotmail.com', documento_identidad = '52121562', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'casaseliza72@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ametesa@yah00.com', documento_identidad = '41337358', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'ametesa@yah00.com' AND tenant_id = 1;
UPDATE users SET name = 'dimasca01@gmail.com', documento_identidad = '1007885262', departamento_id = 16, municipio_id = 740, updated_at = NOW() WHERE email = 'dimasca01@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'josejuliancardenasz@gmail.com', documento_identidad = '79108533', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'josejuliancardenasz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jpulido_garcia@hotmail.com', documento_identidad = '16508353', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'jpulido_garcia@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'diana.cardenas@correounivalle.edu.co', documento_identidad = '1143980284', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'diana.cardenas@correounivalle.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'kalejarosero99@gmail.com', documento_identidad = '1124867384', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'kalejarosero99@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'julianfajardo598@gmail.com', documento_identidad = '1114338604', departamento_id = 21, municipio_id = 963, updated_at = NOW() WHERE email = 'julianfajardo598@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'corazonvalientecorazon@gmail.com', documento_identidad = '2774682', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'corazonvalientecorazon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ruizxiomara606@gmail.com', documento_identidad = '25273466', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'ruizxiomara606@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'abdenagoaguilar16@gmail.com', documento_identidad = '73106965', departamento_id = 9, municipio_id = 532, updated_at = NOW() WHERE email = 'abdenagoaguilar16@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kantojas69@gmail.com', documento_identidad = '71396791', departamento_id = 1, municipio_id = 27, updated_at = NOW() WHERE email = 'kantojas69@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'santialugoreyes@gmail.com', documento_identidad = '1073177697', departamento_id = 9, municipio_id = 484, updated_at = NOW() WHERE email = 'santialugoreyes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlosgarzonconcejal1@gmail.com', documento_identidad = '1003704515', departamento_id = 9, municipio_id = 465, updated_at = NOW() WHERE email = 'carlosgarzonconcejal1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Juanjosevargaspaspur@gmail.com', documento_identidad = '1002955974', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'Juanjosevargaspaspur@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tapiagomezlaura@gmail.com', documento_identidad = '1073237441', departamento_id = 9, municipio_id = 487, updated_at = NOW() WHERE email = 'tapiagomezlaura@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ogpayares02@gmail.com', documento_identidad = '19146159', departamento_id = 8, municipio_id = 415, updated_at = NOW() WHERE email = 'ogpayares02@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'natalyjimenezts@gmail.com', documento_identidad = '52974993', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'natalyjimenezts@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'afroteatrodigitaldetumaco@gmail.com', documento_identidad = '29509788', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'afroteatrodigitaldetumaco@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gerardo610701@hotmail.com', documento_identidad = '10539543', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'gerardo610701@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'manuel.cuadrado@hotmail.com', documento_identidad = '11078810', departamento_id = 8, municipio_id = 414, updated_at = NOW() WHERE email = 'manuel.cuadrado@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'npnuse@gmail.com', documento_identidad = '51832604', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'npnuse@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'victoriabarcoyamil@gmail.com', documento_identidad = '31588580', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'victoriabarcoyamil@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'viajeconalejo@gmail.com', documento_identidad = '3250100', departamento_id = 9, municipio_id = 447, updated_at = NOW() WHERE email = 'viajeconalejo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carmenrochaperez60@gmail.com', documento_identidad = '22968545', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'carmenrochaperez60@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'casavargaspublicidad@gmail.com', documento_identidad = '72177780', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'casavargaspublicidad@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'colombia_sam@hotmail.com', documento_identidad = '29700895', departamento_id = 21, municipio_id = 962, updated_at = NOW() WHERE email = 'colombia_sam@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'antoniolentesur@gmail.com', documento_identidad = '3355199', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'antoniolentesur@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'veroholguib@gmail.com', documento_identidad = '3434403', departamento_id = 1, municipio_id = 31, updated_at = NOW() WHERE email = 'veroholguib@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'c.josefagua@gmail.com', documento_identidad = '6768077', departamento_id = 4, municipio_id = 220, updated_at = NOW() WHERE email = 'c.josefagua@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'diegoojeda04@yahoo.com', documento_identidad = '13071091', updated_at = NOW() WHERE email = 'diegoojeda04@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'alalimon@yahoo.es', documento_identidad = '32323071', departamento_id = 1, municipio_id = 92, updated_at = NOW() WHERE email = 'alalimon@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'lucyministerial@yahoo.es', documento_identidad = '36543504', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'lucyministerial@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'yoissypolitica@yahoo.es', documento_identidad = '39175534', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'yoissypolitica@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'dianalopez@gmail.com', documento_identidad = '42690349', departamento_id = 1, municipio_id = 40, updated_at = NOW() WHERE email = 'dianalopez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sandralezcano29@gmail.com', documento_identidad = '42938682', departamento_id = 1, municipio_id = 105, updated_at = NOW() WHERE email = 'sandralezcano29@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marlenysparra.6@gmail.com', documento_identidad = '43141868', departamento_id = 1, municipio_id = 32, updated_at = NOW() WHERE email = 'marlenysparra.6@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'paolamerlano@yahoo.es', documento_identidad = '43277700', departamento_id = 1, municipio_id = 85, updated_at = NOW() WHERE email = 'paolamerlano@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'stellamimaddre@gmail.com', documento_identidad = '43611443', departamento_id = 1, municipio_id = 85, updated_at = NOW() WHERE email = 'stellamimaddre@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dianarinro@gmail.com', documento_identidad = '43742107', departamento_id = 1, municipio_id = 33, updated_at = NOW() WHERE email = 'dianarinro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jujigay@hotmail.com', documento_identidad = '49655647', departamento_id = 7, municipio_id = 383, updated_at = NOW() WHERE email = 'jujigay@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nohorasuarez123@yahoo.com', documento_identidad = '51574032', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'nohorasuarez123@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'amandy.0227@gmail.com', documento_identidad = '59820467', updated_at = NOW() WHERE email = 'amandy.0227@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'melagret@outlook.es', documento_identidad = '1000194843', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'melagret@outlook.es' AND tenant_id = 1;
UPDATE users SET name = 'yornelidisja@gmail.com', documento_identidad = '1001548579', departamento_id = 1, municipio_id = 35, updated_at = NOW() WHERE email = 'yornelidisja@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sebastianpolifonia@gmail.com', documento_identidad = '1026148303', departamento_id = 1, municipio_id = 27, updated_at = NOW() WHERE email = 'sebastianpolifonia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'estrada_0520@hotmail.com', documento_identidad = '1041150747', departamento_id = 1, municipio_id = 54, updated_at = NOW() WHERE email = 'estrada_0520@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nasnermonica@gmail.com', documento_identidad = '1087417855', updated_at = NOW() WHERE email = 'nasnermonica@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'brigitte.ariasb@hotmail.com', documento_identidad = '1121960979', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'brigitte.ariasb@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'karina.contrerasve@amigo.edu.co', documento_identidad = '1128434100', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'karina.contrerasve@amigo.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'alejandra16250@hotmail.com', documento_identidad = '1144135830', departamento_id = 21, municipio_id = 962, updated_at = NOW() WHERE email = 'alejandra16250@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maviclo99@gmail.com', documento_identidad = '1233194378', updated_at = NOW() WHERE email = 'maviclo99@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ubercondes@gmail.com', documento_identidad = '1977546', departamento_id = 16, municipio_id = 758, updated_at = NOW() WHERE email = 'ubercondes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cheovelasquezp@hotmail.com', documento_identidad = '2376176', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'cheovelasquezp@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rafaellvillanuevas@gmail.com', documento_identidad = '3771374', departamento_id = 2, municipio_id = 147, updated_at = NOW() WHERE email = 'rafaellvillanuevas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elyermanito@gmail.com', documento_identidad = '4351385', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'elyermanito@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luishernanramirez54@gmail.com', documento_identidad = '4663800', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'luishernanramirez54@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luciak2531@gmail.com', documento_identidad = '4774785', departamento_id = 6, municipio_id = 374, updated_at = NOW() WHERE email = 'luciak2531@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vidalrojasdiego@gmail.com', documento_identidad = '4851790', departamento_id = 11, municipio_id = 555, updated_at = NOW() WHERE email = 'vidalrojasdiego@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cesajaramillo26@hotmail.com', documento_identidad = '4939964', departamento_id = 12, municipio_id = 609, updated_at = NOW() WHERE email = 'cesajaramillo26@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgepeinado21@gmail.com', documento_identidad = '5591543', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'jorgepeinado21@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'panfinioramirez0821@gmail.com', documento_identidad = '5671243', departamento_id = 18, municipio_id = 822, updated_at = NOW() WHERE email = 'panfinioramirez0821@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leomogotes75@gmail.com', documento_identidad = '5690061', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'leomogotes75@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'antoniorojas79@outlook.com', documento_identidad = '5765378', departamento_id = 18, municipio_id = 854, updated_at = NOW() WHERE email = 'antoniorojas79@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'eugenio.rodriguez.lievano@gmail.com', documento_identidad = '5933024', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'eugenio.rodriguez.lievano@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlosamurielrojas_1@hotmail.com', documento_identidad = '6110430', departamento_id = 21, municipio_id = 970, updated_at = NOW() WHERE email = 'carlosamurielrojas_1@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sixtotul617@hotmail.com', documento_identidad = '6175787', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'sixtotul617@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marcos-dani.perez@hotmail.com', documento_identidad = '6228319', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'marcos-dani.perez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fidel.arias753@gmail.com', documento_identidad = '6298067', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'fidel.arias753@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'franciscojcifuentes@gmail.com', documento_identidad = '6318579', departamento_id = 21, municipio_id = 955, updated_at = NOW() WHERE email = 'franciscojcifuentes@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'javy926@gmail.com', documento_identidad = '6342645', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'javy926@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'inkary@hotmail.com', documento_identidad = '6423138', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'inkary@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rafaelguerra1324@gmail.com', documento_identidad = '6661821', departamento_id = 8, municipio_id = 422, updated_at = NOW() WHERE email = 'rafaelguerra1324@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'monocat012957@gmail.com', documento_identidad = '6760331', departamento_id = 24, municipio_id = 1006, updated_at = NOW() WHERE email = 'monocat012957@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arambal@live.com', documento_identidad = '7010875', departamento_id = 4, municipio_id = 214, updated_at = NOW() WHERE email = 'arambal@live.com' AND tenant_id = 1;
UPDATE users SET name = 'ulises020871@gmail.com', documento_identidad = '7164402', departamento_id = 4, municipio_id = 191, updated_at = NOW() WHERE email = 'ulises020871@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marcosdaniel07@gmail.com', documento_identidad = '7305668', departamento_id = 4, municipio_id = 206, updated_at = NOW() WHERE email = 'marcosdaniel07@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ulisesnino67@gmail.com', documento_identidad = '7361787', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'ulisesnino67@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rafaeljulio030@hotmail.com', documento_identidad = '7462630', departamento_id = 24, municipio_id = 1007, updated_at = NOW() WHERE email = 'rafaeljulio030@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edorpenen@gmail.com', documento_identidad = '7479539', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'edorpenen@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alvarolargorada@gmail.com', documento_identidad = '7508823', departamento_id = 17, municipio_id = 763, updated_at = NOW() WHERE email = 'alvarolargorada@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'amoral21@hotmail.com', documento_identidad = '7520839', departamento_id = 35, municipio_id = 1255, updated_at = NOW() WHERE email = 'amoral21@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgeluisternera@hotmail.com', documento_identidad = '7592452', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'jorgeluisternera@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'collazosedgardo177@gmail.com', documento_identidad = '7598299', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'collazosedgardo177@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'casimirodera@gmail.com', documento_identidad = '7630187', departamento_id = 18, municipio_id = 836, updated_at = NOW() WHERE email = 'casimirodera@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'williguevara35@gmail.com', documento_identidad = '7709927', departamento_id = 23, municipio_id = 996, updated_at = NOW() WHERE email = 'williguevara35@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'oscaralfaroreales@gmail.com', documento_identidad = '7958564', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'oscaralfaroreales@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'benitoalcalde@gmail.com', documento_identidad = '8045578', departamento_id = 1, municipio_id = 35, updated_at = NOW() WHERE email = 'benitoalcalde@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'argonconstrucciones@yahoo.es', documento_identidad = '8171986', departamento_id = 1, municipio_id = 13, updated_at = NOW() WHERE email = 'argonconstrucciones@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'renevillau@gmail.com', documento_identidad = '8400069', departamento_id = 1, municipio_id = 55, updated_at = NOW() WHERE email = 'renevillau@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sumoexisto@gmail.com', documento_identidad = '8405489', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'sumoexisto@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dreyes1801@hotmail.com', documento_identidad = '8633462', departamento_id = 2, municipio_id = 142, updated_at = NOW() WHERE email = 'dreyes1801@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'emilianomejia875@gmail.com', documento_identidad = '8686014', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'emilianomejia875@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'omarbv1901@gmail.com', documento_identidad = '8739126', departamento_id = 2, municipio_id = 127, updated_at = NOW() WHERE email = 'omarbv1901@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'evarolando1957@gmail.com', documento_identidad = '8752019', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'evarolando1957@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cassereschavez1004@gmail.com', documento_identidad = '9085950', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'cassereschavez1004@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'clubdebeisbolsanjose@yahoo.es', documento_identidad = '9092027', departamento_id = 8, municipio_id = 429, updated_at = NOW() WHERE email = 'clubdebeisbolsanjose@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'williamgarcia1754@gmail.com', documento_identidad = '9309125', departamento_id = 19, municipio_id = 864, updated_at = NOW() WHERE email = 'williamgarcia1754@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cesargiovanny@gmail.com', documento_identidad = '9399612', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'cesargiovanny@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'amigosdeyopal82@hotmail.com', documento_identidad = '9431123', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'amigosdeyopal82@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jhmenan@gmail.com', documento_identidad = '9930241', departamento_id = 17, municipio_id = 774, updated_at = NOW() WHERE email = 'jhmenan@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ofcr356@gmail.com', documento_identidad = '10082363', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'ofcr356@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lucarga@gmail.com', documento_identidad = '10098737', departamento_id = 35, municipio_id = 1280, updated_at = NOW() WHERE email = 'lucarga@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nicolasarielocampo1@gmail.com', documento_identidad = '10247006', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'nicolasarielocampo1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgepizo@gmail.com', documento_identidad = '10299344', departamento_id = 6, municipio_id = 377, updated_at = NOW() WHERE email = 'jorgepizo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'albertoe15@hotmail.com', documento_identidad = '10347852', departamento_id = 6, municipio_id = 360, updated_at = NOW() WHERE email = 'albertoe15@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'silviomedina39@gmail.com', documento_identidad = '10476367', departamento_id = 8, municipio_id = 420, updated_at = NOW() WHERE email = 'silviomedina39@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pardito1254@gmail.com', documento_identidad = '10522258', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'pardito1254@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luchoafinamiento@gmail.com', documento_identidad = '10536038', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'luchoafinamiento@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arcesioquinonez7@gmail.com', documento_identidad = '10690981', departamento_id = 6, municipio_id = 372, updated_at = NOW() WHERE email = 'arcesioquinonez7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edilsonangulo22@gmail.com', documento_identidad = '10692536', departamento_id = 6, municipio_id = 344, updated_at = NOW() WHERE email = 'edilsonangulo22@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisfernando_ballesteros@hotmail.com', documento_identidad = '10766008', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'luisfernando_ballesteros@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'benycruz06@gmail.com', documento_identidad = '11292499', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'benycruz06@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgebarriosgutie@gmail.com', documento_identidad = '11294380', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'jorgebarriosgutie@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'j.erinzonruiz@gmail.com', documento_identidad = '11405903', departamento_id = 24, municipio_id = 1015, updated_at = NOW() WHERE email = 'j.erinzonruiz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carmelovalencia@hotmail.com', documento_identidad = '11788516', departamento_id = 11, municipio_id = 550, updated_at = NOW() WHERE email = 'carmelovalencia@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'huilahumana@gmail.com', documento_identidad = '12105800', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'huilahumana@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carbajaldelfin@yahoo.com', documento_identidad = '12189313', departamento_id = 12, municipio_id = 1306, updated_at = NOW() WHERE email = 'carbajaldelfin@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'leomuco310@gmail.com', documento_identidad = '12270837', departamento_id = 12, municipio_id = 593, updated_at = NOW() WHERE email = 'leomuco310@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ceravegagabriel@gmail.com', documento_identidad = '12536067', departamento_id = 13, municipio_id = 639, updated_at = NOW() WHERE email = 'ceravegagabriel@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'karulramirez_70@hotmail.com', documento_identidad = '12618335', departamento_id = 13, municipio_id = 639, updated_at = NOW() WHERE email = 'karulramirez_70@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bayarruiz0@gmail.com', documento_identidad = '12978869', departamento_id = 14, municipio_id = 646, updated_at = NOW() WHERE email = 'bayarruiz0@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'willmarquiroz2018@gmail.com', documento_identidad = '12996182', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'willmarquiroz2018@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tobal59@hotmail.com', documento_identidad = '13445001', departamento_id = 18, municipio_id = 859, updated_at = NOW() WHERE email = 'tobal59@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jamerchan83@gmail.com', documento_identidad = '13466836', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'jamerchan83@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'riosjuagro@gmail.com', documento_identidad = '13566560', departamento_id = 1, municipio_id = 83, updated_at = NOW() WHERE email = 'riosjuagro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tiinfred_@hotmail.com', documento_identidad = '13620528', departamento_id = 18, municipio_id = 829, updated_at = NOW() WHERE email = 'tiinfred_@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'miguel.garcia20@gmail.com', documento_identidad = '13835235', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'miguel.garcia20@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tecnorotativos@yahoo.com', documento_identidad = '13846918', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'tecnorotativos@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'luisalejandrorangel0210@gmail.com', documento_identidad = '13880745', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'luisalejandrorangel0210@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gabrielmoyapedrozo@gmail.com', documento_identidad = '13883188', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'gabrielmoyapedrozo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'laeb23@hotmail.com', documento_identidad = '13883385', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'laeb23@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alvaradoseduard@gmail.com', documento_identidad = '13907011', departamento_id = 18, municipio_id = 791, updated_at = NOW() WHERE email = 'alvaradoseduard@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'petronicpjp1@gmail.com', documento_identidad = '14449411', departamento_id = 21, municipio_id = 956, updated_at = NOW() WHERE email = 'petronicpjp1@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fexpovalle2019@gmail.com', documento_identidad = '14839434', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'fexpovalle2019@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisfesalazar@hotmail.com', documento_identidad = '14894954', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'luisfesalazar@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'algonso7@gmail.com', documento_identidad = '14947178', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'algonso7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luiseduardojimenezbedoya@gmail.com', documento_identidad = '14987736', departamento_id = 21, municipio_id = 974, updated_at = NOW() WHERE email = 'luiseduardojimenezbedoya@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejofreitass74@gmail.com', documento_identidad = '15877884', departamento_id = 30, municipio_id = 1077, updated_at = NOW() WHERE email = 'alejofreitass74@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joaleochoa@gmail.com', documento_identidad = '16253650', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'joaleochoa@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jairotovarichoctubre1917@gmail.com', documento_identidad = '16263538', departamento_id = 21, municipio_id = 962, updated_at = NOW() WHERE email = 'jairotovarichoctubre1917@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jmoncadaesquivel@outlook.com', documento_identidad = '16348723', departamento_id = 21, municipio_id = 970, updated_at = NOW() WHERE email = 'jmoncadaesquivel@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'gabrielcusi1954@gmail.com', documento_identidad = '16447732', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'gabrielcusi1954@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camiyeso0504@gmail.com', documento_identidad = '16447871', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'camiyeso0504@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'blaimiralarcon@gmail.com', documento_identidad = '16448266', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'blaimiralarcon@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arseniojusto1962@hotmail.com', documento_identidad = '16482296', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'arseniojusto1962@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'che.saavedra@hotmail.com', documento_identidad = '16580742', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'che.saavedra@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisfernandocaballos@yahoo.com', documento_identidad = '16582233', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'luisfernandocaballos@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'lmoreno.ipk41643@gmail.com', documento_identidad = '16605745', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'lmoreno.ipk41643@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juaneleon24@gmail.com', documento_identidad = '16614118', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'juaneleon24@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'magimaya@hotmail.es', documento_identidad = '16633640', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'magimaya@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'ledesfred60@gmail.com', documento_identidad = '16657247', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'ledesfred60@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cano230363@gmail.com', documento_identidad = '16681753', departamento_id = 21, municipio_id = 950, updated_at = NOW() WHERE email = 'cano230363@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jhsaenz63@gmail.com', documento_identidad = '16686208', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'jhsaenz63@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisalf2164@hotmail.com', documento_identidad = '16857445', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'luisalf2164@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jhozap841@outlook.com', documento_identidad = '16897841', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'jhozap841@outlook.com' AND tenant_id = 1;
UPDATE users SET name = 'perezequiceno@gmail.com', documento_identidad = '17099518', departamento_id = 15, municipio_id = 712, updated_at = NOW() WHERE email = 'perezequiceno@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'romanicoltda@gmail.com', documento_identidad = '17186820', departamento_id = 9, municipio_id = 439, updated_at = NOW() WHERE email = 'romanicoltda@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'efradelirio@yahoo.es', documento_identidad = '17315181', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'efradelirio@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'electrocacique@gmail.com', documento_identidad = '17555169', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'electrocacique@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gp18toro@gmail.com', documento_identidad = '18186942', departamento_id = 31, municipio_id = 1101, updated_at = NOW() WHERE email = 'gp18toro@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tolozavictor40@gmail.com', documento_identidad = '18970529', departamento_id = 7, municipio_id = 388, updated_at = NOW() WHERE email = 'tolozavictor40@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'claudio.quembah@gmail.com', documento_identidad = '19069508', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'claudio.quembah@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alvarohmejia@hotmail.com', documento_identidad = '19109017', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'alvarohmejia@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'macarios51@hotmail.com', documento_identidad = '19167392', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'macarios51@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'acb.395@hotmail.com', documento_identidad = '19226395', departamento_id = 9, municipio_id = 496, updated_at = NOW() WHERE email = 'acb.395@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'encinardemanrre.elrefugio@gmail.com', documento_identidad = '19232248', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'encinardemanrre.elrefugio@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'grobayodef20@gmail.com', documento_identidad = '19300354', departamento_id = 9, municipio_id = 544, updated_at = NOW() WHERE email = 'grobayodef20@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nohelindaromero@yahoo.es', documento_identidad = '19393908', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'nohelindaromero@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'carlos.vlopez1960@gmail.com', documento_identidad = '19462290', departamento_id = 9, municipio_id = 468, updated_at = NOW() WHERE email = 'carlos.vlopez1960@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edil17@gmail.com', documento_identidad = '19403044', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'edil17@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'magnasi@gmail.com', documento_identidad = '21223663', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'magnasi@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bayuelomartha12@gmail.com', documento_identidad = '22726945', departamento_id = 2, municipio_id = 127, updated_at = NOW() WHERE email = 'bayuelomartha12@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'henapao@hotmail.com', documento_identidad = '23229677', departamento_id = 3, municipio_id = 187, updated_at = NOW() WHERE email = 'henapao@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hildalulu@gmail.com', documento_identidad = '24047149', departamento_id = 4, municipio_id = 299, updated_at = NOW() WHERE email = 'hildalulu@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mis2tesoritossj.ypgv@gmail.com', documento_identidad = '26231544', departamento_id = 8, municipio_id = 434, updated_at = NOW() WHERE email = 'mis2tesoritossj.ypgv@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zoilarosas054@gmail.com', documento_identidad = '26900868', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'zoilarosas054@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'negrasoy1958@gmail.com', documento_identidad = '27258066', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'negrasoy1958@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'florca33.2015@gmail.com', documento_identidad = '27592111', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'florca33.2015@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dulceyjudith@gmail.com', documento_identidad = '28338877', departamento_id = 18, municipio_id = 802, updated_at = NOW() WHERE email = 'dulceyjudith@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ortizramirezana@gmail.com', documento_identidad = '28494687', departamento_id = 18, municipio_id = 854, updated_at = NOW() WHERE email = 'ortizramirezana@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sandravasquez3000@gmail.com', documento_identidad = '28496706', departamento_id = 18, municipio_id = 854, updated_at = NOW() WHERE email = 'sandravasquez3000@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lireti123@gmail.com', documento_identidad = '28688090', departamento_id = 20, municipio_id = 901, updated_at = NOW() WHERE email = 'lireti123@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'giraldonelly59@gmail.com', documento_identidad = '29702490', departamento_id = 21, municipio_id = 962, updated_at = NOW() WHERE email = 'giraldonelly59@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ana_maya_alegrias@hotmail.com', documento_identidad = '29703957', departamento_id = 21, municipio_id = 962, updated_at = NOW() WHERE email = 'ana_maya_alegrias@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'huellitasdeamorj@gmail.com', documento_identidad = '30335854', departamento_id = 5, municipio_id = 316, updated_at = NOW() WHERE email = 'huellitasdeamorj@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bibianapc2012@gmail.com', documento_identidad = '30659697', departamento_id = 8, municipio_id = 417, updated_at = NOW() WHERE email = 'bibianapc2012@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'asojupmpal@gmail.com', documento_identidad = '31141669', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'asojupmpal@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lucha2824@hotmail.com', documento_identidad = '31281329', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'lucha2824@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marcerolces54@gmail.com', documento_identidad = '31468760', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'marcerolces54@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'oliverrodrigu843@gmail.com', documento_identidad = '31794475', departamento_id = 21, municipio_id = 942, updated_at = NOW() WHERE email = 'oliverrodrigu843@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nancysanchez50028@yahoo.com', documento_identidad = '31847907', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'nancysanchez50028@yahoo.com' AND tenant_id = 1;
UPDATE users SET name = 'sanchezn205@gmail.com', documento_identidad = '31867470', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'sanchezn205@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'blam_68@msn.com', documento_identidad = '31981920', departamento_id = 35, municipio_id = 1167, updated_at = NOW() WHERE email = 'blam_68@msn.com' AND tenant_id = 1;
UPDATE users SET name = 'ginamariagomez16@gmail.com', documento_identidad = '31983459', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'ginamariagomez16@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luciadelsocorro@yahoo.es', documento_identidad = '32487158', departamento_id = 1, municipio_id = 83, updated_at = NOW() WHERE email = 'luciadelsocorro@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'noiramargarita.perez@gmail.com', documento_identidad = '32632972', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'noiramargarita.perez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carmendelahoz2211@gmail.com', documento_identidad = '32698066', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'carmendelahoz2211@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'candanozarocioisabel@gmail.com', documento_identidad = '32864034', departamento_id = 2, municipio_id = 145, updated_at = NOW() WHERE email = 'candanozarocioisabel@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'astwoodalejandra@gmail.com', documento_identidad = '32892658', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'astwoodalejandra@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'velascopbe75@gmail.com', documento_identidad = '34528950', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'velascopbe75@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anromoca59@hotmail.com', documento_identidad = '35314944', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'anromoca59@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dolisesthergutierrez@gmail.com', documento_identidad = '36577172', departamento_id = 13, municipio_id = 637, updated_at = NOW() WHERE email = 'dolisesthergutierrez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fabiolasilvatorres2013@gmail.com', documento_identidad = '37625730', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'fabiolasilvatorres2013@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lejomer@protonmail.com', documento_identidad = '37864683', departamento_id = 18, municipio_id = 835, updated_at = NOW() WHERE email = 'lejomer@protonmail.com' AND tenant_id = 1;
UPDATE users SET name = 'misabelgarcia.50@hotmail.com', documento_identidad = '37889251', departamento_id = 18, municipio_id = 795, updated_at = NOW() WHERE email = 'misabelgarcia.50@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzmape2023@gmail.com', documento_identidad = '39015679', departamento_id = 13, municipio_id = 623, updated_at = NOW() WHERE email = 'luzmape2023@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anabejarano86@hotmail.com', documento_identidad = '39527696', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'anabejarano86@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marthacecilia_1004@hotmail.com', documento_identidad = '40271580', departamento_id = 24, municipio_id = 1015, updated_at = NOW() WHERE email = 'marthacecilia_1004@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jepavi1955@gmail.com', documento_identidad = '40330403', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'jepavi1955@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angelamonzon1@hotmail.com', documento_identidad = '40412088', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'angelamonzon1@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ayubibeatriz@gmail.com', documento_identidad = '40762278', departamento_id = 23, municipio_id = 984, updated_at = NOW() WHERE email = 'ayubibeatriz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'elisabeth_csjd_1983@hotmail.com', documento_identidad = '41061044', departamento_id = 35, municipio_id = 1251, updated_at = NOW() WHERE email = 'elisabeth_csjd_1983@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mstella.hs@gmail.com', documento_identidad = '41578409', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'mstella.hs@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alisiapv2020@gmail.com', documento_identidad = '42756546', departamento_id = 1, municipio_id = 52, updated_at = NOW() WHERE email = 'alisiapv2020@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rmontalvomonroy@gmail.com', documento_identidad = '45437089', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'rmontalvomonroy@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kandykandy3235@gmail.com', documento_identidad = '45490473', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'kandykandy3235@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'viajacontraveltour@gmail.com', documento_identidad = '47429117', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'viajacontraveltour@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'albac.caro@hotmail.com', documento_identidad = '49719600', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'albac.caro@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yelisbuelvaspitre@gmail.com', documento_identidad = '49732594', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'yelisbuelvaspitre@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'barbaraortizgutierrez@gmail.com', documento_identidad = '49746388', departamento_id = 7, municipio_id = 390, updated_at = NOW() WHERE email = 'barbaraortizgutierrez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anasabogalhumana@gmail.com', documento_identidad = '51592305', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'anasabogalhumana@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'barreroluzangela@gmail.com', documento_identidad = '51644671', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'barreroluzangela@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bettypenal@hotmail.com', documento_identidad = '51654973', departamento_id = 3, municipio_id = 155, updated_at = NOW() WHERE email = 'bettypenal@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juordy65@hotmail.com', documento_identidad = '51771958', departamento_id = 12, municipio_id = 597, updated_at = NOW() WHERE email = 'juordy65@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'claudia.cabrera352@gmail.com', documento_identidad = '51851371', departamento_id = 24, municipio_id = 1006, updated_at = NOW() WHERE email = 'claudia.cabrera352@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'revistam.buchelly@gmail.com', documento_identidad = '52040266', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'revistam.buchelly@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'menchisana@gmail.com', documento_identidad = '52052118', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'menchisana@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sanabriasandra27@gmail.com', documento_identidad = '52219936', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'sanabriasandra27@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carmenzatorres2018@gmail.com', documento_identidad = '52363841', departamento_id = 26, municipio_id = 1051, updated_at = NOW() WHERE email = 'carmenzatorres2018@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'linapaolaalza@gmail.com', documento_identidad = '52703525', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'linapaolaalza@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jmontaezt@gmail.com', documento_identidad = '52719144', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'jmontaezt@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carolinagarciaquiroga@gmail.com', documento_identidad = '52786137', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'carolinagarciaquiroga@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'francyamelia@gmail.com', documento_identidad = '53009076', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'francyamelia@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edithparada@gmail.com', documento_identidad = '53124146', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'edithparada@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rubygomez1419945@gmail.com', documento_identidad = '57301696', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'rubygomez1419945@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'monigarabata@gmail.com', documento_identidad = '57432690', departamento_id = 35, municipio_id = 1114, updated_at = NOW() WHERE email = 'monigarabata@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'eangul68@gmail.com', documento_identidad = '59666217', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'eangul68@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lopezgladis7459@yahoo.es', documento_identidad = '59824956', departamento_id = 31, municipio_id = 1095, updated_at = NOW() WHERE email = 'lopezgladis7459@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'lunaverde19@gmail.com', documento_identidad = '63354130', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'lunaverde19@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rochy251093@gmail.com', documento_identidad = '63366557', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'rochy251093@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'inesorqui3015@gmail.com', documento_identidad = '63442888', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'inesorqui3015@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'chatica2497@gmail.com', documento_identidad = '63493122', departamento_id = 18, municipio_id = 854, updated_at = NOW() WHERE email = 'chatica2497@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carmencovo56@gmail.com', documento_identidad = '64541994', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'carmencovo56@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanita.j.contreras.s@gmail.com', documento_identidad = '64891932', departamento_id = 19, municipio_id = 876, updated_at = NOW() WHERE email = 'juanita.j.contreras.s@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sandralilianacupitra@gmail.com', documento_identidad = '65790950', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'sandralilianacupitra@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'nanaville662@gmail.com', documento_identidad = '66782313', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'nanaville662@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lapita246@gmail.com', documento_identidad = '70036512', departamento_id = 18, municipio_id = 854, updated_at = NOW() WHERE email = 'lapita246@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rosalioarroyo7@gmail.com', documento_identidad = '70527141', departamento_id = 1, municipio_id = 32, updated_at = NOW() WHERE email = 'rosalioarroyo7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vivianavargas0902@gmail.com', documento_identidad = '71876722', departamento_id = 21, municipio_id = 942, updated_at = NOW() WHERE email = 'vivianavargas0902@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jberdugo.ledger@gmail.com', documento_identidad = '72021095', departamento_id = 2, municipio_id = 127, updated_at = NOW() WHERE email = 'jberdugo.ledger@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'isloma66@hotmail.com', documento_identidad = '72139139', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'isloma66@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hayder2782@gmail.com', documento_identidad = '72295027', departamento_id = 2, municipio_id = 129, updated_at = NOW() WHERE email = 'hayder2782@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'asopisan@hotmail.es', documento_identidad = '73095021', departamento_id = 13, municipio_id = 639, updated_at = NOW() WHERE email = 'asopisan@hotmail.es' AND tenant_id = 1;
UPDATE users SET name = 'edwinalexanderf452@gmail.com', documento_identidad = '74082270', departamento_id = 24, municipio_id = 1001, updated_at = NOW() WHERE email = 'edwinalexanderf452@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hriveraxx@gmail.com', documento_identidad = '74300212', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'hriveraxx@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leandroadamemvz@gmail.com', documento_identidad = '74380429', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'leandroadamemvz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alcibiadesrojasmarino@gmail.com', documento_identidad = '74846473', departamento_id = 24, municipio_id = 1017, updated_at = NOW() WHERE email = 'alcibiadesrojasmarino@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andresrosascristancho18@gmail.com', documento_identidad = '74857324', departamento_id = 24, municipio_id = 1010, updated_at = NOW() WHERE email = 'andresrosascristancho18@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'lfenriquez1@misena.edu.co', documento_identidad = '76311121', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'lfenriquez1@misena.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'jagonu4851@gmail.com', documento_identidad = '77154851', departamento_id = 7, municipio_id = 384, updated_at = NOW() WHERE email = 'jagonu4851@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edcastellano2015@hotmail.com', documento_identidad = '77156816', departamento_id = 7, municipio_id = 384, updated_at = NOW() WHERE email = 'edcastellano2015@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'emeilhidalgo82@gmail.com', documento_identidad = '77176504', departamento_id = 7, municipio_id = 389, updated_at = NOW() WHERE email = 'emeilhidalgo82@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'asodeviaicaesal@gmail.com', documento_identidad = '77188432', departamento_id = 8, municipio_id = 436, updated_at = NOW() WHERE email = 'asodeviaicaesal@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carvajal7869@gmail.com', documento_identidad = '78691271', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'carvajal7869@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jahiramp@yahoo.com.mx', documento_identidad = '79255137', departamento_id = 9, municipio_id = 454, updated_at = NOW() WHERE email = 'jahiramp@yahoo.com.mx' AND tenant_id = 1;
UPDATE users SET name = 'henry.comercio9@gmail.com', documento_identidad = '79272602', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'henry.comercio9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'saxofon_armando@hotmail.com', documento_identidad = '79303049', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'saxofon_armando@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ricardomurcia99@gmail.com', documento_identidad = '79344098', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'ricardomurcia99@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jah7565@gmail.com', documento_identidad = '79366154', departamento_id = 9, municipio_id = 439, updated_at = NOW() WHERE email = 'jah7565@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marquezabdul512@gmail.com', documento_identidad = '79374339', departamento_id = 7, municipio_id = 379, updated_at = NOW() WHERE email = 'marquezabdul512@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'erikwercan@gmail.com', documento_identidad = '79503065', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'erikwercan@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'amelo74@misena.edu.co', documento_identidad = '79542647', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'amelo74@misena.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'jabca2020@gmail.com', documento_identidad = '79804381', departamento_id = 11, municipio_id = 564, updated_at = NOW() WHERE email = 'jabca2020@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'salamancapena10851225@gmail.com', documento_identidad = '80387964', departamento_id = 24, municipio_id = 1011, updated_at = NOW() WHERE email = 'salamancapena10851225@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'potogalindo@hotmail.com', documento_identidad = '80471156', departamento_id = 35, municipio_id = 1168, updated_at = NOW() WHERE email = 'potogalindo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camilobolanosmunoz@gmail.com', documento_identidad = '83028111', departamento_id = 23, municipio_id = 996, updated_at = NOW() WHERE email = 'camilobolanosmunoz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'danielsalazarmolina@hotmail.com', documento_identidad = '83218275', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'danielsalazarmolina@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'docentealbertomunoz@gmail.com', documento_identidad = '84032063', departamento_id = 25, municipio_id = 1019, updated_at = NOW() WHERE email = 'docentealbertomunoz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pushainasilver76@gmail.com', documento_identidad = '84062614', departamento_id = 25, municipio_id = 1021, updated_at = NOW() WHERE email = 'pushainasilver76@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joseluisortegaaponte@gmail.com', documento_identidad = '84450687', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'joseluisortegaaponte@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'artunduaga.dio@hotmail.com', documento_identidad = '86069945', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'artunduaga.dio@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlosangulogongora07@gmail.com', documento_identidad = '87943201', departamento_id = 14, municipio_id = 706, updated_at = NOW() WHERE email = 'carlosangulogongora07@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'osmarsep49@gmail.com', documento_identidad = '88200359', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'osmarsep49@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jramirezfuentes582@gmail.com', documento_identidad = '88203918', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'jramirezfuentes582@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'javierojas090@gmail.com', documento_identidad = '88247045', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'javierojas090@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlosgomez7812@gmail.com', documento_identidad = '88278021', departamento_id = 16, municipio_id = 741, updated_at = NOW() WHERE email = 'carlosgomez7812@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlos.alons29@gmail.com', documento_identidad = '91071444', departamento_id = 18, municipio_id = 795, updated_at = NOW() WHERE email = 'carlos.alons29@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luchoarenas57@hotmail.com', documento_identidad = '91100582', departamento_id = 18, municipio_id = 793, updated_at = NOW() WHERE email = 'luchoarenas57@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'chatoski4@gmail.com', documento_identidad = '91220715', departamento_id = 18, municipio_id = 854, updated_at = NOW() WHERE email = 'chatoski4@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'arojas136312@yahoo.es', documento_identidad = '91227670', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'arojas136312@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'lisbeliaduran62@gmail.com', documento_identidad = '91237627', departamento_id = 18, municipio_id = 802, updated_at = NOW() WHERE email = 'lisbeliaduran62@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'edinsondiosabca@gmail.com', documento_identidad = '91269014', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'edinsondiosabca@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'javierperhdz@yahoo.es', documento_identidad = '91421469', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'javierperhdz@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'nelsonarmesto@hotmail.com', documento_identidad = '91425199', departamento_id = 13, municipio_id = 637, updated_at = NOW() WHERE email = 'nelsonarmesto@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jaimevillalobos178@gmail.com', documento_identidad = '91425765', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'jaimevillalobos178@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisc1548@hotmail.com', documento_identidad = '91427083', departamento_id = 18, municipio_id = 781, updated_at = NOW() WHERE email = 'luisc1548@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jpabonbarajas@gmail.com', documento_identidad = '91465919', departamento_id = 18, municipio_id = 802, updated_at = NOW() WHERE email = 'jpabonbarajas@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisga2004@gmail.com', documento_identidad = '91480881', departamento_id = 18, municipio_id = 795, updated_at = NOW() WHERE email = 'luisga2004@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tulycysar18@yahoo.es', documento_identidad = '92496207', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'tulycysar18@yahoo.es' AND tenant_id = 1;
UPDATE users SET name = 'mariaauxiliadora24@hotmail.com', documento_identidad = '92517105', departamento_id = 19, municipio_id = 855, updated_at = NOW() WHERE email = 'mariaauxiliadora24@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'singes.h.s.e.q.consultoria@gmail.com', documento_identidad = '92556211', departamento_id = 4, municipio_id = 264, updated_at = NOW() WHERE email = 'singes.h.s.e.q.consultoria@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'geovaldysalcedo@hotmail.com', documento_identidad = '92600147', departamento_id = 19, municipio_id = 863, updated_at = NOW() WHERE email = 'geovaldysalcedo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'acosta_asoabog@hotmail.com', documento_identidad = '93151110', departamento_id = 20, municipio_id = 926, updated_at = NOW() WHERE email = 'acosta_asoabog@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'garzonballesteros@gmail.com', documento_identidad = '93392600', departamento_id = 5, municipio_id = 316, updated_at = NOW() WHERE email = 'garzonballesteros@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'tino.murillo@hotmail.com', documento_identidad = '94320421', departamento_id = 11, municipio_id = 556, updated_at = NOW() WHERE email = 'tino.murillo@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rodolfogholguin@hotmail.com', documento_identidad = '94499752', updated_at = NOW() WHERE email = 'rodolfogholguin@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'actorarteaga@hotmail.com', documento_identidad = '94517726', departamento_id = 21, municipio_id = 935, updated_at = NOW() WHERE email = 'actorarteaga@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carmol0374@gmail.com', documento_identidad = '97446185', departamento_id = 4, municipio_id = 191, updated_at = NOW() WHERE email = 'carmol0374@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'osckrmb@hotmail.com', documento_identidad = '97481163', departamento_id = 31, municipio_id = 1093, updated_at = NOW() WHERE email = 'osckrmb@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'petrugo1976@hotmail.com', documento_identidad = '98628097', departamento_id = 1, municipio_id = 52, updated_at = NOW() WHERE email = 'petrugo1976@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wvsogamoso@gmail.com', documento_identidad = '1000517089', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'wvsogamoso@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ivanna.vargas.22@gmail.com', documento_identidad = '1002030933', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'ivanna.vargas.22@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jeankrv51@gmail.com', documento_identidad = '1002035009', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'jeankrv51@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zulypin09@gmail.com', documento_identidad = '1002460114', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'zulypin09@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ingrid.barrera@uptc.edu.co', documento_identidad = '1002559724', departamento_id = 24, municipio_id = 1005, updated_at = NOW() WHERE email = 'ingrid.barrera@uptc.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'mayragarzon75@gmail.com', documento_identidad = '1003690267', departamento_id = 9, municipio_id = 484, updated_at = NOW() WHERE email = 'mayragarzon75@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hamiltoncarpiomembache@gmail.com', documento_identidad = '1003787268', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'hamiltoncarpiomembache@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'rodeca1994@gmail.com', documento_identidad = '1004122635', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'rodeca1994@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cesara0307@gmail.com', documento_identidad = '1005237692', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'cesara0307@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'zharito16@hotmail.com', documento_identidad = '1005345380', departamento_id = 18, municipio_id = 822, updated_at = NOW() WHERE email = 'zharito16@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzadrianamunozviloria@gmail.com', documento_identidad = '1005581998', departamento_id = 19, municipio_id = 873, updated_at = NOW() WHERE email = 'luzadrianamunozviloria@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'barrera.luis@correounivalle.edu.co', documento_identidad = '1005744411', departamento_id = 21, municipio_id = 941, updated_at = NOW() WHERE email = 'barrera.luis@correounivalle.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'wildervaldezrosero@gmail.com', documento_identidad = '1006787959', departamento_id = 31, municipio_id = 1095, updated_at = NOW() WHERE email = 'wildervaldezrosero@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'k28535516@gmail.com', documento_identidad = '1007190552', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'k28535516@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jorgetami1935@gmail.com', documento_identidad = '1007196941', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'jorgetami1935@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'julieth.lamus26042000@gmail.com', documento_identidad = '1007323048', departamento_id = 18, municipio_id = 796, updated_at = NOW() WHERE email = 'julieth.lamus26042000@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mayerlinpaz11@gmail.com', documento_identidad = '1007440605', departamento_id = 6, municipio_id = 347, updated_at = NOW() WHERE email = 'mayerlinpaz11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alejandravides03@gmail.com', documento_identidad = '1007570617', departamento_id = 13, municipio_id = 623, updated_at = NOW() WHERE email = 'alejandravides03@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'adelarueda1624@gmail.com', documento_identidad = '1007890260', departamento_id = 2, municipio_id = 136, updated_at = NOW() WHERE email = 'adelarueda1624@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anderson.rivas@correounivalle.edu.co', documento_identidad = '1007954452', departamento_id = 21, municipio_id = 969, updated_at = NOW() WHERE email = 'anderson.rivas@correounivalle.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'jorgeluisurianaepiayu@gmail.com', documento_identidad = '1010015311', departamento_id = 25, municipio_id = 1026, updated_at = NOW() WHERE email = 'jorgeluisurianaepiayu@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'vathoryangel@hotmail.com', documento_identidad = '1014185620', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'vathoryangel@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joseluisbohorquezlopez@hotmail.com', documento_identidad = '1014246607', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'joseluisbohorquezlopez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juankos94@hotmail.com', documento_identidad = '1014252994', departamento_id = 4, municipio_id = 193, updated_at = NOW() WHERE email = 'juankos94@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'candidaturatefasanchez@gmail.com', documento_identidad = '1017196268', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'candidaturatefasanchez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leyes.higuita@gmail.com', documento_identidad = '1020395220', departamento_id = 1, municipio_id = 73, updated_at = NOW() WHERE email = 'leyes.higuita@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanita.arbelaez@hotmail.com', documento_identidad = '1020755697', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'juanita.arbelaez@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aleja.1192@hotmail.com', documento_identidad = '1022944946', departamento_id = 4, municipio_id = 289, updated_at = NOW() WHERE email = 'aleja.1192@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joseterdel@hotmail.com', documento_identidad = '1026261774', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'joseterdel@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leidycardenas852@hotmail.com', documento_identidad = '1031135811', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'leidycardenas852@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'oscwil@gmail.com', documento_identidad = '1032372962', departamento_id = 31, municipio_id = 1088, updated_at = NOW() WHERE email = 'oscwil@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joserperio@gmail.com', documento_identidad = '1032388015', departamento_id = 10, municipio_id = 549, updated_at = NOW() WHERE email = 'joserperio@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kristiansneyderodriguez@gmail.com', documento_identidad = '1032499013', departamento_id = 9, municipio_id = 519, updated_at = NOW() WHERE email = 'kristiansneyderodriguez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mauriciopalacio68@gmail.com', documento_identidad = '1035423924', departamento_id = 28, municipio_id = 1073, updated_at = NOW() WHERE email = 'mauriciopalacio68@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jopse86@gmail.com', documento_identidad = '1037577070', departamento_id = 1, municipio_id = 1, updated_at = NOW() WHERE email = 'jopse86@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mosquerajhonjairo02@gmail.com', documento_identidad = '1038804957', departamento_id = 11, municipio_id = 561, updated_at = NOW() WHERE email = 'mosquerajhonjairo02@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'maritzacpino@gmail.com', documento_identidad = '1046340376', departamento_id = 2, municipio_id = 143, updated_at = NOW() WHERE email = 'maritzacpino@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'pasogutan@gmail.com', documento_identidad = '1052414937', departamento_id = 4, municipio_id = 222, updated_at = NOW() WHERE email = 'pasogutan@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camachorafael21@hotmail.com', documento_identidad = '1053331431', departamento_id = 4, municipio_id = 202, updated_at = NOW() WHERE email = 'camachorafael21@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camilito8a236@gmail.com', documento_identidad = '1053605284', departamento_id = 4, municipio_id = 256, updated_at = NOW() WHERE email = 'camilito8a236@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jennyalejandratobon84@gmail.com', documento_identidad = '1053786574', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'jennyalejandratobon84@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'javier.pira@hotmail.com', documento_identidad = '1056482830', departamento_id = 4, municipio_id = 270, updated_at = NOW() WHERE email = 'javier.pira@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'alonrop@gmail.com', documento_identidad = '1057464051', departamento_id = 4, municipio_id = 266, updated_at = NOW() WHERE email = 'alonrop@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camilita625@gmail.com', documento_identidad = '1057584205', departamento_id = 4, municipio_id = 288, updated_at = NOW() WHERE email = 'camilita625@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'verdugom786@gmail.com', documento_identidad = '1058430848', departamento_id = 4, municipio_id = 296, updated_at = NOW() WHERE email = 'verdugom786@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ericamd.1989@gmail.com', documento_identidad = '1061018117', departamento_id = 6, municipio_id = 359, updated_at = NOW() WHERE email = 'ericamd.1989@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'karenyuliethmamian@gmail.com', documento_identidad = '1061694594', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'karenyuliethmamian@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carolina-cabanillas@hotmail.com', documento_identidad = '1061700285', departamento_id = 6, municipio_id = 346, updated_at = NOW() WHERE email = 'carolina-cabanillas@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jhonhedertfernandezlopez@gmail.com', documento_identidad = '1061727183', departamento_id = 6, municipio_id = 359, updated_at = NOW() WHERE email = 'jhonhedertfernandezlopez@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'hoyosjhon8@gmail.com', documento_identidad = '1061738075', departamento_id = 6, municipio_id = 342, updated_at = NOW() WHERE email = 'hoyosjhon8@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andresramirez137@hotmail.com', documento_identidad = '1062297274', departamento_id = 6, municipio_id = 347, updated_at = NOW() WHERE email = 'andresramirez137@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'miluchaapenascomienza89@gmail.com', documento_identidad = '1062807321', departamento_id = 7, municipio_id = 386, updated_at = NOW() WHERE email = 'miluchaapenascomienza89@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'margaritamariotarra@gmail.com', documento_identidad = '1063080334', departamento_id = 8, municipio_id = 414, updated_at = NOW() WHERE email = 'margaritamariotarra@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'gabbymu0496@gmail.com', documento_identidad = '1063817034', departamento_id = 6, municipio_id = 374, updated_at = NOW() WHERE email = 'gabbymu0496@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'brayanoteroyen@gmail.com', documento_identidad = '1067869533', departamento_id = 8, municipio_id = 407, updated_at = NOW() WHERE email = 'brayanoteroyen@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jjx39062@gmail.com', documento_identidad = '1075299906', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'jjx39062@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andresfelipeatiasarias@gmail.com', documento_identidad = '1075300690', departamento_id = 12, municipio_id = 580, updated_at = NOW() WHERE email = 'andresfelipeatiasarias@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camandc@hotmail.com', documento_identidad = '1075673929', departamento_id = 9, municipio_id = 548, updated_at = NOW() WHERE email = 'camandc@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'samirderecho@gmail.com', documento_identidad = '1077200072', departamento_id = 11, municipio_id = 569, updated_at = NOW() WHERE email = 'samirderecho@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'karina24yeraldin@gmail.com', documento_identidad = '1077840154', departamento_id = 12, municipio_id = 581, updated_at = NOW() WHERE email = 'karina24yeraldin@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'csrodmar@gmail.com', documento_identidad = '1079914488', departamento_id = 13, municipio_id = 631, updated_at = NOW() WHERE email = 'csrodmar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angelapatriciazunigaortiz@gmail.com', documento_identidad = '1082214208', departamento_id = 12, municipio_id = 615, updated_at = NOW() WHERE email = 'angelapatriciazunigaortiz@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ismargy@gmail.com', documento_identidad = '1082854071', departamento_id = 13, municipio_id = 626, updated_at = NOW() WHERE email = 'ismargy@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'montecristo.castilla@gmail.com', documento_identidad = '1082956969', departamento_id = 13, municipio_id = 616, updated_at = NOW() WHERE email = 'montecristo.castilla@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sv10901415@gmail.com', documento_identidad = '1082957247', departamento_id = 13, municipio_id = 630, updated_at = NOW() WHERE email = 'sv10901415@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andrescutiva24@gmail.com', documento_identidad = '1084330697', departamento_id = 6, municipio_id = 340, updated_at = NOW() WHERE email = 'andrescutiva24@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yonatanpantoja181@gmail.com', documento_identidad = '1085245481', departamento_id = 14, municipio_id = 654, updated_at = NOW() WHERE email = 'yonatanpantoja181@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'manuelcai456@hotmail.com', documento_identidad = '1085938650', departamento_id = 31, municipio_id = 1100, updated_at = NOW() WHERE email = 'manuelcai456@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'magueguenavas@hotmail.com', documento_identidad = '1088009015', departamento_id = 15, municipio_id = 715, updated_at = NOW() WHERE email = 'magueguenavas@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ingcarloso@gmail.com', documento_identidad = '1088279100', departamento_id = 15, municipio_id = 709, updated_at = NOW() WHERE email = 'ingcarloso@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'haver9054@gmail.com', documento_identidad = '1089480903', departamento_id = 14, municipio_id = 678, updated_at = NOW() WHERE email = 'haver9054@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'dodyjamirsanguino@gmail.com', documento_identidad = '1090178912', departamento_id = 16, municipio_id = 736, updated_at = NOW() WHERE email = 'dodyjamirsanguino@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'bhsotom@gmail.com', documento_identidad = '1090375884', departamento_id = 35, municipio_id = 1255, updated_at = NOW() WHERE email = 'bhsotom@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Yinaduque08@gmail.com', documento_identidad = '1093213242', departamento_id = 12, municipio_id = 602, updated_at = NOW() WHERE email = 'Yinaduque08@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'kesa281@hotmail.com', documento_identidad = '1094242400', departamento_id = 13, municipio_id = 640, updated_at = NOW() WHERE email = 'kesa281@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'anaestefaniaaepb@gmail.com', documento_identidad = '1094285409', departamento_id = 16, municipio_id = 723, updated_at = NOW() WHERE email = 'anaestefaniaaepb@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mileidy.segurahe@gmail.com', documento_identidad = '1095806390', departamento_id = 18, municipio_id = 807, updated_at = NOW() WHERE email = 'mileidy.segurahe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'm.andres.m.castillo30987@gmail.com', documento_identidad = '1095911277', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'm.andres.m.castillo30987@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'sharilynvilla@gmail.com', documento_identidad = '1095937587', departamento_id = 18, municipio_id = 810, updated_at = NOW() WHERE email = 'sharilynvilla@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jaime.tovar09@hotmail.com', documento_identidad = '1098620762', departamento_id = 18, municipio_id = 775, updated_at = NOW() WHERE email = 'jaime.tovar09@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ximenarenas92@gmail.com', documento_identidad = '1098728455', departamento_id = 18, municipio_id = 829, updated_at = NOW() WHERE email = 'ximenarenas92@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'julianestevez9333@gmail.com', documento_identidad = '1098738065', departamento_id = 18, municipio_id = 839, updated_at = NOW() WHERE email = 'julianestevez9333@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'darlymendozatello@gmail.com', documento_identidad = '1098785847', departamento_id = 18, municipio_id = 823, updated_at = NOW() WHERE email = 'darlymendozatello@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juan2170736@correo.uis.edu.co', documento_identidad = '1098818928', departamento_id = 4, municipio_id = 223, updated_at = NOW() WHERE email = 'juan2170736@correo.uis.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'ender_ortega@hotmail.com', documento_identidad = '1100690395', departamento_id = 19, municipio_id = 868, updated_at = NOW() WHERE email = 'ender_ortega@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'xcmiguelcx10@gmail.com', documento_identidad = '1100971696', departamento_id = 18, municipio_id = 844, updated_at = NOW() WHERE email = 'xcmiguelcx10@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzdarylr@hotmail.com', documento_identidad = '1102354395', departamento_id = 18, municipio_id = 835, updated_at = NOW() WHERE email = 'luzdarylr@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisssangel25@gmail.com', documento_identidad = '1103095124', departamento_id = 19, municipio_id = 862, updated_at = NOW() WHERE email = 'luisssangel25@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yoelpd21@gmail.com', documento_identidad = '1103980694', departamento_id = 19, municipio_id = 873, updated_at = NOW() WHERE email = 'yoelpd21@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ferneyfigueroa12@gmail.com', documento_identidad = '1110178586', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'ferneyfigueroa12@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'leydijhoanamejiauribe@gmail.com', documento_identidad = '1113310293', departamento_id = 21, municipio_id = 967, updated_at = NOW() WHERE email = 'leydijhoanamejiauribe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luzangelamartinezvillamil@gmail.com', documento_identidad = '1113690678', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'luzangelamartinezvillamil@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mari_velez_15@hotmail.com', documento_identidad = '1114730792', departamento_id = 21, municipio_id = 950, updated_at = NOW() WHERE email = 'mari_velez_15@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marisolsale11@gmail.com', documento_identidad = '1114821520', departamento_id = 21, municipio_id = 947, updated_at = NOW() WHERE email = 'marisolsale11@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'luisatr2017@gmail.com', documento_identidad = '1114898492', departamento_id = 21, municipio_id = 949, updated_at = NOW() WHERE email = 'luisatr2017@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'yuliana-2102@hotmail.com', documento_identidad = '1115065401', departamento_id = 21, municipio_id = 942, updated_at = NOW() WHERE email = 'yuliana-2102@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fernandezmaikel8@gmail.com', documento_identidad = '1115722888', departamento_id = 18, municipio_id = 841, updated_at = NOW() WHERE email = 'fernandezmaikel8@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'riveritalmrr@gmail.com', documento_identidad = '1115910132', departamento_id = 24, municipio_id = 1016, updated_at = NOW() WHERE email = 'riveritalmrr@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'joroscomayan@gmail.com', documento_identidad = '1115911690', departamento_id = 24, municipio_id = 1010, updated_at = NOW() WHERE email = 'joroscomayan@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jaivercabrera30@gmail.com', documento_identidad = '1117807329', departamento_id = 23, municipio_id = 992, updated_at = NOW() WHERE email = 'jaivercabrera30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angelhomero2017@gmail.com', documento_identidad = '1118167933', departamento_id = 24, municipio_id = 1000, updated_at = NOW() WHERE email = 'angelhomero2017@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jadrianarodriguez9@gmail.com', documento_identidad = '1118288224', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'jadrianarodriguez9@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'andressolano645@gmail.com', documento_identidad = '1118306157', departamento_id = 6, municipio_id = 342, updated_at = NOW() WHERE email = 'andressolano645@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'juanchopoloe@gmail.com', documento_identidad = '1121926956', departamento_id = 26, municipio_id = 1030, updated_at = NOW() WHERE email = 'juanchopoloe@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'cpdj20695@gmail.com', documento_identidad = '1129504155', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'cpdj20695@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'aherrerat45@gmail.com', documento_identidad = '1129570063', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'aherrerat45@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'camila.palacio.m18@gmail.com', documento_identidad = '1140824841', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'camila.palacio.m18@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jesusferrersar@gmail.com', documento_identidad = '1140854680', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'jesusferrersar@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ponlaw301@gmail.com', documento_identidad = '1140864579', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'ponlaw301@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jennyfuentesgrijalba@gmail.com', documento_identidad = '1144044846', departamento_id = 6, municipio_id = 351, updated_at = NOW() WHERE email = 'jennyfuentesgrijalba@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'wilmerjuniorg@gmail.com', documento_identidad = '1152939974', departamento_id = 13, municipio_id = 645, updated_at = NOW() WHERE email = 'wilmerjuniorg@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'omar.acendra45@hotmail.com', documento_identidad = '1152943557', departamento_id = 13, municipio_id = 645, updated_at = NOW() WHERE email = 'omar.acendra45@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'borreroov7@gmail.com', documento_identidad = '1193292469', departamento_id = 21, municipio_id = 975, updated_at = NOW() WHERE email = 'borreroov7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'everthleon1018@gmail.com', documento_identidad = '1193525072', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'everthleon1018@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jmilena769@gmail.com', documento_identidad = '1193545701', departamento_id = 2, municipio_id = 126, updated_at = NOW() WHERE email = 'jmilena769@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'valentina0985@hotmail.com', documento_identidad = '1193547282', departamento_id = 21, municipio_id = 957, updated_at = NOW() WHERE email = 'valentina0985@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'madelpi57@gmail.com', documento_identidad = '30286734', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'madelpi57@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Brayanvergara17185@gmail.com', documento_identidad = '10538672798', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'Brayanvergara17185@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'Vcardona515@gmail.com', documento_identidad = '10252507', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'Vcardona515@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'jurepomor@hotmail.com', documento_identidad = '10251395', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'jurepomor@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ugonzalez@misena.edu.com', documento_identidad = '10237467', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'ugonzalez@misena.edu.com' AND tenant_id = 1;
UPDATE users SET name = 'Carlosandrescruzdelgadillo@gmail.com', documento_identidad = '75089269', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'Carlosandrescruzdelgadillo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'solocuandoelultimoarbolsea@gmail.com', documento_identidad = '80097847', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'solocuandoelultimoarbolsea@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'marthatobon7@gmail.com', documento_identidad = '25379681', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'marthatobon7@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'olgatamayo762@gmail.com', documento_identidad = '30300011', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'olgatamayo762@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'mencharuiz89@gmail.com', documento_identidad = '30312398', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'mencharuiz89@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'soto.segra2016@gmail.com', documento_identidad = '1054858152', departamento_id = 5, municipio_id = 314, updated_at = NOW() WHERE email = 'soto.segra2016@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'martac.caba@gmail.com', documento_identidad = '36550593', departamento_id = 35, municipio_id = 1153, updated_at = NOW() WHERE email = 'martac.caba@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'fanylealquiros@gmail.com', documento_identidad = '63347250', updated_at = NOW() WHERE email = 'fanylealquiros@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'carlosfbaron@gmail.com', documento_identidad = '13437734', updated_at = NOW() WHERE email = 'carlosfbaron@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ligiamonbe@hotmail.com', documento_identidad = '41578171', departamento_id = 1, municipio_id = 13, updated_at = NOW() WHERE email = 'ligiamonbe@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'acamargo@corefex.net', documento_identidad = '19462416', updated_at = NOW() WHERE email = 'acamargo@corefex.net' AND tenant_id = 1;
UPDATE users SET name = 'juliethsanchezs.10@hotmail.com', documento_identidad = '1077862938', updated_at = NOW() WHERE email = 'juliethsanchezs.10@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'delfincarv@hotmail.com', documento_identidad = '12189313', updated_at = NOW() WHERE email = 'delfincarv@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'ancabeju@hotmail.com', documento_identidad = '41943618', updated_at = NOW() WHERE email = 'ancabeju@hotmail.com' AND tenant_id = 1;
UPDATE users SET name = 'evelioperezgalvis@gmail.com', documento_identidad = '7520713', updated_at = NOW() WHERE email = 'evelioperezgalvis@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'japalacios@uqvirtual.edu.co', documento_identidad = '1099683329', updated_at = NOW() WHERE email = 'japalacios@uqvirtual.edu.co' AND tenant_id = 1;
UPDATE users SET name = 'miguelangelgrisales143@gmail.com', documento_identidad = '1094949626', updated_at = NOW() WHERE email = 'miguelangelgrisales143@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'locayo1334@gmail.com', documento_identidad = '1104697385', departamento_id = 12, updated_at = NOW() WHERE email = 'locayo1334@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'deisonzl53@gmail.com', documento_identidad = '12021810', updated_at = NOW() WHERE email = 'deisonzl53@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'angela123acevedo@gmail.com', documento_identidad = '1030573131', updated_at = NOW() WHERE email = 'angela123acevedo@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'deisonzll30@gmail.com', documento_identidad = '12021810', updated_at = NOW() WHERE email = 'deisonzll30@gmail.com' AND tenant_id = 1;
UPDATE users SET name = 'osbaylopez20@gmail.com', documento_identidad = '71411247', updated_at = NOW() WHERE email = 'osbaylopez20@gmail.com' AND tenant_id = 1;

-- Mostrar estadísticas
SELECT 'ESTADÍSTICAS FINALES:' as info;
SELECT 
    1789 as registros_csv,
    1789 as registros_validos,
    1750 as con_departamento,
    1734 as con_municipio,
    (SELECT COUNT(*) FROM users WHERE tenant_id = 1) as usuarios_total_tenant;

DROP FUNCTION normalize_name;
SELECT '✅ Importación completada exitosamente' as resultado;
