<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '') ?: '🏅';
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');

    if ($action === 'add' || $action === 'update') {
        $dupe = q_val('SELECT COUNT(*) FROM sports WHERE (name = ? OR slug = ?) AND id <> ?', [$name, $slug, $id]);
        if (mb_strlen($name) < 2 || mb_strlen($name) > 50) flash('danger', 'Sport name must be 2–50 characters.');
        elseif (mb_strlen($icon) > 4)                       flash('danger', 'Icon should be a single emoji.');
        elseif ($dupe)                                       flash('danger', 'A sport with this name already exists.');
        elseif ($action === 'add') {
            q('INSERT INTO sports (name, slug, icon) VALUES (?, ?, ?)', [$name, $slug, $icon]);
            flash('success', "Sport \"$name\" added.");
        } else {
            q('UPDATE sports SET name = ?, slug = ?, icon = ? WHERE id = ?', [$name, $slug, $icon, $id]);
            flash('success', 'Sport updated.');
        }
    }
    if ($action === 'delete') {
        if (q_val('SELECT COUNT(*) FROM courts WHERE sport_id = ?', [$id]) > 0) {
            flash('warning', 'This sport is used by existing courts and cannot be deleted.');
        } else {
            q('DELETE FROM sports WHERE id = ?', [$id]);
            flash('success', 'Sport deleted.');
        }
    }
    redirect('admin/sports.php');
}

$sports = q_all('SELECT s.*, (SELECT COUNT(*) FROM courts c WHERE c.sport_id = s.id) AS courts FROM sports s ORDER BY s.name');
$pageTitle = 'Sports';
$activeNav = 'sports';
require __DIR__ . '/../includes/dash_header.php';
?>
<div class="detail-layout" style="grid-template-columns:minmax(0,1fr) 340px">
    <div class="table-wrap">
        <table>
            <tr><th>Icon</th><th>Sport</th><th>Courts</th><th></th></tr>
            <?php foreach ($sports as $s): ?>
                <tr>
                    <td colspan="4">
                        <div class="flex">
                            <form method="post" class="flex" style="flex:1">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="action" value="update">
                                <input type="text" name="icon" value="<?= e($s['icon']) ?>" style="width:60px;text-align:center;font-size:20px">
                                <input type="text" name="name" value="<?= e($s['name']) ?>" style="flex:1;min-width:140px">
                                <span class="muted small" style="width:80px"><?= (int)$s['courts'] ?> courts</span>
                                <button class="btn btn-outline btn-sm">Save</button>
                            </form>
                            <form method="post" data-confirm="Delete sport <?= e($s['name']) ?>?">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $s['id'] ?>"><input type="hidden" name="action" value="delete">
                                <button class="btn btn-danger btn-sm" <?= $s['courts'] ? 'disabled title="In use by courts"' : '' ?>>Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <aside class="card">
        <h3>Add sport</h3>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="add">
            <div class="form-group"><label>Icon (emoji)</label><input type="text" name="icon" placeholder="🎾" maxlength="4"></div>
            <div class="form-group"><label>Sport name</label><input type="text" name="name" placeholder="e.g. Squash" required></div>
            <button class="btn btn-primary btn-block">+ Add sport</button>
        </form>
    </aside>
</div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
