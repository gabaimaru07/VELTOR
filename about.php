<?php
require __DIR__ . '/config/bootstrap.php';
$language = $GLOBALS['language'];
$pageTitle = t('about');
$activePage = 'about';
$sections = require __DIR__ . '/config/content.php';
require __DIR__ . '/includes/header.php';
?>
<main class="container content-page">
  <p class="eyebrow"><?= h(t('about')) ?></p>
  <h1><?= h(t('about')) ?></h1>
  <p><?= h(t('about_intro')) ?></p>
  <div class="content-grid">
    <?php foreach ($sections[$language] as [$title, $copy]): ?>
      <article class="content-card"><h2><?= h(t($title)) ?></h2><p><?= h($copy) ?></p></article>
    <?php endforeach; ?>
  </div>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
