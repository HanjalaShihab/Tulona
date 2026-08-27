MASTER FRONTEND REDESIGN PROMPT
================================

We already have the backend, database, APIs, authentication,
product system, merchant system, affiliate system, scraper/import
system, comparison system, landing pages, etc. implemented.

DO NOT modify backend logic, database schema, API contracts, routes,
authentication logic, scraping logic, affiliate generation logic,
or business logic unless absolutely necessary for frontend
compatibility.

YOUR TASK:
Completely redesign and improve the FRONTEND/UI/UX of the existing
application.

The goal is to make the application look and feel like a premium,
modern product discovery, deal, affiliate, and comparison platform.

The Admin UI should feel like a professional SaaS/data management
dashboard.

The public website should feel like a trustworthy technology and
shopping discovery platform.

============================================================
1. OVERALL DESIGN DIRECTION
============================================================

Design language:

- Premium
- Modern
- Clean
- Fast
- Trustworthy
- Minimal but information-rich
- Professional
- Conversion-focused
- Excellent typography
- Strong visual hierarchy
- Excellent spacing
- Responsive
- Mobile-first where appropriate

Avoid:

- Generic Bootstrap-looking layouts
- Excessive gradients
- Excessive rounded cards
- Huge unnecessary whitespace
- Too many colors
- Cluttered dashboards
- Excessive animations
- Fake statistics
- Overly futuristic "AI dashboard" aesthetics
- Generic template-like UI

The website should look like a real commercial product.

Think of the visual quality of modern product discovery/comparison
platforms rather than a generic affiliate blog.

============================================================
2. FRONTEND TECHNOLOGY
============================================================

Use the existing frontend stack.

Do NOT replace the current frontend framework unless absolutely
necessary.

Reuse:

- existing routing
- existing API service layer
- existing authentication
- existing state management
- existing components where useful
- existing design system where useful

Refactor components when necessary.

Do not create duplicate components unnecessarily.

============================================================
3. DESIGN SYSTEM
============================================================

Create a consistent design system.

Define reusable:

- buttons
- inputs
- selects
- dropdowns
- badges
- cards
- tables
- tabs
- modals
- drawers
- alerts
- tooltips
- pagination
- breadcrumbs
- skeleton loaders
- empty states
- error states
- confirmation dialogs

Typography should have:

- Display heading
- Page heading
- Section heading
- Body
- Small
- Caption
- Price
- Metadata

Maintain consistent:

- spacing
- border radius
- shadows
- borders
- typography
- icon sizing
- interaction states

============================================================
4. PUBLIC WEBSITE STRUCTURE
============================================================

Public navigation:

Logo

Categories
Deals
Comparisons
Brands
Search

Optional:

Trending
Guides

Right side:

Search
Menu

On mobile use a clean mobile navigation.

============================================================
5. HOMEPAGE
============================================================

The homepage should NOT look like a traditional e-commerce store.

It should look like a product discovery and comparison platform.

Suggested structure:

------------------------------------------------
HEADER
------------------------------------------------

Logo
Categories
Deals
Comparisons
Brands

Search bar:

"Search products, brands, or categories..."

------------------------------------------------
HERO
------------------------------------------------

Headline:

"Find the best products at the best price."

Subheading:

"Compare products across trusted stores and discover better deals."

Large search field.

Example:

[ Search for a phone, GPU, laptop, mouse... ]

Optional category shortcuts:

PC Components
Phones
Gadgets
Accessories
Fashion
Beauty
Home
AI Tools

------------------------------------------------
TRENDING / POPULAR
------------------------------------------------

Show popular products in a horizontal/ responsive grid.

Each card:

Image
Brand
Product name
Best price
Price comparison indicator
Merchant count

Example:

Logitech G102
From ৳1,790
3 stores

[Compare Prices]

------------------------------------------------
TODAY'S DEALS
------------------------------------------------

Show products with meaningful price drops.

Example:

RTX 4060

৳39,999
৳44,000

↓ 9% price drop

[View Deal]

------------------------------------------------
POPULAR COMPARISONS
------------------------------------------------

Examples:

Best Gaming Mouse Under ৳3,000

Best Budget SSDs

Best Mechanical Keyboards

Best Smartphones Under ৳30,000

Cards should visually communicate that these are comparisons.

------------------------------------------------
CATEGORIES
------------------------------------------------

Use visual category cards.

Electronics
PC Components
Mobile
Accessories
Fashion
Beauty
Home & Kitchen
Fitness
Travel
Software
AI Tools

------------------------------------------------
WHY USE US
------------------------------------------------

Keep this section short.

Examples:

