<?php
/**
 * Order integration: line item meta, emails/admin display, child stock sync.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Order.
 */
class CBFW_Order {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		// Fires for classic checkout AND Store API (blocks) checkout.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_line_item_meta' ), 10, 3 );

		add_action( 'woocommerce_reduce_order_stock', array( $this, 'reduce_children_stock' ) );
		add_action( 'woocommerce_restore_order_stock', array( $this, 'restore_children_stock' ) );

		// Hide internal meta keys from admin order screens.
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hidden_meta' ) );
	}

	/**
	 * Copy bundle contents onto the order line item.
	 *
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 */
	public function add_line_item_meta( $item, $cart_item_key, $values ) {
		$product = isset( $values['data'] ) ? $values['data'] : null;
		$bundle  = $product ? cbfw_get_bundle( $product ) : false;

		if ( ! $bundle ) {
			return;
		}

		$contents = array();

		foreach ( $bundle->get_bundled_products() as $bundled ) {
			$contents[] = array(
				'id'   => $bundled['product']->get_id(),
				'name' => $bundled['product']->get_name(),
				'qty'  => $bundled['qty'],
			);
		}

		$pricing = cbfw_get_bundle_pricing( $bundle );

		// Internal meta (analytics + stock handling).
		$item->add_meta_data( '_cbfw_bundled_items', $contents, true );
		$item->add_meta_data( '_cbfw_savings', wc_format_decimal( $pricing['savings'] * $item->get_quantity() ), true );

		// Visible meta — shows automatically in order details, emails and admin.
		$lines = array();
		foreach ( $contents as $content ) {
			/* translators: 1: quantity, 2: product name. */
			$lines[] = sprintf( _x( '%1$s × %2$s', 'bundled item summary', 'codeholt-bundles-for-woocommerce' ), $content['qty'], $content['name'] );
		}
		$item->add_meta_data( __( 'Includes', 'codeholt-bundles-for-woocommerce' ), implode( ', ', $lines ), true );

		/**
		 * Fires after bundle meta is added to an order line item.
		 *
		 * @since 1.0.0
		 *
		 * @param WC_Order_Item_Product $item   Order item.
		 * @param CBFW_Product_Bundle   $bundle Bundle product.
		 */
		do_action( 'cbfw_order_line_item_created', $item, $bundle );
	}

	/**
	 * Reduce bundled product stock when order stock is reduced.
	 *
	 * @param WC_Order $order Order.
	 */
	public function reduce_children_stock( $order ) {
		foreach ( $order->get_items() as $item ) {
			$contents = $item->get_meta( '_cbfw_bundled_items', true );

			if ( ! is_array( $contents ) || ! $contents || 'yes' === $item->get_meta( '_cbfw_stock_reduced', true ) ) {
				continue;
			}

			$notes = array();

			foreach ( $contents as $content ) {
				$child = wc_get_product( $content['id'] );

				if ( ! $child || ! $child->managing_stock() ) {
					continue;
				}

				$qty       = $content['qty'] * $item->get_quantity();
				$new_stock = wc_update_product_stock( $child, $qty, 'decrease' );

				if ( false !== $new_stock ) {
					$notes[] = $child->get_formatted_name() . ' ' . ( $new_stock + $qty ) . '→' . $new_stock;
				}
			}

			$item->update_meta_data( '_cbfw_stock_reduced', 'yes' );
			$item->save();

			if ( $notes ) {
				$order->add_order_note(
					/* translators: %s: list of stock changes. */
					sprintf( __( 'Bundled product stock reduced: %s', 'codeholt-bundles-for-woocommerce' ), implode( ', ', $notes ) )
				);
			}
		}
	}

	/**
	 * Restore bundled product stock when order stock is restored.
	 *
	 * @param WC_Order $order Order.
	 */
	public function restore_children_stock( $order ) {
		foreach ( $order->get_items() as $item ) {
			$contents = $item->get_meta( '_cbfw_bundled_items', true );

			if ( ! is_array( $contents ) || ! $contents || 'yes' !== $item->get_meta( '_cbfw_stock_reduced', true ) ) {
				continue;
			}

			$notes = array();

			foreach ( $contents as $content ) {
				$child = wc_get_product( $content['id'] );

				if ( ! $child || ! $child->managing_stock() ) {
					continue;
				}

				$qty       = $content['qty'] * $item->get_quantity();
				$new_stock = wc_update_product_stock( $child, $qty, 'increase' );

				if ( false !== $new_stock ) {
					$notes[] = $child->get_formatted_name() . ' ' . ( $new_stock - $qty ) . '→' . $new_stock;
				}
			}

			$item->update_meta_data( '_cbfw_stock_reduced', 'no' );
			$item->save();

			if ( $notes ) {
				$order->add_order_note(
					/* translators: %s: list of stock changes. */
					sprintf( __( 'Bundled product stock restored: %s', 'codeholt-bundles-for-woocommerce' ), implode( ', ', $notes ) )
				);
			}
		}
	}

	/**
	 * Keep internal meta out of the admin order item UI.
	 *
	 * @param array $keys Hidden meta keys.
	 * @return array
	 */
	public function hidden_meta( $keys ) {
		$keys[] = '_cbfw_bundled_items';
		$keys[] = '_cbfw_savings';
		$keys[] = '_cbfw_stock_reduced';
		return $keys;
	}
}
