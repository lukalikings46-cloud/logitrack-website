<?php
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/roles.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

function nextVehicleId(PDO $pdo): string
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
    return 'VH' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
}

switch ($method) {

    case 'GET':
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $row = $stmt->fetch();
            if (!$row) json_error('Vehicle not found.', 404);
            json_out(['success' => true, 'vehicle' => $row]);
        }
        $rows = $pdo->query('SELECT * FROM vehicles ORDER BY id')->fetchAll();
        json_out(['success' => true, 'vehicles' => $rows]);
        break;

    case 'POST':
        $userId = require_login();
        require_role($pdo, $userId, ['admin', 'manager']); // staff cannot manage the fleet
        $data = body_json();
        foreach (['type', 'status', 'location', 'fuel', 'nextMaintenance'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') json_error("Missing field: $field");
        }

        $id = nextVehicleId($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO vehicles (id, type, status, driver, location, fuel, next_maintenance) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $id,
            $data['type'],
            $data['status'],
            $data['driver'] ?: 'N/A',
            $data['location'],
            $data['fuel'],
            $data['nextMaintenance'],
        ]);

        json_out(['success' => true, 'id' => $id], 201);
        break;

    case 'PUT':
        $userId = require_login();
        require_role($pdo, $userId, ['admin', 'manager']);
        $data = body_json();
        $id = $data['id'] ?? null;
        if (!$id) json_error('Missing vehicle id.');

        $stmt = $pdo->prepare('SELECT * FROM vehicles WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) json_error('Vehicle not found.', 404);

        $update = $pdo->prepare(
            'UPDATE vehicles SET type=?, status=?, driver=?, location=?, fuel=?, next_maintenance=? WHERE id=?'
        );
        $update->execute([
            $data['type'] ?? $existing['type'],
            $data['status'] ?? $existing['status'],
            $data['driver'] ?? $existing['driver'],
            $data['location'] ?? $existing['location'],
            $data['fuel'] ?? $existing['fuel'],
            $data['nextMaintenance'] ?? $existing['next_maintenance'],
            $id,
        ]);

        json_out(['success' => true]);
        break;

    case 'DELETE':
        $userId = require_login();
        require_role($pdo, $userId, ['admin', 'manager']);
        parse_str(file_get_contents('php://input'), $params);
        $id = $_GET['id'] ?? ($params['id'] ?? null);
        if (!$id) json_error('Missing vehicle id.');

        $pdo->prepare('DELETE FROM vehicles WHERE id = ?')->execute([$id]);
        json_out(['success' => true]);
        break;

    default:
        json_error('Method not allowed.', 405);
}
