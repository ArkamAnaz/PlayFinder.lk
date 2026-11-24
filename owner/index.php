<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_role('owner');

$ownerId = $user['id'];
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

$facCounts = q_one('SELECT COUNT(*) AS total,
                           SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS approved,
                           SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS pending
                    FROM facilities WHERE owner_id = ?', ['approved', 'pending', $ownerId]);

$base = ' FROM bookings b JOIN courts c ON c.id = b.court_id JOIN facilities f ON f.id = c.facility_id
          WHERE f.owner_id = ? AND b.status = ?';
$todayCount   = q_val('SELECT COUNT(*)' . $base . ' AND b.booking_date = ?', [$ownerId, 'confirmed', $today]);
$upcoming     = q_val('SELECT COUNT(*)' . $base . ' AND b.booking_date >= ?', [$ownerId, 'confirmed', $today]);
$monthRevenue = q_val('SELECT COALESCE(SUM(b.total_price),0)' . $base . ' AND b.booking_date >= ? AND b.booking_date <= ?',
                      [$ownerId, 'confirmed', $monthStart, date('Y-m-t')]);

// bookings for the next 7 days (chart)
$chart = [];
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime("+$i days"));
    $chart[$d] = 0;
}
foreach (q_all('SELECT b.booking_date, COUNT(*) AS n' . $base . ' AND b.booking_date >= ? AND b.booking_date <= ? GROUP BY b.booking_date',
               [$ownerId, 'confirmed', $today, date('Y-m-d', strtotime('+6 days'))]) as $r) {
    $chart[$r['booking_date']] = (int)$r['n'];
}
$maxBar = max(1, max($chart));

$todayList = q_all(booking_select_sql() . ' WHERE f.owner_id = ? AND b.booking_date = ? AND b.status = ? ORDER BY b.start_time',
                   [$ownerId, $today, 'confirmed']);

$pageTitle = 'Owner Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/../includes/dash_header.php';
?>
<?php if ((int)$facCounts['total'] === 0): ?>
    <div class="card text-center">
        <div style="font-size:48px">🏟️</div>
        <h2>Add your first facility</h2>
        <p class="muted">List your sports venue and courts. Once approved by the admin, players can find and book it online.</p>
        <a href="<?= url('owner/facility-form.php') ?>" class="btn btn-primary">+ Add facility</a>
    </div>
<?php else: ?>
    <?php if ($facCounts['pending'] > 0): ?>
        <div class="alert alert-warning"><?= (int)$facCounts['pending'] ?> facilit<?= $facCounts['pending'] == 1 ? 'y is' : 'ies are' ?> waiting for admin approval.</div>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat"><span class="icon">🏟️</span><small>Live facilities</small><strong><?= (int)$facCounts['approved'] ?> / <?= (int)$facCounts['total'] ?></strong></div>
        <div class="stat"><span class="icon">📅</span><small>Bookings today</small><strong><?= (int)$todayCount ?></strong></div>
        <div class="stat"><span class="icon">⏭️</span><small>Upcoming bookings</small><strong><?= (int)$upcoming ?></strong></div>
        <div class="stat"><span class="icon">💰</span><small>Revenue this month</small><strong><?= lkr($monthRevenue) ?></strong></div>
    </div>

    <div class="grid-2" style="grid-template-columns:1.4fr 1fr">
        <div class="card">
            <div class="card-title"><h3>Today's schedule</h3><a href="<?= url('owner/bookings.php?date=' . $today) ?>" class="btn btn-outline btn-sm">All bookings</a></div>
            <?php if (!$todayList): ?>
                <p class="muted">No bookings today.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <tr><th>Time</th><th>Court</th><th>Customer</th><th>Amount</th></tr>
                        <?php foreach ($todayList as $b): ?>
                            <tr>
                                <td><strong><?= time_label($b['start_time']) ?></strong><span class="sub">to <?= time_label($b['end_time']) ?></span></td>
                                <td><?= e($b['court_name']) ?><span class="sub"><?= e($b['facility_name']) ?></span></td>
                                <td><?= e($b['user_name']) ?><span class="sub"><?= e($b['user_phone']) ?></span></td>
                                <td><?= lkr($b['total_price']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <h3>Bookings – next 7 days</h3>
            <div class="bar-chart">
                <?php foreach ($chart as $d => $n): ?>
                    <div class="bar">
                        <b><?= $n ?></b>
                        <div style="height:<?= round($n / $maxBar * 100) ?>%"></div>
                        <small><?= date('D', strtotime($d)) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
