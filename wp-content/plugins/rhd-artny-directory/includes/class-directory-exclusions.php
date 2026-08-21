<?php
/**
 * Manual directory exclusions (ineligible Xplor IDs).
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and queries Xplor IDs flagged as ineligible for the public directory.
 *
 * Flagged entries are skipped during sync regardless of membership status or
 * the membership expiry grace period.
 */
final class RHD_Artny_Directory_Exclusions {

	/**
	 * Option name for the ineligible ID map.
	 */
	const OPTION_KEY = 'rhd_artny_directory_ineligible';

	/**
	 * Whether a Xplor record ID is flagged as ineligible for a directory type.
	 *
	 * @param string $type organizations|individuals.
	 * @param string $id   Xplor Account or Contact ID.
	 * @return bool
	 */
	public static function is_ineligible( $type, $id ) {
		$id = trim( (string) $id );
		if ( '' === $id || null === RHD_Artny_Directory_Config::get( $type ) ) {
			return false;
		}

		$map = self::get_for_type( $type );

		return isset( $map[ $id ] );
	}

	/**
	 * Ineligible entries for a directory type, keyed by Xplor ID.
	 *
	 * @param string $type organizations|individuals.
	 * @return array<string, array{name: string, updated_at: int}>
	 */
	public static function get_for_type( $type ) {
		$all = self::get_all();

		if ( ! isset( $all[ $type ] ) || ! is_array( $all[ $type ] ) ) {
			return array();
		}

		return $all[ $type ];
	}

	/**
	 * Full option payload for both directory types.
	 *
	 * @return array{organizations: array<string, array{name: string, updated_at: int}>, individuals: array<string, array{name: string, updated_at: int}>}
	 */
	public static function get_all() {
		$stored = get_option( self::OPTION_KEY, array() );

		return self::normalize_option( $stored );
	}

	/**
	 * Replace the ineligible map for one directory type.
	 *
	 * @param string                                                              $type    organizations|individuals.
	 * @param array<string, array{name?: string, updated_at?: int}|string|mixed> $entries ID => meta (or legacy string name).
	 * @return bool Whether the option was updated.
	 */
	public static function set_for_type( $type, $entries ) {
		if ( null === RHD_Artny_Directory_Config::get( $type ) ) {
			return false;
		}

		$all          = self::get_all();
		$all[ $type ] = self::normalize_type_map( $entries );

		return update_option( self::OPTION_KEY, $all, false );
	}

	/**
	 * @param mixed $stored Raw option value.
	 * @return array{organizations: array<string, array{name: string, updated_at: int}>, individuals: array<string, array{name: string, updated_at: int}>}
	 */
	private static function normalize_option( $stored ) {
		$empty = array(
			RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS => array(),
			RHD_Artny_Directory_Config::TYPE_INDIVIDUALS   => array(),
		);

		if ( ! is_array( $stored ) ) {
			return $empty;
		}

		foreach ( array_keys( $empty ) as $type ) {
			if ( isset( $stored[ $type ] ) && is_array( $stored[ $type ] ) ) {
				$empty[ $type ] = self::normalize_type_map( $stored[ $type ] );
			}
		}

		return $empty;
	}

	/**
	 * @param mixed $entries Raw type map.
	 * @return array<string, array{name: string, updated_at: int}>
	 */
	private static function normalize_type_map( $entries ) {
		if ( ! is_array( $entries ) ) {
			return array();
		}

		$normalized = array();
		$now        = time();

		foreach ( $entries as $id => $meta ) {
			$id = trim( (string) $id );
			if ( '' === $id ) {
				continue;
			}

			if ( is_string( $meta ) ) {
				$normalized[ $id ] = array(
					'name'       => sanitize_text_field( $meta ),
					'updated_at' => $now,
				);
				continue;
			}

			if ( ! is_array( $meta ) ) {
				$normalized[ $id ] = array(
					'name'       => '',
					'updated_at' => $now,
				);
				continue;
			}

			$name = isset( $meta['name'] ) ? sanitize_text_field( (string) $meta['name'] ) : '';
			$at   = isset( $meta['updated_at'] ) ? (int) $meta['updated_at'] : $now;
			if ( $at <= 0 ) {
				$at = $now;
			}

			$normalized[ $id ] = array(
				'name'       => $name,
				'updated_at' => $at,
			);
		}

		return $normalized;
	}
}
