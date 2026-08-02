<?php
/**
 * Gutenberg "Bundle" block (dynamic, server-rendered).
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Block.
 */
class CBFW_Block {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ), 20 );
	}

	/**
	 * Register the block and its editor script.
	 */
	public function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'cbfw-block',
			CBFW_PLUGIN_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			CBFW_VERSION,
			true
		);

		wp_register_style( 'cbfw-frontend', CBFW_PLUGIN_URL . 'assets/css/frontend.css', array(), CBFW_VERSION );

		register_block_type(
			'cbfw/bundle',
			array(
				'api_version'     => 3,
				'title'           => __( 'Product Bundle', 'codeholt-bundles-for-woocommerce' ),
				'description'     => __( 'Display a WooCommerce product bundle.', 'codeholt-bundles-for-woocommerce' ),
				'category'        => 'woocommerce',
				'editor_script'   => 'cbfw-block',
				'style'           => 'cbfw-frontend',
				'attributes'      => array(
					'bundleId'  => array(
						'type'    => 'number',
						'default' => 0,
					),
					'layout'    => array(
						'type'    => 'string',
						'default' => 'card',
					),
					'showImage' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showItems' => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
				'render_callback' => array( $this, 'render' ),
			)
		);

		// Bundle choices for the editor dropdown.
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );
	}

	/**
	 * Provide bundle choices to the editor.
	 */
	public function editor_data() {
		$choices = array(
			array(
				'value' => 0,
				'label' => __( '— Select a bundle —', 'codeholt-bundles-for-woocommerce' ),
			),
		);

		foreach ( cbfw_get_bundle_choices() as $id => $name ) {
			$choices[] = array(
				'value' => $id,
				'label' => $name,
			);
		}

		wp_localize_script( 'cbfw-block', 'cbfwBlockData', array( 'bundles' => $choices ) );
	}

	/**
	 * Server-side render callback.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$bundle_id = isset( $attributes['bundleId'] ) ? absint( $attributes['bundleId'] ) : 0;

		if ( ! $bundle_id ) {
			return '';
		}

		$html = cbfw_render_bundle_card(
			$bundle_id,
			array(
				'layout'     => isset( $attributes['layout'] ) ? sanitize_key( $attributes['layout'] ) : 'card',
				'show_image' => ! empty( $attributes['showImage'] ),
				'show_items' => ! isset( $attributes['showItems'] ) || $attributes['showItems'],
			)
		);

		if ( '' === $html && defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return '<p>' . esc_html__( 'Bundle not found or not published.', 'codeholt-bundles-for-woocommerce' ) . '</p>';
		}

		return $html;
	}
}
