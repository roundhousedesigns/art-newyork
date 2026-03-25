<?php
/**
 * Title: Contact, info and locations
 * Slug: rhd-art-new-york/contact-info-locations
 * Keywords: contact, location
 * Categories: contact
 * Viewport width: 1400
 * Description: Contact section with social media links, email, and multiple location details.
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since RHD ART/New York 1.0
 */

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"textAlign":"left","align":"full","fontSize":"xx-large"} -->
		<h2 class="wp-block-heading alignfull has-text-align-left has-xx-large-font-size"><?php esc_html_e( 'How to get in touch with us', 'rhd-art-new-york' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|50","margin":{"top":"var:preset|spacing|50"}},"border":{"top":{"color":"var:preset|color|accent-4","width":"1px"}}},"layout":{"type":"grid","minimumColumnWidth":"23rem"}} -->
		<div class="wp-block-group alignwide" style="border-top-color:var(--wp--preset--color--accent-4);border-top-width:1px;margin-top:var(--wp--preset--spacing--50);padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
			<!-- wp:group {"style":{"layout":{"rowSpan":1,"columnSpan":2}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
				<h3 class="wp-block-heading has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Social media', 'rhd-art-new-york' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"medium","layout":{"type":"flex","orientation":"vertical"},"ariaLabel":"<?php esc_attr_e( 'Social media', 'rhd-art-new-york' ); ?>"} -->
					<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'X', 'Refers to the social media platform formerly known as Twitter.', 'rhd-art-new-york' ); ?>","url":"#"} /-->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Instagram', 'rhd-art-new-york' ); ?>","url":"#"} /-->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'Facebook', 'rhd-art-new-york' ); ?>","url":"#"} /-->
					<!-- wp:navigation-link {"label":"<?php esc_html_e( 'TikTok', 'rhd-art-new-york' ); ?>","url":"#"} /-->
				<!-- /wp:navigation -->
				<!-- wp:heading {"level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
				<h3 class="wp-block-heading has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Email', 'rhd-art-new-york' ); ?></h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph {"fontSize":"medium"} -->
				<p class="has-medium-font-size"><?php esc_html_e( 'example@example.com', 'rhd-art-new-york' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"layout":{"type":"grid","minimumColumnWidth":null,"columnCount":2}} -->
			<div class="wp-block-group">
				<!-- wp:group {"style":{"layout":{"columnSpan":1,"rowSpan":1}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'New York', 'rhd-art-new-york' ); ?></h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size"><?php esc_html_e( '123 Example St. Manhattan, NY 10300 United States', 'rhd-art-new-york' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"layout":{"columnSpan":1,"rowSpan":1}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'San Diego', 'rhd-art-new-york' ); ?></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size"><?php esc_html_e( '123 Example St. Manhattan, NY 10300 United States', 'rhd-art-new-york' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"layout":{"columnSpan":1,"rowSpan":1}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Salt Lake City', 'rhd-art-new-york' ); ?></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size"><?php esc_html_e( '123 Example St. Manhattan, NY 10300 United States', 'rhd-art-new-york' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"layout":{"columnSpan":1,"rowSpan":1}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"style":{"typography":{"fontStyle":"normal","fontWeight":"700"}},"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size" style="font-style:normal;font-weight:700"><?php esc_html_e( 'Portland', 'rhd-art-new-york' ); ?></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size"><?php esc_html_e( '123 Example St. Manhattan, NY 10300 United States', 'rhd-art-new-york' ); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
