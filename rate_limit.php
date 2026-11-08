<?php
/**
 * Authenticates a request using an X-API-Key header (for external /
 * third-party integrations, as opposed to the browser session used
 * by the main dashboard) and enforces a per-key rate limit using a
 * sliding 60-second window.
 *
 * Include after includes/api_helpers.php. On success, returns the
 * api_keys row (array). On failure, sends a JSON error and exits.
 */
function require_api_key(PDO $pdo): array
{
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $provided = $headers['X-API-Key'] ?? $headers['x-api-key'] ?? ($_GET['api_key'] ?? null);

    if (!$provided) {
        json_error('Missing API key. Pass it in the X-API-Key header.', 401);
    }

    $stmt = $pdo->prepare('SELECT * FROM api_keys WHERE api_key = ? AND active = 1');
    $stmt->execute([$provided]);
    $key = $stmt->fetch();

    if (!$key) {
        json_error('Invalid or inactive API key.', 401);
    }

    // Sliding-window rate limit: count requests from this key in the last 60 seconds.
    $count = $pdo->prepare(
        'SELECT COUNT(*) FROM api_request_log WHERE api_key_id = ? AND requested_at > (NOW() - INTERVAL 60 SECOND)'
    );
    $count->execute([$key['id']]);
    $recent = (int) $count->fetchColumn();

    if ($recent >= (int) $key['rate_limit_per_min']) {
        header('Retry-After: 60');
        json_error('Rate limit exceeded. Max ' . $key['rate_limit_per_min'] . ' requests per minute.', 429);
    }

    // Log this request and update last-used timestamp.
    $pdo->prepare('INSERT INTO api_request_log (api_key_id) VALUES (?)')->execute([$key['id']]);
    $pdo->prepare('UPDATE api_keys SET last_used_at = NOW() WHERE id = ?')->execute([$key['id']]);

    // Occasionally prune old log rows so the table doesn't grow forever.
    if (random_int(1, 50) === 1) {
        $pdo->exec('DELETE FROM api_request_log WHERE requested_at < (NOW() - INTERVAL 1 DAY)');
    }

    return $key;
}

function generate_api_key(): string
{
    return bin2hex(random_bytes(24)); // 48-character key
}
