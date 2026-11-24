<?php
/**
 * Shared bookings list with filters (used by owner/bookings.php and admin/bookings.php).
 * Expects: $scopeSql (extra WHERE, e.g. 'f.owner_id = ?'), $scopeParams, $selfPage, $cancelRole
 */
$fDate   = $_GET['date'] ?? '';
$fStatus = $_GET['status'] ?? '';
$fSearch = trim($_GET['q'] ?? '');

$where = [$scopeSql ?: '1 = 1'];
$params = $scopeParams;
if ($fDate && DateTime::createFromFormat('Y-m-d', $fDate)) {
    $where[] = 'b.booking_date = ?';
    $params[] = $fDate;
}
$now = date('Y-m-d H:i:s');
$today = date('Y-m-d');
$nowTime = date('H:i:s');
if ($fStatus === 'cancelled') {
    $where[] = 'b.status = ?'; $params[] = 'cancelled';
} elseif ($fStatus === 'upcoming') {
    $where[] = 'b.status = ? AND (b.booking_date > ? OR (b.booking_date = ? AND b.end_time > ?))';
    array_push($params, 'confirmed', $today, $today, $nowTime);
} elseif ($fStatus === 'completed') {
    $where[] = 'b.status = ? AND (b.booking_date < ? OR (b.booking_date = ? AND b.end_time <= ?))';
    array_push($params, 'confirmed', $today, $today, $nowTime);
}
if ($fSearch !== '') {
    $where[] = '(b.booking_ref LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ? OR f.name LIKE ?)';
    $kw = "%$fSearch%";
    array_push($params, $kw, $kw, $kw, $kw);
}

$bookings = q_all(booking_select_sql() . ' WHERE ' . implode(' AND ', $where)
                  . ' ORDER BY b.booking_date DESC, b.start_time DESC LIMIT 300', $params);
$sum = array_sum(array_map(fn($b) => $b['status'] === 'confirmed' ? $b['total_price'] : 0, $bookings));
$backUrl = $selfPage . '?' . http_build_query(array_filter(['date' => $fDate, 'status' => $fStatus, 'q' => $fSearch]));
?>
<form class="card flex" method="get" style="padding:14px">
    <input type="search" name="q" value="<?= e($fSearch) ?>" placeholder="Ref, customer, phone, facility…" style="flex:2;min-width:180px">
    <input type="date" name="date" value="<?= e($fDate) ?>" style="flex:1;min-width:150px">
    <select name="status" style="flex:1;min-width:140px">
        <option value="">All statuses</option>
        <?php foreach (['upcoming', 'completed', 'cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm">Filter</button>
    <a href="<?= url($selfPage) ?>" class="btn btn-outline btn-sm">Reset</a>
</form>

<p class="muted mt-2"><?= count($bookings) ?> booking(s) · confirmed value <strong><?= lkr($sum) ?></strong></p>

<div class="table-wrap">
    <table>
        <tr><th>Ref</th><th>Date & time</th><th>Facility / court</th><th>Customer</th><th>Amount</th><th>Status</th><th></th></tr>
        <?php if (!$bookings): ?><tr><td colspan="7" class="text-center muted" style="padding:30px">No bookings found.</td></tr><?php endif; ?>
        <?php foreach ($bookings as $b): ?>
            <tr>
                <td><a href="<?= url('booking.php?ref=' . urlencode($b['booking_ref'])) ?>"><strong><?= e($b['booking_ref']) ?></strong></a></td>
                <td><?= date('d M Y', strtotime($b['booking_date'])) ?><span class="sub"><?= time_label($b['start_time']) ?> – <?= time_label($b['end_time']) ?></span></td>
                <td><?= e($b['facility_name']) ?><span class="sub"><?= e($b['icon'] . ' ' . $b['court_name']) ?></span></td>
                <td><?= e($b['user_name']) ?><span class="sub"><?= e($b['user_phone']) ?></span></td>
                <td><?= lkr($b['total_price']) ?></td>
                <td><?= booking_badge($b) ?><?php if ($b['cancelled_by']): ?><span class="sub">by <?= e($b['cancelled_by']) ?></span><?php endif; ?></td>
                <td>
                    <?php if (can_cancel($b, $cancelRole)): ?>
                        <form method="post" action="<?= url('cancel-booking.php') ?>" data-confirm="Cancel booking <?= e($b['booking_ref']) ?>? The customer should be informed.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                            <input type="hidden" name="back" value="<?= e($backUrl) ?>">
                            <button class="btn btn-danger btn-sm">Cancel</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
