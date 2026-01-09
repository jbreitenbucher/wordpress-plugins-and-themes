<?php
defined( 'ABSPATH' ) || exit;
/**
 * Title: Person card
 * Slug: wooster-block-patterns/person-card
 * Description: Single person card with image, name, title, email, phone, and optional profile link.
 * Categories: wbp-people
 * Block Types: core/group
 * Inserter: true
 * Version: 1.0.0
 */
?>
<!-- wp:group {"className":"wbp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group wbp-container">
  <!-- wp:group {"className":"wbp-muted","layout":{"type":"constrained"}} -->
  <div class="wp-block-group wbp-muted">

    <!-- wp:image {"sizeSlug":"full","linkDestination":"none","width":160,"align":"center"} -->
    <figure class="wp-block-image aligncenter size-full is-resized">
      <img src="<?php echo esc_url( WBP_ASSETS_URL . "images/avatar-placeholder.png" ); ?>" alt="Headshot placeholder" width="160" />
    </figure>
    <!-- /wp:image -->

    <!-- wp:heading -->
    <h2 class="wp-block-heading">Person Name</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Title<br><a href="mailto:person@wooster.edu">person@wooster.edu</a><br>330-000-0000</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">
      <!-- wp:button {"className":"is-style-outline"} -->
      <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Profile</a></div>
      <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->

  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->