<?php
require_once __DIR__ . '/../includes/init.php';
$admin = require_role('admin');

if (is_post()) {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $target = q_one('SELECT * FROM users WHERE id = ?', [$id]);
    $action = $_POST['action'] ?? '';

    if (!$target) {
        flash('danger', 'User not found.');
    } elseif ($target['id'] == $admin['id']) {
        flash('warning', 'You cannot change your own account here.');
    } elseif ($action === 'toggle') {
        $new = $target['status'] === 'active' ? 'blocked' : 'active';
        q('UPDATE users SET status = ? WHERE id = ?', [$new, $id]);
        flash('success', $target['full_name'] . ($new === 'blocked' ? ' has been blocked.' : ' has been unblocked.'));
    } elseif ($action === 'role' && in_array($_POST['role'] ?? '', ['user', 'owner', 'admin'], true)) {
        if ($target['role'] === 'owner' && $_POST['role'] !== 'owner'
            && q_val('SELECT COUNT(*) FROM facilities WHERE owner_id = ?', [$id]) > 0) {
            flash('warning', 'This owner still has facilities. Reassign or delete them first.');
        } else {
            q('UPDATE users SET role = ? WHERE id = ?', [$_POST['role'], $id]);
            flash('success', 'Role updated for ' . $target['full_name'] . '.');
        }
    }
    redirect('admin/users.php?' . http_build_query(array_filter(['role' => $_POST['f_role'] ?? '', 'q' => $_POST['f_q'] ?? ''])));
}

$role = $_GET['role'] ?? '';
$search = trim($_GET['q'] ?? '');
$where = ['1 = 1']; $params = ['confirmed'];
if (in_array($role, ['user', 'owner', 'admin'], true)) { $where[] = 'u.role = ?'; $params[] = $role; }
if ($search !== '') { $where[] = '(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)'; array_push($params, "%$search%", "%$search%", "%$search%"); }

$users = q_all('SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id AND b.status = ?) AS bookings
                FROM users u WHERE ' . implode(' AND ', $where) . ' ORDER BY u.created_at DESC', $params);

$roleLabels = ['user' => 'Player', 'owner' => 'Owner', 'admin' => 'Admin'];
$pageTitle = 'Users';
$activeNav = 'users';
require __DIR__ . '/../includes/dash_header.php';
?>
<div class="flex between mb-2">
    <div class="tabs mb-0">
        <a href="?" class="<?= $role === '' ? 'active' : '' ?>">All</a>
        <?php foreach ($roleLabels as $k => $l): ?><a href="?role=<?= $k ?>" class="<?= $role === $k ? 'active' : '' ?>"><?= $l ?>s</a><?php endforeach; ?>
    </div>
    <form method="get" class="flex">
        <?php if ($role): ?><input type="hidden" name="role" value="<?= e($role) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Name, email or phone…" style="width:240px">
    </form>
</div>

<div class="table-wrap">
    <table>
        <tr><th>Name</th><th>Phone</th><th>Role</th><th>Bookings</th><th>Joined</th><th>Status</th><th></th></tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><strong><?= e($u['full_name']) ?></strong><span class="sub"><?= e($u['email']) ?></span></td>
                <td><?= e($u['phone']) ?></td>
                <td>
                    <?php if ($u['id'] == $admin['id']): ?>
                        <?= badge('Admin (you)', 'info') ?>
                    <?php else: ?>
                        <form method="post" class="flex" style="flex-wrap:nowrap">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="role">
                            <input type="hidden" name="f_role" value="<?= e($role) ?>"><input type="hidden" name="f_q" value="<?= e($search) ?>">
                            <select name="role" onchange="this.form.submit()" style="padding:6px 8px;width:auto">
                                <?php foreach ($roleLabels as $k => $l): ?><option value="<?= $k ?>" <?= $u['role'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                            </select>
                        </form>
                    <?php endif; ?>
                </td>
                <td><?= (int)$u['bookings'] ?></td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td><?= $u['status'] === 'active' ? badge('Active', 'success') : badge('Blocked', 'danger') ?></td>
                <td>
                    <?php if ($u['id'] != $admin['id']): ?>
                        <form method="post" data-confirm="<?= $u['status'] === 'active' ? 'Block' : 'Unblock' ?> <?= e($u['full_name']) ?>?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="f_role" value="<?= e($role) ?>"><input type="hidden" name="f_q" value="<?= e($search) ?>">
                            <button class="btn btn-sm <?= $u['status'] === 'active' ? 'btn-danger' : 'btn-primary' ?>"><?= $u['status'] === 'active' ? 'Block' : 'Unblock' ?></button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/../includes/dash_footer.php'; ?>
