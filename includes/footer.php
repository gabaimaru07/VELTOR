<?php $store = require __DIR__ . '/../config/store.php'; ?>
<footer class="site-footer">
  <div class="container footer-inner">
    <a class="wordmark" href="index.php">VELTOR<span>.</span></a>
    <p><?= h(t('footer_note')) ?></p>
    <div class="footer-links">
      <a href="products.php"><?= h(t('products')) ?></a>
      <a href="about.php"><?= h(t('about')) ?></a>
      <a href="contact.php"><?= h(t('contact')) ?></a>
      <a href="mailto:<?= h($store['email']) ?>"><?= h($store['email']) ?></a>
    </div>
    <small>© <?= date('Y') ?> VELTOR</small>
  </div>
</footer>
</body>
</html>
