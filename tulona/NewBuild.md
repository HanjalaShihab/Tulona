============================================================
MASTER PROMPT
MULTI-MERCHANT AFFILIATE PRODUCT & COMPARISON PLATFORM
============================================================



Your task is to UPDATE and EXTEND this EXISTING APPLICATION into a
scalable multi-merchant affiliate marketing, product discovery,
comparison, and content management platform.

IMPORTANT:
- DO NOT rebuild the project from scratch.
- DO NOT create a separate new project.
- DO NOT unnecessarily replace the current architecture.
- DO NOT delete existing functionality without a strong reason.
- First inspect the existing codebase thoroughly.
- Reuse existing models, migrations, controllers, services,
  components, routes, layouts, authentication, admin functionality,
  and database structures wherever appropriate.
- Only create or modify what is actually required.


============================================================
1. BUSINESS MODEL
============================================================

This website is NOT an e-commerce store.

Our website is an affiliate product discovery and comparison
platform.

Users do NOT need accounts.

Users do NOT:
- add products to our cart
- checkout on our website
- make payments on our website
- create orders on our website

Instead:

USER
  ↓
Discovers product/comparison
  ↓
Views product information
  ↓
Compares merchants
  ↓
Clicks "Buy"
  ↓
Affiliate tracking URL
  ↓
Merchant website
  ↓
Merchant handles checkout/payment
  ↓
Affiliate commission may be earned

The platform must support multiple affiliate merchants.

Possible merchants include:

- Star Tech
- Daraz
- Ryans
- Pickaboo
- Amazon
- AliExpress
- eBay
- and future affiliate merchants.

DO NOT hardcode the entire system around Star Tech.

Star Tech should only be the first merchant implementation.

============================================================
2. PRODUCT CATEGORIES
============================================================

The platform should primarily support:

- Electronics
- Gadgets
- PC Components
- PC Accessories
- Mobile Phones
- Mobile Accessories
- Fashion
- Clothing
- Beauty
- Skincare
- Home
- Kitchen
- Fitness products
- Travel products
- Software
- AI tools
- Other related categories in the future

The architecture must allow new categories without code changes.

============================================================
3. CORE ARCHITECTURE
============================================================

The most important architectural rule is:

PRODUCT != MERCHANT PRODUCT/OFFER

A Product is the canonical product.

A Merchant Product / Offer represents a specific merchant's listing
of that product.

Example:

Canonical Product:
Logitech G102 LIGHTSYNC

Merchant Offers:

Star Tech
  Price: ৳1800
  Product URL: ...
  Affiliate URL: ...

Daraz
  Price: ৳1850
  Product URL: ...
  Affiliate URL: ...

Ryans
  Price: ৳1790
  Product URL: ...
  Affiliate URL: ...

There must NOT be three separate canonical products simply because
three merchants sell the same product.

============================================================
4. FIRST STEP — INSPECT EXISTING APPLICATION
============================================================

Before implementing anything, inspect:

- Laravel version
- PHP version
- database
- migrations
- models
- controllers
- services
- repositories
- jobs
- queues
- scheduler
- events/listeners
- routes
- middleware
- authentication
- authorization
- admin panel
- frontend
- existing product system
- existing merchant system
- existing affiliate functionality
- existing comparison functionality
- existing landing pages
- existing CMS functionality
- existing SEO functionality
- existing tests

Determine:

1. What already exists
2. What can be reused
3. What needs modification
4. What is missing
5. What architecture conflicts exist
6. What database changes are required

Do not blindly create duplicate tables/models.

Before major implementation, provide a concise architecture
assessment and implementation plan.

============================================================
5. MERCHANT SYSTEM
============================================================

Create/reuse a generic Merchant entity.

Possible fields:

- id
- name
- slug
- website_url
- logo
- description
- status
- affiliate_enabled
- product_import_method
- affiliate_link_method
- connector_type
- configuration
- created_at
- updated_at

A merchant must be configurable from Admin.

Example:

Star Tech
Daraz
Ryans
Pickaboo
Amazon
etc.

============================================================
6. MERCHANT CONNECTOR ARCHITECTURE
============================================================

Do NOT put merchant-specific logic everywhere in controllers.

Use a merchant connector/service architecture.

Conceptually:

Merchant
   ↓
Merchant Connector
   ├── Product Importer
   ├── Product Parser
   ├── Product Normalizer
   ├── Category Mapper
   └── Affiliate Link Generator

Use interfaces/contracts where appropriate.

Example conceptual interfaces:

MerchantProductImporterInterface
MerchantProductParserInterface
AffiliateLinkGeneratorInterface

Then implementations may include:

StarTechProductImporter
StarTechAffiliateLinkGenerator

DarazProductImporter
DarazAffiliateLinkGenerator

