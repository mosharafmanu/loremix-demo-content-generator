<?php
/**
 * WooCommerce Generator for Loremix Demo Content Generator.
 *
 * Generates demo WooCommerce reviews and orders.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_Woo_Generator
 */
class WPDCG_Woo_Generator {

	const REVIEW_META_KEY = '_wpdcg_generated';
	const ORDER_META_KEY  = '_wpdcg_generated';
	const BATCH_META_KEY  = '_wpdcg_batch_id';

	private static $review_texts = array(
		5 => array(
			'Excellent quality and exactly as described. The finish feels premium and the order arrived sooner than expected.',
			'Very happy with this purchase. It looks great, works well, and feels dependable for daily use.',
			'Five stars. The product matched the photos, the packaging was clean, and setup was simple.',
			'This was a strong value for the price. I would feel comfortable ordering it again.',
			'Well made and thoughtfully presented. It fits the space perfectly and feels built to last.',
		),
		4 => array(
			'Good quality overall. There was a small packaging mark, but the product itself works as expected.',
			'Very solid for the price. The details are useful and the product feels reliable.',
			'Arrived quickly and matched the description. I would recommend it with only minor reservations.',
			'Good purchase. The design is clean and the product does what I needed it to do.',
		),
		3 => array(
			'Decent product for basic use. It works, but I expected a little more refinement in the details.',
			'Average experience overall. The product is usable, though the description made it sound slightly more polished.',
			'The item is fine, but delivery took longer than expected and the packaging could be better.',
		),
		2 => array(
			'Not quite what I expected. It works, but the quality feels lower than similar products I have purchased.',
			'Usable, but I would compare alternatives before buying again. The value is not quite there for me.',
		),
		1 => array(
			'Disappointed with the quality. The item did not match the description closely enough.',
			'The product arrived damaged and was not usable out of the box. I am hoping support can resolve it.',
		),
	);

	private static $reviewer_names = array(
		'Alex Morgan', 'Sam Taylor', 'Jordan Lee', 'Casey Wilson', 'Riley Parker',
		'Morgan Davis', 'Jamie Roberts', 'Quinn Anderson', 'Avery Thompson', 'Blake Harris',
		'Drew Martinez', 'Peyton Clark', 'Skyler Lewis', 'Reese Walker', 'Logan Young',
		'Taylor Reed', 'Dana Foster', 'Jesse Kim', 'Cameron Stone', 'Rowan Ellis',
		'Harper Mitchell', 'Emery Turner', 'Finley Collins', 'River Baker', 'Sage Rivera',
	);

	private static $domains = array( 'gmail.com', 'yahoo.com', 'outlook.com', 'example.com', 'hotmail.com' );

	// ── Product IDs cache ─────────────────────────────────────────────────────

	private function get_product_ids( string $attach_to ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}

