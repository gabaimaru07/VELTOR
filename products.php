<?php
require __DIR__ . '/config/bootstrap.php';
$language = $GLOBALS['language'];
$pageTitle = t('products');
$activePage = 'products';
$pdo = db();
$categories = $pdo->query("SELECT * FROM categories ORDER BY id")->fetchAll();
$categorySlug = trim(query_string('category'));
$search = trim(mb_substr(query_string('q'), 0, 100));
$sort = query_string('sort', 'featured');
$salePrice = '(p.price * (100 - p.discount_percent) / 100)';
$sorts = ['featured' => 'p.is_new DESC, p.id DESC', 'price-asc' => $salePrice . ' ASC, p.id DESC', 'price-desc' => $salePrice . ' DESC, p.id DESC'];
$where = ['p.active=1'];
$params = [];
if ($categorySlug !== '') { $where[] = 'c.slug = ?'; $params[] = $categorySlug; }
if ($search !== '') {
    $where[] = '(p.name_en LIKE ? OR p.name_fr LIKE ? OR p.name_ar LIKE ? OR p.description_en LIKE ? OR p.description_fr LIKE ? OR p.description_ar LIKE ?)';
    for ($i = 0; $i < 6; $i++) $params[] = '%' . $search . '%';
}
$sql = 'SELECT p.*, c.audience FROM products p JOIN categories c ON c.id=p.category_id WHERE ' . implode(' AND ', $where) . ' ORDER BY ' . ($sorts[$sort] ?? $sorts['featured']);
$query = $pdo->prepare($sql);
$query->execute($params);
$products = $query->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<main>
  <section class="page-hero container">
    <p class="eyebrow"><?= h(t('products')) ?></p><h1><?= h(t('products')) ?><span class="gold-dot">.</span></h1>
    <p><?= h(t('hero_copy')) ?></p>
  </section>
  <section class="container catalog-layout">
    <aside class="filter-panel"><form class="filter-form" method="get" action="products.php">
      <input type="hidden" name="lang" value="<?= h($language) ?>">
      <label for="q"><?= h(t('search')) ?></label><input id="q" name="q" type="search" maxlength="100" placeholder="<?= h(t('search_placeholder')) ?>" value="<?= h($search) ?>">
      <label for="category"><?= h(t('category')) ?></label>
      <select id="category" name="category"><option value=""><?= h(t('all_categories')) ?></option>
        <?php foreach ($categories as $category): ?><option value="<?= h($category['slug']) ?>" <?= $categorySlug === $category['slug'] ? 'selected' : '' ?>><?= h($category['name_' . $language]) ?></option><?php endforeach; ?>
      </select>
      <label for="sort"><?= h(t('sort')) ?></label>
      <select id="sort" name="sort">
        <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>><?= h(t('sort_featured')) ?></option>
        <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>><?= h(t('sort_low')) ?></option>
        <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>><?= h(t('sort_high')) ?></option>
      </select>
      <button class="button button-dark" type="submit"><?= h(t('filter')) ?></button>
      <a class="text-link" href="products.php?lang=<?= h($language) ?>"><?= h(t('clear')) ?></a>
    </form></aside>
    <div class="catalog-results">
      <div class="catalog-toolbar"><span><?= count($products) ?> <?= h(t('products')) ?></span></div>
      <?php if ($products): ?><div class="product-grid"><?php foreach ($products as $product): require __DIR__ . '/includes/product-card.php'; endforeach; ?></div>
      <?php else: ?><div class="empty-state"><?= h(t('empty_products')) ?></div><?php endif; ?>
    </div>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
