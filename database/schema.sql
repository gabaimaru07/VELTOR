CREATE DATABASE IF NOT EXISTS veltor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE veltor;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(64) NOT NULL UNIQUE,
    name_en VARCHAR(100) NOT NULL,
    name_fr VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100) NOT NULL,
    audience ENUM('women','men','all') NOT NULL DEFAULT 'all',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    name_en VARCHAR(160) NOT NULL,
    name_fr VARCHAR(160) NOT NULL,
    name_ar VARCHAR(160) NOT NULL,
    description_en TEXT NOT NULL,
    description_fr TEXT NOT NULL,
    description_ar TEXT NOT NULL,
    price DECIMAL(10,2) UNSIGNED NOT NULL,
    discount_percent DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NOT NULL,
    is_new TINYINT(1) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT chk_products_discount CHECK (discount_percent <= 80),
    INDEX idx_products_category_active (category_id, active),
    INDEX idx_products_new_active (is_new, active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(24) NOT NULL UNIQUE,
    request_key CHAR(64) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(190) NOT NULL DEFAULT '',
    phone VARCHAR(40) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address VARCHAR(500) NOT NULL,
    language CHAR(2) NOT NULL DEFAULT 'en',
    total DECIMAL(10,2) UNSIGNED NOT NULL,
    status ENUM('new','in_delivery','delivered') NOT NULL DEFAULT 'new',
    email_status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    email_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    email_last_error VARCHAR(1000) NULL,
    email_sent_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_orders_status_created (status, created_at),
    INDEX idx_orders_email_status (email_status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(160) NOT NULL,
    unit_price DECIMAL(10,2) UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(10,2) UNSIGNED NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO categories (slug, name_en, name_fr, name_ar, audience) VALUES
('women', 'Women', 'Femmes', 'نساء', 'women'),
('men', 'Men', 'Hommes', 'رجال', 'men');

INSERT INTO products (category_id, name_en, name_fr, name_ar, description_en, description_fr, description_ar, price, discount_percent, stock, image_path, is_new)
SELECT c.id, p.name_en, p.name_fr, p.name_ar, p.description_en, p.description_fr, p.description_ar, p.price, p.discount_percent, p.stock, p.image_path, p.is_new
FROM (
    SELECT 'women' category, 'Aurelia Leather Tote' name_en, 'Cabas en cuir Aurélia' name_fr, 'حقيبة أوريليا الجلدية' name_ar,
    'A structured everyday tote in supple textured leather, with a zipped pocket and comfortable shoulder straps.' description_en,
    'Un cabas structuré en cuir souple grainé, doté d’une poche zippée et de poignées confortables.' description_fr,
    'حقيبة يومية أنيقة من الجلد المحبب الناعم، بجيب بسحاب ومقابض مريحة.' description_ar, 1290.00 price, 10.00 discount_percent, 14 stock, 'images/products/aurelia-tote.svg' image_path, 1 is_new
    UNION ALL SELECT 'women','Solace Gold Watch','Montre dorée Solace','ساعة سولاس الذهبية','A slim stainless-steel watch with a brushed gold finish and a clean, easy-to-read dial.','Une montre fine en acier inoxydable doré, au cadran épuré et lisible.','ساعة رفيعة من الفولاذ المقاوم للصدأ بلمسة ذهبية وميناء واضح وأنيق.',1590,10,8,'images/products/solace-watch.svg',1
    UNION ALL SELECT 'women','Luma Pearl Bracelet','Bracelet de perles Luma','سوار لوما اللؤلؤي','A delicate bracelet pairing luminous freshwater pearls with a polished adjustable clasp.','Un bracelet délicat mêlant perles d’eau douce et fermoir réglable poli.','سوار رقيق يجمع بين لآلئ المياه العذبة ومشبك قابل للتعديل.',420,0,22,'images/products/luma-bracelet.svg',1
    UNION ALL SELECT 'men','Atlas Leather Wallet','Portefeuille en cuir Atlas','محفظة أطلس الجلدية','A compact full-grain leather wallet with six card slots and a separate note compartment.','Un portefeuille compact en cuir pleine fleur, avec six rangements pour cartes et un compartiment billets.','محفظة صغيرة من الجلد الطبيعي تضم ست خانات للبطاقات وقسماً للنقود.',590,0,20,'images/products/atlas-wallet.svg',1
    UNION ALL SELECT 'men','Meridian Steel Watch','Montre acier Meridian','ساعة ميريديان الفولاذية','A versatile brushed-steel watch with a sapphire-look dial and a comfortable adjustable bracelet.','Une montre polyvalente en acier brossé, au cadran raffiné et au bracelet ajustable.','ساعة متعددة الاستخدامات من الفولاذ المصقول، بميناء أنيق وسوار قابل للتعديل.',1890,0,7,'images/products/meridian-watch.svg',1
    UNION ALL SELECT 'men','Forma Classic Sunglasses','Lunettes de soleil Forma','نظارات فورما الشمسية','Lightweight acetate sunglasses with UV400 lenses and a timeless square silhouette.','Des lunettes légères en acétate, verres UV400 et silhouette carrée intemporelle.','نظارات شمسية خفيفة من الأسيتات بعدسات UV400 وتصميم مربع كلاسيكي.',680,0,16,'images/products/forma-sunglasses.svg',1
) AS p JOIN categories c ON c.slug = p.category
WHERE NOT EXISTS (SELECT 1 FROM products existing WHERE existing.name_en = p.name_en);
