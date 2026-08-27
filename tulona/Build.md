# Build a Production-Ready Affiliate Shopping & Product Comparison Platform

The full project should be on laravel, frontend and backend both in laravel

You are a senior full-stack engineer, product architect, UI/UX designer, SEO specialist, and affiliate-commerce systems engineer.

I want you to build a complete, production-ready affiliate marketing and product discovery platform.

The website should NOT function as a traditional e-commerce marketplace.

We do NOT sell products ourselves.

We do NOT process payments.

We do NOT manage shipping.

We do NOT need user accounts, login, registration, cart, checkout, order management, or customer purchase history.

Our website's purpose is:

> Help users discover products, compare products/offers from different stores, read useful product information and buying guides, and then send users to external stores through affiliate links.

When a user clicks "Buy Now", "View Deal", or similar CTA, they are redirected to the external merchant using our affiliate tracking URL.

The business earns affiliate commissions when users purchase through those links.

---

# 1. Core Business Concept

Build a multi-category affiliate shopping platform initially targeting Bangladesh, but architect it so it can later support India and global markets.

Primary product categories should include:

* Electronics
* Gadgets
* PC Components
* PC Accessories
* Mobile Phones
* Mobile Accessories
* Fashion
* Clothing
* Beauty
* Skincare
* Home & Kitchen
* Software
* AI Tools
* Travel Products

The architecture must allow unlimited future categories and subcategories.

The website should combine:

1. Product discovery
2. Product search
3. Product filtering
4. Product comparison
5. Store/merchant comparison
6. Price comparison
7. Deals
8. Price history
9. Product reviews/editorial content
10. Buying guides
11. Affiliate links
12. SEO-driven content
13. AI-assisted recommendations
14. Admin-controlled product management

---

# 2. Important Product Philosophy

Do NOT make the website look like Daraz or Amazon where users think they are purchasing directly from us.

Instead, position the platform as:

> A smart shopping research and product comparison platform.

The user should understand:

"Find the right product here → compare options → click the best store/deal → purchase there."

The website should feel trustworthy, modern, fast, editorial, and data-driven.

---

# 3. Target Audience

Primary audience:

* Bangladeshi online shoppers
* Students
* PC builders
* Gamers
* Tech enthusiasts
* Smartphone buyers
* People researching products before purchasing
* People looking for discounts
* People comparing prices across stores

Future audiences:

* Indian shoppers
* International shoppers

Support both Bangla and English architecture, but initially prioritize English unless otherwise specified.

The architecture should support localization later.

---

# 4. Affiliate Marketplaces / Merchants

The platform should support multiple affiliate merchants.

Initial examples:

* Daraz
* Amazon
* AliExpress
* eBay
* Other affiliate networks
* Other local and international merchants

Do NOT hardcode the application around only these merchants.

Create a generic merchant/affiliate system.

Each merchant should have:

* ID
* Name
* Logo
* Website
* Country/region
* Currency
* Affiliate network
* Affiliate tracking configuration
* Affiliate base URL
* API/feed configuration if applicable
* Status
* Commission information if available
* Last synchronization time
* Product count
* Terms/notes

The admin should be able to add new merchants without modifying the core application.

---

# 5. Critical Affiliate Rule

Every outbound product link must pass through an internal tracking/redirect mechanism where appropriate.

Example:

User clicks:

"Buy on Daraz"

Instead of exposing the raw affiliate URL everywhere, use something like:

/go/product-name/merchant

or an equivalent secure redirect route.

The system should:

1. Record the click
2. Identify product
3. Identify merchant
4. Identify affiliate offer
5. Record timestamp
6. Record basic analytics data where legally appropriate
7. Redirect the user to the affiliate URL

Do not collect unnecessary personally identifiable information.

Do not violate merchant affiliate program rules.

Do not implement scraping or automation that bypasses anti-bot protections, CAPTCHAs, authentication, robots restrictions, or merchant terms.

Use official APIs, affiliate APIs, product feeds, permitted data sources, manual imports, or other legally permitted mechanisms.

---

# 6. User Accounts

Do NOT build:

* User registration
* Login
* Password reset
* User dashboard
* Shopping cart
* Checkout
* Order history
* Customer addresses
* Customer payment information

Users should be able to use the entire shopping platform without creating an account.

The website is primarily anonymous/public browsing.

---

# 7. Main User Journey

The ideal journey should be:

Homepage
→ Search / Category / Buying Guide / Deal
→ Product listing
→ Product detail
→ Compare merchant offers
→ Click Buy
→ Affiliate redirect
→ External merchant

Example:

User searches:

"RTX 5070"

Website displays:

* Product image
* Product name
* Specification
* Price
* Different merchants
* Price differences
* Availability
* Deal information
* Historical price information
* Editorial summary
* Related products
* Alternative products

Then:

"Buy from Daraz"

or

"Buy from Amazon"

or

"View Deal"

redirects to the appropriate affiliate destination.

---

# 8. Homepage

Create a premium, modern homepage.

The homepage should contain:

## Hero Section

Clear value proposition.

