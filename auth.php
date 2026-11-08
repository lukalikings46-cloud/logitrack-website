<?php
require_once __DIR__ . '/../includes/api_helpers.php';
require_once __DIR__ . '/../includes/login_security.php';
require_once __DIR__ . '/../includes/security.php';

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($method === 'POST' ? (body_json()['action'] ?? '') : '');

switch ($action) {

    case 'login':
        $data = body_json();
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            json_error('Email and password are required.');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            record_login_attempt($pdo, $email, false, $user ?: null);
            json_error('Invalid email or password.', 401);
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];

        record_login_attempt($pdo, $email, true, $user);

        unset($user['password_hash']);
        json_out(['success' => true, 'user' => $user]);
        break;

    case 'register':
        $data = body_json();
        $fullName = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $role = in_array($data['role'] ?? '', ['admin', 'manager', 'staff'], true) ? $data['role'] : 'staff';

        if ($fullName === '' || $email === '') {
            json_error('Full name and a valid email are required.');
        }

        $strength = check_password_strength($password);
        if (!$strength['valid']) {
            json_error($strength['message']);
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            json_error('An account with that email already exists.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $ins->execute([$fullName, $email, $hash, $role]);
        $newId = (int) $pdo->lastInsertId();

        $pdo->prepare('INSERT INTO user_settings (user_id) VALUES (?)')->execute([$newId]);

        $_SESSION['user_id'] = $newId;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['role'] = $role;

        json_out(['success' => true, 'user' => ['id' => $newId, 'full_name' => $fullName, 'email' => $email, 'role' => $role]]);
        break;

    case 'logout':
        $_SESSION = [];
        session_destroy();
        json_out(['success' => true]);
        break;

    case 'me':
        if (empty($_SESSION['user_id'])) {
            json_out(['success' => true, 'loggedIn' => false]);
        }
        $stmt = $pdo->prepare('SELECT id, full_name, email, phone, position, department, avatar, role FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        json_out(['success' => true, 'loggedIn' => (bool) $user, 'user' => $user ?: null]);
        break;

    default:
        json_error('Unknown action. Use ?action=login|register|logout|me.', 404);
}
