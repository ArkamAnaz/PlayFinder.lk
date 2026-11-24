<?php
/** Dashboard layout (Admin + Owner). Set $pageTitle and $activeNav before including. */
$user = current_user();
$isAdmin = $user['role'] === 'admin';
$activeNav = $activeNav ?? '';

if ($isAdmin) {
    $pendingCount = (int)q_val('SELECT COUNT(*) FROM facilities WHERE status = ?', ['pending']);
    $unreadCount  = (int)q_val('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');
    $menu = [
        'dashboard'  => ['admin/index.php', '📊', 'Dashboard', 0],
        'facilities' => ['admin/facilities.php', '🏟️', 'Facilities', $pendingCount],
        'bookings'   => ['admin/bookings.php', '📅', 'Bookings', 0],
        'users'      => ['admin/users.php', '👥', 'Users', 0],
        'sports'     => ['admin/sports.php', '🏅', 'Sports', 0],
        'messages'   => ['admin/messages.php', '✉️', 'Messages', $unreadCount],
    ];
} else {
    $menu = [
        'dashboard'  => ['owner/index.php', '📊', 'Dashboard', 0],
        'facilities' => ['owner/facilities.php', '🏟️', 'My Facilities', 0],
        'bookings'   => ['owner/bookings.php', '📅', 'Bookings', 0],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Dashboard') . ' | ' . SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body class="dash-body">
<aside class="sidebar" id="sidebar">
    <a href="<?= url('index.php') ?>" class="logo logo-light">Play<span>Finder</span>.lk</a>
    <div class="sidebar-role"><?= $isAdmin ? 'Admin Panel' : 'Facility Owner' ?></div>
    <nav>
        <?php foreach ($menu as $key => [$href, $icon, $label, $count]): ?>
            <a href="<?= url($href) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>">
                <span><?= $icon ?></span> <?= e($label) ?>
                <?php if ($count > 0): ?><em class="count"><?= $count ?></em><?php endif; ?>
            </a>
        <?php endforeach; ?>
        <hr>
        <a href="<?= url('index.php') ?>"><span>🌐</span> View Website</a>
        <a href="<?= url('profile.php') ?>"><span>👤</span> My Profile</a>
        <a href="<?= url('logout.php') ?>"><span>🚪</span> Log out</a>
    </nav>
</aside>

<div class="dash-main">
    <header class="dash-top">
        <button class="nav-toggle dash-toggle" data-toggle="#sidebar" aria-label="Menu">☰</button>
        <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
        <div class="user-chip static">
            <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
            <?= e($user['full_name']) ?>
        </div>
    </header>
    <div class="dash-content">
    <?php foreach (get_flashes() as $flashItem): ?>
        <div class="alert alert-<?= e($flashItem['type']) ?>"><?= e($flashItem['message']) ?><button class="alert-close" aria-label="Close">×</button></div>
    <?php endforeach; ?>
