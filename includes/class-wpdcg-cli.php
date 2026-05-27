<?php
/**
 * WP-CLI commands for Loremix Demo Content Generator.
 *
 * Loaded only when WP-CLI is present.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage demo content from the command line.
 */
class WPDCG_CLI {

	/**
	 * Generate demo content.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<type>]
	 * : Post type slug. Default: post
	 *
	 * [--count=<number>]
	 * : Number of items to create (1–500). Default: 5
	 *
	 * [--status=<status>]
	 * : Post status: publish, draft, or pending. Default: publish
	 *
	 * [--paragraphs=<number>]
	 * : Content paragraphs per item (1–8). Default: 3
	 *
	 * [--featured-image]
	 * : Generate a placeholder featured image for each post (requires GD).
	 *
	 * [--product-type=<type>]
	 * : WooCommerce product type when --post_type=product. simple or variable. Default: simple
	 *
	 * [--date-from=<date>]
	 * : Earliest post date (YYYY-MM-DD). Default: today
	 *
	 * [--date-to=<date>]
	 * : Latest post date (YYYY-MM-DD). Default: today
	 *
	 * [--ai-topic=<topic>]
	 * : Generate AI titles/content for this client topic. Requires a configured WordPress AI connector.
	 *
	 * [--ai-tone=<tone>]
	 * : AI content tone. Default: professional
	 *
	 * [--ai-audience=<audience>]
	 * : Optional target audience for AI content.
	 *
	 * [--ai-image]
	 * : Generate topic-based featured images using a configured WordPress AI connector.
	 *
	 * ## EXAMPLES
	 *
	 *     wp loremix generate --count=20 --post_type=post --status=draft
	 *     wp loremix generate --count=5 --featured-image --date-from=2024-01-01 --date-to=2024-12-31
	 *
	 * @when after_wp_load
	 *
	 * @param array $args        Positional args (unused).
	 * @param array $assoc_args  Named args.
	 */
	public function generate( array $args, array $assoc_args ) {
		$post_type      = \WP_CLI\Utils\get_flag_value( $assoc_args, 'post_type', 'post' );
		$count          = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'count', 5 );
		$status         = \WP_CLI\Utils\get_flag_value( $assoc_args, 'status', 'publish' );
		$paragraphs     = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'paragraphs', 3 );
		$feat_image     = isset( $assoc_args['featured-image'] );
		$product_type   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'product-type', '' );
		$date_from      = \WP_CLI\Utils\get_flag_value( $assoc_args, 'date-from', '' );
		$date_to        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'date-to', '' );
		$ai_topic       = \WP_CLI\Utils\get_flag_value( $assoc_args, 'ai-topic', '' );
		$ai_tone        = \WP_CLI\Utils\get_flag_value( $assoc_args, 'ai-tone', 'professional' );
		$ai_audience    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'ai-audience', '' );
		$ai_image       = isset( $assoc_args['ai-image'] );

		if ( ! post_type_exists( $post_type ) ) {
			WP_CLI::error( sprintf( 'Post type "%s" does not exist.', $post_type ) );
		}

		WP_CLI::log( sprintf( 'Generating %d "%s" item(s)…', $count, $post_type ) );

		$result = ( new WPDCG_Generator() )->generate( array(
			'post_type'       => sanitize_key( $post_type ),
			'count'           => $count,
			'status'          => $status,
			'paragraph_count' => $paragraphs,
			'featured_image'  => $feat_image,
			'product_type'    => sanitize_key( $product_type ),
			'date_from'       => sanitize_text_field( $date_from ),
			'date_to'         => sanitize_text_field( $date_to ),
			'ai_enabled'      => '' !== $ai_topic,
			'ai_topic'        => sanitize_text_field( $ai_topic ),
			'ai_tone'         => sanitize_key( $ai_tone ),
			'ai_audience'     => sanitize_text_field( $ai_audience ),
			'ai_image'        => $ai_image,
		) );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$this->report_generation_result( $result, 'item(s)' );
	}

	/**
	 * Generate comments for demo posts.
	 *
	 * ## OPTIONS
	 *
	 * [--attach-to=<target>]
	 * : Target posts: all or latest_batch. Default: all
	 *
	 * [--per-post=<number>]
	 * : Comments per post (1–20). Default: 3
	 *
	 * [--status=<status>]
	 * : Comment status: approve or hold. Default: approve
	 *
	 * [--threaded]
	 * : Generate threaded replies. Enabled by default. Use --no-threaded to disable.
	 *
	 * ## EXAMPLES
	 *
	 *     wp loremix generate-comments --per-post=5
	 *     wp loremix generate-comments --attach-to=latest_batch --status=hold --no-threaded
	 *
	 * @subcommand generate-comments
	 * @when after_wp_load
	 *
	 * @param array $args        Positional args (unused).
	 * @param array $assoc_args  Named args.
	 */
	public function generate_comments( array $args, array $assoc_args ) {
		$attach_to = \WP_CLI\Utils\get_flag_value( $assoc_args, 'attach-to', 'all' );
		$per_post  = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'per-post', 3 );
		$status    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'status', 'approve' );
		$threaded  = (bool) \WP_CLI\Utils\get_flag_value( $assoc_args, 'threaded', true );

		WP_CLI::log( sprintf( 'Generating %d comment(s) per demo post…', $per_post ) );

		$result = ( new WPDCG_Comment_Generator() )->generate(
			array(
				'attach_to' => sanitize_key( $attach_to ),
				'per_post'  => $per_post,
				'status'    => sanitize_key( $status ),
				'threaded'  => $threaded,
			)
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$this->report_generation_result( $result, 'comment(s)' );
	}

	/**
	 * Generate demo WordPress users.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : Number of users to create (1–50). Default: 5
	 *
	 * [--role=<role>]
	 * : WordPress role slug. Default: subscriber
	 *
	 * ## EXAMPLES
	 *
	 *     wp loremix generate-users --count=10 --role=author
	 *
	 * @subcommand generate-users
	 * @when after_wp_load
	 *
	 * @param array $args        Positional args (unused).
	 * @param array $assoc_args  Named args.
	 */
	public function generate_users( array $args, array $assoc_args ) {
		$count = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'count', 5 );
		$role  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'role', 'subscriber' );

		WP_CLI::log( sprintf( 'Generating %d user(s)…', $count ) );

		$result = ( new WPDCG_User_Generator() )->generate(
			array(
				'count' => $count,
				'role'  => sanitize_key( $role ),
			)
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$this->report_generation_result( $result, 'user(s)' );
	}

	/**
	 * Generate WooCommerce product reviews for demo products.
	 *
	 * ## OPTIONS
	 *
	 * [--attach-to=<target>]
	 * : Target products: all or latest_batch. Default: all
	 *
	 * [--per-product=<number>]
	 * : Reviews per product (1–10). Default: 3
	 *
	 * ## EXAMPLES
	 *
	 *     wp loremix generate-reviews --per-product=4
	 *     wp loremix generate-reviews --attach-to=latest_batch
	 *
	 * @subcommand generate-reviews
	 * @when after_wp_load
	 *
	 * @param array $args        Positional args (unused).
	 * @param array $assoc_args  Named args.
	 */
	public function generate_reviews( array $args, array $assoc_args ) {
		$attach_to   = \WP_CLI\Utils\get_flag_value( $assoc_args, 'attach-to', 'all' );
		$per_product = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'per-product', 3 );

		WP_CLI::log( sprintf( 'Generating %d review(s) per demo product…', $per_product ) );

		$result = ( new WPDCG_Woo_Generator() )->generate_reviews(
			array(
				'attach_to'   => sanitize_key( $attach_to ),
				'per_product' => $per_product,
			)
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$this->report_generation_result( $result, 'review(s)' );
	}

	/**
	 * Generate WooCommerce orders.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : Number of orders to create (1–50). Default: 5
	 *
	 * [--status=<status>]
	 * : WooCommerce order status without the wc- prefix. Default: completed
	 *
	 * ## EXAMPLES
	 *
	 *     wp loremix generate-orders --count=10 --status=processing
	 *
	 * @subcommand generate-orders
	 * @when after_wp_load
	 *
	 * @param array $args        Positional args (unused).
	 * @param array $assoc_args  Named args.
	 */
	public function generate_orders( array $args, array $assoc_args ) {
		$count  = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'count', 5 );
		$status = \WP_CLI\Utils\get_flag_value( $assoc_args, 'status', 'completed' );

		WP_CLI::log( sprintf( 'Generating %d WooCommerce order(s)…', $count ) );

		$result = ( new WPDCG_Woo_Generator() )->generate_orders(
			array(
				'count'  => $count,
				'status' => sanitize_key( $status ),
			)
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		$this->report_generation_result( $result, 'order(s)' );
	}

	/**
	 * Prints a standard generation summary and warnings.
	 *
	 * @param array  $result Result returned by a generator.
	 * @param string $label  Human-readable item label.
	 */
	private function report_generation_result( array $result, string $label ) {
		$n = isset( $result['created'] ) ? count( $result['created'] ) : 0;
		WP_CLI::success( sprintf( '%d %s created. Batch ID: %s', $n, $label, $result['batch_id'] ) );

		if ( ! empty( $result['errors'] ) ) {
			foreach ( $result['errors'] as $err ) {
				WP_CLI::warning( $err );
			}
		}
	}

	/**
	 * Delete demo content.
	 *
	 * ## OPTIONS
	 *
	 * [--all]
	 * : Delete all demo content created by this plugin.
	 *
	 * [--batch=<batch_id>]
	 * : Delete a specific batch by its ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp loremix delete --all
	 *     wp loremix delete --batch=batch_20240101_120000_abc123
	 *
	 * @when after_wp_load
	 *
	 * @param array $args        Positional args (unused).
	 * @param array $assoc_args  Named args.
	 */
	public function delete( array $args, array $assoc_args ) {
		$batch_id = \WP_CLI\Utils\get_flag_value( $assoc_args, 'batch', '' );
		$all      = isset( $assoc_args['all'] );

		if ( ! $all && ! $batch_id ) {
			WP_CLI::error( 'Specify --all to delete everything, or --batch=<id> to delete a single batch.' );
		}

		$cleaner = new WPDCG_Cleaner();

		if ( $all ) {
			WP_CLI::confirm( 'This will permanently delete ALL demo content. Continue?' );
			$result  = $cleaner->delete_all();
			$deleted = $result['deleted'];
			WP_CLI::success( sprintf( '%d item(s) permanently deleted.', $deleted ) );
		} else {
			$result  = $cleaner->delete_batch( sanitize_key( $batch_id ) );
			$deleted = $result['deleted'];
			WP_CLI::success( sprintf( '%d item(s) from batch "%s" permanently deleted.', $deleted, $batch_id ) );
		}

		if ( ! empty( $result['errors'] ) ) {
			foreach ( $result['errors'] as $err ) {
				WP_CLI::warning( $err );
			}
		}
	}

	/**
	 * List all recorded demo content batches.
	 *
	 * ## EXAMPLES
	 *
	 *     wp loremix list
	 *
	 * @subcommand list
	 * @when after_wp_load
	 *
	 * @param array $args        Positional args (unused).
	 * @param array $assoc_args  Named args (unused).
	 */
	public function list_batches( array $args, array $assoc_args ) {
		$batches = WPDCG_Tracker::get_batches();

		if ( empty( $batches ) ) {
			WP_CLI::log( 'No demo content batches found.' );
			return;
		}

		$rows = array();
		foreach ( $batches as $batch ) {
			$rows[] = array(
				'Batch ID'  => $batch['id'],
				'Post Type' => $batch['post_type'],
				'Items'     => $batch['count'],
				'Created'   => gmdate( 'Y-m-d H:i:s', $batch['created'] ),
			);
		}

		WP_CLI\Utils\format_items( 'table', $rows, array( 'Batch ID', 'Post Type', 'Items', 'Created' ) );
	}
}
