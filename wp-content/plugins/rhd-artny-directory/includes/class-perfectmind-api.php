<?php
/**
 * Xplor B2C API client for directory records.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches ObjectRecords from Xplor for configured directory types.
 */
final class RHD_Artny_Directory_Perfectmind_Api {

	/**
	 * Whether API credentials are configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== self::base_url()
			&& '' !== self::access_key()
			&& '' !== self::client_number();
	}

	/**
	 * Fetch Account records for the organizations directory.
	 *
	 * @return array{records: array<int, array<string, mixed>>, error: string}
	 */
	public static function fetch_accounts() {
		return self::fetch_records( RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS );
	}

	/**
	 * Fetch Contact records for the individuals directory.
	 *
	 * @return array{records: array<int, array<string, mixed>>, error: string}
	 */
	public static function fetch_contacts() {
		return self::fetch_records( RHD_Artny_Directory_Config::TYPE_INDIVIDUALS );
	}

	/**
	 * Contact ID => primary-contact fields used by the organizations directory.
	 *
	 * Includes MembershipExpiry (membership gate) and OrganizationBio
	 * (Account Description fallback when the org row has no bio).
	 *
	 * @return array{
	 *     map: array<string, array{MembershipExpiry: string|null, OrganizationBio: string}>,
	 *     error: string
	 * }
	 */
	public static function fetch_primary_contact_map() {
		$result = self::fetch_table_rows( 'Contact' );

		if ( '' !== $result['error'] ) {
			return array(
				'map'   => array(),
				'error' => $result['error'],
			);
		}

		$map = array();

		foreach ( $result['rows'] as $row ) {
			if ( ! is_array( $row ) || empty( $row['ID'] ) ) {
				continue;
			}

			$id = (string) $row['ID'];
			$map[ $id ] = array(
				'MembershipExpiry' => isset( $row['MembershipExpiry'] ) && null !== $row['MembershipExpiry']
					? (string) $row['MembershipExpiry']
					: null,
				'OrganizationBio'  => isset( $row['OrganizationBio'] ) && null !== $row['OrganizationBio']
					? (string) $row['OrganizationBio']
					: '',
			);
		}

		return array(
			'map'   => $map,
			'error' => '',
		);
	}

	/**
	 * @deprecated Use fetch_primary_contact_map().
	 *
	 * @return array{map: array<string, string|null>, error: string}
	 */
	public static function fetch_contact_membership_expiry_map() {
		$result = self::fetch_primary_contact_map();

		if ( '' !== $result['error'] ) {
			return array(
				'map'   => array(),
				'error' => $result['error'],
			);
		}

		$map = array();

		foreach ( $result['map'] as $id => $contact ) {
			$map[ $id ] = $contact['MembershipExpiry'];
		}

		return array(
			'map'   => $map,
			'error' => '',
		);
	}

	/**
	 * Fetch records for a directory type.
	 *
	 * @param string $type organizations|individuals.
	 * @return array{records: array<int, array<string, mixed>>, error: string}
	 */
	public static function fetch_records( $type ) {
		$config = RHD_Artny_Directory_Config::get( $type );

		if ( null === $config ) {
			return array(
				'records' => array(),
				'error'   => __( 'Unknown directory type.', 'rhd-artny-directory' ),
			);
		}

		if ( ! self::is_configured() ) {
			return array(
				'records' => array(),
				'error'   => __( 'Xplor API credentials are not configured.', 'rhd-artny-directory' ),
			);
		}

		$table_result = self::fetch_table_rows( $config['table'] );

		if ( '' !== $table_result['error'] ) {
			return array(
				'records' => array(),
				'error'   => $table_result['error'],
			);
		}

		$records = array();

		foreach ( $table_result['rows'] as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$record = self::normalize_row( $type, $row );
			if ( null !== $record ) {
				$records[] = $record;
			}
		}

		usort(
			$records,
			static function ( $a, $b ) {
				return strcasecmp( (string) $a['Name'], (string) $b['Name'] );
			}
		);

		return array(
			'records' => $records,
			'error'   => '',
		);
	}