Compare multiple stores
Track better prices
Find genuine deals
One place for product research

------------------------------------------------
EDITORIAL / BUYING GUIDES
------------------------------------------------

If supported by the backend:

Buying guides
Product guides
Comparison articles

------------------------------------------------
FOOTER
------------------------------------------------

Categories
Popular comparisons
About
Contact
Affiliate disclosure
Privacy
Terms

============================================================
6. SEARCH EXPERIENCE
============================================================

Search is one of the most important features.

Create a modern search interface.

When user clicks search:

Large search field.

Suggestions:

Products
Brands
Categories
Comparisons

Example:

User types:

"g102"

Results:

Logitech G102
Logitech G102 LIGHTSYNC
Gaming Mouse category
Related comparisons

Use debounce.

Show loading state.

Show "No results" state.

============================================================
7. PRODUCT CARD
============================================================

Product cards should be visually excellent.

Structure:

[IMAGE]

Brand

Product name

Short key specification

Best price:

৳1,790

From 3 stores

↓ 12% price drop

[Compare]
[View Product]

Do NOT overload cards with specifications.

On hover:

subtle image/card interaction.

============================================================
8. PRODUCT PAGE
============================================================

This is one of the most important pages.

Layout:

Breadcrumb

Product title

Brand

Rating if available

------------------------------------------------
LEFT
------------------------------------------------

Large product image

Thumbnail gallery

------------------------------------------------
RIGHT
------------------------------------------------

Product title

Short summary

Best price

Price difference

Price drop

[Buy Now]

[Compare Prices]

------------------------------------------------
MERCHANT OFFERS
------------------------------------------------

Show:

Merchant
Price
Availability
Warranty
Discount

Example:

Star Tech
৳1,800
In Stock

[Buy]

Daraz
৳1,850
In Stock

[Buy]

Ryans
৳1,790
In Stock

[Buy]

The Buy button must use the affiliate URL supplied by backend.

Never visually imply that the purchase happens on our website.

Clearly communicate:

"You'll be redirected to the merchant to complete your purchase."

============================================================
9. PRICE COMPARISON
============================================================

Create a strong comparison table.

Example:

--------------------------------------------------------
Store       Price       Availability      Warranty
--------------------------------------------------------
Ryans       ৳1,790      In Stock           1 Year
Star Tech   ৳1,800      In Stock           1 Year
Daraz       ৳1,850      In Stock           ...
--------------------------------------------------------

Highlight:

Best Price

Best Overall Deal

Do not make the UI overly colorful.

Use subtle visual emphasis.

============================================================
10. PRICE HISTORY
============================================================

If price history exists:

Show a clean chart.

Example:

Price History

৳2,200
│
│       ╲
│        ╲
│         ╲____
│              ╲
│               ╲
└────────────────────
 Aug 1      Aug 27

Also show:

Lowest recorded price
Current price
Previous price

============================================================
11. PRODUCT SPECIFICATIONS
============================================================

Use grouped specification sections.

Example:

Specifications

General
Brand       Logitech
Model       G102

Technical
Sensor      Optical
DPI         8,000

Connectivity
Connection  Wired

Avoid huge tables on mobile.

Use accordion sections on mobile if necessary.

============================================================
12. COMPARISON PAGE
============================================================

Comparison page should be one of the strongest public experiences.

Example:

Best Gaming Mouse Under ৳3,000

Introduction

------------------------------------------------
BEST OVERALL
------------------------------------------------

Product card

Logitech G102

Best for:
Budget gaming

Best price:
৳1,790

[View Deal]

------------------------------------------------
COMPARISON TABLE
------------------------------------------------

Product

Price
Stores
Rating if available
Key specifications
Best deal

------------------------------------------------
DETAILED PRODUCTS
------------------------------------------------

Each product gets a section.

------------------------------------------------
BUYING GUIDE
------------------------------------------------

Explain what matters when choosing.

------------------------------------------------
FAQ
------------------------------------------------

Common questions.

============================================================
13. COMPARISON TABLE MOBILE UX
============================================================

Do NOT simply overflow a giant desktop table on mobile.

On mobile:

Use horizontally scrollable table OR

Convert each comparison row into cards.

Maintain readability.

============================================================
14. CATEGORY PAGE
============================================================

Example:

PC Accessories

Header:

PC Accessories

Description

Filters:

Brand
Price
Availability
Merchant
Discount
Price drop

Sort:

Best Match
Lowest Price
Highest Discount
Newest
Popular

Product grid.

Pagination or infinite loading depending on existing architecture.

============================================================
15. DEALS PAGE
============================================================

Create a dedicated deals experience.

Header:

