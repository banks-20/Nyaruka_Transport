<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_auth('admin');
$allowedStatus = ['active', 'maintenance', 'inactive'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['create_bus'])) {
            $status = in_array($_POST['status'] ?? '', $allowedStatus, true) ? (string) $_POST['status'] : 'active';
            $stmt = db()->prepare('INSERT INTO buses (plate_number, model_name, seat_capacity, status) VALUES (:plate, :model, :seats, :status)');
            $stmt->execute([
                ':plate' => strtoupper(trim((string) $_POST['plate_number'])),
                ':model' => trim((string) $_POST['model_name']),
                ':seats' => max(10, (int) $_POST['seat_capacity']),
                ':status' => $status,
            ]);
            set_flash('Fleet unit added.');
        } elseif (isset($_POST['update_bus'])) {
            $status = in_array($_POST['status'] ?? '', $allowedStatus, true) ? (string) $_POST['status'] : 'active';
            $stmt = db()->prepare('UPDATE buses SET plate_number = :plate, model_name = :model, seat_capacity = :seats, status = :status WHERE id = :id');
            $stmt->execute([
                ':plate' => strtoupper(trim((string) $_POST['plate_number'])),
                ':model' => trim((string) $_POST['model_name']),
                ':seats' => max(10, (int) $_POST['seat_capacity']),
                ':status' => $status,
                ':id' => (int) $_POST['bus_id'],
            ]);
            set_flash('Fleet record updated.');
        }
    } catch (Throwable $e) {
        set_flash('Action failed: ' . $e->getMessage());
    }
    header('Location: ' . app_url('fleet-management.php'));
    exit;
}

if (isset($_GET['delete'])) {
    try {
        $stmt = db()->prepare('DELETE FROM buses WHERE id = :id');
        $stmt->execute([':id' => (int) $_GET['delete']]);
        set_flash('Bus removed.');
    } catch (Throwable $e) {
        set_flash('Delete blocked: bus is tied to trips.');
    }
    header('Location: ' . app_url('fleet-management.php'));
    exit;
}

$buses = db()->query('SELECT id, plate_number, model_name, seat_capacity, status FROM buses ORDER BY id DESC')->fetchAll();
$flash = get_flash();

render_head('Fleet Management');
render_dashboard_shell($user, 'fleet');
?>
<section class="crud-page">
    <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <div class="crud-layout">
        <article class="card">
            <div class="card-head"><h4>Add Bus</h4></div>
            <form method="post" class="stack-form">
                <input type="text" name="plate_number" placeholder="Plate Number (e.g., RAB123A)" required>
                <input type="text" name="model_name" placeholder="Model Name" required>
                <input type="number" name="seat_capacity" placeholder="Seat Capacity" min="10" required>
                <select name="status" required>
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button class="primary-btn" type="submit" name="create_bus">Add to Fleet</button>
            </form>
        </article>

        <article class="card table-card">
            <div class="card-head"><h4>Fleet Inventory</h4></div>
            <table>
                <thead>
                <tr><th>ID</th><th>Plate</th><th>Model</th><th>Seats</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($buses as $bus): ?>
                    <tr>
                        <form method="post">
                            <td>
                                <?= (int) $bus['id'] ?>
                                <input type="hidden" name="bus_id" value="<?= (int) $bus['id'] ?>">
                            </td>
                            <td><input type="text" name="plate_number" value="<?= htmlspecialchars($bus['plate_number']) ?>" required></td>
                            <td><input type="text" name="model_name" value="<?= htmlspecialchars($bus['model_name']) ?>" required></td>
                            <td><input type="number" name="seat_capacity" value="<?= (int) $bus['seat_capacity'] ?>" min="10" required></td>
                            <td>
                                <select name="status">
                                    <?php foreach ($allowedStatus as $status): ?>
                                        <option value="<?= $status ?>" <?= $bus['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="table-actions">
                                <button class="primary-btn small" type="submit" name="update_bus">Save</button>
                                <a class="danger-btn" href="<?= app_url('fleet-management.php?delete=' . (int) $bus['id']) ?>" onclick="return confirm('Delete this bus?')">Delete</a>
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

