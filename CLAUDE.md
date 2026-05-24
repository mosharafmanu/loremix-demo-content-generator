# QuickDemo Content Generator — Agent Reference

Plugin slug: `quickdemo-content-generator`  
Text domain: `quickdemo-content-generator`  
Main file: `quickdemo-content-generator.php`  
Admin page: **QuickDemo** top-level admin menu (`admin.php?page=quickdemo-content-generator`) with submenu deep links for Posts & Pages, Comments, Users, WooCommerce, and Extras.
Capability required: `manage_options`

---

## Plugin Name History

The plugin was originally developed as **"Demo Content Generator"** with the slug `demo-content-generator` and folder name `wp-demo-content-generator`.

It was submitted to the WordPress.org plugin repository for review. The WordPress.org review team rejected the name because it was too generic — purely descriptive names with no distinctive element are not accepted. The team requested a rename to something unique.

The plugin was renamed to **"QuickDemo Content Generator"** with slug `quickdemo-content-generator`. All internal prefixes (`wpdcg_`), class names (`WPDCG_`), and constants (`WPDCG_*`) were kept the same throughout the rename — only the user-facing name, slug, text domain, and folder changed.

**If WordPress.org submission ever comes up again:** the current slug `quickdemo-content-generator` is what should be submitted. The text domain is `quickdemo-content-generator`. The review team's original concern was the generic name — "QuickDemo" addresses that.

---

## ✅ What Is Done

- Post / page / CPT generation with full field control (status, author, paragraphs, excerpt, date range, featured image, taxonomy terms)
- WooCommerce product generation with full WC meta injection (simple products, plus complete variable products with local attributes and child variations)
- Comment generation (threaded, approve/hold, attach to all or latest batch)
- User generation (lower-privilege roles only, random names/emails, collision-safe logins)
- WooCommerce product review generation (1–5 stars with matching text, rating cache refresh)
- WooCommerce order generation (full billing/shipping address, real line items from demo products, HPOS-compatible)
- Auto-generate taxonomy terms (opt-in checkbox; preset names for category, post_tag, product_cat, product_tag; idempotent via `term_exists` error handling)
- Optional WordPress AI Client-powered post/product titles, content, featured images, and product images from a client topic, using credentials from WordPress 7.0 Settings → Connectors
- PHP GD featured image generation (1200×630, 8 colour themes, ghost numbers, safe-zone aware for square thumbnail cropping)
- Standalone Media Library image generation (GD placeholder images or AI-generated images, saved to Media Library without parent post, Extras tab)
- WordPress nav menu generation (demo menus with realistic labels, optional child items, Extras tab)
- Generation presets (save/load/delete named form state per tab, stored in `wpdcg_presets` wp_option)
- AJAX form submission with animated progress bar (falls back to native POST on failure)
- Batch tracking system (all content types, type-prefix routing for deletion)
- Top-level QuickDemo admin menu with submenu deep links and tabbed UI (Posts | Comments | Users | WooCommerce | Extras)
- WooCommerce tab uses independent accordion panels for Products, Reviews, and Orders
- Per-batch and delete-all deletion across all content types
- WP-CLI commands (`wp quickdemo generate`, `generate-comments`, `generate-users`, `generate-reviews`, `generate-orders`, `delete`, `list`)
- Stats bar showing separate counts per content type
- "QuickDemo" action link on Plugins page

---

## ❌ What Still Needs To Be Done

The implementation checklist items below are complete. The only remaining item here is the external WordPress.org re-submission process.

### 1. ~~Bump version~~ — Version set to 1.0.0 ✅
**Decision:** Starting at 1.0.0 for the first WordPress.org public release. Internal dev version history is irrelevant to the public. Both `quickdemo-content-generator.php` (header + constant) and `readme.txt` (`Stable tag`) are set to `1.0.0`.

### 2. ~~Update plugin header description~~ ✅
Updated to: `Generate demo posts, products, comments, users, and WooCommerce orders — then safely delete only what was generated.`

### 3. ~~Rewrite readme.txt for WordPress.org~~ ✅
Fully rewritten with correct features, tabbed UI description, WooCommerce sections, updated FAQ, updated WP-CLI examples (`wp quickdemo`), and a clean single `= 1.0.0 =` changelog entry.

### 4. ~~Fix uninstall.php~~ ✅
Now deletes all nine options: `wpdcg_version`, `wpdcg_generated_ids`, `wpdcg_batches`, `wpdcg_comment_ids`, `wpdcg_user_ids`, `wpdcg_order_ids`, `wpdcg_settings`, `wpdcg_menu_ids`, `wpdcg_presets`. Package docblock corrected to `QuickDemo_Content_Generator`.

Note: `wpdcg_settings` is not yet written anywhere in the codebase — it is a pre-emptive delete to clean up a future plugin settings panel if one is added. Safe to leave as-is.

### 5. ~~Extend WP-CLI to cover all content types~~ ✅
**File:** `includes/class-wpdcg-cli.php`  
The CLI now covers all generator types:
- `wp quickdemo generate` — wraps `WPDCG_Generator::generate()`
- `wp quickdemo generate-comments` — wraps `WPDCG_Comment_Generator::generate()`
- `wp quickdemo generate-users` — wraps `WPDCG_User_Generator::generate()`
- `wp quickdemo generate-reviews` — wraps `WPDCG_Woo_Generator::generate_reviews()`
- `wp quickdemo generate-orders` — wraps `WPDCG_Woo_Generator::generate_orders()`

### 6. ~~Re-submit to WordPress.org~~ — Submitted, awaiting review ✅

**Current status (as of 2026-05-24):** Plugin submitted to WordPress.org via the existing `demo-content-generator` review ticket (the only path available — WP.org blocks new submissions while one is pending). Updated zip `quickdemo-content-generator.zip` uploaded. Slug change from `demo-content-generator` → `quickdemo-content-generator` requested by email reply to `plugins@wordpress.org`.

**What was done:**
1. Security audit completed and all issues fixed (see Agent Notes below)
2. All Plugin Check errors and warnings resolved
3. `.gitignore` and `.distignore` created for clean distribution
4. Plugin URI updated to `https://github.com/mosharafmanu/wp-quickdemo-content-generator`
5. Code pushed to new GitHub repo: `https://github.com/mosharafmanu/wp-quickdemo-content-generator`
6. Clean zip built at `/tmp/quickdemo-content-generator.zip` (excludes `.git`, `.claude/`, `CLAUDE.md`, `AI_CONTENT_GENERATION.md`, `.distignore`, `.gitignore`, report `.md` files)
7. Zip uploaded to WordPress.org via "Upload updated plugin for review" button
8. Slug change requested via email reply

**Remaining — after WordPress.org approval:**
- Upload screenshots to SVN `assets/` folder (not required for initial submission)
- Once slug is updated to `quickdemo-content-generator`, the TextDomainMismatch errors in Plugin Check will resolve automatically — no code change needed

