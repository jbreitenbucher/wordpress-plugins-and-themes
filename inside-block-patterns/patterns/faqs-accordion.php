<?php
/**
 * Title: FAQ Accordion (custom block)
 * Slug: inside-block-patterns/faq-accordion
 * Description: Heading plus Accordion block with three FAQ items.
 * Categories: ibp-content
 * Inserter: true
 * Version: 1.1.1
 */
?>

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">FAQs</h2>
	<!-- /wp:heading -->

	<!-- wp:ibp/accordion -->
	<div class="wp-block-ibp-accordion ibp-accordion">
		<!-- wp:ibp/accordion-item {"title":"When are add/drop deadlines?","uid":"pat1"} -->
		<div class="wp-block-ibp-accordion-item ibp-accordion__item">
			<h3 class="ibp-accordion__heading">
				<button type="button" class="ibp-accordion__button" aria-expanded="false" aria-controls="ibp-acc-panel-pat1" id="ibp-acc-control-pat1">
					<span class="ibp-accordion__icon" aria-hidden="true"></span>
					<span class="ibp-accordion__label">When are add/drop deadlines?</span>
				</button>
			</h3>
			<div id="ibp-acc-panel-pat1" class="ibp-accordion__panel" role="region" aria-labelledby="ibp-acc-control-pat1">
				<!-- wp:paragraph -->
				<p>See the Academic Deadlines page for current term dates.</p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:ibp/accordion-item -->

		<!-- wp:ibp/accordion-item {"title":"How do I request employment verification?","uid":"pat2"} -->
		<div class="wp-block-ibp-accordion-item ibp-accordion__item">
			<h3 class="ibp-accordion__heading">
				<button type="button" class="ibp-accordion__button" aria-expanded="false" aria-controls="ibp-acc-panel-pat2" id="ibp-acc-control-pat2">
					<span class="ibp-accordion__icon" aria-hidden="true"></span>
					<span class="ibp-accordion__label">How do I request employment verification?</span>
				</button>
			</h3>
			<div id="ibp-acc-panel-pat2" class="ibp-accordion__panel" role="region" aria-labelledby="ibp-acc-control-pat2">
				<!-- wp:paragraph -->
				<p>Submit the verification request using our online form.</p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:ibp/accordion-item -->

		<!-- wp:ibp/accordion-item {"title":"Where do I find tax forms?","uid":"pat3"} -->
		<div class="wp-block-ibp-accordion-item ibp-accordion__item">
			<h3 class="ibp-accordion__heading">
				<button type="button" class="ibp-accordion__button" aria-expanded="false" aria-controls="ibp-acc-panel-pat3" id="ibp-acc-control-pat3">
					<span class="ibp-accordion__icon" aria-hidden="true"></span>
					<span class="ibp-accordion__label">Where do I find tax forms?</span>
				</button>
			</h3>
			<div id="ibp-acc-panel-pat3" class="ibp-accordion__panel" role="region" aria-labelledby="ibp-acc-control-pat3">
				<!-- wp:paragraph -->
				<p>Visit the Policies &amp; Forms page on the Finance and Business site.</p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:ibp/accordion-item -->
	</div>
	<!-- /wp:ibp/accordion -->
</div>
<!-- /wp:group -->
