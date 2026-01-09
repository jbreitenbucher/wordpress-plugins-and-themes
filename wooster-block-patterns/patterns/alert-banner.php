<?php
defined( 'ABSPATH' ) || exit;
/**
 * Title: Alert banner (info)
 * Slug: wooster-block-patterns/alert-banner
 * Description: Simple alert banner with accessible text. Duplicate and switch modifier class to warn or danger as needed.
 * Categories: wbp-messaging
 * Block Types: core/group
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"className":"wbp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group wbp-container"><!-- wp:group {"className":"wbp-alert wbp-alert--info"} -->
<div class="wp-block-group wbp-alert wbp-alert--info"><!-- wp:paragraph -->
<p><strong>Service update:</strong> Example notice for today. Link to <a href="#">details</a>.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
