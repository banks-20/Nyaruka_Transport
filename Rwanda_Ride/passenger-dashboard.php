<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/dashboard-data.php';

$user = require_auth('passenger');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_booking'])) {
    try {
        $tripId = (int) ($_POST['trip_id'] ?? 0);
        $agencyId = (int) ($_POST['agency_id'] ?? 0);
        $seatNumber = strtoupper(trim((string) ($_POST['seat_number'] ?? '')));

        if ($tripId <= 0 || $agencyId <= 0 || $seatNumber === '') {
            throw new RuntimeException('Please select a trip, bus agency, and seat number.');
        }
        if (!isset($_POST['confirm_details'])) {
            throw new RuntimeException('Please confirm your selected agency and trip details.');
        }

        $tripStmt = db()->prepare("
            SELECT t.id, r.fare_frw
            FROM trips t
            JOIN routes r ON r.id = t.route_id
            WHERE t.id = :trip_id
            LIMIT 1
        ");
        $tripStmt->execute([':trip_id' => $tripId]);
        $trip = $tripStmt->fetch();
        if (!$trip) {
            throw new RuntimeException('Selected trip was not found.');
        }
        $agencyStmt = db()->prepare("SELECT id FROM bus_agencies WHERE id = :agency_id LIMIT 1");
        $agencyStmt->execute([':agency_id' => $agencyId]);
        if (!$agencyStmt->fetch()) {
            throw new RuntimeException('Selected bus agency was not found.');
        }

        $bookingCode = 'BK' . random_int(10000, 99999);
        $insertStmt = db()->prepare('
            INSERT INTO bookings (booking_code, user_id, trip_id, agency_id, seat_number, amount_frw, booking_status)
            VALUES (:code, :user_id, :trip_id, :agency_id, :seat_number, :amount_frw, :booking_status)
        ');
        $insertStmt->execute([
            ':code' => $bookingCode,
            ':user_id' => (int) $user['id'],
            ':trip_id' => $tripId,
            ':agency_id' => $agencyId,
            ':seat_number' => $seatNumber,
            ':amount_frw' => (int) $trip['fare_frw'],
            ':booking_status' => 'pending',
        ]);

        set_flash('Booking request submitted successfully. Agent confirmation is pending.');
    } catch (Throwable $e) {
        set_flash('Booking request failed: ' . $e->getMessage());
    }

    header('Location: ' . app_url('passenger-dashboard.php'));
    exit;
}

$snapshot = fetch_passenger_snapshot((int) $user['id']);
$agencies = fetch_bus_agencies();
$upcoming = $snapshot['upcoming_trip'];
$flash = get_flash();
$from = trim((string) ($_GET['from'] ?? 'Kigali'));
$to = trim((string) ($_GET['to'] ?? 'Huye'));
$date = trim((string) ($_GET['date'] ?? date('Y-m-d')));
$searchStmt = db()->prepare("
    SELECT t.id, r.origin, r.destination, DATE_FORMAT(t.departure_time, '%b %d, %Y %h:%i %p') AS departure_label, r.fare_frw
    FROM trips t
    JOIN routes r ON r.id = t.route_id
    WHERE r.origin LIKE :origin
      AND r.destination LIKE :destination
      AND DATE(t.departure_time) = :trip_date
    ORDER BY t.departure_time ASC
");
$searchStmt->execute([
    ':origin' => '%' . $from . '%',
    ':destination' => '%' . $to . '%',
    ':trip_date' => $date,
]);
$tripSearchResults = $searchStmt->fetchAll();
$tripOptions = db()->query("
    SELECT t.id, r.origin, r.destination, DATE_FORMAT(t.departure_time, '%b %d, %Y %h:%i %p') AS departure_label, r.fare_frw
    FROM trips t
    JOIN routes r ON r.id = t.route_id
    WHERE t.status IN ('scheduled', 'in_progress')
    ORDER BY t.departure_time ASC
    LIMIT 20
")->fetchAll();
render_head('Passenger Dashboard');
render_dashboard_shell($user, 'dashboard');
?>
<?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<section class="content-grid passenger-grid">
    <article class="card panel-wide">
        <div class="card-head"><h4>Search Trip</h4></div>
        <form class="trip-form" method="get">
            <input type="text" name="from" value="<?= htmlspecialchars($from) ?>" aria-label="From">
            <input type="text" name="to" value="<?= htmlspecialchars($to) ?>" aria-label="To">
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" aria-label="Date">
            <button class="primary-btn" type="submit">Search Buses</button>
        </form>
        <div class="search-results">
            <?php if ($tripSearchResults === []): ?>
                <p>No matching trips found for this date.</p>
            <?php else: ?>
                <ul class="list-lines">
                    <?php foreach ($tripSearchResults as $tripResult): ?>
                        <li>
                            <?= htmlspecialchars((string) $tripResult['origin'] . ' → ' . (string) $tripResult['destination']) ?>
                            <span><?= htmlspecialchars((string) $tripResult['departure_label']) ?> | <?= htmlspecialchars(format_frw((int) $tripResult['fare_frw'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </article>
    
    <article class="card panel-wide">
        <div class="card-head"><h4>Request Booking (Agent Confirmation)</h4></div>
        <form class="stack-form" method="post">
            <select name="trip_id" required>
                <option value="">Select trip</option>
                <?php foreach ($tripOptions as $tripOption): ?>
                    <option value="<?= (int) $tripOption['id'] ?>">
                        <?= htmlspecialchars((string) $tripOption['origin'] . ' → ' . (string) $tripOption['destination'] . ' | ' . (string) $tripOption['departure_label'] . ' | ' . format_frw((int) $tripOption['fare_frw'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="agency_id" required>
                <option value="">Select bus agency</option>
                <?php foreach ($agencies as $agency): ?>
                    <option value="<?= (int) $agency['id'] ?>">
                        <?= htmlspecialchars((string) $agency['agency_name'] . ' | ' . (string) $agency['service_level'] . ' | From ' . format_frw((int) $agency['price_from_frw'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="seat_number" placeholder="Preferred seat (e.g., 14B)" required>
            <label>
                <input type="checkbox" name="confirm_details" value="1" required>
                I confirm my selected trip and bus agency.
            </label>
            <button class="primary-btn" type="submit" name="request_booking">Confirm & Submit Booking Request</button>
        </form>
    </article>

    <article class="card">
        <div class="card-head"><h4>Upcoming Trip</h4></div>
        <?php if ($upcoming): ?>
            <p class="route-main"><?= htmlspecialchars((string) $upcoming['origin'] . ' → ' . (string) $upcoming['destination']) ?></p>
            <p><?= htmlspecialchars((string) $upcoming['departure_label']) ?> | Seat <?= htmlspecialchars((string) $upcoming['seat_number']) ?> | <?= htmlspecialchars((string) $upcoming['agency_name']) ?></p>
        <?php else: ?>
            <p class="route-main">No upcoming trip</p>
            <p>Book your next journey in a few taps.</p>
        <?php endif; ?>
        <div class="mini-bus"></div>
    </article>

    <article class="card">
        <div class="card-head"><h4>E-wallet Balance</h4></div>
        <h3><?= htmlspecialchars(format_frw((int) $snapshot['wallet_balance'])) ?></h3>
        <button class="primary-btn small">Top up Wallet</button>
    </article>

    <article class="card">
        <div class="card-head"><h4>Popular Routes</h4></div>
        <ul class="list-lines">
            <?php if ($snapshot['popular_routes'] === []): ?>
                <li>Routes will appear once bookings start <span>FRW 0</span></li>
            <?php else: ?>
                <?php foreach ($snapshot['popular_routes'] as $route): ?>
                    <li>
                        <?= htmlspecialchars((string) $route['origin'] . ' → ' . (string) $route['destination']) ?>
                        <span><?= htmlspecialchars(format_frw((int) $route['fare_frw'])) ?></span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </article>

    <article class="card">
        <div class="card-head"><h4>Quick Actions</h4></div>
        <div class="quick-actions">
            <a href="<?= app_url('role-panel.php?view=bookings') ?>">Book Ticket</a>
            <a href="<?= app_url('role-panel.php?view=bookings') ?>" title="Live booking updates">Track Bus</a>
            <a href="<?= app_url('role-panel.php?view=support') ?>">Support</a>
        </div>
    </article>

    <article class="card panel-wide table-card">
        <div class="card-head"><h4>Ticket History</h4></div>
        <table>
            <thead>
            <tr><th>Ticket</th><th>Route</th><th>Agency</th><th>Date</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if ($snapshot['ticket_history'] === []): ?>
                <tr><td colspan="5">No ticket history yet.</td></tr>
            <?php else: ?>
                <?php foreach ($snapshot['ticket_history'] as $ticket): ?>
                    <?php
                    $status = (string) $ticket['booking_status'];
                    $statusClass = $status === 'cancelled' ? 'danger' : ($status === 'pending' ? 'warn' : 'ok');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $ticket['booking_code']) ?></td>
                        <td><?= htmlspecialchars((string) $ticket['origin'] . ' → ' . (string) $ticket['destination']) ?></td>
                        <td><?= htmlspecialchars((string) $ticket['agency_name']) ?></td>
                        <td><?= htmlspecialchars((string) $ticket['trip_date']) ?></td>
                        <td><span class="status <?= $statusClass ?>"><?= ucfirst($status) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </article>

    <article class="card panel-wide">
        <div class="card-head"><h4>Bus Agencies</h4></div>
        <div class="agencies-grid">
            <?php foreach ($agencies as $agency): ?>
                <div class="agency-card">
                    <div class="agency-icon" style="background: <?= htmlspecialchars((string) $agency['primary_color']) ?>;">
                        <i class="ri-bus-2-line"></i>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars((string) $agency['agency_name']) ?></strong>
                        <p><?= htmlspecialchars((string) $agency['tagline']) ?></p>
                        <div class="agency-tags">
                            <span><?= htmlspecialchars((string) $agency['route_focus']) ?></span>
                            <span><?= htmlspecialchars((string) $agency['service_level']) ?></span>
                            <span>From <?= htmlspecialchars(format_frw((int) $agency['price_from_frw'])) ?></span>
                        </div>
                    </div>
                    <span class="agency-rating"><i class="ri-star-fill"></i> <?= number_format((float) $agency['rating'], 1) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php end_dashboard_shell(); render_footer(); ?>

