<?php
/** Add / edit a facility. Used by facility owners (their own) and admin (any). */
require_once __DIR__ . '/../includes/init.php';
$user = require_role('owner', 'admin');
$isAdmin = $user['role'] === 'admin';

$id = (int)($_GET['id'] ?? 0);
$facility = null;
if ($id) {
    $facility = q_one('SELECT * FROM facilities WHERE id = ?', [$id]);
    if (!$facility || (!$isAdmin && $facility['owner_id'] != $user['id'])) {
        flash('danger', 'Facility not found.');
        redirect($isAdmin ? 'admin/facilities.php' : 'owner/facilities.php');
    }
}

$AMENITIES = ['Parking', 'Changing Rooms', 'Showers', 'Air Conditioned', 'Cafeteria', 'Equipment Rental',
              'Coaching', 'Bowling Machine', 'Spectator Seating', 'Drinking Water', 'First Aid', 'Wi-Fi'];
$owners = $isAdmin ? q_all('SELECT id, full_name, email FROM users WHERE role = ? ORDER BY full_name', ['owner']) : [];

$f = $facility ?? [
    'name' => '', 'description' => '', 'address' => '', 'area' => '', 'latitude' => '', 'longitude' => '',
    'phone' => $user['phone'], 'open_time' => '06:00:00', 'close_time' => '22:00:00', 'amenities' => '', 'image' => null,
    'owner_id' => $isAdmin ? '' : $user['id'],
];
$errors = [];

