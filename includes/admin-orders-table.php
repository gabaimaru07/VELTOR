<div class="table-wrap"><table class="data-table">
  <thead><tr><th><?= h(t('order_ref')) ?></th><th><?= h(t('full_name')) ?></th><th><?= h(t('total')) ?></th><th><?= h(t('status')) ?></th><th><?= h(t('email_status')) ?></th><th><?= h(t('date')) ?></th><?php if ($tab !== 'overview' && $tab !== 'reports'): ?><th></th><?php endif; ?></tr></thead>
  <tbody>
  <?php foreach ($orders as $order): ?>
    <tr>
      <td><?= h($order['reference']) ?></td><td><details><summary><?= h($order['customer_name']) ?></summary><span><?= h($order['phone']) ?><br><?= h($order['city']) ?><br><?= h($order['address']) ?><?php if (!empty($order['customer_email'])): ?><br><?= h($order['customer_email']) ?><?php endif; ?></span></details></td><td><?= h(money($order['total'])) ?></td>
      <td><?= h(t($order['status'] === 'in_delivery' ? 'in_delivery' : $order['status'])) ?></td>
      <td><?= h(t($order['email_status'] === 'sent' ? 'email_sent_short' : ($order['email_status'] === 'failed' ? 'email_failed_short' : 'email_pending'))) ?></td>
      <td><?= h($order['created_at']) ?></td>
      <?php if ($tab !== 'overview' && $tab !== 'reports'): ?>
      <td>
        <form action="actions.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="admin_order_status"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
          <select name="status" aria-label="<?= h(t('status')) ?>">
            <?php foreach (['new','in_delivery','delivered'] as $status): ?><option value="<?= h($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= h(t($status === 'in_delivery' ? 'in_delivery' : $status)) ?></option><?php endforeach; ?>
          </select><button type="submit"><?= h(t('update')) ?></button>
        </form>
        <?php if ($order['email_status'] !== 'sent'): ?><form action="actions.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="admin_retry_email"><input type="hidden" name="id" value="<?= (int) $order['id'] ?>"><button type="submit"><?= h(t('retry_email')) ?></button></form><?php endif; ?>
      </td>
      <?php endif; ?>
    </tr>
    <tr class="order-items-row"><td colspan="<?= $tab !== 'overview' && $tab !== 'reports' ? 7 : 6 ?>">
      <?php $itemQuery = $pdo->prepare('SELECT product_name,quantity,unit_price,line_total FROM order_items WHERE order_id=?'); $itemQuery->execute([$order['id']]); ?>
      <?php foreach ($itemQuery->fetchAll() as $item): ?><span><?= h($item['product_name']) ?> × <?= (int) $item['quantity'] ?> (<?= h(money($item['unit_price'])) ?>)</span><?php endforeach; ?>
    </td></tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
