<?php
require_once __DIR__ . '/includes/init.php';

$courtId = (int)($_GET['court'] ?? $_POST['court_id'] ?? 0);
$court = get_bookable_court($courtId);
if (!$court) {
    flash('danger', 'That court is not available for booking.');
    redirect('facilities.php');
}

/* ---------- Handle booking submission ---------- */
if (is_post()) {
    verify_csrf();
    $user = require_login();
    $date  = $_POST['date'] ?? '';
    $start = $_POST['start'] ?? '';
    $hours = (int)($_POST['hours'] ?? 0);
    try {
        $ref = create_booking((int)$user['id'], $courtId, $date, $start, $hours, $_POST['notes'] ?? '');
        flash('success', 'Booking confirmed! Your reference is ' . $ref . '.');
        redirect('booking.php?ref=' . urlencode($ref));
    } catch (RuntimeException $ex) {
        flash('danger', $ex->getMessage());
        redirect('book.php?court=' . $courtId . '&date=' . urlencode($date));
    }
}

$date = $_GET['date'] ?? date('Y-m-d');
if (validate_booking_date($date)) $date = date('Y-m-d');

// Other courts in this facility (to switch quickly)
$otherCourts = q_all('SELECT c.id, c.name, c.price_per_hour, s.name AS sport_name, s.icon FROM courts c
                      JOIN sports s ON s.id = c.sport_id WHERE c.facility_id = ? AND c.is_active = 1 ORDER BY s.name, c.name',
                      [$court['facility_id']]);

$slots = get_day_slots($court, $date);
$user = current_user();

$pageTitle = 'Book ' . $court['name'] . ' – ' . $court['facility_name'];
$activeNav = 'facilities';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <div class="breadcrumb"><a href="<?= url('facilities.php') ?>">Facilities</a> / <a href="<?= url('facility.php?id=' . $court['facility_id']) ?>"><?= e($court['facility_name']) ?></a> / Book</div>
        <h1><?= e($court['icon'] . ' ' . $court['name']) ?></h1>
        <p><?= e($court['facility_name']) ?> · <?= e($court['area']) ?> · <?= lkr($court['price_per_hour']) ?> per hour</p>
    </div>
</section>

