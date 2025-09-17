<?php
/**
 * Title: Section landing: left nav + content + toc
 * Slug: inside-block-patterns/section-landing-leftnav
 * Description: Two-column layout with a left navigation, table of contents, and content with details/FAQ.
 * Categories: ibp-content
 * Block Types: core/group
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container"><!-- wp:columns {"className":"ibp-aside"} -->
<div class="wp-block-columns ibp-aside"><!-- wp:column {"width":"280px"} -->
<div class="wp-block-column" style="flex-basis:280px"><!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">On this page</h4>
<!-- /wp:heading -->

<!-- wp:list {"className":"cnbp-toc"} -->
<ul class="cnbp-toc"><li><a href="#overview">Overview</a></li><li><a href="#process">Process</a></li><li><a href="#forms">Forms</a></li><li><a href="#faq">FAQ</a></li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading" id="overview">Page title</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Short intro for this section landing page explaining who this is for and how to use it.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading" id="process">Process</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Step one</li><li>Step two</li><li>Step three</li></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading" id="forms">Forms</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li><a href="#">Form A</a></li><li><a href="#">Form B</a></li></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading" id="faq">FAQ</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li><strong>Question one:</strong> Answer.</li><li><strong>Question two:</strong> Answer.</li></ul>
<!-- /wp:list --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
