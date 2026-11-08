<?php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDB();
$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->prepare('SELECT id, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $unread = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $unread->execute([$userId]);

        json_out(['success' => true, 'notifications' => $rows, 'unread' => (int) $unread->fetchColumn()]);
        break;

    case 'POST':
        // Mark one or all notifications as read.
        $data = body_json();
        if (!empty($data['markAll'])) {
            $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
        } elseif (!empty($data['id'])) {
            $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$data['id'], $userId]);
        } else {
            json_error('Provide "id" or "markAll".');
        }
        json_out(['success' => true]);
        break;

    default:
        json_error('Method not allowed.', 405);
}
