# PHP E-Commerce

An e-commerce website for selling and managing products, written in PHP with a
MySQL database.

## Features

- Product catalogue with categories and dynamic product pages
- Shopping cart and checkout
- Order management
- Product and stock management
- User authentication
- Barcode generation for products
- Email through PHPMailer
- Product image uploads

## Stack

| Area | Technology |
| --- | --- |
| Language | PHP |
| Database | MySQL (`ecommerce.sql`) |
| Templating | `views/` |
| Styling | CSS, SCSS, Less |
| Client-side | JavaScript |
| Email | PHPMailer |
| Barcodes | picqer/php-barcode-generator |
| Dependencies | Composer |

## Repository layout

```text
index.php          Entry point
app/               Application code
auth/              Authentication
config/            Configuration
helpers/           Helper functions
views/             Templates
public/            Web root
assets/            Styles and scripts
uploads/           User uploads
set_session.php    Session bootstrap
ecommerce.sql      Schema and seed data
composer.json      Dependencies
```

## Setup

1. Import the schema:

   ```bash
   mysql -u root -p < ecommerce.sql
   ```

2. Configure the database credentials for your environment.
3. Install dependencies:

   ```bash
   composer install
   ```

4. Serve the `public/` directory with any PHP-capable web server.

## Live demo

https://php-e-commerce.vercel.app