**Note on TextDomainMismatch errors:** Plugin Check shows `Expected 'demo-content-generator' but got 'quickdemo-content-generator'` because WP.org's system still has the old slug assigned. The text domain in the plugin is correct. These errors disappear once WP.org updates the slug.

---

## What This Plugin Does

Generates safe, fully-tracked demo content for WordPress sites and removes only what it created — real site content is never touched. Covers every major content type:

| Tab | What it generates |
|---|---|
| Posts | Posts, pages, and any registered CPT (excluding `product`) |
| Comments | Threaded comments attached to demo posts |
| Users | WordPress users with lower-privilege roles |
| WooCommerce | Products, product reviews, and orders |
| Extras | Standalone Media Library images and WordPress nav menus |

Every item is stamped with a meta flag and recorded in `wp_options` so it can be deleted individually (by batch) or all at once.

---

## File Structure

```
quickdemo-content-generator.php   Main plugin file — constants, require_once, hooks
uninstall.php                     Runs on plugin deletion

includes/
  class-wpdcg-tracker.php         Static tracker — wp_options ID lists and batch metadata
  class-wpdcg-generator.php       Post/page/CPT/product generator (also GD image helpers)
  class-wpdcg-ai-generator.php    Optional WordPress AI Client integration for topic-based content/images
  class-wpdcg-comment-generator.php  Comment generator
  class-wpdcg-user-generator.php  User generator
  class-wpdcg-woo-generator.php   WooCommerce reviews + orders generator
  class-wpdcg-cleaner.php         Deletion logic for all content types
  class-wpdcg-core.php            Singleton bootstrap — loads admin + CLI
  class-wpdcg-cli.php             WP-CLI commands (wp quickdemo generate/delete/list)
  class-wpdcg-presets.php         Static preset manager — named form-state snapshots per tab
  class-wpdcg-media-generator.php Standalone GD image generation to Media Library (no parent post)
  class-wpdcg-menu-generator.php  WordPress nav menu generator with realistic labels

admin/
  class-wpdcg-admin.php           Admin hooks, form handlers, AJAX handlers, page render
  views/admin-page.php            Admin UI template (tabbed, no inline logic)
  css/wpdcg-admin.css             Admin styles
  js/wpdcg-admin.js               Admin JS (AJAX submit, progress bar, presets, toggles, accordion)

assets/
  fonts/Inter-Bold.ttf            Bundled TrueType font for GD featured image rendering (SIL OFL licence).
                                  Used as first priority in find_ttf_font(); system fonts are the fallback.

AI_CONTENT_GENERATION.md          Agent handoff for WordPress AI Client / Connectors integration
```

---

## Constants (defined in main plugin file)

| Constant | Value |
|---|---|
| `WPDCG_VERSION` | `1.0.0` |
| `WPDCG_FILE` | Absolute path to main plugin file |
| `WPDCG_PATH` | Plugin directory path (trailing slash) |
| `WPDCG_URL` | Plugin directory URL (trailing slash) |
| `WPDCG_BASENAME` | `quickdemo-content-generator/quickdemo-content-generator.php` |

---

## Meta Keys & Option Keys

### Post meta (WPDCG_Generator)
| Constant | Value | Purpose |
|---|---|---|
| `WPDCG_Generator::META_KEY` | `_demo_content_generator_generated` | Flags every generated post |
| `WPDCG_Generator::BATCH_META_KEY` | `_demo_content_generator_batch_id` | Stores batch ID on each post |
| `WPDCG_Generator::TERM_META_KEY` | `_wpdcg_auto_term` | Flags auto-created taxonomy terms |

### Comment meta (WPDCG_Comment_Generator)
| Constant | Value | Purpose |
|---|---|---|
| `WPDCG_Comment_Generator::COMMENT_META_KEY` | `_wpdcg_generated` | Flags generated comments |
| `WPDCG_Comment_Generator::BATCH_META_KEY` | `_wpdcg_batch_id` | Stores batch ID on each comment |

### User meta (WPDCG_User_Generator)
| Constant | Value | Purpose |
|---|---|---|
| `WPDCG_User_Generator::USER_META_KEY` | `_wpdcg_generated` | Flags generated users |
| `WPDCG_User_Generator::BATCH_META_KEY` | `_wpdcg_batch_id` | Stores batch ID on each user |

### WooCommerce meta (WPDCG_Woo_Generator)
| Constant | Value | Purpose |
|---|---|---|
| `WPDCG_Woo_Generator::REVIEW_META_KEY` | `_wpdcg_generated` | Flags generated reviews |
| `WPDCG_Woo_Generator::ORDER_META_KEY` | `_wpdcg_generated` | Flags generated orders |
| `WPDCG_Woo_Generator::BATCH_META_KEY` | `_wpdcg_batch_id` | Stores batch ID on reviews/orders |

### wp_options keys (WPDCG_Tracker)
| Constant | Option key | Stores |
|---|---|---|
| `WPDCG_Tracker::OPTION_KEY` | `wpdcg_generated_ids` | All post IDs (int[]) |
| `WPDCG_Tracker::BATCH_OPTION_KEY` | `wpdcg_batches` | Batch metadata (array of arrays) |
| `WPDCG_Tracker::COMMENT_OPTION_KEY` | `wpdcg_comment_ids` | Comment IDs (int[]) |
| `WPDCG_Tracker::USER_OPTION_KEY` | `wpdcg_user_ids` | User IDs (int[]) |
| `WPDCG_Tracker::ORDER_OPTION_KEY` | `wpdcg_order_ids` | WC order IDs (int[]) |

### wp_options keys (other classes)
| Class constant | Option key | Stores |
|---|---|---|
| `WPDCG_Presets::OPTION_KEY` | `wpdcg_presets` | Named presets per tab (`{ tab: [ {name, data}, … ] }`) |
| `WPDCG_Menu_Generator::MENU_OPTION_KEY` | `wpdcg_menu_ids` | Generated nav menu IDs (int[]) |

---

## Batch System

Every generation run produces a **batch ID**: `batch_YYYYMMDD_HHiiss_xxxxxx`.

Each batch is stored in `wpdcg_batches` as:
```php
[
    'id'        => 'batch_20260101_120000_abc123',
    'post_type' => 'post',   // or: '_comment', '_user', '_wc_review', '_wc_order'
    'count'     => 10,
    'created'   => 1735689600,  // Unix timestamp
    'ids'       => [101, 102, 103, …],
]
```

**Batch type prefix convention** — non-post content uses a `_` prefix so the cleaner can route deletion correctly:

| Prefix | Content type |
|---|---|
| no prefix / any CPT slug | Posts (any post type) — also handles `_media` (attachments are WP posts) |
| `_comment` | Regular comments |
| `_user` | WordPress users |
| `_wc_review` | WooCommerce product reviews |
| `_wc_order` | WooCommerce orders |
| `_menu` | WordPress nav menus (taxonomy terms — requires special routing) |
| `_media` | Standalone Media Library images (falls through to post deletion path) |

`WPDCG_Cleaner::delete_batch()` reads this prefix to route to the correct deletion method.

---

## Class Reference