RyansProductImporter
RyansAffiliateLinkGenerator

etc.

Adding a new merchant should not require rewriting the entire
application.

============================================================
7. CANONICAL PRODUCT
============================================================

Create/reuse a Product entity.

Possible fields:

- id
- name
- slug
- brand_id
- category_id
- subcategory_id
- sku
- model
- mpn
- short_description
- description
- features
- status
- visibility
- featured
- deal_status
- metadata
- seo_title
- seo_description
- created_at
- updated_at

Product publication states:

- draft
- pending_review
- published
- archived

Scraped products should not automatically become publicly published
unless explicitly configured.

============================================================
8. MERCHANT PRODUCT / LISTING
============================================================

Create/reuse a MerchantProduct or MerchantListing entity.

It should contain merchant-specific information such as:

- id
- merchant_id
- product_id
- external_product_id
- external_sku
- external_url
- merchant_product_name
- merchant_brand
- merchant_category
- current_price
- original_price
- discount
- availability
- stock_status
- warranty
- shipping_information
- raw_data
- source_data
- last_scraped_at
- status
- created_at
- updated_at

This represents the merchant's version/listing of the canonical
product.

============================================================
9. PRODUCT IMAGES
============================================================

Support multiple product images.

Possible fields:

- product_id
- image_url/path
- source_merchant_id
- sort_order
- is_primary
- alt_text

Admin can:

- view images
- reorder images
- choose primary image
- remove images
- replace images
- add custom images where appropriate

IMPORTANT:
Do not automatically copy or redistribute merchant-owned images
unless their terms/license permit it.

Support remote source URLs when appropriate.

============================================================
10. DYNAMIC PRODUCT SPECIFICATIONS
============================================================

Product specifications must be dynamic.

Do NOT create fixed columns for every possible specification.

Example:

Logitech G102:

Sensor → HERO
DPI → 25600
Connection → Wired
Buttons → 6
Weight → 85g

Admin must be able to:

- add specification
- edit specification
- remove specification
- reorder specifications

============================================================
11. CATEGORY SYSTEM
============================================================

Use hierarchical categories.

Example:

Electronics
 ├── PC Components
 │    ├── CPU
 │    ├── GPU
 │    ├── RAM
 │    ├── SSD
 │    └── PSU
 │
 ├── PC Accessories
 │    ├── Mouse
 │    ├── Keyboard
 │    ├── Headset
 │    └── Webcam
 │
 ├── Mobile
 │    ├── Smartphones
 │    ├── Chargers
 │    ├── Power Banks
 │    └── Cases
 │
 ├── Fashion
 ├── Clothing
 ├── Beauty
 ├── Skincare
 ├── Home
 ├── Kitchen
 ├── Fitness
 ├── Travel
 ├── Software
 └── AI Tools

Merchant categories must be mapped to our canonical categories.

============================================================
12. BRAND SYSTEM
============================================================

Create/reuse a Brand entity.

Products can belong to brands.

Support:

- brand name
- slug
- logo
- description
- SEO metadata
- status

============================================================
13. PRODUCT IMPORT / SCRAPER SYSTEM
============================================================

Create a reusable product import system.

The Admin should be able to select a merchant and provide a source
URL.

Example for Star Tech:

Admin
→ Imports
→ New Import
→ Merchant: Star Tech
→ Commissioned Product List URL
→ Analyze
→ Preview
→ Import

The system should collect available information such as:

- product name
- product URL
- product ID
- SKU
- brand
- model
- category
- price
- original price
- discount
- availability
- stock
- images
- description
- features
- specifications
- commission eligibility
- commission rate if available
- source information

Do NOT assume all merchants provide the same data.

Use merchant-specific parsers/mappers.

============================================================
14. COMMISSION PRODUCT LIST IMPORT
============================================================

For merchants such as Star Tech, the Admin may provide a
Commission Product List URL.

Example:

Star Tech Commission Product List URL
[........................................]

The system should:

1. Access the source using an allowed/authorized method.
2. Identify commission-eligible products.
3. Extract product URLs and available product data.
4. Create/update merchant product records.
5. Match products against the canonical product catalog.
6. Create new products where appropriate.
7. Mark commission eligibility correctly.
8. Show an import preview.
9. Queue the actual processing.

IMPORTANT:
A product being available on a merchant website does NOT mean it is
automatically commissionable.

Only products explicitly eligible according to the merchant's current
commission source should be marked commission eligible.

============================================================
15. SCRAPER MUST BE QUEUE BASED
============================================================

Do NOT scrape hundreds or thousands of products in one normal
HTTP request.

Use Laravel queues/jobs.

Workflow:

Import Created
   ↓
Import Job
   ↓
Discover Products
   ↓
