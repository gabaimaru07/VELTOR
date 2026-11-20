<?php
require __DIR__ . '/../config/bootstrap.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$translations = $GLOBALS['translations'];
$englishKeys = array_keys($translations['en']);
foreach (['fr', 'ar'] as $language) {
    check(array_diff($englishKeys, array_keys($translations[$language])) === [], "{$language} translation coverage");
}

check(h('<VELTOR & "store">') === '&lt;VELTOR &amp; &quot;store&quot;&gt;', 'HTML escaping');
check(product_price_cents(['price' => '1290.00', 'discount_percent' => '10.00']) === 116100, 'discounted server price');
check(product_price_cents(['price' => '100.00', 'discount_percent' => '100.00']) === 2000, 'discount cap');
check(strlen(csrf_token()) === 64, 'CSRF token generation');
check(str_contains(csrf_field(), h(csrf_token())), 'CSRF form field');

$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
preg_match_all("/'images\\/products\\/([^']+)'/", $schema, $imageNames);
check(count($imageNames[1]) === 6, 'six seeded product image paths');
foreach ($imageNames[1] as $image) {
    check(is_file(__DIR__ . '/../images/products/' . $image), "seed image exists: {$image}");
}

fwrite(STDOUT, "PHP smoke tests passed.\n");
