<?php
/**
 * Title: Department home: audience gateways + news
 * Slug: inside-block-patterns/department-home-audiences-news
 * Description: Three audience routes (Students, Faculty, Staff) plus latest news.
 * Categories: ibp-department
 * Inserter: true
 * Version: 1.0.3
 */
?>

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">How can we help?</h1>
	<!-- /wp:heading -->

	<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--30)">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"ibp-muted"} -->
			<div class="wp-block-group ibp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Students</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph -->
				<p>Registration, advising, transcripts.</p>
				<!-- /wp:paragraph -->
				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Student hub</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"ibp-muted"} -->
			<div class="wp-block-group ibp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Faculty</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph -->
				<p>Policies, forms, curricular changes.</p>
				<!-- /wp:paragraph -->
				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Faculty hub</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"ibp-muted"} -->
			<div class="wp-block-group ibp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Staff</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph -->
				<p>Processes, deadlines, directory.</p>
				<!-- /wp:paragraph -->
				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Staff hub</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--50)">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Latest news</h2>
		<!-- /wp:heading -->

		<!-- wp:query {"queryId":3,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"}} -->
		<div class="wp-block-query">
			<!-- wp:post-template {"layout":{"type":"default"}} -->
				<!-- wp:group {"className":"ibp-muted"} -->
				<div class="wp-block-group ibp-muted">
					<!-- wp:post-title {"isLink":true} /-->
					<!-- wp:post-date /-->
					<!-- wp:post-excerpt {"moreText":"Read more"} /-->
				</div>
				<!-- /wp:group -->
			<!-- /wp:post-template -->

			<!-- wp:query-no-results -->
			<!-- wp:paragraph --><p>No posts found.</p><!-- /wp:paragraph -->
			<!-- /wp:query-no-results -->
		</div>
		<!-- /wp:query -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->