Queue Individual Product Jobs
   ↓
Parse
   ↓
Normalize
   ↓
Match
   ↓
Store
   ↓
Update Progress

Admin should see:

Total: 532
Processed: 312
Successful: 300
Failed: 12
Remaining: 220

Support:

- retry failed
- cancel
- resume where practical
- inspect errors

Respect merchant terms, robots/access restrictions, rate limits,
authentication requirements and applicable policies.

Do NOT bypass CAPTCHA, anti-bot protection, access controls,
security mechanisms, or rate limits.

============================================================
16. IMPORT PREVIEW
============================================================

Before committing a large import, show:

Total products found
New products
Existing products
Updated products
Potential duplicates
Potential matches
Errors

Example:

Total: 532
New: 84
Existing: 421
Changed: 27
Potential duplicates: 8
Errors: 0

Admin actions:

[Import All]
[Import Selected]
[Cancel]

============================================================
17. RAW DATA VS CURATED DATA
============================================================

This is critical.

Scraped/source data must not destroy Admin-curated content.

Maintain a distinction between:

SOURCE / RAW DATA

and

CURATED / PUBLISHED DATA.

Example:

Scraped title:
"Logitech G102 LIGHTSYNC Gaming Mouse"

Admin-curated title:
"Logitech G102 LIGHTSYNC – Best Budget Gaming Mouse"

A future scraping run must not overwrite the curated title unless
the Admin explicitly chooses to update it.

============================================================
18. DUPLICATE PRODUCT DETECTION
============================================================

Repeated imports must not create duplicate canonical products.

Matching priority:

1. SKU
2. MPN
3. Manufacturer Part Number
4. Model
5. Brand + Model
6. External product ID
7. normalized product URL
8. normalized product name
9. relevant specifications

For uncertain matches:

Show:

Potential Match

and allow Admin:

[Merge]
[Not Same Product]
[Ignore]

Do not automatically merge uncertain products.

============================================================
19. AFFILIATE OFFER SYSTEM
============================================================

Affiliate information must be separate from the normal merchant
product URL.

Each merchant product can have an Affiliate Offer.

Possible fields:

- product_id
- merchant_id
- merchant_product_id
- normal_product_url
- affiliate_url
- tracking_identifier
- commission_rate
- commission_type
- commission_eligible
- status
- generation_method
- generated_at
- last_verified_at
- last_error
- metadata

Affiliate statuses:

- pending
- generating
- generated
- failed
- manual
- invalid
- inactive

============================================================
20. CRITICAL: AFFILIATE LINK GENERATION
============================================================

The system MUST support TWO methods:

A. MANUAL AFFILIATE LINK GENERATION
B. AUTOMATED AFFILIATE GENERATOR WORKFLOW

The automated workflow must NOT simply guess or construct affiliate
URLs from a hardcoded URL pattern.

If a merchant provides an official affiliate link generator page/form,
the system should be designed to operate on that generator workflow,
provided such automation is permitted and technically accessible.

Example:

Merchant:
Star Tech

Affiliate Generator Page:
[official generator URL]

The Admin configures this generator in the merchant's Affiliate
Settings.

============================================================
21. MANUAL AFFILIATE LINK GENERATION
============================================================

Every affiliate offer must support manual link entry.

Workflow:

Product
 ↓
Open Merchant Generator
 ↓
Paste Product URL
 ↓
Merchant generates affiliate URL
 ↓
Copy URL
 ↓
Our Admin
 ↓
Paste Affiliate URL
 ↓
Save

Admin UI:

Product:
Logitech G102

Merchant:
Star Tech

Normal Product URL:
[........................]

Affiliate URL:
[........................]

[Save Affiliate URL]

============================================================
22. AUTOMATED AFFILIATE GENERATOR WORKFLOW
============================================================

When supported and permitted by the merchant:

Admin provides the official affiliate generator page URL.

Example:

Affiliate Generator URL:
[................................]

The system then uses the merchant's authorized generator workflow.

For each product requiring an affiliate link:

Merchant Product
   ↓
Get Normal Product URL
   ↓
Open Authorized Affiliate Generator
   ↓
Submit Product URL through the generator's supported form/workflow
   ↓
Trigger Generate
   ↓
Capture the generated affiliate URL
   ↓
Validate the URL
   ↓
Store Affiliate URL
   ↓
Mark status = generated

IMPORTANT:

This is NOT a URL-formatting feature.

The automation should actually operate on the merchant-provided
affiliate generator workflow when permitted.

Do not invent or assume the merchant's affiliate URL structure.

============================================================
23. BULK AFFILIATE LINK GENERATION
============================================================

Support bulk generation.

Example:

487 products require affiliate links.

Admin:

Affiliate
→ Link Generator
→ Merchant: Star Tech
→ [Generate All Pending]

System:

Total: 487
Processed: 350
Generated: 343
Failed: 7
Pending: 137

Use Laravel queues/jobs.

Do not block a normal HTTP request.

Actions:

[Generate Selected]
[Generate All Pending]
[Retry Failed]
[Pause]
[Cancel]

============================================================
24. AFFILIATE LINK GENERATION FAILURE
============================================================

If automation fails:

DO NOT delete the product.

DO NOT delete the merchant offer.

Keep:

- product
- merchant product
- normal product URL
- commission status

Set:

Affiliate Status = Failed

Store the error.

Admin must be able to:

[Retry]
[Enter Manually]

Example:

Status:
⚠ Failed

Error:
Generator did not return a valid affiliate URL.

[Retry]
[Enter Manually]

============================================================
25. MERCHANT-SPECIFIC AFFILIATE GENERATORS
============================================================

Use:

AffiliateLinkGeneratorInterface

Possible implementations:

StarTechAffiliateLinkGenerator
DarazAffiliateLinkGenerator
RyansAffiliateLinkGenerator
AmazonAffiliateLinkGenerator
etc.

A merchant may support:

- official API
- official feed
- official deep-link generator
- authorized web-form automation
- manual-only

Store the supported method per merchant.

Do not hardcode Star Tech logic into generic controllers.

============================================================
26. AUTOMATION SAFETY
============================================================

Only automate workflows that are allowed and technically accessible.

Do NOT bypass:

- CAPTCHA
- anti-bot systems
- authentication controls
- access controls
- rate limits
- security mechanisms

If automation cannot be safely/permissibly performed, provide the
manual workflow.

============================================================
27. AFFILIATE LINK GENERATOR ADMIN PAGE
============================================================

Create:

Admin
→ Affiliate
→ Link Generator

Filters:

- Merchant
- Category
- Affiliate Status
- Product Status
- Commission Eligibility

Each row should show:

Product
Merchant
Product URL
Affiliate URL
Commission eligibility
Generation status
Last generated
Last error

Actions:

[Generate]
[Retry]
[Enter Manually]
[Open Product]
[Open Merchant Generator]
[Regenerate]

============================================================
28. AFFILIATE GENERATION HISTORY
============================================================

Track every generation attempt.

Store:

- product
- merchant
- timestamp
- generation method
- result
- generated URL
- error
- initiating admin
- execution metadata where appropriate

This is necessary for debugging.

============================================================
29. COMPARISON SYSTEM
============================================================

Create a completely separate Admin module:

Comparisons

The comparison system must reuse existing:

- Products
- Merchant Products
- Affiliate Offers
- Prices
- Specifications
- Availability

Do NOT create duplicate product records specifically for
comparisons.

============================================================
30. CREATE COMPARISON
============================================================

Admin:

Comparisons
→ Create Comparison

Example:

Title:
Best Gaming Mouse Under ৳3000

Select merchants:

☑ Star Tech
☑ Daraz
☑ Ryans
☐ Amazon
☐ AliExpress

For each selected merchant, Admin can provide the relevant
commission/product source URL.

Example:

Star Tech Commission List URL
[................................]

Daraz Source URL
[................................]

Ryans Source URL
[................................]

Then:

[Start Scraping]

============================================================
31. COMPARISON SCRAPING
============================================================

For every selected merchant:

Source URL
   ↓
Merchant-specific scraper
   ↓
Collect products
   ↓
Normalize products
   ↓
Store temporary/import data
   ↓
Match against canonical products
   ↓
Find common products

Example:

Star Tech: 500
Daraz: 1200
Ryans: 350

Common products:
187

============================================================
32. COMPARISON PRODUCT MATCHING
============================================================

Identify products that are genuinely the same product across
merchants.

Use:

- SKU
- MPN
- Model
- Brand
- Product identifiers
- normalized product names
- specifications

Do not consider two products identical merely because their names
are vaguely similar.

Classify results:

- Common products
- Potential matches
- Merchant-specific products
- Unmatched products

============================================================
33. COMPARE COMMON ITEMS
============================================================

Provide an Admin action:

[Compare Common Items]

This should display products available across all selected merchants.

Example:

☑ Logitech G102
☑ Logitech G304
☑ Redragon K617
☑ Kingston NV2 1TB

Admin selects products.

Then:

[Create Comparison]

============================================================
34. COMPARISON EDITOR
============================================================

After automatic comparison generation, Admin must be able to edit
everything before publication.

Support editing:

- title
- slug
- introduction
- description
- products
- merchant order
- product order
- specifications shown
- prices shown
- availability
- warranty
- shipping information
- best price
- best deal
- verdict
- notes
- CTA text
- SEO title
- SEO description