### WPDCG_Tracker (static)
All methods are static. Manages four flat ID lists (posts, comments, users, orders) and the batch metadata array.

Key methods:
- `get_all_ids() / add_ids() / remove_ids() / count()` — post IDs
- `get_comment_ids() / add_comment_ids() / remove_comment_ids() / count_comments()`
- `get_user_ids() / add_user_ids() / remove_user_ids() / count_users()`
- `get_order_ids() / add_order_ids() / remove_order_ids() / count_orders()`
- `get_batches()` — returns all batches, newest first
- `add_batch( $id, $post_type, $ids )` — records a new batch
- `get_batch_ids( $batch_id )` — returns IDs for a single batch
- `remove_batch( $batch_id )` — removes a batch record
- `clear()` — deletes all five option keys

---

### WPDCG_Generator
Generates posts, pages, products, and any CPT. Called from the admin handler and WP-CLI.

`generate( array $args )` accepts:
| Arg | Default | Notes |
|---|---|---|
| `post_type` | `'post'` | Any registered post type |
| `count` | `5` | 1–500 |
| `status` | `'publish'` | publish / draft / pending |
| `author_id` | `0` (current user) | |
| `paragraph_count` | `3` | 1–8 content paragraphs |
| `excerpt_enabled` | `false` | |
| `excerpt_length` | `30` | words |
| `featured_image` | `false` | Requires PHP GD extension |
| `product_type` | `'simple'` | WooCommerce products only: `simple` or `variable` |
| `auto_terms` | `false` | Creates sample taxonomy terms if no manual selection |
| `taxonomy_terms` | `[]` | `[ tax_slug => [ term_id, … ] ]` |
| `date_from` | `''` | YYYY-MM-DD |
| `date_to` | `''` | YYYY-MM-DD |
| `ai_enabled` | `false` | Uses WordPress AI Client content when true and a topic/configured connector exists |
| `ai_topic` | `''` | Client topic, e.g. "family dental clinic in Austin" |
| `ai_tone` | `'professional'` | Tone hint |
| `ai_audience` | `''` | Optional audience hint |
| `ai_image` | `false` | Uses WordPress AI Client image generation for featured/product images when supported |
**GD image internals**: All GD drawing (canvas, gradient, accent bar, rings, ghost number, "DEMO CONTENT" band) is in the private `create_gd_image_file(int $index, string $slug): array|false` helper, which returns `['filepath' => …, 'url' => …]`. Both `generate_featured_image()` (attaches to a post) and `generate_standalone_image()` (Media Library only) call this helper. This avoids 150-line duplication.

`generate_standalone_image( string $title, int $index, string $batch_id ): int|false` — public method used by `WPDCG_Media_Generator`. Saves a GD image as a Media Library attachment with `post_parent = 0`. Stamps `META_KEY` and `BATCH_META_KEY` on the attachment.

**AI content**: `WPDCG_AI_Generator` uses WordPress 7.0's `wp_ai_client_prompt()` abstraction. Credentials come from Settings → Connectors and may be provided by environment variables, PHP constants, or the connector database setting according to WordPress core. QuickDemo does not store provider API keys. Text generation asks for JSON items containing `title`, `excerpt`, and safe HTML `content`; the JSON schema uses `additionalProperties => false` for OpenAI strict structured-output compatibility. If AI is unavailable or parsing fails, generation falls back to built-in demo titles/content and records a warning. AI image generation uses `generate_image()`, applies a 120-second image-only request timeout, does not set temperature on image prompts, validates the returned image bytes, and saves the data URI to the Media Library as a plugin-tracked attachment. If AI image generation fails and the built-in placeholder image option is enabled, the placeholder image is used as a fallback. See `AI_CONTENT_GENERATION.md` before changing this flow.

**`WPDCG_AI_Generator::generate_standalone_image( string $topic, string $title, int $index, string $batch_id )`** — public method for generating a standalone AI image to the Media Library (no parent post, no post thumbnail). Returns attachment ID or `WP_Error`. Used by `WPDCG_Media_Generator`. Calls `save_data_uri_as_attachment()` with `$post_id = 0` which skips `set_post_thumbnail()` and uses the provided `$batch_id` directly for meta rather than reading from parent post meta.

**`save_data_uri_as_attachment()` standalone mode**: accepts `$post_id = 0` to create a Media Library attachment with no parent post. When `$post_id = 0`, skips `set_post_thumbnail()` and uses the `$batch_id` parameter (instead of reading from parent post meta) for `BATCH_META_KEY`. Filename uses `media` as the slug instead of post ID.

**WooCommerce products**: when `post_type === 'product'`, `inject_product_meta()` creates a complete WooCommerce product according to `product_type`. Simple products receive price, SKU, stock, virtual/downloadable, tax, and sale-price meta, then get the `simple` product type term. Variable products get the `variable` product type term, local Color and Size variation attributes, default attributes, and four child `product_variation` posts with their own prices, SKUs, stock status, and plugin batch/meta flags. Clears WC transients and syncs variable-product price caches afterwards. The admin UI intentionally exposes only `simple` and `variable`; generic `product_type` taxonomy chips are hidden so external/grouped products are not created incompletely.

**Featured images**: PHP GD generates a 1200×630 gradient image with 8 rotating colour themes (indigo, green, red, amber, blue, violet, emerald, rose), a large ghost number (zero-padded, e.g. `01`, `02`), and a centred "DEMO CONTENT" band. The image is saved to the media library and set as the post thumbnail. Important: WP square-crops the centre 630px (x=285–915 of the 1200px width) for thumbnails — all key visual elements are placed within this safe zone.

**Auto-terms**: `maybe_create_terms()` iterates public taxonomies for the post type, creates sample terms using `wp_insert_term()`, stamps new terms with `TERM_META_KEY`, and reuses existing terms by ID (without stamping) on `term_exists` error. Preset term names are defined in `get_sample_term_names()`:
- `category` → Technology, Business, Design, Development, Marketing
- `post_tag` → demo, sample, tutorial, guide, quickdemo
- `product_cat` → Electronics, Clothing, Home & Garden, Sports, Books, Beauty, Toys
- `product_tag` → new, sale, featured, bestseller, limited, eco-friendly
- Custom taxonomies → Demo Term A/B/C

---

### WPDCG_Comment_Generator
`generate( array $args )` accepts:
| Arg | Default | Notes |
|---|---|---|
| `attach_to` | `'all'` | `'all'` or `'latest_batch'` |
| `per_post` | `3` | 1–20 |
| `status` | `'approve'` | `'approve'` or `'hold'` |
| `threaded` | `true` | Adds nested replies up to depth 2 |

Attaches to demo posts that already exist (identified via `WPDCG_Generator::META_KEY`). Random author names and emails are picked from built-in pools. Batch type stored as `_comment`.

---

### WPDCG_User_Generator
`generate( array $args )` accepts:
| Arg | Default | Notes |
|---|---|---|
| `count` | `5` | 1–50 |
| `role` | `'subscriber'` | Any valid WP role slug without `manage_options`; high-privilege roles fall back to subscriber |

