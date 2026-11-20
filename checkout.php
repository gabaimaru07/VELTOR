<?php
require __DIR__ . '/config/bootstrap.php';
$language = $GLOBALS['language'];
$pageTitle = t('checkout_title');
$activePage = '';
$products = db()->query("SELECT p.*, c.audience FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.id")->fetchAll();
$requestToken = bin2hex(random_bytes(24));
require __DIR__ . '/includes/header.php';
?>
<main class="container">
  <section class="page-hero"><p class="eyebrow"><?= h(t('checkout')) ?></p><h1><?= h(t('checkout_title')) ?></h1></section>
  <section class="checkout-layout">
    <form class="stack-form" action="actions.php" method="post" data-checkout-form>
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="checkout">
      <input type="hidden" name="cart_json" value="[]">
      <input type="hidden" name="request_token" value="<?= h($requestToken) ?>">
      <div class="form-field"><label for="customer-name"><?= h(t('full_name')) ?></label><input id="customer-name" name="name" required maxlength="120" autocomplete="name"></div>
      <div class="form-field"><label for="customer-phone"><?= h(t('phone')) ?></label><input id="customer-phone" name="phone" required maxlength="24" autocomplete="tel" inputmode="tel"></div>
      <div class="form-field"><label for="customer-city"><?= h(t('city')) ?></label><input id="customer-city" name="city" required maxlength="100" autocomplete="address-level2"></div>
      <div class="form-field"><label for="customer-address"><?= h(t('address')) ?></label><textarea id="customer-address" name="address" required maxlength="500" autocomplete="street-address"></textarea></div>
      <div class="form-field"><label for="customer-email"><?= h(t('email')) ?> <small>(<?= h(t('optional')) ?>)</small></label><input id="customer-email" name="email" type="email" maxlength="190" autocomplete="email"></div>
      <p class="hint"><?= h(t('checkout_note')) ?></p>
      <button class="button button-dark" type="submit"><?= h(t('place_order')) ?></button>
    </form>
    <aside class="summary-card">
      <h2><?= h(t('order_summary')) ?></h2>
      <?php foreach ($products as $product): ?>
        <div class="summary-line" data-checkout-row data-id="<?= (int) $product['id'] ?>" data-price="<?= h(product_price($product)) ?>" hidden>
          <span><?= h(product_name($product)) ?> × <b data-checkout-quantity>0</b></span>
          <strong data-checkout-line></strong>
        </div>
      <?php endforeach; ?>
      <div class="summary-line total"><span><?= h(t('total')) ?></span><strong data-checkout-total>0 MAD</strong></div>
      <a class="text-link" href="cart.php"><?= h(t('your_cart')) ?></a>
    </aside>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
