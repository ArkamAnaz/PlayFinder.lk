<?php
require_once __DIR__ . '/includes/init.php';
$user = require_login();

$tab = $_GET['tab'] ?? 'upcoming';
if (!in_array($tab, ['upcoming', 'past', 'cancelled'], true)) $tab = 'upcoming';

$all = q_all(booking_select_sql() . ' WHERE b.user_id = ? ORDER BY b.booking_date DESC, b.start_time DESC', [$user['id']]);

$groups = ['upcoming' => [], 'past' => [], 'cancelled' => []];
foreach ($all as $b) {
    $s = booking_state($b);
    $groups[$s === 'completed' ? 'past' : $s][] = $b;
}
$groups['upcoming'] = array_reverse($groups['upcoming']); // soonest first
$list = $groups[$tab];

$totalSpent = array_sum(array_map(fn($b) => $b['total_price'], $groups['past']));

$pageTitle = 'My Bookings';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="container flex between">
        <div>
            <h1>My bookings</h1>
            <p>Hi <?= e(explode(' ', $user['full_name'])[0]) ?> — <?= count($groups['upcoming']) ?> upcoming game<?= count($groups['upcoming']) === 1 ? '' : 's' ?>, <?= count($groups['past']) ?> played (<?= lkr($totalSpent) ?>).</p>
        </div>
        <a href="<?= url('facilities.php') ?>" class="btn btn-volt">+ New booking</a>
    </div>
</section>

<div class="container section" style="padding-top:28px">
    <div class="tabs">
        <?php foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $k => $label): ?>
            <a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= $label ?> (<?= count($groups[$k]) ?>)</a>
        <?php endforeach; ?>
    </div>

    <?php if (!$list): ?>
        <div class="empty-state">
            <div class="icon">🗓️</div>
            <h3>No <?= $tab ?> bookings</h3>
            <p class="muted">Find a court and book your next game.</p>
            <a href="<?= url('facilities.php') ?>" class="btn btn-primary">Find courts</a>
        </div>
    <?php endif; ?>

    <?php foreach ($list as $b): ?>
        <div class="booking-card">
            <div class="booking-date">
                <small><?= date('M', strtotime($b['booking_date'])) ?></small>
                <strong><?= date('d', strtotime($b['booking_date'])) ?></strong>
                <small><?= date('D', strtotime($b['booking_date'])) ?></small>
            </div>
            <div>
                <h3><?= e($b['facility_name']) ?> <?= booking_badge($b) ?></h3>
                <div class="muted small">
                    <?= e($b['icon'] . ' ' . $b['court_name'] . ' · ' . $b['sport_name']) ?> ·
                    🕒 <?= time_label($b['start_time']) ?> – <?= time_label($b['end_time']) ?> ·
                    📍 <?= e($b['area']) ?>
                </div>
                <div class="small mt-1">Ref <strong><?= e($b['booking_ref']) ?></strong> · <?= lkr($b['total_price']) ?></div>
            </div>
            <div class="actions">
                <a href="<?= url('booking.php?ref=' . urlencode($b['booking_ref'])) ?>" class="btn btn-outline btn-sm">View</a>
                <?php if (can_cancel($b, 'user')): ?>
                    <form method="post" action="<?= url('cancel-booking.php') ?>" data-confirm="Cancel booking <?= e($b['booking_ref']) ?>?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <input type="hidden" name="back" value="my-bookings.php?tab=<?= $tab ?>">
                        <button class="btn btn-danger btn-sm">Cancel</button>
                    </form>
                <?php elseif ($tab !== 'upcoming'): ?>
                    <a href="<?= url('book.php?court=' . $b['court_id']) ?>" class="btn btn-primary btn-sm">Book again</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
