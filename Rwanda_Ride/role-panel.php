<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/dashboard-data.php';

$user = require_auth();
$role = (string) $user['role'];
$view = (string) ($_GET['view'] ?? 'dashboard');

$allowedViews = match ($role) {
    'admin' => ['analytics', 'users', 'trips', 'payments', 'reports', 'notifications', 'settings'],
    'driver' => ['trips', 'notifications', 'settings'],
    'agent' => ['payments', 'reports', 'settings'],
    default => ['bookings', 'payments', 'notifications', 'support', 'settings'],
};

if (!in_array($view, $allowedViews, true)) {
    header('Location: ' . app_url('dashboard.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($view === 'settings' && isset($_POST['save_profile'])) {
            $fullName = trim((string) ($_POST['full_name'] ?? ''));
            $avatarColor = trim((string) ($_POST['avatar_color'] ?? '#1f6feb'));
            if ($fullName === '') {
                throw new RuntimeException('Full name is required.');
            }

            $stmt = db()->prepare('UPDATE users SET full_name = :full_name, avatar_color = :avatar_color WHERE id = :id');
            $stmt->execute([
                ':full_name' => $fullName,
                ':avatar_color' => $avatarColor,
                ':id' => (int) $user['id'],
            ]);
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['avatar_color'] = $avatarColor;
            set_flash('Profile settings updated successfully.');
        }

        if ($role === 'passenger' && $view === 'bookings' && isset($_POST['cancel_booking'])) {
            $bookingId = (int) ($_POST['booking_id'] ?? 0);
            $stmt = db()->prepare("UPDATE bookings SET booking_status = 'cancelled' WHERE id = :id AND user_id = :user_id AND booking_status = 'pending'");
            $stmt->execute([
                ':id' => $bookingId,
                ':user_id' => (int) $user['id'],
            ]);
            set_flash($stmt->rowCount() > 0 ? 'Booking cancelled successfully.' : 'Booking could not be cancelled.');
        }
    } catch (Throwable $e) {
        set_flash('Action failed: ' . $e->getMessage());
    }

    header('Location: ' . app_url('role-panel.php?view=' . urlencode($view)));
    exit;
}

$flash = get_flash();

$title = match ($view) {
    'analytics' => 'Analytics',
    'users' => 'Users Management',
    'trips' => 'Trip Management',
    'bookings' => 'My Bookings',
    'payments' => 'Payments',
    'reports' => 'Reports',
    'notifications' => 'Notifications',
    'support' => 'Support',
    'settings' => 'Settings',
    default => 'Panel',
};

