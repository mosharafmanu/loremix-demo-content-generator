<?php
/**
 * Admin class for Loremix Demo Content Generator.
 *
 * Handles all WordPress admin interactions:
 * - Registering the admin menu page
 * - Processing nonce-verified form submissions (generate / delete / delete-batch)
 * - AJAX generation endpoint for progress-bar UX
 * - Preset save/load/delete via AJAX
 * - Passing transient notices back to the view
 * - Enqueueing admin assets
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_Admin
 */
class WPDCG_Admin {

	/** @var string Capability required to use this plugin. */
	const CAPABILITY = 'manage_options';

	/** @var string Admin menu/page slug. */
	const MENU_SLUG = 'loremix-demo-content-generator';

	/** @var int Maximum preset payload size in bytes. */
	const MAX_PRESET_PAYLOAD_BYTES = 20000;

	/** @var int Maximum preset name length. */
	const MAX_PRESET_NAME_LENGTH = 80;

	/**
	 * Registers all admin-area hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu',                               array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts',                    array( $this, 'enqueue_assets' ) );

		// Standard form-based handlers (work without JS).
		add_action( 'admin_post_wpdcg_generate',                array( $this, 'handle_generate' ) );
		add_action( 'admin_post_wpdcg_generate_comments',       array( $this, 'handle_generate_comments' ) );
		add_action( 'admin_post_wpdcg_generate_users',          array( $this, 'handle_generate_users' ) );
		add_action( 'admin_post_wpdcg_generate_woo_reviews',    array( $this, 'handle_generate_woo_reviews' ) );
		add_action( 'admin_post_wpdcg_generate_woo_orders',     array( $this, 'handle_generate_woo_orders' ) );
		add_action( 'admin_post_wpdcg_generate_media',          array( $this, 'handle_generate_media' ) );
		add_action( 'admin_post_wpdcg_generate_menu',           array( $this, 'handle_generate_menu' ) );
		add_action( 'admin_post_wpdcg_delete',                  array( $this, 'handle_delete' ) );
		add_action( 'admin_post_wpdcg_delete_batch',            array( $this, 'handle_delete_batch' ) );

		// AJAX handler — single endpoint dispatches by wpdcg_sub_action.
		add_action( 'wp_ajax_wpdcg_ajax_generate',              array( $this, 'handle_ajax_generate' ) );

		// Preset AJAX endpoints.
		add_action( 'wp_ajax_wpdcg_preset_save',                array( $this, 'handle_preset_save' ) );
		add_action( 'wp_ajax_wpdcg_preset_delete',              array( $this, 'handle_preset_delete' ) );

		add_filter( 'plugin_action_links_' . WPDCG_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Adds a "Loremix" link to the plugin row on the Plugins page.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function add_action_links( array $links ): array {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->page_url() ),
			esc_html__( 'Loremix', 'loremix-demo-content-generator' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Registers the plugin as a top-level admin menu item.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Loremix Demo Content Generator', 'loremix-demo-content-generator' ),
			__( 'Loremix', 'loremix-demo-content-generator' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-database',
			58
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Generate Posts & Pages', 'loremix-demo-content-generator' ),
			__( 'Posts & Pages', 'loremix-demo-content-generator' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Generate Comments', 'loremix-demo-content-generator' ),
			__( 'Comments', 'loremix-demo-content-generator' ),
			self::CAPABILITY,
			self::MENU_SLUG . '&tab=comments',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Generate Users', 'loremix-demo-content-generator' ),
			__( 'Users', 'loremix-demo-content-generator' ),
			self::CAPABILITY,
			self::MENU_SLUG . '&tab=users',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Generate WooCommerce Data', 'loremix-demo-content-generator' ),
			__( 'WooCommerce', 'loremix-demo-content-generator' ),
			self::CAPABILITY,
			self::MENU_SLUG . '&tab=woocommerce',
			array( $this, 'render_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Extras', 'loremix-demo-content-generator' ),
			__( 'Extras', 'loremix-demo-content-generator' ),
			self::CAPABILITY,
			self::MENU_SLUG . '&tab=extras',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Enqueues admin assets on the plugin page only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( string $hook ) {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpdcg-admin',
			WPDCG_URL . 'admin/css/wpdcg-admin.css',
			array(),
			WPDCG_VERSION
		);

		wp_enqueue_script(
			'wpdcg-admin',
			WPDCG_URL . 'admin/js/wpdcg-admin.js',
			array( 'jquery' ),
			WPDCG_VERSION,
			true
		);

		// Determine current tab for preset data.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'posts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		wp_localize_script(
			'wpdcg-admin',
			'wpdcgAdmin',
			array(
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'ajaxNonce'          => wp_create_nonce( 'wpdcg_ajax_generate' ),
				'presetNonce'        => wp_create_nonce( 'wpdcg_preset_action' ),
				'activeTab'          => $active_tab,
				'presets'            => class_exists( 'WPDCG_Presets' ) ? WPDCG_Presets::get_for_tab( $active_tab ) : array(),
				'confirmBatchDelete' => __( 'Are you sure you want to permanently delete this batch of demo content?', 'loremix-demo-content-generator' ),
				'confirmPresetDelete' => __( 'Delete this preset?', 'loremix-demo-content-generator' ),
				'generating'         => __( 'Generating…', 'loremix-demo-content-generator' ),
				'generateText'       => __( 'Generate Demo Content', 'loremix-demo-content-generator' ),
				'savePreset'         => __( 'Preset name:', 'loremix-demo-content-generator' ),
				'saved'              => __( 'Saved', 'loremix-demo-content-generator' ),
				'errorText'          => __( 'An error occurred. Please try again.', 'loremix-demo-content-generator' ),
			)
		);
	}

	/**
	 * Renders the admin page by loading the view template.
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'loremix-demo-content-generator' ) );
		}
		require_once WPDCG_PATH . 'admin/views/admin-page.php';
	}

	// ── AJAX Handler ─────────────────────────────────────────────────────────

	/**
	 * Single AJAX endpoint for all generation actions.
	 * Dispatches by wpdcg_sub_action, verifies the appropriate nonce,
	 * runs the generator, and returns JSON. The page redirect is handled
	 * client-side so the transient notice appears after reload.
	 */
	public function handle_ajax_generate(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'loremix-demo-content-generator' ) ) );
		}

		check_ajax_referer( 'wpdcg_ajax_generate', 'wpdcg_ajax_nonce' );

		$sub = isset( $_POST['wpdcg_sub_action'] ) ? sanitize_key( wp_unslash( $_POST['wpdcg_sub_action'] ) ) : '';

		switch ( $sub ) {
			case 'wpdcg_generate':
				$result  = $this->do_generate();
				$tab     = ( isset( $_POST['wpdcg_post_type'] ) && 'product' === sanitize_key( wp_unslash( $_POST['wpdcg_post_type'] ) ) ) ? 'woocommerce' : 'posts';
				$message = $this->build_generate_message( $result );
				break;

			case 'wpdcg_generate_comments':
				$result  = $this->do_generate_comments();
				$tab     = 'comments';
				$message = $this->build_generate_message( $result, 'comment' );
				break;

			case 'wpdcg_generate_users':
				$result  = $this->do_generate_users();
				$tab     = 'users';
				$message = $this->build_generate_message( $result, 'user' );
				break;

			case 'wpdcg_generate_woo_reviews':
				$result  = $this->do_generate_woo_reviews();
				$tab     = 'woocommerce';
				$message = $this->build_generate_message( $result, 'review' );
				break;

			case 'wpdcg_generate_woo_orders':
				$result  = $this->do_generate_woo_orders();
				$tab     = 'woocommerce';
				$message = $this->build_generate_message( $result, 'order' );
				break;

			case 'wpdcg_generate_media':
				$result  = $this->do_generate_media();
				$tab     = 'extras';
				$message = $this->build_generate_message( $result, 'image' );
				break;

			case 'wpdcg_generate_menu':
				$result  = $this->do_generate_menu();
				$tab     = 'extras';
				$message = $this->build_menu_message( $result );
				break;

			default:
				wp_send_json_error( array( 'message' => __( 'Unknown action.', 'loremix-demo-content-generator' ) ) );
		}

		if ( is_wp_error( $result ) ) {
			$msg = $result->get_error_message();
			$this->set_notice( 'error', $msg );
			wp_send_json_error( array( 'message' => $msg, 'redirect' => $this->page_url( $tab ) ) );
		}

		if ( ! empty( $result['errors'] ) && empty( $result['created'] ) ) {
			$msg = implode( ' ', array_map( 'wp_strip_all_tags', $result['errors'] ) );
			$this->set_notice( 'error', $msg );
			wp_send_json_error( array( 'message' => $msg, 'redirect' => $this->page_url( $tab ) ) );
		}

		$this->set_notice( empty( $result['errors'] ) ? 'success' : 'warning', $message );
		wp_send_json_success( array( 'message' => $message, 'redirect' => $this->page_url( $tab ) ) );
	}

	// ── Preset AJAX Handlers ─────────────────────────────────────────────────

	public function handle_preset_save(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'loremix-demo-content-generator' ) ) );
		}
		check_ajax_referer( 'wpdcg_preset_action', 'preset_nonce' );

		$name = isset( $_POST['preset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_name'] ) ) : '';
		$tab  = isset( $_POST['preset_tab'] )  ? sanitize_key( wp_unslash( $_POST['preset_tab'] ) )          : '';

		if ( '' === $name || '' === $tab ) {
			wp_send_json_error( array( 'message' => __( 'Preset name and tab are required.', 'loremix-demo-content-generator' ) ) );
		}

		if ( strlen( $name ) > self::MAX_PRESET_NAME_LENGTH || ! WPDCG_Presets::is_valid_tab( $tab ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid preset name or tab.', 'loremix-demo-content-generator' ) ) );
		}

		$raw  = isset( $_POST['preset_data'] ) ? wp_unslash( $_POST['preset_data'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( strlen( $raw ) > self::MAX_PRESET_PAYLOAD_BYTES ) {
			wp_send_json_error( array( 'message' => __( 'Preset data is too large.', 'loremix-demo-content-generator' ) ) );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid preset data.', 'loremix-demo-content-generator' ) ) );
		}

		// Sanitize each key/value.
		$clean = array();
		foreach ( $data as $k => $v ) {
			$clean[ sanitize_text_field( $k ) ] = is_array( $v )
				? array_map( 'sanitize_text_field', $v )
				: sanitize_text_field( $v );
		}

		WPDCG_Presets::save( $name, $tab, $clean );
		wp_send_json_success( array( 'presets' => WPDCG_Presets::get_for_tab( $tab ) ) );
	}

	public function handle_preset_delete(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'loremix-demo-content-generator' ) ) );
		}
		check_ajax_referer( 'wpdcg_preset_action', 'preset_nonce' );

		$name = isset( $_POST['preset_name'] ) ? sanitize_text_field( wp_unslash( $_POST['preset_name'] ) ) : '';
		$tab  = isset( $_POST['preset_tab'] )  ? sanitize_key( wp_unslash( $_POST['preset_tab'] ) )          : '';

		if ( '' === $name || strlen( $name ) > self::MAX_PRESET_NAME_LENGTH || ! WPDCG_Presets::is_valid_tab( $tab ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid preset name or tab.', 'loremix-demo-content-generator' ) ) );
		}

		WPDCG_Presets::delete_preset( $name, $tab );
		wp_send_json_success( array( 'presets' => WPDCG_Presets::get_for_tab( $tab ) ) );
	}

	// ── Standard Form Handlers ────────────────────────────────────────────────

	public function handle_generate() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_generate', 'wpdcg_generate_nonce' );

		$post_type = isset( $_POST['wpdcg_post_type'] ) ? sanitize_key( wp_unslash( $_POST['wpdcg_post_type'] ) ) : 'post';

		if ( ! post_type_exists( $post_type ) ) {
			$this->set_notice( 'error', __( 'Invalid post type selected.', 'loremix-demo-content-generator' ) );
			wp_safe_redirect( $this->page_url() ); exit;
		}

		$ai_enabled = ! empty( $_POST['wpdcg_ai_enabled'] );
		$ai_topic   = isset( $_POST['wpdcg_ai_topic'] ) ? sanitize_text_field( wp_unslash( $_POST['wpdcg_ai_topic'] ) ) : '';

		if ( $ai_enabled && '' === $ai_topic ) {
			$this->set_notice( 'error', __( 'Client topic is required when AI Content is enabled.', 'loremix-demo-content-generator' ) );
			wp_safe_redirect( $this->page_url( 'product' === $post_type ? 'woocommerce' : 'posts' ) ); exit;
		}

		$result = $this->do_generate();
		$tab    = 'product' === $post_type ? 'woocommerce' : 'posts';

		if ( is_wp_error( $result ) ) {
			$this->set_notice( 'error', $result->get_error_message() );
		} else {
			$this->set_notice( empty( $result['errors'] ) ? 'success' : 'warning', $this->build_generate_message( $result ) );
		}

		wp_safe_redirect( $this->page_url( $tab ) );
		exit;
	}

	public function handle_generate_comments() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_generate_comments', 'wpdcg_generate_comments_nonce' );

		$result = $this->do_generate_comments();

		if ( is_wp_error( $result ) ) {
			$this->set_notice( 'error', $result->get_error_message() );
		} else {
			$this->set_notice( 'success', $this->build_generate_message( $result, 'comment' ) );
		}

		wp_safe_redirect( $this->page_url( 'comments' ) );
		exit;
	}

	public function handle_generate_users() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_generate_users', 'wpdcg_generate_users_nonce' );

		$result = $this->do_generate_users();

		if ( is_wp_error( $result ) ) {
			$this->set_notice( 'error', $result->get_error_message() );
		} else {
			$this->set_notice( 'success', $this->build_generate_message( $result, 'user' ) );
		}

		wp_safe_redirect( $this->page_url( 'users' ) );
		exit;
	}

	public function handle_generate_woo_reviews() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_generate_woo_reviews', 'wpdcg_generate_woo_reviews_nonce' );

		$result = $this->do_generate_woo_reviews();

		if ( is_wp_error( $result ) ) {
			$this->set_notice( 'error', $result->get_error_message() );
		} else {
			$this->set_notice( 'success', $this->build_generate_message( $result, 'review' ) );
		}

		wp_safe_redirect( $this->page_url( 'woocommerce' ) );
		exit;
	}

	public function handle_generate_woo_orders() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_generate_woo_orders', 'wpdcg_generate_woo_orders_nonce' );

		$result = $this->do_generate_woo_orders();

		if ( is_wp_error( $result ) ) {
			$this->set_notice( 'error', $result->get_error_message() );
		} else {
			$this->set_notice( 'success', $this->build_generate_message( $result, 'order' ) );
		}

		wp_safe_redirect( $this->page_url( 'woocommerce' ) );
		exit;
	}

	public function handle_generate_media() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_generate_media', 'wpdcg_generate_media_nonce' );

		$result = $this->do_generate_media();

		$this->set_notice(
			empty( $result['errors'] ) ? 'success' : 'warning',
			$this->build_generate_message( $result, 'image' )
		);

		wp_safe_redirect( $this->page_url( 'extras' ) );
		exit;
	}

	public function handle_generate_menu() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_generate_menu', 'wpdcg_generate_menu_nonce' );

		$result = $this->do_generate_menu();

		$this->set_notice( 'success', $this->build_menu_message( $result ) );

		wp_safe_redirect( $this->page_url( 'extras' ) );
		exit;
	}

	public function handle_delete() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_delete', 'wpdcg_delete_nonce' );

		if ( empty( $_POST['wpdcg_confirm_delete'] ) ) {
			$this->set_notice( 'warning', __( 'Please check the confirmation checkbox before deleting demo content.', 'loremix-demo-content-generator' ) );
			wp_safe_redirect( $this->page_url() );
			exit;
		}

		$result  = ( new WPDCG_Cleaner() )->delete_all();
		$deleted = $result['deleted'];

		$this->set_notice(
			'success',
			sprintf(
				/* translators: %d: number of items deleted */
				_n( '%d demo item permanently deleted.', '%d demo items permanently deleted.', $deleted, 'loremix-demo-content-generator' ),
				$deleted
			)
		);

		wp_safe_redirect( $this->page_url() );
		exit;
	}

	public function handle_delete_batch() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'loremix-demo-content-generator' ) );
		}
		check_admin_referer( 'wpdcg_delete_batch', 'wpdcg_delete_batch_nonce' );

		$batch_id = isset( $_POST['wpdcg_batch_id'] ) ? sanitize_key( wp_unslash( $_POST['wpdcg_batch_id'] ) ) : '';

		if ( ! $batch_id ) {
			$this->set_notice( 'error', __( 'Invalid batch ID.', 'loremix-demo-content-generator' ) );
			wp_safe_redirect( $this->page_url() );
			exit;
		}

		$result  = ( new WPDCG_Cleaner() )->delete_batch( $batch_id );
		$deleted = $result['deleted'];

		$this->set_notice(
			'success',
			sprintf(
				/* translators: %d: number of items deleted */
				_n( '%d demo item from this batch permanently deleted.', '%d demo items from this batch permanently deleted.', $deleted, 'loremix-demo-content-generator' ),
				$deleted
			)
		);

		wp_safe_redirect( $this->page_url() );
		exit;
	}

	// ── Generator runners (shared by form and AJAX handlers) ─────────────────
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by the public handler before dispatching to these private methods.

	private function do_generate() {
		$post_type = isset( $_POST['wpdcg_post_type'] ) ? sanitize_key( wp_unslash( $_POST['wpdcg_post_type'] ) ) : 'post';

		if ( ! post_type_exists( $post_type ) ) {
			return new WP_Error( 'invalid_post_type', __( 'Invalid post type.', 'loremix-demo-content-generator' ) );
		}

		$count           = max( 1, min( WPDCG_Generator::MAX_COUNT, isset( $_POST['wpdcg_count'] )           ? absint( $_POST['wpdcg_count'] )           : 5 ) );
		$status          = isset( $_POST['wpdcg_status'] )           ? sanitize_key( wp_unslash( $_POST['wpdcg_status'] ) )                      : 'publish';
		$author_id       = isset( $_POST['wpdcg_author'] )           ? absint( $_POST['wpdcg_author'] )                                          : 0;
		$paragraph_count = max( 1, min( 8,   isset( $_POST['wpdcg_paragraph_count'] )  ? absint( $_POST['wpdcg_paragraph_count'] )  : 3 ) );
		$excerpt_enabled = ! empty( $_POST['wpdcg_excerpt_enabled'] );
		$excerpt_length  = max( 1, min( 500, isset( $_POST['wpdcg_excerpt_length'] )   ? absint( $_POST['wpdcg_excerpt_length'] )   : 30 ) );
		$feat_enabled         = ! empty( $_POST['wpdcg_featured_image_generate'] );
		$content_images       = ! empty( $_POST['wpdcg_content_images'] );
		$content_image_count  = max( 1, min( 3, isset( $_POST['wpdcg_content_image_count'] ) ? absint( $_POST['wpdcg_content_image_count'] ) : 1 ) );
		$product_type    = isset( $_POST['wpdcg_product_type'] )     ? sanitize_key( wp_unslash( $_POST['wpdcg_product_type'] ) )                 : '';
		$auto_terms      = ! empty( $_POST['wpdcg_auto_terms'] );
		$date_from       = isset( $_POST['wpdcg_date_from'] )        ? sanitize_text_field( wp_unslash( $_POST['wpdcg_date_from'] ) )             : '';
		$date_to         = isset( $_POST['wpdcg_date_to'] )          ? sanitize_text_field( wp_unslash( $_POST['wpdcg_date_to'] ) )               : '';
		$ai_enabled      = ! empty( $_POST['wpdcg_ai_enabled'] );
		$ai_topic        = isset( $_POST['wpdcg_ai_topic'] )         ? sanitize_text_field( wp_unslash( $_POST['wpdcg_ai_topic'] ) )              : '';
		$ai_tone         = isset( $_POST['wpdcg_ai_tone'] )          ? sanitize_key( wp_unslash( $_POST['wpdcg_ai_tone'] ) )                     : 'professional';
		$ai_audience     = isset( $_POST['wpdcg_ai_audience'] )      ? sanitize_text_field( wp_unslash( $_POST['wpdcg_ai_audience'] ) )           : '';
		$ai_image        = ! empty( $_POST['wpdcg_ai_image'] );

		$taxonomy_terms = array();
		if ( isset( $_POST['wpdcg_terms'] ) && is_array( $_POST['wpdcg_terms'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_terms = wp_unslash( $_POST['wpdcg_terms'] );
			foreach ( $raw_terms as $tax => $ids ) {
				$taxonomy_terms[ sanitize_key( $tax ) ] = array_map( 'absint', (array) $ids );
			}
		}

		return ( new WPDCG_Generator() )->generate( array(
			'post_type'       => $post_type,
			'count'           => $count,
			'status'          => $status,
			'author_id'       => $author_id,
			'paragraph_count' => $paragraph_count,
			'excerpt_enabled' => $excerpt_enabled,
			'excerpt_length'  => $excerpt_length,
			'featured_image'      => $feat_enabled,
			'content_images'      => $content_images,
			'content_image_count' => $content_image_count,
			'product_type'        => $product_type,
			'auto_terms'      => $auto_terms,
			'taxonomy_terms'  => $taxonomy_terms,
			'date_from'       => $date_from,
			'date_to'         => $date_to,
			'ai_enabled'      => $ai_enabled,
			'ai_topic'        => $ai_topic,
			'ai_tone'         => $ai_tone,
			'ai_audience'     => $ai_audience,
			'ai_image'        => $ai_image,
		) );
	}

	private function do_generate_comments() {
		$per_post  = max( 1, min( 20,  isset( $_POST['wpdcg_comments_per_post'] )  ? absint( $_POST['wpdcg_comments_per_post'] )  : 3 ) );
		$attach_to = isset( $_POST['wpdcg_comments_attach_to'] ) ? sanitize_key( wp_unslash( $_POST['wpdcg_comments_attach_to'] ) ) : 'all';
		$status    = isset( $_POST['wpdcg_comments_status'] )    ? sanitize_key( wp_unslash( $_POST['wpdcg_comments_status'] ) )    : 'approve';
		$threaded  = ! empty( $_POST['wpdcg_comments_threaded'] );

		return ( new WPDCG_Comment_Generator() )->generate( array(
			'attach_to' => $attach_to,
			'per_post'  => $per_post,
			'status'    => $status,
			'threaded'  => $threaded,
		) );
	}

	private function do_generate_users() {
		$count = max( 1, min( 50, isset( $_POST['wpdcg_users_count'] ) ? absint( $_POST['wpdcg_users_count'] ) : 5 ) );
		$role  = isset( $_POST['wpdcg_users_role'] )  ? sanitize_key( wp_unslash( $_POST['wpdcg_users_role'] ) ) : 'subscriber';

		return ( new WPDCG_User_Generator() )->generate( array(
			'count' => $count,
			'role'  => $role,
		) );
	}

	private function do_generate_woo_reviews() {
		$per_product = max( 1, min( 10, isset( $_POST['wpdcg_reviews_per_product'] ) ? absint( $_POST['wpdcg_reviews_per_product'] ) : 3 ) );
		$attach_to   = isset( $_POST['wpdcg_reviews_attach_to'] )   ? sanitize_key( wp_unslash( $_POST['wpdcg_reviews_attach_to'] ) ) : 'all';

		return ( new WPDCG_Woo_Generator() )->generate_reviews( array(
			'attach_to'   => $attach_to,
			'per_product' => $per_product,
		) );
	}

	private function do_generate_woo_orders() {
		$count  = max( 1, min( 50, isset( $_POST['wpdcg_orders_count'] ) ? absint( $_POST['wpdcg_orders_count'] ) : 5 ) );
		$status = isset( $_POST['wpdcg_orders_status'] ) ? sanitize_key( wp_unslash( $_POST['wpdcg_orders_status'] ) ) : 'completed';

		return ( new WPDCG_Woo_Generator() )->generate_orders( array(
			'count'  => $count,
			'status' => $status,
		) );
	}

	private function do_generate_media() {
		$count      = max( 1, min( 50, isset( $_POST['wpdcg_media_count'] ) ? absint( $_POST['wpdcg_media_count'] ) : 5 ) );
		$ai_enabled = ! empty( $_POST['wpdcg_media_ai_enabled'] );
		$ai_topic   = isset( $_POST['wpdcg_media_ai_topic'] ) ? sanitize_text_field( wp_unslash( $_POST['wpdcg_media_ai_topic'] ) ) : '';

		return ( new WPDCG_Media_Generator() )->generate( array(
			'count'      => $count,
			'ai_enabled' => $ai_enabled,
			'ai_topic'   => $ai_topic,
		) );
	}

	private function do_generate_menu() {
		$name          = isset( $_POST['wpdcg_menu_name'] )       ? sanitize_text_field( wp_unslash( $_POST['wpdcg_menu_name'] ) ) : '';
		$item_count    = max( 3, min( 12, isset( $_POST['wpdcg_menu_item_count'] ) ? absint( $_POST['wpdcg_menu_item_count'] ) : 5 ) );
		$with_children = ! empty( $_POST['wpdcg_menu_children'] );

		return ( new WPDCG_Menu_Generator() )->generate( array(
			'name'          => $name,
			'item_count'    => $item_count,
			'with_children' => $with_children,
		) );
	}

	// phpcs:enable WordPress.Security.NonceVerification.Missing

	// ── Message builders ─────────────────────────────────────────────────────

	private function build_generate_message( $result, string $type = 'item' ): string {
		if ( is_wp_error( $result ) ) {
			return $result->get_error_message();
		}

		$n        = count( $result['created'] ?? array() );
		$batch_id = $result['batch_id'] ?? '';

		switch ( $type ) {
			case 'comment':
				/* translators: 1: number of comments created, 2: batch ID */
				$message = sprintf( _n( '%1$d demo comment created. Batch ID: %2$s', '%1$d demo comments created. Batch ID: %2$s', $n, 'loremix-demo-content-generator' ), $n, $batch_id );
				break;
			case 'user':
				/* translators: 1: number of users created, 2: batch ID */
				$message = sprintf( _n( '%1$d demo user created. Batch ID: %2$s', '%1$d demo users created. Batch ID: %2$s', $n, 'loremix-demo-content-generator' ), $n, $batch_id );
				break;
			case 'review':
				/* translators: 1: number of reviews created, 2: batch ID */
				$message = sprintf( _n( '%1$d demo review created. Batch ID: %2$s', '%1$d demo reviews created. Batch ID: %2$s', $n, 'loremix-demo-content-generator' ), $n, $batch_id );
				break;
			case 'order':
				/* translators: 1: number of orders created, 2: batch ID */
				$message = sprintf( _n( '%1$d demo order created. Batch ID: %2$s', '%1$d demo orders created. Batch ID: %2$s', $n, 'loremix-demo-content-generator' ), $n, $batch_id );
				break;
			case 'image':
				/* translators: 1: number of images created, 2: batch ID */
				$message = sprintf( _n( '%1$d demo image created. Batch ID: %2$s', '%1$d demo images created. Batch ID: %2$s', $n, 'loremix-demo-content-generator' ), $n, $batch_id );
				break;
			default:
				/* translators: 1: number of items created, 2: batch ID */
				$message = sprintf( _n( '%1$d demo item created. Batch ID: %2$s', '%1$d demo items created. Batch ID: %2$s', $n, 'loremix-demo-content-generator' ), $n, $batch_id );
				break;
		}

		if ( ! empty( $result['errors'] ) ) {
			$message .= ' ' . implode( ' ', array_map( 'wp_strip_all_tags', $result['errors'] ) );
		}

		return $message;
	}

	private function build_menu_message( $result ): string {
		if ( is_wp_error( $result ) ) {
			return $result->get_error_message();
		}

		if ( ! empty( $result['errors'] ) && empty( $result['created'] ) ) {
			return implode( ' ', array_map( 'wp_strip_all_tags', $result['errors'] ) );
		}

		return sprintf(
			/* translators: 1: menu name, 2: batch ID */
			__( 'Nav menu "%1$s" created. Batch ID: %2$s', 'loremix-demo-content-generator' ),
			$result['menu_name'] ?? '',
			$result['batch_id']  ?? ''
		);
	}

	// ── Helpers ──────────────────────────────────────────────────────────────

	/**
	 * Returns the canonical URL of the plugin admin page.
	 *
	 * @param string $tab Optional tab slug to land on after redirect.
	 * @return string
	 */
	private function page_url( string $tab = '' ): string {
		$url = admin_url( 'admin.php?page=' . self::MENU_SLUG );
		if ( $tab ) {
			$url = add_query_arg( 'tab', $tab, $url );
		}
		return $url;
	}

	/**
	 * Stores a transient admin notice scoped to the current user (60 s TTL).
	 *
	 * @param string $type    'success' | 'error' | 'warning'.
	 * @param string $message Human-readable notice text.
	 */
	private function set_notice( string $type, string $message ) {
		set_transient(
			'wpdcg_notice_' . get_current_user_id(),
			array(
				'type'    => $type,
				'message' => $message,
			),
			60
		);
	}
}
