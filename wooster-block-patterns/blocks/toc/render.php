<?php
/**
 * Server render for TOC block (H2..H{maxDepth}, no per-level toggles).
 *
 * @param array  $attributes Block attributes.
 * @param string $content    Block content (unused).
 * @return string
 */
if ( ! function_exists( 'wbp_render_toc_block' ) ) {
	function wbp_render_toc_block( $attributes, $content ) {
		$title     = isset( $attributes['title'] ) ? $attributes['title'] : __( 'On this page', 'wooster-block-patterns' );
		$collapsed = ! empty( $attributes['collapsed'] );
		$max_depth = isset( $attributes['maxDepth'] ) ? max( 2, min( 6, (int) $attributes['maxDepth'] ) ) : 3;

		$post = get_post();
		if ( ! $post ) {
			$wrapper = get_block_wrapper_attributes( array(
				'class' => 'wbp-toc' . ( $collapsed ? ' is-collapsed' : '' ),
			) );
			return '<nav ' . $wrapper . ' aria-label="' . esc_attr__( 'Table of contents', 'wooster-block-patterns' ) . '"><p class="wbp-toc__empty">' . esc_html__( 'No headings found.', 'wooster-block-patterns' ) . '</p></nav>';
		}

		// Parse blocks to collect headings in document order.
		$blocks = parse_blocks( $post->post_content );

		$items = array();
		$walk  = function( $nodes ) use ( &$walk, &$items ) {
			foreach ( (array) $nodes as $node ) {
				if ( empty( $node['blockName'] ) ) {
					continue;
				}
				if ( 'core/heading' === $node['blockName'] ) {
					$attrs  = isset( $node['attrs'] ) ? (array) $node['attrs'] : array();
					$level  = isset( $attrs['level'] ) ? (int) $attrs['level'] : 2;
					$anchor = isset( $attrs['anchor'] ) ? (string) $attrs['anchor'] : '';

					$text = '';
					if ( isset( $attrs['content'] ) && $attrs['content'] ) {
						$text = wp_strip_all_tags( $attrs['content'] );
					} elseif ( isset( $node['innerHTML'] ) ) {
						$text = wp_strip_all_tags( $node['innerHTML'] );
					}

					$items[] = array(
						'level'  => max( 2, min( 6, $level ) ),
						'text'   => $text,
						'anchor' => $anchor,
					);
				}
				if ( ! empty( $node['innerBlocks'] ) ) {
					$walk( $node['innerBlocks'] );
				}
			}
		};
		$walk( $blocks );

		// Keep only H2..H{max_depth}
		$items = array_values( array_filter( $items, function( $h ) use ( $max_depth ) {
			return ( $h['level'] >= 2 && $h['level'] <= $max_depth );
		} ) );

		if ( empty( $items ) ) {
			$wrapper = get_block_wrapper_attributes( array(
				'class' => 'wbp-toc' . ( $collapsed ? ' is-collapsed' : '' ),
			) );
			return '<nav ' . $wrapper . ' aria-label="' . esc_attr__( 'Table of contents', 'wooster-block-patterns' ) . '"><p class="wbp-toc__empty">' . esc_html__( 'No headings found.', 'wooster-block-patterns' ) . '</p></nav>';
		}

		// Assign stable unique ids (prefer heading anchor; otherwise slugified text with de-dupe).
		$slug_counts = array();
		foreach ( $items as $i => $h ) {
			if ( ! empty( $h['anchor'] ) ) {
				$slug = sanitize_title( $h['anchor'] );
			} else {
				$base = sanitize_title( $h['text'] );
				if ( '' === $base ) {
					$base = 'section';
				}
				$slug = $base;
				$k    = 2;
				while ( isset( $slug_counts[ $slug ] ) ) {
					$slug = $base . '-' . $k++;
				}
			}
			$slug_counts[ $slug ] = true;
			$items[ $i ]['id']    = $slug;
		}

		// Build nested ULs starting at H2 depth.
		$base_level = 2;
		$current    = $base_level;
		$list_html  = '<ul class="wbp-toc__list level-' . $base_level . '">';
		foreach ( $items as $h ) {
			$level = max( $base_level, min( 6, (int) $h['level'] ) );
			while ( $current < $level ) {
				$list_html .= '<ul class="wbp-toc__list level-' . ( $current + 1 ) . '">';
				$current++;
			}
			while ( $current > $level ) {
				$list_html .= '</ul>';
				$current--;
			}
			$list_html .= '<li class="wbp-toc__item level-' . $level . '"><a href="#' . esc_attr( $h['id'] ) . '">' . esc_html( $h['text'] ?: __( '(untitled)', 'wooster-block-patterns' ) ) . '</a></li>';
		}
		while ( $current > $base_level ) {
			$list_html .= '</ul>';
			$current--;
		}
		$list_html .= '</ul>';

		$content_id = 'wbp-toc-' . wp_unique_id();
		$title_id  = 'wbp-toc-title-' . $content_id;

		$wrapper = get_block_wrapper_attributes( array(
			'class' => 'wbp-toc' . ( $collapsed ? ' is-collapsed' : '' ),
		) );

		ob_start();
		?>
		<nav <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			aria-label="<?php esc_attr_e( 'Table of contents', 'wooster-block-patterns' ); ?>"
			data-ids="<?php echo esc_attr( wp_json_encode( wp_list_pluck( $items, 'id' ) ) ); ?>"
			data-content="<?php echo esc_attr( $content_id ); ?>"
			data-max-depth="<?php echo esc_attr( $max_depth ); ?>"
		>
			<button type="button"
				class="wbp-toc__toggle"
				aria-controls="<?php echo esc_attr( $content_id ); ?>"
				aria-expanded="<?php echo $collapsed ? 'false' : 'true'; ?>">
				<?php echo esc_html( $title ?: __( 'On this page', 'wooster-block-patterns' ) ); ?>
				<span class="wbp-toc__caret" aria-hidden="true"></span>
			</button>

			<div id="<?php echo esc_attr( $content_id ); ?>" class="wbp-toc__content" <?php echo $collapsed ? 'hidden' : ''; ?>>
				<?php echo $list_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</nav>
		<?php
		return ob_get_clean();
	}
}

return wbp_render_toc_block( $attributes, $content );