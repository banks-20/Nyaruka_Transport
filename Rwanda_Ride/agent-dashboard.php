<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/dashboard-data.php';
require_once __DIR__ . '/includes/mailer.php';

$user = require_auth('agent');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_booking_request']) || isset($_POST['reject_booking_request'])) {
        try {
            $bookingId = (int) ($_POST['booking_id'] ?? 0);
            if ($bookingId <= 0) {
                throw new RuntimeException('Invalid booking request selected.');
            }

            $newStatus = isset($_POST['confirm_booking_request']) ? 'confirmed' : 'cancelled';
            $updateStmt = db()->prepare("
                UPDATE bookings
                SET booking_status = :status
                WHERE id = :booking_id
                  AND booking_status = 'pending'
            ");
            $updateStmt->execute([
                ':status' => $newStatus,
                ':booking_id' => $bookingId,
            ]);

            if ($updateStmt->rowCount() > 0) {
                set_flash($newStatus === 'confirmed'
                    ? 'Booking request confirmed successfully.'
                    : 'Booking request rejected successfully.');
            } else {
                set_flash('Booking request was already processed.');
            }
        } catch (Throwable $e) {
            set_flash('Request update failed: ' . $e->getMessage());
        }
        header('Location: ' . app_url('agent-dashboard.php'));
        exit;
    }

    if (isset($_POST['create_ticket'])) {
        try {
            $bookingCode = 'AG' . random_int(10000, 99999);
            $stmt = db()->prepare('INSERT INTO bookings (booking_code, user_id, trip_id, seat_number, amount_frw, booking_status) VALUES (:code, :user, :trip, :seat, :amount, :status)');
            $stmt->execute([
                ':code' => $bookingCode,
                ':user' => (int) ($_POST['user_id'] ?? 0),
                ':trip' => (int) ($_POST['trip_id'] ?? 0),
                ':seat' => strtoupper(trim((string) ($_POST['seat_number'] ?? '01A'))),
                ':amount' => max(100, (int) ($_POST['amount_frw'] ?? 100)),
                ':status' => 'confirmed',
            ]);
            set_flash('Ticket created successfully.');
        } catch (Throwable $e) {
            set_flash('Ticket creation failed: ' . $e->getMessage());
        }
        header('Location: ' . app_url('agent-dashboard.php'));
        exit;
    }

    if (isset($_POST['create_driver'])) {
        try {
            $driverName = trim((string) ($_POST['driver_name'] ?? ''));
            $driverEmail = strtolower(trim((string) ($_POST['driver_email'] ?? '')));
            if ($driverName === '' || $driverEmail === '') {
                throw new RuntimeException('Driver name and email are required.');
            }
            if (!filter_var($driverEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Please enter a valid driver email.');
            }

            $existingUser = get_user_by_email($driverEmail);
            if ($existingUser !== null) {
                throw new RuntimeException('This email is already registered as ' . ucfirst((string) $existingUser['role']) . '.');
            }

            $temporaryPassword = 'Drv@' . random_int(10000, 99999);
            create_user_account($driverName, $driverEmail, $temporaryPassword, 'driver');
            $mailSent = send_driver_credentials_email($driverEmail, $driverName, $temporaryPassword);

            if ($mailSent) {
                set_flash('Driver account created and credentials sent to ' . $driverEmail . '.');
            } else {
                set_flash('Driver account created, but email delivery failed. Configure SMTP_* in server environment. Temporary password: ' . $temporaryPassword . ' (also logged in storage/email-log.txt).');
            }
        } catch (Throwable $e) {
            set_flash('Driver creation failed: ' . $e->getMessage());
        }
        header('Location: ' . app_url('agent-dashboard.php'));
        exit;
    }
}

