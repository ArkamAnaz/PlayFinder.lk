<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'About';
$activeNav = 'about';
require __DIR__ . '/includes/header.php';
?>
<section class="page-head">
    <div class="container">
        <h1>About PlayFinder.lk</h1>
        <p>A centralized web platform for finding and booking indoor sports facilities in Colombo.</p>
    </div>
</section>

<div class="container section">
    <div class="about-grid">
        <div>
            <div class="eyebrow">The problem</div>
            <h2>Booking a court shouldn't take five phone calls</h2>
            <p>Most indoor sports facilities in Colombo — badminton courts, futsal pitches, cricket nets and table tennis halls — still take bookings by phone, social media or walk-in. Players don't know which venues are nearby, which courts are free, or how to book them.</p>
            <p>Facility owners, meanwhile, struggle to manage schedules and avoid double bookings using notebooks and WhatsApp messages.</p>
        </div>
        <div>
            <div class="eyebrow">Our solution</div>
            <h2>Search, map, book — in one place</h2>
            <p>PlayFinder.lk brings Colombo's indoor sports venues onto a single platform. Search by sport and area, see venues on Google Maps, check real-time court availability and confirm a booking instantly. Our system validates every booking so the same slot can never be booked twice.</p>
            <a href="<?= url('facilities.php') ?>" class="btn btn-primary">Find a court</a>
        </div>
    </div>

    <div class="section-head mt-3" style="margin-top:60px">
        <div><div class="eyebrow">Features</div><h2>What you can do</h2></div>
    </div>
    <div class="feature-list" style="grid-template-columns:repeat(3,1fr)">
        <div class="feature"><span class="icon">🔎</span><h4>Smart search</h4><p>Filter by sport type, area and price, or sort by distance from you.</p></div>
        <div class="feature"><span class="icon">🗺️</span><h4>Google Maps</h4><p>See every venue on the map and get directions in one tap.</p></div>
        <div class="feature"><span class="icon">⏱️</span><h4>Live availability</h4><p>Hour-by-hour slot view for every court, updated in real time.</p></div>
        <div class="feature"><span class="icon">🛡️</span><h4>No double bookings</h4><p>Server-side validation with database locking prevents conflicts.</p></div>
        <div class="feature"><span class="icon">📋</span><h4>Booking history</h4><p>View upcoming and past games and cancel when plans change.</p></div>
        <div class="feature"><span class="icon">🏟️</span><h4>Owner dashboard</h4><p>Facility owners manage courts, prices and bookings online.</p></div>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
