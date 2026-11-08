<?php
/**
 * Include at the very top of any page that requires a logged-in user.
 * Redirects to login.php if there is no active session.
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Load the current user's profile fresh from the DB so index.php
// can render real values instead of the old hard-coded placeholders.
$currentUser = null;
try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT id, full_name, email, phone, position, department, avatar, role FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch();
} catch (Exception $e) {
    // If the DB isn't reachable we still let the page render;
    // the JS layer falls back to its built-in sample data.
    $currentUser = null;
}

if (!$currentUser) {
    // Session pointed at a user that no longer exists.
    session_destroy();
    header('Location: login.php');
    exit;
}
