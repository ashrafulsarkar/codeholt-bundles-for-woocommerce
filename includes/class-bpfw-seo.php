<?php
/**
 * SEO: structured data enhancements for bundles.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_SEO.
 */
class BPFW_SEO {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_filter( 'woocommerce_structured_data_product', array( $this, 'structured_data' ), 10, 2 );
	}

	/**
	 * Enrich product schema for bundles: mark as bundle and list contents.
	 *
	 * @param array      $markup  Structured data.
	 * @param WC_Product $product Product.
	 * @return array
	 */
	public function structured_data( $markup, $product ) {
		$bundle = bpfw_get_bundle( $product );

		if ( ! $bundle ) {
			return $markup;
		}

		$contents = array();

		foreach ( $bundle->get_bundled_products() as $item ) {
			$contents[] = array(
				'@type'    => 'Product',
				'name'     => $item['product']->get_name(),
				'sku'      => $item['product']->get_sku(),
				'url'      => $item['product']->is_visible() ? $item['product']->get_permalink() : '',
			);
		}

		if ( $contents ) {
			// Google understands isRelatedTo for bundled contents; also flag the offer.
			$markup['isRelatedTo'] = $contents;
		}

		/**
		 * Filter bundle structured data.
		 *
		 * @since 1.0.0
		 *
		 * @param array               $markup Structured data markup.
		 * @param BPFW_Product_Bundle $bundle Bundle product.
		 */
		return apply_filters( 'bpfw_structured_data', $markup, $bundle );
	}
}
