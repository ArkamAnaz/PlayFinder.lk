<?php
require_once __DIR__ . '/includes/init.php';

/** Only allow redirecting to pages inside this site */
function safe_next(?string $next): ?string
{
    if (!$next) return null;
    $next = preg_replace('#^' . preg_quote(BASE_URL, '#') . '/#', '', $next);
    return preg_match('#^[a-z0-9_\-/]+\.php(\?[^\s<>"]*)?$#i', $next) ? $next : null;
}

if (is_logged_in()) redirect(dashboard_url(current_user()['role']));

if (isset($_GET['next'])) $_SESSION['redirect_after_login'] = $_GET['next'];

$errors = [];
if (is_post()) {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $u = q_one('SELECT * FROM users WHERE email = ?', [$email]);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        $errors[] = 'Incorrect email or password.';
    } elseif ($u['status'] !== 'active') {
        $errors[] = 'Your account has been blocked. Please contact PlayFinder.lk support.';
    } else {
        login_user($u);
        flash('success', 'Welcome back, ' . explode(' ', $u['full_name'])[0] . '!');
        $next = safe_next($_SESSION['redirect_after_login'] ?? null);
        unset($_SESSION['redirect_after_login']);
        redirect($next ?? dashboard_url($u['role']));
    }
}

$pageTitle = 'Log in';
$activeNav = 'login';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-side">
        <div class="emoji-row">🏸⚽🏏🏓</div>
        <h2>Welcome back to PlayFinder.lk</h2>
        <p>Log in to book courts, manage your games and view booking history.</p>
    </div>
    <div class="auth-form">
        <h1>Log in</h1>
        <p class="muted">Don't have an account? <a href="<?= url('register.php') ?>">Sign up free</a></p>

        <?php if ($errors): ?><div class="alert alert-danger"><?= e($errors[0]) ?></div><?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="flex" style="flex-wrap:nowrap">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline btn-sm" data-show-password="#password">Show</button>
                </div>
            </div>
            <button class="btn btn-primary btn-block">Log in</button>
        </form>

        <div class="demo-box">
            <strong>Demo accounts</strong><br>
            Player: <code>user@playfinder.lk</code> / <code>User@123</code><br>
            Owner: <code>owner@playfinder.lk</code> / <code>Owner@123</code><br>
            Admin: <code>admin@playfinder.lk</code> / <code>Admin@123</code>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
