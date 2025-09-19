<?php
/**
 * Title: FAQ Accordion (custom block)
 * Slug: wooster-block-patterns/faq-accordion
 * Description: Heading plus Accordion block with three FAQ items.
 * Categories: wbp-content
 * Inserter: true
 * Version: 1.1.1
 */
?>

<!-- wp:group {"className":"wbp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group wbp-container">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">FAQs</h2>
	<!-- /wp:heading -->

	<!-- wp:wbp/accordion -->
	<div class="wp-block-wbp-accordion wbp-accordion">
		<!-- wp:wbp/accordion-item {"title":"When are add/drop deadlines?","uid":"pat1"} -->
		<div class="wp-block-wbp-accordion-item wbp-accordion__item">
			<h3 class="wbp-accordion__heading">
				<button type="button" class="wbp-accordion__button" aria-expanded="false" aria-controls="wbp-acc-panel-pat1" id="wbp-acc-control-pat1">
					<span class="wbp-accordion__icon" aria-hidden="true"></span>
					<span class="wbp-accordion__label">When are add/drop deadlines?</span>
				</button>
			</h3>
			<div id="wbp-acc-panel-pat1" class="wbp-accordion__panel" role="region" aria-labelledby="wbp-acc-control-pat1">
				<!-- wp:paragraph -->
				<p>See the Academic Deadlines page for current term dates.</p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:wbp/accordion-item -->

		<!-- wp:wbp/accordion-item {"title":"How do I request employment verification?","uid":"pat2"} -->
		<div class="wp-block-wbp-accordion-item wbp-accordion__item">
			<h3 class="wbp-accordion__heading">
				<button type="button" class="wbp-accordion__button" aria-expanded="false" aria-controls="wbp-acc-panel-pat2" id="wbp-acc-control-pat2">
					<span class="wbp-accordion__icon" aria-hidden="true"></span>
					<span class="wbp-accordion__label">How do I request employment verification?</span>
				</button>
			</h3>
			<div id="wbp-acc-panel-pat2" class="wbp-accordion__panel" role="region" aria-labelledby="wbp-acc-control-pat2">
				<!-- wp:paragraph -->
				<p>Submit the verification request using our online form.</p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:wbp/accordion-item -->

		<!-- wp:wbp/accordion-item {"title":"Where do I find tax forms?","uid":"pat3"} -->
		<div class="wp-block-wbp-accordion-item wbp-accordion__item">
			<h3 class="wbp-accordion__heading">
				<button type="button" class="wbp-accordion__button" aria-expanded="false" aria-controls="wbp-acc-panel-pat3" id="wbp-acc-control-pat3">
					<span class="wbp-accordion__icon" aria-hidden="true"></span>
					<span class="wbp-accordion__label">Where do I find tax forms?</span>
				</button>
			</h3>
			<div id="wbp-acc-panel-pat3" class="wbp-accordion__panel" role="region" aria-labelledby="wbp-acc-control-pat3">
				<!-- wp:paragraph -->
				<p>Visit the Policies &amp; Forms page on the Finance and Business site.</p>
				<!-- /wp:paragraph -->
			</div>
		</div>
		<!-- /wp:wbp/accordion-item -->
	</div>
	<!-- /wp:wbp/accordion -->
</div>
<!-- /wp:group -->
