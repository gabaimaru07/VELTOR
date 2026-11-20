<?php
declare(strict_types=1);

$requestPath = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));
$requestPath = str_replace('\\', '/', $requestPath);
$relativePath = ltrim($requestPath, '/');

if (
    str_contains($requestPath, "\0")
    || preg_match('~(^|/)\.\.?(/|$)~', $requestPath)
    || preg_match('~(^|/)\.[^/]+$~', $relativePath)
    || preg_match('~^(?:config|includes|database|logs|vendor)(?:/|$)~i', $relativePath)
    || preg_match('~^(?:\.env(?:\..*)?|.*\.sql|composer\.(?:json|lock)|setup-admin\.php)$~i', basename($relativePath))
    || (str_starts_with($relativePath, 'uploads/') && preg_match('/\.(?:php[0-9]?|phtml|phar|cgi)$/i', $relativePath))
) {
    http_response_code(404);
    exit;
}

$target = __DIR__ . '/' . $relativePath;
if ($relativePath === '') {
    return false;
}
if ($relativePath !== '' && is_file($target)) {
    return false;
}

http_response_code(404);
echo 'Not found';
