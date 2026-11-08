<?php
/**
 * Role-based access control. Roles (least to most privileged):
 *   staff   - can view and create shipments; cannot delete anything
 *             or manage the fleet/warehouse
 *   manager - full access to shipments, fleet, and warehouse
 *   admin   - everything a manager can do, plus user management
 *             and API key generation
 *
 * Include this after includes/api_helpers.php (needs $_SESSION + PDO).
 */

/**
 * Fetches the current user's role from the DB. Cached per-request.
 */
function current_user_role(PDO $pdo, int $userId): string
{
    static $cache = [];
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $role = $stmt->fetchColumn();
    $cache[$userId] = $role ?: 'staff';
    return $cache[$userId];
}

/**
 * Aborts the request with a 403 JSON error unless the current user's
 * role is one of $allowedRoles. Call require_login() first.
 */
function require_role(PDO $pdo, int $userId, array $allowedRoles): string
{
    $role = current_user_role($pdo, $userId);
    if (!in_array($role, $allowedRoles, true)) {
        json_error('You do not have permission to perform this action.', 403);
    }
    return $role;
}

function require_admin(PDO $pdo, int $userId): void
{
    require_role($pdo, $userId, ['admin']);
}
