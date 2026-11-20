<?php
declare(strict_types=1);

function send_order_notification(array $order, array $items): array
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (!is_file($autoload)) {
        error_log('VELTOR SMTP delivery failed for ' . ($order['reference'] ?? 'unknown') . ': PHPMailer is not installed.');
        return ['sent' => false, 'error' => 'PHPMailer is not installed. Run composer install.'];
    }

    $required = ['MAIL_HOST', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM_ADDRESS'];
    foreach ($required as $key) {
        if (!env_value($key)) {
            error_log('VELTOR SMTP delivery failed for ' . ($order['reference'] ?? 'unknown') . ': SMTP configuration is incomplete.');
            return ['sent' => false, 'error' => 'SMTP configuration is incomplete.'];
        }
    }

    require_once $autoload;
    $escape = static fn (mixed $value): string => h($value);
    $copy = match ($order['language'] ?? 'en') {
        'fr' => ['new'=>'NOUVELLE COMMANDE', 'order'=>'Commande', 'date'=>'Date', 'customer'=>'Client et livraison', 'product'=>'Produit', 'quantity'=>'Qté', 'price'=>'Prix unitaire', 'line_total'=>'Total ligne', 'total'=>'Total'],
        'ar' => ['new'=>'طلب جديد', 'order'=>'الطلب', 'date'=>'التاريخ', 'customer'=>'العميل والتوصيل', 'product'=>'المنتج', 'quantity'=>'الكمية', 'price'=>'سعر الوحدة', 'line_total'=>'الإجمالي', 'total'=>'المجموع'],
        default => ['new'=>'NEW ONLINE ORDER', 'order'=>'Order', 'date'=>'Date', 'customer'=>'Customer and delivery', 'product'=>'Product', 'quantity'=>'Qty', 'price'=>'Unit price', 'line_total'=>'Line total', 'total'=>'Total'],
    };
    $direction = ($order['language'] ?? 'en') === 'ar' ? 'rtl' : 'ltr';
    $rows = '';
    $plainRows = [];
    foreach ($items as $item) {
        $rows .= '<tr><td style="padding:12px;border-bottom:1px solid #eee">' . $escape($item['product_name']) . '</td><td style="padding:12px;border-bottom:1px solid #eee;text-align:center">' . (int) $item['quantity'] . '</td><td style="padding:12px;border-bottom:1px solid #eee;text-align:end">' . $escape(money($item['unit_price'])) . '</td><td style="padding:12px;border-bottom:1px solid #eee;text-align:end">' . $escape(money($item['line_total'])) . '</td></tr>';
        $plainRows[] = sprintf('%s × %d — %s', $item['product_name'], $item['quantity'], money($item['line_total']));
    }
    $reference = $escape($order['reference']);
    $html = '<!doctype html><html lang="' . $escape($order['language']) . '" dir="' . $direction . '"><body style="margin:0;background:#f5f5f5;font-family:Arial,sans-serif;color:#151515"><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:30px 12px"><tr><td align="center"><table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#fff;border:1px solid #e8e8e8"><tr><td style="padding:28px 32px;border-bottom:1px solid #eee"><strong style="font-size:24px;letter-spacing:4px">VELTOR<span style="color:#a88d5d">.</span></strong><div style="margin-top:8px;color:#777;font-size:12px">' . $escape($copy['new']) . '</div></td></tr><tr><td style="padding:30px 32px"><h1 style="font-size:23px;margin:0 0 8px">' . $escape($copy['order']) . ' ' . $reference . '</h1><p style="color:#666;margin:0 0 24px">' . $escape($copy['date']) . ': ' . $escape($order['created_at']) . '</p><h2 style="font-size:15px">' . $escape($copy['customer']) . '</h2><p style="line-height:1.8;margin-bottom:24px"><strong>' . $escape($order['customer_name']) . '</strong><br>' . $escape($order['phone']) . '<br>' . $escape($order['city']) . '<br>' . nl2br($escape($order['address'])) . ($order['customer_email'] !== '' ? '<br>' . $escape($order['customer_email']) : '') . '</p><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px"><thead><tr><th align="start" style="padding:10px 12px;background:#f5f5f5">' . $escape($copy['product']) . '</th><th style="padding:10px;background:#f5f5f5">' . $escape($copy['quantity']) . '</th><th align="end" style="padding:10px;background:#f5f5f5">' . $escape($copy['price']) . '</th><th align="end" style="padding:10px;background:#f5f5f5">' . $escape($copy['line_total']) . '</th></tr></thead><tbody>' . $rows . '</tbody></table><p style="text-align:end;font-size:19px;font-weight:bold;margin-top:22px">' . $escape($copy['total']) . ': ' . $escape(money($order['total'])) . '</p></td></tr><tr><td style="padding:18px 32px;background:#fafafa;color:#777;font-size:12px">VELTOR · Accessories for everyday life</td></tr></table></td></tr></table></body></html>';

    try {
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $mailer->isSMTP();
        $mailer->Host = (string) env_value('MAIL_HOST');
        $mailer->SMTPAuth = true;
        $mailer->Username = (string) env_value('MAIL_USERNAME');
        $mailer->Password = (string) env_value('MAIL_PASSWORD');
        $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mailer->Port = (int) env_value('MAIL_PORT', '587');
        $mailer->Timeout = 12;
        $mailer->CharSet = 'UTF-8';
        $mailer->setFrom((string) env_value('MAIL_FROM_ADDRESS'), (string) env_value('MAIL_FROM_NAME', 'VELTOR'));
        $mailer->addAddress((string) env_value('ORDER_NOTIFICATION_EMAIL', 'gabaimaru07@gmail.com'));
        $mailer->isHTML(true);
        $mailer->Subject = 'VELTOR ' . $copy['order'] . ' ' . $order['reference'];
        $mailer->Body = $html;
        $mailer->AltBody = "Order {$order['reference']}\n{$order['customer_name']}\n{$order['phone']}\n{$order['city']}\n{$order['address']}\n" . implode("\n", $plainRows) . "\nTotal: " . money($order['total']);
        $mailer->send();
        return ['sent' => true, 'error' => null];
    } catch (Throwable $exception) {
        error_log('VELTOR SMTP delivery failed for ' . ($order['reference'] ?? 'unknown') . ': ' . $exception->getMessage());
        return ['sent' => false, 'error' => 'SMTP delivery failed. Check server logs.'];
    }
}
