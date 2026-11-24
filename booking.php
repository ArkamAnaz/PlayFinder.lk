<?php
/** Booking confirmation / details ("ticket") page */
require_once __DIR__ . '/includes/init.php';
$user = require_login();

$b = q_one(booking_select_sql() . ' WHERE b.booking_ref = ?', [$_GET['ref'] ?? '']);

// Only the customer, the facility owner or admin can view a booking
$allowed = $b && ($b['user_id'] == $user['id'] || $user['role'] === 'admin'
           || ($user['role'] === 'owner' && $b['owner_id'] == $user['id']));
if (!$allowed) {
    flash('danger', 'Booking not found.');
    redirect('my-bookings.php');
}

$state = booking_state($b);
$isCustomer = $b['user_id'] == $user['id'];
$cancelRole = $isCustomer ? 'user' : $user['role'];

$pageTitle = 'Booking ' . $b['booking_ref'];
require __DIR__ . '/includes/header.php';
?>
<div class="container section">
    <div class="ticket">
        <div class="ticket-head">
            <div style="font-size:40px"><?= $state === 'cancelled' ? '❌' : ($state === 'completed' ? '🏁' : '✅') ?></div>
            <h2><?= $state === 'cancelled' ? 'Booking cancelled' : ($state === 'completed' ? 'Booking completed' : 'Booking confirmed') ?></h2>
            <div class="muted-light">Booking reference</div>
            <div class="ref"><?= e($b['booking_ref']) ?></div>
        </div>
        <div class="ticket-body">
            <div class="summary-row"><span class="muted">Facility</span><strong><?= e($b['facility_name']) ?></strong></div>
            <div class="summary-row"><span class="muted">Court</span><strong><?= e($b['icon'] . ' ' . $b['court_name'] . ' · ' . $b['sport_name']) ?></strong></div>
            <div class="summary-row"><span class="muted">Date</span><strong><?= date_label($b['booking_date']) ?></strong></div>
            <div class="summary-row"><span class="muted">Time</span><strong><?= time_label($b['start_time']) ?> – <?= time_label($b['end_time']) ?> (<?= (int)$b['hours'] ?>h)</strong></div>
            <div class="summary-row"><span class="muted">Booked by</span><strong><?= e($b['user_name']) ?> · <?= e($b['user_phone']) ?></strong></div>
            <div class="summary-row"><span class="muted">Status</span><span><?= booking_badge($b) ?></span></div>
            <?php if ($b['notes']): ?>
                <div class="summary-row"><span class="muted">Note</span><span><?= e($b['notes']) ?></span></div>
            <?php endif; ?>
            <div class="summary-total"><span>Amount (pay at venue)</span><span><?= lkr($b['total_price']) ?></span></div>
        </div>
        <div class="ticket-cut"></div>
        <div class="ticket-body">
            <p class="muted small mt-0">📍 <?= e($b['address']) ?> · 📞 <?= e($b['facility_phone']) ?></p>
            <div class="flex">
                <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="<?= e(maps_directions_url((float)$b['latitude'], (float)$b['longitude'])) ?>">🧭 Directions</a>
                <button class="btn btn-outline btn-sm" onclick="window.print()">🖨️ Print</button>
                <a class="btn btn-outline btn-sm" href="<?= url('my-bookings.php') ?>">My bookings</a>
                <?php if (can_cancel($b, $cancelRole)): ?>
                    <form method="post" action="<?= url('cancel-booking.php') ?>" data-confirm="Cancel this booking? This cannot be undone.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <input type="hidden" name="back" value="booking.php?ref=<?= e($b['booking_ref']) ?>">
                        <button class="btn btn-danger btn-sm">Cancel booking</button>
                    </form>
                <?php elseif ($state === 'upcoming' && $isCustomer): ?>
                    <span class="muted small">Online cancellation closes <?= CANCEL_LIMIT_HOURS ?>h before start. Please call the venue.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