Generates random first+last name combinations, avoids login/email collisions by appending extra digits if needed, stores a random 16-char password. Batch type stored as `_user`.

---

### WPDCG_Woo_Generator
Requires WooCommerce to be active (`function_exists( 'wc_get_product' )`). Returns `WP_Error` with code `wpdcg_no_woo` if not active.

**`generate_reviews( array $args )`**
| Arg | Default | Notes |
|---|---|---|
| `attach_to` | `'all'` | `'all'` or `'latest_batch'` |
| `per_product` | `3` | 1–10 |

Creates `comment_type = 'review'` comments with a `rating` meta (1–5). Review text matches the star rating (5-star texts are enthusiastic, 1-star are negative). Recalculates `_wc_review_count` and `_wc_average_rating` after each product. Batch type: `_wc_review`.

**`generate_orders( array $args )`**
| Arg | Default | Notes |
|---|---|---|
| `count` | `5` | 1–50 |
| `status` | `'completed'` | Any valid WC order status (without `wc-` prefix) |

Uses `wc_create_order()`. Sets billing + shipping address, adds demo products as line items if any exist (otherwise adds a placeholder line item), sets payment method to `bacs`, randomises `date_created` within the past 30 days. Batch type: `_wc_order`.

---

### WPDCG_Cleaner
**`delete_all()`** — deletes all demo content of every type:
1. Posts (and media attachments) via `WP_Query` on `META_KEY`
2. Comments/reviews via direct DB query on `COMMENT_META_KEY`
3. Users via `WPDCG_Tracker::get_user_ids()` + `wp_delete_user()`
4. WC orders via `WPDCG_Tracker::get_order_ids()` + `$order->delete(true)`
5. Nav menus via `WPDCG_Menu_Generator::delete_all_menus()` (if class exists)
6. Auto-generated terms via `TERM_META_KEY` term meta query
7. Calls `WPDCG_Tracker::clear()`

**`delete_batch( $batch_id )`** — routes by batch type prefix:
- Default (no `_` prefix) or `_media` → `delete_batch_posts()` — WP_Query requiring generated meta, `BATCH_META_KEY`, and the QuickDemo source marker (handles both posts and Media Library attachments since both are WP posts)
- `_comment` / `_wc_review` → `delete_batch_comments()` — DB query on comment meta
- `_user` → `delete_batch_users()` — DB query on user meta
- `_wc_order` → `delete_batch_orders()` — WC order delete
- `_menu` → `WPDCG_Menu_Generator::delete_menu_batch( $batch_id )`

---

### WPDCG_Admin
Registers the admin page as a top-level **QuickDemo** menu item. All form submissions use either `admin_post_*` actions (non-JS fallback) or the single AJAX dispatcher `wp_ajax_wpdcg_ajax_generate`.

All generation logic is extracted into private `do_*` methods so both the admin_post handlers and the AJAX dispatcher can call the same code without duplication.

**Registered actions:**
| Action | Handler | Purpose |
|---|---|---|
| `admin_post_wpdcg_generate` | `handle_generate()` | Posts, pages, CPTs, products (non-JS fallback) |
| `admin_post_wpdcg_generate_comments` | `handle_generate_comments()` | Demo comments |
| `admin_post_wpdcg_generate_users` | `handle_generate_users()` | Demo users |
| `admin_post_wpdcg_generate_woo_reviews` | `handle_generate_woo_reviews()` | WC product reviews |
| `admin_post_wpdcg_generate_woo_orders` | `handle_generate_woo_orders()` | WC orders |
| `admin_post_wpdcg_generate_media` | `handle_generate_media()` | Standalone Media Library images |
| `admin_post_wpdcg_generate_menu` | `handle_generate_menu()` | WordPress nav menus |
| `admin_post_wpdcg_delete` | `handle_delete()` | Delete all (requires confirm checkbox) |
| `admin_post_wpdcg_delete_batch` | `handle_delete_batch()` | Delete single batch |
| `wp_ajax_wpdcg_ajax_generate` | `handle_ajax_generate()` | Single AJAX dispatcher for all generation types |
| `wp_ajax_wpdcg_preset_save` | `handle_preset_save()` | Save a named preset |
| `wp_ajax_wpdcg_preset_delete` | `handle_preset_delete()` | Delete a named preset |

**AJAX generation flow**: `handle_ajax_generate()` checks the `wpdcg_ajax_nonce` nonce (action `wpdcg_ajax_generate`), reads `wpdcg_sub_action` from POST, calls the matching `do_*` method, sets a user transient notice, then returns `wp_send_json_success(['redirect' => $url])`. JS redirects to that URL so the transient notice is displayed. On AJAX failure the JS falls back to native form submit.

Notices use a transient keyed to the current user (`wpdcg_notice_{user_id}`, 60s TTL).

`page_url( string $tab = '' ): string` is a **private** helper that returns the canonical admin page URL, with an optional `?tab=` query arg for post-redirect tab targeting.

`enqueue_assets()` loads `admin/css/wpdcg-admin.css` and `admin/js/wpdcg-admin.js` on the plugin page only. It calls `wp_localize_script()` to pass a `wpdcgAdmin` JS object:

| Key | Value |
|---|---|
| `ajaxUrl` | `admin_url('admin-ajax.php')` |
| `ajaxNonce` | nonce for `wpdcg_ajax_generate` |
| `presetNonce` | nonce for `wpdcg_preset_action` |
| `activeTab` | current tab slug |
| `presets` | array of presets for the current tab (server-rendered) |
| `confirmBatchDelete` | i18n confirmation string |
| `confirmPresetDelete` | i18n confirmation string |
| `generating` | i18n spinner label |
| `savePreset` | i18n prompt label |

If you need to pass additional PHP data to the admin JS, add it here.

---

### Admin UI (admin-page.php)

Five tabs, URL param `?tab=posts|comments|users|woocommerce|extras`:

**Posts tab** — generates posts, pages, and custom post types (excludes `attachment` and `product`). Fields: Post Type, Count, Status, Author, Taxonomy Terms (auto-generate checkbox + manual chip selectors), Paragraphs, Excerpt, Date Range, Featured Image.

- *Presets bar*: shown above the form. "Save As" prompts for a name → AJAX → updates the select. "Load" reads `data-fields` from the selected option and populates the form. "Delete" confirms → AJAX → rebuilds the select.

**Comments tab** — generates comments on existing demo posts. Fields: Attach To, Per Post, Status, Threaded Replies.

**Users tab** — generates WordPress users. Fields: Count, Role.

**WooCommerce tab** — shown with "Inactive" badge if WooCommerce is not installed.
- Uses independent accordion panels. Products is open by default; Products, Reviews, and Orders can be opened or closed one at a time.
- *Products* panel: Count, Status, Author, Taxonomy Terms (auto-generate + product_cat/product_tag chips), AI Content, Paragraphs, Date Range, Featured Image. Uses the `wpdcg_generate` action with `post_type=product` hidden input.
- *Reviews* panel: Attach To, Per Product.
- *Orders* panel: Count, Order Status.

