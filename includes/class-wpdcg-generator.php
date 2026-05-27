<?php
/**
 * Generator class for Loremix Demo Content Generator.
 *
 * Creates demo posts, pages, and CPT entries via wp_insert_post().
 * Every item is stamped with the `_wpdcg_generated` post meta flag and
 * its ID is recorded in WPDCG_Tracker so it can be safely removed later.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_Generator
 */
class WPDCG_Generator {

	/**
	 * Post meta key used to flag all generated content.
	 */
	const META_KEY = '_demo_content_generator_generated';

	/**
	 * Post meta key that stores the unique batch identifier.
	 */
	const BATCH_META_KEY = '_demo_content_generator_batch_id';

	/**
	 * Extra ownership marker used to make destructive cleanup more specific.
	 */
	const SOURCE_META_KEY = '_wpdcg_source';

	/**
	 * Value stored in SOURCE_META_KEY for content created by this plugin.
	 */
	const SOURCE_VALUE = 'loremix-demo-content-generator';

	/**
	 * Term meta key used to flag auto-generated taxonomy terms.
	 */
	const TERM_META_KEY = '_wpdcg_auto_term';

	/**
	 * Maximum number of items that can be generated in one request.
	 */
	const MAX_COUNT = 500;

	/**
	 * Sample titles for post type "post".
	 *
	 * @var string[]
	 */
	private static $post_titles = array(
		'Getting Started with WordPress Development',
		'Top 10 Tips for Building Fast Websites',
		'Understanding Custom Post Types',
		'A Complete Guide to WordPress Hooks',
		'Best Practices for WordPress Security',
		'How to Use the WordPress REST API',
		'Building Responsive Themes with WordPress',
		'Mastering the Block Editor',
		'How to Optimise Images for the Web',
		'The Ultimate Guide to WordPress SEO',
		'Getting Started with Advanced Custom Fields',
		'How to Create a Child Theme',
		'WordPress Performance: A Developer Checklist',
		'Deploying WordPress with CI/CD Pipelines',
		'Working with the WordPress Transients API',
	);

	/**
	 * Sample titles for post type "product".
	 *
	 * @var string[]
	 */
	private static $product_titles = array(
		'Premium Wireless Headphones',
		'Leather Laptop Bag',
		'Minimalist Desk Watch',
		'Mechanical Keyboard TKL',
		'Anti-Fatigue Standing Mat',
		'USB-C Hub 7-in-1',
		'Ergonomic Lumbar Cushion',
		'Smart LED Desk Lamp',
		'Portable Charger 20 000 mAh',
		'Noise-Cancelling Earbuds',
		'Insulated Water Bottle 1 L',
		'Bamboo Desk Organiser',
		'Adjustable Monitor Stand',
		'Webcam HD 1080p',
		'Cable Management Kit',
		'Wireless Charging Pad',
		'Laptop Cooling Stand',
		'Blue-Light Blocking Glasses',
	);

	/**
	 * Sample titles for post type "page".
	 *
	 * @var string[]
	 */
	private static $page_titles = array(
		'About Us',
		'Our Services',
		'Contact Us',
		'Frequently Asked Questions',
		'Our Team',
		'Portfolio',
		'Testimonials',
		'Get a Quote',
		'Privacy Policy',
		'Terms and Conditions',
	);

