<?php
/**
 * Admin page view — QuickDemo Content Generator.
 * Rendered by WPDCG_Admin::render_page(). All output is escaped at point of output.
 *
 * @package QuickDemo_Content_Generator
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Notice ───────────────────────────────────────────────────────────────────
$notice_key = 'wpdcg_notice_' . get_current_user_id();
$notice     = get_transient( $notice_key );
if ( $notice ) { delete_transient( $notice_key ); }

// ── Active tab ───────────────────────────────────────────────────────────────
$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'posts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$valid_tabs = array( 'posts', 'comments', 'users', 'woocommerce', 'extras' );
if ( ! in_array( $active_tab, $valid_tabs, true ) ) {
	$active_tab = 'posts';
}

// ── Page data ────────────────────────────────────────────────────────────────
$all_tracked   = WPDCG_Tracker::count();
$comment_count = WPDCG_Tracker::count_comments();
$user_count    = WPDCG_Tracker::count_users();
$order_count   = WPDCG_Tracker::count_orders();
$total_tracked = $all_tracked + $comment_count + $user_count + $order_count;
$batches       = WPDCG_Tracker::get_batches();

$commentable_demo_posts = new WP_Query(
	array(
		'post_type'      => 'any',
		'post_status'    => array( 'publish', 'draft' ),
		'posts_per_page' => 1,
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
$has_comment_targets = ! empty( $commentable_demo_posts->posts );

$all_post_types = get_post_types( array( 'public' => true ), 'objects' );
$excluded_types = array( 'attachment', 'product' );
$post_types     = array_filter( $all_post_types, function( $pt ) use ( $excluded_types ) {
	return ! in_array( $pt->name, $excluded_types, true );
} );
$preview_type = ( isset( $_GET['wpdcg_preview_type'] ) && post_type_exists( sanitize_key( wp_unslash( $_GET['wpdcg_preview_type'] ) ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	? sanitize_key( wp_unslash( $_GET['wpdcg_preview_type'] ) ) : 'post'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$taxonomies   = get_object_taxonomies( $preview_type, 'objects' );
$users        = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name' ) );

// Map post type slug → plural label.
$pt_labels = array();
foreach ( $post_types as $pt ) {
	$pt_labels[ $pt->name ] = $pt->labels->name;
}

// Singular label for the current preview type.
$preview_pt_obj   = get_post_type_object( $preview_type );
$preview_pt_label = $preview_pt_obj ? $preview_pt_obj->labels->singular_name : $preview_type;

// Batch type → human label map.
$batch_type_labels = array(
	'_comment'  => __( 'Comments', 'quickdemo-content-generator' ),
	'_user'     => __( 'Users', 'quickdemo-content-generator' ),
	'_wc_review' => __( 'WC Reviews', 'quickdemo-content-generator' ),
	'_wc_order' => __( 'WC Orders', 'quickdemo-content-generator' ),
	'_media'    => __( 'Media Images', 'quickdemo-content-generator' ),
	'_menu'     => __( 'Nav Menus', 'quickdemo-content-generator' ),
);

// Presets for the active tab.
$tab_presets = class_exists( 'WPDCG_Presets' ) ? WPDCG_Presets::get_for_tab( $active_tab ) : array();

// WooCommerce status list (safe fallback).
$wc_order_statuses = array();
if ( function_exists( 'wc_get_order_statuses' ) ) {
	foreach ( wc_get_order_statuses() as $key => $label ) {
		$wc_order_statuses[ 0 === strpos( $key, 'wc-' ) ? substr( $key, 3 ) : $key ] = $label;
	}
}

$wc_active = function_exists( 'wc_get_product' );

// WordPress roles.
$wp_roles = wp_roles()->roles;
$wp_roles = array_filter(
	$wp_roles,
	function( $role_data ) {
		return empty( $role_data['capabilities']['manage_options'] );
	}
);

// WordPress AI Client / Connectors status.
$ai_client_available = WPDCG_AI_Generator::is_ai_client_available();
$ai_text_supported   = WPDCG_AI_Generator::supports_text_generation();
$ai_image_supported  = WPDCG_AI_Generator::supports_image_generation();
$ai_connectors       = WPDCG_AI_Generator::get_ai_connectors();

// Tab URL helper.
$tab_url = function( string $tab ) use ( $active_tab ) {
	return add_query_arg( 'tab', $tab, admin_url( 'admin.php?page=quickdemo-content-generator' ) );
};
?>
<div class="wrap wpdcg-wrap">
<h1><?php esc_html_e( 'QuickDemo Content Generator', 'quickdemo-content-generator' ); ?></h1>

<?php if ( $notice ) : ?>
<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
	<p><?php echo esc_html( $notice['message'] ); ?></p>
</div>
<?php endif; ?>

<?php /* ── Stats bar ──────────────────────────────────────────────────────── */ ?>
<div class="wpdcg-stats">
	<?php if ( $total_tracked > 0 ) : ?>
		<span class="wpdcg-stats__label">
			<strong><?php echo (int) $total_tracked; ?></strong>
			<?php esc_html_e( 'items tracked', 'quickdemo-content-generator' ); ?>
		</span>
		<span class="wpdcg-stats__sep">—</span>
		<div class="wpdcg-stats__types">
			<?php
			// Split post-type tracked count into products vs. other posts for clearer stats.
			$product_tracked = 0;
			$post_tracked    = 0;
			foreach ( WPDCG_Tracker::get_batches() as $_b ) {
				$_type = isset( $_b['post_type'] ) ? $_b['post_type'] : '';
				if ( 'product' === $_type ) {
					$product_tracked += (int) $_b['count'];
				} elseif ( '' !== $_type && '_' !== substr( $_type, 0, 1 ) ) {
					$post_tracked += (int) $_b['count'];
				}
			}
			?>
			<?php if ( $post_tracked > 0 ) : ?>
				<span class="wpdcg-badge">
					<?php esc_html_e( 'Posts', 'quickdemo-content-generator' ); ?>&nbsp;<strong><?php echo (int) $post_tracked; ?></strong>
				</span>
			<?php endif; ?>
			<?php if ( $product_tracked > 0 ) : ?>
				<span class="wpdcg-badge">
					<?php esc_html_e( 'Products', 'quickdemo-content-generator' ); ?>&nbsp;<strong><?php echo (int) $product_tracked; ?></strong>
				</span>
			<?php endif; ?>
			<?php if ( $comment_count > 0 ) : ?>
				<span class="wpdcg-badge">
					<?php esc_html_e( 'Comments', 'quickdemo-content-generator' ); ?>&nbsp;<strong><?php echo (int) $comment_count; ?></strong>
				</span>
			<?php endif; ?>
			<?php if ( $user_count > 0 ) : ?>
				<span class="wpdcg-badge">
					<?php esc_html_e( 'Users', 'quickdemo-content-generator' ); ?>&nbsp;<strong><?php echo (int) $user_count; ?></strong>
				</span>
			<?php endif; ?>
			<?php if ( $order_count > 0 ) : ?>
				<span class="wpdcg-badge">
					<?php esc_html_e( 'Orders', 'quickdemo-content-generator' ); ?>&nbsp;<strong><?php echo (int) $order_count; ?></strong>
				</span>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<span class="wpdcg-stats__label">
			<?php esc_html_e( 'No demo content generated yet.', 'quickdemo-content-generator' ); ?>
		</span>
	<?php endif; ?>
</div>

