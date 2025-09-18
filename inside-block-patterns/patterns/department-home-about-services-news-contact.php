<?php
/**
 * Title: Department home: about + services + news + contact
 * Slug: inside-block-patterns/department-home-about-services-news-contact
 * Description: Two-section hero/about with image, audience/services tiles, recent news grid, and contact/social locations.
 * Categories: ibp-department
 * Inserter: true
 * Version: 1.0.0
 */
?>

<!-- wp:group {"align":"full","className":"is-style-section-5","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-5" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
	<div class="wp-block-group ibp-container">
		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|60","left":"var:preset|spacing|80"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
			<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
				<!-- wp:heading -->
				<h2 class="wp-block-heading">About the department</h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"fontSize":"medium"} -->
				<p class="has-medium-font-size">Held over a weekend, the event is structured around a series of exhibitions, workshops, and panel discussions. The exhibitions showcase a curated selection of photographs that tell compelling stories from various corners of the globe, each image accompanied by detailed narratives that provide context and deeper insight into the historical significance of the scenes depicted. These photographs are drawn from the archives of renowned photographers, as well as emerging talents, ensuring a blend of both classical and contemporary perspectives.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
			<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
				<!-- wp:image {"aspectRatio":"1","scale":"cover","sizeSlug":"full"} -->
				<figure class="wp-block-image size-full"><img src="<?php echo esc_url( IBP_ASSETS_URL . "images/hero-placeholder-2.jpg" ); ?>" alt="Ruby-throated Hummingbird" style="aspect-ratio:1;object-fit:cover"/></figure>
				<!-- /wp:image -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"blockGap":"0","margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
	<div class="wp-block-group ibp-container">
		<!-- wp:group {"layout":{"type":"constrained","justifyContent":"left"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"fontSize":"x-large"} -->
			<h2 class="wp-block-heading has-x-large-font-size">Services we provide</h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph -->
			<p>These are some of the services we provide by audience.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"0","left":"var:preset|spacing|50"},"padding":{"top":"0","bottom":"0"}}}} -->
		<div class="wp-block-columns" style="padding-top:0;padding-bottom:0">
			<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70"},"blockGap":"0"}}} -->
			<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--70)">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full"><img src="<?php echo esc_url( IBP_ASSETS_URL . "images/image-4.jpg" ); ?>" alt="Event image"/></figure>
				<!-- /wp:image -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","padding":{"top":"var:preset|spacing|30"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
				<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30)">
					<!-- wp:heading {"level":3,"style":{"spacing":{"padding":{"top":"var:preset|spacing|20"}}}} -->
					<h3 class="wp-block-heading" style="padding-top:var(--wp--preset--spacing--20)">Faculty</h3>
					<!-- /wp:heading -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70"},"blockGap":"0"}}} -->
			<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--70)">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full"><img src="<?php echo esc_url( IBP_ASSETS_URL . "images/image-6.jpg" ); ?>" alt="Event image"/></figure>
				<!-- /wp:image -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","padding":{"top":"var:preset|spacing|30"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
				<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30)">
					<!-- wp:heading {"level":3,"style":{"spacing":{"padding":{"top":"var:preset|spacing|20"}}}} -->
					<h3 class="wp-block-heading" style="padding-top:var(--wp--preset--spacing--20)">Staff</h3>
					<!-- /wp:heading -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"style":{"spacing":{"padding":{"top":"var:preset|spacing|70"},"blockGap":"0"}}} -->
			<div class="wp-block-column" style="padding-top:var(--wp--preset--spacing--70)">
				<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->
				<figure class="wp-block-image size-full"><img src="<?php echo esc_url( IBP_ASSETS_URL . "images/image-2.jpg" ); ?>" alt="Event image"/></figure>
				<!-- /wp:image -->
				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","padding":{"top":"var:preset|spacing|30"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
				<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30)">
					<!-- wp:heading {"level":3,"style":{"spacing":{"padding":{"top":"var:preset|spacing|20"}}}} -->
					<h3 class="wp-block-heading" style="padding-top:var(--wp--preset--spacing--20)">Students</h3>
					<!-- /wp:heading -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
<div class="wp-block-group ibp-container">
	<!-- wp:query {"queryId":54,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","sticky":"exclude","inherit":false}} -->
	<div class="wp-block-query">
		<!-- wp:heading -->
		<h2 class="wp-block-heading">Recent news</h2>
		<!-- /wp:heading -->

		<!-- wp:post-template {"layout":{"type":"grid","minimumColumnWidth":"12rem"}} -->
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"30px","right":"30px","bottom":"30px","left":"30px"}}},"layout":{"inherit":false}} -->
			<div class="wp-block-group" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">
				<!-- wp:post-featured-image /-->
				<!-- wp:post-title {"level":3,"isLink":true} /-->
				<!-- wp:post-excerpt /-->
				<!-- wp:post-date /-->
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
	<!-- wp:group {"className":"ibp-container","layout":{"type":"constrained"}} -->
	<div class="wp-block-group ibp-container">
		<!-- wp:group {"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"textAlign":"left","align":"full","fontSize":"xx-large"} -->
			<h2 class="wp-block-heading alignfull has-text-align-left has-xx-large-font-size">How to get in touch with us</h2>
			<!-- /wp:heading -->

			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"},"blockGap":"var:preset|spacing|50","margin":{"top":"var:preset|spacing|50"}},"border":{"top":{"color":"var:preset|color|accent-4","width":"1px"}}},"layout":{"type":"grid","minimumColumnWidth":"23rem"}} -->
			<div class="wp-block-group" style="border-top-color:var(--wp--preset--color--accent-4);border-top-width:1px;margin-top:var(--wp--preset--spacing--50);padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
				<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size">Social media</h3>
					<!-- /wp:heading -->

					<!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"medium","layout":{"type":"flex","orientation":"vertical"},"ariaLabel":"Social media"} -->
					<!-- wp:navigation-link {"label":"X","url":"#"} /-->
					<!-- wp:navigation-link {"label":"Instagram","url":"#"} /-->
					<!-- wp:navigation-link {"label":"Facebook","url":"#"} /-->
					<!-- wp:navigation-link {"label":"TikTok","url":"#"} /-->
					<!-- /wp:navigation -->

					<!-- wp:heading {"level":3,"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size">Email</h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size">example@example.com</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size">New York</h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size">123 Example St. Manhattan, NY 10300 United States</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size">San Diego</h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size">123 Example St. Manhattan, NY 10300 United States</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size">Salt Lake City</h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size">123 Example St. Manhattan, NY 10300 United States</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

				<!-- wp:group {"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"fontSize":"medium"} -->
					<h3 class="wp-block-heading has-medium-font-size">Portland</h3>
					<!-- /wp:heading -->
					<!-- wp:paragraph {"fontSize":"medium"} -->
					<p class="has-medium-font-size">123 Example St. Manhattan, NY 10300 United States</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
