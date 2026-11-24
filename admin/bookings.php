<?php
require_once __DIR__ . '/../includes/init.php';
require_role('admin');

$pageTitle = 'All Bookings';
$activeNav = 'bookings';
require __DIR__ . '/../includes/dash_header.php';

$scopeSql = '';
$scopeParams = [];
$selfPage = 'admin/bookings.php';
$cancelRole = 'admin';
require __DIR__ . '/../includes/bookings_table.php';

require __DIR__ . '/../includes/dash_footer.php';
