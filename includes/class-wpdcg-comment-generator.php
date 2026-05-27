<?php
/**
 * Comment Generator for Loremix Demo Content Generator.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_Comment_Generator
 */
class WPDCG_Comment_Generator {

	const COMMENT_META_KEY = '_wpdcg_generated';
	const BATCH_META_KEY   = '_wpdcg_batch_id';

	/**
	 * Sample comment texts.
	 *
	 * @var string[]
	 */
	private static $texts = array(
		'This was easy to follow and gave me a clear next step. The examples made the main point much easier to understand.',
		'Helpful breakdown. I especially liked how the sections were organized around practical decisions instead of abstract theory.',
		'This answers a question I had while comparing a few different approaches. Bookmarking it for later reference.',
		'The explanation feels balanced and realistic. It covers the benefits without ignoring the details that usually slow a project down.',
		'Useful article. I would be interested to see a follow-up with a checklist or a few implementation examples.',
		'The section on planning before execution is especially relevant. That small step saves a lot of rework later.',
		'Clear and concise. This is the kind of content that works well for both clients and internal teams.',
		'I appreciate the practical tone here. It makes the topic feel approachable without oversimplifying it.',
		'Good points throughout. The structure makes it easy to scan and still get the important details.',
		'This helped clarify the trade-offs for me. I can see how the same advice would apply across different projects.',
		'Strong summary. The article gives enough context to be useful without becoming too long.',
		'I shared this with a teammate because it explains the workflow in plain language.',
		'The examples are helpful. They make the advice feel grounded in real project work.',
		'Nice write-up. I would like to see more content like this around launch planning and ongoing maintenance.',
		'This is a good reminder that small content and layout decisions can have a big impact on the final experience.',
		'Well explained. The article gives a useful framework for reviewing similar work in the future.',
	);

	/**
	 * Sample commenter names.
	 *
	 * @var string[]
	 */
	private static $names = array(
		'Alex Morgan', 'Sam Taylor', 'Jordan Lee', 'Casey Wilson', 'Riley Parker',
		'Morgan Davis', 'Jamie Roberts', 'Quinn Anderson', 'Avery Thompson', 'Blake Harris',
		'Drew Martinez', 'Peyton Clark', 'Skyler Lewis', 'Reese Walker', 'Logan Young',
		'Taylor Reed', 'Dana Foster', 'Jesse Kim', 'Cameron Stone', 'Rowan Ellis',
	);

	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Generates demo comments and attaches them to existing demo posts.
	 *
	 * @param array $args {
	 *   @type string $attach_to  'all' | 'latest_batch'. Default 'all'.
	 *   @type int    $per_post   Comments per post (1–20). Default 3.
	 *   @type string $status     'approve' | 'hold'. Default 'approve'.
	 *   @type bool   $threaded   Enable threaded (nested) replies. Default true.
	 * }
	 * @return array|WP_Error
	 */
	public function generate( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'attach_to' => 'all',
				'per_post'  => 3,
				'status'    => 'approve',
				'threaded'  => true,
			)
		);

		$per_post = max( 1, min( absint( $args['per_post'] ), 20 ) );
		$status   = in_array( $args['status'], array( 'approve', 'hold' ), true ) ? $args['status'] : 'approve';
		$threaded = (bool) $args['threaded'];

		$post_ids = $this->get_target_post_ids( $args['attach_to'] );

		if ( empty( $post_ids ) ) {
			return new WP_Error(
				'wpdcg_no_posts',
				__( 'No demo posts found to attach comments to. Generate some posts first.', 'loremix-demo-content-generator' )
			);
		}

		$batch_id = 'batch_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( uniqid( '', true ) ), 0, 6 );
		$created  = array();
		$errors   = array();

		foreach ( $post_ids as $post_id ) {
			$post_id    = absint( $post_id );
			$parent_ids = array( 0 );

			for ( $i = 0; $i < $per_post; $i++ ) {
				$parent_id = 0;
				if ( $threaded && count( $parent_ids ) > 1 && wp_rand( 0, 1 ) ) {
					// Pick a random existing comment as parent (depth capped at 2).
					$candidate = $parent_ids[ array_rand( array_slice( $parent_ids, 1 ) ) + 1 ];
					if ( $this->comment_depth( $candidate ) < 2 ) {
						$parent_id = $candidate;
					}
				}

				$author     = $this->random_author();
				$comment_id = wp_insert_comment(
					array(
						'comment_post_ID'      => $post_id,
						'comment_author'       => $author['name'],
						'comment_author_email' => $author['email'],
						'comment_author_url'   => '',
						'comment_content'      => self::$texts[ array_rand( self::$texts ) ],
						'comment_type'         => '',
						'comment_parent'       => $parent_id,
						'comment_approved'     => 'approve' === $status ? 1 : 0,
						'comment_date'         => current_time( 'mysql' ),
						'comment_date_gmt'     => current_time( 'mysql', true ),
					)
				);

				if ( $comment_id && ! is_wp_error( $comment_id ) ) {
					$comment_id = absint( $comment_id );
					add_comment_meta( $comment_id, self::COMMENT_META_KEY, '1', true );
					add_comment_meta( $comment_id, self::BATCH_META_KEY,   $batch_id, true );
					add_comment_meta( $comment_id, WPDCG_Generator::SOURCE_META_KEY, WPDCG_Generator::SOURCE_VALUE, true );
					$created[]    = $comment_id;
					$parent_ids[] = $comment_id;
				} else {
					$errors[] = sprintf( 'Failed to create comment for post %d.', $post_id );
				}
			}
		}

		if ( ! empty( $created ) ) {
			WPDCG_Tracker::add_comment_ids( $created );
			WPDCG_Tracker::add_batch( $batch_id, '_comment', $created );
		}

		return array(
			'created'  => $created,
			'errors'   => $errors,
			'batch_id' => $batch_id,
		);
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private function get_target_post_ids( string $attach_to ): array {
		if ( 'latest_batch' === $attach_to ) {
			foreach ( WPDCG_Tracker::get_batches() as $batch ) {
				if ( isset( $batch['post_type'] ) && '_' !== substr( $batch['post_type'], 0, 1 ) ) {
					return isset( $batch['ids'] ) ? array_map( 'absint', (array) $batch['ids'] ) : array();
				}
			}
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => array( 'publish', 'draft' ),
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

	private function comment_depth( int $comment_id ): int {
		$depth = 0;
		$c     = get_comment( $comment_id );
		while ( $c && $c->comment_parent ) {
			$depth++;
			$c = get_comment( $c->comment_parent );
			if ( $depth > 5 ) {
				break;
			}
		}
		return $depth;
	}

	private function random_author(): array {
		$name    = self::$names[ array_rand( self::$names ) ];
		$slug    = strtolower( str_replace( ' ', '.', $name ) );
		$domains = array( 'gmail.com', 'yahoo.com', 'outlook.com', 'example.com' );
		return array(
			'name'  => $name,
			'email' => $slug . '@' . $domains[ array_rand( $domains ) ],
		);
	}
}
