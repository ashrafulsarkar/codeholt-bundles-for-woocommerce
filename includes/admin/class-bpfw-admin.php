<?php
/**
 * Admin: product data tab, bundle builder panel and save logic.
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Admin.
 */
class BPFW_Admin {

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
		add_image_size( 'bpfw_item_thumb', 40, 40, true );
	}

	/**
	 * Add the "Bundled Products" tab.
	 *
	 * @param array $tabs Product data tabs.
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['bpfw_bundled_products'] = array(
			'label'    => __( 'Bundled Products', 'codeholt-bundles-for-woocommerce' ),
			'target'   => 'bpfw_bundled_products_data',
			'class'    => array( 'show_if_bundle' ),
			'priority' => 21,
		);
		return $tabs;
	}

	/**
	 * Render the bundle builder panel.
	 */
	public function render_panel() {
		global $post;

		$bundle_items = get_post_meta( $post->ID, '_bpfw_bundled_items', true );
		$bundle_items = is_array( $bundle_items ) ? $bundle_items : array();
		$pricing_mode = get_post_meta( $post->ID, '_bpfw_pricing_mode', true );
		$pricing_mode = in_array( $pricing_mode, array( 'auto', 'fixed' ), true ) ? $pricing_mode : 'auto';
		$fixed_price  = get_post_meta( $post->ID, '_bpfw_fixed_price', true );
		$layout       = get_post_meta( $post->ID, '_bpfw_layout', true );
		$layout       = array_key_exists( $layout, bpfw_get_product_layouts() ) ? $layout : '';
		?>
		<div id="bpfw_bundled_products_data" class="panel woocommerce_options_panel hidden">

			<div class="options_group">
				<p class="form-field">
					<label for="bpfw_pricing_mode"><?php esc_html_e( 'Pricing mode', 'codeholt-bundles-for-woocommerce' ); ?></label>
					<select id="bpfw_pricing_mode" name="_bpfw_pricing_mode" class="select short">
						<?php foreach ( bpfw_get_pricing_mode_options() as $bpfw_mode => $bpfw_label ) : ?>
							<option value="<?php echo esc_attr( $bpfw_mode ); ?>" <?php selected( $pricing_mode, $bpfw_mode ); ?>><?php echo esc_html( $bpfw_label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo wc_help_tip( __( 'Auto: bundle price is the sum of bundled product prices. Fixed: you set one bundle price, savings are calculated automatically.', 'codeholt-bundles-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>

				<p class="form-field bpfw_fixed_price_field">
					<label for="bpfw_fixed_price">
						<?php
						/* translators: %s: currency symbol. */
						printf( esc_html__( 'Bundle price (%s)', 'codeholt-bundles-for-woocommerce' ), esc_html( get_woocommerce_currency_symbol() ) );
						?>
					</label>
					<input type="text" id="bpfw_fixed_price" name="_bpfw_fixed_price" class="short wc_input_price" value="<?php echo esc_attr( wc_format_localized_price( $fixed_price ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. 99', 'codeholt-bundles-for-woocommerce' ); ?>" />
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
				do_action( 'bpfw_pricing_fields', $post );
				?>
			</div>

			<div class="options_group">
				<p class="form-field bpfw-layout-field">
					<label><?php esc_html_e( 'Product page layout', 'codeholt-bundles-for-woocommerce' ); ?></label>
					<span class="bpfw-choices">
						<?php
						BPFW_Settings::layout_choice( '_bpfw_layout', '', $layout, __( 'Default', 'codeholt-bundles-for-woocommerce' ), 'default' );
						foreach ( bpfw_get_product_layouts() as $bpfw_slug => $bpfw_label ) {
							BPFW_Settings::layout_choice( '_bpfw_layout', $bpfw_slug, $layout, $bpfw_label, $bpfw_slug );
						}
						?>
					</span>
					<?php echo wc_help_tip( __( 'How bundled products are shown on this bundle\'s page. "Default" follows WooCommerce → Bundles → Settings.', 'codeholt-bundles-for-woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			</div>

			<div class="options_group bpfw-builder">
				<p class="form-field">
					<label for="bpfw_add_product"><?php esc_html_e( 'Add product', 'codeholt-bundles-for-woocommerce' ); ?></label>
					<select
						id="bpfw_add_product"
						class="wc-product-search"
						style="width: 50%;"
						data-placeholder="<?php esc_attr_e( 'Search for a simple product…', 'codeholt-bundles-for-woocommerce' ); ?>"
						data-action="bpfw_json_search_products"
						data-exclude="<?php echo esc_attr( $post->ID ); ?>">
					</select>
					<?php echo wc_help_tip( apply_filters( 'bpfw_child_search_help', __( 'Only published simple products can be bundled in the free version.', 'codeholt-bundles-for-woocommerce' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>

				<table class="widefat striped bpfw-items-table">
					<thead>
						<tr>
							<th class="bpfw-col-sort" aria-label="<?php esc_attr_e( 'Sort', 'codeholt-bundles-for-woocommerce' ); ?>"></th>
							<th><?php esc_html_e( 'Product', 'codeholt-bundles-for-woocommerce' ); ?></th>
							<th class="bpfw-col-price"><?php esc_html_e( 'Price', 'codeholt-bundles-for-woocommerce' ); ?></th>
							<th class="bpfw-col-qty"><?php esc_html_e( 'Quantity', 'codeholt-bundles-for-woocommerce' ); ?></th>
							<th class="bpfw-col-hide"><?php esc_html_e( 'Hide', 'codeholt-bundles-for-woocommerce' ); ?></th>
							<th class="bpfw-col-remove" aria-label="<?php esc_attr_e( 'Remove', 'codeholt-bundles-for-woocommerce' ); ?>"></th>
						</tr>
					</thead>
					<tbody id="bpfw_items_body">
						<?php
						foreach ( $bundle_items as $index => $item ) {
							self::render_item_row( absint( $item['id'] ), absint( $item['qty'] ), ! empty( $item['hidden'] ), $index );
						}
						?>
					</tbody>
					<tfoot>
						<tr class="bpfw-empty-row" <?php echo $bundle_items ? 'style="display:none;"' : ''; ?>>
							<td colspan="6"><?php esc_html_e( 'No products in this bundle yet — search above to add some.', 'codeholt-bundles-for-woocommerce' ); ?></td>
						</tr>
						<tr class="bpfw-totals-row">
							<td colspan="2"><strong><?php esc_html_e( 'Products total', 'codeholt-bundles-for-woocommerce' ); ?></strong></td>
							<td colspan="4">
								<span class="bpfw-total-regular"></span>
								<span class="bpfw-total-savings"></span>
							</td>
						</tr>
					</tfoot>
				</table>
				<p class="description bpfw-builder-hint"><?php esc_html_e( 'Drag rows to reorder. Prices and savings are recalculated when you update the product.', 'codeholt-bundles-for-woocommerce' ); ?></p>
			</div>

			<?php
			/**
			 * Fires at the end of the bundle builder panel.
			 *
			 * @since 1.0.0
			 *
			 * @param WP_Post $post Current product post.
			 */
			do_action( 'bpfw_after_builder_panel', $post );
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
		<tr class="bpfw-item" data-price="<?php echo esc_attr( (float) $product->get_price() ); ?>" data-regular="<?php echo esc_attr( (float) $product->get_regular_price() ); ?>">
			<td class="bpfw-col-sort"><span class="dashicons dashicons-menu" aria-hidden="true"></span></td>
			<td class="bpfw-col-product">
				<?php echo wp_kses_post( $product->get_image( 'bpfw_item_thumb' ) ); ?>
				<a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>" target="_blank">
					<?php echo esc_html( $product->get_formatted_name() ); ?>
				</a>
				<input type="hidden" class="bpfw-item-id" name="bpfw_items[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $product_id ); ?>" />
			</td>
			<td class="bpfw-col-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></td>
			<td class="bpfw-col-qty">
				<input type="number" class="bpfw-item-qty" name="bpfw_items[<?php echo esc_attr( $index ); ?>][qty]" value="<?php echo esc_attr( max( 1, $qty ) ); ?>" min="1" step="1" />
			</td>
			<td class="bpfw-col-hide">
				<input type="checkbox" name="bpfw_items[<?php echo esc_attr( $index ); ?>][hidden]" value="1" <?php checked( $hidden ); ?> title="<?php esc_attr_e( 'Hide this product on the bundle page', 'codeholt-bundles-for-woocommerce' ); ?>" />
			</td>
			<td class="bpfw-col-remove">
				<button type="button" class="button-link bpfw-remove-item" aria-label="<?php esc_attr_e( 'Remove from bundle', 'codeholt-bundles-for-woocommerce' ); ?>">&times;</button>
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
		if ( 'bundle' !== $product->get_type() ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by WooCommerce before this hook fires.
		$raw_items = isset( $_POST['bpfw_items'] ) && is_array( $_POST['bpfw_items'] ) ? wp_unslash( $_POST['bpfw_items'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$mode      = isset( $_POST['_bpfw_pricing_mode'] ) ? sanitize_key( wp_unslash( $_POST['_bpfw_pricing_mode'] ) ) : 'auto';
		$fixed     = isset( $_POST['_bpfw_fixed_price'] ) ? wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['_bpfw_fixed_price'] ) ) ) : '';
		$layout    = isset( $_POST['_bpfw_layout'] ) ? sanitize_key( wp_unslash( $_POST['_bpfw_layout'] ) ) : '';
		// phpcs:enable

		$items = array();
		$seen  = array();

		foreach ( $raw_items as $raw ) {
			$id = isset( $raw['id'] ) ? absint( $raw['id'] ) : 0;

			if ( ! $id || isset( $seen[ $id ] ) || $id === $product->get_id() ) {
				continue;
			}

			$child = wc_get_product( $id );

			if ( ! $child || ! in_array( $child->get_type(), bpfw_get_allowed_child_types(), true ) ) {
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
		$items = apply_filters( 'bpfw_save_bundled_items', $items, $product );

		$product->update_meta_data( '_bpfw_bundled_items', $items );
		$product->update_meta_data( '_bpfw_pricing_mode', in_array( $mode, bpfw_get_pricing_modes(), true ) ? $mode : 'auto' );
		$product->update_meta_data( '_bpfw_fixed_price', $fixed );
		$layout_valid = array_key_exists( $layout, bpfw_get_product_layouts() ) && ( bpfw_is_pro() || ! bpfw_layout_requires_pro( $layout ) );
		$product->update_meta_data( '_bpfw_layout', $layout_valid ? $layout : '' );

		// Rebuild the lookup index used to find bundles by child product.
		$product->delete_meta_data( '_bpfw_contains' );
		foreach ( $items as $item ) {
			$product->add_meta_data( '_bpfw_contains', (string) $item['id'] );
		}

		// Recalculate price + stock status before WooCommerce persists the object.
		if ( is_a( $product, 'BPFW_Product_Bundle' ) ) {
			BPFW_Sync::apply_pricing( $product );
			BPFW_Sync::apply_stock_status( $product );
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

		wp_enqueue_style( 'bpfw-admin', BPFW_PLUGIN_URL . 'assets/css/admin.css', array(), BPFW_VERSION );
		wp_enqueue_script( 'bpfw-admin', BPFW_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wc-enhanced-select' ), BPFW_VERSION, true );

		wp_localize_script(
			'bpfw-admin',
			'bpfwAdmin',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'bpfw-admin' ),
				'currency'     => get_woocommerce_currency_symbol(),
				'i18n'         => array(
					'save'      => __( 'Savings:', 'codeholt-bundles-for-woocommerce' ),
					'duplicate' => __( 'This product is already in the bundle.', 'codeholt-bundles-for-woocommerce' ),
				),
			)
		);
	}
}