Allow:

- add product
- remove product
- reorder products
- reorder merchants
- hide merchant offer
- manually override selected data
- publish
- unpublish

============================================================
35. COMPARISON TABLE
============================================================

Example:

Logitech G102 LIGHTSYNC

               Star Tech   Daraz    Ryans

Price           ৳1800       ৳1850    ৳1790
Availability    In Stock    In Stock In Stock
Warranty        ...         ...      ...
Discount        ...         ...      ...

[Buy from Star Tech]
[Buy from Daraz]
[Buy from Ryans]

Automatically calculate:

- lowest price
- highest price
- price difference
- percentage difference
- discount
- availability

============================================================
36. BEST PRICE VS BEST DEAL
============================================================

Do NOT assume lowest price automatically equals best deal.

Support:

BEST PRICE

and

BEST OVERALL DEAL

Best overall deal can consider available information such as:

- price
- shipping
- warranty
- availability
- discount
- other relevant merchant information

Example:

Best Price:
Ryans — ৳1790

Best Overall Deal:
Star Tech — ৳1800 + better warranty

Allow Admin override.

============================================================
37. COMPARISON PUBLISHING
============================================================

A comparison can be published to:

- Homepage
- Category page
- Deals page
- Dedicated landing page
- Custom content section

Example:

/best-gaming-mouse-under-3000

Do not duplicate the comparison data for each placement.

One comparison should be reusable.

============================================================
38. LANDING PAGE SYSTEM
============================================================

Support dynamic landing pages.

Example:

/best-gaming-mouse-under-3000

Possible sections:

- Hero
- Introduction
- Comparison table
- Best deal
- Featured products
- Product cards
- Buying guide
- Specifications
- FAQ
- Related comparisons
- Related products
- CTA

Admin controls which sections are visible.

============================================================
39. PRODUCT PUBLISHING
============================================================

Each imported product should become independently manageable.

Admin can edit:

Basic:
- title
- slug
- brand
- category
- subcategory
- tags

Content:
- short description
- full description
- features
- verdict
- notes

Pricing:
- price
- original price
- discount

Specifications:
- dynamic specifications

Images:
- image management

Affiliate:
- merchant
- merchant product URL
- affiliate URL
- commission eligibility
- commission rate
- affiliate status

SEO:
- meta title
- meta description
- canonical URL

Publishing:
- draft
- pending review
- published
- archived

============================================================
40. PRODUCT PAGE
============================================================

Frontend product page should contain:

- image gallery
- product title
- brand
- description
- features
- specifications
- merchant offers
- current prices
- discount
- availability
- best price
- affiliate CTAs
- price history
- related products
- related comparisons

Example:

Logitech G102

Best Price:
৳1790

Star Tech — ৳1800 [Buy]
Daraz — ৳1850 [Buy]
Ryans — ৳1790 [Buy]

Every Buy button must use the appropriate affiliate URL when one
exists.

Do not pretend a normal product URL is an affiliate URL.

============================================================
41. CATEGORY SYSTEM
============================================================

Products must automatically appear in appropriate categories.

Example:

Logitech G102

→ Electronics
→ PC Accessories
→ Gaming Mouse
→ Logitech

The same product must be reusable across:

- homepage
- category
- brand page
- search
- deals
- comparisons
- landing pages

Do not duplicate product records.

============================================================
42. DEAL SYSTEM
============================================================

Track:

- current price
- previous price
- discount
- price drop
- percentage price drop
- deal status

Example:

Previous:
৳2100

Current:
৳1800

Price drop:
14.3%

Support automatic deal detection.

============================================================
43. PRICE HISTORY
============================================================

Store historical merchant prices.

Example:

Aug 20 → ৳2100
Aug 22 → ৳2000
Aug 24 → ৳1900
Aug 27 → ৳1800

This allows:

- price history
- price drop detection
- deal detection
- future charts
- comparison insights

============================================================
44. SCHEDULED DATA REFRESH
============================================================

Where appropriate, use Laravel Scheduler.

Potential scheduled tasks:

- update prices
- update availability
- update commission eligibility
- refresh merchant product data
- check affiliate URLs
- detect price drops
- update deals

Do not scrape continuously.

Make refresh intervals configurable.

============================================================
45. ADMIN DASHBOARD
============================================================

Dashboard should show:

Products:
- total
- published
- drafts
- pending review

Merchants:
- active merchants
- affiliate-enabled merchants

Imports:
- recent imports
- failed imports
- pending imports

Affiliate:
- generated links
- pending links
- failed links
- commission-eligible products

Comparisons:
- total
- published
- drafts

Deals:
- active deals
- recent price drops

============================================================
46. IMPORT HISTORY
============================================================

Track:

