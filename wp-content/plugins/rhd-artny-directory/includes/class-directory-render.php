<?php
/**
 * Markup for the contacts directory block.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders filter bar and contact cards.
 */
final class RHD_Artny_Directory_Render {

	/**
	 * Contacts shown per results page.
	 */
	const PER_PAGE = 20;

	/**
	 * Render full directory markup.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render( $attributes = array() ) {
		$align = isset( $attributes['align'] ) ? sanitize_key( $attributes['align'] ) : '';
		$align_class = $align ? ' align' . $align : '';

		$labels   = RHD_Artny_Directory_Data::get_taxonomy_labels();
		$contacts = RHD_Artny_Directory_Data::get_contacts();
		$status   = RHD_Artny_Directory_Data::get_sync_status();

		ob_start();
		?>
		<div class="rhd-artny-directory wp-block-group alignfull<?php echo esc_attr( $align_class ); ?>" data-rhd-artny-directory data-rhd-artny-directory-per-page="<?php echo esc_attr( (string) self::PER_PAGE ); ?>">
			<?php self::render_filters( $labels ); ?>
			<?php self::render_status(); ?>
			<?php self::render_results( $contacts, $labels ); ?>
			<?php self::render_pagination(); ?>
			<?php self::render_data_notice( $status ); ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Filter bar: search + multi-select dropdown groups.
	 *
	 * @param array<string, array<string, string>> $labels Taxonomy labels.
	 */
	private static function render_filters( $labels ) {
		$filter_groups = array(
			'ArtisticFocus'              => array(
				'legend'    => __( 'Artistic Focus', 'rhd-artny-directory' ),
				'filter'    => 'artistic_focus',
				'data_attr' => 'data-artistic-focus',
			),
			'OrganizationalFocus'        => array(
				'legend'    => __( 'Organizational Focus', 'rhd-artny-directory' ),
				'filter'    => 'org_focus',
				'data_attr' => 'data-org-focus',
			),
			'PublicProgrammingLocations' => array(
				'legend'    => __( 'Location', 'rhd-artny-directory' ),
				'filter'    => 'location',
				'data_attr' => 'data-location',
			),
		);
		?>
		<section class="rhd-artny-directory__filters" aria-labelledby="rhd-artny-directory-filter-heading">
			<div class="rhd-artny-directory__filters-header wp-block-group alignwide">
				<h2 id="rhd-artny-directory-filter-heading" class="wp-block-heading has-x-large-font-size">
					<?php esc_html_e( 'Search the directory', 'rhd-artny-directory' ); ?>
				</h2>
				<p class="is-style-text-annotation" id="rhd-artny-directory-filter-description">
					<?php esc_html_e( 'Filter by name, focus areas, or location', 'rhd-artny-directory' ); ?>
				</p>
			</div>

			<form class="rhd-artny-directory__filters-form alignwide" role="search" aria-label="<?php esc_attr_e( 'Directory search and filters', 'rhd-artny-directory' ); ?>" aria-describedby="rhd-artny-directory-filter-description" data-rhd-artny-directory-form>
				<div class="rhd-artny-directory__search">
					<label class="rhd-artny-directory__search-label" for="rhd-artny-directory-search">
						<span class="rhd-artny-directory__search-label-text"><?php esc_html_e( 'Search', 'rhd-artny-directory' ); ?></span>
						<input
							type="search"
							id="rhd-artny-directory-search"
							name="q"
							class="rhd-artny-directory__search-input"
							placeholder="<?php esc_attr_e( 'Name or keywords…', 'rhd-artny-directory' ); ?>"
							autocomplete="off"
							data-rhd-artny-directory-search
						/>
					</label>
				</div>

				<div class="rhd-artny-directory__filter-groups">
					<?php foreach ( $filter_groups as $taxonomy => $group ) : ?>
						<?php
						$slugs = RHD_Artny_Directory_Data::get_used_taxonomy_slugs( $taxonomy );
						if ( empty( $slugs ) ) {
							continue;
						}

						$group_id    = 'rhd-artny-directory-' . $group['filter'];
						$label_id    = $group_id . '-label';
						$summary_id  = $group_id . '-summary';
						$trigger_id  = $group_id . '-trigger';
						$panel_id    = $group_id . '-panel';
						$default_label = sprintf(
							/* translators: %s: filter group name, e.g. Artistic Focus */
							__( 'All %s', 'rhd-artny-directory' ),
							$group['legend']
						);
						?>
						<div class="rhd-artny-directory__filter-dropdown" data-rhd-artny-directory-filter-dropdown>
							<span class="rhd-artny-directory__filter-dropdown-label" id="<?php echo esc_attr( $label_id ); ?>">
								<?php echo esc_html( $group['legend'] ); ?>
							</span>
							<div class="rhd-artny-directory__filter-dropdown-control">
								<button
									type="button"
									class="rhd-artny-directory__filter-dropdown-trigger"
									id="<?php echo esc_attr( $trigger_id ); ?>"
									aria-labelledby="<?php echo esc_attr( $label_id . ' ' . $summary_id ); ?>"
									aria-haspopup="true"
									aria-expanded="false"
									aria-controls="<?php echo esc_attr( $panel_id ); ?>"
									data-rhd-artny-directory-filter-trigger
									data-rhd-artny-directory-filter-name="<?php echo esc_attr( $group['filter'] ); ?>"
								>
									<span
										class="rhd-artny-directory__filter-dropdown-summary"
										id="<?php echo esc_attr( $summary_id ); ?>"
										data-rhd-artny-directory-filter-summary
										data-default-label="<?php echo esc_attr( $default_label ); ?>"
									><?php echo esc_html( $default_label ); ?></span>
									<span class="rhd-artny-directory__filter-dropdown-icon" aria-hidden="true"></span>
								</button>
								<div
									class="rhd-artny-directory__filter-dropdown-panel"
									id="<?php echo esc_attr( $panel_id ); ?>"
									role="group"
									aria-labelledby="<?php echo esc_attr( $label_id ); ?>"
									hidden
									data-rhd-artny-directory-filter-panel
								>
									<ul class="rhd-artny-directory__checkbox-list" role="list">
										<?php foreach ( $slugs as $slug ) : ?>
											<?php
											if ( empty( $labels[ $taxonomy ][ $slug ] ) ) {
												continue;
											}
											$input_id = $group_id . '-' . $slug;
											?>
											<li class="rhd-artny-directory__checkbox-item" role="listitem">
												<input
													type="checkbox"
													class="rhd-artny-directory__checkbox"
													id="<?php echo esc_attr( $input_id ); ?>"
													name="<?php echo esc_attr( $group['filter'] ); ?>[]"
													value="<?php echo esc_attr( $slug ); ?>"
													data-rhd-artny-directory-filter="<?php echo esc_attr( $group['filter'] ); ?>"
													data-rhd-artny-directory-filter-label="<?php echo esc_attr( $labels[ $taxonomy ][ $slug ] ); ?>"
												/>
												<label class="rhd-artny-directory__checkbox-label" for="<?php echo esc_attr( $input_id ); ?>">
													<?php echo esc_html( $labels[ $taxonomy ][ $slug ] ); ?>
												</label>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="rhd-artny-directory__filter-actions">
					<button type="button" class="rhd-artny-directory__clear" data-rhd-artny-directory-clear>
						<?php esc_html_e( 'Clear filters', 'rhd-artny-directory' ); ?>
					</button>
				</div>
			</form>
		</section>
		<?php
	}

