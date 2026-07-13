<?php
/**
 * Cached PerfectMind data for directory blocks.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transforms API records into directory entries and taxonomy labels.
 */
final class RHD_Artny_Directory_Data {

	/**
	 * PerfectMind multi-select delimiter.
	 */
	const PM_MULTI_VALUE_DELIMITER = '&#g4;&#4g;';

	/**
	 * Cache lifetime in seconds (12 hours).
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Register cron and sync hooks for all directory types.
	 */
	public static function register_hooks() {
		foreach ( RHD_Artny_Directory_Config::all() as $type => $config ) {
			add_action( $config['cron_hook'], static function () use ( $type ) {
				self::refresh_cache( $type );
			} );

			if ( ! wp_next_scheduled( $config['cron_hook'] ) ) {
				wp_schedule_event( time() + MINUTE_IN_SECONDS, 'twicedaily', $config['cron_hook'] );
			}
		}
	}

	/**
	 * Directory entries for rendering and client-side filtering.
	 *
	 * @param string $type          organizations|individuals.
	 * @param bool   $force_refresh Bypass cache when true.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_contacts( $type = RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS, $force_refresh = false ) {
		$payload = self::get_payload( $type, $force_refresh );

		return isset( $payload['contacts'] ) && is_array( $payload['contacts'] )
			? $payload['contacts']
			: array();
	}

	/**
	 * Taxonomy labels keyed by slug.
	 *
	 * @param string $type          organizations|individuals.
	 * @param bool   $force_refresh Bypass cache when true.
	 * @return array<string, array<string, string>>
	 */
	public static function get_taxonomy_labels( $type = RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS, $force_refresh = false ) {
		$payload = self::get_payload( $type, $force_refresh );

		return isset( $payload['labels'] ) && is_array( $payload['labels'] )
			? $payload['labels']
			: self::empty_taxonomy_labels( $type );
	}

	/**
	 * Unique taxonomy slugs used across directory entries.
	 *
	 * @param string $taxonomy Taxonomy field key.
	 * @param string $type     organizations|individuals.
	 * @param bool   $force_refresh Bypass cache when true.
	 * @return array<int, string>
	 */
	public static function get_used_taxonomy_slugs( $taxonomy, $type = RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS, $force_refresh = false ) {
		$config   = self::require_config( $type );
		$contacts = self::get_contacts( $type, $force_refresh );
		$slugs    = array();

		foreach ( $contacts as $contact ) {
			$values = self::get_taxonomy_values( $contact, $taxonomy, $config );

			foreach ( $values as $slug ) {
				$slugs[ $slug ] = true;
			}
		}

		$slug_list = array_keys( $slugs );
		sort( $slug_list, SORT_STRING );

		if ( empty( $slug_list ) ) {
			$labels = self::get_taxonomy_labels( $type, $force_refresh );
			if ( ! empty( $labels[ $taxonomy ] ) && is_array( $labels[ $taxonomy ] ) ) {
				$slug_list = array_keys( $labels[ $taxonomy ] );
				sort( $slug_list, SORT_STRING );
			}
		}

		return $slug_list;
	}

	/**
	 * Taxonomy field keys for a directory type.
	 *
	 * @param string $type organizations|individuals.
	 * @return array<int, string>
	 */
	public static function get_taxonomy_fields( $type = RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS ) {
		$config = self::require_config( $type );

		return array_keys( $config['taxonomy_fields'] );
	}

