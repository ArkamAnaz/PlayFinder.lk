<?php
require_once __DIR__ . '/includes/init.php';

$activeNav = 'facilities';
$pageTitle = 'Find Courts';

$filters = [
    'q'     => trim($_GET['q'] ?? ''),
    'area'  => in_array($_GET['area'] ?? '', AREAS, true) ? $_GET['area'] : '',
    'sport' => (int)($_GET['sport'] ?? 0),
];
$sort = $_GET['sort'] ?? 'name';
$lat = isset($_GET['lat']) && is_numeric($_GET['lat']) ? (float)$_GET['lat'] : null;
$lng = isset($_GET['lng']) && is_numeric($_GET['lng']) ? (float)$_GET['lng'] : null;

$facilities = search_facilities($filters);

// distance from user (if location shared)
if ($lat !== null && $lng !== null) {
    foreach ($facilities as &$f) {
        $f['distance'] = distance_km($lat, $lng, (float)$f['latitude'], (float)$f['longitude']);
    }
    unset($f);
}

// sorting
usort($facilities, function ($a, $b) use ($sort) {
    return match ($sort) {
        'price_asc'  => $a['min_price'] <=> $b['min_price'],
        'price_desc' => $b['min_price'] <=> $a['min_price'],
        'distance'   => ($a['distance'] ?? 0) <=> ($b['distance'] ?? 0),
        default      => strcasecmp($a['name'], $b['name']),
    };
});

$sportName = '';
foreach (all_sports() as $s) if ($s['id'] == $filters['sport']) $sportName = $s['name'];

// data for the map
$markers = array_map(fn($f) => [
    'id' => (int)$f['id'], 'name' => $f['name'], 'area' => $f['area'],
    'lat' => (float)$f['latitude'], 'lng' => (float)$f['longitude'],
    'price' => lkr($f['min_price']), 'sports' => $f['sports'],
    'url' => url('facility.php?id=' . $f['id']),
], $facilities);

require __DIR__ . '/includes/header.php';
?>
<section class="page-head" style="padding-bottom:60px">
    <div class="container">
        <h1><?= $sportName ? e($sportName) . ' venues' : 'Find a court' ?><?= $filters['area'] ? ' in ' . e($filters['area']) : ' in Colombo' ?></h1>
        <p>Search by sport and area, view venues on the map and book online.</p>
    </div>
</section>

