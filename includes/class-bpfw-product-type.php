<?php
/**
 * Registers the "bundle" product type with WooCommerce.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Product_Type.
 */
class BPFW_Product_Type {

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
		$types['bundle'] = __( 'Bundle product', 'codeholt-bundles-for-woocommerce' );
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
		if ( 'bundle' === $product_type ) {
			$classname = 'BPFW_Product_Bundle';
		}
		return $classname;
	}
}
