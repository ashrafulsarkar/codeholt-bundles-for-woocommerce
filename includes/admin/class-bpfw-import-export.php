<?php
/**
 * Import / Export bundles (JSON + CSV export, JSON import).
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Import_Export.
 */
class BPFW_Import_Export {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'admin_post_bpfw_export_json', array( $this, 'export_json' ) );
		add_action( 'admin_post_bpfw_export_csv', array( $this, 'export_csv' ) );
		add_action( 'admin_post_bpfw_import_json', array( $this, 'import_json' ) );
	}

	/**
	 * Collect all bundles as portable arrays.
	 *
	 * @return array
	 */
	protected static function collect() {
		$bundles = wc_get_products(
			array(
				'type'   => 'bundle',
				'status' => array( 'publish', 'draft' ),
				'limit'  => -1,
			)
		);

		$data = array();

		foreach ( $bundles as $bundle ) {
			$items = array();

			foreach ( $bundle->get_bundled_items() as $item ) {
				$child   = wc_get_product( $item['id'] );
				$items[] = array(
					'product_id' => (int) $item['id'],
					'sku'        => $child ? $child->get_sku() : '',
					'qty'        => (int) $item['qty'],
					'hidden'     => ! empty( $item['hidden'] ),
				);
			}

			$data[] = array(
				'name'         => $bundle->get_name(),
				'slug'         => $bundle->get_slug(),
				'status'       => $bundle->get_status(),
				'description'  => $bundle->get_description(),
				'short_desc'   => $bundle->get_short_description(),
				'sku'          => $bundle->get_sku(),
				'pricing_mode' => $bundle->get_pricing_mode(),
				'fixed_price'  => $bundle->get_fixed_price(),
				'items'        => $items,
			);
		}

		return $data;
	}

	/**
	 * Verify request permissions + nonce for an action.
	 *
	 * @param string $action Nonce action.
	 */
	protected function guard( $action ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'bundle-product-for-woocommerce' ) );
		}
		check_admin_referer( $action );
	}

	/**
	 * Export all bundles as JSON.
	 */
	public function export_json() {
		$this->guard( 'bpfw_export' );

		$payload = array(
			'plugin'    => 'bundle-product-for-woocommerce',
			'version'   => BPFW_VERSION,
			'exported'  => gmdate( 'c' ),
			'bundles'   => self::collect(),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=bundles-' . gmdate( 'Y-m-d' ) . '.json' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Export all bundles as CSV.
	 */
	public function export_csv() {
		$this->guard( 'bpfw_export' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=bundles-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		fputcsv( $out, array( 'name', 'slug', 'status', 'sku', 'pricing_mode', 'fixed_price', 'items' ) );

		foreach ( self::collect() as $bundle ) {
			$items = array();
			foreach ( $bundle['items'] as $item ) {
				// id:sku:qty:hidden joined with |.
				$items[] = $item['product_id'] . ':' . $item['sku'] . ':' . $item['qty'] . ':' . ( $item['hidden'] ? 1 : 0 );
			}

			fputcsv(
				$out,
				array(
					$bundle['name'],
					$bundle['slug'],
					$bundle['status'],
					$bundle['sku'],
					$bundle['pricing_mode'],
					$bundle['fixed_price'],
					implode( '|', $items ),
				)
			);
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Import bundles from an uploaded JSON file.
	 */
	public function import_json() {
		$this->guard( 'bpfw_import' );

		if ( empty( $_FILES['bpfw_import_file']['tmp_name'] ) ) {
			$this->redirect_with( 'error', __( 'No file uploaded.', 'bundle-product-for-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$raw  = file_get_contents( wp_unslash( $_FILES['bpfw_import_file']['tmp_name'] ) );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || empty( $data['bundles'] ) || ! is_array( $data['bundles'] ) ) {
			$this->redirect_with( 'error', __( 'Invalid file — expected a JSON export from this plugin.', 'bundle-product-for-woocommerce' ) );
		}

		$created = 0;

		foreach ( $data['bundles'] as $bundle_data ) {
			if ( empty( $bundle_data['name'] ) ) {
				continue;
			}

			$items = array();

			foreach ( (array) ( $bundle_data['items'] ?? array() ) as $item ) {
				$child_id = 0;

				// Prefer SKU match (portable across sites), fall back to product ID.
				if ( ! empty( $item['sku'] ) ) {
					$child_id = wc_get_product_id_by_sku( wc_clean( $item['sku'] ) );
				}
				if ( ! $child_id && ! empty( $item['product_id'] ) ) {
					$maybe = wc_get_product( absint( $item['product_id'] ) );
					if ( $maybe && $maybe->is_type( 'simple' ) ) {
						$child_id = $maybe->get_id();
					}
				}

				if ( $child_id ) {
					$items[] = array(
						'id'     => $child_id,
						'qty'    => max( 1, absint( $item['qty'] ?? 1 ) ),
						'hidden' => ! empty( $item['hidden'] ),
					);
				}
			}

			$bundle = new BPFW_Product_Bundle();
			$bundle->set_name( sanitize_text_field( $bundle_data['name'] ) );
			$bundle->set_status( in_array( $bundle_data['status'] ?? 'draft', array( 'publish', 'draft' ), true ) ? $bundle_data['status'] : 'draft' );
			$bundle->set_description( wp_kses_post( $bundle_data['description'] ?? '' ) );
			$bundle->set_short_description( wp_kses_post( $bundle_data['short_desc'] ?? '' ) );

			if ( ! empty( $bundle_data['sku'] ) && ! wc_get_product_id_by_sku( wc_clean( $bundle_data['sku'] ) ) ) {
				$bundle->set_sku( wc_clean( $bundle_data['sku'] ) );
			}

			$bundle->update_meta_data( '_bpfw_bundled_items', $items );
			$bundle->update_meta_data( '_bpfw_pricing_mode', in_array( $bundle_data['pricing_mode'] ?? 'auto', array( 'auto', 'fixed' ), true ) ? $bundle_data['pricing_mode'] : 'auto' );
			$bundle->update_meta_data( '_bpfw_fixed_price', wc_format_decimal( $bundle_data['fixed_price'] ?? '' ) );

			foreach ( $items as $item ) {
				$bundle->add_meta_data( '_bpfw_contains', (string) $item['id'] );
			}

			$bundle->save();

			// Assign the bundle product type term + recalc prices.
			wp_set_object_terms( $bundle->get_id(), 'bundle', 'product_type' );
			BPFW_Sync::sync_bundle( $bundle->get_id() );

			$created++;
		}

		/* translators: %d: number of bundles imported. */
		$this->redirect_with( 'success', sprintf( __( '%d bundle(s) imported.', 'bundle-product-for-woocommerce' ), $created ) );
	}

	/**
	 * Redirect back to the Import/Export tab with a notice.
	 *
	 * @param string $type    Notice type.
	 * @param string $message Message.
	 */
	protected function redirect_with( $type, $message ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'         => 'bpfw-bundles',
					'tab'          => 'import_export',
					'bpfw_notice'  => rawurlencode( $message ),
					'bpfw_type'    => $type,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render the Import/Export tab UI.
	 */
	public static function render_tab() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Display-only notice.
		if ( ! empty( $_GET['bpfw_notice'] ) ) {
			$type = ( isset( $_GET['bpfw_type'] ) && 'success' === $_GET['bpfw_type'] ) ? 'success' : 'error';
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['bpfw_notice'] ) ) ) ) . '</p></div>';
		}
		// phpcs:enable
		?>
		<h2><?php esc_html_e( 'Export bundles', 'bundle-product-for-woocommerce' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Download all bundles including their products, quantities and pricing settings.', 'bundle-product-for-woocommerce' ); ?></p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=bpfw_export_json' ), 'bpfw_export' ) ); ?>"><?php esc_html_e( 'Export JSON', 'bundle-product-for-woocommerce' ); ?></a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=bpfw_export_csv' ), 'bpfw_export' ) ); ?>"><?php esc_html_e( 'Export CSV', 'bundle-product-for-woocommerce' ); ?></a>
		</p>

		<hr />

		<h2><?php esc_html_e( 'Import bundles', 'bundle-product-for-woocommerce' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Upload a JSON export. Bundled products are matched by SKU first, then by product ID.', 'bundle-product-for-woocommerce' ); ?></p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="bpfw_import_json" />
			<?php wp_nonce_field( 'bpfw_import' ); ?>
			<p><input type="file" name="bpfw_import_file" accept=".json,application/json" required /></p>
			<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Import', 'bundle-product-for-woocommerce' ); ?></button></p>
		</form>
		<?php
	}
}
