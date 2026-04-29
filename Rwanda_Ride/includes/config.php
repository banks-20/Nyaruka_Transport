<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Kigali');

const APP_NAME = 'NyarukaTransport';
const APP_URL = 'http://localhost/Rwanda_Ride';

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'rwandarite';
const DB_USER = 'root';
const DB_PASS = '';

define('SUPPORT_EMAIL', getenv('SUPPORT_EMAIL') ?: 'wadanny28@gmail.com');
define('SUPPORT_PHONE', getenv('SUPPORT_PHONE') ?: '+250 788 000 100');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'wadanny28@gmail.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME . ' Mailer');
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int) (getenv('SMTP_PORT') ?: 587));
define('SMTP_USER', getenv('SMTP_USER') ?: 'wadanny28@gmail.com');
define('SMTP_PASS', getenv('SMTP_PASS') ?: 'skzohbiquushdwvx');
define('SMTP_SECURE', strtolower((string) (getenv('SMTP_SECURE') ?: 'tls'))); // tls, ssl, or empty for plain

function app_url(string $path = ''): string
{
    $normalizedPath = ltrim($path, '/');
    return rtrim(APP_URL, '/') . ($normalizedPath !== '' ? '/' . $normalizedPath : '');
}

require_once __DIR__ . '/i18n.php';
set_language_from_request();

