<div id="blog_template-selection">
	<h3><?php esc_html_e( 'Select a template', 'blogtemplates' ); ?></h3>
	<?php
		if ( class_exists( 'BuddyPress' ) ) {
			$nbtpl_sign_up_url = bp_get_signup_page();
		}
		else {
			$nbtpl_sign_up_url = network_site_url( 'wp-signup.php' );
				$nbtpl_sign_up_url = apply_filters( 'wp_signup_location', $nbtpl_sign_up_url ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}
		$nbtpl_sign_up_url = add_query_arg( 'blog_template', 'just_user', $nbtpl_sign_up_url );
	?>
	<p><a href="<?php echo esc_url( $nbtpl_sign_up_url ); ?>"><?php esc_html_e( 'Just a username, please.', 'blogtemplates' ); ?></a></p>
	<?php
		if ( $settings['show-categories-selection'] )
			$nbtpl_templates = nbtpl_theme_selection_toolbar( $nbtpl_templates );
    ?>

	<div class="blog_template-option">

	<?php
	foreach ( $nbtpl_templates as $nbtpl_tkey => $nbtpl_template ) {
		nbt_render_theme_selection_item( 'page-showcase', $nbtpl_tkey, $nbtpl_template, $settings );
	}
	?>
	<div style="clear:both;"></div>
	</div>
</div>