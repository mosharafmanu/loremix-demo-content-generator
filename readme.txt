=== QuickDemo Content Generator ===
Contributors: mosharafmanu
Tags: demo content, dummy content, test data, woocommerce, content generator
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate demo posts, products, comments, users, and WooCommerce orders — then safely delete only what was generated.

== Description ==

QuickDemo Content Generator is a developer and agency tool for populating a WordPress site with realistic demo content in seconds. It covers every major content type in one place and cleans up after itself without ever touching your real content.

**What it generates:**

* **Posts, pages, and custom post types** — any public post type registered on your site
* **WooCommerce products** — simple products with price/SKU/stock, plus variable products with attributes and child variations
* **Comments** — threaded replies up to two levels deep, with approve or hold status
* **WordPress users** — lower-privilege roles, with randomly generated names and email addresses
* **WooCommerce product reviews** — 1 to 5 star ratings with matching review text
* **WooCommerce orders** — with full billing and shipping addresses and real product line items

**Content quality:**

* Professional built-in fallback content when AI is disabled: article-style posts, page-aware copy, product descriptions, comments, and reviews
* Structured HTML content: paragraphs, headings (h2, h3), lists, blockquotes, tables, and inline links
* Unique gradient featured images generated on the fly with PHP GD — no external service required
* Post excerpts with a configurable word limit
* Taxonomy term assignment for any detected taxonomy, with an option to auto-generate sample terms
* Posts and products spread across a custom date range for realistic archive pages
* Optional AI-generated titles, body copy, featured images, and product images from a client topic using WordPress AI Connectors
* Standalone Media Library images and WordPress nav menus (Extras tab)
* WooCommerce Products, Reviews, and Orders grouped into independent accordion panels
* Product Type is selected separately from product taxonomy terms; choosing Variable product shows the generated Color/Size variation setup, and the selected type is summarized near the Terms and Generate controls
* WooCommerce system visibility terms are hidden from manual term selection to keep product taxonomy choices focused on categories, tags, and attributes
* Admin compatibility guard prevents known frontend-only WooCommerce attribute label filters from breaking variable product editing in wp-admin
* Variable product stock status is synced through WooCommerce so generated parents and child variations are immediately in stock
* Built-in product image generation creates tracked parent featured images, product gallery images, and generated variation images
* Topic-based AI product images can create the parent image, gallery images, and variable-product variation thumbnails, with built-in placeholder fallback when enabled
* Comments tab shows a pre-generation empty state when no demo posts exist, with a shortcut back to the Posts tab

**What makes it safe:**

Every item created by this plugin is stamped with a private meta flag and a batch identifier. When you delete, only those flagged items are removed — your real site content is never touched. You can delete everything at once, or remove individual batches one at a time.

Release-safety hardening includes nonce and capability checks, QuickDemo-specific cleanup markers, capped preset storage, validated AI image files, and no generated users with full site-management permissions.

**Batch management:**

Every generation run is recorded as a batch with its content type, item count, and creation time. The admin page shows the full batch history. Delete one batch without affecting anything else.

**WP-CLI support:**

Power users and CI/CD pipelines can manage demo content from the command line:

    wp quickdemo generate --count=20 --post_type=post --status=draft
    wp quickdemo generate --count=10 --featured-image --date-from=2024-01-01 --date-to=2024-12-31
    wp quickdemo generate --post_type=product --product-type=variable --count=3
    wp quickdemo generate --count=5 --ai-topic="family dental clinic in Austin" --ai-image
    wp quickdemo generate-comments --per-post=5
    wp quickdemo generate-users --count=10 --role=author
    wp quickdemo generate-reviews --per-product=4
    wp quickdemo generate-orders --count=10 --status=processing
    wp quickdemo list
    wp quickdemo delete --batch=batch_20240101_120000_abc123
    wp quickdemo delete --all

**Who it is for:**

* Developers building or testing a new theme
* Agencies preparing a client demo site
* WooCommerce store owners who need realistic shop data for testing
* Anyone who needs realistic-looking content fast and wants a clean way to remove it afterwards

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install directly from the WordPress plugin screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **QuickDemo** from the WordPress admin menu to start generating content. The submenu links jump directly to Posts & Pages, Comments, Users, or WooCommerce.

== Frequently Asked Questions ==

= Will this plugin delete my real posts, products, or users? =

No. The plugin only deletes content it created itself. Every generated item is stamped internally with a private meta flag, and the delete function filters strictly by that marker.

= What post types are supported? =

