<?php
require __DIR__ . '/config/bootstrap.php';
if (current_user()) redirect('account.php');
$language = $GLOBALS['language'];
$pageTitle = t('login');
$activePage = '';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-shell">
  <p class="eyebrow"><?= h(t('account')) ?></p><h1><?= h(t('login')) ?></h1>
  <form class="stack-form" action="actions.php" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="login">
    <div class="form-field"><label for="email"><?= h(t('email')) ?></label><input id="email" name="email" type="email" required maxlength="190" autocomplete="email"></div>
    <div class="form-field"><label for="password"><?= h(t('password')) ?></label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
    <button class="button button-dark" type="submit"><?= h(t('login')) ?></button>
  </form>
  <p class="hint"><?= h(t('no_account')) ?> <a href="register.php"><?= h(t('register')) ?></a></p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
