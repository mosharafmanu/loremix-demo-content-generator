<?php
/**
 * Optional WordPress AI Client powered generator.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_AI_Generator
 */
class WPDCG_AI_Generator {

	const DEFAULT_TEXT_MODELS  = 'claude-opus-4-7, claude-sonnet-4-6, gpt-4o, gemini-1.5-pro';
	const DEFAULT_IMAGE_MODELS = 'gpt-image-1, gpt-image-2, dall-e-3, dall-e-2, imagen-4';
	const IMAGE_TIMEOUT        = 120.0;
	const MAX_IMAGE_BYTES      = 10485760; // 10 MB.

	/**
	 * Whether the WordPress AI Client is available.
	 */
	public static function is_ai_client_available(): bool {
		return function_exists( 'wp_ai_client_prompt' );
	}

	/**
	 * Whether text generation is supported by a configured connector.
	 */
	public static function supports_text_generation(): bool {
		if ( ! self::is_ai_client_available() ) {
			return false;
		}

		$builder = wp_ai_client_prompt( 'Loremix support check.' );
		return is_object( $builder )
			&& self::builder_can_call( $builder, 'is_supported_for_text_generation' )
			&& $builder->is_supported_for_text_generation();
	}

	/**
	 * Whether image generation is supported by a configured connector.
	 */
	public static function supports_image_generation(): bool {
		if ( ! self::is_ai_client_available() ) {
			return false;
		}

		$builder = ( new self() )->apply_image_preferences( wp_ai_client_prompt( 'Loremix support check.' ) );
		return is_object( $builder )
			&& self::builder_can_call( $builder, 'is_supported_for_image_generation' )
			&& $builder->is_supported_for_image_generation();
	}

	/**
	 * Returns registered AI provider connectors for display.
	 */
	public static function get_ai_connectors(): array {
		if ( ! function_exists( 'wp_get_connectors' ) ) {
			return array();
		}

		$providers = array();
		foreach ( wp_get_connectors() as $id => $connector ) {
			if ( isset( $connector['type'] ) && 'ai_provider' === $connector['type'] ) {
				$providers[ $id ] = $connector;
			}
		}

		return $providers;
	}

