<?php
/**
 * Demo contact records for the directory UI (fallback when API is unavailable).
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static demo dataset mirroring the Directory page mock cards.
 */
final class RHD_Artny_Directory_Demo_Data {

	/**
	 * Taxonomy labels keyed by slug.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function taxonomy_labels() {
		return array(
			'ArtisticFocus' => array(
				'experimental-performance-work' => __( 'Experimental Performance Work', 'rhd-artny-directory' ),
				'new-plays'                     => __( 'New Plays', 'rhd-artny-directory' ),
				'community-arts'                => __( 'Community Arts', 'rhd-artny-directory' ),
				'advocacy-artivism'             => __( 'Advocacy & Artivism', 'rhd-artny-directory' ),
			),
			'OrganizationalFocus' => array(
				'producing-works'       => __( 'Producing Works', 'rhd-artny-directory' ),
				'new-work-development'  => __( 'New Work Development/Commissioning', 'rhd-artny-directory' ),
				'youth-education'       => __( 'Youth Education', 'rhd-artny-directory' ),
				'justice-system-reform' => __( 'Justice System Reform', 'rhd-artny-directory' ),
			),
			'PublicProgrammingLocations' => array(
				'bronx'         => __( 'Bronx', 'rhd-artny-directory' ),
				'brooklyn'      => __( 'Brooklyn', 'rhd-artny-directory' ),
				'manhattan'     => __( 'Manhattan', 'rhd-artny-directory' ),
				'queens'        => __( 'Queens', 'rhd-artny-directory' ),
				'staten-island' => __( 'Staten Island', 'rhd-artny-directory' ),
			),
		);
	}

	/**
	 * Demo contacts using the same field names as live PerfectMind data.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function contacts() {
		return array(
			array(
				'Name'                       => 'New Georges',
				'Description'                => 'New Georges advocates for an intergenerational ecosystem of exuberant theatrical minds, furthering fierce new works along with long-term wellbeing, expanding aesthetic boundaries and gender equity in tandem. Since 1992, we\'ve imagined a new kind of artistic home–a relaxed, participatory culture, a productive oasis in a competitive field.',
				'Website'                    => 'http://www.newgeorges.org',
				'Instagram'                  => 'https://instagram.com/newgeorges',
				'OrganizationLinkedInProfile' => 'https://www.linkedin.com/company/new-georges',
				'OrganizationFacebookPage'   => 'https://www.facebook.com/newgeorges',
				'ArtisticFocus'              => array( 'experimental-performance-work', 'new-plays' ),
				'OrganizationalFocus'        => array( 'producing-works', 'new-work-development' ),
				'PublicProgrammingLocations' => array( 'bronx', 'brooklyn', 'manhattan', 'queens' ),
			),
			array(
				'Name'                       => 'Arts Ignite',
				'Description'                => 'At Arts Ignite, their mission is to develop agency in youth through the arts. Through performing arts, visual arts, and creative writing, they support young people ages 4-22 to discover the joy and power of making art to share their voices, tell their stories, and transform their lives.',
				'Website'                    => 'https://artsignite.org',
				'Instagram'                  => 'https://instagram.com/artsignite',
				'OrganizationLinkedInProfile' => 'https://www.linkedin.com/company/arts-ignite',
				'OrganizationFacebookPage'   => '',
				'ArtisticFocus'              => array( 'community-arts', 'new-plays' ),
				'OrganizationalFocus'        => array( 'youth-education', 'new-work-development' ),
				'PublicProgrammingLocations' => array( 'bronx', 'brooklyn', 'manhattan' ),
			),
			array(
				'Name'                       => 'Broadway Advocacy Coalition',
				'Description'                => 'Broadway Advocacy Coalition (BAC) is an arts-based advocacy organization that unites artists and directly impacted advocates to develop story-based artivism that advances justice and drives systemic change. Through its signature Theater of Change methodology, BAC leverages the arts and storytelling as powerful tools to imagine a world without systemic racism and the carceral state.',
				'Website'                    => 'https://www.broadwayadvocacycoalition.org',
				'Instagram'                  => 'https://instagram.com/broadwayadvocacycoalition',
				'OrganizationLinkedInProfile' => 'https://www.linkedin.com/company/broadway-advocacy-coalition',
				'OrganizationFacebookPage'   => '',
				'ArtisticFocus'              => array( 'advocacy-artivism', 'experimental-performance-work' ),
				'OrganizationalFocus'        => array( 'justice-system-reform', 'producing-works' ),
				'PublicProgrammingLocations' => array( 'manhattan', 'brooklyn' ),
			),
			array(
				'Name'                       => 'Staten Island Ensemble',
				'Description'                => 'A borough-based collective producing new work and community forums for Staten Island artists, with a focus on accessible rehearsal space and interborough collaboration.',
				'Website'                    => 'https://example.org/staten-island-ensemble',
				'Instagram'                  => '',
				'OrganizationLinkedInProfile' => '',
				'OrganizationFacebookPage'   => '',
				'ArtisticFocus'              => array( 'new-plays', 'community-arts' ),
				'OrganizationalFocus'        => array( 'producing-works' ),
				'PublicProgrammingLocations' => array( 'staten-island' ),
			),
		);
	}
}
