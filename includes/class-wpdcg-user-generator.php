<?php
/**
 * User Generator for Loremix Demo Content Generator.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_User_Generator
 */
class WPDCG_User_Generator {

	const USER_META_KEY = '_wpdcg_generated';
	const BATCH_META_KEY = '_wpdcg_batch_id';

	private static $first_names = array(
		'Alex', 'Sam', 'Jordan', 'Casey', 'Riley', 'Morgan', 'Jamie', 'Quinn',
		'Avery', 'Blake', 'Drew', 'Peyton', 'Skyler', 'Reese', 'Logan',
		'Taylor', 'Dana', 'Jesse', 'Cameron', 'Rowan', 'Harper', 'Emery',
		'Finley', 'River', 'Sage', 'Indigo', 'Marlowe', 'Phoenix', 'Remi', 'Kai',
	);

	private static $last_names = array(
		'Morgan', 'Taylor', 'Lee', 'Wilson', 'Parker', 'Davis', 'Roberts',
		'Anderson', 'Thompson', 'Harris', 'Martinez', 'Clark', 'Lewis', 'Walker',
		'Young', 'Reed', 'Foster', 'Kim', 'Stone', 'Ellis', 'Mitchell', 'Turner',
		'Collins', 'Baker', 'Rivera', 'Cooper', 'Cox', 'Howard', 'Ward', 'Torres',
	);

	private static $domains = array(
		'gmail.com', 'yahoo.com', 'outlook.com', 'icloud.com', 'protonmail.com',
		'hotmail.com', 'example.com', 'mail.com', 'fastmail.com',
	);

	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Generates demo WordPress users.
	 *
	 * @param array $args {
	 *   @type int    $count  Number of users to create (1–50). Default 5.
	 *   @type string $role   WordPress role slug. Default 'subscriber'.
	 * }
	 * @return array|WP_Error
	 */
	public function generate( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'count' => 5,
				'role'  => 'subscriber',
			)
		);

		$count = max( 1, min( absint( $args['count'] ), 50 ) );
		$role  = sanitize_key( $args['role'] );

		if ( ! in_array( $role, array_keys( wp_roles()->roles ), true ) || $this->role_can_manage_options( $role ) ) {
			$role = 'subscriber';
		}

		$batch_id = 'batch_' . gmdate( 'Ymd_His' ) . '_' . substr( md5( uniqid( '', true ) ), 0, 6 );
		$created  = array();
		$errors   = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$first    = self::$first_names[ array_rand( self::$first_names ) ];
			$last     = self::$last_names[ array_rand( self::$last_names ) ];
			$domain   = self::$domains[ array_rand( self::$domains ) ];
			$login    = strtolower( $first . '.' . $last ) . wp_rand( 10, 99 );
			$email    = $login . '@' . $domain;

			// Avoid login/email collisions.
			if ( username_exists( $login ) || email_exists( $email ) ) {
				$login .= wp_rand( 100, 999 );
				$email  = $login . '@' . $domain;
			}

			$user_id = wp_insert_user(
				array(
					'user_login'   => $login,
					'user_pass'    => wp_generate_password( 16, true, true ),
					'user_email'   => $email,
					'first_name'   => $first,
					'last_name'    => $last,
					'display_name' => $first . ' ' . $last,
					'role'         => $role,
				)
			);

			if ( $user_id && ! is_wp_error( $user_id ) ) {
				$user_id = absint( $user_id );
				update_user_meta( $user_id, self::USER_META_KEY, '1' );
				update_user_meta( $user_id, self::BATCH_META_KEY, $batch_id );
				update_user_meta( $user_id, WPDCG_Generator::SOURCE_META_KEY, WPDCG_Generator::SOURCE_VALUE );
				$created[] = $user_id;
			} else {
				$msg = is_wp_error( $user_id ) ? $user_id->get_error_message() : 'Unknown error.';
				$errors[] = sprintf( 'Failed to create user %s: %s', $login, $msg );
			}
		}

		if ( ! empty( $created ) ) {
			WPDCG_Tracker::add_user_ids( $created );
			WPDCG_Tracker::add_batch( $batch_id, '_user', $created );
		}

		return array(
			'created'  => $created,
			'errors'   => $errors,
			'batch_id' => $batch_id,
		);
	}

	/**
	 * Blocks generated users from receiving full site-management capability.
	 *
	 * @param string $role Role slug.
	 * @return bool
	 */
	private function role_can_manage_options( string $role ): bool {
		$role_obj = get_role( $role );
		return $role_obj && ! empty( $role_obj->capabilities['manage_options'] );
	}
}
