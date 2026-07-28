<?php
/**
 * Basic analytics dashboard (HPOS compatible).
 *
 * @package BPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * BPFW_Analytics.
 */
class BPFW_Analytics {

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 60 );
	}

	/**
	 * Add "Bundles" page under WooCommerce.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Bundles', 'bundle-product-for-woocommerce' ),
			__( 'Bundles', 'bundle-product-for-woocommerce' ),
			'manage_woocommerce',
			'bpfw-bundles',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Query bundle sales stats for the last N days. Cached for 15 minutes.
	 *
	 * @param int $days Number of days to look back.
	 * @return array{sold:int,revenue:float,savings:float,rows:array}
	 */
	public static function get_stats( $days = 30 ) {
		$days      = max( 1, absint( $days ) );
		$cache_key = 'bpfw_stats_' . $days;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$hpos = class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		$since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// Aggregate reporting query — WooCommerce has no CRUD/API equivalent for
		// this per-bundle rollup. Results are cached in a transient for 15 minutes
		// (see set_transient() below), so no repeated direct queries occur.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $hpos ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT oim_pid.meta_value AS bundle_id,
							SUM( oim_qty.meta_value + 0 ) AS sold,
							SUM( oim_total.meta_value + 0 ) AS revenue,
							SUM( COALESCE( oim_save.meta_value + 0, 0 ) ) AS savings
					 FROM {$wpdb->prefix}woocommerce_order_items oi
					 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_flag  ON oim_flag.order_item_id  = oi.order_item_id AND oim_flag.meta_key  = '_bpfw_bundled_items'
					 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_pid   ON oim_pid.order_item_id   = oi.order_item_id AND oim_pid.meta_key   = '_product_id'
					 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty   ON oim_qty.order_item_id   = oi.order_item_id AND oim_qty.meta_key   = '_qty'
					 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total ON oim_total.order_item_id = oi.order_item_id AND oim_total.meta_key = '_line_total'
					 LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta oim_save  ON oim_save.order_item_id  = oi.order_item_id AND oim_save.meta_key  = '_bpfw_savings'
					 INNER JOIN {$wpdb->prefix}wc_orders o ON o.id = oi.order_id AND o.type = 'shop_order'
					 WHERE o.status IN ('wc-processing','wc-completed') AND o.date_created_gmt >= %s
					 GROUP BY oim_pid.meta_value
					 ORDER BY revenue DESC",
					$since
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT oim_pid.meta_value AS bundle_id,
							SUM( oim_qty.meta_value + 0 ) AS sold,
							SUM( oim_total.meta_value + 0 ) AS revenue,
							SUM( COALESCE( oim_save.meta_value + 0, 0 ) ) AS savings
					 FROM {$wpdb->prefix}woocommerce_order_items oi
					 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_flag  ON oim_flag.order_item_id  = oi.order_item_id AND oim_flag.meta_key  = '_bpfw_bundled_items'
					 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_pid   ON oim_pid.order_item_id   = oi.order_item_id AND oim_pid.meta_key   = '_product_id'
					 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty   ON oim_qty.order_item_id   = oi.order_item_id AND oim_qty.meta_key   = '_qty'
					 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_total ON oim_total.order_item_id = oi.order_item_id AND oim_total.meta_key = '_line_total'
					 LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta oim_save  ON oim_save.order_item_id  = oi.order_item_id AND oim_save.meta_key  = '_bpfw_savings'
					 INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id AND o.post_type = 'shop_order'
					 WHERE o.post_status IN ('wc-processing','wc-completed') AND o.post_date_gmt >= %s
					 GROUP BY oim_pid.meta_value
					 ORDER BY revenue DESC",
					$since
				),
				ARRAY_A
			);
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$stats = array(
			'sold'    => 0,
			'revenue' => 0.0,
			'savings' => 0.0,
			'rows'    => array(),
		);

		foreach ( (array) $rows as $row ) {
			$stats['sold']    += (int) $row['sold'];
			$stats['revenue'] += (float) $row['revenue'];
			$stats['savings'] += (float) $row['savings'];
			$stats['rows'][]   = array(
				'bundle_id' => (int) $row['bundle_id'],
				'name'      => get_the_title( (int) $row['bundle_id'] ),
				'sold'      => (int) $row['sold'],
				'revenue'   => (float) $row['revenue'],
			);
		}

		set_transient( $cache_key, $stats, 15 * MINUTE_IN_SECONDS );

		return $stats;
	}

	/**
	 * Render the page (Overview + Import/Export tabs).
	 */
	public function render_page() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		/**
		 * Filter the tabs on the Bundles admin page (slug => label).
		 * Non-core tabs are rendered via the `bpfw_admin_tab_{slug}` action.
		 *
		 * @since 1.0.0
		 *
		 * @param array $tabs slug => label.
		 */
		$tabs = apply_filters(
			'bpfw_admin_tabs',
			array(
				'overview'      => __( 'Overview', 'bundle-product-for-woocommerce' ),
				'settings'      => __( 'Settings', 'bundle-product-for-woocommerce' ),
				'import_export' => __( 'Import / Export', 'bundle-product-for-woocommerce' ),
			)
		);

		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'overview';
		}
		?>
		<div class="wrap bpfw-admin-page">
			<h1><?php esc_html_e( 'Product Bundles', 'bundle-product-for-woocommerce' ); ?></h1>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $bpfw_slug => $bpfw_label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=bpfw-bundles&tab=' . $bpfw_slug ) ); ?>" class="nav-tab <?php echo $bpfw_slug === $tab ? 'nav-tab-active' : ''; ?>"><?php echo esc_html( $bpfw_label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<?php
			if ( 'import_export' === $tab ) {
				BPFW_Import_Export::render_tab();
			} elseif ( 'settings' === $tab ) {
				BPFW_Settings::render_tab();
			} elseif ( 'overview' === $tab ) {
				$this->render_overview();
			} else {
				/**
				 * Fires to render a custom Bundles page tab.
				 *
				 * @since 1.0.0
				 */
				do_action( 'bpfw_admin_tab_' . $tab );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render the overview tab.
	 */
	protected function render_overview() {
		/**
		 * Filter the Overview reporting period in days.
		 * The Pro add-on hooks this to make the period selectable.
		 *
		 * @since 1.0.0
		 *
		 * @param int $days Days to look back (default 30).
		 */
		$days  = max( 1, absint( apply_filters( 'bpfw_overview_days', 30 ) ) );
		$stats = self::get_stats( $days );
		$top   = $stats['rows'] ? $stats['rows'][0] : null;

		$bundle_count = count(
			wc_get_products(
				array(
					'type'   => 'bundle',
					'status' => 'publish',
					'limit'  => -1,
					'return' => 'ids',
				)
			)
		);
		?>
		<div class="bpfw-toolbar">
			<p class="bpfw-toolbar__text">
				<span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
				<?php
				printf(
					/* translators: %s: number of days. */
					esc_html__( 'Bundle performance for the last %s days — processing and completed orders.', 'bundle-product-for-woocommerce' ),
					esc_html( number_format_i18n( $days ) )
				);
				?>
			</p>
			<div class="bpfw-toolbar__actions">
				<?php
				/**
				 * Fires in the Overview toolbar — add period selectors or
				 * export buttons here (used by the Pro add-on).
				 *
				 * @since 1.0.0
				 *
				 * @param int $days Current reporting period in days.
				 */
				do_action( 'bpfw_overview_actions', $days );
				?>
			</div>
		</div>

		<div class="bpfw-cards">
			<div class="bpfw-stat-card">
				<h3><?php esc_html_e( 'Active bundles', 'bundle-product-for-woocommerce' ); ?></h3>
				<div class="bpfw-stat"><?php echo esc_html( number_format_i18n( $bundle_count ) ); ?></div>
			</div>
			<div class="bpfw-stat-card">
				<h3><?php esc_html_e( 'Bundles sold', 'bundle-product-for-woocommerce' ); ?></h3>
				<div class="bpfw-stat"><?php echo esc_html( number_format_i18n( $stats['sold'] ) ); ?></div>
			</div>
			<div class="bpfw-stat-card">
				<h3><?php esc_html_e( 'Bundle revenue', 'bundle-product-for-woocommerce' ); ?></h3>
				<div class="bpfw-stat"><?php echo wp_kses_post( wc_price( $stats['revenue'] ) ); ?></div>
			</div>
			<div class="bpfw-stat-card">
				<h3><?php esc_html_e( 'Customer savings given', 'bundle-product-for-woocommerce' ); ?></h3>
				<div class="bpfw-stat"><?php echo wp_kses_post( wc_price( $stats['savings'] ) ); ?></div>
			</div>
			<div class="bpfw-stat-card">
				<h3><?php esc_html_e( 'Top bundle', 'bundle-product-for-woocommerce' ); ?></h3>
				<div class="bpfw-stat"><?php echo $top ? esc_html( $top['name'] ) : '—'; ?></div>
			</div>
		</div>

		<div class="bpfw-overview-grid">
			<section class="bpfw-panel-card">
				<h2>
					<?php
					printf(
						/* translators: %s: number of days. */
						esc_html__( 'Sales by bundle — last %s days', 'bundle-product-for-woocommerce' ),
						esc_html( number_format_i18n( $days ) )
					);
					?>
				</h2>

				<table class="widefat striped bpfw-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Bundle', 'bundle-product-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Sold', 'bundle-product-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Revenue', 'bundle-product-for-woocommerce' ); ?></th>
							<th><?php esc_html_e( 'Share of revenue', 'bundle-product-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( $stats['rows'] ) : ?>
							<?php foreach ( $stats['rows'] as $row ) : ?>
								<?php $bpfw_share = $stats['revenue'] > 0 ? round( ( $row['revenue'] / $stats['revenue'] ) * 100 ) : 0; ?>
								<tr>
									<td><a href="<?php echo esc_url( get_edit_post_link( $row['bundle_id'] ) ); ?>"><?php echo esc_html( $row['name'] ); ?></a></td>
									<td><?php echo esc_html( number_format_i18n( $row['sold'] ) ); ?></td>
									<td><?php echo wp_kses_post( wc_price( $row['revenue'] ) ); ?></td>
									<td>
										<span class="bpfw-share">
											<span class="bpfw-share__bar" aria-hidden="true"><i style="width:<?php echo esc_attr( min( 100, $bpfw_share ) ); ?>%;"></i></span>
											<?php echo esc_html( $stats['revenue'] > 0 ? $bpfw_share . '%' : '—' ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="4"><?php esc_html_e( 'No bundle sales in this period yet.', 'bundle-product-for-woocommerce' ); ?></td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</section>

			<section class="bpfw-panel-card bpfw-help-card">
				<h2><?php esc_html_e( 'Quick start', 'bundle-product-for-woocommerce' ); ?></h2>
				<ol>
					<li><?php esc_html_e( 'Go to Products → Add New and choose the "Bundle product" type.', 'bundle-product-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Open the Bundled Products tab, search and add simple products.', 'bundle-product-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Pick Auto or Fixed pricing and a product page layout.', 'bundle-product-for-woocommerce' ); ?></li>
					<li><?php esc_html_e( 'Publish — pricing, savings badge and stock sync are automatic.', 'bundle-product-for-woocommerce' ); ?></li>
				</ol>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=product' ) ); ?>"><?php esc_html_e( 'Create a bundle', 'bundle-product-for-woocommerce' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=bpfw-bundles&tab=settings' ) ); ?>"><?php esc_html_e( 'Layout & design settings', 'bundle-product-for-woocommerce' ); ?></a>
				</p>
				<p class="description"><?php esc_html_e( 'Display bundles anywhere with the [bundle id="123"] shortcode, the Product Bundle block, or the Elementor widget.', 'bundle-product-for-woocommerce' ); ?></p>
			</section>
		</div>
		<?php
	}
}
