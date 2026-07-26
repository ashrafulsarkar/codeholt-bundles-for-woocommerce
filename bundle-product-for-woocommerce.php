<?php
/**
 * Plugin Name:       Bundle Product for WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/bundle-product-for-woocommerce/
 * Description:       Create product bundles for WooCommerce — increase Average Order Value with fixed-price bundles, auto price calculation, stock sync, Gutenberg block, Elementor widget and analytics.
 * Version:           1.1.0
 * Author:            Ashraful Sarkar
 * Author URI:        https://ashrafulsarkar.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bundle-product-for-woocommerce
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 * WC tested up to:   10.0
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

define( 'BPFW_VERSION', '1.1.0' );
define( 'BPFW_PLUGIN_FILE', __FILE__ );
define( 'BPFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BPFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'BPFW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload BPFW_* classes from the includes directory.
 */
spl_autoload_register(
	function ( $class ) {
		if ( 0 !== strpos( $class, 'BPFW_' ) ) {
			return;
		}
		$file = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		$paths = array(
			BPFW_PLUGIN_DIR . 'includes/' . $file,
			BPFW_PLUGIN_DIR . 'includes/admin/' . $file,
			BPFW_PLUGIN_DIR . 'includes/compat/' . $file,
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
function bpfw_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'Bundle Product for WooCommerce requires WooCommerce to be installed and active.', 'bundle-product-for-woocommerce' );
				echo '</p></div>';
			}
		);
		return;
	}

	require_once BPFW_PLUGIN_DIR . 'includes/bpfw-functions.php';

	BPFW_Plugin::instance();
}
add_action( 'plugins_loaded', 'bpfw_init', 11 );

register_activation_hook(
	__FILE__,
	function () {
		if ( ! get_option( 'bpfw_version' ) ) {
			add_option( 'bpfw_version', BPFW_VERSION, '', false );
		} else {
			update_option( 'bpfw_version', BPFW_VERSION, false );
		}
		// Make sure the "bundle" product type term exists.
		if ( ! term_exists( 'bundle', 'product_type' ) ) {
			wp_insert_term( 'bundle', 'product_type' );
		}
	}
);
