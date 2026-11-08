<?php
/**
 * Server-side password strength checking. The frontend also shows a
 * live strength meter, but the server is the source of truth — never
 * trust client-side validation alone.
 */

/**
 * Returns ['valid' => bool, 'message' => string, 'score' => 0-4]
 */
function check_password_strength(string $password): array
{
    $score = 0;
    $issues = [];

    if (strlen($password) < 8) {
        $issues[] = 'at least 8 characters';
    } else {
        $score++;
    }

    if (!preg_match('/[a-z]/', $password)) {
        $issues[] = 'a lowercase letter';
    } else {
        $score++;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $issues[] = 'an uppercase letter';
    } else {
        $score++;
    }

    if (!preg_match('/[0-9]/', $password)) {
        $issues[] = 'a number';
    } else {
        $score++;
    }

    if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $issues[] = 'a special character (e.g. ! @ # $ %)';
    } else {
        $score++;
    }

    if (!empty($issues)) {
        return [
            'valid'   => false,
            'message' => 'Password must include ' . implode(', ', $issues) . '.',
            'score'   => $score,
        ];
    }

    return ['valid' => true, 'message' => 'Strong password.', 'score' => $score];
}
