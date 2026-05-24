# QuickDemo AI Content Generation

This document explains how QuickDemo's optional AI content generation works so future agents can debug or extend it without rediscovering the flow.

## Overview

QuickDemo can generate demo post/page/product titles, excerpts, body content, and optional featured/product images through the WordPress 7.0 AI Client and Connectors system.

The plugin does not store provider API keys. API keys are configured in WordPress admin under `Settings > Connectors`, which currently resolves to `wp-admin/options-connectors.php`. WordPress core passes configured connector credentials into the AI Client registry during `init`.

Default demo content still works without AI. AI is opt-in per generation run.

## Main Files

- `includes/class-wpdcg-ai-generator.php`
  Handles all WordPress AI Client calls, prompt construction, JSON parsing, HTML sanitization, AI image generation, and media-library attachment creation.
- `includes/class-wpdcg-generator.php`
  Main post/page/product generator. It calls `WPDCG_AI_Generator` when AI is enabled, then falls back to built-in demo content if AI fails.
- `admin/views/admin-page.php`
  Renders the AI Content controls for Posts and WooCommerce products. It checks AI text/image support before enabling checkboxes.
- `admin/class-wpdcg-admin.php`
  Sanitizes posted AI fields, validates that a client topic exists when AI is enabled, passes AI args into the generator, and displays generation warnings.
- `admin/js/wpdcg-admin.js`
  Shows or hides the AI option panels when the AI Content checkbox changes. Also controls the independent WooCommerce accordion panels.
- `includes/class-wpdcg-cli.php`
  Adds WP-CLI flags: `--ai-topic`, `--ai-tone`, `--ai-audience`, and `--ai-image`.

## Admin Flow

The admin page checks support with:

- `WPDCG_AI_Generator::is_ai_client_available()`
- `WPDCG_AI_Generator::supports_text_generation()`
- `WPDCG_AI_Generator::supports_image_generation()`

If text generation is unsupported, the AI Content checkbox is disabled and the UI links to `options-connectors.php`.

When enabled, the form posts:

- `wpdcg_ai_enabled`
- `wpdcg_ai_topic`
- `wpdcg_ai_audience`
- `wpdcg_ai_tone`
- `wpdcg_ai_image`

`WPDCG_Admin::handle_generate()` sanitizes these values and rejects the request if AI is enabled without a topic.

## Text Generation

`WPDCG_Generator::generate()` calls:

```php
( new WPDCG_AI_Generator() )->generate_items( array(
	'post_type'       => $post_type,
	'count'           => $count,
	'topic'           => $ai_topic,
	'tone'            => $ai_tone,
	'audience'        => $ai_audience,
	'paragraph_count' => $paragraph_count,
	'excerpt_length'  => $excerpt_length,
) );
```

`generate_items()` builds a prompt asking for only JSON with this shape:

```json
{
  "items": [
    {
      "title": "...",
      "excerpt": "...",
      "content": "<p>...</p><h2>...</h2><p>...</p><p>...</p><ul><li>...</li></ul>"
    }
  ]
}
```

The schema intentionally includes `additionalProperties => false` at object levels. The OpenAI provider sends strict JSON schema requests, and strict OpenAI schemas require this.

### Content depth — word count, not paragraph count

The prompt does **not** pass `paragraph_count` directly to the AI. Passing a raw paragraph count causes the AI to generate many thin sections (one heading → one short paragraph → repeat), which produces skeleton-style content rather than real articles.

Instead, `build_prompt()` converts `$paragraph_count` to a target word count:

```php
$target_words = $paragraph_count * 100;
// paragraph_count=3 → ~300 words  |  paragraph_count=8 → ~800 words
```

The prompt then says `"Target content length: ~{N} words per item."` and includes rules that enforce depth:

- Every paragraph must be at least 60 words — no heading followed by a single sentence.
- Write 2–3 paragraphs under each heading wherever depth is warranted.
- Vary structure with lists and blockquotes naturally.

**Do not revert to passing `paragraph_count` as a literal number.** The AI treats it as a `<p>` tag count and produces thin outlines.

Generated HTML is sanitized through a narrow `wp_kses()` allowlist:

