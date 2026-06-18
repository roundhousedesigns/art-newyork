<?php
/**
 * Cached PerfectMind Account data for the directory block.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transforms API records into directory contacts and taxonomy labels.
 */
final class RHD_Artny_Directory_Data {

	/**
	 * Transient key for cached directory payload.
	 */
	const CACHE_KEY = 'rhd_artny_directory_accounts_v4';

	/**
	 * Account fields retained for display and filtering.
	 */
	const CONTACT_FIELDS = array(
		'Name',
		'Description',
		'Website',
		'Instagram',
		'OrganizationLinkedInProfile',
		'OrganizationFacebookPage',
		'OrganizationalFocus',
		'ArtisticFocus',
		'PublicProgrammingLocations',
	);

	/**
	 * Multi-select fields stored as slug arrays in the contact shape.
	 */
	const TAXONOMY_FIELDS = array(
		'ArtisticFocus',
		'OrganizationalFocus',
		'PublicProgrammingLocations',
	);

	/**
	 * Cache lifetime in seconds (12 hours).
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Cron hook for background refresh.
	 */
	const CRON_HOOK = 'rhd_artny_directory_sync_accounts';

	/**
	 * PerfectMind multi-select delimiter in Account fields.
	 */
	const PM_MULTI_VALUE_DELIMITER = '&#g4;&#4g;';

