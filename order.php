<?php
require __DIR__ . '/config/bootstrap.php';
$language = $GLOBALS['language'];
$reference = strtoupper(substr(query_string('ref'), 0, 24));
$statement = db()->prepare('SELECT reference, email_status, created_at FROM orders WHERE reference=?');
$statement->execute([$reference]);
$order = $statement->fetch();
$clearCart = $order && isset($_SESSION['last_order_ref']) && hash_equals((string) $_SESSION['last_order_ref'], $reference);
if ($clearCart) unset($_SESSION['last_order_ref']);
$pageTitle = t('order_placed');
$activePage = '';
require __DIR__ . '/includes/header.php';
?>
<main class="container content-page">
<?php if (!$order): ?>
  <h1><?= h(t('order_not_found')) ?></h1>
<?php else: ?>
  <p class="eyebrow"><?= h(t('order_placed')) ?></p>
  <h1><?= h(t('order_placed')) ?></h1>
  <div class="summary-card">
    <p><?= h(t('order_ref')) ?>: <strong><?= h($order['reference']) ?></strong></p>
    <p><?= h($order['email_status'] === 'sent' ? t('email_sent') : t('email_failed')) ?></p>
  </div>
  <p><a class="button button-dark" href="products.php"><?= h(t('continue_shopping')) ?></a></p>
<?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
