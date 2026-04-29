<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function format_frw(int|float $amount): string
{
    return 'FRW ' . number_format((float) $amount, 0, '.', ',');
}

function fetch_bus_agencies(): array
{
    try {
        $agencies = db()->query("
            SELECT
                id,
                agency_name,
                tagline,
                rating,
                primary_color,
                route_focus,
                service_level,
                price_from_frw
            FROM bus_agencies
            ORDER BY rating DESC, agency_name ASC
        ")->fetchAll();

        if ($agencies !== []) {
            return $agencies;
        }
    } catch (Throwable $e) {
        // Fallback below keeps UI available even before schema update.
    }

    return [
        ['id' => 1, 'agency_name' => 'Volcano Express', 'tagline' => 'Fast routes to the North', 'rating' => 4.7, 'primary_color' => '#ef4444', 'route_focus' => 'Musanze ↔ Kigali', 'service_level' => 'Premium', 'price_from_frw' => 3000],
        ['id' => 2, 'agency_name' => 'Virunga Express', 'tagline' => 'Comfort across the country', 'rating' => 4.5, 'primary_color' => '#06b6d4', 'route_focus' => 'Rubavu ↔ Kigali', 'service_level' => 'Standard', 'price_from_frw' => 2800],
        ['id' => 3, 'agency_name' => 'Horizon Express', 'tagline' => "Connecting Rwanda's horizon", 'rating' => 4.4, 'primary_color' => '#f59e0b', 'route_focus' => 'Huye ↔ Kigali', 'service_level' => 'Standard', 'price_from_frw' => 2600],
        ['id' => 4, 'agency_name' => 'Trinity Express', 'tagline' => 'Three stars, one journey', 'rating' => 4.6, 'primary_color' => '#9333ea', 'route_focus' => 'Muhanga ↔ Kigali', 'service_level' => 'Premium', 'price_from_frw' => 3200],
        ['id' => 5, 'agency_name' => 'Kigali Coach', 'tagline' => 'Affordable travel for everyone', 'rating' => 4.2, 'primary_color' => '#16a34a', 'route_focus' => 'Rwamagana ↔ Kigali', 'service_level' => 'Economy', 'price_from_frw' => 2200],
    ];
}

function fetch_admin_snapshot(): array
{
    $overview = db()->query("
        SELECT
            COALESCE(SUM(CASE WHEN booking_status IN ('confirmed', 'completed') THEN amount_frw ELSE 0 END), 0) AS total_revenue,
            COUNT(*) AS total_bookings
        FROM bookings
    ")->fetch() ?: [];

    $activeBuses = (int) db()->query("SELECT COUNT(*) FROM buses WHERE status = 'active'")->fetchColumn();
    $totalUsers = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();

    $bookingBreakdownRows = db()->query("
        SELECT booking_status, COUNT(*) AS total
        FROM bookings
        GROUP BY booking_status
    ")->fetchAll();
    $bookingBreakdown = ['completed' => 0, 'pending' => 0, 'cancelled' => 0];
    foreach ($bookingBreakdownRows as $row) {
        $status = (string) ($row['booking_status'] ?? '');
        if (array_key_exists($status, $bookingBreakdown)) {
            $bookingBreakdown[$status] = (int) $row['total'];
        } elseif ($status === 'confirmed') {
            $bookingBreakdown['completed'] += (int) $row['total'];
        }
    }

    $fleetRows = db()->query("
        SELECT status, COUNT(*) AS total
        FROM buses
        GROUP BY status
    ")->fetchAll();
    $fleetBreakdown = ['active' => 0, 'maintenance' => 0, 'inactive' => 0];
    foreach ($fleetRows as $row) {
        $status = (string) ($row['status'] ?? '');
        if (array_key_exists($status, $fleetBreakdown)) {
            $fleetBreakdown[$status] = (int) $row['total'];
        }
    }

    $recentBookings = db()->query("
        SELECT
            b.booking_code,
            r.origin,
            r.destination,
            DATE_FORMAT(t.departure_time, '%b %d, %Y') AS trip_date,
            b.amount_frw,
            b.booking_status
        FROM bookings b
        JOIN trips t ON t.id = b.trip_id
        JOIN routes r ON r.id = t.route_id
        ORDER BY b.created_at DESC, b.id DESC
        LIMIT 6
    ")->fetchAll();

    $routeRows = db()->query("
        SELECT
            CONCAT(r.origin, ' \xE2\x86\x92 ', r.destination) AS route_label,
            COUNT(b.id) AS total
        FROM routes r
        LEFT JOIN trips t ON t.route_id = r.id
        LEFT JOIN bookings b ON b.trip_id = t.id
        GROUP BY r.id
        ORDER BY total DESC, r.id ASC
        LIMIT 5
    ")->fetchAll();

    $revenueRows = db()->query("
        SELECT
            DATE_FORMAT(created_at, '%b %e') AS label,
            SUM(CASE WHEN booking_status IN ('confirmed', 'completed') THEN amount_frw ELSE 0 END) AS total
        FROM bookings
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ")->fetchAll();

    $revenueLabels = [];
    $revenueSeries = [];
    foreach ($revenueRows as $row) {
        $revenueLabels[] = (string) $row['label'];
        $revenueSeries[] = (int) $row['total'];
    }
    if ($revenueLabels === []) {
        $revenueLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $revenueSeries = [120000, 160000, 190000, 180000, 210000, 240000, 260000];
    }

    return [
        'total_revenue' => (int) ($overview['total_revenue'] ?? 0),
        'total_bookings' => (int) ($overview['total_bookings'] ?? 0),
        'active_buses' => $activeBuses,
        'total_users' => $totalUsers,
        'booking_breakdown' => $bookingBreakdown,
        'fleet_breakdown' => $fleetBreakdown,
        'recent_bookings' => $recentBookings,
        'top_routes' => $routeRows,
        'revenue_labels' => $revenueLabels,
        'revenue_series' => $revenueSeries,
    ];
}

function fetch_passenger_snapshot(int $userId): array
{
    $upcoming = db()->prepare("
        SELECT
            b.booking_code,
            b.seat_number,
            b.amount_frw,
            r.origin,
            r.destination,
            COALESCE(a.agency_name, 'Agency TBD') AS agency_name,
            DATE_FORMAT(t.departure_time, '%b %d, %Y %h:%i %p') AS departure_label
        FROM bookings b
        JOIN trips t ON t.id = b.trip_id
        JOIN routes r ON r.id = t.route_id
        LEFT JOIN bus_agencies a ON a.id = b.agency_id
        WHERE b.user_id = :user_id
          AND b.booking_status IN ('pending', 'confirmed')
        ORDER BY t.departure_time ASC
        LIMIT 1
    ");
    $upcoming->execute([':user_id' => $userId]);
    $upcomingTrip = $upcoming->fetch() ?: null;

    $history = db()->prepare("
        SELECT
            b.booking_code,
            r.origin,
            r.destination,
            COALESCE(a.agency_name, '-') AS agency_name,
            DATE_FORMAT(t.departure_time, '%b %d') AS trip_date,
            b.booking_status
        FROM bookings b
        JOIN trips t ON t.id = b.trip_id
        JOIN routes r ON r.id = t.route_id
        LEFT JOIN bus_agencies a ON a.id = b.agency_id
        WHERE b.user_id = :user_id
        ORDER BY t.departure_time DESC
        LIMIT 8
    ");
    $history->execute([':user_id' => $userId]);
    $ticketHistory = $history->fetchAll();

    $popularRoutes = db()->query("
        SELECT
            r.origin,
            r.destination,
            r.fare_frw,
            COUNT(b.id) AS total_bookings
        FROM routes r
        LEFT JOIN trips t ON t.route_id = r.id
        LEFT JOIN bookings b ON b.trip_id = t.id
        GROUP BY r.id
        ORDER BY total_bookings DESC, r.id ASC
        LIMIT 5
    ")->fetchAll();

    $walletEstimate = db()->prepare("
        SELECT COALESCE(SUM(CASE WHEN booking_status IN ('confirmed', 'completed') THEN amount_frw ELSE 0 END), 0) AS spent
        FROM bookings
        WHERE user_id = :user_id
    ");
    $walletEstimate->execute([':user_id' => $userId]);
    $spent = (int) ($walletEstimate->fetchColumn() ?: 0);

    return [
        'upcoming_trip' => $upcomingTrip,
        'ticket_history' => $ticketHistory,
        'popular_routes' => $popularRoutes,
        'wallet_balance' => max(15000, 45000 - $spent),
    ];
}

function fetch_driver_snapshot(): array
{
    $trip = db()->query("
        SELECT
            t.id,
            r.origin,
            r.destination,
            b.plate_number,
            DATE_FORMAT(t.departure_time, '%h:%i %p') AS departure_label,
            DATE_FORMAT(t.arrival_time, '%h:%i %p') AS arrival_label
        FROM trips t
        JOIN routes r ON r.id = t.route_id
        JOIN buses b ON b.id = t.bus_id
        ORDER BY FIELD(t.status, 'in_progress', 'scheduled', 'completed', 'cancelled'), t.departure_time ASC
        LIMIT 1
    ")->fetch() ?: null;

    $tripId = (int) ($trip['id'] ?? 0);
    $passengerCount = 0;
    $earnings = 0;
    if ($tripId > 0) {
        $tripStats = db()->prepare("
            SELECT
                COUNT(*) AS passenger_count,
                COALESCE(SUM(CASE WHEN booking_status IN ('confirmed', 'completed') THEN amount_frw ELSE 0 END), 0) AS total_earnings
            FROM bookings
            WHERE trip_id = :trip_id
        ");
        $tripStats->execute([':trip_id' => $tripId]);
        $stats = $tripStats->fetch() ?: [];
        $passengerCount = (int) ($stats['passenger_count'] ?? 0);
        $earnings = (int) ($stats['total_earnings'] ?? 0);
    }

    return [
        'trip' => $trip,
        'passenger_count' => $passengerCount,
        'earnings' => $earnings,
    ];
}

function fetch_agent_snapshot(): array
{
    $stats = db()->query("
        SELECT
            COUNT(*) AS total_bookings,
            COALESCE(SUM(CASE WHEN booking_status IN ('confirmed', 'completed') THEN amount_frw ELSE 0 END), 0) AS total_sales,
            SUM(CASE WHEN booking_status = 'pending' THEN 1 ELSE 0 END) AS pending_bookings
        FROM bookings
        WHERE DATE(created_at) = CURDATE()
    ")->fetch() ?: [];

    $passengersToday = (int) db()->query("
        SELECT COUNT(DISTINCT user_id)
        FROM bookings
        WHERE DATE(created_at) = CURDATE()
    ")->fetchColumn();

    $recentBookings = db()->query("
        SELECT
            b.booking_code,
            u.full_name,
            CONCAT(r.origin, ' \xE2\x86\x92 ', r.destination) AS route_label,
            b.amount_frw,
            b.booking_status
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        JOIN trips t ON t.id = b.trip_id
        JOIN routes r ON r.id = t.route_id
        ORDER BY b.created_at DESC, b.id DESC
        LIMIT 8
    ")->fetchAll();

    $dailySalesRows = db()->query("
        SELECT
            DATE_FORMAT(created_at, '%a') AS day_label,
            COALESCE(SUM(CASE WHEN booking_status IN ('confirmed', 'completed') THEN amount_frw ELSE 0 END), 0) AS total
        FROM bookings
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ")->fetchAll();

    $salesLabels = [];
    $salesSeries = [];
    foreach ($dailySalesRows as $row) {
        $salesLabels[] = (string) $row['day_label'];
        $salesSeries[] = (int) $row['total'];
    }
    if ($salesLabels === []) {
        $salesLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $salesSeries = [145000, 172000, 188000, 179000, 210000, 241000, 258000];
    }

    return [
        'today_bookings' => (int) ($stats['total_bookings'] ?? 0),
        'today_sales' => (int) ($stats['total_sales'] ?? 0),
        'pending_bookings' => (int) ($stats['pending_bookings'] ?? 0),
        'passengers_today' => $passengersToday,
        'recent_bookings' => $recentBookings,
        'sales_labels' => $salesLabels,
        'sales_series' => $salesSeries,
    ];
}
