# PlayFinder.lk – Sports Indoor Facility Booking System for Colombo

Final Year Project – BSc (Hons) Computer Science (Software Engineering)
Tech stack: **PHP 8 · MySQL/MariaDB · HTML · CSS · JavaScript · Google Maps · XAMPP · VS Code**

---

## 1. Setup on XAMPP (about 5 minutes)

1. Install **XAMPP** (PHP 8.0 or newer) and start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Copy the `playfinder` folder into `C:\xampp\htdocs\` so you get `C:\xampp\htdocs\playfinder\index.php`.
3. Open **http://localhost/phpmyadmin** → **Import** tab → choose `playfinder/database/playfinder.sql` → **Import**.
   This creates the database `playfinder_db` with all tables and demo data.
4. Open **http://localhost/playfinder** in your browser. Done ✅

> If you renamed the folder, change `BASE_URL` in `config/config.php` (e.g. `'/myproject'`).
> If your MySQL root user has a password, set `DB_PASS` in `config/config.php`.

### Demo accounts

| Role              | Email                 | Password   |
|-------------------|-----------------------|------------|
| Admin             | admin@playfinder.lk   | Admin@123  |
| Facility owner    | owner@playfinder.lk   | Owner@123  |
| Facility owner 2  | owner@playfinder.lk   | mohamed@123|
| Player (user)-Raza| user@playfinder.lk    | raza@123   |

Demo bookings use dates relative to the day you import the SQL file. Re-import to reset the demo.

### Google Maps

- **Without an API key (default):** maps use the keyless Google Maps embed. Facility pages show the location,
  the search page shows the selected facility (click a card), and "Get directions" opens Google Maps.
- **With an API key:** paste it into `GOOGLE_MAPS_API_KEY` in `config/config.php`. The search page then shows
  all facilities as markers with info windows, the "Near me" location as a blue dot, and owners can click
  the map to set their facility's location.
  (Get a key at Google Cloud Console → enable *Maps JavaScript API* → Credentials → API key.)

---

## 2. Features

**Players**
- Register / log in (passwords hashed with `password_hash`)
- Search facilities by **sport**, **area** and keyword; sort by price or **distance (Near me)**
- View facilities on **Google Maps**, get directions
- Facility page: description, amenities, courts & hourly prices, opening hours
- **Live slot availability** (1-hour slots) loaded with AJAX
- Book 1–4 consecutive hours, instant confirmation with booking reference (e.g. `PF-7K3M9Q`)
- **Double-booking prevention** (server-side validation + database row lock)
- Booking history: Upcoming / Past / Cancelled; cancel up to 2 hours before start
- Profile & password change

**Facility owners**
- Dashboard: today's schedule, upcoming bookings, monthly revenue, 7-day chart
- Add / edit facilities (location, hours, amenities, photo upload) → goes to admin for approval
- Manage courts: add, edit price, hide/activate, delete
- View & cancel bookings at their facilities

**Admin**
- Dashboard with platform statistics and bookings by sport
- Approve / reject / delete facilities, edit any facility and courts
- Manage users (change role, block/unblock)
- View & cancel all bookings with filters
- Manage sport types
- Read contact-form messages

---

## 3. Folder structure

```
playfinder/
├── index.php            Home page (search, sports, featured venues)
├── facilities.php       Search results + map
├── facility.php         Facility details
├── book.php             Booking page (date, court, time slots)
├── booking.php          Booking confirmation / ticket
├── cancel-booking.php   Cancel action (user / owner / admin)
├── my-bookings.php      Booking history
├── login.php, register.php, logout.php, profile.php
├── about.php, contact.php
├── api/slots.php        AJAX – slot availability JSON
├── owner/               Facility-owner panel (dashboard, facilities, courts, bookings)
├── admin/               Admin panel (dashboard, facilities, bookings, users, sports, messages)
├── includes/            init, db (PDO), auth, booking logic, helpers, layouts
├── config/config.php    Database, Google Maps key, booking rules
├── assets/css, assets/js
├── uploads/facilities/  Uploaded facility photos
└── database/playfinder.sql
```

## 4. Database design (6 tables)

- `users` (role: user / owner / admin, status: active / blocked)
- `sports`
- `facilities` → belongs to an owner (`owner_id`), status pending / approved / rejected, latitude & longitude
- `courts` → belongs to a facility and a sport, `price_per_hour`
- `bookings` → user + court + date + start/end time, status confirmed / cancelled
- `contact_messages`

Relationships: User 1—N Facility, Facility 1—N Court, Sport 1—N Court, Court 1—N Booking, User 1—N Booking.

## 5. How double booking is prevented (important for viva)

`includes/booking.php → create_booking()`

1. Validate on the server: date within 30 days, not in the past, start on the hour, inside opening hours, 1–4 hours.
2. Start a **transaction** and lock the court row with `SELECT ... FOR UPDATE`, so two requests for the same court are processed one after the other.
3. Check for overlap: an existing confirmed booking clashes if `start_time < new_end AND end_time > new_start`.
4. If no clash → insert booking and commit; otherwise roll back and show a friendly error.

The same slot logic (`get_day_slots()`) is used by the AJAX API and the booking page, so what the user sees matches what the server enforces.

## 6. Security measures

- PDO prepared statements (SQL injection)
- `htmlspecialchars` on all output (XSS)
- CSRF token on every form
- `password_hash` / `password_verify`, session ID regenerated at login
- Role-based access checks on every owner/admin page, ownership checks on facilities, courts and bookings
- Image upload: MIME check, size limit, random file names, scripts blocked in `uploads/`
- `config/`, `includes/`, `database/` blocked from direct web access (`.htaccess`)

## 7. Suggested test cases (for the report)

| ID | Test | Expected result |
|----|------|-----------------|
| TC-01 | Register with invalid email / short password | Validation errors shown |
| TC-02 | Log in with correct / wrong password | Dashboard / "Incorrect email or password" |
| TC-03 | Search Badminton in Wellawatte | Only matching facilities listed |
| TC-04 | Click "Near me" | Results sorted by distance |
| TC-05 | Open facility page | Details, courts and Google Map displayed |
| TC-06 | Book 2 free consecutive slots | Booking confirmed with reference |
| TC-07 | Book a slot that overlaps an existing booking | "Slots are already booked" error, no booking created |
| TC-08 | Book outside opening hours / past date / > 4 hours | Rejected with error message |
| TC-09 | Cancel booking more than 2h before start | Status becomes Cancelled, slot free again |
| TC-10 | Owner adds facility | Status Pending, not visible publicly |
| TC-11 | Admin approves facility | Facility visible in search |
| TC-12 | Player opens /admin/index.php | Access denied, redirected |
| TC-13 | Admin blocks user | User is logged out and cannot log in |
