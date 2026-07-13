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
 * Admin-only data refresh controls in the WordPress admin bar.
 */
final class RHD_Artny_Directory_Admin {

	/**
	 * Admin-post action for refreshing directory data.
	 */
	const REFRESH_DATA_ACTION = 'rhd_artny_directory_refresh_data';

	/**
	 * Transient key for refresh results shown after redirect.
	 */
	const REFRESH_RESULT_FLAG = 'rhd_artny_directory_refresh_result';

	/**
	 * Register admin hooks.
	 */
	public static function register_hooks() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_link' ), 100 );
		add_action( 'admin_post_' . self::REFRESH_DATA_ACTION, array( __CLASS__, 'handle_refresh_data' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_refresh_notice' ) );
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
				'id'    => 'rhd-artny-directory-refresh-data',
				'title' => esc_html__( 'Refresh Xplor Data', 'rhd-artny-directory' ),
				'href'  => wp_nonce_url(
					admin_url( 'admin-post.php?action=' . self::REFRESH_DATA_ACTION ),
					self::REFRESH_DATA_ACTION
				),
				'meta'  => array(
					'title' => esc_attr__( 'Clear cached directory data and fetch fresh organizations and individuals from Xplor.', 'rhd-artny-directory' ),
				),
			)
		);
	}

	/**
	 * Clear directory cache, refetch from Xplor, and redirect back.
	 */
	public static function handle_refresh_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to refresh directory data.', 'rhd-artny-directory' ) );
		}

		check_admin_referer( self::REFRESH_DATA_ACTION );

		$results = RHD_Artny_Directory_Data::refresh_all_caches();
		set_transient( self::REFRESH_RESULT_FLAG, $results, MINUTE_IN_SECONDS );

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = is_admin() ? admin_url() : home_url( '/' );
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Confirm data refresh in wp-admin after redirect.
	 */
	public static function render_refresh_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$results = get_transient( self::REFRESH_RESULT_FLAG );
		if ( ! is_array( $results ) ) {
			return;
		}

		delete_transient( self::REFRESH_RESULT_FLAG );

		$org_count = isset( $results['organizations']['count'] ) ? (int) $results['organizations']['count'] : 0;
		$ind_count = isset( $results['individuals']['count'] ) ? (int) $results['individuals']['count'] : 0;
		$org_error = isset( $results['organizations']['error'] ) ? (string) $results['organizations']['error'] : '';
		$ind_error = isset( $results['individuals']['error'] ) ? (string) $results['individuals']['error'] : '';

		$has_error = '' !== $org_error || '' !== $ind_error;
		$notice_class = $has_error ? 'notice-warning' : 'notice-success';

		$message = sprintf(
			/* translators: 1: organizations count, 2: individuals count */
			__( 'Xplor directory data refreshed. Organizations: %1$d entries. Individuals: %2$d entries.', 'rhd-artny-directory' ),
			$org_count,
			$ind_count
		);

		if ( '' !== $org_error ) {
			$message .= ' ' . sprintf(
				/* translators: %s: API error message */
				__( 'Organizations sync warning: %s', 'rhd-artny-directory' ),
				$org_error
			);
		}

		if ( '' !== $ind_error ) {
			$message .= ' ' . sprintf(
				/* translators: %s: API error message */
				__( 'Individuals sync warning: %s', 'rhd-artny-directory' ),
				$ind_error
			);
		}

		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notice_class ),
			esc_html( $message )
		);
	}
}
