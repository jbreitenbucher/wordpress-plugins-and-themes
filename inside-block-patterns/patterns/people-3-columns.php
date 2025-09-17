<?php
/**
 * Title: People: three columns
 * Slug: inside-block-patterns/people-3-columns
 * Description: Three equal columns with a person card in each.
 * Categories: ibp-people
 * Block Types: core/group
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container"><!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column">
<!-- wp:pattern {"slug":"inside-block-patterns/person-card"} /-->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:pattern {"slug":"inside-block-patterns/person-card"} /-->
</div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:pattern {"slug":"inside-block-patterns/person-card"} /-->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
