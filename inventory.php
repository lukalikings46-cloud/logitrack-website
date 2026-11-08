<?php
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/roles.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

function nextSku(PDO $pdo): string
{
    $max = 0;
    foreach ($pdo->query('SELECT sku FROM inventory') as $row) {
        $num = (int) preg_replace('/\D/', '', $row['sku']);
        if ($num > $max) $max = $num;
    }
    return 'ITM-' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
}

switch ($method) {

    case 'GET':
        if (!empty($_GET['sku'])) {
            $stmt = $pdo->prepare('SELECT * FROM inventory WHERE sku = ?');
            $stmt->execute([$_GET['sku']]);
            $row = $stmt->fetch();
            if (!$row) json_error('Item not found.', 404);
            json_out(['success' => true, 'item' => $row]);
        }
        $rows = $pdo->query('SELECT * FROM inventory ORDER BY sku')->fetchAll();
        json_out(['success' => true, 'inventory' => $rows]);
        break;

    case 'POST':
        $userId = require_login();
        require_role($pdo, $userId, ['admin', 'manager']); // staff cannot manage the warehouse
        $data = body_json();
        foreach (['description', 'quantity', 'location', 'status'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') json_error("Missing field: $field");
        }

        $sku = nextSku($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO inventory (sku, description, quantity, location, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$sku, $data['description'], $data['quantity'], $data['location'], $data['status']]);

        json_out(['success' => true, 'sku' => $sku], 201);
        break;

    case 'PUT':
        $userId = require_login();
        require_role($pdo, $userId, ['admin', 'manager']);
        $data = body_json();
        $sku = $data['sku'] ?? null;
        if (!$sku) json_error('Missing item sku.');

        $stmt = $pdo->prepare('SELECT * FROM inventory WHERE sku = ?');
        $stmt->execute([$sku]);
        $existing = $stmt->fetch();
        if (!$existing) json_error('Item not found.', 404);

        $update = $pdo->prepare('UPDATE inventory SET description=?, quantity=?, location=?, status=? WHERE sku=?');
        $update->execute([
            $data['description'] ?? $existing['description'],
            $data['quantity'] ?? $existing['quantity'],
            $data['location'] ?? $existing['location'],
            $data['status'] ?? $existing['status'],
            $sku,
        ]);

        json_out(['success' => true]);
        break;

    case 'DELETE':
        $userId = require_login();
        require_role($pdo, $userId, ['admin', 'manager']);
        parse_str(file_get_contents('php://input'), $params);
        $sku = $_GET['sku'] ?? ($params['sku'] ?? null);
        if (!$sku) json_error('Missing item sku.');

        $pdo->prepare('DELETE FROM inventory WHERE sku = ?')->execute([$sku]);
        json_out(['success' => true]);
        break;

    default:
        json_error('Method not allowed.', 405);
}