	/**
	 * Fetch raw ObjectRecords rows for a table.
	 *
	 * @param string $table PerfectMind table name.
	 * @return array{rows: array<int, array<string, mixed>>, error: string}
	 */
	private static function fetch_table_rows( $table ) {
		if ( ! self::is_configured() ) {
			return array(
				'rows'  => array(),
				'error' => __( 'Xplor API credentials are not configured.', 'rhd-artny-directory' ),
			);
		}

		$url = trailingslashit( self::base_url() ) . 'api/2.0/B2C/ObjectRecords';

		$response = wp_remote_get(
			add_query_arg( 'tableName', $table, $url ),
			array(
				'timeout' => 90,
				'headers' => array(
					'Accept'          => 'application/json',
					'X-Access-Key'    => self::access_key(),
					'X-Client-Number' => self::client_number(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'rows'  => array(),
				'error' => $response->get_error_message(),
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );

		if ( $status < 200 || $status >= 300 ) {
			return array(
				'rows'  => array(),
				'error' => sprintf(
					/* translators: 1: HTTP status code, 2: response body excerpt */
					__( 'Xplor API returned HTTP %1$d: %2$s', 'rhd-artny-directory' ),
					$status,
					wp_html_excerpt( $body, 200, '…' )
				),
			);
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return array(
				'rows'  => array(),
				'error' => __( 'Xplor API returned invalid JSON.', 'rhd-artny-directory' ),
			);
		}

		if ( isset( $data['Result'] ) && is_array( $data['Result'] ) ) {
			$data = $data['Result'];
		}

		return array(
			'rows'  => $data,
			'error' => '',
		);
	}

	/**
	 * Normalize and filter a raw API row for a directory type.
	 *
	 * @param string               $type Directory type.
	 * @param array<string, mixed> $row  Raw API record.
	 * @return array<string, mixed>|null
	 */
	private static function normalize_row( $type, $row ) {
		if ( RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS === $type ) {
			return self::normalize_account_row( $row );
		}

		if ( RHD_Artny_Directory_Config::TYPE_INDIVIDUALS === $type ) {
			return self::normalize_contact_row( $row );
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $row Raw Account row.
	 * @return array<string, mixed>|null
	 */
	private static function normalize_account_row( $row ) {
		if ( isset( $row['AccountType'] ) && 'Organization' !== (string) $row['AccountType'] ) {
			return null;
		}

		$name = isset( $row['Name'] ) ? trim( (string) $row['Name'] ) : '';
		if ( '' === $name ) {
			return null;
		}

		return self::pick_fields(
			$row,
			array(
				'Name',
				'Description',
				'Website',
				'WebsiteforProfile',
				'Instagram',
				'LinkedInProfileURL',
				'OrganizationFacebookPage',
				'OrganizationalFocus',
				'ArtisticFocus',
				'PublicProgrammingLocations',
				'PrimaryContact',
			)
		);
	}

	/**
	 * @param array<string, mixed> $row Raw Contact row.
	 * @return array<string, mixed>|null
	 */
	private static function normalize_contact_row( $row ) {
		$name = self::build_contact_name( $row );
		if ( '' === $name ) {
			return null;
		}

		$record = self::pick_fields(
			$row,
			array(
				'FirstName',
				'MiddleName',
				'LastName',
				'Description',
				'ArtistBio',
				'Email',
				'PrimaryPractice',
				'ArtisticPractices',
				'Website',
				'IndividualMemberWebsite',
				'IndividualMemberInstagram',
				'IndividualMemberFacebook',
				'IndividualMemberLinkedInProfileURL',
				'MembershipExpiry',
			)
		);

		$record['Name'] = $name;

		return $record;
	}

	/**
	 * @param array<string, mixed> $row Contact row.
	 * @return string
	 */
	private static function build_contact_name( $row ) {
		$parts = array();

		foreach ( array( 'FirstName', 'MiddleName', 'LastName' ) as $field ) {
			if ( empty( $row[ $field ] ) ) {
				continue;
			}

			$part = trim( (string) $row[ $field ] );
			if ( '' !== $part ) {
				$parts[] = $part;
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * @param array<string, mixed> $row    Raw API record.
	 * @param array<int, string>   $fields Field names to retain.
	 * @return array<string, mixed>
	 */
	private static function pick_fields( $row, $fields ) {
		$record = array();

		foreach ( $fields as $field ) {
			if ( array_key_exists( $field, $row ) ) {
				$record[ $field ] = $row[ $field ];
			}
		}

		return $record;
	}

	/**
	 * @return string
	 */
	private static function base_url() {
		if ( defined( 'PERFECTMIND_BASE_URL' ) ) {
			return untrailingslashit( (string) PERFECTMIND_BASE_URL );
		}

		return '';
	}

	/**
	 * Supports both PERFECTMIND_ACCESS_KEY (configured) and PERFECTMIND_API_KEY (docs).
	 *
	 * @return string
	 */
	private static function access_key() {
		if ( defined( 'PERFECTMIND_ACCESS_KEY' ) && '' !== (string) PERFECTMIND_ACCESS_KEY ) {
			return (string) PERFECTMIND_ACCESS_KEY;
		}

		if ( defined( 'PERFECTMIND_API_KEY' ) && '' !== (string) PERFECTMIND_API_KEY ) {
			return (string) PERFECTMIND_API_KEY;
		}

		return '';
	}

	/**
	 * @return string
	 */
	private static function client_number() {
		if ( defined( 'PERFECTMIND_CLIENT_NUMBER' ) ) {
			return (string) PERFECTMIND_CLIENT_NUMBER;
		}

		return '';
	}
}
