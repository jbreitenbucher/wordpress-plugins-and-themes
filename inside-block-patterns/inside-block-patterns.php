<?php
/**
 * Plugin Name:       Inside Block Patterns
 * Description:       Curated block patterns and custom blocks for College of Wooster department and office sites.
 * Version:           1.3.5
 * Author:            College of Wooster (Jon Breitenbucher)
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       inside-block-patterns
 * Requires at least: 6.5
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

// URLs and paths for assets so patterns can reference images safely.
if ( ! defined( 'IBP_PLUGIN_URL' ) ) {
	define( 'IBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'IBP_PLUGIN_PATH' ) ) {
	define( 'IBP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'IBP_ASSETS_URL' ) ) {
	define( 'IBP_ASSETS_URL', IBP_PLUGIN_URL . 'assets/' );
}

/**
 * i18n
 */
add_action( 'init', function() {
	load_plugin_textdomain( 'inside-block-patterns' );
} );

/**
 * Pattern categories (for Patterns UI)
 */
add_action( 'init', function() {
	$cats = array(
		'ibp-department' => array( 'label' => __( 'Inside Department & Office', 'inside-block-patterns' ) ),
		'ibp-content'    => array( 'label' => __( 'Inside Content Layouts', 'inside-block-patterns' ) ),
		'ibp-messaging'  => array( 'label' => __( 'Inside Messaging & Alerts', 'inside-block-patterns' ) ),
		'ibp-people'     => array( 'label' => __( 'Inside People & Directory', 'inside-block-patterns' ) ),
		'ibp-news'       => array( 'label' => __( 'Inside News & Posts', 'inside-block-patterns' ) ),
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
 * Make sure the slug matches the one used in block.json ("ibp-content").
 */
add_filter( 'block_categories_all', function( $categories, $post ) {
	$slug  = 'ibp-content';
	$title = __( 'Inside Blocks', 'inside-block-patterns' );

	$found = false;
	foreach ( $categories as &$cat ) {
		if ( ! empty( $cat['slug'] ) && $cat['slug'] === $slug ) {
			$cat['title'] = $title; // enforce our label if the slug already exists
			$found = true;
			break;
		}
	}

	if ( ! $found ) {
		$categories[] = array(
			'slug'  => $slug,
			'title' => $title,
		);
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
	wp_register_style( 'ibp-accordion-style', $url . $acc_style_rel, array(), file_exists( $base . $acc_style_rel ) ? filemtime( $base . $acc_style_rel ) : null );

	$acc_view_rel = '/blocks/accordion/view.js';
	wp_register_script( 'ibp-accordion-view', $url . $acc_view_rel, array(), file_exists( $base . $acc_view_rel ) ? filemtime( $base . $acc_view_rel ) : null, true );

	$acc_editor_rel = '/blocks/accordion/editor.js';
	wp_register_script( 'ibp-accordion-editor', $url . $acc_editor_rel, $deps, file_exists( $base . $acc_editor_rel ) ? filemtime( $base . $acc_editor_rel ) : null, true );

	$item_editor_rel = '/blocks/accordion-item/editor.js';
	wp_register_script( 'ibp-accordion-item-editor', $url . $item_editor_rel, $deps, file_exists( $base . $item_editor_rel ) ? filemtime( $base . $item_editor_rel ) : null, true );

	$accordion_path       = __DIR__ . '/blocks/accordion';
	$accordion_item_path  = __DIR__ . '/blocks/accordion-item';

	if ( file_exists( $accordion_path . '/block.json' ) ) {
		register_block_type( $accordion_path, array(
			'editor_script' => 'ibp-accordion-editor',
			'style'         => 'ibp-accordion-style',
			'view_script'   => 'ibp-accordion-view',
		) );
	} else {
		error_log( 'IBP: missing block.json at ' . $accordion_path );
	}

	if ( file_exists( $accordion_item_path . '/block.json' ) ) {
		register_block_type( $accordion_item_path, array(
			'editor_script' => 'ibp-accordion-item-editor',
			'style'         => 'ibp-accordion-style',
		) );
	} else {
		error_log( 'IBP: missing block.json at ' . $accordion_item_path );
	}

	/* ========= TOC ========= */
	$toc_style_rel        = '/blocks/toc/style.css';
	$toc_editor_style_rel = '/blocks/toc/editor.css';
	$toc_view_rel         = '/blocks/toc/view.js';
	$toc_editor_rel       = '/blocks/toc/index.js';

	wp_register_style(  'ibp-toc-style',        $url . $toc_style_rel,        array(), file_exists( $base . $toc_style_rel ) ? filemtime( $base . $toc_style_rel ) : null );
	wp_register_style(  'ibp-toc-editor-style', $url . $toc_editor_style_rel, array(), file_exists( $base . $toc_editor_style_rel ) ? filemtime( $base . $toc_editor_style_rel ) : null );
	wp_register_script( 'ibp-toc-view',         $url . $toc_view_rel,         array(), file_exists( $base . $toc_view_rel ) ? filemtime( $base . $toc_view_rel ) : null, true );
	wp_register_script( 'ibp-toc-editor',       $url . $toc_editor_rel,       $deps,   file_exists( $base . $toc_editor_rel ) ? filemtime( $base . $toc_editor_rel ) : null, true );

	// Load the render function for the dynamic TOC block and pass it as render_callback.
	$toc_path = __DIR__ . '/blocks/toc';
	if ( file_exists( $toc_path . '/block.json' ) ) {
		$render_php = $toc_path . '/render.php';
		if ( file_exists( $render_php ) ) {
			require_once $render_php;
		}
		register_block_type( $toc_path, array(
			'editor_script'  => 'ibp-toc-editor',
			'editor_style'   => 'ibp-toc-editor-style',
			'style'          => 'ibp-toc-style',
			'view_script'    => 'ibp-toc-view',
			'render_callback'=> function( $attributes, $content, $block ) {
				if ( function_exists( 'ibp_render_toc_block' ) ) {
					return ibp_render_toc_block( $attributes, $content );
				}
				return '';
			},
		) );
	} else {
		error_log( 'IBP: missing block.json at ' . $toc_path );
	}
} );

/**
 * Optional site-wide utilities CSS (both front + editor), cache-busted
 */
add_action( 'enqueue_block_assets', function() {
	$rel  = 'assets/css/ibp.css';
	$path = plugin_dir_path( __FILE__ ) . $rel;
	$url  = plugins_url( $rel, __FILE__ );
	$ver  = file_exists( $path ) ? filemtime( $path ) : null;
	wp_enqueue_style( 'ibp-styles', $url, array(), $ver );
} );