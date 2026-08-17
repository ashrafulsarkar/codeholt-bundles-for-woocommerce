<?php
/**
 * Free-plugin "Get Pro" tab: Free vs Pro feature comparison + upsell links.
 * Hidden entirely once the Pro add-on is active — Pro registers its own
 * License tab in its place (see CBFWP_License::add_tab()).
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Get_Pro.
 */
class CBFW_Get_Pro {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_filter( 'cbfw_admin_tabs', array( $this, 'add_tab' ), 20 );
		add_action( 'cbfw_admin_tab_get-pro', array( $this, 'render_tab' ) );
		add_filter( 'plugin_action_links_' . CBFW_PLUGIN_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add the "Get Pro" tab — only while the Pro add-on isn't active.
	 *
	 * @param array $tabs slug => label.
	 * @return array
	 */
	public function add_tab( $tabs ) {
		if ( ! cbfw_is_pro_active() ) {
			$tabs['get-pro'] = __( 'Get Pro', 'codeholt-bundles-for-woocommerce' );
		}
		return $tabs;
	}

	/**
	 * Plugins screen row link — points to the Get Pro tab, styled to stand out.
	 * Registered after CBFW_Settings so it lands first (both use array_unshift).
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function action_links( $links ) {
		if ( ! cbfw_is_pro_active() ) {
			array_unshift(
				$links,
				'<a href="' . esc_url( self::page_url() ) . '" style="color:#d63638;font-weight:700;">' . esc_html__( 'Get Pro', 'codeholt-bundles-for-woocommerce' ) . '</a>'
			);
		}
		return $links;
	}

	/**
	 * URL of the Get Pro tab.
	 *
	 * @return string
	 */
	public static function page_url() {
		return admin_url( 'admin.php?page=cbfw-bundles&tab=get-pro' );
	}

	/**
	 * Render the Free vs Pro comparison table.
	 */
	public function render_tab() {
		$features = array(
			array(
				'label' => __( 'Unlimited bundles — Table & Compact layouts', 'codeholt-bundles-for-woocommerce' ),
				'free'  => true,
			),
			array(
				'label' => __( 'Auto or fixed bundle pricing', 'codeholt-bundles-for-woocommerce' ),
				'free'  => true,
			),
			array(
				'label' => __( 'Shortcode, Gutenberg block & Elementor widget', 'codeholt-bundles-for-woocommerce' ),
				'free'  => true,
			),
			array(
				'label' => __( 'Savings badge, JSON import/export, sales overview', 'codeholt-bundles-for-woocommerce' ),
				'free'  => true,
			),
			array(
				'label' => __( 'Bundle simple products and product variations', 'codeholt-bundles-for-woocommerce' ),
				'free'  => true,
			),
			array(
				'label' => __( 'Percentage discount pricing + discount scheduling', 'codeholt-bundles-for-woocommerce' ),
				'free'  => false,
			),
			array(
				'label' => __( 'Min / max quantity rules per order', 'codeholt-bundles-for-woocommerce' ),
				'free'  => false,
			),
			array(
				'label' => __( 'Custom savings badge text + shop page badge', 'codeholt-bundles-for-woocommerce' ),
				'free'  => false,
			),
			array(
				'label' => __( 'List, Grid, Inline & Custom layout builder', 'codeholt-bundles-for-woocommerce' ),
				'free'  => false,
			),
			array(
				'label' => __( 'Custom CSS panel', 'codeholt-bundles-for-woocommerce' ),
				'free'  => false,
			),
			array(
				'label' => __( 'Advanced reports with date ranges + CSV export', 'codeholt-bundles-for-woocommerce' ),
				'free'  => false,
			),
			array(
				'label' => __( 'Automatic one-click plugin updates', 'codeholt-bundles-for-woocommerce' ),
				'free'  => false,
			),
			array(
				'label' => __( 'Priority support', 'codeholt-bundles-for-woocommerce' ),
				'free'  => false,
			),
		);
		?>
		<div class="cbfw-getpro">

			<h2><?php esc_html_e( 'Free vs Pro', 'codeholt-bundles-for-woocommerce' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Unlock percentage discounts, bundle variations, quantity rules, extra layouts, a custom CSS panel, advanced reports and automatic updates.', 'codeholt-bundles-for-woocommerce' ); ?>
			</p>

			<table class="widefat striped cbfw-compare-table">
				<thead>
					<tr>
						<th class="cbfw-compare-feature"><?php esc_html_e( 'Feature', 'codeholt-bundles-for-woocommerce' ); ?></th>
						<th class="cbfw-compare-col text-center"><?php esc_html_e( 'Free', 'codeholt-bundles-for-woocommerce' ); ?></th>
						<th class="cbfw-compare-col cbfw-compare-pro"><?php esc_html_e( 'Pro', 'codeholt-bundles-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $features as $feature ) : ?>
						<tr>
							<td class="cbfw-compare-feature"><?php echo esc_html( $feature['label'] ); ?></td>
							<td class="cbfw-compare-col">
								<?php if ( $feature['free'] ) : ?>
									<span class="dashicons dashicons-yes-alt cbfw-compare-yes"></span>
								<?php else : ?>
									<span class="dashicons dashicons-minus cbfw-compare-no"></span>
								<?php endif; ?>
							</td>
							<td class="cbfw-compare-col cbfw-compare-pro"><span class="dashicons dashicons-yes-alt cbfw-compare-yes"></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr>
						<td class="cbfw-compare-feature"></td>
						<td class="cbfw-compare-col"><em><?php esc_html_e( 'You are here', 'codeholt-bundles-for-woocommerce' ); ?></em></td>
						<td class="cbfw-compare-col cbfw-compare-pro">
							<a href="<?php echo esc_url( cbfw_get_upgrade_url() ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary button-hero cbfw-upgrade-btn">
								<?php esc_html_e( 'Upgrade to Pro', 'codeholt-bundles-for-woocommerce' ); ?>
							</a>
						</td>
					</tr>
				</tfoot>
			</table>

		</div>
		<?php
	}
}
