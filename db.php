<?php
/**
 * Conexión y Gestión de Base de Datos MySQL con PDO
 * Compatible con cualquier hosting (cPanel, Hostinger, Plesk, etc.)
 */

require_once __DIR__ . '/config.php';

function getDbConnection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        // ===================================================================
        // AUTO-CREACIÓN DE LA TABLA EN PHPMYADMIN SI AÚN NO EXISTE
        // De esta forma, el usuario no tiene que escribir SQL manualmente
        // ===================================================================
        $tableName = DB_TABLE;
        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `code` VARCHAR(50) NOT NULL UNIQUE,
            `name` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(50) DEFAULT '',
            `count` INT NOT NULL DEFAULT 1,
            `diet` VARCHAR(255) DEFAULT 'Ninguna',
            `registered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `attended` TINYINT(1) NOT NULL DEFAULT 0,
            `attended_at` DATETIME DEFAULT NULL,
            INDEX (`code`),
            INDEX (`attended`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . DB_CHARSET . " COLLATE=" . DB_CHARSET . "_unicode_ci;";

        $pdo->exec($sql);

        return $pdo;

    } catch (PDOException $e) {
        // Enviar respuesta clara si hay error de conexión a la base de datos
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Error de conexión a la base de datos MySQL en phpMyAdmin',
            'detail'  => $e->getMessage(),
            'hint'    => 'Verifica que el nombre de la base de datos, usuario y contraseña en config.php sean correctos.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