<div class="container section" style="padding-top:28px">
    <form method="post" id="bookingForm" class="booking-layout">
        <?= csrf_field() ?>
        <input type="hidden" name="court_id" value="<?= (int)$court['id'] ?>">
        <input type="hidden" name="date" id="dateInput" value="<?= e($date) ?>">
        <input type="hidden" name="start" id="startInput">
        <input type="hidden" name="hours" id="hoursInput">

        <div>
            <div class="card">
                <div class="card-title">
                    <h3>1. Choose court</h3>
                </div>
                <select id="courtSelect" aria-label="Court">
                    <?php foreach ($otherCourts as $oc): ?>
                        <option value="<?= $oc['id'] ?>" <?= $oc['id'] == $court['id'] ? 'selected' : '' ?>>
                            <?= e($oc['icon'] . ' ' . $oc['sport_name'] . ' — ' . $oc['name'] . ' (' . lkr($oc['price_per_hour']) . '/hr)') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="card">
                <div class="card-title">
                    <h3>2. Pick a date</h3>
                    <input type="date" id="datePicker" style="width:auto" value="<?= e($date) ?>"
                           min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . MAX_DAYS_AHEAD . ' days')) ?>">
                </div>
                <div class="date-strip" id="dateStrip">
                    <?php for ($i = 0; $i < 14; $i++):
                        $d = date('Y-m-d', strtotime("+$i days")); ?>
                        <button type="button" class="date-pill <?= $d === $date ? 'active' : '' ?>" data-date="<?= $d ?>">
                            <small><?= $i === 0 ? 'Today' : date('D', strtotime($d)) ?></small>
                            <strong><?= date('j', strtotime($d)) ?></strong>
                            <small><?= date('M', strtotime($d)) ?></small>
                        </button>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-title">
                    <h3>3. Select time</h3>
                    <span class="muted small">Click a start time, then click a later slot to book up to <?= MAX_BOOKING_HOURS ?> hours</span>
                </div>
                <div class="legend">
                    <span><i></i>Available</span><span><i class="l-sel"></i>Selected</span><span><i class="l-booked"></i>Booked</span><span><i style="opacity:.4"></i>Passed</span>
                </div>
                <div class="slot-grid" id="slotGrid">
                    <?php foreach ($slots as $s): ?>
                        <button type="button" class="slot <?= $s['status'] ?>" data-start="<?= $s['start'] ?>" <?= $s['status'] !== 'available' ? 'disabled' : '' ?>>
                            <strong><?= e($s['label']) ?></strong>
                            <small><?= $s['status'] === 'available' ? 'Available' : ($s['status'] === 'booked' ? 'Booked' : '—') ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p class="muted small mt-2" id="noSlots" style="display:<?= array_filter($slots, fn($s) => $s['status'] === 'available') ? 'none' : 'block' ?>">
                    No available slots on this day. Please choose another date or court.
                </p>
            </div>
        </div>

        <aside class="detail-side">
            <div class="card">
                <h3>Booking summary</h3>
                <div class="summary-row"><span class="muted">Facility</span><strong><?= e($court['facility_name']) ?></strong></div>
                <div class="summary-row"><span class="muted">Court</span><strong><?= e($court['name']) ?></strong></div>
                <div class="summary-row"><span class="muted">Date</span><strong id="sumDate"><?= date_label($date) ?></strong></div>
                <div class="summary-row"><span class="muted">Time</span><strong id="sumTime">—</strong></div>
                <div class="summary-row"><span class="muted">Duration</span><strong id="sumHours">—</strong></div>
                <div class="summary-total"><span>Total</span><span id="sumTotal">Rs. 0</span></div>
                <p class="muted small mb-0">Pay at the venue. Free cancellation up to <?= CANCEL_LIMIT_HOURS ?> hours before.</p>

                <?php if ($user): ?>
                    <div class="form-group mt-2">
                        <label for="notes">Note for the venue (optional)</label>
                        <input type="text" name="notes" id="notes" maxlength="255" placeholder="e.g. Need 2 rackets">
                    </div>
                    <button class="btn btn-primary btn-block" id="confirmBtn" disabled>Confirm booking</button>
                <?php else: ?>
                    <a href="<?= url('login.php?next=' . urlencode('book.php?court=' . $court['id'] . '&date=' . $date)) ?>" class="btn btn-primary btn-block mt-2">Log in to book</a>
                    <p class="small muted text-center">New here? <a href="<?= url('register.php') ?>">Create a free account</a></p>
                <?php endif; ?>
            </div>
        </aside>
    </form>
</div>

