<?php
/**
 * Admin bar utilities and directory exclusion settings.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-only data refresh controls and ineligible-member management.
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
	 * Settings page slug under Settings.
	 */
	const SETTINGS_PAGE_SLUG = 'rhd-artny-directory-exclusions';

	/**
	 * Admin-post action for saving ineligible flags.
	 */
	const SAVE_EXCLUSIONS_ACTION = 'rhd_artny_directory_save_exclusions';

	/**
	 * Transient key for exclusion save results shown after redirect.
	 */
	const SAVE_EXCLUSIONS_RESULT_FLAG = 'rhd_artny_directory_exclusions_result';

	/**
	 * Register admin hooks.
	 */
	public static function register_hooks() {
		add_action( 'admin_bar_menu', array( __CLASS__, 'add_admin_bar_link' ), 100 );
		add_action( 'admin_post_' . self::REFRESH_DATA_ACTION, array( __CLASS__, 'handle_refresh_data' ) );
		add_action( 'admin_post_' . self::SAVE_EXCLUSIONS_ACTION, array( __CLASS__, 'handle_save_exclusions' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_refresh_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'render_exclusions_notice' ) );
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
	 * Register Settings → Directory exclusions.
	 */
	public static function register_settings_page() {
		add_options_page(
			__( 'Directory exclusions', 'rhd-artny-directory' ),
			__( 'Directory exclusions', 'rhd-artny-directory' ),
			'manage_options',
			self::SETTINGS_PAGE_SLUG,
			array( __CLASS__, 'render_settings_page' )
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
	 * Persist ineligible flags for one directory type, refresh that cache, redirect.
	 */
	public static function handle_save_exclusions() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to update directory exclusions.', 'rhd-artny-directory' ) );
		}

		check_admin_referer( self::SAVE_EXCLUSIONS_ACTION );

		$type = isset( $_POST['directory_type'] ) ? sanitize_key( wp_unslash( (string) $_POST['directory_type'] ) ) : '';
		if ( null === RHD_Artny_Directory_Config::get( $type ) ) {
			$type = RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS;
		}

		$posted_ids = isset( $_POST['ineligible_ids'] ) && is_array( $_POST['ineligible_ids'] )
			? wp_unslash( $_POST['ineligible_ids'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per ID below.
			: array();

		$posted_names = isset( $_POST['ineligible_names'] ) && is_array( $_POST['ineligible_names'] )
			? wp_unslash( $_POST['ineligible_names'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per name below.
			: array();

		$visible_ids = isset( $_POST['visible_ids'] ) && is_array( $_POST['visible_ids'] )
			? wp_unslash( $_POST['visible_ids'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per ID below.
			: array();

		$checked = array();
		foreach ( $posted_ids as $raw_id ) {
			$id = sanitize_text_field( (string) $raw_id );
			if ( '' !== $id ) {
				$checked[ $id ] = true;
			}
		}

		$visible = array();
		foreach ( $visible_ids as $raw_id ) {
			$id = sanitize_text_field( (string) $raw_id );
			if ( '' !== $id ) {
				$visible[ $id ] = true;
			}
		}

		$entries = RHD_Artny_Directory_Exclusions::get_for_type( $type );
		$now     = time();

		// Drop any previously ineligible ID that was shown on this form but left unchecked.
		foreach ( array_keys( $visible ) as $id ) {
			if ( ! isset( $checked[ $id ] ) ) {
				unset( $entries[ $id ] );
			}
		}

		foreach ( array_keys( $checked ) as $id ) {
			$name = '';
			if ( isset( $posted_names[ $id ] ) ) {
				$name = sanitize_text_field( (string) $posted_names[ $id ] );
			} elseif ( isset( $entries[ $id ]['name'] ) ) {
				$name = (string) $entries[ $id ]['name'];
			}

			$entries[ $id ] = array(
				'name'       => $name,
				'updated_at' => $now,
			);
		}

		RHD_Artny_Directory_Exclusions::set_for_type( $type, $entries );
		$payload = RHD_Artny_Directory_Data::refresh_cache( $type );

		set_transient(
			self::SAVE_EXCLUSIONS_RESULT_FLAG,
			array(
				'type'            => $type,
				'excluded_count'  => count( $entries ),
				'directory_count' => isset( $payload['contacts'] ) && is_array( $payload['contacts'] ) ? count( $payload['contacts'] ) : 0,
				'error'           => isset( $payload['error'] ) ? (string) $payload['error'] : '',
			),
			MINUTE_IN_SECONDS
		);

		$redirect = add_query_arg(
			array(
				'page'           => self::SETTINGS_PAGE_SLUG,
				'directory_type' => $type,
			),
			admin_url( 'options-general.php' )
		);

		$search = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['q'] ) ) : '';
		if ( '' !== $search ) {
			$redirect = add_query_arg( 'q', $search, $redirect );
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

		$has_error    = '' !== $org_error || '' !== $ind_error;
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

	/**
	 * Confirm exclusion save in wp-admin after redirect.
	 */
	public static function render_exclusions_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'settings_page_' . self::SETTINGS_PAGE_SLUG !== $screen->id ) {
			return;
		}

		$results = get_transient( self::SAVE_EXCLUSIONS_RESULT_FLAG );
		if ( ! is_array( $results ) ) {
			return;
		}

		delete_transient( self::SAVE_EXCLUSIONS_RESULT_FLAG );

		$excluded = isset( $results['excluded_count'] ) ? (int) $results['excluded_count'] : 0;
		$listed   = isset( $results['directory_count'] ) ? (int) $results['directory_count'] : 0;
		$error    = isset( $results['error'] ) ? (string) $results['error'] : '';
		$type     = isset( $results['type'] ) ? (string) $results['type'] : '';
		$config   = RHD_Artny_Directory_Config::get( $type );
		$label    = $config ? $config['entry_label_plural'] : $type;

		$notice_class = '' !== $error ? 'notice-warning' : 'notice-success';

		$message = sprintf(
			/* translators: 1: excluded count, 2: directory type label, 3: remaining listed count */
			__( 'Directory exclusions saved. %1$d %2$s marked ineligible. Directory now lists %3$d entries.', 'rhd-artny-directory' ),
			$excluded,
			$label,
			$listed
		);

		if ( '' !== $error ) {
			$message .= ' ' . sprintf(
				/* translators: %s: sync error */
				__( 'Sync warning: %s', 'rhd-artny-directory' ),
				$error
			);
		}

		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $notice_class ),
			esc_html( $message )
		);
	}

	/**
	 * Settings page: flag members as ineligible for the public directory.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$type = isset( $_GET['directory_type'] ) ? sanitize_key( wp_unslash( (string) $_GET['directory_type'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab switch.
		if ( null === RHD_Artny_Directory_Config::get( $type ) ) {
			$type = RHD_Artny_Directory_Config::TYPE_ORGANIZATIONS;
		}

		$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['q'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only search filter.

		$config     = RHD_Artny_Directory_Config::get( $type );
		$ineligible = RHD_Artny_Directory_Exclusions::get_for_type( $type );
		$contacts   = RHD_Artny_Directory_Data::get_contacts( $type );

		$ineligible_rows = self::build_ineligible_rows( $ineligible, $search );
		$listed_rows     = self::build_listed_rows( $contacts, $ineligible, $search );

		$base_url = add_query_arg(
			array(
				'page'           => self::SETTINGS_PAGE_SLUG,
				'directory_type' => $type,
			),
			admin_url( 'options-general.php' )
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Directory exclusions', 'rhd-artny-directory' ); ?></h1>
			<p>
				<?php esc_html_e( 'Flag members to exclude them from the public directory immediately, including during the membership expiry grace period. Excluded entries are skipped on the next Xplor sync and removed from the live list right away.', 'rhd-artny-directory' ); ?>
			</p>

			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Directory type', 'rhd-artny-directory' ); ?>">
				<?php foreach ( RHD_Artny_Directory_Config::all() as $tab_type => $tab_config ) : ?>
					<?php
					$tab_url = add_query_arg(
						array(
							'page'           => self::SETTINGS_PAGE_SLUG,
							'directory_type' => $tab_type,
						),
						admin_url( 'options-general.php' )
					);
					if ( '' !== $search ) {
						$tab_url = add_query_arg( 'q', $search, $tab_url );
					}
					$is_current = ( $tab_type === $type );
					?>
					<a
						href="<?php echo esc_url( $tab_url ); ?>"
						class="nav-tab<?php echo $is_current ? ' nav-tab-active' : ''; ?>"
						<?php echo $is_current ? ' aria-current="page"' : ''; ?>
					>
						<?php echo esc_html( $tab_config['title'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="get" action="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>" style="margin: 1em 0;">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SETTINGS_PAGE_SLUG ); ?>" />
				<input type="hidden" name="directory_type" value="<?php echo esc_attr( $type ); ?>" />
				<label for="rhd-artny-directory-exclusions-search">
					<?php esc_html_e( 'Search by name', 'rhd-artny-directory' ); ?>
				</label>
				<input
					type="search"
					id="rhd-artny-directory-exclusions-search"
					name="q"
					value="<?php echo esc_attr( $search ); ?>"
					class="regular-text"
				/>
				<?php submit_button( __( 'Search', 'rhd-artny-directory' ), 'secondary', '', false ); ?>
				<?php if ( '' !== $search ) : ?>
					<a class="button button-link" href="<?php echo esc_url( $base_url ); ?>">
						<?php esc_html_e( 'Clear search', 'rhd-artny-directory' ); ?>
					</a>
				<?php endif; ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::SAVE_EXCLUSIONS_ACTION ); ?>" />
				<input type="hidden" name="directory_type" value="<?php echo esc_attr( $type ); ?>" />
				<input type="hidden" name="q" value="<?php echo esc_attr( $search ); ?>" />
				<?php wp_nonce_field( self::SAVE_EXCLUSIONS_ACTION ); ?>

				<?php
				self::render_member_table(
					'ineligible',
					sprintf(
						/* translators: %s: directory entry plural label */
						__( 'Currently ineligible %s', 'rhd-artny-directory' ),
						$config['entry_label_plural']
					),
					$ineligible_rows,
					true
				);

				self::render_member_table(
					'listed',
					sprintf(
						/* translators: %s: directory entry plural label */
						__( 'Currently listed %s', 'rhd-artny-directory' ),
						$config['entry_label_plural']
					),
					$listed_rows,
					false
				);
				?>

				<?php submit_button( __( 'Save exclusions', 'rhd-artny-directory' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param array<string, array{name: string, updated_at: int}> $ineligible Ineligible map.
	 * @param string                                               $search     Name search filter.
	 * @return array<int, array{id: string, name: string, checked: bool}>
	 */
	private static function build_ineligible_rows( $ineligible, $search ) {
		$rows = array();

		foreach ( $ineligible as $id => $meta ) {
			$name = isset( $meta['name'] ) ? (string) $meta['name'] : '';
			if ( ! self::name_matches_search( $name, $search ) && ! self::name_matches_search( (string) $id, $search ) ) {
				continue;
			}

			$rows[] = array(
				'id'      => (string) $id,
				'name'    => '' !== $name ? $name : (string) $id,
				'checked' => true,
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		return $rows;
	}

	/**
	 * @param array<int, array<string, mixed>>                     $contacts   Displayable contacts.
	 * @param array<string, array{name: string, updated_at: int}> $ineligible Ineligible map.
	 * @param string                                               $search     Name search filter.
	 * @return array<int, array{id: string, name: string, checked: bool}>
	 */
	private static function build_listed_rows( $contacts, $ineligible, $search ) {
		$rows = array();

		foreach ( $contacts as $contact ) {
			if ( ! is_array( $contact ) ) {
				continue;
			}

			$id = isset( $contact['ID'] ) ? trim( (string) $contact['ID'] ) : '';
			if ( '' === $id || isset( $ineligible[ $id ] ) ) {
				continue;
			}

			$name = isset( $contact['Name'] ) ? (string) $contact['Name'] : '';
			if ( ! self::name_matches_search( $name, $search ) ) {
				continue;
			}

			$rows[] = array(
				'id'      => $id,
				'name'    => $name,
				'checked' => false,
			);
		}

		usort(
			$rows,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		return $rows;
	}

	/**
	 * @param string $name   Member name.
	 * @param string $search Search needle.
	 * @return bool
	 */
	private static function name_matches_search( $name, $search ) {
		$search = trim( $search );
		if ( '' === $search ) {
			return true;
		}

		return false !== stripos( $name, $search );
	}

	/**
	 * @param string                                                    $table_id Table id suffix.
	 * @param string                                                    $caption  Table caption.
	 * @param array<int, array{id: string, name: string, checked: bool}> $rows     Rows to render.
	 * @param bool                                                      $checked_default Whether rows are currently excluded.
	 */
	private static function render_member_table( $table_id, $caption, $rows, $checked_default ) {
		$table_dom_id = 'rhd-artny-directory-exclusions-' . sanitize_key( $table_id );
		?>
		<table class="widefat striped" id="<?php echo esc_attr( $table_dom_id ); ?>" style="margin-top: 1.5em;">
			<caption class="screen-reader-text"><?php echo esc_html( $caption ); ?></caption>
			<thead>
				<tr>
					<th scope="col" style="width: 3em;"><?php esc_html_e( 'Exclude', 'rhd-artny-directory' ); ?></th>
					<th scope="col"><?php echo esc_html( $caption ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr>
						<td colspan="2">
							<?php
							echo esc_html(
								$checked_default
									? __( 'No ineligible members match this view.', 'rhd-artny-directory' )
									: __( 'No listed members match this view.', 'rhd-artny-directory' )
							);
							?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$checkbox_id = 'rhd-excl-' . sanitize_html_class( $row['id'] );
						$name_id     = $checkbox_id . '-name';
						?>
						<tr>
							<td>
								<input type="hidden" name="visible_ids[]" value="<?php echo esc_attr( $row['id'] ); ?>" />
								<input
									type="checkbox"
									name="ineligible_ids[]"
									id="<?php echo esc_attr( $checkbox_id ); ?>"
									value="<?php echo esc_attr( $row['id'] ); ?>"
									<?php checked( ! empty( $row['checked'] ) ); ?>
									aria-labelledby="<?php echo esc_attr( $name_id ); ?>"
								/>
								<input
									type="hidden"
									name="ineligible_names[<?php echo esc_attr( $row['id'] ); ?>]"
									value="<?php echo esc_attr( $row['name'] ); ?>"
								/>
							</td>
							<td>
								<label id="<?php echo esc_attr( $name_id ); ?>" for="<?php echo esc_attr( $checkbox_id ); ?>">
									<?php echo esc_html( $row['name'] ); ?>
								</label>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
		<?php
	}
}