Example concept:

"Find the right product at the right price."

Supporting text:

"Compare products, prices, deals and trusted stores before you buy."

Include prominent search.

Search placeholder:

"Search phones, laptops, GPUs, skincare, AI tools..."

Do not use generic lorem ipsum.

---

## Popular Categories

Display major categories with attractive icons/images:

* Electronics
* PC & Gaming
* Mobile
* Fashion
* Beauty
* Home & Kitchen
* Software & AI
* Travel

---

## Trending Products

Display dynamically selected popular products.

---

## Today's Best Deals

Display products with:

* Current price
* Previous price
* Discount
* Merchant
* Deal badge
* CTA

---

## Price Drops

Display recently reduced products.

Example:

"Price dropped 15%"

---

## Popular Comparisons

Examples:

* iPhone vs Samsung
* RTX 5070 vs RTX 5070 Ti
* AirPods vs Galaxy Buds

---

## Buying Guides

Examples:

* Best laptops for students
* Best phones under ৳30,000
* Best gaming mouse under ৳5,000
* Best AI tools for developers
* Best budget skincare products

---

## Popular Stores

Display supported merchants.

---

## Why Use This Website?

Examples:

* Compare multiple stores
* Find better prices
* Discover useful products
* Research before buying
* Find deals faster

---

# 9. Global Search

Implement a powerful search system.

Search should support:

* Product names
* Brands
* Categories
* Subcategories
* Product models
* Merchant names
* Buying guides
* Articles

Example:

Search:

"iphone 17"

Results should intelligently understand:

* iPhone 17
* iPhone 17 Pro
* iPhone 17 Pro Max
* Accessories
* Related articles

Search should support typo tolerance where possible.

Example:

"iphon 17"

should still produce useful results.

---

# 10. Category Pages

Each category should have a dedicated SEO-friendly page.

Example:

/electronics

/pc-components

/mobile-phones

/fashion

/beauty

/software

/ai-tools

etc.

Each category page should contain:

* SEO title
* SEO description
* Introductory content
* Subcategories
* Popular brands
* Popular products
* Trending products
* Deals
* Filters
* Sorting
* Buying guides

---

# 11. Product Listing Pages

Product listing pages should have:

* Grid/list toggle
* Product cards
* Search within category
* Filters
* Sorting
* Pagination or infinite loading
* Price range
* Brand
* Merchant
* Rating
* Availability
* Discount
* Product attributes

Sorting options:

* Relevance
* Price low to high
* Price high to low
* Highest discount
* Most popular
* Best rated
* Newest
* Biggest price drop

Filters should be category-specific where possible.

For example:

Laptop:

* RAM
* Storage
* CPU
* GPU
* Screen size
* Refresh rate
* Brand

Phone:

* RAM
* Storage
* Camera
* Battery
* Display
* Brand
* 5G

GPU:

* VRAM
* Brand
* Memory type
* Interface
* Performance class

Do NOT show irrelevant generic filters when category-specific filters are available.

---

# 12. Product Cards

Each product card should display:

* Product image
* Brand
* Product name
* Short specification
* Current best price
* Previous price if available
* Discount percentage
* Cheapest merchant
* Number of stores
* Rating if available
* Deal badge
* Price-drop badge if applicable
* "Compare Prices"
* "View Deal"

Do not make every card visually overloaded.

Maintain clean hierarchy.

---

# 13. Product Detail Page

This is one of the most important pages.

Example:

/product/iphone-17-pro

The page should contain:

## Product Header

* Product name
* Brand
* Product images
* Rating
* Short description
* Category
* Key specifications

---

## Best Price

Prominently display:

"Best price: ৳XX,XXX"

"Available from X stores"

CTA:

"View Best Deal"

---

## Compare Stores

Create a comparison table.

Example:

| Store      | Price | Availability | Discount | Action |
| ---------- | ----: | ------------ | -------: | ------ |
| Daraz      |   ৳XX | Available    |      10% | Buy    |
| Amazon     |   ৳XX | Available    |       5% | Buy    |
| AliExpress |   ৳XX | Available    |      15% | Buy    |
| eBay       |   ৳XX | Available    |       8% | Buy    |

Each Buy button should use the correct affiliate URL.

Clearly indicate:

"Prices and availability may change."

---

# 14. Price History

If historical price data exists, show:

* Current price
* Lowest recorded price
* Highest recorded price
* Average price
* Price history chart
* Recent price changes

Example:

"Lowest recorded price: ৳XX,XXX"

"Current price is 8% above the lowest recorded price."

Use a clean chart.

Do not fabricate historical data.

If insufficient data exists, don't show misleading statistics.

---

# 15. Product Price Comparison

The platform should determine:

* Cheapest current offer
* Most expensive offer
* Average offer price
* Price difference
* Best-value offer where appropriate

Example:

"Save up to ৳5,000 by choosing another store."

Only make claims when supported by actual data.

---

# 16. Product Alternatives

Every product page should recommend:

* Similar products
* Cheaper alternatives
* Better-performing alternatives
* Newer alternatives
* Popular alternatives

