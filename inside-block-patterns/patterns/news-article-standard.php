<?php
/**
 * Title: News article: standard + related links
 * Slug: inside-block-patterns/news-article-standard
 * Description: Standard post layout with title, featured image, and related links section.
 * Categories: ibp-news
 * Block Types: core/group
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container">

	<!-- wp:post-featured-image /-->

	<!-- wp:paragraph -->
	<p>Optional short deck/summary goes here. Keep it to one or two sentences.</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p>Start your article here. Replace this text with your content.</p>
	<!-- /wp:paragraph -->

	<!-- wp:separator {"className":"is-style-wide"} -->
	<hr class="wp-block-separator is-style-wide"/>
	<!-- /wp:separator -->

	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">Related links</h2>
	<!-- /wp:heading -->

	<!-- wp:list -->
	<ul class="wp-block-list">
		<!-- wp:list-item -->
		<li><a href="#">Policy reference</a></li>
		<!-- /wp:list-item -->

		<!-- wp:list-item -->
		<li><a href="#">Form</a></li>
		<!-- /wp:list-item -->

		<!-- wp:list-item -->
		<li><a href="#">Contact</a></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->

</div>
<!-- /wp:group -->