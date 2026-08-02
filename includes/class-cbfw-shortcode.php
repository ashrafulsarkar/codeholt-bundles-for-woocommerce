<?php
/**
 * [cbfw_bundle] shortcode.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Shortcode.
 */
class CBFW_Shortcode {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_shortcode( 'cbfw_bundle', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * Usage: [cbfw_bundle id="123" layout="card" show_image="yes" show_items="yes"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'         => 0,
				'layout'     => '', // Empty = default from plugin settings.
				'show_image' => 'yes',
				'show_items' => 'yes',
			),
			$atts,
			'cbfw_bundle'
		);

		return cbfw_render_bundle_card(
			absint( $atts['id'] ),
			array(
				'layout'     => $atts['layout'] ? sanitize_key( $atts['layout'] ) : cbfw_get_setting( 'card_layout', 'card' ),
				'show_image' => wc_string_to_bool( $atts['show_image'] ),
				'show_items' => wc_string_to_bool( $atts['show_items'] ),
			)
		);
	}
}