Example:

If viewing an expensive laptop:

"Want something cheaper?"

→ similar products under the current price.

---

# 17. Product Comparison Tool

Users should be able to compare products without logging in.

Example:

/compare

Allow comparison of 2–4 products.

Comparison should display:

* Price
* Stores
* Specifications
* Ratings
* Features
* Pros/cons if editorial data exists
* Price history
* Value indicators

The comparison should be category-aware.

Comparing four phones should show phone-specific attributes.

Comparing GPUs should show GPU-specific attributes.

---

# 18. Deals Page

Create:

/deals

Show:

* Today's deals
* Biggest discounts
* Price drops
* Limited-time offers
* Popular deals
* Store-specific deals

Deal cards should include:

* Product
* Old price
* New price
* Discount
* Merchant
* Deal expiry if known
* CTA

Do not create fake urgency.

Only show expiry dates when provided by a reliable source.

---

# 19. Price Drops Page

Create:

/price-drops

Show products where prices have recently decreased.

Examples:

"RTX 5070 price dropped 9%"

"Samsung phone price dropped ৳3,000"

Allow sorting by:

* Largest percentage drop
* Largest absolute drop
* Most recent

---

# 20. Buying Guides

Create a strong editorial content system.

Examples:

* Best laptops under ৳80,000
* Best phones under ৳30,000
* Best wireless headphones for students
* Best keyboards for programmers
* Best AI tools for developers
* Best travel gadgets

Each guide should contain:

* Introduction
* Selection criteria
* Recommended products
* Product comparison
* Pros
* Cons
* Who should buy
* Who should avoid
* Alternative options
* Affiliate CTAs
* FAQ
* Last updated date

The content should be useful and genuinely informative.

Do not generate low-quality SEO spam.

---

# 21. Editorial Product Reviews

Create review pages.

Each review may contain:

* Overview
* Key specifications
* Real-world considerations
* Pros
* Cons
* Who it's for
* Who shouldn't buy it
* Price information
* Store comparison
* Alternatives
* Verdict

Include an obvious affiliate disclosure.

Example:

"We may earn a commission when you purchase through links on this page."

---

# 22. Software & AI Tools

Software and AI tools should be treated as a special product category.

Examples:

* AI coding tools
* AI writing tools
* Design tools
* Productivity software
* SaaS
* Developer tools
* Education software

Product information can include:

* Pricing model
* Free plan
* Paid plan
* Main features
* Use cases
* Platform
* Pros
* Cons
* Alternatives
* Affiliate/referral link

The data model must support both physical products and digital products.

---

# 23. Affiliate Offer Architecture

Do not attach a single affiliate link directly to a product.

Create a separate Offer entity.

Example:

Product:

"Samsung Galaxy X"

Offers:

* Daraz offer
* Amazon offer
* eBay offer
* AliExpress offer

Each offer should contain:

* product_id
* merchant_id
* external_product_id
* external_url
* affiliate_url
* current_price
* original_price
* currency
* availability
* discount
* shipping information if available
* last_updated
* source
* status

This allows one product to have many merchant offers.

---

# 24. Product Matching / Deduplication

A major challenge will be that different merchants may use different names for the same product.

Example:

Store A:

"Apple iPhone 17 Pro 256GB Natural Titanium"

Store B:

"iPhone 17 Pro 256GB - Natural Titanium"

Store C:

"Apple iPhone 17 Pro 256GB"

The system should support product matching.

Use:

* Brand
* Model
* SKU
* UPC/EAN/GTIN where available
* Merchant product ID
* Model number
* Structured attributes
* Carefully designed similarity matching

Do NOT blindly merge products based only on name similarity.

Provide admin override capability.

---

# 25. Product Data Import

Build a flexible import system.

Support:

* CSV import
* JSON import
* API-based imports
* Affiliate feed imports
* Manual product creation

Admin should be able to upload/import products.

Provide validation.

Detect:

* Duplicate products
* Missing images
* Invalid prices
* Invalid URLs
* Unknown categories
* Missing brand
* Missing merchant
* Invalid currency

Show import errors clearly.

---

# 26. Automated Product Synchronization

Where official APIs or affiliate feeds are available, support scheduled synchronization.

Example:

Every 6 hours:

* Fetch product updates
* Update prices
* Update availability
* Update discounts
* Update images
* Update product metadata

Use background jobs/queues.

Do not perform expensive synchronization inside normal HTTP requests.

Store:

* Last sync time
* Sync status
* Number updated
* Number failed
* Error logs

---

# 27. Price History System

Every meaningful price update should be recorded.

Price history table should contain:

* Offer ID
* Price
* Currency
* Timestamp

Use this to generate:

* Price charts
* Lowest price
* Highest price
* Average price
* Price-drop detection

Avoid creating unnecessary duplicate history records when the price hasn't changed.

---

# 28. Price Drop Detection

When:

previous price > current price

calculate:

percentage drop

and create a price-drop event.

Example:

Previous: ৳50,000

Current: ৳45,000

Drop: ৳5,000

Percentage:

10%

Use this information for:

* Deals page
* Price-drop page
* Homepage
* Product page

---

# 29. Analytics

Build affiliate analytics.

Track:

* Total outbound clicks
* Clicks by product
* Clicks by merchant
* Clicks by category
* Clicks by page
* Clicks by date
* Top converting destinations if conversion data is available
* CTR

If an affiliate network provides actual conversion/commission data through an API, design the architecture to import it.

Do NOT claim sales/conversions based only on outbound clicks.

---

# 30. Admin Dashboard

Create a complete admin dashboard.

Admin should see:

## Overview

* Products
* Offers
* Merchants
* Categories
* Articles
* Reviews
* Deals
* Clicks
* Revenue/commission data if available
* Failed imports
* Synchronization status

---

# 31. Product Management

Admin should be able to:

* Create product
* Edit product
* Delete/archive product
* Change category
* Change brand
* Upload images
* Edit specifications
* Add SEO metadata
* Add editorial content
* Add pros/cons
* Mark featured
* Mark trending
* Mark deal
* Merge duplicate products
* Manage offers

---

# 32. Merchant Management

Admin should be able to:

* Add merchant
* Edit merchant
* Enable/disable merchant
* Configure affiliate URL
* Configure API/feed
* View product count
* View clicks
* View synchronization status

---

# 33. Category Management

Support hierarchical categories.

Example:

Electronics
→ Computers
→ Laptops
→ Gaming Laptops

PC
→ Components
→ GPUs
→ NVIDIA GPUs

Mobile
→ Smartphones
→ Android
→ Samsung

Admin should be able to create arbitrary category depth.

---

# 34. Brand Management

Create a brand system.

Each brand:

* Name
* Logo
* Description
* Website
* Categories
* Products
* SEO metadata

Brand pages:

/brand/apple

/brand/samsung

/brand/logitech

etc.

---

# 35. CMS / Content Management

Admin should be able to manage:

* Articles
* Buying guides
* Reviews
* FAQs
* Homepage sections
* Banners
* SEO metadata

Article fields:

* Title
* Slug
* Content
* Featured image
* Category
* Author
* Status
* Published date
* Updated date
* SEO title
* SEO description
* Canonical URL
* Open Graph image

---

# 36. SEO

SEO is extremely important because affiliate websites depend heavily on organic traffic.

Implement:

* Clean URLs
* Dynamic meta titles
* Dynamic meta descriptions
* Canonical URLs
* Open Graph tags
* Twitter/X cards
* XML sitemap
* Robots.txt
* Breadcrumb structured data
* Product structured data where appropriate
* Article structured data
* FAQ structured data where appropriate
* Organization structured data
* WebSite structured data
* Internal linking
* SEO-friendly pagination

Avoid duplicate content.

Avoid thin product pages.

Avoid generating thousands of low-quality pages automatically.

---

# 37. SEO URL Structure

Use clean URLs.

Examples:

/products

/product/iphone-17-pro

/category/mobile-phones

/category/mobile-phones/samsung

/brand/apple

/merchant/daraz

/deals

/price-drops

/compare

/guides/best-phones-under-30000

/reviews/iphone-17-pro-review

Do not expose database IDs unnecessarily when slugs can be used.

---

# 38. Structured Data

Use Schema.org where appropriate.

Potential types:

* Product
* Offer
* AggregateOffer
* Article
* Review
* FAQPage
* BreadcrumbList
* Organization
* WebSite

Only output structured data that accurately reflects visible page content.

Do not create fake ratings or reviews.

---

# 39. Performance

The website must be extremely fast.

Optimize for:

* Core Web Vitals
* LCP
* CLS
* INP
* Image optimization
* Lazy loading
* Code splitting
* Caching
* Database indexing
* API response caching

Use CDN-compatible assets.

Do not load unnecessary JavaScript.

---

# 40. Responsive Design

The website must work beautifully on:

* Mobile
* Tablet
* Laptop
* Desktop
* Large screens

Mobile experience is especially important because a large portion of users will likely come from mobile devices.

---

# 41. UI/UX Direction

Design style:

* Modern
* Premium
* Clean
* Trustworthy
* Tech-forward
* Data-driven
* Not overly colorful
* Not a cheap-looking coupon site

Use strong typography.

Use consistent spacing.

Use cards carefully.

Use clear CTAs.

Important actions:

* Search
* Compare
* View Deal
* Buy
* Explore
* Read Review

should be visually obvious.

---

# 42. Navigation

Desktop navigation should include:

* Home
* Categories
* Deals
* Price Drops
* Comparisons
* Guides
* Reviews

Include a prominent global search.

Use category mega-menu if appropriate.

Mobile navigation should be simple and touch-friendly.

---

# 43. Trust & Transparency

Affiliate websites need trust.

Clearly disclose:

"We may earn a commission when you purchase through links on our website."

Also explain:

* Prices can change
* Availability can change
* External stores control checkout and shipping
* We do not sell products directly

Create:

* Affiliate Disclosure
* Privacy Policy
* Terms of Use
* Cookie Policy where applicable
* Contact page
* About page

