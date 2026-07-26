<?php
/**
 * [bundle] shortcode.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Shortcode.
 */
class BPFW_Shortcode {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_shortcode( 'bundle', array( $this, 'render' ) );
		add_shortcode( 'bpfw_bundle', array( $this, 'render' ) ); // Prefixed alias to avoid conflicts.
	}

	/**
	 * Render the shortcode.
	 *
	 * Usage: [bundle id="123" layout="card" show_image="yes" show_items="yes"]
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
			'bundle'
		);

		return bpfw_render_bundle_card(
			absint( $atts['id'] ),
			array(
				'layout'     => $atts['layout'] ? sanitize_key( $atts['layout'] ) : bpfw_get_setting( 'card_layout', 'card' ),
				'show_image' => wc_string_to_bool( $atts['show_image'] ),
				'show_items' => wc_string_to_bool( $atts['show_items'] ),
			)
		);
	}
}
