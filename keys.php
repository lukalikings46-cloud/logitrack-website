<?php
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/roles.php';
require_once __DIR__ . '/../includes/rate_limit.php';

$pdo = getDB();
$userId = require_login();
require_admin($pdo, $userId); // only admins can manage integration API keys

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->prepare('SELECT id, label, api_key, rate_limit_per_min, active, created_at, last_used_at FROM api_keys WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        json_out(['success' => true, 'keys' => $stmt->fetchAll()]);
        break;

    case 'POST':
        $data = body_json();
        $label = trim($data['label'] ?? 'Integration key');
        $rateLimit = (int) ($data['rateLimit'] ?? 60);
        $key = generate_api_key();

        $pdo->prepare('INSERT INTO api_keys (user_id, api_key, label, rate_limit_per_min) VALUES (?, ?, ?, ?)')
            ->execute([$userId, $key, $label, $rateLimit]);

        json_out(['success' => true, 'apiKey' => $key, 'id' => (int) $pdo->lastInsertId()], 201);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $params);
        $id = $_GET['id'] ?? ($params['id'] ?? null);
        if (!$id) json_error('Missing key id.');

        $pdo->prepare('DELETE FROM api_keys WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
        json_out(['success' => true]);
        break;

    default:
        json_error('Method not allowed.', 405);
}
