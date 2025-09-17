<?php
/**
 * Title: Department home: hero + search + tasks + news
 * Slug: inside-block-patterns/department-home-hero-search-tasks
 * Description: Hero with value prop, site search, task cards, and latest news.
 * Categories: ibp-department
 * Inserter: true
 * Version: 1.0.3
 */
?>

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container">

	<!-- wp:group {"className":"ibp-muted","style":{"spacing":{"padding":{"top":"6vh","bottom":"6vh"}}}} -->
	<div class="wp-block-group ibp-muted" style="padding-top:6vh;padding-bottom:6vh">
		<!-- wp:heading {"level":1} -->
		<h1 class="wp-block-heading">Department or Office Name</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"large"} -->
		<p class="has-large-font-size">Short value proposition. Keep it under two lines.</p>
		<!-- /wp:paragraph -->

		<!-- wp:search {"label":"Search","showLabel":false,"placeholder":"Search this site","buttonUseIcon":true} /-->

		<!-- wp:buttons -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-fill"} -->
			<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="#">Contact us</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Directory</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--50)">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Quick tasks</h2>
		<!-- /wp:heading -->

		<!-- wp:columns {"className":"ibp-quicklinks"} -->
		<div class="wp-block-columns ibp-quicklinks">
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ibp-muted"} -->
				<div class="wp-block-group ibp-muted">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading">Forms</h3>
					<!-- /wp:heading -->

					<!-- wp:list -->
					<ul class="wp-block-list">
						<!-- wp:list-item --><li><a href="#">Travel request</a></li><!-- /wp:list-item -->
						<!-- wp:list-item --><li><a href="#">Reimbursement</a></li><!-- /wp:list-item -->
						<!-- wp:list-item --><li><a href="#">IT help</a></li><!-- /wp:list-item -->
					</ul>
					<!-- /wp:list -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ibp-muted"} -->
				<div class="wp-block-group ibp-muted">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading">Deadlines</h3>
					<!-- /wp:heading -->

					<!-- wp:list -->
					<ul class="wp-block-list">
						<!-- wp:list-item --><li>Add/Drop: Sept 6</li><!-- /wp:list-item -->
						<!-- wp:list-item --><li>Graduation App: Oct 15</li><!-- /wp:list-item -->
						<!-- wp:list-item --><li>IS Monday: Apr 14</li><!-- /wp:list-item -->
					</ul>
					<!-- /wp:list -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ibp-muted"} -->
				<div class="wp-block-group ibp-muted">
					<!-- wp:heading {"level":3} -->
					<h3 class="wp-block-heading">Services</h3>
					<!-- /wp:heading -->

					<!-- wp:buttons -->
					<div class="wp-block-buttons">
						<!-- wp:button -->
						<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Request advising</a></div>
						<!-- /wp:button -->
						<!-- wp:button {"className":"is-style-outline"} -->
						<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Book appointment</a></div>
						<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--60)">
		<!-- wp:column {"width":"66.66%"} -->
		<div class="wp-block-column" style="flex-basis:66.66%">
			<!-- wp:heading -->
			<h2 class="wp-block-heading">Latest news</h2>
			<!-- /wp:heading -->

			<!-- wp:query {"queryId":9,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"}} -->
			<div class="wp-block-query">
				<!-- wp:post-template {"layout":{"type":"default"}} -->
					<!-- wp:group {"className":"ibp-muted"} -->
					<div class="wp-block-group ibp-muted">
						<!-- wp:post-featured-image {"isLink":true} /-->
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
		<!-- /wp:column -->

		<!-- wp:column {"width":"33.33%"} -->
		<div class="wp-block-column" style="flex-basis:33.33%">
			<!-- wp:group {"className":"ibp-muted"} -->
			<div class="wp-block-group ibp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Contact</h3>
				<!-- /wp:heading -->
				<!-- wp:paragraph -->
				<p>Address line<br>City, State ZIP</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph -->
				<p><strong>Hours:</strong> 9:00 a.m. – 4:30 p.m.<br><strong>Phone:</strong> 330-000-0000<br><strong>Email:</strong> dept@wooster.edu</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->