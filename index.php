<?php
require __DIR__ . '/config/bootstrap.php';
$language = $GLOBALS['language'];
$pageTitle = t('home');
$activePage = 'home';
$statement = db()->query("SELECT p.*, c.audience FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.is_new DESC, p.created_at DESC, p.id DESC LIMIT 5");
$arrivals = $statement->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<main>
  <section class="hero">
    <div class="hero-copy">
      <p class="eyebrow"><?= h(t('hero_kicker')) ?></p>
      <h1><?= h(t('hero_title')) ?></h1>
      <p class="hero-description"><?= h(t('hero_copy')) ?></p>
      <a class="button button-dark" href="products.php"><?= h(t('shop_now')) ?></a>
    </div>
    <div class="hero-visual">
      <img src="images/hero.svg" alt="<?= h(t('hero_image')) ?>">
      <aside class="promo-glass"><strong><?= h(t('promo_discount')) ?></strong><p><?= h(t('announcement')) ?></p></aside>
    </div>
  </section>
  <section class="welcome-band">
    <div class="container"><h2><?= h(t('welcome_title')) ?></h2><p><?= h(t('welcome_copy')) ?></p></div>
  </section>
  <section class="section container">
    <div class="section-heading"><div><p class="eyebrow"><?= h(t('new_arrivals')) ?></p><h2><?= h(t('new_arrivals')) ?></h2></div><a class="text-link" href="products.php"><?= h(t('view_all')) ?></a></div>
    <div class="product-grid">
      <?php foreach ($arrivals as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
