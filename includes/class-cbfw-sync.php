<?php
/**
 * Keeps bundle price & stock status in sync with bundled products.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Sync.
 */
class CBFW_Sync {

	/**
	 * Guard against recursive syncs within one request.
	 *
	 * @var array
	 */
	protected static $syncing = array();

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'woocommerce_update_product', array( $this, 'on_child_update' ), 20, 2 );
		// Variations are saved through their own data store and fire their own hook.
		add_action( 'woocommerce_update_product_variation', array( $this, 'on_child_update' ), 20, 2 );
		add_action( 'woocommerce_product_set_stock', array( $this, 'on_child_stock' ), 20 );
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'on_child_stock_status' ), 20, 3 );
		add_action( 'wp_trash_post', array( $this, 'on_child_trashed' ) );
	}

	/**
	 * Compute and apply price + stock status on a bundle object (no save).
	 *
	 * @param CBFW_Product_Bundle $bundle Bundle product.
	 */
	public static function apply_pricing( $bundle ) {
		$totals        = $bundle->get_items_totals();
		$regular_total = wc_format_decimal( $totals['regular'] );
		$active_total  = wc_format_decimal( $totals['active'] );

		if ( ! $bundle->get_bundled_products() ) {
			$bundle->set_regular_price( '' );
			$bundle->set_sale_price( '' );
			$bundle->set_price( '' );
			return;
		}

		$mode = $bundle->get_pricing_mode();

		/**
		 * Allow custom pricing modes (registered via `cbfw_pricing_modes`) to
		 * supply computed prices. Return array{regular:string,sale:string}
		 * to take over; return null to fall through to auto/fixed.
		 *
		 * @since 1.0.0
		 *
		 * @param array|null          $prices Computed prices or null.
		 * @param CBFW_Product_Bundle $bundle Bundle product.
		 * @param string              $mode   Pricing mode slug.
		 * @param array               $totals Items totals (regular/active).
		 */
		$custom = apply_filters( 'cbfw_custom_mode_prices', null, $bundle, $mode, $totals );

		if ( is_array( $custom ) && isset( $custom['regular'] ) ) {
			$regular = wc_format_decimal( $custom['regular'] );
			$sale    = isset( $custom['sale'] ) && '' !== $custom['sale'] ? wc_format_decimal( $custom['sale'] ) : '';

			$bundle->set_regular_price( $regular );

			if ( '' !== $sale && (float) $sale < (float) $regular ) {
				$bundle->set_sale_price( $sale );
				$bundle->set_price( $sale );
			} else {
				$bundle->set_sale_price( '' );
				$bundle->set_price( $regular );
			}
			return;
		}

		if ( 'fixed' === $mode && '' !== $bundle->get_fixed_price() ) {
			$fixed = wc_format_decimal( $bundle->get_fixed_price() );

			if ( (float) $fixed < (float) $regular_total ) {
				$bundle->set_regular_price( $regular_total );
				$bundle->set_sale_price( $fixed );
				$bundle->set_price( $fixed );
			} else {
				$bundle->set_regular_price( $fixed );
				$bundle->set_sale_price( '' );
				$bundle->set_price( $fixed );
			}
		} else {
			// Auto mode: regular = sum of regular prices, active = sum of current prices.
			$bundle->set_regular_price( $regular_total );

			if ( (float) $active_total < (float) $regular_total ) {
				$bundle->set_sale_price( $active_total );
				$bundle->set_price( $active_total );
			} else {
				$bundle->set_sale_price( '' );
				$bundle->set_price( $regular_total );
			}
		}
	}

	/**
	 * Compute and apply stock status from children (no save).
	 *
	 * @param CBFW_Product_Bundle $bundle Bundle product.
	 */
	public static function apply_stock_status( $bundle ) {
		$items = $bundle->get_bundled_products();

		if ( ! $items ) {
			$bundle->set_stock_status( 'outofstock' );
			return;
		}

		$status = 'instock';

		foreach ( $items as $item ) {
			$product = $item['product'];

			if ( ! $product->is_in_stock() || ! $product->has_enough_stock( $item['qty'] ) ) {
				$status = 'outofstock';
				break;
			}
		}

		// A bundle managing its own stock can also be out of stock by itself.
		if ( 'instock' === $status && $bundle->managing_stock() && ! $bundle->backorders_allowed() && (int) $bundle->get_stock_quantity() <= 0 ) {
			$status = 'outofstock';
		}

		$bundle->set_stock_status( $status );
	}

	/**
	 * Fully resync one bundle and persist it.
	 *
	 * @param int $bundle_id Bundle product ID.
	 */
	public static function sync_bundle( $bundle_id ) {
		if ( isset( self::$syncing[ $bundle_id ] ) ) {
			return;
		}
		self::$syncing[ $bundle_id ] = true;

		$bundle = cbfw_get_bundle( $bundle_id );

		if ( $bundle ) {
			self::apply_pricing( $bundle );
			self::apply_stock_status( $bundle );
			$bundle->save();

			/**
			 * Fires after a bundle has been resynced with its children.
			 *
			 * @since 1.0.0
			 *
			 * @param CBFW_Product_Bundle $bundle Bundle product.
			 */
			do_action( 'cbfw_bundle_synced', $bundle );
		}

		unset( self::$syncing[ $bundle_id ] );
	}

	/**
	 * Find bundle IDs that contain a given child product.
	 *
	 * @param int $child_id Child product ID.
	 * @return int[]
	 */
	public static function get_containing_bundles( $child_id ) {
		return get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'   => '_cbfw_contains',
						'value' => (string) $child_id,
					),
				),
			)
		);
	}

	/**
	 * Resync all bundles containing a child.
	 *
	 * @param int $child_id Child product ID.
	 */
	protected function resync_parents( $child_id ) {
		foreach ( self::get_containing_bundles( $child_id ) as $bundle_id ) {
			self::sync_bundle( $bundle_id );
		}
	}

	/**
	 * Child product saved → resync parents.
	 *
	 * @param int        $product_id Product ID.
	 * @param WC_Product $product    Product object.
	 */
	public function on_child_update( $product_id, $product = null ) {
		$product = $product ? $product : wc_get_product( $product_id );

		if ( ! $product || 'cbfw_bundle' === $product->get_type() ) {
			return;
		}

		$this->resync_parents( $product_id );
	}

	/**
	 * Child stock quantity changed → resync parents' stock status.
	 *
	 * @param WC_Product $product Product object.
	 */
	public function on_child_stock( $product ) {
		if ( ! $product || 'cbfw_bundle' === $product->get_type() ) {
			return;
		}

		$this->resync_parents( $product->get_id() );
	}

	/**
	 * Child stock status changed → resync parents' stock status.
	 *
	 * @param int        $product_id Product ID.
	 * @param string     $status     New status.
	 * @param WC_Product $product    Product object.
	 */
	public function on_child_stock_status( $product_id, $status, $product = null ) {
		$product = $product ? $product : wc_get_product( $product_id );

		if ( ! $product || 'cbfw_bundle' === $product->get_type() ) {
			return;
		}

		$this->resync_parents( $product_id );
	}

	/**
	 * Child trashed → resync parents (item will be skipped once unavailable).
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_child_trashed( $post_id ) {
		if ( ! in_array( get_post_type( $post_id ), array( 'product', 'product_variation' ), true ) ) {
			return;
		}

		$this->resync_parents( $post_id );
	}
}
