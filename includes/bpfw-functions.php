<?php
/**
 * Helper functions.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether a product is a bundle.
 *
 * @param mixed $product Product object or ID.
 * @return bool
 */
function bpfw_is_bundle( $product ) {
	$product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product( $product );
	return $product && 'bundle' === $product->get_type();
}

/**
 * Get a bundle product object, or false.
 *
 * @param mixed $product Product object or ID.
 * @return BPFW_Product_Bundle|false
 */
function bpfw_get_bundle( $product ) {
	$product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product( $product );
	return ( $product && 'bundle' === $product->get_type() ) ? $product : false;
}

/**
 * Get all published bundle products as id => name pairs (for selects).
 *
 * @return array
 */
function bpfw_get_bundle_choices() {
	$ids = wc_get_products(
		array(
			'type'    => 'bundle',
			'status'  => 'publish',
			'limit'   => 200,
			'orderby' => 'title',
			'order'   => 'ASC',
			'return'  => 'ids',
		)
	);

	$choices = array();
	foreach ( $ids as $id ) {
		$choices[ $id ] = get_the_title( $id );
	}

	return $choices;
}

/**
 * Default plugin settings.
 *
 * @return array
 */
function bpfw_get_default_settings() {
	/**
	 * Filter the default plugin settings.
	 *
	 * @since 1.1.0
	 *
	 * @param array $defaults Default settings.
	 */
	return apply_filters(
		'bpfw_default_settings',
		array(
			'product_layout'     => 'list',
			'card_layout'        => 'card',
			'included_title'     => __( "What's included", 'bundle-product-for-woocommerce' ),
			'show_savings_badge' => 'yes',
			'accent_color'       => '#2f4df5',
			'savings_color'      => '#00a32a',
			'border_color'       => '#e3e5e8',
			'border_radius'      => 10,
		)
	);
}

/**
 * Get all plugin settings merged with defaults.
 *
 * @return array
 */
function bpfw_get_settings() {
	$saved = get_option( 'bpfw_settings', array() );

	/**
	 * Filter the resolved plugin settings.
	 *
	 * @since 1.1.0
	 *
	 * @param array $settings Settings merged with defaults.
	 */
	return apply_filters(
		'bpfw_settings',
		wp_parse_args( is_array( $saved ) ? $saved : array(), bpfw_get_default_settings() )
	);
}

/**
 * Get a single plugin setting.
 *
 * @param string $key      Setting key.
 * @param mixed  $fallback Value when the key is unknown.
 * @return mixed
 */
function bpfw_get_setting( $key, $fallback = null ) {
	$settings = bpfw_get_settings();
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $fallback;
}

/**
 * Resolve the product page layout for a bundle: per-product override
 * (`_bpfw_layout` meta) falling back to the global setting.
 *
 * @param BPFW_Product_Bundle $bundle Bundle product.
 * @return string 'list' | 'grid' | 'compact'
 */
function bpfw_get_bundle_layout( $bundle ) {
	$allowed = array( 'list', 'grid', 'compact' );
	$layout  = $bundle->get_meta( '_bpfw_layout' );

	if ( ! in_array( $layout, $allowed, true ) ) {
		$layout = bpfw_get_setting( 'product_layout', 'list' );
	}

	if ( ! in_array( $layout, $allowed, true ) ) {
		$layout = 'list';
	}

	/**
	 * Filter the resolved bundle page layout.
	 *
	 * @since 1.1.0
	 *
	 * @param string              $layout Layout slug.
	 * @param BPFW_Product_Bundle $bundle Bundle product.
	 */
	return apply_filters( 'bpfw_bundle_layout', $layout, $bundle );
}

/**
 * CSS variable overrides generated from the design settings.
 * Attached inline to the frontend stylesheet.
 *
 * @return string
 */
function bpfw_get_inline_css() {
	$settings = bpfw_get_settings();
	$defaults = bpfw_get_default_settings();

	$vars = array(
		'--bpfw-accent'  => sanitize_hex_color( $settings['accent_color'] ) ? sanitize_hex_color( $settings['accent_color'] ) : $defaults['accent_color'],
		'--bpfw-savings' => sanitize_hex_color( $settings['savings_color'] ) ? sanitize_hex_color( $settings['savings_color'] ) : $defaults['savings_color'],
		'--bpfw-border'  => sanitize_hex_color( $settings['border_color'] ) ? sanitize_hex_color( $settings['border_color'] ) : $defaults['border_color'],
		'--bpfw-radius'  => absint( $settings['border_radius'] ) . 'px',
	);

	$css = '';
	foreach ( $vars as $name => $value ) {
		$css .= $name . ':' . $value . ';';
	}

	/**
	 * Filter the inline CSS generated from the design settings.
	 *
	 * @since 1.1.0
	 *
	 * @param string $css      Inline CSS.
	 * @param array  $settings Plugin settings.
	 */
	return apply_filters( 'bpfw_inline_css', ':root{' . $css . '}', $settings );
}

/**
 * Get pricing totals for a bundle.
 *
 * @param BPFW_Product_Bundle $bundle Bundle product.
 * @return array{regular:float,active:float,price:float,savings:float,percent:float}
 */
