<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$allowedRoles = ['passenger', 'driver', 'agent', 'admin'];
$role = $_GET['role'] ?? 'passenger';
$role = in_array($role, $allowedRoles, true) ? $role : 'passenger';
$error = $_SESSION['auth_error'] ?? null;
unset($_SESSION['auth_error']);

render_head('Role Login');
?>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
<section class="auth-shell">
    <div class="auth-card glass">
        <div class="auth-top">
            <a href="<?= app_url('index.php') ?>" class="back-link"><i class="ri-arrow-left-line"></i> <?= t('back_to_landing') ?></a>
            <form method="get" class="auth-lang-form">
                <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
                <select class="language-pill-select" name="lang" onchange="this.form.submit()">
                    <option value="en" <?= current_language() === 'en' ? 'selected' : '' ?>><?= t('lang_en') ?></option>
                    <option value="rw" <?= current_language() === 'rw' ? 'selected' : '' ?>><?= t('lang_rw') ?></option>
                </select>
            </form>
        </div>
        <h1><?= t('role_login') ?></h1>
        <p>Sign in to continue to your <?= ucfirst($role) ?> workspace.</p>

        <?php if ($error): ?>
            <div class="alert-error"><?= htmlspecialchars((string) $error) ?></div>
        <?php endif; ?>

        <div class="tabs">
            <?php foreach ($allowedRoles as $item): ?>
                <a class="tab <?= $item === $role ? 'active' : '' ?>" href="<?= app_url('login.php?role=' . $item) ?>">
                    <?= ucfirst($item) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form action="<?= app_url('process-login.php') ?>" method="post" class="auth-form">
            <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
            <label>Full Name
                <input type="text" name="full_name" placeholder="e.g., Jean Claude">
            </label>
            <label>Email
                <input type="email" name="email" placeholder="name@example.com" required>
            </label>
            <label>Password
                <input type="password" name="password" placeholder="Minimum 6 characters" minlength="6" required>
            </label>
            <button type="submit" class="primary-btn full">Continue as <?= ucfirst($role) ?></button>
        </form>

        <div class="demo-credentials">
            <h4>Real Account Access</h4>
            <p>
                Use your normal email and password. If your account does not exist yet, it will be
                created automatically under the selected role and you will be logged in instantly.
                Driver accounts are created by agents and credentials are sent to the registered email.
            </p>
        </div>
    </div>
</section>
<?php render_footer(); ?>

