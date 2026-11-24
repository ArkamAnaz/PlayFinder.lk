<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_role('owner');

$pageTitle = 'Bookings';
$activeNav = 'bookings';
require __DIR__ . '/../includes/dash_header.php';

$scopeSql = 'f.owner_id = ?';
$scopeParams = [$user['id']];
$selfPage = 'owner/bookings.php';
$cancelRole = 'owner';
require __DIR__ . '/../includes/bookings_table.php';

require __DIR__ . '/../includes/dash_footer.php';
