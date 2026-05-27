<?php
/**
 * Uninstall Loremix Demo Content Generator
 *
 * Runs automatically when the plugin is deleted via the WordPress admin.
 * Removes all options stored by the plugin. Generated content (posts, comments,
 * users, orders) is intentionally left in place unless the user has already
 * deleted it via the plugin UI — respecting the principle of least surprise.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpdcg_version' );
delete_option( 'wpdcg_generated_ids' );
delete_option( 'wpdcg_batches' );
delete_option( 'wpdcg_comment_ids' );
delete_option( 'wpdcg_user_ids' );
delete_option( 'wpdcg_order_ids' );
delete_option( 'wpdcg_menu_ids' );
delete_option( 'wpdcg_presets' );
delete_option( 'wpdcg_settings' );