	/**
	 * Register cron and sync hooks.
	 */
	public static function register_hooks() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'refresh_cache' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'twicedaily', self::CRON_HOOK );
		}
	}

	/**
	 * Directory contacts for rendering and client-side filtering.
	 *
	 * @param bool $force_refresh Bypass cache when true.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_contacts( $force_refresh = false ) {
		$payload = self::get_payload( $force_refresh );

		return isset( $payload['contacts'] ) && is_array( $payload['contacts'] )
			? $payload['contacts']
			: array();
	}

	/**
	 * Taxonomy labels keyed by slug.
	 *
	 * @param bool $force_refresh Bypass cache when true.
	 * @return array<string, array<string, string>>
	 */
	public static function get_taxonomy_labels( $force_refresh = false ) {
		$payload = self::get_payload( $force_refresh );

		return isset( $payload['labels'] ) && is_array( $payload['labels'] )
			? $payload['labels']
			: self::empty_taxonomy_labels();
	}

	/**
	 * Unique taxonomy slugs used across contacts.
	 *
	 * @param string $taxonomy ArtisticFocus|OrganizationalFocus|PublicProgrammingLocations.
	 * @param bool   $force_refresh Bypass cache when true.
	 * @return array<int, string>
	 */
	public static function get_used_taxonomy_slugs( $taxonomy, $force_refresh = false ) {
		$contacts = self::get_contacts( $force_refresh );
		$slugs    = array();

		foreach ( $contacts as $contact ) {
			if ( empty( $contact[ $taxonomy ] ) || ! is_array( $contact[ $taxonomy ] ) ) {
				continue;
			}

			foreach ( $contact[ $taxonomy ] as $slug ) {
				$slugs[ $slug ] = true;
			}
		}

		$slug_list = array_keys( $slugs );
		sort( $slug_list, SORT_STRING );

		return $slug_list;
	}

	/**
	 * Last sync metadata for debugging/admin surfaces.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_sync_status() {
		$payload = get_transient( self::CACHE_KEY );

		if ( ! is_array( $payload ) ) {
			return array(
				'synced_at' => 0,
				'count'     => 0,
				'error'     => '',
				'source'    => 'none',
			);
		}

		return array(
			'synced_at' => isset( $payload['synced_at'] ) ? (int) $payload['synced_at'] : 0,
			'count'     => isset( $payload['contacts'] ) && is_array( $payload['contacts'] ) ? count( $payload['contacts'] ) : 0,
			'error'     => isset( $payload['error'] ) ? (string) $payload['error'] : '',
			'source'    => isset( $payload['source'] ) ? (string) $payload['source'] : 'cache',
		);
	}

	/**
	 * Pull fresh Account data from PerfectMind and store in cache.
	 *
	 * @return array<string, mixed>
	 */
	public static function refresh_cache() {
		$lock_key = self::CACHE_KEY . '_lock';

		if ( get_transient( $lock_key ) ) {
			$cached = get_transient( self::CACHE_KEY );
			return is_array( $cached ) ? $cached : self::build_fallback_payload( __( 'Sync already in progress.', 'rhd-artny-directory' ) );
		}

		set_transient( $lock_key, 1, 2 * MINUTE_IN_SECONDS );

		$result  = RHD_Artny_Directory_Perfectmind_Api::fetch_accounts();
		$records = $result['records'];
		$error   = $result['error'];

		if ( empty( $records ) && '' !== $error ) {
			$cached = get_transient( self::CACHE_KEY );
			delete_transient( $lock_key );

			if ( is_array( $cached ) && ! empty( $cached['contacts'] ) ) {
				$cached['error']  = $error;
				$cached['source'] = 'stale-cache';
				set_transient( self::CACHE_KEY, $cached, self::CACHE_TTL );
				return $cached;
			}

			$payload = self::build_fallback_payload( $error );
			set_transient( self::CACHE_KEY, $payload, 5 * MINUTE_IN_SECONDS );
			return $payload;
		}

		$payload = self::build_payload_from_records( $records, $error );
		set_transient( self::CACHE_KEY, $payload, self::CACHE_TTL );
		delete_transient( $lock_key );

		return $payload;
	}

	/**
	 * @param bool $force_refresh Bypass cache when true.
	 * @return array<string, mixed>
	 */
	private static function get_payload( $force_refresh = false ) {
		if ( $force_refresh ) {
			return self::refresh_cache();
		}

		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && ! empty( $cached['contacts'] ) ) {
			return $cached;
		}

		return self::refresh_cache();
	}

	/**
	 * @param array<int, array<string, mixed>> $records Account rows.
	 * @param string                           $error   Optional API warning.
	 * @return array<string, mixed>
	 */
	private static function build_payload_from_records( $records, $error = '' ) {
		$labels   = self::empty_taxonomy_labels();
		$contacts = array();

		foreach ( $records as $record ) {
			$contact = self::transform_record( $record, $labels );
			if ( null !== $contact ) {
				$contacts[] = $contact;
			}
		}

		foreach ( $labels as $taxonomy => $map ) {
			uasort(
				$labels[ $taxonomy ],
				static function ( $a, $b ) {
					return strcasecmp( $a, $b );
				}
			);
		}

		return array(
			'synced_at' => time(),
			'contacts'  => $contacts,
			'labels'    => $labels,
			'error'     => $error,
			'source'    => 'api',
		);
	}

	/**
	 * @param string $error Error message.
	 * @return array<string, mixed>
	 */
	private static function build_fallback_payload( $error ) {
		if ( RHD_Artny_Directory_Perfectmind_Api::is_configured() ) {
			return array(
				'synced_at' => time(),
				'contacts'  => array(),
				'labels'    => self::empty_taxonomy_labels(),
				'error'     => $error,
				'source'    => 'error',
			);
		}

		return array(
			'synced_at' => time(),
			'contacts'  => RHD_Artny_Directory_Demo_Data::contacts(),
			'labels'    => RHD_Artny_Directory_Demo_Data::taxonomy_labels(),
			'error'     => $error,
			'source'    => 'demo',
		);
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	private static function empty_taxonomy_labels() {
		return array(
			'ArtisticFocus'              => array(),
			'OrganizationalFocus'        => array(),
			'PublicProgrammingLocations' => array(),
		);
	}

	/**
	 * @param array<string, mixed>               $record Raw Account row.
	 * @param array<string, array<string, string>> $labels Taxonomy labels (by reference).
	 * @return array<string, mixed>|null
	 */
	private static function transform_record( $record, &$labels ) {
		$name = isset( $record['Name'] ) ? sanitize_text_field( (string) $record['Name'] ) : '';
		if ( '' === $name ) {
			return null;
		}

		$website = '';
		if ( ! empty( $record['Website'] ) ) {
			$website = self::normalize_url( (string) $record['Website'] );
		} elseif ( ! empty( $record['WebsiteforProfile'] ) ) {
			$website = self::normalize_url( (string) $record['WebsiteforProfile'] );
		}

		$contact = array(
			'Name'                         => $name,
			'Description'                  => isset( $record['Description'] ) ? sanitize_textarea_field( (string) $record['Description'] ) : '',
			'Website'                      => $website,
			'Instagram'                    => self::normalize_instagram( $record['Instagram'] ?? '' ),
			'OrganizationLinkedInProfile'  => self::normalize_url( (string) ( $record['OrganizationLinkedInProfile'] ?? $record['LinkedInProfileURL'] ?? '' ) ),
			'OrganizationFacebookPage'     => self::normalize_url( (string) ( $record['OrganizationFacebookPage'] ?? '' ) ),
			'ArtisticFocus'                => self::register_taxonomy_values( $labels, 'ArtisticFocus', self::parse_multi_value( $record['ArtisticFocus'] ?? '' ) ),
			'OrganizationalFocus'          => self::register_taxonomy_values( $labels, 'OrganizationalFocus', self::parse_multi_value( $record['OrganizationalFocus'] ?? '' ) ),
			'PublicProgrammingLocations'   => self::register_taxonomy_values( $labels, 'PublicProgrammingLocations', self::parse_multi_value( $record['PublicProgrammingLocations'] ?? '' ) ),
		);

		return self::contact_is_displayable( $contact ) ? $contact : null;
	}

	/**
	 * Whether a contact has the minimum fields required for directory display.
	 *
	 * @param array<string, mixed> $contact Normalized contact record.
	 * @return bool
	 */
	private static function contact_is_displayable( $contact ) {
		$name = isset( $contact['Name'] ) ? trim( (string) $contact['Name'] ) : '';
		if ( '' === $name ) {
			return false;
		}

		$description = isset( $contact['Description'] ) ? trim( (string) $contact['Description'] ) : '';
		
		// TODO re-enable Description check when more descriptions are available.
		// if ( '' === $description ) {
		// 	return false;
		// }

		$has_web_presence = ! empty( $contact['Website'] )
			|| ! empty( $contact['Instagram'] )
			|| ! empty( $contact['OrganizationFacebookPage'] )
			|| ! empty( $contact['OrganizationLinkedInProfile'] );

		return $has_web_presence;
	}

	/**
	 * @param mixed $value PerfectMind multi-select field.
	 * @return array<int, string>
	 */
	private static function parse_multi_value( $value ) {
		if ( null === $value || '' === $value ) {
			return array();
		}

		$parts = preg_split(
			'/' . preg_quote( self::PM_MULTI_VALUE_DELIMITER, '/' ) . '/',
			(string) $value,
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		if ( ! is_array( $parts ) ) {
			return array();
		}

		$values = array();

		foreach ( $parts as $part ) {
			$label = trim( sanitize_text_field( $part ) );
			if ( '' !== $label && '--None--' !== $label ) {
				$values[] = $label;
			}
		}

		return array_values( array_unique( $values ) );
	}

	/**
	 * @param array<string, array<string, string>> $labels   Taxonomy labels (by reference).
	 * @param string                               $taxonomy ArtisticFocus|OrganizationalFocus|PublicProgrammingLocations.
	 * @param array<int, string>                   $values   Human-readable labels.
	 * @return array<int, string>
	 */
	private static function register_taxonomy_values( &$labels, $taxonomy, $values ) {
		$slugs = array();

		foreach ( $values as $label ) {
			$slug = sanitize_title( $label );
			if ( '' === $slug ) {
				continue;
			}

			$labels[ $taxonomy ][ $slug ] = $label;
			$slugs[]                      = $slug;
		}

		return $slugs;
	}

	/**
	 * @param mixed $value Instagram handle or URL from PerfectMind.
	 * @return string
	 */
	private static function normalize_instagram( $value ) {
		$instagram = trim( (string) $value );
		if ( '' === $instagram ) {
			return '';
		}

		if ( 0 !== strpos( $instagram, 'http' ) ) {
			$instagram = 'https://instagram.com/' . ltrim( $instagram, '@' );
		}

		return (string) esc_url_raw( $instagram, array( 'https', 'http' ) );
	}

	/**
	 * @param string $url Raw URL or host/path.
	 * @return string
	 */
	private static function normalize_url( $url ) {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		if ( 0 !== strpos( $url, 'http' ) ) {
			$url = 'https://' . ltrim( $url, '/' );
		}

		return (string) esc_url_raw( $url, array( 'https', 'http' ) );
	}
}
