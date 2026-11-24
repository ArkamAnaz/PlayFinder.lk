<?php
/** Public site header. Set $pageTitle and optional $activeNav before including. */
$user = current_user();
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(isset($pageTitle) ? "$pageTitle | " . SITE_NAME : SITE_NAME . ' - Book Indoor Sports Courts in Colombo') ?></title>
    <meta name="description" content="Search and book badminton, futsal, cricket nets, table tennis and basketball courts across Colombo.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏸</text></svg>">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a href="<?= url('index.php') ?>" class="logo">Play<span>Finder</span>.lk</a>

        <button class="nav-toggle" aria-label="Open menu" data-toggle="#mainNav">☰</button>

        <nav class="main-nav" id="mainNav">
            <a href="<?= url('index.php') ?>" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Home</a>
            <a href="<?= url('facilities.php') ?>" class="<?= $activeNav === 'facilities' ? 'active' : '' ?>">Find Courts</a>
            <a href="<?= url('about.php') ?>" class="<?= $activeNav === 'about' ? 'active' : '' ?>">About</a>
            <a href="<?= url('contact.php') ?>" class="<?= $activeNav === 'contact' ? 'active' : '' ?>">Contact</a>

            <div class="nav-auth">
            <?php if ($user): ?>
                <div class="dropdown">
                    <button class="user-chip" data-toggle="#userMenu">
                        <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></span>
                        <?= e(explode(' ', $user['full_name'])[0]) ?> ▾
                    </button>
                    <div class="dropdown-menu" id="userMenu">
                        <?php if ($user['role'] === 'admin'): ?>
                            <a href="<?= url('admin/index.php') ?>">Admin Dashboard</a>
                        <?php elseif ($user['role'] === 'owner'): ?>
                            <a href="<?= url('owner/index.php') ?>">Owner Dashboard</a>
                        <?php endif; ?>
                        <a href="<?= url('my-bookings.php') ?>">My Bookings</a>
                        <a href="<?= url('profile.php') ?>">My Profile</a>
                        <a href="<?= url('logout.php') ?>" class="text-danger">Log out</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= url('login.php') ?>" class="<?= $activeNav === 'login' ? 'active' : '' ?>">Log in</a>
                <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">Sign up</a>
            <?php endif; ?>
            </div>
        </nav>
    </div>
</header>

<main>
<?php $flashes = get_flashes(); if ($flashes): ?>
    <div class="container flash-wrap">
        <?php foreach ($flashes as $flashItem): ?>
            <div class="alert alert-<?= e($flashItem['type']) ?>"><?= e($flashItem['message']) ?><button class="alert-close" aria-label="Close">×</button></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
