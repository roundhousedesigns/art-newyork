<?php
/**
 * RHD ART/New York functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage rhd-art-new-york
 * @since RHD ART/New York 1.0
 */

// Adds theme support for post formats.
if ( !function_exists( 'rhd_art_new_york_post_format_setup' ) ) {
	/**
	 * Adds theme support for post formats.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return void
	 */
	function rhd_art_new_york_post_format_setup() {
		add_theme_support( 'post-formats', ['aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video'] );
	}
}
add_action( 'after_setup_theme', 'rhd_art_new_york_post_format_setup' );

// Enqueues editor-style.css and the main theme stylesheet in the editors.
if ( !function_exists( 'rhd_art_new_york_editor_style' ) ) {
	/**
	 * Enqueues editor-style.css and the main theme stylesheet in the editors.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return void
	 */
	function rhd_art_new_york_editor_style() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		add_editor_style( [
			'style' . $suffix . '.css',
			'assets/css/editor-style.css',
		] );
	}
}
add_action( 'after_setup_theme', 'rhd_art_new_york_editor_style' );

// Enqueues the theme stylesheet on the front.
if ( !function_exists( 'rhd_art_new_york_enqueue_styles' ) ) {
	/**
	 * Enqueues the theme stylesheet on the front.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return void
	 */
	function rhd_art_new_york_enqueue_styles() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$src    = 'style' . $suffix . '.css';

		wp_enqueue_style(
			'rhd-art-new-york-style',
			get_parent_theme_file_uri( $src ),
			[],
			wp_get_theme()->get( 'Version' )
		);
		wp_style_add_data(
			'rhd-art-new-york-style',
			'path',
			get_parent_theme_file_path( $src )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'rhd_art_new_york_enqueue_styles' );

// Enqueues the theme frontend script.
if ( !function_exists( 'rhd_art_new_york_enqueue_scripts' ) ) {
	/**
	 * Enqueues the theme frontend script.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return void
	 */
	function rhd_art_new_york_enqueue_scripts() {
		wp_enqueue_script(
			'rhd-art-new-york-frontend',
			get_parent_theme_file_uri( 'assets/js/frontend.js' ),
			[],
			wp_get_theme()->get( 'Version' ),
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);
	}
}
add_action( 'wp_enqueue_scripts', 'rhd_art_new_york_enqueue_scripts' );

// Registers custom block styles.
if ( !function_exists( 'rhd_art_new_york_block_styles' ) ) {
	/**
	 * Registers custom block styles.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return void
	 */
	function rhd_art_new_york_block_styles() {
		register_block_style(
			'core/list',
			[
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'rhd-art-new-york' ),
				'inline_style' => '
									ul.is-style-checkmark-list {
										list-style-type: "\2713";
									}

									ul.is-style-checkmark-list li {
										padding-inline-start: 1ch;
									}',
			]
		);
	}
}
add_action( 'init', 'rhd_art_new_york_block_styles' );

// Registers pattern categories.
if ( !function_exists( 'rhd_art_new_york_pattern_categories' ) ) {
	/**
	 * Registers pattern categories.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return void
	 */
	function rhd_art_new_york_pattern_categories() {
		// TODO delete these
		register_block_pattern_category(
			'rhd_art_new_york_page',
			[
				'label'       => __( 'Pages', 'rhd-art-new-york' ),
				'description' => __( 'A collection of full page layouts.', 'rhd-art-new-york' ),
			]
		);

		register_block_pattern_category(
			'rhd_art_new_york_post-format',
			[
				'label'       => __( 'Post formats', 'rhd-art-new-york' ),
				'description' => __( 'A collection of post format patterns.', 'rhd-art-new-york' ),
			]
		);

		register_block_pattern_category(
			'rhd_art_new_york_block',
			[
				'label'       => __( 'A.R.T./New York Blocks', 'rhd-art-new-york' ),
				'description' => __( 'Custom block patterns built for the A.R.T./New York theme.', 'rhd-art-new-york' ),
			]
		);
	}
}
add_action( 'init', 'rhd_art_new_york_pattern_categories' );

// Registers block binding sources.
if ( !function_exists( 'rhd_art_new_york_register_block_bindings' ) ) {
	/**
	 * Registers the post format block binding source.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return void
	 */
	function rhd_art_new_york_register_block_bindings() {
		register_block_bindings_source(
			'rhd-art-new-york/format',
			[
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'rhd-art-new-york' ),
				'get_value_callback' => 'rhd_art_new_york_format_binding',
			]
		);
	}
}
add_action( 'init', 'rhd_art_new_york_register_block_bindings' );

// Registers block binding callback function for the post format name.
if ( !function_exists( 'rhd_art_new_york_format_binding' ) ) {
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function rhd_art_new_york_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
}

if ( !function_exists( 'rhd_art_new_york_post_type_support' ) ) {
	/**
	 * Adds theme support for the post types.
	 *
	 * @since RHD ART/New York 1.0
	 *
	 * @return void
	 */
	function rhd_art_new_york_post_type_support() {
		// Add excerpt support to the page post type.
		add_post_type_support( 'page', 'excerpt' );
	}
}
add_action( 'init', 'rhd_art_new_york_post_type_support' );

if ( !function_exists( 'rhd_art_new_york_get_the_excerpt' ) ) {
/**
 * Filters the excerpt to return an empty string if the post has no excerpt.
 *
 * @since RHD ART/New York 1.0
 *
 * @param string $excerpt The excerpt.
 * @return string The excerpt.
 */
	function rhd_art_new_york_get_the_excerpt( $excerpt ) {
		if ( !has_excerpt() ) {
			return '';
		}
		return $excerpt;
	}
}
add_filter( 'get_the_excerpt', 'rhd_art_new_york_get_the_excerpt', 10, 1 );
