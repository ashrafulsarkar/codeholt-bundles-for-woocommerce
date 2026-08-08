<?php
/**
 * Dismissible admin notice asking for a WordPress.org review after the
 * plugin has been in use for a while.
 *
 * @package CBFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * CBFW_Review_Notice.
 */
class CBFW_Review_Notice {

	const INSTALLED_OPTION  = 'cbfw_installed';
	const DISMISSED_OPTION  = 'cbfw_review_dismissed';
	const LATER_OPTION      = 'cbfw_review_later';
	const NONCE_ACTION      = 'cbfw_review_action';
	const REVIEW_URL        = 'https://wordpress.org/support/plugin/codeholt-bundles-for-woocommerce/reviews/#new-post';

	/**
	 * Days of use before the notice appears (also the snooze length).
	 */
	const SHOW_AFTER_DAYS = 7;

	/**
	 * Hook everything up.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
		add_action( 'wp_ajax_cbfw_dismiss_review', array( $this, 'ajax_dismiss' ) );
	}

	/**
	 * Whether the notice should be rendered for the current request.
	 *
	 * @return bool
	 */
	private function should_show() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( get_option( self::DISMISSED_OPTION ) ) {
			return false;
		}

		$later = (int) get_option( self::LATER_OPTION );
		if ( $later && time() < $later ) {
			return false;
		}

		$installed = (int) get_option( self::INSTALLED_OPTION );
		if ( ! $installed ) {
			// Fallback for installs that predate the option — start counting now.
			update_option( self::INSTALLED_OPTION, time() );
			return false;
		}

		return ( time() - $installed ) >= self::SHOW_AFTER_DAYS * DAY_IN_SECONDS;
	}

	/**
	 * Processes the notice action links (rate / later / dismiss).
	 */
	public function handle_actions() {
		if ( ! isset( $_GET['cbfw_review_action'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_GET['cbfw_review_action'] ) );

		if ( 'rate' === $action ) {
			update_option( self::DISMISSED_OPTION, 1 );
			wp_redirect( self::REVIEW_URL ); // phpcs:ignore WordPress.Security.SafeRedirect -- intentional external redirect to wordpress.org
			exit;
		}

		if ( 'later' === $action ) {
			update_option( self::LATER_OPTION, time() + self::SHOW_AFTER_DAYS * DAY_IN_SECONDS );
		} elseif ( 'dismiss' === $action ) {
			update_option( self::DISMISSED_OPTION, 1 );
		}

		wp_safe_redirect( remove_query_arg( array( 'cbfw_review_action', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Permanently dismisses the notice when the native × button is clicked.
	 */
	public function ajax_dismiss() {
		check_ajax_referer( self::NONCE_ACTION );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		update_option( self::DISMISSED_OPTION, 1 );
		wp_send_json_success();
	}

	/**
	 * Outputs the review notice.
	 */
	public function render_notice() {
		if ( ! $this->should_show() ) {
			return;
		}

		$nonce       = wp_create_nonce( self::NONCE_ACTION );
		$rate_url    = add_query_arg( array( 'cbfw_review_action' => 'rate', '_wpnonce' => $nonce ) );
		$later_url   = add_query_arg( array( 'cbfw_review_action' => 'later', '_wpnonce' => $nonce ) );
		$dismiss_url = add_query_arg( array( 'cbfw_review_action' => 'dismiss', '_wpnonce' => $nonce ) );
		?>
		<div class="notice notice-info is-dismissible cbfw-review-notice">
			<p>
				<strong><?php esc_html_e( 'Enjoying Codeholt Bundles for WooCommerce?', 'codeholt-bundles-for-woocommerce' ); ?></strong><br>
				<?php esc_html_e( 'You have been using it for over a week — that\'s awesome! Could you please do us a big favor and give it a 5-star rating on WordPress.org? It really helps us spread the word and keep improving the plugin.', 'codeholt-bundles-for-woocommerce' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $rate_url ); ?>" class="button button-primary"><?php esc_html_e( 'Ok, you deserve it!', 'codeholt-bundles-for-woocommerce' ); ?></a>
				<a href="<?php echo esc_url( $later_url ); ?>" class="button"><?php esc_html_e( 'Maybe later', 'codeholt-bundles-for-woocommerce' ); ?></a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="cbfw-review-dismiss-link"><?php esc_html_e( 'I already did', 'codeholt-bundles-for-woocommerce' ); ?></a>
			</p>
		</div>
		<script>
			jQuery( function ( $ ) {
				$( document ).on( 'click', '.cbfw-review-notice .notice-dismiss', function () {
					$.post( ajaxurl, {
						action: 'cbfw_dismiss_review',
						_wpnonce: '<?php echo esc_js( $nonce ); ?>'
					} );
				} );
			} );
		</script>
		<?php
	}
}