render_head($title);
render_dashboard_shell($user, $view);
?>
<section class="crud-page">
    <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

    <?php if ($view === 'analytics'): ?>
        <?php
        $monthlyRows = db()->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_label,
                   COUNT(*) AS total_bookings,
                   COALESCE(SUM(CASE WHEN booking_status IN ('confirmed','completed') THEN amount_frw ELSE 0 END),0) AS total_revenue
            FROM bookings
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month_label DESC
            LIMIT 8
        ")->fetchAll();
        ?>
        <article class="card table-card">
            <div class="card-head"><h4>Booking and Revenue Analytics</h4></div>
            <table>
                <thead><tr><th>Month</th><th>Bookings</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php foreach ($monthlyRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['month_label']) ?></td>
                        <td><?= number_format((int) $row['total_bookings']) ?></td>
                        <td><?= htmlspecialchars(format_frw((int) $row['total_revenue'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>
    <?php endif; ?>

    <?php if ($view === 'users'): ?>
        <?php
        $roleRows = db()->query("SELECT role, COUNT(*) AS total FROM users GROUP BY role ORDER BY role ASC")->fetchAll();
        $userRows = db()->query("SELECT full_name, email, role, created_at FROM users ORDER BY id DESC LIMIT 20")->fetchAll();
        ?>
        <section class="metrics-grid">
            <?php foreach ($roleRows as $row): ?>
                <article class="metric-card">
                    <p><?= ucfirst((string) $row['role']) ?></p>
                    <h3><?= number_format((int) $row['total']) ?></h3>
                    <span>Registered accounts</span>
                </article>
            <?php endforeach; ?>
        </section>
        <article class="card table-card">
            <div class="card-head"><h4>Recent Users</h4></div>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($userRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['full_name']) ?></td>
                        <td><?= htmlspecialchars((string) $row['email']) ?></td>
                        <td><?= ucfirst((string) $row['role']) ?></td>
                        <td><?= htmlspecialchars((string) $row['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>
    <?php endif; ?>

    <?php if ($view === 'trips'): ?>
        <?php
        $trips = db()->query("
            SELECT t.id, r.origin, r.destination, b.plate_number, t.departure_time, t.arrival_time, t.status
            FROM trips t
            JOIN routes r ON r.id = t.route_id
            JOIN buses b ON b.id = t.bus_id
            ORDER BY t.departure_time DESC
            LIMIT 30
        ")->fetchAll();
        ?>
        <article class="card table-card">
            <div class="card-head"><h4>Scheduled Trips</h4></div>
            <table>
                <thead><tr><th>ID</th><th>Route</th><th>Bus</th><th>Departure</th><th>Arrival</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($trips as $trip): ?>
                    <?php $statusClass = ((string) $trip['status']) === 'cancelled' ? 'danger' : (((string) $trip['status']) === 'scheduled' ? 'warn' : 'ok'); ?>
                    <tr>
                        <td><?= (int) $trip['id'] ?></td>
                        <td><?= htmlspecialchars((string) $trip['origin'] . ' → ' . (string) $trip['destination']) ?></td>
                        <td><?= htmlspecialchars((string) $trip['plate_number']) ?></td>
                        <td><?= htmlspecialchars((string) $trip['departure_time']) ?></td>
                        <td><?= htmlspecialchars((string) $trip['arrival_time']) ?></td>
                        <td><span class="status <?= $statusClass ?>"><?= ucfirst((string) $trip['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>
    <?php endif; ?>

    <?php if ($view === 'bookings'): ?>
        <?php
        $bookings = db()->prepare("
            SELECT b.id, b.booking_code, r.origin, r.destination, t.departure_time, b.amount_frw, b.booking_status
            FROM bookings b
            JOIN trips t ON t.id = b.trip_id
            JOIN routes r ON r.id = t.route_id
            WHERE b.user_id = :user_id
            ORDER BY b.id DESC
        ");
        $bookings->execute([':user_id' => (int) $user['id']]);
        $bookingRows = $bookings->fetchAll();
        ?>
        <article class="card table-card">
            <div class="card-head"><h4>My Bookings</h4></div>
            <table>
                <thead><tr><th>Code</th><th>Route</th><th>Departure</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($bookingRows as $row): ?>
                    <?php $status = (string) $row['booking_status']; ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['booking_code']) ?></td>
                        <td><?= htmlspecialchars((string) $row['origin'] . ' → ' . (string) $row['destination']) ?></td>
                        <td><?= htmlspecialchars((string) $row['departure_time']) ?></td>
                        <td><?= htmlspecialchars(format_frw((int) $row['amount_frw'])) ?></td>
                        <td><span class="status <?= $status === 'cancelled' ? 'danger' : ($status === 'pending' ? 'warn' : 'ok') ?>"><?= ucfirst($status) ?></span></td>
                        <td>
                            <?php if ($status === 'pending'): ?>
                                <form method="post">
                                    <input type="hidden" name="booking_id" value="<?= (int) $row['id'] ?>">
                                    <button class="danger-btn" type="submit" name="cancel_booking">Cancel</button>
                                </form>
                            <?php else: ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>
    <?php endif; ?>

    <?php if ($view === 'payments'): ?>
        <?php
        if ($role === 'passenger') {
            $payments = db()->prepare("
                SELECT b.booking_code, b.amount_frw, p.method, p.payment_status, p.paid_at
                FROM bookings b
                LEFT JOIN payments p ON p.booking_id = b.id
                WHERE b.user_id = :user_id
                ORDER BY b.id DESC
            ");
            $payments->execute([':user_id' => (int) $user['id']]);
            $paymentRows = $payments->fetchAll();
        } else {
            $paymentRows = db()->query("
                SELECT b.booking_code, u.full_name, b.amount_frw, p.method, p.payment_status, p.paid_at
                FROM bookings b
                JOIN users u ON u.id = b.user_id
                LEFT JOIN payments p ON p.booking_id = b.id
                ORDER BY b.id DESC
                LIMIT 40
            ")->fetchAll();
        }
        ?>
        <article class="card table-card">
            <div class="card-head"><h4>Payment Records</h4></div>
            <table>
                <thead>
                <tr>
                    <th>Booking</th>
                    <?php if ($role !== 'passenger'): ?><th>Passenger</th><?php endif; ?>
                    <th>Amount</th><th>Method</th><th>Status</th><th>Paid At</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($paymentRows as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['booking_code']) ?></td>
                        <?php if ($role !== 'passenger'): ?><td><?= htmlspecialchars((string) $row['full_name']) ?></td><?php endif; ?>
                        <td><?= htmlspecialchars(format_frw((int) $row['amount_frw'])) ?></td>
                        <td><?= htmlspecialchars((string) ($row['method'] ?? 'N/A')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['payment_status'] ?? 'pending')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['paid_at'] ?? 'Not paid')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </article>
    <?php endif; ?>

    <?php if ($view === 'reports'): ?>
        <?php
        $summary = db()->query("
            SELECT
                COUNT(*) AS total_bookings,
                COALESCE(SUM(CASE WHEN booking_status IN ('confirmed','completed') THEN amount_frw ELSE 0 END),0) AS total_revenue
            FROM bookings
        ")->fetch() ?: [];
        $topRoute = db()->query("
            SELECT CONCAT(r.origin, ' → ', r.destination) AS route_label, COUNT(b.id) AS bookings_total
            FROM routes r
            LEFT JOIN trips t ON t.route_id = r.id
            LEFT JOIN bookings b ON b.trip_id = t.id
            GROUP BY r.id
            ORDER BY bookings_total DESC
            LIMIT 1
        ")->fetch() ?: null;
        ?>
        <section class="metrics-grid">
            <article class="metric-card"><p>Total Bookings</p><h3><?= number_format((int) ($summary['total_bookings'] ?? 0)) ?></h3><span>Platform wide</span></article>
            <article class="metric-card"><p>Total Revenue</p><h3><?= htmlspecialchars(format_frw((int) ($summary['total_revenue'] ?? 0))) ?></h3><span>Confirmed and completed</span></article>
            <article class="metric-card"><p>Top Route</p><h3><?= htmlspecialchars((string) ($topRoute['route_label'] ?? 'N/A')) ?></h3><span><?= number_format((int) ($topRoute['bookings_total'] ?? 0)) ?> bookings</span></article>
            <article class="metric-card"><p>Generated</p><h3><?= date('Y-m-d') ?></h3><span>Live data snapshot</span></article>
        </section>
    <?php endif; ?>

    <?php if ($view === 'notifications'): ?>
        <?php
        $recentNotifications = db()->query("
            SELECT CONCAT('Booking ', booking_code, ' is ', booking_status) AS note, created_at
            FROM bookings
            ORDER BY created_at DESC
            LIMIT 12
        ")->fetchAll();
        ?>
        <article class="card">
            <div class="card-head"><h4>Recent Notifications</h4></div>
            <ul class="list-lines">
                <?php foreach ($recentNotifications as $notification): ?>
                    <li>
                        <?= htmlspecialchars((string) $notification['note']) ?>
                        <span><?= htmlspecialchars((string) $notification['created_at']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>
    <?php endif; ?>

    <?php if ($view === 'support'): ?>
        <article class="card">
            <div class="card-head"><h4>Support Center</h4></div>
            <p>Need help? Contact NyarukaTransport support team.</p>
            <ul class="list-lines">
                <li>Email <span><?= htmlspecialchars(SUPPORT_EMAIL) ?></span></li>
                <li>Hotline <span><?= htmlspecialchars(SUPPORT_PHONE) ?></span></li>
                <li>Hours <span>24/7 support</span></li>
            </ul>
        </article>
    <?php endif; ?>

    <?php if ($view === 'settings'): ?>
        <article class="card">
            <div class="card-head"><h4>Profile Settings</h4></div>
            <form method="post" class="stack-form">
                <input type="text" name="full_name" value="<?= htmlspecialchars((string) $user['full_name']) ?>" required>
                <input type="email" value="<?= htmlspecialchars((string) $user['email']) ?>" disabled>
                <input type="color" name="avatar_color" value="<?= htmlspecialchars((string) $user['avatar_color']) ?>" aria-label="Avatar color">
                <button class="primary-btn" type="submit" name="save_profile">Save Settings</button>
            </form>
        </article>
    <?php endif; ?>
</section>
<?php end_dashboard_shell(); render_footer(); ?>
