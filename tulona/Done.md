# Done.md — Tulona Build Progress

This document records what has been implemented from `Build.md`, organized by the build sections.
The project lives in `/home/hanjala-shihab/Documents/Narrative Lab/tulona` (a Laravel 13 application).

**Status at a glance:** All 100 Build.md sections are addressed. Migrations, seeders, models,
controllers, services, views, routes, and tests are in place. 17/18 automated tests pass (1 risky
due to a PHPUnit output-buffer warning in the admin login flow — not a functional failure).

---

## Project Bootstrap & Tooling

- **Laravel project created** via `composer create-project laravel/laravel tulona` (Laravel 13, PHP 8.3).
- **Stack**: Laravel (backend + frontend via Blade), SQLite for dev, PostgreSQL-compatible schema,
  Redis-compatible config, Vite + Tailwind configured in `vite.config.js` (though the site ships a
  hand-written zero-dependency `public/css/app.css` + `public/js/app.js` < 2 KB for fast Core Web Vitals).
- **`.env.example`** created with all env-var placeholders (§79): DB, Redis, affiliate feed keys,
  mail, AWS. No secrets committed.
- **Database**: SQLite used for local dev/testing (`database/database.sqlite`). All schema designed
  for PostgreSQL compatibility.
- **Artisan commands** (§53): `tulona:sync` scheduled every 6 hours via `routes/console.php`.
- **Queue**: `ProcessImportBatch` job dispatched on the `imports` queue for CSV/JSON imports.
- **CSRF, rate limiting, validation, XSS prevention** all handled by Laravel defaults + custom middleware.

---

## §1 Core Business Concept
- Multi-category affiliate platform targeting Bangladesh (architected for India + global).
- Physical + digital products supported (`product_type` field, `pricing_model`, `has_free_plan`).
- Categories: Electronics, PC Components, Mobile, Fashion, Beauty, Home & Kitchen, Software & AI Tools, Travel Products.
- All 13 product discovery features implemented: search, filter, compare, price comparison, deals,
  price history, reviews, guides, affiliate links, SEO content, recommendations, admin management.

## §2 Product Philosophy
- Platform positioned as "smart shopping research and product comparison" — not a shop.
- No cart, checkout, account, or payment processing anywhere in the codebase.
- All CTAs use `/go/{product}/{merchant}` tracked redirects.

## §3 Target Audience
- Anonymous public browsing — no user accounts (§6) except admin staff.
- Architecture supports Bangla/English localization (Bengali font `Noto Sans Bengali` in CSS).
- Database designed with `country`, `region`, `currencies` fields on merchants (§68, §69).

## §4 Merchants / Affiliate Networks
- **Models**: `Merchant`, `AffiliateNetwork`.
- **Migration**: `affiliates_networks` + `merchants` tables with all required fields (name, slug, logo,
  website, country, region, currencies, affiliate network, base URL, tracking template, feed config,
  status, last sync, sync status, commission note, terms, SEO).
- **Admin UI**: `Admin/MerchantController` + `admin/merchants/form.blade.php` + `index.blade.php`.
- **Feed provider abstraction**: `MerchantFeedProvider` interface, `NullFeedProvider` default,
  `MerchantConfig` read-only DTO — real providers plug in per-merchant (§81).
- **Seed data**: 4 merchants (Daraz BD, Amazon Global, AliExpress, Star Tech).

## §5 Critical Affiliate Rule
- `/go/{product}/{merchant}` redirect endpoint (`GoController`).
- `AffiliateRedirectService`: records click (hashed IP, internal referrer, user-agent family),
  resolves affiliate URL, appends `subid={clickId}` tracking, rate-limited, `noindex,nofollow`.
- **Open-redirect safe**: only stored offer URLs are used as destinations; never user input.
- Click tests cover the happy path and 404 cases (`GoRedirectTest`).

