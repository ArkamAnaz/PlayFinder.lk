<?php
require_once __DIR__ . '/includes/init.php';

$activeNav = 'home';
$featured = search_facilities(['limit' => 6]);

$stats = [
    'facilities' => q_val('SELECT COUNT(*) FROM facilities WHERE status = ?', ['approved']),
    'courts'     => q_val('SELECT COUNT(*) FROM courts c JOIN facilities f ON f.id = c.facility_id WHERE c.is_active = 1 AND f.status = ?', ['approved']),
    'areas'      => q_val('SELECT COUNT(DISTINCT area) FROM facilities WHERE status = ?', ['approved']),
];

// number of facilities offering each sport
$sportCounts = [];
foreach (q_all('SELECT c.sport_id, COUNT(DISTINCT c.facility_id) AS n FROM courts c
                JOIN facilities f ON f.id = c.facility_id
                WHERE c.is_active = 1 AND f.status = ? GROUP BY c.sport_id', ['approved']) as $r) {
    $sportCounts[$r['sport_id']] = $r['n'];
}

require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="hero-tag">🇱🇰 Colombo's indoor sports booking platform</span>
            <h1>Find a court.<br>Book it in <em>seconds.</em></h1>
            <p class="lead">Search badminton courts, futsal pitches, cricket nets and more across Colombo, see them on the map, check live availability and book online — no phone calls.</p>

            <form class="search-box" action="<?= url('facilities.php') ?>" method="get">
                <select name="sport" aria-label="Sport">
                    <option value="">All sports</option>
                    <?php foreach (all_sports() as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= e($s['icon'] . ' ' . $s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="area" aria-label="Area">
                    <option value="">All areas</option>
                    <?php foreach (AREAS as $a): ?><option><?= e($a) ?></option><?php endforeach; ?>
                </select>
                <input type="search" name="q" placeholder="Facility name…" aria-label="Keyword">
                <button class="btn btn-primary">Search</button>
            </form>

            <div class="hero-stats">
                <div><strong><?= (int)$stats['facilities'] ?>+</strong><span>Facilities</span></div>
                <div><strong><?= (int)$stats['courts'] ?>+</strong><span>Courts</span></div>
                <div><strong><?= (int)$stats['areas'] ?></strong><span>Areas covered</span></div>
            </div>
        </div>
        <div class="hero-visual" aria-hidden="true">
            <div>🏸</div><div>⚽</div><div>🏏</div><div>🏓</div>
        </div>
    </div>
</section>

<section class="section" style="padding-top:30px">
    <div class="container">
        <div class="section-head">
            <div><div class="eyebrow">Browse by sport</div><h2>What do you want to play?</h2></div>
        </div>
        <div class="sport-grid">
            <?php foreach (all_sports() as $s): ?>
                <a class="sport-card" href="<?= url('facilities.php?sport=' . $s['id']) ?>">
                    <span class="icon"><?= e($s['icon']) ?></span>
                    <strong><?= e($s['name']) ?></strong>
                    <small><?= (int)($sportCounts[$s['id']] ?? 0) ?> venues</small>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:10px">
    <div class="container">
        <div class="section-head">
            <div><div class="eyebrow">Popular venues</div><h2>Featured facilities</h2></div>
            <a href="<?= url('facilities.php') ?>" class="btn btn-outline">View all →</a>
        </div>
        <div class="facility-grid">
            <?php foreach ($featured as $f) include __DIR__ . '/includes/facility_card.php'; ?>
        </div>
    </div>
</section>

<section class="section" style="padding-top:10px">
    <div class="container">
        <div class="section-head">
            <div><div class="eyebrow">How it works</div><h2>Book your game in 3 steps</h2></div>
        </div>
        <div class="steps">
            <div class="step"><span class="icon">🔎</span><h3>Search</h3><p class="muted">Filter by sport and area, or find venues near you on Google Maps.</p></div>
            <div class="step"><span class="icon">🗓️</span><h3>Pick a slot</h3><p class="muted">See real-time availability and choose 1–<?= MAX_BOOKING_HOURS ?> consecutive hours.</p></div>
            <div class="step"><span class="icon">✅</span><h3>Play</h3><p class="muted">Get an instant booking reference. Show it at the venue and pay there.</p></div>
        </div>
    </div>
</section>

<section class="container">
    <div class="cta-band">
        <div>
            <h2>Own a sports facility in Colombo?</h2>
            <p>List your courts on PlayFinder.lk and accept online bookings — free.</p>
        </div>
        <a href="<?= url('register.php?type=owner') ?>" class="btn btn-volt">List your facility →</a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
