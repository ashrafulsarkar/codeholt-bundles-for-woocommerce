<?php
/**
 * Main plugin loader.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Plugin — wires up every module of the plugin.
 */
final class CBFW_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var CBFW_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the single instance.
	 *
	 * @return CBFW_Plugin
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

		new CBFW_Product_Type();
		new CBFW_Sync();
		new CBFW_Cart();
		new CBFW_Order();
		new CBFW_Frontend();
		new CBFW_Shortcode();
		new CBFW_Block();
		new CBFW_SEO();

		if ( is_admin() ) {
			new CBFW_Admin();
			new CBFW_Ajax();
			new CBFW_Analytics();
			new CBFW_Import_Export();
			new CBFW_Settings();
		}

		// Elementor widget (only when Elementor is active).
		add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );

		/**
		 * Fires after Codeholt Bundles for WooCommerce is fully loaded.
		 *
		 * @since 1.0.0
		 */
		do_action( 'cbfw_loaded' );
	}

	/**
	 * Ensure the bundle product type term exists (covers non-activation installs).
	 */
	public function ensure_product_type_term() {
		if ( ! term_exists( 'cbfw_bundle', 'product_type' ) ) {
			wp_insert_term( 'cbfw_bundle', 'product_type' );
		}
	}

	/**
	 * Register the Elementor widget.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_elementor_widget( $widgets_manager ) {
		require_once CBFW_PLUGIN_DIR . 'includes/compat/class-cbfw-elementor-widget.php';
		$widgets_manager->register( new CBFW_Elementor_Widget() );
	}
}
