<?php
/**
 * Server render for TOC block.
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content (unused).
 * @return string
 */
if ( ! function_exists( 'ibp_render_toc_block' ) ) {
	function ibp_render_toc_block( $attributes, $content ) {
		$title      = isset( $attributes['title'] ) ? $attributes['title'] : 'On this page';
		$include_h3 = ! empty( $attributes['includeH3'] );
		$include_h4 = ! empty( $attributes['includeH4'] );
		$include_h5 = ! empty( $attributes['includeH5'] );
		$include_h6 = ! empty( $attributes['includeH6'] );
		$collapsed  = ! empty( $attributes['collapsed'] );
		$max_depth  = isset( $attributes['maxDepth'] ) ? max( 2, min( 6, intval( $attributes['maxDepth'] ) ) ) : 6;

		global $post;
		if ( ! $post ) {
			return '';
		}

		$blocks = parse_blocks( $post->post_content );

		$items = array();
		$walker = function( $nodes ) use ( &$walker, &$items ) {
			foreach ( $nodes as $node ) {
				if ( empty( $node['blockName'] ) ) {
					continue;
				}
				if ( 'core/heading' === $node['blockName'] ) {
					$attrs = isset( $node['attrs'] ) ? $node['attrs'] : array();
					$level = isset( $attrs['level'] ) ? intval( $attrs['level'] ) : 2;

					$text  = '';
					if ( isset( $attrs['content'] ) && $attrs['content'] ) {
						$text = wp_strip_all_tags( $attrs['content'] );
					} elseif ( isset( $node['innerHTML'] ) ) {
						$text = wp_strip_all_tags( $node['innerHTML'] );
					}
					$anchor = isset( $attrs['anchor'] ) ? $attrs['anchor'] : '';

					$items[] = array(
						'level'  => max( 2, min( 6, $level ) ),
						'text'   => $text,
						'anchor' => $anchor,
					);
				}
				if ( ! empty( $node['innerBlocks'] ) ) {
					$walker( $node['innerBlocks'] );
				}
			}
		};
		$walker( $blocks );

		// Filter by settings
		$items = array_values( array_filter( $items, function( $h ) use ( $include_h3, $include_h4, $include_h5, $include_h6, $max_depth ) {
			if ( $h['level'] > $max_depth ) return false;
			if ( 2 === $h['level'] ) return true;
			if ( 3 === $h['level'] ) return $include_h3;
			if ( 4 === $h['level'] ) return $include_h4;
			if ( 5 === $h['level'] ) return $include_h5;
			if ( 6 === $h['level'] ) return $include_h6;
			return false;
		} ) );

		if ( ! $items ) {
			return '<nav class="ibp-toc" aria-label="' . esc_attr__( 'Table of contents', 'inside-block-patterns' ) . '"><p class="ibp-toc__empty">' . esc_html__( 'No headings found.', 'inside-block-patterns' ) . '</p></nav>';
		}

		// Assign stable ids
		$slug_counts = array();
		foreach ( $items as $i => $h ) {
			if ( ! empty( $h['anchor'] ) ) {
				$slug = sanitize_title( $h['anchor'] );
			} else {
				$base = sanitize_title( $h['text'] );
				if ( '' === $base ) $base = 'section';
				$slug = $base;
				$k = 2;
				while ( isset( $slug_counts[ $slug ] ) ) {
					$slug = $base . '-' . $k;
					$k++;
				}
			}
			$slug_counts[ $slug ] = true;
			$items[ $i ]['id'] = $slug;
		}

		// Build nested ULs
		$base_level = 2;
		$current    = $base_level;
		$list_html  = '<ul class="ibp-toc__list level-' . $base_level . '">';
		foreach ( $items as $h ) {
			$level = max( $base_level, min( 6, intval( $h['level'] ) ) );
			while ( $current < $level ) {
				$list_html .= '<ul class="ibp-toc__list level-' . ( $current + 1 ) . '">';
				$current++;
			}
			while ( $current > $level ) {
				$list_html .= '</ul>';
				$current--;
			}
			$list_html .= '<li class="ibp-toc__item level-' . $level . '"><a href="#' . esc_attr( $h['id'] ) . '">' . esc_html( $h['text'] ?: __( '(untitled)', 'inside-block-patterns' ) ) . '</a></li>';
		}
		while ( $current > $base_level ) {
			$list_html .= '</ul>';
			$current--;
		}
		$list_html .= '</ul>';

		$content_id = 'ibp-toc-' . wp_unique_id();

		$nav_classes = 'ibp-toc' . ( $collapsed ? ' is-collapsed' : '' );
		$html  = '<nav class="' . esc_attr( $nav_classes ) . '" aria-label="' . esc_attr__( 'Table of contents', 'inside-block-patterns' ) . '"';
		$html .= ' data-ids="' . esc_attr( wp_json_encode( wp_list_pluck( $items, 'id' ) ) ) . '"';
		$html .= ' data-content="' . esc_attr( $content_id ) . '"';
		$html .= ' data-max-depth="' . esc_attr( $max_depth ) . '"';
		$html .= '>';

		// Toggle button (always rendered; state via aria-expanded + hidden)
		$html .= '<button type="button" class="ibp-toc__toggle" aria-controls="' . esc_attr( $content_id ) . '" aria-expanded="' . ( $collapsed ? 'false' : 'true' ) . '">';
		$html .= esc_html( $title ?: __( 'On this page', 'inside-block-patterns' ) ) . '<span class="ibp-toc__caret" aria-hidden="true"></span></button>';

		$html .= '<div id="' . esc_attr( $content_id ) . '" class="ibp-toc__content" ' . ( $collapsed ? 'hidden' : '' ) . '>' . $list_html . '</div>';
		$html .= '</nav>';

		return $html;
	}
}

return ibp_render_toc_block( $attributes, $content );