<?php
/**
 * Registers the "cbfw_bundle" product type with WooCommerce.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Product_Type.
 */
class CBFW_Product_Type {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_filter( 'product_type_selector', array( $this, 'add_type' ) );
		add_filter( 'woocommerce_product_class', array( $this, 'product_class' ), 10, 2 );
	}

	/**
	 * Add "Bundle" to the product type dropdown.
	 *
	 * @param array $types Existing types.
	 * @return array
	 */
	public function add_type( $types ) {
		$types['cbfw_bundle'] = __( 'Bundle product', 'codeholt-bundles-for-woocommerce' );
		return $types;
	}

	/**
	 * Map the bundle type to our product class.
	 *
	 * @param string $classname    Product class name.
	 * @param string $product_type Product type.
	 * @return string
	 */
	public function product_class( $classname, $product_type ) {
		if ( 'cbfw_bundle' === $product_type ) {
			$classname = 'CBFW_Product_Bundle';
		}
		return $classname;
	}
}