	/**
	 * Generates topic-specific post/product drafts through the WordPress AI Client.
	 *
	 * @param array $args Generation context.
	 * @return array|WP_Error
	 */
	public function generate_items( array $args ) {
		if ( ! self::supports_text_generation() ) {
			return new WP_Error(
				'wpdcg_ai_text_unavailable',
				__( 'No WordPress AI connector is configured for text generation.', 'loremix-demo-content-generator' )
			);
		}

		$count           = max( 1, min( absint( $args['count'] ?? 1 ), 20 ) );
		$post_type       = sanitize_key( $args['post_type'] ?? 'post' );
		$topic           = sanitize_text_field( $args['topic'] ?? '' );
		$tone            = sanitize_text_field( $args['tone'] ?? 'professional' );
		$audience        = sanitize_text_field( $args['audience'] ?? '' );
		$paragraph_count = max( 1, min( absint( $args['paragraph_count'] ?? 3 ), 8 ) );
		$excerpt_length  = max( 12, min( absint( $args['excerpt_length'] ?? 30 ), 120 ) );

		if ( '' === $topic ) {
			return new WP_Error( 'wpdcg_ai_missing_topic', __( 'Client topic is required for AI content.', 'loremix-demo-content-generator' ) );
		}

		$schema = array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'items' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'additionalProperties' => false,
						'properties'           => array(
							'title'   => array( 'type' => 'string' ),
							'excerpt' => array( 'type' => 'string' ),
							'content' => array( 'type' => 'string' ),
						),
						'required'   => array( 'title', 'excerpt', 'content' ),
					),
				),
			),
			'required' => array( 'items' ),
		);

		$builder = wp_ai_client_prompt( $this->build_prompt( $post_type, $topic, $tone, $audience, $count, $paragraph_count, $excerpt_length ) );
		$builder = $this->apply_text_preferences( $builder );

		if ( self::builder_can_call( $builder, 'using_temperature' ) ) {
			$builder = $builder->using_temperature( 0.7 );
		}
		if ( self::builder_can_call( $builder, 'using_max_tokens' ) ) {
			$builder = $builder->using_max_tokens( min( 12000, 900 + ( $count * $paragraph_count * 220 ) ) );
		}
		if ( self::builder_can_call( $builder, 'as_json_response' ) ) {
			$builder = $builder->as_json_response( $schema );
		}

		$text = $builder->generate_text();
		if ( is_wp_error( $text ) ) {
			return $text;
		}

		$json = $this->extract_json( (string) $text );
		if ( ! is_array( $json ) || empty( $json['items'] ) || ! is_array( $json['items'] ) ) {
			return new WP_Error( 'wpdcg_ai_bad_response', __( 'AI response could not be parsed.', 'loremix-demo-content-generator' ) );
		}

		$items = array();
		foreach ( $json['items'] as $item ) {
			if ( count( $items ) >= $count || ! is_array( $item ) ) {
				break;
			}

			$title   = sanitize_text_field( $item['title'] ?? '' );
			$content = $this->sanitize_content( $item['content'] ?? '' );
			$excerpt = sanitize_text_field( $item['excerpt'] ?? '' );

			if ( '' === $title || '' === $content ) {
				continue;
			}

			$items[] = array(
				'title'   => $title,
				'content' => $content,
				'excerpt' => $excerpt,
			);
		}

		if ( empty( $items ) ) {
			return new WP_Error( 'wpdcg_ai_empty_response', __( 'AI returned no usable content.', 'loremix-demo-content-generator' ) );
		}

		return $items;
	}

	/**
	 * Generates an AI image and saves it directly to the Media Library (no parent post).
	 *
	 * @return int|WP_Error Attachment ID or error.
	 */
	public function generate_standalone_image( string $topic, string $title, int $index, string $batch_id ) {
		if ( ! self::supports_image_generation() ) {
			return new WP_Error(
				'wpdcg_ai_image_unavailable',
				__( 'No WordPress AI connector is configured for image generation.', 'loremix-demo-content-generator' )
			);
		}

		$prompt = sprintf(
			'Create a clean, high-quality, website-ready image for: %1$s. Style: modern editorial photography or professional illustration, landscape composition, no text, no watermark, no logos.',
			$topic ?: $title
		);

		$builder = wp_ai_client_prompt( $prompt );
		$builder = $this->apply_image_preferences( $builder );
		$builder = $this->apply_image_request_options( $builder );

		$image_file = $builder->generate_image();
		if ( is_wp_error( $image_file ) ) {
			return $image_file;
		}
		if ( ! is_object( $image_file ) || ! method_exists( $image_file, 'getDataUri' ) ) {
			return new WP_Error( 'wpdcg_ai_image_bad_response', __( 'AI image response was not usable.', 'loremix-demo-content-generator' ) );
		}

		return $this->save_data_uri_as_attachment( $image_file->getDataUri(), 0, $title, $index, $batch_id, false );
	}

	/**
	 * Generates an AI image and attaches it to a post.
	 *
	 * @return int|WP_Error Attachment ID or error.
	 */
	public function generate_featured_image( int $post_id, string $title, string $topic, string $post_type, int $index = 1 ) {
		if ( ! self::supports_image_generation() ) {
			return new WP_Error(
				'wpdcg_ai_image_unavailable',
				__( 'No WordPress AI connector is configured for image generation.', 'loremix-demo-content-generator' )
			);
		}

		$prompt = sprintf(
			'Create a clean, realistic, website-ready featured image for %1$s. Client topic: %2$s. Item title: %3$s. Style: modern editorial photography, high quality, no text, no watermark, no logos, landscape composition.',
			'product' === $post_type ? 'a WooCommerce product' : 'a WordPress article',
			$topic,
			$title
		);

		$builder = wp_ai_client_prompt( $prompt );
		$builder = $this->apply_image_preferences( $builder );
		$builder = $this->apply_image_request_options( $builder );

		$image_file = $builder->generate_image();
		if ( is_wp_error( $image_file ) ) {
			return $image_file;
		}
		if ( ! is_object( $image_file ) || ! method_exists( $image_file, 'getDataUri' ) ) {
			return new WP_Error( 'wpdcg_ai_image_bad_response', __( 'AI image response was not usable.', 'loremix-demo-content-generator' ) );
		}

		return $this->save_data_uri_as_attachment( $image_file->getDataUri(), $post_id, $title, $index, '', true );
	}

	/**
	 * Generates an AI image for a product gallery slot.
	 *
	 * @return int|WP_Error Attachment ID or error.
	 */
	public function generate_product_gallery_image( int $post_id, string $title, string $topic, int $slot, int $index = 1 ) {
		$prompt = sprintf(
			'Create a clean, realistic WooCommerce product gallery image. Client topic: %1$s. Product title: %2$s. Gallery angle/detail number: %3$d. Show a distinct product view, detail, lifestyle use, or packaging variation. Style: modern ecommerce photography, high quality, no text, no watermark, no logos, landscape composition.',
			$topic,
			$title,
			$slot
		);

		return $this->generate_attached_image_from_prompt(
			$post_id,
			sprintf(
				/* translators: 1: product title, 2: gallery image number */
				__( '%1$s Gallery Image %2$d', 'loremix-demo-content-generator' ),
				$title,
				$slot
			),
			$prompt,
			$index,
			false
		);
	}

	/**
	 * Generates an AI image for a product variation thumbnail.
	 *
	 * @return int|WP_Error Attachment ID or error.
	 */
	public function generate_variation_image( int $post_id, string $title, string $topic, string $color, string $size, int $index = 1 ) {
		$prompt = sprintf(
			'Create a clean, realistic WooCommerce product variation image. Client topic: %1$s. Product title: %2$s. Variation attributes: Color %3$s, Size %4$s. Show the same product clearly with this variation represented. Style: modern ecommerce photography, high quality, no text, no watermark, no logos, landscape composition.',
			$topic,
			$title,
			$color,
			$size
		);

		return $this->generate_attached_image_from_prompt(
			$post_id,
			sprintf(
				'%1$s - %2$s / %3$s',
				$title,
				$color,
				$size
			),
			$prompt,
			$index,
			false
		);
	}

	/**
	 * Generates an AI image from an explicit prompt and attaches it to a post.
	 *
	 * @return int|WP_Error Attachment ID or error.
	 */
	private function generate_attached_image_from_prompt( int $post_id, string $title, string $prompt, int $index, bool $set_thumbnail = false ) {
		if ( ! self::supports_image_generation() ) {
			return new WP_Error(
				'wpdcg_ai_image_unavailable',
				__( 'No WordPress AI connector is configured for image generation.', 'loremix-demo-content-generator' )
			);
		}

		$builder = wp_ai_client_prompt( $prompt );
		$builder = $this->apply_image_preferences( $builder );
		$builder = $this->apply_image_request_options( $builder );

		$image_file = $builder->generate_image();
		if ( is_wp_error( $image_file ) ) {
			return $image_file;
		}
		if ( ! is_object( $image_file ) || ! method_exists( $image_file, 'getDataUri' ) ) {
			return new WP_Error( 'wpdcg_ai_image_bad_response', __( 'AI image response was not usable.', 'loremix-demo-content-generator' ) );
		}

		return $this->save_data_uri_as_attachment( $image_file->getDataUri(), $post_id, $title, $index, '', $set_thumbnail );
	}

	/**
	 * Builds a constrained prompt that asks for JSON only.
	 */
	private function build_prompt( string $post_type, string $topic, string $tone, string $audience, int $count, int $paragraph_count, int $excerpt_length ): string {
		$type_label    = 'product' === $post_type ? 'WooCommerce product descriptions' : 'WordPress posts or pages';
		$audience_line = $audience ? 'Audience: ' . $audience . "\n" : '';
		$target_words  = $paragraph_count * 100;

		return sprintf(
			"Create %d realistic demo %s for a client site.\nTopic: %s\nTone: %s\n%sTarget content length: ~%d words per item.\nExcerpt length: about %d words.\n\nReturn only valid JSON with this shape:\n{\"items\":[{\"title\":\"...\",\"excerpt\":\"...\",\"content\":\"<p>...</p><h2>...</h2><p>...</p><p>...</p><ul><li>...</li></ul>\"}]}\n\nRules:\n- Content must be specific to the topic, not generic placeholder text.\n- Every paragraph must be substantive — at least 60 words. Never follow a heading with a single short sentence and move on.\n- Write 2–3 paragraphs under each heading wherever the topic warrants depth.\n- Vary the structure naturally: mix paragraphs, unordered lists, ordered lists, and blockquotes.\n- Use safe HTML only: p, h2, h3, h4, ul, ol, li, blockquote, strong, em, code, table, thead, tbody, tr, th, td, a (with href and rel).\n- Do not include Markdown fences, explanations, scripts, forms, iframes, or external links.\n- Keep titles natural and distinct across items.\n- For products, write buyer-focused copy with specific features, practical benefits, and sensory detail.",
			$count,
			$type_label,
			$topic,
			$tone,
			$audience_line,
			$target_words,
			$excerpt_length
		);
	}

	/**
	 * Applies configured model preferences for text.
	 */
	private function apply_text_preferences( $builder ) {
		return $this->apply_model_preferences( $builder, self::DEFAULT_TEXT_MODELS );
	}

	/**
	 * Applies configured model preferences for images.
	 */
	private function apply_image_preferences( $builder ) {
		return $this->apply_model_preferences( $builder, self::DEFAULT_IMAGE_MODELS );
	}

	/**
	 * Applies a longer request timeout for slow image-generation calls.
	 */
	private function apply_image_request_options( $builder ) {
		if ( ! self::builder_can_call( $builder, 'using_request_options' ) || ! class_exists( '\WordPress\AiClient\Providers\Http\DTO\RequestOptions' ) ) {
			return $builder;
		}

		$options = \WordPress\AiClient\Providers\Http\DTO\RequestOptions::fromArray(
			array(
				\WordPress\AiClient\Providers\Http\DTO\RequestOptions::KEY_TIMEOUT => self::IMAGE_TIMEOUT,
			)
		);

		return $builder->using_request_options( $options );
	}

	/**
	 * Applies model preferences if the WP AI Client supports it.
	 */
	private function apply_model_preferences( $builder, string $fallback ) {
		if ( ! self::builder_can_call( $builder, 'using_model_preference' ) ) {
			return $builder;
		}

		$models = array_filter( array_map( 'trim', explode( ',', $fallback ) ) );
		if ( empty( $models ) ) {
			return $builder;
		}

		return call_user_func_array( array( $builder, 'using_model_preference' ), $models );
	}

	/**
	 * Checks real and magic methods on the WordPress AI prompt builder.
	 */
	private static function builder_can_call( $builder, string $method ): bool {
		return is_object( $builder ) && ( method_exists( $builder, $method ) || is_callable( array( $builder, $method ) ) );
	}

	/**
	 * Extracts a JSON object from model text.
	 */
	private function extract_json( string $text ) {
		$text = trim( $text );
		$text = preg_replace( '/^```(?:json)?\s*/i', '', $text );
		$text = preg_replace( '/\s*```$/', '', $text );

		$decoded = json_decode( $text, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}

		$start = strpos( $text, '{' );
		$end   = strrpos( $text, '}' );
		if ( false === $start || false === $end || $end <= $start ) {
			return null;
		}

		return json_decode( substr( $text, $start, $end - $start + 1 ), true );
	}

	/**
	 * Allows a narrow set of post-content HTML.
	 */
	private function sanitize_content( $content ): string {
		$allowed = array(
			'p'          => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'ul'         => array(),
			'ol'         => array(),
			'li'         => array(),
			'blockquote' => array(),
			'strong'     => array(),
			'em'         => array(),
			'code'       => array(),
			'table'      => array(),
			'thead'      => array(),
			'tbody'      => array(),
			'tr'         => array(),
			'th'         => array( 'scope' => array() ),
			'td'         => array( 'colspan' => array(), 'rowspan' => array() ),
			'a'          => array( 'href' => array(), 'target' => array(), 'rel' => array() ),
		);

		return wp_kses( (string) $content, $allowed );
	}

	/**
	 * Saves a data URI returned by the AI Client into the Media Library.
	 *
	 * When $post_id is 0 the attachment is standalone (no parent post, no thumbnail).
	 * Pass $batch_id directly for standalone images; for post-attached images the
	 * batch ID is read from the parent post's meta.
	 */
	private function save_data_uri_as_attachment( string $data_uri, int $post_id, string $title, int $index, string $batch_id = '', bool $set_thumbnail = true ) {
		if ( ! preg_match( '#^data:(image/(png|jpeg|jpg|webp));base64,(.+)$#', $data_uri, $matches ) ) {
			return new WP_Error( 'wpdcg_ai_image_invalid_data', __( 'AI image data was not a supported image type.', 'loremix-demo-content-generator' ) );
		}

		$bytes = base64_decode( $matches[3], true );

		if ( false === $bytes ) {
			return new WP_Error( 'wpdcg_ai_image_decode_failed', __( 'AI image data could not be decoded.', 'loremix-demo-content-generator' ) );
		}

		if ( strlen( $bytes ) > self::MAX_IMAGE_BYTES ) {
			return new WP_Error( 'wpdcg_ai_image_too_large', __( 'AI image data exceeded the 10 MB safety limit.', 'loremix-demo-content-generator' ) );
		}

		$image_info = @getimagesizefromstring( $bytes ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false === $image_info || empty( $image_info['mime'] ) ) {
			return new WP_Error( 'wpdcg_ai_image_invalid_file', __( 'AI image data was not a valid image file.', 'loremix-demo-content-generator' ) );
		}

		$allowed_mimes = array(
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/webp' => 'webp',
		);
		$mime          = strtolower( (string) $image_info['mime'] );
		if ( ! isset( $allowed_mimes[ $mime ] ) ) {
			return new WP_Error( 'wpdcg_ai_image_invalid_mime', __( 'AI image file type is not allowed.', 'loremix-demo-content-generator' ) );
		}
		$extension = $allowed_mimes[ $mime ];

		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'wpdcg_upload_failed', $upload['error'] );
		}

		$slug     = $post_id > 0 ? $post_id : 'media';
		$filename = wp_unique_filename(
			$upload['path'],
			sanitize_file_name( 'loremix-ai-' . $slug . '-' . $index . '.' . $extension )
		);
		$filepath = trailingslashit( $upload['path'] ) . $filename;

		if ( false === file_put_contents( $filepath, $bytes ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return new WP_Error( 'wpdcg_ai_image_write_failed', __( 'AI image could not be saved.', 'loremix-demo-content-generator' ) );
		}

		$file_check = wp_check_filetype_and_ext(
			$filepath,
			$filename,
			array(
				'jpg|jpeg' => 'image/jpeg',
				'png'      => 'image/png',
				'webp'     => 'image/webp',
			)
		);
		if ( empty( $file_check['type'] ) || $file_check['type'] !== $mime ) {
			wp_delete_file( $filepath );
			return new WP_Error( 'wpdcg_ai_image_file_check_failed', __( 'AI image failed WordPress file validation.', 'loremix-demo-content-generator' ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $mime,
				'post_title'     => $title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$filepath,
			$post_id
		);

		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $filepath );
			return $attachment_id;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $filepath );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		$resolved_batch = $batch_id ?: get_post_meta( $post_id, WPDCG_Generator::BATCH_META_KEY, true );
		WPDCG_Generator::stamp_generated_post( absint( $attachment_id ), (string) $resolved_batch );

		if ( $post_id > 0 && $set_thumbnail ) {
			set_post_thumbnail( $post_id, $attachment_id );
		}

		return $attachment_id;
	}
}
