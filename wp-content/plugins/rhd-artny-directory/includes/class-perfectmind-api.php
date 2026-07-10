<?php
/**
 * PerfectMind B2C API client for directory records.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches ObjectRecords from PerfectMind for configured directory types.
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
				'error'   => __( 'PerfectMind API credentials are not configured.', 'rhd-artny-directory' ),
			);
		}

		$url = trailingslashit( self::base_url() ) . 'api/2.0/B2C/ObjectRecords';

		$response = wp_remote_get(
			add_query_arg( 'tableName', $config['table'], $url ),
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
				'records' => array(),
				'error'   => $response->get_error_message(),
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = wp_remote_retrieve_body( $response );

		if ( $status < 200 || $status >= 300 ) {
			return array(
				'records' => array(),
				'error'   => sprintf(
					/* translators: 1: HTTP status code, 2: response body excerpt */
					__( 'PerfectMind API returned HTTP %1$d: %2$s', 'rhd-artny-directory' ),
					$status,
					wp_html_excerpt( $body, 200, '…' )
				),
			);
		}

		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return array(
				'records' => array(),
				'error'   => __( 'PerfectMind API returned invalid JSON.', 'rhd-artny-directory' ),
			);
		}

		if ( isset( $data['Result'] ) && is_array( $data['Result'] ) ) {
			$data = $data['Result'];
		}

		$records = array();

		foreach ( $data as $row ) {
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
				'PrimaryPractice',
				'ArtisticPractices',
				'Website',
				'IndividualMemberWebsite',
				'IndividualMemberInstagram',
				'IndividualMemberFacebook',
				'IndividualMemberLinkedInProfileURL',
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
