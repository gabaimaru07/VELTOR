<?php
require __DIR__ . '/config/bootstrap.php';
if (current_user()) redirect('account.php');
$language = $GLOBALS['language'];
$pageTitle = t('register');
$activePage = '';
require __DIR__ . '/includes/header.php';
?>
<main class="auth-shell">
  <p class="eyebrow"><?= h(t('account')) ?></p><h1><?= h(t('register')) ?></h1>
  <form class="stack-form" action="actions.php" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="register">
    <div class="form-field"><label for="name"><?= h(t('full_name')) ?></label><input id="name" name="name" required minlength="2" maxlength="120" autocomplete="name"></div>
    <div class="form-field"><label for="email"><?= h(t('email')) ?></label><input id="email" name="email" type="email" required maxlength="190" autocomplete="email"></div>
    <div class="form-field"><label for="password"><?= h(t('password')) ?></label><input id="password" name="password" type="password" required minlength="10" autocomplete="new-password"><small><?= h(t('password_rule')) ?></small></div>
    <div class="form-field"><label for="password-confirm"><?= h(t('confirm_password')) ?></label><input id="password-confirm" name="password_confirm" type="password" required minlength="10" autocomplete="new-password"></div>
    <button class="button button-dark" type="submit"><?= h(t('create_account')) ?></button>
  </form>
  <p class="hint"><?= h(t('have_account')) ?> <a href="login.php"><?= h(t('login')) ?></a></p>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
