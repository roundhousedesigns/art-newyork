<?php
/**
 * Markup for directory blocks.
 *
 * @package RHD_Artny_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders filter bar and directory cards.
 */
final class RHD_Artny_Directory_Render {

	/**
	 * Entries shown per results page.
	 */
	const PER_PAGE = 20;

	/**
	 * Render full directory markup.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $block_name Registered block name.
	 * @return string
	 */
	public static function render( $attributes = array(), $block_name = '' ) {
		$type   = RHD_Artny_Directory_Config::resolve_type( $attributes, $block_name );
		$config = RHD_Artny_Directory_Config::get( $type );

		if ( null === $config ) {
			return '';
		}

		$align       = isset( $attributes['align'] ) ? sanitize_key( $attributes['align'] ) : '';
		$align_class = $align ? ' align' . $align : '';

		$labels   = RHD_Artny_Directory_Data::get_taxonomy_labels( $type );
		$contacts = RHD_Artny_Directory_Data::get_contacts( $type );
		$status   = RHD_Artny_Directory_Data::get_sync_status( $type );

		$filter_config = self::build_filter_config( $config );
		$root_id       = 'rhd-artny-directory-' . $type;

		ob_start();
		?>
		<div
			class="rhd-artny-directory wp-block-group<?php echo esc_attr( $align_class ); ?>"
			id="<?php echo esc_attr( $root_id ); ?>"
			data-rhd-artny-directory
			data-rhd-artny-directory-type="<?php echo esc_attr( $type ); ?>"
			data-rhd-artny-directory-per-page="<?php echo esc_attr( (string) self::PER_PAGE ); ?>"
			data-rhd-artny-directory-filters="<?php echo esc_attr( wp_json_encode( $filter_config ) ); ?>"
			data-rhd-artny-directory-entry-singular="<?php echo esc_attr( $config['entry_label_singular'] ); ?>"
			data-rhd-artny-directory-entry-plural="<?php echo esc_attr( $config['entry_label_plural'] ); ?>"
		>
			<?php self::render_filters( $config, $labels, $type, $root_id ); ?>
			<?php self::render_status( $root_id ); ?>
			<?php self::render_results( $contacts, $labels, $config, $root_id ); ?>
			<?php self::render_pagination( $root_id ); ?>
			<?php self::render_data_notice( $status ); ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Client-side filter metadata for view.js.
	 *
	 * @param array<string, mixed> $config Directory config.
	 * @return array<int, array<string, string>>
	 */
	private static function build_filter_config( $config ) {
		$filters = array();

		foreach ( $config['taxonomy_fields'] as $taxonomy => $taxonomy_config ) {
			$filters[] = array(
				'filter'  => $taxonomy_config['filter'],
				'attr'    => str_replace( 'data-', '', $taxonomy_config['data_attr'] ),
				'mode'    => ! empty( $taxonomy_config['multi'] ) ? 'multi' : 'single',
			);
		}

		return $filters;
	}

	/**
	 * Filter bar: search + taxonomy dropdown groups.
	 *
	 * @param array<string, mixed>               $config Directory config.
	 * @param array<string, array<string, string>> $labels Taxonomy labels.
	 * @param string                             $type   Directory type.
	 * @param string                             $root_id Root element ID prefix.
	 */
	private static function render_filters( $config, $labels, $type, $root_id ) {
		?>
		<section class="rhd-artny-directory__filters" aria-labelledby="<?php echo esc_attr( $root_id . '-filter-heading' ); ?>">
			<div class="rhd-artny-directory__filters-header wp-block-group alignwide">
				<h2 id="<?php echo esc_attr( $root_id . '-filter-heading' ); ?>" class="wp-block-heading has-x-large-font-size">
					<?php echo esc_html( $config['filter_heading'] ); ?>
				</h2>
				<p class="is-style-text-annotation" id="<?php echo esc_attr( $root_id . '-filter-description' ); ?>">
					<?php echo esc_html( $config['filter_hint'] ); ?>
				</p>
			</div>

			<form
				class="rhd-artny-directory__filters-form alignwide"
				role="search"
				aria-label="<?php esc_attr_e( 'Directory search and filters', 'rhd-artny-directory' ); ?>"
				aria-describedby="<?php echo esc_attr( $root_id . '-filter-description' ); ?>"
				data-rhd-artny-directory-form
			>
				<div class="rhd-artny-directory__search">
					<label class="rhd-artny-directory__search-label" for="<?php echo esc_attr( $root_id . '-search' ); ?>">
						<span class="rhd-artny-directory__search-label-text"><?php esc_html_e( 'Search', 'rhd-artny-directory' ); ?></span>
						<input
							type="search"
							id="<?php echo esc_attr( $root_id . '-search' ); ?>"
							name="q"
							class="rhd-artny-directory__search-input"
							placeholder="<?php echo esc_attr( $config['search_placeholder'] ); ?>"
							autocomplete="off"
							data-rhd-artny-directory-search
						/>
					</label>
				</div>

				<div class="rhd-artny-directory__filter-groups">
					<?php foreach ( $config['taxonomy_fields'] as $taxonomy => $group ) : ?>
						<?php
						$slugs = RHD_Artny_Directory_Data::get_used_taxonomy_slugs( $taxonomy, $type );
						if ( empty( $slugs ) ) {
							continue;
						}

						$group_id      = $root_id . '-' . $group['filter'];
						$label_id      = $group_id . '-label';
						$summary_id    = $group_id . '-summary';
						$trigger_id    = $group_id . '-trigger';
						$panel_id      = $group_id . '-panel';
						$default_label = sprintf(
							/* translators: %s: filter group name, e.g. Artistic Focus */
							__( 'All %s', 'rhd-artny-directory' ),
							$group['label']
						);
						$is_multi      = ! empty( $group['multi'] );
						$input_type    = $is_multi ? 'checkbox' : 'radio';
						$input_name    = $is_multi ? $group['filter'] . '[]' : $group['filter'];
						?>
						<div class="rhd-artny-directory__filter-dropdown" data-rhd-artny-directory-filter-dropdown data-rhd-artny-directory-filter-mode="<?php echo esc_attr( $is_multi ? 'multi' : 'single' ); ?>">
							<span class="rhd-artny-directory__filter-dropdown-label" id="<?php echo esc_attr( $label_id ); ?>">
								<?php echo esc_html( $group['label'] ); ?>
							</span>
							<div class="rhd-artny-directory__filter-dropdown-control">
								<button
									type="button"
									class="rhd-artny-directory__filter-dropdown-trigger"
									id="<?php echo esc_attr( $trigger_id ); ?>"
									aria-labelledby="<?php echo esc_attr( $label_id . ' ' . $summary_id ); ?>"
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
													type="<?php echo esc_attr( $input_type ); ?>"
													class="rhd-artny-directory__checkbox"
													id="<?php echo esc_attr( $input_id ); ?>"
													name="<?php echo esc_attr( $input_name ); ?>"
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
	 *
	 * @param string $root_id Root element ID prefix.
	 */
	private static function render_status( $root_id ) {
		?>
		<p class="rhd-artny-directory__status alignwide" role="status" aria-live="polite" data-rhd-artny-directory-status></p>
		<?php
	}

	/**
	 * Directory cards grid.
	 *
	 * @param array<int, array<string, mixed>>     $contacts Contact records.
	 * @param array<string, array<string, string>> $labels   Taxonomy labels.
	 * @param array<string, mixed>                 $config   Directory config.
	 * @param string                               $root_id  Root element ID prefix.
	 */
	private static function render_results( $contacts, $labels, $config, $root_id ) {
		$column_style = 'border-width:1px;border-radius:10px;padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40);';
		?>
		<div class="rhd-artny-directory__results-viewport alignwide" data-rhd-artny-directory-results-viewport>
			<div class="rhd-artny-directory__results" data-rhd-artny-directory-results>
				<?php foreach ( $contacts as $contact ) : ?>
					<?php echo self::render_card( $contact, $labels, $config, $column_style ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render_card. ?>
				<?php endforeach; ?>
			</div>
		</div>
		<p class="rhd-artny-directory__empty alignwide" hidden aria-hidden="true" data-rhd-artny-directory-empty>
			<?php echo esc_html( $config['empty_message'] ); ?>
		</p>
		<?php
	}

	/**
	 * Pagination controls (populated by view.js).
	 *
	 * @param string $root_id Root element ID prefix.
	 */
	private static function render_pagination( $root_id ) {
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
	 * Single directory card (matches theme column card pattern).
	 *
	 * @param array<string, mixed>               $contact      Contact record.
	 * @param array<string, array<string, string>> $labels       Taxonomy labels.
	 * @param array<string, mixed>                 $config       Directory config.
	 * @param string                               $column_style Inline column styles.
	 * @return string
	 */
	private static function render_card( $contact, $labels, $config, $column_style ) {
		$search_blob = self::build_search_blob( $contact, $labels, $config );
		$data_attrs  = array(
			'data-rhd-artny-directory-card' => '',
			'data-name'                     => $contact['Name'],
			'data-search'                   => $search_blob,
		);

		foreach ( $config['taxonomy_fields'] as $taxonomy => $taxonomy_config ) {
			$value = $contact[ $taxonomy ] ?? ( ! empty( $taxonomy_config['multi'] ) ? array() : '' );

			if ( ! empty( $taxonomy_config['multi'] ) ) {
				$data_attrs[ $taxonomy_config['data_attr'] ] = is_array( $value ) ? implode( ',', $value ) : '';
			} else {
				$data_attrs[ $taxonomy_config['data_attr'] ] = is_string( $value ) ? $value : '';
			}
		}

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
			$social_links = self::build_social_links( $contact, $config );
			if ( ! empty( $social_links ) ) {
				echo self::render_social_links( $social_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- do_blocks output.
			}
			?>

			<?php foreach ( $config['taxonomy_fields'] as $taxonomy => $taxonomy_config ) : ?>
				<?php
				$values = $contact[ $taxonomy ] ?? ( ! empty( $taxonomy_config['multi'] ) ? array() : '' );
				$slugs  = array();

				if ( ! empty( $taxonomy_config['multi'] ) ) {
					$slugs = is_array( $values ) ? $values : array();
				} elseif ( is_string( $values ) && '' !== $values ) {
					$slugs = array( $values );
				}

				if ( empty( $slugs ) ) {
					continue;
				}
				?>
				<p class="has-custom-1-font-size">
					<?php echo esc_html( $taxonomy_config['card_label'] ); ?>
					<?php echo self::render_taxonomy_links( $slugs, $labels[ $taxonomy ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Build social-link block input from stored contact fields.
	 *
	 * @param array<string, mixed> $contact Contact record.
	 * @param array<string, mixed> $config  Directory config.
	 * @return array<int, array<string, string>>
	 */
	private static function build_social_links( $contact, $config ) {
		$social = array();

		foreach ( $config['social_fields'] as $service => $field ) {
			if ( empty( $contact[ $field ] ) ) {
				continue;
			}

			$social[] = array(
				'service' => $service,
				'url'     => $contact[ $field ],
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
	 * @param array<string, mixed>               $config  Directory config.
	 * @return string
	 */
	private static function build_search_blob( $contact, $labels, $config ) {
		$chunks = array(
			$contact['Name'] ?? '',
			$contact['Description'] ?? '',
		);

		foreach ( array_keys( $config['taxonomy_fields'] ) as $taxonomy ) {
			$taxonomy_config = $config['taxonomy_fields'][ $taxonomy ];
			$values          = $contact[ $taxonomy ] ?? ( ! empty( $taxonomy_config['multi'] ) ? array() : '' );
			$slugs           = array();

			if ( ! empty( $taxonomy_config['multi'] ) ) {
				$slugs = is_array( $values ) ? $values : array();
			} elseif ( is_string( $values ) && '' !== $values ) {
				$slugs = array( $values );
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
