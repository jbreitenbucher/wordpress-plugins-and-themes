<?php
/**
 * Title: FAQ Accordion (custom block)
 * Slug: inside-block-patterns/faq-accordion
 * Description: Accordion wrapper with three sections using the custom ibp/accordion blocks.
 * Categories: ibp-content
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">FAQs</h2>
	<!-- /wp:heading -->

	<!-- wp:ibp/accordion -->
		<!-- wp:ibp/accordion-item {"title":"When are add/drop deadlines?"} -->
			<!-- wp:paragraph -->
			<p>See the Academic Deadlines page for current term dates.</p>
			<!-- /wp:paragraph -->
		<!-- /wp:ibp/accordion-item -->

		<!-- wp:ibp/accordion-item {"title":"How do I request employment verification?"} -->
			<!-- wp:paragraph -->
			<p>Submit the verification request using our online form.</p>
			<!-- /wp:paragraph -->
		<!-- /wp:ibp/accordion-item -->

		<!-- wp:ibp/accordion-item {"title":"Where do I find tax forms?"} -->
			<!-- wp:paragraph -->
			<p>Visit the Policies &amp; Forms page on the Finance and Business site.</p>
			<!-- /wp:paragraph -->
		<!-- /wp:ibp/accordion-item -->
	<!-- /wp:ibp/accordion -->
</div>
<!-- /wp:group -->