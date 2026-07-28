<?php
/**
 * Main plugin loader.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Plugin — wires up every module of the plugin.
 */
final class BPFW_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var BPFW_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the single instance.
	 *
	 * @return BPFW_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — register all modules.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'ensure_product_type_term' ), 5 );

		new BPFW_Product_Type();
		new BPFW_Sync();
		new BPFW_Cart();
		new BPFW_Order();
		new BPFW_Frontend();
		new BPFW_Shortcode();
		new BPFW_Block();
		new BPFW_SEO();

		if ( is_admin() ) {
			new BPFW_Admin();
			new BPFW_Ajax();
			new BPFW_Analytics();
			new BPFW_Import_Export();
			new BPFW_Settings();
		}

		// Elementor widget (only when Elementor is active).
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );

		/**
		 * Fires after Bundle Product for WooCommerce is fully loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'bpfw_loaded' );
	}

	/**
	 * Ensure the bundle product type term exists (covers non-activation installs).
	 */
	public function ensure_product_type_term() {
		if ( ! term_exists( 'bundle', 'product_type' ) ) {
			wp_insert_term( 'bundle', 'product_type' );
		}
	}

	/**
	 * Register the Elementor widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_elementor_widget( $widgets_manager ) {
		require_once BPFW_PLUGIN_DIR . 'includes/compat/class-bpfw-elementor-widget.php';
		$widgets_manager->register( new BPFW_Elementor_Widget() );
	}
}
