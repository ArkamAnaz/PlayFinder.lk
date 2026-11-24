<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_role('owner');

$facilities = q_all('SELECT f.*,
        (SELECT COUNT(*) FROM courts c WHERE c.facility_id = f.id) AS court_count,
        (SELECT COUNT(*) FROM bookings b JOIN courts c ON c.id = b.court_id WHERE c.facility_id = f.id AND b.status = ?) AS booking_count
    FROM facilities f WHERE f.owner_id = ? ORDER BY f.created_at DESC', ['confirmed', $user['id']]);

$pageTitle = 'My Facilities';
$activeNav = 'facilities';
require __DIR__ . '/../includes/dash_header.php';
?>
<div class="flex between mb-2">
    <p class="muted mb-0">New facilities and their courts become visible to players after admin approval.</p>
    <a href="<?= url('owner/facility-form.php') ?>" class="btn btn-primary">+ Add facility</a>
</div>

<?php if (!$facilities): ?>
    <div class="empty-state"><div class="icon">🏟️</div><h3>No facilities yet</h3><a href="<?= url('owner/facility-form.php') ?>" class="btn btn-primary">Add your first facility</a></div>
<?php else: ?>
<div class="table-wrap">
    <table>
        <tr><th>Facility</th><th>Area</th><th>Hours</th><th>Courts</th><th>Bookings</th><th>Status</th><th></th></tr>
        <?php foreach ($facilities as $f): ?>
            <tr>
                <td><strong><?= e($f['name']) ?></strong><span class="sub"><?= e($f['address']) ?></span></td>
                <td><?= e($f['area']) ?></td>
                <td class="small"><?= time_label($f['open_time']) ?> – <?= time_label($f['close_time']) ?></td>
                <td><?= (int)$f['court_count'] ?></td>
                <td><?= (int)$f['booking_count'] ?></td>
                <td><?= facility_status_badge($f['status']) ?></td>
                <td class="actions">
                    <a href="<?= url('owner/courts.php?facility=' . $f['id']) ?>" class="btn btn-dark btn-sm">Courts</a>
                    <a href="<?= url('owner/facility-form.php?id=' . $f['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                    <a href="<?= url('facility.php?id=' . $f['id']) ?>" class="btn btn-outline btn-sm" target="_blank">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
