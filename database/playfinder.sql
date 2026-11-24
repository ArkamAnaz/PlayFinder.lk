-- =====================================================================
--  PlayFinder.lk - Sports Indoor Facility Booking System (Colombo)
--  Database schema + demo data
--  Import this file in phpMyAdmin (XAMPP) -> Import tab
-- =====================================================================

DROP DATABASE IF EXISTS playfinder_db;
CREATE DATABASE playfinder_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE playfinder_db;

-- ---------------------------------------------------------------------
-- 1. USERS  (role: user = player, owner = facility owner, admin)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('user','owner','admin') NOT NULL DEFAULT 'user',
    status        ENUM('active','blocked')     NOT NULL DEFAULT 'active',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. SPORTS  (sport types used for searching)
-- ---------------------------------------------------------------------
CREATE TABLE sports (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(50) NOT NULL UNIQUE,
    slug  VARCHAR(50) NOT NULL UNIQUE,
    icon  VARCHAR(10) NOT NULL DEFAULT '🏅'
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. FACILITIES  (one sports centre, owned by an owner, approved by admin)
-- ---------------------------------------------------------------------
CREATE TABLE facilities (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    owner_id    INT NOT NULL,
    name        VARCHAR(120) NOT NULL,
    description TEXT,
    address     VARCHAR(255) NOT NULL,
    area        VARCHAR(60)  NOT NULL,
    latitude    DECIMAL(10,7) NOT NULL,
    longitude   DECIMAL(10,7) NOT NULL,
    phone       VARCHAR(20)  NOT NULL,
    open_time   TIME NOT NULL DEFAULT '06:00:00',
    close_time  TIME NOT NULL DEFAULT '22:00:00',
    amenities   VARCHAR(255) DEFAULT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fac_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_fac_area (area),
    INDEX idx_fac_status (status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. COURTS  (a bookable unit inside a facility, for one sport)
-- ---------------------------------------------------------------------
CREATE TABLE courts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    facility_id    INT NOT NULL,
    sport_id       INT NOT NULL,
    name           VARCHAR(80) NOT NULL,
    price_per_hour DECIMAL(10,2) NOT NULL,
    is_active      TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_court_fac   FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
    CONSTRAINT fk_court_sport FOREIGN KEY (sport_id)    REFERENCES sports(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. BOOKINGS  (1-hour slots; a booking can span 1-4 consecutive hours)
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref   VARCHAR(12) NOT NULL UNIQUE,
    user_id       INT NOT NULL,
    court_id      INT NOT NULL,
    booking_date  DATE NOT NULL,
    start_time    TIME NOT NULL,
    end_time      TIME NOT NULL,
    hours         TINYINT NOT NULL,
    total_price   DECIMAL(10,2) NOT NULL,
    status        ENUM('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
    notes         VARCHAR(255) DEFAULT NULL,
    cancelled_by  ENUM('user','owner','admin') DEFAULT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_at  DATETIME DEFAULT NULL,
    CONSTRAINT fk_book_user  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_book_court FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE,
    INDEX idx_book_slot (court_id, booking_date, status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. CONTACT MESSAGES  (from the Contact Us page)
-- ---------------------------------------------------------------------
CREATE TABLE contact_messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    subject    VARCHAR(150) NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
--  DEMO DATA
--  Passwords:  admin@playfinder.lk / Admin@123
--              owner@playfinder.lk / Owner@123
--              user@playfinder.lk  / User@123
-- =====================================================================
INSERT INTO users (id, full_name, email, phone, password_hash, role) VALUES
(1, 'System Admin',      'admin@playfinder.lk', '0112345678', '$2y$10$DXIUZncrK/5fjawefnBumul8q9wb9b7wz6PJmU/peHMSFnNIDKKAS', 'admin'),
(2, 'Nuwan Perera',      'owner@playfinder.lk', '0771234567', '$2y$10$of3VQlFVmljfBjzbShfwVOro3bop7UUVfbnNhekuijq0UBO.SEGe6', 'owner'),
(3, 'Arkam Anaz',        'user@playfinder.lk',  '0759876543', '$2y$10$jNPKp81oKsaOIfEx1GF5wOzf7llOK0iloeGHkYCzxDWzul7lSw4lO', 'user'),
(4, 'Fathima Rizwan',    'owner2@playfinder.lk','0712223344', '$2y$10$of3VQlFVmljfBjzbShfwVOro3bop7UUVfbnNhekuijq0UBO.SEGe6', 'owner');

INSERT INTO sports (id, name, slug, icon) VALUES
(1, 'Badminton',    'badminton',    '🏸'),
(2, 'Futsal',       'futsal',       '⚽'),
(3, 'Cricket Nets', 'cricket-nets', '🏏'),
(4, 'Table Tennis', 'table-tennis', '🏓'),
(5, 'Basketball',   'basketball',   '🏀');

-- Note: facility names below are fictional demo data.
INSERT INTO facilities (id, owner_id, name, description, address, area, latitude, longitude, phone, open_time, close_time, amenities, status) VALUES
(1, 2, 'Smash Arena', 'Air-conditioned badminton hall with 4 wooden courts, BWF-standard lighting and racket rental.', '45 Galle Road, Wellawatte', 'Wellawatte', 6.8747000, 79.8612000, '0112581234', '06:00:00', '22:00:00', 'Parking,Changing Rooms,Air Conditioned,Equipment Rental', 'approved'),
(2, 2, 'Goal Zone Futsal', 'Rooftop 5-a-side futsal pitch with FIFA-quality artificial turf and floodlights.', '12 Ward Place, Colombo 07', 'Colombo 07', 6.9064000, 79.8640000, '0112695555', '07:00:00', '23:00:00', 'Parking,Changing Rooms,Showers,Cafeteria', 'approved'),
(3, 2, 'Boundary Box Cricket Nets', 'Indoor cricket nets with bowling machine and turf / matting wickets.', '88 Baseline Road, Borella', 'Borella', 6.9147000, 79.8778000, '0112691122', '06:00:00', '22:00:00', 'Bowling Machine,Parking,Equipment Rental', 'approved'),
(4, 4, 'Spin Point Table Tennis', 'Dedicated table tennis centre with 6 ITTF-approved tables and coaching.', '21 Hill Street, Dehiwala', 'Dehiwala', 6.8510000, 79.8650000, '0112734455', '08:00:00', '21:00:00', 'Air Conditioned,Coaching,Equipment Rental', 'approved'),
(5, 2, 'Kickoff Indoor Arena', 'Multi-sport arena offering futsal and badminton under one roof.', '150 Parliament Road, Rajagiriya', 'Rajagiriya', 6.9094000, 79.8945000, '0112881234', '06:00:00', '23:00:00', 'Parking,Changing Rooms,Showers,Cafeteria,Air Conditioned', 'approved'),
(6, 4, 'Hoop House Basketball', 'Full-size indoor basketball court with spectator seating.', '33 Stanley Thilakaratne Mawatha, Nugegoda', 'Nugegoda', 6.8649000, 79.8997000, '0112812345', '07:00:00', '22:00:00', 'Parking,Changing Rooms,Spectator Seating', 'approved'),
(7, 4, 'Coastline Sports Complex', 'Seaside complex with badminton, table tennis and futsal facilities.', '5 Hotel Road, Mount Lavinia', 'Mount Lavinia', 6.8390000, 79.8653000, '0112739999', '06:00:00', '22:00:00', 'Parking,Changing Rooms,Showers,Cafeteria', 'approved'),
(8, 2, 'Racket Hub', 'Modern badminton centre with synthetic mats, close to Battaramulla junction.', '71 Pannipitiya Road, Battaramulla', 'Battaramulla', 6.9020000, 79.9180000, '0112865432', '06:00:00', '22:00:00', 'Parking,Air Conditioned,Equipment Rental', 'approved'),
(9, 2, 'City Sports Hall', 'Central Colombo hall for badminton and basketball, near Kollupitiya.', '210 Galle Road, Kollupitiya', 'Kollupitiya', 6.9110000, 79.8510000, '0112577777', '06:00:00', '22:00:00', 'Changing Rooms,Air Conditioned', 'approved'),
(10, 4, 'Maharagama Indoor Nets', 'Affordable cricket practice nets with coaching sessions on weekends.', '18 High Level Road, Maharagama', 'Maharagama', 6.8480000, 79.9265000, '0112841111', '06:00:00', '21:00:00', 'Parking,Coaching', 'approved'),
(11, 4, 'Kotte Shuttle Club', 'Newly opened badminton club awaiting admin approval (demo).', '9 Kotte Road, Sri Jayawardenepura Kotte', 'Kotte', 6.8880000, 79.9020000, '0112867777', '06:00:00', '22:00:00', 'Parking', 'pending');

INSERT INTO courts (facility_id, sport_id, name, price_per_hour) VALUES
(1, 1, 'Court 1', 2000.00), (1, 1, 'Court 2', 2000.00), (1, 1, 'Court 3 (Premium)', 2500.00),
(2, 2, 'Rooftop Pitch', 8000.00),
(3, 3, 'Net 1 (Turf)', 3500.00), (3, 3, 'Net 2 (Matting)', 3000.00), (3, 3, 'Net 3 + Bowling Machine', 4500.00),
(4, 4, 'Table 1', 1000.00), (4, 4, 'Table 2', 1000.00), (4, 4, 'Table 3', 1200.00),
(5, 2, 'Futsal Pitch A', 7500.00), (5, 1, 'Badminton Court 1', 1800.00), (5, 1, 'Badminton Court 2', 1800.00),
(6, 5, 'Main Court', 6000.00),
(7, 1, 'Badminton Court', 1700.00), (7, 4, 'TT Table', 900.00), (7, 2, 'Beach-side Futsal', 7000.00),
(8, 1, 'Court A', 1600.00), (8, 1, 'Court B', 1600.00),
(9, 1, 'Hall Court 1', 2200.00), (9, 5, 'Basketball Court', 5500.00),
(10, 3, 'Net 1', 2500.00), (10, 3, 'Net 2', 2500.00),
(11, 1, 'Court 1', 1500.00);

-- Demo bookings (dates relative to the day you import the file)
INSERT INTO bookings (booking_ref, user_id, court_id, booking_date, start_time, end_time, hours, total_price, status, created_at) VALUES
('PF-DEMO01', 3, 1,  CURDATE() + INTERVAL 1 DAY, '18:00:00', '20:00:00', 2, 4000.00, 'confirmed', NOW()),
('PF-DEMO02', 3, 4,  CURDATE() + INTERVAL 2 DAY, '19:00:00', '20:00:00', 1, 8000.00, 'confirmed', NOW()),
('PF-DEMO03', 3, 5,  CURDATE() - INTERVAL 3 DAY, '07:00:00', '08:00:00', 1, 3500.00, 'confirmed', NOW() - INTERVAL 5 DAY),
('PF-DEMO04', 3, 12, CURDATE() - INTERVAL 7 DAY, '17:00:00', '18:00:00', 1, 1800.00, 'cancelled', NOW() - INTERVAL 9 DAY),
('PF-DEMO05', 3, 2,  CURDATE() + INTERVAL 1 DAY, '07:00:00', '08:00:00', 1, 2000.00, 'confirmed', NOW());

INSERT INTO contact_messages (name, email, subject, message) VALUES
('Kasun Silva', 'kasun@example.com', 'List my facility', 'Hi, I own a futsal court in Kottawa. How can I list it on PlayFinder.lk?');
