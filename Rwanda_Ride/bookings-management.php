<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_auth();
if (!in_array($user['role'], ['admin', 'agent'], true)) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

$allowedStatus = ['pending', 'confirmed', 'cancelled', 'completed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_booking'])) {
            $status = in_array($_POST['booking_status'] ?? '', $allowedStatus, true) ? (string) $_POST['booking_status'] : 'pending';
            $bookingCode = 'BK' . random_int(10000, 99999);
            $stmt = db()->prepare('INSERT INTO bookings (booking_code, user_id, trip_id, agency_id, seat_number, amount_frw, booking_status) VALUES (:code, :user, :trip, :agency, :seat, :amount, :status)');
            $stmt->execute([
                ':code' => $bookingCode,
                ':user' => (int) $_POST['user_id'],
                ':trip' => (int) $_POST['trip_id'],
                ':agency' => (int) ($_POST['agency_id'] ?? 0),
                ':seat' => strtoupper(trim((string) $_POST['seat_number'])),
                ':amount' => max(100, (int) $_POST['amount_frw']),
                ':status' => $status,
            ]);
            set_flash('Booking created successfully.');
        } elseif (isset($_POST['update_booking'])) {
            $status = in_array($_POST['booking_status'] ?? '', $allowedStatus, true) ? (string) $_POST['booking_status'] : 'pending';
            $stmt = db()->prepare('UPDATE bookings SET agency_id = :agency, seat_number = :seat, amount_frw = :amount, booking_status = :status WHERE id = :id');
            $stmt->execute([
                ':agency' => (int) ($_POST['agency_id'] ?? 0),
                ':seat' => strtoupper(trim((string) $_POST['seat_number'])),
                ':amount' => max(100, (int) $_POST['amount_frw']),
                ':status' => $status,
                ':id' => (int) $_POST['booking_id'],
            ]);
            set_flash('Booking updated.');
        }
    } catch (Throwable $e) {
        set_flash('Action failed: ' . $e->getMessage());
    }
    header('Location: ' . app_url('bookings-management.php'));
    exit;
}

if (isset($_GET['delete'])) {
    try {
        $stmt = db()->prepare('DELETE FROM bookings WHERE id = :id');
        $stmt->execute([':id' => (int) $_GET['delete']]);
        set_flash('Booking deleted.');
    } catch (Throwable $e) {
        set_flash('Delete failed: ' . $e->getMessage());
    }
    header('Location: ' . app_url('bookings-management.php'));
    exit;
}

$passengers = db()->query("SELECT id, full_name FROM users WHERE role = 'passenger' ORDER BY full_name")->fetchAll();
$trips = db()->query("
    SELECT t.id, r.origin, r.destination, DATE_FORMAT(t.departure_time, '%Y-%m-%d %H:%i') AS departure_label
    FROM trips t
    JOIN routes r ON r.id = t.route_id
    ORDER BY t.departure_time DESC
")->fetchAll();
$agencies = db()->query("
    SELECT id, agency_name
    FROM bus_agencies
    ORDER BY agency_name ASC
")->fetchAll();
$bookings = db()->query("
    SELECT b.id, b.booking_code, b.agency_id, b.seat_number, b.amount_frw, b.booking_status, p.full_name,
           COALESCE(a.agency_name, 'Agency TBD') AS agency_name,
           r.origin, r.destination, DATE_FORMAT(t.departure_time, '%Y-%m-%d %H:%i') AS departure_label
    FROM bookings b
    JOIN users p ON p.id = b.user_id
    JOIN trips t ON t.id = b.trip_id
    JOIN routes r ON r.id = t.route_id
    LEFT JOIN bus_agencies a ON a.id = b.agency_id
    ORDER BY b.id DESC
")->fetchAll();

$flash = get_flash();

render_head('Booking Management');
render_dashboard_shell($user, 'bookings');
?>
<section class="crud-page">
    <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <div class="crud-layout">
        <article class="card">
            <div class="card-head"><h4>Create Booking</h4></div>
            <form method="post" class="stack-form">
                <select name="user_id" required>
                    <option value="">Select Passenger</option>
                    <?php foreach ($passengers as $passenger): ?>
                        <option value="<?= (int) $passenger['id'] ?>"><?= htmlspecialchars($passenger['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="trip_id" required>
                    <option value="">Select Trip</option>
                    <?php foreach ($trips as $trip): ?>
                        <option value="<?= (int) $trip['id'] ?>">
                            <?= htmlspecialchars($trip['origin'] . ' → ' . $trip['destination'] . ' | ' . $trip['departure_label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="agency_id" required>
                    <option value="">Select Bus Agency</option>
                    <?php foreach ($agencies as $agency): ?>
                        <option value="<?= (int) $agency['id'] ?>"><?= htmlspecialchars((string) $agency['agency_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="seat_number" placeholder="Seat Number (e.g., 21A)" required>
                <input type="number" name="amount_frw" placeholder="Amount (FRW)" min="100" required>
                <select name="booking_status" required>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <button class="primary-btn" type="submit" name="create_booking">Create Booking</button>
            </form>
        </article>

        <article class="card table-card">
            <div class="card-head"><h4>All Bookings</h4></div>
            <table>
                <thead>
                <tr><th>Code</th><th>Passenger</th><th>Trip</th><th>Agency</th><th>Seat</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <form method="post">
                            <td>
                                <?= htmlspecialchars($booking['booking_code']) ?>
                                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                            </td>
                            <td><?= htmlspecialchars($booking['full_name']) ?></td>
                            <td><?= htmlspecialchars($booking['origin'] . ' → ' . $booking['destination']) ?><br><small><?= htmlspecialchars($booking['departure_label']) ?></small></td>
                            <td>
                                <select name="agency_id" required>
                                    <?php foreach ($agencies as $agency): ?>
                                        <option value="<?= (int) $agency['id'] ?>" <?= (int) $booking['agency_id'] === (int) $agency['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $agency['agency_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="seat_number" value="<?= htmlspecialchars($booking['seat_number']) ?>" required></td>
                            <td><input type="number" name="amount_frw" value="<?= (int) $booking['amount_frw'] ?>" min="100" required></td>
                            <td>
                                <select name="booking_status">
                                    <?php foreach ($allowedStatus as $status): ?>
                                        <option value="<?= $status ?>" <?= $booking['booking_status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="table-actions">
                                <button class="primary-btn small" type="submit" name="update_booking">Save</button>
                                <a class="danger-btn" href="<?= app_url('bookings-management.php?delete=' . (int) $booking['id']) ?>" onclick="return confirm('Delete this booking?')">Delete</a>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>
    </div>
</section>
<?php end_dashboard_shell(); render_footer(); ?>

