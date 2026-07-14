<?php
/**
 * Directory type definitions (organizations vs individuals).
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central configuration for each searchable directory variant.
 */
final class RHD_Artny_Directory_Config {

	/**
	 * Organization directory (Xplor Account table).
	 */
	const TYPE_ORGANIZATIONS = 'organizations';

	/**
	 * Individuals directory (Xplor Contact table).
	 */
	const TYPE_INDIVIDUALS = 'individuals';

	/**
	 * @param string $type organizations|individuals.
	 * @return array<string, mixed>|null
	 */
	public static function get( $type ) {
		$types = self::all();

		return isset( $types[ $type ] ) ? $types[ $type ] : null;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function all() {
		return array(
			self::TYPE_ORGANIZATIONS => self::organizations_config(),
			self::TYPE_INDIVIDUALS   => self::individuals_config(),
		);
	}

	/**
	 * Resolve directory type from block name or attribute.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $block_name Registered block name.
	 * @return string
	 */
	public static function resolve_type( $attributes, $block_name = '' ) {
		if ( isset( $attributes['directoryType'] ) ) {
			$type = sanitize_key( (string) $attributes['directoryType'] );
			if ( null !== self::get( $type ) ) {
				return $type;
			}
		}

		if ( 'rhd/artny-individuals-directory' === $block_name ) {
			return self::TYPE_INDIVIDUALS;
		}

		return self::TYPE_ORGANIZATIONS;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function organizations_config() {
		return array(
			'type'            => self::TYPE_ORGANIZATIONS,
			'block_name'      => 'rhd/artny-directory',
			'title'           => __( 'ART/NY Organizations Directory', 'rhd-artny-directory' ),
			'description'     => __( 'Searchable member directory with filters for Xplor organizations.', 'rhd-artny-directory' ),
			'icon'            => 'groups',
			'table'           => 'Account',
			'cache_key'       => 'rhd_artny_directory_accounts_v8',
			'cron_hook'       => 'rhd_artny_directory_sync_accounts',
			'api_fields'      => array(
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
				'PrimaryContact',
			),
			'taxonomy_fields' => array(
				'ArtisticFocus'              => array(
					'multi'       => true,
					'label'       => __( 'Artistic Focus', 'rhd-artny-directory' ),
					'card_label'  => __( 'Artistic Focus:', 'rhd-artny-directory' ),
					'filter'      => 'artistic_focus',
					'data_attr'   => 'data-artistic-focus',
				),
				'OrganizationalFocus'        => array(
					'multi'       => true,
					'label'       => __( 'Organizational Focus', 'rhd-artny-directory' ),
					'card_label'  => __( 'Org. Focus:', 'rhd-artny-directory' ),
					'filter'      => 'org_focus',
					'data_attr'   => 'data-org-focus',
				),
				'PublicProgrammingLocations' => array(
					'multi'       => true,
					'label'       => __( 'Location', 'rhd-artny-directory' ),
					'card_label'  => __( 'Programming Location:', 'rhd-artny-directory' ),
					'filter'      => 'location',
					'data_attr'   => 'data-location',
				),
			),
			'social_fields'   => array(
				'linkedin'  => 'OrganizationLinkedInProfile',
				'facebook'  => 'OrganizationFacebookPage',
				'instagram' => 'Instagram',
			),
			'filter_heading'  => __( 'Search the directory', 'rhd-artny-directory' ),
			'filter_hint'     => __( 'Filter by name, focus areas, or location', 'rhd-artny-directory' ),
			'search_placeholder' => __( 'Name or keywords…', 'rhd-artny-directory' ),
			'empty_message'   => __( 'No contacts match your filters. Try adjusting your search or clearing filters.', 'rhd-artny-directory' ),
			'require_description' => false,
			'require_web_presence' => true,
			'entry_label_singular' => __( 'contact', 'rhd-artny-directory' ),
			'entry_label_plural'   => __( 'contacts', 'rhd-artny-directory' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function individuals_config() {
		return array(
			'type'            => self::TYPE_INDIVIDUALS,
			'block_name'      => 'rhd/artny-individuals-directory',
			'title'           => __( 'ART/NY Individuals Directory', 'rhd-artny-directory' ),
			'description'     => __( 'Searchable individuals directory with filters for Xplor contacts.', 'rhd-artny-directory' ),
			'icon'            => 'admin-users',
			'table'           => 'Contact',
			'cache_key'       => 'rhd_artny_directory_individuals_v10',
			'cron_hook'       => 'rhd_artny_directory_sync_individuals',
			'api_fields'      => array(
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
			),
			'taxonomy_fields' => array(
				'PrimaryPractice'   => array(
					'multi'       => false,
					'label'       => __( 'Primary Practice', 'rhd-artny-directory' ),
					'card_label'  => __( 'Primary Practice:', 'rhd-artny-directory' ),
					'filter'      => 'primary_practice',
					'data_attr'   => 'data-primary-practice',
				),
				'ArtisticPractices' => array(
					'multi'       => true,
					'label'       => __( 'Artistic Practices', 'rhd-artny-directory' ),
					'card_label'  => __( 'Artistic Practices:', 'rhd-artny-directory' ),
					'filter'      => 'artistic_practices',
					'data_attr'   => 'data-artistic-practices',
				),
			),
			'social_fields'   => array(
				'linkedin'  => 'LinkedIn',
				'facebook'  => 'Facebook',
				'instagram' => 'Instagram',
			),
			'filter_heading'  => __( 'Search the directory', 'rhd-artny-directory' ),
			'filter_hint'     => __( 'Filter by name or practice areas', 'rhd-artny-directory' ),
			'search_placeholder' => __( 'Name or keywords…', 'rhd-artny-directory' ),
			'empty_message'   => __( 'No individuals match your filters. Try adjusting your search or clearing filters.', 'rhd-artny-directory' ),
			// Temporary: ArtistBio/Description not required. Web presence not required alone;
			// PrimaryPractice and/or web/social qualifies.
			'require_description'  => false,
			'require_web_presence' => false,
			'entry_label_singular' => __( 'individual', 'rhd-artny-directory' ),
			'entry_label_plural'   => __( 'individuals', 'rhd-artny-directory' ),
		);
	}
}
