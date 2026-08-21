<?php
/**
 * Plugin Name: RHD ART/NY Directory
 * Description: Searchable member directory blocks with filters for Xplor organizations and individuals.
 * Version: 1.2.0
 * Author: Roundhouse Designs
 * Text Domain: rhd-artny-directory
 * Requires at least: 6.5
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RHD_ARTNY_DIRECTORY_VERSION', '1.2.0' );
define( 'RHD_ARTNY_DIRECTORY_PATH', plugin_dir_path( __FILE__ ) );
define( 'RHD_ARTNY_DIRECTORY_URL', plugin_dir_url( __FILE__ ) );

require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-directory-config.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-directory-exclusions.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-demo-data.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-perfectmind-api.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-directory-data.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-directory-render.php';
require_once RHD_ARTNY_DIRECTORY_PATH . 'includes/class-directory-admin.php';

/**
 * Bootstrap directory blocks.
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
		add_action( 'init', array( $this, 'register_blocks' ) );
		RHD_Artny_Directory_Data::register_hooks();
		RHD_Artny_Directory_Admin::register_hooks();
	}

	/**
	 * Register shared editor script.
	 *
	 * @return string Script handle.
	 */
	private function register_editor_script() {
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

		return $editor_script;
	}

	/**
	 * Register front-end view script with filemtime cache-busting.
	 *
	 * block.json "viewScript" alone uses the static block version (1.0.0),
	 * which Cloudflare caches indefinitely across asset updates.
	 *
	 * @return string Script handle.
	 */
	private function register_view_script() {
		$view_script = 'rhd-artny-directory-view';
		$view_path   = RHD_ARTNY_DIRECTORY_PATH . 'assets/view.js';

		wp_register_script(
			$view_script,
			RHD_ARTNY_DIRECTORY_URL . 'assets/view.js',
			array(),
			file_exists( $view_path ) ? (string) filemtime( $view_path ) : RHD_ARTNY_DIRECTORY_VERSION,
			true
		);

		return $view_script;
	}

	/**
	 * Register front-end stylesheet with filemtime cache-busting.
	 *
	 * @return string Style handle.
	 */
	private function register_block_style() {
		$style_handle = 'rhd-artny-directory-style';
		$style_path   = RHD_ARTNY_DIRECTORY_PATH . 'assets/style.css';

		wp_register_style(
			$style_handle,
			RHD_ARTNY_DIRECTORY_URL . 'assets/style.css',
			array(),
			file_exists( $style_path ) ? (string) filemtime( $style_path ) : RHD_ARTNY_DIRECTORY_VERSION
		);

		return $style_handle;
	}

	/**
	 * Register directory blocks from block.json metadata.
	 */
	public function register_blocks() {
		$editor_script = $this->register_editor_script();
		$view_script   = $this->register_view_script();
		$style_handle  = $this->register_block_style();

		register_block_type(
			RHD_ARTNY_DIRECTORY_PATH,
			array(
				'editor_script' => $editor_script,
				'view_script'   => $view_script,
				'style'         => $style_handle,
			)
		);

		register_block_type(
			RHD_ARTNY_DIRECTORY_PATH . 'blocks/individuals',
			array(
				'editor_script' => $editor_script,
				'view_script'   => $view_script,
				'style'         => $style_handle,
			)
		);
	}
}

RHD_Artny_Directory_Plugin::instance();