---

# 44. External Merchant Disclaimer

On product pages:

"Prices and availability are provided for informational purposes and may change. Clicking a retailer link will take you to the retailer's website, where final pricing and availability are determined."

---

# 45. No Fake Data

This is extremely important.

Never fabricate:

* Prices
* Reviews
* Ratings
* Discounts
* Availability
* Product specifications
* Price history
* Affiliate commissions
* Merchant information

If data is unavailable, display an appropriate fallback.

Example:

"Price unavailable"

rather than inventing a price.

---

# 46. AI Features

Build the architecture so AI can be added cleanly.

Potential AI features:

## AI Product Assistant

User can type:

"I need a laptop for programming under ৳80,000."

The AI should query actual product data and recommend products.

It must NOT invent products or prices.

The AI should only recommend products available in the platform's database.

---

## AI Product Comparison

User asks:

"Which is better for programming?"

The system compares actual specifications.

---

## AI Shopping Recommendations

Possible inputs:

* Budget
* Category
* Intended use
* Required features

The AI returns:

* Recommended products
* Reasons
* Tradeoffs
* Alternatives

All recommendations must be grounded in actual database data.

Do not build AI first if it delays the core affiliate platform.

Keep AI modular.

---

# 47. Recommendation Engine

Eventually build a recommendation system using:

* Popularity
* Price
* Rating
* User-independent aggregate clicks
* Product attributes
* Editorial score
* Deal strength
* Recency
* Category

Because users don't have accounts, recommendations should primarily be:

* Contextual
* Product-based
* Category-based
* Popularity-based
* Search-based

Do not require personal profiles.

---

# 48. Merchant Pages

Create merchant pages.

Example:

/merchant/daraz

Page contains:

* Merchant logo
* Description
* Categories
* Featured offers
* Current deals
* Popular products
* General information

Do not imply that the merchant is partnered unless that relationship actually exists.

---

# 49. Product Ranking

Create a transparent internal ranking system.

Possible factors:

* Price competitiveness
* Product popularity
* Editorial quality
* Rating
* Availability
* Deal strength
* Freshness of data

Do NOT rank products purely because they generate the highest commission.

Trust should be prioritized.

If sponsored/paid placement is ever introduced, clearly label it.

---

# 50. Featured Products

Admin can manually mark:

* Featured
* Trending
* Editor's Pick
* Best Value
* Budget Pick
* Premium Pick

These should be editorial labels, not fake review scores.

---

# 51. Database Architecture

Design a normalized relational database.

Expected core entities:

* users/admin_users
* roles
* permissions
* categories
* brands
* products
* product_images
* product_attributes
* attribute_definitions
* merchants
* affiliate_networks
* offers
* price_history
* deals
* articles
* reviews/editorial_reviews
* product_comparisons if persistence is needed
* clicks
* affiliate_conversions
* sync_jobs
* import_batches
* import_errors
* redirects
* seo_metadata

Use appropriate foreign keys and indexes.

Products should not be tightly coupled to merchants.

Offers should connect products to merchants.

---

# 52. Suggested Backend Architecture

Use a clean service-oriented architecture.

Separate:

* Product service
* Merchant service
* Offer service
* Affiliate redirect service
* Price tracking service
* Import service
* Synchronization service
* Analytics service
* SEO service
* Content service
* Recommendation service
* AI service

Avoid putting huge amounts of business logic inside controllers.

Use:

* Form Requests / validation
* Service classes
* Policies
* Jobs
* Events
* Resources/transformers
* Repositories only where useful
* Proper exception handling

---

# 53. Background Jobs

Use queue/background workers for:

* Product imports
* Merchant feed processing
* Price synchronization
* Price history creation
* Deal detection
* Sitemap generation
* Image processing
* Analytics aggregation
* AI processing
* Affiliate conversion imports

---

# 54. API Architecture

If using a frontend/backend separation, expose clean REST APIs.

Examples:

GET /api/products

GET /api/products/{slug}

GET /api/categories

GET /api/categories/{slug}/products

GET /api/brands/{slug}

GET /api/merchants

GET /api/merchants/{slug}

GET /api/deals

GET /api/price-drops

GET /api/search

GET /api/products/{slug}/offers

GET /api/products/{slug}/price-history

GET /api/products/{slug}/alternatives

GET /api/compare

Admin endpoints should be protected separately.

---

# 55. Affiliate Redirect Endpoint

Implement something similar to:

GET /go/{product}/{merchant}

The redirect system should:

1. Validate product
2. Validate merchant
3. Find active offer
4. Record click
5. Resolve affiliate URL
6. Redirect safely

Prevent open redirect vulnerabilities.

Never allow arbitrary user-provided URLs to become redirects.

---

# 56. Security

Implement:

* Authentication only for admins
* Authorization
* CSRF protection where applicable
* Rate limiting
* Input validation
* SQL injection prevention
* XSS prevention
* Secure file uploads
* Secure outbound redirect validation
* API authentication
* Admin audit logs
* Secrets stored in environment variables
* No API keys in frontend
* Proper CORS configuration

