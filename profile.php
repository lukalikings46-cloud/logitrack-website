<?php
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/security.php';

$pdo = getDB();
$userId = require_login();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $stmt = $pdo->prepare(
            'SELECT u.id, u.full_name, u.email, u.phone, u.position, u.department, u.avatar, u.role,
                    s.dark_mode, s.theme_color, s.font_size, s.card_style, s.animation_speed,
                    s.dashboard_view, s.shipment_updates
             FROM users u LEFT JOIN user_settings s ON s.user_id = u.id
             WHERE u.id = ?'
        );
        $stmt->execute([$userId]);
        json_out(['success' => true, 'profile' => $stmt->fetch()]);
        break;

    case 'POST':
    case 'PUT':
        $data = body_json();

        if (isset($data['full_name']) || isset($data['email']) || isset($data['phone'])
            || isset($data['position']) || isset($data['department'])) {
            $pdo->prepare(
                'UPDATE users SET full_name=COALESCE(?,full_name), email=COALESCE(?,email), phone=COALESCE(?,phone),
                 position=COALESCE(?,position), department=COALESCE(?,department) WHERE id=?'
            )->execute([
                $data['full_name'] ?? null,
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['position'] ?? null,
                $data['department'] ?? null,
                $userId,
            ]);
        }

        if (isset($data['dark_mode']) || isset($data['theme_color']) || isset($data['font_size'])
            || isset($data['card_style']) || isset($data['animation_speed'])) {
            $pdo->prepare(
                'INSERT INTO user_settings (user_id, dark_mode, theme_color, font_size, card_style, animation_speed)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    dark_mode=VALUES(dark_mode), theme_color=VALUES(theme_color), font_size=VALUES(font_size),
                    card_style=VALUES(card_style), animation_speed=VALUES(animation_speed)'
            )->execute([
                $userId,
                $data['dark_mode'] ?? 0,
                $data['theme_color'] ?? '#3498db',
                $data['font_size'] ?? 'medium',
                $data['card_style'] ?? 'default',
                $data['animation_speed'] ?? 'normal',
            ]);
        }

        if (!empty($data['new_password'])) {
            $strength = check_password_strength($data['new_password']);
            if (!$strength['valid']) json_error($strength['message']);
            $hash = password_hash($data['new_password'], PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([$hash, $userId]);
        }

        json_out(['success' => true]);
        break;

    default:
        json_error('Method not allowed.', 405);
}
