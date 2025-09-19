<?php
/**
 * Title: Department home: service tiles + announcements + hours
 * Slug: wooster-block-patterns/department-home-services-announcements
 * Description: Four service tiles, announcements, and contact/hours.
 * Categories: wbp-department
 * Inserter: true
 * Version: 1.0.3
 */
?>

<!-- wp:group {"className":"wbp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group wbp-container">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">Department or Office Name</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"large"} -->
	<p class="has-large-font-size">How we help students, staff, and faculty. Start with the tiles below.</p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--40)">
		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"wbp-muted"} -->
			<div class="wp-block-group wbp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Advising</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>Academic planning and progress checks.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Request advising</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"wbp-muted"} -->
			<div class="wp-block-group wbp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Records</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>Transcripts, verification, and diplomas.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Order transcript</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"wbp-muted"} -->
			<div class="wp-block-group wbp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Policies</h3>
				<!-- /wp:heading -->

				<!-- wp:list -->
				<ul class="wp-block-list">
					<!-- wp:list-item -->
					<li><a href="#">Catalog</a></li>
					<!-- /wp:list-item -->
					<!-- wp:list-item -->
					<li><a href="#">FERPA</a></li>
					<!-- /wp:list-item -->
					<!-- wp:list-item -->
					<li><a href="#">Graduation</a></li>
					<!-- /wp:list-item -->
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"wbp-muted"} -->
			<div class="wp-block-group wbp-muted">
				<!-- wp:heading {"level":3} -->
				<h3 class="wp-block-heading">Support</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p>Find the right contact for your question.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Contact us</a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
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
				<h2 class="wp-block-heading">Announcements</h2>
				<!-- /wp:heading -->

				<!-- wp:list -->
				<ul class="wp-block-list">
					<!-- wp:list-item --><li>Office closed Friday for staff training.</li><!-- /wp:list-item -->
					<!-- wp:list-item --><li>Fall registration opens Oct 1.</li><!-- /wp:list-item -->
					<!-- wp:list-item --><li>New advising drop-in hours posted.</li><!-- /wp:list-item -->
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
				<h3 class="wp-block-heading">Hours &amp; Contact</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph -->
				<p><strong>Hours:</strong> Mon–Fri, 9–4:30<br><strong>Phone:</strong> 330-000-0000<br><strong>Email:</strong> dept@wooster.edu</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->