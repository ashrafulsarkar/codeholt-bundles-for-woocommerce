<?php
/**
 * Bundle product class.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Product_Bundle.
 *
 * Extends WC_Product_Simple so pricing, tax, shipping and AJAX
 * add-to-cart behave natively; only bundle-specific logic is added.
 */
class CBFW_Product_Bundle extends WC_Product_Simple {

	/**
	 * Cached resolved bundled products for this request.
	 *
	 * @var array|null
	 */
	protected $cbfw_resolved = null;

	/**
	 * Product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'cbfw_bundle';
	}

	/**
	 * Raw bundled items as saved in meta.
	 *
	 * @return array[] Each: ['id' => int, 'qty' => int, 'hidden' => bool]
	 */
	public function get_bundled_items() {
		$items = $this->get_meta( '_cbfw_bundled_items', true );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Pricing mode: 'auto' or 'fixed'.
	 *
	 * @return string
	 */
	public function get_pricing_mode() {
		$mode = $this->get_meta( '_cbfw_pricing_mode', true );
		return in_array( $mode, cbfw_get_pricing_modes(), true ) ? $mode : 'auto';
	}

	/**
	 * Fixed bundle price (only used in fixed mode).
	 *
	 * @return string
	 */
	public function get_fixed_price() {
		return (string) $this->get_meta( '_cbfw_fixed_price', true );
	}

	/**
	 * Resolved bundled products (invalid/removed products skipped).
	 *
	 * @return array[] Each: ['product' => WC_Product, 'qty' => int, 'hidden' => bool]
	 */
	public function get_bundled_products() {
		if ( null !== $this->cbfw_resolved ) {
			return $this->cbfw_resolved;
		}

		$resolved = array();

		foreach ( $this->get_bundled_items() as $item ) {
			$product = wc_get_product( absint( $item['id'] ) );

			// Deliberately permissive: a saved child only drops out once it is
			// gone, unpublished, or would recurse — not because its price was
			// temporarily cleared.
			if ( ! $product || 'publish' !== $product->get_status() || cbfw_is_bundle( $product ) ) {
				continue;
			}

			$resolved[] = array(
				'product' => $product,
				'qty'     => max( 1, absint( $item['qty'] ) ),
				'hidden'  => ! empty( $item['hidden'] ),
			);
		}

		/**
		 * Filter the resolved bundled products.
		 *
		 * @since 1.0.0
		 *
		 * @param array               $resolved Resolved items.
		 * @param CBFW_Product_Bundle $bundle   Bundle product.
		 */
		$this->cbfw_resolved = apply_filters( 'cbfw_bundled_products', $resolved, $this );

		return $this->cbfw_resolved;
	}

	/**
	 * Sum of child regular and active prices.
	 *
	 * @return array{regular:float,active:float}
	 */
	public function get_items_totals() {
		$regular = 0.0;
		$active  = 0.0;

		foreach ( $this->get_bundled_products() as $item ) {
			$regular += (float) $item['product']->get_regular_price() * $item['qty'];
			$price    = $item['product']->get_price();
			$active  += ( '' !== $price ? (float) $price : (float) $item['product']->get_regular_price() ) * $item['qty'];
		}

		return array(
			'regular' => $regular,
			'active'  => $active,
		);
	}

	/**
	 * A bundle is purchasable when it has items and a price.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		$purchasable = 'publish' === $this->get_status()
			&& '' !== $this->get_price()
			&& count( $this->get_bundled_products() ) > 0;

		/** This filter is documented in WooCommerce. */
		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce filter, must keep its original name.
	}

	/**
	 * Check that every bundled item has enough stock for a quantity of bundles.
	 *
	 * @param int $bundle_qty Number of bundles required.
	 * @return true|WP_Error
	 */
	public function has_enough_stock( $bundle_qty = 1 ) {
		foreach ( $this->get_bundled_products() as $item ) {
			$product = $item['product'];
			$needed  = $item['qty'] * max( 1, (int) $bundle_qty );

			if ( ! $product->is_in_stock() ) {
				return new WP_Error(
					'cbfw_out_of_stock',
					/* translators: %s: product name. */
					sprintf( __( '"%s" is out of stock and cannot be bundled right now.', 'codeholt-bundles-for-woocommerce' ), $product->get_name() )
				);
			}

			if ( ! $product->has_enough_stock( $needed ) ) {
				return new WP_Error(
					'cbfw_not_enough_stock',
					sprintf(
						/* translators: 1: product name, 2: available quantity. */
						__( 'Not enough stock for "%1$s" — only %2$s available.', 'codeholt-bundles-for-woocommerce' ),
						$product->get_name(),
						wc_stock_amount( $product->get_stock_quantity() )
					)
				);
			}
		}

		return true;
	}

	/**
	 * Maximum bundles purchasable based on child stock (0 = none, null = unlimited).
	 *
	 * @return int|null
	 */
	public function get_max_purchasable() {
		$max = null;

		foreach ( $this->get_bundled_products() as $item ) {
			$product = $item['product'];

			if ( ! $product->is_in_stock() ) {
				return 0;
			}

			if ( $product->managing_stock() && ! $product->backorders_allowed() ) {
				$possible = (int) floor( (float) $product->get_stock_quantity() / $item['qty'] );
				$max      = null === $max ? $possible : min( $max, $possible );
			}
		}

		if ( $this->managing_stock() && ! $this->backorders_allowed() ) {
			$own = (int) $this->get_stock_quantity();
			$max = null === $max ? $own : min( $max, $own );
		}

		return $max;
	}

	/**
	 * Add-to-cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		$text = $this->is_purchasable() && $this->is_in_stock()
			? __( 'Add bundle to cart', 'codeholt-bundles-for-woocommerce' )
			: __( 'Read more', 'codeholt-bundles-for-woocommerce' );

		/** This filter is documented in WooCommerce. */
		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce filter, must keep its original name.
	}
}