function bpfw_get_bundle_pricing( $bundle ) {
	$totals  = $bundle->get_items_totals();
	$price   = (float) $bundle->get_price();
	$regular = (float) $totals['regular'];
	$savings = max( 0, $regular - $price );
	$percent = $regular > 0 ? round( ( $savings / $regular ) * 100 ) : 0;

	/**
	 * Filter the computed bundle pricing summary.
	 *
	 * @since 1.0.0
	 *
	 * @param array               $pricing Pricing data.
	 * @param BPFW_Product_Bundle $bundle  Bundle product.
	 */
	return apply_filters(
		'bpfw_bundle_pricing',
		array(
			'regular' => $regular,
			'active'  => (float) $totals['active'],
			'price'   => $price,
			'savings' => $savings,
			'percent' => $percent,
		),
		$bundle
	);
}

/**
 * Load a plugin template, allowing theme overrides in
 * yourtheme/bundle-product-for-woocommerce/{template}.
 *
 * @param string $template Template file relative to templates dir.
 * @param array  $args     Variables passed to the template.
 */
function bpfw_get_template( $template, $args = array() ) {
	wc_get_template(
		$template,
		$args,
		'bundle-product-for-woocommerce/',
		BPFW_PLUGIN_DIR . 'templates/'
	);
}

/**
 * Render a bundle card (used by shortcode, block and Elementor widget).
 *
 * @param int   $bundle_id Bundle product ID.
 * @param array $args      Display args.
 * @return string HTML.
 */
function bpfw_render_bundle_card( $bundle_id, $args = array() ) {
	$bundle = bpfw_get_bundle( absint( $bundle_id ) );

	if ( ! $bundle || 'publish' !== $bundle->get_status() ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'layout'     => bpfw_get_setting( 'card_layout', 'card' ), // card | list.
			'show_image' => true,
			'show_items' => true,
		)
	);

	// Frontend styles are needed wherever a card renders.
	wp_enqueue_style( 'bpfw-frontend' );

	ob_start();
	bpfw_get_template(
		'bundle-card.php',
		array(
			'bundle' => $bundle,
			'args'   => $args,
		)
	);

	/**
	 * Filter the rendered bundle card HTML.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $html   Card HTML.
	 * @param BPFW_Product_Bundle $bundle Bundle product.
	 * @param array               $args   Display args.
	 */
	return apply_filters( 'bpfw_bundle_card_html', ob_get_clean(), $bundle, $args );
}

/**
 * Build a short, human readable summary of bundled items.
 * Example: "2 × Cake Bars, 1 × Stadium Sauce".
 *
 * @param BPFW_Product_Bundle $bundle       Bundle product.
 * @param bool                $include_hidden Include hidden items.
 * @return string
 */
function bpfw_get_items_summary( $bundle, $include_hidden = true ) {
	$parts = array();

	foreach ( $bundle->get_bundled_products() as $item ) {
		if ( ! $include_hidden && $item['hidden'] ) {
			continue;
		}
		/* translators: 1: quantity, 2: product name. */
		$parts[] = sprintf( _x( '%1$s × %2$s', 'bundled item summary', 'bundle-product-for-woocommerce' ), $item['qty'], $item['product']->get_name() );
	}

	return implode( ', ', $parts );
}

/**
 * Map of child product requirements for the current cart contents.
 *
 * @param WC_Cart  $cart          Cart object.
 * @param int|null $extra_bundle  Bundle ID being added (optional).
 * @param int      $extra_qty     Quantity being added.
 * @param string   $skip_item_key Cart item key to exclude (when re-validating an update).
 * @param int      $override_qty  Quantity to use for the skipped item instead.
 * @return array child_product_id => required quantity
 */
function bpfw_collect_child_requirements( $cart, $extra_bundle = null, $extra_qty = 0, $skip_item_key = '', $override_qty = 0 ) {
	$needs = array();

	$add = function ( $bundle, $qty ) use ( &$needs ) {
		foreach ( $bundle->get_bundled_products() as $item ) {
			$pid = $item['product']->get_stock_managed_by_id();
			if ( ! isset( $needs[ $pid ] ) ) {
				$needs[ $pid ] = 0;
			}
			$needs[ $pid ] += $item['qty'] * $qty;
		}
	};

	foreach ( $cart->get_cart() as $key => $cart_item ) {
		$product = $cart_item['data'];
		$qty     = ( $key === $skip_item_key ) ? $override_qty : $cart_item['quantity'];

		if ( bpfw_is_bundle( $product ) ) {
			$add( $product, $qty );
		} elseif ( $product && $product->managing_stock() ) {
			$pid = $product->get_stock_managed_by_id();
			if ( ! isset( $needs[ $pid ] ) ) {
				$needs[ $pid ] = 0;
			}
			$needs[ $pid ] += $qty;
		}
	}

	if ( $extra_bundle ) {
		$bundle = bpfw_get_bundle( $extra_bundle );
		if ( $bundle ) {
			$add( $bundle, max( 1, (int) $extra_qty ) );
		}
	}

	return $needs;
}
