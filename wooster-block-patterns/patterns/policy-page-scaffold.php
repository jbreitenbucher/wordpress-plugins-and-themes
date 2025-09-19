<?php
/**
 * Title: Policy page scaffold
 * Slug: wooster-block-patterns/policy-page-scaffold
 * Description: Standardized policy layout with meta, headings, and related references.
 * Categories: wbp-content
 * Block Types: core/group
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"className":"wbp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group wbp-container">

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><strong>Effective date:</strong> YYYY‑MM‑DD · <strong>Owner:</strong> Office name · <strong>Review cycle:</strong> Annually</p>
<!-- /wp:paragraph -->

<!-- wp:separator {"className":"is-style-wide"} -->
<hr class="wp-block-separator is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"70%"} -->
<div class="wp-block-column" style="flex-basis:70%"><!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Purpose</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>State why this policy exists.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Scope</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Who and what this applies to.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Policy</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Requirement one</li><li>Requirement two</li></ul>
<!-- /wp:list -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Procedures</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Step one</li><li>Step two</li></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"30%"} -->
<div class="wp-block-column" style="flex-basis:30%"><!-- wp:group {"className":"cnbp-muted"} -->
<div class="wp-block-group wbp-muted"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Related</h3>
<!-- /wp:heading -->

<!-- wp:list -->
<ul><li>Reference A</li><li>Reference B</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
