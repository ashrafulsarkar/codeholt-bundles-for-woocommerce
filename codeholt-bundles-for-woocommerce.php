<?php
/**
 * Plugin Name:       Codeholt Bundles for WooCommerce
 * Plugin URI:        https://github.com/ashrafulsarkar/codeholt-bundles-for-woocommerce
 * Description:       Create product bundles for WooCommerce — increase Average Order Value with fixed-price bundles, auto price calculation, stock sync, Gutenberg block, Elementor widget and analytics.
 * Version:           1.0.0
 * Author:            Codeholt
 * Author URI:        https://codeholt.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       codeholt-bundles-for-woocommerce
 * Domain Path:       /languages
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 * WC tested up to:   10.0
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

define( 'CBFW_VERSION', '1.0.0' );
define( 'CBFW_PLUGIN_FILE', __FILE__ );
define( 'CBFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CBFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CBFW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload CBFW_* classes from the includes directory.
 */
spl_autoload_register(
	function ( $class ) {
		if ( 0 !== strpos( $class, 'CBFW_' ) ) {
			return;
		}
		$file = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		$paths = array(
			CBFW_PLUGIN_DIR . 'includes/' . $file,
			CBFW_PLUGIN_DIR . 'includes/admin/' . $file,
			CBFW_PLUGIN_DIR . 'includes/compat/' . $file,
		);
		foreach ( $paths as $path ) {
			if ( file_exists( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
);

// Declare WooCommerce feature compatibility (HPOS + Cart/Checkout Blocks).
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

/**
 * Boot the plugin once all plugins are loaded.
 */
function cbfw_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'Codeholt Bundles for WooCommerce requires WooCommerce to be installed and active.', 'codeholt-bundles-for-woocommerce' );
				echo '</p></div>';
			}
		);
		return;
	}

	require_once CBFW_PLUGIN_DIR . 'includes/cbfw-functions.php';
	require_once CBFW_PLUGIN_DIR . 'includes/class-cbfw-plugin.php';

	CBFW_Plugin::instance();
}
add_action( 'plugins_loaded', 'cbfw_init', 11 );

register_activation_hook(
	__FILE__,
	function () {
		if ( ! get_option( 'cbfw_version' ) ) {
			add_option( 'cbfw_version', CBFW_VERSION, '', false );
		} else {
			update_option( 'cbfw_version', CBFW_VERSION, false );
		}
		if ( ! get_option( 'cbfw_installed' ) ) {
			add_option( 'cbfw_installed', time(), '', false );
		}
		// Make sure the product type term exists.
		if ( ! term_exists( 'cbfw_bundle', 'product_type' ) ) {
			wp_insert_term( 'cbfw_bundle', 'product_type' );
		}
	}
);