**Extras tab** — two cards side by side:
- *Media Images* card: Count (1–50), generates standalone GD placeholder images to the Media Library. Action: `wpdcg_generate_media`.
- *Navigation Menus* card: Menu Name (optional, auto-chosen if blank), Item Count (3–12), With Child Items checkbox. Action: `wpdcg_generate_menu`.

**Progress bars** — each generate form footer includes a `.wpdcg-progress` bar that appears during AJAX submission and animates to ~90% before the response arrives.

**Stats bar** — always visible above tabs. Shows separate badges for Posts, Products, Comments, Users, Orders counts.

**Batch history** — shows the 30 most recent batches with type label, item count, date, and individual delete button. Type labels are human-readable (e.g. `_comment` → "Comments", `product` → "Product", `_media` → "Media Images", `_menu` → "Nav Menus").

**Delete All card** — visible only when tracked content exists. Requires a confirmation checkbox.

---

### WPDCG_Presets (static)
Manages named form-state snapshots stored in the `wpdcg_presets` wp_option. Presets are scoped to a tab so Posts and WooCommerce presets are independent.

| Method | Purpose |
|---|---|
| `get_for_tab( string $tab ): array` | Returns all presets for a tab, sorted alphabetically by name |
| `save( string $name, string $tab, array $data ): bool` | Upserts a preset |
| `load( string $name, string $tab ): ?array` | Returns preset data or null |
| `delete_preset( string $name, string $tab ): bool` | Removes a preset |

Preset data is whatever `serializeFormToObject()` captures — field values keyed by input `name`, skipping nonces and `action`.

---

### WPDCG_Media_Generator
Generates standalone images directly to the Media Library (no parent post). Supports both GD placeholder images and AI-generated images via `WPDCG_AI_Generator::generate_standalone_image()`.

`generate( array $args )` accepts:
| Arg | Default | Notes |
|---|---|---|
| `count` | `5` | 1–50 |
| `ai_enabled` | `false` | Use WordPress AI Client image generation |
| `ai_topic` | `''` | Topic/subject for AI prompts — required when `ai_enabled` is true |

When `ai_enabled` is true and `ai_topic` is set and `WPDCG_AI_Generator::supports_image_generation()` returns true, calls `WPDCG_AI_Generator::generate_standalone_image()` for each image. If AI fails for an individual image and GD is available, falls back to GD for that image. When `ai_enabled` is false or the AI connector is not configured, uses GD placeholders. Batch type stored as `_media`. Media attachments are WP posts, so the default `delete_batch_posts()` path in the cleaner handles deletion automatically.

---

### WPDCG_Menu_Generator
Creates WordPress nav menus populated with custom-URL items.

`generate( array $args )` accepts:
| Arg | Default | Notes |
|---|---|---|
| `name` | auto-chosen | Menu display name; counter suffix appended if name exists |
| `item_count` | `5` | 3–12 |
| `with_children` | `false` | If true, adds child items under the first two top-level items |

Picks menu names from a pool (e.g. "Main Menu", "Footer Links"). Items use realistic labels ("Home", "About", "Services", …) with `#` as the URL. Child items are added under the first two parents if `with_children=true`.

Static helpers:
- `get_menu_ids(): array` — reads `wpdcg_menu_ids`
- `count_menus(): int`
- `delete_all_menus(): int` — deletes all tracked menus, returns count
- `delete_menu_batch( string $batch_id ): int` — deletes the single menu for this batch

Calls `WPDCG_Tracker::add_batch( $batch_id, '_menu', [ $menu_id ] )`.

---

### WP-CLI (WPDCG_CLI)
Command: `wp quickdemo`

```bash
# Generate 20 draft posts with featured images across a date range
wp quickdemo generate --count=20 --post_type=post --status=draft \
  --featured-image --date-from=2025-01-01 --date-to=2025-12-31

# Generate AI-written posts for a client topic
wp quickdemo generate --count=5 --ai-topic="family dental clinic in Austin" --ai-tone=friendly --ai-image

# Generate complete variable WooCommerce products with attributes and variations
wp quickdemo generate --post_type=product --product-type=variable --count=3

# Delete a specific batch
wp quickdemo delete --batch=batch_20260101_120000_abc123

# Generate comments, users, WooCommerce reviews, and WooCommerce orders
wp quickdemo generate-comments --per-post=5 --attach-to=latest_batch
wp quickdemo generate-users --count=10 --role=author
wp quickdemo generate-reviews --per-product=4
wp quickdemo generate-orders --count=10 --status=processing

# Delete everything
wp quickdemo delete --all

# List all batches
wp quickdemo list
```

CLI generation covers posts, comments, users, WooCommerce reviews, and WooCommerce orders.

---

## Key Design Decisions

**Cleanup safety**: Generated posts/attachments/variations carry the legacy generated flag, a batch ID, and the QuickDemo-specific source marker (`_wpdcg_source = quickdemo-content-generator`). Comments, users, reviews, and orders also receive the source marker alongside their generated/batch meta. Delete-all requires the QuickDemo source marker; old tracked batches are backfilled before cleanup so dev data remains removable without widening delete-all to arbitrary items with only a generic generated meta key.

**Product type is WooCommerce, not Posts**: `product` is excluded from the Posts tab dropdown. All WooCommerce content (products, reviews, orders) lives in the WooCommerce tab to match how users think about their shop.

**Featured image safe zone**: WordPress square-crops the centre 630px of a 1200px-wide image for thumbnails (x=285–915). All visual elements — the top accent bar (full width), ghost number (~x=520), "DEMO CONTENT" label (horizontally centred) — are placed within this range.

**Auto-terms idempotency**: `wp_insert_term()` returns a `term_exists` error code with the existing term's ID when the term already exists. This lets the generator reuse terms like "Uncategorized" without stamping them as auto-generated and thus without deleting them during cleanup.

**Batch type routing**: The `_` prefix convention on non-post batch types (`_comment`, `_user`, `_wc_review`, `_wc_order`, `_menu`, `_media`) lets `WPDCG_Cleaner::delete_batch()` route to the correct cleanup method without storing additional type metadata. `_media` is a special case: media attachments are WordPress posts, so they fall through to the default `delete_batch_posts()` path with generated, batch, and QuickDemo source-marker checks.

**GD code shared between posts and media**: `create_gd_image_file()` is the single private method that does all GD drawing and file saving. Both `generate_featured_image()` (post thumbnail) and `generate_standalone_image()` (Media Library only) call it with different slugs. This avoids ~150 lines of duplication.

**Preset load without AJAX**: Preset data for the active tab is embedded server-side as `data-fields` attributes on `<option>` elements. When the user clicks Load, JS reads the attribute and calls `populateForm()` — no round-trip needed.

**AJAX nonce injection**: The AJAX nonce (`wpdcg_ajax_nonce`) is not a hidden field in any form — it is injected by JS into the serialized data just before POSTing. This keeps the existing form nonces intact for the non-JS `admin_post` fallback.

---

## Recent Agent Notes

### 2026-05-24 — WordPress AI Connectors integration hardening

