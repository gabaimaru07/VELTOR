# VELTOR

VELTOR is a server-rendered accessories store for women and men. The storefront uses HTML, CSS, and vanilla JavaScript; PHP 8.1+ and PDO provide authentication, catalog administration, contact submissions, checkout, inventory control, and order email. MySQL/MariaDB stores application data. English, French, and Arabic (RTL) are supported.

The old `.html` entry points redirect to their PHP counterparts. Run the application through PHP or Apache; opening an `.html` file directly is not a working deployment.

## Requirements

- PHP 8.1 or later with `pdo_mysql`, `mbstring`, `fileinfo`, and sessions.
- MySQL 8+ or MariaDB 10.5+.
- Composer, for PHPMailer.
- Apache with PHP, or PHP's built-in development server.

On Ubuntu, install the common packages with:

```sh
sudo apt update
sudo apt install php php-mysql php-mbstring php-xml composer mysql-server
sudo systemctl enable --now mysql
```

## Local setup

1. **Create and seed the database.** This imports the schema, bilingual categories, six accessories, and sample inventory:

   ```sh
   sudo mysql < database/schema.sql
   ```

2. **Create an application database user.** In `sudo mysql`, use a unique password in place of `CHOOSE_A_LONG_DATABASE_PASSWORD`:

   ```sql
   CREATE USER 'veltor'@'127.0.0.1' IDENTIFIED BY 'CHOOSE_A_LONG_DATABASE_PASSWORD';
   GRANT SELECT, INSERT, UPDATE, DELETE ON veltor.* TO 'veltor'@'127.0.0.1';
   FLUSH PRIVILEGES;
   ```

3. **Configure environment variables.**

   ```sh
   cp .env.example .env
   php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
   ```

   Edit `.env`, paste the generated 64-character value into `APP_KEY`, and set the database values. Keep `.env` private; it is ignored by Git and blocked by the included web-server rules.

4. **Install the mail dependency.**

   ```sh
   composer install --no-dev --optimize-autoloader
   ```

5. **Create the first administrator** from the project directory. The password is read through the local terminal without echoing:

   ```sh
   php setup-admin.php
   ```

   The command is CLI-only. Public registration always creates a customer account; it cannot grant administrator access.

6. **Allow the application to store product images.** Keep `uploads/` owned by the web-server account and writable by PHP. For a local Ubuntu setup, adjust ownership to the account running PHP rather than making it world-writable. The upload handler accepts JPEG, PNG, or WebP images up to 3 MB.

7. **Run locally:**

   ```sh
   php -S 127.0.0.1:8000 router.php
   ```

   Open <http://127.0.0.1:8000/>. The router blocks direct requests to `.env`, SQL files, Composer metadata, and application-private directories. For Apache, point the virtual host at this directory, enable `.htaccess` overrides, and ensure PHP handles `.php` files.

## Gmail order notifications

The checkout saves the order and item snapshots in a database transaction, recalculates prices and stock on the server, commits the order, then tries to send a responsive HTML notification to `gabaimaru07@gmail.com`. It includes the order reference and date, customer name, phone, city, full address, optional email, products, quantities, unit prices, and total.

Set these values in `.env` to enable delivery:

```dotenv
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail-address@gmail.com
MAIL_PASSWORD=your-16-character-google-app-password
MAIL_FROM_ADDRESS=your-gmail-address@gmail.com
MAIL_FROM_NAME=VELTOR
ORDER_NOTIFICATION_EMAIL=gabaimaru07@gmail.com
```

Use the sending Gmail account as both `MAIL_USERNAME` and `MAIL_FROM_ADDRESS`. Enable 2-Step Verification on that Google account, then create an **App Password** in the Google Account security settings. Store the generated App Password only in `.env`; do not send it in chat, add it to HTML/JavaScript, or commit it. The `.env.example` contains no credentials.

The recipient address is preconfigured as requested. This environment has no configured Gmail credentials, so successful delivery has **not** been verified here. If PHPMailer or SMTP is unavailable, the order remains recorded with `email_status=failed` (or pending if the database update itself fails); the confirmation does not claim delivery, and an administrator can retry from the Orders dashboard. Technical failures are written to the PHP server error log without being shown to customers.

## Store management

- **Catalog:** products and categories are loaded from MySQL. The admin can add/edit/delete products, translations, stock, price, discounts, categories, and uploaded images.
- **Cart:** quantities persist in browser `localStorage`; no browser-provided price is trusted during checkout.
- **Checkout:** cash-on-delivery order simulation with server-side validation, unique idempotency key, row locks, stock decrement, and a transaction. No card payments are collected.
- **Accounts:** customer registration/login uses PHP sessions, `password_hash()`, and `password_verify()`. Admin roles are assigned only by the CLI setup command or directly by an authorized database operator.
- **Orders:** admin users can review customer delivery details and ordered-item snapshots, update status (new, in delivery, delivered), and retry failed notifications.
- **Reports:** delivered sales totals, recent orders, best sellers, and a seven-day CSS bar chart.
- **Contact:** submissions are validated, rate-limited per session, saved to MySQL, and visible in the admin Messages tab.
- **Images:** editable local illustrations live in `images/products/`; uploaded raster images live in the ignored `uploads/` directory. Store contact details are in `config/store.php`, and editable About copy is in `config/content.php`.

## Languages and design

The language selector stores the choice in a year-long, HTTP-only preference cookie. It changes all storefront/admin labels and product content; Arabic sets RTL and bold Cairo typography, while French/English use Inter. The white minimalist layout, glass navigation/promo card, responsive product grids, and reduced-motion behavior are in `css/store.css` and `css/styles.css`.

## Deployment and GitHub

PHP and MySQL cannot run on GitHub Pages. This project is server-rendered, so its **functional storefront and backend must be deployed together to a PHP-capable host** with MySQL/MariaDB, Composer dependencies, HTTPS, environment variables, and writable uploads. GitHub can host the source repository and deployment workflow, but GitHub Pages cannot serve these PHP routes or connect directly to the database. A separately hosted GitHub Pages frontend would require converting the server-rendered routes into a static client and adding a CORS/session-aware JSON API; that split architecture is not part of this build.

For production, set `APP_ENV=production`, use HTTPS, use a unique `APP_KEY` and database password, keep `.env` outside public source control, configure PHP error logging, install Composer dependencies, and make only `uploads/` writable. The included router and `.htaccess` deny common private files; confirm the host honors those protections before launch.

## Project structure

```text
index.php, products.php, product.php, about.php, contact.php
cart.php, checkout.php, order.php, login.php, register.php, account.php
admin.php, actions.php, setup-admin.php, router.php
config/                 Environment bootstrap, store details, editable content
includes/               Layout, escaping/CSRF helpers, translations, email template
database/schema.sql     Database schema and six sample products
css/                    Shared styles and responsive store design
js/store.js             Cart persistence, menu, checkout summary
tests/smoke.php         Database-free translation, security-helper, pricing, and seed-asset checks
images/products/        Six replaceable sample product illustrations
uploads/                Ignored, runtime product-image uploads
composer.json           PHPMailer dependency
.env.example            Safe environment-variable template
```

## Validation status

- PHP syntax check: passed for all PHP files.
- JavaScript syntax check: passed for the shared storefront script.
- `php tests/smoke.php`: passed (translation coverage, escaping, discount calculations, CSRF helpers, and all six seeded image paths).
- MySQL import and database-backed user/order/admin flows: not run because this environment's MySQL server denies access to the current account; set up the database using the steps above before functional testing.
- Gmail SMTP and real order notification: not run; PHPMailer and Gmail App Password configuration are not present in this environment.
