# Public website plan

Customer-facing site for Bag Factory ERP. Visually separate from the ERP dashboard. Built on the current app (Laravel, Inertia Vue, Tailwind). No second frontend.

## What already exists

- Finished bags live on `bag_products` (name, size, thickness, color, internal `selling_price`).
- Dana / granules live on `materials` (internal `default_unit_cost`, reorder level, stock balances).
- `product_categories` is factory taxonomy, not a marketing catalog.
- Customers are financial accounts (opening and current balance). There is no lead or enquiry table.
- Auth is session-based. Modules and permissions come from `config/erp.php` plus Spatie. Screens use `ErpLayout`.

## Catalog: visibility flags, not a second product master

Public pages must not show internal prices or stock. A flag is smaller than copying product masters.

Additive columns on `bag_products` and `materials` (default `is_public = false`, so current rows stay private):

- `is_public`, `public_slug`, `public_category`, `public_summary`, `public_description`, `public_image_path`, `public_sort`

Allowed public categories:

| Slug | Shown as | Source |
| --- | --- | --- |
| `shopping-bags` | Shopping Bags | bag products |
| `printed-bags` | Printed Bags | bag products |
| `custom-bags` | Custom Packaging | bag products |
| `dana-granules` | Dana Products | materials |

The public serializer selects name, slug, category, summary, description, size, and image only. It never selects `selling_price`, `default_unit_cost`, reorder level, balances, BOM, or customer data.

`product_categories` stays the internal taxonomy. Marketing blurbs live in a new `website_categories` table because nothing in ERP stores that copy.

## New tables (nothing in ERP fits)

| Table | Why |
| --- | --- |
| `website_enquiries` | Quote, custom order, and contact messages. Not customers: creating a customer would open a receivable. |
| `website_settings` | One row. Company name defaults to “Bag Factory”. Phone, email, WhatsApp, address, and map stay empty until an admin sets them. |
| `website_categories` | Editable sample catalog descriptions for the four public categories. |
| `website_testimonials` | Empty until an admin adds a row. The home section and nav item render only when a visible row exists. |
| `website_gallery_items` | Admin-uploaded gallery images. Empty state until then. |

## Enquiries reach sales

Public forms post to the ERP and insert `website_enquiries` (`type`: `quote`, `custom_order`, `contact`, `status`: `new`).

Staff with `website.view` open **Website → Enquiries** inside the existing ERP shell. They can set status, internal notes, and an optional link to an existing customer. No invoice, receipt, or balance is created.

Duplicate protection:

- Hidden idempotency token issued when the form is rendered, unique on the enquiry row.
- Same normalized email + phone + enquiry type + subject within 15 minutes updates nothing and confirms the original enquiry.

Validation strips tags. Artwork is jpg, jpeg, png, webp, or pdf, max 5 MB, stored on the private `local` disk (`storage/app/private/website/...`), never under `public/`. PHP, SVG, and other executables are rejected. Artwork download is authenticated. Public image routes stream only image extensions for published listings and visible gallery items, with `nosniff`.

## Admin (existing auth)

New ERP module `website` (`website.view`, `website.manage`). Owner/admin receives it through `*`. Factory manager can manage the site. Accountant can review enquiries (sales follow-up) but not publish the catalog.

Pages inside `ErpLayout`: hub, listings, categories, enquiries, gallery, testimonials, settings. A sales hub link appears only when the user may view the website module.

Guests are sent to login. Users without `website.view` receive 403.

## Public routes (no auth)

`/` home, `/about`, `/products`, `/products/{category}`, `/products/{category}/{slug}`, `/custom-orders`, `/request-a-quote`, `/manufacturing`, `/gallery`, `/contact`, `/testimonials`.

`/` no longer redirects to login. Staff sign-in stays at `/login`, linked from the public footer.

Inertia props on these routes omit the ERP user, permissions, and navigation.

## Content

Seeded category blurbs and the default about / manufacturing text are sample catalog copy an admin can replace. The public site says so while `show_sample_catalog_notice` is on.

Not invented: certifications, testimonials, production capacity, phone numbers, email addresses, street addresses. Empty contact fields are omitted. Testimonials with no rows are omitted. Gallery with no images shows an empty configurable slot. Illustrations are inline SVG. Admins can upload their own images. No stock-photo hotlinks.

## Visual

New `PublicLayout` (not `ErpLayout`). Indigo / blue / teal / white. Top bar (phone and email only if set, tagline, social links only if set). Nav with Request a Quote. Hero “Quality Bags. Built for Your Business.”, SVG bag and film presentation, feature strip, four product cards, why-choose-us, custom-order panel, footer.

## Checks

Feature tests: guest 200s, enquiry validation, bad upload rejected, duplicate suppressed, enquiry visible to an authorized user, guest cannot open admin website screens, public payloads omit prices and stock. `php artisan test` and `npm run build`. Desktop and mobile screenshots of the home page. Additive `php artisan migrate` only — never `migrate:fresh`, wipe, or truncate.
