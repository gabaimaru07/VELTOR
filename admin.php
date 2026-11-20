<?php
require __DIR__ . '/config/bootstrap.php';
require_admin();
$language = $GLOBALS['language'];
$pageTitle = t('admin');
$activePage = '';
$pdo = db();
$tab = query_string('tab', 'overview');
if (!in_array($tab, ['overview', 'products', 'orders', 'messages', 'reports'], true)) $tab = 'overview';
$tabs = ['overview' => 'overview', 'products' => 'manage_products', 'orders' => 'manage_orders', 'messages' => 'messages', 'reports' => 'reports'];
$stats = [
    'products' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE active=1')->fetchColumn(),
    'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'sales' => (string) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='delivered'")->fetchColumn(),
];
$categories = $pdo->query('SELECT * FROM categories ORDER BY id')->fetchAll();
$editProduct = null;
if ($tab === 'products' && isset($_GET['edit'])) {
    $editQuery = $pdo->prepare('SELECT * FROM products WHERE id=?');
    $editQuery->execute([filter_var($_GET['edit'], FILTER_VALIDATE_INT) ?: 0]);
    $editProduct = $editQuery->fetch() ?: null;
}
require __DIR__ . '/includes/header.php';
?>
<main class="container">
  <section class="page-hero"><p class="eyebrow"><?= h(t('admin')) ?></p><h1><?= h(t('dashboard')) ?></h1></section>
  <nav class="admin-tabs"><?php foreach ($tabs as $key => $title): ?><a class="<?= $tab === $key ? 'is-active' : '' ?>" href="admin.php?tab=<?= h($key) ?>"><?= h(t($title)) ?></a><?php endforeach; ?></nav>

  <?php if ($tab === 'overview'): ?>
    <section class="stats-grid">
      <article class="stat-card"><span><?= h(t('total_products')) ?></span><strong><?= $stats['products'] ?></strong></article>
      <article class="stat-card"><span><?= h(t('total_orders')) ?></span><strong><?= $stats['orders'] ?></strong></article>
      <article class="stat-card"><span><?= h(t('total_sales')) ?></span><strong><?= h(money($stats['sales'])) ?></strong></article>
      <article class="stat-card"><span><?= h(t('email_failed_short')) ?></span><strong><?= (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE email_status='failed'")->fetchColumn() ?></strong></article>
    </section>
    <h2><?= h(t('recent_orders')) ?></h2>
    <?php $orders = $pdo->query('SELECT id,reference,customer_name,customer_email,phone,city,address,total,status,email_status,created_at FROM orders ORDER BY id DESC LIMIT 10')->fetchAll(); ?>
    <?php require __DIR__ . '/includes/admin-orders-table.php'; ?>

  <?php elseif ($tab === 'products'): ?>
    <section class="admin-columns">
      <form class="stack-form content-card" action="actions.php" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="action" value="admin_product"><input type="hidden" name="id" value="<?= (int) ($editProduct['id'] ?? 0) ?>">
        <h2><?= h(t($editProduct ? 'edit' : 'new_product')) ?></h2>
        <div class="form-field"><label for="category-id"><?= h(t('category')) ?></label><select id="category-id" name="category_id" required><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) ($editProduct['category_id'] ?? 0) === (int) $category['id'] ? 'selected' : '' ?>><?= h($category['name_' . $language]) ?></option><?php endforeach; ?></select></div>
        <?php foreach (['en', 'fr', 'ar'] as $locale): ?>
          <div class="form-field"><label for="name-<?= $locale ?>"><?= h(t('product_name')) ?> (<?= strtoupper($locale) ?>)</label><input id="name-<?= $locale ?>" name="name_<?= $locale ?>" required maxlength="160" value="<?= h($editProduct['name_' . $locale] ?? '') ?>"></div>
          <div class="form-field"><label for="description-<?= $locale ?>"><?= h(t('product_description')) ?> (<?= strtoupper($locale) ?>)</label><textarea id="description-<?= $locale ?>" name="description_<?= $locale ?>" required><?= h($editProduct['description_' . $locale] ?? '') ?></textarea></div>
        <?php endforeach; ?>
        <div class="form-field"><label for="price"><?= h(t('price')) ?> (MAD)</label><input id="price" name="price" type="number" min="0" max="99999999" step="0.01" required value="<?= h($editProduct['price'] ?? '') ?>"></div>
        <div class="form-field"><label for="discount"><?= h(t('discount')) ?></label><input id="discount" name="discount_percent" type="number" min="0" max="80" step="0.01" value="<?= h($editProduct['discount_percent'] ?? '0') ?>"></div>
        <div class="form-field"><label for="stock"><?= h(t('stock_quantity')) ?></label><input id="stock" name="stock" type="number" min="0" max="1000000" required value="<?= h($editProduct['stock'] ?? '0') ?>"></div>
        <?php if (!$editProduct): ?><div class="form-field"><label for="image-path"><?= h(t('image_path')) ?></label><input id="image-path" name="image_path" placeholder="images/products/example.svg"><small><?= h(t('image_path_hint')) ?></small></div><?php endif; ?>
        <div class="form-field"><label for="image"><?= h(t('upload_image')) ?></label><input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" data-image-input><small><?= h(t('image_upload_hint')) ?></small></div>
        <?php if ($editProduct): ?><img class="admin-product-image" src="<?= h($editProduct['image_path']) ?>" alt="<?= h($editProduct['name_en']) ?>" data-image-preview><?php else: ?><img class="admin-product-image" alt="" data-image-preview hidden><?php endif; ?>
        <label><input type="checkbox" name="is_new" value="1" <?= !empty($editProduct['is_new']) ? 'checked' : '' ?>> <?= h(t('new_arrivals')) ?></label>
        <button class="button button-dark" type="submit"><?= h(t('save_product')) ?></button>
        <?php if ($editProduct): ?><a class="text-link" href="admin.php?tab=products"><?= h(t('clear')) ?></a><?php endif; ?>
      </form>
      <div>
        <?php $products = $pdo->query('SELECT p.*,c.name_' . $language . ' AS category_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.id DESC')->fetchAll(); ?>
        <h2><?= h(t('manage_products')) ?></h2>
        <div class="table-wrap"><table class="data-table"><thead><tr><th></th><th><?= h(t('product_name')) ?></th><th><?= h(t('category')) ?></th><th><?= h(t('price')) ?></th><th><?= h(t('stock_quantity')) ?></th><th></th></tr></thead><tbody>
        <?php foreach ($products as $product): ?><tr>
          <td><img class="admin-product-image" src="<?= h($product['image_path']) ?>" alt=""></td>
          <td><?= h($product['name_' . $language]) ?></td><td><?= h($product['category_name']) ?></td><td><?= h(money(product_price($product))) ?><?php if ((float) $product['discount_percent'] > 0): ?><br><del class="old-price"><?= h(money($product['price'])) ?></del><?php endif; ?></td><td><?= (int) $product['stock'] ?></td>
          <td class="table-actions"><a href="admin.php?tab=products&amp;edit=<?= (int) $product['id'] ?>"><?= h(t('edit')) ?></a>
            <form action="actions.php" method="post" data-confirm="<?= h(t('delete')) ?>?"><?= csrf_field() ?><input type="hidden" name="action" value="admin_delete_product"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button type="submit"><?= h(t('delete')) ?></button></form></td>
        </tr><?php endforeach; ?>
        </tbody></table></div>
        <h2><?= h(t('category')) ?></h2>
        <?php $managedCategories = $pdo->query('SELECT c.*,(SELECT COUNT(*) FROM products p WHERE p.category_id=c.id) product_count FROM categories c ORDER BY c.id')->fetchAll(); ?>
        <?php foreach ($managedCategories as $managedCategory): ?>
          <form class="stack-form content-card" action="actions.php" method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="admin_category_update"><input type="hidden" name="id" value="<?= (int) $managedCategory['id'] ?>">
            <?php foreach (['en', 'fr', 'ar'] as $locale): ?><div class="form-field"><label for="update-category-<?= $locale ?>-<?= (int) $managedCategory['id'] ?>"><?= h(t('category_name')) ?> (<?= strtoupper($locale) ?>)</label><input id="update-category-<?= $locale ?>-<?= (int) $managedCategory['id'] ?>" name="name_<?= $locale ?>" required maxlength="100" value="<?= h($managedCategory['name_' . $locale]) ?>"></div><?php endforeach; ?>
            <div class="form-field"><label for="update-audience-<?= (int) $managedCategory['id'] ?>"><?= h(t('gender')) ?></label><select id="update-audience-<?= (int) $managedCategory['id'] ?>" name="audience"><?php foreach (['women','men','all'] as $audience): ?><option value="<?= h($audience) ?>" <?= $managedCategory['audience'] === $audience ? 'selected' : '' ?>><?= h(t($audience === 'all' ? 'all_categories' : $audience)) ?></option><?php endforeach; ?></select></div>
            <button class="button button-outline" type="submit"><?= h(t('update')) ?></button>
          </form>
          <?php if (!in_array($managedCategory['slug'], ['women','men'], true) && (int) $managedCategory['product_count'] === 0): ?>
            <form action="actions.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="admin_delete_category"><input type="hidden" name="id" value="<?= (int) $managedCategory['id'] ?>"><button type="submit"><?= h(t('delete')) ?> · <?= h($managedCategory['name_' . $language]) ?></button></form>
          <?php endif; ?>
        <?php endforeach; ?>
        <form class="stack-form content-card" action="actions.php" method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="admin_category"><h2><?= h(t('add_category')) ?></h2>
          <?php foreach (['en', 'fr', 'ar'] as $locale): ?><div class="form-field"><label for="category-<?= $locale ?>"><?= h(t('category_name')) ?> (<?= strtoupper($locale) ?>)</label><input id="category-<?= $locale ?>" name="name_<?= $locale ?>" required maxlength="100"></div><?php endforeach; ?>
          <div class="form-field"><label for="audience"><?= h(t('gender')) ?></label><select id="audience" name="audience"><option value="women"><?= h(t('women')) ?></option><option value="men"><?= h(t('men')) ?></option><option value="all"><?= h(t('all_categories')) ?></option></select></div>
          <button class="button button-outline" type="submit"><?= h(t('save_category')) ?></button>
        </form>
      </div>
    </section>

  <?php elseif ($tab === 'orders'): ?>
    <h2><?= h(t('manage_orders')) ?></h2>
    <?php $orders = $pdo->query('SELECT id,reference,customer_name,customer_email,phone,city,address,total,status,email_status,created_at FROM orders ORDER BY id DESC')->fetchAll(); ?>
    <?php require __DIR__ . '/includes/admin-orders-table.php'; ?>

  <?php elseif ($tab === 'messages'): ?>
    <h2><?= h(t('messages')) ?></h2>
    <?php $messages = $pdo->query('SELECT id,name,email,message,created_at FROM contact_messages ORDER BY id DESC')->fetchAll(); ?>
    <div class="table-wrap"><table class="data-table"><thead><tr><th><?= h(t('full_name')) ?></th><th><?= h(t('email')) ?></th><th><?= h(t('message')) ?></th><th><?= h(t('date')) ?></th></tr></thead><tbody>
      <?php foreach ($messages as $message): ?><tr><td><?= h($message['name']) ?></td><td><a href="mailto:<?= h($message['email']) ?>"><?= h($message['email']) ?></a></td><td><?= nl2br(h($message['message'])) ?></td><td><?= h($message['created_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>

  <?php else: ?>
    <?php
      $bestSellers = $pdo->query('SELECT product_name,SUM(quantity) units,SUM(line_total) revenue FROM order_items GROUP BY product_name ORDER BY units DESC LIMIT 8')->fetchAll();
      $dailyQuery = $pdo->query("SELECT DATE(created_at) day,SUM(total) sales FROM orders WHERE status='delivered' AND created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(created_at)");
      $dailyRows = [];
      foreach ($dailyQuery as $row) $dailyRows[$row['day']] = (float) $row['sales'];
      $days = [];
      for ($offset = 6; $offset >= 0; $offset--) {
          $date = date('Y-m-d', strtotime("-$offset days"));
          $days[] = ['label' => date('d/m', strtotime($date)), 'sales' => $dailyRows[$date] ?? 0.0];
      }
      $maxSales = max(1.0, ...array_column($days, 'sales'));
    ?>
    <section class="stats-grid">
      <article class="stat-card"><span><?= h(t('total_sales')) ?></span><strong><?= h(money($stats['sales'])) ?></strong></article>
      <article class="stat-card"><span><?= h(t('total_orders')) ?></span><strong><?= $stats['orders'] ?></strong></article>
    </section>
    <h2><?= h(t('sales_report')) ?></h2>
    <div class="bar-chart" role="img" aria-label="<?= h(t('sales_report')) ?>">
      <?php foreach ($days as $day): ?><span title="<?= h($day['label'] . ' ' . money($day['sales'])) ?>" style="height:<?= max(2, (int) round($day['sales'] / $maxSales * 100)) ?>%"></span><?php endforeach; ?>
    </div>
    <div class="bar-labels"><?php foreach ($days as $day): ?><span><?= h($day['label']) ?></span><?php endforeach; ?></div>
    <h2><?= h(t('best_sellers')) ?></h2>
    <div class="table-wrap"><table class="data-table"><thead><tr><th><?= h(t('product_name')) ?></th><th><?= h(t('units_sold')) ?></th><th><?= h(t('total_sales')) ?></th></tr></thead><tbody>
      <?php foreach ($bestSellers as $product): ?><tr><td><?= h($product['product_name']) ?></td><td><?= (int) $product['units'] ?></td><td><?= h(money($product['revenue'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <h2><?= h(t('recent_orders')) ?></h2>
    <?php $orders = $pdo->query('SELECT id,reference,customer_name,customer_email,phone,city,address,total,status,email_status,created_at FROM orders ORDER BY id DESC LIMIT 10')->fetchAll(); ?>
    <?php require __DIR__ . '/includes/admin-orders-table.php'; ?>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