---

# 57. Admin Authentication

Admin dashboard should require authentication.

Support roles such as:

* Super Admin
* Content Manager
* Product Manager
* Analyst

Permissions should be granular.

Example:

Product Manager can manage products but cannot change system settings.

---

# 58. Admin Analytics Dashboard

Show:

* Total products
* Active offers
* Active merchants
* Total affiliate clicks
* Clicks today
* Clicks this week
* Clicks this month
* Top products
* Top merchants
* Top categories
* Top landing pages
* Most clicked affiliate offers
* Price drops
* Import status
* Sync health

Charts should be useful and not decorative.

---

# 59. Affiliate Revenue

If affiliate networks provide commission information through APIs/imports, support:

* Commission
* Conversion
* Merchant
* Product
* Date
* Status
* Network

Dashboard:

Revenue today

Revenue this month

Revenue by merchant

Revenue by category

Revenue by product

Do not calculate revenue from clicks unless explicitly labeled as estimated.

---

# 60. Caching

Use caching strategically.

Cache:

* Popular products
* Categories
* Merchant lists
* Product details
* Search results where appropriate
* Homepage sections
* Price history summaries

Invalidate cache when important product/offer data changes.

---

# 61. Database Indexing

Properly index:

* product slug
* product name
* category_id
* brand_id
* merchant_id
* offer product_id
* offer merchant_id
* offer price
* price_history offer_id
* price_history created_at
* clicks product_id
* clicks merchant_id
* clicks created_at

Use full-text search or a dedicated search engine when scale requires it.

---

# 62. Search Scalability

Start with database search if appropriate for MVP.

Architecture should allow later migration to:

* Meilisearch
* Elasticsearch/OpenSearch
* Algolia
* Typesense

Do not overengineer the first version unnecessarily.

---

# 63. Image Management

Product images should support:

* Multiple images
* Thumbnail
* Main image
* Gallery
* Alt text
* Optimized formats
* Lazy loading

Do not hotlink unreliable external images if licensing/terms do not allow it.

Use permitted product images according to affiliate/merchant terms.

---

# 64. Product Data Freshness

Every price/offer should display:

"Updated X minutes/hours ago"

where reliable timestamp data exists.

If data becomes stale:

"Price may be outdated"

should be displayed.

Create configurable freshness thresholds.

---

# 65. Error Handling

If a merchant API/feed fails:

Do not break the website.

Instead:

* Keep last known valid data
* Mark synchronization as failed
* Record error
* Show appropriate freshness status
* Retry using background jobs

---

# 66. Empty States

Create professional empty states for:

* No search results
* No deals
* No price history
* No offers
* Product unavailable
* Merchant unavailable
* Import failure

Never show broken layouts.

---

# 67. Admin Import Interface

Build:

Upload file
→ Validate
→ Preview
→ Show errors/warnings
→ Confirm import
→ Process background job
→ Show results

Example result:

Imported: 4,280

Updated: 3,920

Created: 310

Skipped: 50

Failed: 0

---

# 68. Internationalization

Design database and application architecture to support:

* Bangladesh
* India
* Global

Currency should not be hardcoded.

Support:

* BDT
* INR
* USD
* EUR
* GBP

Prices should retain their original currency.

Do not automatically convert prices and present them as if they were merchant prices.

If currency conversion is added, clearly label converted prices.

---

# 69. Country / Region Architecture

Merchants should support:

* country
* region
* supported currencies
* shipping region if known
* availability region

This allows future expansion.

---

# 70. Legal / Compliance

Build the platform with affiliate-program compliance in mind.

Do not:

* Misrepresent merchants
* Fake reviews
* Fake discounts
* Fake scarcity
* Hide affiliate relationships
* Scrape protected systems
* Circumvent anti-bot systems
* Store unnecessary personal information

Always respect each affiliate network's rules.

---

# 71. Technical Stack

Preferred stack:

Frontend:

* React
* TypeScript
* Vite
* Tailwind CSS
* React Router
* TanStack Query
* Axios or equivalent

Backend:

* Laravel
* REST API
* Laravel Sanctum for admin/API authentication if appropriate

Database:

* PostgreSQL

Infrastructure should be compatible with:

* Redis
* Queue workers
* Scheduled tasks
* Object storage
* CDN
* Email service

If a better technology is genuinely required for a specific component, explain why before introducing it.

Do not unnecessarily change the entire stack.

---

# 72. Development Strategy

Do NOT attempt to build every advanced feature at once.

Build in phases.

## Phase 1 — Foundation

Build:

* Project architecture
* Database
* Admin authentication
* Categories
* Brands
* Products
* Merchants
* Offers
* Basic frontend
* Homepage
* Category pages
* Product pages

---

## Phase 2 — Affiliate Core

Build:

* Affiliate links
* Redirect tracking
* Click analytics
* Merchant management
* Offer management
* Affiliate disclosure
* Outbound tracking

---

## Phase 3 — Comparison

Build:

* Store comparison
* Price comparison
* Product comparison
* Alternatives
* Filtering
* Sorting
* Search