$snapshot = fetch_agent_snapshot();
$agencies = fetch_bus_agencies();
$passengers = db()->query("SELECT id, full_name FROM users WHERE role = 'passenger' ORDER BY full_name")->fetchAll();
$trips = db()->query("
    SELECT t.id, r.origin, r.destination, DATE_FORMAT(t.departure_time, '%b %d %H:%i') AS departure_label, r.fare_frw
    FROM trips t
    JOIN routes r ON r.id = t.route_id
    ORDER BY t.departure_time DESC
    LIMIT 20
")->fetchAll();
$drivers = db()->query("
    SELECT full_name, email, created_at
    FROM users
    WHERE role = 'driver'
    ORDER BY id DESC
    LIMIT 8
")->fetchAll();
$pendingRequests = db()->query("
    SELECT
        b.id,
        b.booking_code,
        u.full_name AS passenger_name,
        u.email AS passenger_email,
        COALESCE(a.agency_name, 'Agency TBD') AS agency_name,
        r.origin,
        r.destination,
        DATE_FORMAT(t.departure_time, '%b %d, %Y %h:%i %p') AS departure_label,
        b.seat_number,
        b.amount_frw
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    JOIN trips t ON t.id = b.trip_id
    JOIN routes r ON r.id = t.route_id
    LEFT JOIN bus_agencies a ON a.id = b.agency_id
    WHERE b.booking_status = 'pending'
    ORDER BY b.created_at DESC, b.id DESC
    LIMIT 12
")->fetchAll();
$premiumAgencies = count(array_filter($agencies, static fn (array $agency): bool => (string) $agency['service_level'] === 'Premium'));
$avgAgencyRating = $agencies === [] ? 0.0 : array_sum(array_map(static fn (array $agency): float => (float) $agency['rating'], $agencies)) / count($agencies);
$flash = get_flash();
render_head('Agent Dashboard');
render_dashboard_shell($user, 'dashboard');
?>
<?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
<section class="metrics-grid">
    <article class="metric-card">
        <p>Today's Bookings</p>
        <h3><?= number_format((int) $snapshot['today_bookings']) ?></h3>
        <span>Bookings created today</span>
    </article>
    <article class="metric-card">
        <p>Daily Sales</p>
        <h3><?= htmlspecialchars(format_frw((int) $snapshot['today_sales'])) ?></h3>
        <span>Confirmed and completed collections</span>
    </article>
    <article class="metric-card">
        <p>Total Passengers</p>
        <h3><?= number_format((int) $snapshot['passengers_today']) ?></h3>
        <span>Unique passengers served today</span>
    </article>
    <article class="metric-card">
        <p>Pending Bookings</p>
        <h3><?= number_format((int) $snapshot['pending_bookings']) ?></h3>
        <span>Require immediate follow-up</span>
    </article>
    <article class="metric-card">
        <p>Bus Agencies</p>
        <h3><?= number_format(count($agencies)) ?></h3>
        <span><?= $premiumAgencies ?> premium providers</span>
    </article>
    <article class="metric-card">
        <p>Average Agency Rating</p>
        <h3><?= number_format($avgAgencyRating, 1) ?></h3>
        <span>Service quality benchmark</span>
    </article>
</section>

<section class="content-grid">
    <article class="card panel-wide table-card">
        <div class="card-head"><h4>Booking Management</h4></div>
        <table>
            <thead>
            <tr><th>Booking ID</th><th>Passenger</th><th>Route</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if ($snapshot['recent_bookings'] === []): ?>
                <tr><td colspan="5">No booking records found.</td></tr>
            <?php else: ?>
                <?php foreach ($snapshot['recent_bookings'] as $booking): ?>
                    <?php
                    $status = (string) $booking['booking_status'];
                    $statusClass = $status === 'cancelled' ? 'danger' : ($status === 'pending' ? 'warn' : 'ok');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $booking['booking_code']) ?></td>
                        <td><?= htmlspecialchars((string) $booking['full_name']) ?></td>
                        <td><?= htmlspecialchars((string) $booking['route_label']) ?></td>
                        <td><?= htmlspecialchars(format_frw((int) $booking['amount_frw'])) ?></td>
                        <td><span class="status <?= $statusClass ?>"><?= ucfirst($status) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </article>

    <article class="card panel-wide table-card">
        <div class="card-head"><h4>Passenger Booking Confirmation Panel</h4></div>
        <table>
            <thead>
            <tr><th>Request Code</th><th>Passenger</th><th>Trip</th><th>Agency</th><th>Seat</th><th>Amount</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php if ($pendingRequests === []): ?>
                <tr><td colspan="7">No pending booking requests right now.</td></tr>
            <?php else: ?>
                <?php foreach ($pendingRequests as $request): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $request['booking_code']) ?></td>
                        <td>
                            <?= htmlspecialchars((string) $request['passenger_name']) ?><br>
                            <small><?= htmlspecialchars((string) $request['passenger_email']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars((string) $request['origin'] . ' → ' . (string) $request['destination']) ?><br>
                            <small><?= htmlspecialchars((string) $request['departure_label']) ?></small>
                        </td>
                        <td><?= htmlspecialchars((string) $request['agency_name']) ?></td>
                        <td><?= htmlspecialchars((string) $request['seat_number']) ?></td>
                        <td><?= htmlspecialchars(format_frw((int) $request['amount_frw'])) ?></td>
                        <td class="table-actions">
                            <form method="post" style="display:inline-flex; gap:6px;">
                                <input type="hidden" name="booking_id" value="<?= (int) $request['id'] ?>">
                                <button class="primary-btn small" type="submit" name="confirm_booking_request">Confirm</button>
                                <button class="danger-btn" type="submit" name="reject_booking_request">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </article>

    <article class="card">
        <div class="card-head"><h4>Ticket Creation Tools</h4></div>
        <form class="stack-form" method="post">
            <select name="user_id" required>
                <option value="">Select passenger</option>
                <?php foreach ($passengers as $passenger): ?>
                    <option value="<?= (int) $passenger['id'] ?>"><?= htmlspecialchars((string) $passenger['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="trip_id" required>
                <option value="">Select route/trip</option>
                <?php foreach ($trips as $trip): ?>
                    <option value="<?= (int) $trip['id'] ?>">
                        <?= htmlspecialchars((string) $trip['origin'] . ' → ' . (string) $trip['destination'] . ' | ' . (string) $trip['departure_label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="seat_number" placeholder="Seat number (e.g., 12B)" required>
            <input type="number" name="amount_frw" placeholder="Amount (FRW)" min="100" required>
            <button type="submit" name="create_ticket" class="primary-btn">Create Ticket</button>
        </form>
    </article>

    <article class="card">
        <div class="card-head"><h4>Add Driver Account</h4></div>
        <form class="stack-form" method="post">
            <input type="text" name="driver_name" placeholder="Driver full name" required>
            <input type="email" name="driver_email" placeholder="driver@example.com" required>
            <button type="submit" name="create_driver" class="primary-btn">Create Driver & Send Credentials</button>
        </form>
        <ul class="list-lines" style="margin-top:10px;">
            <?php foreach ($drivers as $driver): ?>
                <li>
                    <?= htmlspecialchars((string) $driver['full_name']) ?>
                    <span><?= htmlspecialchars((string) $driver['email']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <div class="card-head"><h4>Quick Actions</h4></div>
        <div class="quick-actions">
            <a href="<?= app_url('bookings-management.php') ?>">New Booking</a>
            <a href="<?= app_url('bookings-management.php') ?>">Cancel Booking</a>
            <a href="<?= app_url('role-panel.php?view=payments') ?>">Issue Refund</a>
            <a href="<?= app_url('role-panel.php?view=reports') ?>">Print Ticket</a>
        </div>
    </article>

    <article class="card panel-wide">
        <div class="card-head"><h4>Daily Sales Overview</h4></div>
        <canvas
            id="salesChart"
            data-labels='<?= htmlspecialchars(json_encode($snapshot['sales_labels'], JSON_THROW_ON_ERROR), ENT_QUOTES) ?>'
            data-series='<?= htmlspecialchars(json_encode($snapshot['sales_series'], JSON_THROW_ON_ERROR), ENT_QUOTES) ?>'
        ></canvas>
    </article>

    <article class="card panel-wide">
        <div class="card-head"><h4>Registered Bus Agencies</h4></div>
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

