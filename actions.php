<?php
require __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}
verify_csrf();
$pdo = db();
$action = post_string('action');

if ($action === 'register') {
    $name = trim(post_string('name'));
    $email = strtolower(trim(post_string('email')));
    $password = post_string('password');
    $confirm = post_string('password_confirm');
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10 || strlen($password) > 200) {
        flash_set('error', 'required');
        redirect('register.php');
    }
    if (!hash_equals($password, $confirm)) {
        flash_set('error', 'password_mismatch');
        redirect('register.php');
    }
    try {
        $statement = $pdo->prepare("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'customer')");
        $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            flash_set('error', 'required');
            redirect('register.php');
        }
        throw $exception;
    }
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int) $pdo->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'customer'];
    flash_set('success', 'registered');
    redirect('account.php');
}

if ($action === 'login') {
    $attempt = $_SESSION['login_attempt'] ?? ['count' => 0, 'start' => time(), 'blocked' => 0];
    if (!is_array($attempt) || time() - (int) ($attempt['start'] ?? 0) > 900) {
        $attempt = ['count' => 0, 'start' => time(), 'blocked' => 0];
    }
    if ((int) $attempt['blocked'] > time()) {
        flash_set('error', 'invalid_credentials');
        redirect('login.php');
    }
    $email = strtolower(trim(post_string('email')));
    $password = post_string('password');
    if (strlen($email) > 190 || strlen($password) > 200) {
        flash_set('error', 'invalid_credentials');
        redirect('login.php');
    }
    $statement = $pdo->prepare('SELECT id,name,email,password_hash,role FROM users WHERE email=? LIMIT 1');
    $statement->execute([$email]);
    $user = $statement->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $attempt['count'] = (int) $attempt['count'] + 1;
        if ($attempt['count'] >= 8) $attempt['blocked'] = time() + 900;
        $_SESSION['login_attempt'] = $attempt;
        flash_set('error', 'invalid_credentials');
        redirect('login.php');
    }
    unset($_SESSION['login_attempt']);
    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    redirect('account.php');
}

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], '', $params['secure'], $params['httponly']);
    }
    session_destroy();
    redirect('index.php');
}

if ($action === 'contact') {
    $name = trim(post_string('name'));
    $email = strtolower(trim(post_string('email')));
    $message = trim(post_string('message'));
    if (post_string('website') !== '') {
        redirect('contact.php');
    }
    $lastMessage = (int) ($_SESSION['last_contact_at'] ?? 0);
    if (time() - $lastMessage < 10 || mb_strlen($name) < 2 || mb_strlen($name) > 120 || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '' || mb_strlen($message) > 3000) {
        flash_set('error', 'contact_error');
        redirect('contact.php');
    }
    $statement = $pdo->prepare('INSERT INTO contact_messages (name,email,message) VALUES (?,?,?)');
    $statement->execute([$name, $email, $message]);
    $_SESSION['last_contact_at'] = time();
    flash_set('success', 'contact_sent');
    redirect('contact.php');
}

