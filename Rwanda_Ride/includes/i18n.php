<?php
declare(strict_types=1);

const SUPPORTED_LANGUAGES = ['en', 'rw'];

function current_language(): string
{
    $lang = (string) ($_SESSION['lang'] ?? 'en');
    return in_array($lang, SUPPORTED_LANGUAGES, true) ? $lang : 'en';
}

function set_language_from_request(): void
{
    if (!isset($_GET['lang'])) {
        return;
    }

    $lang = strtolower(trim((string) $_GET['lang']));
    if (in_array($lang, SUPPORTED_LANGUAGES, true)) {
        $_SESSION['lang'] = $lang;
    }
}

function t(string $key): string
{
    static $messages = [
        'en' => [
            'lang_en' => 'English',
            'lang_rw' => 'Kinyarwanda',
            'dashboard' => 'Dashboard',
            'analytics' => 'Analytics',
            'users_mgmt' => 'Users Management',
            'fleet_mgmt' => 'Fleet Management',
            'route_mgmt' => 'Route Management',
            'trip_mgmt' => 'Trip Management',
            'bookings' => 'Bookings',
            'my_bookings' => 'My Bookings',
            'payments' => 'Payments',
            'reports' => 'Reports',
            'notifications' => 'Notifications',
            'settings' => 'Settings',
            'support' => 'Support',
            'logout' => 'Logout',
            'panel' => 'Panel',
            'welcome_back' => 'Welcome back',
            'topbar_subtitle' => 'Manage Rwanda\'s transport operations in real time.',
            'role_login' => 'Role-Based Login',
            'back_to_landing' => 'Back to landing page',
            'landing_title' => 'Smart Transport for Rwanda',
            'landing_subtitle' => 'Safe, Reliable, Affordable',
            'access_platform' => 'Access Platform',
            'login_account' => 'Login to your account',
            'choose_role' => 'Choose your role to continue',
            'landing_intro' => 'NyarukaTransport connects people to places with intelligent route optimization, live tracking, and modern digital ticketing designed for Kigali and beyond.',
            'role_passenger' => 'Passenger',
            'role_driver' => 'Driver',
            'role_agent' => 'Agent',
            'role_admin' => 'Admin',
            'role_passenger_desc' => 'Book and manage trips instantly',
            'role_driver_desc' => 'Track routes and earnings',
            'role_agent_desc' => 'Handle bookings and sales',
            'role_admin_desc' => 'Control system operations',
            'trust_secure' => 'Secure Payments',
            'trust_tracking' => 'Live Tracking',
            'trust_support' => '24/7 Support',
            'trust_service' => 'Trusted Service',
            'who_we_are' => 'Who We Are',
            'who_we_are_p1' => 'We are a modern transport booking platform dedicated to simplifying travel across Rwanda. Our mission is to provide a reliable, efficient, and user-friendly system that connects passengers with transport services in real time.',
            'who_we_are_p2' => 'With a focus on innovation and convenience, we empower agents and transport operators to manage bookings seamlessly, track trips, and deliver better service to customers. Every transaction is built for transparency, speed, and trust.',
            'why_choose' => 'Why People Choose NyarukaTransport',
            'why_passengers_title' => 'For Passengers',
            'why_passengers_desc' => 'Book faster, travel safer, and track your trip from departure to arrival.',
            'why_agents_title' => 'For Agents',
            'why_agents_desc' => 'Handle daily bookings with less stress, better visibility, and higher productivity.',
            'why_operators_title' => 'For Operators',
            'why_operators_desc' => 'Monitor routes, improve seat utilization, and serve customers with confidence.',
            'why_everyone_title' => 'For Everyone',
            'why_everyone_desc' => 'Accurate records, accountable operations, and a dependable platform every day.',
            'contact_us' => 'Contact Us',
            'contact_intro' => 'Ready to grow with us or need support for your next trip? Our team is here for you.',
            'contact_location' => 'Kigali, Rwanda',
            'cta_title' => 'Join the Smart Transport Movement',
            'cta_desc' => 'Whether you are applying as an agent, partnering as an operator, or booking your next journey, NyarukaTransport gives you the modern tools to move with confidence.',
            'cta_button' => 'Get Started Now',
        ],
        'rw' => [
            'lang_en' => 'Icyongereza',
            'lang_rw' => 'Kinyarwanda',
            'dashboard' => 'Imbonerahamwe',
            'analytics' => 'Isesengura',
            'users_mgmt' => 'Imicungire y\'abakoresha',
            'fleet_mgmt' => 'Imicungire y\'imodoka',
            'route_mgmt' => 'Imicungire y\'inzira',
            'trip_mgmt' => 'Imicungire y\'ingendo',
            'bookings' => 'Ibikorwa byo kwiyandikisha',
            'my_bookings' => 'Ibyanjye byo kwiyandikisha',
            'payments' => 'Kwishyura',
            'reports' => 'Raporo',
            'notifications' => 'Amatangazo',
            'settings' => 'Igenamiterere',
            'support' => 'Ubufasha',
            'logout' => 'Sohoka',
            'panel' => 'Urupapuro',
            'welcome_back' => 'Murakaza neza',
            'topbar_subtitle' => 'Cunga ibikorwa by\'ubwikorezi mu Rwanda mu gihe nyacyo.',
            'role_login' => 'Kwinjira hakurikijwe uruhare',
            'back_to_landing' => 'Subira ku rupapuro rw\'ibanze',
            'landing_title' => 'Ubwikorezi Bw\'Ikoranabuhanga ku Rwanda',
            'landing_subtitle' => 'Umutekano, Kwizerwa, Igiciro Gito',
            'access_platform' => 'Injira muri sisiteme',
            'login_account' => 'Injira kuri konti yawe',
            'choose_role' => 'Hitamo uruhare kugirango ukomeze',
            'landing_intro' => 'NyarukaTransport ihuza abantu n\'aho bagana ikoresheje gutegura inzira neza, gukurikirana urugendo mu gihe nyacyo, n\'itike zigezweho z\'ikoranabuhanga zibereye Kigali n\'u Rwanda rwose.',
            'role_passenger' => 'Umugenzi',
            'role_driver' => 'Umushoferi',
            'role_agent' => 'Umukozi',
            'role_admin' => 'Umuyobozi',
            'role_passenger_desc' => 'Bika itike kandi ucunge ingendo vuba',
            'role_driver_desc' => 'Kurikirana inzira n\'inyungu',
            'role_agent_desc' => 'Cunga booking n\'igurisha',
            'role_admin_desc' => 'Genzura ibikorwa bya sisiteme yose',
            'trust_secure' => 'Kwishyura kwizewe',
            'trust_tracking' => 'Gukurikirana live',
            'trust_support' => 'Ubufasha 24/7',
            'trust_service' => 'Serivisi yizewe',
            'who_we_are' => 'Abo Turi Bo',
            'who_we_are_p1' => 'Turi urubuga rugezweho rwo kubika ingendo rugamije koroshya urugendo mu Rwanda hose. Intego yacu ni ugutanga sisiteme yizewe, ikora neza kandi yorohereza abakoresha, ihuza abagenzi n\'abatanga serivisi z\'ubwikorezi mu gihe nyacyo.',
            'who_we_are_p2' => 'Dushyira imbere udushya n\'uburyo bworohereza abakiriya, tugafasha abakozi n\'abakora ubwikorezi gucunga booking neza, gukurikirana ingendo no gutanga serivisi nziza. Buri gikorwa cyose gishingiye ku mucyo, umuvuduko n\'icyizere.',
            'why_choose' => 'Impamvu Bahitamo NyarukaTransport',
            'why_passengers_title' => 'Ku Bagenzi',
            'why_passengers_desc' => 'Bika vuba, ugende neza kandi ukurikirane urugendo rwawe kuva rutangiye kugeza rurangiye.',
            'why_agents_title' => 'Ku Bakozi',
            'why_agents_desc' => 'Cunga booking za buri munsi utaruhijwe, ubone amakuru neza kandi wongere umusaruro.',
            'why_operators_title' => 'Ku Batanga Ubwikorezi',
            'why_operators_desc' => 'Kurikirana inzira, wongere imyanya ikoreshwa kandi uhe abakiriya serivisi yizewe.',
            'why_everyone_title' => 'Kuri Bose',
            'why_everyone_desc' => 'Amakuru nyayo, imikorere iboneye kandi urubuga rwizewe buri munsi.',
            'contact_us' => 'Twandikire',
            'contact_intro' => 'Witeguye gukorana natwe cyangwa ukeneye ubufasha ku rugendo rwawe rukurikira? Ikipe yacu irahari.',
            'contact_location' => 'Kigali, Rwanda',
            'cta_title' => 'Injira mu rugendo rw\'ubwikorezi bugezweho',
            'cta_desc' => 'Waba uri kwiyandikisha nk\'umukozi, ufatanya natwe nk\'utanga ubwikorezi, cyangwa uri kubika urugendo rwawe rukurikira, NyarukaTransport iguha ibikoresho bigezweho byo kugenda ufite icyizere.',
            'cta_button' => 'Tangira Ubu',
        ],
    ];

    $lang = current_language();
    return $messages[$lang][$key] ?? $messages['en'][$key] ?? $key;
}

function language_switch_url(string $lang): string
{
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $parts = parse_url($requestUri);
    $path = (string) ($parts['path'] ?? '/');
    $query = [];
    if (isset($parts['query'])) {
        parse_str((string) $parts['query'], $query);
    }
    $query['lang'] = $lang;
    return $path . '?' . http_build_query($query);
}
