<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) redirect(dashboard_url(current_user()['role']));

$type = ($_POST['account_type'] ?? $_GET['type'] ?? 'user') === 'owner' ? 'owner' : 'user';
$errors = [];

if (is_post()) {
    verify_csrf();
    $name  = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password_confirm'] ?? '';

    if (mb_strlen($name) < 3)                         $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Please enter a valid email address.';
    if (!preg_match('/^0\d{9}$/', $phone))            $errors[] = 'Phone number must be 10 digits, e.g. 0771234567.';
    if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass))
                                                      $errors[] = 'Password must be at least 8 characters and include letters and numbers.';
    if ($pass !== $pass2)                             $errors[] = 'Passwords do not match.';
    if (empty($_POST['terms']))                       $errors[] = 'Please accept the terms to continue.';
    if (!$errors && q_val('SELECT COUNT(*) FROM users WHERE email = ?', [$email])) {
        $errors[] = 'An account with this email already exists. Try logging in.';
    }

    if (!$errors) {
        q('INSERT INTO users (full_name, email, phone, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, ?)',
          [$name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), $type, date('Y-m-d H:i:s')]);
        $u = q_one('SELECT * FROM users WHERE email = ?', [$email]);
        login_user($u);
        if ($type === 'owner') {
            flash('success', 'Account created! Add your first facility — it will go live after admin approval.');
            redirect('owner/facility-form.php');
        }
        flash('success', 'Welcome to PlayFinder.lk, ' . explode(' ', $name)[0] . '! Find a court and book your first game.');
        redirect('facilities.php');
    }
}

$pageTitle = 'Sign up';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
    <div class="auth-side">
        <div class="emoji-row">🏀🏸⚽</div>
        <h2>Join Colombo's sports community</h2>
        <p>Players book courts in seconds. Facility owners list their venues and accept online bookings — free.</p>
    </div>
    <div class="auth-form">
        <h1>Create account</h1>
        <p class="muted">Already registered? <a href="<?= url('login.php') ?>">Log in</a></p>

        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="form-group">
                <label>I want to…</label>
                <div class="radio-cards">
                    <label><input type="radio" name="account_type" value="user" <?= $type === 'user' ? 'checked' : '' ?>><strong>🏃 Book courts</strong><span class="muted small">Player account</span></label>
                    <label><input type="radio" name="account_type" value="owner" <?= $type === 'owner' ? 'checked' : '' ?>><strong>🏟️ List my facility</strong><span class="muted small">Facility owner</span></label>
                </div>
            </div>
            <div class="form-group">
                <label for="full_name">Full name</label>
                <input type="text" id="full_name" name="full_name" value="<?= e(old('full_name')) ?>" required autocomplete="name">
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="phone">Mobile number</label>
                    <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>" placeholder="07XXXXXXXX" required autocomplete="tel">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password">
                    <div class="hint">Min 8 characters, letters + numbers</div>
                </div>
                <div class="form-group">
                    <label for="password_confirm">Confirm password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                </div>
            </div>
            <div class="form-group">
                <label style="font-weight:500;display:flex;gap:8px;align-items:center">
                    <input type="checkbox" name="terms" value="1" <?= old('terms') ? 'checked' : '' ?>> I agree to the terms of use and privacy policy
                </label>
            </div>
            <button class="btn btn-primary btn-block">Create account</button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
