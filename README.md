# QuickDemo Content Generator

A lightweight WordPress plugin to quickly fill a site with demo posts, pages, products, comments, users, and WooCommerce orders — and cleanly remove them when you are done.

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b?logo=wordpress)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb3?logo=php)
![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)

---

## Features

- Supports any public post type — posts, pages, products, or custom post types registered by your theme or other plugins
- Generates comments, lower-privilege users, WooCommerce product reviews, and WooCommerce orders
- Provides WP-CLI commands for generation, batch listing, and deletion
- Optional WordPress AI Connector-powered titles, content, featured images, and product images based on a client topic
- WooCommerce tools are grouped into independent Products, Reviews, and Orders accordions
- Generates a unique gradient featured image for each post using PHP GD and saves it to the media library
- Builds professional fallback content without AI, including post articles, page-specific copy, product descriptions, comments, and reviews
- Uses structured HTML with headings, paragraphs, lists, blockquotes, tables, and inline links for realistic theme testing
- Optionally creates a post excerpt with a configurable word limit
- Lets you assign existing taxonomy terms to every generated post
- Choose any registered WordPress user as the author
- Set the post status to Published, Draft, or Pending Review
- Tracks everything it creates so deletion only ever touches plugin-generated content — your real content is safe
- Uses nonce/capability checks, scoped cleanup markers, capped presets, and validated AI image files for release safety
- Follows WordPress coding standards with nonce verification, capability checks, and escaped output

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 6.0 |
| PHP | 7.4 |
| PHP GD extension | Required for featured image generation |

---

## Installation

1. Clone or download this repository into your `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/mosharafmanu/quickdemo-content-generator.git
   ```
2. Log in to your WordPress admin.
3. Go to **Plugins → Installed Plugins**.
4. Find **QuickDemo Content Generator** and click **Activate**.

---

## Usage

Go to **QuickDemo** in the WordPress admin menu. Its submenu links open Posts & Pages, Comments, Users, and WooCommerce directly.

To use AI content, configure an AI provider under **Settings → Connectors**, then enable **AI Content** while generating posts or products and enter the client topic. AI images can be slower than text generation; for best reliability, generate one or two AI images per run or use the built-in placeholder image option as a fallback.

### Generating content

| Field | Description |
|---|---|
| Post Type | Any public post type registered on the site |
| Count | How many items to create (1–500) |
| Post Status | Published, Draft, or Pending Review |
| Author | Any registered WordPress user |
| Paragraphs | Controls content depth per post (1–8) |
| Featured Image | Uses a built-in gradient placeholder image, or acts as a fallback when AI image generation is enabled |
| Excerpt | Optionally adds an excerpt with a custom word limit |
| Assign Terms | Attaches existing taxonomy terms to each generated post |

Hit **Generate Demo Content** and the plugin logs every item it creates.

### Deleting content

Once generated content exists, a delete section appears at the bottom of the page. Tick the confirmation checkbox and click **Delete All Demo Content**. Only items created by this plugin are removed.

---

## Content structure

Each generated item uses professional built-in fallback copy when AI is disabled. Posts are built as complete articles, pages use page-aware copy for common titles, and WooCommerce products use product-specific descriptions instead of generic article text. The structure varies per run and scales with the Paragraphs setting:

```
Intro paragraph
h2 + paragraph + list or blockquote
h2 + paragraph
h3 + paragraph + blockquote
Closing paragraph with an inline link
```

---

## Changelog

### 1.0.0
- Initial release
- Generate posts, pages, and any registered custom post type
- Generate WooCommerce products with price, SKU, stock, and product type meta
- Generate WooCommerce product reviews (1–5 stars with matching text)
- Generate WooCommerce orders with billing/shipping address and real line items (HPOS-compatible)
- Generate WordPress users with lower-privilege roles
- Generate comments (threaded, approve/hold, attach to all demo posts or latest batch)
- PHP GD featured image generation (1200×630, 8 colour themes, ghost numbers)
- Optional AI-powered titles, content, and featured images via WordPress AI Connectors
- Rich HTML content with headings, lists, blockquotes, and inline links
- Auto-generate taxonomy terms per content type (idempotent)
- Taxonomy term assignment from existing terms
- Date range, excerpt, author, and post status controls
- Full WP-CLI support: `wp quickdemo generate`, `generate-comments`, `generate-users`, `generate-reviews`, `generate-orders`, `delete`, `list`
- Batch tracking system — every item is stamped and recorded so deletion never touches real content
- Per-batch and delete-all deletion across all content types
- Stats bar with live counts per content type

---

## License

[GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html)

---

## Author

**Mosharaf Hossain** — [mosharafmanu.com](https://mosharafmanu.com/)