<?php /* ── Tab navigation ──────────────────────────────────────────────────── */ ?>
<div class="wpdcg-tabs">
	<a href="<?php echo esc_url( $tab_url( 'posts' ) ); ?>"
		class="wpdcg-tab<?php echo 'posts' === $active_tab ? ' is-active' : ''; ?>">
		<span class="dashicons dashicons-admin-post"></span>
		<?php esc_html_e( 'Posts', 'quickdemo-content-generator' ); ?>
	</a>
	<a href="<?php echo esc_url( $tab_url( 'comments' ) ); ?>"
		class="wpdcg-tab<?php echo 'comments' === $active_tab ? ' is-active' : ''; ?>">
		<span class="dashicons dashicons-admin-comments"></span>
		<?php esc_html_e( 'Comments', 'quickdemo-content-generator' ); ?>
	</a>
	<a href="<?php echo esc_url( $tab_url( 'users' ) ); ?>"
		class="wpdcg-tab<?php echo 'users' === $active_tab ? ' is-active' : ''; ?>">
		<span class="dashicons dashicons-admin-users"></span>
		<?php esc_html_e( 'Users', 'quickdemo-content-generator' ); ?>
	</a>
	<a href="<?php echo esc_url( $tab_url( 'woocommerce' ) ); ?>"
		class="wpdcg-tab<?php echo 'woocommerce' === $active_tab ? ' is-active' : ''; ?>">
		<span class="dashicons dashicons-cart"></span>
		<?php esc_html_e( 'WooCommerce', 'quickdemo-content-generator' ); ?>
		<?php if ( ! $wc_active ) : ?>
			<span class="wpdcg-tab__badge"><?php esc_html_e( 'Inactive', 'quickdemo-content-generator' ); ?></span>
		<?php endif; ?>
	</a>
	<a href="<?php echo esc_url( $tab_url( 'extras' ) ); ?>"
		class="wpdcg-tab<?php echo 'extras' === $active_tab ? ' is-active' : ''; ?>">
		<span class="dashicons dashicons-superhero-alt"></span>
		<?php esc_html_e( 'Extras', 'quickdemo-content-generator' ); ?>
	</a>
</div>

