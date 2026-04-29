CREATE DATABASE IF NOT EXISTS rwandarite;
USE rwandarite;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(140) NOT NULL UNIQUE,
    role ENUM('passenger', 'driver', 'agent', 'admin') NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_color VARCHAR(30) NOT NULL DEFAULT '#1f6feb',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    origin VARCHAR(80) NOT NULL,
    destination VARCHAR(80) NOT NULL,
    distance_km INT NOT NULL,
    fare_frw INT NOT NULL
);

CREATE TABLE IF NOT EXISTS buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(30) NOT NULL UNIQUE,
    model_name VARCHAR(120) NOT NULL,
    seat_capacity INT NOT NULL,
    status ENUM('active', 'maintenance', 'inactive') NOT NULL DEFAULT 'active'
);

CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    bus_id INT NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    FOREIGN KEY (route_id) REFERENCES routes(id),
    FOREIGN KEY (bus_id) REFERENCES buses(id)
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_code VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    trip_id INT NOT NULL,
    agency_id INT NULL,
    seat_number VARCHAR(20) NOT NULL,
    amount_frw INT NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (trip_id) REFERENCES trips(id)
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    method ENUM('wallet', 'mobile_money', 'card', 'cash') NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    amount_frw INT NOT NULL,
    paid_at DATETIME NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);

CREATE TABLE IF NOT EXISTS bus_agencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agency_name VARCHAR(120) NOT NULL UNIQUE,
    tagline VARCHAR(255) NOT NULL,
    rating DECIMAL(2,1) NOT NULL DEFAULT 4.0,
    primary_color VARCHAR(20) NOT NULL DEFAULT '#1f6feb',
    route_focus VARCHAR(120) NOT NULL DEFAULT 'Rwanda Routes',
    service_level ENUM('Economy', 'Standard', 'Premium') NOT NULL DEFAULT 'Standard',
    price_from_frw INT NOT NULL DEFAULT 3000
);

SET @add_route_focus = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'bus_agencies'
              AND column_name = 'route_focus'
        ),
        'SELECT 1',
        "ALTER TABLE bus_agencies ADD COLUMN route_focus VARCHAR(120) NOT NULL DEFAULT 'Rwanda Routes'"
    )
);
PREPARE stmt_route_focus FROM @add_route_focus;
EXECUTE stmt_route_focus;
DEALLOCATE PREPARE stmt_route_focus;

SET @add_service_level = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'bus_agencies'
              AND column_name = 'service_level'
        ),
        'SELECT 1',
        "ALTER TABLE bus_agencies ADD COLUMN service_level ENUM('Economy', 'Standard', 'Premium') NOT NULL DEFAULT 'Standard'"
    )
);
PREPARE stmt_service_level FROM @add_service_level;
EXECUTE stmt_service_level;
DEALLOCATE PREPARE stmt_service_level;

SET @add_price_from = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'bus_agencies'
              AND column_name = 'price_from_frw'
        ),
        'SELECT 1',
        'ALTER TABLE bus_agencies ADD COLUMN price_from_frw INT NOT NULL DEFAULT 3000'
    )
);
PREPARE stmt_price_from FROM @add_price_from;
EXECUTE stmt_price_from;
DEALLOCATE PREPARE stmt_price_from;

SET @add_booking_agency = (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'bookings'
              AND column_name = 'agency_id'
        ),
        'SELECT 1',
        'ALTER TABLE bookings ADD COLUMN agency_id INT NULL AFTER trip_id'
    )
);
PREPARE stmt_booking_agency FROM @add_booking_agency;
EXECUTE stmt_booking_agency;
DEALLOCATE PREPARE stmt_booking_agency;

INSERT INTO users (full_name, email, role, password_hash, avatar_color) VALUES
('Aline Passenger', 'passenger.rwandarite@gmail.com', 'passenger', 'passenger123', '#1f6feb'),
('Eric Driver', 'driver.rwandarite@gmail.com', 'driver', 'driver123', '#22a06b'),
('Marie Agent', 'agent.rwandarite@gmail.com', 'agent', 'agent123', '#f59f0b'),
('Super Admin', 'admin.rwandarite@gmail.com', 'admin', 'admin123', '#8250df')
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO routes (origin, destination, distance_km, fare_frw) VALUES
('Kigali', 'Huye', 132, 7500),
('Kigali', 'Musanze', 102, 6000),
('Kigali', 'Rubavu', 154, 8000)
ON DUPLICATE KEY UPDATE destination = VALUES(destination);

INSERT INTO buses (plate_number, model_name, seat_capacity, status) VALUES
('RAB123A', 'Yutong ZK6105', 52, 'active'),
('RAB432B', 'Scania Touring', 50, 'active'),
('RAB921C', 'Volvo 9700', 48, 'maintenance')
ON DUPLICATE KEY UPDATE model_name = VALUES(model_name);

INSERT IGNORE INTO trips (id, route_id, bus_id, departure_time, arrival_time, status) VALUES
(1, 1, 1, '2026-04-25 07:00:00', '2026-04-25 10:15:00', 'scheduled'),
(2, 2, 2, '2026-04-25 08:30:00', '2026-04-25 11:10:00', 'scheduled'),
(3, 3, 1, '2026-04-26 06:45:00', '2026-04-26 11:00:00', 'scheduled');

INSERT IGNORE INTO bookings (booking_code, user_id, trip_id, agency_id, seat_number, amount_frw, booking_status) VALUES
('BK23001', 1, 1, 1, '21A', 7500, 'confirmed'),
('BK23002', 1, 2, 2, '13B', 6000, 'pending'),
('BK23003', 1, 3, 3, '04C', 8000, 'completed');

INSERT INTO bus_agencies (agency_name, tagline, rating, primary_color, route_focus, service_level, price_from_frw) VALUES
('Volcano Express', 'Fast routes to the North', 4.7, '#ef4444', 'Musanze ↔ Kigali', 'Premium', 3000),
('Virunga Express', 'Comfort across the country', 4.5, '#06b6d4', 'Rubavu ↔ Kigali', 'Standard', 2800),
('Horizon Express', 'Connecting Rwanda''s horizon', 4.4, '#f59e0b', 'Huye ↔ Kigali', 'Standard', 2600),
('Trinity Express', 'Three stars, one journey', 4.6, '#9333ea', 'Muhanga ↔ Kigali', 'Premium', 3200),
('Kigali Coach', 'Affordable travel for everyone', 4.2, '#16a34a', 'Rwamagana ↔ Kigali', 'Economy', 2200)
ON DUPLICATE KEY UPDATE
    tagline = VALUES(tagline),
    rating = VALUES(rating),
    primary_color = VALUES(primary_color),
    route_focus = VALUES(route_focus),
    service_level = VALUES(service_level),
    price_from_frw = VALUES(price_from_frw);

UPDATE bookings
SET agency_id = (SELECT MIN(id) FROM bus_agencies)
WHERE agency_id IS NULL;

