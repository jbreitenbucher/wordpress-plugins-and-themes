<?php
defined( 'ABSPATH' ) || exit;
/**
 * Title: Department home: split hero + highlights + dates
 * Slug: wooster-block-patterns/department-home-split-hero-highlights
 * Description: Two-column hero with image, highlights list, and upcoming dates.
 * Categories: wbp-department
 * Inserter: true
 * Version: 1.0.3
 */
?>

<!-- wp:group {"className":"wbp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group wbp-container">

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"60%"} -->
		<div class="wp-block-column" style="flex-basis:60%">
			<!-- wp:heading {"level":1} -->
			<h1 class="wp-block-heading">Department or Office Name</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"large"} -->
			<p class="has-large-font-size">Clear promise and what to do next. Keep it short.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Get help</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Policies</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"40%"} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
			<figure class="wp-block-image size-large"><img alt="Department illustration or photo" src="<?php echo esc_url( WBP_ASSETS_URL . "images/hero-placeholder-1.jpg" ); ?>"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--50)">
		<!-- wp:column {"width":"66.66%"} -->
		<div class="wp-block-column" style="flex-basis:66.66%">
			<!-- wp:group {"className":"wbp-muted"} -->
			<div class="wp-block-group wbp-muted">
				<!-- wp:heading -->
				<h2 class="wp-block-heading">Highlights</h2>
				<!-- /wp:heading -->
				<!-- wp:list -->
				<ul class="wp-block-list">
					<!-- wp:list-item --><li>New advising appointments available this week.</li><!-- /wp:list-item -->
					<!-- wp:list-item --><li>Policy updates for Fall term published.</li><!-- /wp:list-item -->
					<!-- wp:list-item --><li>Student employment forms updated.</li><!-- /wp:list-item -->
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"33.33%"} -->
		<div class="wp-block-column" style="flex-basis:33.33%">
			<!-- wp:group {"className":"wbp-muted"} -->
			<div class="wp-block-group wbp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Upcoming dates</h3>
				<!-- /wp:heading -->
				<!-- wp:list -->
				<ul class="wp-block-list">
					<!-- wp:list-item --><li>Drop/Add deadline — Sept 6</li><!-- /wp:list-item -->
					<!-- wp:list-item --><li>Graduation application — Oct 15</li><!-- /wp:list-item -->
					<!-- wp:list-item --><li>IS Monday — Apr 14</li><!-- /wp:list-item -->
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->