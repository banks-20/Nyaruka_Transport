<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/dashboard-data.php';
$agencies = fetch_bus_agencies();
render_head('Smart Transport for Rwanda');
?>
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
<section class="landing-shell">
    <div class="hero-bg"></div>
    <div class="landing-content">
        <header class="landing-header">
            <div class="brand-inline">
                <img src="<?= app_url('assets/images/nyaruka-logo.png') ?>" alt="<?= APP_NAME ?> logo" class="landing-logo">
                <strong>NyarukaTransport</strong>
            </div>
            <form method="get">
                <select class="language-pill-select" name="lang" onchange="this.form.submit()">
                    <option value="en" <?= current_language() === 'en' ? 'selected' : '' ?>><?= t('lang_en') ?></option>
                    <option value="rw" <?= current_language() === 'rw' ? 'selected' : '' ?>><?= t('lang_rw') ?></option>
                </select>
            </form>
        </header>

        <div class="hero-grid">
            <div class="hero-copy">
                <h1 style="color:#ffffff; text-shadow:0 8px 30px rgba(0,0,0,0.35);"><?= t('landing_title') ?></h1>
                <p class="subtitle" style="color:#cfe3ff; text-shadow:0 4px 18px rgba(0,0,0,0.28);"><?= t('landing_subtitle') ?></p>
                <p style="color:#e9f1ff; text-shadow:0 4px 18px rgba(0,0,0,0.24);">
                    <?= t('landing_intro') ?>
                </p>
                <a class="primary-btn" href="<?= app_url('login.php') ?>"><?= t('access_platform') ?></a>
            </div>
            <div class="bus-visual glass">
                <div class="bus-tag">Nyaruka Express</div>
                <img class="hero-bus-image" src="<?= app_url('assets/images/nyaruka-hero.png') ?>" alt="Nyaruka modern bus">
            </div>
        </div>

        <div class="role-selector glass">
            <h3><?= t('login_account') ?></h3>
            <p><?= t('choose_role') ?></p>
            <div class="role-grid">
                <a href="<?= app_url('login.php?role=passenger') ?>" class="role-card passenger">
                    <i class="ri-user-3-line"></i>
                    <strong><?= t('role_passenger') ?></strong>
                    <span><?= t('role_passenger_desc') ?></span>
                </a>
                <a href="<?= app_url('login.php?role=driver') ?>" class="role-card driver">
                    <i class="ri-steering-2-line"></i>
                    <strong><?= t('role_driver') ?></strong>
                    <span><?= t('role_driver_desc') ?></span>
                </a>
                <a href="<?= app_url('login.php?role=agent') ?>" class="role-card agent">
                    <i class="ri-customer-service-2-line"></i>
                    <strong><?= t('role_agent') ?></strong>
                    <span><?= t('role_agent_desc') ?></span>
                </a>
                <a href="<?= app_url('login.php?role=admin') ?>" class="role-card admin">
                    <i class="ri-shield-user-line"></i>
                    <strong><?= t('role_admin') ?></strong>
                    <span><?= t('role_admin_desc') ?></span>
                </a>
            </div>
        </div>

        <div class="trust-bar glass">
            <span><i class="ri-shield-check-line"></i> <?= t('trust_secure') ?></span>
            <span><i class="ri-map-pin-time-line"></i> <?= t('trust_tracking') ?></span>
            <span><i class="ri-customer-service-line"></i> <?= t('trust_support') ?></span>
            <span><i class="ri-checkbox-circle-line"></i> <?= t('trust_service') ?></span>
        </div>

        <article class="landing-panel glass agencies-panel">
            <h3>Bus Agencies</h3>
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

        <section class="landing-sections">
            <article class="landing-panel glass who-we-are-panel">
                <h3><?= t('who_we_are') ?></h3>
                <p><?= t('who_we_are_p1') ?></p>
                <p><?= t('who_we_are_p2') ?></p>
            </article>

            <article class="landing-panel glass">
                <h3><?= t('why_choose') ?></h3>
                <div class="motivation-grid">
                    <div class="motivation-item">
                        <i class="ri-user-heart-line"></i>
                        <h4><?= t('why_passengers_title') ?></h4>
                        <p><?= t('why_passengers_desc') ?></p>
                    </div>
                    <div class="motivation-item">
                        <i class="ri-store-2-line"></i>
                        <h4><?= t('why_agents_title') ?></h4>
                        <p><?= t('why_agents_desc') ?></p>
                    </div>
                    <div class="motivation-item">
                        <i class="ri-bus-2-line"></i>
                        <h4><?= t('why_operators_title') ?></h4>
                        <p><?= t('why_operators_desc') ?></p>
                    </div>
                    <div class="motivation-item">
                        <i class="ri-shield-check-line"></i>
                        <h4><?= t('why_everyone_title') ?></h4>
                        <p><?= t('why_everyone_desc') ?></p>
                    </div>
                </div>
            </article>

            <article class="landing-panel glass contact-panel">
                <div>
                    <h3><?= t('contact_us') ?></h3>
                    <p><?= t('contact_intro') ?></p>
                    <ul class="contact-list">
                        <li><i class="ri-mail-line"></i> <?= htmlspecialchars(SUPPORT_EMAIL) ?></li>
                        <li><i class="ri-phone-line"></i> <?= htmlspecialchars(SUPPORT_PHONE) ?></li>
                        <li><i class="ri-map-pin-line"></i> <?= t('contact_location') ?></li>
                    </ul>
                </div>
                <div class="contact-cta">
                    <h4><?= t('cta_title') ?></h4>
                    <p><?= t('cta_desc') ?></p>
                    <a class="primary-btn" href="<?= app_url('login.php') ?>"><?= t('cta_button') ?></a>
                </div>
            </article>
        </section>
    </div>
</section>
<?php render_footer(); ?>