---

## Phase 4 — Price Intelligence

Build:

* Price history
* Price drops
* Deals
* Historical charts
* Freshness indicators

---

## Phase 5 — Content & SEO

Build:

* Articles
* Buying guides
* Reviews
* CMS
* SEO metadata
* Sitemap
* Structured data
* Internal linking

---

## Phase 6 — Automation

Build:

* CSV imports
* API integrations
* Affiliate feeds
* Scheduled synchronization
* Queue workers
* Error monitoring

---

## Phase 7 — AI

Build:

* AI product assistant
* AI comparison
* AI recommendations
* AI-generated product summaries only where appropriate and human-reviewable

AI must always be grounded in actual product data.

---

## Phase 8 — Scaling

Later add:

* Search engine
* Advanced analytics
* Affiliate conversion imports
* International markets
* Multi-currency
* Advanced recommendation engine
* More merchants
* Performance optimization

---

# 73. MVP Priority

If development time is limited, prioritize:

1. Product database
2. Merchant database
3. Affiliate offers
4. Product pages
5. Search
6. Categories
7. Merchant price comparison
8. Affiliate redirect tracking
9. Admin panel
10. SEO foundation

Do NOT prioritize:

* User accounts
* Social features
* Chat
* Complex personalization
* Loyalty systems
* Internal checkout

These are unnecessary for the core business.

---

# 74. Design Requirements

The final UI should feel like a combination of:

* modern product comparison engine
* premium editorial website
* technology publication
* smart shopping platform

Avoid:

* generic Bootstrap-looking UI
* excessive gradients
* excessive glassmorphism
* fake 3D effects
* huge unnecessary animations
* cluttered product cards
* copied Amazon/Daraz layouts

Use subtle animations only where they improve UX.

---

# 75. Mobile-First Requirements

On mobile:

* Search must be immediately accessible
* Product cards should remain readable
* Comparison tables should be horizontally scrollable or intelligently transformed
* Affiliate CTAs should be easy to tap
* Filters should open through a bottom sheet/drawer
* Navigation should remain simple
* Images should be optimized

---

# 76. Accessibility

Implement:

* Semantic HTML
* Keyboard navigation
* Proper labels
* Accessible buttons
* Alt text
* Sufficient contrast
* Focus states
* ARIA only when needed

---

# 77. Testing

Implement tests for critical functionality.

Backend:

* Product creation
* Offer creation
* Merchant management
* Affiliate redirect
* Click tracking
* Price update
* Price-drop calculation
* Product matching
* Authorization

Frontend:

* Search
* Product listing
* Product detail
* Comparison
* Affiliate CTA
* Filters
* Responsive behavior

---

# 78. Logging & Monitoring

Log:

* API errors
* Import failures
* Feed failures
* Synchronization failures
* Affiliate redirect failures
* Admin actions
* Unexpected exceptions

Do not log sensitive credentials or unnecessary personal information.

---

# 79. Environment Configuration

All external credentials must be environment variables.

Examples:

* Database credentials
* Redis credentials
* Affiliate API keys
* AI API keys
* Storage credentials
* Email credentials

Never commit secrets to Git.

Provide a clean `.env.example`.

---

# 80. Code Quality

Write maintainable production-quality code.

Requirements:

* Clear naming
* Reusable components
* Small focused functions
* Strong typing
* Proper validation
* No unnecessary duplication
* No massive components
* No business logic hidden in UI components
* No hardcoded merchant-specific logic where generic architecture is possible

---

# 81. Important Rule About Fake Implementations

Do not create fake APIs and pretend they work.

If a real affiliate API is unavailable during development:

Create a clean interface/service abstraction and use clearly marked development seed data.

Example:

MerchantFeedProvider interface

DarazFeedProvider

AmazonFeedProvider

AliExpressFeedProvider

etc.

When credentials/API access is available, the real provider can be plugged in.

---

# 82. Seed Data

Create realistic seed/demo data for development.

Include:

* Several categories
* Several brands
* 30–50 products
* Multiple offers per product
* Multiple merchants
* Different prices
* Some discounts
* Some price history
* Some deals
* Some articles

Do not use misleading real-world claims in demo data.

Clearly identify demo data where necessary.

---

# 83. Admin UX

Admin should be efficient rather than visually flashy.

Provide:

* Tables
* Search
* Filters
* Bulk actions
* Pagination
* Sorting
* Inline status indicators
* Import/export
* Confirmation dialogs
* Validation errors
* Toast notifications

---

# 84. Bulk Operations

Admin should be able to:

* Bulk activate/deactivate products
* Bulk assign category
* Bulk update merchant
* Bulk mark featured
* Bulk archive
* Bulk import
* Bulk update prices when importing valid data

---

# 85. Homepage Content Should Be Dynamic

Do not hardcode product sections.

Admin should control:

* Featured products
* Trending products
* Deals
* Buying guides
* Categories
* Banners

The homepage should consume API data.

---

# 86. SEO Content Strategy

The system should support SEO landing pages around:

