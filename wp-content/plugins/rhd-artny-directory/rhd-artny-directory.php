<?php
/**
 * Plugin Name: RHD ART/NY Directory
 * Description: Searchable member directory block with filters for Perfectmind/Xplor contacts.
 * Version: 1.0.0
 * Author: Roundhouse Designs
 * Text Domain: rhd-artny-directory
 * Requires at least: 6.5
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RHD_ARTNY_DIRECTORY_VERSION', '1.0.0' );
define( 'RHD_ARTNY_DIRECTORY_PATH', plugin_dir_path( __FILE__ ) );
define( 'RHD_ARTNY_DIRECTORY_URL', plugin_dir_url( __FILE__ ) );

require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-demo-data.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-perfectmind-api.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-directory-data.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-directory-render.php';

/**
 * Bootstrap the contacts directory block.
 */
final class RHD_Artny_Directory_Plugin {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		RHD_Artny_Directory_Data::register_hooks();
	}

	/**
	 * Register the dynamic block from block.json metadata.
	 */
	public function register_block() {
		$editor_script = 'rhd-artny-directory-editor';
		$editor_path   = RHD_ARTNY_DIRECTORY_PATH . 'assets/editor.js';

		wp_register_script(
			$editor_script,
			RHD_ARTNY_DIRECTORY_URL . 'assets/editor.js',
			array(
				'wp-blocks',
				'wp-element',
				'wp-block-editor',
				'wp-server-side-render',
				'wp-components',
			),
			file_exists( $editor_path ) ? (string) filemtime( $editor_path ) : RHD_ARTNY_DIRECTORY_VERSION,
			false
		);

		register_block_type(
			RHD_ARTNY_DIRECTORY_PATH,
			array(
				'editor_script' => $editor_script,
			)
		);

		// Back-compat for content saved under the pre-rename block name.
		register_block_type(
			'rhd/contacts-directory',
			array(
				'api_version'     => 3,
				'title'           => 'ART/NY Directory',
				'category'        => 'widgets',
				'icon'            => 'groups',
				'editor_script'   => $editor_script,
				'view_script'     => 'rhd-artny-directory-view-script',
				'style'           => 'rhd-artny-directory-style',
				'render_callback' => array( 'RHD_Artny_Directory_Render', 'render' ),
				'attributes'      => array(
					'align' => array(
						'type' => 'string',
					),
				),
				'supports'        => array(
					'html'  => false,
					'align' => array( 'wide', 'full' ),
				),
			)
		);
	}
}

RHD_Artny_Directory_Plugin::instance();
