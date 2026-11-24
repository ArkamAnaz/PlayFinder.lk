<?php
/**
 * Booking logic: slot availability, double-booking prevention, cancellation.
 * Used by both the booking page and the AJAX slots API so the rules are identical.
 */

/** Court with its facility & sport details (only active courts of approved facilities) */
function get_bookable_court(int $courtId): ?array
{
    return q_one(
        'SELECT c.*, s.name AS sport_name, s.icon, f.name AS facility_name, f.id AS facility_id,
                f.open_time, f.close_time, f.area, f.address, f.owner_id
         FROM courts c
         JOIN facilities f ON f.id = c.facility_id
         JOIN sports s ON s.id = c.sport_id
         WHERE c.id = ? AND c.is_active = 1 AND f.status = ?',
        [$courtId, 'approved']
    );
}

/** Check a date string is valid and inside the allowed booking window */
function validate_booking_date(string $date): ?string
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if (!$d || $d->format('Y-m-d') !== $date) {
        return 'Please choose a valid date.';
    }
    $today = date('Y-m-d');
    $max = date('Y-m-d', strtotime('+' . MAX_DAYS_AHEAD . ' days'));
    if ($date < $today) return 'You cannot book a date in the past.';
    if ($date > $max)   return 'Bookings can only be made up to ' . MAX_DAYS_AHEAD . ' days in advance.';
    return null;
}

/** Confirmed bookings for a court on a date: [['start_time'=>..,'end_time'=>..], ...] */
function booked_ranges(int $courtId, string $date): array
{
    return q_all(
        'SELECT start_time, end_time FROM bookings
         WHERE court_id = ? AND booking_date = ? AND status = ?',
        [$courtId, $date, 'confirmed']
    );
}

/**
 * Build the list of 1-hour slots for a court on a date.
 * Each slot: ['start' => '18:00', 'end' => '19:00', 'label' => '6:00 PM', 'status' => available|booked|past]
 */
function get_day_slots(array $court, string $date): array
{
    $open  = (int)substr($court['open_time'], 0, 2);
    $close = (int)substr($court['close_time'], 0, 2);
    $booked = booked_ranges((int)$court['id'], $date);
    $now = time();
    $slots = [];

    for ($h = $open; $h < $close; $h++) {
        $start = sprintf('%02d:00:00', $h);
        $end   = sprintf('%02d:00:00', $h + 1);
        $status = 'available';

        if (strtotime("$date $start") <= $now) {
            $status = 'past';
        } else {
            foreach ($booked as $b) {
                if ($b['start_time'] < $end && $b['end_time'] > $start) {
                    $status = 'booked';
                    break;
                }
            }
        }
        $slots[] = [
            'start'  => substr($start, 0, 5),
            'end'    => substr($end, 0, 5),
            'label'  => time_label($start),
            'status' => $status,
        ];
    }
    return $slots;
}

function generate_booking_ref(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $ref = 'PF-';
        for ($i = 0; $i < 6; $i++) {
            $ref .= $chars[random_int(0, strlen($chars) - 1)];
        }
    } while (q_val('SELECT COUNT(*) FROM bookings WHERE booking_ref = ?', [$ref]) > 0);
    return $ref;
}

/**
 * Create a booking after validating everything on the server.
 * Uses a DB transaction + row lock on the court so two people can never
 * book the same slot at the same moment (double-booking prevention).
 *
 * @return string booking reference
 * @throws RuntimeException with a user-friendly message
 */
