<?php
/**
 * Title: Photo blog home
 * Slug: rhd-art-new-york/template-home-photo-blog
 * Template Types: front-page, index, home
 * Viewport width: 1400
 * Inserter: no
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since RHD ART/New York 1.0
 */

?>
<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60)">
	<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":1,"className":"is-style-text-annotation"} -->
		<h1 class="wp-block-heading has-text-align-center is-style-text-annotation"><?php esc_html_e( 'Stories', 'rhd-art-new-york' ); ?></h1>
		<!-- /wp:heading -->
	</div>
	<!-- /wp:group -->
	<!-- wp:heading {"textAlign":"center","align":"wide","fontSize":"xx-large"} -->
	<h2 class="wp-block-heading alignwide has-text-align-center has-xx-large-font-size"><?php esc_html_e( 'Tell your story', 'rhd-art-new-york' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:pattern {"slug":"rhd-art-new-york/template-query-loop-photo-blog"} /-->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->
