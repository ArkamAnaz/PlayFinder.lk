<?php
/** Cancels a booking (POST only). Used by customers, facility owners and admin. */
require_once __DIR__ . '/includes/init.php';
$user = require_login();

if (!is_post()) redirect('my-bookings.php');
verify_csrf();

$b = q_one(booking_select_sql() . ' WHERE b.id = ?', [(int)($_POST['id'] ?? 0)]);

// Work out in which capacity this user is cancelling
$role = null;
if ($b) {
    if ($user['role'] === 'admin') $role = 'admin';
    elseif ($user['role'] === 'owner' && $b['owner_id'] == $user['id']) $role = 'owner';
    elseif ($b['user_id'] == $user['id']) $role = 'user';
}

if (!$role) {
    flash('danger', 'Booking not found.');
} elseif (!can_cancel($b, $role)) {
    flash('warning', $role === 'user'
        ? 'This booking can no longer be cancelled online (limit: ' . CANCEL_LIMIT_HOURS . ' hours before start).'
        : 'Only upcoming bookings can be cancelled.');
} else {
    cancel_booking((int)$b['id'], $role);
    flash('success', 'Booking ' . $b['booking_ref'] . ' has been cancelled.');
}

// Only allow redirecting back to a relative page inside the site
$back = $_POST['back'] ?? 'my-bookings.php';
if (!preg_match('#^[a-z0-9_\-/]+\.php(\?[\w=&%+\-.]*)?$#i', $back)) $back = 'my-bookings.php';
redirect($back);
