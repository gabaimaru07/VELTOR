<?php
require __DIR__ . '/config/bootstrap.php';
$language = $GLOBALS['language'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$statement = db()->prepare('SELECT p.*, c.audience FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.active=1');
$statement->execute([$id ?: 0]);
$product = $statement->fetch();
if (!$product) {
    http_response_code(404);
    $pageTitle = t('products');
    $activePage = 'products';
    require __DIR__ . '/includes/header.php';
    echo '<main class="container content-page"><h1>' . h(t('order_not_found')) . '</h1><a class="button button-dark" href="products.php">' . h(t('continue_shopping')) . '</a></main>';
    require __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = product_name($product);
$activePage = 'products';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
  <div class="detail-layout" data-product-detail>
    <img class="detail-image" src="<?= h($product['image_path']) ?>" alt="<?= h(product_name($product)) ?>">
    <section class="detail-copy">
      <p class="eyebrow"><?= h(t(in_array($product['audience'], ['women', 'men'], true) ? $product['audience'] : 'all_categories')) ?></p>
      <h1><?= h(product_name($product)) ?></h1>
      <p class="detail-price"><?= h(money(product_price($product))) ?><?php if ((float) $product['discount_percent'] > 0): ?> <del class="old-price"><?= h(money($product['price'])) ?></del><?php endif; ?></p>
      <p><?= h(product_description($product)) ?></p>
      <p><?= h((int) $product['stock'] > 0 ? t('stock') : t('sold_out')) ?></p>
      <?php if ((int) $product['stock'] > 0): ?>
        <form action="cart.php" method="get">
          <label for="detail-quantity"><?= h(t('quantity')) ?></label>
          <input id="detail-quantity" type="number" min="1" max="<?= (int) $product['stock'] ?>" value="1" data-detail-quantity>
          <button class="button button-dark" type="button" data-add-cart="<?= (int) $product['id'] ?>" data-stock="<?= (int) $product['stock'] ?>"><?= h(t('add_cart')) ?></button>
        </form>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