- merchant
- source URL
- start time
- end time
- status
- total found
- new
- updated
- removed
- failed
- initiated by
- errors/logs

Admin can inspect old imports.

============================================================
47. COMPARISON SCRAPE HISTORY
============================================================

Track:

- comparison
- merchant
- source URL
- scrape status
- product count
- matched count
- unmatched count
- errors
- timestamp

============================================================
48. BULK PRODUCT MANAGEMENT
============================================================

Support:

- bulk publish
- bulk unpublish
- bulk archive
- bulk category assignment
- bulk tag assignment
- bulk feature
- bulk affiliate processing

Use confirmation for destructive actions.

============================================================
49. SEARCH AND FILTERING
============================================================

Admin product management should support:

- search
- merchant
- category
- brand
- affiliate status
- commission eligibility
- publication status
- price range
- import source
- date imported

Frontend should support appropriate product search/filtering.

============================================================
50. CLICK TRACKING
============================================================

If appropriate and legally/commercially acceptable, track outbound
affiliate clicks.

Possible fields:

- product_id
- merchant_id
- affiliate_offer_id
- comparison_id nullable
- landing_page_id nullable
- timestamp
- page/source
- minimal non-sensitive analytics metadata

Do NOT collect unnecessary personal data.

Admin can see:

Product:
Logitech G102

Star Tech clicks: 150
Daraz clicks: 90
Ryans clicks: 200

============================================================
51. PRODUCT RELATIONSHIPS
============================================================

Support:

- related products
- similar products
- alternatives
- same-brand products
- same-category products

Prefer automatic relationships with optional Admin overrides.

============================================================
52. SEO
============================================================

The platform is content/affiliate driven, so SEO is important.

Support:

- SEO-friendly slugs
- meta title
- meta description
- canonical URLs
- Open Graph metadata
- breadcrumbs
- structured data where valid
- product schema where valid
- FAQ schema where appropriate
- sitemap
- robots configuration

Do not automatically create huge amounts of thin/duplicate content.

============================================================
53. ADMIN UI/UX
============================================================

Admin interface should prioritize productivity.

Use:

- searchable tables
- filters
- pagination
- bulk actions
- clear status badges
- progress indicators
- loading states
- error states
- success notifications
- confirmation dialogs
- responsive layouts
- useful empty states

Important Admin pages:

Dashboard

Products
 ├── All Products
 ├── Draft
 ├── Published
 └── Archived

Categories

Brands

Merchants

Imports
 ├── New Import
 ├── Import History
 └── Failed Imports

Affiliate
 ├── Offers
 ├── Link Generator
 └── Generation History

Comparisons
 ├── All Comparisons
 ├── Create Comparison
 ├── Scraping Jobs
 ├── Matching
 └── Published

Deals

Price History

Landing Pages

Settings

============================================================
54. DATABASE ARCHITECTURE
============================================================

Adapt the following conceptual entities to the existing database.

Potential tables:

merchants

products

brands

categories

product_images

product_specifications

merchant_products

affiliate_offers

affiliate_link_generations

price_history

imports

import_items

comparisons

comparison_products

comparison_offers

landing_pages

deals

affiliate_clicks

Do not blindly create duplicate tables if equivalent structures already
exist.

Use:

- foreign keys
- indexes
- unique constraints
- appropriate cascading behavior
- nullable relationships where necessary

Potential unique constraints:

merchant + external_product_id

merchant_product + product relationship

etc.

============================================================
55. DATA RELATIONSHIP
============================================================

The core relationship should conceptually be:

                         PRODUCT
                            │
            ┌───────────────┼───────────────┐
            ▼               ▼               ▼
       Star Tech          Daraz           Ryans
       Merchant           Merchant        Merchant
       Product            Product         Product
            │               │               │
            ▼               ▼               ▼
      Affiliate Offer  Affiliate Offer  Affiliate Offer
            │               │               │
            └───────────────┼───────────────┘
                            ▼
                       COMPARISON
                            │
               ┌────────────┼────────────┐
               ▼            ▼            ▼
            Homepage     Category    Landing Page

PRODUCT MUST REMAIN THE SINGLE SOURCE OF TRUTH.

============================================================
56. IMPORTANT DATA FLOW
============================================================

The intended overall workflow is:

COMMISSIONS / PRODUCT LIST
        ↓
MERCHANT SCRAPER
        ↓
RAW PRODUCT DATA
        ↓
NORMALIZATION
        ↓
PRODUCT MATCHING
        ↓
CANONICAL PRODUCT
        ↓
MERCHANT PRODUCT
        ↓
AFFILIATE OFFER
        ↓
AFFILIATE LINK
        ↓
PUBLISHED PRODUCT
        ↓
COMPARISON
        ↓
