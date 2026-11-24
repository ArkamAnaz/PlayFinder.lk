<?php
/** Manage courts of a facility (owner of the facility or admin) */
require_once __DIR__ . '/../includes/init.php';
$user = require_role('owner', 'admin');
$isAdmin = $user['role'] === 'admin';

$facilityId = (int)($_GET['facility'] ?? 0);
$facility = q_one('SELECT * FROM facilities WHERE id = ?', [$facilityId]);
if (!$facility || (!$isAdmin && $facility['owner_id'] != $user['id'])) {
    flash('danger', 'Facility not found.');
    redirect($isAdmin ? 'admin/facilities.php' : 'owner/facilities.php');
}
$sportIds = array_column(all_sports(), 'id');
$self = 'owner/courts.php?facility=' . $facilityId;

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $courtId = (int)($_POST['court_id'] ?? 0);

    // make sure the court belongs to this facility
    $court = $courtId ? q_one('SELECT * FROM courts WHERE id = ? AND facility_id = ?', [$courtId, $facilityId]) : null;

    if ($action === 'add' || $action === 'update') {
        $name  = trim($_POST['name'] ?? '');
        $sport = (int)($_POST['sport_id'] ?? 0);
        $price = (float)($_POST['price_per_hour'] ?? 0);
        $err = null;
        if (mb_strlen($name) < 1 || mb_strlen($name) > 80) $err = 'Court name is required (max 80 characters).';
        elseif (!in_array($sport, $sportIds))               $err = 'Please choose a sport.';
        elseif ($price < 100 || $price > 100000)            $err = 'Price per hour must be between Rs. 100 and Rs. 100,000.';

        if ($err) {
            flash('danger', $err);
        } elseif ($action === 'add') {
            q('INSERT INTO courts (facility_id, sport_id, name, price_per_hour, is_active) VALUES (?, ?, ?, ?, 1)',
              [$facilityId, $sport, $name, $price]);
            flash('success', "Court \"$name\" added.");
        } elseif ($court) {
            q('UPDATE courts SET name = ?, sport_id = ?, price_per_hour = ? WHERE id = ?', [$name, $sport, $price, $court['id']]);
            flash('success', 'Court updated.');
        }
    }

    if ($action === 'toggle' && $court) {
        q('UPDATE courts SET is_active = ? WHERE id = ?', [$court['is_active'] ? 0 : 1, $court['id']]);
        flash('success', $court['is_active'] ? 'Court hidden from booking.' : 'Court is bookable again.');
    }

    if ($action === 'delete' && $court) {
        if (q_val('SELECT COUNT(*) FROM bookings WHERE court_id = ?', [$court['id']]) > 0) {
            flash('warning', 'This court has booking history, so it cannot be deleted. It has been deactivated instead.');
            q('UPDATE courts SET is_active = 0 WHERE id = ?', [$court['id']]);
        } else {
            q('DELETE FROM courts WHERE id = ?', [$court['id']]);
            flash('success', 'Court deleted.');
        }
    }
    redirect($self);
}

$courts = q_all('SELECT c.*, s.name AS sport_name, s.icon,
                   (SELECT COUNT(*) FROM bookings b WHERE b.court_id = c.id AND b.status = ?) AS bookings
                 FROM courts c JOIN sports s ON s.id = c.sport_id WHERE c.facility_id = ? ORDER BY s.name, c.name',
                 ['confirmed', $facilityId]);
$editId = (int)($_GET['edit'] ?? 0);

$pageTitle = 'Courts – ' . $facility['name'];
$activeNav = 'facilities';
require __DIR__ . '/../includes/dash_header.php';
?>
<div class="flex between mb-2">
    <div>
        <a href="<?= url($isAdmin ? 'admin/facilities.php' : 'owner/facilities.php') ?>">← Back to facilities</a>
        <span class="muted"> · <?= facility_status_badge($facility['status']) ?> · <?= time_label($facility['open_time']) ?> – <?= time_label($facility['close_time']) ?></span>
    </div>
    <a href="<?= url('owner/facility-form.php?id=' . $facilityId) ?>" class="btn btn-outline btn-sm">Edit facility details</a>
</div>

<div class="detail-layout" style="grid-template-columns:minmax(0,1fr) 360px">
    <div class="table-wrap">
        <table>
            <tr><th>Court</th><th>Sport</th><th>Price / hour</th><th>Bookings</th><th>Status</th><th></th></tr>
            <?php if (!$courts): ?>
                <tr><td colspan="6" class="muted text-center" style="padding:30px">No courts yet. Add your first court using the form →</td></tr>
            <?php endif; ?>
            <?php foreach ($courts as $c): ?>
                <?php if ($editId === (int)$c['id']): ?>
                    <tr>
                        <td colspan="6">
                            <form method="post" class="flex">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="update"><input type="hidden" name="court_id" value="<?= $c['id'] ?>">
                                <input type="text" name="name" value="<?= e($c['name']) ?>" style="flex:2" required>
                                <select name="sport_id" style="flex:1.5">
                                    <?php foreach (all_sports() as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= $s['id'] == $c['sport_id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="price_per_hour" value="<?= e((float)$c['price_per_hour']) ?>" min="100" step="50" style="flex:1" required>
                                <button class="btn btn-primary btn-sm">Save</button>
                                <a href="<?= url($self) ?>" class="btn btn-outline btn-sm">Cancel</a>
                            </form>
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td><strong><?= e($c['name']) ?></strong></td>
                        <td><?= e($c['icon'] . ' ' . $c['sport_name']) ?></td>
                        <td><?= lkr($c['price_per_hour']) ?></td>
                        <td><?= (int)$c['bookings'] ?></td>
                        <td><?= $c['is_active'] ? badge('Active', 'success') : badge('Hidden', 'muted') ?></td>
                        <td class="actions">
                            <a href="<?= url($self . '&edit=' . $c['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                            <form method="post">
                                <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="court_id" value="<?= $c['id'] ?>">
                                <button class="btn btn-outline btn-sm"><?= $c['is_active'] ? 'Hide' : 'Activate' ?></button>
                            </form>
                            <form method="post" data-confirm="Delete court <?= e($c['name']) ?>?">
                                <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="court_id" value="<?= $c['id'] ?>">
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>

    <aside class="card">
        <h3>Add a court</h3>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="add">
            <div class="form-group"><label>Court name</label><input type="text" name="name" placeholder="e.g. Court 1" maxlength="80" required></div>
            <div class="form-group">
                <label>Sport</label>
                <select name="sport_id" required>
                    <?php foreach (all_sports() as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['icon'] . ' ' . $s['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Price per hour (Rs.)</label><input type="number" name="price_per_hour" min="100" step="50" placeholder="2000" required></div>
            <button class="btn btn-primary btn-block">+ Add court</button>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
