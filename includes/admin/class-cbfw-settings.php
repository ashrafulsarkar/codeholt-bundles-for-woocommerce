<?php
/**
 * Plugin settings: layout and display defaults.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Settings.
 */
class CBFW_Settings {

	/**
	 * Option name holding all settings.
	 */
	const OPTION = 'cbfw_settings';

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'admin_post_cbfw_save_settings', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . CBFW_PLUGIN_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add a Settings link on the Plugins screen row.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( self::page_url() ) . '">' . esc_html__( 'Settings', 'codeholt-bundles-for-woocommerce' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Enqueue assets on the Bundles admin page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( 'woocommerce_page_cbfw-bundles' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'cbfw-admin', CBFW_PLUGIN_URL . 'assets/css/admin.css', array(), CBFW_VERSION );

		if ( isset( $_GET['tab'] ) && 'import_export' === $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab check.
			wp_enqueue_script( 'cbfw-import-export', CBFW_PLUGIN_URL . 'assets/js/import-export.js', array(), CBFW_VERSION, true );
		}
	}

	/**
	 * URL of the settings tab.
	 *
	 * @param array $args Extra query args.
	 * @return string
	 */
	public static function page_url( $args = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'page' => 'cbfw-bundles',
					'tab'  => 'settings',
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Handle the settings form submit (admin-post.php).
	 */
	public function save() {
		check_admin_referer( 'cbfw-settings' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'codeholt-bundles-for-woocommerce' ) );
		}

		if ( ! empty( $_POST['cbfw_reset'] ) ) {
			delete_option( self::OPTION );
			wp_safe_redirect( self::page_url( array( 'reset' => 1 ) ) );
			exit;
		}

		$defaults = cbfw_get_default_settings();

		$product_layout = isset( $_POST['product_layout'] ) ? sanitize_key( wp_unslash( $_POST['product_layout'] ) ) : '';
		$card_layout    = isset( $_POST['card_layout'] ) ? sanitize_key( wp_unslash( $_POST['card_layout'] ) ) : '';
		$included_title = isset( $_POST['included_title'] ) ? sanitize_text_field( wp_unslash( $_POST['included_title'] ) ) : '';

		if ( ! array_key_exists( $product_layout, cbfw_get_product_layouts() ) ) {
			$product_layout = $defaults['product_layout'];
		}

		$settings = array(
			'product_layout'     => $product_layout,
			'card_layout'        => in_array( $card_layout, array( 'card', 'list' ), true ) ? $card_layout : $defaults['card_layout'],
			'included_title'     => '' !== $included_title ? $included_title : $defaults['included_title'],
			'show_savings_badge' => empty( $_POST['show_savings_badge'] ) ? 'no' : 'yes',
		);

		/**
		 * Filter settings before they are saved.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings Sanitized settings.
		 */
		update_option( self::OPTION, apply_filters( 'cbfw_save_settings', $settings ), false );