LANDING PAGE / CATEGORY / HOMEPAGE
        ↓
USER
        ↓
AFFILIATE CLICK
        ↓
MERCHANT
        ↓
PURCHASE

============================================================
57. STAR TECH EXAMPLE
============================================================

A complete Star Tech workflow should be possible.

STEP 1:

Admin:
Imports
→ New Import

Merchant:
Star Tech

Source:
Star Tech Commissioned Product List URL

STEP 2:

Click:

[Analyze]

System identifies available commissioned products.

STEP 3:

Show:

Total: 532
New: 84
Existing: 421
Changed: 27

STEP 4:

Admin clicks:

[Import]

STEP 5:

Laravel queues process products.

STEP 6:

Products are:

Draft / Pending Review

STEP 7:

Admin can edit each product independently.

STEP 8:

Admin goes to:

Affiliate
→ Link Generator

STEP 9:

Admin configures/selects:

Star Tech Affiliate Generator URL

STEP 10:

System identifies products without affiliate URLs.

STEP 11:

Admin can:

[Generate Automatically]

OR

[Enter Manually]

STEP 12:

If automation is permitted:

Product URL
→ Star Tech official affiliate generator workflow
→ Submit
→ Generate
→ Capture generated affiliate URL
→ Save

STEP 13:

If automation fails:

[Retry]
[Enter Manually]

STEP 14:

Admin publishes the product.

STEP 15:

Product automatically becomes available in appropriate:

- category
- brand
- search
- homepage if featured
- deals if applicable
- comparisons
- landing pages

============================================================
58. COMPARISON EXAMPLE
============================================================

Admin:

Comparisons
→ Create Comparison

Title:

Best Gaming Mouse Under ৳3000

Select:

Star Tech
Daraz
Ryans

Enter source URLs.

Click:

[Start Scraping]

System:

Star Tech → scrape
Daraz → scrape
Ryans → scrape

Then:

Normalize
→ Match
→ Identify common products

Example:

Common:
187

Admin clicks:

[Compare Common Items]

Selects:

Logitech G102
Logitech G304
Redragon K617

Then:

[Create Comparison]

System creates comparison.

Admin edits.

Admin publishes to:

Homepage
Gaming Mouse category
Dedicated landing page

Example:

/best-gaming-mouse-under-3000

============================================================
59. PRODUCT AND COMPARISON INDEPENDENCE
============================================================

The Product CMS and Comparison system must remain separate modules.

However, they share the same underlying:

- canonical products
- merchant products
- prices
- affiliate offers
- specifications

Do not create duplicate products for comparison.

A comparison is essentially a curated presentation/relationship
between existing products and merchant offers.

============================================================
60. CONTENT CURATION
============================================================

The scraper should NOT be considered the final content authority.

The Admin must be able to turn scraped data into high-quality
curated content.

Example:

RAW:
"Logitech G102 LIGHTSYNC Gaming Mouse"

CURATED:
"Logitech G102 LIGHTSYNC – Best Budget Gaming Mouse"

Future scraping must preserve curated content.

============================================================
61. PERFORMANCE
============================================================

The application must be designed for potentially large catalogs.

Consider:

- database indexes
- eager loading
- pagination
- caching
- queue workers
- batch processing
- chunking
- optimized queries
- avoiding N+1 queries
- background jobs
- import locking
- concurrency control

Do not load thousands of products into one Admin page.

============================================================
62. SECURITY
============================================================

Maintain:

- authentication
- authorization
- Admin middleware
- CSRF protection
- validation
- rate limiting
- secure configuration
- secure credential storage

Merchant credentials must never be exposed to frontend users.

Do not store sensitive credentials as plain text where avoidable.

============================================================
63. TESTING
============================================================

Create/update tests for:

- merchant creation
- product creation
- merchant product creation
- product matching
- duplicate detection
- category mapping
- imports
- import failures
- affiliate offer creation
- manual affiliate URL entry
- automated affiliate generation workflow where testable
- affiliate generation failure
- retry
- comparison creation
- common-product detection
- price updates
- deal detection
- publishing
- permissions
- affiliate click tracking

Most importantly, test the end-to-end flow:

Commission List URL
→ Import
→ Product
→ Merchant Product
→ Affiliate Offer
→ Affiliate Link
→ Comparison
→ Publish
→ User clicks Buy
→ Affiliate URL
→ Merchant

============================================================
64. FUTURE MERCHANT EXTENSIBILITY
============================================================

Adding a new merchant should ideally require:

1. Create merchant configuration
2. Implement importer
3. Implement parser/mapper
4. Implement affiliate integration
5. Configure category mapping
6. Configure supported automation capabilities

It should NOT require rewriting:

- product system
- comparison system
- landing pages
- frontend architecture
- database architecture

