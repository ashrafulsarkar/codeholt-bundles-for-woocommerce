<?php
/**
 * Import / Export bundles (JSON + CSV export, JSON import).
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Import_Export.
 */
class CBFW_Import_Export {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'admin_post_cbfw_export_json', array( $this, 'export_json' ) );
		add_action( 'admin_post_cbfw_export_csv', array( $this, 'export_csv' ) );
		add_action( 'admin_post_cbfw_import_json', array( $this, 'import_json' ) );
	}

	/**
	 * Collect all bundles as portable arrays.
	 *
	 * @return array
	 */
	protected static function collect() {
		$bundles = wc_get_products(
			array(
				'type'   => 'cbfw_bundle',
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

			/**
			 * Filter a bundle's export row — add-ons can append their own
			 * fields here and restore them via the `cbfw_import_bundle` action.
			 *
			 * @since 1.0.0
			 *
			 * @param array               $row    Export data.
			 * @param CBFW_Product_Bundle $bundle Bundle product.
			 */
			$data[] = apply_filters(
				'cbfw_export_bundle_data',
				array(
					'name'         => $bundle->get_name(),
					'slug'         => $bundle->get_slug(),
					'status'       => $bundle->get_status(),
					'description'  => $bundle->get_description(),
					'short_desc'   => $bundle->get_short_description(),
					'sku'          => $bundle->get_sku(),
					'pricing_mode' => $bundle->get_pricing_mode(),
					'fixed_price'  => $bundle->get_fixed_price(),
					'items'        => $items,
				),
				$bundle
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
			wp_die( esc_html__( 'You are not allowed to do this.', 'codeholt-bundles-for-woocommerce' ) );
		}
		check_admin_referer( $action );
	}

	/**
	 * Export all bundles as JSON.
	 */
	public function export_json() {
		$this->guard( 'cbfw_export' );

		$payload = array(
			'plugin'    => 'codeholt-bundles-for-woocommerce',
			'version'   => CBFW_VERSION,
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
		$this->guard( 'cbfw_export' );

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
				array_map(
					array( __CLASS__, 'escape_csv_cell' ),
					array(
						$bundle['name'],
						$bundle['slug'],
						$bundle['status'],
						$bundle['sku'],
						$bundle['pricing_mode'],
						$bundle['fixed_price'],
						implode( '|', $items ),
					)
				)
			);
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Neutralize CSV formula injection by prefixing a leading
	 * `= + - @` (or tab/CR) with a single quote, as spreadsheet
	 * apps treat such cells as executable formulas.
	 *
	 * @param string $cell Raw cell value.
	 * @return string
	 */
	protected static function escape_csv_cell( $cell ) {
		$cell = (string) $cell;

		if ( '' !== $cell && false !== strpos( "=+-@\t\r", $cell[0] ) ) {
			return "'" . $cell;
		}

		return $cell;
	}

	/**
	 * Import bundles from an uploaded JSON file.
	 */
	public function import_json() {
		$this->guard( 'cbfw_import' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in guard() via check_admin_referer() above.
		if ( empty( $_FILES['cbfw_import_file'] ) || ! isset( $_FILES['cbfw_import_file']['error'] ) ) {
			$this->redirect_with( 'error', __( 'No file uploaded.', 'codeholt-bundles-for-woocommerce' ) );
		}

		$error = (int) $_FILES['cbfw_import_file']['error'];

		if ( UPLOAD_ERR_NO_FILE === $error ) {
			$this->redirect_with( 'error', __( 'No file uploaded.', 'codeholt-bundles-for-woocommerce' ) );
		}

		if ( UPLOAD_ERR_INI_SIZE === $error || UPLOAD_ERR_FORM_SIZE === $error ) {
			$this->redirect_with( 'error', __( 'The file is larger than this server allows. Try importing fewer bundles at a time.', 'codeholt-bundles-for-woocommerce' ) );
		}

		if ( UPLOAD_ERR_OK !== $error ) {
			$this->redirect_with( 'error', __( 'The file could not be uploaded. Please try again.', 'codeholt-bundles-for-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Server-generated upload path, validated by is_uploaded_file() below.
		$tmp_name = wp_unslash( $_FILES['cbfw_import_file']['tmp_name'] );

		// Guard against anything that is not a genuine PHP upload.
		if ( ! is_string( $tmp_name ) || ! is_uploaded_file( $tmp_name ) ) {
			$this->redirect_with( 'error', __( 'The file could not be read. Please try again.', 'codeholt-bundles-for-woocommerce' ) );
		}

		$filename = isset( $_FILES['cbfw_import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['cbfw_import_file']['name'] ) ) : '';
		$filetype = wp_check_filetype( $filename, array( 'json' => 'application/json' ) );

		if ( 'json' !== $filetype['ext'] ) {
			$this->redirect_with( 'error', __( 'Please upload a .json file exported from this plugin.', 'codeholt-bundles-for-woocommerce' ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a validated temporary upload, not a remote resource.
		$raw = file_get_contents( $tmp_name );

		if ( false === $raw ) {
			$this->redirect_with( 'error', __( 'The file could not be read. Please try again.', 'codeholt-bundles-for-woocommerce' ) );
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || empty( $data['bundles'] ) || ! is_array( $data['bundles'] ) ) {
			$this->redirect_with( 'error', __( 'Invalid file — expected a JSON export from this plugin.', 'codeholt-bundles-for-woocommerce' ) );
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

			$name         = is_string( $bundle_data['name'] ?? null ) ? $bundle_data['name'] : '';
			$status       = is_string( $bundle_data['status'] ?? null ) ? $bundle_data['status'] : 'draft';
			$description  = is_string( $bundle_data['description'] ?? null ) ? $bundle_data['description'] : '';
			$short_desc   = is_string( $bundle_data['short_desc'] ?? null ) ? $bundle_data['short_desc'] : '';
			$sku          = is_string( $bundle_data['sku'] ?? null ) ? $bundle_data['sku'] : '';
			$pricing_mode = is_string( $bundle_data['pricing_mode'] ?? null ) ? $bundle_data['pricing_mode'] : 'auto';
			$fixed_price  = is_scalar( $bundle_data['fixed_price'] ?? null ) ? $bundle_data['fixed_price'] : '';

			$bundle = new CBFW_Product_Bundle();
			$bundle->set_name( sanitize_text_field( $name ) );
			$bundle->set_status( in_array( $status, array( 'publish', 'draft' ), true ) ? $status : 'draft' );
			$bundle->set_description( wp_kses_post( $description ) );
			$bundle->set_short_description( wp_kses_post( $short_desc ) );

			if ( '' !== $sku && ! wc_get_product_id_by_sku( wc_clean( $sku ) ) ) {
				$bundle->set_sku( wc_clean( $sku ) );
			}

			$bundle->update_meta_data( '_cbfw_bundled_items', $items );
			$bundle->update_meta_data( '_cbfw_pricing_mode', in_array( $pricing_mode, cbfw_get_pricing_modes(), true ) ? $pricing_mode : 'auto' );
			$bundle->update_meta_data( '_cbfw_fixed_price', wc_format_decimal( $fixed_price ) );

			foreach ( $items as $item ) {
				$bundle->add_meta_data( '_cbfw_contains', (string) $item['id'] );
			}

			$bundle->save();

			/**
			 * Fires after a bundle is created from an import row, before
			 * prices are resynced — add-ons restore their own fields here.
			 *
			 * @since 1.0.0
			 *
			 * @param CBFW_Product_Bundle $bundle      Imported bundle.
			 * @param array               $bundle_data Raw import row.
			 */
			do_action( 'cbfw_import_bundle', $bundle, $bundle_data );

			// Assign the bundle product type term + recalc prices.
			wp_set_object_terms( $bundle->get_id(), 'cbfw_bundle', 'product_type' );
			CBFW_Sync::sync_bundle( $bundle->get_id() );

			$created++;
		}

		/* translators: %d: number of bundles imported. */
		$this->redirect_with( 'success', sprintf( __( '%d bundle(s) imported.', 'codeholt-bundles-for-woocommerce' ), $created ) );
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
					'page'         => 'cbfw-bundles',
					'tab'          => 'import_export',
					'cbfw_notice'  => rawurlencode( $message ),
					'cbfw_type'    => $type,
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
		if ( ! empty( $_GET['cbfw_notice'] ) ) {
			$type = ( isset( $_GET['cbfw_type'] ) && 'success' === $_GET['cbfw_type'] ) ? 'success' : 'error';
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['cbfw_notice'] ) ) ) ) . '</p></div>';
		}
		// phpcs:enable

		$bundle_count = count(
			wc_get_products(
				array(
					'type'   => 'cbfw_bundle',
					'status' => array( 'publish', 'draft' ),
					'limit'  => -1,
					'return' => 'ids',
				)
			)
		);
		?>
		<div class="cbfw-ie-grid">

			<section class="cbfw-panel-card cbfw-ie-card">
				<div class="cbfw-ie-card__icon cbfw-ie-card__icon--export" aria-hidden="true">
					<span class="dashicons dashicons-download"></span>
				</div>
				<h2><?php esc_html_e( 'Export bundles', 'codeholt-bundles-for-woocommerce' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Download every bundle with its products, quantities and pricing settings.', 'codeholt-bundles-for-woocommerce' ); ?></p>

				<ul class="cbfw-ie-list">
					<li>
						<?php
						printf(
							/* translators: %s: number of bundles. */
							esc_html( _n( '%s bundle will be exported (published and draft).', '%s bundles will be exported (published and draft).', $bundle_count, 'codeholt-bundles-for-woocommerce' ) ),
							'<strong>' . esc_html( number_format_i18n( $bundle_count ) ) . '</strong>'
						);
						?>
					</li>
					<li><?php esc_html_e( 'JSON keeps everything and can be re-imported on any site.', 'codeholt-bundles-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'CSV is a flat list for spreadsheets — export only.', 'codeholt-bundles-for-woocommerce' ); ?></li>
				</ul>

				<p class="cbfw-ie-actions">
					<a class="button button-primary button-hero" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cbfw_export_json' ), 'cbfw_export' ) ); ?>">
						<span class="dashicons dashicons-media-code" aria-hidden="true"></span>
						<?php esc_html_e( 'Export JSON', 'codeholt-bundles-for-woocommerce' ); ?>
					</a>
					<a class="button button-hero" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cbfw_export_csv' ), 'cbfw_export' ) ); ?>">
						<span class="dashicons dashicons-media-spreadsheet" aria-hidden="true"></span>
						<?php esc_html_e( 'Export CSV', 'codeholt-bundles-for-woocommerce' ); ?>
					</a>
				</p>
			</section>

			<section class="cbfw-panel-card cbfw-ie-card">
				<div class="cbfw-ie-card__icon cbfw-ie-card__icon--import" aria-hidden="true">
					<span class="dashicons dashicons-upload"></span>
				</div>
				<h2><?php esc_html_e( 'Import bundles', 'codeholt-bundles-for-woocommerce' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Upload a JSON export from this plugin — new bundles are created, nothing is overwritten.', 'codeholt-bundles-for-woocommerce' ); ?></p>

				<ul class="cbfw-ie-list">
					<li><?php esc_html_e( 'Bundled products are matched by SKU first, then by product ID.', 'codeholt-bundles-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Products missing on this site are skipped safely.', 'codeholt-bundles-for-woocommerce' ); ?></li>
				</ul>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="cbfw_import_json" />
					<?php wp_nonce_field( 'cbfw_import' ); ?>

					<label class="cbfw-ie-dropzone" for="cbfw_import_file">
						<span class="dashicons dashicons-media-archive" aria-hidden="true"></span>
						<span class="cbfw-ie-dropzone__text"><?php esc_html_e( 'Choose a .json export file', 'codeholt-bundles-for-woocommerce' ); ?></span>
						<span class="cbfw-ie-dropzone__filename" data-placeholder="<?php esc_attr_e( 'No file chosen', 'codeholt-bundles-for-woocommerce' ); ?>"><?php esc_html_e( 'No file chosen', 'codeholt-bundles-for-woocommerce' ); ?></span>
						<input type="file" id="cbfw_import_file" name="cbfw_import_file" accept=".json,application/json" required />
					</label>

					<p class="cbfw-ie-actions">
						<button type="submit" class="button button-primary button-hero">
							<span class="dashicons dashicons-upload" aria-hidden="true"></span>
							<?php esc_html_e( 'Import bundles', 'codeholt-bundles-for-woocommerce' ); ?>
						</button>
					</p>
				</form>
			</section>

		</div>
		<?php
	}
}
