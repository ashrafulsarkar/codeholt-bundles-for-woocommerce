<?php
/**
 * Helper functions.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Check whether a product is a bundle.
 *
 * @param mixed $product Product object or ID.
 * @return bool
 */
function cbfw_is_bundle( $product ) {
	$product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product( $product );
	return $product && 'cbfw_bundle' === $product->get_type();
}

/**
 * Get a bundle product object, or false.
 *
 * @param mixed $product Product object or ID.
 * @return CBFW_Product_Bundle|false
 */
function cbfw_get_bundle( $product ) {
	$product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product( $product );
	return ( $product && 'cbfw_bundle' === $product->get_type() ) ? $product : false;
}

/**
 * Get all published bundle products as id => name pairs (for selects).
 *
 * @return array
 */
function cbfw_get_bundle_choices() {
	/**
	 * How many bundles to load into editor dropdowns. This is only there to
	 * keep the block/widget selects from querying an unbounded number of
	 * products on very large stores — raise it if you have more bundles.
	 *
	 * @since 1.0.0
	 *
	 * @param int $limit Number of bundles to load (-1 for all).
	 */
	$limit = (int) apply_filters( 'cbfw_bundle_choices_limit', 200 );

	$ids = wc_get_products(
		array(
			'type'    => 'cbfw_bundle',
			'status'  => 'publish',
			'limit'   => $limit,
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
 * Registered pricing mode slugs.
 *
 * @return string[]
 */
function cbfw_get_pricing_modes() {
	/**
	 * Filter the registered pricing mode slugs.
	 * Custom modes must also hook `cbfw_custom_mode_prices` to compute prices.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $modes Mode slugs.
	 */
	return apply_filters( 'cbfw_pricing_modes', array( 'auto', 'fixed' ) );
}

/**
 * Pricing mode options for the admin select (slug => label).
 *
 * @return array
 */
function cbfw_get_pricing_mode_options() {
	/**
	 * Filter the pricing mode dropdown options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $options slug => label.
	 */
	return apply_filters(
		'cbfw_pricing_mode_options',
		array(
			'auto'  => __( 'Auto calculate (sum of products)', 'codeholt-bundles-for-woocommerce' ),
			'fixed' => __( 'Fixed bundle price', 'codeholt-bundles-for-woocommerce' ),
		)
	);
}

/**
 * Whether a product can be added to a bundle.
 *
 * Any purchasable product qualifies — simple products, variations, and any
 * other purchasable type a third party registers. Two technical exclusions
 * apply: a bundle cannot contain another bundle (that would recurse when
 * totalling prices and stock), and a product WooCommerce reports as not
 * purchasable has no price to total (variable parents, grouped and external
 * products, or a product with an empty price) — pick a specific variation
 * rather than its parent.
 *
 * @param mixed $product Product object or ID.
 * @return bool
 */
function cbfw_can_bundle_product( $product ) {
	$product = is_a( $product, 'WC_Product' ) ? $product : wc_get_product( $product );

	$can = $product
		&& ! cbfw_is_bundle( $product )
		&& 'publish' === $product->get_status()
		&& $product->is_purchasable();

	/**
	 * Filter whether a product can be bundled.
	 *
	 * @since 1.0.0
	 *
	 * @param bool            $can     Whether the product can be bundled.
	 * @param WC_Product|null $product Product object.
	 */
	return (bool) apply_filters( 'cbfw_can_bundle_product', $can, $product );
}

/**
 * Registered product page layouts (slug => label).
 * Table and Compact ship with the plugin. Other plugins can register more
 * layouts via the `cbfw_product_layouts` filter and render them themselves.
 *
 * @return array
 */
function cbfw_get_product_layouts() {
	$layouts = array(
		'table'   => __( 'Table', 'codeholt-bundles-for-woocommerce' ),
		'compact' => __( 'Compact', 'codeholt-bundles-for-woocommerce' ),
	);

	/**
	 * Filter the registered product page layouts.
	 * Custom layouts render the standard bundled-items markup with a
	 * `cbfw-layout-{slug}` wrapper class — style them via CSS.
	 *
	 * @since 1.0.0
	 *
	 * @param array $layouts slug => label.
	 */
	return apply_filters( 'cbfw_product_layouts', $layouts );
}

/**
 * Default plugin settings.
 *
 * @return array
 */
function cbfw_get_default_settings() {
	/**
	 * Filter the default plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $defaults Default settings.
	 */
	return apply_filters(
		'cbfw_default_settings',
		array(
			'product_layout'     => 'table',
			'card_layout'        => 'card',
			'included_title'     => __( "What's included", 'codeholt-bundles-for-woocommerce' ),
			'show_savings_badge' => 'yes',
		)
	);
}

/**
 * Get all plugin settings merged with defaults.
 *
 * @return array
 */
function cbfw_get_settings() {
	$saved = get_option( 'cbfw_settings', array() );

	/**
	 * Filter the resolved plugin settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings Settings merged with defaults.
	 */
	return apply_filters(
		'cbfw_settings',
		wp_parse_args( is_array( $saved ) ? $saved : array(), cbfw_get_default_settings() )
	);
}

/**
 * Get a single plugin setting.
 *
 * @param string $key      Setting key.
 * @param mixed  $fallback Value when the key is unknown.
 * @return mixed
 */
function cbfw_get_setting( $key, $fallback = null ) {
	$settings = cbfw_get_settings();
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $fallback;
}

/**
 * Resolve the product page layout for a bundle: per-product override
 * (`_cbfw_layout` meta) falling back to the global setting.
 *
 * @param CBFW_Product_Bundle $bundle Bundle product.
 * @return string Layout slug (see cbfw_get_product_layouts()).
 */
function cbfw_get_bundle_layout( $bundle ) {
	$allowed = array_keys( cbfw_get_product_layouts() );
	$layout  = $bundle->get_meta( '_cbfw_layout' );

	if ( ! in_array( $layout, $allowed, true ) ) {
		$layout = cbfw_get_setting( 'product_layout', 'table' );
	}

	// A layout registered by another plugin must fall back once that plugin is gone.
	if ( ! in_array( $layout, $allowed, true ) ) {
		$layout = 'table';
	}

	/**
	 * Filter the resolved bundle page layout.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $layout Layout slug.
	 * @param CBFW_Product_Bundle $bundle Bundle product.
	 */
	return apply_filters( 'cbfw_bundle_layout', $layout, $bundle );
}

/**
 * CSS variable overrides, attached inline to the frontend stylesheet.
 * Nothing is emitted unless another plugin or theme filters `cbfw_inline_css`;
 * otherwise the built-in defaults in frontend.css (:root) apply.
 *
 * @return string
 */
function cbfw_get_inline_css() {
	$settings = cbfw_get_settings();

	/**
	 * Filter the inline CSS attached to the frontend stylesheet.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css      Inline CSS.
	 * @param array  $settings Plugin settings.
	 */
	return apply_filters( 'cbfw_inline_css', '', $settings );
}

/**
 * Get pricing totals for a bundle.
 *
 * @param CBFW_Product_Bundle $bundle Bundle product.
 * @return array{regular:float,active:float,price:float,savings:float,percent:float}
 */
function cbfw_get_bundle_pricing( $bundle ) {
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
	 * @param CBFW_Product_Bundle $bundle  Bundle product.
	 */
	return apply_filters(
		'cbfw_bundle_pricing',
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
 * yourtheme/codeholt-bundles-for-woocommerce/{template}.
 *
 * @param string $template Template file relative to templates dir.
 * @param array  $args     Variables passed to the template.
 */
function cbfw_get_template( $template, $args = array() ) {
	wc_get_template(
		$template,
		$args,
		'codeholt-bundles-for-woocommerce/',
		CBFW_PLUGIN_DIR . 'templates/'
	);
}

/**
 * Render a bundle card (used by shortcode, block and Elementor widget).
 *
 * @param int   $bundle_id Bundle product ID.
 * @param array $args      Display args.
 * @return string HTML.
 */
function cbfw_render_bundle_card( $bundle_id, $args = array() ) {
	$bundle = cbfw_get_bundle( absint( $bundle_id ) );

	if ( ! $bundle || 'publish' !== $bundle->get_status() ) {
		return '';
	}

	$args = wp_parse_args(
		$args,
		array(
			'layout'     => cbfw_get_setting( 'card_layout', 'card' ), // card | list.
			'show_image' => true,
			'show_items' => true,
		)
	);

	// Frontend styles are needed wherever a card renders.
	wp_enqueue_style( 'cbfw-frontend' );

	ob_start();
	cbfw_get_template(
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
	 * @param CBFW_Product_Bundle $bundle Bundle product.
	 * @param array               $args   Display args.
	 */
	return apply_filters( 'cbfw_bundle_card_html', ob_get_clean(), $bundle, $args );
}

/**
 * Build a short, human readable summary of bundled items.
 * Example: "2 × Cake Bars, 1 × Stadium Sauce".
 *
 * @param CBFW_Product_Bundle $bundle       Bundle product.
 * @param bool                $include_hidden Include hidden items.
 * @return string
 */
function cbfw_get_items_summary( $bundle, $include_hidden = true ) {
	$parts = array();

	foreach ( $bundle->get_bundled_products() as $item ) {
		if ( ! $include_hidden && $item['hidden'] ) {
			continue;
		}
		/* translators: 1: quantity, 2: product name. */
		$parts[] = sprintf( _x( '%1$s × %2$s', 'bundled item summary', 'codeholt-bundles-for-woocommerce' ), $item['qty'], $item['product']->get_name() );
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
function cbfw_collect_child_requirements( $cart, $extra_bundle = null, $extra_qty = 0, $skip_item_key = '', $override_qty = 0 ) {
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

		if ( cbfw_is_bundle( $product ) ) {
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
		$bundle = cbfw_get_bundle( $extra_bundle );
		if ( $bundle ) {
			$add( $bundle, max( 1, (int) $extra_qty ) );
		}
	}

	return $needs;
}