Today's Best Deals

Filters:

Category
Merchant
Price range
Discount
Price drop

Deal card:

Product

Previous:
৳5,000

Now:
৳4,200

Save:
৳800

↓ 16%

[View Deal]

Do not call a product a "deal" unless backend data indicates a
meaningful deal/price change.

============================================================
16. BRAND PAGE
============================================================

Example:

Logitech

Logo

Short description

Popular products

Latest products

Deals

Comparisons

Product grid.

============================================================
17. LANDING PAGE SYSTEM
============================================================

Dynamic landing pages created by Admin should look polished even
though content is dynamic.

Support sections such as:

Hero
Introduction
Featured products
Comparison table
Deals
Buying guide
FAQ
CTA
Related comparisons

Admin controls content.

Frontend should render sections consistently.

============================================================
18. AFFILIATE DISCLOSURE
============================================================

Because this is an affiliate platform, provide a clear but
non-intrusive affiliate disclosure.

Example:

"Some links on this website are affiliate links. We may earn a
commission if you purchase through our links, at no extra cost to you."

Do not make this look like a warning banner.

============================================================
19. ADMIN UI — DESIGN DIRECTION
============================================================

The Admin UI should look like a professional SaaS dashboard.

Layout:

------------------------------------------------
SIDEBAR             MAIN CONTENT
------------------------------------------------
Dashboard
Products
Categories
Brands
Merchants
Imports
Affiliate
Comparisons
Deals
Price History
Landing Pages
Analytics
Settings

Sidebar should support:

- collapse
- mobile drawer
- active navigation state

Header:

Search
Notifications if supported
Admin profile/menu

============================================================
20. ADMIN DASHBOARD
============================================================

Dashboard should provide operational overview.

Top cards:

Products
Published
Pending Review
Affiliate Links
Comparisons
Active Deals

Example:

Products
12,482

Published
9,230

Pending Review
184

Affiliate Links
8,912

Do NOT hardcode values.

Use real API data.

Below:

------------------------------------------------
IMPORT ACTIVITY
------------------------------------------------

Recent imports

Merchant
Products
Status
Date

------------------------------------------------
AFFILIATE STATUS
------------------------------------------------

Generated
Pending
Failed

Use simple visual statistics.

------------------------------------------------
RECENT PRICE DROPS
------------------------------------------------

Product
Merchant
Old Price
New Price
Drop

============================================================
21. ADMIN PRODUCT MANAGEMENT
============================================================

Page:

Products

Top:

[Search products...]

Filters:

Merchant
Category
Brand
Status
Affiliate status
Commission eligibility

Actions:

[Import]
[Bulk Actions]

Table:

□

Image
Product
Brand
Category
Best Price
Merchants
Affiliate
Status
Updated

Actions:

Edit
View
More

Do not make tables excessively wide.

============================================================
22. ADMIN PRODUCT EDITOR
============================================================

This should be one of the most useful Admin screens.

Layout:

Product Editor

Tabs:

Overview
Content
Images
Specifications
Merchant Offers
Affiliate
SEO
Publishing

------------------------------------------------
OVERVIEW
------------------------------------------------

Product name
Slug
Brand
Category
Tags

------------------------------------------------
CONTENT
------------------------------------------------

Short description
Full description
Features
Verdict

Use a proper rich text editor if already available.

------------------------------------------------
IMAGES
------------------------------------------------

Gallery

Drag to reorder.

Set primary image.

------------------------------------------------
SPECIFICATIONS
------------------------------------------------

Dynamic specification editor.

Example:

Display
Resolution    1920x1080
Refresh Rate  144Hz

[+ Add Specification]

------------------------------------------------
MERCHANT OFFERS
------------------------------------------------

Star Tech
Price
Stock
URL

Daraz
Price
Stock
URL

Ryans
Price
Stock
URL

------------------------------------------------
AFFILIATE
------------------------------------------------

Affiliate status

Affiliate URL

Generation method

Commission eligibility

[Generate Automatically]

[Enter Manually]

[Retry]

------------------------------------------------
SEO
------------------------------------------------

SEO title
Meta description
Slug
Canonical URL

------------------------------------------------
PUBLISHING
------------------------------------------------

Draft
Pending Review
Published
Archived

[Save Draft]

[Publish]

============================================================
23. ADMIN IMPORT UI
============================================================

Create a professional import workflow.

Step indicator:

1. Source
2. Analyze
3. Preview
4. Import
5. Results

Step 1:

Merchant

Source type

Source URL / file

[Analyze]

Step 2:

Show discovered information.

Step 3:

Preview:

Found
New
Existing
Potential duplicates
Errors

