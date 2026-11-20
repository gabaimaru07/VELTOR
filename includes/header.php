<?php
$currentUser = current_user();
$cartCount = 0;
?>
<!doctype html>
<html lang="<?= h($language) ?>" dir="<?= $language === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#ffffff">
  <title><?= h($pageTitle ?? 'VELTOR') ?> — VELTOR</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/styles.css">
  <link rel="stylesheet" href="css/store.css">
  <script defer src="js/store.js"></script>
</head>
<body<?= !empty($clearCart) ? ' data-clear-cart="1"' : '' ?>>
<div class="announcement"><?= h(t('announcement')) ?></div>
<header class="site-header">
  <div class="container nav-wrap">
    <a class="wordmark" href="index.php" aria-label="VELTOR">VELTOR<span>.</span></a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" data-menu-toggle>☰</button>
    <nav class="primary-nav" id="primary-nav" data-menu>
      <a class="<?= ($activePage ?? '') === 'home' ? 'is-active' : '' ?>" href="index.php"><?= h(t('home')) ?></a>
      <a class="<?= ($activePage ?? '') === 'products' ? 'is-active' : '' ?>" href="products.php"><?= h(t('products')) ?></a>
      <a class="<?= ($activePage ?? '') === 'about' ? 'is-active' : '' ?>" href="about.php"><?= h(t('about')) ?></a>
      <a class="<?= ($activePage ?? '') === 'contact' ? 'is-active' : '' ?>" href="contact.php"><?= h(t('contact')) ?></a>
      <?php if (($currentUser['role'] ?? '') === 'admin'): ?><a href="admin.php"><?= h(t('admin')) ?></a><?php endif; ?>
    </nav>
    <div class="nav-actions">
      <div class="language-switch" aria-label="<?= h(t('language')) ?>">
        <a class="<?= $language === 'en' ? 'selected' : '' ?>" href="<?= h(language_url('en')) ?>">EN</a>
        <a class="<?= $language === 'fr' ? 'selected' : '' ?>" href="<?= h(language_url('fr')) ?>">FR</a>
        <a class="<?= $language === 'ar' ? 'selected' : '' ?>" href="<?= h(language_url('ar')) ?>">ع</a>
      </div>
      <a class="account-link" href="<?= $currentUser ? 'account.php' : 'login.php' ?>"><?= h($currentUser ? t('account') : t('login')) ?></a>
      <a class="bag-link" href="cart.php"><?= h(t('cart')) ?><b data-cart-count>0</b></a>
    </div>
  </div>
</header>
<?php if ($flash = flash_get()): ?>
  <div class="container"><div class="flash flash-<?= h($flash['type']) ?>" role="status"><?= h($flash['message']) ?></div></div>
<?php endif; ?>
