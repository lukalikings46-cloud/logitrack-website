<?php
/**
 * Shared helpers for every file in /api. Include this first.
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../config/database.php';

// Never let PHP warnings/notices leak into the JSON body (breaks res.json() in the browser)
ini_set('display_errors', '0');

header('Content-Type: application/json');

function json_out($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function json_error(string $message, int $code = 400): void
{
    json_out(['success' => false, 'error' => $message], $code);
}

function require_login(): int
{
    if (empty($_SESSION['user_id'])) {
        json_error('Not authenticated. Please log in.', 401);
    }
    return (int) $_SESSION['user_id'];
}

function body_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