	/**
	 * Optional notice when live data is unavailable (visible to site admins only).
	 *
	 * @param array<string, mixed> $status Sync metadata.
	 */
	private static function render_data_notice( $status ) {
		if ( empty( $status['error'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$message = (string) $status['error'];
		if ( ! empty( $status['source'] ) && 'stale-cache' === $status['source'] ) {
			$message = sprintf(
				/* translators: %s: API error message */
				__( 'Directory is showing cached data. Latest sync failed: %s', 'rhd-artny-directory' ),
				$status['error']
			);
		}
		?>
		<p class="rhd-artny-directory__data-notice alignwide is-style-text-annotation" role="status">
			<?php echo esc_html( $message ); ?>
		</p>
		<?php
	}

	/**
	 * Live region for result count / empty state.
	 */
	private static function render_status() {
		?>
		<p class="rhd-artny-directory__status alignwide" role="status" aria-live="polite" data-rhd-artny-directory-status></p>
		<?php
	}

	/**
	 * Contact cards grid.
	 *
	 * @param array<int, array<string, mixed>>     $contacts Contact records.
	 * @param array<string, array<string, string>> $labels   Taxonomy labels.
	 */
	private static function render_results( $contacts, $labels ) {
		$column_style = 'border-width:1px;border-radius:10px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40);';
		?>
		<div class="rhd-artny-directory__results-viewport alignwide" data-rhd-artny-directory-results-viewport>
			<div class="rhd-artny-directory__results" data-rhd-artny-directory-results>
				<?php foreach ( $contacts as $contact ) : ?>
					<?php echo self::render_card( $contact, $labels, $column_style ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_card. ?>
				<?php endforeach; ?>
			</div>
		</div>
		<p class="rhd-artny-directory__empty alignwide" hidden aria-hidden="true" data-rhd-artny-directory-empty>
			<?php esc_html_e( 'No contacts match your filters. Try adjusting your search or clearing filters.', 'rhd-artny-directory' ); ?>
		</p>
		<?php
	}

	/**
	 * Pagination controls (populated by view.js).
	 */
	private static function render_pagination() {
		?>
		<nav class="rhd-artny-directory__pagination alignwide" aria-label="<?php esc_attr_e( 'Directory results pages', 'rhd-artny-directory' ); ?>" data-rhd-artny-directory-pagination hidden>
			<button type="button" class="rhd-artny-directory__page-btn" data-rhd-artny-directory-prev>
				<?php esc_html_e( 'Previous', 'rhd-artny-directory' ); ?>
			</button>
			<p class="rhd-artny-directory__page-status" data-rhd-artny-directory-page-status></p>
			<button type="button" class="rhd-artny-directory__page-btn" data-rhd-artny-directory-next>
				<?php esc_html_e( 'Next', 'rhd-artny-directory' ); ?>
			</button>
		</nav>
		<?php
	}

	/**
	 * Single contact card (matches theme column card pattern).
	 *
	 * @param array<string, mixed>               $contact      Contact record.
	 * @param array<string, array<string, string>> $labels       Taxonomy labels.
	 * @param string                               $column_style Inline column styles.
	 * @return string
	 */
	private static function render_card( $contact, $labels, $column_style ) {
		$search_blob = self::build_search_blob( $contact, $labels );
		$data_attrs  = array(
			'data-rhd-artny-directory-card' => '',
			'data-name'                     => $contact['Name'],
			'data-search'                   => $search_blob,
			'data-artistic-focus'           => implode( ',', $contact['ArtisticFocus'] ?? array() ),
			'data-org-focus'                => implode( ',', $contact['OrganizationalFocus'] ?? array() ),
			'data-location'                 => implode( ',', $contact['PublicProgrammingLocations'] ?? array() ),
		);

		$attr_string = '';
		foreach ( $data_attrs as $key => $value ) {
			if ( '' === $value && 'data-rhd-artny-directory-card' !== $key ) {
				continue;
			}
			$attr_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		ob_start();
		?>
		<div class="wp-block-column has-border-color has-accent-6-border-color has-global-padding is-content-justification-center is-layout-constrained wp-block-column-is-layout-constrained rhd-artny-directory__card" style="<?php echo esc_attr( $column_style ); ?>"<?php echo $attr_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<h3 class="wp-block-heading has-large-font-size" style="margin-bottom:var(--wp--preset--spacing--20)">
				<?php echo esc_html( $contact['Name'] ); ?>
			</h3>

			<?php if ( ! empty( $contact['Description'] ) ) : ?>
				<details class="wp-block-details">
					<summary><?php esc_html_e( 'About', 'rhd-artny-directory' ); ?></summary>
					<p><?php echo esc_html( $contact['Description'] ); ?></p>
				</details>
			<?php endif; ?>

			<?php if ( ! empty( $contact['Website'] ) ) : ?>
				<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)">
					<div class="wp-block-button has-custom-width wp-block-button__width-100">
						<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $contact['Website'] ); ?>" style="letter-spacing:0.08px;line-height:1.2" target="_blank" rel="noreferrer noopener">
							<?php esc_html_e( 'Visit Website', 'rhd-artny-directory' ); ?>
						</a>
					</div>
				</div>
			<?php endif; ?>

			<?php
			$social_links = self::build_social_links( $contact );
			if ( ! empty( $social_links ) ) {
				echo self::render_social_links( $social_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks output.
			}
			?>

			<?php if ( ! empty( $contact['ArtisticFocus'] ) ) : ?>
				<p class="has-custom-1-font-size">
					<?php esc_html_e( 'Artistic Focus:', 'rhd-artny-directory' ); ?>
					<?php echo self::render_taxonomy_links( $contact['ArtisticFocus'], $labels['ArtisticFocus'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $contact['OrganizationalFocus'] ) ) : ?>
				<p class="has-custom-1-font-size">
					<?php esc_html_e( 'Org. Focus:', 'rhd-artny-directory' ); ?>
					<?php echo self::render_taxonomy_links( $contact['OrganizationalFocus'], $labels['OrganizationalFocus'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $contact['PublicProgrammingLocations'] ) ) : ?>
				<p class="has-custom-1-font-size">
					<?php esc_html_e( 'Programming Location:', 'rhd-artny-directory' ); ?>
					<?php echo self::render_taxonomy_links( $contact['PublicProgrammingLocations'], $labels['PublicProgrammingLocations'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Build social-link block input from stored contact fields.
	 *
	 * @param array<string, mixed> $contact Contact record.
	 * @return array<int, array<string, string>>
	 */
	private static function build_social_links( $contact ) {
		$social = array();

		if ( ! empty( $contact['OrganizationLinkedInProfile'] ) ) {
			$social[] = array(
				'service' => 'linkedin',
				'url'     => $contact['OrganizationLinkedInProfile'],
			);
		}

		if ( ! empty( $contact['OrganizationFacebookPage'] ) ) {
			$social[] = array(
				'service' => 'facebook',
				'url'     => $contact['OrganizationFacebookPage'],
			);
		}

		if ( ! empty( $contact['Instagram'] ) ) {
			$social[] = array(
				'service' => 'instagram',
				'url'     => $contact['Instagram'],
			);
		}

		return $social;
	}

	/**
	 * Render social icons via core/social-links (includes SVG icons).
	 *
	 * @param array<int, array<string, string>> $social_links Service/url pairs.
	 * @return string
	 */
	private static function render_social_links( $social_links ) {
		$inner_blocks = '';

		foreach ( $social_links as $link ) {
			$service = isset( $link['service'] ) ? sanitize_key( $link['service'] ) : '';
			$url     = isset( $link['url'] ) ? esc_url( $link['url'], array( 'https', 'http' ) ) : '';

			if ( '' === $service || '' === $url ) {
				continue;
			}

			$inner_blocks .= sprintf(
				'<!-- wp:social-link {"url":%1$s,"service":%2$s} /-->',
				wp_json_encode( $url ),
				wp_json_encode( $service )
			);
		}

		if ( '' === $inner_blocks ) {
			return '';
		}

		self::enqueue_social_link_assets();

		$block_markup = sprintf(
			'<!-- wp:social-links {"iconColor":"base","iconColorValue":"#FFFFFF","iconBackgroundColor":"contrast","iconBackgroundColorValue":"#111111","className":"is-style-default","layout":{"type":"flex","justifyContent":"center"},"openInNewTab":true} -->
<ul class="wp-block-social-links has-icon-color has-icon-background-color is-style-default">%s</ul>
<!-- /wp:social-links -->',
			$inner_blocks
		);

		return do_blocks( $block_markup );
	}

	/**
	 * Load core social-link block styles when directory cards render.
	 */
	private static function enqueue_social_link_assets() {
		static $enqueued = false;

		if ( $enqueued ) {
			return;
		}

		$enqueued = true;

		if ( wp_style_is( 'wp-block-social-links', 'registered' ) ) {
			wp_enqueue_style( 'wp-block-social-links' );
		}
	}

	/**
	 * Comma-separated linked taxonomy terms (matches demo page).
	 *
	 * @param array<int, string>    $slugs  Term slugs.
	 * @param array<string, string> $labels Slug => label.
	 * @return string
	 */
	private static function render_taxonomy_links( $slugs, $labels ) {
		$parts = array();

		foreach ( $slugs as $slug ) {
			if ( empty( $labels[ $slug ] ) ) {
				continue;
			}
			$parts[] = sprintf(
				'<a href="#" data-rhd-artny-directory-term="%1$s" data-rhd-artny-directory-term-label="%2$s">%2$s</a>',
				esc_attr( $slug ),
				esc_html( $labels[ $slug ] )
			);
		}

		return implode( ', ', $parts );
	}

	/**
	 * Lowercase searchable text for client-side filtering.
	 *
	 * @param array<string, mixed>               $contact Contact.
	 * @param array<string, array<string, string>> $labels  Labels.
	 * @return string
	 */
	private static function build_search_blob( $contact, $labels ) {
		$chunks = array(
			$contact['Name'] ?? '',
			$contact['Description'] ?? '',
		);

		foreach ( RHD_Artny_Directory_Data::TAXONOMY_FIELDS as $taxonomy ) {
			$slugs = $contact[ $taxonomy ] ?? array();
			if ( ! is_array( $slugs ) ) {
				continue;
			}

			foreach ( $slugs as $slug ) {
				if ( ! empty( $labels[ $taxonomy ][ $slug ] ) ) {
					$chunks[] = $labels[ $taxonomy ][ $slug ];
				}
			}
		}

		return strtolower( implode( ' ', $chunks ) );
	}
}
