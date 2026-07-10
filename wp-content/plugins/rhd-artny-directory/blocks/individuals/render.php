<?php
/**
 * Server-side render for the Individuals Directory block.
 *
 * @package RHD_Artny_Directory
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$block_name = isset( $block->name ) ? $block->name : 'rhd/artny-individuals-directory';

echo RHD_Artny_Directory_Render::render( $attributes, $block_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
