<?php
declare(strict_types=1);

function load_environment(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === '' || getenv($key) !== false) {
            continue;
        }
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

load_environment(dirname(__DIR__) . '/.env');

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('veltor_session');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob:; connect-src 'self'");

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/translations.php';

$language = $_GET['lang'] ?? $_COOKIE['veltor_language'] ?? 'en';
if (!in_array($language, ['en', 'fr', 'ar'], true)) {
    $language = 'en';
}
if (isset($_GET['lang'])) {
    setcookie('veltor_language', $language, [
        'expires' => time() + 31536000,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
$GLOBALS['language'] = $language;

set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    if (!headers_sent()) {
        http_response_code($exception instanceof PDOException ? 503 : 500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $lang = $GLOBALS['language'] ?? 'en';
    $message = $exception instanceof PDOException
        ? t('db_setup')
        : ($lang === 'ar'
            ? 'تعذر إكمال الطلب. حاولوا مجدداً أو تواصلوا مع المتجر.'
            : ($lang === 'fr'
                ? 'La demande n’a pas pu aboutir. Réessayez ou contactez la boutique.'
                : 'The request could not be completed. Please try again or contact the store.'));
    echo '<!doctype html><html lang="' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>VELTOR</title><body style="font:16px/1.6 Arial,sans-serif;max-width:680px;margin:12vh auto;padding:24px;color:#151515"><h1>VELTOR</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
});

function env_value(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = env_value('DB_HOST', '127.0.0.1');
    $port = env_value('DB_PORT', '3306');
    $name = env_value('DB_NAME', 'veltor');
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    $pdo = new PDO($dsn, env_value('DB_USER', 'veltor'), env_value('DB_PASSWORD', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
