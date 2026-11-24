<?php
/**
 * AJAX endpoint: returns 1-hour slot availability for a court on a date.
 * GET api/slots.php?court=1&date=2026-12-01
 */
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

$court = get_bookable_court((int)($_GET['court'] ?? 0));
$date = $_GET['date'] ?? '';

if (!$court) {
    http_response_code(404);
    echo json_encode(['error' => 'Court not found.']);
    exit;
}
if ($err = validate_booking_date($date)) {
    http_response_code(422);
    echo json_encode(['error' => $err]);
    exit;
}

echo json_encode([
    'court' => (int)$court['id'],
    'date'  => $date,
    'price' => (float)$court['price_per_hour'],
    'slots' => get_day_slots($court, $date),
]);