- Fixed Connectors admin URL to `admin_url( 'options-connectors.php' )`.
- Fixed AI Client support checks to account for `WP_AI_Client_Prompt_Builder` magic methods (`__call()`); use `is_callable()` in addition to `method_exists()`.
- Made OpenAI strict JSON schema compatible by adding `additionalProperties => false` to AI text response schemas.
- Removed image `using_temperature()` because OpenAI image models do not advertise that option.
- Added image model preferences: `gpt-image-1, gpt-image-2, dall-e-3, dall-e-2, imagen-4`.
- Added image-only request timeout via `RequestOptions::KEY_TIMEOUT` with `WPDCG_AI_Generator::IMAGE_TIMEOUT` (`120.0` seconds).
- Admin notices now append AI fallback errors so failed text/image generation is visible after a run.
- Added `AI_CONTENT_GENERATION.md` as the dedicated AI handoff document.

### 2026-05-24 — WooCommerce admin accordion

- Converted the WooCommerce tab's Products, Reviews, and Orders sections into independent accordion panels.
- Products is open by default.
- Users can open multiple panels at the same time or close each panel individually.

### 2026-05-24 — Bug fixes (full code audit)

- **Fixed `ltrim` character-mask bug** (`class-wpdcg-woo-generator.php`, `admin/views/admin-page.php`): `ltrim($s, 'wc-')` strips any combination of the characters `w`, `c`, `-` — not the string prefix `wc-`. This caused `wc-completed` → `ompleted` and `wc-cancelled` → `ancelled`, making those statuses invalid when set on orders. Replaced with `0 === strpos( $s, 'wc-' ) ? substr( $s, 3 ) : $s` in both locations.
- **Fixed GD featured image missing batch meta** (`class-wpdcg-generator.php`): `generate_featured_image()` stamped attachments with `META_KEY` but not `BATCH_META_KEY`, so GD placeholder images survived batch deletion (only `delete_all()` caught them via `META_KEY`). Added `update_post_meta( $attachment_id, self::BATCH_META_KEY, … )` to match what the AI image path already did.
- **Replaced deprecated `current_time('timestamp')`** (`class-wpdcg-woo-generator.php`): Changed to `time()` which returns a UTC Unix timestamp — correct for `set_date_created()`.
- **Removed hard cap of 100 posts/products** (`class-wpdcg-comment-generator.php`, `class-wpdcg-woo-generator.php`): `posts_per_page => 100` silently prevented comments and reviews from reaching posts/products beyond the first 100. Changed to `-1` (no limit).
- **Fixed `DEFAULT_TEXT_MODELS` constant** (`class-wpdcg-ai-generator.php`): `gemini-3.1-pro-preview` and `gpt-5.4` were not real model IDs and would have been silently skipped by the AI Client. Replaced with `claude-opus-4-7, claude-sonnet-4-6, gpt-4o, gemini-1.5-pro`.

### 2026-05-24 — AI content depth fix (word-count prompt)

AI-generated content was producing thin skeleton articles: many headings each followed by a single short paragraph. Root cause: passing `paragraph_count` as a literal number made the AI interpret it as a `<p>` tag count and fill each section minimally.

**Fix** (`class-wpdcg-ai-generator.php` — `build_prompt()`): `$paragraph_count` is now converted to a target word count (`$paragraph_count × 100`) and the prompt says `"Target content length: ~N words per item."` Three new rules enforce depth: paragraphs must be ≥ 60 words, headings get 2–3 paragraphs each, and structure should vary (lists, blockquotes). The `paragraph_count` field in the UI and all form handlers are unchanged — only the signal sent to the AI changed. See `AI_CONTENT_GENERATION.md` for the full rationale and a **do-not-revert** note.

---

### 2026-05-24 — AJAX submission, presets, media generation, nav menus

Four new features added in a single session (custom fields were added then removed — see note below):

**1. AJAX form submission with progress bar**
- `wp_ajax_wpdcg_ajax_generate` handler dispatches to private `do_*` methods; returns `{redirect}` on success
- JS intercepts `.wpdcg-generate-form` submit, shows animated progress bar, POSTs to ajaxUrl, redirects on success; falls back to native POST on AJAX failure
- `animateProgress()` eases toward 90% at 200ms intervals, stalls until server responds
- All six generate forms have a `.wpdcg-progress` / `.wpdcg-progress__bar` element in their footer

**2. Generation presets**
- `WPDCG_Presets` (new static class) — stores named presets in `wpdcg_presets` wp_option, scoped per tab
- Save As: JS prompts for name → `wp_ajax_wpdcg_preset_save` → returns updated list
- Load: preset data is embedded as `data-fields` on `<option>` elements; JS reads attribute and calls `populateForm()` (no AJAX needed)
- Delete: confirm → `wp_ajax_wpdcg_preset_delete` → rebuilds select
- Presets bar shown above the Posts form (and can be added to other tabs)

**3. Standalone Media Library images**
- `WPDCG_Media_Generator` (new class) — calls `WPDCG_Generator::generate_standalone_image()` per image
- GD drawing extracted to `create_gd_image_file()` private helper shared with `generate_featured_image()`
- Batch type `_media`; falls through to `delete_batch_posts()` since attachments are WP posts
- Extras tab, Media Images card

**4. WordPress nav menus**
- `WPDCG_Menu_Generator` (new class) — uses `wp_create_nav_menu()` + `wp_update_nav_menu_item()`
- Picks from pools of realistic menu names and item labels; appends counter suffix if name exists
- Optional child items under first two top-level items when `with_children=true`
- Tracks menu IDs in `wpdcg_menu_ids` option; batch type `_menu` requires explicit routing in cleaner
- Extras tab, Navigation Menus card

**New CSS**: `.wpdcg-progress`, `.wpdcg-progress__bar`, `.wpdcg-presets-bar` and children

**Files changed**: `class-wpdcg-presets.php` (new), `class-wpdcg-media-generator.php` (new), `class-wpdcg-menu-generator.php` (new), `quickdemo-content-generator.php` (requires), `uninstall.php` (2 new option deletes), `class-wpdcg-generator.php` (standalone image, GD refactor), `class-wpdcg-cleaner.php` (menu deletion, _menu routing), `class-wpdcg-admin.php` (rewrite), `admin/views/admin-page.php` (Extras tab, presets bar, progress bars), `admin/js/wpdcg-admin.js` (rewrite), `admin/css/wpdcg-admin.css` (new component styles)

---

### 2026-05-24 — UX fixes and improvements (follow-up session)

**1. Preset reload bug fix**
- Root cause: `populateForm()` called `.trigger('change')` unconditionally on selects. The `#wpdcg_post_type` change handler always navigates the page, so even when the value was unchanged, the page reloaded and discarded the populated fields.
- Fix: In `populateForm()`, compare old value before calling `.trigger('change')` — only fires if value genuinely changed.
- Edge case: If the preset's post type differs from the current page, fields are stored in `sessionStorage` before navigating, then restored after the reload in a self-invoking IIFE in document ready.

