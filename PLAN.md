# Build Plan (module-wise)

Reference store: Shopify-style men's shirt store (mega-menu, collections, product pages with colour/size variants,
cart drawer, prepaid discount, exchange policy, tracking, newsletter).

| # | Module | Frontend | Backend |
|---|--------|----------|---------|
| 1 | Foundation | layout, helpers, param router | Database (MySQL + sqlite test), migrations runner |
| 2 | Catalog schema + seed | - | categories tree, products, meta, variants, images, reviews seed |
| 3 | Header / footer / mega-menu | announcement bar, mega menu, search, cart icon, footer, newsletter | MenuService, newsletter API |
| 4 | Home page | hero, product rails, video, trust badges, about | HomeController |
| 5 | Collections | grid, filters (stock, price), sort (9 options), pagination | CollectionController |
| 6 | Product page | gallery, colour swatches, size selector + chart, qty, add/buy now, tabs, reviews | ProductController, ReviewController |
| 7 | Search | /search page + live suggestions | SearchController |
| 8 | Cart | slide drawer, /cart page | CartService (session), cart API |
| 9 | Checkout + payment | address form, coupon, COD / online (Razorpay or test gateway) | OrderService, Coupon, PaymentService |
| 10 | Account | orders, order detail, addresses, profile | AccountController |
| 11 | Order tracking + exchange | /track-order, /exchange-request | TrackingController, ExchangeController |
| 12 | CMS pages + blog + contact | about, policies, contact form, blog | PageController, BlogController |
| 13 | Admin panel | dashboard, products, categories, orders, coupons, reviews, exchanges, messages, subscribers, pages | Admin controllers |
| 14 | QA | lint + HTTP smoke tests over every route | - |
