<?php
/**
 * Admin bar utilities for the directory plugin.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-only cache controls in the WordPress admin bar.
 */
final class RHD_Artny_Directory_Admin {

	/**
	 * Admin-post action for clearing directory cache.
	 */
	const CLEAR_CACHE_ACTION = 'rhd_artny_directory_clear_cache';

	/**
	 * Transient flag set after a successful cache clear.
	 */
	const CACHE_CLEARED_FLAG = 'rhd_artny_directory_cache_cleared';

	/**
	 * Register admin hooks.
	 */
	public static function register_hooks() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_link' ), 100 );
		add_action( 'admin_post_' . self::CLEAR_CACHE_ACTION, array( __CLASS__, 'handle_clear_cache' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_cache_cleared_notice' ) );
	}

	/**
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public static function add_admin_bar_link( $wp_admin_bar ) {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'rhd-artny-directory-clear-cache',
				'title' => esc_html__( 'Clear PerfectMind Cache', 'rhd-artny-directory' ),
				'href'  => wp_nonce_url(
					admin_url( 'admin-post.php?action=' . self::CLEAR_CACHE_ACTION ),
					self::CLEAR_CACHE_ACTION
				),
				'meta'  => array(
					'title' => esc_attr__( 'Clear cached PerfectMind directory data for organizations and individuals.', 'rhd-artny-directory' ),
				),
			)
		);
	}

	/**
	 * Clear directory transients and redirect back.
	 */
	public static function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to clear the directory cache.', 'rhd-artny-directory' ) );
		}

		check_admin_referer( self::CLEAR_CACHE_ACTION );

		RHD_Artny_Directory_Data::clear_cache();
		set_transient( self::CACHE_CLEARED_FLAG, 1, MINUTE_IN_SECONDS );

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = is_admin() ? admin_url() : home_url( '/' );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Confirm cache clear in wp-admin after redirect.
	 */
	public static function render_cache_cleared_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! get_transient( self::CACHE_CLEARED_FLAG ) ) {
			return;
		}

		delete_transient( self::CACHE_CLEARED_FLAG );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'PerfectMind directory cache cleared. Data will refresh on the next directory request.', 'rhd-artny-directory' )
		);
	}
}