function create_booking(int $userId, int $courtId, string $date, string $startHHMM, int $hours, string $notes = ''): string
{
    $court = get_bookable_court($courtId);
    if (!$court) throw new RuntimeException('This court is not available for booking.');

    if ($err = validate_booking_date($date)) throw new RuntimeException($err);

    if (!preg_match('/^([01]\d|2[0-3]):00$/', $startHHMM)) {
        throw new RuntimeException('Please select a valid start time.');
    }
    if ($hours < 1 || $hours > MAX_BOOKING_HOURS) {
        throw new RuntimeException('You can book between 1 and ' . MAX_BOOKING_HOURS . ' hours at a time.');
    }

    $startH = (int)substr($startHHMM, 0, 2);
    $endH   = $startH + $hours;
    $open   = (int)substr($court['open_time'], 0, 2);
    $close  = (int)substr($court['close_time'], 0, 2);
    if ($startH < $open || $endH > $close) {
        throw new RuntimeException('Selected time is outside the facility opening hours ('
            . time_label($court['open_time']) . ' - ' . time_label($court['close_time']) . ').');
    }

    $start = sprintf('%02d:00:00', $startH);
    $end   = sprintf('%02d:00:00', $endH);
    if (strtotime("$date $start") <= time()) {
        throw new RuntimeException('That time has already passed. Please pick a later slot.');
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Lock the court row until commit (MySQL InnoDB)
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            q('SELECT id FROM courts WHERE id = ? FOR UPDATE', [$courtId]);
        }

        // Overlap check: an existing booking overlaps if it starts before our end and ends after our start
        $clash = q_val(
            'SELECT COUNT(*) FROM bookings
             WHERE court_id = ? AND booking_date = ? AND status = ?
               AND start_time < ? AND end_time > ?',
            [$courtId, $date, 'confirmed', $end, $start]
        );
        if ($clash > 0) {
            throw new RuntimeException('Sorry, one or more of the selected slots are already booked. Please choose another time.');
        }

        $ref = generate_booking_ref();
        q(
            'INSERT INTO bookings (booking_ref, user_id, court_id, booking_date, start_time, end_time, hours, total_price, status, notes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$ref, $userId, $courtId, $date, $start, $end, $hours,
             $court['price_per_hour'] * $hours, 'confirmed', mb_substr(trim($notes), 0, 255) ?: null, date('Y-m-d H:i:s')]
        );
        $pdo->commit();
        return $ref;
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex instanceof RuntimeException ? $ex : new RuntimeException('Booking failed. Please try again.');
    }
}

/** upcoming | completed | cancelled  (completed is derived from the time, no cron job needed) */
function booking_state(array $b): string
{
    if ($b['status'] === 'cancelled') return 'cancelled';
    return strtotime($b['booking_date'] . ' ' . $b['end_time']) <= time() ? 'completed' : 'upcoming';
}

function booking_badge(array $b): string
{
    $state = booking_state($b);
    $map = ['upcoming' => 'info', 'completed' => 'success', 'cancelled' => 'danger'];
    return badge(ucfirst($state), $map[$state]);
}

/** Players can cancel up to CANCEL_LIMIT_HOURS before start. Owners/admin can cancel any upcoming booking. */
function can_cancel(array $b, string $role): bool
{
    if (booking_state($b) !== 'upcoming') return false;
    if ($role === 'admin' || $role === 'owner') return true;
    return strtotime($b['booking_date'] . ' ' . $b['start_time']) - time() >= CANCEL_LIMIT_HOURS * 3600;
}

function cancel_booking(int $bookingId, string $by): void
{
    q('UPDATE bookings SET status = ?, cancelled_by = ?, cancelled_at = ? WHERE id = ?',
      ['cancelled', $by, date('Y-m-d H:i:s'), $bookingId]);
}

/** Common SELECT for booking lists */
function booking_select_sql(): string
{
    return 'SELECT b.*, c.name AS court_name, c.facility_id, s.name AS sport_name, s.icon,
                   f.name AS facility_name, f.area, f.address, f.phone AS facility_phone, f.owner_id,
                   f.latitude, f.longitude,
                   u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone
            FROM bookings b
            JOIN courts c ON c.id = b.court_id
            JOIN sports s ON s.id = c.sport_id
            JOIN facilities f ON f.id = c.facility_id
            JOIN users u ON u.id = b.user_id';
}
