<?php
/**
 * PlayFinder.lk - Main configuration
 * Change these values to match your XAMPP setup.
 */

// ---- Database (XAMPP default: user "root", empty password) ----
defined('DB_HOST') || define('DB_HOST', 'localhost');
defined('DB_NAME') || define('DB_NAME', 'playfinder_db');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');
defined('DB_DSN')  || define('DB_DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

// ---- Site ----
define('SITE_NAME', 'PlayFinder.lk');
// Folder name inside htdocs. If you put the project at htdocs/playfinder -> '/playfinder'
defined('BASE_URL') || define('BASE_URL', '/playfinder');

// ---- Google Maps ----
// Paste your Google Maps JavaScript API key here to enable the interactive map
// (markers, info windows, click-to-pick location).
// If left empty, the system automatically uses the keyless Google Maps embed.
define('GOOGLE_MAPS_API_KEY', '');
define('MAP_DEFAULT_LAT', 6.9010);   // Colombo centre
define('MAP_DEFAULT_LNG', 79.8730);

// ---- Booking rules ----
define('MAX_BOOKING_HOURS', 4);     // max consecutive hours in one booking
define('MAX_DAYS_AHEAD', 30);       // how far in advance users can book
define('CANCEL_LIMIT_HOURS', 2);    // users can cancel up to X hours before start

// ---- Uploads ----
define('UPLOAD_DIR', __DIR__ . '/../uploads/facilities/');
define('UPLOAD_URL', BASE_URL . '/uploads/facilities/');
define('MAX_UPLOAD_MB', 2);

// ---- Areas covered (Colombo + suburbs) ----
define('AREAS', [
    'Colombo 01', 'Colombo 02', 'Colombo 03', 'Kollupitiya', 'Colombo 05', 'Wellawatte',
    'Colombo 07', 'Borella', 'Colombo 10', 'Colombo 13', 'Colombo 15',
    'Dehiwala', 'Mount Lavinia', 'Nugegoda', 'Rajagiriya', 'Battaramulla',
    'Kotte', 'Maharagama', 'Kottawa', 'Wattala', 'Kelaniya', 'Moratuwa',
]);

date_default_timezone_set('Asia/Colombo');
