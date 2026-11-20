<article class="product-card">
  <a class="product-image-link" href="product.php?id=<?= (int) $product['id'] ?>">
    <img src="<?= h($product['image_path']) ?>" alt="<?= h(product_name($product)) ?>" loading="lazy">
    <?php if ((float) ($product['discount_percent'] ?? 0) > 0): ?><span class="sale-tag">−<?= h(rtrim(rtrim(number_format((float) $product['discount_percent'], 2, '.', ''), '0'), '.')) ?>%</span><?php endif; ?>
  </a>
  <div class="product-meta">
    <span><?= h(t(in_array($product['audience'], ['women', 'men'], true) ? $product['audience'] : 'all_categories')) ?></span>
    <span><?= (int) $product['stock'] > 0 ? h(t('stock')) : h(t('sold_out')) ?></span>
  </div>
  <h3><a href="product.php?id=<?= (int) $product['id'] ?>"><?= h(product_name($product)) ?></a></h3>
  <div class="product-buy-row">
    <strong><?= h(money(product_price($product))) ?><?php if ((float) ($product['discount_percent'] ?? 0) > 0): ?> <del class="old-price"><?= h(money($product['price'])) ?></del><?php endif; ?></strong>
    <?php if ((int) $product['stock'] > 0): ?>
      <button class="button button-outline button-small" type="button" data-add-cart="<?= (int) $product['id'] ?>" data-stock="<?= (int) $product['stock'] ?>"><?= h(t('add_cart')) ?></button>
    <?php endif; ?>
  </div>
</article>
