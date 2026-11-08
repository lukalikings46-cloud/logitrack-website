<?php
/**
 * Admin-only user management: list every account and change roles,
 * or remove an account entirely. This is what "Administrator —
 * manage user accounts" on the landing page actually does.
 */
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/roles.php';

$pdo = getDB();
$userId = require_login();
require_admin($pdo, $userId); // only admins can view/manage other accounts

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->query(
            'SELECT id, full_name, email, phone, position, department, role, created_at FROM users ORDER BY created_at ASC'
        );
        json_out(['success' => true, 'users' => $stmt->fetchAll()]);
        break;

    case 'PUT':
        $data = body_json();
        $id = (int) ($data['id'] ?? 0);
        $role = $data['role'] ?? null;
        if (!$id) json_error('Missing user id.');
        if (!in_array($role, ['admin', 'manager', 'staff'], true)) {
            json_error('Role must be admin, manager, or staff.');
        }

        if ($id === $userId) {
            json_error("You can't change your own role.");
        }

        $target = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
        $target->execute([$id]);
        $targetRow = $target->fetch();
        if (!$targetRow) json_error('User not found.', 404);

        // Never leave the system with zero admins.
        if ($targetRow['role'] === 'admin' && $role !== 'admin') {
            $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($adminCount <= 1) {
                json_error('At least one admin account must remain.');
            }
        }

        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        json_out(['success' => true]);
        break;

    case 'DELETE':
        parse_str(file_get_contents('php://input'), $params);
        $id = (int) ($_GET['id'] ?? ($params['id'] ?? 0));
        if (!$id) json_error('Missing user id.');

        if ($id === $userId) {
            json_error("You can't delete your own account.");
        }

        $target = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
        $target->execute([$id]);
        $targetRow = $target->fetch();
        if (!$targetRow) json_error('User not found.', 404);

        if ($targetRow['role'] === 'admin') {
            $adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            if ($adminCount <= 1) {
                json_error('At least one admin account must remain.');
            }
        }

        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        json_out(['success' => true]);
        break;

    default:
        json_error('Method not allowed.', 405);
}