<div class="container">
    <form class="filter-bar" method="get" id="filterForm">
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search facility name or address…">
        <select name="sport">
            <option value="">All sports</option>
            <?php foreach (all_sports() as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $filters['sport'] == $s['id'] ? 'selected' : '' ?>><?= e($s['icon'] . ' ' . $s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="area">
            <option value="">All areas</option>
            <?php foreach (AREAS as $a): ?>
                <option <?= $filters['area'] === $a ? 'selected' : '' ?>><?= e($a) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="sort">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Sort: Name</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low → High</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High → Low</option>
            <?php if ($lat !== null): ?>
                <option value="distance" <?= $sort === 'distance' ? 'selected' : '' ?>>Nearest first</option>
            <?php endif; ?>
        </select>
        <?php if ($lat !== null): ?>
            <input type="hidden" name="lat" value="<?= e($lat) ?>"><input type="hidden" name="lng" value="<?= e($lng) ?>">
        <?php endif; ?>
        <button class="btn btn-primary">Search</button>
    </form>

    <div class="search-layout">
        <div>
            <div class="flex between results-count">
                <span><strong><?= count($facilities) ?></strong> facilit<?= count($facilities) === 1 ? 'y' : 'ies' ?> found</span>
                <span class="flex">
                    <button type="button" class="btn btn-outline btn-sm" id="nearMe">📍 Near me</button>
                    <?php if ($filters['q'] || $filters['area'] || $filters['sport'] || $lat !== null): ?>
                        <a href="<?= url('facilities.php') ?>" class="btn btn-outline btn-sm">Clear filters</a>
                    <?php endif; ?>
                </span>
            </div>

            <?php if (!$facilities): ?>
                <div class="empty-state">
                    <div class="icon">🔍</div>
                    <h3>No facilities match your search</h3>
                    <p class="muted">Try a different area or sport.</p>
                    <a href="<?= url('facilities.php') ?>" class="btn btn-primary">Show all facilities</a>
                </div>
            <?php else: ?>
                <div class="facility-grid">
                    <?php foreach ($facilities as $f) include __DIR__ . '/includes/facility_card.php'; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="map-panel">
            <?php if (maps_enabled()): ?>
                <div id="map"></div>
            <?php else:
                $first = $facilities[0] ?? null;
                $src = $first ? maps_embed_url((float)$first['latitude'], (float)$first['longitude'], 14)
                              : maps_embed_url(MAP_DEFAULT_LAT, MAP_DEFAULT_LNG, 12); ?>
                <iframe id="mapFrame" src="<?= e($src) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Facility map"></iframe>
                <div class="map-note">👆 Click a facility card to show it on the map.</div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php
ob_start(); ?>
<script>
const MARKERS = <?= json_encode($markers, JSON_HEX_TAG | JSON_HEX_AMP) ?>;
const USER_POS = <?= $lat !== null ? json_encode(['lat' => $lat, 'lng' => $lng]) : 'null' ?>;

// "Near me" - uses browser geolocation, then reloads sorted by distance
document.getElementById('nearMe').addEventListener('click', () => {
    if (!navigator.geolocation) return alert('Your browser does not support location.');
    const btn = document.getElementById('nearMe');
    btn.textContent = 'Locating…';
    navigator.geolocation.getCurrentPosition(pos => {
        const params = new URLSearchParams(new FormData(document.getElementById('filterForm')));
        params.set('lat', pos.coords.latitude.toFixed(6));
        params.set('lng', pos.coords.longitude.toFixed(6));
        params.set('sort', 'distance');
        location.search = params.toString();
    }, () => { btn.textContent = '📍 Near me'; alert('Could not get your location. Please allow location access.'); });
});

function highlightCard(id) {
    document.querySelectorAll('.facility-card').forEach(c => c.classList.toggle('highlight', c.dataset.id == id));
}

// Keyless map: clicking a card moves the embedded map to that facility
const frame = document.getElementById('mapFrame');
if (frame) {
    document.querySelectorAll('.facility-card').forEach(card => {
        card.addEventListener('click', ev => {
            if (ev.target.closest('a')) return;
            frame.src = `https://maps.google.com/maps?q=${card.dataset.lat},${card.dataset.lng}&z=15&output=embed`;
            highlightCard(card.dataset.id);
        });
        card.style.cursor = 'pointer';
    });
}

// Full Google Maps JavaScript API (when an API key is configured)
function initMap() {
    const map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: <?= MAP_DEFAULT_LAT ?>, lng: <?= MAP_DEFAULT_LNG ?> }, zoom: 12, mapTypeControl: false, streetViewControl: false,
    });
    const info = new google.maps.InfoWindow();
    const bounds = new google.maps.LatLngBounds();
    const markerById = {};

    MARKERS.forEach(m => {
        const marker = new google.maps.Marker({ position: { lat: m.lat, lng: m.lng }, map, title: m.name });
        markerById[m.id] = marker;
        bounds.extend(marker.getPosition());
        marker.addListener('click', () => {
            const div = document.createElement('div');
            div.innerHTML = '<strong></strong><br><small></small><br><span></span><br><a>View & book →</a>';
            div.querySelector('strong').textContent = m.name;
            div.querySelector('small').textContent = m.area + ' · ' + m.sports;
            div.querySelector('span').textContent = m.price + ' / hour onwards';
            div.querySelector('a').href = m.url;
            info.setContent(div);
            info.open(map, marker);
            highlightCard(m.id);
            document.getElementById('fac-' + m.id)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    if (USER_POS) {
        new google.maps.Marker({ position: USER_POS, map, title: 'You are here',
            icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8, fillColor: '#2563eb', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 } });
        bounds.extend(USER_POS);
    }
    if (MARKERS.length > 1 || USER_POS) map.fitBounds(bounds);
    else if (MARKERS.length === 1) { map.setCenter(bounds.getCenter()); map.setZoom(15); }

    document.querySelectorAll('.facility-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', ev => {
            if (ev.target.closest('a')) return;
            const mk = markerById[card.dataset.id];
            if (mk) { map.panTo(mk.getPosition()); map.setZoom(15); google.maps.event.trigger(mk, 'click'); }
        });
    });
}
</script>
<?= maps_script_tag('initMap') ?>
<?php $pageScripts = ob_get_clean();
require __DIR__ . '/includes/footer.php';
