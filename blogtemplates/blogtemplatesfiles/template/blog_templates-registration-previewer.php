<div id="blog_template-selection">
	<h3><?php esc_html_e( 'Select a template', 'blogtemplates' ); ?></h3>

	<?php
		if ( $settings['show-categories-selection'] )
			$nbtpl_templates = nbt_theme_selection_toolbar( $nbtpl_templates );
    ?>

	<div class="blog_template-option">

	<?php
	foreach ($nbtpl_templates as $nbtpl_tkey => $nbtpl_template) {
		nbt_render_theme_selection_item( 'previewer', $nbtpl_tkey, $nbtpl_template, $settings );
	}
	?>
	<div style="clear:both;"></div>
	</div>
</div>