Step 4:

[Import Selected]
[Import All]

Step 5:

Progress.

Use a progress interface.

============================================================
24. ADMIN IMPORT DETAILS
============================================================

After import:

Import #1024

Merchant:
Star Tech

Status:
Completed

Statistics:

Found: 532
Imported: 532
New: 84
Updated: 421
Skipped: 19
Failed: 8

Show errors in expandable rows.

Actions:

[Retry Failed]

============================================================
25. ADMIN AFFILIATE LINK GENERATOR UI
============================================================

This page is extremely important.

Page:

Affiliate
→ Link Generator

Header:

Affiliate Link Generator

Merchant selector:

Star Tech

Generator configuration:

Affiliate Generator URL

Products:

Search/filter.

Table:

Product
Merchant
Product URL
Affiliate URL
Status
Last Attempt

Actions:

[Generate]
[Manual]
[Retry]

Bulk actions:

[Generate Selected]
[Generate All Pending]

When processing:

Show a live progress panel.

Example:

Generating affiliate links

██████████████░░░░

343 / 487

Generated: 343
Failed: 7
Pending: 137

[Pause]
[Cancel]

Do NOT freeze the page.

============================================================
26. ADMIN AFFILIATE GENERATION HISTORY
============================================================

Show:

Product
Merchant
Generation method
Status
Timestamp
Error

Allow:

Retry
View
Manual override

============================================================
27. ADMIN COMPARISON BUILDER
============================================================

This should feel like a professional content builder.

Page:

Comparisons
→ Create

Step 1:

Title
Slug
Description

Step 2:

Select merchants.

Step 3:

Source URLs.

Step 4:

Run comparison/import.

Step 5:

Show:

Star Tech
500 products

Daraz
1200 products

Ryans
350 products

Common:
187

Step 6:

"Common Products"

Show matched products.

Each row:

Product
Matched merchants
Confidence/status

Checkbox.

[Compare Selected]

============================================================
28. COMPARISON EDITOR
============================================================

Editor layout:

Comparison title

Introduction

Selected products

Drag-to-reorder.

Each product:

Product image
Name
Merchant offers

Choose:

Best Price
Best Overall Deal

Edit:

Description
Verdict
Specifications
CTA

Then:

Preview

[Save Draft]
[Publish]

============================================================
29. COMPARISON PREVIEW
============================================================

Admin should be able to preview the exact public page.

Use:

[Preview]

Open a preview mode that resembles the real frontend.

============================================================
30. ADMIN LANDING PAGE BUILDER
============================================================

If backend supports dynamic sections:

Use a clean section manager.

Sections:

Hero
Product Grid
Comparison
Deals
Text
FAQ
Buying Guide
CTA

Admin can:

Add section
Remove section
Reorder section
Edit section

Prefer drag-and-drop if the existing frontend architecture supports
it.

============================================================
31. RESPONSIVE ADMIN UI
============================================================

Desktop:

Sidebar + content.

Tablet:

Collapsible sidebar.

Mobile:

Sidebar becomes drawer.

Tables:

Use responsive transformation rather than forcing impossible
horizontal layouts.

Forms:

Single-column on mobile.

============================================================
32. LOADING STATES
============================================================

Every API-driven page must have proper loading states.

Use skeleton loaders instead of blank screens.

Examples:

Product skeleton
Table skeleton
Dashboard skeleton
Comparison skeleton

============================================================
33. EMPTY STATES
============================================================

Never show a blank page.

Example:

No products found.

"No products match your current filters."

[Clear Filters]

Import empty state:

"No imports yet."

[Create Import]

============================================================
34. ERROR STATES
============================================================

Errors must be understandable.

Example:

"Unable to load products."

[Try Again]

Affiliate generation:

"Affiliate link generation failed."

Show backend error when safe.

Provide:

[Retry]
[Enter Manually]

============================================================
35. TOASTS / NOTIFICATIONS
============================================================

Use consistent notifications.

Success:

"Product updated successfully."

Error:

"Unable to save product."

Warning:

"Affiliate URL is missing."

============================================================
36. MICROINTERACTIONS
============================================================

Use subtle animations only where useful.

Examples:

- button hover
- card hover
- dropdown
- modal
- drawer
- skeleton shimmer
- progress animation
- tab transition

Do not animate everything.

============================================================
37. ACCESSIBILITY
============================================================

Implement:

- keyboard navigation
- visible focus states
- semantic HTML
- accessible labels
- alt text
- sufficient contrast
- accessible modals
- accessible dropdowns

============================================================
38. SEO-FRIENDLY FRONTEND
============================================================

Maintain proper:

