<?php
/** Facility card partial. Expects $f (facility row with sports, min_price, optional distance). */
$sportsList = $f['sports'] ? explode(',', $f['sports']) : [];
?>
<article class="facility-card" id="fac-<?= (int)$f['id'] ?>" data-id="<?= (int)$f['id'] ?>"
         data-lat="<?= e($f['latitude']) ?>" data-lng="<?= e($f['longitude']) ?>">
    <a href="<?= url('facility.php?id=' . $f['id']) ?>"><?= facility_cover($f) ?></a>
    <div class="facility-body">
        <div class="flex between">
            <span class="facility-meta">📍 <?= e($f['area']) ?></span>
            <?php if (isset($f['distance'])): ?>
                <span class="distance"><?= number_format($f['distance'], 1) ?> km away</span>
            <?php endif; ?>
        </div>
        <h3><a href="<?= url('facility.php?id=' . $f['id']) ?>"><?= e($f['name']) ?></a></h3>
        <div class="facility-sports">
            <?php foreach ($sportsList as $sp): ?><span class="chip"><?= e($sp) ?></span><?php endforeach; ?>
        </div>
        <div class="facility-meta small">🕒 <?= time_label($f['open_time']) ?> – <?= time_label($f['close_time']) ?></div>
        <div class="facility-foot">
            <div class="price"><?= lkr($f['min_price']) ?> <small>/ hour onwards</small></div>
            <a href="<?= url('facility.php?id=' . $f['id']) ?>" class="btn btn-primary btn-sm">Book</a>
        </div>
    </div>
</article>
