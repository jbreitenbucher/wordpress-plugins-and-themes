<?php
defined( 'ABSPATH' ) || exit;
/**
 * Title: Section landing: left nav + content + toc
 * Slug: wooster-block-patterns/section-landing-leftnav
 * Description: Two-column layout with a left navigation (TOC) and content area ending with an FAQ accordion.
 * Categories: wbp-content
 * Block Types: core/group
 * Inserter: true
 * Version: 1.1.0
 */
?>

<!-- wp:group {"className":"wbp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group wbp-container">
	<!-- wp:columns {"className":"wbp-aside"} -->
	<div class="wp-block-columns wbp-aside">
		<!-- wp:column {"width":"280px"} -->
		<div class="wp-block-column" style="flex-basis:280px">
			<!-- wp:wbp/toc {"title":"On this page","collapsed":false,"maxDepth":4,"includeH3":true,"includeH4":false,"includeH5":false,"includeH6":false} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading -->
			<h2 class="wp-block-heading" id="overview">Page title</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p>Short intro for this section landing page explaining who this is for and how to use it.</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading" id="process">Process</h3>
			<!-- /wp:heading -->

			<!-- wp:list -->
			<ul class="wp-block-list">
				<li>Step one</li>
				<li>Step two</li>
				<li>Step three</li>
			</ul>
			<!-- /wp:list -->

			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading" id="forms">Forms</h3>
			<!-- /wp:heading -->

			<!-- wp:list -->
			<ul class="wp-block-list">
				<li><a href="#">Form A</a></li>
				<li><a href="#">Form B</a></li>
			</ul>
			<!-- /wp:list -->

			<!-- wp:pattern {"slug":"wooster-block-patterns/faq-accordion"} /-->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->