- page titles
- meta descriptions
- canonical URLs
- semantic headings
- breadcrumbs
- structured content

Do not break existing SEO implementation.

============================================================
39. MOBILE EXPERIENCE
============================================================

Mobile is extremely important.

Public mobile UI should provide:

- sticky header
- fast search
- easy Buy CTA
- easy Compare CTA
- readable product cards
- horizontal comparison where necessary
- collapsible specifications
- optimized image sizes

Avoid desktop UI simply squeezed into mobile.

============================================================
40. PERFORMANCE
============================================================

Frontend must remain fast.

Use:

- lazy loading images
- pagination
- code splitting where appropriate
- debounced search
- skeleton states
- optimized rendering
- avoid unnecessary API calls
- avoid unnecessary re-renders

Do not load the entire product catalog into the browser.

============================================================
41. FRONTEND ROUTES
============================================================

Adapt to existing routing.

Conceptually:

Public:

/
 /search
 /products/:slug
 /categories/:slug
 /brands/:slug
 /deals
 /comparisons
 /comparisons/:slug
 /pages/:slug

Admin:

 /admin
 /admin/products
 /admin/products/:id
 /admin/categories
 /admin/brands
 /admin/merchants
 /admin/imports
 /admin/imports/:id
 /admin/affiliate
 /admin/affiliate/generator
 /admin/affiliate/history
 /admin/comparisons
 /admin/comparisons/create
 /admin/comparisons/:id
 /admin/deals
 /admin/price-history
 /admin/landing-pages
 /admin/analytics
 /admin/settings

Use the existing routes if they differ.

============================================================
42. IMPORTANT: DO NOT CHANGE BACKEND
============================================================

This task is primarily FRONTEND.

Do NOT:

- redesign database
- create new backend architecture
- rewrite Laravel controllers
- change API response formats unnecessarily
- modify scraper behavior
- modify affiliate generation behavior
- modify merchant logic

If an API limitation prevents a UI feature:

FIRST determine whether the feature can be implemented with the
existing API.

Only request backend changes if genuinely required.

============================================================
43. DO NOT MOCK DATA
============================================================

Do not use fake products, fake prices, fake merchants, fake
affiliate statistics, or fake dashboard numbers in the final UI.

Use existing API data.

Temporary mock data may only be used during UI development and must
be removed before completion.

============================================================
44. FINAL QUALITY REQUIREMENT
============================================================

The final application should visually feel like TWO products sharing
one ecosystem:

PUBLIC SIDE:

Premium product discovery + price comparison platform.

ADMIN SIDE:

Professional affiliate/product operations dashboard.

The public UI should prioritize:

Discovery
Trust
Comparison
Price visibility
Deals
Conversion

The Admin UI should prioritize:

Speed
Control
Data management
Automation
Bulk operations
Product curation
Affiliate management
Comparison creation

============================================================
45. IMPLEMENTATION PROCESS
============================================================

FIRST inspect the existing frontend.

Identify:

- current framework
- routes
- pages
- components
- design system
- API services
- state management
- existing Admin UI
- existing public UI

Then:

1. Create/update global design system.
2. Redesign Admin shell.
3. Redesign public shell.
4. Redesign homepage.
5. Redesign product cards.
6. Redesign product page.
7. Redesign category pages.
8. Redesign deals.
9. Redesign comparison pages.
10. Redesign search.
11. Redesign Admin dashboard.
12. Redesign Product management.
13. Redesign Product editor.
14. Redesign Import workflow.
15. Redesign Affiliate Link Generator.
16. Redesign Affiliate history.
17. Redesign Comparison Builder.
18. Redesign Comparison Editor.
19. Redesign Landing Page Builder.
20. Ensure responsive behavior.
21. Add loading/empty/error states.
22. Test every API-driven page.
23. Fix visual inconsistencies.
24. Test mobile/tablet/desktop.
25. Remove mock data.
26. Final polish.

============================================================
46. MOST IMPORTANT RULE
============================================================

DO NOT merely "make the existing UI prettier."

Actually rethink the UX.

The Admin must be able to efficiently perform this workflow:

Merchant
→ Import Commission List
→ Review Products
→ Edit Products
→ Generate Affiliate Links
→ Fix Failed Links
→ Create Comparison
→ Find Common Products
→ Edit Comparison
→ Publish

The public user must be able to efficiently perform:

Search
→ Discover Product
→ Compare Merchants
→ Find Best Price
→ Click Buy
→ Go to Merchant

The interface should make both workflows obvious without requiring
the user to understand the underlying database or technical system.

============================================================
END OF FRONTEND REDESIGN PROMPT
============================================================