	/**
	 * Sample content paragraphs — mixed at random to build post body.
	 *
	 * @var string[]
	 */
	private static $content_blocks = array(
		// Plain paragraphs — establish the base reading rhythm.
		'<p>A well-planned website gives visitors a clear path from first impression to confident action. Strong content, thoughtful structure, and consistent visual hierarchy work together to make each page easier to scan, understand, and trust.</p>',
		'<p>Modern teams need digital experiences that are fast, accessible, and easy to maintain. The best projects start with practical goals, then build a content and design system that can grow without becoming difficult to manage.</p>',
		'<p>Good demo content should feel realistic enough to reveal how a theme behaves in everyday use. It needs short paragraphs, longer sections, lists, links, and data-heavy elements so spacing, typography, and responsive layouts can be reviewed properly.</p>',
		'<p>Every page has a job to do. Some pages introduce a brand, some explain services, and others help people compare options before making a decision. Clear writing keeps that job visible and removes unnecessary friction.</p>',
		'<p>Reliable workflows make projects easier to launch and support. When content is organized around real user questions, teams can review pages faster and clients can see how the finished site will work in context.</p>',
		'<p>Professional websites balance polish with practicality. They use concise messaging, predictable navigation, useful supporting details, and a visual rhythm that makes the next step obvious without overwhelming the visitor.</p>',
		'<p>Useful content is specific, structured, and easy to reuse. It explains the value of a service or product, supports the reader with details, and leaves room for designers to test real-world content density across templates.</p>',
		'<p>Strong implementation depends on the small details: readable headings, meaningful button labels, consistent spacing, and content blocks that adapt cleanly across mobile, tablet, and desktop views.</p>',
		// Paragraphs with <strong> and <em> — inline emphasis for styling tests.
		'<p>When approaching <strong>complex challenges</strong>, the most effective strategy is to break the problem into smaller, <em>well-defined pieces</em>. Each piece can be solved, tested, and validated independently before being assembled into the whole. This approach reduces risk and makes progress visible at every step.</p>',
		'<p>Consistency is more powerful than intensity. A focused effort applied <strong>every single day</strong> will produce results that no amount of <em>last-minute effort</em> can replicate. The compounding effect of small, deliberate improvements is one of the most underestimated forces in any field.</p>',
		'<p>Understanding your audience is <em>the foundation of effective communication</em>. Before writing a single word, ask yourself: <strong>who is this for, and what do they need to walk away knowing?</strong> Every decision — tone, structure, vocabulary — should flow from the answer to that question.</p>',
		// Paragraphs with <code> — inline code for developer theme testing.
		'<p>In modern development, tools like <code>git</code> for version control and <code>npm</code> for dependency management are not optional extras — they are <strong>essential infrastructure</strong>. Mastering them early pays dividends for the entire lifetime of a project and saves <em>hours of debugging</em> later.</p>',
		'<p>Functions such as <code>array_map()</code>, <code>array_filter()</code>, and <code>array_reduce()</code> are among the most versatile tools in any PHP developer\'s toolkit. Used well, they replace verbose loops with <em>expressive, readable pipelines</em> that communicate intent at a glance.</p>',
	);

	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Generates demo content items.
	 *
	 * @param array $args {
	 *     @type string $post_type       Post type slug. Default 'post'.
	 *     @type int    $count           Number of items. Default 5. Max 500.
	 *     @type string $status          publish|draft|pending. Default 'publish'.
	 *     @type int    $author_id       Author user ID. 0 = current user.
	 *     @type int    $paragraph_count Paragraphs per post. Default 3.
	 *     @type bool   $excerpt_enabled Whether to generate an excerpt. Default false.
	 *     @type int    $excerpt_length  Word limit for excerpt. Default 30.
	 *     @type bool   $featured_image       Auto-generate a placeholder featured image. Default false.
	 *     @type bool   $content_images       Inject placeholder images into the post content body. Default false.
	 *     @type int    $content_image_count  Number of images to inject into content. 1–3. Default 1.
	 *     @type string $product_type         WooCommerce product type for product posts. simple|variable.
	 *     @type array  $taxonomy_terms       [ taxonomy_slug => [ term_id, … ] ].
	 *     @type string $date_from            Earliest post date (YYYY-MM-DD). Empty = now.
	 *     @type string $date_to              Latest post date (YYYY-MM-DD). Empty = now.
	 *     @type bool   $ai_enabled           Whether to use AI-generated titles/content.
	 *     @type string $ai_topic             Client/topic prompt for AI content.
	 *     @type string $ai_tone              Tone for AI content.
	 *     @type string $ai_audience          Target audience for AI content.
	 *     @type bool   $ai_image             Whether to generate featured/content images with AI.
	 * }
	 * @return array|WP_Error Array with 'created', 'errors', 'batch_id'; or WP_Error.
	 */
	public function generate( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'post_type'       => 'post',
				'count'           => 5,
				'status'          => 'publish',
				'author_id'       => 0,
				'paragraph_count' => 3,
				'excerpt_enabled' => false,
				'excerpt_length'  => 30,
				'featured_image'      => false,
				'content_images'      => false,
				'content_image_count' => 1,
				'product_type'        => '',
				'auto_terms'          => false,
				'taxonomy_terms'      => array(),
				'date_from'           => '',
				'date_to'             => '',
				'ai_enabled'          => false,
				'ai_topic'            => '',
				'ai_tone'             => 'professional',
				'ai_audience'         => '',
				'ai_image'            => false,
			)
		);

		$post_type       = sanitize_key( $args['post_type'] );
		$count           = max( 1, min( absint( $args['count'] ), self::MAX_COUNT ) );
		$status          = in_array( $args['status'], array( 'publish', 'draft', 'pending' ), true ) ? $args['status'] : 'publish';
		$author_id       = absint( $args['author_id'] ) ?: get_current_user_id();
		$paragraph_count = max( 1, absint( $args['paragraph_count'] ) );
		$excerpt_enabled = (bool) $args['excerpt_enabled'];
		$excerpt_length  = max( 1, absint( $args['excerpt_length'] ) );
		$featured_image      = (bool) $args['featured_image'];
		$content_images      = (bool) $args['content_images'];
		$content_image_count = max( 1, min( 3, absint( $args['content_image_count'] ) ) );
		$product_type        = sanitize_key( $args['product_type'] );
		$auto_terms      = (bool) $args['auto_terms'];
		$taxonomy_terms  = is_array( $args['taxonomy_terms'] ) ? $args['taxonomy_terms'] : array();
		$date_from       = sanitize_text_field( $args['date_from'] );
		$date_to         = sanitize_text_field( $args['date_to'] );
		$ai_enabled      = (bool) $args['ai_enabled'];
		$ai_topic        = sanitize_text_field( $args['ai_topic'] );
		$ai_tone         = sanitize_text_field( $args['ai_tone'] );
		$ai_audience     = sanitize_text_field( $args['ai_audience'] );
		$ai_image        = (bool) $args['ai_image'];
		// Pre-compute date range timestamps once (outside the loop).
		$ts_from   = ( $date_from ) ? strtotime( $date_from ) : 0;
		$ts_to     = ( $date_to ) ? strtotime( $date_to ) : 0;
		$use_dates = ( $ts_from && $ts_to );

		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error(
				'wpdcg_invalid_post_type',
				sprintf(
					/* translators: %s: post type slug */
					__( 'Post type "%s" does not exist.', 'loremix-demo-content-generator' ),
					$post_type
				)
			);
		}

		if ( 'product' === $post_type ) {
			$product_type = $this->normalise_product_type( $product_type, $taxonomy_terms );
			unset( $taxonomy_terms['product_type'] );
		}

		// When opted in, create sample terms for any taxonomy the user didn't
		// select manually, and track which ones were auto-populated.
		$auto_tax = $this->maybe_create_terms( $post_type, $auto_terms, $taxonomy_terms );

		$batch_id = 'batch_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( uniqid( '', true ) ), 0, 6 );
		$created  = array();
		$errors   = array();
		$ai_items = array();

		if ( $ai_enabled && '' !== $ai_topic && class_exists( 'WPDCG_AI_Generator' ) ) {
			$ai_result = ( new WPDCG_AI_Generator() )->generate_items(
				array(
					'post_type'       => $post_type,
					'count'           => $count,
					'topic'           => $ai_topic,
					'tone'            => $ai_tone,
					'audience'        => $ai_audience,
					'paragraph_count' => $paragraph_count,
					'excerpt_length'  => $excerpt_length,
				)
			);

			if ( is_wp_error( $ai_result ) ) {
				$errors[] = sprintf(
					/* translators: %s: AI error message */
					__( 'AI content unavailable; used built-in demo content instead. Reason: %s', 'loremix-demo-content-generator' ),
					$ai_result->get_error_message()
				);
			} else {
				$ai_items = $ai_result;
			}
		}

		for ( $i = 1; $i <= $count; $i++ ) {
			$ai_item = $ai_items[ $i - 1 ] ?? array();
			$title   = ! empty( $ai_item['title'] ) ? $ai_item['title'] : $this->get_title( $post_type, $i );
			$content = ! empty( $ai_item['content'] ) ? $ai_item['content'] : $this->get_content( $paragraph_count, $post_type, $title );
			$excerpt = '';
			if ( $excerpt_enabled ) {
				$excerpt = ! empty( $ai_item['excerpt'] ) ? $ai_item['excerpt'] : $this->get_excerpt( $content, $excerpt_length );
			}

			$post_data = array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_excerpt' => $excerpt,
				'post_status'  => $status,
				'post_type'    => $post_type,
				'post_author'  => $author_id,
			);

			if ( $use_dates ) {
				$ts                        = wp_rand( min( $ts_from, $ts_to ), max( $ts_from, $ts_to ) );
				$post_data['post_date']    = gmdate( 'Y-m-d H:i:s', $ts );
				$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $ts );
			}

			$post_id = wp_insert_post( $post_data, true );

			if ( is_wp_error( $post_id ) ) {
				$errors[] = $post_id->get_error_message();
				continue;
			}

			self::stamp_generated_post( $post_id, $batch_id );

			if ( 'product' === $post_type ) {
				$this->inject_product_meta( $post_id, $product_type, $batch_id, $i, $featured_image, $title, $ai_image, $ai_topic );
			}

			if ( $ai_image && '' !== $ai_topic && class_exists( 'WPDCG_AI_Generator' ) ) {
				$image_result = ( new WPDCG_AI_Generator() )->generate_featured_image( $post_id, $title, $ai_topic, $post_type, $i );
				if ( is_wp_error( $image_result ) ) {
					$errors[] = sprintf(
						/* translators: %s: AI image error message */
						__( 'AI image unavailable for "%1$s"; used built-in featured image if enabled. Reason: %2$s', 'loremix-demo-content-generator' ),
						$title,
						$image_result->get_error_message()
					);

					if ( $featured_image ) {
						$this->generate_featured_image( $post_id, $title, $i );
					}
				}
			} elseif ( $featured_image ) {
				$this->generate_featured_image( $post_id, $title, $i );
			}

			if ( 'product' === $post_type && ( $featured_image || ( $ai_image && '' !== $ai_topic ) ) ) {
				$this->generate_product_gallery_images( $post_id, $title, $i, $ai_image && '' !== $ai_topic, $ai_topic, $featured_image );
			}

			// Content body images — only for non-product post types.
			if ( $content_images && 'product' !== $post_type ) {
				$use_ai_ci  = $ai_image && '' !== $ai_topic;
				$ci_att_ids = $this->generate_content_images( $post_id, $title, $content_image_count, $i, $use_ai_ci, $ai_topic );
				if ( ! empty( $ci_att_ids ) ) {
					wp_update_post( array(
						'ID'           => $post_id,
						'post_content' => $this->inject_images_into_content( $content, $ci_att_ids ),
					) );
				}
			}

			foreach ( $taxonomy_terms as $taxonomy => $term_ids ) {
				$taxonomy = sanitize_key( $taxonomy );
				$term_ids = array_map( 'absint', (array) $term_ids );
				if ( ! $taxonomy || empty( $term_ids ) ) {
					continue;
				}
				if ( in_array( $taxonomy, $auto_tax, true ) ) {
					// Auto-created terms: distribute 1–2 randomly for realistic content.
					$shuffled = $term_ids;
					shuffle( $shuffled );
					$assign = array_slice( $shuffled, 0, wp_rand( 1, min( 2, count( $shuffled ) ) ) );
				} else {
					// User-selected terms: assign exactly as chosen.
					$assign = $term_ids;
				}
				wp_set_object_terms( $post_id, $assign, $taxonomy );
			}

			$created[] = $post_id;
		}

		if ( ! empty( $created ) ) {
			WPDCG_Tracker::add_ids( $created );
			WPDCG_Tracker::add_batch( $batch_id, $post_type, $created );
		}

		return array(
			'created'  => $created,
			'errors'   => $errors,
			'batch_id' => $batch_id,
		);
	}

	/**
	 * Resolves the requested WooCommerce product type.
	 *
	 * Product type used to be selectable through the generic taxonomy chips. Keep
	 * a fallback for older presets/forms, but only allow fully generated types.
	 *
	 * @param string $product_type   Explicit product type from the form/CLI.
	 * @param array  $taxonomy_terms Submitted taxonomy term IDs.
	 * @return string
	 */
	private function normalise_product_type( string $product_type, array $taxonomy_terms ): string {
		$allowed = array( 'simple', 'variable' );

		if ( '' === $product_type && ! empty( $taxonomy_terms['product_type'] ) ) {
			$term = get_term( absint( reset( $taxonomy_terms['product_type'] ) ), 'product_type' );
			if ( $term && ! is_wp_error( $term ) ) {
				$product_type = sanitize_key( $term->slug );
			}
		}

		return in_array( $product_type, $allowed, true ) ? $product_type : 'simple';
	}

	/**
	 * Stamps generated posts/attachments/variations with both legacy and
	 * Loremix-specific ownership markers.
	 *
	 * @param int    $post_id  Post, attachment, or variation ID.
	 * @param string $batch_id Batch identifier.
	 */
	public static function stamp_generated_post( int $post_id, string $batch_id = '' ): void {
		update_post_meta( $post_id, self::META_KEY, '1' );
		update_post_meta( $post_id, self::SOURCE_META_KEY, self::SOURCE_VALUE );

		if ( '' !== $batch_id ) {
			update_post_meta( $post_id, self::BATCH_META_KEY, $batch_id );
		}
	}

	/**
	 * When $auto_terms is true, iterates every public taxonomy on $post_type.
	 * For any taxonomy where the user made no manual chip selection, creates a
	 * sample set of terms, stamps newly-created ones with TERM_META_KEY, and
	 * merges the IDs into $taxonomy_terms.
	 *
	 * If a sample term already exists (e.g. "Uncategorized" / "Technology")
	 * its existing ID is reused but it is NOT stamped — only truly new terms
	 * are flagged for cleanup.
	 *
	 * Returns the slugs of taxonomies that were auto-populated so the caller
	 * can use random-distribution assignment for those.
	 *
	 * @param string $post_type      Post type slug.
	 * @param bool   $auto_terms     Whether auto-term creation is opted in.
	 * @param array  $taxonomy_terms Reference to the taxonomy→term_ids map.
	 * @return string[] Slugs of taxonomies that were auto-populated.
	 */
	private function maybe_create_terms( string $post_type, bool $auto_terms, array &$taxonomy_terms ): array {
		if ( ! $auto_terms ) {
			return array();
		}

		$auto_populated = array();
		$taxonomies     = get_object_taxonomies( $post_type, 'objects' );

		foreach ( $taxonomies as $tax_slug => $tax_obj ) {
			if ( ! $tax_obj->public || ! $tax_obj->show_ui ) {
				continue;
			}

			// User made a manual chip selection for this taxonomy — respect it.
			if ( ! empty( $taxonomy_terms[ $tax_slug ] ) ) {
				continue;
			}

			$new_ids = array();
			foreach ( $this->get_sample_term_names( $tax_slug ) as $name ) {
				$result = wp_insert_term( $name, $tax_slug );
				if ( ! is_wp_error( $result ) ) {
					// Freshly created term — stamp it for cleanup.
					$term_id = (int) $result['term_id'];
					add_term_meta( $term_id, self::TERM_META_KEY, '1', true );
					$new_ids[] = $term_id;
				} elseif ( 'term_exists' === $result->get_error_code() ) {
					// Term already exists — reuse its ID, don't stamp it.
					$new_ids[] = (int) $result->get_error_data();
				}
			}

			if ( ! empty( $new_ids ) ) {
				$taxonomy_terms[ $tax_slug ] = $new_ids;
				$auto_populated[]            = $tax_slug;
			}
		}

		return $auto_populated;
	}

	/**
	 * Returns sample term names for a given taxonomy slug.
	 * Falls back to generic names for custom taxonomies.
	 *
	 * @param string $tax_slug Taxonomy slug.
	 * @return string[]
	 */
	private function get_sample_term_names( string $tax_slug ): array {
		$presets = array(
			'category'    => array( 'Technology', 'Business', 'Design', 'Development', 'Marketing' ),
			'post_tag'    => array( 'demo', 'sample', 'tutorial', 'guide', 'loremix' ),
			'product_cat' => array( 'Electronics', 'Clothing', 'Home & Garden', 'Sports', 'Books', 'Beauty', 'Toys' ),
			'product_tag' => array( 'new', 'sale', 'featured', 'bestseller', 'limited', 'eco-friendly' ),
		);

		return $presets[ $tax_slug ] ?? array( 'Demo Term A', 'Demo Term B', 'Demo Term C' );
	}

	/**
	 * Picks a sample title for the given post type and iteration index.
	 * Cycles through the pool; appends a suffix after the first full cycle.
	 *
	 * @param string $post_type Post type slug.
	 * @param int    $index     1-based iteration counter.
	 * @return string
	 */
	private function get_title( string $post_type, int $index ) {
		if ( 'page' === $post_type ) {
			$pool = self::$page_titles;
		} elseif ( 'product' === $post_type ) {
			$pool = self::$product_titles;
		} else {
			$pool = self::$post_titles;
		}
		$size  = count( $pool );
		$title = $pool[ ( $index - 1 ) % $size ];

		if ( $index > $size ) {
			$title .= ' ' . (int) ceil( $index / $size );
		}

		return $title;
	}

	/**
	 * Builds page-style fallback content for common static pages.
	 *
	 * @param string $title Page title.
	 * @param int    $paragraph_count Controls overall length / depth.
	 * @return string
	 */
	private function get_page_content( string $title, int $paragraph_count = 3 ): string {
		$slug = sanitize_title( $title );

		$templates = array(
			'about-us' => array(
				'<p>We help organizations create clear, useful digital experiences that support real goals. Our work brings together strategy, design, content, and practical implementation so every page has a purpose.</p>',
				'<h2>What Guides Our Work</h2>',
				'<p>Every project starts with understanding the people who will use the site. From there, we shape the structure, messaging, and visual system around the actions visitors need to take.</p>',
				"<ul>\n<li>Simple communication that respects the reader's time</li>\n<li>Flexible systems that can grow with the business</li>\n<li>Thoughtful details across content, layout, and performance</li>\n</ul>",
			),
			'our-services' => array(
				'<p>Our services are designed for teams that need a dependable website, stronger content, or a smoother launch process. Each engagement is scoped around clear outcomes and practical next steps.</p>',
				'<h2>Services We Provide</h2>',
				"<ul>\n<li>Website planning and content structure</li>\n<li>Design systems and reusable page layouts</li>\n<li>WordPress implementation and performance review</li>\n<li>Ongoing support for improvements and maintenance</li>\n</ul>",
				'<p>Whether the project is a focused landing page or a complete site refresh, the goal is the same: a finished experience that is easy to manage and clear for visitors.</p>',
			),
			'contact-us' => array(
				'<p>Have a question, project idea, or support request? Send a message with a few details and we will respond with the next practical step.</p>',
				'<h2>How We Can Help</h2>',
				"<ul>\n<li>Discuss a new website or redesign</li>\n<li>Review an existing page or content flow</li>\n<li>Plan a launch, migration, or support schedule</li>\n</ul>",
				'<p>Include your timeline, goals, and any useful links so the first reply can be specific and useful.</p>',
			),
			'frequently-asked-questions' => array(
				'<p>These answers cover the questions visitors often ask before starting a project, booking a service, or making a purchase.</p>',
				'<h2>Common Questions</h2>',
				"<dl>\n<dt>How long does a typical project take?</dt>\n<dd>Most focused projects take a few weeks, while larger builds depend on content, approvals, and integrations.</dd>\n<dt>Can the site be updated later?</dt>\n<dd>Yes. The goal is to create a manageable structure that can grow after launch.</dd>\n</dl>",
				'<p>If a question is not covered here, the contact page is the best place to ask for a detailed answer.</p>',
			),
			'our-team' => array(
				'<p>Our team combines planning, design, development, and support experience. Each role contributes to a smoother process and a stronger final result.</p>',
				'<h2>How We Collaborate</h2>',
				'<p>We keep communication clear, document key decisions, and review each milestone before moving to the next stage.</p>',
				"<ul>\n<li>Strategy and project planning</li>\n<li>Design and content direction</li>\n<li>Development, QA, and launch support</li>\n</ul>",
			),
		);

		$parts = $templates[ $slug ] ?? array(
			'<p>This page is built with realistic placeholder copy so you can review layout, spacing, and content flow before adding final client material.</p>',
			'<h2>Overview</h2>',
			'<p>Use this section to introduce the main topic, explain why it matters, and guide visitors toward the information or action they need next.</p>',
			"<ul>\n<li>Clear page structure for scanning</li>\n<li>Practical details that support decisions</li>\n<li>Flexible content blocks for real-world layouts</li>\n</ul>",
		);

		return implode( "\n\n", array_slice( $parts, 0, max( 2, min( count( $parts ), $paragraph_count + 1 ) ) ) );
	}

	/**
	 * Builds product-specific fallback content instead of generic article copy.
	 *
	 * @param string $title Product title.
	 * @param int    $paragraph_count Controls overall length / depth.
	 * @return string
	 */
	private function get_product_content( string $title, int $paragraph_count = 3 ): string {
		$parts = array(
			sprintf(
				'<p>Built around %s, this demo product combines practical details with a clean, reliable finish. It works well in modern homes, offices, studios, and retail displays where dependable quality matters.</p>',
				esc_html( $title )
			),
			'<h2>Product Highlights</h2>',
			"<ul>\n<li>Durable materials chosen for regular daily use</li>\n<li>Clean design that fits a wide range of spaces and styles</li>\n<li>Easy to compare, configure, and add to a store catalogue</li>\n<li>Suitable for testing product grids, filters, carts, and checkout flows</li>\n</ul>",
			'<h3>Details</h3>',
			'<p>This demo product includes realistic pricing, SKU data, stock status, gallery images, and optional variations so WooCommerce templates can be reviewed with fuller product information.</p>',
			"<table>\n<thead>\n<tr>\n<th>Feature</th>\n<th>Detail</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>Availability</td>\n<td>In stock</td>\n</tr>\n<tr>\n<td>Use case</td>\n<td>Everyday retail demo</td>\n</tr>\n<tr>\n<td>Care</td>\n<td>Wipe clean with a soft dry cloth</td>\n</tr>\n</tbody>\n</table>",
			'<p>Use this product as a realistic placeholder while testing product archives, single-product layouts, related products, and variation selection.</p>',
		);

		return implode( "\n\n", array_slice( $parts, 0, max( 2, min( count( $parts ), $paragraph_count + 2 ) ) ) );
	}

	/**
	 * Builds rich post content containing h2/h3, p, ul, ol, blockquote, and
	 * inline links. Structure scales with $paragraph_count:
	 *  - 1 intro <p>
	 *  - One section (heading + p + optional block) per remaining paragraph
	 *  - A closing <p> with an inline <a> link
	 *
	 * @param int    $paragraph_count Controls overall length / depth.
	 * @param string $post_type       Post type slug.
	 * @param string $title           Generated item title.
	 * @return string HTML string ready for post_content.
	 */
	private function get_content( int $paragraph_count = 3, string $post_type = 'post', string $title = '' ): string {
		if ( 'product' === $post_type ) {
			return $this->get_product_content( $title, $paragraph_count );
		}

		if ( 'page' === $post_type ) {
			return $this->get_page_content( $title, $paragraph_count );
		}

		// ── Paragraph pool ─────────────────────────────────────────────────────
		$paras = self::$content_blocks;
		shuffle( $paras );
		$paras = array_values( $paras );

		// ── Heading pool ───────────────────────────────────────────────────────
		$h2 = array(
			'<h2>Getting Started</h2>',
			'<h2>Key Concepts to Understand</h2>',
			'<h2>Why This Matters</h2>',
			'<h2>Best Practices</h2>',
			'<h2>How It Works</h2>',
			'<h2>Core Principles</h2>',
			'<h2>Taking It Further</h2>',
		);
		$h3 = array(
			'<h3>A Closer Look</h3>',
			'<h3>Important Considerations</h3>',
			'<h3>Practical Tips</h3>',
			'<h3>Common Mistakes to Avoid</h3>',
			'<h3>Quick Summary</h3>',
			'<h3>Worth Knowing</h3>',
		);
		$h4 = array(
			'<h4>A Note on Implementation</h4>',
			'<h4>Things to Keep in Mind</h4>',
			'<h4>Going Deeper</h4>',
			'<h4>The Detail That Matters</h4>',
			'<h4>One More Thing</h4>',
			'<h4>Worth Remembering</h4>',
		);

		// ── Blockquote pool ────────────────────────────────────────────────────
		$quotes = array(
			'<blockquote><p>The best way to predict the future is to invent it. Good design is not just about aesthetics — it is about solving real problems for real people.</p></blockquote>',
			'<blockquote><p>Simplicity is the ultimate sophistication. Every complex problem has a solution that is clear, simple, and deceptively difficult to reach.</p></blockquote>',
			'<blockquote><p>First, solve the problem. Then, write the code. Clean code always looks like it was written by someone who cares deeply about their craft.</p></blockquote>',
			'<blockquote><p>Make it work, make it right, make it fast — in that order. The function of good software is to make the complex appear effortlessly simple.</p></blockquote>',
		);

		// ── Unordered list pool ────────────────────────────────────────────────
		$uls = array(
			"<ul>\n<li>Plan your project structure before writing a single line of code</li>\n<li>Write tests early and run them often throughout development</li>\n<li>Document your decisions as well as your implementations</li>\n<li>Review your own code as if a colleague wrote it</li>\n</ul>",
			"<ul>\n<li>Use meaningful names that reveal the intent behind each variable and function</li>\n<li>Keep functions small and focused on a single, well-defined responsibility</li>\n<li>Prefer composition over inheritance wherever it makes sense</li>\n<li>Refactor continuously — never leave code worse than you found it</li>\n</ul>",
			"<ul>\n<li>Performance optimisation should be driven by measurement, not assumption</li>\n<li>Security is not an afterthought — build it in from the very beginning</li>\n<li>Accessibility benefits every user, not only those with disabilities</li>\n<li>Consistency in style and patterns reduces cognitive load for everyone</li>\n</ul>",
			"<ul>\n<li>Version control every project, no matter how small</li>\n<li>Automate repetitive tasks to reduce human error</li>\n<li>Peer review catches problems that self-review will always miss</li>\n<li>Ship small, ship often, and iterate based on real feedback</li>\n</ul>",
		);

		// ── Ordered list pool ──────────────────────────────────────────────────
		$ols = array(
			"<ol>\n<li>Define the problem clearly before proposing any solution</li>\n<li>Research existing approaches and understand their trade-offs</li>\n<li>Design at a high level before diving into implementation details</li>\n<li>Build incrementally and validate at each milestone</li>\n<li>Gather feedback early and iterate until the goal is fully met</li>\n</ol>",
			"<ol>\n<li>Set up your development environment and initialise version control</li>\n<li>Create a minimal prototype to validate the core idea quickly</li>\n<li>Add features one at a time, testing thoroughly after each addition</li>\n<li>Conduct a full code review before any production release</li>\n<li>Monitor and measure after deployment to catch regressions early</li>\n</ol>",
			"<ol>\n<li>Identify your target audience and their primary needs</li>\n<li>Map out user journeys before designing any interface</li>\n<li>Create low-fidelity wireframes and gather stakeholder sign-off</li>\n<li>Build a high-fidelity prototype and run usability tests</li>\n<li>Refine based on findings and hand off to development</li>\n</ol>",
		);

		// ── Table pool ─────────────────────────────────────────────────────────
		$tables = array(
			"<table>\n<thead>\n<tr>\n<th>Feature</th>\n<th>Basic Plan</th>\n<th>Pro Plan</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>Storage</td>\n<td>5 GB</td>\n<td>100 GB</td>\n</tr>\n<tr>\n<td>Users</td>\n<td>1</td>\n<td>Unlimited</td>\n</tr>\n<tr>\n<td>Support</td>\n<td>Email only</td>\n<td>Priority 24/7</td>\n</tr>\n<tr>\n<td>Custom domain</td>\n<td>No</td>\n<td>Yes</td>\n</tr>\n<tr>\n<td>Analytics</td>\n<td>Basic</td>\n<td>Advanced</td>\n</tr>\n</tbody>\n</table>",
			"<table>\n<thead>\n<tr>\n<th>Approach</th>\n<th>Pros</th>\n<th>Cons</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>Option A</td>\n<td>Fast to implement, low cost</td>\n<td>Limited scalability</td>\n</tr>\n<tr>\n<td>Option B</td>\n<td>Highly scalable, flexible</td>\n<td>Higher upfront investment</td>\n</tr>\n<tr>\n<td>Option C</td>\n<td>Balanced trade-off</td>\n<td>Moderate complexity</td>\n</tr>\n</tbody>\n</table>",
			"<table>\n<thead>\n<tr>\n<th>Metric</th>\n<th>Q1</th>\n<th>Q2</th>\n<th>Q3</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>Visitors</td>\n<td>12,400</td>\n<td>18,750</td>\n<td>24,100</td>\n</tr>\n<tr>\n<td>Conversions</td>\n<td>3.2%</td>\n<td>4.1%</td>\n<td>5.8%</td>\n</tr>\n<tr>\n<td>Revenue</td>\n<td>\$8,200</td>\n<td>\$14,500</td>\n<td>\$21,900</td>\n</tr>\n</tbody>\n</table>",
			"<table>\n<thead>\n<tr>\n<th>Technology</th>\n<th>Use Case</th>\n<th>Learning Curve</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>WordPress</td>\n<td>CMS &amp; websites</td>\n<td>Low</td>\n</tr>\n<tr>\n<td>React</td>\n<td>Interactive UIs</td>\n<td>Medium</td>\n</tr>\n<tr>\n<td>Node.js</td>\n<td>Server-side logic</td>\n<td>Medium</td>\n</tr>\n<tr>\n<td>GraphQL</td>\n<td>Flexible APIs</td>\n<td>High</td>\n</tr>\n</tbody>\n</table>",
		);

		// ── Inline link closing paragraphs ─────────────────────────────────────
		$links = array(
			'<p>For further reading, visit the <a href="https://developer.wordpress.org" target="_blank" rel="noopener noreferrer">WordPress Developer Resources</a> — a comprehensive reference covering every core API.</p>',
			'<p>The <a href="https://wordpress.org/support/" target="_blank" rel="noopener noreferrer">WordPress Support Forums</a> are an excellent place to ask questions and share knowledge with the global community.</p>',
			'<p>Explore <a href="https://make.wordpress.org" target="_blank" rel="noopener noreferrer">Make WordPress</a> to learn how you can contribute to the project and collaborate with thousands of contributors worldwide.</p>',
			'<p>The <a href="https://wordpress.org/plugins/" target="_blank" rel="noopener noreferrer">Plugin Directory</a> hosts tens of thousands of extensions — a great source of inspiration and real-world code examples.</p>',
		);

		// ── Build document ─────────────────────────────────────────────────────
		$parts   = array();
		$used    = 0;
		$max_p   = min( $paragraph_count, count( $paras ) );

		// 1. Intro paragraph.
		$parts[] = $paras[ $used++ ];

		// 2. Sections: heading → paragraph → optional block element.
		$section = 0;
		while ( $used < $max_p ) {
			// Cycle h2 → h3 → h4 for a natural heading hierarchy.
			if ( 0 === $section % 3 ) {
				$parts[] = $h2[ array_rand( $h2 ) ];
			} elseif ( 1 === $section % 3 ) {
				$parts[] = $h3[ array_rand( $h3 ) ];
			} else {
				$parts[] = $h4[ array_rand( $h4 ) ];
			}

			$parts[] = $paras[ $used++ ];

			// On alternating sections inject a block element.
			// Slot order is deterministic so every type is guaranteed to appear
			// before any type repeats: lists → blockquote → table → lists …
			if ( 0 === $section % 2 && $used < $max_p ) {
				$slot = intdiv( $section, 2 ); // 0, 1, 2, 3 …
				switch ( $slot % 4 ) {
					case 0:
						// Lists first — ul or ol chosen randomly.
						$parts[] = wp_rand( 0, 1 ) ? $uls[ array_rand( $uls ) ] : $ols[ array_rand( $ols ) ];
						break;
					case 1:
						$parts[] = $quotes[ array_rand( $quotes ) ];
						break;
					case 2:
						$parts[] = $tables[ array_rand( $tables ) ];
						break;
					default:
						// Second list pass — use the other list type for variety.
						$parts[] = wp_rand( 0, 1 ) ? $ols[ array_rand( $ols ) ] : $uls[ array_rand( $uls ) ];
				}
			}

			$section++;
		}

		// 3. Closing paragraph with an inline link.
		$parts[] = $links[ array_rand( $links ) ];

		return implode( "\n\n", $parts );
	}

	/**
	 * Generates a word-limited plain-text excerpt from HTML post content.
	 *
	 * @param string $content    Raw HTML post content.
	 * @param int    $word_limit Maximum number of words.
	 * @return string
	 */
	private function get_excerpt( string $content, int $word_limit ) {
		$text  = wp_strip_all_tags( $content );
		$words = preg_split( '/\s+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );

		if ( count( $words ) <= $word_limit ) {
			return $text;
		}

		return implode( ' ', array_slice( $words, 0, $word_limit ) ) . "\u{2026}";
	}

	/**
	 * Injects WooCommerce product data required for products to render correctly.
	 * Simple products receive price/SKU/stock data. Variable products receive
	 * local variation attributes plus child product_variation posts.
	 *
	 * @param int    $post_id      Product post ID.
	 * @param string $product_type WooCommerce product type.
	 * @param string $batch_id     Batch ID used to stamp child variations.
	 * @param int    $index        1-based generation index.
	 * @param bool   $images       Whether to generate built-in variation images.
	 * @param string $title        Parent product title.
	 * @param bool   $ai_images    Whether to generate AI variation images.
	 * @param string $ai_topic     AI image topic.
	 */
	private function inject_product_meta( int $post_id, string $product_type = 'simple', string $batch_id = '', int $index = 1, bool $images = false, string $title = '', bool $ai_images = false, string $ai_topic = '' ): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		if (
			'variable' === $product_type
			&& class_exists( 'WC_Product_Variable' )
			&& class_exists( 'WC_Product_Variation' )
			&& class_exists( 'WC_Product_Attribute' )
		) {
			$this->inject_variable_product_meta( $post_id, $batch_id, $index, $images, $title, $ai_images, $ai_topic );
			return;
		}

		$this->inject_simple_product_meta( $post_id );
	}

	/**
	 * Adds complete WooCommerce data for a simple product.
	 *
	 * @param int $post_id Product post ID.
	 */
	private function inject_simple_product_meta( int $post_id ): void {
		// Regular price: $4.99 – $149.99.
		$base  = wp_rand( 5, 150 );
		$price = number_format( $base - 0.01, 2, '.', '' );

		// ~30 % chance of a sale at 60–85 % of the regular price.
		$on_sale    = ( wp_rand( 1, 10 ) <= 3 );
		$sale_price = $on_sale
			? number_format( round( ( $base - 0.01 ) * ( wp_rand( 60, 85 ) / 100 ), 2 ), 2, '.', '' )
			: '';

		$meta = array(
			'_regular_price'         => $price,
			'_sale_price'            => $sale_price,
			'_price'                 => $on_sale ? $sale_price : $price,
			'_sale_price_dates_from' => '',
			'_sale_price_dates_to'   => '',
			'_stock_status'          => 'instock',
			'_manage_stock'          => 'no',
			'_backorders'            => 'no',
			'_virtual'               => 'no',
			'_downloadable'          => 'no',
			'_tax_status'            => 'taxable',
			'_tax_class'             => '',
			'_sku'                   => 'DEMO-' . $post_id,
			'_featured'              => 'no',
			'_weight'                => '',
			'_length'                => '',
			'_width'                 => '',
			'_height'                => '',
			'total_sales'            => '0',
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		wp_set_object_terms( $post_id, 'simple', 'product_type' );

		// Clear WooCommerce caches so the product reflects correctly immediately.
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $post_id );
		}
	}

	/**
	 * Adds attributes and child variations for a variable product.
	 *
	 * @param int    $post_id  Product post ID.
	 * @param string $batch_id Batch ID used to stamp child variations.
	 * @param int    $index    1-based generation index.
	 * @param bool   $images   Whether to generate built-in variation images.
	 * @param string $title    Parent product title.
	 * @param bool   $ai_images Whether to generate AI variation images.
	 * @param string $ai_topic  AI image topic.
	 */
	private function inject_variable_product_meta( int $post_id, string $batch_id, int $index, bool $images, string $title, bool $ai_images, string $ai_topic ): void {
		wp_set_object_terms( $post_id, 'variable', 'product_type' );

		$sets = array(
			array(
				'colors' => array( 'Black', 'Navy', 'Silver' ),
				'sizes'  => array( 'Small', 'Medium', 'Large' ),
			),
			array(
				'colors' => array( 'Natural', 'Charcoal', 'Forest' ),
				'sizes'  => array( 'One Size', 'Travel', 'Studio' ),
			),
			array(
				'colors' => array( 'White', 'Graphite', 'Copper' ),
				'sizes'  => array( 'Compact', 'Standard', 'Extended' ),
			),
		);
		$set    = $sets[ ( $index - 1 ) % count( $sets ) ];
		$colors = $set['colors'];
		$sizes  = $set['sizes'];

		$color_attribute = new WC_Product_Attribute();
		$color_attribute->set_id( 0 );
		$color_attribute->set_name( 'Color' );
		$color_attribute->set_options( $colors );
		$color_attribute->set_position( 0 );
		$color_attribute->set_visible( true );
		$color_attribute->set_variation( true );

		$size_attribute = new WC_Product_Attribute();
		$size_attribute->set_id( 0 );
		$size_attribute->set_name( 'Size' );
		$size_attribute->set_options( $sizes );
		$size_attribute->set_position( 1 );
		$size_attribute->set_visible( true );
		$size_attribute->set_variation( true );

		$product = new WC_Product_Variable( $post_id );
		$product->set_sku( 'DEMO-' . $post_id );
		$product->set_manage_stock( false );
		$product->set_stock_status( 'instock' );
		$product->set_tax_status( 'taxable' );
		$product->set_attributes( array( $color_attribute, $size_attribute ) );
		$product->set_default_attributes(
			array(
				'color' => $colors[0],
				'size'  => $sizes[0],
			)
		);
		$product->save();

		$combinations = array(
			array( $colors[0], $sizes[0] ),
			array( $colors[1], $sizes[1] ),
			array( $colors[2], $sizes[2] ),
			array( $colors[0], $sizes[2] ),
		);

		$base = wp_rand( 20, 180 );
		foreach ( $combinations as $variation_index => $combination ) {
			$regular_price = number_format( ( $base + ( $variation_index * wp_rand( 4, 12 ) ) ) - 0.01, 2, '.', '' );
			$on_sale       = ( wp_rand( 1, 10 ) <= 3 );
			$sale_price    = $on_sale
				? number_format( round( (float) $regular_price * ( wp_rand( 70, 90 ) / 100 ), 2 ), 2, '.', '' )
				: '';

			$variation_id = wp_insert_post(
				array(
					'post_title'  => sprintf( '#%1$d Variation %2$d', $post_id, $variation_index + 1 ),
					'post_name'   => 'product-' . $post_id . '-variation-' . ( $variation_index + 1 ),
					'post_status' => 'publish',
					'post_parent' => $post_id,
					'post_type'   => 'product_variation',
					'menu_order'  => $variation_index,
				),
				true
			);

			if ( is_wp_error( $variation_id ) ) {
				continue;
			}

			$variation_meta = array(
				'attribute_color'         => $combination[0],
				'attribute_size'          => $combination[1],
				'_regular_price'          => $regular_price,
				'_sale_price'             => $sale_price,
				'_price'                  => '' !== $sale_price ? $sale_price : $regular_price,
				'_sale_price_dates_from'  => '',
				'_sale_price_dates_to'    => '',
				'_sku'                    => sprintf( 'DEMO-%d-V%d', $post_id, $variation_index + 1 ),
				'_stock_status'           => 'instock',
				'_manage_stock'           => 'no',
				'_backorders'             => 'no',
				'_virtual'                => 'no',
				'_downloadable'           => 'no',
				'_tax_status'             => 'taxable',
				'_tax_class'              => '',
			);

			foreach ( $variation_meta as $meta_key => $meta_value ) {
				update_post_meta( $variation_id, $meta_key, $meta_value );
			}

			self::stamp_generated_post( absint( $variation_id ), $batch_id );

			if ( $ai_images && '' !== $ai_topic && class_exists( 'WPDCG_AI_Generator' ) ) {
				$image_id = ( new WPDCG_AI_Generator() )->generate_variation_image(
					$post_id,
					$title ?: get_the_title( $post_id ),
					$ai_topic,
					$combination[0],
					$combination[1],
					( $index * 10 ) + $variation_index
				);
				if ( is_wp_error( $image_id ) ) {
					$image_id = false;
				}
			} else {
				$image_id = false;
			}

			if ( ! $image_id && $images ) {
				$image_id = $this->generate_variation_image(
					$post_id,
					absint( $variation_id ),
					sprintf(
						'%1$s - %2$s / %3$s',
						$title ?: get_the_title( $post_id ),
						$combination[0],
						$combination[1]
					),
					( $index * 10 ) + $variation_index
				);
			}

			if ( $image_id ) {
				update_post_meta( $variation_id, '_thumbnail_id', $image_id );
			}

			$this->sync_variation_stock_status( absint( $variation_id ) );
		}

		if ( method_exists( 'WC_Product_Variable', 'sync' ) ) {
			WC_Product_Variable::sync( $post_id );
		}

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $post_id );
		}
	}

	/**
	 * Persists variation stock status through WooCommerce so lookup tables stay
	 * in sync before the variable parent calculates its aggregate stock status.
	 *
	 * @param int $variation_id Variation post ID.
	 */
	private function sync_variation_stock_status( int $variation_id ): void {
		if ( ! function_exists( 'wc_update_product_stock_status' ) ) {
			return;
		}

		if ( class_exists( 'WPDCG_Core' ) && method_exists( 'WPDCG_Core', 'get_instance' ) ) {
			$core = WPDCG_Core::get_instance();
			if ( method_exists( $core, 'remove_incompatible_admin_attribute_filters' ) ) {
				$core->remove_incompatible_admin_attribute_filters();
			}
		}

		wc_update_product_stock_status( $variation_id, 'instock' );
	}

	/**
	 * Generates and assigns a built-in placeholder image for a variation.
	 *
	 * @param int    $parent_id    Parent product ID.
	 * @param int    $variation_id Variation post ID.
	 * @param string $title        Attachment title.
	 * @param int    $index        Image theme rotation index.
	 * @return int|false Attachment ID on success, false on failure.
	 */
	private function generate_variation_image( int $parent_id, int $variation_id, string $title, int $index ) {
		$gd = $this->create_gd_image_file( $index, 'variation-' . $variation_id . '-' . substr( md5( (string) $variation_id ), 0, 6 ) );
		if ( ! $gd ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $gd['url'],
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$gd['filepath'],
			$parent_id
		);

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $gd['filepath'] ) );
		self::stamp_generated_post( absint( $attachment_id ), (string) get_post_meta( $parent_id, self::BATCH_META_KEY, true ) );

		return absint( $attachment_id );
	}

	/**
	 * Generates built-in placeholder images for a WooCommerce product gallery.
	 *
	 * @param int    $post_id Product post ID.
	 * @param string $title   Parent product title.
	 * @param int    $index   1-based product index.
	 * @return int[] Attachment IDs.
	 */
	private function generate_product_gallery_images( int $post_id, string $title, int $index, bool $ai_images = false, string $ai_topic = '', bool $fallback_gd = true ): array {
		$gallery_ids = array();

		for ( $slot = 1; $slot <= 3; $slot++ ) {
			if ( $ai_images && '' !== $ai_topic && class_exists( 'WPDCG_AI_Generator' ) ) {
				$attachment_id = ( new WPDCG_AI_Generator() )->generate_product_gallery_image( $post_id, $title, $ai_topic, $slot, ( $index * 20 ) + $slot );
				if ( ! is_wp_error( $attachment_id ) ) {
					$gallery_ids[] = absint( $attachment_id );
					continue;
				}
			}

			if ( ! $fallback_gd ) {
				continue;
			}

			$gd = $this->create_gd_image_file( ( $index * 20 ) + $slot, 'gallery-' . $post_id . '-' . $slot . '-' . substr( md5( $post_id . '-' . $slot ), 0, 6 ) );
			if ( ! $gd ) {
				continue;
			}

			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attachment_id = wp_insert_attachment(
				array(
					'guid'           => $gd['url'],
					'post_mime_type' => 'image/jpeg',
					'post_title'     => sprintf(
						/* translators: 1: product title, 2: gallery image number */
						__( '%1$s Gallery Image %2$d', 'loremix-demo-content-generator' ),
						$title,
						$slot
					),
					'post_content'   => '',
					'post_status'    => 'inherit',
				),
				$gd['filepath'],
				$post_id
			);

			if ( is_wp_error( $attachment_id ) ) {
				continue;
			}

			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $gd['filepath'] ) );
			self::stamp_generated_post( absint( $attachment_id ), (string) get_post_meta( $post_id, self::BATCH_META_KEY, true ) );

			$gallery_ids[] = absint( $attachment_id );
		}

		if ( ! empty( $gallery_ids ) ) {
			update_post_meta( $post_id, '_product_image_gallery', implode( ',', $gallery_ids ) );
		}

		return $gallery_ids;
	}

	/**
	 * Generates 1–3 images and saves them as attachments parented to the post.
	 * Uses AI when $ai_images is true and an AI connector is configured; falls
	 * back to GD for each slot where AI fails or is not enabled.
	 *
	 * @param int    $post_id    Post ID to parent the attachments to.
	 * @param string $title      Post title used in attachment titles and AI prompts.
	 * @param int    $count      Number of images to generate (1–3).
	 * @param int    $index      1-based loop counter driving colour-theme rotation.
	 * @param bool   $ai_images  Whether to attempt AI generation.
	 * @param string $ai_topic   AI topic/subject string.
	 * @return int[] Array of attachment IDs (may be fewer than $count on partial failure).
	 */
	private function generate_content_images( int $post_id, string $title, int $count, int $index, bool $ai_images, string $ai_topic ): array {
		$attachment_ids = array();

		require_once ABSPATH . 'wp-admin/includes/image.php';

		for ( $slot = 1; $slot <= $count; $slot++ ) {
			// Use a high offset so colour themes differ from the featured/gallery images.
			$img_index = ( $index * 10 ) + $slot + 100;

			// Try AI first if enabled.
			if ( $ai_images && '' !== $ai_topic && class_exists( 'WPDCG_AI_Generator' ) ) {
				$att_id = ( new WPDCG_AI_Generator() )->generate_content_image( $post_id, $title, $ai_topic, $slot, $img_index );
				if ( ! is_wp_error( $att_id ) ) {
					$attachment_ids[] = absint( $att_id );
					continue;
				}
			}

			// GD fallback (or primary when AI is not enabled).
			$slug = 'content-' . $post_id . '-' . $slot . '-' . substr( md5( $post_id . 'ci' . $slot ), 0, 6 );
			$gd   = $this->create_gd_image_file( $img_index, $slug );
			if ( ! $gd ) {
				continue;
			}

			$att_id = wp_insert_attachment(
				array(
					'guid'           => $gd['url'],
					'post_mime_type' => 'image/jpeg',
					'post_title'     => sprintf(
						/* translators: 1: post title, 2: image slot number */
						__( '%1$s — Content Image %2$d', 'loremix-demo-content-generator' ),
						$title,
						$slot
					),
					'post_content'   => '',
					'post_status'    => 'inherit',
				),
				$gd['filepath'],
				$post_id
			);

			if ( is_wp_error( $att_id ) ) {
				continue;
			}

			wp_update_attachment_metadata( absint( $att_id ), wp_generate_attachment_metadata( absint( $att_id ), $gd['filepath'] ) );
			self::stamp_generated_post( absint( $att_id ), (string) get_post_meta( $post_id, self::BATCH_META_KEY, true ) );

			$attachment_ids[] = absint( $att_id );
		}

		return $attachment_ids;
	}

	/**
	 * Splits HTML post content at block-level closing tags and injects a
	 * <figure> image block after each evenly-spaced position.
	 *
	 * @param string $content        Original post content HTML.
	 * @param int[]  $attachment_ids Ordered list of attachment IDs to inject.
	 * @return string Content with <figure> image blocks woven in.
	 */
	private function inject_images_into_content( string $content, array $attachment_ids ): string {
		if ( empty( $attachment_ids ) || '' === trim( $content ) ) {
			return $content;
		}

		// Split at closing block-level tags, keeping each tag as part of its block.
		$parts = preg_split( '/((?:<\/(?:p|h[2-6]|ul|ol|blockquote|figure)>)\s*)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( ! is_array( $parts ) || count( $parts ) < 3 ) {
			// Content has no recognisable block structure — append images at the end.
			return $content . $this->build_content_figures( $attachment_ids );
		}

		// Reassemble into complete blocks: text + closing tag pairs.
		$blocks    = array();
		$pair_end  = count( $parts ) - ( count( $parts ) % 2 === 1 ? 1 : 0 );
		for ( $j = 0; $j + 1 < $pair_end; $j += 2 ) {
			$blocks[] = $parts[ $j ] . $parts[ $j + 1 ];
		}
		$remainder   = ( count( $parts ) % 2 === 1 ) ? end( $parts ) : '';
		$block_count = count( $blocks );
		$img_count   = count( $attachment_ids );

		if ( 0 === $block_count ) {
			return $content . $this->build_content_figures( $attachment_ids );
		}

		// Calculate evenly-spaced insertion positions.
		$interval  = max( 1, (int) floor( $block_count / ( $img_count + 1 ) ) );
		$positions = array();
		for ( $k = 1; $k <= $img_count; $k++ ) {
			$positions[] = min( $interval * $k - 1, $block_count - 1 );
		}
		$positions = array_values( array_unique( $positions ) );

		$result    = '';
		$img_index = 0;
		foreach ( $blocks as $idx => $block ) {
			$result .= $block;
			if ( in_array( $idx, $positions, true ) && isset( $attachment_ids[ $img_index ] ) ) {
				$att_id   = absint( $attachment_ids[ $img_index ] );
				$img_html = wp_get_attachment_image(
					$att_id,
					'large',
					false,
					array(
						'class' => 'wp-image-' . $att_id,
						'alt'   => '',
					)
				);
				if ( $img_html ) {
					$result .= '<figure class="wp-block-image size-large aligncenter wpdcg-content-image">' . $img_html . '</figure>' . "\n";
				}
				++$img_index;
			}
		}

		return $result . $remainder;
	}

	/**
	 * Renders a sequence of <figure> blocks from an array of attachment IDs.
	 * Used as a fallback when content has no parseable block structure.
	 *
	 * @param int[] $attachment_ids Attachment IDs.
	 * @return string HTML string of <figure> elements.
	 */
	private function build_content_figures( array $attachment_ids ): string {
		$html = '';
		foreach ( $attachment_ids as $att_id ) {
			$att_id   = absint( $att_id );
			$img_html = wp_get_attachment_image(
				$att_id,
				'large',
				false,
				array(
					'class' => 'wp-image-' . $att_id,
					'alt'   => '',
				)
			);
			if ( $img_html ) {
				$html .= '<figure class="wp-block-image size-large aligncenter wpdcg-content-image">' . $img_html . '</figure>' . "\n";
			}
		}
		return $html;
	}

	/**
	 * Generates a clean placeholder image via PHP GD, registers it as a
	 * WordPress media attachment, stamps it with the plugin meta flag, and sets
	 * it as the featured image of the given post.
	 *
	 * @param int    $post_id    The post that will own the featured image.
	 * @param string $post_title Used only as the attachment title in the DB.
	 * @param int    $index      1-based loop counter — drives colour theme rotation.
	 * @return int|false  Attachment ID on success, false if GD is unavailable.
	 */
	private function generate_featured_image( int $post_id, string $post_title, int $index = 1 ) {
		$gd = $this->create_gd_image_file( $index, $post_id . '-' . substr( md5( (string) $post_id ), 0, 6 ) );
		if ( ! $gd ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $gd['url'],
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $post_title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$gd['filepath'],
			$post_id
		);

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $gd['filepath'] ) );
		self::stamp_generated_post( absint( $attachment_id ), (string) get_post_meta( $post_id, self::BATCH_META_KEY, true ) );
		set_post_thumbnail( $post_id, $attachment_id );

		return $attachment_id;
	}

	/**
	 * Creates a standalone placeholder image in the Media Library (no parent post).
	 * Used by WPDCG_Media_Generator.
	 *
	 * @param string $title     Attachment title stored in the DB.
	 * @param int    $index     1-based index for colour-theme rotation.
	 * @param string $batch_id  Batch identifier to stamp on the attachment.
	 * @return int|false Attachment ID on success, false on failure.
	 */
	public function generate_standalone_image( string $title, int $index, string $batch_id ) {
		$gd = $this->create_gd_image_file( $index, 'media-' . $index . '-' . substr( md5( $batch_id . $index ), 0, 6 ) );
		if ( ! $gd ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = wp_insert_attachment(
			array(
				'guid'           => $gd['url'],
				'post_mime_type' => 'image/jpeg',
				'post_title'     => $title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$gd['filepath'],
			0  // no parent post
		);

		if ( is_wp_error( $attachment_id ) ) {
			return false;
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $gd['filepath'] ) );
		self::stamp_generated_post( absint( $attachment_id ), $batch_id );

		return absint( $attachment_id );
	}

	/**
	 * Draws the demo placeholder image and saves it to the uploads directory.
	 *
	 * Returns an associative array with 'filepath' and 'url' on success,
	 * or false if GD is unavailable or the write fails.
	 *
	 * Design: vertical gradient background, left + top accent bars, large ghost
	 * index number, decorative ring outlines, semi-opaque bottom band with
	 * "DEMO CONTENT" label. 8 rotating colour themes ensure variety.
	 *
	 * @param int    $index 1-based counter for colour-theme cycling.
	 * @param string $slug  Filename slug (no extension).
	 * @return array{filepath:string,url:string}|false
	 */
	private function create_gd_image_file( int $index, string $slug ) {
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return false;
		}

		// ── Canvas ────────────────────────────────────────────────────────────
		$width  = 1200;
		$height = 630;
		$img    = imagecreatetruecolor( $width, $height );
		imagealphablending( $img, true );

		// ── 8 colour themes: [ bg-top, bg-bottom, accent ] ────────────────────
		$themes = array(
			array( array( 30, 27, 75 ),  array( 10,  9, 30 ),  array( 129, 140, 248 ) ), // indigo
			array( array(  5, 46, 22 ),  array(  2, 22,  8 ),  array(  74, 222, 128 ) ), // green
			array( array( 69, 10, 10 ),  array( 38,  3,  3 ),  array( 252, 129, 129 ) ), // red
			array( array( 69, 26,  3 ),  array( 38, 13,  0 ),  array( 251, 191,  36 ) ), // amber
			array( array( 10, 42, 90 ),  array(  4, 18, 46 ),  array(  96, 165, 250 ) ), // blue
			array( array( 59,  7, 100 ), array( 25,  3, 50 ),  array( 192, 132, 252 ) ), // violet
			array( array(  2, 44, 34 ),  array(  1, 18, 14 ),  array(  52, 211, 153 ) ), // emerald
			array( array( 76,  5, 25 ),  array( 40,  2, 12 ),  array( 251, 113, 133 ) ), // rose
		);
		[ $bg1, $bg2, $ac ] = $themes[ ( $index - 1 ) % count( $themes ) ];

		// ── Gradient background (scan-line loop) ──────────────────────────────
		for ( $y = 0; $y < $height; $y++ ) {
			$t = $y / ( $height - 1 );
			imageline(
				$img, 0, $y, $width - 1, $y,
				imagecolorallocate(
					$img,
					(int) round( $bg1[0] + ( $bg2[0] - $bg1[0] ) * $t ),
					(int) round( $bg1[1] + ( $bg2[1] - $bg1[1] ) * $t ),
					(int) round( $bg1[2] + ( $bg2[2] - $bg1[2] ) * $t )
				)
			);
		}

		// ── Decorative ring outlines ──────────────────────────────────────────
		$rc1 = imagecolorallocatealpha( $img, $ac[0], $ac[1], $ac[2], 90 );
		for ( $d = 340; $d <= 460; $d += 40 ) {
			imageellipse( $img, $width - 10, -10, $d, $d, $rc1 );
		}
		$rc2 = imagecolorallocatealpha( $img, $ac[0], $ac[1], $ac[2], 100 );
		for ( $d = 180; $d <= 260; $d += 40 ) {
			imageellipse( $img, 10, $height + 10, $d, $d, $rc2 );
		}

		// ── Accent bars (left + top) — top bar always visible in any square crop ─
		$bar_c = imagecolorallocate( $img, $ac[0], $ac[1], $ac[2] );
		imagefilledrectangle( $img, 0, 0, 13, $height, $bar_c );  // left
		imagefilledrectangle( $img, 0, 0, $width, 10, $bar_c );   // top

		// ── Ghost number (01, 02 … from batch loop index) ─────────────────────
		$number    = str_pad( (string) $index, 2, '0', STR_PAD_LEFT );
		$font_path = $this->find_ttf_font();
		$num_c     = imagecolorallocatealpha( $img, $ac[0], $ac[1], $ac[2], 70 );
		$band_h    = 72;
		$body_h    = $height - $band_h;

		if ( $font_path && function_exists( 'imagettftext' ) ) {
			$num_sz = 220;
			$box    = imagettfbbox( $num_sz, 0, $font_path, $number );
			$nw     = abs( $box[4] - $box[0] );
			$nh     = abs( $box[5] - $box[1] );
			imagettftext(
				$img, $num_sz, 0,
				(int) ( ( $width - $nw ) / 2 ),
				(int) ( ( $body_h + $nh ) / 2 ),
				$num_c, $font_path, $number
			);
		} else {
			$font  = 5;
			$fw    = imagefontwidth( $font );
			$fh    = imagefontheight( $font );
			imagestring(
				$img, $font,
				(int) ( ( $width - strlen( $number ) * $fw ) / 2 ),
				(int) ( ( $body_h - $fh ) / 2 ),
				$number, $num_c
			);
		}

		// ── Bottom band ───────────────────────────────────────────────────────
		$band_c = imagecolorallocatealpha( $img, 0, 0, 0, 45 );
		imagefilledrectangle( $img, 0, $height - $band_h, $width, $height, $band_c );

		$white_c = imagecolorallocate( $img, 255, 255, 255 );
		$label   = 'DEMO CONTENT';

		if ( $font_path && function_exists( 'imagettftext' ) ) {
			// Center the label + dot so it always falls in the square-crop safe zone.
			$lb      = imagettfbbox( 15, 0, $font_path, $label );
			$lw      = abs( $lb[2] - $lb[0] );
			$dot_gap = 18; // dot width (10) + gap (8)
			$start_x = (int) ( ( $width - $dot_gap - $lw ) / 2 );
			$ty      = $height - $band_h + (int) ( $band_h / 2 ) + 7;
			// Accent dot centred with text baseline.
			imagefilledrectangle( $img, $start_x, $ty - 10, $start_x + 10, $ty, $bar_c );
			imagettftext( $img, 15, 0, $start_x + $dot_gap, $ty, $white_c, $font_path, $label );
		} else {
			$f4  = 4;
			$fw4 = imagefontwidth( $f4 );
			$fh4 = imagefontheight( $f4 );
			$lw  = strlen( $label ) * $fw4;
			$tx  = (int) ( ( $width - $lw ) / 2 );
			$ty  = $height - $band_h + (int) ( ( $band_h - $fh4 ) / 2 );
			imagestring( $img, $f4, $tx, $ty, $label, $white_c );
		}

		// ── Save to uploads ───────────────────────────────────────────────────
		$upload   = wp_upload_dir();
		$filename = wp_unique_filename( $upload['path'], 'wpdcg-' . $slug . '.jpg' );
		$filepath = trailingslashit( $upload['path'] ) . $filename;
		imagejpeg( $img, $filepath, 92 );
		imagedestroy( $img );

		if ( ! file_exists( $filepath ) ) {
			return false;
		}

		return array(
			'filepath' => $filepath,
			'url'      => trailingslashit( $upload['url'] ) . $filename,
		);
	}

	/**
	 * Locates a TrueType font file to use for image text rendering.
	 *
	 * Priority:
	 *  1. Inter Bold bundled with this plugin (assets/fonts/Inter-Bold.ttf) —
	 *     guaranteed to exist if the plugin was installed correctly.
	 *  2. System fonts: macOS supplemental directory, then Linux, then Windows.
	 *
	 * @return string Absolute path to a .ttf file, or '' if nothing found.
	 */
	private function find_ttf_font(): string {
		// 1. Bundled font — always preferred (Inter Bold, SIL OFL licence).
		$bundled = dirname( __FILE__ ) . '/../assets/fonts/Inter-Bold.ttf';
		if ( file_exists( $bundled ) ) {
			return realpath( $bundled );
		}

		// 2. System fonts — ordered by likelihood of availability.
		$candidates = array(
			// macOS — supplemental fonts (confirmed present on macOS 12+).
			'/System/Library/Fonts/Supplemental/Arial Bold.ttf',
			'/System/Library/Fonts/Supplemental/Verdana Bold.ttf',
			'/System/Library/Fonts/Supplemental/Trebuchet MS Bold.ttf',
			'/System/Library/Fonts/Supplemental/DIN Alternate Bold.ttf',
			'/System/Library/Fonts/Supplemental/Georgia Bold.ttf',
			'/System/Library/Fonts/Supplemental/Tahoma Bold.ttf',
			'/System/Library/Fonts/Supplemental/Arial.ttf',
			// macOS — /Library/Fonts (e.g. installed by MS Office).
			'/Library/Fonts/Arial Bold.ttf',
			'/Library/Fonts/Arial.ttf',
			// Linux — DejaVu (present on almost every distro).
			'/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
			'/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
			'/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
			'/usr/share/fonts/liberation/LiberationSans-Bold.ttf',
			'/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
			// Windows.
			'C:\\Windows\\Fonts\\arialbd.ttf',
			'C:\\Windows\\Fonts\\arial.ttf',
		);

		foreach ( $candidates as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		return '';
	}

}
