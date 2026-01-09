<?php
/**
 * Plugin Name:       Wooster Block Patterns
 * Description:       Curated block patterns and custom blocks for College of Wooster department and office sites.
 * Version:           1.4.1
 * Author:            College of Wooster (Jon Breitenbucher)
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wooster-block-patterns
 * Requires at least: 6.5
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

// URLs and paths for assets so patterns can reference images safely.
if ( ! defined( 'WBP_PLUGIN_URL' ) ) {
	define( 'WBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'WBP_PLUGIN_PATH' ) ) {
	define( 'WBP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WBP_ASSETS_URL' ) ) {
	define( 'WBP_ASSETS_URL', WBP_PLUGIN_URL . 'assets/' );
}

/**
 * Pattern categories (for Patterns UI)
 */
add_action( 'init', function() {
	$cats = array(
		'wbp-department' => array( 'label' => __( 'Wooster Department & Office', 'wooster-block-patterns' ) ),
		'wbp-content'    => array( 'label' => __( 'Wooster Content Layouts', 'wooster-block-patterns' ) ),
		'wbp-messaging'  => array( 'label' => __( 'Wooster Messaging & Alerts', 'wooster-block-patterns' ) ),
		'wbp-people'     => array( 'label' => __( 'Wooster People & Directory', 'wooster-block-patterns' ) ),
		'wbp-news'       => array( 'label' => __( 'Wooster News & Posts', 'wooster-block-patterns' ) ),
	);
	foreach ( $cats as $slug => $args ) {
		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category( $slug, $args );
		}
	}
} );

/**
 * Register patterns from /patterns
 */
