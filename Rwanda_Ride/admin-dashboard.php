<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/dashboard-data.php';

$user = require_auth('admin');
$snapshot = fetch_admin_snapshot();
$agencies = fetch_bus_agencies();
render_head('Admin Dashboard');
render_dashboard_shell($user, 'dashboard');
?>
<section class="metrics-grid">
    <article class="metric-card">
        <p>Total Revenue</p>
        <h3><?= htmlspecialchars(format_frw($snapshot['total_revenue'])) ?></h3>
        <span>Revenue from confirmed and completed bookings</span>
    </article>
    <article class="metric-card">
        <p>Total Bookings</p>
        <h3><?= number_format((int) $snapshot['total_bookings']) ?></h3>
        <span>All trips booked on the platform</span>
    </article>
    <article class="metric-card">
        <p>Active Buses</p>
        <h3><?= number_format((int) $snapshot['active_buses']) ?></h3>
        <span>Units currently in active service</span>
    </article>
    <article class="metric-card">
        <p>Total Users</p>
        <h3><?= number_format((int) $snapshot['total_users']) ?></h3>
        <span>Passengers, drivers, agents and admins</span>
    </article>
</section>

<section class="content-grid admin-grid">
    <article class="card panel-large">
        <div class="card-head"><h4>Revenue Overview</h4></div>
        <canvas
            id="revenueChart"
            data-labels='<?= htmlspecialchars(json_encode($snapshot['revenue_labels'], JSON_THROW_ON_ERROR), ENT_QUOTES) ?>'
            data-series='<?= htmlspecialchars(json_encode($snapshot['revenue_series'], JSON_THROW_ON_ERROR), ENT_QUOTES) ?>'
        ></canvas>
    </article>
    <article class="card">
        <div class="card-head"><h4>Bookings Overview</h4></div>
        <canvas
            id="bookingDonut"
            data-completed="<?= (int) $snapshot['booking_breakdown']['completed'] ?>"
            data-pending="<?= (int) $snapshot['booking_breakdown']['pending'] ?>"
            data-cancelled="<?= (int) $snapshot['booking_breakdown']['cancelled'] ?>"
        ></canvas>
    </article>
    <article class="card">
        <div class="card-head"><h4>Fleet Status</h4></div>
        <canvas
            id="fleetPie"
            data-active="<?= (int) $snapshot['fleet_breakdown']['active'] ?>"
            data-maintenance="<?= (int) $snapshot['fleet_breakdown']['maintenance'] ?>"
            data-inactive="<?= (int) $snapshot['fleet_breakdown']['inactive'] ?>"
        ></canvas>
    </article>
    <article class="card map-card">
        <div class="card-head"><h4>Live Bus Tracking</h4></div>
        <div class="live-map" data-route="kigali-huye"></div>
    </article>
    <article class="card">
        <div class="card-head"><h4>Top Routes</h4></div>
        <ul class="list-lines">
            <?php if ($snapshot['top_routes'] === []): ?>
                <li>No route activity yet <span>0 bookings</span></li>
            <?php else: ?>
                <?php foreach ($snapshot['top_routes'] as $route): ?>
                    <li>
                        <?= htmlspecialchars((string) $route['route_label']) ?>
                        <span><?= (int) $route['total'] ?> bookings</span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </article>
    <article class="card">
        <div class="card-head"><h4>Top Bus Agencies</h4></div>
        <div class="agencies-grid">
            <?php foreach (array_slice($agencies, 0, 3) as $agency): ?>
                <div class="agency-card">
                    <div class="agency-icon" style="background: <?= htmlspecialchars((string) $agency['primary_color']) ?>;">
                        <i class="ri-bus-2-line"></i>
                    </div>
                    <div>
                        <strong><?= htmlspecialchars((string) $agency['agency_name']) ?></strong>
                        <p><?= htmlspecialchars((string) $agency['route_focus']) ?></p>
                    </div>
                    <span class="agency-rating"><i class="ri-star-fill"></i> <?= number_format((float) $agency['rating'], 1) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="card table-card panel-wide">
        <div class="card-head"><h4>Recent Bookings</h4></div>
        <table>
            <thead>
            <tr><th>ID</th><th>Route</th><th>Date</th><th>Amount</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php if ($snapshot['recent_bookings'] === []): ?>
                <tr><td colspan="5">No bookings yet.</td></tr>
            <?php else: ?>
                <?php foreach ($snapshot['recent_bookings'] as $booking): ?>
                    <?php
                    $status = (string) $booking['booking_status'];
                    $statusClass = $status === 'cancelled' ? 'danger' : ($status === 'pending' ? 'warn' : 'ok');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $booking['booking_code']) ?></td>
                        <td><?= htmlspecialchars((string) $booking['origin'] . ' → ' . (string) $booking['destination']) ?></td>
                        <td><?= htmlspecialchars((string) $booking['trip_date']) ?></td>
                        <td><?= htmlspecialchars(format_frw((int) $booking['amount_frw'])) ?></td>
                        <td><span class="status <?= $statusClass ?>"><?= ucfirst($status) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </article>
</section>
<?php end_dashboard_shell(); render_footer(); ?>

