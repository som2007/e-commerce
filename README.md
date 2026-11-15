# Threadwell - e-commerce 
(Core PHP MVC)

## Setup
1. `composer install`
2. Copy `.env.example` to `.env`, set DB + `APP_URL`.
3. `php database/migrate.php` then `php database/seed.php`
4. Point Apache/XAMPP at `public/` (or `php -S 127.0.0.1:8000 -t public public/index.php`).
5. Admin: `/admin` (login at `/login` with the seeded admin, change the password).

Quick test without MySQL: `DB_DRIVER=sqlite` (file `storage/database.sqlite`).

## Modules
See `PLAN.md`. Storefront: home, collections (filter/sort/pagination), product (colour swatches, sizes, reviews),
search, cart drawer, checkout (COD + online), payment, account, tracking, exchange, CMS pages, blog, contact, newsletter.
Admin: dashboard, products (+variants, uploads), categories, orders, coupons, reviews, exchanges, messages, subscribers, pages, blog.

## Payments
Set `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` for live Razorpay. Without keys (non-production) a test gateway is used.
