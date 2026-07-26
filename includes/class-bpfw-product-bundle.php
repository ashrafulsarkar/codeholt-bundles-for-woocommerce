<?php
/**
 * Bundle product class.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Product_Bundle.
 *
 * Extends WC_Product_Simple so pricing, tax, shipping and AJAX
 * add-to-cart behave natively; only bundle-specific logic is added.
 */
class BPFW_Product_Bundle extends WC_Product_Simple {

	/**
	 * Cached resolved bundled products for this request.
	 *
	 * @var array|null
	 */
	protected $bpfw_resolved = null;

	/**
	 * Product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'bundle';
	}

	/**
	 * Raw bundled items as saved in meta.
	 *
	 * @return array[] Each: ['id' => int, 'qty' => int, 'hidden' => bool]
	 */
	public function get_bundled_items() {
		$items = $this->get_meta( '_bpfw_bundled_items', true );
		return is_array( $items ) ? $items : array();
	}

	/**
	 * Pricing mode: 'auto' or 'fixed'.
	 *
	 * @return string
	 */
	public function get_pricing_mode() {
		$mode = $this->get_meta( '_bpfw_pricing_mode', true );
		return in_array( $mode, array( 'auto', 'fixed' ), true ) ? $mode : 'auto';
	}

	/**
	 * Fixed bundle price (only used in fixed mode).
	 *
	 * @return string
	 */
	public function get_fixed_price() {
		return (string) $this->get_meta( '_bpfw_fixed_price', true );
	}

	/**
	 * Resolved bundled products (invalid/removed products skipped).
	 *
	 * @return array[] Each: ['product' => WC_Product, 'qty' => int, 'hidden' => bool]
	 */
	public function get_bundled_products() {
		if ( null !== $this->bpfw_resolved ) {
			return $this->bpfw_resolved;
		}

		$resolved = array();

		foreach ( $this->get_bundled_items() as $item ) {
			$product = wc_get_product( absint( $item['id'] ) );

			if ( ! $product || 'publish' !== $product->get_status() || ! $product->is_type( 'simple' ) ) {
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
		 * @param BPFW_Product_Bundle $bundle   Bundle product.
		 */
		$this->bpfw_resolved = apply_filters( 'bpfw_bundled_products', $resolved, $this );

		return $this->bpfw_resolved;
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
		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
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
					'bpfw_out_of_stock',
					/* translators: %s: product name. */
					sprintf( __( '"%s" is out of stock and cannot be bundled right now.', 'bundle-product-for-woocommerce' ), $product->get_name() )
				);
			}

			if ( ! $product->has_enough_stock( $needed ) ) {
				return new WP_Error(
					'bpfw_not_enough_stock',
					sprintf(
						/* translators: 1: product name, 2: available quantity. */
						__( 'Not enough stock for "%1$s" — only %2$s available.', 'bundle-product-for-woocommerce' ),
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
			? __( 'Add bundle to cart', 'bundle-product-for-woocommerce' )
			: __( 'Read more', 'woocommerce' );

		/** This filter is documented in WooCommerce. */
		return apply_filters( 'woocommerce_product_add_to_cart_text', $text, $this );
	}
}