	/**
	 * Last sync metadata for debugging/admin surfaces.
	 *
	 * @param string $type organizations|individuals.
	 * @return array<string, mixed>
	 */
	public static function get_sync_status( $type = RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS ) {
		$config    = self::require_config( $type );
		$cache_key = $config['cache_key'];
		$payload   = get_transient( $cache_key );

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
	 * Pull fresh data from PerfectMind and store in cache.
	 *
	 * @param string $type organizations|individuals.
	 * @return array<string, mixed>
	 */
	public static function refresh_cache( $type = RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS ) {
		$config   = self::require_config( $type );
		$lock_key = $config['cache_key'] . '_lock';

		if ( get_transient( $lock_key ) ) {
			$cached = get_transient( $config['cache_key'] );
			return is_array( $cached ) ? $cached : self::build_fallback_payload( $type, __( 'Sync already in progress.', 'rhd-artny-directory' ) );
		}

		set_transient( $lock_key, 1, 2 * MINUTE_IN_SECONDS );

		$result  = RHD_Artny_Directory_Perfectmind_Api::fetch_records( $type );
		$records = $result['records'];
		$error   = $result['error'];

		if ( empty( $records ) && '' !== $error ) {
			$cached = get_transient( $config['cache_key'] );
			delete_transient( $lock_key );

			if ( is_array( $cached ) && ! empty( $cached['contacts'] ) ) {
				$cached['error']  = $error;
				$cached['source'] = 'stale-cache';
				set_transient( $config['cache_key'], $cached, self::CACHE_TTL );
				return $cached;
			}

			$payload = self::build_fallback_payload( $type, $error );
			set_transient( $config['cache_key'], $payload, 5 * MINUTE_IN_SECONDS );
			return $payload;
		}

		$payload = self::build_payload_from_records( $type, $records, $error, self::get_membership_expiry_map_for_type( $type ) );
		set_transient( $config['cache_key'], $payload, self::CACHE_TTL );
		delete_transient( $lock_key );

		return $payload;
	}

	/**
	 * Delete cached directory payloads and sync locks for all directory types.
	 */
	public static function clear_cache() {
		foreach ( RHD_Artny_Directory_Config::all() as $config ) {
			delete_transient( $config['cache_key'] );
			delete_transient( $config['cache_key'] . '_lock' );
		}
	}

	/**
	 * @param string $type          organizations|individuals.
	 * @param bool   $force_refresh Bypass cache when true.
	 * @return array<string, mixed>
	 */
	private static function get_payload( $type, $force_refresh = false ) {
		if ( $force_refresh ) {
			return self::refresh_cache( $type );
		}

		$config = self::require_config( $type );
		$cached = get_transient( $config['cache_key'] );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		return self::refresh_cache( $type );
	}

	/**
	 * @param string                             $type       Directory type.
	 * @param array<int, array<string, mixed>>   $records    API rows.
	 * @param string                             $error      Optional API warning.
	 * @param array<string, string|null>|null $expiry_map Contact ID => MembershipExpiry, or null to skip filtering.
	 * @return array<string, mixed>
	 */
	private static function build_payload_from_records( $type, $records, $error = '', $expiry_map = null ) {
		$labels   = self::empty_taxonomy_labels( $type );
		$contacts = array();

		foreach ( $records as $record ) {
			$contact = self::transform_record( $type, $record, $labels, $expiry_map );
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
	 * @param string $type  Directory type.
	 * @param string $error Error message.
	 * @return array<string, mixed>
	 */
	private static function build_fallback_payload( $type, $error ) {
		if ( RHD_Artny_Directory_Perfectmind_Api::is_configured() ) {
			return array(
				'synced_at' => time(),
				'contacts'  => array(),
				'labels'    => self::empty_taxonomy_labels( $type ),
				'error'     => $error,
				'source'    => 'error',
			);
		}

		return array(
			'synced_at' => time(),
			'contacts'  => RHD_Artny_Directory_Demo_Data::contacts( $type ),
			'labels'    => RHD_Artny_Directory_Demo_Data::taxonomy_labels( $type ),
			'error'     => $error,
			'source'    => 'demo',
		);
	}

	/**
	 * @param string $type organizations|individuals.
	 * @return array<string, array<string, string>>
	 */
	private static function empty_taxonomy_labels( $type ) {
		$config = self::require_config( $type );
		$labels = array();

		foreach ( array_keys( $config['taxonomy_fields'] ) as $taxonomy ) {
			$labels[ $taxonomy ] = array();
		}

		return $labels;
	}

	/**
	 * @param string                               $type       Directory type.
	 * @param array<string, mixed>                 $record     Raw API row.
	 * @param array<string, array<string, string>> $labels     Taxonomy labels (by reference).
	 * @param array<string, string|null>|null     $expiry_map Contact ID => MembershipExpiry, or null to skip filtering.
	 * @return array<string, mixed>|null
	 */
	private static function transform_record( $type, $record, &$labels, $expiry_map = null ) {
		if ( RHD_Artny_Directory_Config::TYPE_INDIVIDUALS === $type ) {
			return self::transform_individual_record( $record, $labels, self::require_config( $type ) );
		}

		return self::transform_organization_record( $record, $labels, self::require_config( $type ), $expiry_map );
	}

	/**
	 * @param array<string, mixed>                 $record     Raw Account row.
	 * @param array<string, array<string, string>> $labels     Taxonomy labels (by reference).
	 * @param array<string, mixed>                 $config     Directory config.
	 * @param array<string, string|null>|null       $expiry_map Contact ID => MembershipExpiry, or null to skip filtering.
	 * @return array<string, mixed>|null
	 */
	private static function transform_organization_record( $record, &$labels, $config, $expiry_map = null ) {
		if ( ! self::organization_membership_is_active( $record, $expiry_map ) ) {
			return null;
		}

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
			'Name'                        => $name,
			'Description'                 => isset( $record['Description'] ) ? sanitize_textarea_field( (string) $record['Description'] ) : '',
			'Website'                     => $website,
			'Instagram'                   => self::normalize_instagram( $record['Instagram'] ?? '' ),
			'OrganizationLinkedInProfile' => self::normalize_url( (string) ( $record['OrganizationLinkedInProfile'] ?? $record['LinkedInProfileURL'] ?? '' ) ),
			'OrganizationFacebookPage'    => self::normalize_url( (string) ( $record['OrganizationFacebookPage'] ?? '' ) ),
		);

		foreach ( $config['taxonomy_fields'] as $taxonomy => $taxonomy_config ) {
			$contact[ $taxonomy ] = self::register_taxonomy_values(
				$labels,
				$taxonomy,
				self::parse_multi_value( $record[ $taxonomy ] ?? '' ),
				! empty( $taxonomy_config['multi'] )
			);
		}

		return self::contact_is_displayable( $contact, $config ) ? $contact : null;
	}

	/**
	 * @param array<string, mixed>                 $record Raw Contact row.
	 * @param array<string, array<string, string>> $labels Taxonomy labels (by reference).
	 * @param array<string, mixed>                 $config Directory config.
	 * @return array<string, mixed>|null
	 */
	private static function transform_individual_record( $record, &$labels, $config ) {
		if ( self::is_membership_expired( $record['MembershipExpiry'] ?? null ) ) {
			return null;
		}

		$name = isset( $record['Name'] ) ? sanitize_text_field( (string) $record['Name'] ) : '';
		if ( '' === $name ) {
			return null;
		}

		$website = '';
		if ( ! empty( $record['Website'] ) ) {
			$website = self::normalize_url( (string) $record['Website'] );
		} elseif ( ! empty( $record['IndividualMemberWebsite'] ) ) {
			$website = self::normalize_url( (string) $record['IndividualMemberWebsite'] );
		}

		$contact = array(
			'Name'        => $name,
			'Description' => isset( $record['Description'] ) ? sanitize_textarea_field( (string) $record['Description'] ) : '',
			'Website'     => $website,
			'Instagram'   => self::normalize_instagram( $record['IndividualMemberInstagram'] ?? '' ),
			'Facebook'    => self::normalize_url( (string) ( $record['IndividualMemberFacebook'] ?? '' ) ),
			'LinkedIn'    => self::normalize_url( (string) ( $record['IndividualMemberLinkedInProfileURL'] ?? '' ) ),
		);

		foreach ( $config['taxonomy_fields'] as $taxonomy => $taxonomy_config ) {
			$contact[ $taxonomy ] = self::register_taxonomy_values(
				$labels,
				$taxonomy,
				self::parse_multi_value( $record[ $taxonomy ] ?? '' ),
				! empty( $taxonomy_config['multi'] )
			);
		}

		return self::contact_is_displayable( $contact, $config ) ? $contact : null;
	}

	/**
	 * Whether an entry has the minimum fields required for directory display.
	 *
	 * @param array<string, mixed> $contact Normalized contact record.
	 * @param array<string, mixed> $config  Directory config.
	 * @return bool
	 */
	private static function contact_is_displayable( $contact, $config ) {
		$name = isset( $contact['Name'] ) ? trim( (string) $contact['Name'] ) : '';
		if ( '' === $name ) {
			return false;
		}

		if ( ! empty( $config['require_description'] ) ) {
			$description = isset( $contact['Description'] ) ? trim( (string) $contact['Description'] ) : '';
			if ( '' === $description ) {
				return false;
			}
		}

		$has_web_presence = ! empty( $contact['Website'] )
			|| ! empty( $contact['Instagram'] )
			|| ! empty( $contact['Facebook'] )
			|| ! empty( $contact['LinkedIn'] )
			|| ! empty( $contact['OrganizationFacebookPage'] )
			|| ! empty( $contact['OrganizationLinkedInProfile'] );

		return $has_web_presence;
	}

	/**
	 * Contact membership expiry map for organization directory sync.
	 *
	 * @param string $type organizations|individuals.
	 * @return array<string, string|null>|null Null when the Contact fetch failed (skip org expiry filtering).
	 */
	private static function get_membership_expiry_map_for_type( $type ) {
		if ( RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS !== $type ) {
			return null;
		}

		if ( ! RHD_Artny_Directory_Perfectmind_Api::is_configured() ) {
			return null;
		}

		$result = RHD_Artny_Directory_Perfectmind_Api::fetch_contact_membership_expiry_map();

		if ( '' !== $result['error'] ) {
			return null;
		}

		return $result['map'];
	}

	/**
	 * Whether an organization's primary contact has active membership.
	 *
	 * @param array<string, mixed>                 $record     Raw Account row.
	 * @param array<string, string|null>|null     $expiry_map Contact ID => MembershipExpiry.
	 * @return bool
	 */
	private static function organization_membership_is_active( $record, $expiry_map ) {
		if ( null === $expiry_map ) {
			return true;
		}

		$primary_contact = isset( $record['PrimaryContact'] ) ? trim( (string) $record['PrimaryContact'] ) : '';

		if ( '' === $primary_contact || ! array_key_exists( $primary_contact, $expiry_map ) ) {
			return false;
		}

		return ! self::is_membership_expired( $expiry_map[ $primary_contact ] );
	}

	/**
	 * Whether a MembershipExpiry value is in the past.
	 *
	 * Null/empty expiry is treated as active (no expiration date on file).
	 *
	 * @param mixed $expiry PerfectMind MembershipExpiry value.
	 * @return bool
	 */
	private static function is_membership_expired( $expiry ) {
		if ( null === $expiry || '' === $expiry ) {
			return false;
		}

		$timestamp = strtotime( (string) $expiry );
		if ( false === $timestamp ) {
			return false;
		}

		$expiry_date = wp_date( 'Y-m-d', $timestamp );
		$today       = wp_date( 'Y-m-d' );

		return $today > $expiry_date;
	}

	/**
	 * @param array<string, mixed> $contact  Normalized contact.
	 * @param string               $taxonomy Taxonomy field key.
	 * @param array<string, mixed> $config   Directory config.
	 * @return array<int, string>
	 */
	private static function get_taxonomy_values( $contact, $taxonomy, $config ) {
		if ( empty( $contact[ $taxonomy ] ) ) {
			return array();
		}

		$taxonomy_config = $config['taxonomy_fields'][ $taxonomy ] ?? array();

		if ( ! empty( $taxonomy_config['multi'] ) ) {
			return is_array( $contact[ $taxonomy ] ) ? $contact[ $taxonomy ] : array();
		}

		$slug = is_string( $contact[ $taxonomy ] ) ? $contact[ $taxonomy ] : '';

		return '' !== $slug ? array( $slug ) : array();
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
	 * @param string                               $taxonomy Taxonomy field key.
	 * @param array<int, string>                   $values   Human-readable labels.
	 * @param bool                                 $multi    Whether multiple values are allowed.
	 * @return array<int, string>|string
	 */
	private static function register_taxonomy_values( &$labels, $taxonomy, $values, $multi = true ) {
		$slugs = array();

		foreach ( $values as $label ) {
			$slug = sanitize_title( $label );
			if ( '' === $slug ) {
				continue;
			}

			$labels[ $taxonomy ][ $slug ] = $label;
			$slugs[]                      = $slug;
		}

		if ( $multi ) {
			return $slugs;
		}

		return isset( $slugs[0] ) ? $slugs[0] : '';
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

	/**
	 * @param string $type organizations|individuals.
	 * @return array<string, mixed>
	 */
	private static function require_config( $type ) {
		$config = RHD_Artny_Directory_Config::get( $type );

		if ( null === $config ) {
			return RHD_Artny_Directory_Config::get( RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS );
		}

		return $config;
	}
}
