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

<!-- wp:group {"metadata":{"categories":["ibp-content"],"patternName":"inside-block-patterns/mixed-media-article","name":"Mixed media article (gallery + wrapped images + text)"},"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container">

	<!-- wp:gallery {"columns":3,"linkTo":"none"} -->
	<figure class="wp-block-gallery has-nested-images columns-3 is-cropped">
		<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
		<figure class="wp-block-image size-large">
			<img src="https://upload.wikimedia.org/wikipedia/commons/5/5f/Dark_mossy_forest.jpg" alt="Mossy forest trunks" />
			<figcaption>Photo: Jon Sullivan (Public Domain) via Wikimedia Commons</figcaption>
		</figure>
		<!-- /wp:image -->

		<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
		<figure class="wp-block-image size-large">
			<img src="https://upload.wikimedia.org/wikipedia/commons/2/21/Old_growth_forest_scenic.jpg" alt="Old-growth forest in mist" />
			<figcaption>Photo: Patte David, USFWS (Public Domain) via Wikimedia Commons</figcaption>
		</figure>
		<!-- /wp:image -->

		<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
		<figure class="wp-block-image size-large">
			<img src="https://upload.wikimedia.org/wikipedia/commons/8/88/Forest_dark.jpg" alt="Forest silhouettes with backlight" />
			<figcaption>Photo: Paolo Neo (Public Domain) via Wikimedia Commons</figcaption>
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

	<!-- wp:image {"sizeSlug":"medium","linkDestination":"none","align":"left"} -->
	<figure class="wp-block-image alignleft size-medium">
		<img src="https://upload.wikimedia.org/wikipedia/commons/c/c8/Library_Interior_%285604862859%29.jpg" alt="Vasconcelos Library interior, Mexico City" />
		<figcaption>Francisco Anzola, <a href="https://creativecommons.org/licenses/by/2.0" target="_blank" rel="noopener">CC BY 2.0</a>, via Flickr/Openverse</figcaption>
	</figure>
	<!-- /wp:image -->

	<!-- wp:paragraph -->
	<p>Text flows around the left-aligned image. Replace the placeholder with a real image and meaningful alt text if needed.</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p>A second paragraph to demonstrate wrapping behavior on wider screens. On narrow screens the image stacks above.</p>
	<!-- /wp:paragraph -->

	<!-- wp:image {"sizeSlug":"medium","linkDestination":"none","align":"right"} -->
	<figure class="wp-block-image alignright size-medium">
		<img src="https://upload.wikimedia.org/wikipedia/commons/e/ea/San_Francisco_city_skyline.jpg" alt="San Francisco skyline from Salesforce Tower" />
		<figcaption>Lisafern, <a href="https://creativecommons.org/publicdomain/zero/1.0/" target="_blank" rel="noopener">CC0</a>, via Openverse</figcaption>
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