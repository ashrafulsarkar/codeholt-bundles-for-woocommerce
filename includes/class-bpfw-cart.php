<?php
/**
 * Cart integration: validation, item data, stock checks.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Cart.
 */
class BPFW_Cart {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add' ), 10, 3 );
		add_filter( 'woocommerce_update_cart_validation', array( $this, 'validate_update' ), 10, 4 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_item_data' ), 10, 4 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_item_data' ), 10, 2 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ) );
	}

	/**
	 * Validate adding a bundle to the cart (child stock aware).
	 *
	 * @param bool $passed     Validation state.
	 * @param int  $product_id Product ID.
	 * @param int  $quantity   Quantity.
	 * @return bool
	 */
	public function validate_add( $passed, $product_id, $quantity ) {
		$bundle = bpfw_get_bundle( $product_id );

		if ( ! $bundle || ! $passed ) {
			return $passed;
		}

		if ( ! $bundle->get_bundled_products() ) {
			wc_add_notice( __( 'This bundle has no products configured yet.', 'codeholt-bundles-for-woocommerce' ), 'error' );
			return false;
		}

		return $this->validate_requirements(
			bpfw_collect_child_requirements( WC()->cart, $product_id, $quantity )
		);
	}

	/**
	 * Validate cart quantity updates for bundle items.
	 *
	 * @param bool   $passed        Validation state.
	 * @param string $cart_item_key Cart item key.
	 * @param array  $values        Cart item values.
	 * @param int    $quantity      New quantity.
	 * @return bool
	 */
	public function validate_update( $passed, $cart_item_key, $values, $quantity ) {
		if ( ! $passed || empty( $values['data'] ) || ! bpfw_is_bundle( $values['data'] ) ) {
			return $passed;
		}

		return $this->validate_requirements(
			bpfw_collect_child_requirements( WC()->cart, null, 0, $cart_item_key, $quantity )
		);
	}

	/**
	 * Find the first stock problem in a requirement map.
	 *
	 * @param array $needs child_id => required qty.
	 * @return string Error message, or empty string when everything is available.
	 */
	protected function requirement_error( $needs ) {
		foreach ( $needs as $child_id => $required ) {
			$child = wc_get_product( $child_id );

			if ( ! $child ) {
				continue;
			}

			if ( ! $child->is_in_stock() ) {
				/* translators: %s: product name. */
				return sprintf( __( '"%s" is out of stock, so this bundle cannot be purchased right now.', 'codeholt-bundles-for-woocommerce' ), $child->get_name() );
			}

			if ( $child->managing_stock() && ! $child->backorders_allowed() && (float) $child->get_stock_quantity() < $required ) {
				return sprintf(
					/* translators: 1: product name, 2: quantity available, 3: quantity required. */
					__( 'Not enough stock for "%1$s" — only %2$s left, but your cart needs %3$s.', 'codeholt-bundles-for-woocommerce' ),
					$child->get_name(),
					wc_stock_amount( $child->get_stock_quantity() ),
					wc_stock_amount( $required )
				);
			}
		}

		return '';
	}

	/**
	 * Check every requirement map entry against live stock, adding a notice on failure.
	 *
	 * @param array $needs child_id => required qty.
	 * @return bool
	 */
	protected function validate_requirements( $needs ) {
		$error = $this->requirement_error( $needs );

		if ( $error ) {
			wc_add_notice( $error, 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Snapshot bundled items into the cart item (for display + merging).
	 *
	 * Also enforces child stock here: this filter runs inside
	 * WC_Cart::add_to_cart(), so programmatic adds that skip the request-level
	 * validation filter are still blocked (WooCommerce catches the exception).
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id     Product ID.
	 * @param int   $variation_id   Variation ID (unused for bundles).
	 * @param int   $quantity       Quantity being added.
	 * @return array
	 *
	 * @throws Exception When bundled products lack stock.
	 */
	public function add_item_data( $cart_item_data, $product_id, $variation_id = 0, $quantity = 1 ) {
		$bundle = bpfw_get_bundle( $product_id );

		if ( ! $bundle ) {
			return $cart_item_data;
		}

		if ( isset( WC()->cart ) ) {
			$error = $this->requirement_error(
				bpfw_collect_child_requirements( WC()->cart, $product_id, max( 1, (int) $quantity ) )
			);

			if ( $error ) {
				throw new Exception( esc_html( $error ) );
			}
		}

		$snapshot = array();

		foreach ( $bundle->get_bundled_products() as $item ) {
			$snapshot[] = array(
				'id'   => $item['product']->get_id(),
				'name' => $item['product']->get_name(),
				'qty'  => $item['qty'],
			);
		}

		$cart_item_data['bpfw_items'] = $snapshot;

		return $cart_item_data;
	}

	/**
	 * Show bundled products + savings under the cart item name.
	 * Works in both classic cart and Cart/Checkout Blocks (Store API).
	 *
	 * @param array $item_data Existing item data.
	 * @param array $cart_item Cart item.
	 * @return array
	 */
	public function display_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['bpfw_items'] ) || ! is_array( $cart_item['bpfw_items'] ) ) {
			return $item_data;
		}

		$lines = array();

		foreach ( $cart_item['bpfw_items'] as $item ) {
			/* translators: 1: quantity, 2: product name. */
			$lines[] = sprintf( _x( '%1$s × %2$s', 'bundled item summary', 'codeholt-bundles-for-woocommerce' ), $item['qty'], $item['name'] );
		}

		$item_data[] = array(
			'key'   => __( 'Includes', 'codeholt-bundles-for-woocommerce' ),
			'value' => implode( ', ', $lines ),
		);

		$bundle = isset( $cart_item['data'] ) ? bpfw_get_bundle( $cart_item['data'] ) : false;

		if ( $bundle ) {
			$pricing = bpfw_get_bundle_pricing( $bundle );

			if ( $pricing['savings'] > 0 ) {
				$item_data[] = array(
					'key'   => __( 'You save', 'codeholt-bundles-for-woocommerce' ),
					'value' => wp_strip_all_tags( wc_price( $pricing['savings'] * $cart_item['quantity'] ) ),
				);
			}
		}

		return $item_data;
	}

	/**
	 * Re-validate child stock for every bundle on cart/checkout load.
	 */
	public function check_cart_items() {
		$has_bundle = false;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( bpfw_is_bundle( $cart_item['data'] ) ) {
				$has_bundle = true;
				break;
			}
		}

		if ( $has_bundle ) {
			$this->validate_requirements( bpfw_collect_child_requirements( WC()->cart ) );
		}
	}
}