- `p`
- `h2`
- `h3`
- `ul`
- `ol`
- `li`
- `blockquote`
- `strong`
- `em`

If AI text generation fails or returns unusable data, the generator falls back to built-in demo titles/content and records the AI error in the result's `errors` array.

## Image Generation

If `ai_image` is true, `WPDCG_Generator::generate()` calls:

```php
( new WPDCG_AI_Generator() )->generate_featured_image( $post_id, $title, $ai_topic, $post_type, $i );
```

The image prompt asks for a realistic, website-ready, no-text/no-logo landscape image based on the client topic and item title.

Important compatibility note: do not set `using_temperature()` on image prompts. The OpenAI provider's image models do not advertise temperature support, so adding temperature can make the AI Client report:

```text
No models found that support image_generation for this prompt.
```

Image generation uses a longer per-request timeout than text generation. QuickDemo sets `RequestOptions::KEY_TIMEOUT` to `WPDCG_AI_Generator::IMAGE_TIMEOUT` seconds on image prompts only. The current value is 120 seconds. This avoids changing the global WordPress AI Client timeout while giving slow image endpoints enough time to respond.

Current image model preferences are:

```text
gpt-image-1, gpt-image-2, dall-e-3, dall-e-2, imagen-4
```

The AI Client will use a matching preferred model when available. If no preference matches but another compatible image model is available, the underlying AI Client can still fall back to a compatible candidate.

The returned image must be a `File`-like object with `getDataUri()`. QuickDemo accepts PNG, JPEG/JPG, and WebP data URIs, writes the bytes into the uploads directory, creates an attachment, copies QuickDemo tracking meta to the attachment, and sets it as the post thumbnail.

If AI image generation fails and the built-in featured image option was also checked, QuickDemo falls back to its local generated placeholder image.

In the admin UI the placeholder image option remains visible when AI images are enabled. Its copy explains that it is used as a fallback when AI image generation fails.

## WordPress AI Client Method Checks

Many `WP_AI_Client_Prompt_Builder` methods are exposed through `__call()`. Do not rely on `method_exists()` alone for methods such as:

- `is_supported_for_text_generation`
- `is_supported_for_image_generation`
- `using_model_preference`
- `using_temperature`
- `using_max_tokens`
- `as_json_response`

QuickDemo uses `builder_can_call()`:

```php
is_object( $builder ) && ( method_exists( $builder, $method ) || is_callable( array( $builder, $method ) ) )
```

This is required because `method_exists()` returns false for magic methods even though the AI Client can call them.

## Common Failure Modes

### AI Content checkbox stays disabled

Likely causes:

- WordPress AI Client is unavailable.
- No configured Connector supports text generation.
- The support check is using `method_exists()` instead of `is_callable()` for AI Client magic methods.

### Text generation falls back to demo content

Check the admin notice after generation. QuickDemo appends AI errors to the success notice so fallback reasons are visible.

Common causes:

- Connector key is missing or invalid.
- Provider plugin is inactive.
- Strict JSON schema incompatibility.
- AI response is not parseable JSON.

### Image generation says no image model found

Likely causes:

- Image prompt included unsupported options such as temperature.
- The provider plugin/account does not expose `gpt-image-*`, `dall-e-*`, or another image-generation model through the AI Client model registry.
- Connector supports text but not image generation at runtime.

### Image generation times out

Image generation is much slower than text generation. QuickDemo gives each image request a 120 second timeout, but generating many images in a single synchronous admin request can still be unreliable. For best results, generate 1-2 items per run with AI images enabled, or leave AI images unchecked and use the built-in placeholder featured image option.

### Connectors link goes to the wrong URL

Use:

```php
admin_url( 'options-connectors.php' )
```

Do not use:

```php
admin_url( 'options-general.php?page=connectors' )
```

## Verification Checklist

1. Configure OpenAI or another AI provider in `Settings > Connectors`.
2. Reload QuickDemo admin page.
3. Confirm AI Content checkbox is enabled.
4. Generate one post/product with AI Content enabled and AI Image disabled.
5. Confirm title/body content are topic-specific.
6. Generate one post/product with AI Image enabled.
7. Confirm a media attachment is created and set as featured image.
8. If generation falls back, read the admin notice for the exact AI Client error.
