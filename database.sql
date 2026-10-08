-- ===================================================================
-- Estructura de tabla para phpMyAdmin / MySQL (Opcional)
-- NOTA: El sistema la crea automáticamente al conectar, pero si
-- prefieres importarla manualmente desde phpMyAdmin, puedes usar este archivo.
-- ===================================================================

CREATE TABLE IF NOT EXISTS `invitados_2910` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único del pase (Ej: Event-4821)',
  `name` VARCHAR(255) NOT NULL COMMENT 'Nombre completo del invitado',
  `phone` VARCHAR(50) DEFAULT '' COMMENT 'Número de teléfono o WhatsApp',
  `count` INT NOT NULL DEFAULT 1 COMMENT 'Cantidad de pases confirmados',
  `diet` VARCHAR(255) DEFAULT 'Ninguna' COMMENT 'Restricción alimentaria o alergia',
  `registered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de registro',
  `attended` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = Pendiente, 1 = Ingresó al evento',
  `attended_at` DATETIME DEFAULT NULL COMMENT 'Fecha y hora en que ingresó en puerta',
  INDEX (`code`),
  INDEX (`attended`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