<?php ob_start(); ?>
<script>
(() => {
    const API = <?= json_encode(url('api/slots.php')) ?>;
    const COURT = <?= (int)$court['id'] ?>;
    const PRICE = <?= (float)$court['price_per_hour'] ?>;
    const MAX_HOURS = <?= MAX_BOOKING_HOURS ?>;
    const grid = document.getElementById('slotGrid');
    const confirmBtn = document.getElementById('confirmBtn');
    let selStart = null, selHours = 0;

    const fmt = n => 'Rs. ' + Number(n).toLocaleString('en-LK');
    const label = hhmm => { let [h] = hhmm.split(':').map(Number); const ap = h >= 12 ? 'PM' : 'AM'; h = h % 12 || 12; return h + ':00 ' + ap; };
    const slots = () => [...grid.querySelectorAll('.slot')];

    function render() {
        const list = slots();
        const startIdx = list.findIndex(b => b.dataset.start === selStart);
        list.forEach((b, i) => b.classList.toggle('selected', startIdx >= 0 && i >= startIdx && i < startIdx + selHours));
        document.getElementById('startInput').value = selStart || '';
        document.getElementById('hoursInput').value = selHours || '';
        if (selStart) {
            const endH = parseInt(selStart) + selHours;
            document.getElementById('sumTime').textContent = label(selStart) + ' – ' + label(String(endH).padStart(2, '0') + ':00');
            document.getElementById('sumHours').textContent = selHours + ' hour' + (selHours > 1 ? 's' : '');
            document.getElementById('sumTotal').textContent = fmt(PRICE * selHours);
        } else {
            document.getElementById('sumTime').textContent = '—';
            document.getElementById('sumHours').textContent = '—';
            document.getElementById('sumTotal').textContent = 'Rs. 0';
        }
        if (confirmBtn) confirmBtn.disabled = !selStart;
    }

    grid.addEventListener('click', ev => {
        const btn = ev.target.closest('.slot');
        if (!btn || btn.disabled) return;
        const list = slots();
        const i = list.indexOf(btn);
        const startIdx = list.findIndex(b => b.dataset.start === selStart);

        if (selStart && i === startIdx && selHours === 1) { selStart = null; selHours = 0; }       // deselect
        else if (selStart && i > startIdx && i - startIdx < MAX_HOURS
                 && list.slice(startIdx, i + 1).every(b => !b.disabled)) { selHours = i - startIdx + 1; } // extend range
        else if (selStart && i > startIdx && i - startIdx >= MAX_HOURS) {
            alert('You can book a maximum of ' + MAX_HOURS + ' consecutive hours.'); return;
        }
        else { selStart = btn.dataset.start; selHours = 1; }                                       // new start
        render();
    });

    async function loadSlots(date) {
        selStart = null; selHours = 0; render();
        grid.innerHTML = '<p class="muted">Loading availability…</p>';
        try {
            const res = await fetch(`${API}?court=${COURT}&date=${date}`);
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Could not load slots');
            grid.innerHTML = '';
            data.slots.forEach(s => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'slot ' + s.status;
                b.dataset.start = s.start;
                b.disabled = s.status !== 'available';
                b.innerHTML = `<strong>${s.label}</strong><small>${s.status === 'available' ? 'Available' : s.status === 'booked' ? 'Booked' : '—'}</small>`;
                grid.appendChild(b);
            });
            document.getElementById('noSlots').style.display = data.slots.some(s => s.status === 'available') ? 'none' : 'block';
        } catch (e) {
            grid.innerHTML = `<p class="text-danger">${e.message}</p>`;
        }
    }

    function setDate(date) {
        document.getElementById('dateInput').value = date;
        document.getElementById('datePicker').value = date;
        document.querySelectorAll('.date-pill').forEach(p => p.classList.toggle('active', p.dataset.date === date));
        document.getElementById('sumDate').textContent = new Date(date + 'T00:00').toLocaleDateString('en-GB', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
        history.replaceState(null, '', `?court=${COURT}&date=${date}`);
        loadSlots(date);
    }

    document.getElementById('dateStrip').addEventListener('click', ev => {
        const p = ev.target.closest('.date-pill'); if (p) setDate(p.dataset.date);
    });
    document.getElementById('datePicker').addEventListener('change', ev => ev.target.value && setDate(ev.target.value));
    document.getElementById('courtSelect').addEventListener('change', ev => {
        location.href = `?court=${ev.target.value}&date=${document.getElementById('dateInput').value}`;
    });
    document.getElementById('bookingForm').addEventListener('submit', ev => {
        if (!selStart) { ev.preventDefault(); alert('Please select a time slot.'); }
        else if (confirmBtn) { confirmBtn.disabled = true; confirmBtn.textContent = 'Booking…'; }
    });
    render();
})();
</script>
<?php $pageScripts = ob_get_clean();
require __DIR__ . '/includes/footer.php';
