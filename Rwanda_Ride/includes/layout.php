<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function render_head(string $title): void
{
    ?>
    <!doctype html>
    <html lang="<?= htmlspecialchars(current_language()) ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> | <?= APP_NAME ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            (function () {
                var theme = localStorage.getItem('nyaruka-theme');
                if (theme === 'dark') {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>
        <link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
    </head>
    <body>
    <?php
}

function render_footer(): void
{
    ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="<?= app_url('assets/js/app.js') ?>"></script>
    </body>
    </html>
    <?php
}

function role_badge_class(string $role): string
{
    return match ($role) {
        'driver' => 'role-driver',
        'agent' => 'role-agent',
        'admin' => 'role-admin',
        default => 'role-passenger',
    };
}

function render_dashboard_shell(array $user, string $active): void
{
    $menu = match ($user['role']) {
        'admin' => [
            ['id' => 'dashboard', 'label' => t('dashboard'), 'icon' => 'ri-dashboard-line', 'href' => app_url('admin-dashboard.php')],
            ['id' => 'analytics', 'label' => t('analytics'), 'icon' => 'ri-line-chart-line', 'href' => app_url('role-panel.php?view=analytics')],
            ['id' => 'users', 'label' => t('users_mgmt'), 'icon' => 'ri-team-line', 'href' => app_url('role-panel.php?view=users')],
            ['id' => 'fleet', 'label' => t('fleet_mgmt'), 'icon' => 'ri-bus-2-line', 'href' => app_url('fleet-management.php')],
            ['id' => 'routes', 'label' => t('route_mgmt'), 'icon' => 'ri-road-map-line', 'href' => app_url('routes-management.php')],
            ['id' => 'trips', 'label' => t('trip_mgmt'), 'icon' => 'ri-route-line', 'href' => app_url('role-panel.php?view=trips')],
            ['id' => 'bookings', 'label' => t('bookings'), 'icon' => 'ri-ticket-line', 'href' => app_url('bookings-management.php')],
            ['id' => 'payments', 'label' => t('payments'), 'icon' => 'ri-wallet-3-line', 'href' => app_url('role-panel.php?view=payments')],
            ['id' => 'reports', 'label' => t('reports'), 'icon' => 'ri-file-chart-line', 'href' => app_url('role-panel.php?view=reports')],
            ['id' => 'notifications', 'label' => t('notifications'), 'icon' => 'ri-notification-3-line', 'href' => app_url('role-panel.php?view=notifications')],
            ['id' => 'settings', 'label' => t('settings'), 'icon' => 'ri-settings-3-line', 'href' => app_url('role-panel.php?view=settings')],
        ],
        'driver' => [
            ['id' => 'dashboard', 'label' => t('dashboard'), 'icon' => 'ri-dashboard-line', 'href' => app_url('driver-dashboard.php')],
            ['id' => 'trips', 'label' => t('trip_mgmt'), 'icon' => 'ri-route-line', 'href' => app_url('role-panel.php?view=trips')],
            ['id' => 'notifications', 'label' => t('notifications'), 'icon' => 'ri-notification-3-line', 'href' => app_url('role-panel.php?view=notifications')],
            ['id' => 'settings', 'label' => t('settings'), 'icon' => 'ri-settings-3-line', 'href' => app_url('role-panel.php?view=settings')],
        ],
        'agent' => [
            ['id' => 'dashboard', 'label' => t('dashboard'), 'icon' => 'ri-dashboard-line', 'href' => app_url('agent-dashboard.php')],
            ['id' => 'bookings', 'label' => t('bookings'), 'icon' => 'ri-ticket-line', 'href' => app_url('bookings-management.php')],
            ['id' => 'payments', 'label' => t('payments'), 'icon' => 'ri-wallet-3-line', 'href' => app_url('role-panel.php?view=payments')],
            ['id' => 'reports', 'label' => t('reports'), 'icon' => 'ri-file-chart-line', 'href' => app_url('role-panel.php?view=reports')],
            ['id' => 'settings', 'label' => t('settings'), 'icon' => 'ri-settings-3-line', 'href' => app_url('role-panel.php?view=settings')],
        ],
        default => [
            ['id' => 'dashboard', 'label' => t('dashboard'), 'icon' => 'ri-dashboard-line', 'href' => app_url('passenger-dashboard.php')],
            ['id' => 'bookings', 'label' => t('my_bookings'), 'icon' => 'ri-ticket-line', 'href' => app_url('role-panel.php?view=bookings')],
            ['id' => 'payments', 'label' => t('payments'), 'icon' => 'ri-wallet-3-line', 'href' => app_url('role-panel.php?view=payments')],
            ['id' => 'support', 'label' => t('support'), 'icon' => 'ri-customer-service-2-line', 'href' => app_url('role-panel.php?view=support')],
            ['id' => 'settings', 'label' => t('settings'), 'icon' => 'ri-settings-3-line', 'href' => app_url('role-panel.php?view=settings')],
        ]
    };
    ?>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="brand">
                <img src="<?= app_url('assets/images/nyaruka-logo.png') ?>" alt="<?= APP_NAME ?> logo" class="brand-logo">
                <div>
                    <strong><?= APP_NAME ?></strong>
                    <span class="<?= role_badge_class($user['role']) ?>"><?= ucfirst($user['role']) . ' ' . t('panel') ?></span>
                </div>
            </div>
            <nav class="side-nav">
                <?php foreach ($menu as $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="nav-item <?= $item['id'] === $active ? 'active' : '' ?>">
                        <i class="<?= $item['icon'] ?>"></i>
                        <span><?= htmlspecialchars($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <a href="<?= app_url('logout.php') ?>" class="logout-btn"><i class="ri-logout-box-r-line"></i> <?= t('logout') ?></a>
        </aside>
        <main class="dashboard-main">
            <header class="topbar glass">
                <div>
                    <h2><?= t('welcome_back') ?>, <?= htmlspecialchars($user['full_name']) ?></h2>
                    <p><?= t('topbar_subtitle') ?></p>
                </div>
                <div class="topbar-actions">
                    <form method="get" class="language-form">
                        <label class="visually-hidden" for="lang-select">Language</label>
                        <?php foreach ($_GET as $param => $value): ?>
                            <?php if ($param !== 'lang' && is_scalar($value)): ?>
                                <input type="hidden" name="<?= htmlspecialchars((string) $param) ?>" value="<?= htmlspecialchars((string) $value) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <select id="lang-select" name="lang" class="language-select" onchange="this.form.submit()">
                            <option value="en" <?= current_language() === 'en' ? 'selected' : '' ?>><?= t('lang_en') ?></option>
                            <option value="rw" <?= current_language() === 'rw' ? 'selected' : '' ?>><?= t('lang_rw') ?></option>
                        </select>
                    </form>
                    <a class="icon-btn" href="<?= app_url('role-panel.php?view=notifications') ?>" title="<?= t('notifications') ?>"><i class="ri-notification-3-line"></i></a>
                    <button class="icon-btn" data-theme-toggle title="Toggle dark mode"><i class="ri-contrast-2-line"></i></button>
                    <div class="avatar" style="background: <?= htmlspecialchars($user['avatar_color']) ?>;">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                </div>
            </header>
    <?php
}

function end_dashboard_shell(): void
{
    ?>
        </main>
    </div>
    <?php
}

