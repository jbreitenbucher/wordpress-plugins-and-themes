<?php
/**
 * Title: Mixed media article (gallery + wrapped images + text)
 * Slug: inside-block-patterns/mixed-media-article
 * Description: Starts with a gallery, then paragraphs, then a left-aligned image with copy, then a right-aligned image with more copy.
 * Categories: ibp-content
 * Block Types: core/group
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container">

	<!-- wp:gallery {"columns":3,"linkTo":"none"} -->
	<figure class="wp-block-gallery has-nested-images columns-3 is-cropped">
		<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
		<figure class="wp-block-image size-large">
			<img src="<?php echo esc_url( IBP_ASSETS_URL . "images/image-6.jpg" ); ?>" alt="Black-bellied Whistling-Ducks" />
			<figcaption>Photo: Jon Breitenbucher</figcaption>
		</figure>
		<!-- /wp:image -->

		<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
		<figure class="wp-block-image size-large">
			<img src="<?php echo esc_url( IBP_ASSETS_URL . "images/image-2.jpg" ); ?>" alt="Fungus on a rotting log" />
			<figcaption>Photo: Jon Breitenbucher</figcaption>
		</figure>
		<!-- /wp:image -->

		<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
		<figure class="wp-block-image size-large">
			<img src="<?php echo esc_url( IBP_ASSETS_URL . "images/image-4.jpg" ); ?>" alt="Monarch Butterfly on Blazing Star" />
			<figcaption>Photo: Jon Breitenbucher</figcaption>
		</figure>
		<!-- /wp:image -->
	</figure>
	<!-- /wp:gallery -->

	<!-- wp:paragraph -->
	<p>Opening paragraph introducing the topic. Keep it short and readable.</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p>Another paragraph to provide context, link to resources, or set expectations.</p>
	<!-- /wp:paragraph -->

	<!-- wp:image {"linkDestination":"none","align":"left","sizeSlug":"medium","width":320} -->
	<figure class="wp-block-image alignleft size-medium is-resized">
		<img src="<?php echo esc_url( IBP_ASSETS_URL . "images/image-1.jpg" ); ?>" alt="Yellow flower" width="320" />
		<figcaption>Jon Breitenbucher</figcaption>
	</figure>
	<!-- /wp:image -->

	<!-- wp:paragraph -->
	<p>Text flows around the left-aligned image. Replace the placeholder with a real image and meaningful alt text if needed.</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p>A second paragraph to demonstrate wrapping behavior on wider screens. On narrow screens the image stacks above.</p>
	<!-- /wp:paragraph -->

	<!-- wp:image {"linkDestination":"none","align":"right","sizeSlug":"medium","width":300} -->
	<figure class="wp-block-image alignright size-medium is-resized">
		<img src="<?php echo esc_url( IBP_ASSETS_URL . "images/image-5.jpg" ); ?>" alt="Wild Teasle" width="300" />
		<figcaption>Jon Breitenbucher</figcaption>
	</figure>
	<!-- /wp:image -->

	<!-- wp:paragraph -->
	<p>Now text wraps around a right-aligned image. This works reliably in block themes that support left/right alignment.</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p>Add another paragraph to make the layout feel balanced and demonstrate longer text flow with the floated image.</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p>Finish with a concluding thought or a call to action.</p>
	<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
