<?php
/**
 * Presets manager for Loremix Demo Content Generator.
 *
 * Stores and retrieves named generation presets per tab so users can
 * save and reload their favourite generation settings without re-entering
 * them on every run.
 *
 * @package Loremix_Demo_Content_Generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WPDCG_Presets
 */
class WPDCG_Presets {

	/** wp_options key for all saved presets. */
	const OPTION_KEY = 'wpdcg_presets';

	/** Maximum presets stored per tab. */
	const MAX_PRESETS_PER_TAB = 30;

	/**
	 * Valid tabs that can own presets.
	 *
	 * @var string[]
	 */
	private static $valid_tabs = array( 'posts', 'comments', 'users', 'woocommerce', 'extras' );

	/**
	 * Whether a tab slug is supported for presets.
	 *
	 * @param string $tab Tab slug.
	 * @return bool
	 */
	public static function is_valid_tab( string $tab ): bool {
		return in_array( $tab, self::$valid_tabs, true );
	}

	/**
	 * Returns all presets saved for a specific tab, sorted alphabetically by name.
	 *
	 * @param string $tab Tab slug (posts|comments|users|woocommerce|extras).
	 * @return array[]
	 */
	public static function get_for_tab( string $tab ): array {
		if ( ! self::is_valid_tab( $tab ) ) {
			return array();
		}

		$all = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $all ) ) {
			return array();
		}
		$filtered = array_values(
			array_filter(
				$all,
				function ( $p ) use ( $tab ) {
					return isset( $p['tab'] ) && $p['tab'] === $tab;
				}
			)
		);
		usort(
			$filtered,
			function ( $a, $b ) {
				return strcmp( $a['name'] ?? '', $b['name'] ?? '' );
			}
		);
		return $filtered;
	}

	/**
	 * Saves a named preset, overwriting any existing preset with the same name+tab.
	 *
	 * @param string $name Preset name (already sanitized by caller).
	 * @param string $tab  Tab slug.
	 * @param array  $data Key/value form field data.
	 * @return bool
	 */
	public static function save( string $name, string $tab, array $data ): bool {
		if ( '' === $name || ! self::is_valid_tab( $tab ) ) {
			return false;
		}

		$all = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $all ) ) {
			$all = array();
		}

		foreach ( $all as &$preset ) {
			if ( isset( $preset['name'], $preset['tab'] ) && $preset['name'] === $name && $preset['tab'] === $tab ) {
				$preset['data']    = $data;
				$preset['updated'] = time();
				return (bool) update_option( self::OPTION_KEY, $all, false );
			}
		}
		unset( $preset );

		$all[] = array(
			'name'    => $name,
			'tab'     => $tab,
			'data'    => $data,
			'created' => time(),
			'updated' => time(),
		);

		$tab_indexes = array();
		foreach ( $all as $index => $preset ) {
			if ( isset( $preset['tab'] ) && $preset['tab'] === $tab ) {
				$tab_indexes[] = $index;
			}
		}

		while ( count( $tab_indexes ) > self::MAX_PRESETS_PER_TAB ) {
			$remove_index = array_shift( $tab_indexes );
			unset( $all[ $remove_index ] );
		}

		$all = array_values( $all );
		return (bool) update_option( self::OPTION_KEY, $all, false );
	}

	/**
	 * Returns the stored field data for a specific named preset, or null if not found.
	 *
	 * @param string $name Preset name.
	 * @param string $tab  Tab slug.
	 * @return array|null
	 */
	public static function load( string $name, string $tab ): ?array {
		$all = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $all ) ) {
			return null;
		}
		foreach ( $all as $preset ) {
			if ( isset( $preset['name'], $preset['tab'] ) && $preset['name'] === $name && $preset['tab'] === $tab ) {
				return isset( $preset['data'] ) ? (array) $preset['data'] : null;
			}
		}
		return null;
	}

	/**
	 * Deletes a specific named preset.
	 *
	 * @param string $name Preset name.
	 * @param string $tab  Tab slug.
	 * @return bool True if removed; false if not found.
	 */
	public static function delete_preset( string $name, string $tab ): bool {
		if ( '' === $name || ! self::is_valid_tab( $tab ) ) {
			return false;
		}

		$all = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $all ) ) {
			return false;
		}
		$filtered = array_values(
			array_filter(
				$all,
				function ( $p ) use ( $name, $tab ) {
					return ! ( isset( $p['name'], $p['tab'] ) && $p['name'] === $name && $p['tab'] === $tab );
				}
			)
		);
		if ( count( $filtered ) === count( $all ) ) {
			return false;
		}
		return (bool) update_option( self::OPTION_KEY, $filtered, false );
	}
}
