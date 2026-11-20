<?php
declare(strict_types=1);

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function query_string(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? $value : $default;
}

function t(string $key): string
{
    $language = $GLOBALS['language'] ?? 'en';
    $copy = $GLOBALS['translations'][$language] ?? $GLOBALS['translations']['en'];
    return $copy[$key] ?? $GLOBALS['translations']['en'][$key] ?? $key;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('<!doctype html><meta charset="utf-8"><title>VELTOR</title><p>' . h(t('required')) . '</p>');
    }
}

function flash_set(string $type, string $messageKey): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => t($messageKey)];
}

function flash_get(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_admin(): void
{
    if ((current_user()['role'] ?? '') !== 'admin') {
        flash_set('error', 'admin_required');
        redirect('login.php');
    }
}

function order_reference(): string
{
    return 'VT-' . strtoupper(bin2hex(random_bytes(8)));
}

function money(float|string $amount): string
{
    return number_format((float) $amount, 2, '.', ',') . ' MAD';
}

function product_name(array $product): string
{
    return (string) ($product['name_' . ($GLOBALS['language'] ?? 'en')] ?? $product['name_en'] ?? '');
}

function product_description(array $product): string
{
    return (string) ($product['description_' . ($GLOBALS['language'] ?? 'en')] ?? $product['description_en'] ?? '');
}

function product_price_cents(array $product): int
{
    $baseCents = (int) round((float) $product['price'] * 100);
    $discount = min(80.0, max(0.0, (float) ($product['discount_percent'] ?? 0)));
    return (int) round($baseCents * (100 - $discount) / 100);
}

function product_price(array $product): string
{
    return number_format(product_price_cents($product) / 100, 2, '.', '');
}

function language_url(string $language): string
{
    $path = strtok($_SERVER['REQUEST_URI'] ?? 'index.php', '?') ?: 'index.php';
    $query = $_GET;
    $query['lang'] = $language;
    return $path . '?' . http_build_query($query);
}
