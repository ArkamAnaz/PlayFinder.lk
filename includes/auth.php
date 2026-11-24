<?php
/**
 * Authentication & role-based access control
 */

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $user = q_one('SELECT id, full_name, email, phone, role, status FROM users WHERE id = ?', [$_SESSION['user_id']]);
            // Log out blocked or deleted users immediately
            if (!$user || $user['status'] !== 'active') {
                $user = null;
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(string ...$roles): bool
{
    $u = current_user();
    return $u && in_array($u['role'], $roles, true);
}

function login_user(array $user): void
{
    session_regenerate_id(true);          // prevent session fixation
    $_SESSION['user_id'] = $user['id'];
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

/** Redirect to login if not signed in. Remembers where the user wanted to go. */
function require_login(): array
{
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? url();
        flash('warning', 'Please log in to continue.');
        redirect('login.php');
    }
    return current_user();
}

function require_role(string ...$roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        flash('danger', 'You do not have permission to access that page.');
        redirect('index.php');
    }
    return $user;
}

/** Where to send each role after logging in */
function dashboard_url(string $role): string
{
    return match ($role) {
        'admin' => 'admin/index.php',
        'owner' => 'owner/index.php',
        default => 'my-bookings.php',
    };
}
