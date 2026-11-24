</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a href="<?= url('index.php') ?>" class="logo logo-light">Play<span>Finder</span>.lk</a>
            <p class="muted-light">Colombo's one-stop platform to find and book indoor sports courts online — no more phone calls.</p>
        </div>
        <div>
            <h4>Explore</h4>
            <a href="<?= url('facilities.php') ?>">Find Courts</a>
            <a href="<?= url('about.php') ?>">About Us</a>
            <a href="<?= url('contact.php') ?>">Contact</a>
        </div>
        <div>
            <h4>Sports</h4>
            <?php foreach (all_sports() as $s): ?>
                <a href="<?= url('facilities.php?sport=' . $s['id']) ?>"><?= e($s['icon'] . ' ' . $s['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <div>
            <h4>Facility Owners</h4>
            <a href="<?= url('register.php?type=owner') ?>">List your facility</a>
            <a href="<?= url('login.php') ?>">Owner login</a>
        </div>
    </div>
    <div class="container footer-bottom">
        © <?= date('Y') ?> PlayFinder.lk — Final Year Project, BSc Computer Science (Software Engineering)
    </div>
</footer>

<script src="<?= url('assets/js/main.js') ?>"></script>
<?= $pageScripts ?? '' ?>
</body>
</html>
