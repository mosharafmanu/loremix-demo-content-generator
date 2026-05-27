<?php
/**
 * Navigation menu generator for Loremix Demo Content Generator.
 *
 * Creates a realistic demo WordPress navigation menu with custom-URL items
 * and optional nested child items. Menus are tracked so they can be
 * individually or bulk-deleted later.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_Menu_Generator
 */
class WPDCG_Menu_Generator {

	/** wp_options key for tracked menu term IDs. */
	const MENU_OPTION_KEY = 'wpdcg_menu_ids';

	/** Term meta key used to flag generated menus for cleanup. */
	const MENU_META_KEY = '_wpdcg_generated';

	/** @var string[] Pool of demo menu names. */
	private static $menu_names = array(
		'Main Navigation', 'Primary Menu', 'Header Menu', 'Site Navigation',
		'Top Menu', 'Footer Links', 'Secondary Menu', 'Quick Links',
	);

	/** @var string[] Pool of top-level nav item labels. */
	private static $top_items = array(
		'Home', 'About', 'Services', 'Portfolio', 'Blog', 'Shop', 'Team',
		'Contact', 'Pricing', 'FAQ', 'Resources', 'Testimonials', 'Gallery',
	);

	/** @var string[] Pool of child nav item labels. */
	private static $child_items = array(
		'Our Story', 'Mission & Vision', 'Meet the Team', 'Web Design',
		'Development', 'Consulting', 'Case Studies', 'Recent Work',
		'Latest Posts', 'Get a Quote', 'Support', 'Documentation',
		'Product Catalogue', 'Delivery Info', 'Returns Policy', 'Privacy Policy',
	);

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Generates a demo navigation menu.
	 *
	 * @param array $args {
	 *   @type string $name          Menu name. Auto-generated from pool if empty.
	 *   @type int    $item_count    Number of top-level items (3–12). Default 5.
	 *   @type bool   $with_children Add child items under the first two parents. Default true.
	 * }
	 * @return array { created: int[], menu_name: string, batch_id: string, errors: string[] }
	 */
	public function generate( array $args = array() ): array {
		$name          = sanitize_text_field( $args['name'] ?? '' );
		$item_count    = max( 3, min( absint( $args['item_count'] ?? 5 ), 12 ) );
		$with_children = (bool) ( $args['with_children'] ?? true );

		if ( '' === $name ) {
			$name = self::$menu_names[ array_rand( self::$menu_names ) ];
		}

		// Append a counter to guarantee uniqueness.
		$base   = $name;
		$suffix = 2;
		while ( is_nav_menu( $name ) ) {
			$name = $base . ' ' . $suffix++;
		}

		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return array(
				'created'   => array(),
				'menu_name' => $name,
				'batch_id'  => '',
				'errors'    => array( $menu_id->get_error_message() ),
			);
		}

		update_term_meta( $menu_id, self::MENU_META_KEY, '1' );

		// Select and shuffle top-level items.
		$top_pool = self::$top_items;
		shuffle( $top_pool );
		$top_pool = array_slice( $top_pool, 0, $item_count );

		$top_ids = array();
		foreach ( $top_pool as $label ) {
			$item_id = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $label,
					'menu-item-url'    => home_url( '/' . sanitize_title( $label ) . '/' ),
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				)
			);
			if ( ! is_wp_error( $item_id ) ) {
				$top_ids[] = absint( $item_id );
			}
		}

		// Add child items to the first two top-level parents.
		if ( $with_children && count( $top_ids ) >= 2 ) {
			$child_pool = self::$child_items;
			shuffle( $child_pool );
			$child_pool = array_slice( $child_pool, 0, 8 );

			foreach ( array_slice( $top_ids, 0, 2 ) as $idx => $parent_id ) {
				$children = array_slice( $child_pool, $idx * 4, 4 );
				foreach ( $children as $child_label ) {
					wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-title'     => $child_label,
							'menu-item-url'       => home_url( '/' . sanitize_title( $child_label ) . '/' ),
							'menu-item-status'    => 'publish',
							'menu-item-type'      => 'custom',
							'menu-item-parent-id' => $parent_id,
						)
					);
				}
			}
		}

		$batch_id   = 'batch_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( uniqid( '', true ) ), 0, 6 );
		$menu_ids   = self::get_menu_ids();
		$menu_ids[] = $menu_id;
		update_option( self::MENU_OPTION_KEY, array_values( array_unique( $menu_ids ) ), false );

		WPDCG_Tracker::add_batch( $batch_id, '_menu', array( $menu_id ) );

		return array(
			'created'   => array( $menu_id ),
			'menu_name' => $name,
			'batch_id'  => $batch_id,
			'errors'    => array(),
		);
	}

	// ── Static helpers ────────────────────────────────────────────────────────

	public static function get_menu_ids(): array {
		$ids = get_option( self::MENU_OPTION_KEY, array() );
		return is_array( $ids ) ? array_map( 'absint', $ids ) : array();
	}

	public static function count_menus(): int {
		return count( self::get_menu_ids() );
	}

	/**
	 * Deletes all tracked demo menus and clears the option.
	 *
	 * @return int Number of menus deleted.
	 */
	public static function delete_all_menus(): int {
		$ids     = self::get_menu_ids();
		$deleted = 0;
		foreach ( $ids as $menu_id ) {
			$result = wp_delete_nav_menu( absint( $menu_id ) );
			if ( ! is_wp_error( $result ) && false !== $result ) {
				$deleted++;
			}
		}
		delete_option( self::MENU_OPTION_KEY );
		return $deleted;
	}

	/**
	 * Deletes the menu(s) belonging to a specific batch.
	 *
	 * @param string $batch_id Batch identifier.
	 * @return int Number of menus deleted.
	 */
	public static function delete_menu_batch( string $batch_id ): int {
		$menu_ids = WPDCG_Tracker::get_batch_ids( $batch_id );
		$deleted  = 0;

		foreach ( $menu_ids as $menu_id ) {
			$result = wp_delete_nav_menu( absint( $menu_id ) );
			if ( ! is_wp_error( $result ) && false !== $result ) {
				$deleted++;
			}
		}

		$remaining = array_values( array_diff( self::get_menu_ids(), array_map( 'absint', $menu_ids ) ) );
		update_option( self::MENU_OPTION_KEY, $remaining, false );
		WPDCG_Tracker::remove_batch( $batch_id );

		return $deleted;
	}
}
