/* global jQuery, wpdcgAdmin */
( function ( $ ) {
	'use strict';

	var cfg = wpdcgAdmin || {};

	$( document ).ready( function () {

		// ── Post type change → server-side taxonomy reload ────────────────────
		$( '#wpdcg_post_type' ).on( 'change', function () {
			var url = new URL( window.location.href );
			url.searchParams.set( 'wpdcg_preview_type', $( this ).val() );
			window.location.href = url.toString();
		} );

		// ── Excerpt toggle ────────────────────────────────────────────────────
		$( '#wpdcg_exc_toggle' ).on( 'change', function () {
			$( '#wpdcg-exc-wrap' ).toggle( this.checked );
		} );

		// ── AI content toggles (posts tab + products tab + media tab) ────────
		$( '#wpdcg_ai_toggle' ).on( 'change', function () {
			$( '#wpdcg-ai-wrap' ).toggle( this.checked );
		} );
		$( '#wpdcg_wc_ai_toggle' ).on( 'change', function () {
			$( '#wpdcg-wc-ai-wrap' ).toggle( this.checked );
		} );
		$( '#wpdcg_media_ai_enabled' ).on( 'change', function () {
			$( '#wpdcg-media-ai-wrap' ).toggle( this.checked );
		} );

		// ── WooCommerce product type preview ─────────────────────────────────
		$( '#wpdcg_wc_product_type' ).on( 'change', function () {
			var isVariable = 'variable' === $( this ).val();
			var label = isVariable ? 'Variable product' : 'Simple product';
			var detail = isVariable
				? 'Creates Color and Size attributes plus four child variations.'
				: 'Creates regular demo products with price, SKU, and stock data.';

			$( '#wpdcg-variable-preview' ).prop( 'hidden', ! isVariable );
			$( '#wpdcg-product-type-summary-value, #wpdcg-product-type-footer-value' ).text( label );
			$( '#wpdcg-product-type-summary-detail' ).text( detail );
		} ).trigger( 'change' );

		// ── Date range toggles ────────────────────────────────────────────────
		$( '#wpdcg_date_toggle' ).on( 'change', function () {
			$( '#wpdcg-date-wrap' ).toggle( this.checked );
		} );
		$( '#wpdcg_wc_date_toggle' ).on( 'change', function () {
			$( '#wpdcg-wc-date-wrap' ).toggle( this.checked );
		} );

		// ── WooCommerce accordion ─────────────────────────────────────────────
		$( document ).on( 'click', '.wpdcg-accordion__trigger', function () {
			var $trigger   = $( this );
			var $accordion = $trigger.closest( '.wpdcg-accordion' );
			var panelId    = $trigger.attr( 'aria-controls' );
			var $panel     = $( '#' + panelId );
			var isOpen     = $accordion.hasClass( 'is-open' );

			if ( isOpen ) {
				$accordion.removeClass( 'is-open' );
				$trigger.attr( 'aria-expanded', 'false' );
				$trigger.find( '.wpdcg-accordion__icon' )
					.removeClass( 'dashicons-arrow-up-alt2' )
					.addClass( 'dashicons-arrow-down-alt2' );
				$panel.prop( 'hidden', true );
				return;
			}

			$accordion.addClass( 'is-open' );
			$trigger.attr( 'aria-expanded', 'true' );
			$trigger.find( '.wpdcg-accordion__icon' )
				.removeClass( 'dashicons-arrow-down-alt2' )
				.addClass( 'dashicons-arrow-up-alt2' );
			$panel.prop( 'hidden', false );
		} );

		// ── Batch delete confirmation ─────────────────────────────────────────
		$( '.wpdcg-batch-delete-form' ).on( 'submit', function ( e ) {
			if ( ! window.confirm( cfg.confirmBatchDelete ) ) {
				e.preventDefault();
			}
		} );

		// ── Delete-all confirmation gate ─────────────────────────────────────
		$( '#wpdcg_confirm_delete' ).on( 'change', function () {
			$( '#wpdcg_submit_delete' ).prop( 'disabled', ! this.checked );
		} );

		// ── Chip checked-state sync ───────────────────────────────────────────
		$( document ).on( 'change', '.wpdcg-chip input[type="checkbox"]', function () {
			$( this ).closest( '.wpdcg-chip' ).toggleClass( 'is-checked', this.checked );
		} );

		// ── AJAX generation with progress bar ────────────────────────────────
		$( '.wpdcg-generate-form' ).on( 'submit', function ( e ) {
			e.preventDefault();

			var $form     = $( this );
			var $btn      = $form.find( '.wpdcg-generate-btn' );
			var $progress = $form.find( '.wpdcg-progress' );
			var $btnText  = $btn.find( '.wpdcg-btn-text' );
			var subAction = $form.find( 'input[name="action"]' ).val();

			// Create status span once and reuse it on subsequent submits.
			var $status = $form.find( '.wpdcg-status-text' );
			if ( ! $status.length ) {
				$status = $( '<span class="wpdcg-status-text" aria-live="polite"></span>' );
				$btn.after( $status );
			}
			$status.text( buildStatusText( $form ) );

			$btn.addClass( 'is-loading' ).prop( 'disabled', true );
			$btnText.text( cfg.generating || 'Generating…' );
			$progress.show();
			animateProgress( $progress );

			var data = $form.serialize();
			data += '&action=wpdcg_ajax_generate';
			data += '&wpdcg_sub_action=' + encodeURIComponent( subAction );
			data += '&wpdcg_ajax_nonce=' + encodeURIComponent( cfg.ajaxNonce || '' );

			$.post( cfg.ajaxUrl, data )
				.done( function ( response ) {
					if ( response && response.data && response.data.redirect ) {
						window.location.href = response.data.redirect;
					} else {
						window.location.reload();
					}
				} )
				.fail( function () {
					$progress.hide();
					$status.text( '' );
					$btn.removeClass( 'is-loading' ).prop( 'disabled', false );
					$btnText.text( $btn.data( 'original-text' ) || 'Generate' );
					// Fall back to native form submit on AJAX failure.
					$form.off( 'submit' ).submit();
				} );
		} );

		// Store original button text for reset.
		$( '.wpdcg-generate-btn' ).each( function () {
			$( this ).data( 'original-text', $( this ).find( '.wpdcg-btn-text' ).text() );
		} );

		// ── Presets ──────────────────────────────────────────────────────────
		initPresets();

		// ── Restore preset stashed before a post-type navigation ──────────────
		( function () {
			var raw;
			try { raw = sessionStorage.getItem( 'wpdcg_pending_preset' ); } catch ( e ) {}
			if ( ! raw ) {
				return;
			}
			try { sessionStorage.removeItem( 'wpdcg_pending_preset' ); } catch ( e ) {}
			var stashed;
			try { stashed = JSON.parse( raw ); } catch ( e ) {}
			if ( stashed ) {
				populateForm( $( '.wpdcg-generate-form' ).first(), stashed );
			}
		}() );

	} ); // end ready

	// ── Progress bar animation ────────────────────────────────────────────────
	function animateProgress( $progress ) {
		var $bar = $progress.find( '.wpdcg-progress__bar' );
		var pct  = 0;
		var timer = setInterval( function () {
			// Ease toward 90 % then stall until server responds.
			pct += ( 90 - pct ) * 0.06;
			$bar.css( 'width', Math.min( pct, 90 ) + '%' );
			if ( pct >= 89 ) {
				clearInterval( timer );
			}
		}, 200 );
		$progress.data( 'timer', timer );
	}

	// ── Generation status text ────────────────────────────────────────────────
	function buildStatusText( $form ) {
		var action = $form.find( 'input[name="action"]' ).val();
		var count, n;

		if ( 'wpdcg_generate' === action ) {
			n = parseInt( $form.find( '[name="wpdcg_count"]' ).val(), 10 ) || 5;
			var postType = $form.find( '[name="wpdcg_post_type"]' ).val();
			if ( 'product' === postType ) {
				return 'Generating ' + n + ' ' + ( 1 === n ? 'product' : 'products' ) + '…';
			}
			var $ptSelect = $form.find( 'select[name="wpdcg_post_type"]' );
			var label = $ptSelect.length
				? $ptSelect.find( ':selected' ).text().toLowerCase()
				: ( postType || 'post' );
			return 'Generating ' + n + ' ' + label + ( 1 === n ? '' : 's' ) + '…';
		}
		if ( 'wpdcg_generate_comments' === action ) {
			n = parseInt( $form.find( '[name="wpdcg_comments_per_post"]' ).val(), 10 ) || 3;
			return 'Generating ' + n + ' comment' + ( 1 === n ? '' : 's' ) + ' per post…';
		}
		if ( 'wpdcg_generate_users' === action ) {
			n = parseInt( $form.find( '[name="wpdcg_users_count"]' ).val(), 10 ) || 5;
			return 'Generating ' + n + ' ' + ( 1 === n ? 'user' : 'users' ) + '…';
		}
		if ( 'wpdcg_generate_woo_reviews' === action ) {
			n = parseInt( $form.find( '[name="wpdcg_reviews_per_product"]' ).val(), 10 ) || 3;
			return 'Generating ' + n + ' ' + ( 1 === n ? 'review' : 'reviews' ) + ' per product…';
		}
		if ( 'wpdcg_generate_woo_orders' === action ) {
			n = parseInt( $form.find( '[name="wpdcg_orders_count"]' ).val(), 10 ) || 5;
			return 'Generating ' + n + ' ' + ( 1 === n ? 'order' : 'orders' ) + '…';
		}
		if ( 'wpdcg_generate_media' === action ) {
			n = parseInt( $form.find( '[name="wpdcg_media_count"]' ).val(), 10 ) || 5;
			return 'Generating ' + n + ' ' + ( 1 === n ? 'image' : 'images' ) + '…';
		}
		if ( 'wpdcg_generate_menu' === action ) {
			return 'Generating navigation menu…';
		}
		return 'Generating…';
	}

	// ── Presets ───────────────────────────────────────────────────────────────
	function initPresets() {
		var tab = cfg.activeTab || 'posts';

		// Save As.
		$( document ).on( 'click', '.wpdcg-preset-save-btn', function () {
			var name = window.prompt( cfg.savePreset || 'Preset name:' );
			if ( ! name || ! name.trim() ) {
				return;
			}
			name = name.trim();

			// Collect form data from the nearest form.
			var $form = $( this ).closest( '.wpdcg-card' ).find( '.wpdcg-generate-form' );
			if ( ! $form.length ) {
				$form = $( '.wpdcg-generate-form' ).first();
			}
			var fields = serializeFormToObject( $form );

			$.post( cfg.ajaxUrl, {
				action:       'wpdcg_preset_save',
				preset_nonce: cfg.presetNonce,
				preset_name:  name,
				preset_tab:   tab,
				preset_data:  JSON.stringify( fields )
			} ).done( function ( res ) {
				if ( res && res.success ) {
					rebuildPresetSelect( res.data.presets );
				}
			} );
		} );

		// Load.
		$( document ).on( 'click', '.wpdcg-preset-load-btn', function () {
			var $select = $( this ).closest( '.wpdcg-presets-bar' ).find( '.wpdcg-preset-select' );
			var $option = $select.find( ':selected' );
			if ( ! $option.val() ) {
				return;
			}
			var fields = $option.data( 'fields' );
			if ( ! fields ) {
				return;
			}
			if ( typeof fields === 'string' ) {
				try { fields = JSON.parse( fields ); } catch ( e ) { return; }
			}

			// Scope to the card containing this presets bar (mirrors Save handler).
			var $form = $( this ).closest( '.wpdcg-card' ).find( '.wpdcg-generate-form' ).first();
			if ( ! $form.length ) {
				$form = $( '.wpdcg-generate-form' ).first();
			}
			var $ptEl = $form.find( '#wpdcg_post_type' );

			// If the preset requires a different post type, stash the full
			// preset in sessionStorage before the post-type reload, then
			// apply it on the next page load.
			if ( $ptEl.length && fields.wpdcg_post_type && $ptEl.val() !== fields.wpdcg_post_type ) {
				try { sessionStorage.setItem( 'wpdcg_pending_preset', JSON.stringify( fields ) ); } catch ( e ) {}
				$ptEl.val( fields.wpdcg_post_type ).trigger( 'change' );
				return;
			}

			populateForm( $form, fields );
		} );

		// Delete.
		$( document ).on( 'click', '.wpdcg-preset-delete-btn', function () {
			var $select = $( this ).closest( '.wpdcg-presets-bar' ).find( '.wpdcg-preset-select' );
			var name    = $select.val();
			if ( ! name ) {
				return;
			}
			if ( ! window.confirm( cfg.confirmPresetDelete || 'Delete this preset?' ) ) {
				return;
			}
			$.post( cfg.ajaxUrl, {
				action:       'wpdcg_preset_delete',
				preset_nonce: cfg.presetNonce,
				preset_name:  name,
				preset_tab:   tab
			} ).done( function ( res ) {
				if ( res && res.success ) {
					rebuildPresetSelect( res.data.presets );
				}
			} );
		} );
	}

	function serializeFormToObject( $form ) {
		var obj = {};
		var arr = $form.serializeArray();
		$.each( arr, function ( i, pair ) {
			// Skip nonces and action fields.
			if ( pair.name.indexOf( 'nonce' ) !== -1 || pair.name === 'action' ) {
				return;
			}
			if ( obj[ pair.name ] !== undefined ) {
				if ( ! Array.isArray( obj[ pair.name ] ) ) {
					obj[ pair.name ] = [ obj[ pair.name ] ];
				}
				obj[ pair.name ].push( pair.value );
			} else {
				obj[ pair.name ] = pair.value;
			}
		} );
		return obj;
	}

	function populateForm( $form, fields ) {
		$.each( fields, function ( name, value ) {
			var $el = $form.find( '[name="' + name + '"]' );
			if ( ! $el.length ) {
				return;
			}
			var type = $el.attr( 'type' );
			if ( type === 'checkbox' ) {
				$el.prop( 'checked', !! value ).trigger( 'change' );
			} else if ( type === 'radio' ) {
				$form.find( '[name="' + name + '"][value="' + value + '"]' ).prop( 'checked', true ).trigger( 'change' );
			} else {
				// Only fire 'change' when the value genuinely changes.
				// Unconditionally triggering 'change' on #wpdcg_post_type
				// causes the page to navigate even when nothing changed.
				var prev = $el.val();
				$el.val( value );
				if ( $el.val() !== prev ) {
					$el.trigger( 'change' );
				}
			}
		} );
	}

	function rebuildPresetSelect( presets ) {
		var $bar    = $( '.wpdcg-presets-bar' );
		var $select = $bar.find( '.wpdcg-preset-select' );

		if ( ! $select.length ) {
			// No select yet (was empty bar) — reload so PHP re-renders.
			window.location.reload();
			return;
		}

		$select.find( 'option:not(:first)' ).remove();
		if ( presets && presets.length ) {
			$.each( presets, function ( i, p ) {
				$select.append(
					$( '<option>' )
						.val( p.name )
						.text( p.name )
						.attr( 'data-fields', JSON.stringify( p.data ) )
				);
			} );
		}
		if ( ! presets || ! presets.length ) {
			window.location.reload();
		}
	}

} )( jQuery );
