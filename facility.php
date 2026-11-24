<?php
require_once __DIR__ . '/includes/init.php';

$id = (int)($_GET['id'] ?? 0);
$facility = q_one('SELECT f.*, u.full_name AS owner_name FROM facilities f JOIN users u ON u.id = f.owner_id WHERE f.id = ?', [$id]);

// Only approved facilities are public. Owners/admin can preview their own pending ones.
$u = current_user();
$canPreview = $facility && $u && ($u['role'] === 'admin' || ($u['role'] === 'owner' && $u['id'] == $facility['owner_id']));
if (!$facility || ($facility['status'] !== 'approved' && !$canPreview)) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container section"><div class="empty-state"><div class="icon">🏟️</div><h3>Facility not found</h3><a class="btn btn-primary" href="' . url('facilities.php') . '">Browse facilities</a></div></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$courts = q_all('SELECT c.*, s.name AS sport_name, s.icon FROM courts c JOIN sports s ON s.id = c.sport_id
                 WHERE c.facility_id = ? AND c.is_active = 1 ORDER BY s.name, c.price_per_hour, c.name', [$id]);
$bySport = [];
foreach ($courts as $c) $bySport[$c['sport_name']][] = $c;

$facility['icon'] = $courts[0]['icon'] ?? '🏟️';
$minPrice = $courts ? min(array_column($courts, 'price_per_hour')) : 0;
$bookingsDone = (int)q_val('SELECT COUNT(*) FROM bookings b JOIN courts c ON c.id = b.court_id WHERE c.facility_id = ? AND b.status = ?', [$id, 'confirmed']);

$pageTitle = $facility['name'];
$activeNav = 'facilities';
require __DIR__ . '/includes/header.php';
$lat = (float)$facility['latitude'];
$lng = (float)$facility['longitude'];
?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> / <a href="<?= url('facilities.php') ?>">Facilities</a> / <?= e($facility['name']) ?></div>
        <h1><?= e($facility['name']) ?></h1>
        <p>📍 <?= e($facility['address']) ?> · <?= e($facility['area']) ?></p>
    </div>
</section>

<div class="container section" style="padding-top:28px">
    <?php if ($facility['status'] !== 'approved'): ?>
        <div class="alert alert-warning">Preview only — this facility is <strong><?= e($facility['status']) ?></strong> and not visible to the public yet.</div>
    <?php endif; ?>

    <div class="detail-layout">
        <div>
            <?= facility_cover($facility, 'cover cover-lg') ?>

            <div class="card mt-3">
                <h3>About this venue</h3>
                <p><?= nl2br(e($facility['description'] ?: 'No description provided.')) ?></p>
                <?php if ($am = amenities_list($facility['amenities'])): ?>
                    <h4 class="mt-2">Amenities</h4>
                    <div class="facility-sports">
                        <?php foreach ($am as $a): ?><span class="chip">✔ <?= e($a) ?></span><?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-title"><h3>Courts & pricing</h3><span class="muted small">Prices per hour · pay at the venue</span></div>
                <?php if (!$courts): ?>
                    <p class="muted">No courts available yet.</p>
                <?php endif; ?>
                <?php foreach ($bySport as $sport => $list): ?>
                    <h4 class="mt-2 mb-0"><?= e($list[0]['icon'] . ' ' . $sport) ?></h4>
                    <?php foreach ($list as $c): ?>
                        <div class="court-row">
                            <div><strong><?= e($c['name']) ?></strong><div class="muted small"><?= e($sport) ?></div></div>
                            <div class="flex">
                                <span class="price"><?= lkr($c['price_per_hour']) ?><small> /hr</small></span>
                                <?php if ($facility['status'] === 'approved'): ?>
                                    <a href="<?= url('book.php?court=' . $c['id']) ?>" class="btn btn-primary btn-sm">Check availability</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>

            <div class="card">
                <div class="card-title">
                    <h3>Location</h3>
                    <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="<?= e(maps_directions_url($lat, $lng)) ?>">🧭 Get directions</a>
                </div>
                <?php if (maps_enabled()): ?>
                    <div id="map" class="map-embed"></div>
                <?php else: ?>
                    <iframe class="map-embed" src="<?= e(maps_embed_url($lat, $lng)) ?>" loading="lazy" title="Map of <?= e($facility['name']) ?>"></iframe>
                <?php endif; ?>
            </div>
        </div>

        <aside class="detail-side">
            <div class="card">
                <div class="muted small">Starting from</div>
                <div class="price" style="font-size:30px"><?= lkr($minPrice) ?><small> / hour</small></div>
                <?php if ($courts && $facility['status'] === 'approved'): ?>
                    <a href="<?= url('book.php?court=' . $courts[0]['id']) ?>" class="btn btn-primary btn-block mt-2">Book a court</a>
                <?php endif; ?>
                <ul class="info-list mt-2">
                    <li><span>🕒</span><span><?= time_label($facility['open_time']) ?> – <?= time_label($facility['close_time']) ?> (daily)</span></li>
                    <li><span>📞</span><span><a href="tel:<?= e($facility['phone']) ?>"><?= e($facility['phone']) ?></a></span></li>
                    <li><span>📍</span><span><?= e($facility['address']) ?></span></li>
                    <li><span>🏟️</span><span><?= count($courts) ?> court<?= count($courts) === 1 ? '' : 's' ?> · <?= count($bySport) ?> sport<?= count($bySport) === 1 ? '' : 's' ?></span></li>
                    <li><span>✅</span><span><?= $bookingsDone ?> bookings made on PlayFinder</span></li>
                </ul>
            </div>
            <div class="card">
                <h4>Booking policy</h4>
                <ul class="muted small" style="padding-left:18px;margin:0">
                    <li>Bookings are in 1-hour slots (max <?= MAX_BOOKING_HOURS ?> hours).</li>
                    <li>Book up to <?= MAX_DAYS_AHEAD ?> days in advance.</li>
                    <li>Free cancellation up to <?= CANCEL_LIMIT_HOURS ?> hours before start.</li>
                    <li>Payment is made at the venue.</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
<?php
if (maps_enabled()) {
    $pageScripts = '<script>function initMap(){const p={lat:' . $lat . ',lng:' . $lng . '};'
        . 'const m=new google.maps.Map(document.getElementById("map"),{center:p,zoom:15,mapTypeControl:false});'
        . 'new google.maps.Marker({position:p,map:m,title:' . json_encode($facility['name'], JSON_HEX_TAG) . '});}</script>'
        . maps_script_tag('initMap');
}
require __DIR__ . '/includes/footer.php';
