<?php
/**
 * Server-side render for the Contacts Directory block.
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

echo RHD_Artny_Directory_Render::render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
