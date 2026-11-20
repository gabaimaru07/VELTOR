<?php
require __DIR__ . '/config/bootstrap.php';
$language = $GLOBALS['language'];
$pageTitle = t('contact');
$activePage = 'contact';
$store = require __DIR__ . '/config/store.php';
require __DIR__ . '/includes/header.php';
?>
<main class="container">
  <section class="page-hero"><p class="eyebrow"><?= h(t('contact')) ?></p><h1><?= h(t('contact')) ?></h1><p><?= h(t('contact_intro')) ?></p></section>
  <section class="contact-layout">
    <aside class="contact-info">
      <h2><?= h(t('owner')) ?></h2>
      <p><strong><?= h($store['owner']) ?></strong><br><?= h($store['city'][$language]) ?>, <?= h($store['country'][$language]) ?></p>
      <p><a href="mailto:<?= h($store['email']) ?>"><?= h($store['email']) ?></a><br><a href="tel:<?= h($store['phone']) ?>"><?= h($store['phone']) ?></a></p>
      <p><a href="https://wa.me/<?= h($store['whatsapp']) ?>" rel="noopener noreferrer"><?= h(t('whatsapp')) ?></a></p>
      <h3><?= h(t('social')) ?></h3>
      <p><a href="<?= h($store['social']['instagram']) ?>" target="_blank" rel="noopener noreferrer">Instagram</a> · <a href="<?= h($store['social']['facebook']) ?>" target="_blank" rel="noopener noreferrer">Facebook</a></p>
    </aside>
    <form class="stack-form" action="actions.php" method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="contact">
      <div class="form-field"><label for="contact-name"><?= h(t('full_name')) ?></label><input id="contact-name" name="name" required maxlength="120" autocomplete="name"></div>
      <div class="form-field"><label for="contact-email"><?= h(t('email')) ?></label><input id="contact-email" name="email" type="email" required maxlength="190" autocomplete="email"></div>
      <div class="form-field"><label for="contact-message"><?= h(t('message')) ?></label><textarea id="contact-message" name="message" required maxlength="3000"></textarea></div>
      <div class="honeypot" hidden aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
      <button class="button button-dark" type="submit"><?= h(t('send_message')) ?></button>
    </form>
  </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