if (is_post()) {
    verify_csrf();
    $f = array_merge($f, [
        'name'        => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'address'     => trim($_POST['address'] ?? ''),
        'area'        => $_POST['area'] ?? '',
        'latitude'    => trim($_POST['latitude'] ?? ''),
        'longitude'   => trim($_POST['longitude'] ?? ''),
        'phone'       => preg_replace('/\s+/', '', $_POST['phone'] ?? ''),
        'open_time'   => sprintf('%02d:00:00', (int)($_POST['open_hour'] ?? 6)),
        'close_time'  => sprintf('%02d:00:00', (int)($_POST['close_hour'] ?? 22)),
        'amenities'   => implode(',', array_intersect($_POST['amenities'] ?? [], $AMENITIES)),
    ]);
    if ($isAdmin) $f['owner_id'] = (int)($_POST['owner_id'] ?? 0);

    if (mb_strlen($f['name']) < 3)                    $errors[] = 'Facility name is required.';
    if (mb_strlen($f['address']) < 5)                 $errors[] = 'Address is required.';
    if (!in_array($f['area'], AREAS, true))           $errors[] = 'Please select an area.';
    if (!preg_match('/^0\d{9}$/', $f['phone']))       $errors[] = 'Phone number must be 10 digits.';
    if (!is_numeric($f['latitude']) || !is_numeric($f['longitude'])
        || $f['latitude'] < 5.8 || $f['latitude'] > 10 || $f['longitude'] < 79.4 || $f['longitude'] > 82) {
        $errors[] = 'Please set a valid location inside Sri Lanka (latitude ≈ 6.9, longitude ≈ 79.8).';
    }
    if ($f['close_time'] <= $f['open_time'])          $errors[] = 'Closing time must be after opening time.';
    if ($isAdmin && !in_array($f['owner_id'], array_column($owners, 'id'))) $errors[] = 'Please select the facility owner.';

    if (!$errors) {
        try {
            $newImage = save_uploaded_image('image');
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }

    if (!$errors) {
        $image = $f['image'];
        if ($newImage || !empty($_POST['remove_image'])) {
            if ($image && is_file(UPLOAD_DIR . $image)) @unlink(UPLOAD_DIR . $image);
            $image = $newImage;
        }
        $values = [$f['name'], $f['description'], $f['address'], $f['area'], $f['latitude'], $f['longitude'],
                   $f['phone'], $f['open_time'], $f['close_time'], $f['amenities'], $image];

        if ($facility) {
            q('UPDATE facilities SET name=?, description=?, address=?, area=?, latitude=?, longitude=?, phone=?,
               open_time=?, close_time=?, amenities=?, image=?, owner_id=? WHERE id=?',
               array_merge($values, [$f['owner_id'], $facility['id']]));
            flash('success', 'Facility updated.');
            redirect($isAdmin ? 'admin/facilities.php' : 'owner/facilities.php');
        } else {
            // Owner submissions need admin approval; admin-created facilities go live immediately
            q('INSERT INTO facilities (name, description, address, area, latitude, longitude, phone, open_time, close_time,
               amenities, image, owner_id, status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
               array_merge($values, [$f['owner_id'], $isAdmin ? 'approved' : 'pending', date('Y-m-d H:i:s')]));
            $newId = db()->lastInsertId();
            flash('success', 'Facility created! Now add the courts players can book.'
                . ($isAdmin ? '' : ' It will be visible after admin approval.'));
            redirect('owner/courts.php?facility=' . $newId);
        }
    }
}

$pageTitle = $facility ? 'Edit Facility' : 'Add Facility';
$activeNav = 'facilities';
require __DIR__ . '/../includes/dash_header.php';
$amenSelected = amenities_list($f['amenities']);
$lat = is_numeric($f['latitude']) ? (float)$f['latitude'] : MAP_DEFAULT_LAT;
$lng = is_numeric($f['longitude']) ? (float)$f['longitude'] : MAP_DEFAULT_LNG;
?>
<?php if ($errors): ?>
    <div class="alert alert-danger"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="detail-layout" style="grid-template-columns:minmax(0,1fr) 420px">
    <?= csrf_field() ?>
    <div>
        <div class="card">
            <h3>Basic details</h3>
            <?php if ($isAdmin): ?>
                <div class="form-group">
                    <label>Facility owner</label>
                    <select name="owner_id" required>
                        <option value="">— Select owner —</option>
                        <?php foreach ($owners as $o): ?>
                            <option value="<?= $o['id'] ?>" <?= $f['owner_id'] == $o['id'] ? 'selected' : '' ?>><?= e($o['full_name'] . ' (' . $o['email'] . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="form-group"><label>Facility name</label><input type="text" name="name" value="<?= e($f['name']) ?>" maxlength="120" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" maxlength="2000"><?= e($f['description']) ?></textarea></div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Area</label>
                    <select name="area" required>
                        <option value="">— Select area —</option>
                        <?php foreach (AREAS as $a): ?><option <?= $f['area'] === $a ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Contact phone</label><input type="tel" name="phone" value="<?= e($f['phone']) ?>" placeholder="0112345678" required></div>
            </div>
            <div class="form-group"><label>Street address</label><input type="text" name="address" value="<?= e($f['address']) ?>" maxlength="255" required></div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Opens at</label>
                    <select name="open_hour">
                        <?php for ($h = 0; $h <= 23; $h++): ?>
                            <option value="<?= $h ?>" <?= (int)substr($f['open_time'], 0, 2) === $h ? 'selected' : '' ?>><?= time_label("$h:00") ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Closes at</label>
                    <select name="close_hour">
                        <?php for ($h = 1; $h <= 23; $h++): ?>
                            <option value="<?= $h ?>" <?= (int)substr($f['close_time'], 0, 2) === $h ? 'selected' : '' ?>><?= time_label("$h:00") ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Amenities</h3>
            <div class="check-grid">
                <?php foreach ($AMENITIES as $a): ?>
                    <label><input type="checkbox" name="amenities[]" value="<?= e($a) ?>" <?= in_array($a, $amenSelected, true) ? 'checked' : '' ?>> <?= e($a) ?></label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h3>Photo</h3>
            <?php if ($f['image'] && facility_image($f)): ?>
                <img src="<?= e(facility_image($f)) ?>" alt="" style="max-height:160px;border-radius:10px;display:block;margin-bottom:10px">
                <label style="font-weight:500"><input type="checkbox" name="remove_image" value="1"> Remove current photo</label>
            <?php endif; ?>
            <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
            <div class="hint">JPG, PNG or WEBP, max <?= MAX_UPLOAD_MB ?> MB. If no photo is uploaded a sport icon is shown.</div>
        </div>
    </div>

    <aside class="detail-side">
        <div class="card">
            <h3>Location on map</h3>
            <p class="muted small">
                <?= maps_enabled() ? 'Click on the map to place the pin.' : 'Open Google Maps, right-click your venue and click the coordinates to copy them, then paste below.' ?>
            </p>
            <?php if (maps_enabled()): ?>
                <div id="map" class="map-embed"></div>
            <?php else: ?>
                <iframe id="mapFrame" class="map-embed" src="<?= e(maps_embed_url($lat, $lng, 15)) ?>" title="Location preview"></iframe>
            <?php endif; ?>
            <div class="grid-2 mt-2">
                <div class="form-group"><label>Latitude</label><input type="text" name="latitude" id="lat" value="<?= e($f['latitude']) ?>" placeholder="6.9010" required></div>
                <div class="form-group"><label>Longitude</label><input type="text" name="longitude" id="lng" value="<?= e($f['longitude']) ?>" placeholder="79.8730" required></div>
            </div>
            <button type="button" class="btn btn-outline btn-sm btn-block" id="useMyLocation">📍 Use my current location</button>
        </div>
        <div class="card">
            <button class="btn btn-primary btn-block"><?= $facility ? 'Save changes' : 'Create facility' ?></button>
            <a href="<?= url($isAdmin ? 'admin/facilities.php' : 'owner/facilities.php') ?>" class="btn btn-outline btn-block mt-1">Cancel</a>
        </div>
    </aside>
</form>

<?php ob_start(); ?>
<script>
const latIn = document.getElementById('lat'), lngIn = document.getElementById('lng');
const frame = document.getElementById('mapFrame');
let gMap = null, gMarker = null;

function updatePreview() {
    const lat = parseFloat(latIn.value), lng = parseFloat(lngIn.value);
    if (isNaN(lat) || isNaN(lng)) return;
    if (frame) frame.src = `https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed`;
    if (gMarker) { gMarker.setPosition({ lat, lng }); gMap.panTo({ lat, lng }); }
}
latIn.addEventListener('change', updatePreview);
lngIn.addEventListener('change', updatePreview);

// Accept pasted "6.9012, 79.8601" in the latitude box
latIn.addEventListener('paste', ev => {
    const txt = (ev.clipboardData || window.clipboardData).getData('text');
    const m = txt.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);
    if (m) { ev.preventDefault(); latIn.value = m[1]; lngIn.value = m[2]; updatePreview(); }
});

document.getElementById('useMyLocation').addEventListener('click', () => {
    navigator.geolocation?.getCurrentPosition(p => {
        latIn.value = p.coords.latitude.toFixed(7);
        lngIn.value = p.coords.longitude.toFixed(7);
        updatePreview();
    }, () => alert('Could not get your location.'));
});

function initMap() {
    const pos = { lat: <?= $lat ?>, lng: <?= $lng ?> };
    gMap = new google.maps.Map(document.getElementById('map'), { center: pos, zoom: 14, streetViewControl: false });
    gMarker = new google.maps.Marker({ position: pos, map: gMap, draggable: true });
    const set = ll => { latIn.value = ll.lat().toFixed(7); lngIn.value = ll.lng().toFixed(7); gMarker.setPosition(ll); };
    gMap.addListener('click', ev => set(ev.latLng));
    gMarker.addListener('dragend', ev => set(ev.latLng));
}
</script>
<?= maps_script_tag('initMap') ?>
<?php $pageScripts = ob_get_clean();
require __DIR__ . '/../includes/dash_footer.php';
