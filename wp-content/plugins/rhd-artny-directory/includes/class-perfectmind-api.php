<?php
/**
 * PerfectMind B2C API client for directory Account records.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches Account object records from PerfectMind.
 */
final class RHD_Artny_Directory_Perfectmind_Api {

	/**
	 * ObjectRecords table name for member organizations.
	 */
	const ACCOUNT_TABLE = 'Account';

	/**
	 * Account fields used by the directory (API source names).
	 */
	const ACCOUNT_FIELDS = array(
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
		'AccountType',
	);

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
	 * Fetch all Account records from ObjectRecords.
	 *
	 * @return array{records: array<int, array<string, mixed>>, error: string}
	 */
	public static function fetch_accounts() {
		if ( ! self::is_configured() ) {
			return array(
				'records' => array(),
				'error'   => __( 'PerfectMind API credentials are not configured.', 'rhd-artny-directory' ),
			);
		}

		$url = trailingslashit( self::base_url() ) . 'api/2.0/B2C/ObjectRecords';

		$response = wp_remote_get(
			add_query_arg( 'tableName', self::ACCOUNT_TABLE, $url ),
			array(
				'timeout' => 90,
				'headers' => array(
					'Accept'           => 'application/json',
					'X-Access-Key'     => self::access_key(),
					'X-Client-Number'  => self::client_number(),
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

		// Some endpoints wrap results; Account returns a bare array.
		if ( isset( $data['Result'] ) && is_array( $data['Result'] ) ) {
			$data = $data['Result'];
		}

		$records = array();

		foreach ( $data as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			if ( isset( $row['AccountType'] ) && 'Organization' !== (string) $row['AccountType'] ) {
				continue;
			}

			$name = isset( $row['Name'] ) ? trim( (string) $row['Name'] ) : '';
			if ( '' === $name ) {
				continue;
			}

			$records[] = self::pick_account_fields( $row );
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
	 * Reduce a raw Account row to directory-relevant fields only.
	 *
	 * @param array<string, mixed> $row Raw API record.
	 * @return array<string, mixed>
	 */
	private static function pick_account_fields( $row ) {
		$record = array();

		foreach ( self::ACCOUNT_FIELDS as $field ) {
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
