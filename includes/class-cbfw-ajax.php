<?php
/**
 * AJAX handlers (admin bundle builder).
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Ajax.
 */
class CBFW_Ajax {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'wp_ajax_cbfw_json_search_products', array( $this, 'search_products' ) );
		add_action( 'wp_ajax_cbfw_add_bundle_item', array( $this, 'add_bundle_item' ) );
	}

	/**
	 * Search bundleable products for the builder (select2 endpoint).
	 */
	public function search_products() {
		check_ajax_referer( 'search-products', 'security' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_die( -1 );
		}

		$term    = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		$exclude = isset( $_GET['exclude'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['exclude'] ) ) : array();

		if ( '' === $term ) {
			wp_die();
		}

		$data_store = WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $term, '', true, false, 30, null, $exclude );

		$results = array();

		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );

			if ( ! cbfw_can_bundle_product( $product ) ) {
				continue;
			}

			$results[ $id ] = rawurldecode( wp_strip_all_tags( $product->get_formatted_name() ) );
		}

		// wc-product-search (selectWoo) expects a plain id => label object.
		wp_send_json( $results );
	}

	/**
	 * Return a rendered builder row for a newly added product.
	 */
	public function add_bundle_item() {
		check_ajax_referer( 'cbfw-admin', 'nonce' );

		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'codeholt-bundles-for-woocommerce' ) ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$index      = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : 0;
		$product    = wc_get_product( $product_id );

		if ( ! cbfw_can_bundle_product( $product ) ) {
			wp_send_json_error( array( 'message' => __( 'This product cannot be bundled. Choose a published, purchasable product — for a variable product, pick one of its variations.', 'codeholt-bundles-for-woocommerce' ) ) );
		}

		ob_start();
		CBFW_Admin::render_item_row( $product_id, 1, false, $index );

		wp_send_json_success( array( 'row' => ob_get_clean() ) );
	}
}
