<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_auth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_route'])) {
            $stmt = db()->prepare('INSERT INTO routes (origin, destination, distance_km, fare_frw) VALUES (:origin, :destination, :distance, :fare)');
            $stmt->execute([
                ':origin' => trim((string) $_POST['origin']),
                ':destination' => trim((string) $_POST['destination']),
                ':distance' => max(1, (int) $_POST['distance_km']),
                ':fare' => max(100, (int) $_POST['fare_frw']),
            ]);
            set_flash('Route created successfully.');
        } elseif (isset($_POST['update_route'])) {
            $stmt = db()->prepare('UPDATE routes SET origin = :origin, destination = :destination, distance_km = :distance, fare_frw = :fare WHERE id = :id');
            $stmt->execute([
                ':origin' => trim((string) $_POST['origin']),
                ':destination' => trim((string) $_POST['destination']),
                ':distance' => max(1, (int) $_POST['distance_km']),
                ':fare' => max(100, (int) $_POST['fare_frw']),
                ':id' => (int) $_POST['route_id'],
            ]);
            set_flash('Route updated.');
        }
    } catch (Throwable $e) {
        set_flash('Action failed: ' . $e->getMessage());
    }
    header('Location: ' . app_url('routes-management.php'));
    exit;
}

if (isset($_GET['delete'])) {
    try {
        $stmt = db()->prepare('DELETE FROM routes WHERE id = :id');
        $stmt->execute([':id' => (int) $_GET['delete']]);
        set_flash('Route deleted.');
    } catch (Throwable $e) {
        set_flash('Delete blocked: remove dependent trips first.');
    }
    header('Location: ' . app_url('routes-management.php'));
    exit;
}

$routes = db()->query('SELECT id, origin, destination, distance_km, fare_frw FROM routes ORDER BY id DESC')->fetchAll();
$flash = get_flash();

render_head('Route Management');
render_dashboard_shell($user, 'routes');
?>
<section class="crud-page">
    <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <div class="crud-layout">
        <article class="card">
            <div class="card-head"><h4>Add Route</h4></div>
            <form method="post" class="stack-form">
                <input type="text" name="origin" placeholder="Origin (e.g., Kigali)" required>
                <input type="text" name="destination" placeholder="Destination (e.g., Huye)" required>
                <input type="number" name="distance_km" placeholder="Distance (KM)" min="1" required>
                <input type="number" name="fare_frw" placeholder="Fare (FRW)" min="100" required>
                <button class="primary-btn" type="submit" name="create_route">Create Route</button>
            </form>
        </article>

        <article class="card table-card">
            <div class="card-head"><h4>Existing Routes</h4></div>
            <table>
                <thead>
                <tr><th>ID</th><th>Origin</th><th>Destination</th><th>Distance</th><th>Fare</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($routes as $route): ?>
                    <tr>
                        <form method="post">
                            <td>
                                <?= (int) $route['id'] ?>
                                <input type="hidden" name="route_id" value="<?= (int) $route['id'] ?>">
                            </td>
                            <td><input type="text" name="origin" value="<?= htmlspecialchars($route['origin']) ?>" required></td>
                            <td><input type="text" name="destination" value="<?= htmlspecialchars($route['destination']) ?>" required></td>
                            <td><input type="number" name="distance_km" value="<?= (int) $route['distance_km'] ?>" min="1" required></td>
                            <td><input type="number" name="fare_frw" value="<?= (int) $route['fare_frw'] ?>" min="100" required></td>
                            <td class="table-actions">
                                <button class="primary-btn small" type="submit" name="update_route">Save</button>
                                <a class="danger-btn" href="<?= app_url('routes-management.php?delete=' . (int) $route['id']) ?>" onclick="return confirm('Delete this route?')">Delete</a>
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

