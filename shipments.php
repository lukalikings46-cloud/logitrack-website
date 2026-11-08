<?php
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/mailer.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

function nextShipmentId(PDO $pdo): string
{
    // Next number after the highest existing TRK number (safe even after deletions)
    $max = (int) $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(id, 4) AS UNSIGNED)), 0) FROM shipments")->fetchColumn();
    return 'TRK' . str_pad((string) ($max + 1), 3, '0', STR_PAD_LEFT);
}

switch ($method) {

    case 'GET':
        if (!empty($_GET['id'])) {
            $stmt = $pdo->prepare('SELECT * FROM shipments WHERE id = ?');
            $stmt->execute([$_GET['id']]);
            $row = $stmt->fetch();
            if (!$row) json_error('Shipment not found.', 404);
            json_out(['success' => true, 'shipment' => $row]);
        }
        $rows = $pdo->query('SELECT * FROM shipments ORDER BY CAST(SUBSTRING(id, 4) AS UNSIGNED) ASC, id ASC')->fetchAll();
        json_out(['success' => true, 'shipments' => $rows]);
        break;

    case 'POST':
        require_login();
        $data = body_json();
        foreach (['origin', 'destination', 'weight', 'priority', 'estimatedDelivery'] as $field) {
            if (empty($data[$field])) json_error("Missing field: $field");
        }

        $id = nextShipmentId($pdo);
        $stmt = $pdo->prepare(
            'INSERT INTO shipments (id, origin, destination, weight, priority, status, progress, estimated_delivery, instructions, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())'
        );
        $stmt->execute([
            $id,
            $data['origin'],
            $data['destination'],
            $data['weight'],
            $data['priority'],
            $data['status'] ?? 'Pending',
            $data['progress'] ?? 0,
            $data['estimatedDelivery'],
            $data['instructions'] ?? null,
            $_SESSION['user_id'],
        ]);

        $pdo->prepare(
            'INSERT INTO tracking_updates (shipment_id, title, location, event_status, event_time) VALUES (?, ?, ?, ?, NOW())'
        )->execute([$id, 'Shipment Created', $data['origin'], 'completed']);

        json_out(['success' => true, 'id' => $id], 201);
        break;

    case 'PUT':
        $userId = require_login();
        require_role($pdo, $userId, ['admin', 'manager']); // staff can view & create, not edit
        $data = body_json();
        $id = $data['id'] ?? null;
        if (!$id) json_error('Missing shipment id.');

        $stmt = $pdo->prepare('SELECT * FROM shipments WHERE id = ?');
        $stmt->execute([$id]);
        $existing = $stmt->fetch();
        if (!$existing) json_error('Shipment not found.', 404);

        // Progress always follows status: Pending=0%, Delayed=50%, Delivered=100%.
        // In Transit is the only status allowed to carry a custom/climbing value
        // (the dashboard advances it by itself every 30 seconds).
        $newStatus = $data['status'] ?? $existing['status'];
        $progress = $data['progress'] ?? $existing['progress'];
        switch ($newStatus) {
            case 'Pending':   $progress = 0;   break;
            case 'Delayed':   $progress = 50;  break;
            case 'Delivered': $progress = 100; break;
        }

        $update = $pdo->prepare(
            'UPDATE shipments SET origin=?, destination=?, weight=?, priority=?, status=?, progress=?, estimated_delivery=? WHERE id=?'
        );
        $update->execute([
            $data['origin'] ?? $existing['origin'],
            $data['destination'] ?? $existing['destination'],
            $data['weight'] ?? $existing['weight'],
            $data['priority'] ?? $existing['priority'],
            $data['status'] ?? $existing['status'],
            $progress,
            $data['estimatedDelivery'] ?? $existing['estimated_delivery'],
            $id,
        ]);

        $statusChanged = ($data['status'] ?? null) && $data['status'] !== $existing['status'];

        if ($statusChanged) {
            $pdo->prepare(
                'INSERT INTO tracking_updates (shipment_id, title, location, event_status, event_time) VALUES (?, ?, ?, ?, NOW())'
            )->execute([$id, $data['status'], $data['destination'] ?? $existing['destination'], 'current']);
        }

        // Email + in-app alert to whoever created the shipment when it becomes Delayed.
        if ($statusChanged && $data['status'] === 'Delayed' && $existing['created_by']) {
            $owner = $pdo->prepare('SELECT full_name, email FROM users WHERE id = ?');
            $owner->execute([$existing['created_by']]);
            $ownerRow = $owner->fetch();
            if ($ownerRow) {
                notify_user(
                    $pdo,
                    $existing['created_by'],
                    "Shipment {$id} has been marked as Delayed.",
                    'warning',
                    $ownerRow['email'],
                    "Shipment {$id} delayed",
                    "Hi {$ownerRow['full_name']},\n\nShipment {$id} ({$existing['origin']} → {$existing['destination']}) "
                        . "has been marked as Delayed. Please review it in LogiTrack Pro.\n\n— LogiTrack Pro"
                );
            }
        }

        json_out(['success' => true]);
        break;

    case 'DELETE':
        $userId = require_login();
        require_role($pdo, $userId, ['admin', 'manager']); // staff cannot delete shipments
        parse_str(file_get_contents('php://input'), $params);
        $id = $_GET['id'] ?? ($params['id'] ?? null);
        if (!$id) json_error('Missing shipment id.');

        $pdo->prepare('DELETE FROM shipments WHERE id = ?')->execute([$id]);
        json_out(['success' => true]);
        break;

    default:
        json_error('Method not allowed.', 405);
}
