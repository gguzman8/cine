-- ===============================================================
-- Cine Sendera — Esquema de Base de Datos
-- ===============================================================
-- Este archivo contiene TODA la estructura: tablas, relaciones,
-- procedimiento almacenado y datos de prueba.
--
-- Importar:
--   mysql -u root -p < sql/schema.sql
-- ===============================================================

CREATE DATABASE IF NOT EXISTS cine
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE cine;

-- ─── 1. TABLA: usuarios ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT             AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(100)    NOT NULL,
    email         VARCHAR(255)    NOT NULL UNIQUE,
    password_hash VARCHAR(255)    NOT NULL,
    rol           ENUM('cliente','admin','vendedor') NOT NULL DEFAULT 'cliente',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── 2. TABLA: peliculas ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS peliculas (
    id         INT             AUTO_INCREMENT PRIMARY KEY,
    titulo     VARCHAR(200)    NOT NULL,
    sinopsis   TEXT,
    poster     VARCHAR(255)    NOT NULL DEFAULT 'default.svg',
    precio     DECIMAL(10,2)   NOT NULL,
    activa     BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── 3. TABLA: funciones ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS funciones (
    id          INT             AUTO_INCREMENT PRIMARY KEY,
    pelicula_id INT             NOT NULL,
    horario     DATETIME        NOT NULL,
    sala        VARCHAR(50)     NOT NULL,
    es_matinee  BOOLEAN         NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelicula_id) REFERENCES peliculas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── 4. TABLA: asientos ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS asientos (
    id          INT         AUTO_INCREMENT PRIMARY KEY,
    funcion_id  INT         NOT NULL,
    fila        CHAR(1)     NOT NULL,
    numero      INT         NOT NULL,
    disponible  BOOLEAN     NOT NULL DEFAULT TRUE,
    FOREIGN KEY (funcion_id) REFERENCES funciones(id) ON DELETE CASCADE,
    UNIQUE KEY uq_asiento (funcion_id, fila, numero)
) ENGINE=InnoDB;

-- ─── 5. TABLA: compras ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS compras (
    id          INT             AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT             NOT NULL,
    funcion_id  INT             NOT NULL,
    cupon_id    INT             DEFAULT NULL,
    total       DECIMAL(10,2)   NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (funcion_id) REFERENCES funciones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── 6. TABLA: detalle_compra ────────────────────────────────
CREATE TABLE IF NOT EXISTS detalle_compra (
    id          INT             AUTO_INCREMENT PRIMARY KEY,
    compra_id   INT             NOT NULL,
    asiento_id  INT             NOT NULL,
    precio      DECIMAL(10,2)   NOT NULL,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE,
    FOREIGN KEY (asiento_id) REFERENCES asientos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─── 7. TABLA: cupones (PLUS) ────────────────────────────────
CREATE TABLE IF NOT EXISTS cupones (
    id                  INT             AUTO_INCREMENT PRIMARY KEY,
    codigo              VARCHAR(20)     NOT NULL UNIQUE,
    descripcion         VARCHAR(255)    DEFAULT NULL,
    descuento_porcentaje INT            NOT NULL DEFAULT 10,
    usos_maximos        INT             NOT NULL DEFAULT 1,
    usos_actuales       INT             NOT NULL DEFAULT 0,
    activo              BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─── 8. STORED PROCEDURE: generar_asientos ───────────────────
-- Genera 40 asientos (5 filas x 8 columnas) para una función.
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS generar_asientos(IN p_funcion_id INT)
BEGIN
    DECLARE v_fila INT DEFAULT 0;
    DECLARE v_num INT DEFAULT 0;
    DECLARE letras CHAR(5) DEFAULT 'ABCDE';

    -- Limpiar asientos existentes para esta función
    DELETE FROM asientos WHERE funcion_id = p_funcion_id;

    SET v_fila = 0;
    WHILE v_fila < 5 DO
        SET v_num = 1;
        WHILE v_num <= 8 DO
            INSERT INTO asientos (funcion_id, fila, numero, disponible)
            VALUES (p_funcion_id, SUBSTRING(letras, v_fila + 1, 1), v_num, TRUE);
            SET v_num = v_num + 1;
        END WHILE;
        SET v_fila = v_fila + 1;
    END WHILE;
END//
DELIMITER ;

-- ===============================================================
-- DATOS DE PRUEBA
-- ===============================================================

-- Los usuarios se crean via src/seed.php (para hash bcrypt correcto)

-- ─── Películas ───────────────────────────────────────────────
INSERT INTO peliculas (titulo, sinopsis, poster, precio) VALUES
('Dune: Parte Dos',
 'Paul Atreides se une a los Fremen para buscar venganza contra quienes destruyeron su familia. Una épica de ciencia ficción.',
 'dune.svg', 95.00),

('Kung Fu Panda 4',
 'Po busca a su sucesor como Guerrero Dragón mientras enfrenta a una nueva villana camaleónica.',
 'kungfu.svg', 75.00),

('Intensamente 2',
 'Riley entra en la adolescencia y nuevas emociones aparecen en el cuartel central. Una aventura divertida y conmovedora.',
 'insideout.svg', 80.00);

-- ─── Funciones (con matiné en horarios antes de 12:00 entre semana) ──
INSERT INTO funciones (pelicula_id, horario, sala, es_matinee) VALUES
(1, '2026-05-26 10:30:00', 'Sala 1', TRUE),
(1, '2026-05-26 16:00:00', 'Sala 1', FALSE),
(1, '2026-05-26 20:00:00', 'Sala IMAX', FALSE),
(2, '2026-05-26 11:00:00', 'Sala 2', TRUE),
(2, '2026-05-26 15:30:00', 'Sala 2', FALSE),
(2, '2026-05-26 18:00:00', 'Sala 2', FALSE),
(3, '2026-05-26 10:00:00', 'Sala 3', TRUE),
(3, '2026-05-26 14:00:00', 'Sala 3', FALSE),
(3, '2026-05-26 19:30:00', 'Sala 3', FALSE);

-- ─── Generar asientos para todas las funciones ───────────────
CALL generar_asientos(1);
CALL generar_asientos(2);
CALL generar_asientos(3);
CALL generar_asientos(4);
CALL generar_asientos(5);
CALL generar_asientos(6);
CALL generar_asientos(7);
CALL generar_asientos(8);
CALL generar_asientos(9);

-- ─── Cupones de descuento (PLUS) ─────────────────────────────
INSERT INTO cupones (codigo, descripcion, descuento_porcentaje, usos_maximos) VALUES
('CINE10', '10% de descuento en tu primera compra', 10, 50),
('BIENVENIDO', 'Descuento especial para nuevos usuarios', 15, 30),
('MATINE20', '20% extra en funciones matiné', 20, 20);