		wp_safe_redirect( self::page_url( array( 'updated' => 1 ) ) );
		exit;
	}

	/**
	 * Render one layout choice (radio styled as a visual picker).
	 * Also used by the product data panel for the per-bundle layout override.
	 *
	 * @param string $name    Input name.
	 * @param string $value   Option value.
	 * @param string $current Currently selected value.
	 * @param string $label   Visible label.
	 * @param string $icon    Icon style: default|list|grid|compact|card|wide (add-ons may register more).
	 */
	public static function layout_choice( $name, $value, $current, $label, $icon = 'default' ) {
		/**
		 * Filter the cell count used to draw a layout icon.
		 * Add-ons registering layouts can map their icon slug here and
		 * style `.cbfw-choice__icon--{slug}` with their own admin CSS.
		 *
		 * @since 1.0.0
		 *
		 * @param array $cells icon slug => number of <i> cells.
		 */
		$cells = apply_filters(
			'cbfw_layout_icon_cells',
			array(
				'list'    => 3,
				'grid'    => 4,
				'compact' => 3,
				'table'   => 6,
				'card'    => 2,
				'wide'    => 2,
				'inline'  => 3,
				'custom'  => 4,
				'default' => 0,
			)
		);
		$count = isset( $cells[ $icon ] ) ? $cells[ $icon ] : 0;
		?>
		<label class="cbfw-choice">
			<input
				type="radio"
				name="<?php echo esc_attr( $name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				<?php checked( $current, $value ); ?>
			/>
			<span class="cbfw-choice__box">
				<span class="cbfw-choice__icon cbfw-choice__icon--<?php echo esc_attr( $icon ); ?>" aria-hidden="true"><?php echo str_repeat( '<i></i>', $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?></span>
				<span class="cbfw-choice__label"><?php echo esc_html( $label ); ?></span>
			</span>
		</label>
		<?php
	}

	/**
	 * Render the Settings tab on the Bundles page.
	 */
	public static function render_tab() {
		$settings = cbfw_get_settings();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only success notices.
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'codeholt-bundles-for-woocommerce' ) . '</p></div>';
		}
		if ( isset( $_GET['reset'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings reset to defaults.', 'codeholt-bundles-for-woocommerce' ) . '</p></div>';
		}
		// phpcs:enable
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cbfw-settings-form">
			<input type="hidden" name="action" value="cbfw_save_settings" />
			<?php wp_nonce_field( 'cbfw-settings' ); ?>

			<div class="cbfw-settings-grid">

				<section class="cbfw-panel-card">
					<h2><?php esc_html_e( 'Layout', 'codeholt-bundles-for-woocommerce' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Default layouts used across the shop. Each bundle can override the product page layout from its own edit screen.', 'codeholt-bundles-for-woocommerce' ); ?></p>

					<div class="cbfw-field">
						<span class="cbfw-field__label"><?php esc_html_e( 'Product page layout', 'codeholt-bundles-for-woocommerce' ); ?></span>
						<div class="cbfw-choices">
							<?php
							foreach ( cbfw_get_product_layouts() as $cbfw_slug => $cbfw_label ) {
								self::layout_choice( 'product_layout', $cbfw_slug, $settings['product_layout'], $cbfw_label, $cbfw_slug );
							}
							?>
						</div>
					</div>

					<div class="cbfw-field">
						<span class="cbfw-field__label"><?php esc_html_e( 'Bundle card layout (shortcode / block / widget)', 'codeholt-bundles-for-woocommerce' ); ?></span>
						<div class="cbfw-choices">
							<?php
							self::layout_choice( 'card_layout', 'card', $settings['card_layout'], __( 'Card', 'codeholt-bundles-for-woocommerce' ), 'card' );
							self::layout_choice( 'card_layout', 'list', $settings['card_layout'], __( 'Wide', 'codeholt-bundles-for-woocommerce' ), 'wide' );
							?>
						</div>
					</div>

					<div class="cbfw-field">
						<label class="cbfw-field__label" for="cbfw_included_title"><?php esc_html_e( 'Included products heading', 'codeholt-bundles-for-woocommerce' ); ?></label>
						<input type="text" id="cbfw_included_title" name="included_title" class="regular-text" value="<?php echo esc_attr( $settings['included_title'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Shown above the bundled products list on the product page.', 'codeholt-bundles-for-woocommerce' ); ?></p>
					</div>

					<div class="cbfw-field">
						<label class="cbfw-toggle">
							<input type="checkbox" name="show_savings_badge" value="yes" <?php checked( 'yes', $settings['show_savings_badge'] ); ?> />
							<span><?php esc_html_e( 'Show the "Save X (Y%)" badge next to bundle prices', 'codeholt-bundles-for-woocommerce' ); ?></span>
						</label>
					</div>

					<?php
					/**
					 * Fires at the bottom of the Layout card so add-ons can append
					 * closely-related fields (e.g. badge settings) without
					 * opening a separate panel card.
					 *
					 * @since 1.0.0
					 *
					 * @param array $settings Current settings.
					 */
					do_action( 'cbfw_layout_card_fields', $settings );
					?>
				</section>

				<?php
				/**
				 * Fires after the core settings sections so add-ons can render
				 * their own sections. Add-on fields are saved through the
				 * `cbfw_save_settings` filter.
				 *
				 * @since 1.0.0
				 *
				 * @param array $settings Current settings.
				 */
				do_action( 'cbfw_settings_sections', $settings );
				?>

			</div>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'codeholt-bundles-for-woocommerce' ); ?></button>
				<button type="submit" name="cbfw_reset" value="1" class="button" onclick="return confirm( '<?php echo esc_js( __( 'Reset all layout and design settings to defaults?', 'codeholt-bundles-for-woocommerce' ) ); ?>' );"><?php esc_html_e( 'Reset to defaults', 'codeholt-bundles-for-woocommerce' ); ?></button>
			</p>
		</form>
		<?php
	}
}
