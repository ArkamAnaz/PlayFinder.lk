<?php
/**
 * General helper functions
 */

/** Escape output for HTML (prevents XSS) */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Build a URL relative to the project root */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* ---------------- Flash messages ---------------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------------- CSRF protection ---------------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if (!hash_equals(csrf_token(), $_POST['csrf'] ?? '')) {
        http_response_code(419);
        exit('Session expired. Please go back, refresh the page and try again.');
    }
}

/* ---------------- Old form input ---------------- */
function old(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

/* ---------------- Formatting ---------------- */
function lkr($amount): string
{
    return 'Rs. ' . number_format((float)$amount, 0);
}

/** '14:00:00' -> '2:00 PM' */
function time_label(string $time): string
{
    return date('g:i A', strtotime($time));
}

function date_label(string $date): string
{
    return date('D, d M Y', strtotime($date));
}

/* ---------------- Facility helpers ---------------- */
function facility_image(array $f): ?string
{
    if (!empty($f['image']) && is_file(UPLOAD_DIR . $f['image'])) {
        return UPLOAD_URL . rawurlencode($f['image']);
    }
    return null;
}

/** Image block: uploaded photo or a coloured placeholder with a sport emoji */
function facility_cover(array $f, string $class = 'cover'): string
{
    $img = facility_image($f);
    if ($img) {
        return '<div class="' . $class . '" style="background-image:url(\'' . e($img) . '\')"></div>';
    }
    $palette = ['#0f766e', '#1d4ed8', '#7c3aed', '#b45309', '#be123c', '#047857', '#0369a1'];
    $color = $palette[$f['id'] % count($palette)];
    $icon = $f['icon'] ?? '🏟️';
    return '<div class="' . $class . ' cover-placeholder" style="--c:' . $color . '"><span>' . e($icon) . '</span></div>';
}

function amenities_list(?string $amenities): array
{
    return array_filter(array_map('trim', explode(',', (string)$amenities)));
}

/** Distance in km between two lat/lng points (Haversine formula) */
function distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/* ---------------- Google Maps helpers ---------------- */
function maps_enabled(): bool
{
    return GOOGLE_MAPS_API_KEY !== '';
}

/** Keyless embed URL (works without an API key) */
function maps_embed_url(float $lat, float $lng, int $zoom = 15): string
{
    return 'https://maps.google.com/maps?q=' . $lat . ',' . $lng . '&z=' . $zoom . '&output=embed';
}

function maps_directions_url(float $lat, float $lng): string
{
    return 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng;
}

function maps_script_tag(string $callback = 'initMap'): string
{
    if (!maps_enabled()) return '';
    return '<script async src="https://maps.googleapis.com/maps/api/js?key=' . e(GOOGLE_MAPS_API_KEY)
        . '&callback=' . e($callback) . '"></script>';
}

/* ---------------- Status badges ---------------- */
function badge(string $text, string $type): string
{
    return '<span class="badge badge-' . e($type) . '">' . e($text) . '</span>';
}

function facility_status_badge(string $status): string
{
    $map = ['approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger'];
    return badge(ucfirst($status), $map[$status] ?? 'muted');
}

/* ---------------- Image upload ---------------- */
/**
 * Validates and saves an uploaded image. Returns new filename, null if no file,
 * or throws RuntimeException with a user-friendly message.
 */
function save_uploaded_image(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }
    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        throw new RuntimeException('Image must be smaller than ' . MAX_UPLOAD_MB . ' MB.');
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0775, true);
    }
    $name = 'fac_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) {
        throw new RuntimeException('Could not save the image.');
    }
    return $name;
}

/* ---------------- Sports list (cached per request) ---------------- */
function all_sports(): array
{
    static $sports = null;
    if ($sports === null) {
        $sports = q_all('SELECT * FROM sports ORDER BY name');
    }
    return $sports;
}

/* ---------------- Facility search ---------------- */
/**
 * Search approved facilities.
 * $filters: q (keyword), area, sport (id), limit
 * Returns facilities with: sports (comma list), min_price, icon
 */
function search_facilities(array $filters = []): array
{
    $where = ['f.status = ?', 'c.is_active = 1'];
    $params = ['approved'];

    if (!empty($filters['q'])) {
        $where[] = '(f.name LIKE ? OR f.address LIKE ? OR f.area LIKE ?)';
        $kw = '%' . $filters['q'] . '%';
        array_push($params, $kw, $kw, $kw);
    }
    if (!empty($filters['area'])) {
        $where[] = 'f.area = ?';
        $params[] = $filters['area'];
    }
    if (!empty($filters['sport'])) {
        // only count courts of the chosen sport (so min price is for that sport)
        $where[] = 'c.sport_id = ?';
        $params[] = (int)$filters['sport'];
    }

    $sql = 'SELECT f.*, MIN(c.price_per_hour) AS min_price,
                   GROUP_CONCAT(DISTINCT s.name) AS sports, MIN(s.icon) AS icon
            FROM facilities f
            JOIN courts c ON c.facility_id = f.id
            JOIN sports s ON s.id = c.sport_id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY f.id
            ORDER BY f.name';
    if (!empty($filters['limit'])) {
        $sql .= ' LIMIT ' . (int)$filters['limit'];
    }
    return q_all($sql, $params);
}
