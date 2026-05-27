<?php
/**
 * Standalone media attachment generator for Loremix Demo Content Generator.
 *
 * Generates placeholder GD images directly to the WordPress Media Library
 * without attaching them to any specific post. Useful for building up a
 * realistic-looking media library for frontend development.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_Media_Generator
 */
class WPDCG_Media_Generator {

	/**
	 * Generates standalone placeholder images and adds them to the Media Library.
	 *
	 * @param array $args {
	 *   @type int    $count      Number of images to generate (1–50). Default 5.
	 *   @type bool   $ai_enabled Use WordPress AI Client image generation. Default false.
	 *   @type string $ai_topic   Topic / subject for AI image prompts. Required when ai_enabled.
	 * }
	 * @return array { created: int[], errors: string[], batch_id: string }
	 */
	public function generate( array $args = array() ): array {
		$count      = max( 1, min( absint( $args['count'] ?? 5 ), 50 ) );
		$ai_enabled = ! empty( $args['ai_enabled'] );
		$ai_topic   = sanitize_text_field( $args['ai_topic'] ?? '' );

		$batch_id = 'batch_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( uniqid( '', true ) ), 0, 6 );
		$created  = array();
		$errors   = array();

		$use_ai = $ai_enabled && '' !== $ai_topic && WPDCG_AI_Generator::supports_image_generation();

		if ( ! $use_ai && ! function_exists( 'imagecreatetruecolor' ) ) {
			return array(
				'created'  => array(),
				'errors'   => array( __( 'PHP GD extension is not available and AI image generation is not configured. Cannot generate images.', 'loremix-demo-content-generator' ) ),
				'batch_id' => '',
			);
		}

		$gd_generator = $use_ai ? null : new WPDCG_Generator();
		$ai_generator = $use_ai ? new WPDCG_AI_Generator() : null;

		for ( $i = 1; $i <= $count; $i++ ) {
			/* translators: %02d: zero-padded image index */
			$title = sprintf( __( 'Loremix Image %02d', 'loremix-demo-content-generator' ), $i );

			if ( $use_ai ) {
				$attachment_id = $ai_generator->generate_standalone_image( $ai_topic, $title, $i, $batch_id );
				if ( is_wp_error( $attachment_id ) ) {
					// Fall back to GD for this image if GD is available.
					if ( function_exists( 'imagecreatetruecolor' ) ) {
						if ( null === $gd_generator ) {
							$gd_generator = new WPDCG_Generator();
						}
						$attachment_id = $gd_generator->generate_standalone_image( $title, $i, $batch_id );
						if ( false === $attachment_id ) {
							/* translators: %d: image index */
							$errors[] = sprintf( __( 'Could not generate image %d.', 'loremix-demo-content-generator' ), $i );
							continue;
						}
					} else {
						$errors[] = $attachment_id->get_error_message();
						continue;
					}
				}
			} else {
				$attachment_id = $gd_generator->generate_standalone_image( $title, $i, $batch_id );
				if ( false === $attachment_id ) {
					/* translators: %d: image index */
					$errors[] = sprintf( __( 'Could not generate image %d.', 'loremix-demo-content-generator' ), $i );
					continue;
				}
			}

			$created[] = $attachment_id;
		}

		if ( ! empty( $created ) ) {
			WPDCG_Tracker::add_ids( $created );
			WPDCG_Tracker::add_batch( $batch_id, '_media', $created );
		}

		return array(
			'created'  => $created,
			'errors'   => $errors,
			'batch_id' => $batch_id,
		);
	}
}
