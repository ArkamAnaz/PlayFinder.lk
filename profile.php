<?php
require_once __DIR__ . '/includes/init.php';
$user = require_login();

$errors = [];
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $name  = trim($_POST['full_name'] ?? '');
        $phone = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
        if (mb_strlen($name) < 3)              $errors[] = 'Please enter your full name.';
        if (!preg_match('/^0\d{9}$/', $phone)) $errors[] = 'Phone number must be 10 digits, e.g. 0771234567.';
        if (!$errors) {
            q('UPDATE users SET full_name = ?, phone = ? WHERE id = ?', [$name, $phone, $user['id']]);
            flash('success', 'Profile updated.');
            redirect('profile.php');
        }
    }

    if ($action === 'password') {
        $hash = q_val('SELECT password_hash FROM users WHERE id = ?', [$user['id']]);
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $hash)) $errors[] = 'Current password is incorrect.';
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new))
            $errors[] = 'New password must be at least 8 characters and include letters and numbers.';
        if ($new !== ($_POST['confirm_password'] ?? '')) $errors[] = 'New passwords do not match.';
        if (!$errors) {
            q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
    }
}

$pageTitle = 'My Profile';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <h1>My profile</h1>
        <p><?= e($user['email']) ?> · <?= e(ucfirst($user['role'] === 'user' ? 'player' : $user['role'])) ?> account</p>
    </div>
</section>
<div class="container section" style="padding-top:28px;max-width:820px">
    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="card">
        <h3>Personal details</h3>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="profile">
            <div class="grid-2">
                <div class="form-group"><label>Full name</label><input type="text" name="full_name" value="<?= e($user['full_name']) ?>" required></div>
                <div class="form-group"><label>Mobile number</label><input type="tel" name="phone" value="<?= e($user['phone']) ?>" required></div>
            </div>
            <div class="form-group"><label>Email</label><input type="email" value="<?= e($user['email']) ?>" disabled><div class="hint">Email cannot be changed.</div></div>
            <button class="btn btn-primary">Save changes</button>
        </form>
    </div>

    <div class="card">
        <h3>Change password</h3>
        <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="password">
            <div class="form-group"><label>Current password</label><input type="password" name="current_password" required autocomplete="current-password"></div>
            <div class="grid-2">
                <div class="form-group"><label>New password</label><input type="password" name="new_password" required autocomplete="new-password"></div>
                <div class="form-group"><label>Confirm new password</label><input type="password" name="confirm_password" required autocomplete="new-password"></div>
            </div>
            <button class="btn btn-dark">Update password</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
