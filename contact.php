<?php
require_once __DIR__ . '/includes/init.php';

$errors = [];
$u = current_user();
if (is_post()) {
    verify_csrf();
    $data = [
        'name'    => trim($_POST['name'] ?? ''),
        'email'   => trim($_POST['email'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
    ];
    if (mb_strlen($data['name']) < 2)                       $errors[] = 'Please enter your name.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (mb_strlen($data['subject']) < 3)                    $errors[] = 'Please enter a subject.';
    if (mb_strlen($data['message']) < 10)                   $errors[] = 'Message must be at least 10 characters.';

    if (!$errors) {
        q('INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, ?)',
          [mb_substr($data['name'], 0, 100), mb_substr($data['email'], 0, 150), mb_substr($data['subject'], 0, 150), $data['message'], date('Y-m-d H:i:s')]);
        flash('success', 'Thank you! Your message has been sent. We will get back to you soon.');
        redirect('contact.php');
    }
}

$pageTitle = 'Contact';
$activeNav = 'contact';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <h1>Contact us</h1>
        <p>Questions, feedback or want to list your facility? Send us a message.</p>
    </div>
</section>

<div class="container section">
    <div class="detail-layout">
        <div class="card">
            <h3>Send a message</h3>
            <?php if ($errors): ?>
                <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>
            <form method="post">
                <?= csrf_field() ?>
                <div class="grid-2">
                    <div class="form-group"><label for="name">Your name</label><input type="text" id="name" name="name" value="<?= e(old('name', $u['full_name'] ?? '')) ?>" required></div>
                    <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e(old('email', $u['email'] ?? '')) ?>" required></div>
                </div>
                <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" value="<?= e(old('subject')) ?>" required></div>
                <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" required><?= e(old('message')) ?></textarea></div>
                <button class="btn btn-primary">Send message</button>
            </form>
        </div>
        <aside>
            <div class="card">
                <h3>Get in touch</h3>
                <ul class="info-list">
                    <li><span>📧</span><span>hello@playfinder.lk</span></li>
                    <li><span>📞</span><span>+94 11 234 5678</span></li>
                    <li><span>📍</span><span>Colombo, Sri Lanka</span></li>
                    <li><span>🕒</span><span>Mon – Sat, 9:00 AM – 6:00 PM</span></li>
                </ul>
            </div>
            <div class="card">
                <iframe class="map-embed" src="<?= e(maps_embed_url(MAP_DEFAULT_LAT, MAP_DEFAULT_LNG, 12)) ?>" loading="lazy" title="Colombo map"></iframe>
            </div>
        </aside>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