if ($action === 'checkout') {
    $name = trim(post_string('name'));
    $phone = trim(post_string('phone'));
    $city = trim(post_string('city'));
    $address = trim(post_string('address'));
    $email = strtolower(trim(post_string('email')));
    $requestToken = post_string('request_token');
    $cartData = json_decode(post_string('cart_json'), true);
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !preg_match('/^\+?[0-9][0-9()\-\s]{5,22}[0-9]$/', $phone) || mb_strlen($city) < 2 || mb_strlen($city) > 100 || mb_strlen($address) < 5 || mb_strlen($address) > 500 || ($email !== '' && (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL))) || !preg_match('/^[a-f0-9]{48}$/', $requestToken) || !is_array($cartData)) {
        flash_set('error', 'required');
        redirect('checkout.php');
    }
    $cart = [];
    foreach ($cartData as $entry) {
        if (!is_array($entry) || count($entry) !== 2) continue;
        [$id, $quantity] = array_values($entry);
        if (!is_int($id) && !is_string($id)) continue;
        if (!is_int($quantity) && !is_string($quantity)) continue;
        $id = filter_var($id, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
        if (!$id || !$quantity || $quantity < 1 || $quantity > 50) continue;
        $cart[(int) $id] = (int) $quantity;
        if (count($cart) > 30) break;
    }
    if (!$cart || count($cart) > 30) {
        flash_set('error', 'empty_order');
        redirect('cart.php');
    }

    $appKey = env_value('APP_KEY', '');
    if (strlen($appKey) < 32 || str_contains(strtolower($appKey), 'replace')) {
        error_log('VELTOR checkout blocked because APP_KEY is not configured.');
        flash_set('error', 'required');
        redirect('checkout.php');
    }
    $requestKey = hash_hmac('sha256', session_id() . '|' . $requestToken, $appKey);
    $existing = $pdo->prepare('SELECT reference FROM orders WHERE request_key=?');
    $existing->execute([$requestKey]);
    if ($reference = $existing->fetchColumn()) {
        $_SESSION['last_order_ref'] = $reference;
        redirect('order.php?ref=' . rawurlencode((string) $reference));
    }

    $orderItems = [];
    try {
        $pdo->beginTransaction();
        $getProduct = $pdo->prepare('SELECT id,name_en,name_fr,name_ar,price,discount_percent,stock,active FROM products WHERE id=? FOR UPDATE');
        $decrement = $pdo->prepare('UPDATE products SET stock=stock-? WHERE id=? AND active=1 AND stock>=?');
        $totalCents = 0;
        foreach ($cart as $productId => $quantity) {
            $getProduct->execute([$productId]);
            $product = $getProduct->fetch();
            if (!$product || !(int) $product['active'] || (int) $product['stock'] < $quantity) {
                throw new DomainException('Stock changed before order creation');
            }
            $price = (string) $product['price'];
            $unitCents = product_price_cents($product);
            $lineCents = $unitCents * $quantity;
            $totalCents += $lineCents;
            $decrement->execute([$quantity, $productId, $quantity]);
            if ($decrement->rowCount() !== 1) throw new DomainException('Stock changed before order creation');
            $orderItems[] = [
                'product_id' => $productId,
                'product_name' => (string) ($product['name_' . $language] ?? $product['name_en']),
                'unit_price' => number_format($unitCents / 100, 2, '.', ''),
                'quantity' => $quantity,
                'line_total' => number_format($lineCents / 100, 2, '.', ''),
            ];
        }
        $total = number_format($totalCents / 100, 2, '.', '');
        $reference = order_reference();
        $user = current_user();
        $insertOrder = $pdo->prepare("INSERT INTO orders (reference,request_key,user_id,customer_name,customer_email,phone,city,address,language,total) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $insertOrder->execute([$reference, $requestKey, $user['id'] ?? null, $name, $email, $phone, $city, $address, $language, $total]);
        $orderId = (int) $pdo->lastInsertId();
        $insertItem = $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,unit_price,quantity,line_total) VALUES (?,?,?,?,?,?)');
        foreach ($orderItems as $item) {
            $insertItem->execute([$orderId, $item['product_id'], $item['product_name'], $item['unit_price'], $item['quantity'], $item['line_total']]);
        }
        $pdo->commit();
    } catch (DomainException) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash_set('error', 'stock_error');
        redirect('cart.php');
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($exception->getCode() === '23000') {
            $existing->execute([$requestKey]);
            if ($reference = $existing->fetchColumn()) {
                $_SESSION['last_order_ref'] = $reference;
                redirect('order.php?ref=' . rawurlencode((string) $reference));
            }
        }
        throw $exception;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    }

    $getOrder = $pdo->prepare('SELECT * FROM orders WHERE id=?');
    $getOrder->execute([$orderId]);
    $order = $getOrder->fetch();
    $delivery = send_order_notification($order, $orderItems);
    $emailStatus = $delivery['sent'] ? 'sent' : 'failed';
    $sentAt = $delivery['sent'] ? date('Y-m-d H:i:s') : null;
    $updateEmail = $pdo->prepare('UPDATE orders SET email_status=?, email_attempts=email_attempts+1, email_last_error=?, email_sent_at=? WHERE id=?');
    $updateEmail->execute([$emailStatus, $delivery['error'], $sentAt, $orderId]);
    $_SESSION['last_order_ref'] = $reference;
    redirect('order.php?ref=' . rawurlencode($reference));
}