<?php /* ══════════════════════ TAB: POSTS ══════════════════════════════════ */ ?>
<?php if ( 'posts' === $active_tab ) : ?>
<div class="wpdcg-card">

	<div class="wpdcg-card__head">
		<span class="dashicons dashicons-database-add"></span>
		<h2><?php esc_html_e( 'Generate Demo Posts &amp; Pages', 'quickdemo-content-generator' ); ?></h2>
	</div>

	<?php if ( ! empty( $tab_presets ) ) : ?>
	<div class="wpdcg-presets-bar" data-tab="posts">
		<label class="wpdcg-presets-bar__label"><?php esc_html_e( 'Presets', 'quickdemo-content-generator' ); ?></label>
		<span class="dashicons dashicons-editor-help wpdcg-presets-bar__help" title="<?php esc_attr_e( 'Presets let you save and restore your form settings. Configure the form the way you want, click Save As to name it, then pick it from the dropdown and click Load to restore those settings any time. Use Delete to remove a preset you no longer need.', 'quickdemo-content-generator' ); ?>"></span>
		<select class="wpdcg-preset-select">
			<option value="">— <?php esc_html_e( 'Load a preset', 'quickdemo-content-generator' ); ?> —</option>
			<?php foreach ( $tab_presets as $preset ) : ?>
			<option value="<?php echo esc_attr( $preset['name'] ); ?>" data-fields="<?php echo esc_attr( wp_json_encode( $preset['data'] ) ); ?>">
				<?php echo esc_html( $preset['name'] ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<button type="button" class="button wpdcg-preset-load-btn"><?php esc_html_e( 'Load', 'quickdemo-content-generator' ); ?></button>
		<button type="button" class="button wpdcg-preset-save-btn"><?php esc_html_e( 'Save As', 'quickdemo-content-generator' ); ?></button>
		<button type="button" class="button wpdcg-preset-delete-btn"><?php esc_html_e( 'Delete', 'quickdemo-content-generator' ); ?></button>
		<span class="wpdcg-presets-bar__hint"><?php esc_html_e( 'Pick a preset → Load to restore · Save As to store current settings · Delete to remove.', 'quickdemo-content-generator' ); ?></span>
	</div>
	<?php else : ?>
	<div class="wpdcg-presets-bar wpdcg-presets-bar--empty" data-tab="posts">
		<span class="dashicons dashicons-editor-help wpdcg-presets-bar__help" title="<?php esc_attr_e( 'Presets let you save and restore your form settings. Configure the form the way you want, click Save as Preset to name it, then pick it from the dropdown and click Load to restore those settings any time.', 'quickdemo-content-generator' ); ?>"></span>
		<button type="button" class="button wpdcg-preset-save-btn"><?php esc_html_e( 'Save as Preset', 'quickdemo-content-generator' ); ?></button>
		<span class="wpdcg-presets-bar__hint"><?php esc_html_e( 'No presets yet — configure the form, then click Save as Preset to create one.', 'quickdemo-content-generator' ); ?></span>
	</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-generate-form">
		<?php wp_nonce_field( 'wpdcg_generate', 'wpdcg_generate_nonce' ); ?>
		<input type="hidden" name="action" value="wpdcg_generate">

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Post Options', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_post_type"><?php esc_html_e( 'Post Type', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_post_type" name="wpdcg_post_type">
						<?php foreach ( $post_types as $pt ) : ?>
						<option value="<?php echo esc_attr( $pt->name ); ?>"<?php selected( $pt->name, $preview_type ); ?>>
							<?php echo esc_html( $pt->labels->singular_name ); ?> (<?php echo esc_html( $pt->name ); ?>)
						</option>
						<?php endforeach; ?>
					</select>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Changing this reloads taxonomy options below.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_count"><?php esc_html_e( 'Count', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_count" name="wpdcg_count" value="5" min="1" max="500" class="small-text" required>
					<p class="wpdcg-field__hint"><?php esc_html_e( '1–500 items per run.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_status"><?php esc_html_e( 'Status', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_status" name="wpdcg_status">
						<option value="publish"><?php esc_html_e( 'Published', 'quickdemo-content-generator' ); ?></option>
						<option value="draft"><?php esc_html_e( 'Draft', 'quickdemo-content-generator' ); ?></option>
						<option value="pending"><?php esc_html_e( 'Pending Review', 'quickdemo-content-generator' ); ?></option>
					</select>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_author"><?php esc_html_e( 'Author', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_author" name="wpdcg_author">
						<?php foreach ( $users as $u ) : ?>
						<option value="<?php echo esc_attr( $u->ID ); ?>"<?php selected( $u->ID, get_current_user_id() ); ?>>
							<?php echo esc_html( $u->display_name ); ?>
						</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Taxonomy Terms', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" name="wpdcg_auto_terms" value="1">
						<?php esc_html_e( 'Auto-generate terms if none are selected', 'quickdemo-content-generator' ); ?>
					</label>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Creates sample categories, tags, or custom taxonomy terms and assigns them across posts. Has no effect if you manually select terms below.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
		</div>

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Content', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'AI Content', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" id="wpdcg_ai_toggle" name="wpdcg_ai_enabled" value="1" <?php disabled( ! $ai_text_supported ); ?>>
						<?php esc_html_e( 'Generate titles and body content from a client topic', 'quickdemo-content-generator' ); ?>
					</label>
					<?php if ( ! $ai_text_supported ) : ?>
						<p class="wpdcg-field__hint">
							<?php
							printf(
								/* translators: %s: WordPress Connectors settings URL */
								wp_kses_post( __( 'Configure a WordPress AI connector in <a href="%s">Settings</a> to enable AI content.', 'quickdemo-content-generator' ) ),
								esc_url( admin_url( 'options-connectors.php' ) )
							);
							?>
						</p>
					<?php endif; ?>
					<div id="wpdcg-ai-wrap" style="display:none;margin-top:10px">
						<p>
							<label for="wpdcg_ai_topic" style="font-size:12px;color:#50575e;font-weight:600;"><?php esc_html_e( 'Client topic', 'quickdemo-content-generator' ); ?></label><br>
							<input type="text" id="wpdcg_ai_topic" name="wpdcg_ai_topic" class="regular-text" placeholder="<?php esc_attr_e( 'Example: dental clinic in Austin focused on family care', 'quickdemo-content-generator' ); ?>">
						</p>
						<p>
							<label for="wpdcg_ai_audience" style="font-size:12px;color:#50575e;font-weight:600;"><?php esc_html_e( 'Audience', 'quickdemo-content-generator' ); ?></label><br>
							<input type="text" id="wpdcg_ai_audience" name="wpdcg_ai_audience" class="regular-text" placeholder="<?php esc_attr_e( 'Example: local parents and busy professionals', 'quickdemo-content-generator' ); ?>">
						</p>
						<p>
							<label for="wpdcg_ai_tone" style="font-size:12px;color:#50575e;font-weight:600;"><?php esc_html_e( 'Tone', 'quickdemo-content-generator' ); ?></label><br>
							<select id="wpdcg_ai_tone" name="wpdcg_ai_tone">
								<option value="professional"><?php esc_html_e( 'Professional', 'quickdemo-content-generator' ); ?></option>
								<option value="friendly"><?php esc_html_e( 'Friendly', 'quickdemo-content-generator' ); ?></option>
								<option value="luxury"><?php esc_html_e( 'Luxury', 'quickdemo-content-generator' ); ?></option>
								<option value="casual"><?php esc_html_e( 'Casual', 'quickdemo-content-generator' ); ?></option>
								<option value="technical"><?php esc_html_e( 'Technical', 'quickdemo-content-generator' ); ?></option>
							</select>
						</p>
						<label class="wpdcg-check">
							<input type="checkbox" name="wpdcg_ai_image" value="1" <?php disabled( ! $ai_image_supported ); ?>>
							<?php esc_html_e( 'Generate topic-based featured images with AI', 'quickdemo-content-generator' ); ?>
						</label>
						<p class="wpdcg-field__hint"><?php esc_html_e( 'AI images can be slow. For best reliability, generate 1–2 items per run or leave this unchecked and use the built-in placeholder image option.', 'quickdemo-content-generator' ); ?></p>
						<?php if ( ! $ai_image_supported ) : ?>
							<p class="wpdcg-field__hint"><?php esc_html_e( 'No configured connector currently supports image generation.', 'quickdemo-content-generator' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_paragraph_count"><?php esc_html_e( 'Paragraphs', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_paragraph_count" name="wpdcg_paragraph_count" value="3" min="1" max="8" class="small-text">
					<p class="wpdcg-field__hint">
						<?php esc_html_e( 'Controls content depth (1–8).', 'quickdemo-content-generator' ); ?>
						<strong><?php esc_html_e( '1–2:', 'quickdemo-content-generator' ); ?></strong> <?php esc_html_e( 'paragraphs only.', 'quickdemo-content-generator' ); ?>
						<strong><?php esc_html_e( '3–4:', 'quickdemo-content-generator' ); ?></strong> <?php esc_html_e( 'adds h2, h3 and a list.', 'quickdemo-content-generator' ); ?>
						<strong><?php esc_html_e( '5–6:', 'quickdemo-content-generator' ); ?></strong> <?php esc_html_e( 'adds h4 and a blockquote.', 'quickdemo-content-generator' ); ?>
						<strong><?php esc_html_e( '7–8:', 'quickdemo-content-generator' ); ?></strong> <?php esc_html_e( 'full set — all heading levels, list, blockquote, and table.', 'quickdemo-content-generator' ); ?>
					</p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Excerpt', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" id="wpdcg_exc_toggle" name="wpdcg_excerpt_enabled" value="1">
						<?php esc_html_e( 'Generate an excerpt for each post', 'quickdemo-content-generator' ); ?>
					</label>
					<div id="wpdcg-exc-wrap" style="display:none;margin-top:10px">
						<label for="wpdcg_excerpt_length" style="font-size:12px;color:#50575e;font-weight:600;">
							<?php esc_html_e( 'Excerpt length (words):', 'quickdemo-content-generator' ); ?>
						</label>
						<input type="number" id="wpdcg_excerpt_length" name="wpdcg_excerpt_length" value="30" min="1" max="500" class="small-text" style="margin-left:6px">
					</div>
				</div>
			</div>
		</div>

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Scheduling', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Date Range', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" id="wpdcg_date_toggle" value="1">
						<?php esc_html_e( 'Spread posts across a custom date range', 'quickdemo-content-generator' ); ?>
					</label>
					<div id="wpdcg-date-wrap" style="display:none">
						<div class="wpdcg-date-row">
							<label for="wpdcg_date_from"><?php esc_html_e( 'From', 'quickdemo-content-generator' ); ?></label>
							<input type="date" id="wpdcg_date_from" name="wpdcg_date_from">
							<label for="wpdcg_date_to"><?php esc_html_e( 'To', 'quickdemo-content-generator' ); ?></label>
							<input type="date" id="wpdcg_date_to" name="wpdcg_date_to">
						</div>
						<p class="wpdcg-field__hint"><?php esc_html_e( 'Each post gets a random date within this range.', 'quickdemo-content-generator' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Media', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Featured Image', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" name="wpdcg_featured_image_generate" value="1">
						<?php esc_html_e( 'Use a built-in placeholder featured image', 'quickdemo-content-generator' ); ?>
					</label>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Creates a unique gradient image via PHP GD. If AI images are enabled, this is used only as a fallback when AI image generation fails.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
		</div>

<?php if ( ! empty( $taxonomies ) ) :
			$has_terms = false;
			foreach ( $taxonomies as $tax_slug => $tax_obj ) {
				$terms = get_terms( array( 'taxonomy' => $tax_slug, 'hide_empty' => false ) );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$has_terms = true;
					break;
				}
			}
			if ( $has_terms ) :
		?>
		<div style="border-top:1px solid #f6f7f7;padding:14px 20px 4px">
			<div class="wpdcg-section-title">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: post type singular label e.g. "Post" */
						__( 'Terms <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#a7aaad;font-size:11px;">for %s</span>', 'quickdemo-content-generator' ),
						esc_html( $preview_pt_label )
					),
					array( 'span' => array( 'style' => array() ) )
				);
				?>
			</div>
		</div>
		<?php foreach ( $taxonomies as $tax_slug => $tax_obj ) :
			$terms = get_terms( array( 'taxonomy' => $tax_slug, 'hide_empty' => false ) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) { continue; }
		?>
		<div class="wpdcg-terms-wrap">
			<div class="wpdcg-terms-name"><?php echo esc_html( $tax_obj->labels->name ); ?></div>
			<div class="wpdcg-chips">
				<?php foreach ( $terms as $term ) : ?>
				<label class="wpdcg-chip">
					<input type="checkbox"
						name="wpdcg_terms[<?php echo esc_attr( $tax_slug ); ?>][]"
						value="<?php echo esc_attr( $term->term_id ); ?>">
					<?php echo esc_html( $term->name ); ?>
					<span class="wpdcg-chip__count"><?php echo (int) $term->count; ?></span>
				</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endforeach; ?>
		<?php endif; endif; ?>

		<div class="wpdcg-card__foot">
			<button type="submit" class="button button-primary wpdcg-generate-btn">
				<span class="wpdcg-spinner-icon"></span>
				<span class="wpdcg-btn-text"><?php esc_html_e( 'Generate Demo Content', 'quickdemo-content-generator' ); ?></span>
			</button>
		</div>
		<div class="wpdcg-progress" style="display:none" aria-hidden="true">
			<div class="wpdcg-progress__bar"></div>
		</div>
	</form>
</div>
<?php endif; /* end posts tab */ ?>

<?php /* ══════════════════════ TAB: COMMENTS ════════════════════════════════ */ ?>
<?php if ( 'comments' === $active_tab ) : ?>
<div class="wpdcg-card">

	<div class="wpdcg-card__head">
		<span class="dashicons dashicons-admin-comments"></span>
		<h2><?php esc_html_e( 'Generate Demo Comments', 'quickdemo-content-generator' ); ?></h2>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-generate-form">
		<?php wp_nonce_field( 'wpdcg_generate_comments', 'wpdcg_generate_comments_nonce' ); ?>
		<input type="hidden" name="action" value="wpdcg_generate_comments">

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Comment Options', 'quickdemo-content-generator' ); ?></div>

			<?php if ( ! $has_comment_targets ) : ?>
			<div class="wpdcg-inline-notice wpdcg-inline-notice--warning">
				<strong><?php esc_html_e( 'Generate posts first.', 'quickdemo-content-generator' ); ?></strong>
				<span><?php esc_html_e( 'Demo comments can only attach to demo posts created by QuickDemo. Go to the Posts tab and generate at least one post or page, then return here.', 'quickdemo-content-generator' ); ?></span>
			</div>
			<?php endif; ?>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_comments_attach_to"><?php esc_html_e( 'Attach To', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_comments_attach_to" name="wpdcg_comments_attach_to" <?php disabled( ! $has_comment_targets ); ?>>
						<option value="all"><?php esc_html_e( 'All demo posts', 'quickdemo-content-generator' ); ?></option>
						<option value="latest_batch"><?php esc_html_e( 'Latest post batch only', 'quickdemo-content-generator' ); ?></option>
					</select>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Comments will be added to demo posts that are already generated.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_comments_per_post"><?php esc_html_e( 'Per Post', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_comments_per_post" name="wpdcg_comments_per_post" value="3" min="1" max="20" class="small-text" required <?php disabled( ! $has_comment_targets ); ?>>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Comments per post (1–20).', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_comments_status"><?php esc_html_e( 'Status', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_comments_status" name="wpdcg_comments_status" <?php disabled( ! $has_comment_targets ); ?>>
						<option value="approve"><?php esc_html_e( 'Approved', 'quickdemo-content-generator' ); ?></option>
						<option value="hold"><?php esc_html_e( 'Pending (Hold)', 'quickdemo-content-generator' ); ?></option>
					</select>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Threaded Replies', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" name="wpdcg_comments_threaded" value="1" checked <?php disabled( ! $has_comment_targets ); ?>>
						<?php esc_html_e( 'Include nested (reply) comments', 'quickdemo-content-generator' ); ?>
					</label>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Some comments will appear as replies to earlier ones (max depth 2).', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
		</div>

		<div class="wpdcg-card__foot">
			<a href="<?php echo esc_url( $tab_url( 'posts' ) ); ?>" class="button<?php echo $has_comment_targets ? ' hidden' : ''; ?>">
				<?php esc_html_e( 'Go to Posts', 'quickdemo-content-generator' ); ?>
			</a>
			<button type="submit" class="button button-primary wpdcg-generate-btn" <?php disabled( ! $has_comment_targets ); ?>>
				<span class="wpdcg-spinner-icon"></span>
				<span class="wpdcg-btn-text"><?php esc_html_e( 'Generate Demo Comments', 'quickdemo-content-generator' ); ?></span>
			</button>
		</div>
		<div class="wpdcg-progress" style="display:none" aria-hidden="true">
			<div class="wpdcg-progress__bar"></div>
		</div>
	</form>
</div>
<?php endif; /* end comments tab */ ?>

<?php /* ══════════════════════ TAB: USERS ═══════════════════════════════════ */ ?>
<?php if ( 'users' === $active_tab ) : ?>
<div class="wpdcg-card">

	<div class="wpdcg-card__head">
		<span class="dashicons dashicons-admin-users"></span>
		<h2><?php esc_html_e( 'Generate Demo Users', 'quickdemo-content-generator' ); ?></h2>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-generate-form">
		<?php wp_nonce_field( 'wpdcg_generate_users', 'wpdcg_generate_users_nonce' ); ?>
		<input type="hidden" name="action" value="wpdcg_generate_users">

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'User Options', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_users_count"><?php esc_html_e( 'Count', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_users_count" name="wpdcg_users_count" value="5" min="1" max="50" class="small-text" required>
					<p class="wpdcg-field__hint"><?php esc_html_e( '1–50 users per run.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_users_role"><?php esc_html_e( 'Role', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_users_role" name="wpdcg_users_role">
						<?php foreach ( $wp_roles as $role_key => $role_data ) : ?>
						<option value="<?php echo esc_attr( $role_key ); ?>"<?php selected( $role_key, 'subscriber' ); ?>>
							<?php echo esc_html( $role_data['name'] ); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'All generated users get the same role. Passwords are randomly generated. Roles with full site-management permissions are excluded for release safety.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
		</div>

		<div class="wpdcg-card__foot">
			<button type="submit" class="button button-primary wpdcg-generate-btn">
				<span class="wpdcg-spinner-icon"></span>
				<span class="wpdcg-btn-text"><?php esc_html_e( 'Generate Demo Users', 'quickdemo-content-generator' ); ?></span>
			</button>
		</div>
		<div class="wpdcg-progress" style="display:none" aria-hidden="true">
			<div class="wpdcg-progress__bar"></div>
		</div>
	</form>
</div>
<?php endif; /* end users tab */ ?>

<?php /* ══════════════════════ TAB: WOOCOMMERCE ════════════════════════════ */ ?>
<?php if ( 'woocommerce' === $active_tab ) : ?>

<?php if ( ! $wc_active ) : ?>
<div class="wpdcg-card">
	<div class="wpdcg-card__head">
		<span class="dashicons dashicons-cart"></span>
		<h2><?php esc_html_e( 'WooCommerce', 'quickdemo-content-generator' ); ?></h2>
	</div>
	<div style="padding:24px 24px 28px">
		<p style="margin:0;color:#50575e;">
			<?php esc_html_e( 'WooCommerce is not active. Please install and activate WooCommerce to use this tab.', 'quickdemo-content-generator' ); ?>
		</p>
	</div>
</div>

<?php else : ?>

<?php /* ── WC Products ────────────────────────────────────────────── */ ?>
<div class="wpdcg-card wpdcg-accordion is-open">

	<button type="button" class="wpdcg-card__head wpdcg-accordion__trigger" aria-expanded="true" aria-controls="wpdcg-wc-products-panel">
		<span class="dashicons dashicons-products"></span>
		<h2><?php esc_html_e( 'Generate Demo Products', 'quickdemo-content-generator' ); ?></h2>
		<span class="dashicons dashicons-arrow-up-alt2 wpdcg-accordion__icon"></span>
	</button>

	<?php
	$wc_taxonomies = get_object_taxonomies( 'product', 'objects' );
	unset( $wc_taxonomies['product_type'] );
	unset( $wc_taxonomies['product_visibility'] );
	?>
	<div id="wpdcg-wc-products-panel" class="wpdcg-accordion__panel">
	<?php if ( ! empty( $tab_presets ) ) : ?>
	<div class="wpdcg-presets-bar" data-tab="woocommerce">
		<label class="wpdcg-presets-bar__label"><?php esc_html_e( 'Presets', 'quickdemo-content-generator' ); ?></label>
		<span class="dashicons dashicons-editor-help wpdcg-presets-bar__help" title="<?php esc_attr_e( 'Presets let you save and restore your form settings. Configure the form the way you want, click Save As to name it, then pick it from the dropdown and click Load to restore those settings any time. Use Delete to remove a preset you no longer need.', 'quickdemo-content-generator' ); ?>"></span>
		<select class="wpdcg-preset-select">
			<option value="">— <?php esc_html_e( 'Load a preset', 'quickdemo-content-generator' ); ?> —</option>
			<?php foreach ( $tab_presets as $preset ) : ?>
			<option value="<?php echo esc_attr( $preset['name'] ); ?>" data-fields="<?php echo esc_attr( wp_json_encode( $preset['data'] ) ); ?>">
				<?php echo esc_html( $preset['name'] ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<button type="button" class="button wpdcg-preset-load-btn"><?php esc_html_e( 'Load', 'quickdemo-content-generator' ); ?></button>
		<button type="button" class="button wpdcg-preset-save-btn"><?php esc_html_e( 'Save As', 'quickdemo-content-generator' ); ?></button>
		<button type="button" class="button wpdcg-preset-delete-btn"><?php esc_html_e( 'Delete', 'quickdemo-content-generator' ); ?></button>
		<span class="wpdcg-presets-bar__hint"><?php esc_html_e( 'Pick a preset → Load to restore · Save As to store current settings · Delete to remove.', 'quickdemo-content-generator' ); ?></span>
	</div>
	<?php else : ?>
	<div class="wpdcg-presets-bar wpdcg-presets-bar--empty" data-tab="woocommerce">
		<span class="dashicons dashicons-editor-help wpdcg-presets-bar__help" title="<?php esc_attr_e( 'Presets let you save and restore your form settings. Configure the form the way you want, click Save as Preset to name it, then pick it from the dropdown and click Load to restore those settings any time.', 'quickdemo-content-generator' ); ?>"></span>
		<button type="button" class="button wpdcg-preset-save-btn"><?php esc_html_e( 'Save as Preset', 'quickdemo-content-generator' ); ?></button>
		<span class="wpdcg-presets-bar__hint"><?php esc_html_e( 'No presets yet — configure the form, then click Save as Preset to create one.', 'quickdemo-content-generator' ); ?></span>
	</div>
	<?php endif; ?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-generate-form">
		<?php wp_nonce_field( 'wpdcg_generate', 'wpdcg_generate_nonce' ); ?>
		<input type="hidden" name="action" value="wpdcg_generate">
		<input type="hidden" name="wpdcg_post_type" value="product">

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Product Options', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_wc_count"><?php esc_html_e( 'Count', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_wc_count" name="wpdcg_count" value="5" min="1" max="200" class="small-text" required>
					<p class="wpdcg-field__hint"><?php esc_html_e( '1–200 products per run. Each product gets a random price, SKU, and stock status.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_wc_status"><?php esc_html_e( 'Status', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_wc_status" name="wpdcg_status">
						<option value="publish"><?php esc_html_e( 'Published', 'quickdemo-content-generator' ); ?></option>
						<option value="draft"><?php esc_html_e( 'Draft', 'quickdemo-content-generator' ); ?></option>
					</select>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_wc_product_type"><?php esc_html_e( 'Product Type', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_wc_product_type" name="wpdcg_product_type">
						<option value="simple"><?php esc_html_e( 'Simple product', 'quickdemo-content-generator' ); ?></option>
						<option value="variable"><?php esc_html_e( 'Variable product', 'quickdemo-content-generator' ); ?></option>
					</select>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Variable products include Color and Size attributes plus generated child variations with their own prices and SKUs.', 'quickdemo-content-generator' ); ?></p>
					<div id="wpdcg-variable-preview" class="wpdcg-variable-preview" hidden>
						<div class="wpdcg-variable-preview__title"><?php esc_html_e( 'Generated variable setup', 'quickdemo-content-generator' ); ?></div>
						<div class="wpdcg-variable-preview__row">
							<span><?php esc_html_e( 'Attributes', 'quickdemo-content-generator' ); ?></span>
							<strong><?php esc_html_e( 'Color', 'quickdemo-content-generator' ); ?></strong>
							<strong><?php esc_html_e( 'Size', 'quickdemo-content-generator' ); ?></strong>
						</div>
						<div class="wpdcg-variable-preview__row">
							<span><?php esc_html_e( 'Variations', 'quickdemo-content-generator' ); ?></span>
							<code><?php esc_html_e( 'Black / Small', 'quickdemo-content-generator' ); ?></code>
							<code><?php esc_html_e( 'Navy / Medium', 'quickdemo-content-generator' ); ?></code>
							<code><?php esc_html_e( 'Silver / Large', 'quickdemo-content-generator' ); ?></code>
							<code><?php esc_html_e( 'Black / Large', 'quickdemo-content-generator' ); ?></code>
						</div>
					</div>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_wc_author"><?php esc_html_e( 'Author', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_wc_author" name="wpdcg_author">
						<?php foreach ( $users as $u ) : ?>
						<option value="<?php echo esc_attr( $u->ID ); ?>"<?php selected( $u->ID, get_current_user_id() ); ?>>
							<?php echo esc_html( $u->display_name ); ?>
						</option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Taxonomy Terms', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" name="wpdcg_auto_terms" value="1">
						<?php esc_html_e( 'Auto-generate product categories and tags if none are selected', 'quickdemo-content-generator' ); ?>
					</label>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Creates sample product categories and tags and assigns them across products.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
		</div>

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Content', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'AI Content', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" id="wpdcg_wc_ai_toggle" name="wpdcg_ai_enabled" value="1" <?php disabled( ! $ai_text_supported ); ?>>
						<?php esc_html_e( 'Generate product names and descriptions from a client topic', 'quickdemo-content-generator' ); ?>
					</label>
					<?php if ( ! $ai_text_supported ) : ?>
						<p class="wpdcg-field__hint">
							<?php
							printf(
								/* translators: %s: WordPress Connectors settings URL */
								wp_kses_post( __( 'Configure a WordPress AI connector in <a href="%s">Settings</a> to enable AI content.', 'quickdemo-content-generator' ) ),
								esc_url( admin_url( 'options-connectors.php' ) )
							);
							?>
						</p>
					<?php endif; ?>
					<div id="wpdcg-wc-ai-wrap" style="display:none;margin-top:10px">
						<p>
							<label for="wpdcg_wc_ai_topic" style="font-size:12px;color:#50575e;font-weight:600;"><?php esc_html_e( 'Client topic', 'quickdemo-content-generator' ); ?></label><br>
							<input type="text" id="wpdcg_wc_ai_topic" name="wpdcg_ai_topic" class="regular-text" placeholder="<?php esc_attr_e( 'Example: handmade skincare store for sensitive skin', 'quickdemo-content-generator' ); ?>">
						</p>
						<p>
							<label for="wpdcg_wc_ai_audience" style="font-size:12px;color:#50575e;font-weight:600;"><?php esc_html_e( 'Audience', 'quickdemo-content-generator' ); ?></label><br>
							<input type="text" id="wpdcg_wc_ai_audience" name="wpdcg_ai_audience" class="regular-text" placeholder="<?php esc_attr_e( 'Example: eco-conscious shoppers', 'quickdemo-content-generator' ); ?>">
						</p>
						<p>
							<label for="wpdcg_wc_ai_tone" style="font-size:12px;color:#50575e;font-weight:600;"><?php esc_html_e( 'Tone', 'quickdemo-content-generator' ); ?></label><br>
							<select id="wpdcg_wc_ai_tone" name="wpdcg_ai_tone">
								<option value="professional"><?php esc_html_e( 'Professional', 'quickdemo-content-generator' ); ?></option>
								<option value="friendly"><?php esc_html_e( 'Friendly', 'quickdemo-content-generator' ); ?></option>
								<option value="luxury"><?php esc_html_e( 'Luxury', 'quickdemo-content-generator' ); ?></option>
								<option value="casual"><?php esc_html_e( 'Casual', 'quickdemo-content-generator' ); ?></option>
								<option value="technical"><?php esc_html_e( 'Technical', 'quickdemo-content-generator' ); ?></option>
							</select>
						</p>
						<label class="wpdcg-check">
							<input type="checkbox" name="wpdcg_ai_image" value="1" <?php disabled( ! $ai_image_supported ); ?>>
							<?php esc_html_e( 'Generate topic-based product images with AI', 'quickdemo-content-generator' ); ?>
						</label>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'AI images can be slow. When enabled, QuickDemo uses the client topic for the parent product image, product gallery images, and variable-product variation thumbnails. For best reliability, generate 1–2 products per run or keep the built-in placeholder image option checked as fallback.', 'quickdemo-content-generator' ); ?></p>
						<?php if ( ! $ai_image_supported ) : ?>
							<p class="wpdcg-field__hint"><?php esc_html_e( 'No configured connector currently supports image generation.', 'quickdemo-content-generator' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_wc_paragraph_count"><?php esc_html_e( 'Paragraphs', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_wc_paragraph_count" name="wpdcg_paragraph_count" value="2" min="1" max="8" class="small-text">
					<p class="wpdcg-field__hint">
						<?php esc_html_e( 'Controls description depth (1–8).', 'quickdemo-content-generator' ); ?>
						<strong><?php esc_html_e( '1–2:', 'quickdemo-content-generator' ); ?></strong> <?php esc_html_e( 'paragraphs only.', 'quickdemo-content-generator' ); ?>
						<strong><?php esc_html_e( '3–4:', 'quickdemo-content-generator' ); ?></strong> <?php esc_html_e( 'adds h2, h3 and a list.', 'quickdemo-content-generator' ); ?>
						<strong><?php esc_html_e( '5–6:', 'quickdemo-content-generator' ); ?></strong> <?php esc_html_e( 'adds h4 and a blockquote.', 'quickdemo-content-generator' ); ?>
						<strong><?php esc_html_e( '7–8:', 'quickdemo-content-generator' ); ?></strong> <?php esc_html_e( 'full set — all heading levels, list, blockquote, and table.', 'quickdemo-content-generator' ); ?>
					</p>
				</div>
			</div>
		</div>

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Scheduling', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Date Range', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" id="wpdcg_wc_date_toggle" value="1">
						<?php esc_html_e( 'Spread products across a custom date range', 'quickdemo-content-generator' ); ?>
					</label>
					<div id="wpdcg-wc-date-wrap" style="display:none">
						<div class="wpdcg-date-row">
							<label for="wpdcg_wc_date_from"><?php esc_html_e( 'From', 'quickdemo-content-generator' ); ?></label>
							<input type="date" id="wpdcg_wc_date_from" name="wpdcg_date_from">
							<label for="wpdcg_wc_date_to"><?php esc_html_e( 'To', 'quickdemo-content-generator' ); ?></label>
							<input type="date" id="wpdcg_wc_date_to" name="wpdcg_date_to">
						</div>
						<p class="wpdcg-field__hint"><?php esc_html_e( 'Each product gets a random date within this range.', 'quickdemo-content-generator' ); ?></p>
					</div>
				</div>
			</div>
		</div>

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Media', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Featured Image', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" name="wpdcg_featured_image_generate" value="1">
						<?php esc_html_e( 'Use a built-in placeholder product image', 'quickdemo-content-generator' ); ?>
					</label>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Creates unique gradient images via PHP GD: a parent featured image, three product gallery images, and one image per generated variation. If AI images are enabled, these placeholders are used as fallback when an AI image fails.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
		</div>

		<?php
		// Product taxonomy term chips (product_cat, product_tag, etc.).
		$wc_has_terms = false;
		foreach ( $wc_taxonomies as $tax_slug => $tax_obj ) {
			$terms = get_terms( array( 'taxonomy' => $tax_slug, 'hide_empty' => false ) );
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$wc_has_terms = true;
				break;
			}
		}
		if ( $wc_has_terms ) :
		?>
		<div style="border-top:1px solid #f6f7f7;padding:14px 20px 4px">
			<div class="wpdcg-section-title">
				<?php
				echo wp_kses(
					__( 'Terms <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#a7aaad;font-size:11px;">for Product</span>', 'quickdemo-content-generator' ),
					array( 'span' => array( 'style' => array() ) )
				);
				?>
			</div>
			<div class="wpdcg-product-type-summary" aria-live="polite">
				<span class="wpdcg-product-type-summary__label"><?php esc_html_e( 'Selected product type', 'quickdemo-content-generator' ); ?></span>
				<strong id="wpdcg-product-type-summary-value"><?php esc_html_e( 'Simple product', 'quickdemo-content-generator' ); ?></strong>
				<span id="wpdcg-product-type-summary-detail"><?php esc_html_e( 'Creates regular demo products with price, SKU, and stock data.', 'quickdemo-content-generator' ); ?></span>
			</div>
			<p class="wpdcg-field__hint" style="margin:4px 0 0;">
				<?php esc_html_e( 'These checkboxes assign categories, tags, and attributes only. Product type and variations are controlled separately.', 'quickdemo-content-generator' ); ?>
			</p>
		</div>
		<?php foreach ( $wc_taxonomies as $tax_slug => $tax_obj ) :
			$terms = get_terms( array( 'taxonomy' => $tax_slug, 'hide_empty' => false ) );
			if ( is_wp_error( $terms ) || empty( $terms ) ) { continue; }
		?>
		<div class="wpdcg-terms-wrap">
			<div class="wpdcg-terms-name"><?php echo esc_html( $tax_obj->labels->name ); ?></div>
			<div class="wpdcg-chips">
				<?php foreach ( $terms as $term ) : ?>
				<label class="wpdcg-chip">
					<input type="checkbox"
						name="wpdcg_terms[<?php echo esc_attr( $tax_slug ); ?>][]"
						value="<?php echo esc_attr( $term->term_id ); ?>">
					<?php echo esc_html( $term->name ); ?>
					<span class="wpdcg-chip__count"><?php echo (int) $term->count; ?></span>
				</label>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endforeach; ?>
		<?php endif; ?>

		<div class="wpdcg-card__foot">
			<div class="wpdcg-product-type-footer-summary" aria-live="polite">
				<span><?php esc_html_e( 'Product type', 'quickdemo-content-generator' ); ?></span>
				<strong id="wpdcg-product-type-footer-value"><?php esc_html_e( 'Simple product', 'quickdemo-content-generator' ); ?></strong>
			</div>
			<button type="submit" class="button button-primary wpdcg-generate-btn">
				<span class="wpdcg-spinner-icon"></span>
				<span class="wpdcg-btn-text"><?php esc_html_e( 'Generate Demo Products', 'quickdemo-content-generator' ); ?></span>
			</button>
		</div>
		<div class="wpdcg-progress" style="display:none" aria-hidden="true">
			<div class="wpdcg-progress__bar"></div>
		</div>
	</form>
	</div>
</div>

<?php /* ── WC Reviews ─────────────────────────────────────────────── */ ?>
<div class="wpdcg-card wpdcg-accordion">

	<button type="button" class="wpdcg-card__head wpdcg-accordion__trigger" aria-expanded="false" aria-controls="wpdcg-wc-reviews-panel">
		<span class="dashicons dashicons-star-filled"></span>
		<h2><?php esc_html_e( 'Generate Product Reviews', 'quickdemo-content-generator' ); ?></h2>
		<span class="dashicons dashicons-arrow-down-alt2 wpdcg-accordion__icon"></span>
	</button>

	<div id="wpdcg-wc-reviews-panel" class="wpdcg-accordion__panel" hidden>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-generate-form">
		<?php wp_nonce_field( 'wpdcg_generate_woo_reviews', 'wpdcg_generate_woo_reviews_nonce' ); ?>
		<input type="hidden" name="action" value="wpdcg_generate_woo_reviews">

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Review Options', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_reviews_attach_to"><?php esc_html_e( 'Attach To', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_reviews_attach_to" name="wpdcg_reviews_attach_to">
						<option value="all"><?php esc_html_e( 'All demo products', 'quickdemo-content-generator' ); ?></option>
						<option value="latest_batch"><?php esc_html_e( 'Latest product batch only', 'quickdemo-content-generator' ); ?></option>
					</select>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_reviews_per_product"><?php esc_html_e( 'Per Product', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_reviews_per_product" name="wpdcg_reviews_per_product" value="3" min="1" max="10" class="small-text" required>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Reviews per product (1–10). Ratings are randomised 1–5 stars.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
		</div>

		<div class="wpdcg-card__foot">
			<button type="submit" class="button button-primary wpdcg-generate-btn">
				<span class="wpdcg-spinner-icon"></span>
				<span class="wpdcg-btn-text"><?php esc_html_e( 'Generate Product Reviews', 'quickdemo-content-generator' ); ?></span>
			</button>
		</div>
		<div class="wpdcg-progress" style="display:none" aria-hidden="true">
			<div class="wpdcg-progress__bar"></div>
		</div>
	</form>
	</div>
</div>

<?php /* ── WC Orders ──────────────────────────────────────────────── */ ?>
<div class="wpdcg-card wpdcg-accordion">

	<button type="button" class="wpdcg-card__head wpdcg-accordion__trigger" aria-expanded="false" aria-controls="wpdcg-wc-orders-panel">
		<span class="dashicons dashicons-clipboard"></span>
		<h2><?php esc_html_e( 'Generate Demo Orders', 'quickdemo-content-generator' ); ?></h2>
		<span class="dashicons dashicons-arrow-down-alt2 wpdcg-accordion__icon"></span>
	</button>

	<div id="wpdcg-wc-orders-panel" class="wpdcg-accordion__panel" hidden>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-generate-form">
		<?php wp_nonce_field( 'wpdcg_generate_woo_orders', 'wpdcg_generate_woo_orders_nonce' ); ?>
		<input type="hidden" name="action" value="wpdcg_generate_woo_orders">

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Order Options', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_orders_count"><?php esc_html_e( 'Count', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_orders_count" name="wpdcg_orders_count" value="5" min="1" max="50" class="small-text" required>
					<p class="wpdcg-field__hint"><?php esc_html_e( '1–50 orders per run.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_orders_status"><?php esc_html_e( 'Order Status', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<select id="wpdcg_orders_status" name="wpdcg_orders_status">
						<?php foreach ( $wc_order_statuses as $status_key => $status_label ) : ?>
						<option value="<?php echo esc_attr( $status_key ); ?>"<?php selected( $status_key, 'completed' ); ?>>
							<?php echo esc_html( $status_label ); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<p class="wpdcg-field__hint"><?php esc_html_e( 'If demo products exist, they will be added as order line items.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
		</div>

		<div class="wpdcg-card__foot">
			<button type="submit" class="button button-primary wpdcg-generate-btn">
				<span class="wpdcg-spinner-icon"></span>
				<span class="wpdcg-btn-text"><?php esc_html_e( 'Generate Demo Orders', 'quickdemo-content-generator' ); ?></span>
			</button>
		</div>
		<div class="wpdcg-progress" style="display:none" aria-hidden="true">
			<div class="wpdcg-progress__bar"></div>
		</div>
	</form>
	</div>
</div>

<?php endif; /* wc_active */ ?>
<?php endif; /* end woocommerce tab */ ?>

<?php /* ══════════════════════ TAB: EXTRAS ═══════════════════════════════════ */ ?>
<?php if ( 'extras' === $active_tab ) : ?>

<?php /* ── Standalone Media Images ─────────────────────────────────────────── */ ?>
<div class="wpdcg-card">

	<div class="wpdcg-card__head">
		<span class="dashicons dashicons-format-image"></span>
		<h2><?php esc_html_e( 'Generate Media Images', 'quickdemo-content-generator' ); ?></h2>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-generate-form">
		<?php wp_nonce_field( 'wpdcg_generate_media', 'wpdcg_generate_media_nonce' ); ?>
		<input type="hidden" name="action" value="wpdcg_generate_media">

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Image Options', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_media_count"><?php esc_html_e( 'Count', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_media_count" name="wpdcg_media_count" value="5" min="1" max="50" class="small-text" required>
					<p class="wpdcg-field__hint"><?php esc_html_e( '1–50 images saved directly to the Media Library — not attached to any post.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<?php if ( WPDCG_AI_Generator::supports_image_generation() ) : ?>
			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_media_ai_enabled"><?php esc_html_e( 'AI Images', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-toggle">
						<input type="checkbox" id="wpdcg_media_ai_enabled" name="wpdcg_media_ai_enabled" value="1">
						<span><?php esc_html_e( 'Generate images with WordPress AI (falls back to GD placeholder if AI fails)', 'quickdemo-content-generator' ); ?></span>
					</label>
				</div>
			</div>
			<div id="wpdcg-media-ai-wrap" style="display:none">
				<div class="wpdcg-field">
					<div class="wpdcg-field__label">
						<label for="wpdcg_media_ai_topic"><?php esc_html_e( 'Topic', 'quickdemo-content-generator' ); ?></label>
					</div>
					<div class="wpdcg-field__input">
						<input type="text" id="wpdcg_media_ai_topic" name="wpdcg_media_ai_topic" value="" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. family dental clinic, outdoor furniture store', 'quickdemo-content-generator' ); ?>">
						<p class="wpdcg-field__hint"><?php esc_html_e( 'Describe the subject for the AI-generated images. Each image gets a unique prompt based on this topic.', 'quickdemo-content-generator' ); ?></p>
					</div>
				</div>
			</div>
			<?php else : ?>
			<div class="wpdcg-field">
				<div class="wpdcg-field__label"></div>
				<div class="wpdcg-field__input">
					<p class="wpdcg-field__hint"><?php esc_html_e( 'Generates 1200×630 px GD placeholder images. To use AI-generated images, configure a connector under Settings → Connectors in WordPress 7.0+.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>
			<?php endif; ?>
		</div>

		<div class="wpdcg-card__foot">
			<button type="submit" class="button button-primary wpdcg-generate-btn">
				<span class="wpdcg-spinner-icon"></span>
				<span class="wpdcg-btn-text"><?php esc_html_e( 'Generate Media Images', 'quickdemo-content-generator' ); ?></span>
			</button>
		</div>
		<div class="wpdcg-progress" style="display:none" aria-hidden="true">
			<div class="wpdcg-progress__bar"></div>
		</div>
	</form>
</div>

<?php /* ── Navigation Menu ───────────────────────────────────────────────────── */ ?>
<div class="wpdcg-card">

	<div class="wpdcg-card__head">
		<span class="dashicons dashicons-menu-alt"></span>
		<h2><?php esc_html_e( 'Generate Navigation Menu', 'quickdemo-content-generator' ); ?></h2>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-generate-form">
		<?php wp_nonce_field( 'wpdcg_generate_menu', 'wpdcg_generate_menu_nonce' ); ?>
		<input type="hidden" name="action" value="wpdcg_generate_menu">

		<div class="wpdcg-section">
			<div class="wpdcg-section-title"><?php esc_html_e( 'Menu Options', 'quickdemo-content-generator' ); ?></div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_menu_name"><?php esc_html_e( 'Menu Name', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="text" id="wpdcg_menu_name" name="wpdcg_menu_name" class="regular-text" placeholder="<?php esc_attr_e( 'Leave blank to auto-generate', 'quickdemo-content-generator' ); ?>">
					<p class="wpdcg-field__hint"><?php esc_html_e( 'The name used in Appearance → Menus. A suffix is added automatically if the name already exists.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label">
					<label for="wpdcg_menu_item_count"><?php esc_html_e( 'Top-level items', 'quickdemo-content-generator' ); ?></label>
				</div>
				<div class="wpdcg-field__input">
					<input type="number" id="wpdcg_menu_item_count" name="wpdcg_menu_item_count" value="5" min="3" max="12" class="small-text">
					<p class="wpdcg-field__hint"><?php esc_html_e( '3–12 top-level navigation items chosen from a realistic pool of labels.', 'quickdemo-content-generator' ); ?></p>
				</div>
			</div>

			<div class="wpdcg-field">
				<div class="wpdcg-field__label"><?php esc_html_e( 'Child Items', 'quickdemo-content-generator' ); ?></div>
				<div class="wpdcg-field__input">
					<label class="wpdcg-check">
						<input type="checkbox" name="wpdcg_menu_children" value="1" checked>
						<?php esc_html_e( 'Add child items under the first two top-level parents (up to 4 each)', 'quickdemo-content-generator' ); ?>
					</label>
				</div>
			</div>
		</div>

		<div class="wpdcg-card__foot">
			<button type="submit" class="button button-primary wpdcg-generate-btn">
				<span class="wpdcg-spinner-icon"></span>
				<span class="wpdcg-btn-text"><?php esc_html_e( 'Generate Navigation Menu', 'quickdemo-content-generator' ); ?></span>
			</button>
		</div>
		<div class="wpdcg-progress" style="display:none" aria-hidden="true">
			<div class="wpdcg-progress__bar"></div>
		</div>
	</form>
</div>

<?php endif; /* end extras tab */ ?>

<?php /* ── Batch history card ──────────────────────────────────────────────── */ ?>
<?php if ( ! empty( $batches ) ) : ?>
<div class="wpdcg-card">

	<div class="wpdcg-card__head">
		<span class="dashicons dashicons-list-view"></span>
		<h2><?php esc_html_e( 'Generated Batches', 'quickdemo-content-generator' ); ?></h2>
	</div>

	<table class="wpdcg-batch-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Batch', 'quickdemo-content-generator' ); ?></th>
				<th><?php esc_html_e( 'Type', 'quickdemo-content-generator' ); ?></th>
				<th><?php esc_html_e( 'Items', 'quickdemo-content-generator' ); ?></th>
				<th><?php esc_html_e( 'Created', 'quickdemo-content-generator' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'quickdemo-content-generator' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$display_batches = array_slice( $batches, 0, 30 );
			foreach ( $display_batches as $index => $batch ) :
				$batch_num   = $index + 1;
				$type_slug   = isset( $batch['post_type'] ) ? (string) $batch['post_type'] : '';
				$type_label  = $batch_type_labels[ $type_slug ] ?? ( $pt_labels[ $type_slug ] ?? $type_slug );
			?>
			<tr>
				<td>
					<span class="wpdcg-batch-num">
						<?php
						printf(
							/* translators: %d: sequential batch number */
							esc_html__( 'Batch #%d', 'quickdemo-content-generator' ),
							(int) $batch_num
						);
						?>
					</span>
					<span class="wpdcg-batch-raw"><?php echo esc_html( $batch['id'] ); ?></span>
				</td>
				<td><span class="wpdcg-type-pill"><?php echo esc_html( $type_label ); ?></span></td>
				<td><strong><?php echo (int) $batch['count']; ?></strong></td>
				<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $batch['created'] ) ); ?></td>
				<td>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wpdcg-batch-delete-form" style="display:inline">
						<?php wp_nonce_field( 'wpdcg_delete_batch', 'wpdcg_delete_batch_nonce' ); ?>
						<input type="hidden" name="action" value="wpdcg_delete_batch">
						<input type="hidden" name="wpdcg_batch_id" value="<?php echo esc_attr( $batch['id'] ); ?>">
						<button type="submit" class="button button-small"><?php esc_html_e( 'Delete', 'quickdemo-content-generator' ); ?></button>
					</form>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( count( $batches ) > 30 ) : ?>
	<p style="padding:10px 16px;margin:0;font-size:12px;color:#8c8f94;border-top:1px solid #f6f7f7;">
		<?php
		printf(
			/* translators: %d: number of older batches not shown */
			esc_html__( '…and %d older batch(es) not shown. Use "Delete All" below to remove everything at once.', 'quickdemo-content-generator' ),
			count( $batches ) - 30
		);
		?>
	</p>
	<?php endif; ?>

</div>
<?php endif; ?>

<?php /* ── Delete all card ──────────────────────────────────────────────────── */ ?>
<?php if ( $total_tracked > 0 ) : ?>
<div class="wpdcg-card wpdcg-card--danger">

	<div class="wpdcg-card__head">
		<span class="dashicons dashicons-trash"></span>
		<h2><?php esc_html_e( 'Delete All Demo Content', 'quickdemo-content-generator' ); ?></h2>
	</div>

	<div class="wpdcg-delete-body">
		<p><?php esc_html_e( 'Permanently deletes all demo content created by this plugin — posts, comments, users, and WooCommerce data — across all batches. Your real site content is never touched.', 'quickdemo-content-generator' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'wpdcg_delete', 'wpdcg_delete_nonce' ); ?>
			<input type="hidden" name="action" value="wpdcg_delete">

			<label class="wpdcg-delete-confirm">
				<input type="checkbox" name="wpdcg_confirm_delete" value="1" id="wpdcg_confirm_delete">
				<?php esc_html_e( 'I understand this is irreversible and will permanently delete all demo content.', 'quickdemo-content-generator' ); ?>
			</label>

			<?php submit_button( __( 'Delete All Demo Content', 'quickdemo-content-generator' ), 'delete', 'wpdcg_submit_delete', false, array( 'disabled' => 'disabled' ) ); ?>
		</form>
	</div>

</div>
<?php endif; ?>

</div><!-- /.wrap -->
