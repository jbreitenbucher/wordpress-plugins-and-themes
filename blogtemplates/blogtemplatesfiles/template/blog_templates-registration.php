<?php
/**
 * Simple selection box template.
 *
 * Copy this file into your theme directory and edit away!
 * You can also use $nbtpl_templates array to iterate through your templates.
 */
?>
<?php if (defined('BP_VERSION') && 'bp-default' == get_blog_option(bp_get_root_blog_id(), 'stylesheet')) echo '<br style="clear:both" />'; ?>
<?php $nbtpl_checked = isset( $settings['default'] ) ? $settings['default'] : ''; ?>
<div id="blog_template-selection">
	<h3><?php esc_html_e( 'Select a template', 'blogtemplates' ); ?></h3>

	<?php
		if ( $settings['show-categories-selection'] )
			$nbtpl_templates = nbt_theme_selection_toolbar( $nbtpl_templates );
    ?>

    <div class="blog_template-option" style="text-align:center;margin-bottom:30px;">
    	<select name="blog_template">
    		<?php if ( empty( $nbtpl_checked ) ): ?>
    			<option value="none"><?php esc_html_e( 'None', 'blogtemplates' ); ?></option>
    		<?php endif; ?>
			<?php foreach ($nbtpl_templates as $nbtpl_tkey => $nbtpl_template) {
				nbt_render_theme_selection_item( '', $nbtpl_tkey, $nbtpl_template, $settings );
			} ?>
		</select>
	</div>
</div>