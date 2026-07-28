<?php
/**
 * Frontend: single product display, assets, price badge.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Frontend.
 */
class BPFW_Frontend {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'woocommerce_bundle_add_to_cart', array( $this, 'add_to_cart_template' ) );
		add_filter( 'woocommerce_get_price_html', array( $this, 'savings_badge' ), 10, 2 );
	}

	/**
	 * Register (and conditionally enqueue) frontend styles.
	 */
	public function register_assets() {
		wp_register_style( 'bpfw-frontend', BPFW_PLUGIN_URL . 'assets/css/frontend.css', array(), BPFW_VERSION );

		// Design settings are applied as CSS variable overrides.
		wp_add_inline_style( 'bpfw-frontend', bpfw_get_inline_css() );

		if ( is_product() ) {
			$product = wc_get_product( get_queried_object_id() );
			if ( $product && 'bundle' === $product->get_type() ) {
				wp_enqueue_style( 'bpfw-frontend' );
			}
		}

		// Shortcode or block present in the current post content.
		if ( is_singular() ) {
			$post = get_post();
			if ( $post && ( has_shortcode( $post->post_content, 'bundle' ) || has_shortcode( $post->post_content, 'bpfw_bundle' ) || has_block( 'bpfw/bundle', $post ) ) ) {
				wp_enqueue_style( 'bpfw-frontend' );
			}
		}
	}

	/**
	 * Output the bundle add-to-cart area on single product pages.
	 */
	public function add_to_cart_template() {
		global $product;

		$bundle = bpfw_get_bundle( $product );

		if ( ! $bundle ) {
			return;
		}

		wp_enqueue_style( 'bpfw-frontend' );

		bpfw_get_template(
			'single-product/add-to-cart/bundle.php',
			array(
				'bundle'         => $bundle,
				'pricing'        => bpfw_get_bundle_pricing( $bundle ),
				'layout'         => bpfw_get_bundle_layout( $bundle ),
				'included_title' => bpfw_get_setting( 'included_title' ),
			)
		);
	}

	/**
	 * Append a savings badge to bundle price HTML.
	 *
	 * @param string     $price_html Price HTML.
	 * @param WC_Product $product    Product.
	 * @return string
	 */
	public function savings_badge( $price_html, $product ) {
		if ( 'bundle' !== $product->get_type() || is_admin() ) {
			return $price_html;
		}

		$pricing = bpfw_get_bundle_pricing( $product );

		if ( $pricing['savings'] <= 0 ) {
			return $price_html;
		}

		/**
		 * Filter whether the savings badge is shown.
		 * Default comes from the "Show savings badge" plugin setting.
		 *
		 * @since 1.0.0
		 *
		 * @param bool       $show    Show the badge.
		 * @param WC_Product $product Bundle product.
		 */
		if ( ! apply_filters( 'bpfw_show_savings_badge', 'yes' === bpfw_get_setting( 'show_savings_badge', 'yes' ), $product ) ) {
			return $price_html;
		}

		$badge = sprintf(
			/* translators: 1: formatted amount, 2: percentage. */
			esc_html__( 'Save %1$s (%2$s%%)', 'bundle-product-for-woocommerce' ),
			wp_strip_all_tags( wc_price( $pricing['savings'] ) ),
			esc_html( $pricing['percent'] )
		);

		/**
		 * Filter the savings badge text.
		 *
		 * @since 1.0.0
		 *
		 * @param string     $badge   Badge text (escaped).
		 * @param WC_Product $product Bundle product.
		 * @param array      $pricing Pricing summary.
		 */
		$badge = apply_filters( 'bpfw_savings_badge_text', $badge, $product, $pricing );

		return $price_html . ' <span class="bpfw-savings-badge">' . $badge . '</span>';
	}
}
