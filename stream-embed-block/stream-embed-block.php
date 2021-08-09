<?php
/**
 * @link              https://technology.wooster.edu
 * @since             0.1
 * @package           Embed Block for Stream
 *
 * Plugin Name:       Embed Block for Stream
 * Plugin URI:        https://technology.wooster.edu
 * Description:       Easily embed Stream videos in both Gutenberg and Classic Editor.
 * Version:           0.1
 * Author:            audrasjb, sdohri
 * Author URI:        https://jeanbaptisteaudras.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       embed-block-for-Stream
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
 * Add Stream oEmbed provider
 */
wp_oembed_add_provider( '#https?://(web\.)?microsoftstream\.com/video/*', 'https://web.microsoftstream.com/oembed', true );


/*
 * Handle Stream oEmbed Gutenberg Block
 */
function ebStream_embed_video( $attributes ) {
	$Stream_url = trim( $attributes['stream_url'] );
	$content = '';
	if ( '' === trim( $stream_url ) ) {
		$content = '<p>' . esc_html__( 'Use the Sidebar to add the URL of your Stream Video.', 'embed-block-for-stream' ) . '</p>';
	} else {
		//$pattern = "/<iframe width=\"640\" height=\"360\" src=\"https://web.microsoftstream.com/video/";
		//if ( 1 === preg_match($pattern, $stream_url)) {
			return $stream_url;
		//}
	}
	return $content;
}

/*
 * Declare Stream oEmbed Gutenberg Block and add assets
 */
function sdstream_enqueue_scripts() {
	wp_register_script(
		'sdstream-video-editor',
		plugins_url( 'stream-block.js', __FILE__ ),
		array( 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n', 'wp-editor' ),
		filemtime( plugin_dir_path( __FILE__ ) . 'stream-block.js' )
	);
	register_block_type( 'embed-block-for-stream/video', array(
		'editor_script'   => 'sdstream-video-editor',
		'render_callback' => 'sdstream_embed_video',
		'attributes'      => array(
			'stream_url' => array( 'type' => 'string' ),
		),
	) );
}
add_action( 'init', 'sdstream_enqueue_scripts' );
