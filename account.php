<?php
require __DIR__ . '/config/bootstrap.php';
if (!current_user()) redirect('login.php');
$language = $GLOBALS['language'];
$pageTitle = t('account');
$activePage = '';
require __DIR__ . '/includes/header.php';
?>
<main class="container content-page">
  <p class="eyebrow"><?= h(t('account')) ?></p><h1><?= h(current_user()['name']) ?></h1>
  <p><?= h(current_user()['email']) ?></p>
  <?php if (current_user()['role'] === 'admin'): ?><p><a class="button button-outline" href="admin.php"><?= h(t('dashboard')) ?></a></p><?php endif; ?>
  <form action="actions.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="button button-dark" type="submit"><?= h(t('logout')) ?></button></form>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
