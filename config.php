<?php
/**
 * ===================================================================
 * ARCHIVO DE CONFIGURACIÓN DE BASE DE DATOS (MySQL / phpMyAdmin)
 * ===================================================================
 * 
 * INSTRUCCIONES RÁPIDAS PARA SUBIR A TU HOSTING (cPanel / Hostinger / etc.):
 * 1. Ve a tu panel de hosting (cPanel o Hostinger) y crea una Base de Datos MySQL.
 * 2. Asigna un usuario con todos los privilegios a esa base de datos.
 * 3. Coloca el nombre de la base de datos, el usuario y la contraseña abajo:
 */

define('DB_HOST', 'localhost');          // En el 99% de los hostings es 'localhost'
define('DB_NAME', 'semptmhiip_cumple_2910');         // Reemplaza con el nombre de tu base de datos en phpMyAdmin
define('DB_USER', 'semptmhiip_admin');               // Reemplaza con tu usuario de MySQL (ej. u123456_admin)
define('DB_PASS', 'wally26866132$$');                   // Reemplaza con tu contraseña de MySQL
define('DB_CHARSET', 'utf8mb4');         // Soporte completo para caracteres, tildes y emojis
define('DB_TABLE', 'invitados_2910');    // Nombre de la tabla (se creará automáticamente)

// Configuración de zona horaria (opcional, ajusta si es necesario)
date_default_timezone_set('America/Caracas');
