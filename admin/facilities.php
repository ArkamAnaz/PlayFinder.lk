<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

/* ---------- Actions: approve / reject / delete ---------- */
if (is_post()) {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $f = q_one('SELECT * FROM facilities WHERE id = ?', [$id]);
    $action = $_POST['action'] ?? '';
    if ($f) {
        if ($action === 'approve' || $action === 'reject') {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            q('UPDATE facilities SET status = ? WHERE id = ?', [$status, $id]);
            flash('success', "\"{$f['name']}\" has been $status.");
        } elseif ($action === 'delete') {
            $upcoming = q_val('SELECT COUNT(*) FROM bookings b JOIN courts c ON c.id = b.court_id
                               WHERE c.facility_id = ? AND b.status = ? AND b.booking_date >= ?', [$id, 'confirmed', date('Y-m-d')]);
            if ($upcoming > 0) {
                flash('warning', "Cannot delete \"{$f['name']}\" — it has $upcoming upcoming booking(s). Reject it instead to hide it.");
            } else {
                if ($f['image'] && is_file(UPLOAD_DIR . $f['image'])) @unlink(UPLOAD_DIR . $f['image']);
                q('DELETE FROM facilities WHERE id = ?', [$id]);
                flash('success', "\"{$f['name']}\" deleted.");
            }
        }
    }
    $back = $_POST['back'] ?? '';
    redirect('admin/facilities.php' . (preg_match('/^\?[\w=&%+\-]*$/', $back) ? $back : ''));
}

/* ---------- List ---------- */
$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = ['1 = 1'];
$params = ['confirmed'];
if (in_array($status, ['approved', 'pending', 'rejected'], true)) {
    $where[] = 'f.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(f.name LIKE ? OR f.area LIKE ? OR u.full_name LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%");
}

$facilities = q_all("SELECT f.*, u.full_name AS owner_name,
        (SELECT COUNT(*) FROM courts c WHERE c.facility_id = f.id) AS court_count,
        (SELECT COUNT(*) FROM bookings b JOIN courts c ON c.id = b.court_id WHERE c.facility_id = f.id AND b.status = ?) AS booking_count
    FROM facilities f JOIN users u ON u.id = f.owner_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY CASE f.status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END, f.name", $params);

$counts = [];
foreach (q_all('SELECT status, COUNT(*) AS n FROM facilities GROUP BY status') as $r) $counts[$r['status']] = $r['n'];
$back = '?' . http_build_query(array_filter(['status' => $status, 'q' => $search]));

$pageTitle = 'Facilities';
$activeNav = 'facilities';
require __DIR__ . '/../includes/dash_header.php';
?>
<div class="flex between mb-2">
    <div class="tabs mb-0">
        <a href="?" class="<?= $status === '' ? 'active' : '' ?>">All (<?= array_sum($counts) ?>)</a>
        <?php foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?>
            <a href="?status=<?= $k ?>" class="<?= $status === $k ? 'active' : '' ?>"><?= $l ?> (<?= (int)($counts[$k] ?? 0) ?>)</a>
        <?php endforeach; ?>
    </div>
    <div class="flex">
        <form method="get" class="flex">
            <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search…" style="width:200px">
        </form>
        <a href="<?= url('owner/facility-form.php') ?>" class="btn btn-primary btn-sm">+ Add facility</a>
    </div>
</div>

<div class="table-wrap">
    <table>
        <tr><th>Facility</th><th>Owner</th><th>Area</th><th>Courts</th><th>Bookings</th><th>Status</th><th>Actions</th></tr>
        <?php if (!$facilities): ?><tr><td colspan="7" class="text-center muted" style="padding:30px">No facilities found.</td></tr><?php endif; ?>
        <?php foreach ($facilities as $f): ?>
            <tr>
                <td><strong><?= e($f['name']) ?></strong><span class="sub"><?= e($f['address']) ?></span></td>
                <td><?= e($f['owner_name']) ?></td>
                <td><?= e($f['area']) ?></td>
                <td><?= (int)$f['court_count'] ?></td>
                <td><?= (int)$f['booking_count'] ?></td>
                <td><?= facility_status_badge($f['status']) ?></td>
                <td class="actions">
                    <?php if ($f['status'] !== 'approved'): ?>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $f['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
                            <button name="action" value="approve" class="btn btn-primary btn-sm">Approve</button></form>
                    <?php endif; ?>
                    <?php if ($f['status'] !== 'rejected'): ?>
                        <form method="post" data-confirm="Reject / hide &quot;<?= e($f['name']) ?>&quot;? It will no longer be visible to players.">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $f['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
                            <button name="action" value="reject" class="btn btn-outline btn-sm">Reject</button></form>
                    <?php endif; ?>
                    <a href="<?= url('facility.php?id=' . $f['id']) ?>" class="btn btn-outline btn-sm" target="_blank">View</a>
                    <a href="<?= url('owner/facility-form.php?id=' . $f['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                    <a href="<?= url('owner/courts.php?facility=' . $f['id']) ?>" class="btn btn-outline btn-sm">Courts</a>
                    <form method="post" data-confirm="Permanently delete &quot;<?= e($f['name']) ?>&quot; with all its courts and booking history?">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $f['id'] ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
                        <button name="action" value="delete" class="btn btn-danger btn-sm">Delete</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