**2. Presets on WooCommerce Products tab**
- Added the full presets bar (Save As / Load / Delete) inside `#wpdcg-wc-products-panel`, before the Products form.
- WooCommerce presets are scoped to the `woocommerce` tab key — independent from Posts presets.
- Fixed the Load handler to scope form lookup to `$( this ).closest( '.wpdcg-card' )` instead of `.first()` globally, so it doesn't accidentally target the Reviews or Orders form on the WooCommerce tab.

**3. Progress bar layout fix**
- The `.wpdcg-progress` element was inside `.wpdcg-card__foot` (a flex row), making it a 4px-tall strip squeezed beside the button — invisible.
- Moved it outside `.wpdcg-card__foot` but still inside `<form>`, so it appears as a full-width blue stripe at the bottom of the card during generation.
- Applied to all 8 generate forms via `replace_all`.

**4. Generation status text**
- Added `buildStatusText($form)` JS function that reads the form's `action` field and count/type inputs to generate a human-readable message: e.g. `"Generating 10 posts…"`, `"Generating 5 products…"`, `"Generating 3 comments per post…"`.
- Status span is created dynamically after the Generate button on first submit and reused on subsequent submits.
- Cleared on AJAX failure; disappears naturally on redirect after success.
- CSS: `.wpdcg-status-text` — 12px italic grey, shown inline next to the button.

**5. Preset instructions for new users**
- Added a `dashicons-editor-help` icon (`?`) next to the Presets label in both the loaded and empty states. Hovering shows a tooltip explaining what presets are and how to use them.
- Improved empty-state hint text: `"No presets yet — configure the form, then click Save as Preset to create one."`
- Added always-visible hint text to the loaded state: `"Pick a preset → Load to restore · Save As to store current settings · Delete to remove."`
- Applied to both the Posts tab and WooCommerce Products tab presets bars.
- CSS: `.wpdcg-presets-bar__help` — 15px grey dashicon, cursor:help, turns blue on hover.

**Custom fields removed**: The custom fields feature (dynamic key/value/type UI, `update_post_meta()` injection, `cast_custom_field()`) was built then removed at user request. Neither WordPress native meta nor ACF support will be added.

---

### 2026-05-24 — AI image generation for standalone Media Library images

Added topic-based AI image generation to the Extras tab Media Images card.

**Changes:**
- `includes/class-wpdcg-ai-generator.php`: Added public `generate_standalone_image( string $topic, string $title, int $index, string $batch_id )`. Uses same AI image flow as `generate_featured_image()` but with a topic-focused prompt. Calls `save_data_uri_as_attachment()` with `$post_id = 0`.
- `includes/class-wpdcg-ai-generator.php`: Refactored `save_data_uri_as_attachment()` to accept optional `$batch_id = ''` and handle `$post_id = 0` (skips `set_post_thumbnail()`, uses provided batch ID for meta, uses `media` as filename slug instead of post ID).
- `includes/class-wpdcg-media-generator.php`: Added `ai_enabled` and `ai_topic` args. When AI is enabled and topic is set and `supports_image_generation()` returns true, uses AI per image with per-image GD fallback if AI fails. Falls back entirely to GD if AI is not configured.
- `admin/class-wpdcg-admin.php`: `do_generate_media()` reads and passes `wpdcg_media_ai_enabled` and `wpdcg_media_ai_topic` from POST.
- `admin/views/admin-page.php`: Added AI toggle + topic text field to the Media Images form, wrapped in `<?php if ( WPDCG_AI_Generator::supports_image_generation() ) :` so the section only appears when an AI connector is configured. Non-AI users see a hint about configuring a connector.
- `admin/js/wpdcg-admin.js`: Added `#wpdcg_media_ai_enabled` change handler to toggle `#wpdcg-media-ai-wrap`.

---

### 2026-05-24 — Variable product UI clarification

- Product type is no longer shown as generic `product_type` taxonomy chips under Terms because selecting `variable` has required WooCommerce side effects.
- WooCommerce Products form has a dedicated **Product Type** select in Product Options (`simple` or `variable` only).
- Selecting `variable` reveals `#wpdcg-variable-preview`, showing that the generator will create local Color and Size attributes plus four child variations (`Black / Small`, `Navy / Medium`, `Silver / Large`, `Black / Large`).
- Terms section now includes a live `wpdcg-product-type-summary` under the Terms heading and a footer summary near the Generate button, so the selected product type remains visible after scrolling.
- Product Type summary now sits directly under the Terms heading.
- Terms hint now says taxonomy checkboxes assign categories, tags, and attributes only; product type and variations are controlled separately.
- WooCommerce system taxonomy `product_visibility` is hidden from manual term chips because values like `exclude-from-search`, `outofstock`, and `rated-*` are internal visibility/rating state, not normal demo taxonomy choices.
- New CSS: `.wpdcg-variable-preview`, `.wpdcg-product-type-summary`, `.wpdcg-product-type-footer-summary`, and child classes.

---

### 2026-05-24 — Variable product admin compatibility fix

- Generated variable products had correct `_product_attributes` meta and child `product_variation` posts, but WooCommerce's Attributes/Variations panels could appear blank with the active `attribute-thumbnail-for-woocommerce` plugin.
- Root cause: that plugin registers `WcAttributeThumbnail\AttributeFrontend::prepend_attribute_image()` on `woocommerce_attribute_label` with the third argument type-hinted as `array`; WooCommerce passes a `WC_Product` object when loading admin product attributes/variations, so PHP throws a `TypeError` before the callback's `is_admin()` guard can run.
- Added `WPDCG_Core::remove_incompatible_admin_attribute_filters()` on `admin_init` to remove that frontend-only callback in wp-admin/admin-ajax. This preserves storefront behavior while keeping QuickDemo-generated variable products editable in WooCommerce admin.

---

### 2026-05-24 — Variable product stock status fix

- Variable products were generated with child variations marked `_stock_status = instock`, but the variable parent could still show `outofstock`.
- Root cause: QuickDemo creates variation posts/meta directly to avoid the `attribute-thumbnail-for-woocommerce` TypeError, but WooCommerce parent stock sync reads child stock from `wc_product_meta_lookup`, not only post meta. The lookup rows were stale/missing, so `WC_Product_Variable::sync()` thought no child was in stock.
- Added `WPDCG_Generator::sync_variation_stock_status()` after each variation is created. It removes the known incompatible attribute-label filter if present, then calls `wc_update_product_stock_status( $variation_id, 'instock' )` so WooCommerce saves stock status and updates lookup data before parent sync.
- Verification: a generated published variable product reported parent `stock=instock`, `in_stock=1`, `child_is_in_stock=1`, 4 visible children, and all child variations `instock`.

---

### 2026-05-24 — Variation image generation

- When the WooCommerce Products form has `wpdcg_featured_image_generate` checked and Product Type is `variable`, QuickDemo now creates a built-in GD placeholder image for each generated variation.
- New helper: `WPDCG_Generator::generate_variation_image( $parent_id, $variation_id, $title, $index )`.
- Variation image attachments are parented to the product, stamped with `META_KEY` and `BATCH_META_KEY`, and assigned to the variation via `_thumbnail_id`.
- The parent product image behavior is unchanged: AI image is attempted first when enabled; built-in placeholder image is used as fallback or when AI images are not enabled.

