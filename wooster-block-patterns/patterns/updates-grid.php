<?php
defined( 'ABSPATH' ) || exit;
/**
 * Title: Updates grid: query loop (3 columns)
 * Slug: wooster-block-patterns/updates-grid
 * Description: Three-column updates grid using core Query Loop.
 * Categories: wbp-news
 * Block Types: core/group
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"className":"wbp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group wbp-container">
	<!-- wp:heading -->
	<h2 class="wp-block-heading">Latest updates</h2>
	<!-- /wp:heading -->

	<!-- wp:query {"queryId":2,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"}} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->

			<!-- wp:group {"className":"wbp-muted"} -->
			<div class="wp-block-group wbp-muted">
				<!-- wp:post-featured-image {"isLink":true,"sizeSlug":"large"} /-->

				<!-- wp:post-title {"level":4,"isLink":true} /-->

				<!-- wp:post-date /-->

				<!-- wp:post-excerpt {"moreText":"Read more"} /-->
			</div>
			<!-- /wp:group -->

		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
		<!-- wp:paragraph -->
		<p>No posts found.</p>
		<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->