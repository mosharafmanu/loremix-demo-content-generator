<?php
/**
 * Core class for QuickDemo Content Generator.
 *
 * Bootstraps the plugin: instantiates dependencies and registers all hooks.
 * Uses the singleton pattern to ensure only one instance is ever created.
 *
 * @package QuickDemo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_Core
 */
class WPDCG_Core {

	/**
	 * Singleton instance.
	 *
	 * @var WPDCG_Core|null
	 */
	private static $instance = null;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return WPDCG_Core
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — private to enforce singleton.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Loads context-appropriate dependencies.
	 */
	private function load_dependencies() {
		if ( is_admin() ) {
			require_once WPDCG_PATH . 'admin/class-wpdcg-admin.php';
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once WPDCG_PATH . 'includes/class-wpdcg-cli.php';
		}
	}

	/**
	 * Registers WordPress hooks.
	 */
	private function init_hooks() {
		if ( is_admin() ) {
			new WPDCG_Admin();
			add_action( 'admin_init', array( $this, 'remove_incompatible_admin_attribute_filters' ), PHP_INT_MAX );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'quickdemo', 'WPDCG_CLI' );
		}
	}

	/**
	 * Removes known frontend-only attribute label filters that break wp-admin.
	 *
	 * The Attribute Thumbnail for WooCommerce plugin registers a
	 * woocommerce_attribute_label callback with an array type-hint for the third
	 * argument, but WooCommerce passes a WC_Product object while loading product
	 * attributes/variations in wp-admin. Its own is_admin() guard cannot run
	 * because PHP raises the TypeError first. Removing that frontend decoration
	 * in admin keeps generated variable products editable.
	 */
	public function remove_incompatible_admin_attribute_filters(): void {
		global $wp_filter;

		if ( empty( $wp_filter['woocommerce_attribute_label'] ) || ! is_a( $wp_filter['woocommerce_attribute_label'], 'WP_Hook' ) ) {
			return;
		}

		foreach ( $wp_filter['woocommerce_attribute_label']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'] ?? null;
				if (
					is_array( $function )
					&& isset( $function[0], $function[1] )
					&& is_object( $function[0] )
					&& 'WcAttributeThumbnail\\AttributeFrontend' === get_class( $function[0] )
					&& 'prepend_attribute_image' === $function[1]
				) {
					remove_filter( 'woocommerce_attribute_label', $function, (int) $priority );
				}
			}
		}
	}

	/**
	 * Prevent cloning of the singleton instance.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialising of the singleton instance.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton.' );
	}
}
