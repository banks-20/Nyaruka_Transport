# NyarukaTransport - Multi-Role Smart Transport Platform

Modern SaaS-style transport management web application built with PHP, MySQL, HTML, and JavaScript.

## Included UI Modules

- Landing page with Kigali-inspired hero section and role selector
- Role-based authentication (Passenger, Driver, Agent, Admin)
- Admin dashboard
- Passenger dashboard
- Driver dashboard
- Agent dashboard
- Real Leaflet-based route maps with animated bus location updates
- Full dark/light mode toggle with saved preference
- CRUD modules:
  - `routes-management.php`
  - `fleet-management.php`
  - `bookings-management.php`

## Setup

1. Copy project into your WAMP root:
   - `C:\wamp64\www\Rwanda_Ride`
2. Import the database:
   - Open phpMyAdmin
   - Import `database/schema.sql`
   - Database name: `rwandarite`
3. Configure DB credentials in `includes/config.php` if your MySQL credentials differ.
4. Open in browser:
   - `http://localhost/Rwanda_Ride/index.php`

## Demo Login Credentials

- Passenger: `passenger@nyaruka.rw` / `passenger123`
- Driver: `driver@nyaruka.rw` / `driver123`
- Agent: `agent@nyaruka.rw` / `agent123`
- Admin: `admin@nyaruka.rw` / `admin123`