		if ( 'latest_batch' === $attach_to ) {
			foreach ( WPDCG_Tracker::get_batches() as $batch ) {
				if ( isset( $batch['post_type'] ) && 'product' === $batch['post_type'] ) {
					return isset( $batch['ids'] ) ? array_map( 'absint', (array) $batch['ids'] ) : array();
				}
			}
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => WPDCG_Generator::META_KEY,
						'value' => '1',
					),
				),
			)
		);

		return array_map( 'absint', $query->posts );
	}

	// ── Reviews ───────────────────────────────────────────────────────────────

	/**
	 * Generates demo WooCommerce product reviews.
	 *
	 * @param array $args {
	 *   @type string $attach_to  'all' | 'latest_batch'. Default 'all'.
	 *   @type int    $per_product Reviews per product (1–10). Default 3.
	 * }
	 * @return array|WP_Error
	 */
	public function generate_reviews( array $args = array() ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return new WP_Error( 'wpdcg_no_woo', __( 'WooCommerce is not active.', 'loremix-demo-content-generator' ) );
		}

		$args = wp_parse_args(
			$args,
			array(
				'attach_to'   => 'all',
				'per_product' => 3,
			)
		);

		$per_product = max( 1, min( absint( $args['per_product'] ), 10 ) );
		$product_ids = $this->get_product_ids( $args['attach_to'] );

		if ( empty( $product_ids ) ) {
			return new WP_Error(
				'wpdcg_no_products',
				__( 'No demo products found to attach reviews to. Generate some products first.', 'loremix-demo-content-generator' )
			);
		}

		$batch_id = 'batch_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( uniqid( '', true ) ), 0, 6 );
		$created  = array();
		$errors   = array();

		foreach ( $product_ids as $product_id ) {
			for ( $i = 0; $i < $per_product; $i++ ) {
				$rating   = wp_rand( 1, 5 );
				$texts    = self::$review_texts[ $rating ];
				$text     = $texts[ array_rand( $texts ) ];
				$name     = self::$reviewer_names[ array_rand( self::$reviewer_names ) ];
				$slug     = strtolower( str_replace( ' ', '.', $name ) );
				$email    = $slug . '@' . self::$domains[ array_rand( self::$domains ) ];

				$comment_id = wp_insert_comment(
					array(
						'comment_post_ID'      => $product_id,
						'comment_author'       => $name,
						'comment_author_email' => $email,
						'comment_author_url'   => '',
						'comment_content'      => $text,
						'comment_type'         => 'review',
						'comment_parent'       => 0,
						'comment_approved'     => 1,
						'comment_date'         => current_time( 'mysql' ),
						'comment_date_gmt'     => current_time( 'mysql', true ),
					)
				);

				if ( $comment_id && ! is_wp_error( $comment_id ) ) {
					$comment_id = absint( $comment_id );
					add_comment_meta( $comment_id, 'rating', $rating, true );
					add_comment_meta( $comment_id, 'verified', 0, true );
					add_comment_meta( $comment_id, self::REVIEW_META_KEY, '1', true );
					add_comment_meta( $comment_id, self::BATCH_META_KEY, $batch_id, true );
					add_comment_meta( $comment_id, WPDCG_Generator::SOURCE_META_KEY, WPDCG_Generator::SOURCE_VALUE, true );
					$created[] = $comment_id;

					// Refresh product rating cache.
					WC_Comments::get_rating_counts_for_product( wc_get_product( $product_id ) );
				} else {
					$errors[] = sprintf( 'Failed to create review for product %d.', $product_id );
				}
			}

			// Recalculate product rating.
			$this->recalculate_product_rating( $product_id );
		}

		if ( ! empty( $created ) ) {
			WPDCG_Tracker::add_comment_ids( $created );
			WPDCG_Tracker::add_batch( $batch_id, '_wc_review', $created );
		}

		return array(
			'created'  => $created,
			'errors'   => $errors,
			'batch_id' => $batch_id,
		);
	}

	private function recalculate_product_rating( int $product_id ): void {
		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return;
		}
		$rating_count = $product->get_rating_count();
		$average      = $product->get_average_rating();
		update_post_meta( $product_id, '_wc_review_count', $rating_count );
		update_post_meta( $product_id, '_wc_average_rating', $average );
		wc_delete_product_transients( $product_id );
	}

	// ── Orders ────────────────────────────────────────────────────────────────

	/**
	 * Generates demo WooCommerce orders.
	 *
	 * @param array $args {
	 *   @type int    $count   Number of orders (1–50). Default 5.
	 *   @type string $status  WC order status (without 'wc-' prefix). Default 'completed'.
	 * }
	 * @return array|WP_Error
	 */
	public function generate_orders( array $args = array() ) {
		if ( ! function_exists( 'wc_create_order' ) ) {
			return new WP_Error( 'wpdcg_no_woo', __( 'WooCommerce is not active.', 'loremix-demo-content-generator' ) );
		}

		$args = wp_parse_args(
			$args,
			array(
				'count'  => 5,
				'status' => 'completed',
			)
		);

		$count  = max( 1, min( absint( $args['count'] ), 50 ) );
		$status = sanitize_key( $args['status'] );

		$valid_statuses = array_keys( wc_get_order_statuses() );
		$valid_statuses = array_map( function( $s ) { return 0 === strpos( $s, 'wc-' ) ? substr( $s, 3 ) : $s; }, $valid_statuses );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			$status = 'completed';
		}

		$product_ids = $this->get_product_ids( 'all' );

		$batch_id = 'batch_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( uniqid( '', true ) ), 0, 6 );
		$created  = array();
		$errors   = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$order = $this->create_single_order( $product_ids, $status, $batch_id );
			if ( is_wp_error( $order ) ) {
				$errors[] = $order->get_error_message();
			} else {
				$created[] = $order->get_id();
			}
		}

		if ( ! empty( $created ) ) {
			WPDCG_Tracker::add_order_ids( $created );
			WPDCG_Tracker::add_batch( $batch_id, '_wc_order', $created );
		}

		return array(
			'created'  => $created,
			'errors'   => $errors,
			'batch_id' => $batch_id,
		);
	}

	private function create_single_order( array $product_ids, string $status, string $batch_id ) {
		$first   = array( 'Alex', 'Sam', 'Jordan', 'Casey', 'Riley', 'Morgan', 'Jamie', 'Quinn' )[ wp_rand( 0, 7 ) ];
		$last    = array( 'Morgan', 'Taylor', 'Lee', 'Wilson', 'Parker', 'Davis', 'Roberts', 'Anderson' )[ wp_rand( 0, 7 ) ];
		$domain  = self::$domains[ array_rand( self::$domains ) ];
		$email   = strtolower( $first . '.' . $last ) . wp_rand( 10, 99 ) . '@' . $domain;

		$streets = array( '123 Main St', '456 Oak Ave', '789 Pine Rd', '321 Elm St', '654 Maple Dr' );
		$cities  = array( 'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix' );
		$states  = array( 'NY', 'CA', 'IL', 'TX', 'AZ' );
		$zips    = array( '10001', '90001', '60601', '77001', '85001' );
		$idx     = wp_rand( 0, 4 );

		$order = wc_create_order();
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		// Billing address.
		$order->set_billing_first_name( $first );
		$order->set_billing_last_name( $last );
		$order->set_billing_email( $email );
		$order->set_billing_phone( '555-' . wp_rand( 100, 999 ) . '-' . wp_rand( 1000, 9999 ) );
		$order->set_billing_address_1( $streets[ $idx ] );
		$order->set_billing_city( $cities[ $idx ] );
		$order->set_billing_state( $states[ $idx ] );
		$order->set_billing_postcode( $zips[ $idx ] );
		$order->set_billing_country( 'US' );

		// Shipping = billing.
		$order->set_shipping_first_name( $first );
		$order->set_shipping_last_name( $last );
		$order->set_shipping_address_1( $streets[ $idx ] );
		$order->set_shipping_city( $cities[ $idx ] );
		$order->set_shipping_state( $states[ $idx ] );
		$order->set_shipping_postcode( $zips[ $idx ] );
		$order->set_shipping_country( 'US' );

		// Add products to order.
		$item_count = wp_rand( 1, 3 );
		if ( ! empty( $product_ids ) ) {
			$selected = (array) array_rand( array_flip( $product_ids ), min( $item_count, count( $product_ids ) ) );
			foreach ( $selected as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$order->add_product( $product, wp_rand( 1, 3 ) );
				}
			}
		} else {
			// No demo products — use a placeholder line item.
			$item = new WC_Order_Item_Product();
			$item->set_name( 'Demo Product' );
			$item->set_quantity( 1 );
			$item->set_subtotal( wp_rand( 10, 200 ) );
			$item->set_total( $item->get_subtotal() );
			$order->add_item( $item );
		}

		$order->calculate_totals();
		$order->set_status( $status );
		$order->set_payment_method( 'bacs' );
		$order->set_payment_method_title( 'Direct Bank Transfer' );
		$order->set_date_created( time() - wp_rand( 0, 30 * DAY_IN_SECONDS ) );

		$order->update_meta_data( self::ORDER_META_KEY, '1' );
		$order->update_meta_data( self::BATCH_META_KEY, $batch_id );
		$order->update_meta_data( WPDCG_Generator::SOURCE_META_KEY, WPDCG_Generator::SOURCE_VALUE );
		$order->save();

		return $order;
	}
}
