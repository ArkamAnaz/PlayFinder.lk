<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$today = date('Y-m-d');
$stats = [
    'players'    => q_val('SELECT COUNT(*) FROM users WHERE role = ?', ['user']),
    'owners'     => q_val('SELECT COUNT(*) FROM users WHERE role = ?', ['owner']),
    'facilities' => q_val('SELECT COUNT(*) FROM facilities WHERE status = ?', ['approved']),
    'pending'    => q_val('SELECT COUNT(*) FROM facilities WHERE status = ?', ['pending']),
    'courts'     => q_val('SELECT COUNT(*) FROM courts WHERE is_active = 1'),
    'bookings'   => q_val('SELECT COUNT(*) FROM bookings WHERE status = ?', ['confirmed']),
    'today'      => q_val('SELECT COUNT(*) FROM bookings WHERE status = ? AND booking_date = ?', ['confirmed', $today]),
    'value'      => q_val('SELECT COALESCE(SUM(total_price),0) FROM bookings WHERE status = ?', ['confirmed']),
];

// Bookings per sport
$bySport = q_all('SELECT s.name, s.icon, COUNT(b.id) AS n FROM sports s
                  LEFT JOIN courts c ON c.sport_id = s.id
                  LEFT JOIN bookings b ON b.court_id = c.id AND b.status = ?
                  GROUP BY s.id, s.name, s.icon ORDER BY n DESC', ['confirmed']);
$maxSport = max(1, max(array_column($bySport, 'n') ?: [0]));

$pending = q_all('SELECT f.*, u.full_name AS owner_name FROM facilities f JOIN users u ON u.id = f.owner_id
                  WHERE f.status = ? ORDER BY f.created_at', ['pending']);
$recent = q_all(booking_select_sql() . ' ORDER BY b.created_at DESC LIMIT 8');

$pageTitle = 'Admin Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/dash_header.php';
?>
<div class="stat-grid">
    <div class="stat"><span class="icon">🏃</span><small>Players</small><strong><?= (int)$stats['players'] ?></strong></div>
    <div class="stat"><span class="icon">🏟️</span><small>Live facilities</small><strong><?= (int)$stats['facilities'] ?></strong></div>
    <div class="stat"><span class="icon">📅</span><small>Confirmed bookings</small><strong><?= (int)$stats['bookings'] ?></strong></div>
    <div class="stat"><span class="icon">💰</span><small>Booking value</small><strong><?= lkr($stats['value']) ?></strong></div>
    <div class="stat"><span class="icon">🧑‍💼</span><small>Facility owners</small><strong><?= (int)$stats['owners'] ?></strong></div>
    <div class="stat"><span class="icon">⏳</span><small>Pending approval</small><strong><?= (int)$stats['pending'] ?></strong></div>
    <div class="stat"><span class="icon">🎾</span><small>Active courts</small><strong><?= (int)$stats['courts'] ?></strong></div>
    <div class="stat"><span class="icon">☀️</span><small>Bookings today</small><strong><?= (int)$stats['today'] ?></strong></div>
</div>

<?php if ($pending): ?>
<div class="card">
    <div class="card-title"><h3>⏳ Facilities awaiting approval</h3><a href="<?= url('admin/facilities.php?status=pending') ?>" class="btn btn-outline btn-sm">View all</a></div>
    <div class="table-wrap">
        <table>
            <tr><th>Facility</th><th>Owner</th><th>Area</th><th>Submitted</th><th></th></tr>
            <?php foreach ($pending as $f): ?>
                <tr>
                    <td><strong><?= e($f['name']) ?></strong><span class="sub"><?= e($f['address']) ?></span></td>
                    <td><?= e($f['owner_name']) ?></td>
                    <td><?= e($f['area']) ?></td>
                    <td><?= date('d M Y', strtotime($f['created_at'])) ?></td>
                    <td class="actions">
                        <a href="<?= url('facility.php?id=' . $f['id']) ?>" class="btn btn-outline btn-sm" target="_blank">Preview</a>
                        <form method="post" action="<?= url('admin/facilities.php') ?>">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $f['id'] ?>"><input type="hidden" name="action" value="approve">
                            <button class="btn btn-primary btn-sm">Approve</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="grid-2" style="grid-template-columns:1fr 1.6fr;margin-top:20px">
    <div class="card" style="margin:0">
        <h3>Bookings by sport</h3>
        <?php foreach ($bySport as $s): ?>
            <div style="margin:12px 0">
                <div class="flex between small"><span><?= e($s['icon'] . ' ' . $s['name']) ?></span><strong><?= (int)$s['n'] ?></strong></div>
                <div style="background:var(--bg);border-radius:6px;height:10px;margin-top:4px">
                    <div style="background:var(--brand);height:10px;border-radius:6px;width:<?= round($s['n'] / $maxSport * 100) ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card" style="margin:0">
        <div class="card-title"><h3>Latest bookings</h3><a href="<?= url('admin/bookings.php') ?>" class="btn btn-outline btn-sm">All bookings</a></div>
        <div class="table-wrap">
            <table>
                <tr><th>Ref</th><th>Customer</th><th>Facility</th><th>Date</th><th>Status</th></tr>
                <?php foreach ($recent as $b): ?>
                    <tr>
                        <td><a href="<?= url('booking.php?ref=' . urlencode($b['booking_ref'])) ?>"><?= e($b['booking_ref']) ?></a></td>
                        <td><?= e($b['user_name']) ?></td>
                        <td><?= e($b['facility_name']) ?><span class="sub"><?= e($b['court_name']) ?></span></td>
                        <td><?= date('d M', strtotime($b['booking_date'])) ?><span class="sub"><?= time_label($b['start_time']) ?></span></td>
                        <td><?= booking_badge($b) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