============================================================
65. DO NOT OVERENGINEER
============================================================

This is a Laravel-first application.

Prefer:

- Laravel
- Eloquent
- Jobs
- Queues
- Scheduler
- Cache
- Events where useful
- Services
- Contracts/interfaces
- Database
- Storage

Do not introduce microservices unless there is a genuine technical
requirement.

Keep the architecture understandable and maintainable.

============================================================
66. IMPLEMENTATION STRATEGY
============================================================

Implement incrementally.

PHASE 1:
Inspect existing codebase.

PHASE 2:
Document current architecture and identify reusable components.

PHASE 3:
Design required database/model changes.

PHASE 4:
Implement/refactor Merchant architecture.

PHASE 5:
Implement/refactor Product + Merchant Product architecture.

PHASE 6:
Implement Product Import/Scraper system.

PHASE 7:
Implement raw-vs-curated data protection.

PHASE 8:
Implement Affiliate Offer system.

PHASE 9:
Implement Affiliate Link Generator system.

PHASE 10:
Implement manual affiliate link workflow.

PHASE 11:
Implement permitted automated affiliate generator workflow.

PHASE 12:
Implement bulk affiliate processing.

PHASE 13:
Implement Comparison Engine.

PHASE 14:
Implement product matching/common-item detection.

PHASE 15:
Implement Comparison Editor.

PHASE 16:
Implement publishing and Landing Pages.

PHASE 17:
Implement Price History and Deals.

PHASE 18:
Implement click tracking/analytics.

PHASE 19:
Implement scheduler and refresh mechanisms.

PHASE 20:
Testing, optimization, security review, and final integration.

============================================================
67. IMPORTANT: DO NOT BREAK EXISTING FEATURES
============================================================

Before modifying existing functionality:

Understand it.

If existing code already performs part of this specification:

REUSE IT.

If refactoring is necessary:

- preserve existing behavior
- migrate existing data safely
- avoid breaking routes/API contracts where possible
- update dependent frontend code
- add tests

Do not delete working functionality simply because you prefer
a different implementation.

============================================================
68. REQUIRED FINAL BEHAVIOR
============================================================

The final system should allow me, as Admin, to:

1. Add affiliate merchants.

2. Configure each merchant's product import method.

3. Enter a merchant's commission/product-list URL.

4. Automatically scrape/import eligible products where permitted.

5. Create a normalized product catalog.

6. Match products across merchants.

7. Edit every product individually.

8. Manage product images/specifications/content.

9. Manage merchant-specific offers.

10. Manage commission eligibility.

11. Generate affiliate links manually.

12. Provide an official merchant affiliate generator URL.

13. Where permitted, automatically operate that generator workflow
    to generate affiliate links for individual products.

14. Bulk-generate affiliate links for many products.

15. Retry failed generations.

16. Manually enter affiliate links when automation is unavailable.

17. Create multi-merchant comparisons.

18. Scrape multiple merchants for comparison.

19. Detect products common across merchants.

20. Compare only genuinely matching/common products.

21. Edit comparison results.

22. Determine best price.

23. Determine best overall deal.

24. Publish comparisons.

25. Publish products.

26. Reuse products across categories, homepage, deals, comparisons,
    and landing pages.

27. Track price history.

28. Detect price drops/deals.

29. Track affiliate outbound clicks where appropriate.

30. Add new merchants without rewriting the system.

============================================================
69. MOST IMPORTANT ARCHITECTURAL PRINCIPLE
============================================================

The entire application should follow:

SCRAPE ONCE
      ↓
NORMALIZE
      ↓
STORE
      ↓
CURATE
      ↓
REUSE EVERYWHERE

Do NOT create separate copies of the same product for:

- homepage
- category
- comparison
- landing page
- deals
- brand page

There must be one canonical Product.

Everything else references it.

============================================================
70. FINAL INSTRUCTION TO THE CODING AI
============================================================

DO NOT start by immediately writing a large amount of code.

FIRST:

1. Inspect the existing application.
2. Understand the current architecture.
3. Identify existing relevant files/models/tables/routes.
4. Identify what can be reused.
5. Identify what must be changed.
6. Identify what must be added.
7. Propose the database/model relationship changes.
8. Propose implementation phases.

Then implement the system incrementally.

After each major phase:

- verify migrations
- verify existing functionality
- verify routes
- verify models/relationships
- verify frontend integration
- run relevant tests
- fix regressions

Do not create unnecessary files.

Do not duplicate functionality.

Do not rebuild the application from scratch.

The final goal is to evolve the EXISTING Laravel application into a
production-ready, scalable, multi-merchant affiliate product
discovery, product CMS, affiliate link management, comparison,
deal-tracking, and landing-page publishing platform.
============================================================