Any public post type registered on your site — including built-in types like Post and Page, WooCommerce products, and any custom post types added by your theme or plugins.

= Does WooCommerce need to be installed? =

WooCommerce is required only for the Products, Reviews, and Orders sections of the plugin. The Posts, Comments, and Users tabs work on any WordPress site without WooCommerce. The WooCommerce tab displays an "Inactive" notice if WooCommerce is not installed.

= Does it require any external service or API? =

No external service is required for the default generator. If you enable AI Content, the plugin sends the client topic and generation instructions through the WordPress AI Client using credentials configured under **Settings → Connectors**.

= Does GD need to be enabled on my server? =

The GD extension is required only if you enable the featured image option. It is available on the vast majority of shared and managed hosts. All other features work normally without it.

= Can I assign categories or tags to generated posts? =

Yes. The plugin detects all taxonomies registered for the selected post type and lets you pick existing terms before generating. You can also check "Auto-generate terms" to have the plugin create and assign sample categories, tags, or product categories automatically.

= Can I spread generated posts across a date range? =

Yes. Enable the Date Range option and set a From and To date. Each item receives a random date within that range — useful for filling archive pages or pagination.

= Can I delete a single batch without removing everything? =

Yes. The Generated Batches table on the admin page shows every generation run. Click Delete next to any batch to remove just those items.

= Does it support WP-CLI? =

Yes. The plugin registers a `wp quickdemo` command with subcommands for generating posts, comments, users, WooCommerce reviews, WooCommerce orders, listing batches, and deleting generated content. Run `wp help quickdemo` for full usage.

= Can it generate content for a specific client topic? =

Yes. Configure an AI provider under **Settings → Connectors**, then enable **AI Content** on the Posts or WooCommerce product generator. Enter the client topic, audience, and tone before generating. If the connector supports image generation, QuickDemo can also create topic-based featured images and product images. AI image generation can be slower than text generation, so generating one or two AI images per run is recommended.

= Is the generated content translatable? =

All plugin interface strings are translation-ready and use the `quickdemo-content-generator` text domain.

== Screenshots ==

1. The Posts tab — generate demo posts and pages with full control over status, author, content, taxonomy terms, date range, and featured images.
2. The WooCommerce tab — independent accordion panels for products, reviews, and orders.
3. The Comments tab — add threaded demo comments to existing demo posts.
4. The Users tab — generate demo WordPress users with lower-privilege roles.
5. The Generated Batches table showing batch history with per-batch delete buttons.
6. The Delete All section that appears once demo content has been created.

== Changelog ==

= 1.0.0 =
* Generate posts, pages, and any registered custom post type.
* Generate WooCommerce products with automatic price, SKU, stock status, and complete simple or variable product data including Color and Size attributes and four child variations.
* Generate threaded comments on existing demo posts (approve or hold status).
* Generate WordPress users with lower-privilege roles and randomly generated names and emails.
* Generate WooCommerce product reviews with 1–5 star ratings and matching review text.
* Generate WooCommerce orders with full billing and shipping addresses and real product line items.
* Auto-generate taxonomy terms (categories, tags, product categories, product tags) with one checkbox.
* PHP GD featured image generation — unique gradient per item, safe for square thumbnail cropping.
* Variable product image generation — parent featured image, three product gallery images, and one image per child variation.
* Standalone Media Library image generation — save placeholder images directly to the Media Library with no parent post (Extras tab).
* WordPress nav menu generation — realistic labels and optional child items (Extras tab).
* Post excerpts with configurable word limit.
* Date range option — spread items across any custom date range.
* Optional WordPress AI Connector-powered content and image generation from a client topic (requires WordPress 7.0+ with a configured AI connector).
* Generation presets — save, load, and delete named form-state snapshots per tab.
* AJAX form submission with animated progress bar; falls back to native POST if JavaScript is unavailable.
* Tabbed admin UI: Posts, Comments, Users, WooCommerce, Extras.
* Independent WooCommerce accordion panels for Products, Reviews, and Orders.
* Batch tracking — every generation run is recorded with content type, count, and timestamp.
* Per-batch deletion — remove a single batch without affecting other content.
* Delete All — removes all demo content across every content type in one action.
* WP-CLI support for posts, comments, users, WooCommerce reviews, WooCommerce orders, batch deletion, and batch listing.
* Stats bar showing live counts per content type (Posts, Products, Comments, Users, Orders).

== Upgrade Notice ==

= 1.0.0 =
Initial release — no upgrade steps required.
