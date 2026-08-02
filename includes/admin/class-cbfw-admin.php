<?php
/**
 * Admin: product data tab, bundle builder panel and save logic.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Admin.
 */
class CBFW_Admin {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_image_size' ) );
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Register the small square thumbnail used in the bundle builder rows.
	 */
	public function register_image_size() {
		add_image_size( 'cbfw_item_thumb', 40, 40, true );
	}

	/**
	 * Add the "Bundled Products" tab.
	 *
	 * @param array $tabs Product data tabs.
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['cbfw_bundled_products'] = array(
			'label'    => __( 'Bundled Products', 'codeholt-bundles-for-woocommerce' ),
			'target'   => 'cbfw_bundled_products_data',
			'class'    => array( 'show_if_cbfw_bundle' ),
			'priority' => 21,
		);
		return $tabs;
	}

	/**
	 * Render the bundle builder panel.
	 */
	public function render_panel() {
		global $post;

		$bundle_items = get_post_meta( $post->ID, '_cbfw_bundled_items', true );
		$bundle_items = is_array( $bundle_items ) ? $bundle_items : array();
		$pricing_mode = get_post_meta( $post->ID, '_cbfw_pricing_mode', true );
		$pricing_mode = in_array( $pricing_mode, array( 'auto', 'fixed' ), true ) ? $pricing_mode : 'auto';
		$fixed_price  = get_post_meta( $post->ID, '_cbfw_fixed_price', true );
		$layout       = get_post_meta( $post->ID, '_cbfw_layout', true );
		$layout       = array_key_exists( $layout, cbfw_get_product_layouts() ) ? $layout : '';
		?>
		<div id="cbfw_bundled_products_data" class="panel woocommerce_options_panel hidden">

			<div class="options_group">
				<p class="form-field">
					<label for="cbfw_pricing_mode"><?php esc_html_e( 'Pricing mode', 'codeholt-bundles-for-woocommerce' ); ?></label>
					<select id="cbfw_pricing_mode" name="_cbfw_pricing_mode" class="select short">
						<?php foreach ( cbfw_get_pricing_mode_options() as $cbfw_mode => $cbfw_label ) : ?>
							<option value="<?php echo esc_attr( $cbfw_mode ); ?>" <?php selected( $pricing_mode, $cbfw_mode ); ?>><?php echo esc_html( $cbfw_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo wc_help_tip( __( 'Auto: bundle price is the sum of bundled product prices. Fixed: you set one bundle price, savings are calculated automatically.', 'codeholt-bundles-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>

				<p class="form-field cbfw_fixed_price_field">
					<label for="cbfw_fixed_price">
						<?php
						/* translators: %s: currency symbol. */
						printf( esc_html__( 'Bundle price (%s)', 'codeholt-bundles-for-woocommerce' ), esc_html( get_woocommerce_currency_symbol() ) );
						?>
					</label>
					<input type="text" id="cbfw_fixed_price" name="_cbfw_fixed_price" class="short wc_input_price" value="<?php echo esc_attr( wc_format_localized_price( $fixed_price ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. 99', 'codeholt-bundles-for-woocommerce' ); ?>" />
				</p>

				<?php
				/**
				 * Fires after the pricing fields in the bundle builder panel.
				 * Used by add-ons to render extra pricing fields (e.g. discount %).
				 *
				 * @since 1.0.0
				 *
				 * @param WP_Post $post Current product post.
				 */
				do_action( 'cbfw_pricing_fields', $post );
				?>
			</div>

			<div class="options_group">
				<p class="form-field cbfw-layout-field">
					<label><?php esc_html_e( 'Product page layout', 'codeholt-bundles-for-woocommerce' ); ?></label>
					<span class="cbfw-choices">
						<?php
						CBFW_Settings::layout_choice( '_cbfw_layout', '', $layout, __( 'Default', 'codeholt-bundles-for-woocommerce' ), 'default' );
						foreach ( cbfw_get_product_layouts() as $cbfw_slug => $cbfw_label ) {
							CBFW_Settings::layout_choice( '_cbfw_layout', $cbfw_slug, $layout, $cbfw_label, $cbfw_slug );
						}
						?>
					</span>
					<?php echo wc_help_tip( __( 'How bundled products are shown on this bundle\'s page. "Default" follows WooCommerce → Bundles → Settings.', 'codeholt-bundles-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			</div>

			<div class="options_group cbfw-builder">
				<p class="form-field">
					<label for="cbfw_add_product"><?php esc_html_e( 'Add product', 'codeholt-bundles-for-woocommerce' ); ?></label>
					<select
						id="cbfw_add_product"
						class="wc-product-search"
						style="width: 50%;"
						data-placeholder="<?php esc_attr_e( 'Search for a product…', 'codeholt-bundles-for-woocommerce' ); ?>"
						data-action="cbfw_json_search_products"
						data-exclude="<?php echo esc_attr( $post->ID ); ?>">
					</select>
					<?php echo wc_help_tip( apply_filters( 'cbfw_child_search_help', __( 'Any published, purchasable product can be bundled, including variations. For a variable product, search for the specific variation you want.', 'codeholt-bundles-for-woocommerce' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>

				<table class="widefat striped cbfw-items-table">
					<thead>
						<tr>
							<th class="cbfw-col-sort" aria-label="<?php esc_attr_e( 'Sort', 'codeholt-bundles-for-woocommerce' ); ?>"></th>
							<th><?php esc_html_e( 'Product', 'codeholt-bundles-for-woocommerce' ); ?></th>
							<th class="cbfw-col-price"><?php esc_html_e( 'Price', 'codeholt-bundles-for-woocommerce' ); ?></th>
							<th class="cbfw-col-qty"><?php esc_html_e( 'Quantity', 'codeholt-bundles-for-woocommerce' ); ?></th>
							<th class="cbfw-col-hide"><?php esc_html_e( 'Hide', 'codeholt-bundles-for-woocommerce' ); ?></th>
							<th class="cbfw-col-remove" aria-label="<?php esc_attr_e( 'Remove', 'codeholt-bundles-for-woocommerce' ); ?>"></th>
						</tr>
					</thead>
					<tbody id="cbfw_items_body">
						<?php
						foreach ( $bundle_items as $index => $item ) {
							self::render_item_row( absint( $item['id'] ), absint( $item['qty'] ), ! empty( $item['hidden'] ), $index );
						}
						?>
					</tbody>
					<tfoot>
						<tr class="cbfw-empty-row" <?php echo $bundle_items ? 'style="display:none;"' : ''; ?>>
							<td colspan="6"><?php esc_html_e( 'No products in this bundle yet — search above to add some.', 'codeholt-bundles-for-woocommerce' ); ?></td>
						</tr>
						<tr class="cbfw-totals-row">
							<td colspan="2"><strong><?php esc_html_e( 'Products total', 'codeholt-bundles-for-woocommerce' ); ?></strong></td>
							<td colspan="4">
								<span class="cbfw-total-regular"></span>
								<span class="cbfw-total-savings"></span>
							</td>
						</tr>
					</tfoot>
				</table>
				<p class="description cbfw-builder-hint"><?php esc_html_e( 'Drag rows to reorder. Prices and savings are recalculated when you update the product.', 'codeholt-bundles-for-woocommerce' ); ?></p>
			</div>

			<?php
			/**
			 * Fires at the end of the bundle builder panel.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_Post $post Current product post.
			 */
			do_action( 'cbfw_after_builder_panel', $post );
			?>
		</div>
		<?php
	}

	/**
	 * Render a single builder row (also used by AJAX when adding a product).
	 *
	 * @param int  $product_id Product ID.
	 * @param int  $qty        Quantity.
	 * @param bool $hidden     Hidden flag.
	 * @param int  $index      Row index.
	 */
	public static function render_item_row( $product_id, $qty = 1, $hidden = false, $index = 0 ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return;
		}
		?>
		<tr class="cbfw-item" data-price="<?php echo esc_attr( (float) $product->get_price() ); ?>" data-regular="<?php echo esc_attr( (float) $product->get_regular_price() ); ?>">
			<td class="cbfw-col-sort"><span class="dashicons dashicons-menu" aria-hidden="true"></span></td>
			<td class="cbfw-col-product">
				<?php echo wp_kses_post( $product->get_image( 'cbfw_item_thumb' ) ); ?>
				<a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>" target="_blank">
					<?php echo esc_html( $product->get_formatted_name() ); ?>
				</a>
				<input type="hidden" class="cbfw-item-id" name="cbfw_items[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $product_id ); ?>" />
			</td>
			<td class="cbfw-col-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></td>
			<td class="cbfw-col-qty">
				<input type="number" class="cbfw-item-qty" name="cbfw_items[<?php echo esc_attr( $index ); ?>][qty]" value="<?php echo esc_attr( max( 1, $qty ) ); ?>" min="1" step="1" />
			</td>
			<td class="cbfw-col-hide">
				<input type="checkbox" name="cbfw_items[<?php echo esc_attr( $index ); ?>][hidden]" value="1" <?php checked( $hidden ); ?> title="<?php esc_attr_e( 'Hide this product on the bundle page', 'codeholt-bundles-for-woocommerce' ); ?>" />
			</td>
			<td class="cbfw-col-remove">
				<button type="button" class="button-link cbfw-remove-item" aria-label="<?php esc_attr_e( 'Remove from bundle', 'codeholt-bundles-for-woocommerce' ); ?>">&times;</button>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save bundle data.
	 *
	 * @param WC_Product $product Product being saved.
	 */
	public function save( $product ) {
		if ( 'cbfw_bundle' !== $product->get_type() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by WooCommerce before this hook fires.
		$raw_items = isset( $_POST['cbfw_items'] ) && is_array( $_POST['cbfw_items'] ) ? wp_unslash( $_POST['cbfw_items'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$mode      = isset( $_POST['_cbfw_pricing_mode'] ) ? sanitize_key( wp_unslash( $_POST['_cbfw_pricing_mode'] ) ) : 'auto';
		$fixed     = isset( $_POST['_cbfw_fixed_price'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_cbfw_fixed_price'] ) ) ) : '';
		$layout    = isset( $_POST['_cbfw_layout'] ) ? sanitize_key( wp_unslash( $_POST['_cbfw_layout'] ) ) : '';
		// phpcs:enable

		$items = array();
		$seen  = array();

		foreach ( $raw_items as $raw ) {
			$id = isset( $raw['id'] ) ? absint( $raw['id'] ) : 0;

			if ( ! $id || isset( $seen[ $id ] ) || $id === $product->get_id() ) {
				continue;
			}

			$child = wc_get_product( $id );

			if ( ! cbfw_can_bundle_product( $child ) ) {
				continue;
			}

			$seen[ $id ] = true;
			$items[]     = array(
				'id'     => $id,
				'qty'    => isset( $raw['qty'] ) ? max( 1, absint( $raw['qty'] ) ) : 1,
				'hidden' => ! empty( $raw['hidden'] ),
			);
		}

		/**
		 * Filter bundle items before saving.
		 *
		 * @since 1.0.0
		 *
		 * @param array      $items   Sanitized items.
		 * @param WC_Product $product Bundle product.
		 */
		$items = apply_filters( 'cbfw_save_bundled_items', $items, $product );

		$product->update_meta_data( '_cbfw_bundled_items', $items );
		$product->update_meta_data( '_cbfw_pricing_mode', in_array( $mode, cbfw_get_pricing_modes(), true ) ? $mode : 'auto' );
		$product->update_meta_data( '_cbfw_fixed_price', $fixed );
		$layout_valid = array_key_exists( $layout, cbfw_get_product_layouts() );
		$product->update_meta_data( '_cbfw_layout', $layout_valid ? $layout : '' );

		// Rebuild the lookup index used to find bundles by child product.
		$product->delete_meta_data( '_cbfw_contains' );
		foreach ( $items as $item ) {
			$product->add_meta_data( '_cbfw_contains', (string) $item['id'] );
		}

		// Recalculate price + stock status before WooCommerce persists the object.
		if ( is_a( $product, 'CBFW_Product_Bundle' ) ) {
			CBFW_Sync::apply_pricing( $product );
			CBFW_Sync::apply_stock_status( $product );
		}
	}

	/**
	 * Enqueue admin assets on the product edit screen.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== get_current_screen()->post_type ) {
			return;
		}

		wp_enqueue_style( 'cbfw-admin', CBFW_PLUGIN_URL . 'assets/css/admin.css', array(), CBFW_VERSION );
		wp_enqueue_script( 'cbfw-admin', CBFW_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wc-enhanced-select' ), CBFW_VERSION, true );

		wp_localize_script(
			'cbfw-admin',
			'cbfwAdmin',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'cbfw-admin' ),
				'currency'     => get_woocommerce_currency_symbol(),
				'i18n'         => array(
					'save'      => __( 'Savings:', 'codeholt-bundles-for-woocommerce' ),
					'duplicate' => __( 'This product is already in the bundle.', 'codeholt-bundles-for-woocommerce' ),
				),
			)
		);
	}
}