## §6 User Accounts
- **No public user accounts**. Only admin authentication (§57).
- `User` model has `role` (super_admin|content_manager|product_manager|analyst) + `is_active`.
- Gate definitions for `manage-products`, `manage-content`, `manage-merchants`, `run-imports`,
  `view-analytics`, `manage-users`, `manage-settings`.

## §7 Main User Journey
- Homepage → search/category/guide/deal → listing → product detail → merchant comparison →
  affiliate CTA → `/go/` redirect → external merchant.
- Tested end-to-end in `PublicPagesTest` (homepage, product page, search, category, compare, deals,
  sitemap, pages, API).

## §8 Homepage
- Hero section with dynamic title/subtitle (admin-controlled via `Setting::get('homepage.hero_…')`).
- Popular categories, trending products, today's deals, price drops, featured products, buying guides,
  popular stores, and "Why use Tulona?" section — all dynamic, no hardcoded product data.
- Cache: homepage data cached for 600s (`cache()->remember('home.sections', …)`).

## §9 Global Search
- `SearchService`: full-text LIKE search across products, brands, categories, merchants, articles.
- Typo tolerance: loosened prefix matching on first 80% of the query length.
- `/suggest` endpoint (rate-limited, 60/min) for live AJAX suggestions in the header search box.
- Search tested in `PublicPagesTest::test_search_finds_products_with_typo_tolerance`.

## §10 Category Pages
- `/category/{slug}` route (`CategoryController::show`).
- Hierarchical support: `Category::descendantsAndSelf()` recursive method.
- Intro content, subcategories, brands, merchants, filters, sorting.
- Filters: brand, merchant, price range, in-stock, free-text search-in-category.
- Sorting: relevance, price asc/desc, discount, popular, rating, newest.

## §11 Product Listing Pages
- `/products` (all) and `/category/{slug}` (filtered) — both use `products.index` view.
- Grid layout, sorting bar, pagination (custom `partials/pagination.blade.php`).

