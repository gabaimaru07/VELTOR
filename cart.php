<?php
require __DIR__ . '/config/bootstrap.php';
$language = $GLOBALS['language'];
$pageTitle = t('your_cart');
$activePage = '';
$products = db()->query("SELECT p.*, c.audience FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.id")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<main class="container">
  <section class="page-hero"><p class="eyebrow"><?= h(t('cart')) ?></p><h1><?= h(t('your_cart')) ?></h1></section>
  <section class="cart-layout">
    <div class="cart-list" data-cart-list>
      <?php foreach ($products as $product): ?>
        <article class="cart-row" data-cart-row data-id="<?= (int) $product['id'] ?>" data-price="<?= h(product_price($product)) ?>" data-stock="<?= (int) $product['stock'] ?>" hidden>
          <img src="<?= h($product['image_path']) ?>" alt="<?= h(product_name($product)) ?>">
          <div><h3><?= h(product_name($product)) ?></h3><p><?= h(money(product_price($product))) ?><?php if ((float) $product['discount_percent'] > 0): ?> <del class="old-price"><?= h(money($product['price'])) ?></del><?php endif; ?></p></div>
          <label><span class="sr-only"><?= h(t('quantity')) ?></span><input type="number" min="1" max="<?= (int) $product['stock'] ?>" value="1" data-quantity aria-label="<?= h(t('quantity')) ?>"></label>
          <strong class="line-total" data-line-total></strong>
          <button class="remove-item" type="button" data-remove-item aria-label="<?= h(t('remove')) ?>">×</button>
        </article>
      <?php endforeach; ?>
    </div>
    <aside class="summary-card" data-cart-summary hidden>
      <h2><?= h(t('your_cart')) ?></h2>
      <div class="summary-line"><span><?= h(t('subtotal')) ?></span><strong data-subtotal>0 MAD</strong></div>
      <div class="summary-line total"><span><?= h(t('total')) ?></span><strong data-total>0 MAD</strong></div>
      <p class="hint"><?= h(t('cart_note')) ?></p>
      <a class="button button-dark" href="checkout.php"><?= h(t('checkout')) ?></a>
    </aside>
  </section>
  <div class="empty-state" data-cart-empty hidden><p><?= h(t('empty_cart')) ?></p><a class="button button-dark" href="products.php"><?= h(t('continue_shopping')) ?></a></div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
