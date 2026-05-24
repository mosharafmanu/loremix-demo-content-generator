<?php
/**
 * Plugin Name: QuickDemo Content Generator
 * Plugin URI:  https://github.com/mosharafmanu/wp-quickdemo-content-generator
 * Description: Generate demo posts, products, comments, users, and WooCommerce orders — then safely delete only what was generated.
 * Version:     1.0.0
 * Author:      Mosharaf Hossain
 * Author URI:  https://mosharafmanu.com/
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: quickdemo-content-generator
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Constants ───────────────────────────────────────────────────────────────
define( 'WPDCG_VERSION',  '1.0.0' );
define( 'WPDCG_FILE',     __FILE__ );
define( 'WPDCG_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WPDCG_URL',      plugin_dir_url( __FILE__ ) );
define( 'WPDCG_BASENAME', plugin_basename( __FILE__ ) );

// ─── Includes ────────────────────────────────────────────────────────────────
require_once WPDCG_PATH . 'includes/class-wpdcg-tracker.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-presets.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-ai-generator.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-generator.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-comment-generator.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-user-generator.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-woo-generator.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-media-generator.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-menu-generator.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-cleaner.php';
require_once WPDCG_PATH . 'includes/class-wpdcg-core.php';

// ─── Bootstrap ───────────────────────────────────────────────────────────────
/**
 * Initialises the plugin after all plugins are loaded.
 */
function wpdcg_init() {
	WPDCG_Core::get_instance();
}
add_action( 'plugins_loaded', 'wpdcg_init' );

// ─── Activation / Deactivation ───────────────────────────────────────────────
register_activation_hook( __FILE__, 'wpdcg_activate' );
register_deactivation_hook( __FILE__, 'wpdcg_deactivate' );

/**
 * Runs on plugin activation.
 * Sets a version flag in the database for future upgrade routines.
 */
function wpdcg_activate() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	update_option( 'wpdcg_version', WPDCG_VERSION );
}

/**
 * Runs on plugin deactivation.
 * Does NOT delete generated content — that is an explicit user action.
 */
function wpdcg_deactivate() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	// Intentionally left minimal — content cleanup is handled via the admin UI.
}