* Category
* Brand
* Product
* Product comparison
* "Best X"
* "Best X under Y"
* Reviews
* Deals
* Price drops

But avoid automatically creating thousands of useless pages.

Every indexed page should provide meaningful value.

---

# 87. Internal Linking

Automatically suggest related links:

Product
→ Category

Product
→ Brand

Product
→ Similar products

Product
→ Buying guides

Buying guide
→ Products

Article
→ Related guides

This should improve navigation and SEO.

---

# 88. Breadcrumbs

Implement breadcrumbs.

Example:

Home
→ Electronics
→ PC Components
→ GPUs
→ NVIDIA RTX 5070

Use structured data where appropriate.

---

# 89. Product Availability

Support statuses:

* Available
* Out of stock
* Pre-order
* Unknown
* Discontinued

Do not show "Available" unless the source supports it.

---

# 90. Merchant Offer Ranking

When displaying multiple offers, calculate:

* Lowest price
* Best availability
* Best overall value

But make the logic transparent.

Possible default:

1. Active valid offer
2. Available
3. Lowest total known price
4. Freshest data

If shipping costs are known, consider them.

Do not claim "cheapest" if shipping/taxes materially change the total cost and are unknown.

---

# 91. Affiliate CTA Design

Use contextual CTAs:

"Buy from Daraz"

"View on Amazon"

"Check AliExpress"

"See Deal"

"Compare Prices"

Avoid misleading CTA text like:

"Buy Now" if clicking only opens an external merchant and the user hasn't actually initiated checkout.

---

# 92. External Link Handling

Affiliate links should:

* Open safely
* Use appropriate rel attributes where necessary
* Preserve tracking parameters
* Never expose sensitive internal data
* Never allow arbitrary redirect destinations

---

# 93. Admin Audit Trail

Record important admin actions:

* Product created
* Product edited
* Offer modified
* Merchant changed
* Article published
* Product archived
* Price manually changed

Include:

* Admin
* Action
* Entity
* Timestamp

---

# 94. Future Expansion

The architecture should allow future:

* Mobile application
* Browser extension
* Telegram/WhatsApp deal alerts
* Email newsletters
* Price alerts
* API access
* Partner websites
* Embedded product widgets
* Public product API
* International expansion

Do not build these now unless necessary.

Design the architecture so they are possible later.

---

# 95. Final Product Vision

The finished platform should feel like:

> "The place I visit before buying something online."

A user should be able to:

1. Search for a product
2. Understand what it is
3. Compare alternatives
4. Compare stores
5. See current prices
6. See whether the price is good
7. Read useful information
8. Find deals
9. Choose the best option
10. Click through to the merchant
11. Purchase externally

The platform should make the user feel that they made a better purchasing decision because they used our website.

---

# 96. Critical Business Principle

Optimize for:

**Trust → Useful information → Product discovery → Click-through → Affiliate revenue**

NOT:

**Maximum ads → Maximum clicks → Poor user experience**

The long-term goal is to build a trusted shopping research brand, not a spammy affiliate site.

---

# 97. Implementation Instructions

Before writing large amounts of code:

1. Analyze the existing project structure if one exists.
2. Identify the current frontend/backend architecture.
3. Identify missing components.
4. Create an implementation plan.
5. Identify database relationships.
6. Identify API boundaries.
7. Identify reusable frontend components.
8. Identify security risks.
9. Identify affiliate-specific concerns.
10. Then implement incrementally.

Do not destroy working functionality.

Do not rewrite the entire project unnecessarily.

Do not create duplicate components/services when existing ones can be extended properly.

---

# 98. Development Output Requirements

For every major phase:

* Explain what is being implemented
* Implement it completely
* Validate it
* Run relevant tests
* Check API responses
* Check database relationships
* Check responsive UI
* Fix errors before moving forward

Do not simply create UI mockups.

The system must have real:

* database persistence
* API integration
* affiliate offer logic
* product management
* merchant management
* search
* comparison
* tracking
* admin functionality

---

# 99. Definition of Done

The project is considered successful when:

A visitor can:

* Open homepage
* Search products
* Browse categories
* Filter products
* Open product page
* See multiple merchant offers
* Compare prices
* View price history when available
* See alternatives
* Read buying guides
* Browse deals
* Click an affiliate CTA
* Be safely redirected to the merchant

And an admin can:

* Login
* Manage products
* Manage categories
* Manage brands
* Manage merchants
* Manage affiliate offers
* Import product data
* Manage deals
* Manage articles
* Manage SEO
* View affiliate clicks
* Monitor synchronization
* Manage homepage content

The system should be production-oriented, scalable, SEO-friendly, mobile-friendly, secure, and maintainable.

---

# 100. Most Important Instruction

Do not interpret this as a request to merely create a visually attractive frontend.

This is a **real affiliate-commerce platform**.

The business logic, data architecture, affiliate offer architecture, product/merchant relationship, price history, SEO, tracking, administration, and scalability are as important as the UI.

Build the foundation correctly so that adding new merchants, products, categories, countries, affiliate networks, and AI capabilities later does not require rewriting the core application.
