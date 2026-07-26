<?php
/**
 * Plugin settings: layout defaults + limited design options.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Settings.
 */
class BPFW_Settings {

	/**
	 * Option name holding all settings.
	 */
	const OPTION = 'bpfw_settings';

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'admin_post_bpfw_save_settings', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . BPFW_PLUGIN_BASENAME, array( $this, 'action_links' ) );
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
			'<a href="' . esc_url( self::page_url() ) . '">' . esc_html__( 'Settings', 'bundle-product-for-woocommerce' ) . '</a>'
		);
		return $links;
	}

	/**
	 * Enqueue assets on the Bundles admin page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( 'woocommerce_page_bpfw-bundles' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'bpfw-admin', BPFW_PLUGIN_URL . 'assets/css/admin.css', array(), BPFW_VERSION );
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script( 'wp-color-picker', 'jQuery( function( $ ) { $( ".bpfw-color-field" ).wpColorPicker(); } );' );
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
					'page' => 'bpfw-bundles',
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
		check_admin_referer( 'bpfw-settings' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage these settings.', 'bundle-product-for-woocommerce' ) );
		}

		if ( ! empty( $_POST['bpfw_reset'] ) ) {
			delete_option( self::OPTION );
			wp_safe_redirect( self::page_url( array( 'reset' => 1 ) ) );
			exit;
		}

		$defaults = bpfw_get_default_settings();

		$product_layout = isset( $_POST['product_layout'] ) ? sanitize_key( wp_unslash( $_POST['product_layout'] ) ) : '';
		$card_layout    = isset( $_POST['card_layout'] ) ? sanitize_key( wp_unslash( $_POST['card_layout'] ) ) : '';
		$included_title = isset( $_POST['included_title'] ) ? sanitize_text_field( wp_unslash( $_POST['included_title'] ) ) : '';

		$settings = array(
			'product_layout'     => in_array( $product_layout, array( 'list', 'grid', 'compact' ), true ) ? $product_layout : $defaults['product_layout'],
			'card_layout'        => in_array( $card_layout, array( 'card', 'list' ), true ) ? $card_layout : $defaults['card_layout'],
			'included_title'     => '' !== $included_title ? $included_title : $defaults['included_title'],
			'show_savings_badge' => empty( $_POST['show_savings_badge'] ) ? 'no' : 'yes',
			'accent_color'       => self::sanitize_color( 'accent_color', $defaults ),
			'savings_color'      => self::sanitize_color( 'savings_color', $defaults ),
			'border_color'       => self::sanitize_color( 'border_color', $defaults ),
			'border_radius'      => isset( $_POST['border_radius'] ) ? min( 40, absint( $_POST['border_radius'] ) ) : $defaults['border_radius'],
		);

		/**
		 * Filter settings before they are saved.
		 *
		 * @since 1.1.0
		 *
		 * @param array $settings Sanitized settings.
		 */
		update_option( self::OPTION, apply_filters( 'bpfw_save_settings', $settings ), false );

		wp_safe_redirect( self::page_url( array( 'updated' => 1 ) ) );
		exit;
	}

	/**
	 * Sanitize a posted hex color, falling back to the default.
	 *
	 * @param string $key      POST/settings key.
	 * @param array  $defaults Default settings.
	 * @return string
	 */
	protected static function sanitize_color( $key, $defaults ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce checked in save().
		$raw   = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		$color = sanitize_hex_color( $raw );

		return $color ? $color : $defaults[ $key ];
	}

	/**
	 * Render one layout choice (radio styled as a visual picker).
	 * Also used by the product data panel for the per-bundle layout override.
	 *
	 * @param string $name    Input name.
	 * @param string $value   Option value.
	 * @param string $current Currently selected value.
	 * @param string $label   Visible label.
	 * @param string $icon    Icon style: default|list|grid|compact|card|wide.
	 */
	public static function layout_choice( $name, $value, $current, $label, $icon = 'default' ) {
		$cells = array(
			'list'    => 3,
			'grid'    => 4,
			'compact' => 3,
			'card'    => 2,
			'wide'    => 2,
			'default' => 0,
		);
		$count = isset( $cells[ $icon ] ) ? $cells[ $icon ] : 0;
		?>
		<label class="bpfw-choice">
			<input type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( $current, $value ); ?> />
			<span class="bpfw-choice__box">
				<span class="bpfw-choice__icon bpfw-choice__icon--<?php echo esc_attr( $icon ); ?>" aria-hidden="true"><?php echo str_repeat( '<i></i>', $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup. ?></span>
				<span class="bpfw-choice__label"><?php echo esc_html( $label ); ?></span>
			</span>
		</label>
		<?php
	}

	/**
	 * Render the Settings tab on the Bundles page.
	 */
	public static function render_tab() {
		$settings = bpfw_get_settings();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only success notices.
		if ( isset( $_GET['updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'bundle-product-for-woocommerce' ) . '</p></div>';
		}
		if ( isset( $_GET['reset'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings reset to defaults.', 'bundle-product-for-woocommerce' ) . '</p></div>';
		}
		// phpcs:enable
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bpfw-settings-form">
			<input type="hidden" name="action" value="bpfw_save_settings" />
			<?php wp_nonce_field( 'bpfw-settings' ); ?>

			<div class="bpfw-settings-grid">

				<section class="bpfw-panel-card">
					<h2><?php esc_html_e( 'Layout', 'bundle-product-for-woocommerce' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Default layouts used across the shop. Each bundle can override the product page layout from its own edit screen.', 'bundle-product-for-woocommerce' ); ?></p>

					<div class="bpfw-field">
						<span class="bpfw-field__label"><?php esc_html_e( 'Product page layout', 'bundle-product-for-woocommerce' ); ?></span>
						<div class="bpfw-choices">
							<?php
							self::layout_choice( 'product_layout', 'list', $settings['product_layout'], __( 'List', 'bundle-product-for-woocommerce' ), 'list' );
							self::layout_choice( 'product_layout', 'grid', $settings['product_layout'], __( 'Grid', 'bundle-product-for-woocommerce' ), 'grid' );
							self::layout_choice( 'product_layout', 'compact', $settings['product_layout'], __( 'Compact', 'bundle-product-for-woocommerce' ), 'compact' );
							?>
						</div>
					</div>

					<div class="bpfw-field">
						<span class="bpfw-field__label"><?php esc_html_e( 'Bundle card layout (shortcode / block / widget)', 'bundle-product-for-woocommerce' ); ?></span>
						<div class="bpfw-choices">
							<?php
							self::layout_choice( 'card_layout', 'card', $settings['card_layout'], __( 'Card', 'bundle-product-for-woocommerce' ), 'card' );
							self::layout_choice( 'card_layout', 'list', $settings['card_layout'], __( 'Wide', 'bundle-product-for-woocommerce' ), 'wide' );
							?>
						</div>
					</div>

					<div class="bpfw-field">
						<label class="bpfw-field__label" for="bpfw_included_title"><?php esc_html_e( 'Included products heading', 'bundle-product-for-woocommerce' ); ?></label>
						<input type="text" id="bpfw_included_title" name="included_title" class="regular-text" value="<?php echo esc_attr( $settings['included_title'] ); ?>" />
						<p class="description"><?php esc_html_e( 'Shown above the bundled products list on the product page.', 'bundle-product-for-woocommerce' ); ?></p>
					</div>

					<div class="bpfw-field">
						<label class="bpfw-toggle">
							<input type="checkbox" name="show_savings_badge" value="yes" <?php checked( 'yes', $settings['show_savings_badge'] ); ?> />
							<span><?php esc_html_e( 'Show the "Save X (Y%)" badge next to bundle prices', 'bundle-product-for-woocommerce' ); ?></span>
						</label>
					</div>
				</section>

				<section class="bpfw-panel-card">
					<h2><?php esc_html_e( 'Design', 'bundle-product-for-woocommerce' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Light-touch styling applied via CSS variables — your theme keeps control of everything else.', 'bundle-product-for-woocommerce' ); ?></p>

					<div class="bpfw-field">
						<label class="bpfw-field__label" for="bpfw_accent_color"><?php esc_html_e( 'Accent color', 'bundle-product-for-woocommerce' ); ?></label>
						<input type="text" id="bpfw_accent_color" name="accent_color" class="bpfw-color-field" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" data-default-color="#2f4df5" />
						<p class="description"><?php esc_html_e( 'Used for quantity badges and the bundle card button.', 'bundle-product-for-woocommerce' ); ?></p>
					</div>

					<div class="bpfw-field">
						<label class="bpfw-field__label" for="bpfw_savings_color"><?php esc_html_e( 'Savings color', 'bundle-product-for-woocommerce' ); ?></label>
						<input type="text" id="bpfw_savings_color" name="savings_color" class="bpfw-color-field" value="<?php echo esc_attr( $settings['savings_color'] ); ?>" data-default-color="#00a32a" />
						<p class="description"><?php esc_html_e( 'Used for the savings badge and savings text.', 'bundle-product-for-woocommerce' ); ?></p>
					</div>

					<div class="bpfw-field">
						<label class="bpfw-field__label" for="bpfw_border_color"><?php esc_html_e( 'Border color', 'bundle-product-for-woocommerce' ); ?></label>
						<input type="text" id="bpfw_border_color" name="border_color" class="bpfw-color-field" value="<?php echo esc_attr( $settings['border_color'] ); ?>" data-default-color="#e3e5e8" />
					</div>

					<div class="bpfw-field">
						<label class="bpfw-field__label" for="bpfw_border_radius"><?php esc_html_e( 'Corner radius (px)', 'bundle-product-for-woocommerce' ); ?></label>
						<input type="number" id="bpfw_border_radius" name="border_radius" min="0" max="40" step="1" value="<?php echo esc_attr( $settings['border_radius'] ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( '0 for square corners, up to 40 for very round. Default: 10.', 'bundle-product-for-woocommerce' ); ?></p>
					</div>
				</section>

			</div>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'bundle-product-for-woocommerce' ); ?></button>
				<button type="submit" name="bpfw_reset" value="1" class="button" onclick="return confirm( '<?php echo esc_js( __( 'Reset all layout and design settings to defaults?', 'bundle-product-for-woocommerce' ) ); ?>' );"><?php esc_html_e( 'Reset to defaults', 'bundle-product-for-woocommerce' ); ?></button>
			</p>
		</form>
		<?php
	}
}
