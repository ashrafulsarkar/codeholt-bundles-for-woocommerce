<?php
/**
 * Bundle product add-to-cart area.
 *
 * Override this template by copying it to
 * yourtheme/codeholt-bundles-for-woocommerce/single-product/add-to-cart/bundle.php
 *
 * @package CBFW\Templates
 * @version 1.0.0
 *
 * @var CBFW_Product_Bundle $bundle         Bundle product.
 * @var array               $pricing        Pricing summary.
 * @var string              $layout         Layout slug (see cbfw_get_product_layouts()).
 * @var string              $included_title Heading above the items list.
 */

defined( 'ABSPATH' ) || exit;

$cbfw_items  = $bundle->get_bundled_products();
$cbfw_max    = $bundle->get_max_purchasable();
$cbfw_layout = isset( $layout ) && array_key_exists( $layout, cbfw_get_product_layouts() ) ? $layout : cbfw_get_bundle_layout( $bundle );
$cbfw_title  = isset( $included_title ) && '' !== trim( (string) $included_title ) ? $included_title : __( "What's included", 'codeholt-bundles-for-woocommerce' );

if ( ! $cbfw_items ) {
	return;
}

/**
 * Fires before the bundled items list on the product page.
 *
 * @since 1.0.0
 *
 * @param CBFW_Product_Bundle $bundle Bundle product.
 */
do_action( 'cbfw_before_bundled_items', $bundle );
?>

<div class="cbfw-bundled-items cbfw-layout-<?php echo esc_attr( $cbfw_layout ); ?>" aria-label="<?php esc_attr_e( 'Products included in this bundle', 'codeholt-bundles-for-woocommerce' ); ?>">
	<h3 class="cbfw-bundled-items__title"><?php echo esc_html( $cbfw_title ); ?></h3>

	<ul class="cbfw-bundled-items__list">
		<?php foreach ( $cbfw_items as $cbfw_item ) : ?>
			<?php
			if ( $cbfw_item['hidden'] ) {
				continue;
			}
			$cbfw_child = $cbfw_item['product'];
			?>
			<li class="cbfw-bundled-item">
				<span class="cbfw-bundled-item__image">
					<?php echo wp_kses_post( $cbfw_child->get_image( 'woocommerce_gallery_thumbnail' ) ); ?>
				</span>
				<span class="cbfw-bundled-item__info">
					<span class="cbfw-bundled-item__name">
						<?php if ( $cbfw_child->is_visible() ) : ?>
							<a href="<?php echo esc_url( $cbfw_child->get_permalink() ); ?>"><?php echo esc_html( $cbfw_child->get_name() ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $cbfw_child->get_name() ); ?>
						<?php endif; ?>
					</span>
					<span class="cbfw-bundled-item__price"><?php echo wp_kses_post( $cbfw_child->get_price_html() ); ?></span>
				</span>
				<span class="cbfw-bundled-item__qty">&times;<?php echo esc_html( $cbfw_item['qty'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( $pricing['savings'] > 0 ) : ?>
		<div class="cbfw-summary">
			<span class="cbfw-summary__label"><?php esc_html_e( 'Total bundle price:', 'codeholt-bundles-for-woocommerce' ); ?></span>
			<span class="cbfw-summary__prices">
				<del><?php echo wp_kses_post( wc_price( $pricing['regular'] ) ); ?></del>
				<ins><?php echo wp_kses_post( wc_price( $pricing['price'] ) ); ?></ins>
			</span>
		</div>
	<?php endif; ?>
</div>

<?php
/**
 * Fires after the bundled items list on the product page.
 *
 * @since 1.0.0
 *
 * @param CBFW_Product_Bundle $bundle Bundle product.
 */
do_action( 'cbfw_after_bundled_items', $bundle );

if ( ! $bundle->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $bundle ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( $bundle->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name. ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $bundle->get_permalink() ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name. ?>" method="post" enctype="multipart/form-data">

		<?php do_action( 'woocommerce_before_add_to_cart_button' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name. ?>

		<?php
		do_action( 'woocommerce_before_add_to_cart_quantity' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name.

		woocommerce_quantity_input(
			array(
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $bundle->get_min_purchase_quantity(), $bundle ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name.
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', null !== $cbfw_max ? $cbfw_max : $bundle->get_max_purchase_quantity(), $bundle ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name.
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) ) : $bundle->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Add-to-cart form submission is handled and verified by WooCommerce.
			)
		);

		do_action( 'woocommerce_after_add_to_cart_quantity' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name.
		?>

		<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $bundle->get_id() ); ?>" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
			<?php echo esc_html( $bundle->single_add_to_cart_text() ); ?>
		</button>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name. ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce template hook, must keep its original name. ?>

<?php endif; ?>
