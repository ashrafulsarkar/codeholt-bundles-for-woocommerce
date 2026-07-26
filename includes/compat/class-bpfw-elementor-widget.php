<?php
/**
 * Elementor widget: Product Bundle.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Elementor_Widget.
 */
class BPFW_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'bpfw_bundle';
	}

	/**
	 * Widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Product Bundle', 'bundle-product-for-woocommerce' );
	}

	/**
	 * Widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-product-related';
	}

	/**
	 * Widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'woocommerce-elements', 'general' );
	}

	/**
	 * Widget keywords.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'bundle', 'woocommerce', 'product', 'combo' );
	}

	/**
	 * Styles used by this widget.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'bpfw-frontend' );
	}

	/**
	 * Register controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_bundle',
			array(
				'label' => __( 'Bundle', 'bundle-product-for-woocommerce' ),
			)
		);

		$this->add_control(
			'bundle_id',
			array(
				'label'   => __( 'Select bundle', 'bundle-product-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::SELECT2,
				'options' => bpfw_get_bundle_choices(),
			)
		);

		$this->add_control(
			'layout',
			array(
				'label'   => __( 'Layout', 'bundle-product-for-woocommerce' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'card',
				'options' => array(
					'card' => __( 'Card', 'bundle-product-for-woocommerce' ),
					'list' => __( 'List', 'bundle-product-for-woocommerce' ),
				),
			)
		);

		$this->add_control(
			'show_image',
			array(
				'label'        => __( 'Show bundle image', 'bundle-product-for-woocommerce' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'show_items',
			array(
				'label'        => __( 'Show bundled products', 'bundle-product-for-woocommerce' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Style', 'bundle-product-for-woocommerce' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent_color',
			array(
				'label'     => __( 'Accent color', 'bundle-product-for-woocommerce' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bpfw-card' => '--bpfw-accent: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_bg',
			array(
				'label'     => __( 'Button background', 'bundle-product-for-woocommerce' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bpfw-card__button' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Button text color', 'bundle-product-for-woocommerce' ),
				'type'      => \Elementor\Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .bpfw-card__button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget.
	 */
	protected function render() {
		$settings  = $this->get_settings_for_display();
		$bundle_id = ! empty( $settings['bundle_id'] ) ? absint( $settings['bundle_id'] ) : 0;

		if ( ! $bundle_id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p>' . esc_html__( 'Select a bundle to display.', 'bundle-product-for-woocommerce' ) . '</p>';
			}
			return;
		}

		echo bpfw_render_bundle_card( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output is escaped at source.
			$bundle_id,
			array(
				'layout'     => sanitize_key( $settings['layout'] ),
				'show_image' => 'yes' === $settings['show_image'],
				'show_items' => 'yes' === $settings['show_items'],
			)
		);
	}
}
