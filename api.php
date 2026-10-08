<?php
/**
 * API Backend RESTful en PHP para Gestión de Asistencias
 * Comunica el formulario de confirmación y el panel de asistencia con phpMyAdmin
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/db.php';

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$method = $_SERVER['REQUEST_METHOD'];

// Helper para leer cuerpo JSON en peticiones POST
function getJsonInput() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {
    $pdo = getDbConnection();
    $tableName = DB_TABLE;

    switch ($action) {

        // ===================================================================
        // 1. LISTAR TODOS LOS REGISTROS
        // ===================================================================
        case 'list':
            $stmt = $pdo->query("SELECT id, code, name, phone, count, diet, registered_at, attended, attended_at FROM `{$tableName}` ORDER BY id DESC");
            $rows = $stmt->fetchAll();

            $guests = [];
            foreach ($rows as $row) {
                $guests[] = [
                    'id'           => (string)$row['id'],
                    'code'         => $row['code'],
                    'name'         => $row['name'],
                    'phone'        => $row['phone'],
                    'count'        => (int)$row['count'],
                    'diet'         => $row['diet'],
                    'registeredAt' => $row['registered_at'],
                    'attended'     => (bool)$row['attended'],
                    'attendedAt'   => $row['attended_at']
                ];
            }

            echo json_encode([
                'success' => true,
                'data'    => $guests
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ===================================================================
        // 2. REGISTRAR ASISTENCIA DESDE confirmar.html
        // ===================================================================
        case 'register':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }

            $input = getJsonInput();
            $name  = isset($input['name'])  ? trim($input['name'])  : '';
            $phone = isset($input['phone']) ? trim($input['phone']) : '';
            $count = isset($input['count']) ? (int)$input['count']  : 1;
            $diet  = isset($input['diet'])  ? trim($input['diet'])  : 'Ninguna';
            $code  = isset($input['code'])  ? trim($input['code'])  : '';

            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'El nombre es requerido']);
                exit;
            }

            if ($count < 1) $count = 1;

            // Si no se proporcionó código, generar uno único
            if (empty($code)) {
                $code = 'Event-' . mt_rand(1000, 9999);
            }

            // Asegurar que el código sea único en la base de datos
            $checkStmt = $pdo->prepare("SELECT id FROM `{$tableName}` WHERE `code` = ?");
            $checkStmt->execute([$code]);
            while ($checkStmt->fetch()) {
                $code = 'Event-' . mt_rand(1000, 9999);
                $checkStmt->execute([$code]);
            }

            $sql = "INSERT INTO `{$tableName}` (`code`, `name`, `phone`, `count`, `diet`, `registered_at`, `attended`, `attended_at`) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 0, NULL)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $name, $phone, $count, $diet]);

            $newId = $pdo->lastInsertId();

            // Consultar registro recién creado
            $getStmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE `id` = ?");
            $getStmt->execute([$newId]);
            $created = $getStmt->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Registro guardado exitosamente en phpMyAdmin',
                'guest'   => [
                    'id'           => (string)$created['id'],
                    'code'         => $created['code'],
                    'name'         => $created['name'],
                    'phone'        => $created['phone'],
                    'count'        => (int)$created['count'],
                    'diet'         => $created['diet'],
                    'registeredAt' => $created['registered_at'],
                    'attended'     => (bool)$created['attended'],
                    'attendedAt'   => $created['attended_at']
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ===================================================================
        // 3. VERIFICAR CÓDIGO DE PASE
        // ===================================================================
        case 'verify':
            $code = isset($_GET['code']) ? trim($_GET['code']) : '';
            if (empty($code)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Código no proporcionado']);
                exit;
            }

            // Búsqueda flexible (admite "Event-1234", "event-1234" o solo "1234")
            $cleanCode = str_ireplace('event-', '', $code);
            $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE LOWER(code) = LOWER(?) OR LOWER(REPLACE(code, 'event-', '')) = LOWER(?) LIMIT 1");
            $stmt->execute([$code, $cleanCode]);
            $row = $stmt->fetch();

            if (!$row) {
                echo json_encode([
                    'success' => true,
                    'found'   => false,
                    'message' => 'Código no encontrado'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => true,
                    'found'   => true,
                    'guest'   => [
                        'id'           => (string)$row['id'],
                        'code'         => $row['code'],
                        'name'         => $row['name'],
                        'phone'        => $row['phone'],
                        'count'        => (int)$row['count'],
                        'diet'         => $row['diet'],
                        'registeredAt' => $row['registered_at'],
                        'attended'     => (bool)$row['attended'],
                        'attendedAt'   => $row['attended_at']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            }
            break;

        // ===================================================================
        // 4. CONFIRMAR ASISTENCIA EL DÍA DEL EVENTO (TOGGLE CHECK-IN)
        // ===================================================================
        case 'toggle_attendance':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }

            $input = getJsonInput();
            $id    = isset($input['id'])   ? trim($input['id'])   : '';
            $code  = isset($input['code']) ? trim($input['code']) : '';

            if (empty($id) && empty($code)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Identificador no proporcionado']);
                exit;
            }

            // Buscar invitado
            if (!empty($id)) {
                $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE `id` = ?");
                $stmt->execute([$id]);
            } else {
                $cleanCode = str_ireplace('event-', '', $code);
                $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE LOWER(code) = LOWER(?) OR LOWER(REPLACE(code, 'event-', '')) = LOWER(?) LIMIT 1");
                $stmt->execute([$code, $cleanCode]);
            }
            $row = $stmt->fetch();

            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Invitado no encontrado']);
                exit;
            }

            $newAttended = $row['attended'] ? 0 : 1;
            $nowSql = $newAttended ? 'NOW()' : 'NULL';

            $updateStmt = $pdo->prepare("UPDATE `{$tableName}` SET `attended` = ?, `attended_at` = " . ($newAttended ? "NOW()" : "NULL") . " WHERE `id` = ?");
            $updateStmt->execute([$newAttended, $row['id']]);

            // Obtener estado actualizado
            $getUpdated = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE `id` = ?");
            $getUpdated->execute([$row['id']]);
            $updatedRow = $getUpdated->fetch();

            echo json_encode([
                'success'    => true,
                'attended'   => (bool)$updatedRow['attended'],
                'attendedAt' => $updatedRow['attended_at'],
                'guest'      => [
                    'id'           => (string)$updatedRow['id'],
                    'code'         => $updatedRow['code'],
                    'name'         => $updatedRow['name'],
                    'phone'        => $updatedRow['phone'],
                    'count'        => (int)$updatedRow['count'],
                    'diet'         => $updatedRow['diet'],
                    'registeredAt' => $updatedRow['registered_at'],
                    'attended'     => (bool)$updatedRow['attended'],
                    'attendedAt'   => $updatedRow['attended_at']
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ===================================================================
        // 5. REGISTRO MANUAL EN PUERTA
        // ===================================================================
        case 'manual_register':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }

            $input = getJsonInput();
            $name  = isset($input['name'])  ? trim($input['name'])  : '';
            $phone = isset($input['phone']) ? trim($input['phone']) : '';
            $count = isset($input['count']) ? (int)$input['count']  : 1;
            $diet  = isset($input['diet'])  ? trim($input['diet'])  : 'Ninguna';
            $instantCheckin = !empty($input['instantCheckin']);

            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'El nombre es obligatorio']);
                exit;
            }

            if ($count < 1) $count = 1;

            $code = 'Event-' . mt_rand(1000, 9999);
            $checkStmt = $pdo->prepare("SELECT id FROM `{$tableName}` WHERE `code` = ?");
            $checkStmt->execute([$code]);
            while ($checkStmt->fetch()) {
                $code = 'Event-' . mt_rand(1000, 9999);
                $checkStmt->execute([$code]);
            }

            $sql = "INSERT INTO `{$tableName}` (`code`, `name`, `phone`, `count`, `diet`, `registered_at`, `attended`, `attended_at`) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?, " . ($instantCheckin ? "NOW()" : "NULL") . ")";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $name, $phone, $count, $diet, $instantCheckin ? 1 : 0]);

            $newId = $pdo->lastInsertId();
            $getStmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE `id` = ?");
            $getStmt->execute([$newId]);
            $created = $getStmt->fetch();

            echo json_encode([
                'success' => true,
                'guest'   => [
                    'id'           => (string)$created['id'],
                    'code'         => $created['code'],
                    'name'         => $created['name'],
                    'phone'        => $created['phone'],
                    'count'        => (int)$created['count'],
                    'diet'         => $created['diet'],
                    'registeredAt' => $created['registered_at'],
                    'attended'     => (bool)$created['attended'],
                    'attendedAt'   => $created['attended_at']
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ===================================================================
        // 6. ELIMINAR REGISTRO
        // ===================================================================
        case 'delete':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }

            $input = getJsonInput();
            $id    = isset($input['id']) ? trim($input['id']) : '';

            if (empty($id)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM `{$tableName}` WHERE `id` = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'success' => true,
                'message' => 'Registro eliminado de la base de datos'
            ], JSON_UNESCAPED_UNICODE);
            break;

        // ===================================================================
        // 7. PRUEBA DE CONEXIÓN
        // ===================================================================
        case 'test':
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `{$tableName}`");
            $count = $countStmt->fetch()['total'];

            echo json_encode([
                'success'   => true,
                'message'   => '¡Conexión exitosa a MySQL en phpMyAdmin!',
                'database'  => DB_NAME,
                'table'     => $tableName,
                'registros' => (int)$count
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => 'Acción no válida o no especificada',
                'hint'    => 'Usa action=list, action=register, action=verify, action=toggle_attendance, action=manual_register, action=delete, o action=test'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