---

### 2026-05-24 — Product gallery image generation

- When the WooCommerce Products form has `wpdcg_featured_image_generate` checked, QuickDemo now generates three built-in GD placeholder images for the WooCommerce product gallery.
- New helper: `WPDCG_Generator::generate_product_gallery_images( $post_id, $title, $index )`.
- Gallery image attachment IDs are stored in `_product_image_gallery` as a comma-separated list.
- Gallery image attachments are parented to the product and stamped with `META_KEY` and `BATCH_META_KEY`, so per-batch and delete-all cleanup removes them.

---

### 2026-05-24 — AI gallery and variation images

- Topic-based AI product image generation now covers more than the parent featured image.
- New AI helpers:
  - `WPDCG_AI_Generator::generate_product_gallery_image( $post_id, $title, $topic, $slot, $index )`
  - `WPDCG_AI_Generator::generate_variation_image( $post_id, $title, $topic, $color, $size, $index )`
- `save_data_uri_as_attachment()` now accepts `$set_thumbnail`; gallery and variation AI images attach to the product without replacing the parent featured image.
- `WPDCG_Generator::generate_product_gallery_images()` attempts AI gallery images when `ai_image` is enabled and an AI topic exists. If an AI image fails and built-in placeholder images are enabled, it falls back per slot to GD.
- Variable product variation thumbnails attempt AI images when `ai_image` is enabled and an AI topic exists. If an AI variation image fails and built-in placeholder images are enabled, it falls back per variation to GD.

---

### 2026-05-24 — Comments empty-state guard

- Comments tab now checks for at least one generated QuickDemo post target before enabling comment generation.
- If no generated targets exist, the form shows an inline warning, disables comment fields and the Generate button, and offers a **Go to Posts** shortcut.
- New CSS: `.wpdcg-inline-notice` and `.wpdcg-inline-notice--warning`.

---

### 2026-05-24 — Non-AI content quality upgrade

- Replaced lorem-style fallback body paragraphs in `WPDCG_Generator::$content_blocks` with more professional, reusable demo copy.
- `WPDCG_Generator::get_content()` now accepts post type and title context.
- Added page-aware fallback content via `get_page_content()` for common pages such as About, Services, Contact, FAQ, and Team.
- Added product-specific fallback descriptions via `get_product_content()` so WooCommerce products no longer use generic article copy when AI is disabled.
- Updated built-in comment and WooCommerce review text pools to sound more natural and client-demo ready.

---

### 2026-05-24 — Release security hardening

- Added `WPDCG_Generator::SOURCE_META_KEY` / `SOURCE_VALUE` and `stamp_generated_post()` so generated posts, attachments, and variations receive a QuickDemo-specific ownership marker in addition to legacy generated and batch meta. Comments, users, reviews, and orders now receive the same source marker in their own meta stores.
- Delete-all now requires the QuickDemo source marker for post-like content. `WPDCG_Cleaner::backfill_source_meta_from_tracker()` marks previously tracked batches before deletion so existing dev data remains removable.
- Batch post deletion now requires both generated meta and the requested batch meta.
- AI image saves now enforce a 10 MB limit, verify actual image bytes with `getimagesizefromstring()`, allow only JPEG/PNG/WebP, run `wp_check_filetype_and_ext()`, and delete the file if attachment insertion fails.
- Removed the PHP 8 union return type from `WPDCG_AI_Generator::generate_standalone_image()` so the file remains parseable under the declared PHP 7.4 requirement.
- Preset AJAX save/delete now validates tab slugs, caps preset names at 80 bytes, rejects payloads over 20 KB, and `WPDCG_Presets` limits stored presets to 30 per tab.
- User generation now blocks roles with `manage_options`; the admin UI filters those roles out, and the generator falls back to subscriber if such a role is submitted directly or via CLI.

---

### 2026-05-24 — WordPress.org submission prep: security audit, Plugin Check fixes, GitHub, zip

**Security audit — all issues fixed:**
- `do_generate()` AJAX path lacked `post_type_exists()` validation (the non-AJAX handler had it but the AJAX dispatcher called `do_generate()` directly). Moved validation into `do_generate()` so both paths share it.
- All numeric inputs (`count`, `per_post`, `paragraph_count`, `excerpt_length`, etc.) now have server-side `max(low, min(high, value))` caps in every `do_*` method — HTML `min/max` attributes are client-side only.
- `build_generate_message()` rewrote from string concatenation to `switch/case` to satisfy WPCS `NonSingularStringLiteralSingle/Plural` — `_n()` arguments must be string literals, not concatenated variables.
- `class-wpdcg-cleaner.php` line 195: wrapped error string in `__()` with sprintf for translatability.

**Plugin Check errors fixed:**
- `Tested up to: 6.9` → `Tested up to: 7.0` (was an ERROR — must match current WP version).
- Added `phpcs:disable WordPress.Security.NonceVerification.Missing` block around all private `do_*` methods — false positives, nonce IS verified in public handlers upstream.
- Added `phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound` at top of `admin/views/admin-page.php` — false positives, file is a view template included inside a class method, variables are local scope.

**New files created:**
- `.gitignore` — excludes `.claude/` and `quickdemo-content-generator-*.md` report files from git
- `.distignore` — WordPress.org standard; excludes `.git`, `.gitignore`, `.distignore`, `.claude`, `CLAUDE.md`, `AI_CONTENT_GENERATION.md`, and report `.md` files from distribution zip

**GitHub:**
- Old remote (`wp-demo-content-generator`) removed; new remote set to `https://github.com/mosharafmanu/wp-quickdemo-content-generator`
- Plugin URI in plugin header updated to match
- All changes committed and pushed; latest commit on `main`

**WordPress.org submission:**
- Cannot submit as new plugin while old `demo-content-generator` review is pending
- Uploaded `quickdemo-content-generator.zip` via "Upload updated plugin for review" on existing ticket
- Requested slug change from `demo-content-generator` → `quickdemo-content-generator` via email reply to `plugins@wordpress.org`
- **Status: waiting for WordPress.org team response**
- TextDomainMismatch errors in Plugin Check are expected and will resolve automatically once WP.org updates the slug — no code change needed

**Clean zip location:** `/tmp/quickdemo-content-generator.zip` (274 KB). Rebuild command if needed:
```bash
cd /Applications/AMPPS/www/ClientProjects/WordPress/2026/plugins-dev/wp-content/plugins
zip -r /tmp/quickdemo-content-generator.zip quickdemo-content-generator \
  --exclude "*.git*" --exclude "*/CLAUDE.md" --exclude "*/AI_CONTENT_GENERATION.md" \
  --exclude "*/.claude/*" --exclude "*/quickdemo-content-generator-*.md" \
  --exclude "*/.distignore" --exclude "*/.gitignore"
```