add_action( 'init', function() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	$dir   = plugin_dir_path( __FILE__ ) . 'patterns/';
	$files = glob( $dir . '*.php' );
	if ( empty( $files ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	foreach ( $files as $file ) {
		$headers = get_file_data(
			$file,
			array(
				'title'       => 'Title',
				'slug'        => 'Slug',
				'description' => 'Description',
				'categories'  => 'Categories',
			)
		);

		$slug  = isset( $headers['slug'] ) ? trim( $headers['slug'] ) : '';
		$title = isset( $headers['title'] ) ? trim( $headers['title'] ) : '';

		if ( '' === $slug || '' === $title ) {
			continue;
		}

		$cats = array();
		if ( ! empty( $headers['categories'] ) ) {
			$cats = array_map( 'trim', explode( ',', $headers['categories'] ) );
		}

		ob_start();
		include $file; // file outputs block HTML only.
		$content = trim( ob_get_clean() );

		register_block_pattern(
			$slug,
			array(
				'title'       => $title,
				'description' => $headers['description'],
				'categories'  => $cats,
				'content'     => $content,
			)
		);
	}
}, 20 );

/**
 * Block category for our custom blocks (Blocks UI, not Patterns)
 * Make sure the slug matches the one used in block.json ("wbp-content").
 */
add_filter( 'block_categories_all', function( $categories, $editor_context ) {
	$slug  = 'wbp-content';
	$title = __( 'Wooster Blocks', 'wooster-block-patterns' );
	$icon  = 'layout';

	$found = false;
	foreach ( $categories as &$cat ) {
		if ( ! empty( $cat['slug'] ) && $cat['slug'] === $slug ) {
			// Enforce our label/icon if the slug already exists.
			$cat['title'] = $title;
			$cat['icon']  = $icon;
			$found = true;
			break;
		}
	}

	if ( ! $found ) {
		// Put our category first so editors can find it quickly.
		array_unshift( $categories, array(
			'slug'  => $slug,
			'title' => $title,
			'icon'  => $icon,
		) );
	}

	return $categories;
}, 10, 2 );

add_action( 'init', function() {
	$base = plugin_dir_path( __FILE__ );
	$url  = plugins_url( '', __FILE__ );

	// Shared deps for editor scripts that call into window.wp.*
	$deps = array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data' );

	/* ========= ACCORDION ========= */
	$acc_style_rel = '/blocks/accordion/style.css';
	wp_register_style( 'wbp-accordion-style', $url . $acc_style_rel, array(), file_exists( $base . $acc_style_rel ) ? filemtime( $base . $acc_style_rel ) : null );

	$acc_view_rel = '/blocks/accordion/view.js';
	wp_register_script( 'wbp-accordion-view', $url . $acc_view_rel, array(), file_exists( $base . $acc_view_rel ) ? filemtime( $base . $acc_view_rel ) : null, true );

	$acc_editor_rel = '/blocks/accordion/editor.js';
	wp_register_script( 'wbp-accordion-editor', $url . $acc_editor_rel, $deps, file_exists( $base . $acc_editor_rel ) ? filemtime( $base . $acc_editor_rel ) : null, true );

	$item_editor_rel = '/blocks/accordion-item/editor.js';
	wp_register_script( 'wbp-accordion-item-editor', $url . $item_editor_rel, $deps, file_exists( $base . $item_editor_rel ) ? filemtime( $base . $item_editor_rel ) : null, true );

	$accordion_path       = __DIR__ . '/blocks/accordion';
	$accordion_item_path  = __DIR__ . '/blocks/accordion-item';

	if ( file_exists( $accordion_path . '/block.json' ) ) {
		register_block_type( $accordion_path, array(
			'editor_script' => 'wbp-accordion-editor',
			'style'         => 'wbp-accordion-style',
			'view_script'   => 'wbp-accordion-view',
		) );
	} // else: missing block.json; silently skip.

	if ( file_exists( $accordion_item_path . '/block.json' ) ) {
		register_block_type( $accordion_item_path, array(
			'editor_script' => 'wbp-accordion-item-editor',
			'style'         => 'wbp-accordion-style',
		) );
	} // else: missing block.json; silently skip.

	/* ========= TOC ========= */
	$toc_style_rel        = '/blocks/toc/style.css';
	$toc_editor_style_rel = '/blocks/toc/editor.css';
	$toc_view_rel         = '/blocks/toc/view.js';
	$toc_editor_rel       = '/blocks/toc/index.js';

	wp_register_style(  'wbp-toc-style',        $url . $toc_style_rel,        array(), file_exists( $base . $toc_style_rel ) ? filemtime( $base . $toc_style_rel ) : null );
	wp_register_style(  'wbp-toc-editor-style', $url . $toc_editor_style_rel, array(), file_exists( $base . $toc_editor_style_rel ) ? filemtime( $base . $toc_editor_style_rel ) : null );
	wp_register_script( 'wbp-toc-view',         $url . $toc_view_rel,         array(), file_exists( $base . $toc_view_rel ) ? filemtime( $base . $toc_view_rel ) : null, true );
	wp_register_script( 'wbp-toc-editor',       $url . $toc_editor_rel,       $deps,   file_exists( $base . $toc_editor_rel ) ? filemtime( $base . $toc_editor_rel ) : null, true );

	// Load the render function for the dynamic TOC block and pass it as render_callback.
	$toc_path = __DIR__ . '/blocks/toc';
	if ( file_exists( $toc_path . '/block.json' ) ) {
		$render_php = $toc_path . '/render.php';
		if ( file_exists( $render_php ) ) {
			require_once $render_php;
		}
		register_block_type( $toc_path, array(
			'editor_script'  => 'wbp-toc-editor',
			'editor_style'   => 'wbp-toc-editor-style',
			'style'          => 'wbp-toc-style',
			'view_script'    => 'wbp-toc-view',
			'render_callback'=> function( $attributes, $content, $block ) {
				if ( function_exists( 'wbp_render_toc_block' ) ) {
					return wbp_render_toc_block( $attributes, $content );
				}
				return '';
			},
		) );
	} // else: missing block.json; silently skip.
} );

/**
 * Optional site-wide utilities CSS (both front + editor), cache-busted
 */
add_action( 'enqueue_block_assets', function() {
	$rel  = 'assets/css/wbp.css';
	$path = plugin_dir_path( __FILE__ ) . $rel;
	$url  = plugins_url( $rel, __FILE__ );
	$ver  = file_exists( $path ) ? filemtime( $path ) : null;
	wp_enqueue_style( 'wbp-styles', $url, array(), $ver );
} );