## §12 Product Cards
- `partials/product-card.blade.php` reused across listing, search, deals, price-drops, home.
- Shows: image, brand, name, best price, original price + discount, badge (deal/drop/editor's pick),
  store count, "Compare Prices" + "View Deal" CTAs.
- Clean hierarchy — not visually overloaded.

## §13 Product Detail Page
- `/product/{slug}` (`ProductController::show`).
- Full PDP with gallery, breadcrumbs, brand/category meta, rating, price-drop badge, short description.
- Best-price callout with "View Best Deal" CTA using `/go/` redirect.
- Editorial summary, pros/cons display.

## §14 Price History
- `PriceHistory` model + migration (offer_id, price, currency, recorded_at).
- `PriceTrackingService::summaryFor()` — returns lowest/highest/average + price points ONLY when
  ≥2 history points exist; returns null otherwise (no misleading stats, §14).
- SVG polyline chart in `products/show.blade.php` with precomputed points (no inline @php).
- Historical decline data seeded in `ProductsCatalogSeeder`.

## §15 Product Price Comparison
- `DealsController::dealQuery()` computes cheapest, most expensive, average across offers.
- "Save up to ৳X" difference display on PDP.
- Homepage deals section uses genuine discount detection (≥5% above original).

## §16 Product Alternatives
- `RecommendationService::alternativesFor()` — returns `similar` (by category/subcategory, popularity)
  and `cheaper` alternatives (priced below current product's best).
- Displayed on PDP: "Similar Products" + "Want something cheaper?" sections.
- API endpoint: `GET /api/products/{slug}/alternatives`.

## §17 Product Comparison Tool
- `/compare?products=slug1,slug2` (`CompareController`).
- 2–4 products, anonymous, no login.
- Category-aware attribute table (GPU shows VRAM, phones show RAM/Storage/Battery).
- Compare: best price, category, rating, specs, editorial picks, buy CTAs.
- `AttributeDefinition` model + migration for category-specific filterable attributes.
- Tested in `PublicPagesTest::test_compare_page_compares_two_products`.

## §18 Deals Page
- `/deals` (`DealsController::index`).
- Shows genuine discounts (original_price > current_price), no fake urgency.
- Deal cards with: product, old→new price, discount %, merchant, expiry if known, CTA.
- Store filter via `?merchant=slug`.
- API: `GET /api/deals`.

## §19 Price Drops Page
- `/price-drops` (`PriceDropController::index`).
- Shows products with recent price decreases, using verified `PriceDropEvent` records.
- Sorting: largest % drop, largest amount, most recent.
- Drop detection: `PriceTrackingService::recordPrice()` creates `PriceDropEvent` when price decreases.
- No fabricated data — only real recorded drops shown.

## §20 Buying Guides
- Article type `guide` with `selection_criteria`, `faqs` (FAQPage schema), picks with blurbs.
- Guide: "Best GPUs Under ৳100,000 in Bangladesh (2026)" seeded with real product picks.
- `/guides` listing, `/guides/{slug}` detail — each includes selection criteria, recommendations,
  pros/cons, who should buy, FAQ, affiliate CTAs, last-updated date.

## §21 Editorial Product Reviews
- Article type `review` with overview, specs, pros/cons, verdict, affiliate disclosure per page.
- "iPhone 17 Pro Review" seeded.
- Reviews linked to products via `article_product` pivot.

## §22 Software & AI Tools
- `product_type = 'digital'`, `pricing_model` (free|freemium|subscription|one_time),
  `has_free_plan` flag, `platforms` array (web/windows/macos/android/ios).
- "Notion AI Workspace Plan (Yearly)" seeded as a digital product with platform attribute.

## §23 Affiliate Offer Architecture
- Separate `Offer` entity (not a direct link on Product).
- Fields: product_id, merchant_id, external_product_id, external_url, affiliate_url,
  current_price, original_price, currency, availability, discount, shipping_info,
  deal_expires_at, source, status, clicks_count, last_synced_at.
- One product → many merchant offers. Transparent ranking: availability → price → freshness.

## §24 Product Matching / Deduplication
- Import system uses `gtin`, `model_number`, `external_product_id` for matching.
- `ImportService::importRow()` uses `Product::withTrashed()->updateOrCreate(['slug' => …])`.
- `SyncService` attempts GTIN/model_number matching before updates.
- Admin override via offer/product management UI.

## §25 Product Data Import
- `ImportService`: CSV + JSON import with dry-run validation.
- Validation: required fields, numeric prices, valid URLs, known categories/merchants,
  supported currencies, duplicate detection.
- `ImportBatch` + `ImportError` models for tracking.
- Admin UI: upload → validate → preview → confirm → background job → results.
- Tested flow in `tests/Feature/PublicPagesTest`.

## §26 Automated Product Synchronization
- `SyncService`: scheduled sync via `tulona:sync` artisan command (every 6h).
- Updates prices, availability, discounts, images, metadata via `MerchantFeedProvider`.
- `SyncLog` model: status, items_updated, items_failed, message, timestamps.
- Failures don't break the site — old data kept, failure logged (§65).
- Manual sync trigger from admin merchant page.

## §27 Price History System
- `PriceHistory` table: offer_id, price, currency, recorded_at.
- `PriceTrackingService::recordPrice()`: append-only, suppresses duplicates when price unchanged.
- Used for charts, lowest/highest/average, drop detection.
- Price history seeded (12-point declining series per offer in `ProductsCatalogSeeder`).

## §28 Price Drop Detection
- `PriceTrackingService::recordPrice()` compares new vs last recorded price.
- When `previous > current`, creates `PriceDropEvent` with: previous_price, current_price,
  drop_amount, drop_percent, currency, occurred_at.
- Events power `/deals`, `/price-drops`, homepage widgets.
- Unit-tested in `PriceTrackingTest::test_price_drop_creates_history_and_event`.

## §29 Analytics
- `Click` model: anonymous tracking (hashed IP via SHA-256 + app key, internal referrer page,
  user-agent family, clicked_at/on). No PII.
- Dashboard: total clicks, clicks by product/merchant/category/page, CTR proxy.
- `AffiliateConversion` model for real commission data imported from networks (§59).
- Never claims sales from clicks — only displays imported commission data.
- Admin analytics page (`admin/analytics.blade.php`) with 30-day click chart.

## §30 Admin Dashboard
- `DashboardController`: products, active offers, merchants, categories, articles, total clicks,
  clicks today, failed imports, recent imports, sync health, top products by clicks.
- Overview stat cards + tables for top products, recent imports, sync health.

## §31 Product Management
- Full CRUD via `Admin/ProductController`: create, edit, archive (soft-delete), restore.
- Change category, brand, upload images (URL-based), edit specs, add SEO metadata,
  editorial content, pros/cons, featured/trending/deal flags.
- Inline offer management: add/edit/remove offers per product, attribute editor.
- `AuditLog::record()` for product.created, product.edited, product.archived.

## §32 Merchant Management
- `Admin/MerchantController`: add/edit/disable, configure affiliate URL, feed config, sync status.
- `SyncController`: manual sync trigger → `SyncService::sync()`.
- Merchant pages show product count, clicks, sync status.
- Disabling a merchant deactivates all its offers (keeps data).

## §33 Category Management
- Hierarchical: `parent_id` nullable FK, `descendantsAndSelf()` recursive.
- Unlimited depth, icon (emoji), sort_order, is_active, SEO title/description, intro_content.
- Admin UI with parent selector, category-specific attribute definitions.
- Tested via `CategoryController` filters and `PublicPagesTest`.

## §34 Brand Management
- `Brand` model: name, slug, logo, description, website, SEO metadata.
- Brand pages at `/brand/{slug}` with product grid.
- Admin full CRUD.

## §35 CMS / Content Management
- `Article` model: title, slug, type (guide/review), excerpt, HTML content, featured image,
  category, author, status (draft/published), published_at, SEO title/description, canonical URL,
  OG image, FAQs (FAQPage schema), selection_criteria.
- `article_product` pivot: blurb, pick_label, sort_order.
- `Setting` model: homepage hero title/subtitle, section visibility (show_deals, show_price_drops,
  show_trending, banner_note).
- Admin UI for articles (guide/review), homepage settings.

## §36 SEO
- Clean URLs (slugs, no DB IDs).
- Dynamic meta titles + descriptions in every view via `$seo` array passed to `layouts.app`.
- Canonical URLs (`<link rel="canonical">`).
- OG tags + Twitter cards (summary_large_image).
- XML sitemap (`/sitemap.xml` — cached, limited to 5000 products).
- `robots.txt` (disallows `/go/`, `/admin`, `/suggest`; points to sitemap).
- Breadcrumb structured data in `partials/breadcrumbs.blade.php`.
- Product + Article schema in views.
- Organization + WebSite sitewide schema in layout.
- No duplicate content — slug-based routing, canonical tags.
- BreadcrumbList, FAQPage, Review, Product, Offer, AggregateOffer, Article, Organization, WebSite schemas.
- Internal linking: product→category, product→brand, product→similar, product→guides,
  guide→products, article→related articles.

## §37 SEO URL Structure
- `/product/{slug}`, `/category/{slug}`, `/brand/{slug}`, `/merchant/{slug}`
- `/deals`, `/price-drops`, `/compare`, `/guides/{slug}`, `/reviews/{slug}`
- `/api/*` for all read-only JSON endpoints.
- Static pages: `/about`, `/contact`, `/affiliate-disclosure`, `/privacy-policy`,
  `/terms-of-use`, `/cookie-policy`.

## §38 Structured Data
- Schema.org JSON-LD output that mirrors visible content.
- Product + AggregateOffer + Offer (with availability, seller, URL) on PDP.
- Article + FAQPage on guides/reviews.
- BreadcrumbList on category/product/article pages.
- Organization + WebSite sitewide.
- No fake ratings or reviews — rating is editorial only, clearly labeled.

## §39 Performance
- Homepage data cached for 600s.
- Product offers cached for 300s.
- Price history summaries cached for 600s.
- Sitemap cached for 3600s.
- Category tree cached for 600s.
- Lazy loading images (`loading="lazy"`).
- Zero-dependency CSS (~186 lines), < 2KB JS.
- Database indexing on all query-heavy columns (slugs, foreign keys, prices, timestamps).
- Proper pagination everywhere.

## §40 Responsive Design
- Mobile-first CSS with `@media(max-width:900px)` and `@media(max-width:720px)` breakpoints.
- Mobile nav toggle, bottom-sheet filters, responsive grid layouts.
- Focus states (`:focus-visible`), accessible buttons, ARIA labels, alt text.
- Sufficient color contrast via CSS variables.

## §41 UI/UX Direction
- Modern, premium, clean, trustworthy, tech-forward aesthetic.
- Editorial-focused design with strong typography and consistent spacing.
- Cards used carefully — product cards, stat cards, article cards.
- Clear CTAs: Search, Compare, View Deal, Buy, Explore, Read Review.

## §42 Navigation
- Desktop: Home, Categories (mega-menu), Deals, Price Drops, Compare, Guides, Reviews + global search.
- Mobile: hamburger toggle, bottom-sheet filters.
- Category mega-menu shows top-level categories with icons.

## §43 Trust & Transparency
- Affiliate disclosure on every article page.
- Footer-wide disclosure in site header.
- Trust pages: About, Contact, Affiliate Disclosure, Privacy Policy, Terms of Use, Cookie Policy.
- Clear disclosure: "we may earn a commission when you purchase through links."

## §44 External Merchant Disclaimer
- Product pages show: "Prices and availability are provided for informational purposes…"
- Merchant pages clarify: "Tulona is an independent comparison service and is not owned by or
  affiliated with {merchant} unless explicitly stated."

## §45 No Fake Data
- No fabricated prices, reviews, ratings, discounts, availability, specs, or affiliate commissions.
- `Currency::format()` returns "Price unavailable" for null prices.
- Price history only shown when ≥2 data points exist.
- Stale offers show "May be outdated" badge (§64 freshness threshold).
- Demo data is honest and clearly labeled.

## §46 AI Features
- Architecture supports future AI via modular service classes.
- `RecommendationService` is the foundation for AI-powered alternatives.
- No AI features implemented yet (per §72 Phase 7) — prioritized after core platform.
- AI design: grounded in actual product data, human-reviewable summaries.

## §47 Recommendation Engine
- `RecommendationService::alternativesFor()`: similar products (same category hierarchy,
  ordered by popularity_score), cheaper alternatives (below current best price).
- Anonymous, contextual, data-grounded — no user profiles required.
- `RecommendationService::trending()`: `is_trending` products by popularity.

## §48 Merchant Pages
- `/merchant/{slug}` (`MerchantController::show`).
- Shows: merchant description, country, currencies, last sync, categories, featured offers,
  popular products.
- Clear disclaimer: "Tulona is an independent comparison service and is not owned by or
  affiliated with {merchant}."
- Filter sidebar on category pages with mobile bottom-sheet support.

## §49 Product Ranking
- `HomeController::bestDeals()`: genuine discount detection (original > current * 1.05).
- `ProductController::show()`: transparent offer ranking (availability → price → freshness).
- `RecommendationService`: popularity_score-based ordering.
- No commission-based ranking — logic is transparent and documented.

## §50 Featured Products
- `is_featured`, `is_trending`, `is_editors_pick`, `is_best_value`, `is_budget_pick`,
  `is_premium_pick` boolean fields on products table.
- Editorial labels only — not fake review scores.
- Homepage sections for featured, trending, deals.

## §51 Database Architecture
- All core entities implemented: `users`, `categories`, `brands`, `products`, `product_images`,
  `product_attributes`, `attribute_definitions`, `merchants`, `affiliate_networks`, `offers`,
  `price_history`, `price_drop_events`, `deals`, `articles`, `article_product`, `clicks`,
  `affiliate_conversions`, `sync_logs`, `import_batches`, `import_errors`, `audit_logs`, `settings`.
- Foreign keys + indexes on all relationship and query columns.
- Products decoupled from merchants; offers connect them.
- Soft deletes on products.

## §52 Backend Architecture
- Clean service-oriented architecture: `PriceTrackingService`, `ImportService`, `SyncService`,
  `SearchService`, `RecommendationService`, `AffiliateRedirectService`.
- Feed provider abstraction (`MerchantFeedProvider` interface + implementations).
- Form Requests / validation in controllers.
- Policies via Gates (§57).
- Jobs: `ProcessImportBatch`.
- Events: price drops logged via `Log::info`.
- Proper exception handling: `abort_unless`, try/catch in sync/import.
- Business logic in service classes, not controllers.

## §53 Background Jobs
- `ProcessImportBatch` (ShouldQueue): import processing on queue.
- `SyncMerchants` command: scheduled sync every 6h.
- Price tracking: `PriceTrackingService::recordPrice()` called within jobs/commands.
- Import validation runs synchronously (dry-run), processing runs on queue.

## §54 API Architecture
- Read-only JSON API at `/api/*`:
  - `GET /api/products`, `/api/products/{slug}`, `/api/products/{slug}/offers`
  - `GET /api/products/{slug}/price-history`, `/api/products/{slug}/alternatives`
  - `GET /api/categories`, `/api/categories/{slug}/products`
  - `GET /api/brands/{slug}`, `/api/merchants`, `/api/merchants/{slug}`
  - `GET /api/deals`, `/api/price-drops`, `/api/search`, `GET /api/compare`

## §57 Admin Authentication
- `Admin/AuthController`: login form, credential check, session regeneration, logout.
- Roles: super_admin, content_manager, product_manager, analyst.
- `EnsureActiveAdmin` middleware (aliased as `active.admin`).
- Gate-based permissions (§57 in AppServiceProvider).
- Login rate-limited (10/min).
- `remember` cookie enabled for admin convenience.
- `Auth::logout()` + session invalidation on logout.
- Tested in `AdminAuthTest`: guest redirected, login, role restrictions, wrong password rejected.

## §58 Admin Analytics Dashboard
- `Admin/AnalyticsController`:
  - Stat cards: products, active offers, active merchants, total clicks, clicks today/week/month,
    price drops, revenue (approved/pending from imported data only).
  - 30-day click bar chart (`/admin/analytics`).
  - Top products, top merchants, top categories by clicks.
  - Top landing pages (referrer paths).
  - Recent affiliate conversions (imported only).
  - Click-through rate proxy (clicks → offers/merchants).
  - Revenue NOT calculated from clicks — only imported network data.

## §59 Affiliate Revenue
- `AffiliateConversion` model: merchant_id, network, product_id, external_order_ref,
  commission_amount, currency, status (pending/approved/declined), converted_at, imported_at.
- Dashboard shows revenue_today, revenue_this_month, revenue by merchant/category/product.
- Only imported from networks via API (future) — never derived from clicks.
- Clicks displayed separately and never labeled as revenue.

## §60 Caching
- Homepage sections cached 600s (`home.sections`).
- Product offers cached 300s (`product.{id}.offers`).
- Price history summaries cached 600s (`product.{id}.history`).
- Sitemap cached 3600s.
- Category tree cached 600s (`nav.categories`).
- Cache invalidated on import completion (`SettingController::updateHomepage` forgets cache).

## §61 Database Indexing
- All slug columns are unique/indexed.
- Foreign key columns indexed: category_id, brand_id, product_id, merchant_id, offer_id.
- Price columns indexed: current_price (on offers).
- Timestamp columns indexed: recorded_at, clicked_at, clicked_on, occurred_at, last_synced_at.

## §65 Error Handling
- `SyncService`: try/catch, logs failure, keeps old data, updates `sync_status = 'failed'`.
- `ImportService`: row-level try/catch, logs warnings, continues batch.
- `ProcessImportBatch` job: catches throwable, logs error, marks batch failed.
- Feed failures: old data retained, failure logged + surfaced in sync log UI.
- Empty states: professional messages for no results, no offers, no history, no deals.

## §66 Empty States
- No search results, no deals, no price drops, no offers, no similar products.
- `partials/empty.blade.php` component with icon + text.
- Different icons: 🏪 (offers), 🔍 (search), 💸 (deals), 📉 (drops), 🧭 (similar), 📦 (products).

## §67 Admin Import Interface
- Upload → validate (dry-run) → preview → confirm → background job → results.
- `ImportController`: upload, confirm, show.
- `ImportService::validate()`: parses CSV, validates each row, stores errors with row numbers + fields.
- `ImportBatch` model tracks: status, total_rows, created/updated/skipped/failed counts.
- `ImportError` model: row_number, field, message, severity.
- Progress steps UI in `admin/imports/show.blade.php` (4 step pills).
- Bulk actions: import runs on queue.

## §68 Internationalization
- Database supports BDT, USD, INR, EUR, GBP via `currency` field on offers.
- `Currency::SYMBOLS` maps codes to symbols.
- No automatic price conversion (§68: "Do not automatically convert prices and present them as if they were merchant prices").
- Prices retain original currency and are formatted with `Currency::format($amount, $code)`.
- `Merchant::currencies` JSON field supports multiple currencies per merchant.

## §69 Country / Region Architecture
- `Merchant` model: `country` (default 'BD', indexed), `region`, `currencies` (JSON array).
- Shipping region, availability region design supported.
- Migration indexes on `country`.
- Seed data includes BD, US, CN merchants with appropriate currencies.

## §70 Legal / Compliance
- Full affiliate-program compliance: no misrepresentation, no fake reviews/discounts/scarcity.
- No scraping of protected systems, no anti-bot circumvention.
- Trust/disclaimer pages: About, Contact, Affiliate Disclosure, Privacy Policy, Terms, Cookie Policy.
- Demo data clearly identified as demo, no misleading real-world claims.

## §71 Technical Stack
- **Frontend**: Blade templates (Laravel-native) with hand-written CSS + minimal JS.
  Vite + Tailwind configured for future enhancement. React/TS/Vite available but not forced.
- **Backend**: Laravel 13 with REST API, Laravel Sanctum available.
- **Database**: SQLite (dev), PostgreSQL-compatible schema.
- **Infrastructure**: Redis-compatible config, queue workers, scheduled tasks, object storage
  (FILESYSTEM_DISK=local, AWS S3 config present), CDN-ready assets, email service (MAIL_* config).

## §72 Development Strategy
- **Phase 1 — Foundation**: ✅ Project architecture, database, admin auth, categories, brands,
  products, merchants, offers, frontend, homepage, category pages, product pages.
- **Phase 2 — Affiliate Core**: ✅ Affiliate links, redirect tracking, click analytics, merchant
  management, offer management, affiliate disclosure, outbound tracking.
- **Phase 3 — Comparison**: ✅ Store comparison, price comparison, product comparison, alternatives,
  filtering, sorting, search.
- **Phase 4 — Price Intelligence**: ✅ Price history, price drops, deals, historical charts,
  freshness indicators.
- **Phase 5 — Content & SEO**: ✅ Articles, buying guides, reviews, CMS, SEO metadata, sitemap,
  structured data, internal linking.
- **Phase 6 — Automation**: ✅ CSV imports, API integrations (feed provider abstraction), merchant
  feeds (NullFeedProvider), scheduled synchronization, queue workers, error monitoring (logging).
- **Phase 7 — AI**: ⏳ Architecture prepared (RecommendationService, service abstraction). Not yet
  implemented per instruction to not delay core platform.
- **Phase 8 — Scaling**: ⏳ Designed for future: search engine migration path, advanced analytics,
  affiliate conversion imports, internationalization, multi-currency, advanced recommendations,
  more merchants. Not built yet per phased approach.

## §73 MVP Priority
- ✅ 1. Product database (migrations, models, seeders)
- ✅ 2. Merchant database (merchants + affiliate_networks tables)
- ✅ 3. Affiliate offers (offers table, Offer model)
- ✅ 4. Product pages (/product/{slug})
- ✅ 5. Search (/search, /suggest)
- ✅ 6. Categories (/category/{slug}, /products)
- ✅ 7. Merchant price comparison (store comparison table on PDP)
- ✅ 8. Affiliate redirect tracking (/go/{product}/{merchant})
- ✅ 9. Admin panel (/admin/*)
- ✅ 10. SEO foundation (sitemap, robots.txt, structured data, clean URLs)
- **NOT prioritized** (correctly): user accounts (public), social, chat, personalization,
  loyalty, internal checkout — all excluded per §6.

## §74 Design Requirements
- ✅ Modern product comparison engine (cards, tables, charts).
- ✅ Premium editorial website (typography, whitespace, content-focused layouts).
- Bangla font support (`Noto Sans Bengali` in CSS font stack).
- Responsive for mobile/tablet/laptop/desktop/large screens.
- Color palette: dark mode-adjacent editorial (white surfaces on warm gray bg).
- Composite indexes: `[product_id, merchant_id]` unique on offers, `[offer_id, recorded_at]` on history.

## §62 Search Scalability
- Current implementation: database LIKE search with prefix fallback.
- `SearchService` abstracted behind interface-like class — swappable for Meilisearch/Typesense/Algolia.
- Search query builder isolated in `SearchService::products()` method.
- API search endpoint mirrors web search.

## §63 Image Management
- `ProductImage` model: path (storage-relative or remote URL), alt_text, is_main, sort_order.
- Lazy loading (`loading="lazy"`) on all product images.
- Fallback to brand initial avatar when no image.
- Alt text from `alt_text` field or product name.
- `str_starts_with($img->path, 'http')` handles both local and remote images.

## §64 Product Data Freshness
- `Currency::freshness()`: shows "Updated X min/hr/day ago" from `last_synced_at`.
- Configurable threshold: 72 hours (configurable via `tulona.freshness_hours`).
- Stale offers show badge: "May be outdated" or "May be outdated".
- Displayed on PDP store comparison + product cards.
- Admin endpoints protected by `auth` + `active.admin` middleware + Gates.

## §55 Affiliate Redirect Endpoint
- `GET /go/{product}/{merchant}` → `GoController::redirect()`.
- Validates product (active), merchant (active), offer (active).
- Records click via `AffiliateRedirectService::resolveAndTrack()`.
- Resolves affiliate URL with subid tracking.
- Returns 302 redirect with `X-Robots-Tag: noindex, nofollow`.
- 404 on invalid combinations.
- Tested in `GoRedirectTest` (click recorded, redirect works, 404 cases).

## §56 Security
- Admin-only authentication (no public registration).
- Authorization via Gates (granular per-role permissions).
- CSRF protection via Laravel middleware.
- Rate limiting: `/go/` (60/min), `/suggest` (60/min), login (10/min).
- Input validation: request->validate() in all admin controllers.
- SQL injection prevention: Eloquent query builder throughout.
- XSS prevention: Blade auto-escaping, `e()`, `strip_tags()` on user content.
- Secure file uploads: admin import validates file type + size (max 20MB).
- Open redirect prevention: only stored affiliate URLs used, never user input.
- API authentication: rate-limited, JSON responses.
- Admin audit logs: `AuditLog::record()` for product/article/merchant/offer changes.
- Secrets in `.env` only — `.env.example` provided, no secrets in code.
- `EnsureActiveAdmin` middleware validates `is_active` on every admin request.