if ($action === 'admin_product' || $action === 'admin_delete_product' || $action === 'admin_order_status' || $action === 'admin_retry_email' || $action === 'admin_category' || $action === 'admin_category_update' || $action === 'admin_delete_category') {
    require_admin();
    if ($action === 'admin_delete_product') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            flash_set('error', 'required');
            redirect('admin.php?tab=products');
        }
        $statement = $pdo->prepare('SELECT image_path FROM products WHERE id=?');
        $statement->execute([$id]);
        $image = $statement->fetchColumn();
        $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
        if (is_string($image) && str_starts_with($image, 'uploads/')) {
            $file = __DIR__ . '/' . $image;
            if (is_file($file) && str_starts_with(realpath($file) ?: '', realpath(__DIR__ . '/uploads') . DIRECTORY_SEPARATOR)) unlink($file);
        }
        flash_set('success', 'products_deleted');
        redirect('admin.php?tab=products');
    }
    if ($action === 'admin_order_status') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $status = post_string('status');
        if (!$id || !in_array($status, ['new', 'in_delivery', 'delivered'], true)) {
            flash_set('error', 'required');
            redirect('admin.php?tab=orders');
        }
        $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $id]);
        flash_set('success', 'order_updated');
        redirect('admin.php?tab=orders');
    }
    if ($action === 'admin_retry_email') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $statement = $pdo->prepare("SELECT * FROM orders WHERE id=? AND email_status!='sent'");
        $statement->execute([$id ?: 0]);
        $order = $statement->fetch();
        if ($order) {
            $itemsQuery = $pdo->prepare('SELECT product_name,unit_price,quantity,line_total FROM order_items WHERE order_id=?');
            $itemsQuery->execute([$id]);
            $delivery = send_order_notification($order, $itemsQuery->fetchAll());
            $status = $delivery['sent'] ? 'sent' : 'failed';
            $sentAt = $delivery['sent'] ? date('Y-m-d H:i:s') : null;
            $pdo->prepare('UPDATE orders SET email_status=?,email_attempts=email_attempts+1,email_last_error=?,email_sent_at=? WHERE id=?')->execute([$status, $delivery['error'], $sentAt, $id]);
            flash_set($delivery['sent'] ? 'success' : 'error', $delivery['sent'] ? 'mail_retried' : 'admin_retry_unavailable');
        } else {
            flash_set('error', 'required');
        }
        redirect('admin.php?tab=orders');
    }
    if ($action === 'admin_category') {
        $en = trim(post_string('name_en'));
        $fr = trim(post_string('name_fr'));
        $ar = trim(post_string('name_ar'));
        $audience = post_string('audience');
        if (mb_strlen($en) > 100 || mb_strlen($fr) > 100 || mb_strlen($ar) > 100 || $en === '' || $fr === '' || $ar === '' || !in_array($audience, ['women', 'men', 'all'], true)) {
            flash_set('error', 'required');
            redirect('admin.php?tab=products');
        }
        $slugBase = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $en) ?? '', '-'));
        $slug = ($slugBase !== '' ? $slugBase : 'category') . '-' . bin2hex(random_bytes(3));
        $pdo->prepare('INSERT INTO categories (slug,name_en,name_fr,name_ar,audience) VALUES (?,?,?,?,?)')->execute([$slug, $en, $fr, $ar, $audience]);
        flash_set('success', 'category_saved');
        redirect('admin.php?tab=products');
    }
    if ($action === 'admin_category_update') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $en = trim(post_string('name_en'));
        $fr = trim(post_string('name_fr'));
        $ar = trim(post_string('name_ar'));
        $audience = post_string('audience');
        if (!$id || mb_strlen($en) > 100 || mb_strlen($fr) > 100 || mb_strlen($ar) > 100 || $en === '' || $fr === '' || $ar === '' || !in_array($audience, ['women', 'men', 'all'], true)) {
            flash_set('error', 'required');
            redirect('admin.php?tab=products');
        }
        $pdo->prepare('UPDATE categories SET name_en=?,name_fr=?,name_ar=?,audience=? WHERE id=?')->execute([$en, $fr, $ar, $audience, $id]);
        flash_set('success', 'category_saved');
        redirect('admin.php?tab=products');
    }
    if ($action === 'admin_delete_category') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $statement = $pdo->prepare("SELECT slug,(SELECT COUNT(*) FROM products WHERE category_id=categories.id) product_count FROM categories WHERE id=?");
        $statement->execute([$id ?: 0]);
        $category = $statement->fetch();
        if (!$category || in_array($category['slug'], ['women', 'men'], true) || (int) $category['product_count'] > 0) {
            flash_set('error', 'required');
            redirect('admin.php?tab=products');
        }
        $pdo->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);
        flash_set('success', 'category_deleted');
        redirect('admin.php?tab=products');
    }
    if ($action === 'admin_product') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        $nameEn = trim(post_string('name_en'));
        $nameFr = trim(post_string('name_fr'));
        $nameAr = trim(post_string('name_ar'));
        $descEn = trim(post_string('description_en'));
        $descFr = trim(post_string('description_fr'));
        $descAr = trim(post_string('description_ar'));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $discount = filter_var($_POST['discount_percent'] ?? 0, FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
        $categoryExists = $pdo->prepare('SELECT 1 FROM categories WHERE id=?');
        $categoryExists->execute([$categoryId]);
        if (!$categoryId || !$categoryExists->fetchColumn() || mb_strlen($nameEn) > 160 || mb_strlen($nameFr) > 160 || mb_strlen($nameAr) > 160 || mb_strlen($descEn) > 5000 || mb_strlen($descFr) > 5000 || mb_strlen($descAr) > 5000 || $nameEn === '' || $nameFr === '' || $nameAr === '' || $descEn === '' || $descFr === '' || $descAr === '' || $price === false || $price < 0 || $price > 99999999 || $discount === false || $discount < 0 || $discount > 80 || $stock === false || $stock < 0 || $stock > 1000000) {
            flash_set('error', 'required');
            redirect('admin.php?tab=products' . ($id ? '&edit=' . $id : ''));
        }
        $imagePath = '';
        if ($id) {
            $oldImage = $pdo->prepare('SELECT image_path FROM products WHERE id=?');
            $oldImage->execute([$id]);
            $imagePath = (string) $oldImage->fetchColumn();
            if ($imagePath === '') {
                flash_set('error', 'required');
                redirect('admin.php?tab=products');
            }
        } else {
            $imagePath = trim(post_string('image_path'));
            if ($imagePath !== '' && (!preg_match('#^images/products/[a-zA-Z0-9._-]+$#', $imagePath) || !is_file(__DIR__ . '/' . $imagePath))) {
                flash_set('error', 'required');
                redirect('admin.php?tab=products');
            }
        }
        $previousImagePath = $imagePath;
        if (isset($_FILES['image']) && is_array($_FILES['image']) && ($_FILES['image']['name'] ?? '') !== '') {
            $file = $_FILES['image'];
            $temporaryPath = $file['tmp_name'] ?? null;
            $fileSize = filter_var($file['size'] ?? null, FILTER_VALIDATE_INT);
            if (!is_string($file['name'] ?? null) || !is_string($temporaryPath) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($temporaryPath) || $fileSize === false || $fileSize > 3 * 1024 * 1024) {
                flash_set('error', 'required');
                redirect('admin.php?tab=products' . ($id ? '&edit=' . $id : ''));
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
            $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($extensions[$mime])) {
                flash_set('error', 'required');
                redirect('admin.php?tab=products' . ($id ? '&edit=' . $id : ''));
            }
            $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
            $directory = __DIR__ . '/uploads';
            if (!is_dir($directory) || !is_writable($directory) || !move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
                throw new RuntimeException('Product image could not be stored');
            }
            $imagePath = 'uploads/' . $filename;
        }
        if (!$id && ($imagePath === '' || !preg_match('#^images/products/[a-zA-Z0-9._-]+$|^uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$#', $imagePath))) {
            flash_set('error', 'required');
            redirect('admin.php?tab=products');
        }
        if ($id) {
            $statement = $pdo->prepare('UPDATE products SET category_id=?,name_en=?,name_fr=?,name_ar=?,description_en=?,description_fr=?,description_ar=?,price=?,discount_percent=?,stock=?,image_path=?,is_new=? WHERE id=?');
            $statement->execute([$categoryId, $nameEn, $nameFr, $nameAr, $descEn, $descFr, $descAr, number_format((float) $price, 2, '.', ''), number_format((float) $discount, 2, '.', ''), $stock, $imagePath, post_string('is_new') === '1' ? 1 : 0, $id]);
            if ($previousImagePath !== $imagePath && str_starts_with($previousImagePath, 'uploads/')) {
                $oldFile = __DIR__ . '/' . $previousImagePath;
                if (is_file($oldFile) && str_starts_with(realpath($oldFile) ?: '', realpath(__DIR__ . '/uploads') . DIRECTORY_SEPARATOR)) unlink($oldFile);
            }
        } else {
            $statement = $pdo->prepare('INSERT INTO products (category_id,name_en,name_fr,name_ar,description_en,description_fr,description_ar,price,discount_percent,stock,image_path,is_new) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $statement->execute([$categoryId, $nameEn, $nameFr, $nameAr, $descEn, $descFr, $descAr, number_format((float) $price, 2, '.', ''), number_format((float) $discount, 2, '.', ''), $stock, $imagePath, post_string('is_new') === '1' ? 1 : 0]);
        }
        flash_set('success', 'products_saved');
        redirect('admin.php?tab=products');
    }
}

flash_set('error', 'required');
redirect('index.php');
