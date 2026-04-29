<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/dashboard-data.php';

$user = require_auth('driver');
$snapshot = fetch_driver_snapshot();
$trip = $snapshot['trip'];
render_head('Driver Dashboard');
render_dashboard_shell($user, 'dashboard');
?>
<section class="content-grid driver-grid">
    <article class="card">
        <div class="card-head"><h4>Current Trip</h4></div>
        <?php if ($trip): ?>
            <p class="route-main"><?= htmlspecialchars((string) $trip['origin'] . ' → ' . (string) $trip['destination']) ?></p>
            <p>Bus: <?= htmlspecialchars((string) $trip['plate_number']) ?> | Departure: <?= htmlspecialchars((string) $trip['departure_label']) ?></p>
        <?php else: ?>
            <p class="route-main">No assigned trip</p>
            <p>Your next route assignment will appear here.</p>
        <?php endif; ?>
    </article>
    <article class="card">
        <div class="card-head"><h4>Passenger Count</h4></div>
        <h3><?= (int) $snapshot['passenger_count'] ?></h3>
        <span>Current passengers on this route</span>
    </article>
    <article class="card">
        <div class="card-head"><h4>Earnings Summary</h4></div>
        <h3><?= htmlspecialchars(format_frw((int) $snapshot['earnings'])) ?></h3>
        <span>Estimated trip collection</span>
    </article>
    <article class="card map-card panel-wide">
        <div class="card-head"><h4>Live Tracking Map</h4></div>
        <div class="live-map" data-route="kigali-huye"></div>
    </article>
    <article class="card panel-wide">
        <div class="card-head"><h4>Trip Progress Timeline</h4></div>
        <ul class="timeline">
            <li class="done">Kigali Station - <?= htmlspecialchars((string) ($trip['departure_label'] ?? '07:00 AM')) ?></li>
            <li class="done">Nyamata Stop - 08:10 AM</li>
            <li class="active">Nyanza Checkpoint - 09:20 AM</li>
            <li>Terminal Arrival - ETA <?= htmlspecialchars((string) ($trip['arrival_label'] ?? '10:15 AM')) ?></li>
        </ul>
    </article>
</section>
<?php end_dashboard_shell(); render_footer(); ?>

